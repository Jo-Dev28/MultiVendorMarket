<?php
$page_title = 'Seller Orders';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

// Handle order status update - FIXED
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    
    // Verify the order belongs to this seller
    $check_sql = "SELECT id FROM orders WHERE id = ? AND seller_id = ?";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param('ii', $order_id, $seller['id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $update_sql = "UPDATE orders SET status = ? WHERE id = ? AND seller_id = ?";
        $update_stmt = $mysqli->prepare($update_sql);
        $update_stmt->bind_param('sii', $status, $order_id, $seller['id']);
        
        if ($update_stmt->execute()) {
            flash('Order status updated successfully.', 'success');
        } else {
            flash('Failed to update order status.', 'danger');
        }
    } else {
        flash('Order not found or you do not have permission.', 'danger');
    }
    redirect('seller/orders.php');
}

// Search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE seller_id = {$seller['id']}";
if ($search) {
    $where .= " AND (order_number LIKE '%$search%' OR shipping_address LIKE '%$search%')";
}
if ($status_filter) {
    $where .= " AND status = '$status_filter'";
}

$total_result = $mysqli->query("SELECT COUNT(*) as total FROM orders $where");
$total_orders = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

$orders = $mysqli->query("SELECT o.*, u.name as customer_name 
                          FROM orders o 
                          JOIN users u ON u.id = o.user_id 
                          $where 
                          ORDER BY o.created_at DESC 
                          LIMIT $offset, $limit");
?>

<style>
    .seller-wrapper { display: flex; gap: 25px; }
    .seller-sidebar { width: 280px; flex-shrink: 0; }
    .seller-content { flex: 1; }
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
    .btn-outline-primary { background: transparent; color: #0d6efd; border: 1px solid #0d6efd; }
    .btn-outline-primary:hover { background: #0d6efd; color: white; }
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
    }
    
    .btn-chat-customer {
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
    
    .btn-chat-customer:hover {
        background: #d97706;
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
    
    @media (max-width: 992px) { 
        .seller-wrapper { flex-direction: column; } 
        .seller-sidebar { width: 100%; } 
        .action-buttons { flex-direction: column; }
        .order-detail-row { flex-direction: column; }
        .order-detail-label { width: 100%; margin-bottom: 3px; }
        .modal-product-item { flex-direction: column; text-align: center; gap: 5px; }
        .order-modal-actions { flex-direction: column; }
        .order-modal-actions a, .order-modal-actions button { width: 100%; justify-content: center; }
    }
</style>

<div class="container-fluid">
    <div class="seller-wrapper">
        <div class="seller-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="seller-content">
            <h2 class="mb-4"><i class="fa-solid fa-truck"></i> My Orders</h2>
            
            <div class="filter-bar">
                <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="text" name="search" class="search-input" placeholder="Search by order number..." value="<?= htmlspecialchars($search) ?>">
                    <select name="status" class="form-select" style="width: 120px;">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $status_filter == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if($search || $status_filter): ?>
                        <a href="orders.php" class="btn btn-secondary btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $orders->fetch_assoc()): 
                            $status_class = 'status-' . $order['status'];
                            $can_update = ($order['status'] != 'delivered' && $order['status'] != 'cancelled');
                        ?>
                        <tr>
                            <td><strong><?= $order['order_number'] ?></strong></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td>KSH <?= number_format($order['total_amount']) ?></td>
                            <td><?= $order['payment_method'] ?></td>
                            <td><span class="status-badge <?= $status_class ?>"><?= ucfirst($order['status']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                            <td>
                                <div class="action-buttons">
                                    <!-- Status Update Buttons instead of dropdown -->
                                    <?php if ($can_update): ?>
                                        <?php if ($order['status'] == 'pending'): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="status" value="processing">
                                                <button type="submit" name="update_status" class="btn-sm btn-primary" title="Mark as Processing">
                                                    <i class="fa-solid fa-gear"></i> Process
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] == 'pending' || $order['status'] == 'processing'): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="status" value="shipped">
                                                <button type="submit" name="update_status" class="btn-sm btn-info" title="Mark as Shipped">
                                                    <i class="fa-solid fa-truck"></i> Ship
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] == 'shipped'): ?>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="status" value="delivered">
                                                <button type="submit" name="update_status" class="btn-sm btn-success" title="Mark as Delivered">
                                                    <i class="fa-solid fa-check"></i> Deliver
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if ($order['status'] != 'cancelled'): ?>
                                            <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" name="update_status" class="btn-sm btn-danger" title="Cancel Order">
                                                    <i class="fa-solid fa-times"></i> Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Finalized</span>
                                    <?php endif; ?>
                                    
                                    <button class="btn-sm btn-info" onclick="viewOrderDetails(<?= $order['id'] ?>)" title="View Details" style="background: #0dcaf0; color: white; padding: 5px 10px;">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                    
                                    <!-- Receipt Button -->
                                    <button class="btn-receipt" onclick="window.open('../receipt.php?id=<?= $order['id'] ?>', '_blank')" title="View Receipt">
                                        <i class="fa-solid fa-receipt"></i> Receipt
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($orders->num_rows == 0): ?>
                        <tr><td colspan="7" class="text-center py-4">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i=1;$i<=$total_pages;$i++): ?>
                        <li class="page-item <?= $i==$page?'active':'' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
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
        </div>
    `;
    
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
                                <div class="modal-product-info">
                                    <div class="modal-product-name">${item.name || 'Product'}</div>
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
                
                // Get status steps
                const steps = ['pending', 'processing', 'shipped', 'delivered'];
                const currentStatus = data.order.status;
                const currentIndex = steps.indexOf(currentStatus);
                
                let statusTimelineHtml = `
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
                
                // Calculate totals
                let shipping = 250;
                let tax = parseFloat(data.order.total_amount) * 0.16;
                let total = parseFloat(data.order.total_amount);
                
                document.getElementById('orderModalBody').innerHTML = `
                    <!-- Status Timeline -->
                    ${statusTimelineHtml}
                    
                    <!-- Order Info -->
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
                    </div>
                    
                    <!-- Customer Info -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-regular fa-user"></i> Customer Information</h6>
                        <div class="order-detail-row">
                            <div class="order-detail-label">Customer Name</div>
                            <div class="order-detail-value"><strong>${data.order.customer_name}</strong></div>
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
                    
                    <!-- Products -->
                    <div class="order-detail-section">
                        <h6 class="order-detail-section-title"><i class="fa-solid fa-box"></i> Products Ordered</h6>
                        ${productsHtml}
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="order-detail-section">
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
                    
                    <!-- Modal Actions -->
                    <div class="order-detail-section">
                        <div class="d-flex gap-2 flex-wrap">
                            ${data.order.status !== 'cancelled' ? `
                                <a href="chat.php?customer_id=${data.order.user_id}" class="btn-chat-customer">
                                    <i class="fa-regular fa-message"></i> Chat with Customer
                                </a>
                            ` : ''}
                            <button class="btn-receipt" onclick="window.open('receipt.php?id=${data.order.id}', '_blank')" style="padding: 8px 18px; font-size: 0.85rem;">
                                <i class="fa-solid fa-receipt"></i> Download Receipt
                            </button>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('orderModalBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('orderModalBody').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> Error loading order details. Please try again.
                </div>
            `;
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>