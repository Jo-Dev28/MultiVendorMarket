<?php
$page_title = 'My Shop';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT s.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
               FROM sellers s 
               JOIN users u ON u.id = s.user_id 
               WHERE s.user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

// Get product count
$product_count = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'approved'")->fetch_assoc()['count'];

// Get pending products count
$pending_products = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'pending'")->fetch_assoc()['count'];

// Get rejected products count
$rejected_products = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'rejected'")->fetch_assoc()['count'];

// Get order count
$order_count = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = {$seller['id']}")->fetch_assoc()['count'];

// Get total earnings
$earnings = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE seller_id = {$seller['id']} AND status != 'cancelled'")->fetch_assoc()['total'];

// Get rating
$rating_data = $mysqli->query("SELECT COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(*) as review_count 
                               FROM reviews r 
                               JOIN products p ON p.id = r.product_id 
                               WHERE p.seller_id = {$seller['id']} AND r.status = 'approved'")->fetch_assoc();
$rating = round($rating_data['avg_rating'] ?? 0, 1);
$review_count = $rating_data['review_count'] ?? 0;

// Get shop views (if table exists)
$shop_views = 0;
$views_check = $mysqli->query("SHOW TABLES LIKE 'shop_views'");
if ($views_check && $views_check->num_rows > 0) {
    $views_result = $mysqli->query("SELECT COUNT(*) as count FROM shop_views WHERE seller_id = {$seller['id']}");
    if ($views_result) {
        $shop_views = $views_result->fetch_assoc()['count'] ?? 0;
    }
}
?>

<style>
/* ============================================
   MY SHOP PAGE - MODERN CLEAN DESIGN
============================================ */

/* ---------- MAIN CONTAINER ---------- */
.shop-wrapper {
    display: flex;
    gap: 25px;
}

.shop-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.shop-content {
    flex: 1;
}

/* ---------- SHOP PREVIEW CARD ---------- */
.shop-preview-card {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

/* ---------- SHOP HEADER ---------- */
.shop-header {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
    margin-bottom: 20px;
}

/* Shop Logo */
.shop-logo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #e5e7eb;
    flex-shrink: 0;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.shop-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.shop-logo .no-logo {
    font-size: 2.5rem;
    color: #9ca3af;
}

/* Shop Title */
.shop-title {
    flex: 1;
}

.shop-title h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.shop-title .owner {
    color: #6b7280;
    font-size: 0.9rem;
}

.shop-title .status-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 6px;
}

.status-verified { background: #d1fae5; color: #059669; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-rejected { background: #fee2e2; color: #dc2626; }

/* ---------- SHOP STATS ---------- */
.shop-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.shop-stat {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
}

.shop-stat:hover {
    background: #eff6ff;
    transform: translateY(-2px);
}

.shop-stat .number {
    font-size: 1.3rem;
    font-weight: 700;
    color: #2563eb;
}

.shop-stat .label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}

/* ---------- SHOP DESCRIPTION ---------- */
.shop-description {
    background: #f8fafc;
    padding: 15px 20px;
    border-radius: 12px;
    margin: 15px 0;
}

.shop-description p {
    margin: 0;
    color: #4b5563;
    line-height: 1.6;
}

/* ---------- SHOP INFO GRID ---------- */
.shop-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 15px;
}

.shop-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 10px;
}

.shop-info-item i {
    color: #2563eb;
    width: 18px;
}

.shop-info-item .label {
    color: #6b7280;
    font-size: 0.8rem;
}

.shop-info-item .value {
    color: #1f2937;
    font-weight: 500;
    font-size: 0.85rem;
}

/* ---------- SHOP ACTIONS ---------- */
.shop-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.btn-edit-shop {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-edit-shop:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    color: #fff;
}

.btn-view-shop {
    background: #f1f5f9;
    color: #1f2937;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-view-shop:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.btn-add-product {
    background: #f59e0b;
    color: #1f2937;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add-product:hover {
    background: #d97706;
    transform: translateY(-2px);
    color: #1f2937;
}

.btn-manage-products {
    background: #10b981;
    color: #fff;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-manage-products:hover {
    background: #059669;
    transform: translateY(-2px);
    color: #fff;
}

/* ---------- PRODUCT STATUS BADGES ---------- */
.product-status-badges {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.status-badge-small {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge-small.approved { background: #d1fae5; color: #059669; }
.status-badge-small.pending { background: #fef3c7; color: #d97706; }
.status-badge-small.rejected { background: #fee2e2; color: #dc2626; }

/* ---------- RESPONSIVE ---------- */
@media (max-width: 992px) {
    .shop-wrapper {
        flex-direction: column;
    }
    
    .shop-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .shop-header {
        flex-direction: column;
        text-align: center;
    }
    
    .shop-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .shop-info-grid {
        grid-template-columns: 1fr;
    }
    
    .shop-actions {
        justify-content: center;
    }
    
    .shop-logo {
        width: 80px;
        height: 80px;
    }
}

@media (max-width: 480px) {
    .shop-stats {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .shop-stat {
        padding: 10px;
    }
    
    .shop-stat .number {
        font-size: 1.1rem;
    }
    
    .shop-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .shop-actions a {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="container py-4">
    <div class="shop-wrapper">
        <!-- Sidebar -->
        <div class="shop-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="shop-content">
            <div class="shop-preview-card">
                
                <!-- ==========================================
                     SHOP HEADER
                ========================================== -->
                <div class="shop-header">
                    <div class="shop-logo">
                        <?php if (!empty($seller['shop_logo']) && file_exists('../uploads/sellers/' . $seller['shop_logo'])): ?>
                            <img src="../uploads/sellers/<?= $seller['shop_logo'] ?>" alt="Shop Logo">
                        <?php else: ?>
                            <div class="no-logo">
                                <i class="fa-solid fa-store"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="shop-title">
                        <h2><?= sanitize($seller['shop_name']) ?></h2>
                        <div class="owner">
                            <i class="fa-regular fa-user"></i> <?= sanitize($seller['user_name']) ?>
                        </div>
                        <span class="status-badge status-<?= $seller['status'] ?>">
                            <i class="fa-regular fa-circle"></i> <?= ucfirst($seller['status']) ?>
                        </span>
                    </div>
                </div>
                
                <!-- ==========================================
                     SHOP STATS
                ========================================== -->
                <div class="shop-stats">
                    <div class="shop-stat">
                        <div class="number"><?= $product_count ?></div>
                        <div class="label">Products</div>
                    </div>
                    <div class="shop-stat">
                        <div class="number"><?= $order_count ?></div>
                        <div class="label">Orders</div>
                    </div>
                    <div class="shop-stat">
                        <div class="number">KSH <?= number_format($earnings) ?></div>
                        <div class="label">Earnings</div>
                    </div>
                    <div class="shop-stat">
                        <div class="number"><?= $rating ?></div>
                        <div class="label">Rating</div>
                    </div>
                </div>
                
                <!-- Product Status Breakdown -->
                <div class="product-status-badges">
                    <span class="status-badge-small approved">
                        <i class="fa-regular fa-circle-check"></i> <?= $product_count ?> Approved
                    </span>
                    <span class="status-badge-small pending">
                        <i class="fa-regular fa-clock"></i> <?= $pending_products ?> Pending
                    </span>
                    <span class="status-badge-small rejected">
                        <i class="fa-solid fa-circle-xmark"></i> <?= $rejected_products ?> Rejected
                    </span>
                    <span class="status-badge-small" style="background:#e0e7ff; color:#4338ca;">
                        <i class="fa-regular fa-star"></i> <?= $review_count ?> Reviews
                    </span>
                </div>
                
                <!-- ==========================================
                     SHOP DESCRIPTION
                ========================================== -->
                <?php if (!empty($seller['description'])): ?>
                    <div class="shop-description">
                        <p><?= nl2br(sanitize($seller['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- ==========================================
                     SHOP INFO GRID
                ========================================== -->
                <div class="shop-info-grid">
                    <div class="shop-info-item">
                        <i class="fa-solid fa-phone"></i>
                        <div>
                            <span class="label">Phone:</span> 
                            <span class="value"><?= sanitize($seller['phone'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <span class="label">Location:</span> 
                            <span class="value"><?= sanitize($seller['location'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-envelope"></i>
                        <div>
                            <span class="label">Email:</span> 
                            <span class="value"><?= sanitize($seller['user_email']) ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-calendar"></i>
                        <div>
                            <span class="label">Member Since:</span> 
                            <span class="value"><?= date('M d, Y', strtotime($seller['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-id-card"></i>
                        <div>
                            <span class="label">Business ID:</span> 
                            <span class="value"><?= sanitize($seller['business_id'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-circle-check"></i>
                        <div>
                            <span class="label">Status:</span> 
                            <span class="value" style="color:#10b981;"><?= ucfirst($seller['status']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- ==========================================
                     SHOP ACTIONS
                ========================================== -->
                <div class="shop-actions">
                    <a href="edit_profile.php" class="btn-edit-shop">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Shop
                    </a>
                    <a href="<?= BASE_URL ?>seller.php?id=<?= $seller['id'] ?>" target="_blank" class="btn-view-shop">
                        <i class="fa-regular fa-eye"></i> View Shop
                    </a>
                    
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>