<?php
require_once __DIR__ . '/config/database.php';
requireLogin();
$pdo = getDB();

// Tandai semua dibaca
if (isset($_GET['read_all'])) {
    $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    header('Location: ' . BASE_PATH . '/notifikasi.php?msg=read_all'); exit;
}

// Tandai satu dibaca
if (isset($_GET['id'])) {
    $pdo->prepare("UPDATE notifikasi SET is_read = 1 WHERE id = ? AND user_id = ?")
        ->execute([(int)$_GET['id'], $_SESSION['user_id']]);
}

// Hapus satu notifikasi
if (isset($_GET['hapus'])) {
    $pdo->prepare("DELETE FROM notifikasi WHERE id = ? AND user_id = ?")
        ->execute([(int)$_GET['hapus'], $_SESSION['user_id']]);
    header('Location: ' . BASE_PATH . '/notifikasi.php'); exit;
}

// Hapus semua
if (isset($_GET['hapus_semua'])) {
    $pdo->prepare("DELETE FROM notifikasi WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    header('Location: ' . BASE_PATH . '/notifikasi.php'); exit;
}

// Ambil semua notifikasi
$notifList = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC");
$notifList->execute([$_SESSION['user_id']]);
$notifList = $notifList->fetchAll();

$totalUnread = count(array_filter($notifList, fn($n) => !$n['is_read']));
$totalNotif  = count($notifList);

$pageTitle = 'Notifikasi - TokoKu';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:760px;">

    <!-- HEADER -->
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <div>
            <h1 style="font-size:1.3rem; font-weight:800; display:flex; align-items:center; gap:8px;">
                🔔 Notifikasi
                <?php if ($totalUnread > 0): ?>
                    <span style="background:var(--primary); color:white; font-size:0.72rem; padding:2px 10px; border-radius:20px; font-weight:700;">
                        <?= $totalUnread ?> baru
                    </span>
                <?php endif; ?>
            </h1>
            <p style="color:var(--gray); font-size:0.82rem; margin-top:2px;">
                Total <?= $totalNotif ?> notifikasi
            </p>
        </div>
        <?php if (!empty($notifList)): ?>
        <div style="display:flex; gap:8px;">
            <?php if ($totalUnread > 0): ?>
            <a href="?read_all=1" class="btn btn-outline btn-sm">✅ Tandai Semua Dibaca</a>
            <?php endif; ?>
            <a href="?hapus_semua=1"
               onclick="return confirm('Hapus semua notifikasi?')"
               class="btn btn-sm" style="background:#fff0ed; color:var(--primary); border:1px solid #ffd5cc;">
                🗑️ Hapus Semua
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'read_all'): ?>
        <div class="alert alert-success" style="margin-bottom:16px;">✅ Semua notifikasi ditandai sudah dibaca.</div>
    <?php endif; ?>

    <?php if (empty($notifList)): ?>
        <!-- KOSONG -->
        <div class="card" style="padding:60px 20px; text-align:center;">
            <div style="font-size:5rem; margin-bottom:16px; opacity:0.3;">🔔</div>
            <h3 style="font-size:1.1rem; color:var(--dark); margin-bottom:8px;">Tidak Ada Notifikasi</h3>
            <p style="color:var(--gray); font-size:0.85rem; margin-bottom:24px;">
                Belum ada notifikasi untukmu saat ini.<br>
                Mulai belanja untuk mendapatkan update pesanan!
            </p>
            <a href="<?= BASE_PATH ?>/produk.php" class="btn btn-primary">🛍️ Mulai Belanja</a>
        </div>

    <?php else: ?>

        <!-- FILTER TAB -->
        <?php
        $filter = $_GET['filter'] ?? 'semua';
        $tabs   = [
            'semua'   => ['label' => 'Semua',   'icon' => '🔔', 'count' => $totalNotif],
            'pesanan' => ['label' => 'Pesanan', 'icon' => '📦', 'count' => count(array_filter($notifList, fn($n) => $n['tipe'] === 'pesanan'))],
            'promo'   => ['label' => 'Promo',   'icon' => '🎉', 'count' => count(array_filter($notifList, fn($n) => $n['tipe'] === 'promo'))],
            'sistem'  => ['label' => 'Sistem',  'icon' => '⚙️', 'count' => count(array_filter($notifList, fn($n) => $n['tipe'] === 'sistem'))],
        ];
        ?>
        <div style="display:flex; gap:4px; margin-bottom:16px; background:white; border-radius:10px; padding:5px; box-shadow:var(--shadow);">
            <?php foreach ($tabs as $key => $tab): ?>
            <a href="?filter=<?= $key ?>"
               style="flex:1; text-align:center; padding:8px 6px; border-radius:7px; text-decoration:none; font-size:0.8rem; font-weight:600; transition:all 0.2s;
                      background:<?= $filter === $key ? 'var(--primary)' : 'transparent' ?>;
                      color:<?= $filter === $key ? 'white' : 'var(--gray)' ?>;">
                <?= $tab['icon'] ?> <?= $tab['label'] ?>
                <?php if ($tab['count'] > 0): ?>
                    <span style="font-size:0.7rem; opacity:0.8;">(<?= $tab['count'] ?>)</span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- LIST NOTIFIKASI -->
        <div class="card" style="overflow:hidden;">
            <?php
            $filtered = $filter === 'semua'
                ? $notifList
                : array_values(array_filter($notifList, fn($n) => $n['tipe'] === $filter));
            ?>

            <?php if (empty($filtered)): ?>
                <div style="padding:40px; text-align:center; color:var(--gray);">
                    <div style="font-size:3rem; margin-bottom:8px; opacity:0.3;">
                        <?= $tabs[$filter]['icon'] ?>
                    </div>
                    <p style="font-size:0.85rem;">Tidak ada notifikasi <?= $tabs[$filter]['label'] ?>.</p>
                </div>
            <?php else: ?>
                <?php foreach ($filtered as $i => $n):
                    $tipeConfig = match($n['tipe']) {
                        'pesanan' => ['icon' => '📦', 'color' => '#e3f2fd', 'border' => '#1976d2'],
                        'promo'   => ['icon' => '🎉', 'color' => '#fce4ec', 'border' => '#e91e63'],
                        default   => ['icon' => '🔔', 'color' => '#f3e5f5', 'border' => '#9c27b0'],
                    };
                    $isUnread = !$n['is_read'];
                    $isLast   = $i === count($filtered) - 1;

                    // Hitung waktu relatif
                    $diff = time() - strtotime($n['created_at']);
                    if ($diff < 60)          $waktu = 'Baru saja';
                    elseif ($diff < 3600)    $waktu = floor($diff/60) . ' menit lalu';
                    elseif ($diff < 86400)   $waktu = floor($diff/3600) . ' jam lalu';
                    elseif ($diff < 604800)  $waktu = floor($diff/86400) . ' hari lalu';
                    else                     $waktu = date('d M Y', strtotime($n['created_at']));
                ?>
                <div style="display:flex; align-items:flex-start; gap:14px; padding:16px 20px;
                            background:<?= $isUnread ? '#fffaf9' : 'white' ?>;
                            border-bottom:<?= !$isLast ? '1px solid var(--border)' : 'none' ?>;
                            transition:background 0.2s;
                            border-left:3px solid <?= $isUnread ? 'var(--primary)' : 'transparent' ?>;"
                     onmouseover="this.style.background='#fafafa'"
                     onmouseout="this.style.background='<?= $isUnread ? '#fffaf9' : 'white' ?>'">

                    <!-- Icon -->
                    <div style="width:44px; height:44px; border-radius:50%; background:<?= $tipeConfig['color'] ?>;
                                display:flex; align-items:center; justify-content:center;
                                font-size:1.3rem; flex-shrink:0; margin-top:2px;
                                border:2px solid <?= $tipeConfig['border'] ?>22;">
                        <?= $tipeConfig['icon'] ?>
                    </div>

                    <!-- Konten -->
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:8px;">
                            <div style="font-weight:<?= $isUnread ? '700' : '600' ?>; font-size:0.88rem; color:var(--dark); line-height:1.4;">
                                <?= htmlspecialchars($n['judul']) ?>
                                <?php if ($isUnread): ?>
                                    <span style="display:inline-block; width:7px; height:7px; background:var(--primary); border-radius:50%; margin-left:5px; vertical-align:middle;"></span>
                                <?php endif; ?>
                            </div>
                            <!-- Aksi -->
                            <div style="display:flex; gap:6px; flex-shrink:0;">
                                <?php if ($isUnread): ?>
                                <a href="?id=<?= $n['id'] ?>"
                                   title="Tandai dibaca"
                                   style="color:var(--gray); font-size:0.72rem; text-decoration:none; padding:3px 8px; border:1px solid var(--border); border-radius:4px; white-space:nowrap;"
                                   onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'"
                                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--gray)'">
                                    ✓ Baca
                                </a>
                                <?php endif; ?>
                                <a href="?hapus=<?= $n['id'] ?>"
                                   title="Hapus"
                                   onclick="return confirm('Hapus notifikasi ini?')"
                                   style="color:var(--gray); font-size:0.75rem; text-decoration:none; padding:3px 8px; border:1px solid var(--border); border-radius:4px;"
                                   onmouseover="this.style.borderColor='var(--danger)';this.style.color='var(--danger)'"
                                   onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--gray)'">
                                    🗑
                                </a>
                            </div>
                        </div>

                        <p style="font-size:0.82rem; color:var(--gray); margin:5px 0 8px; line-height:1.6;">
                            <?= htmlspecialchars($n['pesan']) ?>
                        </p>

                        <div style="display:flex; align-items:center; gap:10px;">
                            <span style="font-size:0.72rem; color:#bdbdbd;">🕐 <?= $waktu ?></span>
                            <span style="font-size:0.68rem; color:#bdbdbd;">·</span>
                            <span style="font-size:0.68rem; background:<?= $tipeConfig['color'] ?>; color:<?= $tipeConfig['border'] ?>; padding:1px 8px; border-radius:10px; font-weight:600; text-transform:capitalize;">
                                <?= $n['tipe'] ?>
                            </span>

                            <!-- Link ke pesanan jika tipe pesanan -->
                            <?php if ($n['tipe'] === 'pesanan'): ?>
                                <a href="<?= BASE_PATH ?>/profil.php?tab=pesanan"
                                   style="font-size:0.72rem; color:var(--primary); text-decoration:none; margin-left:auto; font-weight:600;">
                                    Lihat Pesanan →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- INFO TOTAL -->
        <?php if ($totalNotif > 0): ?>
        <div style="text-align:center; margin-top:16px; font-size:0.78rem; color:var(--gray);">
            Menampilkan <?= count($filtered) ?> dari <?= $totalNotif ?> notifikasi
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>