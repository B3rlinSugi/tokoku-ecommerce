<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

require __DIR__ . '/../config/config.php';

try {
  $dsn = !empty($DB_SOCKET ?? '')
    ? "mysql:unix_socket={$DB_SOCKET};dbname={$DB_NAME};charset=utf8mb4"
    : "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'DB connect error']);
  exit;
}

// ========== CEK SEWA BERJALAN ==========
$tableSewa = 'tbsewa'; // ganti jika namanya 'sewa'
try {
  // Sesuaikan kriteria "berjalan" sesuai kebutuhanmu:
  // - masih merujuk ke katalog
  // - tanggal_sewa >= hari ini
  // - status_bayar bukan 'Selesai' (atau bukan 'Dibatalkan', dsb.)
  $sqlCheck = "
    SELECT COUNT(*) 
    FROM {$tableSewa} s 
    JOIN tbkatalog k ON k.idadat = s.idadat
    WHERE (s.tgl_sewa IS NULL OR s.tgl_sewa >= CURDATE())
      AND (s.statusbayar IS NULL OR s.statusbayar <> 'Selesai')
  ";
  $hasRunning = (int)$pdo->query($sqlCheck)->fetchColumn();

  if ($hasRunning > 0) {
    http_response_code(409);
    echo json_encode([
      'success'=>false,
      'message'=>'Tidak bisa menghapus semua katalog karena masih ada sewa yang berjalan.'
    ]);
    exit;
  }

  // ========== AMBIL PATH FILE, LALU TRUNCATE ==========
  $paths = $pdo->query("SELECT foto_path FROM tbkatalog")->fetchAll(PDO::FETCH_COLUMN);

  // Jika tabel punya FOREIGN KEY dari tbsewa → tbkatalog (ON DELETE RESTRICT),
  // TRUNCATE akan tetap ditolak. Solusi aman: DELETE + reset AI.
  // Jika kamu YAKIN tidak ada referensi, boleh gunakan TRUNCATE:
  // $pdo->exec("TRUNCATE TABLE tbkatalog");

  // Aman & kompatibel (tanpa matikan FK checks):
  $pdo->exec("DELETE FROM tbkatalog");
  $pdo->exec("ALTER TABLE tbkatalog AUTO_INCREMENT=1");

  // Hapus file fisik
  foreach ($paths as $rel) {
    if (!$rel) continue;
    $abs = dirname(__DIR__) . '/../' . $rel;
    if (is_file($abs)) @unlink($abs);
  }

  echo json_encode(['success'=>true,'message'=>'Seluruh data katalog dihapus dan ID direset.']);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
