<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'TokoKu - Belanja Online Terpercaya';
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT p.*, k.nama_kategori,
           COALESCE(AVG(u.rating), 0) as avg_rating,
           COUNT(u.id) as total_ulasan
    FROM produk p
    LEFT JOIN kategori k ON p.kategori_id = k.id
    LEFT JOIN ulasan u ON p.id = u.produk_id
    WHERE p.status = 'aktif'
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->execute();
$produkTerbaru = $stmt->fetchAll();

$kats = $pdo->query("SELECT * FROM kategori LIMIT 8")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- HERO BANNER -->
<div style="background: linear-gradient(135deg, #ee4d2d 0%, #ff6b35 50%, #f5a623 100%); padding: 0; overflow: hidden; position: relative;">
    <div style="max-width:1200px; margin:0 auto; padding: 40px 20px; display:flex; align-items:center; justify-content:space-between; gap:20px;">
        <div style="color:white; flex:1;">
            <div style="display:inline-block; background:rgba(255,255,255,0.2); padding:4px 12px; border-radius:20px; font-size:0.78rem; font-weight:600; margin-bottom:12px; backdrop-filter:blur(4px);">
                🎉 Flash Sale Hari Ini!
            </div>
            <h1 style="font-size:2.2rem; font-weight:900; line-height:1.2; margin-bottom:10px; text-shadow:0 2px 8px rgba(0,0,0,0.1);">
                Belanja Lebih Hemat<br>di <span style="color:#ffe066;">TokoKu</span>
            </h1>
            <p style="font-size:1rem; opacity:0.9; margin-bottom:20px; line-height:1.6;">
                Temukan jutaan produk dengan harga terbaik.<br>Gratis ongkir untuk semua pesanan!
            </p>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="<?= BASE_PATH ?>/produk.php" style="background:white; color:var(--primary); padding:12px 24px; border-radius:6px; font-weight:800; text-decoration:none; font-size:0.9rem; box-shadow:0 4px 12px rgba(0,0,0,0.15); transition:transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
                    🛍️ Belanja Sekarang
                </a>
                <a href="#produk-terbaru" style="background:rgba(255,255,255,0.2); color:white; padding:12px 24px; border-radius:6px; font-weight:700; text-decoration:none; font-size:0.9rem; border:2px solid rgba(255,255,255,0.4); backdrop-filter:blur(4px);">
                    Lihat Produk ↓
                </a>
            </div>
        </div>
        <div style="flex-shrink:0; display:flex; gap:12px; opacity:0.9;">
            <div style="display:flex; flex-direction:column; gap:10px;">
                <div style="background:rgba(255,255,255,0.15); backdrop-filter:blur(8px); border-radius:12px; padding:16px; text-align:center; color:white; border:1px solid rgba(255,255,255,0.3); min-width:100px;">
                    <div style="font-size:1.8rem; font-weight:900;"><?= number_format($pdo->query("SELECT COUNT(*) FROM produk WHERE status='aktif'")->fetchColumn()) ?>+</div>
                    <div style="font-size:0.72rem; opacity:0.85;">Produk</div>
                </div>
                <div style="background:rgba(255,255,255,0.15); backdrop-filter:blur(8px); border-radius:12px; padding:16px; text-align:center; color:white; border:1px solid rgba(255,255,255,0.3);">
                    <div style="font-size:1.8rem; font-weight:900;"><?= number_format($pdo->query("SELECT COUNT(*) FROM users WHERE role='pelanggan'")->fetchColumn()) ?>+</div>
                    <div style="font-size:0.72rem; opacity:0.85;">Pembeli</div>
                </div>
            </div>
            <div style="display:flex; flex-direction:column; gap:10px; margin-top:20px;">
                <div style="background:rgba(255,255,255,0.15); backdrop-filter:blur(8px); border-radius:12px; padding:16px; text-align:center; color:white; border:1px solid rgba(255,255,255,0.3); min-width:100px;">
                    <div style="font-size:1.8rem; font-weight:900;"><?= number_format($pdo->query("SELECT COUNT(*) FROM pesanan WHERE status='selesai'")->fetchColumn()) ?>+</div>
                    <div style="font-size:0.72rem; opacity:0.85;">Transaksi</div>
                </div>
                <div style="background:rgba(255,255,255,0.15); backdrop-filter:blur(8px); border-radius:12px; padding:16px; text-align:center; color:white; border:1px solid rgba(255,255,255,0.3);">
                    <div style="font-size:1.8rem;">⭐</div>
                    <div style="font-size:0.72rem; opacity:0.85;">Terpercaya</div>
                </div>
            </div>
        </div>
    </div>
    <!-- Dekorasi -->
    <div style="position:absolute; bottom:-30px; left:0; right:0; height:60px; background:#f5f5f5; border-radius:50% 50% 0 0 / 100% 100% 0 0;"></div>
</div>

<div class="container" style="padding-top:40px;">

    <!-- PROMO BANNER -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px;">
        <div style="background:linear-gradient(135deg,#667eea,#764ba2); border-radius:10px; padding:16px; color:white; display:flex; align-items:center; gap:12px;">
            <div style="font-size:2rem;">🚚</div>
            <div>
                <div style="font-weight:700; font-size:0.88rem;">Gratis Ongkir</div>
                <div style="font-size:0.75rem; opacity:0.85;">Untuk semua pesanan</div>
            </div>
        </div>
        <div style="background:linear-gradient(135deg,#f093fb,#f5576c); border-radius:10px; padding:16px; color:white; display:flex; align-items:center; gap:12px;">
            <div style="font-size:2rem;">🎟️</div>
            <div>
                <div style="font-weight:700; font-size:0.88rem;">Voucher Diskon</div>
                <div style="font-size:0.75rem; opacity:0.85;">Hingga 50% off</div>
            </div>
        </div>
        <div style="background:linear-gradient(135deg,#4facfe,#00f2fe); border-radius:10px; padding:16px; color:white; display:flex; align-items:center; gap:12px;">
            <div style="font-size:2rem;">🔒</div>
            <div>
                <div style="font-weight:700; font-size:0.88rem;">Transaksi Aman</div>
                <div style="font-size:0.75rem; opacity:0.85;">Dijamin & terpercaya</div>
            </div>
        </div>
    </div>

    <!-- KATEGORI -->
    <div style="margin-bottom:24px;">
        <div class="section-header">
            <h2>📂 Kategori Produk</h2>
            <a href="<?= BASE_PATH ?>/produk.php">Lihat Semua →</a>
        </div>
        <?php
        $katIcons  = ['📱','👕','🍔','📚','🏠','⚽','💄','🎮'];
        $katColors = [
            ['#dbeafe','#1d4ed8'],
            ['#d1fae5','#065f46'],
            ['#fef3c7','#92400e'],
            ['#ede9fe','#4c1d95'],
            ['#fee2e2','#991b1b'],
            ['#f0fdf4','#14532d'],
            ['#fce7f3','#9d174d'],
            ['#e0f2fe','#075985'],
        ];
        ?>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px;">
            <?php foreach ($kats as $i => $kat): ?>
            <a href="<?= BASE_PATH ?>/produk.php?kategori=<?= $kat['id'] ?>"
               style="background:<?= $katColors[$i % count($katColors)][0] ?>; border-radius:10px; padding:14px 10px; text-align:center; text-decoration:none; transition:all 0.2s; border:2px solid transparent;"
               onmouseover="this.style.transform='translateY(-3px)';this.style.borderColor='<?= $katColors[$i % count($katColors)][1] ?>'"
               onmouseout="this.style.transform='';this.style.borderColor='transparent'">
                <div style="font-size:2rem; margin-bottom:6px;"><?= $katIcons[$i % count($katIcons)] ?></div>
                <div style="font-size:0.78rem; font-weight:700; color:<?= $katColors[$i % count($katColors)][1] ?>;">
                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- VOUCHER PROMO -->
    <?php
    $vouchers = $pdo->query("SELECT * FROM voucher WHERE status='aktif' AND (berlaku_hingga IS NULL OR berlaku_hingga >= CURDATE()) AND terpakai < kuota LIMIT 3")->fetchAll();
    if (!empty($vouchers)):
    ?>
    <div style="margin-bottom:24px;">
        <div class="section-header">
            <h2>🎟️ Voucher Tersedia</h2>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:12px;">
            <?php foreach ($vouchers as $v): ?>
            <div style="background:white; border-radius:10px; overflow:hidden; box-shadow:var(--shadow); display:flex; position:relative;">
                <div style="background:linear-gradient(135deg,var(--primary),#ff6b35); width:90px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:white; padding:12px; flex-shrink:0;">
                    <div style="font-size:1.6rem; font-weight:900;">
                        <?= $v['jenis'] === 'persen' ? $v['nilai'].'%' : '50K' ?>
                    </div>
                    <div style="font-size:0.65rem; text-align:center; opacity:0.9;">DISKON</div>
                </div>
                <!-- Garis putus-putus -->
                <div style="width:1px; border-left:2px dashed #e8e8e8; margin:8px 0;"></div>
                <div style="padding:12px 14px; flex:1;">
                    <div style="font-family:monospace; font-weight:800; font-size:1rem; color:var(--primary); margin-bottom:4px; letter-spacing:1px;">
                        <?= htmlspecialchars($v['kode']) ?>
                    </div>
                    <div style="font-size:0.75rem; color:var(--gray); margin-bottom:6px;">
                        <?= $v['jenis'] === 'persen' ? 'Diskon '.$v['nilai'].'%' : 'Potongan '.rupiahFormat($v['nilai']) ?>
                        · Min. <?= rupiahFormat($v['min_belanja']) ?>
                    </div>
                    <div style="font-size:0.7rem; color:#bdbdbd;">
                        Berlaku s/d <?= date('d M Y', strtotime($v['berlaku_hingga'])) ?>
                    </div>
                </div>
                <button onclick="copyVoucher('<?= $v['kode'] ?>')"
                        style="position:absolute; bottom:8px; right:8px; background:var(--primary-light); color:var(--primary); border:none; border-radius:4px; padding:4px 10px; font-size:0.72rem; font-weight:700; cursor:pointer;">
                    Salin
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- PRODUK TERBARU -->
    <div id="produk-terbaru" style="margin-bottom:24px;">
        <div class="section-header">
            <h2>✨ Produk Terbaru</h2>
            <a href="<?= BASE_PATH ?>/produk.php">Lihat Semua →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($produkTerbaru as $p): ?>
            <a href="<?= BASE_PATH ?>/detail_produk.php?id=<?= $p['id'] ?>" class="product-card">
                <div class="product-card-img">
                    <?php if ($p['gambar'] && file_exists(__DIR__ . '/uploads/' . $p['gambar'])): ?>
                        <img src="<?= BASE_PATH ?>/uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>">
                    <?php else: ?>
                        <div style="width:100%;height:100%;background:linear-gradient(135deg,#f5f5f5,#e8e8e8);display:flex;align-items:center;justify-content:center;font-size:3rem;">📦</div>
                    <?php endif; ?>
                    <?php if ($p['stok'] <= 5 && $p['stok'] > 0): ?>
                        <div style="position:absolute;top:8px;left:8px;background:var(--primary);color:white;font-size:0.65rem;font-weight:700;padding:2px 8px;border-radius:4px;">Hampir Habis!</div>
                    <?php endif; ?>
                </div>
                <div class="product-card-body">
                    <div class="product-card-name"><?= htmlspecialchars($p['nama_produk']) ?></div>
                    <?php if ($p['avg_rating'] > 0): ?>
                    <div class="product-card-rating">
                        <span class="stars">⭐</span>
                        <span><?= number_format($p['avg_rating'], 1) ?></span>
                        <span>(<?= $p['total_ulasan'] ?>)</span>
                    </div>
                    <?php endif; ?>
                    <div class="product-card-price"><?= rupiahFormat($p['harga']) ?></div>
                    <div class="product-card-stok">
                        <?php if ($p['stok'] == 0): ?>
                            <span style="color:var(--danger);">❌ Stok Habis</span>
                        <?php else: ?>
                            <span style="color:var(--success);">✅ Stok: <?= $p['stok'] ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- BANNER BAWAH -->
    <div style="background:linear-gradient(135deg,#1e293b,#334155); border-radius:12px; padding:32px; display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; color:white;">
        <div>
            <h3 style="font-size:1.3rem; font-weight:800; margin-bottom:8px;">📱 Belanja Lebih Mudah</h3>
            <p style="opacity:0.8; font-size:0.88rem; margin-bottom:16px;">Daftar sekarang dan dapatkan voucher selamat datang!</p>
            <?php if (!isLogin()): ?>
            <div style="display:flex; gap:10px;">
                <a href="<?= BASE_PATH ?>/register.php" style="background:var(--primary);color:white;padding:10px 20px;border-radius:6px;font-weight:700;text-decoration:none;font-size:0.85rem;">Daftar Gratis</a>
                <a href="<?= BASE_PATH ?>/login.php" style="background:rgba(255,255,255,0.1);color:white;padding:10px 20px;border-radius:6px;font-weight:700;text-decoration:none;font-size:0.85rem;border:1px solid rgba(255,255,255,0.3);">Masuk</a>
            </div>
            <?php else: ?>
            <a href="<?= BASE_PATH ?>/produk.php" style="background:var(--primary);color:white;padding:10px 20px;border-radius:6px;font-weight:700;text-decoration:none;font-size:0.85rem;">Lihat Semua Produk →</a>
            <?php endif; ?>
        </div>
        <div style="font-size:5rem; opacity:0.3;">🛒</div>
    </div>

</div>

<script>
function copyVoucher(kode) {
    navigator.clipboard.writeText(kode).then(() => {
        showToast('Kode voucher ' + kode + ' disalin! 🎟️', 'success');
    }).catch(() => {
        showToast('Kode: ' + kode, 'success');
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>