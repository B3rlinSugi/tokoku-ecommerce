<?php
require __DIR__.'/../config/config.php';
header('Content-Type: application/json');

try {
  $rows = [];
  $sql = "SELECT s.idsewa, s.nama_cust, s.email, s.notelp, s.kota,
                 s.idadat, k.nama_adat, s.tgl_sewa, s.jenisbayar,
                 s.harga_total, s.statusbayar
          FROM tbsewa s
          LEFT JOIN tbkatalog k ON k.idadat = s.idadat
          ORDER BY s.idsewa DESC";
  $rs = db()->query($sql);
  while ($r = $rs->fetch_assoc()) { $rows[] = $r; }
  echo json_encode(['ok'=>true,'rows'=>$rows]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);
}
