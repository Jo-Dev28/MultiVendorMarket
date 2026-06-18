<?php
$page_title = 'Shop';
require_once 'includes/header.php';

$category_id = intval($_GET['category'] ?? 0);
$search = sanitize($_GET['search'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'newest');
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 0);

// Build query
$query = 'SELECT p.*, c.name AS category_name, s.shop_name, s.shop_logo 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN sellers s ON p.seller_id = s.id
          WHERE p.status = "approved" AND p.stock > 0';
$params = [];
$types = '';

if ($category_id) {
    $query .= ' AND p.category_id = ?';
    $types .= 'i';
    $params[] = $category_id;
}

if ($search) {
    $query .= ' AND (p.name LIKE ? OR p.short_description LIKE ? OR p.description LIKE ? OR s.shop_name LIKE ?)';
    $types .= 'ssss';
    $value = '%' . $search . '%';
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
    $params[] = $value;
}

if ($min_price > 0) {
    $query .= ' AND p.price >= ?';
    $types .= 'd';
    $params[] = $min_price;
}

if ($max_price > 0) {
    $query .= ' AND p.price <= ?';
    $types .= 'd';
    $params[] = $max_price;
}

// Sorting
switch($sort) {
    case 'price_low':
        $query .= ' ORDER BY p.price ASC';
        break;
    case 'price_high':
        $query .= ' ORDER BY p.price DESC';
        break;
    case 'rating':
        $query .= ' ORDER BY p.rating DESC';
        break;
    case 'name':
        $query .= ' ORDER BY p.name ASC';
        break;
    default:
        $query .= ' ORDER BY p.created_at DESC';
}

$query .= ' LIMIT 30';

$stmt = $mysqli->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get current user
$current_user = current_user();
$user_id = $current_user['id'] ?? null;

// Get user's wishlist products from database if logged in
$wishlist_products = [];
if ($user_id) {
    $wishlist_sql = "SELECT product_id FROM wishlists WHERE user_id = ?";
    $wishlist_stmt = $mysqli->prepare($wishlist_sql);
    $wishlist_stmt->bind_param('i', $user_id);
    $wishlist_stmt->execute();
    $wishlist_result = $wishlist_stmt->get_result();
    while ($row = $wishlist_result->fetch_assoc()) {
        $wishlist_products[] = $row['product_id'];
    }
} else {
    // For guests, check session
    $wishlist_products = $_SESSION['wishlist'] ?? [];
}

// Get categories for sidebar
$categories = $mysqli->query('SELECT c.*, COUNT(p.id) as product_count 
                              FROM categories c
                              LEFT JOIN products p ON p.category_id = c.id AND p.status = "approved"
                              GROUP BY c.id 
                              ORDER BY c.name');
?>

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
    
    .page-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 20px 20px;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        background-size: cover;
        opacity: 0.3;
    }
    
    .page-title {
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .page-subtitle {
        color: rgba(255,255,255,0.9);
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .filter-sidebar {
        background: var(--white);
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }
    
    .filter-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--secondary);
        display: inline-block;
    }
    
    .category-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .category-list li {
        margin-bottom: 0.5rem;
    }
    
    .category-list a {
        color: var(--dark);
        text-decoration: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.75rem;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .category-list a:hover {
        background: var(--light-gray);
        color: var(--primary);
        transform: translateX(5px);
    }
    
    .category-list a.active {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
    }
    
    .category-count {
        background: var(--light-gray);
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .category-list a.active .category-count {
        background: rgba(255,255,255,0.3);
        color: white;
    }
    
    .price-range {
        margin-top: 1.5rem;
    }
    
    .price-range .price-inputs {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
        align-items: center;
    }
    
    .price-range .price-input {
        flex: 1;
        padding: 0.6rem 0.8rem;
        border: 1px solid var(--border);
        border-radius: 10px;
        font-size: 0.85rem;
        width: 100%;
        transition: all 0.3s ease;
    }
    
    .price-range .price-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        outline: none;
    }
    
    .price-range .price-input::placeholder {
        color: #9ca3af;
    }
    
    .price-range .price-separator {
        color: var(--gray);
        font-weight: 600;
        padding: 0 2px;
    }
    
    .price-range .btn-apply {
        width: 100%;
        padding: 0.6rem;
        background: var(--secondary);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 0.8rem;
    }
    
    .price-range .btn-apply:hover {
        background: var(--secondary-dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245,158,11,0.3);
    }
    
    .sort-bar {
        background: var(--white);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .results-count {
        color: var(--gray);
        font-size: 0.9rem;
    }
    
    .sort-options {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    
    .sort-btn {
        padding: 0.4rem 1rem;
        border: 1px solid var(--border);
        background: var(--white);
        border-radius: 25px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        color: var(--dark);
    }
    
    .sort-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .sort-btn.active {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-color: transparent;
    }
    
    .product-card {
        background: var(--white);
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }
    
    .product-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        background: var(--secondary);
        color: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 1;
    }
    
    .product-badge.stock {
        background: var(--success);
    }
    
    .product-badge.low-stock {
        background: var(--danger);
    }
    
    .product-discount-badge {
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
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .product-wishlist {
        position: absolute;
        top: 12px;
        right: 12px;
        background: white;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        z-index: 1;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .product-wishlist.active {
        background: var(--danger);
        color: white;
    }
    
    .product-wishlist.active i {
        color: white;
    }
    
    .product-wishlist:hover {
        transform: scale(1.1);
    }
    
    .product-wishlist i {
        font-size: 1rem;
    }
    
    .product-image-container {
        width: 100%;
        height: 220px;
        background: var(--light-gray);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        position: relative;
    }
    
    .product-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 10px;
        transition: transform 0.5s ease;
    }
    
    .product-card:hover .product-image {
        transform: scale(1.05);
    }
    
    .product-image-placeholder {
        font-size: 3rem;
        color: #9ca3af;
    }
    
    .product-info {
        padding: 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .product-category {
        font-size: 0.7rem;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 0.5rem;
    }
    
    .product-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--dark);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        line-height: 1.4;
        min-height: 2.8rem;
    }
    
    .product-title a {
        color: inherit;
        text-decoration: none;
    }
    
    .product-title a:hover {
        color: var(--primary);
    }
    
    .seller-name {
        font-size: 0.75rem;
        color: var(--gray);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .product-rating {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        margin-bottom: 0.5rem;
    }
    
    .stars {
        color: #ffc107;
        font-size: 0.7rem;
    }
    
    .rating-value {
        font-size: 0.7rem;
        color: var(--gray);
    }
    
    .product-price-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 0.75rem;
        flex-wrap: wrap;
    }
    
    .product-price {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .product-price.discounted {
        color: #ef4444;
    }
    
    .product-original-price {
        font-size: 0.9rem;
        color: #9ca3af;
        text-decoration: line-through;
    }
    
    .product-save-badge {
        font-size: 0.7rem;
        background: #d1fae5;
        color: #059669;
        padding: 2px 8px;
        border-radius: 50px;
        font-weight: 600;
    }
    
    .product-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: auto;
    }
    
    .btn-view {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid var(--border);
        background: var(--white);
        border-radius: 10px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        text-decoration: none;
        color: var(--dark);
    }
    
    .btn-view:hover {
        border-color: var(--primary);
        color: var(--primary);
    }
    
    .btn-compare {
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .btn-compare:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    }
    
    .empty-state {
        text-align: center;
        padding: 4rem;
        background: var(--white);
        border-radius: 16px;
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--gray);
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
    }
    
    .active-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .filter-tag {
        background: var(--light-gray);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .filter-tag a {
        color: var(--danger);
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .filter-sidebar {
            position: static;
            margin-bottom: 1.5rem;
        }
        .sort-bar {
            flex-direction: column;
            align-items: flex-start;
        }
        .page-title {
            font-size: 1.5rem;
        }
        .price-range .price-inputs {
            flex-direction: row;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1 class="page-title">Shop Products</h1>
        <p class="page-subtitle">Discover amazing products from our trusted sellers</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="filter-sidebar">
                <h4 class="filter-title">Categories</h4>
                <ul class="category-list">
                    <li>
                        <a href="shop.php" class="<?= $category_id === 0 ? 'active' : '' ?>">
                            <span>All Categories</span>
                            <span class="category-count">All</span>
                        </a>
                    </li>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <li>
                            <a href="shop.php?category=<?= $cat['id'] ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                               class="<?= $category_id === (int)$cat['id'] ? 'active' : '' ?>">
                                <span><?= sanitize($cat['name']) ?></span>
                                <span class="category-count"><?= $cat['product_count'] ?></span>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
                
                <!-- Price Range Filter -->
                <div class="price-range">
                    <h4 class="filter-title">Price Range</h4>
                    <form method="get" id="priceFilterForm">
                        <?php if($category_id): ?>
                            <input type="hidden" name="category" value="<?= $category_id ?>">
                        <?php endif; ?>
                        <?php if($search): ?>
                            <input type="hidden" name="search" value="<?= sanitize($search) ?>">
                        <?php endif; ?>
                        <div class="price-inputs">
                            <input type="number" name="min_price" class="price-input" placeholder="Min" value="<?= $min_price ?: '' ?>">
                            <span class="price-separator">-</span>
                            <input type="number" name="max_price" class="price-input" placeholder="Max" value="<?= $max_price ?: '' ?>">
                        </div>
                        <button type="submit" class="btn-apply">
                            <i class="fa-solid fa-filter"></i> Apply Price
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Products Area -->
        <div class="col-lg-9">
            <!-- Sort Bar -->
            <div class="sort-bar">
                <div class="results-count">
                    <i class="fa-solid fa-list"></i> 
                    <?= $products->num_rows ?> products found
                </div>
                <div class="sort-options">
                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'newest'])) ?>" 
                       class="sort-btn <?= $sort === 'newest' ? 'active' : '' ?>">
                        <i class="fa-regular fa-clock"></i> Newest
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'price_low'])) ?>" 
                       class="sort-btn <?= $sort === 'price_low' ? 'active' : '' ?>">
                        <i class="fa-solid fa-arrow-down-wide-short"></i> Price: Low to High
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'price_high'])) ?>" 
                       class="sort-btn <?= $sort === 'price_high' ? 'active' : '' ?>">
                        <i class="fa-solid fa-arrow-up-wide-short"></i> Price: High to Low
                    </a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['sort' => 'rating'])) ?>" 
                       class="sort-btn <?= $sort === 'rating' ? 'active' : '' ?>">
                        <i class="fa-solid fa-star"></i> Top Rated
                    </a>
                </div>
            </div>
            
            <!-- Active Filters -->
            <?php if($category_id > 0 || $search || $min_price > 0 || $max_price > 0): ?>
            <div class="active-filters">
                <?php if($category_id > 0): ?>
                    <span class="filter-tag">
                        Category Filter Active 
                        <a href="shop.php?<?= http_build_query(array_merge($_GET, ['category' => null])) ?>">✕</a>
                    </span>
                <?php endif; ?>
                <?php if($search): ?>
                    <span class="filter-tag">
                        Search: "<?= sanitize($search) ?>"
                        <a href="shop.php?<?= http_build_query(array_merge($_GET, ['search' => null])) ?>">✕</a>
                    </span>
                <?php endif; ?>
                <?php if($min_price > 0 || $max_price > 0): ?>
                    <span class="filter-tag">
                        Price: <?= $min_price > 0 ? 'KSH ' . number_format($min_price) : 'Any' ?> - <?= $max_price > 0 ? 'KSH ' . number_format($max_price) : 'Any' ?>
                        <a href="shop.php?<?= http_build_query(array_merge($_GET, ['min_price' => null, 'max_price' => null])) ?>">✕</a>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Products Grid -->
            <?php if ($products->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No products found</h3>
                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                    <a href="shop.php" class="btn btn-primary">Clear all filters</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <?php 
                        $image_result = $mysqli->query("SELECT filename FROM product_images WHERE product_id = {$product['id']} LIMIT 1");
                        $image = $image_result ? $image_result->fetch_assoc() : null;
                        $has_image = $image && !empty($image['filename']) && file_exists('uploads/products/' . $image['filename']);
                        $in_wishlist = in_array($product['id'], $wishlist_products);
                        
                        // Check if product has discount
                        $has_discount = $product['is_on_sale'] == 1 && $product['discount_percent'] > 0 && $product['discount_end_date'] > date('Y-m-d H:i:s');
                        $discount_percent = $has_discount ? $product['discount_percent'] : 0;
                        $discounted_price = $has_discount ? ($product['discounted_price'] ?? $product['price'] * (1 - $discount_percent / 100)) : 0;
                        ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="product-card">
                                <?php if($has_discount): ?>
                                    <div class="product-discount-badge">-<?= $discount_percent ?>% OFF</div>
                                <?php elseif($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                    <div class="product-badge low-stock">Low Stock</div>
                                <?php elseif($product['stock'] > 0): ?>
                                    <div class="product-badge stock">In Stock</div>
                                <?php endif; ?>
                                
                                <div class="product-wishlist <?= $in_wishlist ? 'active' : '' ?>" 
                                     onclick="toggleWishlist(<?= $product['id'] ?>, this)">
                                    <i class="fa-<?= $in_wishlist ? 'solid' : 'regular' ?> fa-heart"></i>
                                </div>
                                
                                <div class="product-image-container">
                                    <?php if ($has_image): ?>
                                        <img src="uploads/products/<?= sanitize($image['filename']) ?>" 
                                             class="product-image" 
                                             alt="<?= sanitize($product['name']) ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-info">
                                    <div class="product-category">
                                        <i class="fa-regular fa-folder"></i> <?= sanitize($product['category_name'] ?? 'Uncategorized') ?>
                                    </div>
                                    
                                    <h5 class="product-title">
                                        <a href="product.php?id=<?= $product['id'] ?>"><?= sanitize($product['name']) ?></a>
                                    </h5>
                                    
                                    <div class="seller-name">
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
                                        <span class="rating-value">(<?= number_format($rating, 1) ?>)</span>
                                    </div>
                                    
                                    <div class="product-price-container">
                                        <?php if($has_discount): ?>
                                            <span class="product-price discounted">KSH <?= number_format($discounted_price) ?></span>
                                            <span class="product-original-price">KSH <?= number_format($product['price']) ?></span>
                                            <!-- <span class="product-save-badge">Save <?= $discount_percent ?>%</span> -->
                                        <?php else: ?>
                                            <span class="product-price">KSH <?= number_format($product['price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-actions">
                                        <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">
                                            <i class="fa-regular fa-eye"></i> View
                                        </a>
                                        <a href="compare.php?add=<?= $product['id'] ?>" class="btn-compare">
                                            <i class="fa-solid fa-chart-simple"></i> Compare
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Toggle wishlist function - saves to database and updates UI
function toggleWishlist(productId, element) {
    const isActive = element.classList.contains('active');
    const icon = element.querySelector('i');
    
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('api/toggle_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            if (data.added) {
                element.classList.add('active');
                icon.className = 'fa-solid fa-heart';
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Wishlist!',
                    text: 'Product saved to your wishlist.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                element.classList.remove('active');
                icon.className = 'fa-regular fa-heart';
                Swal.fire({
                    icon: 'info',
                    title: 'Removed',
                    text: 'Product removed from wishlist.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Login Required',
                text: data.message || 'Please login to manage wishlist.',
                showConfirmButton: true,
                confirmButtonText: 'Login Now'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php?redirect=shop.php';
                }
            });
        }
    })
    .catch(error => {
        Swal.close();
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>