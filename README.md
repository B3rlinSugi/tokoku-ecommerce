<div align="center">
  <br />
  <h1>🛒 Tokoku E-Commerce</h1>
  <p>
    <strong>A Robust Native PHP Backend for E-Commerce Operations</strong>
  </p>
  <p>
    <img src="https://img.shields.io/badge/PHP_8-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8" />
    <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
    <img src="https://img.shields.io/badge/Bootstrap_5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap" />
    <img src="https://img.shields.io/badge/PDO-000000?style=for-the-badge&logo=php&logoColor=white" alt="PDO Database" />
  </p>
  <p>
    <a href="https://tokoku-ecommerce.vercel.app/" target="_blank">View Live Demo</a>
  </p>
</div>

---

## 📌 Overview

**Tokoku E-Commerce** is a comprehensive, backend-heavy e-commerce platform built entirely from scratch using Native PHP 8 and MySQL. This project demonstrates strong foundational knowledge in server-side web development without relying on high-level frameworks like Laravel. 

The system handles the full user shopping lifecycle—from product catalogs, persistent shopping carts, and dynamic vouchers, to secure Role-Based Authentication and a complete admin management dashboard.

## ✨ Key Features

- **Custom Native MVC Architecture**: Clean separation of concerns routing through raw PHP.
- **Role-Based Access Control (RBAC)**: Secure authentication utilizing `bcrypt` password hashing for both `Customer` and `Admin` roles.
- **Complex Relational Database**: Features a highly normalized 10-table MySQL database structure maintaining strict referential integrity.
- **Dynamic Cart & Voucher System**: Real-time stock decrementing, persistent cart logic, and advanced discount engine based on coupon codes.
- **Automated PDF Invoices**: Integrated `DomPDF` for generating and downloading professional invoice receipts.
- **Email Notifications**: Integrated `PHPMailer` for password resets and order confirmations.

> [!NOTE]
> **Honesty & Transparency regarding Payment Gateway**  
> Currently, the payment processing feature is **purely a simulation** (dummy checkout). It allows users to complete the order flow and updates the database status successfully, but no real financial transactions or third-party payment gateway integrations (like Stripe/Midtrans) have been implemented yet. This keeps the application safe for demonstration purposes.

---

## 🛠️ Tech Stack Architecture

### Core Technologies
- **Backend**: PHP 8 (Native)
- **Database**: MySQL 8 (Prepared Statements via PDO to prevent SQL Injection)
- **Frontend**: HTML5, CSS3, Bootstrap 5, Chart.js (for Admin Analytics)
- **Dependency Management**: Composer

### Packages & Libraries
- `phpmailer/phpmailer`: For SMTP email processing.
- `dompdf/dompdf`: For HTML to PDF conversion (Invoices).

---

## 🗄️ Database Structure

The project utilizes a strict relational schema designed for e-commerce logic. Key entities include:
- `users`: Stores encrypted credentials (`bcrypt`) and roles.
- `products` & `categories`: Inventory management.
- `orders` & `order_items`: Tracks transactional history and snapshots product prices at checkout.
- `vouchers`: Manages discount logic and expiration dates.
- `cart`: Persistent database-driven cart (no local storage dependency).

---

## 🚀 Getting Started

### Prerequisites
- **XAMPP / Laragon** or any environment running Apache & PHP 8.x
- **MySQL** Database
- **Composer** (for installing dependencies)

### Installation Guide

1. **Clone the repository:**
   ```bash
   git clone https://github.com/B3rlinSugi/tokoku-ecommerce.git
   cd tokoku-ecommerce
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Database Configuration:**
   - Open phpMyAdmin or your MySQL client.
   - Create a new database named `ecommerce`.
   - Import the SQL dump located at `database/ecommerce.sql`.

4. **Environment Setup:**
   - Locate the configuration file (e.g., `config/database.php`).
   - Ensure the PDO connection strings match your local MySQL credentials (`root` without password by default on XAMPP).

5. **Run the Application:**
   - Place the project folder inside your `htdocs` or `www` directory.
   - Access via browser: `http://localhost/tokoku-ecommerce`

---

## 👨‍💻 Author

**Berlin Sugiyanto**  
Backend Developer & System Architect  
- Portfolio: [berlinsugi.vercel.app](https://berlinsugi.vercel.app/)
- LinkedIn: [linkedin.com/in/berlinsugi](https://linkedin.com/in/berlinsugi)

---

<div align="center">
  <i>"Building from scratch teaches you what frameworks hide."</i>
</div>
