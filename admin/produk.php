<?php
require_once '../config/database.php';
requireAdmin();
$pdo = getDB();

$msg   = '';
$error = '';

$kategoriList = $pdo->query("SELECT * FROM kategori ORDER BY nama_kategori")->fetchAll();

if (isset($_GET['hapus'])) {
    $id   = (int)$_GET['hapus'];
    $prod = $pdo->prepare("SELECT gambar FROM produk WHERE id=?");
    $prod->execute([$id]);
    $prod = $prod->fetch();
    if ($prod && $prod['gambar'] && file_exists(__DIR__.'/../uploads/'.$prod['gambar'])) {
        unlink(__DIR__.'/../uploads/'.$prod['gambar']);
    }
    $pdo->prepare("DELETE FROM produk WHERE id=?")->execute([$id]);
    header('Location: /admin/produk.php?msg=hapus'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)($_POST['id'] ?? 0);
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $harga       = (int)$_POST['harga'];
    $stok        = (int)$_POST['stok'];
    $kategori_id = (int)$_POST['kategori_id'];
    $status      = $_POST['status'] ?? 'aktif';
    $gambar_lama = trim($_POST['gambar_lama'] ?? '');
    $gambar      = $gambar_lama;

    if (!empty($_FILES['gambar']['name'])) {
        $ext   = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
        $allow = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allow)) {
            $error = 'Format gambar tidak didukung! Gunakan JPG, PNG, atau WEBP.';
        } elseif ($_FILES['gambar']['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran gambar maksimal 2MB!';
        } else {
            $namaFile = 'produk_' . time() . '_' . rand(100,999) . '.' . $ext;
            $tujuan   = __DIR__ . '/../uploads/' . $namaFile;
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $tujuan)) {
                if ($gambar_lama && file_exists(__DIR__.'/../uploads/'.$gambar_lama)) unlink(__DIR__.'/../uploads/'.$gambar_lama);
                $gambar = $namaFile;
            }
        }
    }

    if (!$error) {
        if ($id > 0) {
            $pdo->prepare("UPDATE produk SET nama_produk=?,deskripsi=?,harga=?,stok=?,kategori_id=?,status=?,gambar=? WHERE id=?")
                ->execute([$nama_produk,$deskripsi,$harga,$stok,$kategori_id,$status,$gambar,$id]);
            $msg = 'Produk berhasil diperbarui!';
        } else {
            $pdo->prepare("INSERT INTO produk (nama_produk,deskripsi,harga,stok,kategori_id,status,gambar) VALUES (?,?,?,?,?,?,?)")
                ->execute([$nama_produk,$deskripsi,$harga,$stok,$kategori_id,$status,$gambar]);
            $msg = 'Produk berhasil ditambahkan!';
        }
        header('Location: /admin/produk.php?msg=' . urlencode($msg)); exit;
    }
}

if (isset($_GET['msg'])) {
    $msg = $_GET['msg'] === 'hapus' ? 'Produk berhasil dihapus!' : htmlspecialchars($_GET['msg']);
}

$editProduk = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM produk WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduk = $stmt->fetch();
}

$search       = trim($_GET['q'] ?? '');
$filterKat    = (int)($_GET['kat'] ?? 0);
$filterStatus = $_GET['status'] ?? '';
$where        = "WHERE 1=1";
$params       = [];
if ($search)       { $where .= " AND p.nama_produk LIKE ?"; $params[] = "%$search%"; }
if ($filterKat)    { $where .= " AND p.kategori_id=?";      $params[] = $filterKat; }
if ($filterStatus) { $where .= " AND p.status=?";           $params[] = $filterStatus; }

$perPage     = 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produk p LEFT JOIN kategori k ON p.kategori_id=k.id $where");
$countStmt->execute($params);
$totalData  = $countStmt->fetchColumn();
$totalPages = ceil($totalData / $perPage);

$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id=k.id $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$produkList = $stmt->fetchAll();

$pendingCount = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='pending'")->fetchColumn();

function pageUrl($page, $search, $kat, $status) {
    $p = ['page' => $page];
    if ($search) $p['q']      = $search;
    if ($kat)    $p['kat']    = $kat;
    if ($status) $p['status'] = $status;
    return '/admin/produk.php?' . http_build_query($p);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Produk - Admin TokoKu</title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; background: #f5f5f5; font-family: 'Segoe UI', sans-serif; }
.admin-layout { display: flex; min-height: 100vh; }
.admin-sidebar { width: 240px !important; min-height: 100vh !important; background: #1a1a2e !important; position: fixed !important; top: 0 !important; left: 0 !important; bottom: 0 !important; display: flex !important; flex-direction: column !important; z-index: 200 !important; overflow: hidden !important; }
.admin-sidebar .sidebar-brand { background: linear-gradient(135deg, #ee4d2d, #ff6b35) !important; padding: 18px 20px !important; display: flex !important; align-items: center !important; gap: 10px !important; flex-shrink: 0 !important; }
.admin-sidebar .sidebar-brand-icon { font-size: 1.5rem; }
.admin-sidebar .sidebar-brand-text { font-size: 1.1rem; font-weight: 900; color: white !important; line-height: 1.2; }
.admin-sidebar .sidebar-brand-text span { color: #ffe066 !important; }
.admin-sidebar .sidebar-brand-badge { font-size: .6rem; opacity: .85; font-weight: 400; display: block; margin-top: 1px; color: rgba(255,255,255,.85); }
.admin-sidebar .sidebar-nav { flex: 1; overflow-y: auto; padding: 8px 0; }
.admin-sidebar .sidebar-section { padding: 10px 16px 4px; font-size: .62rem; text-transform: uppercase; letter-spacing: 1.2px; color: rgba(255,255,255,.28) !important; font-weight: 700; margin-top: 4px; display: block; }
.admin-sidebar .sidebar-nav a { display: flex !important; align-items: center !important; gap: 10px !important; color: rgba(255,255,255,.6) !important; text-decoration: none !important; padding: 10px 16px !important; font-size: .84rem !important; border-radius: 8px !important; margin: 2px 8px !important; transition: all .2s !important; border: none !important; background: transparent !important; }
.admin-sidebar .sidebar-nav a:hover { background: rgba(238,77,45,.18) !important; color: white !important; }
.admin-sidebar .sidebar-nav a.active { background: linear-gradient(135deg, rgba(238,77,45,.35), rgba(255,107,53,.2)) !important; color: white !important; font-weight: 600 !important; }
.admin-sidebar .sidebar-nav a .menu-icon { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }
.admin-sidebar .sidebar-nav a .badge-count { margin-left: auto; background: #ee4d2d; color: white; font-size: .62rem; padding: 1px 7px; border-radius: 10px; font-weight: 800; }
.admin-sidebar .sidebar-footer { padding: 14px 16px !important; border-top: 1px solid rgba(255,255,255,.08) !important; background: rgba(0,0,0,.2) !important; flex-shrink: 0 !important; }
.admin-sidebar .sidebar-user { display: flex; align-items: center; gap: 10px; }
.admin-sidebar .sidebar-avatar { width: 36px; height: 36px; background: #ee4d2d; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 800; font-size: .95rem; flex-shrink: 0; }
.admin-sidebar .sidebar-user-name { color: white !important; font-size: .82rem; font-weight: 600; }
.admin-sidebar .sidebar-user-role { color: rgba(255,255,255,.38) !important; font-size: .68rem; }
.admin-content { margin-left: 240px !important; min-height: 100vh; width: calc(100% - 240px); }
.admin-topbar { background: white; padding: 14px 24px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
.modal-logout-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 99999; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.modal-logout-box { background: white; border-radius: 16px; padding: 32px; width: 100%; max-width: 360px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,.3); animation: popIn .25s ease; margin: 20px; }
@keyframes popIn { from { transform: scale(.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.card { background: white; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.07); overflow: hidden; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: .8rem; font-weight: 600; color: #424242; margin-bottom: 6px; }
.form-control { width: 100%; padding: 9px 12px; border: 1.5px solid #e0e0e0; border-radius: 8px; font-size: .85rem; transition: border-color .2s; outline: none; background: white; }
.form-control:focus { border-color: #ee4d2d; box-shadow: 0 0 0 3px rgba(238,77,45,.08); }
textarea.form-control { resize: vertical; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 9px 18px; border-radius: 8px; font-size: .85rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all .2s; border: none; }
.btn-primary { background: linear-gradient(135deg,#ee4d2d,#ff6b35); color: white; }
.btn-primary:hover { opacity: .9; transform: translateY(-1px); }
.btn-outline { background: white; color: #ee4d2d; border: 1.5px solid #ee4d2d; }
.btn-outline:hover { background: #fff0ed; }
.btn-danger { background: #ffebee; color: #f44336; border: 1px solid #ffcdd2; }
.btn-sm { padding: 6px 12px; font-size: .78rem; border-radius: 6px; }
.btn-full { width: 100%; }
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: .85rem; font-weight: 500; }
.alert-success { background: #e8f5e9; color: #2e7d32; border-left: 3px solid #00b14f; }
.alert-danger  { background: #ffebee; color: #c62828; border-left: 3px solid #f44336; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
thead th { background: #fafafa; padding: 11px 14px; font-size: .75rem; font-weight: 700; color: #757575; text-align: left; border-bottom: 2px solid #f0f0f0; white-space: nowrap; }
tbody td { padding: 11px 14px; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
tbody tr:hover { background: #fafafa; }
tbody tr:last-child td { border-bottom: none; }
.badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: .72rem; font-weight: 700; }
.badge-success { background: #e8f5e9; color: #00b14f; }
.badge-danger  { background: #ffebee; color: #f44336; }
.badge-info    { background: #e3f2fd; color: #1976d2; }
.pagination { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 16px 0; }
.pagination a, .pagination span { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 8px; font-size: .82rem; font-weight: 600; text-decoration: none; transition: all .2s; }
.pagination a { background: white; color: #424242; border: 1.5px solid #e0e0e0; }
.pagination a:hover { border-color: #ee4d2d; color: #ee4d2d; }
.pagination .active { background: #ee4d2d; color: white; border: 1.5px solid #ee4d2d; }
.pagination .dots { background: transparent; border: none; color: #9e9e9e; cursor: default; }
.pagination-info { text-align: center; font-size: .78rem; color: #9e9e9e; margin-top: 4px; }
</style>
    <link rel="shortcut icon" href="/assets/images/favicon.svg" type="image/svg+xml">
</head>
<body>
<div class="admin-layout">
<aside class="admin-sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-brand-icon">🛒</span>
        <div>
            <div class="sidebar-brand-text">Toko<span>Ku</span></div>
            <span class="sidebar-brand-badge">Admin Panel</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Menu Utama</div>
        <a href="/admin/dashboard.php"><span class="menu-icon">📊</span> Dashboard</a>
        <a href="/admin/produk.php" class="active"><span class="menu-icon">📦</span> Produk</a>
        <a href="/admin/stok.php"><span class="menu-icon">📋</span> Manajemen Stok</a>
        <a href="/admin/pesanan.php">
            <span class="menu-icon">🛒</span> Pesanan
            <?php if ($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?>
        </a>
        <a href="/admin/laporan.php"><span class="menu-icon">📈</span> Laporan</a>
        <a href="/admin/users.php"><span class="menu-icon">👥</span> Manajemen User</a>
        <div class="sidebar-section">Lainnya</div>
        <a href="/index.php" target="_blank"><span class="menu-icon">🏠</span> Lihat Toko</a>
        <a href="#" onclick="bukaModalLogout(); return false;"><span class="menu-icon">🚪</span> Keluar</a>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['nama'],0,1)) ?></div>
            <div>
                <div class="sidebar-user-name"><?= htmlspecialchars(explode(' ',$_SESSION['nama'])[0]) ?></div>
                <div class="sidebar-user-role">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<main class="admin-content">
    <div class="admin-topbar">
        <div>
            <div style="font-size:1.1rem;font-weight:800;color:#212121;">📦 Manajemen Produk</div>
            <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px;">
                <?= $totalData ?> produk ditemukan
                <?php if ($totalPages > 1): ?>· Halaman <?= $currentPage ?>/<?= $totalPages ?><?php endif; ?>
            </div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="/admin/produk.php?tambah=1" class="btn btn-primary btn-sm">+ Tambah Produk</a>
            <span style="font-size:.8rem;color:#9e9e9e;"><?= date('d M Y') ?></span>
        </div>
    </div>

    <div style="padding:20px;">
        <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr <?= ($editProduk || isset($_GET['tambah'])) ? '380px' : '' ?>;gap:20px;align-items:start;">
            <div>
                <!-- Filter -->
                <div class="card" style="padding:14px 16px;margin-bottom:16px;">
                    <form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" name="q" class="form-control" placeholder="🔍 Cari produk..." value="<?= htmlspecialchars($search) ?>" style="max-width:240px;">
                        <select name="kat" class="form-control" style="width:160px;">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= $filterKat==$k['id']?'selected':'' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="form-control" style="width:130px;">
                            <option value="">Semua Status</option>
                            <option value="aktif"    <?= $filterStatus==='aktif'?'selected':''    ?>>Aktif</option>
                            <option value="nonaktif" <?= $filterStatus==='nonaktif'?'selected':'' ?>>Nonaktif</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <?php if ($search || $filterKat || $filterStatus): ?>
                            <a href="/admin/produk.php" class="btn btn-outline btn-sm">Reset</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Tabel -->
                <div class="card">
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th style="text-align:center;">Stok</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php if (empty($produkList)): ?>
                                <tr><td colspan="6" style="text-align:center;padding:40px;color:#9e9e9e;"><div style="font-size:2.5rem;margin-bottom:8px;">📦</div>Belum ada produk.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($produkList as $p): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="width:46px;height:46px;background:#f5f5f5;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;font-size:1.3rem;">
                                                <?php if (!empty($p['gambar']) && file_exists(__DIR__.'/../uploads/'.$p['gambar'])): ?>
                                                    <img src="/uploads/<?= htmlspecialchars($p['gambar']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                                <?php else: ?>📦<?php endif; ?>
                                            </div>
                                            <div>
                                                <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                                <div style="font-size:.72rem;color:#9e9e9e;margin-top:1px;"><?= date('d/m/Y', strtotime($p['created_at'])) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-info" style="font-size:.7rem;"><?= htmlspecialchars($p['nama_kategori'] ?? '-') ?></span></td>
                                    <td style="font-weight:700;color:#ee4d2d;white-space:nowrap;"><?= rupiahFormat($p['harga']) ?></td>
                                    <td style="text-align:center;"><span style="font-weight:800;font-size:.95rem;color:<?= $p['stok']==0?'#f44336':($p['stok']<=5?'#f5a623':'#212121') ?>;"><?= $p['stok'] ?></span></td>
                                    <td><?php if ($p['status']==='aktif'): ?><span class="badge badge-success">Aktif</span><?php else: ?><span class="badge badge-danger">Nonaktif</span><?php endif; ?></td>
                                    <td>
                                        <div style="display:flex;gap:6px;">
                                            <a href="?edit=<?= $p['id'] ?><?= $currentPage>1?'&page='.$currentPage:'' ?>" class="btn btn-outline btn-sm">✏️ Edit</a>
                                            <a href="?hapus=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus produk <?= addslashes(htmlspecialchars($p['nama_produk'])) ?>?')">🗑️</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div style="padding:8px 16px;border-top:1px solid #f0f0f0;">
                        <div class="pagination">
                            <?php if ($currentPage > 1): ?>
                                <a href="<?= pageUrl($currentPage-1, $search, $filterKat, $filterStatus) ?>">←</a>
                            <?php endif; ?>
                            <?php for ($pg = 1; $pg <= $totalPages; $pg++):
                                if ($pg === 1 || $pg === $totalPages || ($pg >= $currentPage-2 && $pg <= $currentPage+2)): ?>
                                    <?php if ($pg === $currentPage): ?>
                                        <span class="active"><?= $pg ?></span>
                                    <?php else: ?>
                                        <a href="<?= pageUrl($pg, $search, $filterKat, $filterStatus) ?>"><?= $pg ?></a>
                                    <?php endif; ?>
                                <?php elseif ($pg === $currentPage-3 || $pg === $currentPage+3): ?>
                                    <span class="dots">...</span>
                                <?php endif; endfor; ?>
                            <?php if ($currentPage < $totalPages): ?>
                                <a href="<?= pageUrl($currentPage+1, $search, $filterKat, $filterStatus) ?>">→</a>
                            <?php endif; ?>
                        </div>
                        <div class="pagination-info">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage, $totalData) ?> dari <?= $totalData ?> produk</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FORM TAMBAH/EDIT -->
            <?php if ($editProduk || isset($_GET['tambah'])): ?>
            <div class="card" style="padding:20px;position:sticky;top:80px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #ee4d2d;">
                    <div style="font-size:.95rem;font-weight:700;"><?= $editProduk ? '✏️ Edit Produk' : '➕ Tambah Produk' ?></div>
                    <a href="/admin/produk.php" style="color:#9e9e9e;text-decoration:none;font-size:1.2rem;line-height:1;">×</a>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($editProduk): ?>
                        <input type="hidden" name="id" value="<?= $editProduk['id'] ?>">
                        <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($editProduk['gambar'] ?? '') ?>">
                    <?php endif; ?>

                    <!-- Preview Gambar -->
                    <div style="margin-bottom:14px;">
                        <div id="previewWrap" style="width:100%;height:160px;background:#f5f5f5;border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:8px;border:2px dashed #e0e0e0;cursor:pointer;transition:border-color .2s;"
                             onclick="document.getElementById('fileGambar').click()"
                             onmouseover="this.style.borderColor='#ee4d2d'"
                             onmouseout="this.style.borderColor='#e0e0e0'">
                            <?php if ($editProduk && !empty($editProduk['gambar']) && file_exists(__DIR__.'/../uploads/'.$editProduk['gambar'])): ?>
                                <img id="previewImg" src="/uploads/<?= htmlspecialchars($editProduk['gambar']) ?>" style="max-width:100%;max-height:160px;object-fit:contain;">
                                <div id="previewPlaceholder" style="display:none;text-align:center;color:#bdbdbd;"><div style="font-size:2rem;">🖼️</div><div style="font-size:.75rem;margin-top:4px;">Klik untuk pilih foto</div></div>
                            <?php else: ?>
                                <div id="previewPlaceholder" style="text-align:center;color:#bdbdbd;pointer-events:none;"><div style="font-size:2.5rem;">🖼️</div><div style="font-size:.75rem;margin-top:6px;">Klik untuk pilih foto</div></div>
                                <img id="previewImg" src="" style="display:none;max-width:100%;max-height:160px;object-fit:contain;">
                            <?php endif; ?>
                        </div>
                        <input type="file" name="gambar" id="fileGambar" accept=".jpg,.jpeg,.png,.webp" style="display:none;" onchange="previewGambar(this)">
                        <div style="display:flex;gap:8px;align-items:center;justify-content:center;">
                            <button type="button" onclick="document.getElementById('fileGambar').click()" class="btn btn-outline btn-sm">📷 Pilih Foto</button>
                            <?php if ($editProduk && !empty($editProduk['gambar'])): ?>
                            <button type="button" onclick="hapusPreview()" class="btn btn-sm" style="background:#ffebee;color:#f44336;border:1px solid #ffcdd2;">🗑️ Hapus</button>
                            <?php endif; ?>
                        </div>
                        <div style="text-align:center;font-size:.72rem;color:#9e9e9e;margin-top:4px;">JPG / PNG / WEBP · Maks 2MB</div>
                    </div>

                    <div class="form-group"><label>Nama Produk *</label><input type="text" name="nama_produk" class="form-control" value="<?= htmlspecialchars($editProduk['nama_produk'] ?? '') ?>" placeholder="Nama produk..." required onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'"></div>
                    <div class="form-group"><label>Deskripsi</label><textarea name="deskripsi" class="form-control" rows="3" placeholder="Deskripsi produk..." onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'"><?= htmlspecialchars($editProduk['deskripsi'] ?? '') ?></textarea></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                        <div class="form-group"><label>Harga *</label><input type="number" name="harga" class="form-control" value="<?= $editProduk['harga'] ?? '' ?>" placeholder="0" min="0" required onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'"></div>
                        <div class="form-group"><label>Stok *</label><input type="number" name="stok" class="form-control" value="<?= $editProduk['stok'] ?? '' ?>" placeholder="0" min="0" required onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'"></div>
                    </div>
                    <div class="form-group"><label>Kategori</label>
                        <select name="kategori_id" class="form-control" onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'">
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriList as $k): ?>
                                <option value="<?= $k['id'] ?>" <?= isset($editProduk['kategori_id']) && $editProduk['kategori_id']==$k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group"><label>Status</label>
                        <select name="status" class="form-control" onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'">
                            <option value="aktif"    <?= ($editProduk['status']??'aktif')==='aktif'?'selected':'' ?>>Aktif</option>
                            <option value="nonaktif" <?= ($editProduk['status']??'')==='nonaktif'?'selected':'' ?>>Nonaktif</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-full" style="margin-top:4px;"><?= $editProduk ? '💾 Simpan Perubahan' : '➕ Tambah Produk' ?></button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>

<div class="modal-logout-overlay" id="modalLogout" onclick="if(event.target===this) tutupModalLogout()">
    <div class="modal-logout-box">
        <div style="width:68px;height:68px;background:#fff0ed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px;">🚪</div>
        <h3 style="font-size:1.1rem;font-weight:800;color:#212121;margin:0 0 8px;">Keluar dari Admin?</h3>
        <p style="font-size:.85rem;color:#757575;margin:0 0 24px;line-height:1.7;">Anda akan keluar dari panel admin TokoKu.<br>Yakin ingin melanjutkan?</p>
        <div style="display:flex;gap:10px;">
            <button onclick="tutupModalLogout()" style="flex:1;padding:11px;border:1.5px solid #e0e0e0;border-radius:8px;background:white;color:#757575;font-size:.88rem;font-weight:600;cursor:pointer;" onmouseover="this.style.borderColor='#ee4d2d';this.style.color='#ee4d2d'" onmouseout="this.style.borderColor='#e0e0e0';this.style.color='#757575'">Batal</button>
            <a href="/logout.php" style="flex:1;padding:11px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border-radius:8px;font-size:.88rem;font-weight:700;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">🚪 Ya, Keluar</a>
        </div>
    </div>
</div>

<script>
function bukaModalLogout(){const m=document.getElementById('modalLogout');m.style.display='flex';document.body.style.overflow='hidden';}
function tutupModalLogout(){const m=document.getElementById('modalLogout');m.style.opacity='0';m.style.transition='opacity 0.2s';setTimeout(()=>{m.style.display='none';m.style.opacity='1';document.body.style.overflow='';},200);}
document.addEventListener('keydown',e=>{if(e.key==='Escape')tutupModalLogout();});

function previewGambar(input) {
    const img = document.getElementById('previewImg');
    const ph  = document.getElementById('previewPlaceholder');
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            img.src = e.target.result;
            img.style.display = 'block';
            if (ph) ph.style.display = 'none';
        };
        r.readAsDataURL(input.files[0]);
    }
}

function hapusPreview() {
    const img = document.getElementById('previewImg');
    const ph  = document.getElementById('previewPlaceholder');
    const fi  = document.getElementById('fileGambar');
    const hl  = document.querySelector('input[name="gambar_lama"]');
    img.src = '';
    img.style.display = 'none';
    if (ph) ph.style.display = 'block';
    if (fi) fi.value = '';
    if (hl) hl.value = '';
}
</script>
</body>
</html>