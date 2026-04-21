<div align="center">

<img src="https://capsule-render.vercel.app/api?type=waving&color=0:1a1a2e,50:16213e,100:0f3460&height=180&section=header&text=TokoKu%20E-Commerce&fontSize=45&fontColor=e94560&animation=fadeIn&fontAlignY=38&desc=Full-Stack%20E-Commerce%20Platform%20%7C%20PHP%208%20%7C%20MySQL%20%7C%20RBAC&descAlignY=55&descColor=a8b2d8" />

<a href="https://readme-typing-svg.herokuapp.com"><img src="https://readme-typing-svg.herokuapp.com?font=JetBrains+Mono&size=15&duration=3000&pause=1000&color=E94560&center=true&vCenter=true&width=535&lines=🛒+Full-Stack+B2C+E-Commerce+Platform;🏭+Backend-First+Design+Pattern;📦+Real-Time+Inventory+%26+Stock+Constraints;🎫+Dynamic+Voucher+Generation+Engine" alt="Typing SVG" /></a>
[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)
[![Live Demo](https://img.shields.io/badge/Live_Demo-Railway-0B0D0E?style=for-the-badge&logo=railway&logoColor=white)](https://tokoku-ecommerce-production.up.railway.app/)
[![Status](https://img.shields.io/badge/Status-Complete-brightgreen?style=for-the-badge)](https://github.com/B3rlinSugi/tokoku-ecommerce)
[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)

</div>

---

## 🌐 Live Demo

> **[https://tokoku-ecommerce-production.up.railway.app](https://tokoku-ecommerce-production.up.railway.app/)**

Aplikasi sudah di-deploy di Railway dan dapat diakses secara publik. Gunakan kredensial di bawah untuk mencoba.

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

| Layer | Teknologi |
|---|---|
| Language | PHP 8.x |
| Database | MySQL 8 (InnoDB) |
| DB Access | PDO + Prepared Statements |
| Frontend | Bootstrap 5, HTML5, CSS3, JavaScript |
| Charts | Chart.js |
| Security | bcrypt, RBAC, CSRF Token |

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

## ⚙️ DevOps & Deployment

Proyek ini menggunakan alur **Continuous Deployment (CD)** untuk otomatisasi rilis:

- **Platform Deployment**: [Railway](https://railway.app)
- **Workflow**: Automated triggers on `git push` to `main`.
- **Concurrency**: Database dioptimalkan untuk menangani beban transaksi tinggi secara bersamaan.
- **Reporting**: Background task server-side untuk pembuatan PDF invoice dinamis.

---

## 🔑 Keputusan Teknis

**Mengapa PDO bukan MySQLi?**
PDO menyediakan antarmuka yang konsisten dan mendukung prepared statements secara native, mengurangi risiko SQL injection sambil tetap database-agnostic.

**Mengapa bcrypt bukan MD5/SHA1?**
bcrypt dirancang untuk mahal secara komputasi dan menyertakan salt secara default, membuatnya standar industri untuk keamanan password.

**Mengapa InnoDB bukan MyISAM?**
InnoDB mendukung foreign key constraints dan transaksi ACID, yang sangat kritis untuk menjaga integritas data order dan pembayaran toko online.

---

## 👤 Author

<div align="center">

**Berlin Sugiyanto Hutajulu**

[![GitHub](https://img.shields.io/badge/GitHub-B3rlinSugi-181717?style=for-the-badge&logo=github&logoColor=white)](https://github.com/B3rlinSugi)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-berlinsugi-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white)](https://linkedin.com/in/berlinsugi)
[![Portfolio](https://img.shields.io/badge/Portfolio-berlinsugi.vercel.app-4e73df?style=for-the-badge&logo=vercel&logoColor=white)](https://berlinsugi.vercel.app)

---

Built with ❤️ and Modern PHP · E-Commerce Engineering Simplified

</div>
