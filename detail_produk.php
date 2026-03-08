<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . BASE_PATH . '/produk.php'); exit; }

// Ambil data produk
$stmt = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE p.id = ? AND p.status = 'aktif'");
$stmt->execute([$id]);
$produk = $stmt->fetch();
if (!$produk) { header('Location: ' . BASE_PATH . '/produk.php'); exit; }

// Ambil rating rata-rata
$ratingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total_ulasan FROM ulasan WHERE produk_id = ?");
$ratingStmt->execute([$id]);
$ratingData = $ratingStmt->fetch();
$avgRating  = round($ratingData['avg_rating'] ?? 0, 1);
$totalUlasan = $ratingData['total_ulasan'];

// Ambil ulasan
$ulasanList = $pdo->prepare("SELECT u.*, us.nama FROM ulasan u JOIN users us ON u.user_id = us.id WHERE u.produk_id = ? ORDER BY u.created_at DESC LIMIT 10");
$ulasanList->execute([$id]);
$ulasanList = $ulasanList->fetchAll();

// Cek sudah beli & belum review
$sudahReview = false;
$sudahBeli   = false;
if (isLogin()) {
    $cekBeli = $pdo->prepare("SELECT COUNT(*) FROM detail_pesanan dp JOIN pesanan p ON dp.pesanan_id = p.id WHERE p.user_id = ? AND dp.produk_id = ? AND p.status = 'selesai'");
    $cekBeli->execute([$_SESSION['user_id'], $id]);
    $sudahBeli = $cekBeli->fetchColumn() > 0;

    $cekReview = $pdo->prepare("SELECT COUNT(*) FROM ulasan WHERE user_id = ? AND produk_id = ?");
    $cekReview->execute([$_SESSION['user_id'], $id]);
    $sudahReview = $cekReview->fetchColumn() > 0;
}

// Simpan ulasan
$msgUlasan = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ulasan'])) {
    if (!isLogin()) {
        header('Location: ' . BASE_PATH . '/login.php'); exit;
    }
    $rating   = (int)$_POST['rating'];
    $komentar = trim($_POST['komentar'] ?? '');
    if ($rating >= 1 && $rating <= 5 && $sudahBeli && !$sudahReview) {
        $pdo->prepare("INSERT INTO ulasan (produk_id, user_id, rating, komentar) VALUES (?,?,?,?)")
            ->execute([$id, $_SESSION['user_id'], $rating, $komentar]);
        header('Location: ' . BASE_PATH . '/detail_produk.php?id=' . $id . '&msg=ulasan');
        exit;
    }
}

// Produk terkait
$terkait = $pdo->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE p.kategori_id = ? AND p.id != ? AND p.status = 'aktif' LIMIT 5");
$terkait->execute([$produk['kategori_id'], $id]);
$terkait = $terkait->fetchAll();

$pageTitle = htmlspecialchars($produk['nama_produk']) . ' - TokoKu';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="<?= BASE_PATH ?>/index.php">🏠 Beranda</a>
        <span class="sep">›</span>
        <a href="<?= BASE_PATH ?>/produk.php">Produk</a>
        <span class="sep">›</span>
        <?php if ($produk['nama_kategori']): ?>
            <a href="<?= BASE_PATH ?>/produk.php?kategori=<?= $produk['kategori_id'] ?>"><?= htmlspecialchars($produk['nama_kategori']) ?></a>
            <span class="sep">›</span>
        <?php endif; ?>
        <span class="current"><?= htmlspecialchars($produk['nama_produk']) ?></span>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'ulasan'): ?>
        <div class="alert alert-success">✅ Ulasan berhasil dikirim!</div>
    <?php endif; ?>

    <!-- DETAIL PRODUK -->
    <div style="display:grid;grid-template-columns:400px 1fr;gap:20px;margin-bottom:20px;">
        <!-- FOTO -->
        <div class="card" style="padding:16px;">
            <?php if ($produk['gambar'] && file_exists(__DIR__ . '/uploads/' . $produk['gambar'])): ?>
                <img src="<?= BASE_PATH ?>/uploads/<?= htmlspecialchars($produk['gambar']) ?>"
                     class="product-detail-img" id="mainImg" alt="<?= htmlspecialchars($produk['nama_produk']) ?>">
            <?php else: ?>
                <div class="product-detail-img" style="display:flex;align-items:center;justify-content:center;font-size:5rem;background:#f5f5f5;">📦</div>
            <?php endif; ?>
        </div>

        <!-- INFO -->
        <div class="card" style="padding:20px;">
            <div style="font-size:0.78rem;color:var(--gray);margin-bottom:6px;">
                📂 <?= htmlspecialchars($produk['nama_kategori'] ?? '-') ?>
            </div>
            <h1 style="font-size:1.2rem;font-weight:700;margin-bottom:10px;line-height:1.4;">
                <?= htmlspecialchars($produk['nama_produk']) ?>
            </h1>

            <!-- Rating -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px;padding-bottom:14px;border-bottom:1px solid var(--border);">
                <div class="stars" style="font-size:1rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= round($avgRating) ? '⭐' : '☆' ?>
                    <?php endfor; ?>
                </div>
                <span style="font-weight:700;color:var(--warning);"><?= $avgRating ?: '-' ?></span>
                <span style="color:var(--gray);font-size:0.82rem;"><?= $totalUlasan ?> ulasan</span>
                <span style="color:var(--gray);font-size:0.82rem;">|</span>
                <span style="color:var(--gray);font-size:0.82rem;">Stok: <?= $produk['stok'] ?></span>
            </div>

            <!-- Harga -->
            <div style="font-size:1.8rem;font-weight:800;color:var(--primary);margin-bottom:16px;">
                <?= rupiahFormat($produk['harga']) ?>
            </div>

            <!-- Deskripsi -->
            <?php if ($produk['deskripsi']): ?>
            <div style="margin-bottom:20px;">
                <div style="font-weight:600;margin-bottom:6px;font-size:0.88rem;">Deskripsi Produk</div>
                <p style="font-size:0.85rem;color:var(--gray);line-height:1.7;">
                    <?= nl2br(htmlspecialchars($produk['deskripsi'])) ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Jumlah -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                <span style="font-size:0.85rem;font-weight:600;">Jumlah:</span>
                <div class="qty-control">
                    <button onclick="changeQty(-1)">−</button>
                    <input type="number" id="qtyInput" value="1" min="1" max="<?= $produk['stok'] ?>">
                    <button onclick="changeQty(1)">+</button>
                </div>
                <span style="font-size:0.8rem;color:var(--gray);">Tersedia <?= $produk['stok'] ?> buah</span>
            </div>

            <!-- Tombol -->
            <?php if ($produk['stok'] > 0): ?>
                <?php if (isLogin()): ?>
                <div style="display:flex;gap:10px;">
                    <button onclick="tambahKeKeranjangQty(<?= $produk['id'] ?>)" class="btn btn-secondary" style="flex:1;">
                        🛒 Tambah ke Keranjang
                    </button>
                    <button onclick="beliSekarang(<?= $produk['id'] ?>)" class="btn btn-primary" style="flex:1;">
                        ⚡ Beli Sekarang
                    </button>
                </div>
                <?php else: ?>
                <a href="<?= BASE_PATH ?>/login.php" class="btn btn-primary btn-full">🔐 Login untuk Membeli</a>
                <?php endif; ?>
            <?php else: ?>
                <button disabled class="btn btn-full" style="background:#f5f5f5;color:#bdbdbd;cursor:not-allowed;">❌ Stok Habis</button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ULASAN -->
    <div class="card" style="padding:20px;margin-bottom:20px;">
        <div class="section-header">
            <h2>⭐ Ulasan Pembeli (<?= $totalUlasan ?>)</h2>
        </div>

        <!-- Form ulasan -->
        <?php if (isLogin() && $sudahBeli && !$sudahReview): ?>
        <div style="background:#fff3f0;border-radius:var(--radius);padding:16px;margin-bottom:20px;">
            <h4 style="margin-bottom:12px;font-size:0.9rem;">✍️ Tulis Ulasan Anda</h4>
            <form method="POST">
                <div class="form-group">
                    <label>Rating *</label>
                    <div class="star-rating" id="starRating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span onclick="setRating(<?= $i ?>)" data-val="<?= $i ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" name="rating" id="ratingInput" value="0">
                </div>
                <div class="form-group">
                    <label>Komentar</label>
                    <textarea name="komentar" class="form-control" rows="3" placeholder="Bagikan pengalaman belanja Anda..."></textarea>
                </div>
                <button type="submit" name="submit_ulasan" class="btn btn-primary">Kirim Ulasan</button>
            </form>
        </div>
        <?php elseif (isLogin() && $sudahBeli && $sudahReview): ?>
            <div class="alert alert-info" style="margin-bottom:16px;">✅ Anda sudah memberikan ulasan untuk produk ini.</div>
        <?php elseif (!isLogin()): ?>
            <div class="alert alert-warning" style="margin-bottom:16px;">
                <a href="<?= BASE_PATH ?>/login.php" style="color:inherit;font-weight:700;">Login</a> untuk memberikan ulasan.
            </div>
        <?php endif; ?>

        <!-- Daftar ulasan -->
        <?php if (empty($ulasanList)): ?>
            <div style="text-align:center;padding:30px;color:var(--gray);">
                <div style="font-size:3rem;margin-bottom:8px;">💬</div>
                <p>Belum ada ulasan untuk produk ini.</p>
            </div>
        <?php else: ?>
        <?php foreach ($ulasanList as $u): ?>
            <div style="padding:14px 0;border-bottom:1px solid var(--border);">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                    <div style="width:36px;height:36px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;">
                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:0.85rem;"><?= htmlspecialchars($u['nama']) ?></div>
                        <div style="font-size:0.72rem;color:var(--gray);"><?= date('d M Y', strtotime($u['created_at'])) ?></div>
                    </div>
                    <div class="stars" style="margin-left:auto;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= $u['rating'] ? '⭐' : '☆' ?>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if ($u['komentar']): ?>
                    <p style="font-size:0.85rem;color:var(--dark);padding-left:46px;"><?= htmlspecialchars($u['komentar']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- PRODUK TERKAIT -->
    <?php if (!empty($terkait)): ?>
    <div style="margin-bottom:20px;">
        <div class="section-header">
            <h2>🔗 Produk Terkait</h2>
        </div>
        <div class="product-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
            <?php foreach ($terkait as $p): ?>
            <a href="<?= BASE_PATH ?>/detail_produk.php?id=<?= $p['id'] ?>" class="product-card">
                <div class="product-card-img">
                    <?php if ($p['gambar'] && file_exists(__DIR__ . '/uploads/' . $p['gambar'])): ?>
                        <img src="<?= BASE_PATH ?>/uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="">
                    <?php else: ?>
                        📦
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-card-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                    <div class="product-card-price"><?= rupiahFormat($p['harga']) ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function changeQty(delta) {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) + delta;
    const max = parseInt(input.max);
    if (val < 1) val = 1;
    if (val > max) val = max;
    input.value = val;
}

function tambahKeKeranjangQty(produkId) {
    const qty = document.getElementById('qtyInput').value;
    const formData = new FormData();
    formData.append('action', 'tambah');
    formData.append('produk_id', produkId);
    formData.append('jumlah', qty);
    fetch('<?= BASE_PATH ?>/keranjang.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            showToast(data.message, data.success ? 'success' : 'error');
            if (data.success) updateCartBadge();
        });
}

function beliSekarang(produkId) {
    tambahKeKeranjangQty(produkId);
    setTimeout(() => window.location.href = '<?= BASE_PATH ?>/keranjang.php', 800);
}

function setRating(val) {
    document.getElementById('ratingInput').value = val;
    document.querySelectorAll('#starRating span').forEach((s, i) => {
        s.style.color = i < val ? '#f5a623' : '#e0e0e0';
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>