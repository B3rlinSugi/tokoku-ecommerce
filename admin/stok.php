<?php
require_once '../config/database.php';
requireAdmin();
$pdo = getDB();

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produk_id  = (int)$_POST['produk_id'];
    $jenis      = $_POST['jenis'];
    $jumlah     = (int)$_POST['jumlah'];
    $keterangan = trim($_POST['keterangan'] ?? '');
    if ($jumlah > 0) {
        if ($jenis === 'masuk') {
            $pdo->prepare("UPDATE produk SET stok = stok + ? WHERE id = ?")->execute([$jumlah, $produk_id]);
        } else {
            $pdo->prepare("UPDATE produk SET stok = GREATEST(0, stok - ?) WHERE id = ?")->execute([$jumlah, $produk_id]);
        }
        $pdo->prepare("INSERT INTO riwayat_stok (produk_id, jenis, jumlah, keterangan) VALUES (?,?,?,?)")
            ->execute([$produk_id, $jenis, $jumlah, $keterangan ?: ($jenis === 'masuk' ? 'Penambahan stok manual' : 'Pengurangan stok manual')]);
        $msg = "Stok berhasil di" . ($jenis === 'masuk' ? 'tambah' : 'kurangi') . "!";
    }
}
$produkList  = $pdo->query("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id ORDER BY p.stok ASC, p.nama_produk ASC")->fetchAll();
$riwayatList = $pdo->query("SELECT r.*, p.nama_produk FROM riwayat_stok r JOIN produk p ON r.produk_id = p.id ORDER BY r.created_at DESC LIMIT 20")->fetchAll();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Stok - Admin TokoKu</title>
<link rel="stylesheet" href="/assets/css/style.css">
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:#f5f5f5;font-family:'Segoe UI',sans-serif}
.admin-layout{display:flex;min-height:100vh}
.admin-sidebar{width:240px!important;min-height:100vh!important;background:#1a1a2e!important;position:fixed!important;top:0!important;left:0!important;bottom:0!important;display:flex!important;flex-direction:column!important;z-index:200!important;overflow:hidden!important;}
.admin-sidebar .sidebar-brand{background:linear-gradient(135deg,#ee4d2d,#ff6b35)!important;padding:18px 20px!important;display:flex!important;align-items:center!important;gap:10px!important;flex-shrink:0!important;}
.admin-sidebar .sidebar-brand-icon{font-size:1.5rem}
.admin-sidebar .sidebar-brand-text{font-size:1.1rem;font-weight:900;color:white!important;line-height:1.2}
.admin-sidebar .sidebar-brand-text span{color:#ffe066!important}
.admin-sidebar .sidebar-brand-badge{font-size:0.6rem;opacity:.85;font-weight:400;display:block;margin-top:1px;color:rgba(255,255,255,.85)}
.admin-sidebar .sidebar-nav{flex:1;overflow-y:auto;padding:8px 0}
.admin-sidebar .sidebar-section{padding:10px 16px 4px;font-size:.62rem;text-transform:uppercase;letter-spacing:1.2px;color:rgba(255,255,255,.28)!important;font-weight:700;margin-top:4px;display:block;}
.admin-sidebar .sidebar-nav a{display:flex!important;align-items:center!important;gap:10px!important;color:rgba(255,255,255,.6)!important;text-decoration:none!important;padding:10px 16px!important;font-size:.84rem!important;border-radius:8px!important;margin:2px 8px!important;transition:all .2s!important;border:none!important;background:transparent!important;}
.admin-sidebar .sidebar-nav a:hover{background:rgba(238,77,45,.18)!important;color:white!important}
.admin-sidebar .sidebar-nav a.active{background:linear-gradient(135deg,rgba(238,77,45,.35),rgba(255,107,53,.2))!important;color:white!important;font-weight:600!important;}
.admin-sidebar .sidebar-nav a .menu-icon{font-size:1rem;width:20px;text-align:center;flex-shrink:0}
.admin-sidebar .sidebar-nav a .badge-count{margin-left:auto;background:#ee4d2d;color:white;font-size:.62rem;padding:1px 7px;border-radius:10px;font-weight:800;}
.admin-sidebar .sidebar-footer{padding:14px 16px!important;border-top:1px solid rgba(255,255,255,.08)!important;background:rgba(0,0,0,.2)!important;flex-shrink:0!important;}
.admin-sidebar .sidebar-user{display:flex;align-items:center;gap:10px}
.admin-sidebar .sidebar-avatar{width:36px;height:36px;background:#ee4d2d;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:.95rem;flex-shrink:0;}
.admin-sidebar .sidebar-user-name{color:white!important;font-size:.82rem;font-weight:600}
.admin-sidebar .sidebar-user-role{color:rgba(255,255,255,.38)!important;font-size:.68rem}
.admin-content{margin-left:240px!important;min-height:100vh;width:calc(100% - 240px)}
.admin-topbar{background:white;padding:14px 24px;border-bottom:1px solid #e0e0e0;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 6px rgba(0,0,0,.07);}
.modal-logout-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-logout-box{background:white;border-radius:16px;padding:32px;width:100%;max-width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:popIn .25s ease;margin:20px;}
@keyframes popIn{from{transform:scale(.85);opacity:0}to{transform:scale(1);opacity:1}}
</style>
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
        <a href="/admin/produk.php"><span class="menu-icon">📦</span> Produk</a>
        <a href="/admin/stok.php" class="active"><span class="menu-icon">📋</span> Manajemen Stok</a>
        <a href="/admin/pesanan.php">
            <span class="menu-icon">🛒</span> Pesanan
            <?php if ($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?>
        </a>
        <a href="/admin/laporan.php"><span class="menu-icon">📈</span> Laporan</a>
        <a href="/admin/users.php"><span class="menu-icon">👥</span> Manajemen User</a>
        <div class="sidebar-section">Lainnya</div>
        <a href="/index.php" target="_blank"><span class="menu-icon">🏠</span> Lihat Toko</a>
        <a href="#" onclick="bukaModalLogout();return false;"><span class="menu-icon">🚪</span> Keluar</a>
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
            <div style="font-size:1.1rem;font-weight:800;color:#212121;">📋 Manajemen Stok</div>
            <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px;"><?= count($produkList) ?> produk terdaftar &nbsp;·&nbsp; <?= date('d M Y') ?></div>
        </div>
    </div>

    <div style="padding:20px">
        <?php if ($msg): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:start">

            <div class="card">
                <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                    <div style="font-size:.9rem;font-weight:700;">📦 Daftar Stok Produk</div>
                    <input type="text" id="searchStok" class="form-control" placeholder="🔍 Cari produk..." onkeyup="filterStok()" style="max-width:220px;">
                </div>
                <div class="table-wrap">
                    <table id="tableStok">
                        <thead><tr><th>Produk</th><th>Kategori</th><th style="text-align:center">Stok</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($produkList as $p): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:38px;height:38px;background:#f5f5f5;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
                                        <?php if (!empty($p['gambar']) && file_exists(__DIR__.'/../uploads/'.$p['gambar'])): ?>
                                            <img src="/uploads/<?= $p['gambar'] ?>" style="width:100%;height:100%;object-fit:cover;border-radius:6px">
                                        <?php else: ?>📦<?php endif; ?>
                                    </div>
                                    <div style="font-weight:600;font-size:.84rem"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                </div>
                            </td>
                            <td><span class="badge badge-info" style="font-size:.72rem"><?= htmlspecialchars($p['nama_kategori'] ?? '-') ?></span></td>
                            <td style="text-align:center">
                                <span style="font-size:1.1rem;font-weight:800;color:<?= $p['stok']==0?'#f44336':($p['stok']<=5?'#f5a623':'#00b14f') ?>">
                                    <?= $p['stok'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($p['stok']==0): ?><span class="badge badge-danger">Habis</span>
                                <?php elseif ($p['stok']<=5): ?><span class="badge badge-warning">Minim</span>
                                <?php elseif ($p['stok']<=15): ?><span class="badge badge-info">Cukup</span>
                                <?php else: ?><span class="badge badge-success">Aman</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="isiFormStok(<?= $p['id'] ?>,'<?= htmlspecialchars(addslashes($p['nama_produk'])) ?>',<?= $p['stok'] ?>)" class="btn btn-primary btn-sm">+ Kelola</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:16px">
                <div class="card" style="padding:20px" id="formStokCard">
                    <div style="font-size:.9rem;font-weight:700;margin-bottom:16px;padding-bottom:10px;border-bottom:2px solid #ee4d2d">✏️ Kelola Stok</div>
                    <form method="POST">
                        <div class="form-group">
                            <label>Produk</label>
                            <select name="produk_id" id="selectProduk" class="form-control" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($produkList as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nama_produk']) ?> (Stok: <?= $p['stok'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div id="stokSaatIni" style="display:none;background:#f9f9f9;border-radius:8px;padding:12px;margin-bottom:14px;text-align:center">
                            <div style="font-size:.75rem;color:#757575">Stok Saat Ini</div>
                            <div id="stokAngka" style="font-size:2rem;font-weight:900;color:#212121">0</div>
                        </div>
                        <div class="form-group">
                            <label>Jenis Perubahan</label>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                                <label id="labelMasuk" style="border:2px solid #00b14f;border-radius:8px;padding:10px;cursor:pointer;display:flex;align-items:center;gap:8px;background:#e8f5e9;transition:all .2s">
                                    <input type="radio" name="jenis" value="masuk" checked onchange="highlightJenis('masuk')" style="accent-color:#00b14f">
                                    <span>📥 Stok Masuk</span>
                                </label>
                                <label id="labelKeluar" style="border:2px solid #e8e8e8;border-radius:8px;padding:10px;cursor:pointer;display:flex;align-items:center;gap:8px;transition:all .2s">
                                    <input type="radio" name="jenis" value="keluar" onchange="highlightJenis('keluar')" style="accent-color:#ee4d2d">
                                    <span>📤 Stok Keluar</span>
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Jumlah</label>
                            <input type="number" name="jumlah" class="form-control" placeholder="0" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Keterangan (Opsional)</label>
                            <input type="text" name="keterangan" class="form-control" placeholder="Misal: Restok dari supplier...">
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">💾 Simpan Perubahan Stok</button>
                    </form>
                </div>

                <div class="card">
                    <div style="padding:14px 16px;border-bottom:1px solid #f0f0f0">
                        <div style="font-size:.9rem;font-weight:700">🕐 Riwayat Terbaru</div>
                    </div>
                    <div style="max-height:320px;overflow-y:auto">
                        <?php if (empty($riwayatList)): ?>
                            <div style="padding:30px;text-align:center;color:#9e9e9e;font-size:.82rem">Belum ada riwayat.</div>
                        <?php endif; ?>
                        <?php foreach ($riwayatList as $r): ?>
                        <div style="padding:10px 16px;border-bottom:1px solid #f5f5f5;display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $r['jenis']==='masuk'?'#e8f5e9':'#fce4ec' ?>">
                                <?= $r['jenis']==='masuk'?'📥':'📤' ?>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="font-size:.8rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($r['nama_produk']) ?></div>
                                <div style="font-size:.72rem;color:#9e9e9e"><?= htmlspecialchars($r['keterangan']) ?></div>
                            </div>
                            <div style="text-align:right;flex-shrink:0">
                                <div style="font-weight:800;font-size:.9rem;color:<?= $r['jenis']==='masuk'?'#00b14f':'#ee4d2d' ?>"><?= $r['jenis']==='masuk'?'+':'-' ?><?= $r['jumlah'] ?></div>
                                <div style="font-size:.68rem;color:#bdbdbd"><?= date('d/m H:i',strtotime($r['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</div>

<div class="modal-logout-overlay" id="modalLogout" onclick="if(event.target===this)tutupModalLogout()">
    <div class="modal-logout-box">
        <div style="width:68px;height:68px;background:#fff0ed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2rem;margin:0 auto 16px">🚪</div>
        <h3 style="font-size:1.1rem;font-weight:800;color:#212121;margin:0 0 8px">Keluar dari Admin?</h3>
        <p style="font-size:.85rem;color:#757575;margin:0 0 24px;line-height:1.7">Anda akan keluar dari panel admin TokoKu.<br>Yakin ingin melanjutkan?</p>
        <div style="display:flex;gap:10px">
            <button onclick="tutupModalLogout()" style="flex:1;padding:11px;border:1.5px solid #e0e0e0;border-radius:8px;background:white;color:#757575;font-size:.88rem;font-weight:600;cursor:pointer" onmouseover="this.style.borderColor='#ee4d2d';this.style.color='#ee4d2d'" onmouseout="this.style.borderColor='#e0e0e0';this.style.color='#757575'">Batal</button>
            <a href="/logout.php" style="flex:1;padding:11px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border-radius:8px;font-size:.88rem;font-weight:700;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">🚪 Ya, Keluar</a>
        </div>
    </div>
</div>

<script>
function isiFormStok(id,nama,stok){
    document.getElementById('selectProduk').value=id;
    document.getElementById('stokSaatIni').style.display='block';
    var a=document.getElementById('stokAngka');
    a.textContent=stok;
    a.style.color=stok==0?'#f44336':stok<=5?'#f5a623':'#00b14f';
    document.getElementById('formStokCard').scrollIntoView({behavior:'smooth'});
}
function filterStok(){
    var kw=document.getElementById('searchStok').value.toLowerCase();
    document.querySelectorAll('#tableStok tbody tr').forEach(function(r){
        r.style.display=r.textContent.toLowerCase().includes(kw)?'':'none';
    });
}
function highlightJenis(val){
    var lM=document.getElementById('labelMasuk'),lK=document.getElementById('labelKeluar');
    lM.style.borderColor=val==='masuk'?'#00b14f':'#e8e8e8';
    lM.style.background=val==='masuk'?'#e8f5e9':'white';
    lK.style.borderColor=val==='keluar'?'#ee4d2d':'#e8e8e8';
    lK.style.background=val==='keluar'?'#fce4ec':'white';
}
function bukaModalLogout(){
    var m=document.getElementById('modalLogout');
    m.style.display='flex';
    document.body.style.overflow='hidden';
}
function tutupModalLogout(){
    var m=document.getElementById('modalLogout');
    m.style.opacity='0';m.style.transition='opacity .2s';
    setTimeout(function(){m.style.display='none';m.style.opacity='1';document.body.style.overflow='';},200);
}
document.addEventListener('keydown',function(e){if(e.key==='Escape')tutupModalLogout();});
</script>
</body>
</html>