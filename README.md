# 🛒 TokoKu — E-Commerce Platform

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

Full-stack e-commerce backend built with PHP and MySQL, supporting complete order lifecycle management from cart to delivery confirmation.

---

## Features

**Customer**
- Register, login, and profile management with password reset via tokenized email flow
- Product catalog with category filter and search
- Shopping cart and checkout with voucher/discount support
- Multiple payment methods — bank transfer (BCA, BRI, Mandiri, BNI) and e-wallet (GoPay, OVO, DANA, ShopeePay)
- Order tracking, invoice, and real-time notifications

**Admin**
- Dashboard with daily revenue charts, best-selling products, and payment breakdowns (Chart.js)
- Order and product management with real-time stock tracking
- Voucher management with percentage or fixed-amount discounts, spending minimums, usage quotas, and expiry dates
- User and report management

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8, PDO |
| Database | MySQL 8 (10 tables, Foreign Keys, InnoDB) |
| Frontend | Bootstrap 5, Vanilla JS, Chart.js |
| Auth | Role-Based Access Control, bcrypt, Session |
| Security | Prepared Statements, Tokenized Password Reset |

---

## Database Schema

```
users           — authentication, role (admin/customer)
produk          — product catalog with stock
kategori        — product categories
keranjang       — shopping cart
pesanan         — order header
detail_pesanan  — order line items
voucher         — discount engine
notifikasi      — per-user notifications
riwayat_stok    — stock movement history
password_resets — tokenized reset flow
```

---

## Installation

1. Clone this repository
2. Import `database/tokoku.sql` to MySQL
3. Configure database connection in `config/database.php`
4. Run on localhost using XAMPP or Laragon
5. Login as admin: `admin@tokoku.com` / `admin123`

---

## Project Structure

```
tokoku-ecommerce/
├── config/         — database connection
├── admin/          — admin panel pages
├── customer/       — customer pages
├── auth/           — login, register, password reset
├── assets/         — CSS, JS, images
├── includes/       — shared header, footer
└── database/       — SQL schema
```

---

## Author

**Berlin Sugiyanto** — Junior Backend Developer
- GitHub: [github.com/B3rlinSugi](https://github.com/B3rlinSugi)
- LinkedIn: [linkedin.com/in/berlinsugi](https://linkedin.com/in/berlinsugi)
- Email: berlinsugiyanto23@gmail.com
