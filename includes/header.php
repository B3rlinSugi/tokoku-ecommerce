<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDB();

$jumlahKeranjang = 0;
$jumlahNotifUnread = 0;

if (isLogin()) {
    $stmt = $pdo->prepare("SELECT SUM(jumlah) as total FROM keranjang WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $jumlahKeranjang = $stmt->fetch()['total'] ?? 0;

    $stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM notifikasi WHERE user_id = ? AND is_read = 0");
    $stmt2->execute([$_SESSION['user_id']]);
    $jumlahNotifUnread = $stmt2->fetch()['total'] ?? 0;

    $stmt3 = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt3->execute([$_SESSION['user_id']]);
    $notifList = $stmt3->fetchAll();
}

$searchQuery = htmlspecialchars($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'TokoKu - Belanja Online Terpercaya' ?></title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/style.css">
</head>
<body>

<!-- NAVBAR -->
<nav>
    <div class="navbar-top">
        <a href="<?= BASE_PATH ?>/index.php" class="navbar-logo">Toko<span>Ku</span></a>

        <form class="navbar-search" action="<?= BASE_PATH ?>/produk.php" method="GET">
            <input type="text" name="q" placeholder="Cari produk, brand, kategori..." value="<?= $searchQuery ?>">
            <button type="submit">🔍</button>
        </form>

        <div class="navbar-actions">
            <?php if (isLogin()): ?>
                <!-- Notifikasi -->
                <div style="position:relative;">
                    <a href="#" onclick="toggleNotif(event)" style="position:relative;">
                        <span class="icon">🔔</span>
                        <span style="font-size:0.72rem;">Notifikasi</span>
                        <?php if ($jumlahNotifUnread > 0): ?>
                            <span class="cart-badge"><?= $jumlahNotifUnread ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div style="padding:12px 16px;font-weight:700;font-size:0.85rem;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;">
                            <span>Notifikasi</span>
                            <?php if ($jumlahNotifUnread > 0): ?>
                                <a href="<?= BASE_PATH ?>/notifikasi.php?read_all=1" style="font-size:0.75rem;color:var(--primary);text-decoration:none;">Tandai semua dibaca</a>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($notifList)): ?>
                            <div style="padding:20px;text-align:center;color:var(--gray);font-size:0.82rem;">Tidak ada notifikasi</div>
                        <?php else: ?>
                            <?php foreach ($notifList as $n): ?>
                            <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>"
                                 onclick="window.location='<?= BASE_PATH ?>/notifikasi.php?id=<?= $n['id'] ?>'">
                                <div class="notif-title"><?= htmlspecialchars($n['judul']) ?></div>
                                <div class="notif-pesan"><?= htmlspecialchars(mb_strimwidth($n['pesan'], 0, 60, '...')) ?></div>
                                <div class="notif-time"><?= date('d M, H:i', strtotime($n['created_at'])) ?></div>
                            </div>
                            <?php endforeach; ?>
                            <div style="padding:10px;text-align:center;border-top:1px solid var(--border);">
                                <a href="<?= BASE_PATH ?>/notifikasi.php" style="color:var(--primary);font-size:0.8rem;text-decoration:none;">Lihat semua</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Keranjang -->
                <a href="<?= BASE_PATH ?>/keranjang.php" class="cart-wrapper">
                    <span class="icon">🛒</span>
                    <span style="font-size:0.72rem;">Keranjang</span>
                    <?php if ($jumlahKeranjang > 0): ?>
                        <span class="cart-badge"><?= $jumlahKeranjang ?></span>
                    <?php endif; ?>
                </a>

                <!-- Akun -->
                <a href="<?= BASE_PATH ?>/profil.php">
                    <span class="icon">👤</span>
                    <span style="font-size:0.72rem;"><?= htmlspecialchars(explode(' ', $_SESSION['nama'])[0]) ?></span>
                </a>

                <?php if (isAdmin()): ?>
                <a href="<?= BASE_PATH ?>/admin/dashboard.php" target="tokoku_admin">
                    <span class="icon">⚙️</span>
                    <span style="font-size:0.72rem;">Admin</span>
                </a>
                <?php endif; ?>

                <a href="<?= BASE_PATH ?>/logout.php">
                    <span class="icon">🚪</span>
                    <span style="font-size:0.72rem;">Keluar</span>
                </a>
            <?php else: ?>
                <a href="<?= BASE_PATH ?>/login.php">
                    <span class="icon">🔐</span>
                    <span style="font-size:0.72rem;">Masuk</span>
                </a>
                <a href="<?= BASE_PATH ?>/register.php" style="background:white;color:var(--primary);padding:6px 14px;border-radius:4px;font-weight:700;font-size:0.82rem;text-decoration:none;">Daftar</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="navbar-sub">
        <a href="<?= BASE_PATH ?>/produk.php">Semua Produk</a>
        <?php
        $kats = $pdo->query("SELECT * FROM kategori LIMIT 8")->fetchAll();
        foreach ($kats as $k):
        ?>
        <a href="<?= BASE_PATH ?>/produk.php?kategori=<?= $k['id'] ?>"><?= htmlspecialchars($k['nama_kategori']) ?></a>
        <?php endforeach; ?>
    </div>
</nav>