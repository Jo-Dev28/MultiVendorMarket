# 🛒 Multi-Vendor Marketplace - Complete System Documentation

A fully-featured multi-vendor e-commerce platform with AI-powered shopping assistance, built with PHP, MySQL, and Bootstrap.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap&logoColor=white)
![AI](https://img.shields.io/badge/AI-Gemini-4285F4?style=flat&logo=google&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 📋 Table of Contents

- [System Overview](#system-overview)
- [How The System Works](#how-the-system-works)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Architecture](#system-architecture)
- [User Flow](#user-flow)
- [Installation Guide](#installation-guide)
- [Configuration](#configuration)
- [Database Schema](#database-schema)
- [Security Features](#security-features)
- [Folder Structure](#folder-structure)
- [User Roles & Permissions](#user-roles--permissions)
- [Payment Flow](#payment-flow)
- [Order Process](#order-process)
- [AI Assistant Explained](#ai-assistant-explained)
- [Troubleshooting](#troubleshooting)

---

## 🚀 System Overview

**Multi-Vendor Marketplace** is a complete e-commerce platform that connects buyers with multiple sellers. The system features an AI-powered shopping assistant using Google Gemini AI, secure payment processing, order management, and a modern responsive interface.

### Key Statistics
- 👥 **3 User Roles**: Admin, Seller, Customer
- 🛍️ **Complete E-commerce**: Products, Cart, Checkout, Orders
- 🤖 **AI-Powered**: Google Gemini AI integration
- 🔒 **Secure**: CSRF protection, prepared statements, password hashing
- 📱 **Responsive**: Works on all devices

---

## ⚙️ How The System Works

### Complete System Flow
USER INTERACTS WITH SYSTEM
|
v

USER AUTHENTICATION

Register (Customer/Seller)

Login (Any Role)

Session Created
|
v

BROWSING PRODUCTS

Browse Categories

Search Products

Filter Results
|
v

PRODUCT INTERACTION

View Product

Add to Cart

Add to Wishlist
|
v

CHECKOUT PROCESS

Review Cart

Shipping Address

Payment Method
|
v

ORDER PLACEMENT

Create Order

Update Stock

Notify Seller
|
v

ORDER MANAGEMENT

Track Order

Update Status

Complete Order
|
v

REVIEW & FEEDBACK

Rate Product

Review Product

text

---

### AI Assistant Flow
USER ASKS QUESTION
|
v
PHP RECEIVES REQUEST
|
v
GATHERS CONTEXT

Current Page (Home, Shop, etc.)

User Info (if logged in)

Cart Items

Search Query

Products in Database
|
v
SENDS TO GEMINI AI
|
v
AI GENERATES RESPONSE
|
v
RESPONSE CLEANED & DISPLAYED

text

---

## 🎯 Features

### 👤 Customer Features

| Feature | Description |
|---------|-------------|
| **User Registration** | Create account with email verification |
| **User Login** | Secure login with session management |
| **Product Browsing** | Browse products by category |
| **Product Search** | Search products by name, description |
| **Product Filtering** | Filter by category, price, rating |
| **Product View** | View detailed product information |
| **Add to Cart** | Add products to shopping cart |
| **Add to Wishlist** | Save products for later |
| **Checkout** | Complete purchase process |
| **Order Tracking** | Track order status |
| **Order History** | View past orders |
| **Product Reviews** | Rate and review products |
| **AI Assistant** | Ask questions and get recommendations |
| **Profile Management** | Update profile information |

### 🏪 Seller Features

| Feature | Description |
|---------|-------------|
| **Seller Registration** | Apply to become a seller |
| **Product Management** | Add, edit, delete products |
| **Order Management** | View and update order status |
| **Sales Dashboard** | View sales analytics |
| **Stock Management** | Manage product inventory |
| **Customer Chat** | Chat with customers |
| **Profile Management** | Update shop information |
| **Order Notifications** | Receive order alerts |

### 👑 Admin Features

| Feature | Description |
|---------|-------------|
| **Dashboard** | View system statistics |
| **User Management** | Manage all users |
| **Seller Verification** | Approve/reject seller applications |
| **Product Approval** | Approve/reject product listings |
| **Category Management** | Add, edit, delete categories |
| **Order Management** | View and manage all orders |
| **System Settings** | Configure system settings |
| **Analytics** | View detailed analytics |

---

## 💻 Technology Stack

### Backend
- **PHP 8.0+** - Server-side scripting
- **MySQL 5.7+** - Database management
- **PDO/MySQLi** - Database connectivity
- **Session Management** - User authentication
- **Password Hashing** - Secure password storage

### Frontend
- **Bootstrap 5.3** - Responsive CSS framework
- **jQuery 3.7** - JavaScript library
- **Font Awesome 6** - Icons
- **SweetAlert2** - Beautiful alerts
- **HTML5** - Semantic markup
- **CSS3** - Modern styling

### AI Integration
- **Google Gemini AI** - AI assistant
- **cURL** - API requests
- **JSON** - Data exchange

### Security
- **CSRF Protection** - Token-based
- **XSS Prevention** - Input sanitization
- **SQL Injection Prevention** - Prepared statements
- **Password Hashing** - bcrypt
- **Session Security** - HTTPS-only cookies

---

## 🏗️ System Architecture

```
+--------------------------------------------------+
|               PRESENTATION LAYER                 |
|--------------------------------------------------|
| HTML | CSS | JavaScript | Bootstrap              |
+--------------------------------------------------+
                |
                v
+--------------------------------------------------+
|              APPLICATION LAYER                   |
|--------------------------------------------------|
| PHP Controllers | Form Processing               |
| Session Management | Authentication             |
| Authorization | Input Validation                |
+--------------------------------------------------+
                |
                v
+--------------------------------------------------+
|            BUSINESS LOGIC LAYER                 |
|--------------------------------------------------|
| Product Management | Order Processing           |
| Cart Management | Payment Processing            |
| Shipping Logic | AI Integration                 |
+--------------------------------------------------+
                |
                v
+--------------------------------------------------+
|              DATA ACCESS LAYER                  |
|--------------------------------------------------|
| MySQLi Queries | Prepared Statements            |
| Database Connection | Data Mapping              |
+--------------------------------------------------+
                |
                v
+--------------------------------------------------+
|                DATABASE LAYER                   |
|--------------------------------------------------|
| MySQL Database | Tables | Relationships         |
+--------------------------------------------------+
```

---

## 👥 User Flow

### 🛒 Customer Journey

```
Visit Website
    |
    v
Browse Products / Search
    |
    v
View Product Details
    |
    v
Add to Cart / Wishlist
    |
    v
Proceed to Checkout
    |
    v
Enter Shipping Address
    |
    v
Select Payment Method
    |
    v
Place Order
    |
    v
Receive Order Confirmation
    |
    v
Track Order
    |
    v
Receive Product
    |
    v
Rate & Review Product
```

---

### 🏪 Seller Journey

```
Register as Customer
    |
    v
Apply to Become Seller
    |
    v
Wait for Verification
    |
    v
Seller Dashboard Access
    |
    v
Add Products
    |
    v
Manage Orders
    |
    v
Update Stock
    |
    v
Chat with Customers
    |
    v
View Analytics
    |
    v
Withdraw Earnings
```

## 📥 Installation Guide

### Prerequisites

| Requirement | Version |
|-------------|---------|
| **PHP** | 8.0 or higher |
| **MySQL** | 5.7 or higher |
| **XAMPP/WAMP** | Latest version |
| **Web Browser** | Modern browser |
| **Internet** | For AI features |

### Step 1: Download & Install XAMPP

1. Download XAMPP from [apachefriends.org](https://www.apachefriends.org/)
2. Install XAMPP on your computer
3. Start Apache and MySQL services

### Step 2: Clone or Download the Project

```bash
# Clone from GitHub
git clone https://github.com/Jo-Dev28/MultiVendorMarket.git

# Or download ZIP from GitHub
# Extract to: C:\xampp\htdocs\multi-vendor\
Step 3: Create Database
sql
-- Open phpMyAdmin or MySQL command line
CREATE DATABASE multi_vendor_marketplace;
Step 4: Import Database
bash
# Using MySQL command line
mysql -u root -p multi_vendor_marketplace < database.sql

# Or use phpMyAdmin
# 1. Open http://localhost/phpmyadmin
# 2. Select database
# 3. Click Import
# 4. Select database.sql
# 5. Click Go
Step 5: Configure Application
Open includes/config.php and update:

php
// Database Settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'multi_vendor_marketplace');

// Application Settings
define('SITE_NAME', 'MultiVendor Market');
define('BASE_URL', '/multi-vendor/');
define('ADMIN_EMAIL', 'admin@marketplace.local');

// Upload Directory
define('UPLOAD_DIR', __DIR__ . '/../uploads');

// AI Configuration
define('GEMINI_API_KEY', 'your-api-key-here');
define('GEMINI_MODEL', 'gemini-3.5-flash');
Step 6: Set File Permissions
bash
# Linux/Mac
chmod -R 755 uploads/
chmod -R 777 uploads/products/

# Windows (XAMPP)
# Right-click uploads folder -> Properties -> Security -> Full Control
Step 7: Access Application
text
http://localhost/multi-vendor/
Step 8: Default Login Credentials
Role	Email	Password
Admin	admin@marketplace.local	admin123
Seller	seller@marketplace.local	seller123
Customer	customer@marketplace.local	customer123
⚙️ Configuration
Database Configuration
php
// includes/config.php
define('DB_HOST', 'localhost');      // Database host
define('DB_USER', 'root');           // Database username
define('DB_PASS', '');              // Database password
define('DB_NAME', 'multi_vendor_marketplace'); // Database name
Application Configuration
php
// includes/config.php
define('SITE_NAME', 'MultiVendor Market');   // Site name
define('BASE_URL', '/multi-vendor/');       // Base URL
define('ADMIN_EMAIL', 'admin@marketplace.local'); // Admin email
define('UPLOAD_DIR', __DIR__ . '/../uploads'); // Upload directory
AI Configuration
php
// includes/config.php
define('GEMINI_API_KEY', 'your-api-key-here');  // Gemini API key
define('GEMINI_MODEL', 'gemini-3.5-flash');     // AI model


🔒 Security Features
1. CSRF Protection
php
// Generate CSRF token
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validate CSRF token
function csrf_validate($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}
2. SQL Injection Prevention
php
// Using prepared statements
$sql = "SELECT * FROM users WHERE email = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
3. XSS Prevention
php
// Sanitize user input
function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}
4. Password Hashing
php
// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Verify password
$valid = password_verify($password, $hashed);
📁 Folder Structure
text
multi-vendor/
│
├── admin/                          # Admin Panel
│   ├── ajax/                       # Admin AJAX handlers
│   ├── dashboard.php               # Admin dashboard
│   ├── orders.php                  # Order management
│   ├── products.php                # Product management
│   ├── sellers.php                 # Seller management
│   └── users.php                   # User management
│
├── assets/                         # Static assets
│   ├── css/                        # CSS files
│   ├── images/                     # Image files
│   └── js/                         # JavaScript files
│
├── includes/                       # Core includes
│   ├── config.php                  # Configuration
│   ├── functions.php               # Helper functions
│   ├── header.php                  # Page header
│   └── footer.php                  # Page footer
│
├── seller/                         # Seller Panel
│   ├── dashboard.php               # Seller dashboard
│   ├── orders.php                  # Order management
│   ├── products.php                # Product management
│   ├── edit_product.php            # Edit product
│   └── add_product.php             # Add product
│
├── uploads/                        # Uploaded files
│   └── products/                   # Product images
│
├── api/                            # API endpoints
│   ├── add_to_cart.php             # Add to cart API
│   ├── add_to_wishlist.php         # Add to wishlist API
│   └── get_cart_count.php          # Get cart count API
│
├── ajax/                           # AJAX handlers
│   └── get_order_details.php       # Get order details
│
├── index.php                       # Homepage
├── shop.php                        # Shop page
├── product.php                     # Product details
├── cart.php                        # Shopping cart
├── checkout.php                    # Checkout
├── orders.php                      # My orders
├── wishlist.php                    # Wishlist
├── ai_assistant.php                # AI assistant
├── login.php                       # Login page
├── register.php                    # Register page
├── logout.php                      # Logout
├── profile.php                     # User profile
├── about.php                       # About page
├── contact.php                     # Contact page
├── faq.php                         # FAQ page
├── terms.php                       # Terms & conditions
├── privacy.php                     # Privacy policy
├── shipping.php                    # Shipping info
├── returns.php                     # Returns policy
├── become-seller.php               # Become a seller
├── sellers.php                     # Sellers list
├── track-order.php                 # Track order
├── compare.php                     # Compare products
├── blog.php                        # Blog page
└── .htaccess                       # Apache configuration


## 👥 User Roles & Permissions

### 🛡️ Admin Permissions

| Permission | Description |
|------------|-------------|
| View Dashboard | View system statistics |
| Manage Users | Create, edit, delete users |
| Manage Sellers | Approve, reject sellers |
| Manage Products | Approve, reject products |
| Manage Categories | Create, edit, delete categories |
| Manage Orders | View and manage all orders |
| System Settings | Configure system settings |

---

### 🏪 Seller Permissions

| Permission | Description |
|------------|-------------|
| View Dashboard | View sales statistics |
| Manage Products | Create, edit, delete own products |
| Manage Orders | View and update order status |
| Manage Stock | Update product inventory |
| View Analytics | View sales analytics |
| Chat Customers | Communicate with customers |

---

### 🧑‍💼 Customer Permissions

| Permission | Description |
|------------|-------------|
| Browse Products | View all products |
| Search Products | Search for products |
| Add to Cart | Add products to cart |
| Add to Wishlist | Save products to wishlist |
| Place Orders | Create new orders |
| View Orders | View order history |
| Track Orders | Track order status |
| Review Products | Rate and review products |
| AI Assistant | Use AI shopping assistant |
| Update Profile | Manage personal information |

---

## 💳 Payment Flow

```
USER SELECTS PAYMENT METHOD
        ↓
CHOOSE PAYMENT TYPE
  - M-Pesa
  - Credit/Debit Card
  - Bank Transfer
  - PayPal
        ↓
ENTER PAYMENT DETAILS
        ↓
VALIDATE PAYMENT DETAILS
        ↓
PROCESS PAYMENT
        ↓
PAYMENT SUCCESS / FAILURE
        ↓
CREATE ORDER
        ↓
SEND CONFIRMATION
        ↓
UPDATE STOCK
        ↓
NOTIFY SELLER
```

---

## 📦 Order Process

```
CUSTOMER PLACES ORDER
        ↓
ORDER CREATED (Status: Pending)
        ↓
PAYMENT PROCESSED
        ↓
ORDER CONFIRMED (Status: Processing)
        ↓
SELLER PROCESSES ORDER
        ↓
ORDER SHIPPED (Status: Shipped)
        ↓
CUSTOMER RECEIVES ORDER
        ↓
ORDER DELIVERED (Status: Delivered)
        ↓
CUSTOMER REVIEWS PRODUCT
```

---

## 🤖 AI Assistant Explained

### How AI Works

- Question Detection → detects keywords in user input  
- Context Gathering → fetches database info  
- Prompt Building → creates AI prompt  
- API Request → sends to Gemini API  
- Response Processing → formats output  
- Display → shows to user  

---

### What AI Can Answer

| Category | Examples |
|----------|----------|
| Product Search | "Show me laptops under 50k" |
| Recommendations | "Best phone under 30,000 KSH" |
| Shipping | "Shipping policy?" |
| Payments | "Payment methods?" |
| Sellers | "How to become a seller?" |
| Returns | "Return policy?" |
| System | "What is this platform?" |

---

### AI Response Example

```
User: "Show me laptops under 50,000 KSH"

AI:
Here are some laptops under 50,000 KSH:

1. Dell XPS - 45,000 KSH
   Category: Laptops
   Seller: TechZone
   Rating: 4.5/5

2. MacBook Air - 48,000 KSH
   Category: Laptops
   Seller: GadgetWorld
   Rating: 4.8/5

3. HP Pavilion - 42,000 KSH
   Category: Laptops
   Seller: ComputerHub
   Rating: 4.2/5
```

---

## 🛠️ Troubleshooting

### Common Issues

| Issue | Solution |
|------|----------|
| DB Connection Error | Check config.php credentials |
| AI Not Working | Verify Gemini API key |
| Images Not Loading | Fix uploads permissions |
| Session Errors | Clear browser cache |
| Login Issues | Check database users |
| Cart Empty | Clear cache |
| Order Failed | Check payment logs |

---

### Error Messages

| Error | Cause | Solution |
|------|------|----------|
| Database connection failed | Wrong DB credentials | Fix config.php |
| Invalid API key | Wrong Gemini key | Update API key |
| Access denied | Permission issue | Check user role |
| File not found | Missing file | Check path |
| Upload failed | Folder permission | Set 777 |

---

## 🤝 Contributing

- Fork repository  
- Create feature branch  
- Make changes  
- Test code  
- Submit pull request  

---

## 📧 Contact

- Developer: Jonathan Bosimwenda  
- Email: josbosimwenda@gmail.com  
- GitHub: Jo-Dev28  
- Project: MultiVendorMarket  

---

## 🌟 Support

⭐ If you like this project, give it a star on GitHub!

---

## 🎉 Thank You

Built with ❤️ using PHP, MySQL & Bootstrap.

---

## 📄 LICENSE

```txt
MIT License

Copyright (c) 2024 Jonathan Bosimwenda

Permission is hereby granted...
```

---

## 📁 .gitignore

```gitignore
.env
.env.local
includes/config.php
*.log
node_modules/
vendor/
uploads/products/
.idea/
.vscode/
.DS_Store
Thumbs.db
```
