# 🛒 TokoKu — Performance E-Commerce Engine

A performance-optimized E-Commerce platform built with **Vanilla PHP**. Engineered for high-concurrency transaction reliability, utilizing atomic SQL operations, strictly enforced relational data integrity, and real-time inventory synchronization.

[![Live Demo](https://img.shields.io/badge/Live%20Demo-tokoku.up.railway.app-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://tokoku-ecommerce-production.up.railway.app)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com)

---

## 🏗 System Architecture

The project manages a complex lifecycle from session-based cart state to atomic database persistence.

```mermaid
graph TD
    User["🌐 Customer / Admin"]
    Session["📦 Session Manager (Cart/Auth)"]
    Logic["⚙️ Core PHP Logic"]
    Report["📊 Reporting Engine (Chart.js/Dompdf)"]
    DB[("🗄️ MySQL (Atomic Transactions)")]

    User --> Session
    Session --> Logic
    Logic --> DB
    Logic --> Report
```

---

## ✨ Key Features

- **🛒 Transactional Checkout:** Uses atomic SQL sequences to prevent stock race conditions during simultaneous orders.
- **🔐 Multi-Role RBAC:** Distinct operational views and permissions for `Customer` and `Admin` users.
- **📊 Real-time Inventory:** automated stock deduction and restoration logic on order fulfillment or cancellation.
- **🎫 Dynamic Voucher Engine:** High-precision server-side discount calculation for stacked vouchers.
- **📄 Audit-Ready Analytics:** Professional PDF invoice generation and interactive sales trend visualization.

---

## 🗄 Database Schema

Designed for high relational integrity with strictly defined foreign key constraints.

```mermaid
erDiagram
    USERS ||--o{ ORDERS : "places"
    CATEGORIES ||--o{ PRODUCTS : "contains"
    PRODUCTS ||--o{ ORDER_ITEMS : "referenced_in"
    ORDERS ||--o{ ORDER_ITEMS : "contains"
    ORDERS ||--|| PAYMENTS : "has"

    USERS {
        int id PK
        string role
        string email
    }
    PRODUCTS {
        int id PK
        int category_id FK
        int stock
    }
```

---

## 🚀 Installation & Usage

### Prerequisites
- PHP 8.0+
- MySQL 8.0
- Composer

### Local Setup
1. **Clone & Install:**
   ```bash
   git clone https://github.com/B3rlinSugi/tokoku-ecommerce.git
   cd tokoku-ecommerce
   composer install
   ```

2. **Database:**
   Create a database named `tokoku` and import `database.sql`.

3. **Config:**
   Update `config/database.php` with your credentials.

---

## 👨‍💻 Developed By

**Berlin Sugiyanto Hutajulu**

[![GitHub](https://img.shields.io/badge/GitHub-B3rlinSugi-181717?style=flat&logo=github)](https://github.com/B3rlinSugi)
[![LinkedIn](https://img.shields.io/badge/LinkedIn-berlinsugi-0A66C2?style=flat&logo=linkedin)](https://linkedin.com/in/berlinsugi)
[![Portfolio](https://img.shields.io/badge/Portfolio-berlinsugi.vercel.app-4e73df?style=flat&logo=vercel)](https://berlinsugi.vercel.app)

---
<p align="center">Built with ❤️ and Vanilla PHP · Serious E-Commerce Engineering</p>

