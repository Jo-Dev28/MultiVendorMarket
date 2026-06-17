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
- [API Integration](#api-integration)
- [Security Features](#security-features)
- [Folder Structure](#folder-structure)
- [User Roles & Permissions](#user-roles--permissions)
- [Payment Flow](#payment-flow)
- [Order Process](#order-process)
- [AI Assistant Explained](#ai-assistant-explained)
- [Screenshots](#screenshots)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

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

### 🔄 **Complete System Flow**
┌─────────────────────────────────────────────────────────────────────────────────┐
│ USER INTERACTS WITH SYSTEM │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 1. USER AUTHENTICATION │
│ │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│ │ Register │ → │ Login │ → │ Session │ │
│ │ (Customer) │ │ (Any Role) │ │ Created │ │
│ └──────────────┘ └──────────────┘ └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 2. BROWSING PRODUCTS │
│ │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│ │ Browse │ → │ Search │ → │ Filter │ │
│ │ Categories │ │ Products │ │ Results │ │
│ └──────────────┘ └──────────────┘ └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 3. PRODUCT INTERACTION │
│ │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│ │ View │ → │ Add to │ → │ Add to │ │
│ │ Product │ │ Cart │ │ Wishlist │ │
│ └──────────────┘ └──────────────┘ └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 4. CHECKOUT PROCESS │
│ │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│ │ Review │ → │ Shipping │ → │ Payment │ │
│ │ Cart │ │ Address │ │ Method │ │
│ └──────────────┘ └──────────────┘ └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 5. ORDER PLACEMENT │
│ │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│ │ Create │ → │ Update │ → │ Notify │ │
│ │ Order │ │ Stock │ │ Seller │ │
│ └──────────────┘ └──────────────┘ └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 6. ORDER MANAGEMENT │
│ │
│ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ │
│ │ Track │ → │ Update │ → │ Complete │ │
│ │ Order │ │ Status │ │ Order │ │
│ └──────────────┘ └──────────────┘ └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ 7. REVIEW & FEEDBACK │
│ │
│ ┌──────────────┐ ┌──────────────┐ │
│ │ Rate │ → │ Review │ │
│ │ Product │ │ Product │ │
│ └──────────────┘ └──────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘

text

---

### 🤖 **AI Assistant Flow**
┌─────────────────────────────────────────────────────────────────────────────────┐
│ AI ASSISTANT WORKFLOW │
└─────────────────────────────────────────────────────────────────────────────────┘

USER ASKS QUESTION
│
▼

PHP RECEIVES REQUEST
│
▼

GATHERS CONTEXT
┌─────────────────────────────────────────┐
│ • Current Page (Home, Shop, etc.) │
│ • User Info (if logged in) │
│ • Cart Items │
│ • Search Query │
│ • Products in Database │
└─────────────────────────────────────────┘
│
▼

SENDS TO GEMINI AI
│
▼

AI GENERATES RESPONSE
│
▼

RESPONSE CLEANED & DISPLAYED

text

---

## 🎯 Features

### 👤 **Customer Features**

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

### 🏪 **Seller Features**

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

### 👑 **Admin Features**

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
┌─────────────────────────────────────────────────────────────────────────────────┐
│ PRESENTATION LAYER │
│ │
│ ┌──────────────────────────────────────────────────────────────────────────┐ │
│ │ • HTML Templates • CSS • JavaScript • Bootstrap • jQuery │ │
│ └──────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ APPLICATION LAYER │
│ │
│ ┌──────────────────────────────────────────────────────────────────────────┐ │
│ │ • PHP Controllers • Form Processing • Session Management │ │
│ │ • Authentication • Authorization • Input Validation │ │
│ └──────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ BUSINESS LOGIC LAYER │
│ │
│ ┌──────────────────────────────────────────────────────────────────────────┐ │
│ │ • Product Management • Order Processing • Cart Management │ │
│ │ • Payment Processing • Shipping Logic • AI Integration │ │
│ └──────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ DATA ACCESS LAYER │
│ │
│ ┌──────────────────────────────────────────────────────────────────────────┐ │
│ │ • MySQLi Queries • Prepared Statements • Database Connection │ │
│ └──────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
│
▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│ DATABASE LAYER │
│ │
│ ┌──────────────────────────────────────────────────────────────────────────┐ │
│ │ • MySQL Database • Tables • Relationships • Indexes │ │
│ └──────────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘

text

---

## 👥 User Flow

### Customer Journey
┌─────────────────────────────────────────────────────────────────────────────────┐
│ CUSTOMER JOURNEY │
└─────────────────────────────────────────────────────────────────────────────────┘

Visit Website
│
▼

Browse Products / Search
│
▼

View Product Details
│
▼

Add to Cart / Wishlist
│
▼

Proceed to Checkout
│
▼

Enter Shipping Address
│
▼

Select Payment Method
│
▼

Place Order
│
▼

Receive Order Confirmation
│
▼

Track Order
│
▼

Receive Product
│
▼

Rate & Review Product

text

### Seller Journey
┌─────────────────────────────────────────────────────────────────────────────────┐
│ SELLER JOURNEY │
└─────────────────────────────────────────────────────────────────────────────────┘

Register as Customer
│
▼

Apply to Become Seller
│
▼

Wait for Verification
│
▼

Seller Dashboard Access
│
▼

Add Products
│
▼

Manage Orders
│
▼

Update Stock
│
▼

Chat with Customers
│
▼

View Analytics
│
▼

Withdraw Earnings

text

---

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
# Right-click uploads folder → Properties → Security → Full Control
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
