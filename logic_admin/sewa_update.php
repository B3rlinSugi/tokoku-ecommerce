<?php
declare(strict_types=1);
require __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
ini_set('display_errors','0');

try{
  $idsewa      = (int)($_POST['idsewa'] ?? 0);
  $nama_cust   = trim($_POST['nama_cust'] ?? '');
  $email       = trim($_POST['email'] ?? '');
  $notelp      = trim($_POST['notelp'] ?? '');
  $kota        = trim($_POST['kota'] ?? '');
  $idadat      = (int)($_POST['idadat'] ?? 0);
  $tgl_sewa    = $_POST['tgl_sewa'] ?? '';
  $jenisbayar  = $_POST['jenisbayar'] ?? '';
  $harga_total = ($_POST['harga_total'] ?? '') === '' ? null : (int)$_POST['harga_total'];
  $statusbayar = $_POST['statusbayar'] ?? '';

  if($idsewa<=0) throw new RuntimeException('idsewa tidak valid.');
  if(!$nama_cust || !$email || !$notelp || !$kota || !$idadat || !$tgl_sewa){
    throw new RuntimeException('Field wajib belum lengkap.');
  }
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$tgl_sewa)){
    throw new RuntimeException('Format tanggal sewa harus YYYY-MM-DD.');
  }
  if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
    throw new RuntimeException('Format email tidak valid.');
  }
  if($harga_total!==null && $harga_total<0){
    throw new RuntimeException('Harga total tidak valid.');
  }

  $conn = db();

  // Update
  $sql = "UPDATE tbsewa
          SET nama_cust=?, email=?, notelp=?, kota=?, idadat=?, tgl_sewa=?, jenisbayar=?, harga_total=?, statusbayar=?
          WHERE idsewa=?";
  $stmt = $conn->prepare($sql);
  // bind: s s s s i s s i? s i
  // karena mysqli tidak mendukung NULL pada bind_param jika tipe i, kita handle manual:
  if($harga_total===null){
    // set ke NULL
    $harga_total_sql = null;
    $stmt->bind_param(
      "ssssissssi",
      $nama_cust, $email, $notelp, $kota,
      $idadat, $tgl_sewa, $jenisbayar, $harga_total_sql, $statusbayar, $idsewa
    );
  }else{
    $stmt->bind_param(
      "ssssissisi",
      $nama_cust, $email, $notelp, $kota,
      $idadat, $tgl_sewa, $jenisbayar, $harga_total, $statusbayar, $idsewa
    );
  }
  $stmt->execute();

  echo json_encode([
    'ok'=>true,
    'row'=>[
      'idsewa'=>$idsewa,
      'nama_cust'=>$nama_cust,
      'email'=>$email,
      'notelp'=>$notelp,
      'kota'=>$kota,
      'idadat'=>$idadat,
      'tgl_sewa'=>$tgl_sewa,
      'jenisbayar'=>$jenisbayar,
      'harga_total'=>$harga_total ?? 0,
      'statusbayar'=>$statusbayar
    ]
  ], JSON_UNESCAPED_UNICODE);

}catch(Throwable $e){
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
