-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 17, 2026 at 06:11 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `multi_vendor_marketplace`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_logs`
--

CREATE TABLE `ai_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `response` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ai_logs`
--

INSERT INTO `ai_logs` (`id`, `user_id`, `question`, `response`, `created_at`) VALUES
(1, 3, 'Which laptop is best for gaming?', '🌟 Hi there! I\'m your AI shopping assistant. I can:\r\n        <br><br>\r\n        • Search for products\r\n        <br>\r\n        • Compare prices\r\n        <br>\r\n        • Answer system questions\r\n        <br>\r\n        • Help with orders\r\n        <br><br>\r\n        Just type your question below!', '2026-06-16 21:21:34'),
(2, 3, 'Which laptop is best for gaming?', '😊 I\'m here to help! I can assist with:\r\n        <br><br>\r\n        • 🛍️ Product recommendations\r\n        <br>\r\n        • 🏪 Seller information\r\n        <br>\r\n        • 🚚 Shipping & delivery\r\n        <br>\r\n        • 💳 Payment options\r\n        <br>\r\n        • ❓ General questions\r\n        <br><br>\r\n        What would you like to know?', '2026-06-16 21:22:20'),
(3, 3, 'Which laptop is best for gaming?', '🌟 Hi there! I\'m your AI shopping assistant. I can:\r\n        <br><br>\r\n        • Search for products\r\n        <br>\r\n        • Compare prices\r\n        <br>\r\n        • Answer system questions\r\n        <br>\r\n        • Help with orders\r\n        <br><br>\r\n        Just type your question below!', '2026-06-16 21:22:25'),
(4, 3, 'what is the name of the system', '📊 <strong>About <?= SITE_NAME ?></strong><br><?= SITE_NAME ?> is a multi-vendor marketplace connecting buyers with trusted sellers across Kenya.<br><br>📈 <strong>Platform Statistics:</strong><br>• 🛍️ <strong>3</strong> products available<br>• 🏪 <strong>2</strong> verified sellers<br>• 📁 <strong>3</strong> categories<br>• 📦 <strong>1</strong> orders delivered<br><br>🤖 I\'m your AI shopping assistant! Ask me about products, prices, shipping, or become a seller.', '2026-06-16 21:22:56'),
(5, 3, 'what does the system does?', '🤔 I\'m not sure about that. Here are some things I can help with:\r\n        <br><br>\r\n        • 🔍 Find products (e.g., \"Show me laptops under 50k\")\r\n        <br>\r\n        • 📦 Shipping information\r\n        <br>\r\n        • 💳 Payment methods\r\n        <br>\r\n        • 🏪 Become a seller\r\n        <br>\r\n        • 🔄 Returns & refunds\r\n        <br>\r\n        • 📊 About the platform\r\n        <br><br>\r\n        Try asking in a different way!', '2026-06-16 21:23:29'),
(6, 3, '🏪 Become a seller', '🏪 <strong>Become a Seller on <?= SITE_NAME ?></strong><br><br>📋 <strong>Steps to become a seller:</strong><br>1️⃣ Register as a customer on our platform<br>2️⃣ Go to your dashboard and click \'Become a Seller\'<br>3️⃣ Fill in your business details (shop name, phone, business ID)<br>4️⃣ Submit your application for verification<br>5️⃣ Start listing your products after approval!<br><br>✅ <strong>Benefits:</strong><br>• Reach thousands of customers<br>• AI-powered product recommendations<br>• Secure payment processing<br>• Dedicated seller dashboard<br><br>👉 <a href=\'/multi-vendor/seller/register.php\'>Start Selling Now</a>', '2026-06-16 21:23:45'),
(7, 3, '💳 Payment methods', '💳 <strong>Payment Methods</strong><br><br>We accept multiple payment methods:<br><br>• 📱 <strong>M-Pesa</strong> - Pay via mobile money<br>• 💳 <strong>Credit/Debit Cards</strong> - Visa, Mastercard<br>• 🏦 <strong>Bank Transfer</strong> - Direct bank payments<br>• 🌐 <strong>PayPal</strong> - International payments<br><br>🔒 All payments are secure and encrypted.', '2026-06-16 21:23:58'),
(8, 3, '💳 Payment methods', '💳 <strong>Payment Methods</strong><br><br>We accept multiple payment methods:<br><br>• 📱 <strong>M-Pesa</strong> - Pay via mobile money<br>• 💳 <strong>Credit/Debit Cards</strong> - Visa, Mastercard<br>• 🏦 <strong>Bank Transfer</strong> - Direct bank payments<br>• 🌐 <strong>PayPal</strong> - International payments<br><br>🔒 All payments are secure and encrypted.', '2026-06-16 21:28:50'),
(9, 3, '💳 Payment methods', 'Sorry, I encountered an error. Please try again later.', '2026-06-16 21:38:41'),
(10, 3, '💳 Payment methods', 'Sorry, I encountered an error. Please try again later.', '2026-06-16 21:44:49'),
(11, 3, '💳 Payment methods', 'Sorry, I encountered an error (HTTP 400). Please try again later.', '2026-06-16 21:45:24'),
(12, 3, '💳 Payment methods', 'Hello! Welcome to MultiVendor Market. 😊<br><br>\n\nTo make your shopping experience seamless, we accept the following secure payment methods (all transactions are processed in Kenyan Shillings - KSH):<br><br>\n\n• <strong>M-Pesa</strong> (Lipa na M-Pesa)<br>\n• <strong>Airtel Money</strong><br>\n• <strong>Credit/Debit Cards</strong> (Visa, Mastercard)<br>\n• <strong>Direct Bank Transfer</strong><br><br>\n\nYou can select your preferred payment option at the checkout page. <br><br>\n\nIf you have any questions or need assistance with your payment, please feel free to contact our support team at <strong>admin@marketplace.local</strong>. Happy shopping!', '2026-06-16 21:47:19'),
(13, 3, 'at what time should i order', 'Welcome to MultiVendor Market! <br><br>\n\nYou can place your order at **any time of the day or night**, as our platform is open 24/7! <br><br>\n\nHowever, for the fastest processing and delivery, we highly recommend placing your orders **between 8:00 AM and 5:00 PM (East Africa Time)**. Ordering during these standard business hours allows our vendors to package and dispatch your items much quicker, often leading to same-day or next-day delivery. <br><br>\n\nIf you have any questions about a specific vendor\'s delivery schedule, feel free to reach out to us at **admin@marketplace.local**. Happy shopping!', '2026-06-16 21:48:28'),
(14, 3, 'who is the developer of the system', 'Hello! <br><br>\n\nThank you for reaching out. I don\'t have the specific details about the developer of the MultiVendor Market system in my database. <br><br>\n\nFor technical inquiries or more information about the platform\'s development, please feel free to contact our support team at <strong>admin@marketplace.local</strong>. <br><br>\n\nIs there anything else I can help you with today? I would be happy to help you find products or navigate the marketplace!', '2026-06-16 21:51:48'),
(15, 3, 'who is the best laptop in the system', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 21:52:29'),
(16, 3, 'Sorry, I encountered an error (HTTP 503). Please try again later.', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 21:52:52'),
(17, 3, 'Sorry, I encountered an error (HTTP 503). Please try again later.', 'Hello! I am so sorry to hear that you encountered an HTTP 503 error. <br><br>\n\nA 503 Service Unavailable error usually means that the MultiVendor Market servers are temporarily overloaded or undergoing scheduled maintenance. <br><br>\n\nHere are a few quick things you can try:<br>\n1. <strong>Wait a minute or two</strong> and try refreshing the page.<br>\n2. <strong>Clear your browser cache</strong> or try accessing the site from a different browser.<br><br>\n\nIf the issue persists, please feel free to reach out to our support team directly at <strong>admin@marketplace.local</strong> so we can look into it for you. <br><br>\n\nThank you for your patience, and please let me know if there is anything else I can assist you with in the meantime!', '2026-06-16 21:52:57'),
(18, 3, 'Sorry, I encountered an error (HTTP 503). Please try again later.', 'Hello!<br>I\'m sorry to hear that you encountered an HTTP 503 error. This usually means our servers are temporarily overloaded or undergoing maintenance.<br>Please try refreshing the page or checking back in a few minutes. If the issue persists, feel free to reach out to our support team at admin@marketplace.local so we can assist you.<br>Thank you for your patience!', '2026-06-16 21:53:38'),
(19, 3, 'how can i become a seller', 'Welcome to MultiVendor Market! We are thrilled that you want to join our community of sellers.<br><br>To become a seller, please follow these simple steps:<br>1. Visit our website and click on the \"Register\" or \"Become a Seller\" link.<br>2. Fill out the registration form with your personal and shop details.<br>3. Submit your application for review.<br>4. Once approved, you can set up your shop, list your products in KSH, and start selling!<br><br>If you have any questions or need help setting up, please contact our support team at admin@marketplace.local. Of course, we are always here to help you succeed!', '2026-06-16 21:54:09'),
(20, 3, 'what are the subscription', 'Welcome to MultiVendor Market!<br><br>Are you looking to sell on our platform? We offer subscription plans for vendors to showcase and sell their products in KSH.<br><br>For the most accurate and up-to-date details on our subscription packages, please reach out to our administration team directly at <b>admin@marketplace.local</b>.<br><br>Let me know if you need help finding products or have any other questions!', '2026-06-16 21:55:05'),
(21, 3, 'what are the subscription', 'Welcome to MultiVendor Market!<br>Regarding subscriptions, these typically refer to the plans available for vendors who want to sell their products on our platform.<br>Since I do not have the specific pricing and package details in my database, I kindly recommend reaching out to our support team for the most accurate and up-to-date information.<br>You can contact them directly at <b>admin@marketplace.local</b>.<br>Please let me know if you need help finding any products or have other questions!', '2026-06-16 21:55:11'),
(22, 3, 'who are the admin', 'Hello!<br>The administration team of MultiVendor Market can be reached directly via email at <b>admin@marketplace.local</b>.<br>Please feel free to reach out to them for any platform-related inquiries or official support.', '2026-06-16 21:55:56'),
(23, 3, 'how many order can i place in a day?', 'Welcome to MultiVendor Market!<br><br>There is no limit to the number of orders you can place in a day. You are free to shop and place as many orders as you like!<br><br>If you are planning to make large bulk purchases or experience any issues during checkout, please feel free to reach out to our support team at admin@marketplace.local for assistance.<br><br>Happy shopping!', '2026-06-16 21:56:40'),
(24, 3, 'how can i become a seller in the platform', 'Welcome to MultiVendor Market! We\'re thrilled that you want to join our community of sellers.<br>To become a seller, please follow these simple steps:<br>1. Visit our website and click on the \"Register\" or \"Sell with Us\" link.<br>2. Select the \"I am a vendor\" option during registration.<br>3. Fill in your shop details, including your shop name and contact information.<br>4. Complete the registration and wait for our admin team to review and approve your account.<br>Once approved, you can start listing your products and earning in KSH!<br>If you have any questions or need assistance setting up, please contact us at admin@marketplace.local.', '2026-06-16 21:59:23'),
(25, 3, 'at which time can i order?', 'Welcome to MultiVendor Market!<br><br>You can place an order at any time, 24/7! Our online marketplace is always open for shopping.<br><br>Please note that while you can make purchases anytime, individual vendors will process and ship your orders during their standard business hours.<br><br>If you have any further questions, feel free to ask or contact us at admin@marketplace.local.', '2026-06-16 22:00:46'),
(26, 3, 'What is the shipping policy?', 'Welcome to MultiVendor Market!<br><br>Since we are a multi-vendor platform, shipping policies, delivery times, and rates vary depending on the individual seller and your delivery location. All shipping fees are calculated in KSH at checkout.<br><br>To find the shipping policy for a specific item, please check the shipping information tab on that product\'s page.<br><br>If you have any questions about a specific order, feel free to reach out to us at admin@marketplace.local.', '2026-06-16 22:01:26'),
(27, 3, 'Show me laptops under 50,000 KSH', 'Welcome to MultiVendor Market! Here are some great laptop options under 50,000 KSH available on our platform:<br><br><b>1. Lenovo IdeaPad 3</b><br>Price: KSH 44,999<br>Key Specs: Intel Core i3, 4GB RAM, 1TB HDD, 14\" Display. Perfect for students and office work.<br><br><b>2.', '2026-06-16 22:01:44'),
(28, 3, 'how can i know the best product in multivender', 'Welcome to MultiVendor Market! I\'d be happy to help you find the best products on our platform.<br><br>To identify the top-quality products, we recommend looking at these key indicators:<br>• <b>Customer Reviews & Ratings:</b> Check the star ratings and read feedback from other buyers on the product page.<br>• <b>Vendor Reputation:</b> Look at the vendor\'s overall rating and how long they have been selling on MultiVendor Market.<br>• <b>Best Seller Badges:</b> Keep an eye out for \"Best Seller\" or \"Featured\" tags on products.<br>• <b>Price Comparison:</b> Compare similar items to ensure you are getting the best value for your KSH.<br><br>If you are looking for a specific item, let me know and I can find the best options for you! For any other questions, feel free to reach out to admin@marketplace.local.', '2026-06-16 22:02:45'),
(29, 3, 'how can i know the best product in multivender', 'Hello Jonathan! Welcome to MultiVendor Market.<br><br>To find the best products on our platform, you can easily identify them by looking at these key indicators:<br><br>1. <strong>Customer Reviews & Ratings:</strong> Check the star ratings and feedback left by other shoppers on the product details page.<br>2. <strong>Seller Reputation:</strong> Since we have multiple sellers, check the seller\'s profile rating to ensure they have a history of good service and quality products.<br>3. <strong>Product Description & Specifications:</strong> Read the details carefully to ensure the product meets your exact needs.<br><br>If you have a specific category or item in mind, let me know and I can help you find it!', '2026-06-16 22:06:54'),
(30, 3, 'what are the categories', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 22:07:14'),
(31, 3, 'what are the categories', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:07:15'),
(32, 3, 'what are the categories', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:07:24'),
(33, 3, 'what are the front contents', 'Hello Jonathan! Here is a summary of what is currently on your page and the platform status:<br><br><strong>Current Page:</strong> AI Assistant Page<br><strong>User Logged In:</strong> Jonathan Bosimwenda (josbosimwenda@gmail.com)<br><strong>Shopping Cart:</strong> 1 item in cart<br><strong>Platform Statistics:</strong><br>- Total Products Available: 3<br>- Total Sellers: 2<br>- Preferred Currency: KSH (Kenyan Shilling)<br><br>How can I assist you with your shopping today?', '2026-06-16 22:07:59'),
(34, 3, 'what are items in my cart?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:10:41'),
(35, 3, 'Tell me about what I see on this page', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:00'),
(36, 3, 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:07'),
(37, 3, 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:13'),
(38, 3, 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:15'),
(39, 3, 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:30'),
(40, 3, 'Show me laptops under 50,000 KSH', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 22:11:42'),
(41, 3, 'Show me laptops under 50,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:43'),
(42, 3, 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:54'),
(43, 3, 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:13:36'),
(44, 3, 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:32:11'),
(45, 3, 'Best phone under 30,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:32:24'),
(46, 3, 'Best phone under 30,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:32:40'),
(47, 3, 'Show me laptops under 50,000 KSH', 'br><b>4. Lenovo IdeaPad 3</b> - KSH 48,000<br>Specs: Intel Pentium, 4GB RAM, 1TB HDD.<br', '2026-06-16 22:32:50'),
(48, 3, 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:33:03'),
(49, 3, 'Best phone under 30,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:33:12'),
(50, 3, 'Show me laptops under 50,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:33:17');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`id`, `user_id`, `product_id`, `quantity`, `created_at`) VALUES
(14, 3, 3, 1, '2026-06-16 19:06:04'),
(15, 3, 1, 1, '2026-06-16 22:10:15');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'Electronics', 'electronics', 'Mobile phones, laptops, accessories and gadgets.', '2026-06-15 14:34:08'),
(2, 'Fashion', 'fashion', 'Clothing, shoes, handbags and accessories.', '2026-06-15 14:34:08'),
(4, 'Food', 'testy-is-my-name', 'enjoy good food with us', '2026-06-15 20:01:14');

-- --------------------------------------------------------

--
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `sender` enum('user','seller','admin') NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chats`
--

INSERT INTO `chats` (`id`, `user_id`, `seller_id`, `message`, `sender`, `created_at`) VALUES
(1, 4, 4, 'hi', 'user', '2026-06-15 22:35:00'),
(2, 3, 4, 'ho', 'user', '2026-06-17 19:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(120) NOT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 'chat', 'New Message', 'You have a new message from a customer.', 0, '2026-06-15 22:35:00'),
(2, 3, 'chat', 'New Message', 'You have a new message from a customer.', 0, '2026-06-17 19:10:33');

-- --------------------------------------------------------

--
-- Table structure for table `offers`
--

CREATE TABLE `offers` (
  `id` int(11) NOT NULL,
  `code` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_percent` tinyint(4) NOT NULL,
  `expires_at` date NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offers`
--

INSERT INTO `offers` (`id`, `code`, `description`, `discount_percent`, `expires_at`, `active`, `created_at`) VALUES
(1, 'LAUNCH10', '10% off first order', 10, '2026-07-15', 0, '2026-06-15 14:34:08');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(60) NOT NULL,
  `shipping_address` text NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `seller_id`, `order_number`, `total_amount`, `payment_method`, `shipping_address`, `status`, `created_at`) VALUES
(2, 4, 1, 'ORD-02FC0A', 43997.00, 'M-Pesa', 'highrise', 'pending', '2026-06-15 22:24:38'),
(6, 5, 4, 'ORD-EE573B', 12000.00, 'M-Pesa', 'highrise', 'delivered', '2026-06-16 14:45:51'),
(7, 5, 4, 'ORD-5A3F7A', 12000.00, 'M-Pesa', 'highrise', 'shipped', '2026-06-16 14:47:57'),
(8, 4, 4, 'ORD-09D080', 2000.00, 'Card', 'highrise', 'cancelled', '2026-06-16 17:25:40');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `unit_price`) VALUES
(8, 7, 3, 6, 2000.00),
(9, 8, 3, 1, 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(60) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(190) NOT NULL,
  `slug` varchar(220) NOT NULL,
  `short_description` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `brand` varchar(120) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rating` decimal(3,2) DEFAULT 0.00,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `discount_percent` int(11) DEFAULT 0 COMMENT 'Discount percentage (0-99)',
  `discounted_price` decimal(12,2) DEFAULT NULL COMMENT 'Price after discount',
  `discount_start_date` datetime DEFAULT NULL COMMENT 'When discount starts',
  `discount_end_date` datetime DEFAULT NULL COMMENT 'When discount ends',
  `is_on_sale` tinyint(1) DEFAULT 0 COMMENT '1 = on sale, 0 = not on sale'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `seller_id`, `category_id`, `name`, `slug`, `short_description`, `description`, `price`, `stock`, `brand`, `status`, `rating`, `created_at`, `updated_at`, `discount_percent`, `discounted_price`, `discount_start_date`, `discount_end_date`, `is_on_sale`) VALUES
(1, 4, 1, 'Smartphone Pro', 'smartphone-pro', 'High-performance phone with excellent battery life.', 'A premium smartphone with a responsive display, long battery life and premium camera features.', 29999.00, 11, 'Galaxy', 'approved', 4.50, '2026-06-15 14:34:08', NULL, 30, 20999.30, '2026-06-17 16:07:36', '2026-06-24 16:07:36', 1),
(2, 1, 2, 'Running Shoes', 'running-shoes', 'Lightweight shoes built for comfort and speed.', 'Durable and breathable running shoes designed for everyday training.', 6999.00, 24, 'Sprint', 'approved', 4.20, '2026-06-15 14:34:08', NULL, 25, 5249.25, '2026-06-17 16:07:36', '2026-06-22 16:07:36', 1),
(3, 4, 1, 'Wireless Mouse', 'wireless-mouse-6a305222e3c12', 'ggggfgdrrddr', 'hhjhuuuuuuuuuuuuuuuuuuuuuuuufffffffffffff', 2000.00, 10, '0', 'approved', 0.00, '2026-06-15 22:27:30', NULL, 30, 1400.00, '2026-06-17 16:41:44', '2026-06-20 16:41:44', 1);

-- --------------------------------------------------------

--
-- Table structure for table `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `filename`, `created_at`) VALUES
(1, 1, 'placeholder.png', '2026-06-15 14:34:08'),
(2, 2, 'placeholder.png', '2026-06-15 14:34:08'),
(9, 3, 'img_6a3173937cc8b6.31294522.jpg', '2026-06-16 19:02:27'),
(10, 3, 'img_6a3173b804da54.85047371.jpg', '2026-06-16 19:03:04'),
(11, 3, 'img_6a3173b82784d8.74663276.jpg', '2026-06-16 19:03:04'),
(12, 3, 'img_6a3173b82d7644.32684744.jpg', '2026-06-16 19:03:04');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sellers`
--

CREATE TABLE `sellers` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(150) NOT NULL,
  `shop_logo` varchar(255) DEFAULT NULL,
  `phone` varchar(60) NOT NULL,
  `business_id` varchar(100) NOT NULL,
  `id_image` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `subscription_status` enum('active','expired','none') NOT NULL DEFAULT 'none',
  `subscription_expires` date DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sellers`
--

INSERT INTO `sellers` (`id`, `user_id`, `shop_name`, `shop_logo`, `phone`, `business_id`, `id_image`, `description`, `location`, `status`, `subscription_status`, `subscription_expires`, `created_at`) VALUES
(1, 2, 'Sample Seller', 'placeholder.png', '0722000000', 'ID123456', NULL, 'Trusted seller with quality products.', 'Nairobi', 'verified', 'active', NULL, '2026-06-15 14:34:08'),
(4, 3, 'AquaVibe', 'img_6a32b5ef50ba67.55978965.jpeg', '0999103233', 'OP1193645', 'seller_ids/img_6a3045e8495e07.50391289.jpg', 'jjjjjjjjjjjjjjjjjjjjjjjjyyyyyyyyyyyyyyyyyydddddddddddddddddddddddd', 'Nyayo Highrise Estate, Nyayo Highrise ward, Lang&amp;#039;ata, Nairobi, Nairobi County, 00202, Kenya', 'verified', 'active', NULL, '2026-06-15 21:35:20');

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `plan_name` varchar(120) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'KSH',
  `status` enum('active','expired','cancelled','pending') NOT NULL DEFAULT 'pending',
  `starts_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `seller_id`, `plan_name`, `amount`, `currency`, `status`, `starts_at`, `expires_at`, `created_at`) VALUES
(1, 4, 'Pending Selection', 0.00, 'KSH', 'active', '2026-06-15', '2026-07-15', '2026-06-15 21:42:56'),
(2, 1, 'Pending Selection', 0.00, 'KSH', 'pending', NULL, NULL, '2026-06-15 21:56:32');

-- --------------------------------------------------------

--
-- Table structure for table `support_tickets`
--

CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `subject` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in-progress','resolved','closed') DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `support_tickets`
--

INSERT INTO `support_tickets` (`id`, `user_id`, `subject`, `category`, `message`, `status`, `admin_reply`, `created_at`, `updated_at`) VALUES
(1, 3, 'Une reunion', 'account', 'gggggftytydtr', 'open', NULL, '2026-06-17 18:43:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','seller','admin') NOT NULL DEFAULT 'customer',
  `phone` varchar(40) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verification_token` varchar(64) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `remember_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `phone`, `address`, `email_verified`, `verification_token`, `reset_token`, `reset_expires`, `created_at`, `remember_token`, `remember_expires`) VALUES
(1, 'Marketplace Admin', 'admin@marketplace.local', '$2y$10$JMLnTUbl16xdxme1wthDJ.KbxftRLbliV876fAY43yUAqYdRWwIAa', 'admin', NULL, NULL, 1, NULL, NULL, NULL, '2026-06-15 14:34:08', NULL, NULL),
(2, 'Demo Customer', 'customer@marketplace.local', '$2y$10$JMLnTUbl16xdxme1wthDJ.KbxftRLbliV876fAY43yUAqYdRWwIAa', 'seller', NULL, NULL, 1, NULL, NULL, NULL, '2026-06-15 14:34:08', NULL, NULL),
(3, 'Jonathan Bosimwenda', 'josbosimwenda@gmail.com', '$2y$10$RzkSLJcSeYcpcHAsUn/lbe515bw8F1VYcGxe.I3gLxcXw8kUnYAYG', 'seller', '0999103233', 'highrise', 1, 'f5fa2834a94fd9674ba9de5123c33ecd', NULL, NULL, '2026-06-15 15:30:09', NULL, NULL),
(4, 'Bilema Jon', 'josbosimwendaadmin@gmail.com', '$2y$10$gz1TiBFkC2.I/5a9iC8z9ed3QjBPbVVVksAqdKLDg2ie.YYvPGIOu', 'admin', '0851600109', 'highrise', 1, 'cc0bfe1a0074ec3b02e3f61528270768', NULL, NULL, '2026-06-15 18:51:43', NULL, NULL),
(5, 'Jonathan Bosimwenda', 'josbosimwendacustomer@gmail.com', '$2y$10$tgWhDfKkeLg6ygfNyruAaezxXJfNXoEFVNTvVoq8wsVm0SENcD7SC', 'customer', '0851600109', 'highrise', 0, 'e53f53a3724d900e1e857a3e979f1584', NULL, NULL, '2026-06-16 14:37:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wishlists`
--

CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wishlists`
--

INSERT INTO `wishlists` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(5, 3, 2, '2026-06-15 17:42:45'),
(6, 3, 1, '2026-06-15 17:42:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `slug_2` (`slug`);

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `offers`
--
ALTER TABLE `offers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_ibfk_1` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `status` (`status`),
  ADD KEY `seller_id` (`seller_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `rating` (`rating`);

--
-- Indexes for table `sellers`
--
ALTER TABLE `sellers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `shop_name` (`shop_name`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `seller_id` (`seller_id`);

--
-- Indexes for table `support_tickets`
--
ALTER TABLE `support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role` (`role`),
  ADD KEY `email_2` (`email`);

--
-- Indexes for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_logs`
--
ALTER TABLE `ai_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `offers`
--
ALTER TABLE `offers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sellers`
--
ALTER TABLE `sellers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `support_tickets`
--
ALTER TABLE `support_tickets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wishlists`
--
ALTER TABLE `wishlists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_logs`
--
ALTER TABLE `ai_logs`
  ADD CONSTRAINT `ai_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chats`
--
ALTER TABLE `chats`
  ADD CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sellers`
--
ALTER TABLE `sellers`
  ADD CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wishlists`
--
ALTER TABLE `wishlists`
  ADD CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
