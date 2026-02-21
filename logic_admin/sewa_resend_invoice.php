<?php
// logic_admin/sewa_resend_invoice.php
declare(strict_types=1);

require __DIR__ . '/../config/config.php';
require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json');

try {
  $idsewa = (int)($_POST['idsewa'] ?? 0);
  if ($idsewa <= 0) throw new RuntimeException('idsewa tidak valid');

  $conn = db();
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

  // (Generate ulang PDF sama seperti di sewa_confirm.php)
  $html = '<html><body><h1>Invoice #' . $row['idsewa'] . '</h1>…</body></html>'; // ringkas
  $dompdf = new Dompdf();
  $dompdf->loadHtml($html, 'UTF-8');
  $dompdf->setPaper('A4', 'portrait');
  $dompdf->render();
  $pdfData = $dompdf->output();
  $pdfName = 'invoice-' . $row['idsewa'] . '.pdf';

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
  $mail->Subject = 'Invoice Pesanan #' . $row['idsewa'] . ' - Serenity (Kirim Ulang)';
  $mail->isHTML(true);
  $mail->Body    = '<p>Berikut kami kirim ulang invoice pesanan Anda.</p>';
  $mail->AltBody = 'Invoice terlampir.';
  $mail->addStringAttachment($pdfData, $pdfName, 'base64', 'application/pdf');
  $mail->send();

  echo json_encode(['ok' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
