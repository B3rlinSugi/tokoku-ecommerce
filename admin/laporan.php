<?php
require_once '../config/database.php';
requireAdmin();
$pdo = getDB();

$bulan     = (int)($_GET['bulan'] ?? date('m'));
$tahun     = (int)($_GET['tahun'] ?? date('Y'));
$bulanNama = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// ===== STATISTIK RINGKASAN =====
$totalPesananBulan = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE MONTH(created_at)=? AND YEAR(created_at)=?");
$totalPesananBulan->execute([$bulan, $tahun]);
$totalPesananBulan = $totalPesananBulan->fetchColumn();

$totalOmzetBulan = $pdo->prepare("SELECT COALESCE(SUM(total_harga),0) FROM pesanan WHERE MONTH(created_at)=? AND YEAR(created_at)=? AND status='selesai'");
$totalOmzetBulan->execute([$bulan, $tahun]);
$totalOmzetBulan = $totalOmzetBulan->fetchColumn();

$totalSelesai = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE MONTH(created_at)=? AND YEAR(created_at)=? AND status='selesai'");
$totalSelesai->execute([$bulan, $tahun]);
$totalSelesai = $totalSelesai->fetchColumn();

$totalBatal = $pdo->prepare("SELECT COUNT(*) FROM pesanan WHERE MONTH(created_at)=? AND YEAR(created_at)=? AND status='dibatalkan'");
$totalBatal->execute([$bulan, $tahun]);
$totalBatal = $totalBatal->fetchColumn();

$avgOrder = $totalSelesai > 0 ? $totalOmzetBulan / $totalSelesai : 0;

// ===== GRAFIK HARIAN =====
$hariDalamBulan = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
$grafikLabels = []; $grafikData = [];
for ($h = 1; $h <= $hariDalamBulan; $h++) {
    $tgl  = sprintf('%04d-%02d-%02d', $tahun, $bulan, $h);
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_harga),0) as total FROM pesanan WHERE DATE(created_at)=? AND status='selesai'");
    $stmt->execute([$tgl]);
    $grafikLabels[] = $h;
    $grafikData[]   = (float)$stmt->fetch()['total'];
}

// ===== PRODUK TERLARIS =====
$produkTerlaris = $pdo->prepare("SELECT dp.nama_produk, SUM(dp.jumlah) as terjual, SUM(dp.subtotal) as pendapatan FROM detail_pesanan dp JOIN pesanan p ON dp.pesanan_id = p.id WHERE MONTH(p.created_at)=? AND YEAR(p.created_at)=? AND p.status='selesai' GROUP BY dp.produk_id, dp.nama_produk ORDER BY terjual DESC LIMIT 10");
$produkTerlaris->execute([$bulan, $tahun]);
$produkTerlaris = $produkTerlaris->fetchAll();

// ===== METODE BAYAR =====
$metodeStats = $pdo->prepare("SELECT metode_bayar, COUNT(*) as jumlah, SUM(total_harga) as total FROM pesanan WHERE MONTH(created_at)=? AND YEAR(created_at)=? GROUP BY metode_bayar");
$metodeStats->execute([$bulan, $tahun]);
$metodeStats = $metodeStats->fetchAll();

// ===== PESANAN BULAN INI (dengan PAGINATION) =====
$perPage     = 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$countPesanan = $pdo->prepare("SELECT COUNT(*) FROM pesanan p JOIN users u ON p.user_id=u.id WHERE MONTH(p.created_at)=? AND YEAR(p.created_at)=?");
$countPesanan->execute([$bulan, $tahun]);
$totalPesananPage = $countPesanan->fetchColumn();
$totalPages       = ceil($totalPesananPage / $perPage);

$pesananBulan = $pdo->prepare("SELECT p.*, u.nama as nama_user FROM pesanan p JOIN users u ON p.user_id = u.id WHERE MONTH(p.created_at)=? AND YEAR(p.created_at)=? ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$pesananBulan->execute([$bulan, $tahun]);
$pesananBulan = $pesananBulan->fetchAll();

$pendingCount = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='pending'")->fetchColumn();

function pageUrlLaporan($page, $bulan, $tahun) {
    return '/admin/laporan.php?' . http_build_query(['bulan' => $bulan, 'tahun' => $tahun, 'page' => $page]);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan - Admin TokoKu</title>
<link rel="stylesheet" href="/assets/css/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<style>
*,*::before,*::after{box-sizing:border-box}
body{margin:0;background:#f5f5f5;font-family:"Segoe UI",sans-serif}
.admin-layout{display:flex;min-height:100vh}
.admin-sidebar{width:240px!important;min-height:100vh!important;background:#1a1a2e!important;position:fixed!important;top:0!important;left:0!important;bottom:0!important;display:flex!important;flex-direction:column!important;z-index:200!important;overflow:hidden!important}
.admin-sidebar .sidebar-brand{background:linear-gradient(135deg,#ee4d2d,#ff6b35)!important;padding:18px 20px!important;display:flex!important;align-items:center!important;gap:10px!important;flex-shrink:0!important}
.admin-sidebar .sidebar-brand-icon{font-size:1.5rem}
.admin-sidebar .sidebar-brand-text{font-size:1.1rem;font-weight:900;color:white!important;line-height:1.2}
.admin-sidebar .sidebar-brand-text span{color:#ffe066!important}
.admin-sidebar .sidebar-brand-badge{font-size:.6rem;opacity:.85;font-weight:400;display:block;margin-top:1px;color:rgba(255,255,255,.85)}
.admin-sidebar .sidebar-nav{flex:1;overflow-y:auto;padding:8px 0}
.admin-sidebar .sidebar-section{padding:10px 16px 4px;font-size:.62rem;text-transform:uppercase;letter-spacing:1.2px;color:rgba(255,255,255,.28)!important;font-weight:700;margin-top:4px;display:block}
.admin-sidebar .sidebar-nav a{display:flex!important;align-items:center!important;gap:10px!important;color:rgba(255,255,255,.6)!important;text-decoration:none!important;padding:10px 16px!important;font-size:.84rem!important;border-radius:8px!important;margin:2px 8px!important;transition:all .2s!important;border:none!important;background:transparent!important}
.admin-sidebar .sidebar-nav a:hover{background:rgba(238,77,45,.18)!important;color:white!important}
.admin-sidebar .sidebar-nav a.active{background:linear-gradient(135deg,rgba(238,77,45,.35),rgba(255,107,53,.2))!important;color:white!important;font-weight:600!important}
.admin-sidebar .sidebar-nav a .menu-icon{font-size:1rem;width:20px;text-align:center;flex-shrink:0}
.admin-sidebar .sidebar-nav a .badge-count{margin-left:auto;background:#ee4d2d;color:white;font-size:.62rem;padding:1px 7px;border-radius:10px;font-weight:800}
.admin-sidebar .sidebar-footer{padding:14px 16px!important;border-top:1px solid rgba(255,255,255,.08)!important;background:rgba(0,0,0,.2)!important;flex-shrink:0!important}
.admin-sidebar .sidebar-user{display:flex;align-items:center;gap:10px}
.admin-sidebar .sidebar-avatar{width:36px;height:36px;background:#ee4d2d;border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:.95rem;flex-shrink:0}
.admin-sidebar .sidebar-user-name{color:white!important;font-size:.82rem;font-weight:600}
.admin-sidebar .sidebar-user-role{color:rgba(255,255,255,.38)!important;font-size:.68rem}
.admin-content{margin-left:240px!important;min-height:100vh;width:calc(100% - 240px)}
.admin-topbar{background:white;padding:14px 24px;border-bottom:1px solid #e0e0e0;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;box-shadow:0 1px 6px rgba(0,0,0,.07)}
.modal-logout-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal-logout-box{background:white;border-radius:16px;padding:32px;width:100%;max-width:360px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:popIn .25s ease;margin:20px}
@keyframes popIn{from{transform:scale(.85);opacity:0}to{transform:scale(1);opacity:1}}
.card{background:white;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);overflow:hidden}
.form-control{padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.85rem;outline:none;background:white}
.form-control:focus{border-color:#ee4d2d}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 18px;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;border:none}
.btn-primary{background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white}
.btn-primary:hover{opacity:.9}
.btn-outline{background:white;color:#ee4d2d;border:1.5px solid #ee4d2d}
.btn-outline:hover{background:#fff0ed}
.btn-sm{padding:6px 12px;font-size:.78rem;border-radius:6px}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700}
.badge-warning{background:#fff8e1;color:#f5a623}
.badge-success{background:#e8f5e9;color:#00b14f}
.badge-danger{background:#ffebee;color:#f44336}
.badge-info{background:#e3f2fd;color:#1976d2}
.badge-gray{background:#f5f5f5;color:#757575}
table{width:100%;border-collapse:collapse;font-size:.85rem}
thead th{background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;white-space:nowrap}
tbody td{padding:11px 14px;border-bottom:1px solid #f5f5f5;vertical-align:middle}
tbody tr:hover{background:#fafafa}
tbody tr:last-child td{border-bottom:none}

/* ===== PAGINATION ===== */
.pagination{display:flex;align-items:center;justify-content:center;gap:6px;padding:16px 0}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .2s}
.pagination a{background:white;color:#424242;border:1.5px solid #e0e0e0}
.pagination a:hover{border-color:#ee4d2d;color:#ee4d2d}
.pagination .active{background:#ee4d2d;color:white;border:1.5px solid #ee4d2d}
.pagination .dots{background:transparent;border:none;color:#9e9e9e;cursor:default}
.pagination-info{text-align:center;font-size:.78rem;color:#9e9e9e;margin-top:4px}

@media print{.admin-sidebar,.admin-topbar,.no-print{display:none!important}.admin-content{margin-left:0!important;width:100%!important}}
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
        <a href="/admin/stok.php"><span class="menu-icon">📋</span> Manajemen Stok</a>
        <a href="/admin/pesanan.php">
            <span class="menu-icon">🛒</span> Pesanan
            <?php if ($pendingCount > 0): ?><span class="badge-count"><?= $pendingCount ?></span><?php endif; ?>
        </a>
        <a href="/admin/laporan.php" class="active"><span class="menu-icon">📈</span> Laporan</a>
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
    <div class="admin-topbar no-print">
        <div>
            <div style="font-size:1.1rem;font-weight:800;color:#212121;">📈 Laporan Penjualan</div>
            <div style="font-size:.75rem;color:#9e9e9e;margin-top:1px;"><?= $bulanNama[$bulan] ?> <?= $tahun ?></div>
        </div>
        <div class="no-print" style="display:flex;align-items:center;gap:10px;">
            <form method="GET" style="display:flex;gap:8px;align-items:center;">
                <select name="bulan" class="form-control" style="width:130px;">
                    <?php for ($i=1;$i<=12;$i++): ?><option value="<?= $i ?>" <?= $bulan==$i?'selected':'' ?>><?= $bulanNama[$i] ?></option><?php endfor; ?>
                </select>
                <select name="tahun" class="form-control" style="width:90px;">
                    <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?><option value="<?= $y ?>" <?= $tahun==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </form>
            <button onclick="window.print()" class="btn btn-outline btn-sm no-print">🖨️ Cetak</button>
        </div>
    </div>

    <div style="padding:20px;">

        <!-- KARTU STATISTIK -->
        <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:20px;">
            <div style="background:linear-gradient(135deg,#ee4d2d,#ff6b35);border-radius:12px;padding:16px;color:white;grid-column:span 2;">
                <div style="font-size:.75rem;opacity:.85;margin-bottom:6px;">💰 Total Omzet Bulan Ini</div>
                <div style="font-size:1.4rem;font-weight:900;"><?= rupiahFormat($totalOmzetBulan) ?></div>
                <div style="font-size:.72rem;opacity:.75;margin-top:4px;">Dari <?= $totalSelesai ?> pesanan selesai</div>
            </div>
            <div style="background:white;border-radius:12px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.07);border-top:3px solid #1976d2;">
                <div style="font-size:.72rem;color:#9e9e9e;margin-bottom:4px;">📦 Total Pesanan</div>
                <div style="font-size:1.4rem;font-weight:900;color:#212121;"><?= $totalPesananBulan ?></div>
                <div style="font-size:.7rem;color:#1976d2;margin-top:4px;">Bulan ini</div>
            </div>
            <div style="background:white;border-radius:12px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.07);border-top:3px solid #00b14f;">
                <div style="font-size:.72rem;color:#9e9e9e;margin-bottom:4px;">✅ Selesai</div>
                <div style="font-size:1.4rem;font-weight:900;color:#212121;"><?= $totalSelesai ?></div>
                <div style="font-size:.7rem;color:#00b14f;margin-top:4px;">Pesanan sukses</div>
            </div>
            <div style="background:white;border-radius:12px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.07);border-top:3px solid #f44336;">
                <div style="font-size:.72rem;color:#9e9e9e;margin-bottom:4px;">❌ Dibatalkan</div>
                <div style="font-size:1.4rem;font-weight:900;color:#212121;"><?= $totalBatal ?></div>
                <div style="font-size:.7rem;color:#f44336;margin-top:4px;">Pesanan batal</div>
            </div>
            <div style="background:white;border-radius:12px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.07);border-top:3px solid #9c27b0;">
                <div style="font-size:.72rem;color:#9e9e9e;margin-bottom:4px;">💵 Rata-rata Order</div>
                <div style="font-size:1rem;font-weight:900;color:#212121;"><?= rupiahFormat($avgOrder) ?></div>
                <div style="font-size:.7rem;color:#9c27b0;margin-top:4px;">Per transaksi</div>
            </div>
        </div>

        <!-- GRAFIK -->
        <div style="display:grid;grid-template-columns:1fr 280px;gap:16px;margin-bottom:20px;">
            <div class="card" style="padding:20px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <div>
                        <div style="font-size:.95rem;font-weight:700;">📊 Grafik Penjualan Harian</div>
                        <div style="font-size:.75rem;color:#9e9e9e;"><?= $bulanNama[$bulan] ?> <?= $tahun ?></div>
                    </div>
                </div>
                <canvas id="grafikHarian" height="90"></canvas>
            </div>
            <div class="card" style="padding:20px;">
                <div style="font-size:.95rem;font-weight:700;margin-bottom:16px;">💳 Metode Pembayaran</div>
                <?php if (empty($metodeStats)): ?>
                    <div style="text-align:center;padding:30px;color:#9e9e9e;font-size:.82rem;">Belum ada data</div>
                <?php else: ?>
                    <canvas id="grafikMetode" height="160"></canvas>
                    <div style="margin-top:14px;display:flex;flex-direction:column;gap:6px;">
                        <?php $mc=['#ee4d2d','#1976d2','#00b14f','#f5a623','#9c27b0'];
                        foreach ($metodeStats as $mi => $m): ?>
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:.78rem;">
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div style="width:10px;height:10px;border-radius:2px;background:<?= $mc[$mi%count($mc)] ?>;"></div>
                                <span style="color:#9e9e9e;"><?= htmlspecialchars($m['metode_bayar']) ?></span>
                            </div>
                            <div><span style="font-weight:700;"><?= $m['jumlah'] ?>x</span> <span style="color:#9e9e9e;font-size:.7rem;"><?= rupiahFormat($m['total']) ?></span></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- PRODUK TERLARIS + TABEL PESANAN -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

            <!-- PRODUK TERLARIS (tidak perlu pagination, max 10) -->
            <div class="card">
                <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;">
                    <div style="font-size:.95rem;font-weight:700;">🏆 Produk Terlaris</div>
                    <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px;"><?= $bulanNama[$bulan] ?> <?= $tahun ?></div>
                </div>
                <?php if (empty($produkTerlaris)): ?>
                    <div style="padding:40px;text-align:center;color:#9e9e9e;"><div style="font-size:2.5rem;margin-bottom:8px;">📦</div><p style="font-size:.82rem;">Belum ada penjualan.</p></div>
                <?php else: ?>
                <div style="overflow-x:auto"><table>
                    <thead><tr>
                        <th style="width:40px;">#</th>
                        <th>Produk</th>
                        <th style="text-align:center;">Terjual</th>
                        <th style="text-align:right;">Pendapatan</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($produkTerlaris as $i => $p): ?>
                    <tr>
                        <td style="text-align:center;">
                            <?php if($i===0):?><span style="font-size:1.2rem;">🥇</span>
                            <?php elseif($i===1):?><span style="font-size:1.2rem;">🥈</span>
                            <?php elseif($i===2):?><span style="font-size:1.2rem;">🥉</span>
                            <?php else:?><span style="font-size:.8rem;color:#9e9e9e;font-weight:600;"><?= $i+1 ?></span><?php endif;?>
                        </td>
                        <td style="font-size:.85rem;font-weight:600;"><?= htmlspecialchars($p['nama_produk']) ?></td>
                        <td style="text-align:center;"><span style="background:#fff0ed;color:#ee4d2d;padding:2px 10px;border-radius:12px;font-size:.78rem;font-weight:700;"><?= $p['terjual'] ?> unit</span></td>
                        <td style="text-align:right;font-weight:700;color:#ee4d2d;font-size:.85rem;"><?= rupiahFormat($p['pendapatan']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <?php endif; ?>
            </div>

            <!-- TABEL PESANAN DENGAN PAGINATION -->
            <div class="card">
                <div style="padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
                    <div>
                        <div style="font-size:.95rem;font-weight:700;">🛒 Pesanan Bulan Ini</div>
                        <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px;">
                            <?= $bulanNama[$bulan] ?> <?= $tahun ?>
                            <?php if ($totalPages > 1): ?>· Hal <?= $currentPage ?>/<?= $totalPages ?><?php endif; ?>
                        </div>
                    </div>
                    <a href="/admin/pesanan.php" style="font-size:.75rem;color:#ee4d2d;text-decoration:none;font-weight:600;" class="no-print">Lihat Semua →</a>
                </div>

                <?php if (empty($pesananBulan)): ?>
                    <div style="padding:40px;text-align:center;color:#9e9e9e;">
                        <div style="font-size:2.5rem;margin-bottom:8px;">🛒</div>
                        <p style="font-size:.82rem;">Belum ada pesanan.</p>
                    </div>
                <?php else: ?>
                <div style="overflow-x:auto"><table>
                    <thead><tr>
                        <th>Pelanggan</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Tgl</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($pesananBulan as $p):
                        $bc=match($p['status']){'pending'=>'badge-warning','selesai'=>'badge-success','dibatalkan'=>'badge-danger','dikirim'=>'badge-info',default=>'badge-gray'};
                    ?>
                    <tr>
                        <td style="font-size:.82rem;font-weight:600;"><?= htmlspecialchars($p['nama_user']) ?></td>
                        <td style="font-weight:700;color:#ee4d2d;font-size:.82rem;white-space:nowrap;"><?= rupiahFormat($p['total_harga']) ?></td>
                        <td><span class="badge <?= $bc ?>" style="font-size:.7rem;"><?= ucfirst($p['status']) ?></span></td>
                        <td style="font-size:.72rem;color:#9e9e9e;"><?= date('d/m',strtotime($p['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                <div style="padding:4px 12px;border-top:1px solid #f0f0f0;" class="no-print">
                    <div class="pagination" style="padding:10px 0;">
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= pageUrlLaporan($currentPage-1, $bulan, $tahun) ?>">←</a>
                        <?php endif; ?>
                        <?php for ($pg = 1; $pg <= $totalPages; $pg++):
                            if ($pg===1 || $pg===$totalPages || ($pg>=$currentPage-2 && $pg<=$currentPage+2)): ?>
                                <?php if ($pg === $currentPage): ?>
                                    <span class="active"><?= $pg ?></span>
                                <?php else: ?>
                                    <a href="<?= pageUrlLaporan($pg, $bulan, $tahun) ?>"><?= $pg ?></a>
                                <?php endif; ?>
                            <?php elseif ($pg===$currentPage-3 || $pg===$currentPage+3): ?>
                                <span class="dots">...</span>
                            <?php endif; endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= pageUrlLaporan($currentPage+1, $bulan, $tahun) ?>">→</a>
                        <?php endif; ?>
                    </div>
                    <div class="pagination-info">
                        <?= $offset+1 ?>–<?= min($offset+$perPage, $totalPesananPage) ?> dari <?= $totalPesananPage ?> pesanan
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</main>
</div>

<script>
new Chart(document.getElementById('grafikHarian').getContext('2d'),{
    type:'line',
    data:{
        labels:[<?= implode(',',$grafikLabels) ?>],
        datasets:[{
            label:'Pendapatan',
            data:[<?= implode(',',$grafikData) ?>],
            borderColor:'#ee4d2d',
            backgroundColor:'rgba(238,77,45,0.08)',
            borderWidth:2.5,
            pointBackgroundColor:'#ee4d2d',
            pointRadius:3,pointHoverRadius:6,
            fill:true,tension:0.4
        }]
    },
    options:{
        responsive:true,
        plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>'Rp '+c.parsed.y.toLocaleString('id-ID')}}},
        scales:{
            y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.04)'},ticks:{callback:val=>val>=1000000?'Rp '+(val/1000000).toFixed(1)+'Jt':'Rp '+(val/1000).toFixed(0)+'K',font:{size:10}}},
            x:{grid:{display:false},ticks:{font:{size:10}}}
        }
    }
});
<?php if(!empty($metodeStats)): ?>
new Chart(document.getElementById('grafikMetode').getContext('2d'),{
    type:'doughnut',
    data:{
        labels:[<?= implode(',',array_map(fn($m)=>'"'.addslashes($m['metode_bayar']).'"',$metodeStats)) ?>],
        datasets:[{
            data:[<?= implode(',',array_map(fn($m)=>$m['jumlah'],$metodeStats)) ?>],
            backgroundColor:['#ee4d2d','#1976d2','#00b14f','#f5a623','#9c27b0'],
            borderWidth:2,borderColor:'#fff'
        }]
    },
    options:{responsive:true,cutout:'60%',plugins:{legend:{display:false},tooltip:{callbacks:{label:c=>c.label+': '+c.parsed+' pesanan'}}}}
});
<?php endif; ?>
</script>

<div class="modal-logout-overlay" id="modalLogout" onclick="if(event.target===this)tutupModalLogout()">
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
function bukaModalLogout(){var m=document.getElementById('modalLogout');m.style.display='flex';document.body.style.overflow='hidden';}
function tutupModalLogout(){var m=document.getElementById('modalLogout');m.style.opacity='0';m.style.transition='opacity .2s';setTimeout(function(){m.style.display='none';m.style.opacity='1';document.body.style.overflow='';},200);}
document.addEventListener('keydown',function(e){if(e.key==='Escape')tutupModalLogout();});
</script>
</body>
</html>