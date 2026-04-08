<?php
// logic_admin/sewa_pay_confirm.php
declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');
ini_set('display_errors','0');

define('APP_DEBUG', isset($_GET['debug']) && $_GET['debug'] === '1');

function respond(array $data, int $status = 200): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}


function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function rupiah(int $n): string { return 'Rp ' . number_format($n, 0, ',', '.'); }
function norm(string $s): string {
    $s = trim((string)$s);
    return function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
}

try {
  $idsewa    = (int)($_POST['idsewa'] ?? 0);
  $pelunasan = isset($_POST['pelunasan']) && $_POST['pelunasan'] == '1';
  if ($idsewa <= 0) throw new RuntimeException('idsewa tidak valid');

  $conn = db();

  // Ambil info lengkap
  $sql = "SELECT s.*, k.nama_adat
          FROM tbsewa s
          LEFT JOIN tbkatalog k ON k.idadat = s.idadat
          WHERE s.idsewa=?";
  $st = $conn->prepare($sql);
  $st->bind_param('i', $idsewa);
  $st->execute();
  $row = $st->get_result()->fetch_assoc();
  $st->close();
  if (!$row) throw new RuntimeException('Pesanan tidak ditemukan');

  $statusNow = norm((string)$row['statusbayar']);   // ← pakai lowercase
  $jenis     = norm((string)$row['jenisbayar']);    // ← pakai lowercase

  $total    = (int)$row['harga_total'];
  $dpAmount = (int)round($total * 0.5);
  $sisa     = max(0, $total - $dpAmount);

  // ====== PELUNASAN DP (hanya jika status sekarang Lunas DP) ======
  if ($pelunasan) {
    if ($statusNow !== 'lunas dp') {
      throw new RuntimeException('Pelunasan hanya untuk pesanan berstatus "Lunas DP".');
    }

    $newStatus = 'Lunas Full';
    $up = $conn->prepare("UPDATE tbsewa SET statusbayar=? WHERE idsewa=?");
    $up->bind_param('si', $newStatus, $idsewa);
    $up->execute();
    $up->close();

    // === PDF kwitansi pelunasan ===
    ob_start(); ?>
    <!doctype html><html><head><meta charset="utf-8"><style>
      body{font-family:DejaVu Sans,Arial,sans-serif;font-size:13px;color:#222}
      h2{margin:0 0 8px 0} table{border-collapse:collapse;width:100%;margin-top:10px}
      th,td{border:1px solid #ddd;padding:8px} th{text-align:left;background:#f5f5f5}
      .right{text-align:right}
    </style>    <link rel="shortcut icon" href="/assets/images/favicon.svg" type="image/svg+xml">
</head><body>
      <h2>Kwitansi Pelunasan DP — #<?= h($row['idsewa']) ?></h2>
      <p>Nama: <?= h($row['nama_cust']) ?> | Email: <?= h($row['email']) ?></p>
      <p>Paket: <?= h($row['nama_adat'] ?? $row['idadat']) ?> | Tgl Sewa: <?= h($row['tgl_sewa']) ?></p>
      <table>
        <tr><th>Rincian</th><th class="right">Nominal</th></tr>
        <tr><td>Harga Paket (Total)</td><td class="right"><?= rupiah($total) ?></td></tr>
        <tr><td>DP (50%) — sudah dibayar</td><td class="right"><?= rupiah($dpAmount) ?></td></tr>
        <tr><td>Pelunasan</td><td class="right"><?= rupiah($sisa) ?></td></tr>
        <tr><th>Total Terbayar</th><th class="right"><?= rupiah($total) ?></th></tr>
      </table>
      <p>Status akhir: <b><?= h($newStatus) ?></b></p>
    </body></html>
    <?php
    $html = ob_get_clean();

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdfData = $dompdf->output();
    $pdfName = 'kwitansi-pelunasan-' . $row['idsewa'] . '.pdf';

    // === Email pelunasan ===
    $MAIL = require __DIR__ . '/../config/mail.php';
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $MAIL['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $MAIL['username'];
    $mail->Password   = $MAIL['password'];
    $mail->SMTPSecure = $MAIL['encryption'];
    $mail->Port       = $MAIL['port'];

    $mail->setFrom($MAIL['from_email'], $MAIL['from_name']);
    $mail->addAddress($row['email'], $row['nama_cust']);
    $mail->isHTML(true);
    $mail->Subject = 'Konfirmasi Pelunasan DP — Pesanan #' . $row['idsewa'];
    $mail->Body = '
      <p>Halo ' . h($row['nama_cust']) . ',</p>
      <p>Kami mengonfirmasi bahwa pelunasan DP untuk pesanan Anda sudah diterima.</p>
      <ul>
        <li>No. Pesanan: <b>#' . h($row['idsewa']) . '</b></li>
        <li>Paket: ' . h($row['nama_adat'] ?? $row['idadat']) . '</li>
        <li>Tanggal Sewa: ' . h($row['tgl_sewa']) . '</li>
        <li>Harga Paket (Total): <b>' . rupiah($total) . '</b></li>
        <li>DP (50%): <b>' . rupiah($dpAmount) . '</b></li>
        <li>Pelunasan: <b>' . rupiah($sisa) . '</b></li>
        <li>Total Terbayar: <b>' . rupiah($total) . '</b></li>
        <li>Status Akhir: <b>' . h($newStatus) . '</b></li>
      </ul>
      <p>Kwitansi pelunasan terlampir pada email ini.</p>
      <p>Terima kasih,<br>Serenity</p>';
    $mail->AltBody = 'Pelunasan DP pesanan #' . $row['idsewa'];
    $mail->addStringAttachment($pdfData, $pdfName, 'base64', 'application/pdf');
    $mail->send();

    echo json_encode(['ok'=>true, 'new_status'=>$newStatus]);
    exit;
  }

  // ====== KONFIRMASI PEMBAYARAN PERTAMA ======
  // HANYA izinkan ketika status = "Menunggu Pembayaran"
  if ($statusNow !== 'menunggu pembayaran') {
    throw new RuntimeException('Konfirmasi pembayaran hanya untuk status "Menunggu Pembayaran".');
  }

  $newStatus = ($jenis === 'dp') ? 'Lunas DP' : 'Lunas Full';
  $up = $conn->prepare("UPDATE tbsewa SET statusbayar=? WHERE idsewa=?");
  $up->bind_param('si', $newStatus, $idsewa);
  $up->execute();
  $up->close();

  echo json_encode(['ok'=>true, 'new_status'=>$newStatus]);
} catch (\Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
}
