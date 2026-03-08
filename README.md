# 🛒 TokoKu - E-Commerce Web Application

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)

> Platform e-commerce berbasis web untuk siklus penjualan dan pembelian lengkap dengan manajemen stok item secara otomatis.

🌐 **Live Demo:** [https://tokokuweb.rf.gd](https://tokokuweb.rf.gd)

---

## 📋 Tentang Project

TokoKu adalah aplikasi e-commerce yang dibangun menggunakan PHP dan MySQL. Project ini mencakup siklus penjualan dan pembelian lengkap mulai dari registrasi pelanggan, browse produk, checkout, hingga manajemen stok dan laporan penjualan oleh admin.

---

## ✨ Fitur Utama

### 👤 Pelanggan
- Registrasi & Login dengan sistem keamanan CSRF
- Browse produk dengan filter kategori dan pencarian
- Detail produk dengan rating & ulasan
- Keranjang belanja (tambah, ubah, hapus)
- Checkout dengan 3 metode pembayaran (Transfer Bank, COD, E-Wallet)
- Kode voucher diskon
- Cetak invoice pesanan
- Riwayat & tracking status pesanan
- Ulasan & rating produk
- Notifikasi real-time update pesanan
- Profil dengan upload foto
- Lupa password dengan token reset

### 🔧 Admin
- Dashboard dengan statistik lengkap & grafik (Chart.js)
- Manajemen produk (CRUD + upload gambar)
- Manajemen stok (masuk/keluar + riwayat)
- Manajemen pesanan (update status + notifikasi otomatis)
- Laporan penjualan dengan grafik harian & pie chart metode bayar
- Manajemen user (reset password, aktif/nonaktif)

---

## 🛠️ Teknologi

| Teknologi | Kegunaan |
|-----------|----------|
| PHP 8.x | Backend & server-side logic |
| MySQL | Database |
| PDO | Koneksi database yang aman |
| HTML5 | Struktur halaman |
| CSS3 | Styling & responsive design |
| JavaScript | Interaksi dinamis |
| Chart.js | Grafik dashboard & laporan |

---

## 📁 Struktur Project

```
ecommerce/
├── admin/
│   ├── dashboard.php      # Panel admin utama
│   ├── produk.php         # Manajemen produk
│   ├── stok.php           # Manajemen stok
│   ├── pesanan.php        # Manajemen pesanan
│   ├── laporan.php        # Laporan penjualan
│   └── users.php          # Manajemen user
├── assets/
│   ├── css/style.css      # Global stylesheet
│   └── js/main.js         # Global JavaScript
├── config/
│   └── database.php       # Konfigurasi database
├── includes/
│   ├── header.php         # Template header & navbar
│   └── footer.php         # Template footer
├── uploads/               # Foto produk & avatar
├── index.php              # Halaman utama / beranda
├── login.php              # Halaman login
├── register.php           # Halaman registrasi
├── produk_toko_v2.php     # Daftar semua produk
├── detail_produk.php      # Detail produk & ulasan
├── keranjang.php          # Keranjang belanja
├── checkout.php           # Proses checkout
├── invoice.php            # Cetak invoice
├── profil.php             # Profil pelanggan
├── notifikasi.php         # Halaman notifikasi
├── lupa-password.php      # Lupa password
└── reset-password.php     # Reset password
```

---

## 🚀 Cara Instalasi (Local)

### Prerequisites
- PHP 8.x
- MySQL 8.0
- Web server (Apache/XAMPP)

### Langkah Instalasi

1. **Clone repository**
   ```bash
   git clone https://github.com/[username]/tokoku-ecommerce.git
   cd tokoku-ecommerce
   ```

2. **Import database**
   - Buka phpMyAdmin
   - Buat database baru: `tokoku`
   - Import file `database/ecommerce.sql`

3. **Konfigurasi database**
   - Buka `config/database.php`
   - Sesuaikan credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'tokoku');
   define('BASE_PATH', '/ecommerce');
   ```

4. **Jalankan di browser**
   ```
   http://localhost/ecommerce
   ```

---

## 👥 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@toko.com | password |
| Pelanggan | budi@gmail.com | password |
| Pelanggan | siti@gmail.com | password |
| Pelanggan | andi@gmail.com | password |

---

## 🔄 Alur Siklus Penjualan

```
Register/Login → Browse Produk → Keranjang → Checkout
      ↓
  Pesanan Dibuat (stok berkurang otomatis)
      ↓
  Admin: Pending → Diproses → Dikirim → Selesai
      ↓
  Pelanggan terima notifikasi setiap update
      ↓
  Pelanggan beri ulasan & rating
```

---

## 📸 Screenshots

> Lihat tampilan lengkap di: [https://tokokuweb.rf.gd](https://tokokuweb.rf.gd)

---

## 👨‍💻 Developer

**Berlin Sugiyanto**

---

## 📄 Lisensi

Project ini dibuat untuk keperluan portofolio.
