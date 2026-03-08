-- =============================================
-- TOKOKU - STRUKTUR + DATA DUMMY
-- Import ke: if0_41333560_tokoku
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Tabel Users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','pelanggan') DEFAULT 'pelanggan',
    alamat TEXT,
    telepon VARCHAR(20),
    foto VARCHAR(255) NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Kategori
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Produk
CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT,
    nama_produk VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(15,2) NOT NULL,
    stok INT DEFAULT 0,
    gambar VARCHAR(255),
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

-- Tabel Keranjang
CREATE TABLE IF NOT EXISTS keranjang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    produk_id INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id) ON DELETE CASCADE
);

-- Tabel Pesanan
CREATE TABLE IF NOT EXISTS pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kode_pesanan VARCHAR(50) UNIQUE NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL,
    status ENUM('pending','dibayar','diproses','dikirim','selesai','dibatalkan') DEFAULT 'pending',
    alamat_pengiriman TEXT NOT NULL,
    metode_bayar VARCHAR(50),
    catatan TEXT,
    voucher_kode VARCHAR(50) NULL,
    diskon DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Tabel Detail Pesanan
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL,
    produk_id INT NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    harga DECIMAL(15,2) NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk(id)
);

-- Tabel Riwayat Stok
CREATE TABLE IF NOT EXISTS riwayat_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produk_id INT NOT NULL,
    jenis ENUM('masuk','keluar') NOT NULL,
    jumlah INT NOT NULL,
    keterangan VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produk_id) REFERENCES produk(id)
);

-- Tabel Notifikasi
CREATE TABLE IF NOT EXISTS notifikasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    judul VARCHAR(200) NOT NULL,
    pesan TEXT NOT NULL,
    tipe ENUM('pesanan','promo','sistem') DEFAULT 'sistem',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tabel Voucher
CREATE TABLE IF NOT EXISTS voucher (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(50) UNIQUE NOT NULL,
    jenis ENUM('persen','nominal') NOT NULL,
    nilai DECIMAL(15,2) NOT NULL,
    min_belanja DECIMAL(15,2) DEFAULT 0,
    maks_diskon DECIMAL(15,2) DEFAULT 0,
    kuota INT DEFAULT 1,
    terpakai INT DEFAULT 0,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    berlaku_hingga DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel Password Resets
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- DATA DUMMY
-- =============================================

-- Kategori
INSERT INTO kategori (id, nama_kategori) VALUES
(1, 'Smartphone'),
(2, 'Laptop'),
(3, 'Aksesoris'),
(4, 'Audio'),
(5, 'Tablet');

-- Users (password = "password123")
INSERT INTO users (id, nama, email, password, role, alamat, telepon) VALUES
(1, 'Administrator', 'admin@toko.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'admin', 'Jl. Admin No. 1, Jakarta', '08100000001'),
(2, 'Budi Santoso', 'budi@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'pelanggan', 'Jl. Merdeka No. 12, Bandung, Jawa Barat 40111', '08123456789'),
(3, 'Siti Rahayu', 'siti@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'pelanggan', 'Jl. Sudirman No. 45, Surabaya, Jawa Timur 60111', '08234567890'),
(4, 'Andi Wijaya', 'andi@gmail.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
 'pelanggan', 'Jl. Diponegoro No. 7, Yogyakarta 55111', '08345678901');

-- Produk
INSERT INTO produk (id, nama_produk, deskripsi, harga, stok, kategori_id, status) VALUES
(1, 'Samsung Galaxy A54', 'Layar 6.4 inch Super AMOLED, Kamera 50MP OIS, RAM 8GB, Storage 256GB, Baterai 5000mAh.', 4200000, 14, 1, 'aktif'),
(2, 'iPhone 14', 'Chip A15 Bionic, Layar 6.1 inch Super Retina XDR, Kamera 12MP Dual, 5G Ready.', 13500000, 8, 1, 'aktif'),
(3, 'Xiaomi Redmi Note 12', 'Layar 6.67 inch AMOLED 120Hz, Kamera 50MP, RAM 6GB, Baterai 5000mAh.', 2199000, 20, 1, 'aktif'),
(4, 'ASUS VivoBook 15', 'Intel Core i5-1235U, RAM 8GB DDR4, SSD 512GB, Layar 15.6 inch FHD.', 8750000, 6, 2, 'aktif'),
(5, 'Lenovo IdeaPad Slim 3', 'AMD Ryzen 5 5500U, RAM 8GB, SSD 256GB, Layar 14 inch FHD.', 7200000, 10, 2, 'aktif'),
(6, 'Earphone JBL Tune 215BT', 'Bluetooth 5.0, Bass Pure Sound, Battery 15 jam, Fast Charging.', 299000, 35, 4, 'aktif'),
(7, 'TWS Xiaomi Redmi Buds 4', 'Active Noise Cancellation, Driver 12mm, Latensi rendah 52ms, IPX4.', 399000, 25, 4, 'aktif'),
(8, 'iPad Air 5', 'Chip M1, Layar 10.9 inch Liquid Retina, Wi-Fi 6, USB-C, Touch ID.', 9999000, 5, 5, 'aktif'),
(9, 'Case Samsung A54 Premium', 'Bahan TPU + PC premium, Anti-shock, Dustproof.', 85000, 50, 3, 'aktif'),
(10, 'Charger Fast Charging 65W', 'GaN Technology, Multi-port (2 USB-C + 1 USB-A).', 185000, 40, 3, 'aktif');

-- Voucher
INSERT INTO voucher (kode, jenis, nilai, min_belanja, maks_diskon, kuota, terpakai, status, berlaku_hingga) VALUES
('HEMAT10',   'persen',  10, 100000,  50000, 100, 2, 'aktif', DATE_ADD(NOW(), INTERVAL 30 DAY)),
('DISKON50K', 'nominal', 50000, 200000, 0,   50,  1, 'aktif', DATE_ADD(NOW(), INTERVAL 30 DAY)),
('NEWUSER',   'persen',  15, 50000,   75000, 200, 0, 'aktif', DATE_ADD(NOW(), INTERVAL 60 DAY)),
('SALE20',    'persen',  20, 500000, 100000,  30, 0, 'aktif', DATE_ADD(NOW(), INTERVAL 14 DAY));

-- Pesanan
INSERT INTO pesanan (id, user_id, kode_pesanan, total_harga, alamat_pengiriman, metode_bayar, status, voucher_kode, diskon, created_at) VALUES
(1, 2, 'ORD-2025-001-ABC', 4285000, 'Jl. Merdeka No. 12, Bandung, Jawa Barat 40111', 'Transfer Bank', 'selesai', 'DISKON50K', 50000, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 3, 'ORD-2025-002-DEF', 269100,  'Jl. Sudirman No. 45, Surabaya, Jawa Timur 60111', 'COD', 'selesai', 'HEMAT10', 29900, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 4, 'ORD-2025-003-GHI', 7200000, 'Jl. Diponegoro No. 7, Yogyakarta 55111', 'Transfer Bank', 'dikirim', NULL, 0, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 2, 'ORD-2025-004-JKL', 399000,  'Jl. Merdeka No. 12, Bandung, Jawa Barat 40111', 'E-Wallet', 'diproses', NULL, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 3, 'ORD-2025-005-MNO', 13500000,'Jl. Sudirman No. 45, Surabaya, Jawa Timur 60111', 'Transfer Bank', 'pending', NULL, 0, NOW()),
(6, 4, 'ORD-2025-006-PQR', 370000,  'Jl. Diponegoro No. 7, Yogyakarta 55111', 'COD', 'pending', NULL, 0, NOW());

-- Detail Pesanan
INSERT INTO detail_pesanan (pesanan_id, produk_id, nama_produk, harga, jumlah, subtotal) VALUES
(1, 1, 'Samsung Galaxy A54', 4200000, 1, 4200000),
(1, 9, 'Case Samsung A54 Premium', 85000, 1, 85000),
(2, 6, 'Earphone JBL Tune 215BT', 299000, 1, 299000),
(3, 5, 'Lenovo IdeaPad Slim 3', 7200000, 1, 7200000),
(4, 7, 'TWS Xiaomi Redmi Buds 4', 399000, 1, 399000),
(5, 2, 'iPhone 14', 13500000, 1, 13500000),
(6, 10, 'Charger Fast Charging 65W', 185000, 2, 370000);

-- Ulasan
INSERT INTO ulasan (produk_id, user_id, rating, komentar, created_at) VALUES
(1, 2, 5, 'Produk sesuai deskripsi, pengiriman cepat! Samsung A54 kameranya bagus banget, baterai awet. Sangat puas!', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(9, 2, 4, 'Case-nya pas dan kokoh, agak susah dipasang pertama kali. Overall bagus!', DATE_SUB(NOW(), INTERVAL 11 DAY)),
(6, 3, 5, 'Suaranya jernih, bass-nya mantap! Pakai JBL emang nggak kecewa. Rekomended!', DATE_SUB(NOW(), INTERVAL 8 DAY));

-- Riwayat Stok
INSERT INTO riwayat_stok (produk_id, jenis, jumlah, keterangan, created_at) VALUES
(1,  'masuk',  15, 'Stok awal Samsung Galaxy A54', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2,  'masuk',  10, 'Stok awal iPhone 14', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(3,  'masuk',  20, 'Stok awal Xiaomi Redmi Note 12', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(4,  'masuk',   8, 'Stok awal ASUS VivoBook 15', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(5,  'masuk',  12, 'Stok awal Lenovo IdeaPad Slim 3', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(6,  'masuk',  40, 'Stok awal Earphone JBL Tune 215BT', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(7,  'masuk',  25, 'Stok awal TWS Xiaomi Redmi Buds 4', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(8,  'masuk',   5, 'Stok awal iPad Air 5', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(9,  'masuk',  52, 'Stok awal Case Samsung A54 Premium', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(10, 'masuk',  42, 'Stok awal Charger Fast Charging 65W', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(1,  'keluar',  1, 'Pesanan #ORD-2025-001-ABC', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(9,  'keluar',  1, 'Pesanan #ORD-2025-001-ABC', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(6,  'keluar',  1, 'Pesanan #ORD-2025-002-DEF', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(5,  'keluar',  1, 'Pesanan #ORD-2025-003-GHI', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7,  'keluar',  1, 'Pesanan #ORD-2025-004-JKL', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Notifikasi
INSERT INTO notifikasi (user_id, judul, pesan, tipe, is_read, created_at) VALUES
(2, '🎉 Pesanan #ORD-2025-001-ABC Berhasil!', 'Pesanan #ORD-2025-001-ABC senilai Rp4.285.000 berhasil dibuat. Silakan transfer ke BCA 1234567890 a/n PT TokoKu Indonesia.', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(2, 'Update Pesanan #ORD-2025-001-ABC', 'Pesanan #ORD-2025-001-ABC sedang diproses oleh penjual.', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 14 DAY)),
(2, 'Update Pesanan #ORD-2025-001-ABC', 'Pesanan #ORD-2025-001-ABC sudah dikirim! Mohon ditunggu.', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 13 DAY)),
(2, 'Update Pesanan #ORD-2025-001-ABC', 'Pesanan #ORD-2025-001-ABC telah selesai. Terima kasih sudah belanja!', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(2, '🎉 Pesanan #ORD-2025-004-JKL Berhasil!', 'Pesanan #ORD-2025-004-JKL senilai Rp399.000 berhasil dibuat. Metode: E-Wallet.', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(2, 'Update Pesanan #ORD-2025-004-JKL', 'Pesanan #ORD-2025-004-JKL sedang diproses oleh penjual.', 'pesanan', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, '🎉 Pesanan #ORD-2025-002-DEF Berhasil!', 'Pesanan #ORD-2025-002-DEF senilai Rp269.100 berhasil dibuat. Metode: COD.', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(3, 'Update Pesanan #ORD-2025-002-DEF', 'Pesanan #ORD-2025-002-DEF telah selesai. Terima kasih sudah belanja!', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(3, '🎉 Pesanan #ORD-2025-005-MNO Berhasil!', 'Pesanan #ORD-2025-005-MNO senilai Rp13.500.000 berhasil dibuat. Silakan transfer ke BCA 1234567890.', 'pesanan', 0, NOW()),
(4, '🎉 Pesanan #ORD-2025-003-GHI Berhasil!', 'Pesanan #ORD-2025-003-GHI senilai Rp7.200.000 berhasil dibuat. Silakan transfer ke BCA 1234567890.', 'pesanan', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 'Update Pesanan #ORD-2025-003-GHI', 'Pesanan #ORD-2025-003-GHI sudah dikirim! Mohon ditunggu.', 'pesanan', 0, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(4, '🎉 Pesanan #ORD-2025-006-PQR Berhasil!', 'Pesanan #ORD-2025-006-PQR senilai Rp370.000 berhasil dibuat. Metode: COD.', 'pesanan', 0, NOW()),
(2, '🎉 Promo Spesial!', 'Gunakan kode SALE20 untuk diskon 20% max Rp100.000. Min. belanja Rp500.000. Berlaku 14 hari!', 'promo', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, '🎉 Promo Spesial!', 'Gunakan kode SALE20 untuk diskon 20% max Rp100.000. Min. belanja Rp500.000. Berlaku 14 hari!', 'promo', 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, '🎉 Promo Spesial!', 'Gunakan kode SALE20 untuk diskon 20% max Rp100.000. Min. belanja Rp500.000. Berlaku 14 hari!', 'promo', 0, DATE_SUB(NOW(), INTERVAL 1 DAY));

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- Login:
-- Admin    : admin@toko.com  / password123
-- Pelanggan: budi@gmail.com  / password123
--            siti@gmail.com  / password123
--            andi@gmail.com  / password123
-- =============================================