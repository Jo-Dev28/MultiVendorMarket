<?php
// Start output buffering - MUST be first
ob_start();

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config if not already included
if (!defined('SITE_NAME')) {
    require_once dirname(__DIR__) . '/includes/config.php';
}

// Include functions if not already included
if (!function_exists('sanitize')) {
    require_once dirname(__DIR__) . '/includes/functions.php';
}

// ============================================
// ALWAYS GET FRESH USER DATA ON EACH PAGE LOAD
// ============================================
$user = current_user();
$is_logged_in = ($user && isset($user['id']) && $user['id']);

// Get cart count - only if logged in
$cart_count = 0;
if ($is_logged_in) {
    $cart_count = get_cart_count($mysqli, $user['id']);
} else {
    // For guests, get from session
    $guest_cart = $_SESSION['cart'] ?? [];
    $cart_count = array_sum($guest_cart);
}

// Get categories from database
$categories_sql = "SELECT id, name FROM categories ORDER BY name";
$categories_result = $mysqli->query($categories_sql);
$categories_list = [];
if ($categories_result) {
    while ($cat = $categories_result->fetch_assoc()) {
        $categories_list[] = $cat;
    }
}

$flash = flash_display();

// Check user role for admin access
$is_admin = ($user['role'] ?? '') === 'admin';
$is_seller = ($user['role'] ?? '') === 'seller';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= SITE_NAME ?> - Multi-Vendor Marketplace with AI Shopping Assistant">
    <meta name="keywords" content="marketplace, ecommerce, shopping, kenya, vendors">
    <meta name="author" content="<?= SITE_NAME ?>">
    <title><?= SITE_NAME ?> | <?php echo isset($page_title) ? sanitize($page_title) : 'Marketplace'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #f59e0b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #1f2937;
            --gray: #6b7280;
            --light-gray: #f3f4f6;
            --white: #ffffff;
            --border: #e5e7eb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }
        
        .announcement-bar {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 8px 0;
            font-size: 0.85rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .announcement-close {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
        }
        
        .modern-header {
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 50%, #2563eb 100%);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .top-bar {
            background: rgba(0, 0, 0, 0.2);
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.75rem;
        }
        
        .top-bar a {
            color: rgba(255, 255, 255, 0.75);
            text-decoration: none;
            margin-right: 1.2rem;
        }
        
        .top-bar a:hover {
            color: white;
        }
        
        .navbar-main {
            padding: 0.75rem 0;
        }
        
        .brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        
        .brand-icon {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .brand-icon i {
            font-size: 1.4rem;
            color: white;
        }
        
        .brand-text h1 {
            font-size: 1.3rem;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, white, #bfdbfe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .brand-text span {
            font-size: 0.65rem;
            color: rgba(255, 255, 255, 0.7);
            display: block;
        }
        
        .search-wrapper {
            width: 100%;
            max-width: 450px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            background: white;
            border-radius: 50px;
            overflow: hidden;
        }
        
        .search-category {
            border: none;
            padding: 10px 12px;
            background: #f3f4f6;
            font-size: 0.8rem;
            border-right: 1px solid #e5e7eb;
            outline: none;
        }
        
        .search-input {
            flex: 1;
            border: none;
            padding: 10px 15px;
            font-size: 0.85rem;
            outline: none;
        }
        
        .search-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            padding: 0 20px;
            color: white;
            cursor: pointer;
        }
        
        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .action-btn {
            position: relative;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .badge-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #f59e0b;
            color: white;
            font-size: 0.65rem;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 50%;
            min-width: 18px;
        }
        
        .custom-dropdown {
            position: relative;
            display: inline-block;
        }
        
        .custom-dropdown-btn {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50px;
            padding: 6px 15px;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-size: 0.8rem;
        }
        
        .custom-dropdown-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .user-avatar {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .custom-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            min-width: 220px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            margin-top: 12px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .custom-dropdown:hover .custom-dropdown-menu {
            opacity: 1;
            visibility: visible;
        }
        
        .custom-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            color: #374151;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .logout {
            color: red;
        }
        
        .custom-dropdown-menu a:hover {
            background: #eff6ff;
        }
        
        .custom-dropdown-menu hr {
            margin: 5px 0;
            border-color: #e5e7eb;
        }
        
        /* Admin Badge */
        .admin-badge {
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
        }
        
        .seller-badge {
            background: #f59e0b;
            color: white;
            font-size: 0.65rem;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: 8px;
        }
        
        .nav-menu {
            background: rgba(0, 0, 0, 0.15);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .nav-links {
            display: flex;
            gap: 0;
            margin: 0;
            padding: 0;
            list-style: none;
            flex-wrap: wrap;
        }
        
        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .nav-links li a:hover {
            color: white;
        }
        
        .mobile-menu-btn {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            color: white;
            cursor: pointer;
        }
        
        .mobile-sidebar {
            position: fixed;
            top: 0;
            left: -100%;
            width: 85%;
            max-width: 320px;
            height: 100%;
            background: white;
            z-index: 2000;
            transition: left 0.3s ease;
            overflow-y: auto;
        }
        
        .mobile-sidebar.active {
            left: 0;
        }
        
        .sidebar-header {
            background: linear-gradient(135deg, #1e40af, #1d4ed8);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .sidebar-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
        }
        
        .sidebar-menu {
            padding: 15px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            color: #374151;
            text-decoration: none;
            border-radius: 10px;
            font-size: 0.85rem;
        }
        
        .sidebar-menu a:hover {
            background: #eff6ff;
            color: #2563eb;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1999;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        .floating-cart {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .floating-cart i {
            font-size: 1.3rem;
            color: white;
        }
        
        .floating-cart .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #2563eb;
            color: white;
            font-size: 0.65rem;
            padding: 2px 6px;
            border-radius: 50%;
        }
        
        .alert-custom {
            border-radius: 12px;
            border: none;
        }
        
        @media (max-width: 992px) {
            .search-wrapper { max-width: 280px; }
            .user-name { display: none; }
            .nav-links { display: none; }
            .mobile-menu-btn { display: flex; align-items: center; justify-content: center; }
        }
        
        @media (max-width: 768px) {
            .search-wrapper { max-width: 100%; margin: 12px 0; }
            .top-bar { display: none; }
            .navbar-main .row { flex-direction: column; }
            .brand-logo { margin-bottom: 10px; justify-content: center; }
            .header-actions { justify-content: center; margin-top: 10px; }
            .brand-text h1 { font-size: 1.1rem; }
            .brand-icon { width: 36px; height: 36px; }
            .brand-icon i { font-size: 1.2rem; }
        }
    </style>
</head>
<body>

<!-- Announcement Bar -->
<div class="announcement-bar" id="announcementBar">
    <div class="container">
        <i class="fa-solid fa-gift"></i>
        🎉 Free shipping on orders over KSH 5,000! Use code: FREESHIP
        <span class="announcement-close" onclick="document.getElementById('announcementBar').style.display='none'">
            <i class="fa-solid fa-times"></i>
        </span>
    </div>
</div>

<!-- Modern Header -->
<header class="modern-header">
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <a href="tel:+254700000000"><i class="fa-solid fa-phone"></i> +254 700 000 000</a>
                    <a href="mailto:support@multivendorhub.com"><i class="fa-solid fa-envelope"></i> support@multivendorhub.com</a>
                    <span class="divider">|</span>
                </div>
                <div class="col-md-6 text-end">
                    <a href="<?= BASE_URL ?>become-seller.php"><i class="fa-solid fa-store"></i> Become a Seller</a>
                    <a href="<?= BASE_URL ?>help.php"><i class="fa-solid fa-headset"></i> Help Center</a>
                    <a href="<?= BASE_URL ?>offers.php"><i class="fa-solid fa-percent"></i> Offers</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Navbar -->
    <div class="navbar-main">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-3 col-md-12">
                    <a href="<?= BASE_URL ?>index.php" class="brand-logo">
                        <div class="brand-icon">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div class="brand-text">
                            <h1><?= SITE_NAME ?></h1>
                            <span>Multi-Vendor Marketplace</span>
                        </div>
                    </a>
                </div>
                
                <div class="col-lg-6 col-md-12">
                    <div class="search-wrapper">
                        <form action="<?= BASE_URL ?>shop.php" method="GET" class="search-box">
                            <select name="category" class="search-category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories_list as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?= sanitize($_GET['search'] ?? '') ?>">
                            <button type="submit" class="search-btn">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-12">
                    <div class="header-actions">
                        <button class="action-btn" onclick="window.location.href='<?= BASE_URL ?>wishlist.php'">
                            <i class="fa-regular fa-heart"></i>
                        </button>
                        <button class="action-btn" onclick="window.location.href='<?= BASE_URL ?>cart.php'">
                            <i class="fa-solid fa-bag-shopping"></i>
                            <span class="badge-count" id="cartCount"><?= $cart_count ?></span>
                        </button>
                        
                        <?php if (!$is_logged_in): ?>
                            <button class="action-btn" onclick="window.location.href='<?= BASE_URL ?>login.php'">
                                <i class="fa-regular fa-user"></i>
                            </button>
                        <?php else: ?>
                            <div class="custom-dropdown">
                                <button class="custom-dropdown-btn">
                                    <div class="user-avatar">
                                        <i class="fa-regular fa-user"></i>
                                    </div>
                                    <span class="user-name">
                                        <?= sanitize(explode(' ', $user['name'] ?? $user['email'])[0]) ?>
                                        <?php if ($is_admin): ?>
                                            <span class="admin-badge">Admin</span>
                                        <?php elseif ($is_seller): ?>
                                            <span class="seller-badge">Seller</span>
                                        <?php endif; ?>
                                    </span>
                                    <i class="fa-solid fa-chevron-down" style="font-size: 10px;"></i>
                                </button>
                                <div class="custom-dropdown-menu">
                                    <a href="<?= BASE_URL ?>profile.php"><i class="fa-regular fa-user"></i> My Profile</a>
                                    <a href="<?= BASE_URL ?>orders.php"><i class="fa-solid fa-truck"></i> My Orders</a>
                                    <a href="<?= BASE_URL ?>wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
                                    <hr>
                                    <?php if ($is_seller): ?>
                                        <a href="<?= BASE_URL ?>seller/dashboard.php"><i class="fa-solid fa-chart-line"></i> Seller Dashboard</a>
                                    <?php endif; ?>
                                    <?php if ($is_admin): ?>
                                        <a href="<?= BASE_URL ?>admin/dashboard.php"><i class="fa-solid fa-gear"></i> Admin Panel</a>
                                    <?php endif; ?>
                                    <hr>
                                    <a href="<?= BASE_URL ?>logout.php" onclick="return confirmLogout()"><i class="fa-solid fa-right-from-bracket logout"></i> Logout</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <button class="mobile-menu-btn" id="mobileMenuBtn">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <div class="nav-menu">
        <div class="container">
            <ul class="nav-links">
                <li><a href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-house"></i> Home</a></li>
                <li><a href="<?= BASE_URL ?>shop.php"><i class="fa-solid fa-store"></i> All Products</a></li>
                <li><a href="<?= BASE_URL ?>deals.php"><i class="fa-solid fa-fire-flame-curved"></i> Hot Deals</a></li>
                <li><a href="<?= BASE_URL ?>flash-sales.php"><i class="fa-solid fa-clock"></i> Flash Sales</a></li>
                <li><a href="<?= BASE_URL ?>compare.php"><i class="fa-solid fa-chart-line"></i> Compare</a></li>
                <li><a href="<?= BASE_URL ?>ai_assistant.php"><i class="fa-solid fa-robot"></i> AI Assistant</a></li>
                <li><a href="<?= BASE_URL ?>sellers.php"><i class="fa-solid fa-shop"></i> Top Sellers</a></li>
                <li><a href="<?= BASE_URL ?>blog.php"><i class="fa-solid fa-newspaper"></i> Blog</a></li>
                <li><a href="<?= BASE_URL ?>about.php"><i class="fa-solid fa-info-circle"></i> About</a></li>
                <li><a href="<?= BASE_URL ?>faq.php"><i class="fa-solid fa-question-circle"></i> FAQ</a></li>
                <li><a href="<?= BASE_URL ?>contact.php"><i class="fa-solid fa-headset"></i> Contact</a></li>
                <li><a href="<?= BASE_URL ?>support.php"><i class="fa-solid fa-headset"></i> Support</a></li>
            </ul>
        </div>
    </div>
</header>

<!-- Mobile Sidebar -->
<div class="overlay" id="overlay"></div>
<div class="mobile-sidebar" id="mobileSidebar">
    <div class="sidebar-header">
        <button class="sidebar-close" id="closeSidebar">
            <i class="fa-solid fa-times"></i>
        </button>
        <div class="brand-logo justify-content-center">
            <div class="brand-icon">
                <i class="fa-solid fa-store"></i>
            </div>
            <div class="brand-text">
                <h1><?= SITE_NAME ?></h1>
                <span>Multi-Vendor Marketplace</span>
            </div>
        </div>
    </div>
    <div class="sidebar-menu">
        <a href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-house"></i> Home</a>
        <a href="<?= BASE_URL ?>shop.php"><i class="fa-solid fa-store"></i> All Products</a>
        <a href="<?= BASE_URL ?>deals.php"><i class="fa-solid fa-fire-flame-curved"></i> Hot Deals</a>
        <a href="<?= BASE_URL ?>flash-sales.php"><i class="fa-solid fa-clock"></i> Flash Sales</a>
        <a href="<?= BASE_URL ?>compare.php"><i class="fa-solid fa-chart-line"></i> Compare</a>
        <a href="<?= BASE_URL ?>ai_assistant.php"><i class="fa-solid fa-robot"></i> AI Assistant</a>
        <a href="<?= BASE_URL ?>sellers.php"><i class="fa-solid fa-shop"></i> Top Sellers</a>
        <a href="<?= BASE_URL ?>cart.php"><i class="fa-solid fa-cart-shopping"></i> Cart</a>
        <a href="<?= BASE_URL ?>wishlist.php"><i class="fa-regular fa-heart"></i> Wishlist</a>
        <hr>
        <?php if (!$is_logged_in): ?>
            <a href="<?= BASE_URL ?>login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
            <a href="<?= BASE_URL ?>register.php"><i class="fa-solid fa-user-plus"></i> Register</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>profile.php"><i class="fa-regular fa-user"></i> My Profile</a>
            <a href="<?= BASE_URL ?>orders.php"><i class="fa-solid fa-truck"></i> My Orders</a>
            <?php if ($is_seller): ?>
                <a href="<?= BASE_URL ?>seller/dashboard.php"><i class="fa-solid fa-chart-line"></i> Seller Dashboard</a>
            <?php endif; ?>
            <?php if ($is_admin): ?>
                <a href="<?= BASE_URL ?>admin/dashboard.php"><i class="fa-solid fa-gear"></i> Admin Panel</a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>logout.php" onclick="return confirmLogout()"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php endif; ?>
    </div>
</div>

<!-- Floating Cart Button -->
<div class="floating-cart" onclick="window.location.href='<?= BASE_URL ?>cart.php'">
    <i class="fa-solid fa-cart-shopping"></i>
    <span class="badge" id="floatCartCount"><?= $cart_count ?></span>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Confirm logout function
function confirmLogout() {
    Swal.fire({
        title: 'Logout?',
        text: 'Are you sure you want to logout?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= BASE_URL ?>logout.php';
        }
    });
    return false;
}

// Mobile Sidebar Toggle
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileSidebar = document.getElementById('mobileSidebar');
const overlay = document.getElementById('overlay');
const closeSidebar = document.getElementById('closeSidebar');

function openSidebar() {
    if (mobileSidebar) mobileSidebar.classList.add('active');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebarFunc() {
    if (mobileSidebar) mobileSidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', openSidebar);
}
if (closeSidebar) {
    closeSidebar.addEventListener('click', closeSidebarFunc);
}
if (overlay) {
    overlay.addEventListener('click', closeSidebarFunc);
}

// Sticky Header on Scroll
let lastScroll = 0;
window.addEventListener('scroll', function() {
    const header = document.querySelector('.modern-header');
    if (!header) return;
    
    const currentScroll = window.pageYOffset;
    
    if (currentScroll > 100) {
        header.style.boxShadow = '0 10px 30px rgba(0,0,0,0.2)';
    } else {
        header.style.boxShadow = '0 4px 20px rgba(0,0,0,0.15)';
    }
    
    if (currentScroll > lastScroll && currentScroll > 300) {
        header.style.transform = 'translateY(-100%)';
    } else {
        header.style.transform = 'translateY(0)';
    }
    lastScroll = currentScroll;
});

// Update cart count function
function updateCartCount() {
    fetch('<?= BASE_URL ?>api/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count !== undefined) {
                document.querySelectorAll('.badge-count, #floatCartCount').forEach(el => {
                    if (el) el.textContent = data.count;
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

$(document).ready(function() {
    updateCartCount();
});
</script>

<main class="container my-4">
    <?php if ($flash && isset($flash['message'])): ?>
    <div class="alert alert-<?= $flash['type'] ?? 'info' ?> alert-dismissible fade show alert-custom" role="alert">
        <i class="fa-solid fa-circle-info me-2"></i>
        <?= sanitize($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>