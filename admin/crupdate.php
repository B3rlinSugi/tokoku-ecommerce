<?php
// logic_admin/crupdate.php
session_start();
header('Content-Type: application/json');

// ========== Proteksi dasar ==========
if (empty($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success'=>false, 'message'=>'Unauthorized']);
  exit;
}

// (Opsional) CSRF
// if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? null)) {
//   http_response_code(400);
//   echo json_encode(['success'=>false, 'message'=>'Invalid CSRF token']);
//   exit;
// }

// ========== Koneksi DB ==========
try {
  // GANTI dsn/user/pass sesuai environment kamu
  $pdo = new PDO('mysql:host=localhost;dbname=dbrias;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false, 'message'=>'DB connect error']);
  exit;
}

// ========== Helper upload ==========
function upload_gambar($field = 'foto_file'){
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
    return [null, null]; // tidak ada upload
  }

  $file = $_FILES[$field];

  // Batas ukuran (2MB)
  $max = 2*1024*1024;
  if ($file['size'] > $max) {
    throw new RuntimeException('Ukuran gambar maksimal 2MB');
  }

  // Validasi MIME
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($file['tmp_name']);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
  ];
  if (!isset($allowed[$mime])) {
    throw new RuntimeException('Tipe gambar harus JPG/PNG/WEBP');
  }

  // Nama file unik
  $ext   = $allowed[$mime];
  $fname = bin2hex(random_bytes(8)).'.'.$ext;

  // Folder upload (root/uploads/adat)
  $dirRoot = dirname(__DIR__);            // ke root proyek
  $uploadDir = $dirRoot.'/uploads/adat';  // pastikan bisa ditulis
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  $absTarget = $uploadDir.'/'.$fname;
  if (!move_uploaded_file($file['tmp_name'], $absTarget)) {
    throw new RuntimeException('Gagal menyimpan file upload');
  }

  // Path publik yang disimpan ke DB
  $publicPath = 'uploads/adat/'.$fname;
  return [$publicPath, $absTarget];
}

function hapus_file_absolut($absPath){
  if ($absPath && is_file($absPath)) @unlink($absPath);
}

// ========== Ambil input ==========
$idadat    = isset($_POST['idadat']) ? (int)$_POST['idadat'] : 0; // 0 => CREATE
$nama_adat = trim($_POST['nama_adat'] ?? '');
$isi_desk  = trim($_POST['isi_desk']  ?? '');
$harga     = trim($_POST['harga']     ?? '');

// ========== CREATE ==========
if ($idadat === 0) {
  if ($nama_adat === '' || $harga === '') {
    http_response_code(422);
    echo json_encode(['success'=>false, 'message'=>'Nama adat dan harga wajib diisi']);
    exit;
  }

  try {
    // Wajib ada gambar saat create
    [$foto_path, $absBaru] = upload_gambar('foto_file');
    if (!$foto_path) {
      http_response_code(422);
      echo json_encode(['success'=>false, 'message'=>'File gambar wajib diunggah']);
      exit;
    }

    $stmt = $pdo->prepare("
      INSERT INTO tbkatalog (nama_adat, foto_path, isi_desk, harga)
      VALUES (:nama, :foto, :desk, :harga)
    ");
    $stmt->execute([
      ':nama'  => $nama_adat,
      ':foto'  => $foto_path,
      ':desk'  => $isi_desk,
      ':harga' => (int)$harga
    ]);

    echo json_encode(['success'=>true, 'message'=>'Tambah data berhasil']);
  } catch (Throwable $e) {
    // Hapus file kalau DB gagal
    if (!empty($absBaru)) hapus_file_absolut($absBaru);
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
  }
  exit;
}

// ========== UPDATE ==========
try {
  // Cek data lama (untuk tahu foto lama)
  $stmt = $pdo->prepare("SELECT foto_path FROM tbkatalog WHERE idadat = :id LIMIT 1");
  $stmt->execute([':id' => $idadat]);
  $old = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$old) {
    http_response_code(404);
    echo json_encode(['success'=>false, 'message'=>'Data tidak ditemukan']);
    exit;
  }

  $sets = [];
  $params = [':id' => $idadat];

  if ($nama_adat !== '') { $sets[] = 'nama_adat = :nama'; $params[':nama'] = $nama_adat; }
  if ($isi_desk  !== '') { $sets[] = 'isi_desk  = :desk'; $params[':desk'] = $isi_desk; }
  if ($harga     !== '') {
    if (!ctype_digit($harga)) {
      http_response_code(422);
      echo json_encode(['success'=>false, 'message'=>'Harga tidak valid']);
      exit;
    }
    $sets[] = 'harga = :harga'; 
    $params[':harga'] = (int)$harga;
  }

  // Upload gambar baru jika ada
  $absBaru = null;
  [$foto_path_baru, $absBaru] = upload_gambar('foto_file'); // null kalau tidak upload
  if ($foto_path_baru) {
    $sets[] = 'foto_path = :foto';
    $params[':foto'] = $foto_path_baru;
  }

  if (empty($sets)) {
    echo json_encode(['success'=>true, 'message'=>'Tidak ada perubahan']);
    exit;
  }

  $sql = "UPDATE tbkatalog SET ".implode(', ', $sets)." WHERE idadat = :id";
  $up  = $pdo->prepare($sql);
  $up->execute($params);

  // Jika ganti gambar, hapus file lama
  if ($foto_path_baru && !empty($old['foto_path'])) {
    $absLama = dirname(__DIR__).'/'.$old['foto_path'];
    hapus_file_absolut($absLama);
  }

  echo json_encode(['success'=>true, 'message'=>'Update berhasil']);
} catch (Throwable $e) {
  // Jika file baru sudah tersimpan tapi DB gagal, hapus file baru
  if (!empty($absBaru)) hapus_file_absolut($absBaru);
  http_response_code(500);
  echo json_encode(['success'=>false, 'message'=>'Error: '.$e->getMessage()]);
}
