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
$revenueSql = 'SELECT COALESCE(SUM(total_amount),0) AS revenue FROM orders WHERE seller_id = ?';
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
?>

<style>
    /* Dashboard specific styles - no header conflict */
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
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-radius: 16px;
        padding: 20px 25px;
        margin-bottom: 25px;
        color: white;
    }
    .seller-welcome-card h4 {
        margin: 0 0 5px 0;
        font-size: 1.2rem;
    }
    .seller-welcome-card p {
        margin: 0;
        font-size: 0.8rem;
        opacity: 0.9;
    }
    
    /* Stat Cards */
    .seller-stat-card {
        background: white;
        border-radius: 16px;
        padding: 15px 20px;
        transition: all 0.3s ease;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .seller-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .seller-stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        margin-bottom: 0;
        color: #1f2937;
    }
    .seller-stat-label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 5px;
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
        border-radius: 12px;
        padding: 12px 20px;
        margin-bottom: 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    .seller-alert-subscription {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
    }
    .seller-alert-payment {
        background: #dbeafe;
        border-left: 4px solid #2563eb;
    }
    .seller-alert-success {
        background: #d1fae5;
        border-left: 4px solid #10b981;
    }
    .seller-alert i {
        margin-right: 8px;
    }
    
    /* Action Card */
    .seller-action-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        height: 100%;
    }
    .seller-action-card h4 {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f59e0b;
        display: inline-block;
    }
    .seller-action-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 15px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        transition: all 0.3s ease;
        text-decoration: none;
        color: #374151;
        margin-bottom: 10px;
        font-size: 0.85rem;
    }
    .seller-action-btn:hover {
        background: #f9fafb;
        transform: translateX(5px);
        border-color: #2563eb;
    }
    .seller-action-btn i {
        width: 22px;
        font-size: 0.9rem;
        color: #2563eb;
    }
    .seller-action-btn-subscribe {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border: none;
    }
    .seller-action-btn-subscribe i {
        color: white;
    }
    .seller-action-btn-subscribe:hover {
        background: linear-gradient(135deg, #d97706, #b45309);
        color: white;
    }
    
    /* Status Badges */
    .seller-status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
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
        border-bottom: 1px solid #e5e7eb;
    }
    .seller-table th {
        text-align: left;
        font-weight: 600;
        color: #4b5563;
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
        .seller-stat-card {
            text-align: center;
        }
        .seller-stat-card .d-flex {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .seller-stat-icon {
            margin-top: 10px;
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
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4>Welcome, <?= htmlspecialchars($seller['shop_name']) ?>!</h4>
                        <p>Seller Dashboard - Manage your store</p>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold"><?= date('F d, Y') ?></div>
                        <small><?= date('l') ?></small>
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
                    <a href="<?= BASE_URL ?>become_seller.php" class="btn-subscribe" style="background: #f59e0b; color: white; padding: 6px 18px; border-radius: 8px; text-decoration: none; font-size: 0.8rem;">
                        <i class="fa-solid fa-credit-card"></i> Subscribe Now
                    </a>
                </div>
            <?php elseif ($pending_payment): ?>
                <div class="seller-alert seller-alert-payment">
                    <div>
                        <i class="fa-regular fa-clock"></i> 
                        <strong>Payment Pending Confirmation!</strong>
                        <span class="ms-2">Your subscription payment is awaiting admin confirmation.</span>
                        <div class="mt-1"><small>Order #: <?= htmlspecialchars($pending_payment['order_number']) ?> | Amount: KSH <?= number_format($pending_payment['amount']) ?></small></div>
                    </div>
                    <span class="badge bg-warning">Pending</span>
                </div>
            <?php elseif ($active_subscription): ?>
                <div class="seller-alert seller-alert-success">
                    <div>
                        <i class="fa-solid fa-check-circle"></i> 
                        <strong>Subscription Active!</strong>
                        <span class="ms-2">Plan: <?= htmlspecialchars($active_subscription['plan_name']) ?> | Expires: <?= date('F d, Y', strtotime($active_subscription['expires_at'])) ?></span>
                    </div>
                    <span class="badge bg-success">Active</span>
                </div>
            <?php endif; ?>
            
            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-6">
                    <div class="seller-stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="seller-stat-value">KSH <?= number_format($revenue) ?></div>
                                <div class="seller-stat-label">Total Earnings</div>
                            </div>
                            <div class="seller-stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="seller-stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="seller-stat-value"><?= $totalProducts ?></div>
                                <div class="seller-stat-label">Total Products</div>
                            </div>
                            <div class="seller-stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="seller-stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="seller-stat-value"><?= $totalOrders ?></div>
                                <div class="seller-stat-label">Total Orders</div>
                            </div>
                            <div class="seller-stat-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-truck"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="seller-stat-card">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="seller-stat-value"><?= $pendingOrders ?></div>
                                <div class="seller-stat-label">Pending Orders</div>
                            </div>
                            <div class="seller-stat-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fa-regular fa-clock"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row g-3">
                <!-- Quick Actions -->
                <div class="col-lg-6">
                    <div class="seller-action-card">
                        <h4><i class="fa-solid fa-bolt"></i> Quick Actions</h4>
                        <a href="add_product.php" class="seller-action-btn">
                            <i class="fa-solid fa-plus-circle"></i> 
                            <span>Add New Product</span> 
                            <i class="fa-solid fa-arrow-right ms-auto"></i>
                        </a>
                        <a href="products.php" class="seller-action-btn">
                            <i class="fa-solid fa-box"></i> 
                            <span>Manage Products</span> 
                            <i class="fa-solid fa-arrow-right ms-auto"></i>
                        </a>
                        <a href="orders.php" class="seller-action-btn">
                            <i class="fa-solid fa-truck"></i> 
                            <span>View Orders</span> 
                            <i class="fa-solid fa-arrow-right ms-auto"></i>
                        </a>
                        <?php if ($needs_subscription): ?>
                            <a href="<?= BASE_URL ?>become_seller.php" class="seller-action-btn seller-action-btn-subscribe">
                                <i class="fa-solid fa-credit-card"></i> 
                                <span>Subscribe Now</span> 
                                <i class="fa-solid fa-arrow-right ms-auto"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="col-lg-6">
                    <div class="seller-action-card">
                        <h4><i class="fa-solid fa-receipt"></i> Recent Orders</h4>
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
                                            <td><a href="../receipt.php?id=<?= $order['id'] ?>" style="font-size: 0.75rem;"><?= htmlspecialchars($order['order_number']) ?></a></td>
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
                                            <td colspan="4" class="text-center py-3">No orders yet</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="orders.php" class="btn btn-outline-primary btn-sm w-100 mt-3">View All Orders</a>
                    </div>
                </div>
            </div>
            
            <!-- Low Stock Alert -->
            <?php if ($lowStockProductsResult && $lowStockProductsResult->num_rows > 0): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="seller-action-card">
                        <h4><i class="fa-solid fa-exclamation-triangle"></i> Low Stock Alert</h4>
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
                                        <td><span class="badge bg-danger"><?= $product['stock'] ?> left</span></td>
                                        <td><a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-warning">Restock</a></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>