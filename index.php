<?php
$page_title = 'Home';
require_once 'includes/header.php';

// Get featured products
$products = get_products($mysqli, 8);

// Get categories with product counts from database
$categories_sql = "SELECT c.*, COUNT(p.id) as product_count 
                   FROM categories c
                   LEFT JOIN products p ON p.category_id = c.id AND p.status = 'approved'
                   GROUP BY c.id 
                   ORDER BY product_count DESC 
                   LIMIT 4";
$categories_result = $mysqli->query($categories_sql);
$categories = [];
if ($categories_result) {
    while ($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

// Get top sellers from database
$top_sellers_sql = "SELECT s.id, s.shop_name, s.shop_logo, u.name as owner_name,
                    COUNT(DISTINCT p.id) as product_count,
                    COALESCE(AVG(r.rating), 0) as avg_rating
                    FROM sellers s
                    JOIN users u ON u.id = s.user_id
                    LEFT JOIN products p ON p.seller_id = s.id AND p.status = 'approved'
                    LEFT JOIN reviews r ON r.product_id = p.id
                    WHERE s.status = 'verified'
                    GROUP BY s.id
                    ORDER BY product_count DESC
                    LIMIT 4";
$top_sellers_result = $mysqli->query($top_sellers_sql);
$top_sellers = [];
if ($top_sellers_result) {
    while ($seller = $top_sellers_result->fetch_assoc()) {
        $top_sellers[] = $seller;
    }
}

// Get statistics from database
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM products WHERE status = 'approved') as total_products,
                (SELECT COUNT(*) FROM sellers WHERE status = 'verified') as total_sellers,
                (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
                (SELECT COUNT(*) FROM orders WHERE status = 'delivered') as total_orders
              FROM DUAL";
$stats_result = $mysqli->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// ============================================
// HOT DEALS - Products with 30%+ discount
// ============================================
$hot_deals_sql = "SELECT p.*, c.name as category_name, s.shop_name,
                  (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
                  FROM products p
                  LEFT JOIN categories c ON c.id = p.category_id
                  LEFT JOIN sellers s ON p.seller_id = s.id
                  WHERE p.status = 'approved' 
                  AND p.stock > 0
                  AND p.is_on_sale = 1
                  AND p.discount_percent >= 30
                  ORDER BY p.discount_percent DESC, p.price DESC
                  LIMIT 4";
$hot_deals_result = $mysqli->query($hot_deals_sql);

// ============================================
// FLASH SALES - Products with 1-29% discount
// ============================================
$flash_sales_sql = "SELECT p.*, c.name as category_name, s.shop_name,
                    (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN sellers s ON p.seller_id = s.id
                    WHERE p.status = 'approved' 
                    AND p.stock > 0
                    AND p.is_on_sale = 1
                    AND p.discount_percent BETWEEN 1 AND 29
                    ORDER BY p.discount_percent DESC
                    LIMIT 4";
$flash_sales_result = $mysqli->query($flash_sales_sql);

// Category icons mapping
$category_icons = [
    'Electronics' => 'fa-mobile-screen-button',
    'Fashion' => 'fa-shirt',
    'Home & Living' => 'fa-couch',
    'Beauty' => 'fa-spray-can-sparkles',
    'default' => 'fa-folder'
];
?>

<style>
/* ============================================
   MODERN UI DESIGN - FIXED CSS
============================================ */
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --primary-light: #60a5fa;
    --accent: #f59e0b;
    --accent-dark: #d97706;
    --dark: #1e293b;
    --gray: #64748b;
    --light-gray: #f1f5f9;
    --white: #ffffff;
    --border: #e2e8f0;
    --shadow: 0 4px 20px rgba(0,0,0,0.08);
    --shadow-hover: 0 12px 40px rgba(0,0,0,0.12);
    --radius: 20px;
    -
    -transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}


/* Badges */
.flash-badge {
    background: #ef4444;
    color: white;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    animation: pulse 2s infinite;
}

.hot-badge {
    background: #f59e0b;
    color: white;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.section-header-modern {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
    gap: 12px;
}

.section-title-modern {
    font-size: 1.6rem;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.section-title-modern i {
    color: var(--primary);
}

.section-title-modern .flash-badge {
    font-size: 0.7rem;
    padding: 4px 14px;
}

.section-title-modern .hot-badge {
    font-size: 0.7rem;
    padding: 4px 14px;
}

.section-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}

.section-link:hover {
    color: var(--primary-dark);
    gap: 10px;
}

/* Product Cards */
.product-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.product-card-modern {
    background: var(--white);
    border-radius: var(--radius);
    overflow: hidden;
    transition: var(--transition);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    position: relative;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.product-card-modern:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
}

.product-card-modern .product-image-container {
    width: 100%;
    height: 200px;
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
}

.product-card-modern .product-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    background: var(--light-gray);
    transition: transform 0.5s ease;
    padding: 8px;
}

.product-card-modern:hover .product-img {
    transform: scale(1.05);
}

.product-card-modern .product-img-placeholder {
    font-size: 3rem;
    color: #9ca3af;
}

.product-card-modern .product-discount-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #ef4444;
    color: white;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    z-index: 2;
}

.product-card-modern .product-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: var(--accent);
    color: var(--white);
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 700;
    z-index: 2;
}

.product-card-modern .product-wishlist {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--white);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 2;
    border: none;
    color: #64748b;
}

.product-card-modern .product-wishlist:hover {
    background: #fee2e2;
    color: #ef4444;
}

.product-card-modern .product-info {
    padding: 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.product-card-modern .product-category {
    font-size: 0.7rem;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}

.product-card-modern .product-name {
    font-weight: 600;
    color: var(--dark);
    font-size: 0.95rem;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.6rem;
}

.product-card-modern .product-name a {
    color: inherit;
    text-decoration: none;
}

.product-card-modern .product-name a:hover {
    color: var(--primary);
}

.product-card-modern .product-seller {
    color: var(--gray);
    font-size: 0.75rem;
    margin-bottom: 6px;
}

.product-card-modern .product-rating {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 6px;
}

.product-card-modern .product-rating .stars {
    color: #f59e0b;
    font-size: 0.75rem;
}

.product-card-modern .product-rating .rating-text {
    color: var(--gray);
    font-size: 0.7rem;
}

.product-card-modern .product-price-container {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 10px;
}

.product-card-modern .product-price.discounted {
    font-size: 1.1rem;
    font-weight: 700;
    color: #ef4444;
}

.product-card-modern .product-original-price {
    font-size: 0.8rem;
    color: #9ca3af;
    text-decoration: line-through;
}

.product-card-modern .product-save-badge {
    font-size: 0.65rem;
    background: #d1fae5;
    color: #059669;
    padding: 2px 8px;
    border-radius: 50px;
    font-weight: 600;
}

.product-card-modern .product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 10px;
}

.product-card-modern .product-actions {
    display: flex;
    gap: 8px;
    margin-top: auto;
}

.product-card-modern .btn-view {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid var(--border);
    background: var(--white);
    border-radius: 10px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: var(--transition);
    text-align: center;
    text-decoration: none;
    color: var(--dark);
}

.product-card-modern .btn-view:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.product-card-modern .btn-cart {
    padding: 8px 14px;
    background: var(--primary);
    color: var(--white);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: var(--transition);
}

.product-card-modern .btn-cart:hover {
    background: var(--primary-dark);
    transform: scale(1.05);
}

/* Hero Section */
.hero-modern {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 50%, #1e293b 100%);
    padding: 80px 0 60px;
    position: relative;
    overflow: hidden;
    border-radius: 0 0 30px 30px;
}

.hero-modern::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 700px;
    height: 700px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.hero-modern::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -10%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-badge {
    display: inline-block;
    background: rgba(37, 99, 235, 0.2);
    color: var(--primary-light);
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border: 1px solid rgba(37, 99, 235, 0.3);
    margin-bottom: 20px;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 800;
    color: var(--white);
    line-height: 1.1;
    margin-bottom: 20px;
}

.hero-title span {
    color: var(--accent);
    position: relative;
}

.hero-title span::after {
    content: '';
    position: absolute;
    bottom: 4px;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--accent);
    border-radius: 4px;
    opacity: 0.4;
}

.hero-subtitle {
    color: rgba(255,255,255,0.7);
    font-size: 1.15rem;
    max-width: 500px;
    line-height: 1.7;
    margin-bottom: 30px;
}

.hero-buttons {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
}

.btn-primary-hero {
    background: var(--accent);
    color: var(--dark);
    padding: 16px 36px;
    border-radius: 12px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
}

.btn-primary-hero:hover {
    background: var(--accent-dark);
    transform: translateY(-3px);
    box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
    color: var(--white);
}

.btn-outline-hero {
    background: transparent;
    color: var(--white);
    padding: 16px 36px;
    border-radius: 12px;
    font-weight: 600;
    border: 2px solid rgba(255,255,255,0.2);
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-size: 1rem;
}

.btn-outline-hero:hover {
    background: rgba(255,255,255,0.1);
    border-color: var(--white);
    transform: translateY(-3px);
    color: var(--white);
}

.hero-image-wrapper {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 480px;
    padding: 10px;
}

.hero-image-wrapper img {
    width: 100%;
    height: 480px;
    object-fit: contain;
    border-radius: var(--radius);
    filter: drop-shadow(0 30px 60px rgba(0,0,0,0.4));
    transition: transform 0.5s ease;
}

.hero-image-wrapper img:hover {
    transform: scale(1.02);
}

.hero-image-wrapper .hero-placeholder {
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius);
    height: 480px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gray);
    font-size: 1.2rem;
    border: 2px dashed rgba(255,255,255,0.1);
}

.hero-image-wrapper .hero-placeholder i {
    font-size: 4rem;
    margin-right: 15px;
    opacity: 0.5;
}

.hero-stats {
    display: flex;
    gap: 50px;
    margin-top: 35px;
    flex-wrap: wrap;
}

.hero-stat {
    text-align: left;
}

.hero-stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--white);
    line-height: 1;
}

.hero-stat-label {
    color: rgba(255,255,255,0.5);
    font-size: 0.85rem;
    margin-top: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Category Cards */
.category-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.category-card-modern {
    background: var(--white);
    border-radius: var(--radius);
    padding: 24px 16px;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.category-card-modern:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
    border-color: var(--primary);
}

.category-card-modern .cat-icon {
    width: 64px;
    height: 64px;
    background: var(--light-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 1.5rem;
    color: var(--primary);
    transition: var(--transition);
}

.category-card-modern:hover .cat-icon {
    background: var(--primary);
    color: var(--white);
    transform: scale(1.1);
}

.category-card-modern .cat-name {
    font-weight: 600;
    color: var(--dark);
    font-size: 0.95rem;
}

.category-card-modern .cat-count {
    color: var(--gray);
    font-size: 0.8rem;
    margin-top: 4px;
}

/* Seller Cards */
.seller-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.seller-card-modern {
    background: var(--white);
    border-radius: var(--radius);
    padding: 24px;
    text-align: center;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: var(--transition);
}

.seller-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.seller-card-modern .seller-avatar {
    width: 72px;
    height: 72px;
    background: var(--light-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 2rem;
    color: var(--primary);
    transition: var(--transition);
}

.seller-card-modern:hover .seller-avatar {
    background: var(--primary);
    color: var(--white);
}

.seller-card-modern .seller-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--dark);
    margin-bottom: 4px;
}

.seller-card-modern .seller-rating {
    color: #f59e0b;
    font-size: 0.8rem;
    margin-bottom: 4px;
}

.seller-card-modern .seller-products {
    color: var(--gray);
    font-size: 0.8rem;
    margin-bottom: 12px;
}

.seller-card-modern .btn-visit {
    padding: 8px 20px;
    background: var(--primary);
    color: var(--white);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    display: inline-block;
    font-size: 0.85rem;
    font-weight: 600;
}

.seller-card-modern .btn-visit:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
    color: var(--white);
}

/* Features Section */
.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.feature-card-modern {
    background: var(--white);
    border-radius: var(--radius);
    padding: 24px;
    text-align: center;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    transition: var(--transition);
}

.feature-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.feature-card-modern .feature-icon {
    width: 64px;
    height: 64px;
    background: var(--light-gray);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 1.5rem;
    color: var(--primary);
    transition: var(--transition);
}

.feature-card-modern:hover .feature-icon {
    background: var(--primary);
    color: var(--white);
    transform: scale(1.1) rotate(10deg);
}

.feature-card-modern .feature-title {
    font-weight: 700;
    font-size: 1rem;
    color: var(--dark);
    margin-bottom: 4px;
}

.feature-card-modern .feature-desc {
    color: var(--gray);
    font-size: 0.85rem;
}

/* Newsletter */
.newsletter-modern {
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border-radius: var(--radius);
    padding: 50px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.newsletter-modern::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.newsletter-modern .newsletter-content {
    position: relative;
    z-index: 2;
}

.newsletter-modern h3 {
    color: var(--white);
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 8px;
}

.newsletter-modern p {
    color: rgba(255,255,255,0.6);
    margin-bottom: 20px;
}

.newsletter-input-group {
    max-width: 480px;
    margin: 0 auto;
    display: flex;
    gap: 8px;
}

.newsletter-input-group input {
    flex: 1;
    padding: 12px 20px;
    border-radius: 12px;
    border: none;
    font-size: 0.95rem;
    background: rgba(255,255,255,0.1);
    color: var(--white);
    border: 1px solid rgba(255,255,255,0.1);
    transition: var(--transition);
}

.newsletter-input-group input::placeholder {
    color: rgba(255,255,255,0.4);
}

.newsletter-input-group input:focus {
    outline: none;
    background: rgba(255,255,255,0.15);
    border-color: var(--accent);
}

.newsletter-input-group .btn-subscribe {
    padding: 12px 28px;
    background: var(--accent);
    color: var(--dark);
    border: none;
    border-radius: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}

.newsletter-input-group .btn-subscribe:hover {
    background: var(--accent-dark);
    transform: translateX(4px);
    color: var(--white);
}

/* Responsive */
@media (max-width: 1200px) {
    .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .category-grid,
    .seller-grid,
    .features-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .hero-image-wrapper {
        min-height: 420px;
    }
    .hero-image-wrapper img {
        height: 420px;
    }
    .hero-title {
        font-size: 3rem;
    }
}

@media (max-width: 992px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .category-grid,
    .seller-grid,
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .hero-title {
        font-size: 2.5rem;
    }
    .hero-image-wrapper {
        min-height: 350px;
        margin-top: 20px;
    }
    .hero-image-wrapper img {
        height: 350px;
    }
    .hero-stats {
        gap: 30px;
    }
    .hero-modern {
        padding: 50px 0 40px;
    }
}

@media (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    .category-grid,
    .seller-grid,
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .hero-title {
        font-size: 2rem;
    }
    .hero-image-wrapper {
        min-height: 280px;
    }
    .hero-image-wrapper img {
        height: 280px;
    }
    .hero-stats {
        justify-content: center;
        gap: 25px;
    }
    .hero-buttons {
        justify-content: center;
    }
    .hero-subtitle {
        max-width: 100%;
        text-align: center;
    }
    .hero-title {
        text-align: center;
    }
    .hero-badge {
        display: table;
        margin: 0 auto 16px;
    }
    .hero-stat {
        text-align: center;
    }
    .hero-stat-number {
        font-size: 1.6rem;
    }
    .hero-modern {
        padding: 40px 0 30px;
    }
    .section-header-modern {
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
    .newsletter-modern {
        padding: 30px 20px;
    }
    .newsletter-input-group {
        flex-direction: column;
    }
    .newsletter-input-group .btn-subscribe {
        width: 100%;
        justify-content: center;
    }
    .product-card-modern .product-image-container {
        height: 180px;
    }
}

@media (max-width: 480px) {
    .product-grid {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .category-grid,
    .seller-grid,
    .features-grid {
        grid-template-columns: 1fr 1fr;
    }
    .hero-title {
        font-size: 1.6rem;
    }
    .hero-image-wrapper {
        min-height: 200px;
    }
    .hero-image-wrapper img {
        height: 200px;
    }
    .hero-buttons {
        flex-direction: column;
        align-items: stretch;
    }
    .hero-buttons .btn {
        width: 100%;
        justify-content: center;
    }
    .hero-stats {
        flex-direction: column;
        align-items: center;
    }
    .hero-subtitle {
        font-size: 1rem;
    }
    .btn-primary-hero,
    .btn-outline-hero {
        padding: 14px 24px;
        font-size: 0.9rem;
    }
    .hero-modern {
        padding: 30px 0 20px;
    }
    .hero-image-wrapper {
        padding: 5px;
    }
    .hero-image-wrapper .hero-placeholder {
        height: 200px;
        font-size: 0.9rem;
    }
    .hero-image-wrapper .hero-placeholder i {
        font-size: 2.5rem;
    }
    .product-card-modern .product-image-container {
        height: 150px;
    }
    .product-card-modern .product-info {
        padding: 10px;
    }
    .product-card-modern .product-name {
        font-size: 0.85rem;
        min-height: 2.2rem;
    }
}
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<section class="hero-modern">
    <div class="container">
        <div class="row align-items-center hero-content">
            <div class="col-lg-5">
                <div class="hero-badge">
                    <i class="fa-solid fa-sparkles"></i> AI-Powered Marketplace
                </div>
                <h1 class="hero-title">
                    Discover Amazing<br>
                    <span>Products</span> Today
                </h1>
                <p class="hero-subtitle">
                    Shop thousands of products from trusted sellers across Kenya. 
                    Get AI-powered recommendations and exclusive deals.
                </p>
                <div class="hero-buttons">
                    <a href="shop.php" class="btn-primary-hero">
                        <i class="fa-solid fa-cart-shopping"></i> Start Shopping
                    </a>
                    <a href="ai_assistant.php" class="btn-outline-hero">
                        <i class="fa-solid fa-robot"></i> AI Assistant
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?= number_format($stats['total_products'] ?? 10000) ?>+</div>
                        <div class="hero-stat-label">Products</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?= number_format($stats['total_sellers'] ?? 500) ?>+</div>
                        <div class="hero-stat-label">Sellers</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?= number_format($stats['total_customers'] ?? 5000) ?>+</div>
                        <div class="hero-stat-label">Happy Customers</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7 hero-image-wrapper">
                <img src="1.png" 
                     alt="Shopping" 
                     loading="lazy"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'hero-placeholder\'><i class=\'fa-solid fa-image\'></i> Shopping Banner</div>'">
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     CATEGORIES SECTION
============================================ -->
<section class="py-5">
    <div class="container">
        <div class="section-header-modern">
            <h2 class="section-title-modern">
                <i class="fa-solid fa-grid-2"></i> Shop by Category
            </h2>
            <a href="shop.php" class="section-link">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
                <div class="category-card-modern" onclick="window.location.href='shop.php?category=<?= $category['id'] ?>'">
                    <div class="cat-icon">
                        <i class="fa-solid <?= $category_icons[$category['name']] ?? $category_icons['default'] ?>"></i>
                    </div>
                    <div class="cat-name"><?= sanitize($category['name']) ?></div>
                    <div class="cat-count"><?= number_format($category['product_count'] ?? 0) ?> Products</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================
     HOT DEALS - Products with 30%+ discount
============================================ -->
<?php if ($hot_deals_result && $hot_deals_result->num_rows > 0): ?>
<section class="py-5" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);">
    <div class="container">
        <div class="section-header-modern">
            <h2 class="section-title-modern">
                <i class="fa-solid fa-fire" style="color:#f59e0b;"></i> Hot Deals
                <span class="hot-badge">🔥 30%+ OFF</span>
            </h2>
            <a href="hot-deals.php" class="section-link">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="product-grid">
            <?php while ($product = $hot_deals_result->fetch_assoc()): 
                $image_result = $mysqli->query("SELECT filename FROM product_images WHERE product_id = {$product['id']} LIMIT 1");
                $image = $image_result ? $image_result->fetch_assoc() : null;
                $has_image = $image && !empty($image['filename']) && file_exists('uploads/products/' . $image['filename']);
                $discount = $product['discount_percent'] ?? 0;
                $discounted_price = $product['discounted_price'] ?? ($product['price'] * (1 - $discount / 100));
            ?>
                <div class="product-card-modern">
                    <div class="product-discount-badge">-<?= $discount ?>% OFF</div>
                    <button class="product-wishlist" onclick="addToWishlist(<?= $product['id'] ?>)">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    <div class="product-image-container">
                        <?php if ($has_image): ?>
                            <img src="uploads/products/<?= sanitize($image['filename']) ?>" 
                                 class="product-img" 
                                 alt="<?= sanitize($product['name']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="product-img-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-category">
                            <i class="fa-regular fa-folder"></i> <?= sanitize($product['category_name'] ?? 'Uncategorized') ?>
                        </div>
                        <div class="product-name">
                            <a href="product.php?id=<?= $product['id'] ?>"><?= sanitize($product['name']) ?></a>
                        </div>
                        <div class="product-seller">
                            <i class="fa-solid fa-store"></i> <?= sanitize($product['shop_name'] ?? 'Unknown Seller') ?>
                        </div>
                        <div class="product-rating">
                            <div class="stars">
                                <?php 
                                $rating = round($product['rating'] ?? 0, 1);
                                for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= floor($rating)): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php elseif($i - 0.5 <= $rating): ?>
                                        <i class="fa-solid fa-star-half-stroke"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">(<?= number_format($rating, 1) ?>)</span>
                        </div>
                        <div class="product-price-container">
                            <span class="product-price discounted">KSH <?= number_format($discounted_price) ?></span>
                            <span class="product-original-price">KSH <?= number_format($product['price']) ?></span>
                            <span class="product-save-badge">Save <?= $discount ?>%</span>
                        </div>
                        <div class="product-actions">
                            <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">
                                <i class="fa-regular fa-eye"></i> View
                            </a>
                            <button class="btn-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                <i class="fa-solid fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================
     FLASH SALES - Products with 1-29% discount
============================================ -->
<?php if ($flash_sales_result && $flash_sales_result->num_rows > 0): ?>
<section class="py-5" style="background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);">
    <div class="container">
        <div class="section-header-modern">
            <h2 class="section-title-modern">
                <i class="fa-solid fa-bolt" style="color:#ef4444;"></i> Flash Sales
                <span class="flash-badge">Limited Time!</span>
            </h2>
            <a href="flash-sales.php" class="section-link">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="product-grid">
            <?php while ($product = $flash_sales_result->fetch_assoc()): 
                $image_result = $mysqli->query("SELECT filename FROM product_images WHERE product_id = {$product['id']} LIMIT 1");
                $image = $image_result ? $image_result->fetch_assoc() : null;
                $has_image = $image && !empty($image['filename']) && file_exists('uploads/products/' . $image['filename']);
                $discount = $product['discount_percent'] ?? 0;
                $discounted_price = $product['discounted_price'] ?? ($product['price'] * (1 - $discount / 100));
            ?>
                <div class="product-card-modern">
                    <div class="product-discount-badge">-<?= $discount ?>% OFF</div>
                    <button class="product-wishlist" onclick="addToWishlist(<?= $product['id'] ?>)">
                        <i class="fa-regular fa-heart"></i>
                    </button>
                    <div class="product-image-container">
                        <?php if ($has_image): ?>
                            <img src="uploads/products/<?= sanitize($image['filename']) ?>" 
                                 class="product-img" 
                                 alt="<?= sanitize($product['name']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="product-img-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-category">
                            <i class="fa-regular fa-folder"></i> <?= sanitize($product['category_name'] ?? 'Uncategorized') ?>
                        </div>
                        <div class="product-name">
                            <a href="product.php?id=<?= $product['id'] ?>"><?= sanitize($product['name']) ?></a>
                        </div>
                        <div class="product-seller">
                            <i class="fa-solid fa-store"></i> <?= sanitize($product['shop_name'] ?? 'Unknown Seller') ?>
                        </div>
                        <div class="product-rating">
                            <div class="stars">
                                <?php 
                                $rating = round($product['rating'] ?? 0, 1);
                                for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= floor($rating)): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php elseif($i - 0.5 <= $rating): ?>
                                        <i class="fa-solid fa-star-half-stroke"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">(<?= number_format($rating, 1) ?>)</span>
                        </div>
                        <div class="product-price-container">
                            <span class="product-price discounted">KSH <?= number_format($discounted_price) ?></span>
                            <span class="product-original-price">KSH <?= number_format($product['price']) ?></span>
                            <span class="product-save-badge">Save <?= $discount ?>%</span>
                        </div>
                        <div class="product-actions">
                            <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">
                                <i class="fa-regular fa-eye"></i> View
                            </a>
                            <button class="btn-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                <i class="fa-solid fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============================================
     FEATURED PRODUCTS with Discounts
============================================ -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="section-header-modern">
            <h2 class="section-title-modern">
                <i class="fa-solid fa-star"></i> Featured Products
            </h2>
            <a href="shop.php" class="section-link">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="product-grid">
            <?php if ($products && $products->num_rows > 0): ?>
                <?php while ($product = $products->fetch_assoc()): 
                    $image_result = $mysqli->query("SELECT filename FROM product_images WHERE product_id = {$product['id']} LIMIT 1");
                    $image = $image_result ? $image_result->fetch_assoc() : null;
                    $has_image = $image && !empty($image['filename']) && file_exists('uploads/products/' . $image['filename']);
                    
                    $has_discount = isset($product['is_on_sale']) && $product['is_on_sale'] == 1 && 
                                    $product['discount_percent'] > 0 && 
                                    !empty($product['discount_end_date']) && 
                                    $product['discount_end_date'] > date('Y-m-d H:i:s');
                    $discount = $has_discount ? $product['discount_percent'] : 0;
                    $discounted_price = $has_discount ? ($product['discounted_price'] ?? $product['price'] * (1 - $discount / 100)) : 0;
                ?>
                    <div class="product-card-modern">
                        <?php if($has_discount): ?>
                            <div class="product-discount-badge">-<?= $discount ?>% OFF</div>
                        <?php else: ?>
                            <span class="product-badge">🔥 Featured</span>
                        <?php endif; ?>
                        
                        <button class="product-wishlist" onclick="addToWishlist(<?= $product['id'] ?>)">
                            <i class="fa-regular fa-heart"></i>
                        </button>
                        
                        <div class="product-image-container">
                            <?php if ($has_image): ?>
                                <img src="uploads/products/<?= sanitize($image['filename']) ?>" 
                                     class="product-img" 
                                     alt="<?= sanitize($product['name']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="product-img-placeholder">
                                    <i class="fa-solid fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-category">
                                <i class="fa-regular fa-folder"></i> <?= sanitize($product['category_name'] ?? 'Uncategorized') ?>
                            </div>
                            <div class="product-name">
                                <a href="product.php?id=<?= $product['id'] ?>"><?= sanitize($product['name']) ?></a>
                            </div>
                            <div class="product-seller">
                                <i class="fa-solid fa-store"></i> <?= sanitize($product['shop_name'] ?? 'Unknown Seller') ?>
                            </div>
                            <div class="product-rating">
                                <div class="stars">
                                    <?php 
                                    $rating = round($product['rating'] ?? 0, 1);
                                    for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= floor($rating)): ?>
                                            <i class="fa-solid fa-star"></i>
                                        <?php elseif($i - 0.5 <= $rating): ?>
                                            <i class="fa-solid fa-star-half-stroke"></i>
                                        <?php else: ?>
                                            <i class="fa-regular fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text">(<?= number_format($rating, 1) ?>)</span>
                            </div>
                            
                            <?php if($has_discount): ?>
                                <div class="product-price-container">
                                    <span class="product-price discounted">KSH <?= number_format($discounted_price) ?></span>
                                    <span class="product-original-price">KSH <?= number_format($product['price']) ?></span>
                                    <span class="product-save-badge">Save <?= $discount ?>%</span>
                                </div>
                            <?php else: ?>
                                <div class="product-price">KSH <?= number_format($product['price']) ?></div>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">
                                    <i class="fa-regular fa-eye"></i> View
                                </a>
                                <button class="btn-cart" onclick="addToCart(<?= $product['id'] ?>)">
                                    <i class="fa-solid fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <?php for($i = 0; $i < 4; $i++): ?>
                    <div class="product-card-modern">
                        <span class="product-badge">Featured</span>
                        <div class="product-image-container">
                            <div class="product-img-placeholder">
                                <i class="fa-solid fa-image"></i>
                            </div>
                        </div>
                        <div class="product-info">
                            <div class="product-category"><i class="fa-regular fa-folder"></i> Sample Category</div>
                            <div class="product-name">Sample Product <?= $i+1 ?></div>
                            <div class="product-seller"><i class="fa-solid fa-store"></i> Sample Seller</div>
                            <div class="product-rating">
                                <div class="stars">
                                    <?php for($j = 1; $j <= 5; $j++): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="rating-text">(4.5)</span>
                            </div>
                            <div class="product-price">KSH <?= number_format(rand(500, 5000)) ?></div>
                            <div class="product-actions">
                                <a href="product.php" class="btn-view"><i class="fa-regular fa-eye"></i> View</a>
                                <button class="btn-cart"><i class="fa-solid fa-cart-plus"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================
     TOP SELLERS
============================================ -->
<section class="py-5">
    <div class="container">
        <div class="section-header-modern">
            <h2 class="section-title-modern">
                <i class="fa-solid fa-store"></i> Top Sellers
            </h2>
            <a href="sellers.php" class="section-link">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="seller-grid">
            <?php if (!empty($top_sellers)): ?>
                <?php foreach ($top_sellers as $seller): ?>
                    <div class="seller-card-modern">
                        <div class="seller-avatar">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <div class="seller-name"><?= sanitize($seller['shop_name']) ?></div>
                        <div class="seller-rating">
                            <?php 
                            $rating = round($seller['avg_rating'] ?? 0, 1);
                            for($i = 1; $i <= 5; $i++): ?>
                                <?php if($i <= floor($rating)): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif($i - 0.5 <= $rating): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="seller-products"><?= number_format($seller['product_count'] ?? 0) ?> products</div>
                        <a href="seller.php?id=<?= $seller['id'] ?>" class="btn-visit">Visit Store</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                $default_sellers = [
                    ['id' => 1, 'shop_name' => 'TechZone Kenya', 'avg_rating' => 4.5, 'product_count' => 245],
                    ['id' => 2, 'shop_name' => 'Fashion Hub', 'avg_rating' => 4.8, 'product_count' => 532],
                    ['id' => 3, 'shop_name' => 'Home Essentials', 'avg_rating' => 4.2, 'product_count' => 189],
                    ['id' => 4, 'shop_name' => 'Gadget World', 'avg_rating' => 5.0, 'product_count' => 423]
                ];
                foreach($default_sellers as $seller): 
                ?>
                    <div class="seller-card-modern">
                        <div class="seller-avatar"><i class="fa-solid fa-store"></i></div>
                        <div class="seller-name"><?= $seller['shop_name'] ?></div>
                        <div class="seller-rating">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php if($i <= floor($seller['avg_rating'])): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif($i - 0.5 <= $seller['avg_rating']): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="seller-products"><?= $seller['product_count'] ?> products</div>
                        <a href="seller.php?id=<?= $seller['id'] ?>" class="btn-visit">Visit Store</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ============================================
     FEATURES SECTION
============================================ -->
<section class="py-5">
    <div class="container">
        <div class="section-header-modern">
            <h2 class="section-title-modern">
                <i class="fa-solid fa-circle-check"></i> Why Choose Us
            </h2>
        </div>
        <div class="features-grid">
            <div class="feature-card-modern">
                <div class="feature-icon"><i class="fa-solid fa-truck-fast"></i></div>
                <div class="feature-title">Free Shipping</div>
                <div class="feature-desc">On orders over KSH 5,000</div>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon"><i class="fa-solid fa-robot"></i></div>
                <div class="feature-title">AI Assistance</div>
                <div class="feature-desc">Smart product recommendations</div>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon"><i class="fa-solid fa-shield-heart"></i></div>
                <div class="feature-title">Secure Payment</div>
                <div class="feature-desc">M-Pesa, Card, PayPal</div>
            </div>
            <div class="feature-card-modern">
                <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
                <div class="feature-title">24/7 Support</div>
                <div class="feature-desc">Dedicated customer service</div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     NEWSLETTER
============================================ -->
<section class="py-5">
    <div class="container">
        <div class="newsletter-modern">
            <div class="newsletter-content">
                <h3>Subscribe to Our Newsletter</h3>
                <p>Get the latest updates on new products and exclusive offers</p>
                <div class="newsletter-input-group">
                    <input type="email" id="newsletterEmail" placeholder="Enter your email address">
                    <button class="btn-subscribe" onclick="subscribeNewsletter()">
                        Subscribe <i class="fa-solid fa-paper-plane ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Add to cart
function addToCart(productId) {
    Swal.fire({
        title: 'Adding to cart...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    $.ajax({
        url: 'api/add_to_cart.php',
        method: 'POST',
        data: { product_id: productId, quantity: 1 },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Cart!',
                    text: 'Product has been added to your cart.',
                    timer: 2000,
                    showConfirmButton: false,
                    position: 'top-end'
                });
                updateCartCount();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Failed to add product.'
                });
            }
        },
        error: function() {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Login Required',
                text: 'Please login to add items to cart.',
                confirmButtonText: 'Login Now'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                }
            });
        }
    });
}

// Add to wishlist
function addToWishlist(productId) {
    $.ajax({
        url: 'api/add_to_wishlist.php',
        method: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Wishlist!',
                    text: 'Product saved to your wishlist.',
                    timer: 2000,
                    showConfirmButton: false,
                    position: 'top-end'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message || 'Please login to add to wishlist.'
                });
            }
        }
    });
}

// Update cart count
function updateCartCount() {
    $.ajax({
        url: 'api/get_cart_count.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.count !== undefined) {
                $('.badge-count').text(response.count);
                $('#floatCartCount').text(response.count);
            }
        }
    });
}

// Subscribe to newsletter
function subscribeNewsletter() {
    const email = $('#newsletterEmail').val();
    if (!email) {
        Swal.fire({
            icon: 'warning',
            title: 'Email Required',
            text: 'Please enter your email address.'
        });
        return;
    }
    
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!re.test(email)) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Email',
            text: 'Please enter a valid email address.'
        });
        return;
    }
    
    $.ajax({
        url: 'api/subscribe.php',
        method: 'POST',
        data: { email: email },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Subscribed!',
                    text: 'Thank you for subscribing to our newsletter.',
                    timer: 2000,
                    showConfirmButton: false
                });
                $('#newsletterEmail').val('');
            }
        }
    });
}

$(document).ready(function() {
    updateCartCount();
});
</script>

<?php require_once 'includes/footer.php'; ?>