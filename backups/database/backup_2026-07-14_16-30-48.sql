-- ============================================
-- Database Backup
-- Generated: 2026-07-14 16:30:48
-- Tables: ai_logs, blog_categories, blog_comments, blog_posts, carts, categories, chat_receipts, chat_shared_products, chats, contacts, faq_categories, faq_items, notifications, offers, order_items, orders, payments, product_images, products, reviews, sellers, subscriptions, support_tickets, users, wishlists
-- ============================================

SET FOREIGN_KEY_CHECKS=0;

-- Table: ai_logs
DROP TABLE IF EXISTS `ai_logs`;
CREATE TABLE `ai_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `response` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ai_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ai_logs` VALUES
('1', '3', 'Which laptop is best for gaming?', '🌟 Hi there! I\'m your AI shopping assistant. I can:\r\n        <br><br>\r\n        • Search for products\r\n        <br>\r\n        • Compare prices\r\n        <br>\r\n        • Answer system questions\r\n        <br>\r\n        • Help with orders\r\n        <br><br>\r\n        Just type your question below!', '2026-06-16 21:21:34'),
('2', '3', 'Which laptop is best for gaming?', '😊 I\'m here to help! I can assist with:\r\n        <br><br>\r\n        • 🛍️ Product recommendations\r\n        <br>\r\n        • 🏪 Seller information\r\n        <br>\r\n        • 🚚 Shipping & delivery\r\n        <br>\r\n        • 💳 Payment options\r\n        <br>\r\n        • ❓ General questions\r\n        <br><br>\r\n        What would you like to know?', '2026-06-16 21:22:20'),
('3', '3', 'Which laptop is best for gaming?', '🌟 Hi there! I\'m your AI shopping assistant. I can:\r\n        <br><br>\r\n        • Search for products\r\n        <br>\r\n        • Compare prices\r\n        <br>\r\n        • Answer system questions\r\n        <br>\r\n        • Help with orders\r\n        <br><br>\r\n        Just type your question below!', '2026-06-16 21:22:25'),
('4', '3', 'what is the name of the system', '📊 <strong>About <?= SITE_NAME ?></strong><br><?= SITE_NAME ?> is a multi-vendor marketplace connecting buyers with trusted sellers across Kenya.<br><br>📈 <strong>Platform Statistics:</strong><br>• 🛍️ <strong>3</strong> products available<br>• 🏪 <strong>2</strong> verified sellers<br>• 📁 <strong>3</strong> categories<br>• 📦 <strong>1</strong> orders delivered<br><br>🤖 I\'m your AI shopping assistant! Ask me about products, prices, shipping, or become a seller.', '2026-06-16 21:22:56'),
('5', '3', 'what does the system does?', '🤔 I\'m not sure about that. Here are some things I can help with:\r\n        <br><br>\r\n        • 🔍 Find products (e.g., \"Show me laptops under 50k\")\r\n        <br>\r\n        • 📦 Shipping information\r\n        <br>\r\n        • 💳 Payment methods\r\n        <br>\r\n        • 🏪 Become a seller\r\n        <br>\r\n        • 🔄 Returns & refunds\r\n        <br>\r\n        • 📊 About the platform\r\n        <br><br>\r\n        Try asking in a different way!', '2026-06-16 21:23:29'),
('6', '3', '🏪 Become a seller', '🏪 <strong>Become a Seller on <?= SITE_NAME ?></strong><br><br>📋 <strong>Steps to become a seller:</strong><br>1️⃣ Register as a customer on our platform<br>2️⃣ Go to your dashboard and click \'Become a Seller\'<br>3️⃣ Fill in your business details (shop name, phone, business ID)<br>4️⃣ Submit your application for verification<br>5️⃣ Start listing your products after approval!<br><br>✅ <strong>Benefits:</strong><br>• Reach thousands of customers<br>• AI-powered product recommendations<br>• Secure payment processing<br>• Dedicated seller dashboard<br><br>👉 <a href=\'/multi-vendor/seller/register.php\'>Start Selling Now</a>', '2026-06-16 21:23:45'),
('7', '3', '💳 Payment methods', '💳 <strong>Payment Methods</strong><br><br>We accept multiple payment methods:<br><br>• 📱 <strong>M-Pesa</strong> - Pay via mobile money<br>• 💳 <strong>Credit/Debit Cards</strong> - Visa, Mastercard<br>• 🏦 <strong>Bank Transfer</strong> - Direct bank payments<br>• 🌐 <strong>PayPal</strong> - International payments<br><br>🔒 All payments are secure and encrypted.', '2026-06-16 21:23:58'),
('8', '3', '💳 Payment methods', '💳 <strong>Payment Methods</strong><br><br>We accept multiple payment methods:<br><br>• 📱 <strong>M-Pesa</strong> - Pay via mobile money<br>• 💳 <strong>Credit/Debit Cards</strong> - Visa, Mastercard<br>• 🏦 <strong>Bank Transfer</strong> - Direct bank payments<br>• 🌐 <strong>PayPal</strong> - International payments<br><br>🔒 All payments are secure and encrypted.', '2026-06-16 21:28:50'),
('9', '3', '💳 Payment methods', 'Sorry, I encountered an error. Please try again later.', '2026-06-16 21:38:41'),
('10', '3', '💳 Payment methods', 'Sorry, I encountered an error. Please try again later.', '2026-06-16 21:44:49'),
('11', '3', '💳 Payment methods', 'Sorry, I encountered an error (HTTP 400). Please try again later.', '2026-06-16 21:45:24'),
('12', '3', '💳 Payment methods', 'Hello! Welcome to MultiVendor Market. 😊<br><br>\n\nTo make your shopping experience seamless, we accept the following secure payment methods (all transactions are processed in Kenyan Shillings - KSH):<br><br>\n\n• <strong>M-Pesa</strong> (Lipa na M-Pesa)<br>\n• <strong>Airtel Money</strong><br>\n• <strong>Credit/Debit Cards</strong> (Visa, Mastercard)<br>\n• <strong>Direct Bank Transfer</strong><br><br>\n\nYou can select your preferred payment option at the checkout page. <br><br>\n\nIf you have any questions or need assistance with your payment, please feel free to contact our support team at <strong>admin@marketplace.local</strong>. Happy shopping!', '2026-06-16 21:47:19'),
('13', '3', 'at what time should i order', 'Welcome to MultiVendor Market! <br><br>\n\nYou can place your order at **any time of the day or night**, as our platform is open 24/7! <br><br>\n\nHowever, for the fastest processing and delivery, we highly recommend placing your orders **between 8:00 AM and 5:00 PM (East Africa Time)**. Ordering during these standard business hours allows our vendors to package and dispatch your items much quicker, often leading to same-day or next-day delivery. <br><br>\n\nIf you have any questions about a specific vendor\'s delivery schedule, feel free to reach out to us at **admin@marketplace.local**. Happy shopping!', '2026-06-16 21:48:28'),
('14', '3', 'who is the developer of the system', 'Hello! <br><br>\n\nThank you for reaching out. I don\'t have the specific details about the developer of the MultiVendor Market system in my database. <br><br>\n\nFor technical inquiries or more information about the platform\'s development, please feel free to contact our support team at <strong>admin@marketplace.local</strong>. <br><br>\n\nIs there anything else I can help you with today? I would be happy to help you find products or navigate the marketplace!', '2026-06-16 21:51:48'),
('15', '3', 'who is the best laptop in the system', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 21:52:29'),
('16', '3', 'Sorry, I encountered an error (HTTP 503). Please try again later.', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 21:52:52'),
('17', '3', 'Sorry, I encountered an error (HTTP 503). Please try again later.', 'Hello! I am so sorry to hear that you encountered an HTTP 503 error. <br><br>\n\nA 503 Service Unavailable error usually means that the MultiVendor Market servers are temporarily overloaded or undergoing scheduled maintenance. <br><br>\n\nHere are a few quick things you can try:<br>\n1. <strong>Wait a minute or two</strong> and try refreshing the page.<br>\n2. <strong>Clear your browser cache</strong> or try accessing the site from a different browser.<br><br>\n\nIf the issue persists, please feel free to reach out to our support team directly at <strong>admin@marketplace.local</strong> so we can look into it for you. <br><br>\n\nThank you for your patience, and please let me know if there is anything else I can assist you with in the meantime!', '2026-06-16 21:52:57'),
('18', '3', 'Sorry, I encountered an error (HTTP 503). Please try again later.', 'Hello!<br>I\'m sorry to hear that you encountered an HTTP 503 error. This usually means our servers are temporarily overloaded or undergoing maintenance.<br>Please try refreshing the page or checking back in a few minutes. If the issue persists, feel free to reach out to our support team at admin@marketplace.local so we can assist you.<br>Thank you for your patience!', '2026-06-16 21:53:38'),
('19', '3', 'how can i become a seller', 'Welcome to MultiVendor Market! We are thrilled that you want to join our community of sellers.<br><br>To become a seller, please follow these simple steps:<br>1. Visit our website and click on the \"Register\" or \"Become a Seller\" link.<br>2. Fill out the registration form with your personal and shop details.<br>3. Submit your application for review.<br>4. Once approved, you can set up your shop, list your products in KSH, and start selling!<br><br>If you have any questions or need help setting up, please contact our support team at admin@marketplace.local. Of course, we are always here to help you succeed!', '2026-06-16 21:54:09'),
('20', '3', 'what are the subscription', 'Welcome to MultiVendor Market!<br><br>Are you looking to sell on our platform? We offer subscription plans for vendors to showcase and sell their products in KSH.<br><br>For the most accurate and up-to-date details on our subscription packages, please reach out to our administration team directly at <b>admin@marketplace.local</b>.<br><br>Let me know if you need help finding products or have any other questions!', '2026-06-16 21:55:05'),
('21', '3', 'what are the subscription', 'Welcome to MultiVendor Market!<br>Regarding subscriptions, these typically refer to the plans available for vendors who want to sell their products on our platform.<br>Since I do not have the specific pricing and package details in my database, I kindly recommend reaching out to our support team for the most accurate and up-to-date information.<br>You can contact them directly at <b>admin@marketplace.local</b>.<br>Please let me know if you need help finding any products or have other questions!', '2026-06-16 21:55:11'),
('22', '3', 'who are the admin', 'Hello!<br>The administration team of MultiVendor Market can be reached directly via email at <b>admin@marketplace.local</b>.<br>Please feel free to reach out to them for any platform-related inquiries or official support.', '2026-06-16 21:55:56'),
('23', '3', 'how many order can i place in a day?', 'Welcome to MultiVendor Market!<br><br>There is no limit to the number of orders you can place in a day. You are free to shop and place as many orders as you like!<br><br>If you are planning to make large bulk purchases or experience any issues during checkout, please feel free to reach out to our support team at admin@marketplace.local for assistance.<br><br>Happy shopping!', '2026-06-16 21:56:40'),
('24', '3', 'how can i become a seller in the platform', 'Welcome to MultiVendor Market! We\'re thrilled that you want to join our community of sellers.<br>To become a seller, please follow these simple steps:<br>1. Visit our website and click on the \"Register\" or \"Sell with Us\" link.<br>2. Select the \"I am a vendor\" option during registration.<br>3. Fill in your shop details, including your shop name and contact information.<br>4. Complete the registration and wait for our admin team to review and approve your account.<br>Once approved, you can start listing your products and earning in KSH!<br>If you have any questions or need assistance setting up, please contact us at admin@marketplace.local.', '2026-06-16 21:59:23'),
('25', '3', 'at which time can i order?', 'Welcome to MultiVendor Market!<br><br>You can place an order at any time, 24/7! Our online marketplace is always open for shopping.<br><br>Please note that while you can make purchases anytime, individual vendors will process and ship your orders during their standard business hours.<br><br>If you have any further questions, feel free to ask or contact us at admin@marketplace.local.', '2026-06-16 22:00:46'),
('26', '3', 'What is the shipping policy?', 'Welcome to MultiVendor Market!<br><br>Since we are a multi-vendor platform, shipping policies, delivery times, and rates vary depending on the individual seller and your delivery location. All shipping fees are calculated in KSH at checkout.<br><br>To find the shipping policy for a specific item, please check the shipping information tab on that product\'s page.<br><br>If you have any questions about a specific order, feel free to reach out to us at admin@marketplace.local.', '2026-06-16 22:01:26'),
('27', '3', 'Show me laptops under 50,000 KSH', 'Welcome to MultiVendor Market! Here are some great laptop options under 50,000 KSH available on our platform:<br><br><b>1. Lenovo IdeaPad 3</b><br>Price: KSH 44,999<br>Key Specs: Intel Core i3, 4GB RAM, 1TB HDD, 14\" Display. Perfect for students and office work.<br><br><b>2.', '2026-06-16 22:01:44'),
('28', '3', 'how can i know the best product in multivender', 'Welcome to MultiVendor Market! I\'d be happy to help you find the best products on our platform.<br><br>To identify the top-quality products, we recommend looking at these key indicators:<br>• <b>Customer Reviews & Ratings:</b> Check the star ratings and read feedback from other buyers on the product page.<br>• <b>Vendor Reputation:</b> Look at the vendor\'s overall rating and how long they have been selling on MultiVendor Market.<br>• <b>Best Seller Badges:</b> Keep an eye out for \"Best Seller\" or \"Featured\" tags on products.<br>• <b>Price Comparison:</b> Compare similar items to ensure you are getting the best value for your KSH.<br><br>If you are looking for a specific item, let me know and I can find the best options for you! For any other questions, feel free to reach out to admin@marketplace.local.', '2026-06-16 22:02:45'),
('29', '3', 'how can i know the best product in multivender', 'Hello Jonathan! Welcome to MultiVendor Market.<br><br>To find the best products on our platform, you can easily identify them by looking at these key indicators:<br><br>1. <strong>Customer Reviews & Ratings:</strong> Check the star ratings and feedback left by other shoppers on the product details page.<br>2. <strong>Seller Reputation:</strong> Since we have multiple sellers, check the seller\'s profile rating to ensure they have a history of good service and quality products.<br>3. <strong>Product Description & Specifications:</strong> Read the details carefully to ensure the product meets your exact needs.<br><br>If you have a specific category or item in mind, let me know and I can help you find it!', '2026-06-16 22:06:54'),
('30', '3', 'what are the categories', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 22:07:14'),
('31', '3', 'what are the categories', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:07:15'),
('32', '3', 'what are the categories', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:07:24'),
('33', '3', 'what are the front contents', 'Hello Jonathan! Here is a summary of what is currently on your page and the platform status:<br><br><strong>Current Page:</strong> AI Assistant Page<br><strong>User Logged In:</strong> Jonathan Bosimwenda (josbosimwenda@gmail.com)<br><strong>Shopping Cart:</strong> 1 item in cart<br><strong>Platform Statistics:</strong><br>- Total Products Available: 3<br>- Total Sellers: 2<br>- Preferred Currency: KSH (Kenyan Shilling)<br><br>How can I assist you with your shopping today?', '2026-06-16 22:07:59'),
('34', '3', 'what are items in my cart?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:10:41'),
('35', '3', 'Tell me about what I see on this page', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:00'),
('36', '3', 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:07'),
('37', '3', 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:13'),
('38', '3', 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:15'),
('39', '3', 'How do I become a seller?', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:30'),
('40', '3', 'Show me laptops under 50,000 KSH', 'Sorry, I encountered an error (HTTP 503). Please try again later.', '2026-06-16 22:11:42'),
('41', '3', 'Show me laptops under 50,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:43'),
('42', '3', 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:11:54'),
('43', '3', 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:13:36'),
('44', '3', 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:32:11'),
('45', '3', 'Best phone under 30,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:32:24'),
('46', '3', 'Best phone under 30,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:32:40'),
('47', '3', 'Show me laptops under 50,000 KSH', 'br><b>4. Lenovo IdeaPad 3</b> - KSH 48,000<br>Specs: Intel Pentium, 4GB RAM, 1TB HDD.<br', '2026-06-16 22:32:50'),
('48', '3', 'hi', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:33:03'),
('49', '3', 'Best phone under 30,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:33:12'),
('50', '3', 'Show me laptops under 50,000 KSH', 'I\'m currently experiencing high demand. Please try again in a moment.', '2026-06-16 22:33:17');

-- Table: blog_categories
DROP TABLE IF EXISTS `blog_categories`;
CREATE TABLE `blog_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `blog_categories` VALUES
('1', 'Marketplace News', 'marketplace-news', NULL),
('2', 'Shopping Tips', 'shopping-tips', NULL),
('3', 'Product Reviews', 'product-reviews', NULL),
('4', 'Seller Spotlight', 'seller-spotlight', NULL),
('5', 'Tech & Gadgets', 'tech-gadgets', NULL),
('6', 'Fashion & Style', 'fashion-style', NULL);

-- Table: blog_comments
DROP TABLE IF EXISTS `blog_comments`;
CREATE TABLE `blog_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(190) NOT NULL,
  `comment` text NOT NULL,
  `status` enum('pending','approved','spam') DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `blog_comments` VALUES
('1', '4', '4', 'Jonathan Bosimwenda', 'josbosimwenda@gmail.com', 'he has the best product ever 🙏🏽💗', 'approved', '2026-06-22 16:00:51'),
('2', '3', '4', 'Jonathan Bosimwenda', 'josbosimwendaadmin@gmail.com', 'i love his shop', 'pending', '2026-06-22 16:39:28');

-- Table: blog_posts
DROP TABLE IF EXISTS `blog_posts`;
CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `author_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `featured_image` varchar(255) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `views` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `author_id` (`author_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `blog_posts` VALUES
('1', '4', 'For the best smartphone without breaking the Bank', 'for-the-best-smartphone-without-breaking-the-bank', '', '<h2>Looking for the Best Smartphone Without Breaking the Bank?</h2>\r\n\r\n<p>Finding a quality smartphone under KSH 50,000 in Kenya can be challenging with so many options available. After testing dozens of devices, we\'ve compiled this comprehensive guide to help you make the right choice.</p>\r\n\r\n<p>Whether you\'re a student, professional, or someone who simply wants value for money, these smartphones offer the perfect balance of performance, camera quality, and battery life.</p>\r\n\r\n<h3>1. Samsung Galaxy A54</h3>\r\n<p><strong>Price:</strong> KSH 48,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.4-inch Super AMOLED 120Hz display</li>\r\n    <li>50MP main camera with OIS</li>\r\n    <li>5000mAh battery with 25W fast charging</li>\r\n    <li>128GB storage, expandable via microSD</li>\r\n    <li>IP67 water and dust resistance</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Galaxy A54 offers a flagship-like experience at a mid-range price. The camera quality is exceptional for this price point, and the battery easily lasts a full day of heavy use. Plus, you get Samsung\'s trusted build quality and software support.</p>\r\n\r\n<h3>2. Google Pixel 6a</h3>\r\n<p><strong>Price:</strong> KSH 45,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.1-inch OLED display</li>\r\n    <li>12.2MP dual-pixel camera with Google\'s computational photography</li>\r\n    <li>Google Tensor chip for AI-powered features</li>\r\n    <li>Magic Eraser and Real Tone camera features</li>\r\n    <li>Pure Android experience with guaranteed updates</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> If you value camera quality and a clean Android experience, the Pixel 6a is unbeatable in this price range. Google\'s computational photography delivers stunning photos that rival much more expensive phones.</p>\r\n\r\n<h3>3. Xiaomi Redmi Note 13 Pro</h3>\r\n<p><strong>Price:</strong> KSH 42,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.67-inch AMOLED 120Hz display</li>\r\n    <li>200MP main camera - the highest resolution in this class</li>\r\n    <li>5100mAh battery with 67W fast charging</li>\r\n    <li>MediaTek Dimensity 7200 processor</li>\r\n    <li>In-display fingerprint sensor</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Redmi Note 13 Pro offers incredible value with its 200MP camera and massive battery. It\'s perfect for photography enthusiasts on a budget who want professional-looking photos.</p>\r\n\r\n<h3>4. Oppo Reno 10</h3>\r\n<p><strong>Price:</strong> KSH 46,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.7-inch AMOLED 120Hz display</li>\r\n    <li>64MP main camera with 32MP telephoto portrait lens</li>\r\n    <li>5000mAh battery with 67W SuperVOOC charging</li>\r\n    <li>Snapdragon 778G processor</li>\r\n    <li>Premium design with slim profile</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Oppo Reno 10 excels in portrait photography with its dedicated telephoto lens. The fast charging means you can go from 0 to 80% in just 30 minutes.</p>\r\n\r\n<h3>5. Tecno Camon 20 Pro</h3>\r\n<p><strong>Price:</strong> KSH 39,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.67-inch AMOLED 120Hz display</li>\r\n    <li>64MP main camera with RGBW sensor</li>\r\n    <li>5000mAh battery</li>\r\n    <li>MediaTek Helio G99 processor</li>\r\n    <li>Dual speakers for immersive audio</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Tecno Camon 20 Pro offers outstanding value for money, especially for users who prioritize camera performance and display quality. It\'s a favorite among Kenyan smartphone users for good reason.</p>\r\n\r\n<h3>Comparison Table</h3>\r\n<table border=\"1\" style=\"border-collapse: collapse; width: 100%; margin: 20px 0;\">\r\n    <thead style=\"background: #2563eb; color: white;\">\r\n        <tr>\r\n            <th style=\"padding: 10px;\">Phone</th>\r\n            <th style=\"padding: 10px;\">Price (KSH)</th>\r\n            <th style=\"padding: 10px;\">Camera</th>\r\n            <th style=\"padding: 10px;\">Battery</th>\r\n            <th style=\"padding: 10px;\">Best For</th>\r\n        </tr>\r\n    </thead>\r\n    <tbody>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Samsung Galaxy A54</td>\r\n            <td style=\"padding: 10px;\">48,999</td>\r\n            <td style=\"padding: 10px;\">50MP</td>\r\n            <td style=\"padding: 10px;\">5000mAh</td>\r\n            <td style=\"padding: 10px;\">All-round performance</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Google Pixel 6a</td>\r\n            <td style=\"padding: 10px;\">45,999</td>\r\n            <td style=\"padding: 10px;\">12.2MP</td>\r\n            <td style=\"padding: 10px;\">4410mAh</td>\r\n            <td style=\"padding: 10px;\">Best camera quality</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Xiaomi Redmi Note 13 Pro</td>\r\n            <td style=\"padding: 10px;\">42,999</td>\r\n            <td style=\"padding: 10px;\">200MP</td>\r\n            <td style=\"padding: 10px;\">5100mAh</td>\r\n            <td style=\"padding: 10px;\">Photography enthusiasts</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Oppo Reno 10</td>\r\n            <td style=\"padding: 10px;\">46,999</td>\r\n            <td style=\"padding: 10px;\">64MP</td>\r\n            <td style=\"padding: 10px;\">5000mAh</td>\r\n            <td style=\"padding: 10px;\">Portrait photography</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Tecno Camon 20 Pro</td>\r\n            <td style=\"padding: 10px;\">39,999</td>\r\n            <td style=\"padding: 10px;\">64MP</td>\r\n            <td style=\"padding: 10px;\">5000mAh</td>\r\n            <td style=\"padding: 10px;\">Budget-friendly performance</td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n\r\n<h3>Final Verdict</h3>\r\n<p>If you want the <strong>best all-round experience</strong>, go for the Samsung Galaxy A54. For the <strong>best camera</strong>, the Google Pixel 6a is unbeatable. If you\'re on a <strong>tight budget</strong>, the Tecno Camon 20 Pro offers incredible value.</p>\r\n\r\n<p>Remember to check our <a href=\"shop.php\">shop page</a> for the latest deals on these smartphones!</p>\r\n\r\n<p><strong>Which smartphone would you choose? Let us know in the comments below!</strong></p>\r\n\r\n<p><em>Published by: Jonathan Bosimwenda | Category: Product Reviews | Tags: smartphones, buying guide, tech</em></p>', 'blog_1782133607_7838c3c175b5db62.jpg', '1', 'published', '4', '2026-06-22 15:49:39', '2026-06-22 16:06:47', '2026-06-22 14:49:39'),
('3', '4', 'Top 5 Best Smartphones Under 50,000 KSH in Kenya (2026)', 'top-5-best-smartphones-under-50-000-ksh-in-kenya-2026-', 'Looking for the best smartphone under KSH 50,000 in Kenya? We&#039;ve tested and reviewed the top 5 options to help you find the perfect phone for your budget. From Samsung to Tecno, discover which phone offers the best value for your money.', '<h2>Looking for the Best Smartphone Without Breaking the Bank?</h2>\r\n\r\n<p>Finding a quality smartphone under KSH 50,000 in Kenya can be challenging with so many options available. After testing dozens of devices, we\'ve compiled this comprehensive guide to help you make the right choice.</p>\r\n\r\n<p>Whether you\'re a student, professional, or someone who simply wants value for money, these smartphones offer the perfect balance of performance, camera quality, and battery life.</p>\r\n\r\n<h3>1. Samsung Galaxy A54</h3>\r\n<p><strong>Price:</strong> KSH 48,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.4-inch Super AMOLED 120Hz display</li>\r\n    <li>50MP main camera with OIS</li>\r\n    <li>5000mAh battery with 25W fast charging</li>\r\n    <li>128GB storage, expandable via microSD</li>\r\n    <li>IP67 water and dust resistance</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Galaxy A54 offers a flagship-like experience at a mid-range price. The camera quality is exceptional for this price point, and the battery easily lasts a full day of heavy use.</p>\r\n\r\n<h3>2. Google Pixel 6a</h3>\r\n<p><strong>Price:</strong> KSH 45,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.1-inch OLED display</li>\r\n    <li>12.2MP dual-pixel camera with Google\'s computational photography</li>\r\n    <li>Google Tensor chip for AI-powered features</li>\r\n    <li>Magic Eraser and Real Tone camera features</li>\r\n    <li>Pure Android experience with guaranteed updates</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> If you value camera quality and a clean Android experience, the Pixel 6a is unbeatable in this price range. Google\'s computational photography delivers stunning photos that rival much more expensive phones.</p>\r\n\r\n<h3>3. Xiaomi Redmi Note 13 Pro</h3>\r\n<p><strong>Price:</strong> KSH 42,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.67-inch AMOLED 120Hz display</li>\r\n    <li>200MP main camera - the highest resolution in this class</li>\r\n    <li>5100mAh battery with 67W fast charging</li>\r\n    <li>MediaTek Dimensity 7200 processor</li>\r\n    <li>In-display fingerprint sensor</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Redmi Note 13 Pro offers incredible value with its 200MP camera and massive battery. It\'s perfect for photography enthusiasts on a budget who want professional-looking photos.</p>\r\n\r\n<h3>4. Oppo Reno 10</h3>\r\n<p><strong>Price:</strong> KSH 46,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.7-inch AMOLED 120Hz display</li>\r\n    <li>64MP main camera with 32MP telephoto portrait lens</li>\r\n    <li>5000mAh battery with 67W SuperVOOC charging</li>\r\n    <li>Snapdragon 778G processor</li>\r\n    <li>Premium design with slim profile</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Oppo Reno 10 excels in portrait photography with its dedicated telephoto lens. The fast charging means you can go from 0 to 80% in just 30 minutes.</p>\r\n\r\n<h3>5. Tecno Camon 20 Pro</h3>\r\n<p><strong>Price:</strong> KSH 39,999</p>\r\n<p><strong>Key Features:</strong></p>\r\n<ul>\r\n    <li>6.67-inch AMOLED 120Hz display</li>\r\n    <li>64MP main camera with RGBW sensor</li>\r\n    <li>5000mAh battery</li>\r\n    <li>MediaTek Helio G99 processor</li>\r\n    <li>Dual speakers for immersive audio</li>\r\n</ul>\r\n<p><strong>Why We Love It:</strong> The Tecno Camon 20 Pro offers outstanding value for money, especially for users who prioritize camera performance and display quality. It\'s a favorite among Kenyan smartphone users for good reason.</p>\r\n\r\n<h3>Comparison Table</h3>\r\n<table border=\"1\" style=\"border-collapse: collapse; width: 100%; margin: 20px 0;\">\r\n    <thead style=\"background: #2563eb; color: white;\">\r\n        <tr>\r\n            <th style=\"padding: 10px;\">Phone</th>\r\n            <th style=\"padding: 10px;\">Price (KSH)</th>\r\n            <th style=\"padding: 10px;\">Camera</th>\r\n            <th style=\"padding: 10px;\">Battery</th>\r\n            <th style=\"padding: 10px;\">Best For</th>\r\n        </tr>\r\n    </thead>\r\n    <tbody>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Samsung Galaxy A54</td>\r\n            <td style=\"padding: 10px;\">48,999</td>\r\n            <td style=\"padding: 10px;\">50MP</td>\r\n            <td style=\"padding: 10px;\">5000mAh</td>\r\n            <td style=\"padding: 10px;\">All-round performance</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Google Pixel 6a</td>\r\n            <td style=\"padding: 10px;\">45,999</td>\r\n            <td style=\"padding: 10px;\">12.2MP</td>\r\n            <td style=\"padding: 10px;\">4410mAh</td>\r\n            <td style=\"padding: 10px;\">Best camera quality</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Xiaomi Redmi Note 13 Pro</td>\r\n            <td style=\"padding: 10px;\">42,999</td>\r\n            <td style=\"padding: 10px;\">200MP</td>\r\n            <td style=\"padding: 10px;\">5100mAh</td>\r\n            <td style=\"padding: 10px;\">Photography enthusiasts</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Oppo Reno 10</td>\r\n            <td style=\"padding: 10px;\">46,999</td>\r\n            <td style=\"padding: 10px;\">64MP</td>\r\n            <td style=\"padding: 10px;\">5000mAh</td>\r\n            <td style=\"padding: 10px;\">Portrait photography</td>\r\n        </tr>\r\n        <tr>\r\n            <td style=\"padding: 10px;\">Tecno Camon 20 Pro</td>\r\n            <td style=\"padding: 10px;\">39,999</td>\r\n            <td style=\"padding: 10px;\">64MP</td>\r\n            <td style=\"padding: 10px;\">5000mAh</td>\r\n            <td style=\"padding: 10px;\">Budget-friendly performance</td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n\r\n<h3>Final Verdict</h3>\r\n<p>If you want the <strong>best all-round experience</strong>, go for the Samsung Galaxy A54. For the <strong>best camera</strong>, the Google Pixel 6a is unbeatable. If you\'re on a <strong>tight budget</strong>, the Tecno Camon 20 Pro offers incredible value.</p>\r\n\r\n<p>Remember to check our <a href=\"shop.php\">shop page</a> for the latest deals on these smartphones!</p>\r\n\r\n<p><strong>Which smartphone would you choose? Let us know in the comments below!</strong></p>\r\n\r\n<p><em>Published by: Admin | Category: Product Reviews | Tags: smartphones, buying guide, tech</em></p>', 'blog_1782133060_10b8e2009e19c748.jpg', '1', 'published', '7', '2026-06-22 15:57:40', NULL, '2026-06-22 14:57:40'),
('4', '4', 'Seller Spotlight: How AquaVibe Became a Top Electronics Seller', 'seller-spotlight-how-aquavibe-became-a-top-electronics-seller', 'Meet AquaVibe - one of our top electronics sellers. From humble beginnings to marketplace success, learn their inspiring journey and get valuable tips for choosing the right electronics.', '<h2>From Small Start to Marketplace Success</h2>\r\n\r\n<p>In our Seller Spotlight series, we feature outstanding sellers who have made a mark on our marketplace. Today, we\'re excited to share the inspiring journey of <strong>AquaVibe</strong>, one of our top electronics sellers.</p>\r\n\r\n<p>AquaVibe started as a small shop with a big dream. Today, they are one of the most trusted electronics sellers on our platform, with hundreds of satisfied customers across Kenya.</p>\r\n\r\n<h3>Meet the Founder</h3>\r\n<p><strong>Jonathan Bosimwenda</strong>, the founder of AquaVibe, shares his story:</p>\r\n\r\n<blockquote style=\"border-left: 4px solid #f59e0b; padding: 15px 20px; background: #fef3c7; border-radius: 8px;\">\r\n    <p>\"I started AquaVibe with a simple vision: to provide Kenyans with access to quality electronics at fair prices. I noticed that many people were either overpaying for electronics or buying substandard products. I wanted to change that.\"</p>\r\n    <p><strong>- Jonathan Bosimwenda, Founder of AquaVibe</strong></p>\r\n</blockquote>\r\n\r\n<h3>Why AquaVibe Stands Out</h3>\r\n\r\n<p>Here\'s what makes AquaVibe a favorite among our customers:</p>\r\n\r\n<h4>✅ 100% Authentic Products</h4>\r\n<p>AquaVibe only sells genuine electronics. Every product goes through a strict verification process before being listed. This means you never have to worry about counterfeits when shopping from AquaVibe.</p>\r\n\r\n<h4>✅ Competitive Pricing</h4>\r\n<p>By sourcing directly from authorized distributors, AquaVibe offers some of the most competitive prices on the marketplace. They pass the savings directly to you.</p>\r\n\r\n<h4>✅ Exceptional Customer Support</h4>\r\n<p>AquaVibe prides itself on responsive customer support. Whether you have a question before buying or need help after purchase, their team is always ready to assist.</p>\r\n\r\n<h4>✅ Fast and Reliable Delivery</h4>\r\n<p>Orders from AquaVibe are processed and shipped quickly. They partner with trusted courier services to ensure your products reach you safely and on time.</p>\r\n\r\n<h3>What Customers Are Saying</h3>\r\n\r\n<div style=\"background: #f8fafc; border-radius: 12px; padding: 15px; margin: 15px 0;\">\r\n    <p><strong>⭐ ⭐ ⭐ ⭐ ⭐</strong></p>\r\n    <p>\"I bought a smartphone from AquaVibe and I\'m absolutely satisfied. The phone was delivered within 2 days and is 100% original. Highly recommend!\"</p>\r\n    <p><small>- Mary W., Nairobi</small></p>\r\n</div>\r\n\r\n<div style=\"background: #f8fafc; border-radius: 12px; padding: 15px; margin: 15px 0;\">\r\n    <p><strong>⭐ ⭐ ⭐ ⭐ ⭐</strong></p>\r\n    <p>\"Excellent service! The laptop I ordered arrived in perfect condition. Customer service was very helpful throughout the process.\"</p>\r\n    <p><small>- James K., Mombasa</small></p>\r\n</div>\r\n\r\n<h3>Top Products from AquaVibe</h3>\r\n\r\n<ul>\r\n    <li><strong>Smartphone Pro</strong> - KSH 29,999 (Premium phone with excellent camera)</li>\r\n    <li><strong>Wireless Mouse</strong> - KSH 1,400 (On sale - 30% off)</li>\r\n    <li><strong>Smartphone Pro</strong> - Available with warranty and fast shipping</li>\r\n</ul>\r\n\r\n<p>Visit AquaVibe\'s <a href=\"seller.php?id=4\">shop page</a> to explore their full collection!</p>\r\n\r\n<h3>Tips from AquaVibe: How to Choose the Right Electronics</h3>\r\n\r\n<p>Jonathan shares some valuable tips for electronics shoppers:</p>\r\n\r\n<ol>\r\n    <li><strong>Do your research:</strong> Read reviews and compare specs before buying.</li>\r\n    <li><strong>Check authenticity:</strong> Look for genuine products with warranty.</li>\r\n    <li><strong>Compare prices:</strong> Don\'t buy the first product you see. Shop around.</li>\r\n    <li><strong>Consider your needs:</strong> Buy what you actually need, not just what looks good.</li>\r\n    <li><strong>Trust verified sellers:</strong> Shop from sellers with good ratings and reviews.</li>\r\n</ol>\r\n\r\n<h3>Shop from AquaVibe Today</h3>\r\n\r\n<p>Ready to experience the AquaVibe difference? Visit their shop now and enjoy quality electronics at great prices!</p>\r\n\r\n<p><a href=\"seller.php?id=4\" class=\"btn btn-primary\" style=\"display: inline-block; padding: 12px 25px; background: #2563eb; color: white; text-decoration: none; border-radius: 10px; font-weight: 600;\">\r\n    <i class=\"fa-solid fa-store\"></i> Visit AquaVibe\'s Shop\r\n</a></p>\r\n\r\n<p><em>Published by: Admin | Category: Seller Spotlight | Tags: sellers, success story, electronics</em></p>', 'blog_1782133137_f30fb9932c3cc0a8.png', '4', 'published', '13', '2026-06-22 15:58:57', '2026-06-22 16:05:47', '2026-06-22 14:58:57'),
('5', '4', 'ddddddddddddd', 'ddddddddddddd', 'gggggggggggggggggggggg', '<h2>From Small Start to Marketplace Success</h2>\r\n\r\n<p>In our Seller Spotlight series, we feature outstanding sellers who have made a mark on our marketplace. Today, we\'re excited to share the inspiring journey of <strong>AquaVibe</strong>, one of our top electronics sellers.</p>\r\n\r\n<p>AquaVibe started as a small shop with a big dream. Today, they are one of the most trusted electronics sellers on our platform, with hundreds of satisfied customers across Kenya.</p>\r\n\r\n<h3>Meet the Founder</h3>\r\n<p><strong>Jonathan Bosimwenda</strong>, the founder of AquaVibe, shares his story:</p>\r\n\r\n<blockquote style=\"border-left: 4px solid #f59e0b; padding: 15px 20px; background: #fef3c7; border-radius: 8px;\">\r\n    <p>\"I started AquaVibe with a simple vision: to provide Kenyans with access to quality electronics at fair prices. I noticed that many people were either overpaying for electronics or buying substandard products. I wanted to change that.\"</p>\r\n    <p><strong>- Jonathan Bosimwenda, Founder of AquaVibe</strong></p>\r\n</blockquote>\r\n\r\n<h3>Why AquaVibe Stands Out</h3>\r\n\r\n<p>Here\'s what makes AquaVibe a favorite among our customers:</p>\r\n\r\n<h4>✅ 100% Authentic Products</h4>\r\n<p>AquaVibe only sells genuine electronics. Every product goes through a strict verification process before being listed. This means you never have to worry about counterfeits when shopping from AquaVibe.</p>\r\n\r\n<h4>✅ Competitive Pricing</h4>\r\n<p>By sourcing directly from authorized distributors, AquaVibe offers some of the most competitive prices on the marketplace. They pass the savings directly to you.</p>\r\n\r\n<h4>✅ Exceptional Customer Support</h4>\r\n<p>AquaVibe prides itself on responsive customer support. Whether you have a question before buying or need help after purchase, their team is always ready to assist.</p>\r\n\r\n<h4>✅ Fast and Reliable Delivery</h4>\r\n<p>Orders from AquaVibe are processed and shipped quickly. They partner with trusted courier services to ensure your products reach you safely and on time.</p>\r\n\r\n<h3>What Customers Are Saying</h3>\r\n\r\n<div style=\"background: #f8fafc; border-radius: 12px; padding: 15px; margin: 15px 0;\">\r\n    <p><strong>⭐ ⭐ ⭐ ⭐ ⭐</strong></p>\r\n    <p>\"I bought a smartphone from AquaVibe and I\'m absolutely satisfied. The phone was delivered within 2 days and is 100% original. Highly recommend!\"</p>\r\n    <p><small>- Mary W., Nairobi</small></p>\r\n</div>\r\n\r\n<div style=\"background: #f8fafc; border-radius: 12px; padding: 15px; margin: 15px 0;\">\r\n    <p><strong>⭐ ⭐ ⭐ ⭐ ⭐</strong></p>\r\n    <p>\"Excellent service! The laptop I ordered arrived in perfect condition. Customer service was very helpful throughout the process.\"</p>\r\n    <p><small>- James K., Mombasa</small></p>\r\n</div>\r\n\r\n<h3>Top Products from AquaVibe</h3>\r\n\r\n<ul>\r\n    <li><strong>Smartphone Pro</strong> - KSH 29,999 (Premium phone with excellent camera)</li>\r\n    <li><strong>Wireless Mouse</strong> - KSH 1,400 (On sale - 30% off)</li>\r\n    <li><strong>Smartphone Pro</strong> - Available with warranty and fast shipping</li>\r\n</ul>\r\n\r\n<p>Visit AquaVibe\'s <a href=\"seller.php?id=4\">shop page</a> to explore their full collection!</p>\r\n\r\n<h3>Tips from AquaVibe: How to Choose the Right Electronics</h3>\r\n\r\n<p>Jonathan shares some valuable tips for electronics shoppers:</p>\r\n\r\n<ol>\r\n    <li><strong>Do your research:</strong> Read reviews and compare specs before buying.</li>\r\n    <li><strong>Check authenticity:</strong> Look for genuine products with warranty.</li>\r\n    <li><strong>Compare prices:</strong> Don\'t buy the first product you see. Shop around.</li>\r\n    <li><strong>Consider your needs:</strong> Buy what you actually need, not just what looks good.</li>\r\n    <li><strong>Trust verified sellers:</strong> Shop from sellers with good ratings and reviews.</li>\r\n</ol>\r\n\r\n<h3>Shop from AquaVibe Today</h3>\r\n\r\n<p>Ready to experience the AquaVibe difference? Visit their shop now and enjoy quality electronics at great prices!</p>\r\n\r\n<p><a href=\"seller.php?id=4\" class=\"btn btn-primary\" style=\"display: inline-block; padding: 12px 25px; background: #2563eb; color: white; text-decoration: none; border-radius: 10px; font-weight: 600;\">\r\n    <i class=\"fa-solid fa-store\"></i> Visit AquaVibe\'s Shop\r\n</a></p>\r\n\r\n<p><em>Published by: Admin | Category: Seller Spotlight | Tags: sellers, success story, electronics</em></p>', 'blog_1782134340_4ed919c3dee1b929.jpg', '0', 'published', '0', '2026-06-22 16:19:00', NULL, '2026-06-22 15:19:00');

-- Table: carts
DROP TABLE IF EXISTS `carts`;
CREATE TABLE `carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `carts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `carts` VALUES
('25', '2', '4', '1', '2026-07-01 12:34:56');

-- Table: categories
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `slug_2` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` VALUES
('1', 'Electronics', 'electronics', 'Mobile phones, laptops, accessories and gadgets.', '2026-06-15 14:34:08'),
('2', 'Fashion', 'fashion', 'Clothing, shoes, handbags and accessories.', '2026-06-15 14:34:08'),
('4', 'Food', 'testy-is-my-name', 'enjoy good food with us', '2026-06-15 20:01:14'),
('5', 'Smartphones', 'smartphones', 'Premium smartphones from top brands like Apple, Samsung, Tecno, and more.', '2026-06-23 13:03:30'),
('6', 'Laptops and Computers', 'laptops-computers', 'High-performance laptops, desktops, and computing accessories for work and play.', '2026-06-23 13:04:24'),
('7', 'Audio and Headphones', 'audio-headphones', 'Premium sound equipment including headphones, speakers, and audio accessories.', '2026-06-23 13:05:17'),
('8', 'TV and Video', 'tv-video', 'Smart TVs, projectors, and streaming devices for the ultimate home entertainment.', '2026-06-23 13:07:03'),
('9', 'Smart Home', 'smart-home', 'Smart devices, security systems, and home automation products.', '2026-06-23 13:08:54'),
('10', 'Phone Accessories', 'phone-accessories', 'Cases, screen protectors, chargers, and essential phone accessories.', '2026-06-23 13:09:43'),
('11', 'Cameras and Photography', 'cameras-photography', 'Professional and amateur cameras, lenses, and photography equipment.', '2026-06-23 13:10:37'),
('12', 'Children s Clothing', 'kids-clothing', 'Comfortable and stylish clothing for babies, toddlers, and kids.', '2026-06-23 13:11:49'),
('13', 'Fashion Accessories', 'fashion-accessories', 'Sunglasses, belts, hats, scarves, and stylish accessories.', '2026-06-23 13:14:51'),
('14', 'Furniture', 'furniture', 'Quality sofas, beds, tables, chairs, and wardrobes for every room.', '2026-06-23 13:16:02'),
('15', 'Beauty and Personal Care', 'beauty-personal-care', 'Premium skincare, makeup, haircare, and grooming products.', '2026-06-23 13:16:55'),
('16', 'Health aand Wellness', 'health-wellness', 'Products to support your physical, mental, and emotional well-being.', '2026-06-23 13:18:09'),
('17', 'Books and Stationery', 'books-stationery', 'Books, office supplies, and educational materials.', '2026-06-23 13:19:14'),
('18', 'Sports and Outdoors', 'sports-outdoors', 'Equipment, gear, and apparel for sports and outdoor adventures.', '2026-06-23 13:20:05'),
('19', 'Other', '', '', '2026-06-23 13:21:38');

-- Table: chat_receipts
DROP TABLE IF EXISTS `chat_receipts`;
CREATE TABLE `chat_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `shared_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: chat_shared_products
DROP TABLE IF EXISTS `chat_shared_products`;
CREATE TABLE `chat_shared_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `shared_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_id` (`chat_id`),
  KEY `product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: chats
DROP TABLE IF EXISTS `chats`;
CREATE TABLE `chats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','receipt','product','image') DEFAULT 'text',
  `file_name` varchar(255) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_thumb` varchar(255) DEFAULT NULL,
  `is_file` tinyint(1) DEFAULT 0,
  `receipt_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `sender` enum('user','seller','admin') NOT NULL,
  `shared_by` enum('customer','seller','admin') DEFAULT 'customer',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `chats_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chats_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `chats` VALUES
('1', '4', '4', 'hi', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-15 22:35:00'),
('2', '3', '4', 'ho', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-17 19:10:33'),
('3', '3', '4', 'hey how are you?', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '0', '2026-06-17 19:13:18'),
('4', '4', '4', 'hi how are you doing ?', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '1', '2026-06-17 19:14:12'),
('5', '3', '1', 'Hi', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 12:45:42'),
('6', '5', '4', 'Hi how are you can you please notify me when Iphone 15 pro will be availabe, with this number 0768062600 and also you laptops for good qualities, thank you and have a good day', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 12:53:37'),
('7', '5', '4', 'Hi, i am good and that is fine i will notify you when they arrive', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '1', '2026-06-18 12:55:44'),
('8', '5', '1', 'hi', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 13:11:04'),
('9', '5', '4', 'thank you sir', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 13:19:04'),
('10', '5', '4', 'You are welcome', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '1', '2026-06-18 13:27:35'),
('11', '5', '4', '📄 Order Receipt Shared\nOrder #: ORD-5A3F7A\nTotal: KSH 12,000.00\nStatus: Shipped', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 14:43:58'),
('12', '5', '4', '🧾 <strong>ORDER RECEIPT</strong>\n━━━━━━━━━━━━━━━━━━━━━━━━\n📋 Order #: ORD-5A3F7A\n📅 Date: 16 Jun 2026, 02:47 PM\n👤 Customer: Jonathan Bosimwenda\n🏪 Shop: AquaVibe\n━━━━━━━━━━━━━━━━━━━━━━━━\n📦 ITEMS:\n  • Wireless Mouse\n    Qty: 6 x KSH 2,000.00\n    Subtotal: KSH 12,000.00\n━━━━━━━━━━━━━━━━━━━━━━━━\n💰 TOTAL: KSH 12,000.00\n💳 Payment: M-Pesa\n📦 Status: SHIPPED\n━━━━━━━━━━━━━━━━━━━━━━━━\nThank you for shopping with us!', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 14:49:51'),
('13', '5', '4', '📸 Photo Shared\n📷 View photo: uploads/chat_photos/chat_1781784150_71a80b6acf31f794.jpg', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 15:02:30'),
('14', '5', '4', '🛍️ PRODUCT SHARED\n━━━━━━━━━━━━━━━━━━━━━━━━\n📦 Wireless Mouse\n💰 Price: KSH 1,400.00 🔥 ON SALE!\n📝 ggggfgdrrddr\n📊 Stock: 10 units\n━━━━━━━━━━━━━━━━━━━━━━━━\n🔗 View: product.php?id=3', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '1', '2026-06-18 15:10:23'),
('15', '5', '4', 'thanks i have seen', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-18 15:44:04'),
('16', '4', '4', 'hi this is the admin', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'user', 'customer', '1', '2026-06-22 16:40:06'),
('17', '5', '4', '🧾 ORDER RECEIPT\n━━━━━━━━━━━━━━━━━━━━━━━━\nOrder #: ORD-EE573B\nDate: 16 Jun 2026, 02:45 PM\nCustomer: Jonathan Bosimwenda\nShop: AquaVibe\n━━━━━━━━━━━━━━━━━━━━━━━━\nITEMS:\n━━━━━━━━━━━━━━━━━━━━━━━━\nTOTAL: KSH 12,000.00\nPayment: M-Pesa\nStatus: DELIVERED\n━━━━━━━━━━━━━━━━━━━━━━━━\nThank you for shopping!', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '0', '2026-06-23 11:36:47'),
('18', '5', '1', '🛍️ PRODUCT SHARED\n━━━━━━━━━━━━━━━━━━━━━━━━\n📦 Adidas Ultraboost\n💰 Price: KSH 1,749.30 🔥 ON SALE!\n📝 Lightweight running shoes with responsive cushioning.\n📊 Stock: 18 units\n━━━━━━━━━━━━━━━━━━━━━━━━\n🔗 View: product.php?id=9', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '0', '2026-07-01 12:42:27'),
('19', '5', '1', '🧾 ORDER RECEIPT\n━━━━━━━━━━━━━━━━━━━━━━━━\nOrder #: ORD-QT9HM8\nDate: 22 Jun 2026, 05:14 PM\nCustomer: Jonathan Bosimwenda\nShop: ZAMART Seller\n━━━━━━━━━━━━━━━━━━━━━━━━\nITEMS:\n  • Nike Air Max 270\n    Qty: 1 x KSH 2,249.25\n    Subtotal: KSH 2,249.25\n  • Dyson V15 Detect Cordless Vacuum\n    Qty: 1 x KSH 3,199.20\n    Subtotal: KSH 3,199.20\n━━━━━━━━━━━━━━━━━━━━━━━━\nTOTAL: KSH 5,448.45\nPayment: Card\nStatus: PENDING\n━━━━━━━━━━━━━━━━━━━━━━━━\nThank you for shopping!', 'text', NULL, NULL, NULL, NULL, '0', NULL, NULL, 'seller', 'customer', '0', '2026-07-01 12:42:37');

-- Table: contacts
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `contacts` VALUES
('1', 'Jonathan Bosimwenda', 'Estherlakadia2@gmail.com', 'Complain', 'i want to ask about a product if i can find it here in the system and and how can i become a seller', '2026-06-18 16:47:14');

-- Table: faq_categories
DROP TABLE IF EXISTS `faq_categories`;
CREATE TABLE `faq_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fa-circle-question',
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `faq_categories` VALUES
('1', 'General Questions', 'general', NULL, 'fa-circle-question', '1', '2026-06-23 11:42:16'),
('2', 'Account & Profile', 'account', NULL, 'fa-user', '2', '2026-06-23 11:42:16'),
('3', 'Orders & Payments', 'orders', NULL, 'fa-credit-card', '3', '2026-06-23 11:42:16'),
('4', 'Products & Shopping', 'products', NULL, 'fa-box', '4', '2026-06-23 11:42:16'),
('5', 'Selling on Our Platform', 'selling', NULL, 'fa-store', '5', '2026-06-23 11:42:16'),
('6', 'Shipping & Delivery', 'shipping', NULL, 'fa-truck', '6', '2026-06-23 11:42:16'),
('7', 'Returns & Refunds', 'returns', NULL, 'fa-rotate-left', '7', '2026-06-23 11:42:16'),
('8', 'Technical Issues', 'technical', NULL, 'fa-gear', '8', '2026-06-23 11:42:16');

-- Table: faq_items
DROP TABLE IF EXISTS `faq_items`;
CREATE TABLE `faq_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `question` text NOT NULL,
  `answer` longtext NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `faq_items` VALUES
('1', '1', 'What is this marketplace?', 'Our marketplace is a multi-vendor platform where customers can buy products from various trusted sellers all in one place. We connect buyers with quality products and reliable sellers.', '1', '1', '2026-06-23 11:42:16', NULL),
('2', '1', 'Is it safe to shop here?', 'Yes! We prioritize security with secure payment processing, buyer protection, and verified sellers. All transactions are encrypted and monitored for safety.', '2', '1', '2026-06-23 11:42:16', NULL),
('3', '2', 'How do I create an account?', 'Click on the \"Register\" button at the top right corner of the page. Fill in your name, email, and password, then click \"Create Account\". It\'s free and takes less than 2 minutes!', '1', '1', '2026-06-23 11:42:16', NULL),
('4', '2', 'I forgot my password. How do I reset it?', 'Click on \"Forgot Password\" on the login page. Enter your email address and we\'ll send you a password reset link. Follow the instructions in the email.', '2', '1', '2026-06-23 11:42:16', NULL),
('5', '3', 'What payment methods do you accept?', 'We accept M-Pesa, Credit/Debit Cards (Visa, Mastercard), Bank Transfer, and PayPal. All payments are secure and encrypted.', '1', '1', '2026-06-23 11:42:16', NULL),
('6', '3', 'How do I track my order?', 'Go to \"My Orders\" in your dashboard, find your order, and click \"Track Order\" to see real-time status updates on your delivery.', '2', '1', '2026-06-23 11:42:16', NULL),
('7', '4', 'How do I find products?', 'Use the search bar at the top of the page to search by product name, category, or brand. You can also browse products by category from the main menu.', '1', '1', '2026-06-23 11:42:16', NULL),
('8', '4', 'How can I contact a seller?', 'Go to the product page and click the \"Contact Seller\" button. You can also visit the seller\'s shop page and use the chat feature to send a message.', '2', '1', '2026-06-23 11:42:16', NULL),
('9', '5', 'How do I become a seller?', 'Register as a customer, go to your dashboard, and click \"Become a Seller\". Fill in your business details, upload required documents, and wait for approval.', '1', '1', '2026-06-23 11:42:16', NULL),
('10', '5', 'How much does it cost to sell?', 'We offer flexible subscription plans starting from KSH 999 per month. You can choose the plan that best fits your business needs.', '2', '1', '2026-06-23 11:42:16', NULL),
('11', '6', 'How long does shipping take?', 'Shipping typically takes 2-5 business days within Kenya. Free shipping is available on orders over KSH 5,000.', '1', '1', '2026-06-23 11:42:16', NULL),
('12', '6', 'Do you ship outside Kenya?', 'Currently, we only ship within Kenya. We are working on expanding our delivery services to other countries in the future.', '2', '1', '2026-06-23 11:42:16', NULL),
('13', '7', 'What is your return policy?', 'We accept returns within 7 days of delivery. Items must be unused with original packaging. Contact the seller first to initiate a return.', '1', '1', '2026-06-23 11:42:16', NULL),
('14', '7', 'How do I get a refund?', 'After your return is approved, the seller will process your refund. Depending on the payment method, it may take 3-5 business days for the funds to reflect.', '2', '1', '2026-06-23 11:42:16', NULL),
('15', '8', 'The website is not loading properly. What should I do?', 'Clear your browser cache, try a different browser, or check your internet connection. If the problem persists, contact our support team.', '1', '1', '2026-06-23 11:42:16', NULL);

-- Table: notifications
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(120) NOT NULL,
  `title` varchar(190) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `notifications` VALUES
('1', '3', 'chat', 'New Message', 'You have a new message from a customer.', '0', '2026-06-15 22:35:00'),
('2', '3', 'chat', 'New Message', 'You have a new message from a customer.', '0', '2026-06-17 19:10:33'),
('3', '3', 'chat', 'New Message', 'You have a new message from seller.', '0', '2026-06-17 19:13:18'),
('4', '4', 'chat', 'New Message', 'You have a new message from seller.', '0', '2026-06-17 19:14:12'),
('5', '2', 'chat', 'New Message', 'You have a new message from a customer.', '0', '2026-06-18 12:45:42'),
('6', '3', 'chat', 'New Message', 'You have a new message from a customer.', '0', '2026-06-18 12:53:37'),
('7', '5', 'chat', 'New Message', 'You have a new message from seller.', '0', '2026-06-18 12:55:44'),
('8', '2', 'chat', 'New Message', 'You have a new message from a customer.', '0', '2026-06-18 13:11:04'),
('9', '3', 'chat', 'New Message', 'You have a new message from a customer.', '0', '2026-06-18 13:19:04'),
('10', '5', 'chat', 'New Message', 'You have a new message from seller.', '0', '2026-06-18 13:27:35'),
('11', '3', 'document_rejected', 'Document Rejected', 'Your bank statement was rejected. Reason: Kind Upload the correct document for better review and approve of you request', '0', '2026-06-22 13:54:53');

-- Table: offers
DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `discount_percent` tinyint(4) NOT NULL,
  `expires_at` date NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `offers` VALUES
('1', 'LAUNCH10', '10% off first order', '10', '2026-07-15', '1', '2026-06-15 14:34:08');

-- Table: order_items
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `order_items` VALUES
('8', '7', '3', '6', '2000.00'),
('9', '8', '3', '1', '2000.00'),
('14', '13', '3', '1', '1400.00'),
('15', '13', '4', '1', '102849.15'),
('16', '13', '1', '1', '20999.30'),
('17', '14', '7', '1', '1600.00'),
('18', '14', '12', '1', '49999.00'),
('19', '15', '6', '1', '269999.10'),
('20', '15', '10', '1', '1500.00'),
('21', '16', '8', '1', '2249.25'),
('22', '16', '13', '1', '3199.20'),
('23', '17', '3', '1', '1400.00'),
('24', '17', '1', '1', '20999.30');

-- Table: orders
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(60) NOT NULL,
  `shipping_address` text NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  KEY `user_id` (`user_id`),
  KEY `seller_id` (`seller_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `orders` VALUES
('2', '4', '1', 'ORD-02FC0A', '43997.00', 'M-Pesa', 'highrise', 'pending', '2026-06-15 22:24:38'),
('6', '5', '4', 'ORD-EE573B', '12000.00', 'M-Pesa', 'highrise', 'delivered', '2026-06-16 14:45:51'),
('7', '5', '4', 'ORD-5A3F7A', '12000.00', 'M-Pesa', 'highrise', 'shipped', '2026-06-16 14:47:57'),
('8', '4', '4', 'ORD-09D080', '2000.00', 'Card', 'highrise', 'cancelled', '2026-06-16 17:25:40'),
('13', '5', '4', 'ORD-M85H5P', '125248.45', 'M-Pesa', 'highrise', 'delivered', '2026-06-22 17:08:39'),
('14', '5', '1', 'ORD-X0LMZI', '51599.00', 'M-Pesa', 'highrise', 'pending', '2026-06-22 17:08:39'),
('15', '5', '4', 'ORD-STISJ5', '271499.10', 'Card', 'highrise', 'delivered', '2026-06-22 17:14:00'),
('16', '5', '1', 'ORD-QT9HM8', '5448.45', 'Card', 'highrise', 'pending', '2026-06-22 17:14:00'),
('17', '3', '4', 'ORD-F1OYE1', '22399.30', 'Bank Transfer', 'highrise', 'delivered', '2026-06-22 17:27:45');

-- Table: payments
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `method` varchar(60) NOT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_reference` varchar(120) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_ibfk_1` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table: product_images
DROP TABLE IF EXISTS `product_images`;
CREATE TABLE `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `product_images` VALUES
('9', '3', 'img_6a3173937cc8b6.31294522.jpg', '2026-06-16 19:02:27'),
('10', '3', 'img_6a3173b804da54.85047371.jpg', '2026-06-16 19:03:04'),
('11', '3', 'img_6a3173b82784d8.74663276.jpg', '2026-06-16 19:03:04'),
('12', '3', 'img_6a3173b82d7644.32684744.jpg', '2026-06-16 19:03:04'),
('30', '10', 'img_6a38ff8fa63237.28934170.jpg', '2026-06-22 12:25:35'),
('31', '10', 'img_6a38ff8fa9b109.15627544.jpg', '2026-06-22 12:25:35'),
('32', '10', 'img_6a38ff9fd2da03.46662990.jpg', '2026-06-22 12:25:51'),
('33', '10', 'img_6a38ff9fd63491.01429961.jpg', '2026-06-22 12:25:51'),
('34', '10', 'img_6a38ff9fd80033.19297367.jpg', '2026-06-22 12:25:51'),
('37', '10', 'img_6a38ff9fdf9408.71532688.jpg', '2026-06-22 12:25:51'),
('38', '5', 'img_6a39009a6f3268.82558183.jpg', '2026-06-22 12:30:02'),
('39', '5', 'img_6a39009a7121a0.44586580.jpg', '2026-06-22 12:30:02'),
('40', '5', 'img_6a39009a744146.44529760.jpg', '2026-06-22 12:30:02'),
('41', '5', 'img_6a39009a75d1c6.48162443.jpg', '2026-06-22 12:30:02'),
('42', '5', 'img_6a39009a78e019.96846120.jpg', '2026-06-22 12:30:02'),
('43', '5', 'img_6a39009a7a2563.00754619.jpg', '2026-06-22 12:30:02'),
('44', '5', 'img_6a39009a7cff40.80334986.jpg', '2026-06-22 12:30:02'),
('45', '4', 'img_6a39015f1c20a0.12350419.jpg', '2026-06-22 12:33:19'),
('46', '4', 'img_6a39015f21c970.15686376.jpg', '2026-06-22 12:33:19'),
('47', '4', 'img_6a39015f253ba1.61861163.jpg', '2026-06-22 12:33:19'),
('48', '4', 'img_6a39015f28b7d9.23919000.jpg', '2026-06-22 12:33:19'),
('49', '4', 'img_6a39015f29f011.83730286.jpg', '2026-06-22 12:33:19'),
('50', '4', 'img_6a39015f2d6935.19007962.jpg', '2026-06-22 12:33:19'),
('51', '6', 'img_6a390282a71376.49188076.jpg', '2026-06-22 12:38:10'),
('52', '6', 'img_6a390282ab17d0.33014310.jpg', '2026-06-22 12:38:10'),
('53', '6', 'img_6a390282b30962.39604699.jpg', '2026-06-22 12:38:10'),
('54', '6', 'img_6a390282bb2236.61435435.jpg', '2026-06-22 12:38:10'),
('55', '1', 'img_6a3902fe93a5f5.83582779.jpg', '2026-06-22 12:40:14'),
('56', '1', 'img_6a3902fe97e649.45860936.jpg', '2026-06-22 12:40:14'),
('57', '1', 'img_6a3902fe9cfff9.90435025.jpg', '2026-06-22 12:40:14'),
('58', '1', 'img_6a3902fea29bb8.44375008.jpg', '2026-06-22 12:40:14'),
('59', '7', 'img_6a391cf79db677.64325378.jpg', '2026-06-22 14:31:03'),
('60', '7', 'img_6a391cf79efaf6.96463831.jpg', '2026-06-22 14:31:03'),
('61', '7', 'img_6a391cf7a22d31.33924759.jpg', '2026-06-22 14:31:03'),
('62', '7', 'img_6a391cf7a42c64.86680940.jpg', '2026-06-22 14:31:03'),
('64', '8', 'img_6a391ddc6f4181.87653578.jpg', '2026-06-22 14:34:52'),
('65', '8', 'img_6a391ddc72e278.32233182.jpg', '2026-06-22 14:34:52'),
('66', '8', 'img_6a391ddc766226.23861992.jpg', '2026-06-22 14:34:52'),
('67', '8', 'img_6a391ddc7a3405.48331041.jpg', '2026-06-22 14:34:52'),
('68', '8', 'img_6a391ddc7bc6e3.68316002.jpg', '2026-06-22 14:34:52'),
('69', '9', 'img_6a391f0ac4a2e9.37548113.jpg', '2026-06-22 14:39:54'),
('70', '9', 'img_6a391f0ac74358.86222555.jpg', '2026-06-22 14:39:54'),
('71', '9', 'img_6a391f0aca6260.11526100.jpg', '2026-06-22 14:39:54'),
('72', '9', 'img_6a391f0acb6544.56486137.jpg', '2026-06-22 14:39:54'),
('73', '12', 'img_6a391f6d799693.89227434.jpg', '2026-06-22 14:41:33'),
('74', '12', 'img_6a391f6d7b2181.58135902.jpg', '2026-06-22 14:41:33'),
('75', '12', 'img_6a391f6d7e2a83.32292740.jpg', '2026-06-22 14:41:33'),
('76', '12', 'img_6a391f6d7fade3.54468291.jpg', '2026-06-22 14:41:33'),
('79', '13', 'img_6a391fdd6901d9.80232946.jpg', '2026-06-22 14:43:25'),
('80', '13', 'img_6a391feaa64654.12058856.jpg', '2026-06-22 14:43:38'),
('81', '13', 'img_6a391feaac2bf0.88133385.jpg', '2026-06-22 14:43:38');

-- Table: products
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `is_on_sale` tinyint(1) DEFAULT 0 COMMENT '1 = on sale, 0 = not on sale',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `status` (`status`),
  KEY `seller_id` (`seller_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` VALUES
('1', '4', '5', 'Smartphone Pro', 'smartphone-pro', 'High-performance phone with excellent battery life.', 'A premium smartphone with a responsive display, long battery life and premium camera features.', '29999.00', '9', '0', 'approved', '4.50', '2026-06-15 14:34:08', NULL, '30', '20999.30', '2026-06-23 12:24:44', '2026-06-24 12:24:44', '1'),
('3', '4', '1', 'Wireless Mouse', 'wireless-mouse-6a305222e3c12', 'ggggfgdrrddr', 'hhjhuuuuuuuuuuuuuuuuuuuuuuuufffffffffffff', '2000.00', '8', '0', 'approved', '0.00', '2026-06-15 22:27:30', NULL, '30', '1400.00', '2026-06-17 16:41:44', '2026-06-20 16:41:44', '1'),
('4', '4', '5', 'iPhone 15 Pro 256GB', 'iphone-15-pro-256gb', 'Apple&amp;amp;#039;s latest flagship with titanium design and A17 Pro chip.', 'The iPhone 15 Pro features a lightweight titanium design, A17 Pro chip for next-level performance, 48MP camera system, and USB-C port. Perfect for professionals and content creators.', '120999.00', '14', '0', 'approved', '4.80', '2026-06-22 12:02:15', NULL, '15', '102849.15', '2026-06-23 12:22:53', '2026-07-20 12:22:53', '1'),
('5', '4', '5', 'Samsung Galaxy S24', 'samsung-galaxy-s24-ultra', 'Premium Android smartphone with built-in S Pen and AI features.', 'The Galaxy S24 Ultra 254gb features a 6.8-inch Dynamic AMOLED display, 200MP camera, built-in S Pen, and Galaxy AI for real-time translations and photo editing. A true powerhouse.', '139999.00', '20', '0', 'approved', '4.70', '2026-06-22 12:02:15', NULL, '20', '111999.20', '2026-06-23 12:23:18', '2026-07-04 12:23:18', '1'),
('6', '4', '6', 'MacBook Pro 14', 'macbook-pro-14-m3', 'Powerful laptop with Apple&amp;amp;#039;s latest M3 chip for professionals.', 'The 14-inch MacBook Pro with M3 chip delivers incredible performance for demanding tasks. Perfect for developers, designers, and content creators. Features 16GB RAM, 512GB SSD, and up to 22 hours battery life.', '299999.00', '9', '0', 'approved', '4.90', '2026-06-22 12:02:15', NULL, '10', '269999.10', '2026-06-23 12:23:38', '2026-06-27 12:23:38', '1'),
('7', '1', '7', 'Sony WH-1000XM5', 'sony-wh-1000xm5-headphones', 'Industry-leading noise cancellation with premium sound quality. Noise Cancelling Headphones', 'Experience unparalleled noise cancellation and exceptional sound quality with the Sony WH-1000XM5. Features up to 30 hours battery life, quick charging, and adaptive sound control for the ultimate listening experience.', '1600.00', '29', '0', 'approved', '4.60', '2026-06-22 12:02:15', NULL, '0', NULL, NULL, NULL, '0'),
('8', '1', '13', 'Nike Air Max 270', 'nike-air-max-270', 'Comfortable running shoes with visible air cushioning.', 'The Nike Air Max 270 features a large visible Air unit in the heel for responsive cushioning. Breathable mesh upper with a foam midsole provides all-day comfort. Perfect for running and everyday wear.', '2999.00', '7', '0', 'approved', '4.40', '2026-06-22 12:02:15', NULL, '25', '2249.25', '2026-07-01 11:41:47', '2026-07-10 11:41:47', '1'),
('9', '1', '2', 'Adidas Ultraboost', 'adidas-ultraboost-light', 'Lightweight running shoes with responsive cushioning.', 'The Adidas Ultraboost Light features a lightweight design with responsive BOOST cushioning for ultimate comfort. The Primeknit upper provides a sock-like fit with breathability for long runs.', '2499.00', '18', '0', 'approved', '4.50', '2026-06-22 12:02:15', NULL, '30', '1749.30', '2026-06-22 13:39:54', '2026-06-28 13:39:54', '1'),
('10', '4', '13', 'Gucci GG Shoulder Bag', 'gucci-gg-marmont-bag', 'Luxury Italian leather shoulder bag with iconic GG logo.', 'The Gucci GG Marmont Matelassé shoulder bag features the iconic double G logo in a chevron quilted leather design. Made in Italy with a chain shoulder strap and suede interior. A timeless luxury piece.', '1500.00', '7', '0', 'approved', '4.80', '2026-06-22 12:02:15', NULL, '0', NULL, NULL, NULL, '0'),
('12', '1', '19', 'PlayStation 5', 'playstation-5-digital', 'Next-gen gaming console with blazing fast SSD and stunning graphics.', 'The PlayStation 5 Digital Edition features a custom AMD Zen 2 processor, 16GB GDDR6, and a super-fast SSD for near-instant loading times. Experience immersive gaming with haptic feedback and adaptive triggers on the DualSense controller.', '49999.00', '5', '0', 'approved', '4.90', '2026-06-22 12:02:15', NULL, '0', NULL, NULL, NULL, '0'),
('13', '1', '19', 'Dyson V15 Detect Cordless Vacuum', 'dyson-v15-detect', 'Intelligent cordless vacuum with laser dust detection.', 'The Dyson V15 Detect uses a laser to reveal invisible dust and debris on hard floors. Features powerful suction, up to 60 minutes run time, and an LCD screen showing real-time particle count. The ultimate cleaning tool.', '3999.00', '9', '0', 'approved', '4.60', '2026-06-22 12:02:15', NULL, '20', '3199.20', '2026-07-01 11:40:55', '2026-07-05 11:40:55', '1');

-- Table: reviews
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `rating` (`rating`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reviews` VALUES
('1', '5', '6', '4', 'this is a very good product', 'approved', '2026-06-22 17:17:17'),
('2', '5', '1', '1', 'this is good', 'approved', '2026-06-22 17:17:54'),
('3', '3', '1', '5', 'i love them', 'approved', '2026-06-22 17:28:53');

-- Table: sellers
DROP TABLE IF EXISTS `sellers`;
CREATE TABLE `sellers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `shop_name` varchar(150) NOT NULL,
  `shop_logo` varchar(255) DEFAULT NULL,
  `phone` varchar(60) NOT NULL,
  `business_id` varchar(100) NOT NULL,
  `id_image` varchar(255) DEFAULT NULL,
  `business_license` varchar(255) DEFAULT NULL,
  `tax_compliance` varchar(255) DEFAULT NULL,
  `bank_statement` varchar(255) DEFAULT NULL,
  `other_document` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `rejection_reason` text DEFAULT NULL,
  `rejected_document` varchar(255) DEFAULT NULL,
  `subscription_status` enum('active','expired','none') NOT NULL DEFAULT 'none',
  `subscription_expires` date DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `shop_name` (`shop_name`),
  CONSTRAINT `sellers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sellers` VALUES
('1', '2', 'ZAMART Seller', 'img_6a391c75a2fbd0.38941661.jpg', '0722000000', 'ID123456', NULL, NULL, NULL, NULL, NULL, 'Trusted seller with quality products.', 'Nairobi', 'verified', '1', NULL, NULL, 'active', NULL, '2026-06-15 14:34:08'),
('4', '3', 'AquaVibe', 'img_6a32b5ef50ba67.55978965.jpeg', '0999103233', 'OP1193645', 'seller_doc_1782123277_1d686d52678c8ffc.jpeg', 'seller_doc_1782122746_59acc882c272c2f3.pdf', 'seller_doc_1782122958_ee6a5d4a6980dc9c.pdf', NULL, 'seller_doc_1782123073_f0128d12be88d4f0.pdf', 'Welcome to AquaVibe!\r\n\r\nAt AquaVibe, we bring you the latest and most reliable electronics at competitive prices. From high-performance smartphones to essential accessories, we carefully curate every product to ensure quality and value.\r\n\r\nWith a commitment to customer satisfaction, we offer:\r\n\r\n✅ 100% Authentic Products – No fakes, no compromises.\r\n✅ Competitive Pricing – Best value for your money.\r\n✅ Fast Delivery – Get your orders delivered quickly and securely.\r\n✅ Excellent Customer Support – We&#039;re here to help, always.\r\n\r\nTrust AquaVibe for all your tech needs. Happy shopping!\r\n\r\nFull Description (for shop profile page)\r\nWelcome to AquaVibe – Your Premier Electronics Destination\r\n\r\nFounded with a passion for technology and a commitment to quality, AquaVibe has quickly become a trusted name in the Kenyan electronics market. We believe that everyone deserves access to high-quality technology without breaking the bank.\r\n\r\nWhat We Offer:\r\n\r\nSmartphones &amp; Tablets – The latest models from top brands.\r\n\r\nLaptops &amp; Accessories – Power and performance for work and play.\r\n\r\nAudio &amp; Wearables – Premium sound and smart technology.\r\n\r\nGadgets &amp; Essentials – Everything you need to stay connected.\r\n\r\nWhy Choose AquaVibe?\r\n\r\n🏆 Quality Guaranteed – Every product undergoes strict quality checks.\r\n💰 Best Prices – Competitive pricing without compromising quality.\r\n🚀 Fast &amp; Reliable Delivery – Same-day dispatch for orders placed before 2 PM.\r\n📞 24/7 Support – Our team is always ready to assist you.\r\n🔒 Secure Payments – Shop with confidence using secure payment methods.\r\n\r\nOur Vision:\r\n\r\nTo bridge the gap between technology and accessibility, ensuring that every Kenyan can enjoy the benefits of modern gadgets and electronics.\r\n\r\nOur Promise:\r\n\r\nAt AquaVibe, we don&#039;t just sell products – we build relationships. Every purchase comes with our promise of quality, reliability, and exceptional service.\r\n\r\nJoin Our Community!\r\n\r\nFollow us on social media for the latest deals, product launches, and tech tips. Stay connected, stay ahead.\r\n\r\n📍 Located in Nyayo Highrise Estate, Nairobi\r\n📞 Call us: 0768062600\r\n📧 Email: josbosimwenda@gmail.com\r\n\r\n&quot;Your Tech, Our Passion – AquaVibe&quot;', 'Nyayo Highrise Estate, Nyayo Highrise ward, Lang&amp;amp;#039;ata, Nairobi, Nairobi County, 00202, Kenya', 'verified', '1', 'Kind Upload the correct document for better review and approve of you request', 'bank_statement', 'active', NULL, '2026-06-15 21:35:20');

-- Table: subscriptions
DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `plan_name` varchar(120) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'KSH',
  `status` enum('active','expired','cancelled','pending') NOT NULL DEFAULT 'pending',
  `starts_at` date DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`seller_id`) REFERENCES `sellers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subscriptions` VALUES
('1', '4', 'Pending Selection', '0.00', 'KSH', 'active', '2026-06-15', '2026-07-15', '2026-06-15 21:42:56'),
('2', '1', 'Pending Selection', '0.00', 'KSH', 'pending', NULL, NULL, '2026-06-15 21:56:32');

-- Table: support_tickets
DROP TABLE IF EXISTS `support_tickets`;
CREATE TABLE `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT 0,
  `subject` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `status` enum('open','in-progress','resolved','closed') DEFAULT 'open',
  `admin_reply` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `support_tickets` VALUES
('1', '3', 'Une reunion', 'account', 'gggggftytydtr', 'resolved', 'so sorry for that we have solved that problem', '2026-06-17 18:43:52', '2026-06-22 11:19:57'),
('3', '4', 'Complain', 'account', 'jjjjjjjjjjjjjjjjjjjjjewddddddddddddddddddyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyddddddddddddddddddddd', 'open', NULL, '2026-06-22 16:36:23', NULL),
('4', '0', 'sasa', 'order', 'sasas', 'open', NULL, '2026-06-23 12:27:22', NULL);

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `remember_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `email_2` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` VALUES
('1', 'Marketplace Admin', 'admin@marketplace.local', '$2y$10$JMLnTUbl16xdxme1wthDJ.KbxftRLbliV876fAY43yUAqYdRWwIAa', 'admin', NULL, NULL, '1', NULL, NULL, NULL, '2026-06-15 14:34:08', NULL, NULL),
('2', 'Demo Customer', 'customer@marketplace.local', '$2y$10$LcZ1bYkQHISECEL7tCLVe.fYhfrxsz37cd0UBGAqr5tzZ1m8R/I36', 'seller', '0722000000', NULL, '1', NULL, NULL, NULL, '2026-06-15 14:34:08', NULL, NULL),
('3', 'Jonathan Bosimwenda', 'josbosimwenda@gmail.com', '$2y$10$hkc42iuvR9MHMerfQ7trf.CKmUTfPFrn0suLhDy99oqS2lq1Fgo3q', 'seller', '0999103233', 'highrise', '1', 'f5fa2834a94fd9674ba9de5123c33ecd', NULL, NULL, '2026-06-15 15:30:09', NULL, NULL),
('4', 'Bilema Jon', 'josbosimwendaadmin@gmail.com', '$2y$10$gz1TiBFkC2.I/5a9iC8z9ed3QjBPbVVVksAqdKLDg2ie.YYvPGIOu', 'admin', '0851600109', 'highrise', '1', 'cc0bfe1a0074ec3b02e3f61528270768', NULL, NULL, '2026-06-15 18:51:43', NULL, NULL),
('5', 'Jonathan Bosimwenda', 'josbosimwendacustomer@gmail.com', '$2y$10$tgWhDfKkeLg6ygfNyruAaezxXJfNXoEFVNTvVoq8wsVm0SENcD7SC', 'customer', '0851600109', 'highrise', '1', 'e53f53a3724d900e1e857a3e979f1584', NULL, NULL, '2026-06-16 14:37:00', NULL, NULL),
('6', 'Jenna Mckee', 'zenuse@mailinator.com', '$2y$10$hJL7zaFanyLB7EJdB7Z2z.V92mQemFKXn7HSvg/zwCxqvLJViAOxG', 'customer', '+1 (649) 326-9009', 'Sint voluptate eos', '0', 'e69e65dc786efc5cf8f6978fd9857549', NULL, NULL, '2026-07-06 10:51:24', NULL, NULL),
('7', 'Iona Dyer', 'gukihid@mailinator.com', '$2y$10$BzyENuKJwaQ89X8RP7idwOn2SaS/EenPfU0XoUQ6cn4kvsUTmyRyy', 'customer', '+1 (981) 596-1968', 'Enim consectetur vol', '0', '5b32355b02f7dbede61da77ef47ec9aa', NULL, NULL, '2026-07-06 10:51:50', NULL, NULL);

-- Table: wishlists
DROP TABLE IF EXISTS `wishlists`;
CREATE TABLE `wishlists` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wishlists_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `wishlists` VALUES
('6', '3', '1', '2026-06-15 17:42:59'),
('10', '5', '7', '2026-06-23 12:29:32'),
('11', '5', '5', '2026-06-23 12:29:36'),
('12', '5', '4', '2026-06-23 12:29:39'),
('13', '5', '6', '2026-06-23 12:29:48'),
('14', '5', '1', '2026-06-23 12:30:01'),
('15', '2', '4', '2026-07-01 12:29:55');

SET FOREIGN_KEY_CHECKS=1;
