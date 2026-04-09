<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:1a1a2e,50:16213e,100:0f3460&height=180&section=header&text=TokoKu%20E-Commerce&fontSize=45&fontColor=e94560&animation=fadeIn&fontAlignY=38&desc=Full-Stack%20E-Commerce%20Platform%20%7C%20PHP%208%20%7C%20MySQL%20%7C%20RBAC&descAlignY=55&descColor=a8b2d8" />

[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Status](https://img.shields.io/badge/Status-Complete-brightgreen?style=for-the-badge)](https://github.com/B3rlinSugi/tokoku-ecommerce)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

</div>

---

## 📌 Overview

**TokoKu** adalah platform e-commerce full-stack yang dibangun dengan pendekatan **backend-first**, dirancang untuk menangani kompleksitas transaksi dunia nyata. Dibangun dari nol dengan fokus pada integritas database, keamanan, dan keterbacaan kode.

> 💡 **Mengapa proyek ini penting?** TokoKu mencakup seluruh siklus hidup pesanan — dari keranjang belanja hingga konfirmasi pembayaran — menggunakan pola desain yang konsisten dan skema database yang solid dengan 10 tabel relasional.

### 🏆 Hasil Pengujian

| Metrik | Hasil |
|---|---|
| End-to-end transaction (semua metode pembayaran) | ✅ **0 kegagalan** |
| Percobaan akses tidak sah (QA testing) | ✅ **0 insiden** |
| Edge case voucher (expired, sudah dipakai, invalid) | ✅ **Semua tertangani** |
| Integritas stok setelah order | ✅ **Konsisten** |

---

## ✨ Fitur Utama

### 🔐 Autentikasi & Keamanan
- Role-Based Access Control (RBAC) — **Admin** dan **Customer**
- Password hashing dengan **bcrypt** (cost factor 12)
- Password reset melalui **secure random token** (tokenized)
- Session-based authentication + proteksi CSRF

### 🛍️ Produk & Inventaris
- Manajemen produk dengan kategori
- **Real-time stock tracking** — otomatis berkurang saat order
- Stock log history untuk audit trail

### 🧾 Order & Pembayaran
- Siklus order lengkap: `Cart → Checkout → Pembayaran → Konfirmasi → Diterima`
- **Multi-payment method**: Transfer Bank, E-Wallet
- **Voucher engine** dengan batas penggunaan dan validasi expiry

### 📊 Admin Dashboard
- Grafik revenue 6 bulan menggunakan **Chart.js**
- Overview dan manajemen status pesanan
- Panel manajemen produk & stok
- **PDF invoice** per transaksi

---

## 🗄️ Desain Database

Database terdiri dari **10 tabel** dengan foreign key constraints dan indexing yang tepat:

```
┌─────────────────────────────────────────────────────────────┐
│                      DATABASE SCHEMA                        │
├──────────────────┬──────────────────────────────────────────┤
│ Table            │ Deskripsi                                │
├──────────────────┼──────────────────────────────────────────┤
│ users            │ Akun pengguna dengan diferensiasi role   │
│ categories       │ Master kategori produk                   │
│ products         │ Katalog produk dengan kategori           │
│ product_stock    │ Tracking stok real-time per produk       │
│ stock_logs       │ Audit log pergerakan stok                │
│ orders           │ Header pesanan dengan lifecycle status   │
│ order_items      │ Line item per pesanan                    │
│ payments         │ Rekaman pembayaran dengan metode & status│
│ vouchers         │ Voucher diskon dengan aturan validasi    │
│ voucher_usage    │ Tracking penggunaan voucher per user     │
└──────────────────┴──────────────────────────────────────────┘
```

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────┐
│               CLIENT LAYER                  │
│       Browser (HTML/CSS/Bootstrap 5)        │
└────────────────────┬────────────────────────┘
                     │ HTTP Request
┌────────────────────▼────────────────────────┐
│            APPLICATION LAYER (PHP 8)        │
│                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │  Auth    │  │  Order   │  │ Product  │  │
│  │ (RBAC)   │  │  Engine  │  │ & Stock  │  │
│  └──────────┘  └──────────┘  └──────────┘  │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ Voucher  │  │ Payment  │  │  Admin   │  │
│  │  Engine  │  │ Gateway  │  │Dashboard │  │
│  └──────────┘  └──────────┘  └──────────┘  │
└────────────────────┬────────────────────────┘
                     │ PDO (Prepared Statements)
┌────────────────────▼────────────────────────┐
│             DATABASE LAYER                  │
│            MySQL 8 (InnoDB)                 │
│  users │ products │ orders │ order_items    │
│  payments │ vouchers │ stock_logs │ ...     │
└─────────────────────────────────────────────┘
```

---

## 🛠️ Tech Stack

| Layer | Teknologi | Alasan Pemilihan |
|---|---|---|
| Language | PHP 8.x | Mature ecosystem, native PDO support |
| Database | MySQL 8 (InnoDB) | FK constraints + ACID transactions |
| DB Access | PDO + Prepared Statements | SQL injection prevention, DB-agnostic |
| Frontend | Bootstrap 5, HTML5, CSS3, JS | Responsive UI yang konsisten |
| Charts | Chart.js | Lightweight, flexible data visualization |
| Security | bcrypt, RBAC, CSRF Token | Industry-standard password & access control |
| Version Control | Git & GitHub | Collaborative development |

---

## 🚀 Cara Menjalankan

### Prasyarat
- PHP 8.x
- MySQL 8.0+
- XAMPP / Laragon / web server lokal

### Instalasi

```bash
# 1. Clone repository
git clone https://github.com/B3rlinSugi/tokoku-ecommerce.git
cd tokoku-ecommerce

# 2. Import database
mysql -u root -p < database/tokoku.sql

# 3. Konfigurasi koneksi database
cp config/config.example.php config/config.php
# Edit config.php dengan kredensial DB kamu

# 4. Jalankan aplikasi
# Letakkan folder di htdocs (XAMPP) atau www (Laragon)
# Akses via: http://localhost/tokoku-ecommerce
```

### Kredensial Default

| Role | Email | Password |
|---|---|---|
| Admin | admin@tokoku.com | admin123 |
| Customer | user@tokoku.com | user123 |

---

## 📁 Struktur Proyek

```
tokoku-ecommerce/
├── admin/              # Panel admin (dashboard, orders, products)
├── assets/             # CSS, JS, images
├── config/
│   └── config.php      # Konfigurasi DB & aplikasi
├── database/
│   └── tokoku.sql      # Skema DB lengkap + seed data
├── includes/           # Shared components (header, footer, navbar)
├── uploads/            # Direktori upload gambar produk
├── checkout.php        # Halaman checkout
├── detail_produk.php   # Detail produk
├── index.php           # Halaman utama / storefront
├── invoice.php         # PDF invoice generator
├── keranjang.php       # Keranjang belanja
├── login.php           # Halaman login
├── register.php        # Registrasi user
└── README.md
```

---

## 🔑 Keputusan Teknis

**Mengapa PDO bukan MySQLi?**
PDO menyediakan antarmuka yang konsisten dan mendukung prepared statements secara native, mengurangi risiko SQL injection sambil tetap database-agnostic untuk migrasi di masa depan.

**Mengapa bcrypt bukan MD5/SHA1?**
bcrypt dirancang untuk mahal secara komputasi dan menyertakan salt secara default, membuat brute-force dan rainbow table attack jauh lebih sulit dibanding algoritma hashing lama.

**Mengapa InnoDB bukan MyISAM?**
InnoDB mendukung foreign key constraints dan transaksi ACID, yang sangat kritis untuk menjaga integritas data order dan pembayaran.

---

## 👤 Author

<div align="center">

**Berlin Sugiyanto**

[![LinkedIn](https://img.shields.io/badge/LinkedIn-berlinsugi-0077B5?style=flat-square&logo=linkedin)](https://linkedin.com/in/berlinsugi)
[![Portfolio](https://img.shields.io/badge/Portfolio-berlinsugi.vercel.app-4e73df?style=flat-square&logo=vercel)](https://berlinsugi.vercel.app)
[![Email](https://img.shields.io/badge/Email-berlinsugiyanto23%40gmail.com-D14836?style=flat-square&logo=gmail)](mailto:berlinsugiyanto23@gmail.com)
[![GitHub](https://img.shields.io/badge/GitHub-B3rlinSugi-181717?style=flat-square&logo=github)](https://github.com/B3rlinSugi)

</div>

---

<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:0f3460,50:16213e,100:1a1a2e&height=100&section=footer" />

</div>
