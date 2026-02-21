<?php
declare(strict_types=1);

$DB_HOST = '127.0.0.1';   // atau localhost
$DB_PORT = '3306';        // ganti jika non-standar
$DB_NAME = 'dbrias';      // <<< pakai db yang sama dengan crupdate.php
$DB_USER = 'root';
$DB_PASS = '';
$DB_SOCKET = '';          // kosongkan kalau tidak pakai socket


mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
  $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
  // File ini memang melempar agar tertangkap di login_process.php
  throw new RuntimeException('DB_CONNECTION_FAILED: ' . $e->getMessage(), 0, $e);
}

function db(): mysqli { global $conn; return $conn; }
