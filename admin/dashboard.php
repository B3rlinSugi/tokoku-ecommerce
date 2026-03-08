<?php
require_once '../config/database.php';
requireAdmin();
$pdo = getDB();

// Fungsi helper aktif menu
function aktifJika($halaman) {
    $current = basename($_SERVER['PHP_SELF'], '.php');
    return $current === $halaman ? 'active' : '';
}

$totalProduk     = $pdo->query("SELECT COUNT(*) FROM produk WHERE status='aktif'")->fetchColumn();
$totalPesanan    = $pdo->query("SELECT COUNT(*) FROM pesanan")->fetchColumn();
$totalPelanggan  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='pelanggan'")->fetchColumn();
$totalPendapatan = $pdo->query("SELECT COALESCE(SUM(total_harga),0) FROM pesanan WHERE status='selesai'")->fetchColumn();
$pesananPending  = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='pending'")->fetchColumn();
$stokMinim       = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= 5 AND status='aktif'")->fetchColumn();
$totalUlasan     = $pdo->query("SELECT COUNT(*) FROM ulasan")->fetchColumn();

$pesananTerbaru = $pdo->query("SELECT p.*, u.nama as nama_user FROM pesanan p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

$grafikData = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl  = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_harga),0) as total FROM pesanan WHERE DATE(created_at)=? AND status='selesai'");
    $stmt->execute([$tgl]);
    $grafikData[] = ['label' => date('d/m', strtotime("-$i days")), 'total' => (float)$stmt->fetch()['total']];
}

$produkMinim = $pdo->query("SELECT * FROM produk WHERE stok <= 5 AND status='aktif' ORDER BY stok ASC LIMIT 5")->fetchAll();

$statusCount = [];
foreach (['pending','diproses','dikirim','selesai','dibatalkan'] as $s) {
    $statusCount[$s] = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='$s'")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin - TokoKu</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<style>
*,*::before,*::after { box-sizing: border-box; }
body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f5f5f5; }
.admin-layout { display: flex; min-height: 100vh; }

/* ===== SIDEBAR PAKSA ===== */
.admin-sidebar {
    width: 240px !important;
    min-height: 100vh !important;
    background: #1a1a2e !important;
    position: fixed !important;
    top: 0 !important; left: 0 !important; bottom: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    z-index: 200 !important;
    overflow: hidden !important;
}
.admin-sidebar .sidebar-brand {
    background: linear-gradient(135deg, #ee4d2d, #ff6b35) !important;
    padding: 18px 20px !important;
    display: flex !important; align-items: center !important; gap: 10px !important;
    flex-shrink: 0 !important;
}
.admin-sidebar .sidebar-brand-icon { font-size: 1.5rem; }
.admin-sidebar .sidebar-brand-text { font-size: 1.1rem; font-weight: 900; color: white !important; line-height: 1.2; }
.admin-sidebar .sidebar-brand-text span { color: #ffe066 !important; }
.admin-sidebar .sidebar-brand-badge { font-size: .6rem; opacity: .85; font-weight: 400; display: block; margin-top: 1px; color: rgba(255,255,255,.85); }
.admin-sidebar .sidebar-nav { flex: 1; overflow-y: auto; padding: 8px 0; }
.admin-sidebar .sidebar-section {
    padding: 10px 16px 4px; font-size: .62rem; text-transform: uppercase;
    letter-spacing: 1.2px; color: rgba(255,255,255,.28) !important;
    font-weight: 700; margin-top: 4px; display: block;
}
.admin-sidebar .sidebar-nav a {
    display: flex !important; align-items: center !important; gap: 10px !important;
    color: rgba(255,255,255,.6) !important; text-decoration: none !important;
    padding: 10px 16px !important; font-size: .84rem !important;
    border-radius: 8px !important; margin: 2px 8px !important;
    transition: all .2s !important; border: none !important; background: transparent !important;
}
.admin-sidebar .sidebar-nav a:hover { background: rgba(238,77,45,.18) !important; color: white !important; }
.admin-sidebar .sidebar-nav a.active {
    background: linear-gradient(135deg, rgba(238,77,45,.35), rgba(255,107,53,.2)) !important;
    color: white !important; font-weight: 600 !important;
}
.admin-sidebar .sidebar-nav a .menu-icon { font-size: 1rem; width: 20px; text-align: center; flex-shrink: 0; }
.admin-sidebar .sidebar-nav a .badge-count {
    margin-left: auto; background: #ee4d2d; color: white;
    font-size: .62rem; padding: 1px 7px; border-radius: 10px; font-weight: 800;
}
.admin-sidebar .sidebar-footer {
    padding: 14px 16px !important;
    border-top: 1px solid rgba(255,255,255,.08) !important;
    background: rgba(0,0,0,.2) !important; flex-shrink: 0 !important;
}
.admin-sidebar .sidebar-user { display: flex; align-items: center; gap: 10px; }
.admin-sidebar .sidebar-avatar {
    width: 36px; height: 36px; background: #ee4d2d; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; font-size: .95rem; flex-shrink: 0;
}
.admin-sidebar .sidebar-user-name { color: white !important; font-size: .82rem; font-weight: 600; }
.admin-sidebar .sidebar-user-role { color: rgba(255,255,255,.38) !important; font-size: .68rem; }

/* ===== CONTENT ===== */
.admin-content { margin-left: 240px !important; min-height: 100vh; width: calc(100% - 240px); }

.admin-topbar {
    background: white; padding: 14px 24px;
    border-bottom: 1px solid #e0e0e0;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
}

/* ===== STATS ===== */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 20px; }
.stat-card {
    background: white; border-radius: 12px; padding: 18px;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
    display: flex; flex-direction: column; gap: 10px;
    transition: transform .2s, box-shadow .2s; overflow: hidden; position: relative;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.12); }
.stat-card.c-orange { border-top: 3px solid #ee4d2d; }
.stat-card.c-green  { border-top: 3px solid #00b14f; }
.stat-card.c-blue   { border-top: 3px solid #1976d2; }
.stat-card.c-purple { border-top: 3px solid #9c27b0; }
.stat-card.c-amber  { border-top: 3px solid #f5a623; }
.stat-card.c-red    { border-top: 3px solid #f44336; }
.stat-card.c-teal   { border-top: 3px solid #009688; }
.stat-card.c-pink   { border-top: 3px solid #e91e63; }
.stat-top { display: flex; align-items: flex-start; justify-content: space-between; }
.stat-icon { width: 46px; height: 46px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
.c-orange .stat-icon { background: #fff0ed; }
.c-green  .stat-icon { background: #e8f5e9; }
.c-blue   .stat-icon { background: #e3f2fd; }
.c-purple .stat-icon { background: #f3e5f5; }
.c-amber  .stat-icon { background: #fff8e1; }
.c-red    .stat-icon { background: #ffebee; }
.c-teal   .stat-icon { background: #e0f2f1; }
.c-pink   .stat-icon { background: #fce4ec; }
.stat-val  { font-size: 1.6rem; font-weight: 900; color: #212121; line-height: 1; }
.stat-lbl  { font-size: .74rem; color: #9e9e9e; font-weight: 500; margin-top: 2px; }
.stat-link { font-size: .72rem; font-weight: 600; text-decoration: none; }
.c-orange .stat-link { color: #ee4d2d; }
.c-green  .stat-link { color: #00b14f; }
.c-blue   .stat-link { color: #1976d2; }
.c-purple .stat-link { color: #9c27b0; }
.c-amber  .stat-link { color: #f5a623; }
.c-red    .stat-link { color: #f44336; }
.c-teal   .stat-link { color: #009688; }
.c-pink   .stat-link { color: #e91e63; }

/* MODAL LOGOUT */
.modal-logout-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.5); z-index: 99999;
    align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
}
.modal-logout-box {
    background: white; border-radius: 16px; padding: 32px;
    width: 100%; max-width: 360px; text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    animation: popIn .25s ease; margin: 20px;
}
@keyframes popIn { from { transform: scale(.85); opacity: 0; } to { transform: scale(1); opacity: 1; } }
</style>
</head>
<body>
<div class="admin-layout">

<!-- SIDEBAR -->
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
        <a href="/admin/dashboard.php" class="active">
            <span class="menu-icon">📊</span> Dashboard
        </a>
        <a href="/admin/produk.php">
            <span class="menu-icon">📦</span> Produk
        </a>
        <a href="/admin/stok.php">
            <span class="menu-icon">📋</span> Manajemen Stok
        </a>
        <a href="/admin/pesanan.php">
            <span class="menu-icon">🛒</span> Pesanan
            <?php if ($pesananPending > 0): ?>
                <span class="badge-count"><?= $pesananPending ?></span>
            <?php endif; ?>
        </a>
        <a href="/admin/laporan.php">
            <span class="menu-icon">📈</span> Laporan
        </a>
        <div class="sidebar-section">Lainnya</div>
        <a href="/index.php" target="_blank">
            <span class="menu-icon">🏠</span> Lihat Toko
        </a>
        <a href="#" onclick="bukaModalLogout(); return false;">
            <span class="menu-icon">🚪</span> Keluar
        </a>
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

<!-- KONTEN -->
<main class="admin-content">
    <div class="admin-topbar">
        <div>
            <div style="font-size:1.1rem;font-weight:800;color:#212121;">📊 Dashboard</div>
            <div style="font-size:.75rem;color:#9e9e9e;margin-top:1px;">Selamat datang, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong> — <?= date('d F Y, H:i') ?> WIB</div>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <?php if ($pesananPending > 0): ?>
                <a href="/admin/pesanan.php" style="padding:5px 14px;border-radius:20px;font-size:.77rem;font-weight:700;text-decoration:none;background:#fff0ed;color:#ee4d2d;border:1px solid #ffd5cc;">🔔 <?= $pesananPending ?> Pesanan Pending</a>
            <?php endif; ?>
            <?php if ($stokMinim > 0): ?>
                <a href="/admin/stok.php" style="padding:5px 14px;border-radius:20px;font-size:.77rem;font-weight:700;text-decoration:none;background:#fff8e1;color:#e65100;border:1px solid #ffe082;">⚠️ <?= $stokMinim ?> Stok Minim</a>
            <?php endif; ?>
            <span style="color:#e0e0e0;">|</span>
            <span style="font-size:.8rem;color:#9e9e9e;"><?= date('d M Y') ?></span>
        </div>
    </div>

    <div style="padding:20px;">

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card c-orange">
                <div class="stat-top">
                    <div><div class="stat-lbl">Total Produk</div><div class="stat-val"><?= $totalProduk ?></div></div>
                    <div class="stat-icon">📦</div>
                </div>
                <a href="/admin/produk.php" class="stat-link">Kelola Produk →</a>
            </div>
            <div class="stat-card c-green">
                <div class="stat-top">
                    <div><div class="stat-lbl">Total Pesanan</div><div class="stat-val"><?= $totalPesanan ?></div></div>
                    <div class="stat-icon">🛒</div>
                </div>
                <a href="/admin/pesanan.php" class="stat-link">Lihat Pesanan →</a>
            </div>
            <div class="stat-card c-blue">
                <div class="stat-top">
                    <div><div class="stat-lbl">Total Pelanggan</div><div class="stat-val"><?= $totalPelanggan ?></div></div>
                    <div class="stat-icon">👥</div>
                </div>
                <span class="stat-link" style="color:#1976d2;">Member aktif</span>
            </div>
            <div class="stat-card c-purple">
                <div class="stat-top">
                    <div><div class="stat-lbl">Total Pendapatan</div><div class="stat-val" style="font-size:1.1rem;"><?= rupiahFormat($totalPendapatan) ?></div></div>
                    <div class="stat-icon">💰</div>
                </div>
                <span class="stat-link" style="color:#9c27b0;">Dari pesanan selesai</span>
            </div>
            <div class="stat-card c-amber">
                <div class="stat-top">
                    <div><div class="stat-lbl">Pesanan Pending</div><div class="stat-val" style="color:<?= $pesananPending>0?'#f5a623':'#212121' ?>;"><?= $pesananPending ?></div></div>
                    <div class="stat-icon">⏳</div>
                </div>
                <a href="/admin/pesanan.php?status=pending" class="stat-link">Proses Sekarang →</a>
            </div>
            <div class="stat-card c-red">
                <div class="stat-top">
                    <div><div class="stat-lbl">Stok Minim</div><div class="stat-val" style="color:<?= $stokMinim>0?'#f44336':'#212121' ?>;"><?= $stokMinim ?></div></div>
                    <div class="stat-icon">⚠️</div>
                </div>
                <a href="/admin/stok.php" class="stat-link">Tambah Stok →</a>
            </div>
            <div class="stat-card c-teal">
                <div class="stat-top">
                    <div><div class="stat-lbl">Total Ulasan</div><div class="stat-val"><?= $totalUlasan ?></div></div>
                    <div class="stat-icon">⭐</div>
                </div>
                <span class="stat-link" style="color:#009688;">Rating dari pembeli</span>
            </div>
            <div class="stat-card c-pink">
                <div class="stat-top">
                    <div><div class="stat-lbl">Pesanan Selesai</div><div class="stat-val"><?= $statusCount['selesai'] ?></div></div>
                    <div class="stat-icon">✅</div>
                </div>
                <span class="stat-link" style="color:#e91e63;">Transaksi sukses</span>
            </div>
        </div>

        <!-- GRAFIK + STATUS -->
        <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;margin-bottom:20px;">
            <div class="card" style="padding:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div>
                        <div style="font-size:.95rem;font-weight:700;">📈 Pendapatan 7 Hari Terakhir</div>
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px;">Berdasarkan pesanan selesai</div>
                    </div>
                    <a href="/admin/laporan.php" class="btn btn-outline btn-sm">Laporan Lengkap</a>
                </div>
                <canvas id="grafikPendapatan" height="100"></canvas>
            </div>
            <div class="card" style="padding:20px;">
                <div style="font-size:.95rem;font-weight:700;margin-bottom:16px;">🥧 Status Pesanan</div>
                <canvas id="grafikStatus" height="180"></canvas>
                <div style="margin-top:14px;display:flex;flex-direction:column;gap:6px;">
                    <?php
                    $si = ['pending'=>['Pending','#f5a623'],'diproses'=>['Diproses','#1976d2'],'dikirim'=>['Dikirim','#9c27b0'],'selesai'=>['Selesai','#00b14f'],'dibatalkan'=>['Dibatalkan','#f44336']];
                    foreach ($si as $k => $v): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:.78rem;">
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:10px;height:10px;border-radius:2px;background:<?= $v[1] ?>;"></div>
                            <span style="color:#9e9e9e;"><?= $v[0] ?></span>
                        </div>
                        <span style="font-weight:700;"><?= $statusCount[$k] ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- PESANAN TERBARU + STOK MINIM -->
        <div style="display:grid;grid-template-columns:1fr 320px;gap:16px;">
            <div class="card">
                <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                    <div style="font-size:.95rem;font-weight:700;">🛒 Pesanan Terbaru</div>
                    <a href="/admin/pesanan.php" style="font-size:.78rem;color:#ee4d2d;text-decoration:none;font-weight:600;">Lihat Semua →</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Kode</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Waktu</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($pesananTerbaru as $p):
                            $bc = match($p['status']) { 'pending'=>'badge-warning','selesai'=>'badge-success','dibatalkan'=>'badge-danger','dikirim'=>'badge-info',default=>'badge-gray' };
                        ?>
                        <tr>
                            <td><code style="font-size:.72rem;background:#f5f5f5;padding:2px 6px;border-radius:4px;"><?= substr($p['kode_pesanan'],0,16) ?>...</code></td>
                            <td style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($p['nama_user']) ?></td>
                            <td style="font-weight:700;color:#ee4d2d;font-size:.85rem;"><?= rupiahFormat($p['total_harga']) ?></td>
                            <td><span class="badge <?= $bc ?>"><?= ucfirst($p['status']) ?></span></td>
                            <td style="font-size:.75rem;color:#9e9e9e;"><?= date('d/m H:i',strtotime($p['created_at'])) ?></td>
                            <td><a href="/admin/pesanan.php?detail=<?= $p['id'] ?>" class="btn btn-primary btn-sm">Detail</a></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                    <div style="font-size:.95rem;font-weight:700;">⚠️ Stok Minim</div>
                    <a href="/admin/stok.php" style="font-size:.78rem;color:#ee4d2d;text-decoration:none;font-weight:600;">Kelola →</a>
                </div>
                <?php if (empty($produkMinim)): ?>
                    <div style="padding:30px;text-align:center;color:#9e9e9e;">
                        <div style="font-size:2.5rem;margin-bottom:8px;">✅</div>
                        <div style="font-size:.82rem;">Semua stok aman!</div>
                    </div>
                <?php else: ?>
                    <div style="padding:12px 16px;display:flex;flex-direction:column;gap:10px;">
                    <?php foreach ($produkMinim as $p): ?>
                        <div style="display:flex;align-items:center;gap:12px;padding:10px;background:#fafafa;border-radius:8px;border:1px solid #f0f0f0;">
                            <div style="width:40px;height:40px;background:#f5f5f5;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.2rem;overflow:hidden;">
                                <?php if (!empty($p['gambar']) && file_exists(__DIR__.'/../uploads/'.$p['gambar'])): ?>
                                    <img src="/uploads/<?= $p['gambar'] ?>" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
                                <?php else: ?>📦<?php endif; ?>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.82rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($p['nama_produk']) ?></div>
                                <div style="font-size:.72rem;color:#9e9e9e;">Stok tersisa</div>
                            </div>
                            <div style="font-size:1.1rem;font-weight:900;color:<?= $p['stok']==0?'#f44336':'#f5a623' ?>;flex-shrink:0;"><?= $p['stok'] ?></div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                    <div style="padding:0 16px 14px;">
                        <a href="/admin/stok.php" class="btn btn-warning btn-full btn-sm">+ Tambah Stok Sekarang</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>
</div>

<!-- MODAL LOGOUT -->
<div class="modal-logout-overlay" id="modalLogout" onclick="if(event.target===this)tutupModalLogout()">
    <div class="modal-logout-box">
        <div style="width:64px;height:64px;background:#fff0ed;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.8rem;margin:0 auto 16px;">🚪</div>
        <h3 style="font-size:1.1rem;font-weight:800;color:#212121;margin-bottom:8px;">Keluar dari Admin?</h3>
        <p style="font-size:.85rem;color:#757575;margin-bottom:24px;line-height:1.6;">Anda akan keluar dari panel admin TokoKu.<br>Yakin ingin melanjutkan?</p>
        <div style="display:flex;gap:10px;">
            <button onclick="tutupModalLogout()" style="flex:1;padding:11px;border:1.5px solid #e0e0e0;border-radius:8px;background:white;color:#757575;font-size:.88rem;font-weight:600;cursor:pointer;" onmouseover="this.style.borderColor='#ee4d2d';this.style.color='#ee4d2d'" onmouseout="this.style.borderColor='#e0e0e0';this.style.color='#757575'">Batal</button>
            <a href="/logout.php" style="flex:1;padding:11px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border-radius:8px;font-size:.88rem;font-weight:700;text-decoration:none;display:flex;align-items:center;justify-content:center;" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">🚪 Ya, Keluar</a>
        </div>
    </div>
</div>

<script>
const grafikLabels = [<?= implode(',', array_map(fn($d) => '"'.$d['label'].'"', $grafikData)) ?>];
const grafikValues = [<?= implode(',', array_map(fn($d) => $d['total'], $grafikData)) ?>];

new Chart(document.getElementById('grafikPendapatan').getContext('2d'), {
    type: 'line',
    data: {
        labels: grafikLabels,
        datasets: [{
            label: 'Pendapatan',
            data: grafikValues,
            borderColor: '#ee4d2d',
            backgroundColor: 'rgba(238,77,45,0.08)',
            borderWidth: 2.5,
            pointBackgroundColor: '#ee4d2d',
            pointRadius: 3, pointHoverRadius: 6,
            fill: true, tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => 'Rp ' + c.parsed.y.toLocaleString('id-ID') } } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' }, ticks: { callback: v => v >= 1000000 ? 'Rp '+(v/1000000).toFixed(1)+'Jt' : 'Rp '+(v/1000).toFixed(0)+'K', font: { size: 10 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 } } }
        }
    }
});

new Chart(document.getElementById('grafikStatus').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'],
        datasets: [{
            data: [<?= $statusCount['pending'] ?>, <?= $statusCount['diproses'] ?>, <?= $statusCount['dikirim'] ?>, <?= $statusCount['selesai'] ?>, <?= $statusCount['dibatalkan'] ?>],
            backgroundColor: ['#f5a623','#1976d2','#9c27b0','#00b14f','#f44336'],
            borderWidth: 2, borderColor: '#fff'
        }]
    },
    options: {
        responsive: true, cutout: '60%',
        plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => c.label + ': ' + c.parsed } } }
    }
});

function bukaModalLogout() {
    var m = document.getElementById('modalLogout');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function tutupModalLogout() {
    var m = document.getElementById('modalLogout');
    m.style.opacity = '0'; m.style.transition = 'opacity .2s';
    setTimeout(function(){ m.style.display='none'; m.style.opacity='1'; document.body.style.overflow=''; }, 200);
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') tutupModalLogout(); });
</script>
</body>
</html>