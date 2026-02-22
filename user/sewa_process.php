<?php
// ROOT/sewa_process.php
declare(strict_types=1);
require __DIR__ . '/config/config.php';

const KAPASITAS_PAKET_PER_TANGGAL = 2;
const STATUS_TIDAK_DIHITUNG = ['Dibatalkan']; // sesuaikan jika perlu

// JSON mode jika ?ajax=1 atau Accept: application/json
$wantJson = isset($_GET['ajax']) || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

function json_out(int $code, array $payload) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($payload);
  exit;
}

try {
  // Ambil POST
  $idadat      = (int)($_POST['idadat'] ?? 0);
  $harga_total = (int)($_POST['harga_total'] ?? 0);
  $nama_cust   = trim($_POST['nama_cust'] ?? '');
  $email       = trim($_POST['email'] ?? '');
  $notelp      = trim($_POST['notelp'] ?? '');
  $kota        = trim($_POST['kota'] ?? '');
  $tgl_sewa    = $_POST['tgl_sewa'] ?? '';
  $jenisbayar  = $_POST['jenisbayar'] ?? '';
  $statusbayar = $_POST['statusbayar'] ?? 'Belum Bayar';

  // Validasi
  if(!$idadat || !$nama_cust || !$email || !$notelp || !$kota || !$tgl_sewa || !$jenisbayar){
    throw new RuntimeException('Mohon lengkapi seluruh field wajib.');
  }
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_sewa)){
    throw new RuntimeException('Format tanggal sewa tidak valid (YYYY-MM-DD).');
  }
  if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    throw new RuntimeException('Format email tidak valid.');
  }

  $conn = db();
  // Disarankan jalankan sekali di DB:
  // ALTER TABLE tbsewa ADD INDEX idx_idadat_tgl (idadat, tgl_sewa);

  // Transaksi + cek kuota dengan FOR UPDATE
  $conn->begin_transaction();

  $whereStatus = '';
  $types = 'is';
  $params = [$idadat, $tgl_sewa];
  if (!empty(STATUS_TIDAK_DIHITUNG)) {
    $placeholders = implode(',', array_fill(0, count(STATUS_TIDAK_DIHITUNG), '?'));
    $whereStatus = " AND statusbayar NOT IN ($placeholders)";
    $types .= str_repeat('s', count(STATUS_TIDAK_DIHITUNG));
    $params = array_merge($params, STATUS_TIDAK_DIHITUNG);
  }

  $sqlCount = "SELECT COUNT(*) AS jml FROM tbsewa
               WHERE idadat=? AND tgl_sewa=? $whereStatus
               FOR UPDATE";
  $stmt = $conn->prepare($sqlCount);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $jml = (int)$stmt->get_result()->fetch_assoc()['jml'];
  $stmt->close();

  if ($jml >= KAPASITAS_PAKET_PER_TANGGAL) {
    $conn->rollback();
    $msg = 'Kuota untuk paket & tanggal tersebut sudah penuh. Silakan pilih tanggal lain.';
    if ($wantJson) json_out(409, ['ok'=>false, 'message'=>$msg]);
    // fallback non-JSON (opsional): echo teks
    die($msg);
  }

  // Insert
  $ins = $conn->prepare("INSERT INTO tbsewa
    (nama_cust,email,notelp,kota,idadat,tgl_sewa,jenisbayar,harga_total,statusbayar)
    VALUES (?,?,?,?,?,?,?,?,?)");
  $ins->bind_param("ssssissis",
    $nama_cust,$email,$notelp,$kota,$idadat,$tgl_sewa,$jenisbayar,$harga_total,$statusbayar
  );
  $ins->execute();
  $newId = $conn->insert_id;
  $ins->close();

  $conn->commit();

  if ($wantJson) json_out(200, ['ok'=>true, 'idsewa'=>$newId]);
  // fallback non-JSON (opsional)
  echo "OK";

} catch (Throwable $e) {
  if (isset($conn) && $conn->errno) { $conn->rollback(); }
  $msg = $e->getMessage();
  if ($wantJson) json_out(400, ['ok'=>false, 'message'=>$msg]);
  http_response_code(400);
  echo "ERROR: ".htmlspecialchars($msg);
}
