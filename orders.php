<?php
$page_title = 'My Orders';
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];

// Handle order cancellation
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (csrf_validate($csrf_token)) {
        $check_sql = "SELECT status FROM orders WHERE id = ? AND user_id = ?";
        $check_stmt = $mysqli->prepare($check_sql);
        $check_stmt->bind_param('ii', $order_id, $user_id);
        $check_stmt->execute();
        $order_check = $check_stmt->get_result()->fetch_assoc();
        
        if ($order_check && ($order_check['status'] == 'pending' || $order_check['status'] == 'processing')) {
            $update_sql = "UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?";
            $update_stmt = $mysqli->prepare($update_sql);
            $update_stmt->bind_param('ii', $order_id, $user_id);
            if ($update_stmt->execute()) {
                flash('Order cancelled successfully.', 'success');
            } else {
                flash('Failed to cancel order.', 'danger');
            }
        } else {
            flash('This order cannot be cancelled.', 'danger');
        }
    } else {
        flash('Invalid security token.', 'danger');
    }
    redirect('orders.php');
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 5;
$offset = ($page - 1) * $limit;

// Get total orders count
$count_sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$count_stmt = $mysqli->prepare($count_sql);
$count_stmt->bind_param('i', $user_id);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders with pagination
$sql = 'SELECT o.*, s.shop_name, s.shop_logo,
        s.id as seller_id, s.location as seller_location,
        u.name as seller_owner, u.email as seller_email, u.phone as seller_phone,
        s.status as seller_status
        FROM orders o
        LEFT JOIN sellers s ON o.seller_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?';
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('iii', $user_id, $limit, $offset);
$stmt->execute();
$orders = $stmt->get_result();
?>

<style>
    .orders-wrapper {
        display: flex;
        gap: 25px;
    }
    .orders-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .orders-content {
        flex: 1;
    }
    
    .order-card {
        background: white;
        border-radius: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #f3f4f6;
    }
    
    .order-card:hover {
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }
    
    .order-header {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .order-number {
        font-size: 0.95rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .order-number i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .order-date {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .order-status {
        padding: 4px 14px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-processing { background: #dbeafe; color: #2563eb; }
    .status-shipped { background: #e0e7ff; color: #4338ca; }
    .status-delivered { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    
    .order-body {
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .seller-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .seller-avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        font-size: 1.2rem;
    }
    
    .seller-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 2px;
    }
    
    .seller-location {
        font-size: 0.7rem;
        color: #6b7280;
    }
    
    .order-amount {
        text-align: right;
    }
    
    .amount-label {
        font-size: 0.7rem;
        color: #6b7280;
        display: block;
    }
    
    .amount-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #2563eb;
    }
    
    .order-footer {
        padding: 12px 20px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .order-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    
    .btn-view {
        padding: 8px 18px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }
    
    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        color: white;
    }
    
    .btn-cancel {
        padding: 8px 18px;
        background: #fee2e2;
        color: #dc2626;
        border: none;
        border-radius: 10px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-cancel:hover {
        background: #dc2626;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-cancel:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-track {
        padding: 8px 18px;
        background: transparent;
        color: #2563eb;
        border: 1px solid #2563eb;
        border-radius: 10px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-track:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Receipt Button */
    .btn-receipt {
        padding: 8px 18px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-receipt:hover {
        background: #059669;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16,185,129,0.3);
        color: white;
    }
    
    .empty-orders {
        text-align: center;
        padding: 60px 40px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .empty-orders i {
        font-size: 80px;
        color: #9ca3af;
        margin-bottom: 20px;
    }
    
    .empty-orders h3 {
        font-size: 1.5rem;
        color: #1f2937;
        margin-bottom: 10px;
    }
    
    .empty-orders p {
        color: #6b7280;
        margin-bottom: 25px;
    }
    
    .shop-now-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 12px 30px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .shop-now-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        color: white;
    }
    
    .status-timeline {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 10px 0;
    }
    
    .status-step {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.7rem;
        color: #9ca3af;
    }
    
    .status-step.active {
        color: #2563eb;
        font-weight: 600;
    }
    
    .status-step.completed {
        color: #10b981;
    }
    
    .status-line {
        width: 30px;
        height: 2px;
        background: #e5e7eb;
    }
    
    .status-line.completed {
        background: #10b981;
    }
    
    /* Modal Styles */
    .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 20px 25px;
        border: none;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .modal-body {
        padding: 25px;
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .modal-section {
        margin-bottom: 25px;
    }
    
    .modal-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 2px solid #f59e0b;
        display: inline-block;
    }
    
    .modal-info-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .modal-info-label {
        width: 130px;
        font-weight: 500;
        color: #6b7280;
        flex-shrink: 0;
    }
    
    .modal-info-value {
        flex: 1;
        color: #1f2937;
    }
    
    .modal-seller-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        border-left: 4px solid #2563eb;
        margin-top: 10px;
    }
    
    .modal-product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .modal-product-item:last-child {
        border-bottom: none;
    }
    
    .modal-product-name {
        font-weight: 500;
    }
    
    .modal-product-meta {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .modal-product-price {
        font-weight: 600;
        color: #2563eb;
    }
    
    .modal-total-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        font-weight: 600;
    }
    
    .modal-total-row.grand-total {
        border-top: 2px solid #e5e7eb;
        margin-top: 10px;
        padding-top: 15px;
        font-size: 1.1rem;
        color: #2563eb;
    }
    
    .btn-chat-seller {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #f59e0b;
        color: white;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-chat-seller:hover {
        background: #d97706;
        transform: translateY(-2px);
        color: white;
    }
    
    /* Pagination */
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 30px;
        flex-wrap: wrap;
    }
    
    .pagination a, .pagination span {
        padding: 8px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        text-decoration: none;
        color: #374151;
        transition: all 0.3s ease;
    }
    
    .pagination a:hover {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }
    
    .pagination .active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border-color: transparent;
    }
    
    @media (max-width: 992px) {
        .orders-wrapper {
            flex-direction: column;
        }
        .orders-sidebar {
            width: 100%;
        }
        .modal-info-row {
            flex-direction: column;
        }
        .modal-info-label {
            width: 100%;
            margin-bottom: 3px;
        }
    }
    
    @media (max-width: 768px) {
        .order-header {
            flex-direction: column;
            text-align: center;
        }
        .order-body {
            flex-direction: column;
            text-align: center;
        }
        .order-amount {
            text-align: center;
        }
        .order-footer {
            flex-direction: column;
        }
        .order-actions {
            justify-content: center;
            width: 100%;
        }
        .order-actions a, .order-actions button {
            flex: 1;
            justify-content: center;
        }
        .modal-product-item {
            flex-direction: column;
            text-align: center;
            gap: 5px;
        }
    }
</style>

<div class="container mb-5">
    <div class="orders-wrapper">
        <div class="orders-sidebar">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="orders-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-truck"></i> My Orders</h2>
                <span class="text-muted"><?= $total_orders ?> orders total</span>
            </div>
            
            <?php if ($orders->num_rows === 0): ?>
                <div class="empty-orders">
                    <i class="fa-solid fa-receipt"></i>
                    <h3>No Orders Yet</h3>
                    <p>You haven't placed any orders yet. Start shopping to see your orders here.</p>
                    <a href="<?= BASE_URL ?>shop.php" class="shop-now-btn">
                        <i class="fa-solid fa-store"></i> Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <?php while ($order = $orders->fetch_assoc()): 
                    $status_class = '';
                    $status_icon = '';
                    switch($order['status']) {
                        case 'pending': 
                            $status_class = 'status-pending'; 
                            $status_icon = 'fa-regular fa-clock';
                            break;
                        case 'processing': 
                            $status_class = 'status-processing'; 
                            $status_icon = 'fa-solid fa-gear';
                            break;
                        case 'shipped': 
                            $status_class = 'status-shipped'; 
                            $status_icon = 'fa-solid fa-truck';
                            break;
                        case 'delivered': 
                            $status_class = 'status-delivered'; 
                            $status_icon = 'fa-solid fa-check-circle';
                            break;
                        case 'cancelled': 
                            $status_class = 'status-cancelled'; 
                            $status_icon = 'fa-solid fa-times-circle';
                            break;
                        default: 
                            $status_class = 'status-pending';
                            $status_icon = 'fa-regular fa-clock';
                    }
                    
                    $can_cancel = ($order['status'] == 'pending' || $order['status'] == 'processing');
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-number">
                                <i class="fa-solid fa-hashtag"></i> Order #<?= sanitize($order['order_number']) ?>
                            </div>
                            <div class="order-date">
                                <i class="fa-regular fa-calendar"></i> <?= date('F d, Y', strtotime($order['created_at'])) ?>
                            </div>
                            <div class="order-status <?= $status_class ?>">
                                <i class="<?= $status_icon ?>"></i> <?= ucfirst($order['status']) ?>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <div class="seller-info">
                                <div class="seller-avatar">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div>
                                    <div class="seller-name"><?= sanitize($order['shop_name'] ?? 'Unknown Seller') ?></div>
                                    <div class="seller-location">
                                        <i class="fa-regular fa-envelope"></i> Contact seller for inquiries
                                    </div>
                                </div>
                            </div>
                            <div class="order-amount">
                                <span class="amount-label">Total Amount</span>
                                <div class="amount-value">KSH <?= number_format($order['total_amount']) ?></div>
                            </div>
                        </div>
                        
                        <!-- Status Timeline -->
                        <div class="px-3 pb-2">
                            <div class="status-timeline">
                                <?php 
                                $steps = ['pending', 'processing', 'shipped', 'delivered'];
                                $current_index = array_search($order['status'], $steps);
                                if ($current_index === false) $current_index = 0;
                                ?>
                                <?php foreach ($steps as $index => $step): ?>
                                    <div class="status-step <?= $index <= $current_index ? 'completed' : '' ?> <?= $index == $current_index ? 'active' : '' ?>">
                                        <i class="fa-<?= $index <= $current_index ? 'solid' : 'regular' ?> fa-<?= $step == 'pending' ? 'clock' : ($step == 'processing' ? 'gear' : ($step == 'shipped' ? 'truck' : 'check-circle')) ?>"></i>
                                        <?= ucfirst($step) ?>
                                    </div>
                                    <?php if ($index < count($steps) - 1): ?>
                                        <div class="status-line <?= $index < $current_index ? 'completed' : '' ?>"></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <div>
                                <small class="text-muted">
                                    <i class="fa-regular fa-credit-card"></i> Paid via <?= $order['payment_method'] ?>
                                </small>
                            </div>
                            <div class="order-actions">
                                <?php if ($can_cancel): ?>
                                    <a href="orders.php?cancel=1&id=<?= $order['id'] ?>&csrf_token=<?= csrf_token() ?>" 
                                       class="btn-cancel" 
                                       onclick="return confirm('Are you sure you want to cancel this order?')">
                                        <i class="fa-solid fa-times"></i> Cancel Order
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($order['status'] == 'shipped'): ?>
                                    <a href="<?= BASE_URL ?>track-order.php?id=<?= $order['id'] ?>" class="btn-track">
                                        <i class="fa-solid fa-location-dot"></i> Track Order
                                    </a>
                                <?php endif; ?>
                                
                                <button class="btn-view" onclick="viewOrderDetails(<?= $order['id'] ?>)">
                                    <i class="fa-regular fa-eye"></i> View Details
                                </button>
                                
                                <!-- Receipt Button -->
                                <button class="btn-receipt" onclick="window.open('receipt.php?id=<?= $order['id'] ?>', '_blank')">
                                    <i class="fa-solid fa-receipt"></i> Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>"><i class="fa-solid fa-chevron-left"></i> Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>">Next <i class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-file-lines"></i> Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function viewOrderDetails(orderId) {
    var modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
    
    fetch('ajax/get_order_details.php?id=' + orderId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let productsHtml = '';
                let subtotal = 0;
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        let itemTotal = item.quantity * item.unit_price;
                        subtotal += itemTotal;
                        productsHtml += `
                            <div class="modal-product-item">
                                <div>
                                    <div class="modal-product-name">${item.name || 'Product'}</div>
                                    <div class="modal-product-meta">Qty: ${item.quantity}</div>
                                </div>
                                <div class="modal-product-price">KSH ${parseFloat(item.unit_price).toLocaleString()}</div>
                            </div>
                        `;
                    });
                } else {
                    productsHtml = '<div class="text-muted">No products found for this order.</div>';
                }
                
                // Calculate shipping and tax
                let shipping = 250;
                let tax = parseFloat(data.order.total_amount) * 0.16;
                let total = parseFloat(data.order.total_amount);
                
                document.getElementById('orderDetailsBody').innerHTML = `
                    <!-- Order Info -->
                    <div class="modal-section">
                        <h6 class="modal-section-title"><i class="fa-regular fa-circle-info"></i> Order Information</h6>
                        <div class="modal-info-row">
                            <div class="modal-info-label">Order Number</div>
                            <div class="modal-info-value"><strong>${data.order.order_number}</strong></div>
                        </div>
                        <div class="modal-info-row">
                            <div class="modal-info-label">Order Date</div>
                            <div class="modal-info-value">${new Date(data.order.created_at).toLocaleString()}</div>
                        </div>
                        <div class="modal-info-row">
                            <div class="modal-info-label">Status</div>
                            <div class="modal-info-value">
                                <span class="order-status status-${data.order.status}">${data.order.status.toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="modal-info-row">
                            <div class="modal-info-label">Payment Method</div>
                            <div class="modal-info-value">${data.order.payment_method}</div>
                        </div>
                    </div>
                    
                    <!-- Seller Info -->
                    <div class="modal-section">
                        <h6 class="modal-section-title"><i class="fa-solid fa-store"></i> Seller Information</h6>
                        <div class="modal-seller-card">
                            <div class="modal-info-row">
                                <div class="modal-info-label">Shop Name</div>
                                <div class="modal-info-value"><strong>${data.seller.shop_name || 'N/A'}</strong></div>
                            </div>
                            <div class="modal-info-row">
                                <div class="modal-info-label">Owner</div>
                                <div class="modal-info-value">${data.seller.owner_name || 'N/A'}</div>
                            </div>
                            <div class="modal-info-row">
                                <div class="modal-info-label">Email</div>
                                <div class="modal-info-value">${data.seller.email || 'N/A'}</div>
                            </div>
                            <div class="modal-info-row">
                                <div class="modal-info-label">Phone</div>
                                <div class="modal-info-value">${data.seller.phone || 'N/A'}</div>
                            </div>
                            <div class="modal-info-row">
                                <div class="modal-info-label">Location</div>
                                <div class="modal-info-value">${data.seller.location || 'N/A'}</div>
                            </div>
                            <div class="modal-info-row">
                                <div class="modal-info-label">Status</div>
                                <div class="modal-info-value">
                                    <span class="badge ${data.seller.status == 'verified' ? 'bg-success' : 'bg-warning'}">
                                        ${data.seller.status || 'N/A'}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-3">
                                <a href="chat.php?seller_id=${data.seller.id}" class="btn-chat-seller">
                                    <i class="fa-regular fa-message"></i> Contact Seller
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products -->
                    <div class="modal-section">
                        <h6 class="modal-section-title"><i class="fa-solid fa-box"></i> Products Ordered</h6>
                        ${productsHtml}
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="modal-section">
                        <h6 class="modal-section-title"><i class="fa-solid fa-receipt"></i> Order Summary</h6>
                        <div class="modal-info-row">
                            <div class="modal-info-label">Subtotal</div>
                            <div class="modal-info-value">KSH ${subtotal.toLocaleString()}</div>
                        </div>
                        <div class="modal-info-row">
                            <div class="modal-info-label">Shipping</div>
                            <div class="modal-info-value">KSH ${shipping.toLocaleString()}</div>
                        </div>
                        <div class="modal-info-row">
                            <div class="modal-info-label">Tax (16% VAT)</div>
                            <div class="modal-info-value">KSH ${tax.toLocaleString()}</div>
                        </div>
                        <div class="modal-total-row grand-total">
                            <span>Total</span>
                            <span>KSH ${total.toLocaleString()}</span>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="modal-section">
                        <h6 class="modal-section-title"><i class="fa-solid fa-location-dot"></i> Shipping Address</h6>
                        <div class="modal-seller-card" style="border-left-color: #f59e0b;">
                            <p class="mb-0">${data.order.shipping_address}</p>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('orderDetailsBody').innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('orderDetailsBody').innerHTML = `<div class="alert alert-danger">Error loading order details.</div>`;
        });
}

// Auto-hide flash messages
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>

<?php require_once 'includes/footer.php'; ?>