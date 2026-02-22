<?php
// logic_admin/delete_all_sewa.php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';
header('Content-Type: application/json');

try {
  $conn = db();

  // Info total (opsional)
  $totalRs = $conn->query("SELECT COUNT(*) AS c FROM tbsewa");
  $total   = (int)$totalRs->fetch_assoc()['c'];

  // Cek hanya status yang benar-benar menghalangi penghapusan
  $nonAllowedRs = $conn->query("
  SELECT COUNT(*) AS c
  FROM tbsewa
  WHERE statusbayar NOT IN ('Lunas Full','Dibatalkan')
");
  $nonAllowed = (int)$nonAllowedRs->fetch_assoc()['c'];

  if ($nonAllowed > 0) {
    http_response_code(409);
    echo json_encode([
      'success'  => false,
      'message'  => 'Hanya dapat menghapus jika SEMUA pesanan berstatus "Lunas Full" atau "Dibatalkan". Terdeteksi pesanan yang masih aktif/belum lunas.',
      'blocking' => $nonAllowed,
      'total'    => $total,
    ]);
    exit;
  }

  // Semua baris sudah Lunas Full atau Dibatalkan → aman dihapus
  $conn->query("DELETE FROM tbsewa");
  $conn->query("ALTER TABLE tbsewa AUTO_INCREMENT = 1"); // reset id
  $deleted = $conn->affected_rows;

  echo json_encode([
    'success'       => true,
    'deleted_count' => $deleted,
    'total_before'  => $total,
  ]);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  exit;
}
