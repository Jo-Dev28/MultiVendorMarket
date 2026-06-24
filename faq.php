<?php
$page_title = 'Frequently Asked Questions';
require_once 'includes/header.php';

// Get FAQ categories from database
$categories_sql = "SELECT * FROM faq_categories ORDER BY sort_order, name";
$categories_result = $mysqli->query($categories_sql);

// Check if faq_categories table exists, if not create it
$table_check = $mysqli->query("SHOW TABLES LIKE 'faq_categories'");
if ($table_check && $table_check->num_rows == 0) {
    // Create FAQ categories table
    $create_categories = "CREATE TABLE IF NOT EXISTS `faq_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `slug` varchar(120) NOT NULL,
        `description` text DEFAULT NULL,
        `icon` varchar(50) DEFAULT 'fa-circle-question',
        `sort_order` int(11) DEFAULT 0,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $mysqli->query($create_categories);
    
    // Create FAQ items table
    $create_items = "CREATE TABLE IF NOT EXISTS `faq_items` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $mysqli->query($create_items);
    
    // Insert sample categories
    $sample_categories = "INSERT INTO `faq_categories` (`name`, `slug`, `icon`, `sort_order`, `created_at`) VALUES
        ('General Questions', 'general', 'fa-circle-question', 1, NOW()),
        ('Account & Profile', 'account', 'fa-user', 2, NOW()),
        ('Orders & Payments', 'orders', 'fa-credit-card', 3, NOW()),
        ('Products & Shopping', 'products', 'fa-box', 4, NOW()),
        ('Selling on Our Platform', 'selling', 'fa-store', 5, NOW()),
        ('Shipping & Delivery', 'shipping', 'fa-truck', 6, NOW()),
        ('Returns & Refunds', 'returns', 'fa-rotate-left', 7, NOW()),
        ('Technical Issues', 'technical', 'fa-gear', 8, NOW())";
    $mysqli->query($sample_categories);
    
    // Insert sample FAQ items
    $sample_faqs = "INSERT INTO `faq_items` (`category_id`, `question`, `answer`, `sort_order`, `created_at`) VALUES
        (1, 'What is this marketplace?', 'Our marketplace is a multi-vendor platform where customers can buy products from various trusted sellers all in one place. We connect buyers with quality products and reliable sellers.', 1, NOW()),
        (1, 'Is it safe to shop here?', 'Yes! We prioritize security with secure payment processing, buyer protection, and verified sellers. All transactions are encrypted and monitored for safety.', 2, NOW()),
        (2, 'How do I create an account?', 'Click on the \"Register\" button at the top right corner of the page. Fill in your name, email, and password, then click \"Create Account\". It\'s free and takes less than 2 minutes!', 1, NOW()),
        (2, 'I forgot my password. How do I reset it?', 'Click on \"Forgot Password\" on the login page. Enter your email address and we\'ll send you a password reset link. Follow the instructions in the email.', 2, NOW()),
        (3, 'What payment methods do you accept?', 'We accept M-Pesa, Credit/Debit Cards (Visa, Mastercard), Bank Transfer, and PayPal. All payments are secure and encrypted.', 1, NOW()),
        (3, 'How do I track my order?', 'Go to \"My Orders\" in your dashboard, find your order, and click \"Track Order\" to see real-time status updates on your delivery.', 2, NOW()),
        (4, 'How do I find products?', 'Use the search bar at the top of the page to search by product name, category, or brand. You can also browse products by category from the main menu.', 1, NOW()),
        (4, 'How can I contact a seller?', 'Go to the product page and click the \"Contact Seller\" button. You can also visit the seller\'s shop page and use the chat feature to send a message.', 2, NOW()),
        (5, 'How do I become a seller?', 'Register as a customer, go to your dashboard, and click \"Become a Seller\". Fill in your business details, upload required documents, and wait for approval.', 1, NOW()),
        (5, 'How much does it cost to sell?', 'We offer flexible subscription plans starting from KSH 999 per month. You can choose the plan that best fits your business needs.', 2, NOW()),
        (6, 'How long does shipping take?', 'Shipping typically takes 2-5 business days within Kenya. Free shipping is available on orders over KSH 5,000.', 1, NOW()),
        (6, 'Do you ship outside Kenya?', 'Currently, we only ship within Kenya. We are working on expanding our delivery services to other countries in the future.', 2, NOW()),
        (7, 'What is your return policy?', 'We accept returns within 7 days of delivery. Items must be unused with original packaging. Contact the seller first to initiate a return.', 1, NOW()),
        (7, 'How do I get a refund?', 'After your return is approved, the seller will process your refund. Depending on the payment method, it may take 3-5 business days for the funds to reflect.', 2, NOW()),
        (8, 'The website is not loading properly. What should I do?', 'Clear your browser cache, try a different browser, or check your internet connection. If the problem persists, contact our support team.', 1, NOW())";
    $mysqli->query($sample_faqs);
}

// Get categories with FAQ items
$faq_categories = [];
$categories_sql = "SELECT * FROM faq_categories ORDER BY sort_order, name";
$categories_result = $mysqli->query($categories_sql);

if ($categories_result) {
    while ($cat = $categories_result->fetch_assoc()) {
        $items_sql = "SELECT * FROM faq_items WHERE category_id = ? AND is_active = 1 ORDER BY sort_order, question";
        $items_stmt = $mysqli->prepare($items_sql);
        $items_stmt->bind_param('i', $cat['id']);
        $items_stmt->execute();
        $items = $items_stmt->get_result();
        $cat['items'] = [];
        while ($item = $items->fetch_assoc()) {
            $cat['items'][] = $item;
        }
        $items_stmt->close();
        if (!empty($cat['items'])) {
            $faq_categories[] = $cat;
        }
    }
}

// Handle search
$search_results = [];
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
if (!empty($search_query)) {
    $search_sql = "SELECT fi.*, fc.name as category_name 
                   FROM faq_items fi
                   JOIN faq_categories fc ON fc.id = fi.category_id
                   WHERE fi.is_active = 1 AND (fi.question LIKE ? OR fi.answer LIKE ?)
                   ORDER BY fi.sort_order";
    $search_stmt = $mysqli->prepare($search_sql);
    $search_param = "%$search_query%";
    $search_stmt->bind_param('ss', $search_param, $search_param);
    $search_stmt->execute();
    $search_results = $search_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $search_stmt->close();
}
?>

<style>
    /* ============================================
       FAQ PAGE - MODERN DESIGN
    ============================================ */
    
    .faq-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .faq-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .faq-hero h1 {
        color: #fff;
        font-size: 2.8rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }
    
    .faq-hero h1 i {
        color: #f59e0b;
    }
    
    .faq-hero p {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
    }
    
    .faq-search-box {
        max-width: 600px;
        margin: 20px auto 0;
        position: relative;
        z-index: 1;
    }
    
    .faq-search-box .input-group {
        background: white;
        border-radius: 50px;
        overflow: hidden;
        padding: 4px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .faq-search-box input {
        flex: 1;
        border: none;
        padding: 14px 20px;
        font-size: 1rem;
        outline: none;
        background: transparent;
    }
    
    .faq-search-box button {
        padding: 14px 30px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }
    
    .faq-search-box button:hover {
        background: linear-gradient(135deg, #1d4ed8, #1e40af);
        transform: scale(1.02);
    }
    
    .faq-wrapper {
        display: flex;
        gap: 30px;
    }
    
    .faq-sidebar {
        width: 280px;
        flex-shrink: 0;
        position: sticky;
        top: 100px;
        align-self: flex-start;
    }
    
    .faq-content {
        flex: 1;
    }
    
    /* Category Cards */
    .faq-category-card {
        background: white;
        border-radius: 16px;
        padding: 20px 25px;
        border: 1px solid #e5e7eb;
        margin-bottom: 20px;
        transition: all 0.3s ease;
    }
    
    .faq-category-card:hover {
        border-color: #2563eb;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }
    
    .faq-category-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f59e0b;
    }
    
    .faq-category-title i {
        color: #2563eb;
        font-size: 1.2rem;
    }
    
    .faq-item {
        padding: 12px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .faq-item:last-child {
        border-bottom: none;
    }
    
    .faq-question {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        font-weight: 500;
        color: #1f2937;
        transition: all 0.3s ease;
        padding: 4px 0;
    }
    
    .faq-question:hover {
        color: #2563eb;
    }
    
    .faq-question .question-text {
        flex: 1;
        font-size: 0.95rem;
    }
    
    .faq-question .toggle-icon {
        color: #6b7280;
        transition: transform 0.3s ease;
        font-size: 0.9rem;
        margin-left: 10px;
        flex-shrink: 0;
    }
    
    .faq-question.active .toggle-icon {
        transform: rotate(180deg);
    }
    
    .faq-answer {
        display: none;
        padding-top: 10px;
        color: #4b5563;
        font-size: 0.9rem;
        line-height: 1.7;
    }
    
    .faq-answer.show {
        display: block;
    }
    
    .faq-answer ul, .faq-answer ol {
        padding-left: 20px;
        margin: 8px 0;
    }
    
    .faq-answer li {
        margin-bottom: 4px;
    }
    
    /* Sidebar Navigation */
    .faq-sidebar-nav {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
    
    .faq-sidebar-nav .nav-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .faq-sidebar-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        color: #4b5563;
        text-decoration: none;
        border-radius: 8px;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }
    
    .faq-sidebar-nav a:hover {
        background: #f3f4f6;
        color: #2563eb;
    }
    
    .faq-sidebar-nav a i {
        width: 20px;
        color: #6b7280;
    }
    
    .faq-sidebar-nav a:hover i {
        color: #2563eb;
    }
    
    /* Search Results */
    .search-results {
        margin-bottom: 30px;
    }
    
    .search-result-item {
        background: white;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }
    
    .search-result-item:hover {
        border-color: #2563eb;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    
    .search-result-item .result-question {
        font-weight: 600;
        color: #1f2937;
        font-size: 1rem;
        margin-bottom: 6px;
    }
    
    .search-result-item .result-answer {
        color: #6b7280;
        font-size: 0.9rem;
        line-height: 1.6;
    }
    
    .search-result-item .result-category {
        display: inline-block;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 0.7rem;
        padding: 2px 12px;
        border-radius: 50px;
        margin-top: 8px;
    }
    
    .no-results {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
    }
    
    .no-results i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 15px;
    }
    
    .no-results h4 {
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .no-results p {
        color: #6b7280;
    }
    
    /* Still Need Help */
    .help-card {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 16px;
        padding: 30px;
        text-align: center;
        margin-top: 30px;
        border: 1px solid #f59e0b;
    }
    
    .help-card h3 {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .help-card p {
        color: #6b7280;
        margin-bottom: 15px;
    }
    
    .help-card .btn-help {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 10px 30px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .help-card .btn-help:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        color: white;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .faq-wrapper {
            flex-direction: column;
        }
        .faq-sidebar {
            width: 100%;
            position: static;
        }
        .faq-hero h1 {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 768px) {
        .faq-hero {
            padding: 40px 0;
        }
        .faq-hero h1 {
            font-size: 1.8rem;
        }
        .faq-search-box input {
            font-size: 0.9rem;
            padding: 10px 15px;
        }
        .faq-search-box button {
            font-size: 0.85rem;
            padding: 10px 20px;
        }
        .faq-category-card {
            padding: 15px 18px;
        }
        .faq-question .question-text {
            font-size: 0.85rem;
        }
        .faq-answer {
            font-size: 0.85rem;
        }
        .faq-sidebar-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 12px;
        }
        .faq-sidebar-nav .nav-title {
            width: 100%;
            margin-bottom: 8px;
        }
        .faq-sidebar-nav a {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .help-card {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .faq-search-box .input-group {
            border-radius: 30px;
        }
        .faq-search-box input {
            font-size: 0.85rem;
            padding: 8px 12px;
        }
        .faq-search-box button {
            font-size: 0.8rem;
            padding: 8px 16px;
        }
    }
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="faq-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-regular fa-circle-question"></i> Frequently Asked Questions
        </div>
        <h1><i class="fa-solid fa-circle-question"></i> FAQ</h1>
        <p>Find answers to the most common questions about our marketplace.</p>
        
        <!-- Search Bar -->
        <div class="faq-search-box">
            <form method="GET" action="">
                <div class="input-group">
                    <input type="text" name="search" placeholder="Search for answers..." value="<?= htmlspecialchars($search_query) ?>">
                    <button type="submit"><i class="fa-solid fa-search"></i> Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    <div class="faq-wrapper">
        
        <!-- Sidebar -->
        <div class="faq-sidebar">
            <div class="faq-sidebar-nav">
                <div class="nav-title"><i class="fa-regular fa-list"></i> Categories</div>
                <?php foreach ($faq_categories as $cat): ?>
                    <a href="#category-<?= $cat['id'] ?>">
                        <i class="fa-solid <?= $cat['icon'] ?? 'fa-circle-question' ?>"></i>
                        <?= htmlspecialchars($cat['name']) ?>
                        <span style="margin-left:auto; font-size:0.7rem; color:#9ca3af;"><?= count($cat['items']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Content -->
        <div class="faq-content">
            
            <?php if (!empty($search_query)): ?>
                <!-- Search Results -->
                <div class="search-results">
                    <h4 style="margin-bottom:20px; color:#1f2937;">
                        <i class="fa-solid fa-search"></i> Search Results for "<strong><?= htmlspecialchars($search_query) ?></strong>"
                        <?php if (!empty($search_results)): ?>
                            <span style="font-size:0.85rem; color:#6b7280; font-weight:400;">(<?= count($search_results) ?> results)</span>
                        <?php endif; ?>
                    </h4>
                    
                    <?php if (!empty($search_results)): ?>
                        <?php foreach ($search_results as $result): ?>
                            <div class="search-result-item">
                                <div class="result-question"><?= htmlspecialchars($result['question']) ?></div>
                                <div class="result-answer"><?= nl2br(htmlspecialchars(substr($result['answer'], 0, 300))) ?><?= strlen($result['answer']) > 300 ? '...' : '' ?></div>
                                <span class="result-category"><i class="fa-regular fa-folder"></i> <?= htmlspecialchars($result['category_name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <i class="fa-regular fa-circle-question"></i>
                            <h4>No results found</h4>
                            <p>We couldn't find any answers for "<strong><?= htmlspecialchars($search_query) ?></strong>". Try searching with different keywords.</p>
                            <a href="faq.php" class="btn btn-outline-primary mt-3">
                                <i class="fa-solid fa-arrow-left"></i> Back to FAQs
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- FAQ Categories -->
            <?php foreach ($faq_categories as $cat): ?>
                <div class="faq-category-card" id="category-<?= $cat['id'] ?>">
                    <div class="faq-category-title">
                        <i class="fa-solid <?= $cat['icon'] ?? 'fa-circle-question' ?>"></i>
                        <?= htmlspecialchars($cat['name']) ?>
                        <span style="font-size:0.7rem; color:#9ca3af; font-weight:400; margin-left:auto;">
                            <?= count($cat['items']) ?> questions
                        </span>
                    </div>
                    
                    <?php foreach ($cat['items'] as $index => $item): ?>
                        <div class="faq-item">
                            <div class="faq-question" onclick="toggleFaq(this)">
                                <span class="question-text"><?= htmlspecialchars($item['question']) ?></span>
                                <span class="toggle-icon"><i class="fa-solid fa-chevron-down"></i></span>
                            </div>
                            <div class="faq-answer <?= $index === 0 ? 'show' : '' ?>">
                                <?= nl2br(htmlspecialchars($item['answer'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Still Need Help -->
            <div class="help-card">
                <h3><i class="fa-regular fa-circle-question"></i> Still Need Help?</h3>
                <p>Can't find what you're looking for? Our support team is ready to assist you.</p>
                <a href="support.php" class="btn-help">
                    <i class="fa-regular fa-headset"></i> Contact Support
                </a>
                <a href="contact.php" class="btn-help" style="background: #6b7280; margin-left:10px;">
                    <i class="fa-regular fa-envelope"></i> Send a Message
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle FAQ answer
function toggleFaq(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('.toggle-icon i');
    
    // Close all other FAQs in the same category
    const parentCard = element.closest('.faq-category-card');
    if (parentCard) {
        parentCard.querySelectorAll('.faq-answer').forEach(function(el) {
            if (el !== answer) {
                el.classList.remove('show');
                el.previousElementSibling.querySelector('.toggle-icon i').className = 'fa-solid fa-chevron-down';
                el.previousElementSibling.classList.remove('active');
            }
        });
    }
    
    // Toggle current FAQ
    answer.classList.toggle('show');
    element.classList.toggle('active');
    
    if (answer.classList.contains('show')) {
        icon.className = 'fa-solid fa-chevron-up';
    } else {
        icon.className = 'fa-solid fa-chevron-down';
    }
}

// Auto-expand first FAQ in each category on load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.faq-category-card').forEach(function(card) {
        const firstFaq = card.querySelector('.faq-question');
        if (firstFaq) {
            // Don't auto-expand if it's already expanded
            const answer = firstFaq.nextElementSibling;
            if (!answer.classList.contains('show')) {
                // Only expand first one if not in search mode
                <?php if (empty($search_query)): ?>
                // We'll expand the first one of the first category only
                if (card === document.querySelector('.faq-category-card')) {
                    answer.classList.add('show');
                    firstFaq.classList.add('active');
                    firstFaq.querySelector('.toggle-icon i').className = 'fa-solid fa-chevron-up';
                }
                <?php endif; ?>
            }
        }
    });
    
    // Smooth scroll for category links
    document.querySelectorAll('.faq-sidebar-nav a').forEach(function(link) {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>