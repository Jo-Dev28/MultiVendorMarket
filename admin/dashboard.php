<?php
$page_title = 'Dashboard';
require_once '../includes/header.php';

// Check if user is admin
if (($user['role'] ?? '') !== 'admin') {
    flash('Access denied. Admin only.', 'danger');
    redirect('index.php');
}

// ============================================
// SAFE QUERY FUNCTION WITH TABLE CHECK
// ============================================
function safe_count($mysqli, $table, $where = '') {
    // Check if table exists first
    $table_check = $mysqli->query("SHOW TABLES LIKE '$table'");
    if (!$table_check || $table_check->num_rows == 0) {
        return 0;
    }
    
    $sql = "SELECT COUNT(*) as count FROM $table";
    if (!empty($where)) {
        $sql .= " WHERE $where";
    }
    $result = $mysqli->query($sql);
    if (!$result) {
        return 0;
    }
    return $result->fetch_assoc()['count'] ?? 0;
}

// ============================================
// GET ALL STATS WITH SAFE CHECKS
// ============================================
$stats = [];

// Total users
$stats['total_users'] = safe_count($mysqli, 'users');

// Total sellers
$stats['total_sellers'] = safe_count($mysqli, 'sellers');

// Total products
$stats['total_products'] = safe_count($mysqli, 'products');

// Total orders
$stats['total_orders'] = safe_count($mysqli, 'orders');

// Total revenue
$result = $mysqli->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
$stats['total_revenue'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;

// Pending sellers
$stats['pending_sellers'] = safe_count($mysqli, 'sellers', "status = 'pending'");

// Pending products
$stats['pending_products'] = safe_count($mysqli, 'products', "status = 'pending'");

// Pending reviews (check if table exists)
$stats['pending_reviews'] = safe_count($mysqli, 'reviews', "status = 'pending'");

// Unread contacts (check if table exists and has is_read column)
$stats['unread_contacts'] = 0;
$table_check = $mysqli->query("SHOW TABLES LIKE 'contacts'");
if ($table_check && $table_check->num_rows > 0) {
    $col_check = $mysqli->query("SHOW COLUMNS FROM contacts LIKE 'is_read'");
    if ($col_check && $col_check->num_rows > 0) {
        $result = $mysqli->query("SELECT COUNT(*) as count FROM contacts WHERE is_read = 0 OR is_read IS NULL");
        $stats['unread_contacts'] = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
    } else {
        $stats['unread_contacts'] = safe_count($mysqli, 'contacts');
    }
}

// Open support tickets (check if table exists)
$stats['open_support'] = safe_count($mysqli, 'support_tickets', "status IN ('open', 'in-progress')");

// Pending blog comments (check if table exists)
$stats['pending_comments'] = safe_count($mysqli, 'blog_comments', "status = 'pending'");

// Pending payments (check if table exists)
$stats['pending_payments'] = safe_count($mysqli, 'payments', "status = 'pending'");

// New users (last 7 days)
$stats['new_users'] = 0;
$result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
if ($result) {
    $stats['new_users'] = $result->fetch_assoc()['count'] ?? 0;
}

// Unread chats (check if table exists and has is_read column)
$stats['unread_chats'] = 0;
$table_check = $mysqli->query("SHOW TABLES LIKE 'chats'");
if ($table_check && $table_check->num_rows > 0) {
    $col_check = $mysqli->query("SHOW COLUMNS FROM chats LIKE 'is_read'");
    if ($col_check && $col_check->num_rows > 0) {
        $result = $mysqli->query("SELECT COUNT(*) as count FROM chats WHERE is_read = 0 AND sender = 'user'");
        if ($result) {
            $stats['unread_chats'] = $result->fetch_assoc()['count'] ?? 0;
        }
    }
}

// Total notifications
$stats['total_notifications'] = $stats['pending_sellers'] + $stats['pending_products'] + 
                                $stats['pending_reviews'] + $stats['unread_contacts'] + 
                                $stats['open_support'] + $stats['pending_comments'] + 
                                $stats['pending_payments'] + $stats['unread_chats'];

// ============================================
// GET RECENT DATA WITH SAFE CHECKS
// ============================================

// Recent orders
$recent_orders = null;
$table_check = $mysqli->query("SHOW TABLES LIKE 'orders'");
if ($table_check && $table_check->num_rows > 0) {
    $recent_orders = $mysqli->query("SELECT o.*, u.name as user_name, s.shop_name 
                                     FROM orders o
                                     JOIN users u ON u.id = o.user_id
                                     LEFT JOIN sellers s ON s.id = o.seller_id
                                     ORDER BY o.created_at DESC LIMIT 5");
}

// Recent users
$recent_users = $mysqli->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Monthly sales data for chart
$monthly_sales = $mysqli->query("SELECT MONTH(created_at) as month, SUM(total_amount) as total 
                                  FROM orders 
                                  WHERE YEAR(created_at) = YEAR(NOW()) AND status != 'cancelled'
                                  GROUP BY MONTH(created_at)");

$sales_data = array_fill(0, 12, 0);
if ($monthly_sales) {
    while ($row = $monthly_sales->fetch_assoc()) {
        $sales_data[$row['month'] - 1] = $row['total'];
    }
}

// Get top selling products
$top_products = null;
$table_check = $mysqli->query("SHOW TABLES LIKE 'order_items'");
if ($table_check && $table_check->num_rows > 0) {
    $top_products = $mysqli->query("SELECT p.id, p.name, p.price, SUM(oi.quantity) as total_sold
                                    FROM order_items oi
                                    JOIN products p ON p.id = oi.product_id
                                    GROUP BY p.id
                                    ORDER BY total_sold DESC
                                    LIMIT 5");
}

// Get recent activity
$recent_activity = $mysqli->query("(SELECT 'user' as type, id, name as title, created_at FROM users ORDER BY created_at DESC LIMIT 3)
                                   UNION ALL
                                   (SELECT 'seller' as type, id, shop_name as title, created_at FROM sellers ORDER BY created_at DESC LIMIT 3)
                                   UNION ALL
                                   (SELECT 'order' as type, id, order_number as title, created_at FROM orders ORDER BY created_at DESC LIMIT 3)
                                   ORDER BY created_at DESC LIMIT 10");
?>

<style>
    .admin-content-wrapper {
        display: flex;
        gap: 25px;
        min-height: calc(100vh - 200px);
    }
    
    .admin-sidebar-col {
        width: 280px;
        flex-shrink: 0;
    }
    
    .admin-main-col {
        flex: 1;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        text-decoration: none;
        color: inherit;
    }
    
    .stat-card .notification-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #ef4444;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 10px;
        border-radius: 50px;
        animation: pulse 2s infinite;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .stat-value {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 0;
    }
    
    .stat-label {
        font-size: 0.85rem;
        color: #6b7280;
        margin-top: 5px;
    }
    
    .dashboard-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 25px;
    }
    
    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f59e0b;
        display: inline-block;
    }
    
    .activity-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .activity-item:last-child {
        border-bottom: none;
    }
    
    .activity-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .activity-icon.user { background: #dbeafe; color: #2563eb; }
    .activity-icon.seller { background: #fef3c7; color: #f59e0b; }
    .activity-icon.order { background: #d1fae5; color: #10b981; }
    
    .activity-details {
        flex: 1;
    }
    
    .activity-title {
        font-weight: 500;
        color: #1f2937;
    }
    
    .activity-time {
        font-size: 0.7rem;
        color: #6b7280;
    }
    
    .top-product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .top-product-item:last-child {
        border-bottom: none;
    }
    
    .product-rank {
        width: 30px;
        height: 30px;
        background: #f3f4f6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.8rem;
    }
    
    .hover-shadow {
        transition: all 0.3s ease;
    }
    .hover-shadow:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-color: #2563eb !important;
    }
    .transition {
        transition: all 0.3s ease;
    }
    .text-purple { color: #8b5cf6; }
    .text-cyan { color: #06b6d4; }
    .text-pink { color: #ec4899; }
    
    @media (max-width: 992px) {
        .admin-content-wrapper {
            flex-direction: column;
        }
        .admin-sidebar-col {
            width: 100%;
        }
    }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <!-- Dashboard Sidebar -->
        <div class="admin-sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main-col">
            <!-- Welcome Section -->
            <div class="dashboard-card" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; margin-bottom: 25px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-1" style="color: white;">Welcome back, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>!</h3>
                        <p class="mb-0 opacity-75">Here's what's happening with your marketplace today.</p>
                        <?php if ($stats['total_notifications'] > 0): ?>
                            <div class="mt-2">
                                <span class="badge" style="background: rgba(255,255,255,0.2); color: white; padding: 6px 14px; border-radius: 50px;">
                                    <i class="fa-regular fa-bell"></i> 
                                    <?= $stats['total_notifications'] ?> new notification<?= $stats['total_notifications'] > 1 ? 's' : '' ?> 
                                    awaiting your attention
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-end">
                        <div class="fs-1"><?= date('F d, Y') ?></div>
                        <small><?= date('l') ?></small>
                    </div>
                </div>
            </div>
            
            <!-- Stats Row with Notification Badges -->
            <div class="row g-4 mb-4">
                <!-- Users Card -->
                <div class="col-md-3">
                    <a href="users.php" class="stat-card">
                        <?php if ($stats['new_users'] > 0): ?>
                            <span class="notification-badge">+<?= $stats['new_users'] ?> new</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
                                <div class="stat-label">Total Users</div>
                                <?php if ($stats['new_users'] > 0): ?>
                                    <small class="text-success"><i class="fa-solid fa-user-plus"></i> <?= $stats['new_users'] ?> new this week</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Sellers Card -->
                <div class="col-md-3">
                    <a href="sellers.php" class="stat-card">
                        <?php if ($stats['pending_sellers'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pending_sellers'] ?> pending</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['total_sellers']) ?></div>
                                <div class="stat-label">Total Sellers</div>
                                <?php if ($stats['pending_sellers'] > 0): ?>
                                    <small class="text-warning"><i class="fa-solid fa-clock"></i> <?= $stats['pending_sellers'] ?> pending approval</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-store"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Products Card -->
                <div class="col-md-3">
                    <a href="products.php" class="stat-card">
                        <?php if ($stats['pending_products'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pending_products'] ?> pending</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
                                <div class="stat-label">Total Products</div>
                                <?php if ($stats['pending_products'] > 0): ?>
                                    <small class="text-warning"><i class="fa-solid fa-hourglass-half"></i> <?= $stats['pending_products'] ?> pending approval</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-box"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Revenue Card -->
                <div class="col-md-3">
                    <div class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value">KSH <?= number_format($stats['total_revenue']) ?></div>
                                <div class="stat-label">Total Revenue</div>
                            </div>
                            <div class="stat-icon bg-info bg-opacity-10 text-info">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Second Stats Row with More Badges -->
            <div class="row g-4 mb-4">
                <!-- Orders Card -->
                <div class="col-md-3">
                    <a href="orders.php" class="stat-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
                                <div class="stat-label">Total Orders</div>
                            </div>
                            <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                                <i class="fa-solid fa-truck"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Reviews Card -->
                <div class="col-md-3">
                    <a href="reviews.php" class="stat-card">
                        <?php if ($stats['pending_reviews'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pending_reviews'] ?> pending</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['pending_reviews']) ?></div>
                                <div class="stat-label">Pending Reviews</div>
                                <?php if ($stats['pending_reviews'] > 0): ?>
                                    <small class="text-warning"><i class="fa-solid fa-clock"></i> <?= $stats['pending_reviews'] ?> pending</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon bg-cyan bg-opacity-10 text-cyan" style="background: rgba(6, 182, 212, 0.1); color: #06b6d4;">
                                <i class="fa-solid fa-star"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Contacts Card -->
                <div class="col-md-3">
                    <a href="contacts.php" class="stat-card">
                        <?php if ($stats['unread_contacts'] > 0): ?>
                            <span class="notification-badge"><?= $stats['unread_contacts'] ?> unread</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['unread_contacts']) ?></div>
                                <div class="stat-label">Contact Messages</div>
                                <?php if ($stats['unread_contacts'] > 0): ?>
                                    <small class="text-danger"><i class="fa-regular fa-envelope"></i> <?= $stats['unread_contacts'] ?> unread</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon bg-pink bg-opacity-10 text-pink" style="background: rgba(236, 72, 153, 0.1); color: #ec4899;">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Support Tickets Card -->
                <div class="col-md-3">
                    <a href="support.php" class="stat-card">
                        <?php if ($stats['open_support'] > 0): ?>
                            <span class="notification-badge"><?= $stats['open_support'] ?> open</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['open_support']) ?></div>
                                <div class="stat-label">Support Tickets</div>
                                <?php if ($stats['open_support'] > 0): ?>
                                    <small class="text-danger"><i class="fa-solid fa-headset"></i> <?= $stats['open_support'] ?> open tickets</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon bg-purple bg-opacity-10 text-purple" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                <i class="fa-solid fa-headset"></i>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <!-- Third Stats Row -->
            <div class="row g-4 mb-4">
                <!-- Blog Comments Card -->
                <div class="col-md-3">
                    <a href="blog.php" class="stat-card">
                        <?php if ($stats['pending_comments'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pending_comments'] ?> comments</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['pending_comments']) ?></div>
                                <div class="stat-label">Blog Comments</div>
                                <?php if ($stats['pending_comments'] > 0): ?>
                                    <small class="text-warning"><i class="fa-regular fa-comment"></i> <?= $stats['pending_comments'] ?> pending</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon" style="background: rgba(37, 99, 235, 0.1); color: #2563eb;">
                                <i class="fa-regular fa-comment"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Payments Card -->
                <div class="col-md-3">
                    <a href="payments.php" class="stat-card">
                        <?php if ($stats['pending_payments'] > 0): ?>
                            <span class="notification-badge"><?= $stats['pending_payments'] ?> pending</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['pending_payments']) ?></div>
                                <div class="stat-label">Pending Payments</div>
                                <?php if ($stats['pending_payments'] > 0): ?>
                                    <small class="text-warning"><i class="fa-regular fa-clock"></i> <?= $stats['pending_payments'] ?> pending</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                                <i class="fa-solid fa-credit-card"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Chats Card -->
                <div class="col-md-3">
                    <a href="chats.php" class="stat-card">
                        <?php if ($stats['unread_chats'] > 0): ?>
                            <span class="notification-badge"><?= $stats['unread_chats'] ?> new</span>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value"><?= number_format($stats['unread_chats']) ?></div>
                                <div class="stat-label">Unread Chats</div>
                                <?php if ($stats['unread_chats'] > 0): ?>
                                    <small class="text-danger"><i class="fa-regular fa-message"></i> <?= $stats['unread_chats'] ?> unread</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                                <i class="fa-regular fa-message"></i>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Total Notifications Card -->
                <div class="col-md-3">
                    <div class="stat-card" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white;">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="stat-value" style="color: white;"><?= number_format($stats['total_notifications']) ?></div>
                                <div class="stat-label" style="color: rgba(255,255,255,0.7);">Total Notifications</div>
                                <?php if ($stats['total_notifications'] > 0): ?>
                                    <small style="color: #fbbf24;"><i class="fa-regular fa-bell"></i> Needs your attention</small>
                                <?php else: ?>
                                    <small style="color: #34d399;"><i class="fa-solid fa-check-circle"></i> All clear</small>
                                <?php endif; ?>
                            </div>
                            <div class="stat-icon" style="background: rgba(255,255,255,0.1); color: #fbbf24;">
                                <i class="fa-regular fa-bell"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts and Recent Data -->
            <div class="row g-4">
                <!-- Sales Chart -->
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <h5 class="card-title"><i class="fa-solid fa-chart-line"></i> Monthly Sales (<?= date('Y') ?>)</h5>
                        <canvas id="salesChart" height="250"></canvas>
                    </div>
                </div>
                
                <!-- Top Products -->
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <h5 class="card-title"><i class="fa-solid fa-trophy"></i> Top Selling Products</h5>
                        <div class="top-products-list">
                            <?php if ($top_products && $top_products->num_rows > 0): ?>
                                <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                                    <div class="top-product-item">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="product-rank"><?= $rank++ ?></div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($product['name']) ?></div>
                                                <small class="text-muted">KSH <?= number_format($product['price']) ?></small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-bold"><?= $product['total_sold'] ?></div>
                                            <small class="text-muted">sold</small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">No products sold yet.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders and Recent Activity -->
            <div class="row g-4 mt-2">
                <!-- Recent Orders -->
                <div class="col-lg-7">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0"><i class="fa-solid fa-receipt"></i> Recent Orders</h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
                                        <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                            <tr>
                                                <td><strong><?= $order['order_number'] ?></strong></td>
                                                <td><?= htmlspecialchars($order['user_name']) ?></td>
                                                <td>KSH <?= number_format($order['total_amount']) ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    if ($order['status'] == 'pending') $status_class = 'bg-warning';
                                                    elseif ($order['status'] == 'processing') $status_class = 'bg-info';
                                                    elseif ($order['status'] == 'shipped') $status_class = 'bg-primary';
                                                    elseif ($order['status'] == 'delivered') $status_class = 'bg-success';
                                                    elseif ($order['status'] == 'cancelled') $status_class = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?= $status_class ?>"><?= ucfirst($order['status']) ?></span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No orders yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="col-lg-5">
                    <div class="dashboard-card">
                        <h5 class="card-title"><i class="fa-solid fa-clock"></i> Recent Activity</h5>
                        <div class="activity-list">
                            <?php if ($recent_activity && $recent_activity->num_rows > 0): ?>
                                <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon <?= $activity['type'] ?>">
                                            <i class="fa-solid fa-<?= $activity['type'] == 'user' ? 'user' : ($activity['type'] == 'seller' ? 'store' : 'truck') ?>"></i>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title">
                                                <?php if ($activity['type'] == 'user'): ?>
                                                    New user registered: <?= htmlspecialchars($activity['title']) ?>
                                                <?php elseif ($activity['type'] == 'seller'): ?>
                                                    New seller application: <?= htmlspecialchars($activity['title']) ?>
                                                <?php else: ?>
                                                    New order placed: #<?= htmlspecialchars($activity['title']) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="activity-time"><?= time_ago($activity['created_at']) ?></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-4 text-muted">No recent activity.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="dashboard-card">
                        <h5 class="card-title"><i class="fa-solid fa-bolt"></i> Quick Actions</h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="users.php" class="text-decoration-none">
                                    <div class="text-center p-3 border rounded-3 hover-shadow transition">
                                        <i class="fa-solid fa-users fa-2x text-primary mb-2 d-block"></i>
                                        <span class="text-dark">Manage Users</span>
                                        <?php if ($stats['new_users'] > 0): ?>
                                            <span class="badge bg-success d-block mt-1"><?= $stats['new_users'] ?> new</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="sellers.php" class="text-decoration-none">
                                    <div class="text-center p-3 border rounded-3 hover-shadow transition">
                                        <i class="fa-solid fa-store fa-2x text-success mb-2 d-block"></i>
                                        <span class="text-dark">Manage Sellers</span>
                                        <?php if ($stats['pending_sellers'] > 0): ?>
                                            <span class="badge bg-warning d-block mt-1"><?= $stats['pending_sellers'] ?> pending</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="products.php" class="text-decoration-none">
                                    <div class="text-center p-3 border rounded-3 hover-shadow transition">
                                        <i class="fa-solid fa-box fa-2x text-warning mb-2 d-block"></i>
                                        <span class="text-dark">Manage Products</span>
                                        <?php if ($stats['pending_products'] > 0): ?>
                                            <span class="badge bg-warning d-block mt-1"><?= $stats['pending_products'] ?> pending</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="categories.php" class="text-decoration-none">
                                    <div class="text-center p-3 border rounded-3 hover-shadow transition">
                                        <i class="fa-solid fa-folder fa-2x text-info mb-2 d-block"></i>
                                        <span class="text-dark">Manage Categories</span>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Sales Chart
const ctx = document.getElementById('salesChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Sales (KSH)',
            data: <?= json_encode($sales_data) ?>,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37, 99, 235, 0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#2563eb',
            pointBorderColor: '#fff',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'KSH ' + context.raw.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'KSH ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Auto-refresh notifications every 30 seconds
setInterval(function() {
    location.reload();
}, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>