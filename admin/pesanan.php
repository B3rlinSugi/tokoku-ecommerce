<?php
require_once '../config/database.php';
requireAdmin();
$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE pesanan SET status = ? WHERE id = ?")->execute([$_POST['status'], (int)$_POST['pesanan_id']]);
    $p = $pdo->prepare("SELECT * FROM pesanan WHERE id = ?");
    $p->execute([(int)$_POST['pesanan_id']]);
    $pesanan = $p->fetch();
    $pesanMap = [
        'diproses'   => 'Pesanan #'.$pesanan['kode_pesanan'].' sedang diproses oleh penjual.',
        'dikirim'    => 'Pesanan #'.$pesanan['kode_pesanan'].' sudah dikirim! Mohon ditunggu.',
        'selesai'    => 'Pesanan #'.$pesanan['kode_pesanan'].' telah selesai. Terima kasih sudah belanja!',
        'dibatalkan' => 'Pesanan #'.$pesanan['kode_pesanan'].' telah dibatalkan.',
    ];
    if (isset($pesanMap[$_POST['status']])) {
        $pdo->prepare("INSERT INTO notifikasi (user_id, judul, pesan, tipe) VALUES (?,?,?,?)")
            ->execute([$pesanan['user_id'], 'Update Pesanan #'.$pesanan['kode_pesanan'], $pesanMap[$_POST['status']], 'pesanan']);
    }
    header('Location: /admin/pesanan.php?msg=updated'); exit;
}

$msg          = isset($_GET['msg']) ? 'Status pesanan berhasil diperbarui!' : '';
$filterStatus = $_GET['status'] ?? '';
$search       = trim($_GET['q'] ?? '');

$perPage     = 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$where  = "WHERE 1=1";
$params = [];
if ($filterStatus) { $where .= " AND p.status = ?"; $params[] = $filterStatus; }
if ($search)       { $where .= " AND (p.kode_pesanan LIKE ? OR u.nama LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM pesanan p JOIN users u ON p.user_id = u.id $where");
$countStmt->execute($params);
$totalData  = $countStmt->fetchColumn();
$totalPages = ceil($totalData / $perPage);

$pesananList = $pdo->prepare("SELECT p.*, u.nama as nama_user, u.email, u.telepon FROM pesanan p JOIN users u ON p.user_id = u.id $where ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$pesananList->execute($params);
$pesananList = $pesananList->fetchAll();

$detailPesanan = null; $detailItems = [];
if (isset($_GET['detail'])) {
    $stmt = $pdo->prepare("SELECT p.*, u.nama as nama_user, u.email, u.telepon, u.alamat FROM pesanan p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
    $stmt->execute([(int)$_GET['detail']]);
    $detailPesanan = $stmt->fetch();
    if ($detailPesanan) {
        $stmtItem = $pdo->prepare("SELECT * FROM detail_pesanan WHERE pesanan_id = ?");
        $stmtItem->execute([$detailPesanan['id']]);
        $detailItems = $stmtItem->fetchAll();
    }
}

$statusCounts = [];
foreach (['pending','diproses','dikirim','selesai','dibatalkan'] as $s) {
    $statusCounts[$s] = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='$s'")->fetchColumn();
}

function pageUrl($page, $status, $search) {
    $params = ['page' => $page];
    if ($status) $params['status'] = $status;
    if ($search) $params['q'] = $search;
    return '/admin/pesanan.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen Pesanan - Admin TokoKu</title>
<link rel="stylesheet" href="/assets/css/style.css">
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
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(4px)}
.modal-box{background:white;border-radius:12px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3)}
.modal-header{padding:18px 20px;border-bottom:1px solid #e8e8e8;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:white;z-index:1}
.modal-body{padding:20px}
.pagination{display:flex;align-items:center;justify-content:center;gap:6px;padding:16px 0}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .2s}
.pagination a{background:white;color:#424242;border:1.5px solid #e0e0e0}
.pagination a:hover{border-color:#ee4d2d;color:#ee4d2d}
.pagination .active{background:#ee4d2d;color:white;border:1.5px solid #ee4d2d}
.pagination .dots{background:transparent;border:none;color:#9e9e9e;cursor:default}
.pagination-info{text-align:center;font-size:.78rem;color:#9e9e9e;margin-top:4px}
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
        <a href="/admin/pesanan.php" class="active">
            <span class="menu-icon">🛒</span> Pesanan
            <?php if ($statusCounts['pending'] > 0): ?><span class="badge-count"><?= $statusCounts['pending'] ?></span><?php endif; ?>
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
            <div style="font-size:1.1rem;font-weight:800;color:#212121;">🛒 Manajemen Pesanan</div>
            <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px;">
                Total: <?= $totalData ?> pesanan
                <?php if ($totalPages > 1): ?>· Halaman <?= $currentPage ?>/<?= $totalPages ?><?php endif; ?>
            </div>
        </div>
        <span style="font-size:.8rem;color:#9e9e9e;"><?= date('d M Y') ?></span>
    </div>

    <div style="padding:20px">
        <?php if ($msg): ?><div class="alert alert-success" style="padding:12px 16px;background:#e8f5e9;color:#2e7d32;border-left:3px solid #00b14f;border-radius:8px;margin-bottom:16px;font-size:.85rem;">✅ <?= $msg ?></div><?php endif; ?>

        <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-bottom:20px;">
        <?php
        $sfConfig=[''=>['label'=>'Semua','color'=>'#757575','bg'=>'#f5f5f5','count'=>array_sum($statusCounts)],'pending'=>['label'=>'Pending','color'=>'#f5a623','bg'=>'#fff8e1','count'=>$statusCounts['pending']],'diproses'=>['label'=>'Diproses','color'=>'#1976d2','bg'=>'#e3f2fd','count'=>$statusCounts['diproses']],'dikirim'=>['label'=>'Dikirim','color'=>'#9c27b0','bg'=>'#f3e5f5','count'=>$statusCounts['dikirim']],'selesai'=>['label'=>'Selesai','color'=>'#00b14f','bg'=>'#e8f5e9','count'=>$statusCounts['selesai']]];
        foreach ($sfConfig as $key => $cfg): $isActive = $filterStatus === $key; ?>
        <a href="?status=<?= $key ?>" style="display:block;background:<?= $isActive?$cfg['color']:$cfg['bg'] ?>;border-radius:10px;padding:12px 14px;text-decoration:none;border:2px solid <?= $isActive?$cfg['color']:'transparent' ?>;transition:all .2s;" onmouseover="this.style.borderColor='<?= $cfg['color'] ?>'" onmouseout="this.style.borderColor='<?= $isActive?$cfg['color']:'transparent' ?>'">
            <div style="font-size:1.2rem;font-weight:900;color:<?= $isActive?'white':$cfg['color'] ?>;"><?= $cfg['count'] ?></div>
            <div style="font-size:.72rem;font-weight:600;color:<?= $isActive?'rgba(255,255,255,.85)':$cfg['color'] ?>;margin-top:2px;"><?= $cfg['label'] ?></div>
        </a>
        <?php endforeach; ?>
        </div>

        <div style="background:white;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);padding:14px 16px;margin-bottom:16px;">
            <form method="GET" style="display:flex;gap:10px;align-items:center;">
                <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                <input type="text" name="q" style="flex:1;max-width:400px;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.85rem;outline:none;" placeholder="🔍 Cari kode pesanan atau nama pelanggan..." value="<?= htmlspecialchars($search) ?>" onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'">
                <button type="submit" style="padding:9px 18px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;">Cari</button>
                <?php if ($search || $filterStatus): ?>
                <a href="/admin/pesanan.php" style="padding:9px 18px;background:white;color:#ee4d2d;border:1.5px solid #ee4d2d;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="background:white;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                    <thead>
                        <tr>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">#</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Kode Pesanan</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Pelanggan</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Total</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Metode</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Status</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Tanggal</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($pesananList)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:#9e9e9e;">
                            <div style="font-size:2.5rem;margin-bottom:8px;">🛒</div>
                            Tidak ada pesanan ditemukan.
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($pesananList as $i => $p):
                        $bc=match($p['status']){'pending'=>['#fff8e1','#f5a623'],'selesai'=>['#e8f5e9','#00b14f'],'dibatalkan'=>['#ffebee','#f44336'],'dikirim'=>['#f3e5f5','#9c27b0'],default=>['#f5f5f5','#757575']};
                        $rowNum = $offset + $i + 1;
                    ?>
                    <tr style="border-bottom:1px solid #f5f5f5;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:11px 14px;color:#9e9e9e;font-size:.8rem;"><?= $rowNum ?></td>
                        <td style="padding:11px 14px;"><code style="font-size:.72rem;background:#f5f5f5;padding:3px 7px;border-radius:4px;"><?= htmlspecialchars(substr($p['kode_pesanan'],0,20)) ?>...</code></td>
                        <td style="padding:11px 14px;">
                            <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($p['nama_user']) ?></div>
                            <div style="font-size:.72rem;color:#9e9e9e;"><?= htmlspecialchars($p['email']) ?></div>
                        </td>
                        <td style="padding:11px 14px;font-weight:700;color:#ee4d2d;"><?= rupiahFormat($p['total_harga']) ?></td>
                        <td style="padding:11px 14px;"><span style="font-size:.75rem;background:#f5f5f5;padding:3px 8px;border-radius:4px;"><?= htmlspecialchars($p['metode_bayar']) ?></span></td>
                        <td style="padding:11px 14px;"><span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;background:<?= $bc[0] ?>;color:<?= $bc[1] ?>;"><?= ucfirst($p['status']) ?></span></td>
                        <td style="padding:11px 14px;font-size:.75rem;color:#9e9e9e;"><?= date('d/m/Y H:i',strtotime($p['created_at'])) ?></td>
                        <td style="padding:11px 14px;">
                            <a href="?detail=<?= $p['id'] ?><?= $filterStatus?'&status='.$filterStatus:'' ?><?= $currentPage>1?'&page='.$currentPage:'' ?>"
                               style="padding:6px 12px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border-radius:6px;font-size:.78rem;font-weight:600;text-decoration:none;">Detail</a>
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
                        <a href="<?= pageUrl($currentPage-1, $filterStatus, $search) ?>">←</a>
                    <?php endif; ?>
                    <?php $range=2; for ($pg=1;$pg<=$totalPages;$pg++): if ($pg===1||$pg===$totalPages||($pg>=$currentPage-$range&&$pg<=$currentPage+$range)): ?>
                        <?php if ($pg===$currentPage): ?><span class="active"><?= $pg ?></span>
                        <?php else: ?><a href="<?= pageUrl($pg,$filterStatus,$search) ?>"><?= $pg ?></a><?php endif; ?>
                    <?php elseif ($pg===$currentPage-$range-1||$pg===$currentPage+$range+1): ?><span class="dots">...</span>
                    <?php endif; endfor; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= pageUrl($currentPage+1, $filterStatus, $search) ?>">→</a>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalData) ?> dari <?= $totalData ?> pesanan</div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>

<?php if ($detailPesanan): ?>
<div class="modal-overlay" onclick="if(event.target===this)window.location='/admin/pesanan.php'">
    <div class="modal-box">
        <div class="modal-header">
            <div>
                <div style="font-size:1rem;font-weight:700;">📋 Detail Pesanan</div>
                <code style="font-size:.75rem;color:#9e9e9e;"><?= htmlspecialchars($detailPesanan['kode_pesanan']) ?></code>
            </div>
            <a href="/admin/pesanan.php" style="color:#9e9e9e;text-decoration:none;font-size:1.3rem;line-height:1;">×</a>
        </div>
        <div class="modal-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px;">
                <div style="background:#f9f9f9;border-radius:8px;padding:14px;">
                    <div style="font-size:.72rem;text-transform:uppercase;color:#9e9e9e;margin-bottom:8px;font-weight:600;">👤 Info Pelanggan</div>
                    <div style="font-weight:700;font-size:.9rem;"><?= htmlspecialchars($detailPesanan['nama_user']) ?></div>
                    <div style="font-size:.78rem;color:#9e9e9e;margin-top:3px;"><?= htmlspecialchars($detailPesanan['email']) ?></div>
                    <div style="font-size:.78rem;color:#9e9e9e;"><?= htmlspecialchars($detailPesanan['telepon'] ?? '-') ?></div>
                </div>
                <div style="background:#f9f9f9;border-radius:8px;padding:14px;">
                    <div style="font-size:.72rem;text-transform:uppercase;color:#9e9e9e;margin-bottom:8px;font-weight:600;">📍 Alamat Pengiriman</div>
                    <div style="font-size:.82rem;line-height:1.6;"><?= nl2br(htmlspecialchars($detailPesanan['alamat_pengiriman'])) ?></div>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <div style="font-size:.72rem;text-transform:uppercase;color:#9e9e9e;margin-bottom:10px;font-weight:600;">🛒 Item Pesanan</div>
                <div style="border:1px solid #e8e8e8;border-radius:8px;overflow:hidden;">
                    <table style="width:100%;border-collapse:collapse;font-size:.85rem;margin:0;">
                        <thead><tr style="background:#f9f9f9;">
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;">Produk</th>
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:700;color:#757575;text-align:right;">Harga</th>
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:700;color:#757575;text-align:center;">Qty</th>
                            <th style="padding:10px 12px;font-size:.75rem;font-weight:700;color:#757575;text-align:right;">Subtotal</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($detailItems as $item): ?>
                        <tr style="border-top:1px solid #f0f0f0;">
                            <td style="padding:10px 12px;font-size:.82rem;"><?= htmlspecialchars($item['nama_produk']) ?></td>
                            <td style="padding:10px 12px;font-size:.82rem;text-align:right;"><?= rupiahFormat($item['harga']) ?></td>
                            <td style="padding:10px 12px;font-size:.82rem;text-align:center;"><?= $item['jumlah'] ?></td>
                            <td style="padding:10px 12px;font-size:.82rem;text-align:right;font-weight:600;color:#ee4d2d;"><?= rupiahFormat($item['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="background:#f9f9f9;border-radius:8px;padding:14px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:4px 0;"><span style="color:#9e9e9e;">Metode Bayar</span><span style="font-weight:600;"><?= htmlspecialchars($detailPesanan['metode_bayar']) ?></span></div>
                <?php if ($detailPesanan['diskon'] > 0): ?>
                <div style="display:flex;justify-content:space-between;font-size:.82rem;padding:4px 0;"><span style="color:#9e9e9e;">Diskon (<?= $detailPesanan['voucher_kode'] ?>)</span><span style="color:#00b14f;font-weight:600;">- <?= rupiahFormat($detailPesanan['diskon']) ?></span></div>
                <?php endif; ?>
                <div style="display:flex;justify-content:space-between;font-size:.95rem;padding:8px 0 0;border-top:1px solid #e8e8e8;margin-top:6px;font-weight:800;"><span>Total</span><span style="color:#ee4d2d;"><?= rupiahFormat($detailPesanan['total_harga']) ?></span></div>
            </div>

            <?php if ($detailPesanan['catatan']): ?>
            <div style="background:#fff8e1;border-radius:8px;padding:12px;margin-bottom:16px;font-size:.82rem;">📝 <strong>Catatan:</strong> <?= htmlspecialchars($detailPesanan['catatan']) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="pesanan_id" value="<?= $detailPesanan['id'] ?>">
                <div style="display:flex;gap:10px;align-items:center;">
                    <select name="status" style="flex:1;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.85rem;outline:none;">
                        <?php foreach (['pending','diproses','dikirim','selesai','dibatalkan'] as $s): ?>
                            <option value="<?= $s ?>" <?= $detailPesanan['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="update_status" style="padding:9px 18px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;">💾 Update Status</button>
                    <a href="/invoice.php?kode=<?= $detailPesanan['kode_pesanan'] ?>" style="padding:9px 18px;background:white;color:#ee4d2d;border:1.5px solid #ee4d2d;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;" target="_blank">🖨️ Invoice</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

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
function bukaModalLogout(){var m=document.getElementById('modalLogout');m.style.display='flex';document.body.style.overflow='hidden';}
function tutupModalLogout(){var m=document.getElementById('modalLogout');m.style.opacity='0';m.style.transition='opacity .2s';setTimeout(function(){m.style.display='none';m.style.opacity='1';document.body.style.overflow='';},200);}
document.addEventListener('keydown',function(e){if(e.key==='Escape')tutupModalLogout();});
</script>
</body>
</html>