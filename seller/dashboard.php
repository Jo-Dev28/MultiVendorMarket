<?php
$page_title = 'Seller Dashboard';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT s.*, u.name as user_name, u.email as user_email FROM sellers s JOIN users u ON u.id = s.user_id WHERE s.user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();
$seller = $seller_result->fetch_assoc();

if (!$seller) {
    flash('Seller account not found. Please contact support.', 'danger');
    redirect('index.php');
}

// Check subscription status
$subscription_sql = "SELECT * FROM subscriptions WHERE seller_id = ? AND status = 'active' AND expires_at >= CURDATE() ORDER BY expires_at DESC LIMIT 1";
$subscription_stmt = $mysqli->prepare($subscription_sql);
$subscription_stmt->bind_param('i', $seller['id']);
$subscription_stmt->execute();
$active_subscription = $subscription_stmt->get_result()->fetch_assoc();

// Check for pending payment
$pending_payment_sql = "SELECT p.*, o.order_number FROM payments p 
                        JOIN orders o ON o.id = p.order_id 
                        WHERE o.seller_id = ? AND p.status = 'pending' 
                        ORDER BY p.created_at DESC LIMIT 1";
$pending_payment_stmt = $mysqli->prepare($pending_payment_sql);
$pending_payment_stmt->bind_param('i', $seller['id']);
$pending_payment_stmt->execute();
$pending_payment = $pending_payment_stmt->get_result()->fetch_assoc();

// Check if seller needs to subscribe
$needs_subscription = ($seller['status'] === 'verified' && !$active_subscription && !$pending_payment);

// Get statistics
$revenueSql = 'SELECT COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE seller_id = ? AND status != "cancelled"';
$stmt = $mysqli->prepare($revenueSql);
$stmt->bind_param('i', $seller['id']);
$stmt->execute();
$revenueResult = $stmt->get_result();
$revenue = $revenueResult->fetch_assoc()['revenue'];

$productSql = 'SELECT COUNT(*) AS total FROM products WHERE seller_id = ?';
$stmt = $mysqli->prepare($productSql);
$stmt->bind_param('i', $seller['id']);
$stmt->execute();
$productResult = $stmt->get_result();
$totalProducts = $productResult->fetch_assoc()['total'];

$orderSql = 'SELECT COUNT(*) AS total FROM orders WHERE seller_id = ?';
$stmt = $mysqli->prepare($orderSql);
$stmt->bind_param('i', $seller['id']);
$stmt->execute();
$orderResult = $stmt->get_result();
$totalOrders = $orderResult->fetch_assoc()['total'];

$pendingOrdersSql = 'SELECT COUNT(*) AS total FROM orders WHERE seller_id = ? AND status = "pending"';
$stmt = $mysqli->prepare($pendingOrdersSql);
$stmt->bind_param('i', $seller['id']);
$stmt->execute();
$pendingOrderResult = $stmt->get_result();
$pendingOrders = $pendingOrderResult->fetch_assoc()['total'];

// Get recent orders
$recentOrders = $mysqli->prepare("SELECT o.*, u.name as customer_name FROM orders o JOIN users u ON u.id = o.user_id WHERE o.seller_id = ? ORDER BY o.created_at DESC LIMIT 5");
$recentOrders->bind_param('i', $seller['id']);
$recentOrders->execute();
$recentOrdersResult = $recentOrders->get_result();

// Get low stock products
$lowStockProducts = $mysqli->prepare("SELECT id, name, stock FROM products WHERE seller_id = ? AND stock < 10 AND status = 'approved' ORDER BY stock ASC LIMIT 5");
$lowStockProducts->bind_param('i', $seller['id']);
$lowStockProducts->execute();
$lowStockProductsResult = $lowStockProducts->get_result();

// Get product status counts
$approvedProducts = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'approved'")->fetch_assoc()['count'];
$pendingProducts = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'pending'")->fetch_assoc()['count'];
$rejectedProducts = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'rejected'")->fetch_assoc()['count'];

// Get order status counts
$processingOrders = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = {$seller['id']} AND status = 'processing'")->fetch_assoc()['count'];
$shippedOrders = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = {$seller['id']} AND status = 'shipped'")->fetch_assoc()['count'];
$deliveredOrders = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = {$seller['id']} AND status = 'delivered'")->fetch_assoc()['count'];
?>

<style>
/* ============================================
   SELLER DASHBOARD - MODERN DESIGN
============================================ */
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --gray: #6b7280;
    --light-gray: #f3f4f6;
    --white: #ffffff;
    --border: #e5e7eb;
    --shadow: 0 1px 3px rgba(0,0,0,0.08);
    --shadow-hover: 0 8px 25px rgba(0,0,0,0.12);
    --radius: 12px;
}

.seller-dashboard-wrapper {
    display: flex;
    gap: 25px;
    min-height: calc(100vh - 200px);
}

.seller-sidebar-col {
    width: 280px;
    flex-shrink: 0;
}

.seller-main-col {
    flex: 1;
}

/* Welcome Card */
.seller-welcome-card {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    border-radius: 16px;
    padding: 25px 30px;
    margin-bottom: 25px;
    color: white;
    position: relative;
    overflow: hidden;
}

.seller-welcome-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(37,99,235,0.15) 0%, transparent 70%);
    border-radius: 50%;
}

.seller-welcome-card h4 {
    margin: 0 0 4px 0;
    font-size: 1.3rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.seller-welcome-card p {
    margin: 0;
    font-size: 0.85rem;
    opacity: 0.8;
    position: relative;
    z-index: 1;
}

.seller-welcome-card .date-info {
    text-align: right;
    position: relative;
    z-index: 1;
}

.seller-welcome-card .date-info .day {
    font-size: 1.2rem;
    font-weight: 600;
}

.seller-welcome-card .date-info .date {
    font-size: 0.8rem;
    opacity: 0.7;
}

/* Stat Cards */
.seller-stat-card {
    background: white;
    border-radius: var(--radius);
    padding: 18px 20px;
    transition: all 0.3s ease;
    height: 100%;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.seller-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
}

.seller-stat-value {
    font-size: 1.6rem;
    font-weight: 700;
    margin-bottom: 2px;
    color: #1f2937;
}

.seller-stat-label {
    font-size: 0.75rem;
    color: var(--gray);
}

.seller-stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
}

/* Alerts */
.seller-alert {
    border-radius: var(--radius);
    padding: 14px 20px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.seller-alert-subscription {
    background: #fef3c7;
    border-left: 4px solid var(--warning);
}

.seller-alert-payment {
    background: #dbeafe;
    border-left: 4px solid var(--primary);
}

.seller-alert-success {
    background: #d1fae5;
    border-left: 4px solid var(--success);
}

.seller-alert i {
    margin-right: 8px;
}

.seller-alert .btn-alert {
    padding: 6px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.seller-alert .btn-alert:hover {
    transform: translateY(-2px);
}

/* Action Card */
.seller-action-card {
    background: white;
    border-radius: var(--radius);
    padding: 20px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    height: 100%;
}

.seller-action-card .card-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--warning);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.seller-action-card .card-title i {
    color: var(--warning);
}

.seller-action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 15px;
    border: 1px solid var(--border);
    border-radius: 10px;
    transition: all 0.3s ease;
    text-decoration: none;
    color: #374151;
    margin-bottom: 8px;
    font-size: 0.85rem;
}

.seller-action-btn:hover {
    background: var(--light-gray);
    transform: translateX(5px);
    border-color: var(--primary);
    color: var(--primary);
}

.seller-action-btn i:first-child {
    width: 20px;
    font-size: 0.9rem;
    color: var(--primary);
}

.seller-action-btn .arrow {
    margin-left: auto;
    color: var(--gray);
}

.seller-action-btn-subscribe {
    background: linear-gradient(135deg, var(--warning), #d97706);
    color: white;
    border: none;
}

.seller-action-btn-subscribe i:first-child {
    color: white;
}

.seller-action-btn-subscribe:hover {
    background: linear-gradient(135deg, #d97706, #b45309);
    color: white;
    transform: translateX(5px);
}

/* Status Badges */
.seller-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.65rem;
    font-weight: 600;
}

.status-pending { background: #fef3c7; color: #d97706; }
.status-processing { background: #dbeafe; color: #2563eb; }
.status-shipped { background: #e0e7ff; color: #4338ca; }
.status-delivered { background: #d1fae5; color: #059669; }
.status-cancelled { background: #fee2e2; color: #dc2626; }

/* Tables */
.seller-table {
    width: 100%;
    font-size: 0.8rem;
}

.seller-table th,
.seller-table td {
    padding: 8px 5px;
    border-bottom: 1px solid var(--border);
}

.seller-table th {
    text-align: left;
    font-weight: 600;
    color: #4b5563;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.seller-table tr:hover td {
    background: var(--light-gray);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .seller-dashboard-wrapper {
        flex-direction: column;
    }
    .seller-sidebar-col {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr 1fr;
    }
    .seller-welcome-card {
        padding: 18px;
        text-align: center;
    }
    .seller-welcome-card .date-info {
        text-align: center;
        margin-top: 10px;
    }
    .seller-stat-card {
        text-align: center;
    }
    .seller-stat-card .d-flex {
        flex-direction: column;
        align-items: center;
    }
    .seller-stat-icon {
        margin-top: 10px;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    .seller-alert {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="container-fluid">
    <div class="seller-dashboard-wrapper">
        <!-- Sidebar -->
        <div class="seller-sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="seller-main-col">
            <!-- Welcome Card -->
            <div class="seller-welcome-card">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4><i class="fa-regular fa-hand"></i> Welcome, <?= htmlspecialchars($seller['shop_name']) ?>!</h4>
                        <p>Manage your store, track orders, and grow your business.</p>
                        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-top:8px;">
                            <span class="badge" style="background:rgba(255,255,255,0.15); color:#fff; padding:4px 12px;">
                                <i class="fa-regular fa-circle-check"></i> <?= ucfirst($seller['status']) ?>
                            </span>
                            <?php if($active_subscription): ?>
                                <span class="badge" style="background:rgba(16,185,129,0.3); color:#34d399; padding:4px 12px;">
                                    <i class="fa-regular fa-circle-check"></i> Subscription Active
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4 date-info">
                        <div class="day"><?= date('l') ?></div>
                        <div class="date"><?= date('F d, Y') ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Subscription Status Alerts -->
            <?php if ($needs_subscription): ?>
                <div class="seller-alert seller-alert-subscription">
                    <div>
                        <i class="fa-solid fa-store"></i> 
                        <strong>Subscription Required!</strong>
                        <span class="ms-2">Your seller account is approved but you need to subscribe to start selling.</span>
                    </div>
                    <a href="<?= BASE_URL ?>become_seller.php" class="btn-alert" style="background: #f59e0b; color: white;">
                        <i class="fa-solid fa-credit-card"></i> Subscribe Now
                    </a>
                </div>
            <?php elseif ($pending_payment): ?>
                <div class="seller-alert seller-alert-payment">
                    <div>
                        <i class="fa-regular fa-clock"></i> 
                        <strong>Payment Pending Confirmation!</strong>
                        <span class="ms-2">Your subscription payment is awaiting admin confirmation.</span>
                        <div class="mt-1" style="font-size:0.8rem; opacity:0.8;">
                            Order #: <?= htmlspecialchars($pending_payment['order_number']) ?> | Amount: KSH <?= number_format($pending_payment['amount']) ?>
                        </div>
                    </div>
                    <span class="badge" style="background:#f59e0b; color:white;">Pending</span>
                </div>
            <?php elseif ($active_subscription): ?>
                <div class="seller-alert seller-alert-success">
                    <div>
                        <i class="fa-solid fa-check-circle"></i> 
                        <strong>Subscription Active!</strong>
                        <span class="ms-2">Plan: <?= htmlspecialchars($active_subscription['plan_name']) ?> | Expires: <?= date('F d, Y', strtotime($active_subscription['expires_at'])) ?></span>
                    </div>
                    <span class="badge" style="background:#10b981; color:white;">Active</span>
                </div>
            <?php endif; ?>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="seller-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="seller-stat-value">KSH <?= number_format($revenue) ?></div>
                            <div class="seller-stat-label">Total Earnings</div>
                        </div>
                        <div class="seller-stat-icon" style="background:#d1fae5; color:#10b981;">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                    </div>
                </div>
                <div class="seller-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="seller-stat-value"><?= $totalProducts ?></div>
                            <div class="seller-stat-label">Total Products</div>
                            <div style="display:flex; gap:6px; margin-top:4px; font-size:0.7rem;">
                                <span style="color:#10b981;"><?= $approvedProducts ?> Approved</span>
                                <span style="color:#f59e0b;"><?= $pendingProducts ?> Pending</span>
                                <span style="color:#ef4444;"><?= $rejectedProducts ?> Rejected</span>
                            </div>
                        </div>
                        <div class="seller-stat-icon" style="background:#dbeafe; color:#2563eb;">
                            <i class="fa-solid fa-box"></i>
                        </div>
                    </div>
                </div>
                <div class="seller-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="seller-stat-value"><?= $totalOrders ?></div>
                            <div class="seller-stat-label">Total Orders</div>
                            <div style="display:flex; gap:6px; margin-top:4px; font-size:0.7rem;">
                                <span style="color:#f59e0b;"><?= $pendingOrders ?> Pending</span>
                                <span style="color:#2563eb;"><?= $processingOrders ?> Processing</span>
                                <span style="color:#4338ca;"><?= $shippedOrders ?> Shipped</span>
                                <span style="color:#10b981;"><?= $deliveredOrders ?> Delivered</span>
                            </div>
                        </div>
                        <div class="seller-stat-icon" style="background:#fef3c7; color:#f59e0b;">
                            <i class="fa-solid fa-truck"></i>
                        </div>
                    </div>
                </div>
                <div class="seller-stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="seller-stat-value"><?= $pendingOrders ?></div>
                            <div class="seller-stat-label">Pending Orders</div>
                            <div style="font-size:0.7rem; color:#6b7280; margin-top:4px;">
                                <i class="fa-regular fa-clock"></i> Needs your attention
                            </div>
                        </div>
                        <div class="seller-stat-icon" style="background:#fee2e2; color:#ef4444;">
                            <i class="fa-regular fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-3">
                <!-- Quick Actions -->
                <div class="col-lg-6">
                    <div class="seller-action-card">
                        <div class="card-title">
                            <i class="fa-solid fa-bolt"></i> Quick Actions
                        </div>
                        <a href="add_product.php" class="seller-action-btn">
                            <i class="fa-solid fa-plus-circle"></i> 
                            <span>Add New Product</span> 
                            <i class="fa-solid fa-arrow-right arrow"></i>
                        </a>
                        <a href="products.php" class="seller-action-btn">
                            <i class="fa-solid fa-box"></i> 
                            <span>Manage Products</span> 
                            <i class="fa-solid fa-arrow-right arrow"></i>
                        </a>
                        <a href="orders.php" class="seller-action-btn">
                            <i class="fa-solid fa-truck"></i> 
                            <span>View Orders</span> 
                            <i class="fa-solid fa-arrow-right arrow"></i>
                        </a>
                        <a href="edit_profile.php" class="seller-action-btn">
                            <i class="fa-regular fa-user"></i> 
                            <span>Edit Shop Profile</span> 
                            <i class="fa-solid fa-arrow-right arrow"></i>
                        </a>
                        <?php if ($needs_subscription): ?>
                            <a href="<?= BASE_URL ?>become_seller.php" class="seller-action-btn seller-action-btn-subscribe">
                                <i class="fa-solid fa-credit-card"></i> 
                                <span>Subscribe Now</span> 
                                <i class="fa-solid fa-arrow-right arrow"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="col-lg-6">
                    <div class="seller-action-card">
                        <div class="card-title">
                            <i class="fa-solid fa-receipt"></i> Recent Orders
                        </div>
                        <div class="table-responsive">
                            <table class="seller-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentOrdersResult && $recentOrdersResult->num_rows > 0): ?>
                                        <?php while ($order = $recentOrdersResult->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <a href="../receipt.php?id=<?= $order['id'] ?>" style="font-size:0.75rem; color:#2563eb; text-decoration:none;">
                                                    <?= htmlspecialchars($order['order_number']) ?>
                                                </a>
                                            </td>
                                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                            <td>KSH <?= number_format($order['total_amount']) ?></td>
                                            <td>
                                                <span class="seller-status-badge status-<?= $order['status'] ?>">
                                                    <?= ucfirst($order['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-3" style="color:#6b7280;">
                                                <i class="fa-regular fa-inbox"></i> No orders yet
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="orders.php" class="btn" style="width:100%; margin-top:12px; background:#f3f4f6; color:#374151; border-radius:10px; padding:8px; text-align:center; text-decoration:none; font-size:0.8rem; transition:all 0.3s;">
                            View All Orders <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <?php if ($lowStockProductsResult && $lowStockProductsResult->num_rows > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="seller-action-card">
                        <div class="card-title" style="border-bottom-color: #ef4444;">
                            <i class="fa-solid fa-exclamation-triangle" style="color:#ef4444;"></i> Low Stock Alert
                        </div>
                        <div class="table-responsive">
                            <table class="seller-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Current Stock</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $lowStockProductsResult->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td>
                                            <span class="badge" style="background:#ef4444; color:white; padding:4px 10px;">
                                                <?= $product['stock'] ?> left
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn" style="background:#fef3c7; color:#d97706; padding:4px 12px; border-radius:6px; font-size:0.7rem; text-decoration:none;">
                                                <i class="fa-solid fa-pen"></i> Restock
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Seller Info Card -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="seller-action-card">
                        <div class="card-title">
                            <i class="fa-regular fa-circle-info"></i> Shop Information
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div style="font-size:0.75rem; color:#6b7280;">Shop Name</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($seller['shop_name']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <div style="font-size:0.75rem; color:#6b7280;">Owner</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($seller['user_name']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <div style="font-size:0.75rem; color:#6b7280;">Email</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($seller['user_email']) ?></div>
                            </div>
                            <div class="col-md-3">
                                <div style="font-size:0.75rem; color:#6b7280;">Phone</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($seller['phone'] ?? 'N/A') ?></div>
                            </div>
                            <div class="col-md-3">
                                <div style="font-size:0.75rem; color:#6b7280;">Status</div>
                                <div>
                                    <span class="badge" style="background:<?= $seller['status'] == 'verified' ? '#10b981' : '#f59e0b' ?>; color:white; padding:4px 12px;">
                                        <?= ucfirst($seller['status']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div style="font-size:0.75rem; color:#6b7280;">Member Since</div>
                                <div style="font-weight:600;"><?= date('M d, Y', strtotime($seller['created_at'])) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>