# 🛒 TokoKu — E-Commerce Platform

> A production-ready full-stack e-commerce backend built with PHP 8 and MySQL, featuring complete order lifecycle management, multi-payment support, and role-based access control.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)
![Status](https://img.shields.io/badge/Status-Complete-brightgreen?style=flat-square)

---

## 📌 Overview

TokoKu is a full-stack e-commerce platform with a backend-first approach, designed to handle real-world transaction complexity. Built from scratch with a focus on database integrity, security, and extensibility.

Key highlights:
- **10-table relational database** covering the full order lifecycle — products, orders, payments, vouchers, and stock
- **Zero transaction failures** during end-to-end testing across all payment methods
- **Zero unauthorized access incidents** in QA, achieved through RBAC with bcrypt password hashing and tokenized password reset
- **Real-time admin dashboard** with sales analytics and revenue charts via Chart.js

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────┐
│                  CLIENT LAYER               │
│         Browser (HTML/CSS/Bootstrap 5)      │
└────────────────────┬────────────────────────┘
                     │ HTTP Request
┌────────────────────▼────────────────────────┐
│               APPLICATION LAYER             │
│                  PHP 8 (MVC)                │
│  ┌──────────┐ ┌──────────┐ ┌─────────────┐ │
│  │   Auth   │ │  Order   │ │   Product   │ │
│  │  (RBAC)  │ │  Engine  │ │   & Stock   │ │
│  └──────────┘ └──────────┘ └─────────────┘ │
│  ┌──────────┐ ┌──────────┐                  │
│  │ Voucher  │ │ Payment  │                  │
│  │  Engine  │ │ Gateway  │                  │
│  └──────────┘ └──────────┘                  │
└────────────────────┬────────────────────────┘
                     │ PDO (Prepared Statements)
┌────────────────────▼────────────────────────┐
│                DATABASE LAYER               │
│               MySQL 8 (InnoDB)              │
│   users │ products │ orders │ order_items   │
│   payments │ vouchers │ stock_logs │ ...    │
└─────────────────────────────────────────────┘
```

---

## ✨ Features

### 🔐 Authentication & Security
- Role-based access control (RBAC) — Admin, Customer
- bcrypt password hashing (cost factor 12)
- Tokenized password reset via secure random token
- Session-based authentication with CSRF protection

### 🛍️ Product & Inventory
- Product management with category support
- Real-time stock tracking with automatic decrement on order
- Stock log history for audit trail

### 🧾 Order & Payment
- Complete order lifecycle: Cart → Checkout → Payment → Confirmation → Delivered
- Multi-payment method support: Bank Transfer, E-Wallet
- Voucher discount engine with usage limit and expiry validation

### 📊 Admin Dashboard
- Sales analytics with 6-month revenue chart (Chart.js)
- Order status overview and management
- Product and stock management panel

---

## 🗄️ Database Schema

The database consists of **10 tables** with proper foreign key constraints and indexing:

| Table | Description |
|---|---|
| `users` | User accounts with role differentiation |
| `products` | Product catalog with category |
| `product_stock` | Real-time stock tracking per product |
| `stock_logs` | Stock movement audit log |
| `orders` | Order header with status lifecycle |
| `order_items` | Line items per order |
| `payments` | Payment records with method and status |
| `vouchers` | Discount voucher with validation rules |
| `voucher_usage` | Tracks per-user voucher usage |
| `categories` | Product category master |

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.x |
| Database | MySQL 8 (InnoDB, FK Constraints) |
| DB Access | PDO with Prepared Statements |
| Frontend | Bootstrap 5, HTML5, CSS3, JavaScript |
| Charts | Chart.js |
| Security | bcrypt, RBAC, CSRF Token |
| Version Control | Git & GitHub |

---

## 🚀 Getting Started

### Prerequisites
- PHP 8.x
- MySQL 8.0+
- XAMPP / Laragon / any local server

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/B3rlinSugi/tokoku-ecommerce.git
cd tokoku-ecommerce

# 2. Import the database
# Open phpMyAdmin or MySQL CLI and run:
mysql -u root -p < database/tokoku.sql

# 3. Configure database connection
cp config/config.example.php config/config.php
# Edit config.php with your DB credentials

# 4. Run the application
# Place folder in htdocs (XAMPP) or www (Laragon)
# Access via: http://localhost/tokoku-ecommerce
```

### Default Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@tokoku.com | admin123 |
| Customer | user@tokoku.com | user123 |

---

## 📁 Project Structure

```
tokoku-ecommerce/
├── config/
│   └── config.php          # DB connection & app config
├── database/
│   └── tokoku.sql          # Full DB schema + seed data
├── src/
│   ├── controllers/        # Business logic handlers
│   ├── models/             # DB query abstraction
│   └── views/              # HTML templates
├── public/
│   ├── assets/             # CSS, JS, images
│   └── index.php           # Entry point
└── README.md
```

---

## 🔑 Key Technical Decisions

**Why PDO over MySQLi?**
PDO provides a consistent interface and supports prepared statements natively, reducing SQL injection risk while remaining database-agnostic for future migrations.

**Why bcrypt over MD5/SHA1?**
bcrypt is designed to be computationally expensive and includes a salt by default, making brute-force and rainbow table attacks significantly harder than legacy hashing algorithms.

**Why InnoDB over MyISAM?**
InnoDB supports foreign key constraints and ACID-compliant transactions, which are critical for maintaining order and payment data integrity.

---

## 🧪 Testing Results

| Scenario | Result |
|---|---|
| End-to-end transaction (all payment methods) | ✅ 0 failures |
| Unauthorized access attempts (QA) | ✅ 0 incidents |
| Voucher edge cases (expired, used, invalid) | ✅ All handled |
| Stock integrity after concurrent orders | ✅ Consistent |

---

## 📄 License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

---

## 👤 Author

**Berlin Sugiyanto**
- 🌐 Portfolio: [berlinsugi.vercel.app](https://berlinsugi.vercel.app)
- 💼 LinkedIn: [linkedin.com/in/berlinsugi](https://linkedin.com/in/berlinsugi)
- 📧 Email: berlinsugiyanto23@gmail.com
