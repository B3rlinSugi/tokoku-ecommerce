<?php
// logic_admin/sewa_confirm.php
declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');
ini_set('display_errors', '0');

function h($v): string
{
  return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}
function rupiah(int $n): string
{
  return 'Rp ' . number_format($n, 0, ',', '.');
}

try {
  $idsewa = (int)($_POST['idsewa'] ?? 0);
  if ($idsewa <= 0) throw new RuntimeException('idsewa tidak valid');

  $conn = db();

  // Ambil detail
  $sql = "SELECT s.*, k.nama_adat
          FROM tbsewa s
          LEFT JOIN tbkatalog k ON k.idadat = s.idadat
          WHERE s.idsewa = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i', $idsewa);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$row) throw new RuntimeException('Data pesanan tidak ditemukan');

  // Ubah status jadi "Menunggu Bayaran" kalau belum
  if ($row['statusbayar'] = 'Menunggu Pembayaran') {
    $up = $conn->prepare("UPDATE tbsewa SET statusbayar='Menunggu Pembayaran' WHERE idsewa=?");
    $up->bind_param('i', $idsewa);
    $up->execute();
    $up->close();
    $row['statusbayar'] = 'Menunggu Bayaran';
  }

  // Hitung breakdown untuk DP (50%)
  $total       = (int)$row['harga_total'];
  $isDP        = (string)$row['jenisbayar'] === 'DP';
  $dpAmount    = $isDP ? (int)round($total * 0.5) : 0;
  $sisaSetelahDP = $isDP ? max(0, $total - $dpAmount) : 0;

  // ========= PDF =========
  ob_start();
?>
  <!doctype html>
  <html>

  <head>
    <meta charset="utf-8">
    <style>
      body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 13px;
        color: #222
      }

      h1 {
        margin: 0 0 6px 0
      }

      table {
        border-collapse: collapse;
        width: 100%;
        margin-top: 10px
      }

      th,
      td {
        border: 1px solid #ddd;
        padding: 8px
      }

      th {
        text-align: left;
        background: #f5f5f5
      }

      .right {
        text-align: right
      }

      .muted {
        color: #666
      }
    </style>
  </head>

  <body>
    <h1>Invoice #<?= h($row['idsewa']) ?></h1>
    <p class="muted">Tanggal: <?= h(date('Y-m-d H:i')) ?></p>

    <h3>Customer</h3>
    <p>
      <?= h($row['nama_cust']) ?><br>
      <?= h($row['email']) ?> • <?= h($row['notelp']) ?><br>
      <?= h($row['kota']) ?>
    </p>

    <h3>Detail Pesanan</h3>
    <table>
      <tr>
        <th>Paket Adat</th>
        <th>Tanggal Sewa</th>
        <th>Jenis Bayar</th>
        <th class="right">Harga</th>
      </tr>
      <tr>
        <td><?= h($row['nama_adat'] ?? $row['idadat']) ?></td>
        <td><?= h($row['tgl_sewa']) ?></td>
        <td><?= h($row['jenisbayar']) ?></td>
        <td class="right"><?= rupiah($total) ?></td>
      </tr>
      <?php if ($isDP): ?>
        <tr>
          <th colspan="3" class="right">DP (50%)</th>
          <th class="right"><?= rupiah($dpAmount) ?></th>
        </tr>
        <tr>
          <th colspan="3" class="right">Sisa setelah DP</th>
          <th class="right"><?= rupiah($sisaSetelahDP) ?></th>
        </tr>
      <?php else: ?>
        <tr>
          <th colspan="3" class="right">Total</th>
          <th class="right"><?= rupiah($total) ?></th>
        </tr>
      <?php endif; ?>
    </table>
    <p class="muted">Status: <?= h($row['statusbayar']) ?></p>
  </body>

  </html>
<?php
  $html = ob_get_clean();
  $dompdf = new Dompdf();
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  $pdfData = $dompdf->output();
  $pdfName = 'invoice-' . $row['idsewa'] . '.pdf';

  // ========= Email =========
  $MAIL = require __DIR__ . '/../config/mail.php';
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = $MAIL['host'];
  $mail->SMTPAuth   = true;
  $mail->Username   = $MAIL['username'];
  $mail->Password   = $MAIL['password'];
  $mail->SMTPSecure = $MAIL['encryption']; // 'tls' / 'ssl'
  $mail->Port       = $MAIL['port'];

  $mail->setFrom($MAIL['from_email'], $MAIL['from_name']);
  $mail->addAddress($row['email'], $row['nama_cust']);
  $mail->isHTML(true);
  $mail->Subject = 'Invoice Pesanan #' . $row['idsewa'] . ' - Serenity';

  $dpInfo = $isDP
    ? "<li>DP (50%): <b>" . rupiah($dpAmount) . "</b></li>
       <li>Sisa setelah DP: <b>" . rupiah($sisaSetelahDP) . "</b></li>"
    : "<li>Total yang harus dibayar: <b>" . rupiah($total) . "</b></li>";

  $mail->Body = '
    <p>Halo ' . h($row['nama_cust']) . ',</p>
    <p>Terima kasih telah melakukan pemesanan. Kami lampirkan invoice pesanan Anda.</p>
    <ul>
      <li>No. Pesanan: <b>#' . h($row['idsewa']) . '</b></li>
      <li>Paket: ' . h($row['nama_adat'] ?? $row['idadat']) . '</li>
      <li>Tanggal Sewa: ' . h($row['tgl_sewa']) . '</li>
      <li>Jenis Pembayaran: ' . h($row['jenisbayar']) . '</li>
      ' . $dpInfo . '
      <li>Status Saat Ini: ' . h($row['statusbayar']) . '</li>
    </ul>
    <p>Silakan lakukan pembayaran sesuai instruksi. Terima kasih.</p>
    <p>Hormat kami,<br>Serenity</p>
  ';

  $mail->AltBody = 'Invoice #' . $row['idsewa'];
  $mail->addStringAttachment($pdfData, $pdfName, 'base64', 'application/pdf');
  $mail->send();

  echo json_encode(['ok' => true, 'new_status' => 'Menunggu Pembayaran']);
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
