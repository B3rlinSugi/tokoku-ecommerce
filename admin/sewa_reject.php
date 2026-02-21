<?php
// logic_admin/sewa_reject.php
declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php'; // PHPMailer via Composer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');
ini_set('display_errors','0');

function h($v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES, 'UTF-8'); }

try{
  $idsewa = (int)($_POST['idsewa'] ?? 0);
  $alasan = trim((string)($_POST['alasan'] ?? ''));
  if ($idsewa<=0) throw new RuntimeException('idsewa tidak valid.');
  if (mb_strlen($alasan) < 5) throw new RuntimeException('Alasan terlalu singkat.');

  $conn = db();

  // Ambil data sewa + nama adat
  $sql = "SELECT s.*, k.nama_adat
          FROM tbsewa s
          LEFT JOIN tbkatalog k ON k.idadat = s.idadat
          WHERE s.idsewa=?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param('i',$idsewa);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if(!$row) throw new RuntimeException('Pesanan tidak ditemukan.');

  // Update status → Dibatalkan (kalau kamu punya kolom alasan_penolakan, bisa simpan juga di sana)
  $up = $conn->prepare("UPDATE tbsewa SET statusbayar='Dibatalkan' WHERE idsewa=?");
  $up->bind_param('i',$idsewa);
  $up->execute();
  $up->close();

  // Kirim email ke customer
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

  $mail->Subject = 'Pemberitahuan Penolakan Pesanan - Serenity';
  $mail->isHTML(true);
  $mail->Body = '
    <p>Halo '.h($row['nama_cust']).',</p>
    <p>Mohon maaf, pesanan Anda <b>#'.h($row['idsewa']).'</b> dengan detail berikut <b>tidak dapat kami proses</b>:</p>
    <ul>
      <li>Paket: '.h($row['nama_adat'] ?? $row['idadat']).'</li>
      <li>Tanggal Sewa: '.h($row['tgl_sewa']).'</li>
      <li>Total: Rp '.number_format((int)$row['harga_total'],0,',','.').'</li>
    </ul>
    <p><b>Alasan penolakan:</b><br>'.nl2br(h($alasan)).'</p>
    <p>Jika Anda membutuhkan informasi lebih lanjut atau ingin menjadwalkan ulang, silakan balas email ini.</p>
    <p>Terima kasih,<br>Serenity</p>
  ';
  $mail->AltBody = 'Pesanan #'.$row['idsewa'].' ditolak. Alasan: '.$alasan;

  $mail->send();

  echo json_encode(['ok'=>true]);

}catch(Throwable $e){
  http_response_code(500);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()]);
}
