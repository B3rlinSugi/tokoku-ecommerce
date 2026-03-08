<?php
require_once __DIR__ . '/config/database.php';
requireLogin();
$pdo = getDB();

$kode = $_GET['kode'] ?? '';
if (!$kode) { header('Location: ' . BASE_PATH . '/profil.php?tab=pesanan'); exit; }

$stmt = $pdo->prepare("SELECT p.*, u.nama as nama_user, u.email, u.telepon, u.alamat FROM pesanan p JOIN users u ON p.user_id = u.id WHERE p.kode_pesanan = ? AND p.user_id = ?");
$stmt->execute([$kode, $_SESSION['user_id']]);
$pesanan = $stmt->fetch();
if (!$pesanan) { header('Location: ' . BASE_PATH . '/profil.php?tab=pesanan'); exit; }

$items = $pdo->prepare("SELECT * FROM detail_pesanan WHERE pesanan_id = ?");
$items->execute([$pesanan['id']]);
$items = $items->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice <?= htmlspecialchars($pesanan['kode_pesanan']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; font-size: 13px; color: #212121; background: white; }
        .invoice-wrap { max-width: 720px; margin: 0 auto; padding: 32px; }

        .invoice-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; padding-bottom: 20px; border-bottom: 2px solid #ee4d2d; }
        .invoice-logo { font-size: 1.6rem; font-weight: 800; color: #ee4d2d; }
        .invoice-logo span { color: #f5a623; }
        .invoice-no { text-align: right; }
        .invoice-no h2 { font-size: 1rem; color: #ee4d2d; margin-bottom: 4px; }
        .invoice-no p { font-size: 0.8rem; color: #757575; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
        .info-box h4 { font-size: 0.78rem; text-transform: uppercase; color: #757575; margin-bottom: 8px; letter-spacing: 0.5px; }
        .info-box p { font-size: 0.85rem; line-height: 1.6; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #ee4d2d; color: white; padding: 10px 12px; text-align: left; font-size: 0.8rem; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #f5f5f5; font-size: 0.85rem; }
        tbody tr:nth-child(even) td { background: #fafafa; }

        .total-box { margin-left: auto; width: 260px; }
        .total-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.85rem; }
        .total-row.grand { border-top: 2px solid #ee4d2d; padding-top: 10px; font-weight: 800; color: #ee4d2d; font-size: 1rem; }

        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; background: #e8f5e9; color: #2e7d32; }

        .invoice-footer { margin-top: 32px; padding-top: 16px; border-top: 1px solid #e8e8e8; text-align: center; font-size: 0.78rem; color: #757575; }

        .btn-print { background: #ee4d2d; color: white; border: none; padding: 10px 24px; border-radius: 4px; cursor: pointer; font-size: 0.9rem; font-weight: 700; margin-bottom: 20px; }

        @media print {
            .btn-print { display: none; }
            body { padding: 0; }
            .invoice-wrap { padding: 16px; }
        }
    </style>
</head>
<body>
<div class="invoice-wrap">
    <button class="btn-print" onclick="window.print()">🖨️ Cetak Invoice</button>

    <div class="invoice-header">
        <div>
            <div class="invoice-logo">Toko<span>Ku</span></div>
            <p style="font-size:0.78rem;color:#757575;margin-top:4px;">Platform Belanja Online Terpercaya</p>
        </div>
        <div class="invoice-no">
            <h2>INVOICE</h2>
            <p><strong><?= htmlspecialchars($pesanan['kode_pesanan']) ?></strong></p>
            <p>Tanggal: <?= date('d F Y', strtotime($pesanan['created_at'])) ?></p>
            <p style="margin-top:6px;"><span class="status-badge"><?= ucfirst($pesanan['status']) ?></span></p>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4>Dari</h4>
            <p><strong>TokoKu Official</strong></p>
            <p>Jl. Raya E-Commerce No. 1</p>
            <p>Jakarta, Indonesia</p>
            <p>support@tokoku.com</p>
        </div>
        <div class="info-box">
            <h4>Kepada</h4>
            <p><strong><?= htmlspecialchars($pesanan['nama_user']) ?></strong></p>
            <p><?= htmlspecialchars($pesanan['email']) ?></p>
            <p><?= htmlspecialchars($pesanan['telepon'] ?? '-') ?></p>
            <p style="margin-top:4px;"><?= nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])) ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Produk</th>
                <th style="text-align:right;">Harga</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                <td style="text-align:right;"><?= rupiahFormat($item['harga']) ?></td>
                <td style="text-align:center;"><?= $item['jumlah'] ?></td>
                <td style="text-align:right;"><?= rupiahFormat($item['subtotal']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total-box">
        <div class="total-row">
            <span>Subtotal</span>
            <span><?= rupiahFormat($pesanan['total_harga'] + ($pesanan['diskon'] ?? 0)) ?></span>
        </div>
        <?php if (!empty($pesanan['voucher_kode'])): ?>
        <div class="total-row" style="color:#ee4d2d;">
            <span>Diskon (<?= htmlspecialchars($pesanan['voucher_kode']) ?>)</span>
            <span>- <?= rupiahFormat($pesanan['diskon']) ?></span>
        </div>
        <?php endif; ?>
        <div class="total-row">
            <span>Ongkos Kirim</span>
            <span>Gratis</span>
        </div>
        <div class="total-row grand">
            <span>TOTAL</span>
            <span><?= rupiahFormat($pesanan['total_harga']) ?></span>
        </div>
    </div>

    <div style="margin-top:16px;padding:12px 16px;background:#fafafa;border-radius:4px;font-size:0.82rem;">
        <strong>Metode Pembayaran:</strong> <?= htmlspecialchars($pesanan['metode_bayar']) ?>
        <?php if ($pesanan['catatan']): ?>
        <br><strong>Catatan:</strong> <?= htmlspecialchars($pesanan['catatan']) ?>
        <?php endif; ?>
    </div>

    <div class="invoice-footer">
        <p>Terima kasih telah berbelanja di <strong>TokoKu</strong>!</p>
        <p style="margin-top:4px;">Invoice ini dibuat secara otomatis dan sah tanpa tanda tangan.</p>
    </div>
</div>
</body>
</html>