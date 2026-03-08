<?php
require_once '../config/database.php';
requireAdmin();
$pdo = getDB();

$msg    = '';
$msgErr = '';

// ===== RESET PASSWORD OLEH ADMIN =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pw'])) {
    $userId      = (int)$_POST['user_id'];
    $passwordBaru = $_POST['password_baru'] ?? '';
    $konfirmasi   = $_POST['konfirmasi'] ?? '';

    if (strlen($passwordBaru) < 8) {
        $msgErr = 'Password minimal 8 karakter.';
    } elseif ($passwordBaru !== $konfirmasi) {
        $msgErr = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'pelanggan'")->execute([$hash, $userId]);
        // Hapus token reset yang pending milik user ini
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$userId]);
        $msg = 'Password user berhasil direset!';
        header('Location: /admin/users.php?msg=' . urlencode($msg)); exit;
    }
}

// ===== NONAKTIFKAN / AKTIFKAN USER =====
if (isset($_GET['toggle'])) {
    $userId = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET status = IF(status='aktif','nonaktif','aktif') WHERE id = ? AND role = 'pelanggan'")->execute([$userId]);
    header('Location: /admin/users.php?msg=Status+user+diperbarui'); exit;
}

if (isset($_GET['msg'])) $msg = htmlspecialchars($_GET['msg']);

// ===== LIST USER =====
$search  = trim($_GET['q'] ?? '');
$where   = "WHERE role = 'pelanggan'";
$params  = [];
if ($search) { $where .= " AND (nama LIKE ? OR email LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$perPage     = 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$countStmt->execute($params);
$totalData  = $countStmt->fetchColumn();
$totalPages = ceil($totalData / $perPage);

$stmt = $pdo->prepare("SELECT u.*, 
    (SELECT COUNT(*) FROM pesanan WHERE user_id = u.id) as total_pesanan,
    (SELECT COUNT(*) FROM pesanan WHERE user_id = u.id AND status = 'selesai') as pesanan_selesai,
    (SELECT COALESCE(SUM(total_harga),0) FROM pesanan WHERE user_id = u.id AND status = 'selesai') as total_belanja
    FROM users u $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$userList = $stmt->fetchAll();

// User yang sedang akan direset (untuk pre-fill form)
$resetUser = null;
if (isset($_GET['reset'])) {
    $stmt = $pdo->prepare("SELECT id, nama, email FROM users WHERE id = ? AND role = 'pelanggan'");
    $stmt->execute([(int)$_GET['reset']]);
    $resetUser = $stmt->fetch();
}

$pendingCount = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='pending'")->fetchColumn();

function pageUrlUsers($page, $search) {
    $p = ['page' => $page];
    if ($search) $p['q'] = $search;
    return '/admin/users.php?' . http_build_query($p);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manajemen User - Admin TokoKu</title>
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
.modal-logout-overlay,.modal-reset-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99999;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
.modal-box{background:white;border-radius:16px;padding:32px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:popIn .25s ease;margin:20px}
@keyframes popIn{from{transform:scale(.85);opacity:0}to{transform:scale(1);opacity:1}}
.pagination{display:flex;align-items:center;justify-content:center;gap:6px;padding:16px 0}
.pagination a,.pagination span{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:8px;font-size:.82rem;font-weight:600;text-decoration:none;transition:all .2s}
.pagination a{background:white;color:#424242;border:1.5px solid #e0e0e0}
.pagination a:hover{border-color:#ee4d2d;color:#ee4d2d}
.pagination .active{background:#ee4d2d;color:white;border:1.5px solid #ee4d2d}
.pagination .dots{background:transparent;border:none;color:#9e9e9e;cursor:default}
.pagination-info{text-align:center;font-size:.78rem;color:#9e9e9e;margin-top:4px}
.pw-strength{height:4px;border-radius:4px;margin-top:5px;transition:all .3s;background:#e0e0e0;width:0}
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
        <a href="/admin/laporan.php"><span class="menu-icon">📈</span> Laporan</a>
        <a href="/admin/users.php" class="active"><span class="menu-icon">👥</span> Manajemen User</a>
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
            <div style="font-size:1.1rem;font-weight:800;color:#212121;">👥 Manajemen User</div>
            <div style="font-size:.75rem;color:#9e9e9e;margin-top:2px;">
                <?= $totalData ?> user terdaftar
                <?php if ($totalPages > 1): ?>· Halaman <?= $currentPage ?>/<?= $totalPages ?><?php endif; ?>
            </div>
        </div>
        <span style="font-size:.8rem;color:#9e9e9e;"><?= date('d M Y') ?></span>
    </div>

    <div style="padding:20px;">
        <?php if ($msg): ?>
            <div style="padding:12px 16px;background:#e8f5e9;color:#2e7d32;border-left:3px solid #00b14f;border-radius:8px;margin-bottom:16px;font-size:.85rem;">✅ <?= $msg ?></div>
        <?php endif; ?>
        <?php if ($msgErr): ?>
            <div style="padding:12px 16px;background:#ffebee;color:#c62828;border-left:3px solid #f44336;border-radius:8px;margin-bottom:16px;font-size:.85rem;">❌ <?= htmlspecialchars($msgErr) ?></div>
        <?php endif; ?>

        <!-- Search -->
        <div style="background:white;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);padding:14px 16px;margin-bottom:16px;">
            <form method="GET" style="display:flex;gap:10px;align-items:center;">
                <input type="text" name="q"
                       style="flex:1;max-width:360px;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.85rem;outline:none;"
                       placeholder="🔍 Cari nama atau email user..."
                       value="<?= htmlspecialchars($search) ?>"
                       onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'">
                <button type="submit" style="padding:9px 18px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);color:white;border:none;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;">Cari</button>
                <?php if ($search): ?>
                    <a href="/admin/users.php" style="padding:9px 18px;background:white;color:#ee4d2d;border:1.5px solid #ee4d2d;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabel User -->
        <div style="background:white;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.07);overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
                    <thead>
                        <tr>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">#</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">User</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Kontak</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:center;border-bottom:2px solid #f0f0f0;">Pesanan</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:right;border-bottom:2px solid #f0f0f0;">Total Belanja</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Status</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Bergabung</th>
                            <th style="background:#fafafa;padding:11px 14px;font-size:.75rem;font-weight:700;color:#757575;text-align:left;border-bottom:2px solid #f0f0f0;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($userList)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:40px;color:#9e9e9e;">
                            <div style="font-size:2.5rem;margin-bottom:8px;">👥</div>
                            Tidak ada user ditemukan.
                        </td></tr>
                    <?php endif; ?>
                    <?php foreach ($userList as $i => $u): ?>
                    <tr style="border-bottom:1px solid #f5f5f5;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:12px 14px;color:#9e9e9e;font-size:.8rem;"><?= $offset + $i + 1 ?></td>
                        <td style="padding:12px 14px;">
                            <div style="display:flex;align-items:center;gap:10px;">
                                <div style="width:38px;height:38px;background:linear-gradient(135deg,#ee4d2d,#ff6b35);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:800;font-size:.9rem;flex-shrink:0;">
                                    <?= strtoupper(substr($u['nama'],0,1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight:600;font-size:.85rem;"><?= htmlspecialchars($u['nama']) ?></div>
                                    <div style="font-size:.72rem;color:#9e9e9e;">ID: <?= $u['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding:12px 14px;">
                            <div style="font-size:.82rem;"><?= htmlspecialchars($u['email']) ?></div>
                            <div style="font-size:.72rem;color:#9e9e9e;"><?= htmlspecialchars($u['telepon'] ?? '-') ?></div>
                        </td>
                        <td style="padding:12px 14px;text-align:center;">
                            <div style="font-weight:800;color:#212121;"><?= $u['total_pesanan'] ?></div>
                            <div style="font-size:.7rem;color:#00b14f;"><?= $u['pesanan_selesai'] ?> selesai</div>
                        </td>
                        <td style="padding:12px 14px;text-align:right;font-weight:700;color:#ee4d2d;font-size:.85rem;">
                            <?= rupiahFormat($u['total_belanja']) ?>
                        </td>
                        <td style="padding:12px 14px;">
                            <?php $isAktif = ($u['status'] ?? 'aktif') === 'aktif'; ?>
                            <span style="display:inline-block;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:700;background:<?= $isAktif?'#e8f5e9':'#ffebee' ?>;color:<?= $isAktif?'#00b14f':'#f44336' ?>;">
                                <?= $isAktif ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </td>
                        <td style="padding:12px 14px;font-size:.75rem;color:#9e9e9e;"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td style="padding:12px 14px;">
                            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                <button onclick="bukaModalReset(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['nama'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>')"
                                        style="padding:5px 10px;background:#e3f2fd;color:#1976d2;border:1px solid #bbdefb;border-radius:6px;font-size:.75rem;font-weight:600;cursor:pointer;">
                                    🔑 Reset PW
                                </button>
                                <a href="/admin/users.php?toggle=<?= $u['id'] ?>"
                                   onclick="return confirm('<?= $isAktif ? 'Nonaktifkan' : 'Aktifkan' ?> user ini?')"
                                   style="padding:5px 10px;background:<?= $isAktif?'#fff8e1':'#e8f5e9' ?>;color:<?= $isAktif?'#f5a623':'#00b14f' ?>;border:1px solid <?= $isAktif?'#ffe082':'#a5d6a7' ?>;border-radius:6px;font-size:.75rem;font-weight:600;text-decoration:none;">
                                    <?= $isAktif ? '🚫 Nonaktif' : '✅ Aktifkan' ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div style="padding:8px 16px;border-top:1px solid #f0f0f0;">
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="<?= pageUrlUsers($currentPage-1, $search) ?>">←</a>
                    <?php endif; ?>
                    <?php for ($pg=1; $pg<=$totalPages; $pg++):
                        if ($pg===1 || $pg===$totalPages || ($pg>=$currentPage-2 && $pg<=$currentPage+2)): ?>
                            <?php if ($pg===$currentPage): ?>
                                <span class="active"><?= $pg ?></span>
                            <?php else: ?>
                                <a href="<?= pageUrlUsers($pg, $search) ?>"><?= $pg ?></a>
                            <?php endif; ?>
                        <?php elseif ($pg===$currentPage-3 || $pg===$currentPage+3): ?>
                            <span class="dots">...</span>
                        <?php endif; endfor; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="<?= pageUrlUsers($currentPage+1, $search) ?>">→</a>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">
                    Menampilkan <?= $offset+1 ?>–<?= min($offset+$perPage,$totalData) ?> dari <?= $totalData ?> user
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</div>

<!-- MODAL RESET PASSWORD -->
<div class="modal-reset-overlay" id="modalReset" onclick="if(event.target===this)tutupModalReset()">
    <div class="modal-box">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
                <div style="font-size:1rem;font-weight:800;">🔑 Reset Password User</div>
                <div id="resetUserInfo" style="font-size:.78rem;color:#9e9e9e;margin-top:2px;"></div>
            </div>
            <button onclick="tutupModalReset()" style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#9e9e9e;line-height:1;">×</button>
        </div>

        <form method="POST" onsubmit="return validasiFormReset()">
            <input type="hidden" name="reset_pw" value="1">
            <input type="hidden" name="user_id" id="resetUserId">

            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#424242;margin-bottom:6px;">🔒 Password Baru</label>
                <div style="position:relative;">
                    <input type="password" name="password_baru" id="modalPwBaru"
                           placeholder="Minimal 8 karakter" required minlength="8"
                           oninput="cekKekuatanModal(this.value)"
                           style="width:100%;padding:10px 40px 10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.88rem;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'">
                    <span onclick="togglePwModal('modalPwBaru',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:.9rem;">👁️</span>
                </div>
                <div class="pw-strength" id="modalStrengthBar"></div>
                <div id="modalStrengthText" style="font-size:.7rem;color:#9e9e9e;margin-top:2px;"></div>
            </div>

            <div style="margin-bottom:20px;">
                <label style="display:block;font-size:.8rem;font-weight:600;color:#424242;margin-bottom:6px;">🔒 Konfirmasi Password</label>
                <div style="position:relative;">
                    <input type="password" name="konfirmasi" id="modalPwKonfirmasi"
                           placeholder="Ulangi password baru" required
                           oninput="cekKonfirmasiModal()"
                           style="width:100%;padding:10px 40px 10px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:.88rem;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='#ee4d2d'" onblur="this.style.borderColor='#e0e0e0'">
                    <span onclick="togglePwModal('modalPwKonfirmasi',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:.9rem;">👁️</span>
                </div>
                <div id="modalKonfirmasiInfo" style="font-size:.7rem;margin-top:2px;"></div>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="button" onclick="tutupModalReset()"
                        style="flex:1;padding:10px;border:1.5px solid #e0e0e0;border-radius:8px;background:white;color:#757575;font-size:.88rem;font-weight:600;cursor:pointer;">
                    Batal
                </button>
                <button type="submit"
                        style="flex:1;padding:10px;background:linear-gradient(135deg,#1976d2,#42a5f5);color:white;border:none;border-radius:8px;font-size:.88rem;font-weight:700;cursor:pointer;">
                    🔑 Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL LOGOUT -->
<div class="modal-logout-overlay" id="modalLogout" onclick="if(event.target===this)tutupModalLogout()">
    <div class="modal-box" style="text-align:center;">
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
// ===== MODAL RESET =====
function bukaModalReset(id, nama, email) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUserInfo').textContent = nama + ' · ' + email;
    document.getElementById('modalPwBaru').value = '';
    document.getElementById('modalPwKonfirmasi').value = '';
    document.getElementById('modalStrengthBar').style.width = '0';
    document.getElementById('modalStrengthText').textContent = '';
    document.getElementById('modalKonfirmasiInfo').textContent = '';
    const m = document.getElementById('modalReset');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('modalPwBaru').focus(), 100);
}
function tutupModalReset() {
    const m = document.getElementById('modalReset');
    m.style.opacity = '0'; m.style.transition = 'opacity .2s';
    setTimeout(() => { m.style.display='none'; m.style.opacity='1'; document.body.style.overflow=''; }, 200);
}
function validasiFormReset() {
    const pw1 = document.getElementById('modalPwBaru').value;
    const pw2 = document.getElementById('modalPwKonfirmasi').value;
    if (pw1.length < 8) { alert('Password minimal 8 karakter!'); return false; }
    if (pw1 !== pw2)    { alert('Konfirmasi password tidak cocok!'); return false; }
    return true;
}

// ===== KEKUATAN PASSWORD =====
function cekKekuatanModal(pw) {
    const bar = document.getElementById('modalStrengthBar');
    const txt = document.getElementById('modalStrengthText');
    let score = 0;
    if (pw.length >= 8)  score++;
    if (pw.length >= 12) score++;
    if (/[A-Z]/.test(pw)) score++;
    if (/[0-9]/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const lvl = [{c:'#f44336',l:'Sangat Lemah',w:'20%'},{c:'#ff7043',l:'Lemah',w:'40%'},{c:'#f5a623',l:'Sedang',w:'60%'},{c:'#66bb6a',l:'Kuat',w:'80%'},{c:'#00b14f',l:'Sangat Kuat',w:'100%'}][Math.min(score,4)];
    bar.style.background = lvl.c; bar.style.width = pw.length ? lvl.w : '0';
    txt.textContent = pw.length ? lvl.l : ''; txt.style.color = lvl.c;
}
function cekKonfirmasiModal() {
    const pw1 = document.getElementById('modalPwBaru').value;
    const pw2 = document.getElementById('modalPwKonfirmasi').value;
    const info = document.getElementById('modalKonfirmasiInfo');
    if (!pw2) { info.textContent = ''; return; }
    if (pw1 === pw2) { info.textContent = '✅ Password cocok'; info.style.color = '#00b14f'; }
    else             { info.textContent = '❌ Tidak cocok';    info.style.color = '#f44336'; }
}
function togglePwModal(id, el) {
    const inp = document.getElementById(id);
    const hidden = inp.type === 'password';
    inp.type = hidden ? 'text' : 'password';
    el.textContent = hidden ? '🙈' : '👁️';
}

// ===== MODAL LOGOUT =====
function bukaModalLogout(){const m=document.getElementById('modalLogout');m.style.display='flex';document.body.style.overflow='hidden';}
function tutupModalLogout(){const m=document.getElementById('modalLogout');m.style.opacity='0';m.style.transition='opacity .2s';setTimeout(()=>{m.style.display='none';m.style.opacity='1';document.body.style.overflow='';},200);}
document.addEventListener('keydown',e=>{if(e.key==='Escape'){tutupModalReset();tutupModalLogout();}});
</script>
</body>
</html>