<?php
require __DIR__.'/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;

$MAIL = require __DIR__.'/config/mail.php';

$m = new PHPMailer(true);
$m->isSMTP();
$m->Host = $MAIL['host'];
$m->SMTPAuth = true;
$m->Username = $MAIL['username'];
$m->Password = $MAIL['password'];
if ($MAIL['encryption'] === 'ssl') {
  $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  $m->Port = $MAIL['port'] ?? 465;
} else {
  $m->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $m->Port = $MAIL['port'] ?? 587;
}
$m->setFrom($MAIL['from_email'], $MAIL['from_name']);
$m->addAddress($MAIL['username'], 'Test');
$m->Subject = 'Test SMTP Serenity';
$m->Body = 'Halo, ini test.';
$m->isHTML(false);
$m->SMTPDebug = 2;           // hanya untuk debug manual, jangan di endpoint JSON
$m->Debugoutput = 'html';    // biar kebaca di browser

$m->send();
echo 'OK';
