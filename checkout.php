<?php
require_once __DIR__ . '/config/database.php';
requireLogin();
$pdo = getDB();

// Info rekening bank (dipakai di halaman sukses)
$rekeningBank = [
    'Transfer Bank' => [
        ['bank' => 'BCA',     'logo' => '🔵', 'norek' => '1234567890',  'atas_nama' => 'PT TokoKu Indonesia'],
        ['bank' => 'BRI',     'logo' => '🔵', 'norek' => '0987654321',  'atas_nama' => 'PT TokoKu Indonesia'],
        ['bank' => 'Mandiri', 'logo' => '🟡', 'norek' => '1122334455',  'atas_nama' => 'PT TokoKu Indonesia'],
        ['bank' => 'BNI',     'logo' => '🟠', 'norek' => '5544332211',  'atas_nama' => 'PT TokoKu Indonesia'],
    ],
    'E-Wallet' => [
        ['bank' => 'GoPay',      'logo' => '🟢', 'norek' => '0812-3456-7890', 'atas_nama' => 'TokoKu Official'],
        ['bank' => 'OVO',        'logo' => '🟣', 'norek' => '0812-3456-7890', 'atas_nama' => 'TokoKu Official'],
        ['bank' => 'DANA',       'logo' => '🔵', 'norek' => '0812-3456-7890', 'atas_nama' => 'TokoKu Official'],
        ['bank' => 'ShopeePay', 'logo' => '🟠', 'norek' => '0812-3456-7890', 'atas_nama' => 'TokoKu Official'],
    ],
];

// Cek keranjang tidak kosong
$stmt = $pdo->prepare("SELECT COUNT(*) as c FROM keranjang WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetch()['c'] == 0 && !isset($_GET['sukses'])) {
    header('Location: ' . BASE_PATH . '/keranjang.php');
    exit;
}

// ===== AJAX: Cek Voucher =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cek_voucher') {
    header('Content-Type: application/json');

    $kode = strtoupper(trim($_POST['kode'] ?? ''));

    $stmt = $pdo->prepare("SELECT SUM(k.jumlah * p.harga) as total FROM keranjang k JOIN produk p ON k.produk_id = p.id WHERE k.user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $total = $stmt->fetch()['total'] ?? 0;

    $vStmt = $pdo->prepare("SELECT * FROM voucher WHERE kode = ? AND status = 'aktif' AND (berlaku_hingga IS NULL OR berlaku_hingga >= CURDATE()) AND terpakai < kuota");
    $vStmt->execute([$kode]);
    $voucher = $vStmt->fetch();

    if (!$voucher) {
        echo json_encode(['success' => false, 'message' => 'Voucher tidak valid atau sudah habis!']);
    } elseif ($total < $voucher['min_belanja']) {
        echo json_encode(['success' => false, 'message' => 'Minimum belanja ' . rupiahFormat($voucher['min_belanja']) . ' untuk voucher ini!']);
    } else {
        if ($voucher['jenis'] === 'persen') {
            $diskon = ($total * $voucher['nilai']) / 100;
            if ($voucher['maks_diskon'] > 0) $diskon = min($diskon, $voucher['maks_diskon']);
        } else {
            $diskon = $voucher['nilai'];
        }
        $total_bayar = $total - $diskon;
        echo json_encode([
            'success'     => true,
            'message'     => 'Voucher valid! Hemat ' . rupiahFormat($diskon),
            'diskon'      => $diskon,
            'total_bayar' => $total_bayar,
            'diskon_fmt'  => rupiahFormat($diskon),
            'total_fmt'   => rupiahFormat($total_bayar),
        ]);
    }
    exit;
}

// ===== PROSES CHECKOUT =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $alamat       = trim($_POST['alamat'] ?? '');
    $metode_bayar = $_POST['metode_bayar'] ?? '';
    $catatan      = trim($_POST['catatan'] ?? '');
    $voucher_kode = strtoupper(trim($_POST['voucher_kode'] ?? ''));
    $error        = '';

    if (!$alamat || !$metode_bayar) {
        $error = 'Isi alamat dan metode pembayaran!';
    } else {
        $stmt = $pdo->prepare("SELECT k.*, p.harga, p.nama_produk, p.stok FROM keranjang k JOIN produk p ON k.produk_id = p.id WHERE k.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $items = $stmt->fetchAll();

        foreach ($items as $item) {
            if ($item['jumlah'] > $item['stok']) {
                $error = "Stok {$item['nama_produk']} tidak mencukupi!";
                break;
            }
        }

        if (!$error) {
            $subtotal = array_sum(array_map(fn($i) => $i['harga'] * $i['jumlah'], $items));
            $diskon   = 0;
            $voucher  = null;

            if ($voucher_kode) {
                $vStmt = $pdo->prepare("SELECT * FROM voucher WHERE kode = ? AND status = 'aktif' AND (berlaku_hingga IS NULL OR berlaku_hingga >= CURDATE()) AND terpakai < kuota");
                $vStmt->execute([$voucher_kode]);
                $voucher = $vStmt->fetch();

                if ($voucher && $subtotal >= $voucher['min_belanja']) {
                    if ($voucher['jenis'] === 'persen') {
                        $diskon = ($subtotal * $voucher['nilai']) / 100;
                        if ($voucher['maks_diskon'] > 0) $diskon = min($diskon, $voucher['maks_diskon']);
                    } else {
                        $diskon = $voucher['nilai'];
                    }
                } else {
                    $voucher_kode = '';
                    $diskon       = 0;
                }
            }

            $total = $subtotal - $diskon;
            $kode  = generateKodePesanan();

            try {
                $pdo->beginTransaction();

                $pdo->prepare("INSERT INTO pesanan (user_id, kode_pesanan, total_harga, alamat_pengiriman, metode_bayar, catatan, voucher_kode, diskon) VALUES (?,?,?,?,?,?,?,?)")
                    ->execute([$_SESSION['user_id'], $kode, $total, $alamat, $metode_bayar, $catatan, $voucher_kode ?: null, $diskon]);
                $pesananId = $pdo->lastInsertId();

                foreach ($items as $item) {
                    $subtotalItem = $item['harga'] * $item['jumlah'];
                    $pdo->prepare("INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, harga, jumlah, subtotal) VALUES (?,?,?,?,?,?)")
                        ->execute([$pesananId, $item['produk_id'], $item['nama_produk'], $item['harga'], $item['jumlah'], $subtotalItem]);
                    $pdo->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?")
                        ->execute([$item['jumlah'], $item['produk_id']]);
                    $pdo->prepare("INSERT INTO riwayat_stok (produk_id, jenis, jumlah, keterangan) VALUES (?,?,?,?)")
                        ->execute([$item['produk_id'], 'keluar', $item['jumlah'], "Pesanan #$kode"]);
                }

                if ($voucher) {
                    $pdo->prepare("UPDATE voucher SET terpakai = terpakai + 1 WHERE id = ?")
                        ->execute([$voucher['id']]);
                }

                // Buat pesan notifikasi sesuai metode bayar
                if ($metode_bayar === 'COD') {
                    $pesanNotif = "Pesanan #{$kode} senilai " . rupiahFormat($total) . " berhasil dibuat. Metode: COD — Siapkan uang tunai saat kurir tiba.";
                } elseif ($metode_bayar === 'Transfer Bank') {
                    $pesanNotif = "Pesanan #{$kode} senilai " . rupiahFormat($total) . " berhasil dibuat. Silakan transfer ke BCA 1234567890 / BRI 0987654321 / Mandiri 1122334455 / BNI 5544332211 a/n PT TokoKu Indonesia. Batas bayar 24 jam.";
                } elseif ($metode_bayar === 'E-Wallet') {
                    $pesanNotif = "Pesanan #{$kode} senilai " . rupiahFormat($total) . " berhasil dibuat. Silakan transfer ke GoPay/OVO/DANA/ShopeePay: 0812-3456-7890 a/n TokoKu Official. Batas bayar 24 jam.";
                } else {
                    $pesanNotif = "Pesanan #{$kode} senilai " . rupiahFormat($total) . " berhasil dibuat. Tim kami akan menghubungi Anda untuk proses pembayaran kartu kredit.";
                }

                $pdo->prepare("INSERT INTO notifikasi (user_id, judul, pesan, tipe) VALUES (?,?,?,?)")
                    ->execute([
                        $_SESSION['user_id'],
                        '🎉 Pesanan #' . $kode . ' Berhasil!',
                        $pesanNotif,
                        'pesanan'
                    ]);

                $pdo->prepare("DELETE FROM keranjang WHERE user_id = ?")->execute([$_SESSION['user_id']]);
                $pdo->commit();

                header('Location: ' . BASE_PATH . '/checkout.php?sukses=' . urlencode($kode));
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Terjadi kesalahan: ' . $e->getMessage();
            }
        }
    }
}

// ===== HALAMAN SUKSES =====
if (isset($_GET['sukses'])) {
    $kode = $_GET['sukses'];

    $stmt = $pdo->prepare("SELECT * FROM pesanan WHERE kode_pesanan = ? AND user_id = ?");
    $stmt->execute([$kode, $_SESSION['user_id']]);
    $pesanan = $stmt->fetch();

    if (!$pesanan) {
        header('Location: ' . BASE_PATH . '/index.php'); exit;
    }

    $batasWaktu  = date('d M Y H:i', strtotime($pesanan['created_at'] . ' +24 hours'));
    $metodeBayar = $pesanan['metode_bayar'];
    $rekeningList = $rekeningBank[$metodeBayar] ?? null;

    $pageTitle = 'Pesanan Berhasil! - TokoKu';
    require_once __DIR__ . '/includes/header.php';
    ?>

    <div class="container" style="max-width:680px;">

        <!-- STATUS BERHASIL -->
        <div class="card" style="text-align:center; padding:32px 24px; margin-bottom:16px;">
            <div style="width:72px; height:72px; background:#e8f5e9; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2.2rem; margin:0 auto 16px;">
                🎉
            </div>
            <h2 style="color:var(--success); font-size:1.3rem; font-weight:800; margin-bottom:6px;">
                Pesanan Berhasil Dibuat!
            </h2>
            <p style="color:var(--gray); font-size:0.85rem; margin-bottom:16px;">
                Selesaikan pembayaran sebelum
                <strong style="color:var(--danger);"><?= $batasWaktu ?> WIB</strong>
            </p>
            <div style="background:#f5f5f5; border-radius:8px; padding:14px 20px; display:inline-flex; align-items:center; gap:12px;">
                <div>
                    <div style="font-size:0.72rem; color:var(--gray); margin-bottom:2px;">Kode Pesanan</div>
                    <div style="font-family:monospace; font-weight:800; font-size:1rem; color:var(--dark); letter-spacing:0.5px;">
                        <?= htmlspecialchars($kode) ?>
                    </div>
                </div>
                <button onclick="copyText('<?= $kode ?>')"
                        style="background:var(--primary); color:white; border:none; border-radius:6px; padding:6px 12px; font-size:0.75rem; font-weight:700; cursor:pointer;">
                    Salin
                </button>
            </div>
        </div>

        <!-- RINGKASAN PEMBAYARAN -->
        <div class="card" style="padding:20px; margin-bottom:16px;">
            <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                💰 Ringkasan Pembayaran
            </h3>
            <div style="font-size:0.85rem;">
                <div style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
                    <span style="color:var(--gray);">Metode Pembayaran</span>
                    <span style="font-weight:600;"><?= htmlspecialchars($pesanan['metode_bayar']) ?></span>
                </div>
                <?php if ($pesanan['diskon'] > 0): ?>
                <div style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
                    <span style="color:var(--gray);">Diskon Voucher</span>
                    <span style="color:var(--success); font-weight:600;">- <?= rupiahFormat($pesanan['diskon']) ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex; justify-content:space-between; padding:7px 0; border-bottom:1px solid var(--border);">
                    <span style="color:var(--gray);">Ongkos Kirim</span>
                    <span style="color:var(--success); font-weight:600;">Gratis 🎉</span>
                </div>
                <div style="display:flex; justify-content:space-between; padding:12px 0 4px; font-weight:800; font-size:1.1rem;">
                    <span>Total yang Harus Dibayar</span>
                    <span style="color:var(--primary);"><?= rupiahFormat($pesanan['total_harga']) ?></span>
                </div>
            </div>
        </div>

        <!-- CARA PEMBAYARAN -->
        <?php if ($metodeBayar === 'COD'): ?>
        <div class="card" style="padding:20px; margin-bottom:16px;">
            <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                💵 Informasi Pembayaran COD
            </h3>
            <div style="background:#e8f5e9; border-radius:8px; padding:16px; display:flex; gap:14px; align-items:flex-start;">
                <span style="font-size:2rem; flex-shrink:0;">✅</span>
                <div>
                    <div style="font-weight:700; font-size:0.9rem; margin-bottom:6px;">Bayar di Tempat (COD)</div>
                    <p style="font-size:0.82rem; color:#2e7d32; line-height:1.7;">
                        Siapkan uang tunai sebesar <strong><?= rupiahFormat($pesanan['total_harga']) ?></strong>
                        saat kurir tiba. Pastikan Anda ada di alamat pengiriman untuk menerima paket.
                    </p>
                </div>
            </div>
            <div style="margin-top:12px; padding:12px 16px; background:#fff8e1; border-radius:8px; font-size:0.8rem; color:#795548;">
                💡 <strong>Tips:</strong> Periksa kondisi paket sebelum membayar ke kurir.
            </div>
        </div>

        <?php elseif ($metodeBayar === 'Kartu Kredit'): ?>
        <div class="card" style="padding:20px; margin-bottom:16px;">
            <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                💳 Informasi Pembayaran Kartu Kredit
            </h3>
            <div style="background:#e3f2fd; border-radius:8px; padding:16px; display:flex; gap:14px; align-items:flex-start;">
                <span style="font-size:2rem; flex-shrink:0;">💳</span>
                <div>
                    <div style="font-weight:700; font-size:0.9rem; margin-bottom:6px;">Pembayaran Kartu Kredit</div>
                    <p style="font-size:0.82rem; color:#1565c0; line-height:1.7;">
                        Pembayaran sebesar <strong><?= rupiahFormat($pesanan['total_harga']) ?></strong>
                        akan diproses melalui payment gateway. Tim kami akan menghubungi Anda dalam 1x24 jam.
                    </p>
                </div>
            </div>
        </div>

        <?php elseif ($rekeningList): ?>
        <div class="card" style="padding:20px; margin-bottom:16px;">
            <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                🏦 Informasi <?= $metodeBayar === 'E-Wallet' ? 'E-Wallet' : 'Transfer Bank' ?>
            </h3>

            <!-- Countdown -->
            <div style="background:#fff3f0; border:1px solid #ffd5cc; border-radius:8px; padding:12px 16px; margin-bottom:16px; display:flex; align-items:center; gap:10px;">
                <span style="font-size:1.3rem;">⏰</span>
                <div>
                    <div style="font-size:0.78rem; color:var(--gray);">Selesaikan pembayaran dalam</div>
                    <div style="font-weight:800; color:var(--danger); font-size:1rem;" id="countdown">23:59:59</div>
                </div>
                <div style="margin-left:auto; font-size:0.75rem; color:var(--gray); text-align:right;">
                    Batas:<br><strong><?= $batasWaktu ?> WIB</strong>
                </div>
            </div>

            <!-- Nominal -->
            <div style="background:linear-gradient(135deg,#fff0ed,#fff8f6); border:2px solid var(--primary); border-radius:10px; padding:16px 20px; margin-bottom:16px; text-align:center;">
                <div style="font-size:0.78rem; color:var(--gray); margin-bottom:4px;">Transfer tepat sebesar</div>
                <div style="font-size:1.6rem; font-weight:900; color:var(--primary); letter-spacing:0.5px;">
                    <?= rupiahFormat($pesanan['total_harga']) ?>
                </div>
                <div style="font-size:0.72rem; color:var(--gray); margin-top:4px;">
                    ⚠️ Transfer sesuai nominal untuk mempercepat verifikasi
                </div>
            </div>

            <!-- Daftar Rekening -->
            <div style="font-size:0.8rem; font-weight:600; color:var(--gray); margin-bottom:10px; text-transform:uppercase; letter-spacing:0.5px;">
                Pilih salah satu rekening tujuan:
            </div>
            <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                <?php foreach ($rekeningList as $idx => $rek): ?>
                <div style="border:1.5px solid var(--border); border-radius:10px; padding:14px 16px; transition:all 0.2s;"
                     onmouseover="this.style.borderColor='var(--primary)'; this.style.background='#fff0ed'"
                     onmouseout="this.style.borderColor='var(--border)'; this.style.background='white'">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <div style="width:52px; height:32px; background:#f5f5f5; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:800; color:var(--dark); flex-shrink:0; border:1px solid var(--border);">
                                <?= $rek['bank'] ?>
                            </div>
                            <div>
                                <div style="font-weight:700; font-size:0.88rem; margin-bottom:2px;">
                                    <?= $rek['logo'] ?> <?= $rek['bank'] ?>
                                </div>
                                <div style="font-family:monospace; font-size:1rem; font-weight:800; color:var(--dark); letter-spacing:1px;">
                                    <?= $rek['norek'] ?>
                                </div>
                                <div style="font-size:0.72rem; color:var(--gray);">a/n <?= $rek['atas_nama'] ?></div>
                            </div>
                        </div>
                        <button onclick="copyText('<?= $rek['norek'] ?>')"
                                style="background:var(--primary-light); color:var(--primary); border:1px solid var(--primary); border-radius:6px; padding:7px 14px; font-size:0.75rem; font-weight:700; cursor:pointer; white-space:nowrap; flex-shrink:0; transition:all 0.2s;"
                                onmouseover="this.style.background='var(--primary)'; this.style.color='white'"
                                onmouseout="this.style.background='var(--primary-light)'; this.style.color='var(--primary)'">
                            📋 Salin
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Langkah pembayaran -->
            <div style="background:#f9f9f9; border-radius:8px; padding:14px 16px;">
                <div style="font-size:0.82rem; font-weight:700; margin-bottom:10px; color:var(--dark);">
                    📋 Langkah Pembayaran:
                </div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <?php
                    $steps = $metodeBayar === 'E-Wallet' ? [
                        'Buka aplikasi e-wallet yang Anda pilih',
                        'Pilih menu Transfer / Kirim Uang',
                        'Masukkan nomor di atas & nominal <strong>' . rupiahFormat($pesanan['total_harga']) . '</strong>',
                        'Isi catatan dengan kode pesanan <strong>' . $kode . '</strong>',
                        'Konfirmasi & simpan bukti pembayaran',
                    ] : [
                        'Buka aplikasi m-banking atau pergi ke ATM',
                        'Pilih Transfer ke rekening tujuan di atas',
                        'Masukkan nominal tepat <strong>' . rupiahFormat($pesanan['total_harga']) . '</strong>',
                        'Isi berita transfer dengan kode <strong>' . $kode . '</strong>',
                        'Simpan bukti transfer Anda',
                    ];
                    foreach ($steps as $no => $step):
                    ?>
                    <div style="display:flex; gap:10px; align-items:flex-start; font-size:0.82rem; color:var(--gray); line-height:1.5;">
                        <span style="width:22px; height:22px; background:var(--primary); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:0.7rem; font-weight:800; flex-shrink:0; margin-top:1px;">
                            <?= $no + 1 ?>
                        </span>
                        <span><?= $step ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TOMBOL AKSI -->
        <div class="card" style="padding:16px 20px; margin-bottom:24px;">
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="<?= BASE_PATH ?>/profil.php?tab=pesanan"
                   class="btn btn-secondary" style="flex:1; justify-content:center;">
                    📦 Lihat Pesanan
                </a>
                <a href="<?= BASE_PATH ?>/invoice.php?kode=<?= urlencode($kode) ?>"
                   class="btn btn-outline" style="flex:1; justify-content:center;" target="_blank">
                    🖨️ Cetak Invoice
                </a>
                <a href="<?= BASE_PATH ?>/index.php"
                   class="btn btn-primary" style="flex:1; justify-content:center;">
                    🏠 Kembali Belanja
                </a>
            </div>
        </div>
    </div>

    <script>
    function copyText(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Berhasil disalin: ' + text + ' 📋', 'success');
        }).catch(() => {
            showToast('Teks: ' + text, 'success');
        });
    }

    (function() {
        const created  = new Date('<?= $pesanan['created_at'] ?>');
        const deadline = new Date(created.getTime() + 24 * 60 * 60 * 1000);
        const el       = document.getElementById('countdown');
        if (!el) return;
        function update() {
            const diff = deadline - new Date();
            if (diff <= 0) { el.textContent = 'Waktu habis!'; el.style.color = '#bdbdbd'; return; }
            const h = String(Math.floor(diff / 3600000)).padStart(2, '0');
            const m = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
            const s = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
            el.textContent = `${h}:${m}:${s}`;
        }
        update();
        setInterval(update, 1000);
    })();
    </script>

    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// ===== AMBIL DATA FORM =====
$error = $error ?? '';
$stmt = $pdo->prepare("SELECT k.*, p.nama_produk, p.harga, p.gambar FROM keranjang k JOIN produk p ON k.produk_id = p.id WHERE k.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$items    = $stmt->fetchAll();
$subtotal = array_sum(array_map(fn($i) => $i['harga'] * $i['jumlah'], $items));

$userData = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userData->execute([$_SESSION['user_id']]);
$userData = $userData->fetch();

$pageTitle = 'Checkout - TokoKu';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="breadcrumb">
        <a href="<?= BASE_PATH ?>/index.php">🏠 Beranda</a>
        <span class="sep">›</span>
        <a href="<?= BASE_PATH ?>/keranjang.php">Keranjang</a>
        <span class="sep">›</span>
        <span class="current">Checkout</span>
    </div>

    <h1 style="font-size:1.3rem; font-weight:800; margin-bottom:16px;">✅ Checkout</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 360px; gap:20px; align-items:start;">

        <!-- FORM -->
        <div>
            <!-- Alamat -->
            <div class="card" style="padding:20px; margin-bottom:16px;">
                <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                    📍 Alamat Pengiriman
                </h3>
                <form method="POST" id="formCheckout">
                    <div class="form-group">
                        <label>Nama Penerima</label>
                        <input type="text" class="form-control"
                               value="<?= htmlspecialchars($userData['nama']) ?>" disabled
                               style="background:#f5f5f5;">
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" class="form-control"
                               value="<?= htmlspecialchars($userData['telepon'] ?? '-') ?>" disabled
                               style="background:#f5f5f5;">
                    </div>
                    <div class="form-group">
                        <label>Alamat Lengkap *</label>
                        <textarea name="alamat" class="form-control" rows="3"
                                  placeholder="Masukkan alamat lengkap..." required><?= htmlspecialchars($userData['alamat'] ?? '') ?></textarea>
                    </div>
            </div>

            <!-- Metode Pembayaran -->
            <div class="card" style="padding:20px; margin-bottom:16px;">
                <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                    💳 Metode Pembayaran
                </h3>
                <?php
                $metodeList = [
                    ['Transfer Bank', '🏦', 'BCA / BRI / Mandiri / BNI'],
                    ['COD',           '💵', 'Bayar saat paket tiba'],
                    ['E-Wallet',      '📱', 'GoPay / OVO / DANA / ShopeePay'],
                    ['Kartu Kredit',  '💳', 'Visa / Mastercard'],
                ];
                ?>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    <?php foreach ($metodeList as $m): ?>
                    <label style="border:2px solid var(--border); border-radius:8px; padding:12px; cursor:pointer; transition:all 0.2s; display:flex; align-items:center; gap:10px;"
                           id="label_<?= str_replace(' ', '_', $m[0]) ?>">
                        <input type="radio" name="metode_bayar" value="<?= $m[0] ?>"
                               id="metode_<?= str_replace(' ', '_', $m[0]) ?>"
                               onchange="highlightMetode('<?= str_replace(' ', '_', $m[0]) ?>')"
                               required style="accent-color:var(--primary);">
                        <span style="font-size:1.4rem;"><?= $m[1] ?></span>
                        <div>
                            <div style="font-weight:600; font-size:0.82rem;"><?= $m[0] ?></div>
                            <div style="font-size:0.72rem; color:var(--gray);"><?= $m[2] ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Voucher -->
            <div class="card" style="padding:20px; margin-bottom:16px;">
                <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:16px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                    🎟️ Voucher & Diskon
                </h3>
                <div style="display:flex; gap:8px; margin-bottom:8px;">
                    <input type="text" name="voucher_kode" id="voucherInput"
                           class="form-control" placeholder="Masukkan kode voucher..."
                           style="text-transform:uppercase; letter-spacing:1px;"
                           value="<?= htmlspecialchars($_POST['voucher_kode'] ?? '') ?>">
                    <button type="button" onclick="cekVoucher()" class="btn btn-secondary" style="white-space:nowrap;">Pakai</button>
                </div>
                <div id="voucherInfo"></div>

                <?php
                $voucherList = $pdo->query("SELECT * FROM voucher WHERE status='aktif' AND (berlaku_hingga IS NULL OR berlaku_hingga >= CURDATE()) AND terpakai < kuota LIMIT 3")->fetchAll();
                if (!empty($voucherList)):
                ?>
                <div style="margin-top:12px;">
                    <div style="font-size:0.78rem; color:var(--gray); margin-bottom:8px;">Voucher tersedia:</div>
                    <?php foreach ($voucherList as $v): ?>
                    <div class="voucher-card" style="margin-bottom:8px; cursor:pointer;" onclick="pakaiVoucher('<?= $v['kode'] ?>')">
                        <div>
                            <div class="voucher-code"><?= htmlspecialchars($v['kode']) ?></div>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600; font-size:0.82rem;">
                                <?= $v['jenis'] === 'persen' ? 'Diskon '.$v['nilai'].'%' : 'Potongan '.rupiahFormat($v['nilai']) ?>
                            </div>
                            <div style="font-size:0.72rem; color:var(--gray);">
                                Min. <?= rupiahFormat($v['min_belanja']) ?>
                                <?php if ($v['maks_diskon'] > 0): ?> · Maks. <?= rupiahFormat($v['maks_diskon']) ?><?php endif; ?>
                            </div>
                            <div style="font-size:0.7rem; color:var(--gray);">s/d <?= date('d M Y', strtotime($v['berlaku_hingga'])) ?></div>
                        </div>
                        <button type="button" onclick="pakaiVoucher('<?= $v['kode'] ?>')" class="btn btn-primary btn-sm">Pakai</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Catatan -->
            <div class="card" style="padding:20px; margin-bottom:16px;">
                <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:12px;">📝 Catatan (Opsional)</h3>
                <textarea name="catatan" class="form-control" rows="2"
                          placeholder="Pesan atau catatan untuk penjual..."></textarea>
            </div>

                </form>
        </div>

        <!-- RINGKASAN -->
        <div style="position:sticky; top:80px;">
            <div class="card" style="padding:20px;">
                <h3 style="font-size:0.95rem; font-weight:700; margin-bottom:14px; padding-bottom:10px; border-bottom:2px solid var(--primary);">
                    🧾 Ringkasan Pesanan
                </h3>
                <div style="max-height:280px; overflow-y:auto; margin-bottom:14px;">
                    <?php foreach ($items as $item): ?>
                    <div style="display:flex; gap:10px; padding:8px 0; border-bottom:1px solid var(--border); align-items:center;">
                        <div style="width:44px; height:44px; flex-shrink:0; border-radius:4px; overflow:hidden; background:#f5f5f5; display:flex; align-items:center; justify-content:center; font-size:1.2rem;">
                            <?php if ($item['gambar'] && file_exists(__DIR__ . '/uploads/' . $item['gambar'])): ?>
                                <img src="<?= BASE_PATH ?>/uploads/<?= htmlspecialchars($item['gambar']) ?>" style="width:100%; height:100%; object-fit:cover;">
                            <?php else: ?>
                                📦
                            <?php endif; ?>
                        </div>
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:0.8rem; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?= htmlspecialchars($item['nama_produk']) ?>
                            </div>
                            <div style="font-size:0.75rem; color:var(--gray);"><?= $item['jumlah'] ?>x <?= rupiahFormat($item['harga']) ?></div>
                        </div>
                        <div style="font-size:0.82rem; font-weight:600; color:var(--primary); flex-shrink:0;">
                            <?= rupiahFormat($item['harga'] * $item['jumlah']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="font-size:0.85rem;">
                    <div style="display:flex; justify-content:space-between; padding:6px 0;">
                        <span style="color:var(--gray);">Subtotal (<?= count($items) ?> item)</span>
                        <span id="subtotalDisplay"><?= rupiahFormat($subtotal) ?></span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0;" id="diskonRow" style="display:none;">
                        <span style="color:var(--success);">🎟️ Diskon</span>
                        <span style="color:var(--success);" id="diskonDisplay">- Rp 0</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:6px 0;">
                        <span style="color:var(--gray);">Ongkos Kirim</span>
                        <span style="color:var(--success); font-weight:600;">Gratis 🎉</span>
                    </div>
                    <div style="display:flex; justify-content:space-between; padding:10px 0 6px; border-top:2px solid var(--border); font-weight:800; font-size:1rem;">
                        <span>Total Bayar</span>
                        <span style="color:var(--primary);" id="totalDisplay"><?= rupiahFormat($subtotal) ?></span>
                    </div>
                </div>

                <button type="submit" form="formCheckout" class="btn btn-primary btn-full btn-lg" style="margin-top:12px;">
                    🎉 Buat Pesanan
                </button>
                <a href="<?= BASE_PATH ?>/keranjang.php" class="btn btn-outline btn-full" style="margin-top:8px; font-size:0.82rem;">
                    ← Kembali ke Keranjang
                </a>
            </div>

            <div style="text-align:center; margin-top:12px; font-size:0.75rem; color:var(--gray);">
                🔒 Transaksi aman & terenkripsi<br>
                💯 Belanja terpercaya bersama TokoKu
            </div>
        </div>
    </div>
</div>

<script>
const subtotalAsli = <?= $subtotal ?>;

function cekVoucher() {
    const kode = document.getElementById('voucherInput').value.trim().toUpperCase();
    const info = document.getElementById('voucherInfo');
    if (!kode) { info.innerHTML = '<span style="color:var(--danger);font-size:0.8rem;">❌ Masukkan kode voucher!</span>'; return; }
    info.innerHTML = '<span style="color:var(--gray);font-size:0.8rem;">⏳ Memeriksa...</span>';
    const formData = new FormData();
    formData.append('action', 'cek_voucher');
    formData.append('kode', kode);
    fetch('<?= BASE_PATH ?>/checkout.php', { method:'POST', body:formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                info.innerHTML = `<span style="color:var(--success);font-size:0.8rem;">✅ ${data.message}</span>`;
                document.getElementById('diskonRow').style.display = 'flex';
                document.getElementById('diskonDisplay').textContent = '- ' + data.diskon_fmt;
                document.getElementById('totalDisplay').textContent = data.total_fmt;
            } else {
                info.innerHTML = `<span style="color:var(--danger);font-size:0.8rem;">❌ ${data.message}</span>`;
                resetHarga();
            }
        });
}

function pakaiVoucher(kode) {
    document.getElementById('voucherInput').value = kode;
    cekVoucher();
}

function resetHarga() {
    document.getElementById('diskonRow').style.display = 'none';
    document.getElementById('totalDisplay').textContent = '<?= rupiahFormat($subtotal) ?>';
}

function highlightMetode(val) {
    document.querySelectorAll('[id^="label_"]').forEach(el => {
        el.style.borderColor = 'var(--border)';
        el.style.background  = 'white';
    });
    const label = document.getElementById('label_' + val);
    if (label) {
        label.style.borderColor = 'var(--primary)';
        label.style.background  = 'var(--primary-light)';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>