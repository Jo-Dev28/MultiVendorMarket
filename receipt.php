<?php
$page_title = 'Order Receipt';
require_once 'includes/header.php';
require_login();

$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    flash('Invalid order ID.', 'danger');
    redirect('orders.php');
}

// Check if user is admin
$is_admin = false;
$user_id = $_SESSION['user_id'];

// Check user role
$role_check = $mysqli->prepare("SELECT role FROM users WHERE id = ?");
$role_check->bind_param('i', $user_id);
$role_check->execute();
$role_result = $role_check->get_result();
if ($role_result->num_rows > 0) {
    $user_data = $role_result->fetch_assoc();
    if ($user_data['role'] === 'admin') {
        $is_admin = true;
    }
}

// Check if user is a seller
$is_seller = false;
$seller_id = null;

if (!$is_admin) {
    $seller_check = $mysqli->prepare("SELECT id FROM sellers WHERE user_id = ?");
    $seller_check->bind_param('i', $user_id);
    $seller_check->execute();
    $seller_result = $seller_check->get_result();
    if ($seller_result->num_rows > 0) {
        $is_seller = true;
        $seller_data = $seller_result->fetch_assoc();
        $seller_id = $seller_data['id'];
    }
}

// Get order details - allow admin, customer, or seller to view
$sql = "SELECT o.*, s.shop_name, s.shop_logo, s.location as seller_location,
        s.phone as seller_phone, u.name as seller_owner, u.email as seller_email,
        u2.name as customer_name, u2.email as customer_email, u2.phone as customer_phone
        FROM orders o
        LEFT JOIN sellers s ON o.seller_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN users u2 ON o.user_id = u2.id
        WHERE o.id = ?";

// Add permission conditions
if ($is_admin) {
    // Admin can view any order - no additional WHERE conditions
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $order_id);
} else {
    // Customer or seller - only their orders
    $sql .= " AND (o.user_id = ? OR o.seller_id = ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $order_id, $user_id, $seller_id);
}

$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    flash('Order not found or you do not have permission to view it.', 'danger');
    if ($is_admin) {
        redirect('admin/orders.php');
    } elseif ($is_seller) {
        redirect('seller/orders.php');
    } else {
        redirect('orders.php');
    }
}

// Get order items with discount info
$items_sql = "SELECT oi.*, p.name, p.slug, p.price as original_price, 
              p.discounted_price, p.is_on_sale, p.discount_percent,
              (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
              FROM order_items oi
              LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ?";
$items_stmt = $mysqli->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result();

// Calculate totals with discount detection
$subtotal = 0;
$total_discount = 0;
$items_array = [];
while ($item = $items->fetch_assoc()) {
    // Check if this item had a discount
    $has_discount = false;
    $discount_amount = 0;
    
    if ($item['is_on_sale'] == 1 && $item['discounted_price'] > 0 && $item['discounted_price'] < $item['original_price']) {
        $has_discount = true;
        $discount_amount = ($item['original_price'] - $item['discounted_price']) * $item['quantity'];
        $item['item_total'] = $item['quantity'] * $item['discounted_price'];
    } else {
        $item['item_total'] = $item['quantity'] * $item['unit_price'];
    }
    
    $item['has_discount'] = $has_discount;
    $item['discount_amount'] = $discount_amount;
    $subtotal += $item['item_total'];
    $total_discount += $discount_amount;
    $items_array[] = $item;
}

$shipping = 250;
$tax = $subtotal * 0.16;
$total = $subtotal + $shipping + $tax;

// Determine back URL
if ($is_admin) {
    $back_url = 'admin/orders.php';
} elseif ($is_seller) {
    $back_url = 'seller/orders.php';
} else {
    $back_url = 'orders.php';
}
?>

<style>
    .receipt-wrapper {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        padding: 40px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .receipt-header {
        text-align: center;
        padding-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
        margin-bottom: 25px;
    }
    
    .receipt-header h1 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
    }
    
    .receipt-header .subtitle {
        color: #6b7280;
        font-size: 0.9rem;
    }
    
    .receipt-info {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        margin-bottom: 25px;
    }
    
    .receipt-info-item {
        display: flex;
        flex-direction: column;
    }
    
    .receipt-info-item .label {
        font-size: 0.7rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .receipt-info-item .value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1f2937;
    }
    
    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 25px;
    }
    
    .receipt-table th {
        background: #f8fafc;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .receipt-table td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
        color: #374151;
    }
    
    .receipt-table .item-name {
        font-weight: 500;
    }
    
    .receipt-table .item-price {
        text-align: right;
        font-weight: 600;
        color: #2563eb;
    }
    
    .discount-badge {
        display: inline-block;
        background: #fee2e2;
        color: #dc2626;
        padding: 1px 10px;
        border-radius: 12px;
        font-size: 0.65rem;
        font-weight: 600;
        margin-left: 8px;
    }
    
    .sale-price {
        color: #dc2626;
        font-weight: 600;
    }
    
    .original-price {
        text-decoration: line-through;
        color: #9ca3af;
        font-size: 0.8rem;
        margin-right: 6px;
    }
    
    .discount-saved {
        color: #dc2626;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .receipt-summary {
        border-top: 2px solid #e5e7eb;
        padding-top: 20px;
        margin-top: 10px;
    }
    
    .receipt-summary-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
    }
    
    .receipt-summary-row.discount-row {
        color: #dc2626;
        font-weight: 600;
    }
    
    .receipt-summary-row.total {
        border-top: 2px solid #e5e7eb;
        margin-top: 10px;
        padding-top: 15px;
        font-size: 1.2rem;
        font-weight: 700;
        color: #2563eb;
    }
    
    .receipt-summary-row .label {
        color: #6b7280;
    }
    
    .receipt-summary-row.total .label {
        color: #1f2937;
    }
    
    .receipt-footer {
        text-align: center;
        padding-top: 20px;
        border-top: 2px solid #e5e7eb;
        margin-top: 25px;
    }
    
    .receipt-footer p {
        color: #6b7280;
        font-size: 0.8rem;
        margin: 5px 0;
    }
    
    .receipt-actions {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 25px;
        flex-wrap: wrap;
    }
    
    .btn-print {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }
    
    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    
    .btn-back {
        background: #f3f4f6;
        color: #374151;
        border: none;
        padding: 10px 25px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        font-weight: 600;
    }
    
    .btn-back:hover {
        background: #e5e7eb;
    }
    
    .btn-download {
        background: #10b981;
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }
    
    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(16,185,129,0.3);
    }
    
    .status-badge-receipt {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-processing { background: #dbeafe; color: #2563eb; }
    .status-shipped { background: #e0e7ff; color: #4338ca; }
    .status-delivered { background: #d1fae5; color: #059669; }
    .status-cancelled { background: #fee2e2; color: #dc2626; }
    
    /* Admin Badge */
    .admin-badge {
        background: #2563eb;
        color: white;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
        margin-left: 10px;
    }
    
    @media print {
        .receipt-actions, .btn-print, .btn-back, .btn-download, .modern-header, .floating-cart, .dashboard-sidebar, .announcement-bar {
            display: none !important;
        }
        .receipt-wrapper {
            box-shadow: none !important;
            padding: 20px !important;
        }
        .receipt-info {
            background: #f8fafc !important;
        }
        body {
            background: white !important;
        }
    }
    
    @media (max-width: 768px) {
        .receipt-wrapper {
            padding: 20px;
            margin: 10px;
        }
        .receipt-info {
            flex-direction: column;
            gap: 8px;
        }
        .receipt-table {
            font-size: 0.8rem;
        }
        .receipt-table th, .receipt-table td {
            padding: 8px;
        }
        .receipt-actions {
            flex-direction: column;
        }
        .receipt-actions button, .receipt-actions a {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="container mb-5">
    <div class="receipt-wrapper" id="receiptContent">
        <!-- Receipt Header -->
        <div class="receipt-header">
            <h1><i class="fa-solid fa-store"></i> <?= SITE_NAME ?></h1>
            <div class="subtitle">
                Official Order Receipt
                <?php if ($is_admin): ?>
                    <span class="admin-badge"><i class="fa-solid fa-user-shield"></i> Admin View</span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Receipt Info -->
        <div class="receipt-info">
            <div class="receipt-info-item">
                <span class="label">Order Number</span>
                <span class="value">#<?= htmlspecialchars($order['order_number']) ?></span>
            </div>
            <div class="receipt-info-item">
                <span class="label">Order Date</span>
                <span class="value"><?= date('F d, Y h:i A', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="receipt-info-item">
                <span class="label">Payment Method</span>
                <span class="value"><?= htmlspecialchars($order['payment_method']) ?></span>
            </div>
            <div class="receipt-info-item">
                <span class="label">Status</span>
                <span class="value">
                    <span class="status-badge-receipt status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </span>
            </div>
        </div>
        
        <!-- Seller Info -->
        <div style="background: #f8fafc; border-radius: 12px; padding: 15px 20px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Seller</div>
                    <div style="font-weight: 600; color: #1f2937;"><?= htmlspecialchars($order['shop_name'] ?? 'Unknown Seller') ?></div>
                    <div style="font-size: 0.8rem; color: #6b7280;"><?= htmlspecialchars($order['seller_owner'] ?? '') ?></div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Contact</div>
                    <div style="font-size: 0.8rem; color: #1f2937;"><?= htmlspecialchars($order['seller_phone'] ?? 'N/A') ?></div>
                    <div style="font-size: 0.8rem; color: #1f2937;"><?= htmlspecialchars($order['seller_email'] ?? 'N/A') ?></div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Location</div>
                    <div style="font-size: 0.8rem; color: #1f2937;"><?= htmlspecialchars($order['seller_location'] ?? 'N/A') ?></div>
                </div>
            </div>
        </div>
        
        <!-- Customer Info (for sellers or admin) -->
        <?php if ($is_seller || $is_admin): ?>
        <div style="background: <?= $is_admin ? '#eff6ff' : '#f0fdf4'; ?>; border-radius: 12px; padding: 15px 20px; margin-bottom: 25px; border-left: 4px solid <?= $is_admin ? '#2563eb' : '#10b981'; ?>;">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Customer</div>
                    <div style="font-weight: 600; color: #1f2937;"><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></div>
                    <div style="font-size: 0.8rem; color: #6b7280;"><?= htmlspecialchars($order['customer_email'] ?? 'N/A') ?></div>
                </div>
                <div>
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Phone</div>
                    <div style="font-size: 0.8rem; color: #1f2937;"><?= htmlspecialchars($order['customer_phone'] ?? 'N/A') ?></div>
                </div>
                <?php if ($is_admin): ?>
                <div>
                    <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Customer ID</div>
                    <div style="font-size: 0.8rem; color: #1f2937;">#<?= $order['user_id'] ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Items Table with Discounts -->
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items_array)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #6b7280; padding: 20px;">
                        No items found for this order.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($items_array as $item): 
                    $item_name = htmlspecialchars($item['name'] ?? 'Product #' . $item['product_id']);
                    $has_discount = $item['has_discount'] ?? false;
                    $display_price = $has_discount ? $item['discounted_price'] : $item['unit_price'];
                ?>
                <tr>
                    <td class="item-name">
                        <?= $item_name ?>
                        <?php if ($has_discount): ?>
                            <span class="discount-badge"><?= $item['discount_percent'] ?? 0 ?>% OFF</span>
                        <?php endif; ?>
                        <?php if ($has_discount): ?>
                            <br>
                            <span class="original-price">KSH <?= number_format($item['original_price']) ?></span>
                            <span class="sale-price">KSH <?= number_format($item['discounted_price']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: center;"><?= $item['quantity'] ?></td>
                    <td style="text-align: right;">
                        <?php if ($has_discount): ?>
                            <span class="sale-price">KSH <?= number_format($display_price) ?></span>
                        <?php else: ?>
                            KSH <?= number_format($item['unit_price']) ?>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; font-weight: 600; color: #2563eb;">
                        KSH <?= number_format($item['item_total']) ?>
                        <?php if ($has_discount): ?>
                            <br>
                            <span class="discount-saved">
                                <i class="fa-solid fa-tag"></i> Saved: KSH <?= number_format($item['discount_amount']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Summary with Discounts -->
        <div class="receipt-summary">
            <div class="receipt-summary-row">
                <span class="label">Subtotal</span>
                <span>KSH <?= number_format($subtotal) ?></span>
            </div>
            
            <?php if ($total_discount > 0): ?>
            <div class="receipt-summary-row discount-row">
                <span class="label">Total Discount</span>
                <span>- KSH <?= number_format($total_discount) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="receipt-summary-row">
                <span class="label">Shipping</span>
                <span>KSH <?= number_format($shipping) ?></span>
            </div>
            <div class="receipt-summary-row">
                <span class="label">Tax (16% VAT)</span>
                <span>KSH <?= number_format($tax) ?></span>
            </div>
            
            <?php if ($total_discount > 0): ?>
            <div class="receipt-summary-row" style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-top: 5px;">
                <span class="label" style="font-weight: 600;">Subtotal after discount</span>
                <span style="font-weight: 600;">KSH <?= number_format($subtotal) ?></span>
            </div>
            <?php endif; ?>
            
            <div class="receipt-summary-row total">
                <span class="label">Total</span>
                <span>KSH <?= number_format($total) ?></span>
            </div>
            
            <?php if ($total_discount > 0): ?>
            <div style="text-align: right; margin-top: 10px; color: #10b981; font-size: 0.85rem; font-weight: 600;">
                <i class="fa-solid fa-tag"></i> You saved KSH <?= number_format($total_discount) ?> on this order!
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Shipping Address -->
        <div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #e5e7eb;">
            <div style="font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px;">
                <i class="fa-solid fa-location-dot"></i> Shipping Address
            </div>
            <div style="color: #1f2937;"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></div>
        </div>
        
        <!-- Footer -->
        <div class="receipt-footer">
            <p><i class="fa-regular fa-circle-check"></i> Thank you for shopping with <?= SITE_NAME ?></p>
            <p>For any inquiries, please contact us at support@multivendorhub.com</p>
            <p style="font-size: 0.7rem; color: #9ca3af;">Receipt generated on <?= date('F d, Y h:i A') ?></p>
        </div>
        
        <!-- Actions -->
        <div class="receipt-actions">
            <button class="btn-print" onclick="window.print()">
                <i class="fa-solid fa-print"></i> Print Receipt
            </button>
            <button class="btn-download" onclick="downloadReceipt()">
                <i class="fa-solid fa-download"></i> Download PDF
            </button>
            <a href="<?= $back_url ?>" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
</div>

<!-- SweetAlert2 and html2pdf -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
// Download receipt as PDF
function downloadReceipt() {
    Swal.fire({
        title: 'Generating PDF...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const element = document.getElementById('receiptContent');
    const opt = {
        margin: 10,
        filename: 'receipt_<?= $order['order_number'] ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, logging: false },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).save().then(function() {
        Swal.close();
    }).catch(function(error) {
        Swal.fire({
            title: 'Error!',
            text: 'Failed to generate PDF. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
}

// Print receipt with better formatting
document.querySelector('.btn-print')?.addEventListener('click', function() {
    setTimeout(function() {
        window.print();
    }, 300);
});
</script>

<?php require_once 'includes/footer.php'; ?>