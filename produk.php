<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Semua Produk - TokoKu';
$pdo = getDB();

$kategoriId = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$search     = trim($_GET['q'] ?? '');
$sort       = $_GET['sort'] ?? 'terbaru';

$where  = ["p.status = 'aktif'"];
$params = [];

if ($kategoriId) { $where[] = "p.kategori_id = ?"; $params[] = $kategoriId; }
if ($search)     { $where[] = "p.nama_produk LIKE ?"; $params[] = "%$search%"; }

$orderBy = match($sort) {
    'termurah' => 'p.harga ASC',
    'termahal' => 'p.harga DESC',
    default    => 'p.created_at DESC'
};

$whereStr = implode(' AND ', $where);

$perPage     = 12;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($currentPage - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM produk p LEFT JOIN kategori k ON p.kategori_id = k.id WHERE $whereStr");
$countStmt->execute($params);
$totalData  = $countStmt->fetchColumn();
$totalPages = (int)ceil($totalData / $perPage);

$sql = "SELECT p.*, k.nama_kategori,
        (SELECT ROUND(AVG(rating),1) FROM ulasan WHERE produk_id = p.id) as avg_rating,
        (SELECT COUNT(*) FROM ulasan WHERE produk_id = p.id) as total_ulasan
        FROM produk p
        LEFT JOIN kategori k ON p.kategori_id = k.id
        WHERE $whereStr ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produkList = $stmt->fetchAll();

$kategoriList = $pdo->query("SELECT * FROM kategori")->fetchAll();

function pageUrlProduk($page, $kategoriId, $search, $sort) {
    $p = ['page' => $page];
    if ($kategoriId) $p['kategori'] = $kategoriId;
    if ($search)     $p['q']        = $search;
    if ($sort && $sort !== 'terbaru') $p['sort'] = $sort;
    return BASE_PATH . '/produk.php?' . http_build_query($p);
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
.pagination-toko{display:flex;align-items:center;justify-content:center;gap:6px;padding:32px 0 16px}
.pagination-toko a,.pagination-toko span{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 6px;border-radius:10px;font-size:.88rem;font-weight:600;text-decoration:none;transition:all .2s}
.pagination-toko a{background:white;color:#424242;border:1.5px solid #e0e0e0;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.pagination-toko a:hover{border-color:#ee4d2d;color:#ee4d2d;background:#fff0ed}
.pagination-toko .pg-active{background:#ee4d2d;color:white;border:1.5px solid #ee4d2d;box-shadow:0 2px 8px rgba(238,77,45,.3)}
.pagination-toko .pg-dots{background:transparent;border:none;color:#9e9e9e;cursor:default;box-shadow:none}
.pagination-info-toko{text-align:center;font-size:.82rem;color:#9e9e9e;margin-top:4px;padding-bottom:16px}
.produk-card-link{text-decoration:none;color:inherit;display:block}
.product-card{transition:all .2s;cursor:pointer}
.product-card:hover{transform:translateY(-4px);box-shadow:0 8px 24px rgba(0,0,0,.12)}
.mini-stars{color:#f5a623;font-size:.75rem;letter-spacing:1px}
</style>

<div class="container">
    <h1 style="margin-bottom:20px;font-size:1.6rem">📦 Semua Produk</h1>

    <div style="background:white;padding:20px;border-radius:12px;margin-bottom:24px;box-shadow:var(--shadow)">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
            <div style="flex:1;min-width:200px">
                <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px">🔍 Cari Produk</label>
                <input type="text" name="q" class="form-control" placeholder="Nama produk..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px">📂 Kategori</label>
                <select name="kategori" class="form-control">
                    <option value="0">Semua</option>
                    <?php foreach ($kategoriList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $kategoriId == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:4px">↕️ Urutkan</label>
                <select name="sort" class="form-control">
                    <option value="terbaru"  <?= $sort==='terbaru'?'selected':'' ?>>Terbaru</option>
                    <option value="termurah" <?= $sort==='termurah'?'selected':'' ?>>Termurah</option>
                    <option value="termahal" <?= $sort==='termahal'?'selected':'' ?>>Termahal</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
            <?php if ($search || $kategoriId || $sort !== 'terbaru'): ?>
                <a href="<?= BASE_PATH ?>/produk.php" class="btn btn-outline">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
        <p style="color:var(--gray);font-size:.9rem;margin:0">
            <?php if ($totalData > 0): ?>
                Menampilkan <strong style="color:#212121"><?= $offset+1 ?>–<?= min($offset+$perPage,$totalData) ?></strong> dari <strong style="color:#212121"><?= $totalData ?></strong> produk
                <?php if ($search): ?>· hasil "<strong><?= htmlspecialchars($search) ?></strong>"<?php endif; ?>
            <?php else: ?>0 produk ditemukan<?php endif; ?>
        </p>
        <?php if ($totalPages > 1): ?>
            <span style="font-size:.82rem;color:#9e9e9e">Halaman <?= $currentPage ?> dari <?= $totalPages ?></span>
        <?php endif; ?>
    </div>

    <?php if (empty($produkList)): ?>
        <div style="text-align:center;padding:60px 20px;background:white;border-radius:16px;box-shadow:var(--shadow)">
            <div style="font-size:3rem;margin-bottom:12px">😔</div>
            <div style="font-size:1rem;font-weight:600;color:#424242;margin-bottom:6px">Tidak ada produk ditemukan</div>
            <div style="font-size:.85rem;color:#9e9e9e;margin-bottom:20px">Coba ubah kata kunci atau filter pencarian</div>
            <a href="<?= BASE_PATH ?>/produk.php" class="btn btn-primary">Lihat Semua Produk</a>
        </div>
    <?php else: ?>

    <div class="product-grid">
        <?php foreach ($produkList as $p): ?>
        <div class="card product-card">
            <!-- Gambar bisa diklik -->
            <a href="<?= BASE_PATH ?>/detail_produk.php?id=<?= $p['id'] ?>" class="produk-card-link">
                <div class="product-img">
                    <?php if ($p['gambar'] && file_exists(__DIR__.'/uploads/'.$p['gambar'])): ?>
                        <img src="<?= BASE_PATH ?>/uploads/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama_produk']) ?>" style="width:100%;height:200px;object-fit:cover">
                    <?php else: ?>📦<?php endif; ?>
                </div>
            </a>
            <div class="card-body">
                <div style="font-size:.75rem;color:var(--gray);margin-bottom:4px">📂 <?= htmlspecialchars($p['nama_kategori'] ?? '-') ?></div>
                <!-- Nama bisa diklik -->
                <a href="<?= BASE_PATH ?>/detail_produk.php?id=<?= $p['id'] ?>" style="text-decoration:none;color:inherit">
                    <div class="product-name" style="margin-bottom:4px"><?= htmlspecialchars($p['nama_produk']) ?></div>
                </a>
                <!-- Rating mini -->
                <?php if ($p['total_ulasan'] > 0): ?>
                <div style="display:flex;align-items:center;gap:4px;margin-bottom:4px">
                    <span class="mini-stars"><?= str_repeat('★', round($p['avg_rating'])) . str_repeat('☆', 5 - round($p['avg_rating'])) ?></span>
                    <span style="font-size:.72rem;color:#9e9e9e">(<?= $p['total_ulasan'] ?>)</span>
                </div>
                <?php endif; ?>
                <div class="product-price"><?= rupiahFormat($p['harga']) ?></div>
                <div class="product-stok">
                    <?= $p['stok']>10?'✅':($p['stok']>0?'⚠️':'❌') ?> Stok: <?= $p['stok'] ?>
                </div>
                <?php if ($p['stok'] > 0): ?>
                    <?php if (isLogin()): ?>
                        <button onclick="tambahKeKeranjang(<?= $p['id'] ?>)" class="btn btn-primary btn-sm btn-full">🛒 Tambah ke Keranjang</button>
                    <?php else: ?>
                        <a href="<?= BASE_PATH ?>/login.php" class="btn btn-outline btn-sm btn-full">🔐 Login untuk Beli</a>
                    <?php endif; ?>
                <?php else: ?>
                    <button disabled class="btn btn-sm btn-full" style="background:#e2e8f0;color:#94a3b8;cursor:not-allowed">❌ Habis</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination-toko">
        <?php if ($currentPage > 1): ?>
            <a href="<?= pageUrlProduk($currentPage-1,$kategoriId,$search,$sort) ?>" class="pg-arrow">←</a>
        <?php endif; ?>
        <?php for ($pg=1;$pg<=$totalPages;$pg++):
            if ($pg===1||$pg===$totalPages||($pg>=$currentPage-2&&$pg<=$currentPage+2)): ?>
                <?php if ($pg===$currentPage): ?>
                    <span class="pg-active"><?= $pg ?></span>
                <?php else: ?>
                    <a href="<?= pageUrlProduk($pg,$kategoriId,$search,$sort) ?>"><?= $pg ?></a>
                <?php endif; ?>
            <?php elseif ($pg===$currentPage-3||$pg===$currentPage+3): ?>
                <span class="pg-dots">...</span>
            <?php endif;
        endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= pageUrlProduk($currentPage+1,$kategoriId,$search,$sort) ?>" class="pg-arrow">→</a>
        <?php endif; ?>
    </div>
    <div class="pagination-info-toko">Produk <?= $offset+1 ?>–<?= min($offset+$perPage,$totalData) ?> dari <?= $totalData ?></div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>