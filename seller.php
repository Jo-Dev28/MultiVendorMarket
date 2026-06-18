<?php
$page_title = 'Seller Store';
require_once 'includes/header.php';

$seller_id = intval($_GET['id'] ?? 0);

if (!$seller_id) {
    flash('Invalid seller.', 'danger');
    redirect('sellers.php');
}

// Get seller details
$seller_sql = "SELECT s.*, u.name as owner_name, u.email as owner_email, u.phone as owner_phone,
                (SELECT COUNT(*) FROM products WHERE seller_id = s.id AND status = 'approved') as product_count,
                (SELECT COALESCE(AVG(r.rating), 0) FROM reviews r JOIN products p ON p.id = r.product_id WHERE p.seller_id = s.id) as avg_rating,
                (SELECT COUNT(*) FROM reviews r JOIN products p ON p.id = r.product_id WHERE p.seller_id = s.id) as review_count
                FROM sellers s
                JOIN users u ON u.id = s.user_id
                WHERE s.id = ? AND s.status = 'verified'";
$stmt = $mysqli->prepare($seller_sql);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller not found.', 'danger');
    redirect('sellers.php');
}

// Get seller products with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$products_sql = "SELECT p.*, c.name as category_name,
                (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.seller_id = ? AND p.status = 'approved'
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($products_sql);
$stmt->bind_param('iii', $seller_id, $limit, $offset);
$stmt->execute();
$products = $stmt->get_result();

// Get total products count
$count_sql = "SELECT COUNT(*) as total FROM products WHERE seller_id = ? AND status = 'approved'";
$stmt = $mysqli->prepare($count_sql);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$total_products = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

// Get seller reviews
$reviews_sql = "SELECT r.*, u.name as user_name, p.name as product_name
                FROM reviews r
                JOIN users u ON u.id = r.user_id
                JOIN products p ON p.id = r.product_id
                WHERE p.seller_id = ? AND r.status = 'approved'
                ORDER BY r.created_at DESC
                LIMIT 5";
$stmt = $mysqli->prepare($reviews_sql);
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$reviews = $stmt->get_result();

$rating = round($seller['avg_rating'] ?? 0, 1);
?>

<style>
/* ============================================
   SELLER STORE PAGE - CLEAN MODERN DESIGN
============================================ */

/* ---------- HERO SECTION ---------- */
.seller-store-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 50px 0;
    border-radius: 0 0 30px 30px;
    margin-bottom: 40px;
    position: relative;
    overflow: hidden;
}

.seller-store-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.seller-store-hero .container {
    position: relative;
    z-index: 1;
}

/* ---------- SELLER PROFILE ---------- */
.seller-profile {
    display: flex;
    align-items: center;
    gap: 30px;
    flex-wrap: wrap;
}

/* Avatar / Logo */
.seller-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    flex-shrink: 0;
    border: 4px solid rgba(255, 255, 255, 0.2);
    background: #1e293b;
}

.seller-avatar-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.seller-avatar-large .no-logo {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: #2563eb;
}

/* Seller Info */
.seller-info h1 {
    color: #fff;
    font-size: 2rem;
    font-weight: 800;
    margin-bottom: 4px;
}

.seller-info .owner {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
}

.seller-info .seller-stats {
    display: flex;
    gap: 30px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.seller-info .seller-stats .stat {
    color: rgba(255, 255, 255, 0.8);
}

.seller-info .seller-stats .stat strong {
    color: #fff;
    font-size: 1.1rem;
}

.seller-info .seller-stats .stat .stars {
    color: #f59e0b;
}

/* Action Buttons */
.seller-actions {
    display: flex;
    gap: 12px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.btn-back {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 10px 20px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-back:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.btn-chat {
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-chat:hover {
    background: rgba(255, 255, 255, 0.25);
    color: #fff;
}

.btn-shop {
    background: #f59e0b;
    color: #1f2937;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-shop:hover {
    background: #d97706;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.3);
    color: #1f2937;
}

/* ---------- SECTION TITLE ---------- */
.section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: #2563eb;
}

/* ---------- PRODUCT CARDS ---------- */
.product-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s ease;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
    border: 1px solid #f1f5f9;
    height: 100%;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
}

.product-card .product-img-container {
    height: 180px;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.product-card .product-img {
    height: 100%;
    width: 100%;
    object-fit: contain;
    padding: 10px;
}

.product-card .product-img-placeholder {
    font-size: 3rem;
    color: #9ca3af;
}

.product-card .product-info {
    padding: 16px;
}

.product-card .product-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.95rem;
    margin-bottom: 4px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.6rem;
}

.product-card .product-name a {
    color: inherit;
    text-decoration: none;
}

.product-card .product-name a:hover {
    color: #2563eb;
}

.product-card .product-category {
    font-size: 0.7rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.product-card .product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2563eb;
    margin: 8px 0;
}

.product-card .btn-view {
    width: 100%;
    padding: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    border-radius: 10px;
    font-size: 0.8rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    text-decoration: none;
    color: #1f2937;
    display: block;
}

.product-card .btn-view:hover {
    border-color: #2563eb;
    color: #2563eb;
}

/* ---------- REVIEWS ---------- */
.review-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    border-left: 3px solid #2563eb;
}

.review-card .review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 4px;
    margin-bottom: 4px;
}

.review-card .reviewer {
    font-weight: 600;
    color: #1f2937;
}

.review-card .review-product {
    font-size: 0.8rem;
    color: #6b7280;
}

.review-card .review-stars {
    color: #f59e0b;
    font-size: 0.8rem;
    margin-bottom: 4px;
}

.review-card .review-text {
    color: #4b5563;
    font-size: 0.9rem;
    margin: 0;
}

/* ---------- SIDEBAR ---------- */
.sidebar-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
    border: 1px solid #f1f5f9;
    position: sticky;
    top: 100px;
}

.sidebar-card .info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f1f5f9;
}

.sidebar-card .info-item:last-child {
    border-bottom: none;
}

.sidebar-card .info-item i {
    color: #2563eb;
    width: 20px;
}

.sidebar-card .info-item .label {
    color: #6b7280;
    font-size: 0.85rem;
}

.sidebar-card .info-item .value {
    color: #1f2937;
    font-weight: 500;
    font-size: 0.85rem;
}

/* ---------- NO PRODUCTS ---------- */
.no-products {
    text-align: center;
    padding: 40px;
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
}

.no-products i {
    font-size: 3rem;
    color: #9ca3af;
    margin-bottom: 12px;
}

/* ---------- PAGINATION ---------- */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.pagination a,
.pagination span {
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    text-decoration: none;
    color: #374151;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}

.pagination .active {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border-color: transparent;
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 768px) {
    .seller-profile {
        flex-direction: column;
        text-align: center;
    }
    
    .seller-info .seller-stats {
        justify-content: center;
    }
    
    .seller-actions {
        justify-content: center;
    }
    
    .seller-avatar-large {
        width: 100px;
        height: 100px;
    }
    
    .seller-avatar-large .no-logo {
        font-size: 2.5rem;
    }
}

@media (max-width: 480px) {
    .seller-avatar-large {
        width: 80px;
        height: 80px;
    }
    
    .seller-avatar-large .no-logo {
        font-size: 2rem;
    }
}
</style>

<!-- ============================================
     SELLER STORE HEADER
============================================ -->
<div class="seller-store-hero">
    <div class="container">
        <div class="seller-profile">
            <!-- Seller Logo / Avatar -->
            <div class="seller-avatar-large">
                <?php 
                $has_logo = !empty($seller['shop_logo']) && file_exists('uploads/sellers/' . $seller['shop_logo']);
                if ($has_logo): 
                ?>
                    <img src="uploads/sellers/<?= $seller['shop_logo'] ?>" 
                         alt="<?= sanitize($seller['shop_name']) ?>"
                         loading="lazy">
                <?php else: ?>
                    <div class="no-logo">
                        <i class="fa-solid fa-store"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Seller Info -->
            <div class="seller-info">
                <h1><?= sanitize($seller['shop_name']) ?></h1>
                <div class="owner">
                    <i class="fa-regular fa-user"></i> <?= sanitize($seller['owner_name']) ?>
                </div>
                
                <div class="seller-stats">
                    <div class="stat">
                        <strong><?= number_format($seller['product_count'] ?? 0) ?></strong> Products
                    </div>
                    <div class="stat">
                        <strong><?= number_format($seller['review_count'] ?? 0) ?></strong> Reviews
                    </div>
                    <div class="stat">
                        <span class="stars">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <?php if($i <= floor($rating)): ?>
                                    <i class="fa-solid fa-star"></i>
                                <?php elseif($i - 0.5 <= $rating): ?>
                                    <i class="fa-solid fa-star-half-stroke"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </span>
                        <strong><?= number_format($rating, 1) ?></strong>
                    </div>
                </div>
                
                <div class="seller-actions">
                    <a href="sellers.php" class="btn-back">
                        <i class="fa-solid fa-arrow-left"></i> Back
                    </a>
                    <a href="chat.php?seller=<?= $seller['id'] ?>" class="btn-chat">
                        <i class="fa-regular fa-message"></i> Chat
                    </a>
                    <a href="#products" class="btn-shop">
                        <i class="fa-solid fa-store"></i> Shop Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     PRODUCTS & REVIEWS
============================================ -->
<div class="container mb-5">
    <div class="row g-4">
        <!-- Products Section -->
        <div class="col-lg-8">
            <div id="products">
                <h3 class="section-title">
                    <i class="fa-solid fa-box"></i> Products by <?= sanitize($seller['shop_name']) ?>
                </h3>
                
                <?php if ($products->num_rows === 0): ?>
                    <div class="no-products">
                        <i class="fa-solid fa-box-open"></i>
                        <h4>No Products Yet</h4>
                        <p class="text-muted">This seller hasn't added any products yet.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php while ($product = $products->fetch_assoc()): 
                            $has_image = !empty($product['image']) && file_exists('uploads/products/' . $product['image']);
                        ?>
                            <div class="col-md-6">
                                <div class="product-card">
                                    <div class="product-img-container">
                                        <?php if ($has_image): ?>
                                            <img src="uploads/products/<?= sanitize($product['image']) ?>" 
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
                                        <div class="product-price">KSH <?= number_format($product['price']) ?></div>
                                        <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">
                                            <i class="fa-regular fa-eye"></i> View Product
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?id=<?= $seller_id ?>&page=<?= $page - 1 ?>">
                                    <i class="fa-solid fa-chevron-left"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?id=<?= $seller_id ?>&page=<?= $i ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?id=<?= $seller_id ?>&page=<?= $page + 1 ?>">
                                    Next <i class="fa-solid fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- Reviews Section -->
            <div class="mt-5">
                <h3 class="section-title">
                    <i class="fa-regular fa-star"></i> Customer Reviews
                </h3>
                
                <?php if ($reviews->num_rows === 0): ?>
                    <div class="text-center p-4 bg-white rounded-4 border">
                        <p class="text-muted">No reviews yet for this seller.</p>
                    </div>
                <?php else: ?>
                    <?php while ($review = $reviews->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="reviewer">
                                    <i class="fa-regular fa-user-circle"></i> <?= sanitize($review['user_name']) ?>
                                </span>
                                <span class="review-product">
                                    on <strong><?= sanitize($review['product_name']) ?></strong>
                                </span>
                            </div>
                            <div class="review-stars">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <?php if($i <= $review['rating']): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <p class="review-text"><?= sanitize($review['comment']) ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sidebar-card">
                <h5><i class="fa-solid fa-circle-info"></i> About This Seller</h5>
                
                <div class="info-item">
                    <i class="fa-regular fa-user"></i>
                    <div>
                        <span class="label">Owner:</span> 
                        <span class="value"><?= sanitize($seller['owner_name']) ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-regular fa-envelope"></i>
                    <div>
                        <span class="label">Email:</span> 
                        <span class="value"><?= sanitize($seller['owner_email']) ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-solid fa-phone"></i>
                    <div>
                        <span class="label">Phone:</span> 
                        <span class="value"><?= sanitize($seller['phone'] ?? 'N/A') ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-solid fa-location-dot"></i>
                    <div>
                        <span class="label">Location:</span> 
                        <span class="value"><?= sanitize($seller['location'] ?? 'N/A') ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-regular fa-calendar"></i>
                    <div>
                        <span class="label">Member Since:</span> 
                        <span class="value"><?= date('M d, Y', strtotime($seller['created_at'])) ?></span>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fa-regular fa-circle-check"></i>
                    <div>
                        <span class="label">Status:</span> 
                        <span class="value" style="color:#10b981;">✓ Verified</span>
                    </div>
                </div>
                
                <a href="chat.php?seller=<?= $seller['id'] ?>" class="btn btn-primary w-100 mt-3">
                    <i class="fa-regular fa-message"></i> Contact Seller
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>