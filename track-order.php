<?php
$page_title = 'Track Order';
require_once 'includes/header.php';
require_login();

$order_id = intval($_GET['id'] ?? 0);
$order_number = sanitize($_GET['order_number'] ?? '');

// If order number is provided instead of ID
if (!empty($order_number) && $order_id == 0) {
    $order_sql = "SELECT id FROM orders WHERE order_number = ? AND user_id = ?";
    $order_stmt = $mysqli->prepare($order_sql);
    $order_stmt->bind_param('si', $order_number, $_SESSION['user_id']);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $order_data = $order_result->fetch_assoc();
    if ($order_data) {
        $order_id = $order_data['id'];
    }
}

if ($order_id == 0) {
    flash('Please provide an order ID or number to track.', 'warning');
    redirect('orders.php');
}

// Get order details
$sql = "SELECT o.*, s.shop_name, s.shop_logo, s.location as seller_location,
        u.name as seller_name, u.email as seller_email
        FROM orders o
        LEFT JOIN sellers s ON o.seller_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    flash('Order not found.', 'danger');
    redirect('orders.php');
}

// Get order items
$items_sql = "SELECT oi.*, p.name, p.slug, p.price as product_price,
              (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
              FROM order_items oi
              LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ?";
$items_stmt = $mysqli->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result();

// Get tracking updates (this would come from a tracking table in production)
// For now, we'll create a simple timeline based on order status
function getStatusTimeline($order) {
    $timeline = [];
    
    // Order placed
    $timeline[] = [
        'status' => 'Order Placed',
        'icon' => 'fa-cart-plus',
        'color' => 'primary',
        'date' => $order['created_at'],
        'completed' => true
    ];
    
    // Processing
    $timeline[] = [
        'status' => 'Processing',
        'icon' => 'fa-gear',
        'color' => 'info',
        'date' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +2 hours')),
        'completed' => in_array($order['status'], ['processing', 'shipped', 'delivered'])
    ];
    
    // Shipped
    $timeline[] = [
        'status' => 'Shipped',
        'icon' => 'fa-truck',
        'color' => 'warning',
        'date' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +1 day')),
        'completed' => in_array($order['status'], ['shipped', 'delivered'])
    ];
    
    // Delivered
    $timeline[] = [
        'status' => 'Delivered',
        'icon' => 'fa-check-circle',
        'color' => 'success',
        'date' => date('Y-m-d H:i:s', strtotime($order['created_at'] . ' +3 days')),
        'completed' => $order['status'] == 'delivered'
    ];
    
    return $timeline;
}

$timeline = getStatusTimeline($order);
?>

<style>
    .track-wrapper {
        display: flex;
        gap: 25px;
    }
    .track-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .track-content {
        flex: 1;
    }
    
    .track-header {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .track-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    
    .track-header .order-info {
        position: relative;
        z-index: 1;
    }
    
    .track-header h2 {
        font-size: 1.5rem;
        margin: 0 0 10px 0;
    }
    
    .track-header .order-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        font-size: 0.85rem;
        opacity: 0.9;
    }
    
    .track-header .order-meta span {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .track-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    
    .track-card-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f59e0b;
        display: inline-block;
    }
    
    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 40px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e5e7eb;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 30px;
        padding-left: 20px;
    }
    
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    
    .timeline-icon {
        position: absolute;
        left: -32px;
        top: 0;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        z-index: 1;
    }
    
    .timeline-icon.completed {
        background: #10b981;
        color: white;
    }
    
    .timeline-icon.pending {
        background: #e5e7eb;
        color: #6b7280;
    }
    
    .timeline-icon.active {
        background: #2563eb;
        color: white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
        50% { box-shadow: 0 0 0 10px rgba(37, 99, 235, 0); }
    }
    
    .timeline-content {
        background: #f9fafb;
        padding: 12px 16px;
        border-radius: 12px;
    }
    
    .timeline-content .status-title {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 2px;
    }
    
    .timeline-content .status-date {
        font-size: 0.7rem;
        color: #6b7280;
    }
    
    .timeline-content .status-desc {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 5px;
    }
    
    .timeline-item.completed .timeline-content {
        background: #f0fdf4;
        border-left: 3px solid #10b981;
    }
    
    .timeline-item.active .timeline-content {
        background: #eff6ff;
        border-left: 3px solid #2563eb;
    }
    
    .timeline-item.pending .timeline-content {
        opacity: 0.6;
    }
    
    /* Order Items */
    .order-item {
        display: flex;
        gap: 15px;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .order-item:last-child {
        border-bottom: none;
    }
    
    .order-item-image {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 10px;
        background: #f3f4f6;
    }
    
    .order-item-details {
        flex: 1;
    }
    
    .order-item-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 2px;
    }
    
    .order-item-meta {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .order-item-price {
        font-weight: 600;
        color: #2563eb;
        font-size: 0.9rem;
    }
    
    .order-summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .order-summary-row:last-child {
        border-bottom: none;
    }
    
    .order-summary-total {
        font-weight: 700;
        font-size: 1.1rem;
        color: #2563eb;
        border-top: 2px solid #e5e7eb;
        padding-top: 15px;
        margin-top: 5px;
    }
    
    .btn-back {
        display: inline-block;
        padding: 8px 20px;
        background: #f3f4f6;
        color: #374151;
        border-radius: 10px;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    
    .btn-back:hover {
        background: #e5e7eb;
        transform: translateX(-3px);
    }
    
    .btn-track-refresh {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-track-refresh:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    
    .delivery-info {
        background: #fef3c7;
        border-radius: 12px;
        padding: 15px;
        margin-top: 15px;
        border-left: 4px solid #f59e0b;
    }
    
    @media (max-width: 992px) {
        .track-wrapper {
            flex-direction: column;
        }
        .track-sidebar {
            width: 100%;
        }
        .track-header .order-meta {
            flex-direction: column;
            gap: 5px;
        }
        .order-item {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        .timeline {
            padding-left: 30px;
        }
    }
</style>

<div class="container mb-5">
    <div class="track-wrapper">
        <div class="track-sidebar">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="track-content">
            <!-- Header -->
            <div class="track-header">
                <div class="order-info">
                    <h2><i class="fa-solid fa-truck"></i> Track Order</h2>
                    <div class="order-meta">
                        <span><i class="fa-solid fa-hashtag"></i> Order #<?= sanitize($order['order_number']) ?></span>
                        <span><i class="fa-regular fa-calendar"></i> <?= date('F d, Y', strtotime($order['created_at'])) ?></span>
                        <span><i class="fa-solid fa-store"></i> <?= sanitize($order['shop_name'] ?? 'Unknown Seller') ?></span>
                        <span>
                            <span class="order-status status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Tracking Timeline -->
            <div class="track-card">
                <h5 class="track-card-title"><i class="fa-solid fa-clock"></i> Order Progress</h5>
                
                <div class="timeline">
                    <?php 
                    $current_status = $order['status'];
                    $status_flow = ['Order Placed', 'Processing', 'Shipped', 'Delivered'];
                    $current_index = array_search(ucfirst($current_status), $status_flow);
                    if ($current_index === false) $current_index = 0;
                    
                    foreach ($timeline as $index => $step):
                        $step_class = $step['completed'] ? 'completed' : ($index == $current_index ? 'active' : 'pending');
                        $icon_class = $step['completed'] ? 'fa-check' : $step['icon'];
                    ?>
                        <div class="timeline-item <?= $step_class ?>">
                            <div class="timeline-icon <?= $step_class ?>">
                                <i class="fa-solid <?= $icon_class ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="status-title"><?= $step['status'] ?></div>
                                <div class="status-date">
                                    <?php if ($step['completed']): ?>
                                        <?= date('F d, Y h:i A', strtotime($step['date'])) ?>
                                    <?php elseif ($index == $current_index && !$step['completed']): ?>
                                        <span class="text-muted">In progress...</span>
                                    <?php else: ?>
                                        <span class="text-muted">Pending</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($step['completed'] && $step['status'] == 'Delivered'): ?>
                                    <div class="status-desc">
                                        <i class="fa-regular fa-circle-check text-success"></i> Package delivered successfully!
                                    </div>
                                <?php elseif ($index == $current_index && $step['status'] == 'Shipped'): ?>
                                    <div class="status-desc">
                                        <i class="fa-solid fa-truck"></i> Your order is on the way!
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($order['status'] == 'shipped'): ?>
                <div class="delivery-info">
                    <i class="fa-solid fa-location-dot"></i>
                    <strong>Estimated Delivery:</strong> <?= date('F d, Y', strtotime($order['created_at'] . ' +3 days')) ?>
                    <br>
                    <small class="text-muted">Your package is with the courier and will be delivered soon.</small>
                </div>
                <?php elseif ($order['status'] == 'delivered'): ?>
                <div class="delivery-info" style="background: #d1fae5; border-left-color: #10b981;">
                    <i class="fa-solid fa-check-circle text-success"></i>
                    <strong class="text-success">Delivered!</strong>
                    <br>
                    <small class="text-muted">Your order has been successfully delivered. Thank you for shopping with us!</small>
                </div>
                <?php elseif ($order['status'] == 'cancelled'): ?>
                <div class="delivery-info" style="background: #fee2e2; border-left-color: #dc2626;">
                    <i class="fa-solid fa-times-circle text-danger"></i>
                    <strong class="text-danger">Order Cancelled</strong>
                    <br>
                    <small class="text-muted">This order has been cancelled. If you have any questions, please contact support.</small>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Items -->
            <div class="track-card">
                <h5 class="track-card-title"><i class="fa-solid fa-box"></i> Order Items</h5>
                
                <?php while ($item = $order_items->fetch_assoc()): ?>
                    <div class="order-item">
                        <div class="order-item-image" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-image fa-2x text-muted"></i>
                        </div>
                        <div class="order-item-details">
                            <div class="order-item-name"><?= sanitize($item['name'] ?? 'Product') ?></div>
                            <div class="order-item-meta">Quantity: <?= $item['quantity'] ?></div>
                        </div>
                        <div class="order-item-price">KSH <?= number_format($item['unit_price']) ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Order Summary -->
            <div class="track-card">
                <h5 class="track-card-title"><i class="fa-solid fa-receipt"></i> Order Summary</h5>
                
                <div class="order-summary-row">
                    <span>Subtotal</span>
                    <span>KSH <?= number_format($order['total_amount'] - ($order['total_amount'] * 0.16) - 250) ?></span>
                </div>
                <div class="order-summary-row">
                    <span>Shipping</span>
                    <span>KSH 250</span>
                </div>
                <div class="order-summary-row">
                    <span>Tax (16% VAT)</span>
                    <span>KSH <?= number_format($order['total_amount'] * 0.16) ?></span>
                </div>
                <div class="order-summary-row order-summary-total">
                    <span>Total</span>
                    <span>KSH <?= number_format($order['total_amount']) ?></span>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <a href="orders.php" class="btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Back to Orders
                </a>
                <button class="btn-track-refresh" onclick="location.reload()">
                    <i class="fa-solid fa-rotate"></i> Refresh Status
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
// Auto-refresh tracking status every 30 seconds
$(document).ready(function() {
    let refreshInterval = setInterval(function() {
        // Only refresh if page is visible
        if (!document.hidden) {
            $.ajax({
                url: window.location.href,
                method: 'GET',
                success: function(data) {
                    // Update only the status section without full page reload
                    // This is a simple approach - full page reload is simpler
                    location.reload();
                }
            });
        }
    }, 30000); // 30 seconds
    
    // Stop refreshing when page is hidden
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            clearInterval(refreshInterval);
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>