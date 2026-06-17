<?php
$page_title = 'Orders Management';
require_once '../includes/header.php';

if (($user['role'] ?? '') !== 'admin') { flash('Access denied.', 'danger'); redirect('index.php'); }

// Admin can ONLY view orders - NO status updates

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = []; 
$params = []; 
$types = '';

if($search){ 
    $where_clauses[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR s.shop_name LIKE ?)"; 
    $p = "%$search%"; 
    $params = [$p, $p, $p, $p]; 
    $types = 'ssss'; 
}

if($status_filter){ 
    $where_clauses[] = "o.status=?"; 
    $params[] = $status_filter; 
    $types .= 's'; 
}

$where_sql = !empty($where_clauses) ? "WHERE ".implode(" AND ", $where_clauses) : "";

// Get total orders count
$count_sql = "SELECT COUNT(*) as total FROM orders o 
              JOIN users u ON u.id = o.user_id 
              LEFT JOIN sellers s ON o.seller_id = s.id 
              $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
if(!empty($params)) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_orders = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders with detailed information
$sql = "SELECT o.*, 
        u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
        s.id as seller_id, s.shop_name, s.phone as seller_phone, s.location as seller_location,
        su.name as seller_owner_name, su.email as seller_email,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o 
        JOIN users u ON u.id = o.user_id 
        LEFT JOIN sellers s ON o.seller_id = s.id 
        LEFT JOIN users su ON s.user_id = su.id
        $where_sql 
        ORDER BY o.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[] = $limit; 
$params[] = $offset; 
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();

// Get all statuses for filter
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
?>

<style>
    .admin-wrapper { display: flex; gap: 25px; }
    .admin-sidebar { width: 280px; flex-shrink: 0; }
    .admin-content { flex: 1; }
    .filter-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; }
    .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    .data-table th { background: #f8fafc; font-weight: 600; }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-processing { background: #dbeafe; color: #2563eb; }
    .status-shipped { background: #e0e7ff; color: #4338ca; }
    .status-delivered { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    .btn-sm { padding: 5px 10px; border-radius: 6px; font-size: 11px; border: none; cursor: pointer; transition: all 0.3s ease; }
    .btn-sm:hover { transform: translateY(-2px); }
    .btn-info { background: #0dcaf0; color: white; }
    .btn-primary { background: #0d6efd; color: white; }
    .btn-success { background: #198754; color: white; }
    .btn-warning { background: #ffc107; color: #212529; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-receipt { 
        background: #10b981; 
        color: white; 
        padding: 5px 10px; 
        border-radius: 6px; 
        font-size: 11px; 
        border: none; 
        cursor: pointer; 
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-receipt:hover { 
        background: #059669; 
        transform: translateY(-2px);
        color: white;
    }
    .btn-view {
        background: #0dcaf0;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 11px;
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-view:hover {
        background: #0bb5d4;
        transform: translateY(-2px);
        color: white;
    }
    .search-input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 8px; width: 200px; }
    
    /* Enhanced Modal Styles */
    .order-modal-header {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 20px 25px;
        border: none;
    }
    .order-modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .order-detail-section {
        margin-bottom: 25px;
        padding: 0 5px;
    }
    
    .order-detail-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #f59e0b;
        display: inline-block;
    }
    
    .order-detail-row {
        display: flex;
        padding: 8px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .order-detail-label {
        width: 140px;
        font-weight: 500;
        color: #6b7280;
        flex-shrink: 0;
    }
    
    .order-detail-value {
        flex: 1;
        color: #1f2937;
    }
    
    .modal-product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .modal-product-item:last-child {
        border-bottom: none;
    }
    
    .modal-product-info {
        display: flex;
        flex-direction: column;
    }
    
    .modal-product-name {
        font-weight: 600;
        color: #1f2937;
    }
    
    .modal-product-meta {
        font-size: 0.85rem;
        color: #6b7280;
    }
    
    .modal-product-price {
        font-weight: 600;
        color: #2563eb;
        font-size: 1.05rem;
    }
    
    .modal-total-section {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        margin-top: 15px;
    }
    
    .modal-total-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
    }
    
    .modal-total-row.grand-total {
        border-top: 2px solid #e5e7eb;
        margin-top: 8px;
        padding-top: 12px;
        font-size: 1.15rem;
        font-weight: 700;
        color: #2563eb;
    }
    
    .order-modal-footer {
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
        padding: 15px 25px;
    }
    
    .order-modal-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        flex-wrap: wrap;
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
        font-size: 0.85rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-chat-seller:hover {
        background: #d97706;
        transform: translateY(-2px);
        color: white;
    }
    
    .btn-chat-customer {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: #10b981;
        color: white;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .btn-chat-customer:hover {
        background: #059669;
        transform: translateY(-2px);
        color: white;
    }
    
    .order-status-timeline {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 15px 0;
        padding: 10px 15px;
        background: #f8fafc;
        border-radius: 10px;
        flex-wrap: wrap;
    }
    
    .status-step {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.75rem;
        color: #9ca3af;
    }
    
    .status-step.completed {
        color: #10b981;
    }
    
    .status-step.active {
        color: #2563eb;
        font-weight: 600;
    }
    
    .status-line {
        width: 25px;
        height: 2px;
        background: #e5e7eb;
    }
    
    .status-line.completed {
        background: #10b981;
    }
    
    .action-buttons { display: flex; gap: 4px; flex-wrap: wrap; }
    
    /* Seller info card in modal */
    .seller-info-card {
        background: #fffbeb;
        border-radius: 12px;
        padding: 15px;
        border-left: 4px solid #f59e0b;
    }
    
    .customer-info-card {
        background: #ecfdf5;
        border-radius: 12px;
        padding: 15px;
        border-left: 4px solid #10b981;
    }
    
    .badge-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-verified { background: #d1fae5; color: #059669; }
    .badge-pending { background: #fef3c7; color: #d97706; }
    .badge-rejected { background: #fee2e2; color: #dc2626; }
    
    @media (max-width: 992px) { 
        .admin-wrapper { flex-direction: column; } 
        .admin-sidebar { width: 100%; } 
        .action-buttons { flex-direction: column; }
        .order-detail-row { flex-direction: column; }
        .order-detail-label { width: 100%; margin-bottom: 3px; }
        .modal-product-item { flex-direction: column; text-align: center; gap: 5px; }
        .order-modal-actions { flex-direction: column; }
        .order-modal-actions a, .order-modal-actions button { width: 100%; justify-content: center; }
        .order-status-timeline { flex-direction: column; align-items: flex-start; }
        .status-line { width: 2px; height: 15px; }
    }
    @media (max-width: 768px) {
        .search-input { width: 100%; }
        .filter-bar form { flex-direction: column; }
        .filter-bar select { width: 100%; }
        .filter-bar button { width: 100%; }
    }
</style>

<div class="container-fluid">
    <div class="admin-wrapper">
        <div class="admin-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-truck"></i> Orders Management</h2>
                <span class="text-muted">Total: <?= $total_orders ?> orders</span>
            </div>
            
            <div class="filter-bar">
                <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="text" name="search" class="search-input" placeholder="Search by order #, customer, or shop..." value="<?= htmlspecialchars($search) ?>">
                    <select name="status" class="form-select" style="width: 140px;">
                        <option value="">All Status</option>
                        <?php foreach($statuses as $status): ?>
                            <option value="<?= $status ?>" <?= $status_filter == $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>
                    <?php if($search || $status_filter): ?>
                        <a href="orders.php" class="btn btn-secondary btn-sm">
                            <i class="fa-solid fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($orders->num_rows == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-inbox fa-2x d-block mb-2"></i>
                                    No orders found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while($order = $orders->fetch_assoc()): 
                                $status_class = 'status-' . $order['status'];
                            ?>
                            <tr>
                                <td>
                                    <strong>#<?= htmlspecialchars($order['order_number']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= $order['item_count'] ?> items</small>
                                </td>
                                <td>
                                    <?= htmlspecialchars($order['customer_name']) ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($order['customer_email']) ?></small>
                                </td>
                                <td>
                                    <?php if($order['shop_name']): ?>
                                        <strong><?= htmlspecialchars($order['shop_name']) ?></strong>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($order['seller_owner_name'] ?? 'Unknown') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                <td>
                                    <?= date('M d, Y', strtotime($order['created_at'])) ?>
                                    <br>
                                    <small class="text-muted"><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-view" onclick="viewOrderDetails(<?= $order['id'] ?>)" title="View Details">
                                            <i class="fa-solid fa-eye"></i> View
                                        </button>
                                        
                                        <button class="btn-receipt" onclick="window.open('../receipt.php?id=<?= $order['id'] ?>', '_blank')" title="View Receipt">
                                            <i class="fa-solid fa-receipt"></i> Receipt
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header order-modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-file-lines"></i> Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading order details...</p>
                </div>
            </div>
            <div class="modal-footer order-modal-footer">
                <div class="order-modal-actions w-100">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-times"></i> Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function viewOrderDetails(orderId) {
    var modal = new bootstrap.Modal(document.getElementById('orderModal'));
    modal.show();
    
    document.getElementById('orderModalBody').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading order details...</p>
        </div>
    `;
    
    // Use the correct path - admin/ajax/get_order_details.php
    const ajaxUrl = 'ajax/get_order_details.php?id=' + orderId;
    
    fetch(ajaxUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Order data received:', data);
            
            if (data.success) {
                let productsHtml = '';
                let subtotal = 0;
                
                if (data.items && data.items.length > 0) {
                    data.items.forEach(item => {
                        let itemTotal = item.quantity * item.unit_price;
                        subtotal += itemTotal;
                        productsHtml += `
                            <div class="modal-product-item">
                                <div class="modal-product-info">
                                    <div class="modal-product-name">${item.name || 'Product #' + item.product_id}</div>
                                    <div class="modal-product-meta">
                                        <i class="fa-regular fa-square"></i> Qty: ${item.quantity} 
                                        <i class="fa-regular fa-tag ms-2"></i> KSH ${parseFloat(item.unit_price).toLocaleString()} each
                                    </div>
                                </div>
                                <div class="modal-product-price">KSH ${itemTotal.toLocaleString()}</div>
                            </div>
                        `;
                    });
                } else {
                    productsHtml = '<div class="text-muted text-center py-3">No products found for this order.</div>';
                }
                
                // Status Timeline
                const steps = ['pending', 'processing', 'shipped', 'delivered'];
                const currentStatus = data.order.status;
                const currentIndex = steps.indexOf(currentStatus);
                
                let statusTimelineHtml = '';
                if (currentIndex >= 0) {
                    statusTimelineHtml = `
                        <div class="order-status-timeline">
                            ${steps.map((step, index) => {
                                let isCompleted = index <= currentIndex;
                                let isActive = index === currentIndex;
                                let icon = '';
                                switch(step) {
                                    case 'pending': icon = 'fa-regular fa-clock'; break;
                                    case 'processing': icon = 'fa-solid fa-gear'; break;
                                    case 'shipped': icon = 'fa-solid fa-truck'; break;
                                    case 'delivered': icon = 'fa-solid fa-check-circle'; break;
                                }
                                return `
                                    <div class="status-step ${isCompleted ? 'completed' : ''} ${isActive ? 'active' : ''}">
                                        <i class="${icon}"></i> ${step.charAt(0).toUpperCase() + step.slice(1)}
                                    </div>
                                    ${index < steps.length - 1 ? `<div class="status-line ${index < currentIndex ? 'completed' : ''}"></div>` : ''}
                                `;
                            }).join('')}
                        </div>
                    `;
                }
                
                // Calculate totals
                let shipping = 250;
                let tax = parseFloat(data.order.total_amount) * 0.16;
                let total = parseFloat(data.order.total_amount);
                
                // Get seller status badge class
                let sellerStatusClass = 'badge-pending';
                if (data.seller.status === 'verified') sellerStatusClass = 'badge-verified';
                else if (data.seller.status === 'rejected') sellerStatusClass = 'badge-rejected';
                
                document.getElementById('orderModalBody').innerHTML = `
                    <!-- Status Timeline -->
                    ${statusTimelineHtml}
                    
                    <!-- Order Information -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-regular fa-circle-info"></i> Order Information</h6>
                        <div class="order-detail-row">
                            <div class="order-detail-label">Order Number</div>
                            <div class="order-detail-value"><strong>${data.order.order_number}</strong></div>
                        </div>
                        <div class="order-detail-row">
                            <div class="order-detail-label">Order Date</div>
                            <div class="order-detail-value">${new Date(data.order.created_at).toLocaleString()}</div>
                        </div>
                        <div class="order-detail-row">
                            <div class="order-detail-label">Status</div>
                            <div class="order-detail-value">
                                <span class="status-badge status-${data.order.status}">${data.order.status.toUpperCase()}</span>
                            </div>
                        </div>
                        <div class="order-detail-row">
                            <div class="order-detail-label">Payment Method</div>
                            <div class="order-detail-value">${data.order.payment_method}</div>
                        </div>
                        <div class="order-detail-row">
                            <div class="order-detail-label">Total Amount</div>
                            <div class="order-detail-value"><strong style="color: #2563eb; font-size: 1.1rem;">KSH ${parseFloat(data.order.total_amount).toLocaleString()}</strong></div>
                        </div>
                    </div>
                    
                    <!-- Customer Information -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-regular fa-user"></i> Customer Information</h6>
                        <div class="customer-info-card">
                            <div class="order-detail-row">
                                <div class="order-detail-label">Name</div>
                                <div class="order-detail-value"><strong>${data.order.customer_name || 'N/A'}</strong></div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-label">Email</div>
                                <div class="order-detail-value">${data.order.customer_email || 'N/A'}</div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-label">Phone</div>
                                <div class="order-detail-value">${data.order.customer_phone || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seller Information -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-solid fa-store"></i> Seller Information</h6>
                        <div class="seller-info-card">
                            <div class="order-detail-row">
                                <div class="order-detail-label">Shop Name</div>
                                <div class="order-detail-value"><strong>${data.seller.shop_name || 'N/A'}</strong></div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-label">Owner</div>
                                <div class="order-detail-value">${data.seller.owner_name || 'N/A'}</div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-label">Email</div>
                                <div class="order-detail-value">${data.seller.email || 'N/A'}</div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-label">Phone</div>
                                <div class="order-detail-value">${data.seller.phone || 'N/A'}</div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-label">Location</div>
                                <div class="order-detail-value">${data.seller.location || 'N/A'}</div>
                            </div>
                            <div class="order-detail-row">
                                <div class="order-detail-label">Status</div>
                                <div class="order-detail-value">
                                    <span class="badge-status ${sellerStatusClass}">
                                        ${data.seller.status || 'N/A'}
                                    </span>
                                </div>
                            </div>
                            ${data.seller.id ? `
                            <div class="mt-2">
                                <a href="mailto:${data.seller.email}" class="btn-chat-seller" style="padding: 6px 14px; font-size: 0.8rem;">
                                    <i class="fa-regular fa-envelope"></i> Contact Seller
                                </a>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Products Ordered -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-solid fa-box"></i> Products Ordered (${data.items ? data.items.length : 0})</h6>
                        ${productsHtml}
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-solid fa-receipt"></i> Order Summary</h6>
                        <div class="modal-total-section">
                            <div class="modal-total-row">
                                <span>Subtotal</span>
                                <span>KSH ${subtotal.toLocaleString()}</span>
                            </div>
                            <div class="modal-total-row">
                                <span>Shipping</span>
                                <span>KSH ${shipping.toLocaleString()}</span>
                            </div>
                            <div class="modal-total-row">
                                <span>Tax (16% VAT)</span>
                                <span>KSH ${tax.toLocaleString()}</span>
                            </div>
                            <div class="modal-total-row grand-total">
                                <span>Total</span>
                                <span>KSH ${total.toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-solid fa-location-dot"></i> Shipping Address</h6>
                        <div class="p-3 bg-light rounded" style="border-left: 4px solid #f59e0b;">
                            <p class="mb-0">${data.order.shipping_address}</p>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="order-detail-section">
                        <div class="d-flex gap-2 flex-wrap">
                            ${data.order.customer_email ? `
                                <a href="mailto:${data.order.customer_email}" class="btn-chat-customer">
                                    <i class="fa-regular fa-envelope"></i> Contact Customer
                                </a>
                            ` : ''}
                            <button class="btn-receipt" onclick="window.open('../receipt.php?id=${data.order.id}', '_blank')" style="padding: 8px 18px; font-size: 0.85rem;">
                                <i class="fa-solid fa-receipt"></i> Download Receipt
                            </button>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('orderModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> ${data.message || 'Failed to load order details.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('orderModalBody').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> Error loading order details. Please try again.
                    <br>
                    <small class="text-muted">${error.message}</small>
                </div>
            `;
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>