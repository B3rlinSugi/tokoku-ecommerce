# 🛒 Tokoku - E-Commerce Web Application

Full-stack E-Commerce application with payment gateway integration, RBAC, and real-time stock management.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat&logo=bootstrap)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript)

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Database Schema](#database-schema)
- [Installation](#installation)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Screenshots](#screenshots)
- [License](#license)

---

## ✨ Features

### 👥 Authentication & Authorization
- 🔐 **User Registration & Login** - Secure authentication system
- 👤 **Role-Based Access Control (RBAC)**
  - `Customer` - Browse products, manage cart, checkout
  - `Admin` - Full access: manage products, orders, users, reports
- 🔒 **Password Security** - bcrypt hashing

### 🛒 Shopping Features
- 🛍️ **Shopping Cart** - Add, update, remove items
- 💳 **Multiple Payment Methods**
  - Bank Transfer
  - E-Wallet (OVO, GoPay, Dana)
  - Cash on Delivery (COD)
- 📦 **Order Management** - Order tracking, status updates
- 🖨️ **PDF Invoice Generation** - Downloadable invoices

### 📊 Admin Dashboard
- 📈 **Sales Analytics** - Visual dashboard with charts
- 📦 **Product Management** - CRUD operations
- 👥 **Customer Management** - View and manage customers
- 📊 **Real-time Stock Management** - Track inventory levels
- 📄 **Report Generation** - Sales reports, export to PDF

---

## 🛠 Tech Stack

| Technology | Description |
|------------|-------------|
| **PHP 8.0+** | Server-side scripting |
| **MySQL** | Relational Database |
| **Bootstrap 5** | Frontend framework |
| **JavaScript** | Client-side logic |
| **Chart.js** | Data visualization |
| **Dompdf** | PDF generation |
| **PHPMailer** | Email functionality |

---

## 🗄 Database Schema

### Core Tables
- `users` - User accounts (customers & admins)
- `products` - Product catalog
- `categories` - Product categories
- `orders` - Customer orders
- `order_items` - Individual items in orders
- `payments` - Payment records
- `carts` - Shopping cart items
- `stock` - Inventory management

### Key Relationships
- User → hasMany Orders
- Order → hasMany OrderItems
- Product → belongsTo Category
- Order → hasOne Payment

---

## 🚀 Installation

### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Composer
- Web Server (XAMPP/WAMP/LAMP)

### Steps

1. **Clone the repository**
```bash
git clone https://github.com/B3rlinSugi/tokoku-ecommerce.git
cd tokoku-ecommerce
```

2. **Install dependencies**
```bash
composer install
```

3. **Configure database**
```sql
-- Create database
CREATE DATABASE tokoku;
```

4. **Import database schema**
```bash
# Import the SQL file (if provided)
mysql -u root -p tokoku < database.sql
```

5. **Configure application**
```php
// Update database connection in config/database.php
// Or create .env file with database credentials
```

6. **Start the server**
```bash
# If using XAMPP, place in htdocs folder
# Access: http://localhost/tokoku-ecommerce
```

---

## 📖 Usage

### Customer Flow
1. Register/Login to account
2. Browse products by category
3. Add items to shopping cart
4. Review cart and proceed to checkout
5. Select payment method
6. Confirm order
7. Receive PDF invoice via email

### Admin Flow
1. Login to admin panel (`/admin`)
2. Dashboard - View sales analytics
3. Manage products - Add/Edit/Delete products
4. Manage orders - View and update order status
5. Manage customers - View customer data
6. Generate reports - Export sales reports

---

## 📁 Project Structure

```
tokoku-ecommerce/
├── admin/              # Admin panel pages
│   ├── dashboard.php
│   ├── products.php
│   ├── orders.php
│   ├── customers.php
│   └── reports.php
├── user/               # Customer-facing pages
│   ├── index.php
│   ├── products.php
│   ├── cart.php
│   ├── checkout.php
│   └── orders.php
├── config/             # Database & app config
├── assets/             # CSS, JS, images
├── uploads/            # Product images
├── vendor/             # Composer dependencies
├── index.php           # Entry point
├── style.css           # Custom styles
└── README.md
```

---

## 💡 Key Implementation Details

### Payment Integration
```php
// Multiple payment methods supported
$paymentMethods = [
    'bank_transfer' => 'Bank Transfer (BCA, BRI, Mandi)',
    'ewallet'      => 'E-Wallet (OVO, GoPay, Dana)',
    'cod'          => 'Cash on Delivery'
];
```

### Stock Management
- Real-time stock updates on purchase
- Low stock alerts for admins
- Automatic stock restoration on order cancellation

### PDF Invoice Generation
```php
use Dompdf\Dompdf;

$dompdf = new Dompdf();
$dompdf->loadHtml($invoiceHtml);
$dompdf->render();
$dompdf->stream("invoice_$orderId.pdf");
```

---

## 📸 Features Demo

| Feature | Description |
|---------|-------------|
| Dashboard | Sales charts, recent orders, top products |
| Products | Grid/list view, search, filter by category |
| Cart | Persistent cart, quantity adjustment |
| Checkout | Multi-step checkout with payment selection |
| Invoice | Professional PDF invoice generation |

---

## 🔧 Troubleshooting

### Common Issues

1. **Database connection error**
   - Check MySQL credentials in config
   - Ensure MySQL service is running

2. **Session errors**
   - Check PHP session configuration
   - Ensure `session_start()` is called

3. **Image upload issues**
   - Check `uploads/` folder permissions
   - Verify PHP file upload settings

---

## 📝 License

This project is open-source and available under the [MIT License](LICENSE).

---

## 👨‍💻 Author

**Berlin Sugiyanto**
- Email: berlinsugiyanto23@gmail.com
- GitHub: [@B3rlinSugi](https://github.com/B3rlinSugi)
- LinkedIn: [berlinsugi](https://linkedin.com/in/berlinsugi)

---

<p align="center">
  Built with ❤️ using PHP & Bootstrap
</p>
