<?php
require_once __DIR__ . '/config/database.php';
$pdo = getDB();

// ===== AJAX Handler =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if ($action === 'count') {
        if (!isLogin()) { echo json_encode(['count' => 0]); exit; }
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(jumlah),0) as total FROM keranjang WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        echo json_encode(['count' => (int)$stmt->fetch()['total']]);
        exit;
    }

    if (!isLogin()) {
        echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu!']);
        exit;
    }

    if ($action === 'tambah') {
        $produk_id = (int)($_POST['produk_id'] ?? 0);
        $jumlah    = (int)($_POST['jumlah'] ?? 1);

        // Cek stok
        $stok = $pdo->prepare("SELECT stok, nama_produk FROM produk WHERE id = ? AND status = 'aktif'");
        $stok->execute([$produk_id]);
        $produk = $stok->fetch();

        if (!$produk) {
            echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan!']);
            exit;
        }

        // Cek sudah ada di keranjang
        $existing = $pdo->prepare("SELECT id, jumlah FROM keranjang WHERE user_id = ? AND produk_id = ?");
        $existing->execute([$_SESSION['user_id'], $produk_id]);
        $item = $existing->fetch();

        $totalJumlah = ($item ? $item['jumlah'] : 0) + $jumlah;

        if ($totalJumlah > $produk['stok']) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi! Sisa stok: ' . $produk['stok']]);
            exit;
        }

        if ($item) {
            $pdo->prepare("UPDATE keranjang SET jumlah = jumlah + ? WHERE id = ?")
                ->execute([$jumlah, $item['id']]);
        } else {
            $pdo->prepare("INSERT INTO keranjang (user_id, produk_id, jumlah) VALUES (?,?,?)")
                ->execute([$_SESSION['user_id'], $produk_id, $jumlah]);
        }

        echo json_encode(['success' => true, 'message' => $produk['nama_produk'] . ' ditambahkan ke keranjang! 🛒']);
        exit;
    }

    if ($action === 'update') {
        $id     = (int)($_POST['id'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 1);
        if ($jumlah < 1) $jumlah = 1;

        // Cek stok
        $stmt = $pdo->prepare("SELECT p.stok FROM keranjang k JOIN produk p ON k.produk_id = p.id WHERE k.id = ? AND k.user_id = ?");
        $stmt->execute([$id, $_SESSION['user_id']]);
        $row = $stmt->fetch();

        if ($row && $jumlah > $row['stok']) {
            echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi!']);
            exit;
        }

        $pdo->prepare("UPDATE keranjang SET jumlah = ? WHERE id = ? AND user_id = ?")
            ->execute([$jumlah, $id, $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Jumlah diperbarui']);
        exit;
    }

    if ($action === 'hapus') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM keranjang WHERE id = ? AND user_id = ?")
            ->execute([$id, $_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => 'Item dihapus dari keranjang']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenal']);
    exit;
}

// ===== Tampilan Halaman =====
$items    = [];
$subtotal = 0;

if (isLogin()) {
    $stmt = $pdo->prepare("
        SELECT k.*, p.nama_produk, p.harga, p.stok, p.gambar
        FROM keranjang k
        JOIN produk p ON k.produk_id = p.id
        WHERE k.user_id = ?
        ORDER BY k.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $items    = $stmt->fetchAll();
    $subtotal = array_sum(array_map(fn($i) => $i['harga'] * $i['jumlah'], $items));
}

$pageTitle = 'Keranjang Belanja - TokoKu';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= BASE_PATH ?>/index.php">🏠 Beranda</a>
        <span class="sep">›</span>
        <span class="current">Keranjang Belanja</span>
    </div>

    <h1 style="font-size:1.3rem; font-weight:800; margin-bottom:20px; display:flex; align-items:center; gap:8px;">
        🛒 Keranjang Belanja
        <?php if (!empty($items)): ?>
            <span style="background:var(--primary); color:white; font-size:0.75rem; padding:2px 10px; border-radius:20px; font-weight:700;">
                <?= count($items) ?> item
            </span>
        <?php endif; ?>
    </h1>

    <?php if (!isLogin()): ?>
        <!-- Belum login -->
        <div class="card" style="padding:60px; text-align:center;">
            <div style="font-size:4rem; margin-bottom:16px;">🔐</div>
            <h3 style="margin-bottom:8px;">Silakan Login Terlebih Dahulu</h3>
            <p style="color:var(--gray); margin-bottom:20px;">Login untuk melihat keranjang belanja Anda.</p>
            <a href="<?= BASE_PATH ?>/login.php" class="btn btn-primary">Login Sekarang</a>
        </div>

    <?php elseif (empty($items)): ?>
        <!-- Keranjang kosong -->
        <div class="card" style="padding:60px; text-align:center;">
            <div style="font-size:5rem; margin-bottom:16px;">🛒</div>
            <h3 style="margin-bottom:8px; font-size:1.1rem;">Keranjang Masih Kosong</h3>
            <p style="color:var(--gray); margin-bottom:24px;">Yuk mulai belanja dan tambahkan produk ke keranjang!</p>
            <a href="<?= BASE_PATH ?>/produk.php" class="btn btn-primary btn-lg">🛍️ Mulai Belanja</a>
        </div>

    <?php else: ?>
        <div style="display:grid; grid-template-columns:1fr 320px; gap:20px; align-items:start;">

            <!-- DAFTAR ITEM -->
            <div>
                <!-- Pilih Semua -->
                <div class="card" style="padding:14px 20px; margin-bottom:10px; display:flex; align-items:center; gap:12px;">
                    <input type="checkbox" id="checkAll" onchange="toggleAll(this)"
                           style="width:18px; height:18px; accent-color:var(--primary); cursor:pointer;">
                    <label for="checkAll" style="font-size:0.85rem; font-weight:600; cursor:pointer;">
                        Pilih Semua (<?= count($items) ?> produk)
                    </label>
                    <button onclick="hapusTerpilih()" id="btnHapusTerpilih"
                            style="margin-left:auto; background:none; border:none; color:var(--gray); font-size:0.8rem; cursor:pointer; display:none;">
                        🗑️ Hapus Terpilih
                    </button>
                </div>

                <!-- Item List -->
                <div class="card" style="padding:0 20px;">
                    <?php foreach ($items as $idx => $item): ?>
                    <div class="keranjang-item" id="item-<?= $item['id'] ?>"
                         style="padding:16px 0; border-bottom:<?= $idx < count($items)-1 ? '1px solid var(--border)' : 'none' ?>;">
                        <div style="display:flex; align-items:center; gap:14px; width:100%;">

                            <!-- Checkbox -->
                            <input type="checkbox" class="item-check"
                                   data-id="<?= $item['id'] ?>"
                                   data-subtotal="<?= $item['harga'] * $item['jumlah'] ?>"
                                   onchange="updateRingkasan()"
                                   style="width:18px; height:18px; accent-color:var(--primary); cursor:pointer; flex-shrink:0;">

                            <!-- Foto -->
                            <a href="<?= BASE_PATH ?>/detail_produk.php?id=<?= $item['produk_id'] ?>"
                               style="flex-shrink:0;">
                                <div style="width:88px; height:88px; border-radius:8px; overflow:hidden; border:1px solid var(--border); background:#f5f5f5; display:flex; align-items:center; justify-content:center;">
                                    <?php if ($item['gambar'] && file_exists(__DIR__ . '/uploads/' . $item['gambar'])): ?>
                                        <img src="<?= BASE_PATH ?>/uploads/<?= htmlspecialchars($item['gambar']) ?>"
                                             style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span style="font-size:2.5rem;">📦</span>
                                    <?php endif; ?>
                                </div>
                            </a>

                            <!-- Info Produk -->
                            <div style="flex:1; min-width:0;">
                                <a href="<?= BASE_PATH ?>/detail_produk.php?id=<?= $item['produk_id'] ?>"
                                   style="text-decoration:none; color:var(--dark);">
                                    <div style="font-size:0.9rem; font-weight:600; margin-bottom:4px; line-height:1.4;">
                                        <?= htmlspecialchars($item['nama_produk']) ?>
                                    </div>
                                </a>
                                <div style="color:var(--primary); font-weight:700; font-size:1rem; margin-bottom:10px;">
                                    <?= rupiahFormat($item['harga']) ?>
                                </div>

                                <!-- Qty Control -->
                                <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                    <div style="display:flex; align-items:center; border:1.5px solid var(--border); border-radius:6px; overflow:hidden;">
                                        <button onclick="updateQtyKeranjang(<?= $item['id'] ?>, -1)"
                                                style="width:34px; height:34px; border:none; background:#f5f5f5; color:var(--dark); cursor:pointer; font-size:1.1rem; font-weight:700; transition:background 0.2s;"
                                                onmouseover="this.style.background='var(--primary)';this.style.color='white'"
                                                onmouseout="this.style.background='#f5f5f5';this.style.color='var(--dark)'">−</button>
                                        <input type="number"
                                               id="qty-<?= $item['id'] ?>"
                                               value="<?= $item['jumlah'] ?>"
                                               min="1" max="<?= $item['stok'] ?>"
                                               onchange="setQtyKeranjang(<?= $item['id'] ?>, this.value)"
                                               style="width:48px; height:34px; text-align:center; border:none; border-left:1.5px solid var(--border); border-right:1.5px solid var(--border); font-size:0.9rem; outline:none; font-weight:600;">
                                        <button onclick="updateQtyKeranjang(<?= $item['id'] ?>, 1)"
                                                style="width:34px; height:34px; border:none; background:#f5f5f5; color:var(--dark); cursor:pointer; font-size:1.1rem; font-weight:700; transition:background 0.2s;"
                                                onmouseover="this.style.background='var(--primary)';this.style.color='white'"
                                                onmouseout="this.style.background='#f5f5f5';this.style.color='var(--dark)'">+</button>
                                    </div>
                                    <span style="font-size:0.75rem; color:var(--gray);">Stok: <?= $item['stok'] ?></span>
                                </div>
                            </div>

                            <!-- Subtotal + Hapus -->
                            <div style="text-align:right; flex-shrink:0;">
                                <div style="font-weight:800; color:var(--primary); font-size:1rem; margin-bottom:12px;"
                                     id="subtotal-<?= $item['id'] ?>">
                                    <?= rupiahFormat($item['harga'] * $item['jumlah']) ?>
                                </div>
                                <button onclick="hapusItemKeranjang(<?= $item['id'] ?>)"
                                        style="background:none; border:1px solid #e0e0e0; color:var(--gray); padding:6px 12px; border-radius:6px; cursor:pointer; font-size:0.75rem; transition:all 0.2s;"
                                        onmouseover="this.style.borderColor='var(--danger)';this.style.color='var(--danger)'"
                                        onmouseout="this.style.borderColor='#e0e0e0';this.style.color='var(--gray)'">
                                    🗑️ Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- RINGKASAN PESANAN -->
            <div style="position:sticky; top:80px;">
                <div class="card" style="padding:20px;">
                    <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:16px; padding-bottom:12px; border-bottom:2px solid var(--primary);">
                        🧾 Ringkasan Pesanan
                    </h3>

                    <div style="font-size:0.85rem; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; padding:6px 0;">
                            <span style="color:var(--gray);">Subtotal</span>
                            <span id="ringkasanSubtotal" style="font-weight:600;"><?= rupiahFormat($subtotal) ?></span>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:6px 0;">
                            <span style="color:var(--gray);">Ongkir</span>
                            <span style="color:var(--success); font-weight:600;">Gratis 🎉</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; padding:10px 0; border-top:2px solid var(--border); margin-top:6px; font-weight:800; font-size:1rem;">
                            <span>Total</span>
                            <span style="color:var(--primary);" id="ringkasanTotal"><?= rupiahFormat($subtotal) ?></span>
                        </div>
                    </div>

                    <a href="<?= BASE_PATH ?>/checkout.php"
                       class="btn btn-primary btn-full btn-lg"
                       style="margin-bottom:10px; justify-content:center;">
                        ✅ Lanjut Checkout
                    </a>

                    <a href="<?= BASE_PATH ?>/produk.php"
                       class="btn btn-outline btn-full"
                       style="justify-content:center; font-size:0.82rem;">
                        ← Lanjut Belanja
                    </a>
                </div>

                <!-- Keuntungan belanja -->
                <div class="card" style="padding:16px; margin-top:12px;">
                    <div style="font-size:0.78rem; color:var(--gray); display:flex; flex-direction:column; gap:8px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>🚚</span> <span>Gratis ongkir ke seluruh Indonesia</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>🔒</span> <span>Transaksi aman & terjamin</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>↩️</span> <span>Garansi uang kembali 100%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
const hargaMap = {
    <?php foreach ($items as $item): ?>
    <?= $item['id'] ?>: <?= $item['harga'] ?>,
    <?php endforeach; ?>
};

function updateQtyKeranjang(id, delta) {
    const input  = document.getElementById('qty-' + id);
    const newVal = Math.max(1, Math.min(parseInt(input.max), parseInt(input.value) + delta));
    input.value  = newVal;
    setQtyKeranjang(id, newVal);
}

function setQtyKeranjang(id, jumlah) {
    jumlah = Math.max(1, parseInt(jumlah));
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', id);
    formData.append('jumlah', jumlah);

    fetch('<?= BASE_PATH ?>/keranjang.php', { method:'POST', body:formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // Update subtotal item
                const subtotalEl = document.getElementById('subtotal-' + id);
                if (subtotalEl && hargaMap[id]) {
                    const total = hargaMap[id] * jumlah;
                    subtotalEl.textContent = 'Rp ' + total.toLocaleString('id-ID');
                }
                updateRingkasan();
            } else {
                showToast(data.message, 'error');
                location.reload();
            }
        });
}

function hapusItemKeranjang(id) {
    const formData = new FormData();
    formData.append('action', 'hapus');
    formData.append('id', id);

    fetch('<?= BASE_PATH ?>/keranjang.php', { method:'POST', body:formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const el = document.getElementById('item-' + id);
                el.style.transition = 'all 0.3s';
                el.style.opacity    = '0';
                el.style.height     = '0';
                el.style.padding    = '0';
                setTimeout(() => { el.remove(); updateRingkasan(); updateCartBadge(); }, 300);
                showToast('Item dihapus dari keranjang', 'success');
            }
        });
}

function updateRingkasan() {
    let total = 0;
    const checks = document.querySelectorAll('.item-check');
    const anyChecked = [...checks].some(c => c.checked);

    if (anyChecked) {
        checks.forEach(c => {
            if (c.checked) {
                const id  = c.dataset.id;
                const qty = parseInt(document.getElementById('qty-' + id)?.value || 1);
                total    += (hargaMap[id] || 0) * qty;
            }
        });
    } else {
        // Hitung semua jika tidak ada yang dicentang
        Object.entries(hargaMap).forEach(([id, harga]) => {
            const qty = parseInt(document.getElementById('qty-' + id)?.value || 1);
            total    += harga * qty;
        });
    }

    const fmt = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('ringkasanSubtotal').textContent = fmt;
    document.getElementById('ringkasanTotal').textContent    = fmt;
}

function toggleAll(masterCheck) {
    document.querySelectorAll('.item-check').forEach(c => c.checked = masterCheck.checked);
    const btnHapus = document.getElementById('btnHapusTerpilih');
    btnHapus.style.display = masterCheck.checked ? 'block' : 'none';
    updateRingkasan();
}

function hapusTerpilih() {
    const terpilih = [...document.querySelectorAll('.item-check:checked')].map(c => c.dataset.id);
    if (!terpilih.length) return;
    if (!confirm(`Hapus ${terpilih.length} item terpilih?`)) return;
    terpilih.forEach(id => hapusItemKeranjang(id));
    document.getElementById('btnHapusTerpilih').style.display = 'none';
    document.getElementById('checkAll').checked = false;
}

// Tampilkan tombol hapus terpilih saat ada yang dicentang
document.addEventListener('change', e => {
    if (e.target.classList.contains('item-check')) {
        const anyChecked = [...document.querySelectorAll('.item-check')].some(c => c.checked);
        document.getElementById('btnHapusTerpilih').style.display = anyChecked ? 'block' : 'none';
        updateRingkasan();
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>