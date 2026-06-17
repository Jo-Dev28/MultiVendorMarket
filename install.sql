-- Multi-vendor Marketplace Database Schema
CREATE DATABASE IF NOT EXISTS multi_vendor_marketplace DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE multi_vendor_marketplace;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','seller','admin') NOT NULL DEFAULT 'customer',
    phone VARCHAR(40) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_token VARCHAR(64) DEFAULT NULL,
    reset_token VARCHAR(64) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX (role),
    INDEX (email)
);

CREATE TABLE sellers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shop_name VARCHAR(150) NOT NULL,
    shop_logo VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(60) NOT NULL,
    business_id VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    location VARCHAR(255) DEFAULT NULL,
    status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
    subscription_status ENUM('active','expired','none') NOT NULL DEFAULT 'none',
    subscription_expires DATE DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (status),
    INDEX (shop_name)
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    INDEX (slug)
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    category_id INT DEFAULT NULL,
    name VARCHAR(190) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    short_description VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(12,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    brand VARCHAR(120) DEFAULT NULL,
    status ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'pending',
    rating DECIMAL(3,2) DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX (status),
    INDEX (seller_id),
    INDEX (category_id)
);

CREATE TABLE product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX (product_id)
);

CREATE TABLE carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY user_product (user_id, product_id)
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    seller_id INT NOT NULL,
    order_number VARCHAR(80) NOT NULL UNIQUE,
    total_amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(60) NOT NULL,
    shipping_address TEXT NOT NULL,
    status ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE,
    INDEX (status),
    INDEX (created_at)
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX (order_id)
);

CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX (product_id),
    INDEX (rating)
);

CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    plan_name VARCHAR(120) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'KSH',
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    starts_at DATE NOT NULL,
    expires_at DATE NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method VARCHAR(60) NOT NULL,
    status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
    transaction_reference VARCHAR(120) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(120) NOT NULL,
    title VARCHAR(190) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    discount_percent TINYINT NOT NULL,
    expires_at DATE NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL
);

CREATE TABLE ai_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    question TEXT NOT NULL,
    response TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE chats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    seller_id INT NOT NULL,
    message TEXT NOT NULL,
    sender ENUM('user','seller','admin') NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
);

CREATE TABLE wishlists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY user_product (user_id, product_id)
);

-- Sample admin and customer accounts
INSERT INTO users (name,email,password_hash,role,email_verified,created_at)
VALUES
('Marketplace Admin','admin@marketplace.local','$2y$10$JMLnTUbl16xdxme1wthDJ.KbxftRLbliV876fAY43yUAqYdRWwIAa','admin',1,NOW()),
('Demo Customer','customer@marketplace.local','$2y$10$JMLnTUbl16xdxme1wthDJ.KbxftRLbliV876fAY43yUAqYdRWwIAa','customer',1,NOW());

INSERT INTO categories (name,slug,description,created_at)
VALUES
('Electronics','electronics','Mobile phones, laptops, accessories and gadgets.',NOW()),
('Fashion','fashion','Clothing, shoes, handbags and accessories.',NOW()),
('Home & Living','home-living','Furniture, appliances and home essentials.',NOW());

INSERT INTO sellers (user_id,shop_name,shop_logo,phone,business_id,description,location,status,subscription_status,created_at)
VALUES
(2,'Sample Seller','placeholder.png','0722000000','ID123456','Trusted seller with quality products.','Nairobi','verified','active',NOW());

INSERT INTO products (seller_id,category_id,name,slug,short_description,description,price,stock,brand,status,rating,created_at)
VALUES
(1,1,'Smartphone Pro','smartphone-pro','High-performance phone with excellent battery life.','A premium smartphone with a responsive display, long battery life and premium camera features.',29999.00,12,'Galaxy','approved',4.5,NOW()),
(1,2,'Running Shoes','running-shoes','Lightweight shoes built for comfort and speed.','Durable and breathable running shoes designed for everyday training.',6999.00,25,'Sprint','approved',4.2,NOW());

INSERT INTO product_images (product_id,filename,created_at)
VALUES
(1,'placeholder.png',NOW()),
(2,'placeholder.png',NOW());

INSERT INTO offers (code,description,discount_percent,expires_at,active,created_at)
VALUES
('LAUNCH10','10% off first order',10,DATE_ADD(CURDATE(), INTERVAL 30 DAY),1,NOW());
