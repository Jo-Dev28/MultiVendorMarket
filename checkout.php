<?php
$page_title = 'Checkout';
require_once 'includes/header.php';
require_login();

$user = current_user();
$user_id = $user['id'];

// Get cart - FIRST CHECK DATABASE, THEN SESSION
$cart_items = [];
$subtotal = 0;

// For logged-in users, get cart from database
$sql = "SELECT c.*, p.id as product_id, p.name, p.price, p.stock, p.slug, 
        p.is_on_sale, p.discounted_price, p.discount_percent,
        s.shop_name, s.id as seller_id
        FROM carts c
        JOIN products p ON p.id = c.product_id
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE c.user_id = ? AND p.status = 'approved'";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    // Check if product has discount
    $display_price = $row['price'];
    $is_on_sale = false;
    $discount_percent_item = 0;
    
    if ($row['is_on_sale'] == 1 && $row['discounted_price'] > 0 && $row['discounted_price'] < $row['price']) {
        $display_price = $row['discounted_price'];
        $is_on_sale = true;
        $discount_percent_item = $row['discount_percent'] ?? 0;
    }
    
    $row['display_price'] = $display_price;
    $row['is_on_sale'] = $is_on_sale;
    $row['discount_percent_item'] = $discount_percent_item;
    $row['item_total'] = $display_price * $row['quantity'];
    $cart_items[] = $row;
    $subtotal += $row['item_total'];
}

// If no items in database, check session
if (empty($cart_items) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $session_cart = $_SESSION['cart'];
    $ids = array_keys($session_cart);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.*, p.id as product_id, 
                p.is_on_sale, p.discounted_price, p.discount_percent,
                s.shop_name, s.id as seller_id 
                FROM products p
                LEFT JOIN sellers s ON p.seller_id = s.id
                WHERE p.id IN ($placeholders) AND p.status = 'approved'";
        $stmt = $mysqli->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Check if product has discount
            $display_price = $row['price'];
            $is_on_sale = false;
            $discount_percent_item = 0;
            
            if ($row['is_on_sale'] == 1 && $row['discounted_price'] > 0 && $row['discounted_price'] < $row['price']) {
                $display_price = $row['discounted_price'];
                $is_on_sale = true;
                $discount_percent_item = $row['discount_percent'] ?? 0;
            }
            
            $quantity = $session_cart[$row['id']];
            $row['quantity'] = $quantity;
            $row['display_price'] = $display_price;
            $row['is_on_sale'] = $is_on_sale;
            $row['discount_percent_item'] = $discount_percent_item;
            $row['item_total'] = $display_price * $quantity;
            $cart_items[] = $row;
            $subtotal += $row['item_total'];
        }
    }
}

if (empty($cart_items)) {
    flash('Your cart is empty. Please add items before checkout.', 'warning');
    redirect('cart.php');
}

// Apply coupon discount if exists
$coupon_discount = 0;
$discount_percent = 0;
$coupon_code = '';

if (isset($_SESSION['coupon_code']) && isset($_SESSION['discount_percent'])) {
    $coupon_code = $_SESSION['coupon_code'];
    $discount_percent = $_SESSION['discount_percent'];
    $coupon_discount = $subtotal * ($discount_percent / 100);
}

$subtotal_after_coupon = $subtotal - $coupon_discount;
$shipping_cost = ($subtotal > 0 && $subtotal < 5000) ? 250 : 0;
$tax = $subtotal_after_coupon * 0.16;
$total = $subtotal_after_coupon + $shipping_cost + $tax;

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('checkout.php');
    }
    
    $payment_method = sanitize($_POST['payment_method'] ?? '');
    $shipping_address = sanitize($_POST['shipping_address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    
    if (empty($shipping_address)) {
        flash('Please enter your shipping address.', 'danger');
    } elseif (empty($payment_method)) {
        flash('Please select a payment method.', 'danger');
    } else {
        // Start transaction
        $mysqli->begin_transaction();
        
        try {
            // Group items by seller
            $seller_items = [];
            foreach ($cart_items as $item) {
                $seller_id = $item['seller_id'];
                if (!isset($seller_items[$seller_id])) {
                    $seller_items[$seller_id] = [
                        'seller_name' => $item['shop_name'],
                        'items' => [],
                        'total' => 0
                    ];
                }
                // Use display_price (with discount) for order
                $item_price = $item['display_price'] ?? $item['price'];
                $item['order_price'] = $item_price;
                $item['item_total'] = $item_price * $item['quantity'];
                $seller_items[$seller_id]['items'][] = $item;
                $seller_items[$seller_id]['total'] += $item['item_total'];
            }
            
            $all_orders_success = true;
            $order_ids = [];
            $first_order_id = null;
            
            // Create separate order for each seller
            foreach ($seller_items as $seller_id => $seller_data) {
                // Generate short order number (10 characters)
                $order_number = generate_unique_order_number($mysqli);
                
                $sql = "INSERT INTO orders (user_id, seller_id, order_number, total_amount, payment_method, shipping_address, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('iissss', $user_id, $seller_id, $order_number, $seller_data['total'], $payment_method, $shipping_address);
                
                if ($stmt->execute()) {
                    $order_id = $stmt->insert_id;
                    $order_ids[] = $order_id;
                    if ($first_order_id === null) {
                        $first_order_id = $order_id;
                    }
                    
                    // Add order items and update stock
                    foreach ($seller_data['items'] as $item) {
                        // Insert order item with the discounted price
                        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
                        $item_stmt = $mysqli->prepare($item_sql);
                        $order_price = $item['order_price'] ?? $item['price'];
                        $item_stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $order_price);
                        
                        if (!$item_stmt->execute()) {
                            throw new Exception("Failed to insert order item: " . $item_stmt->error);
                        }
                        
                        // Update product stock
                        $new_stock = $item['stock'] - $item['quantity'];
                        $update_stock = "UPDATE products SET stock = ? WHERE id = ?";
                        $stock_stmt = $mysqli->prepare($update_stock);
                        $stock_stmt->bind_param('ii', $new_stock, $item['product_id']);
                        $stock_stmt->execute();
                    }
                } else {
                    throw new Exception("Failed to create order: " . $stmt->error);
                }
            }
            
            // Commit transaction
            $mysqli->commit();
            
            // Clear cart from database
            $clear_sql = "DELETE FROM carts WHERE user_id = ?";
            $clear_stmt = $mysqli->prepare($clear_sql);
            $clear_stmt->bind_param('i', $user_id);
            $clear_stmt->execute();
            
            // Clear session cart and coupon
            unset($_SESSION['cart']);
            unset($_SESSION['coupon_code']);
            unset($_SESSION['discount_percent']);
            
            // Store order ID in session for receipt redirect
            $_SESSION['last_order_id'] = $first_order_id;
            
            flash('Order placed successfully! Thank you for shopping with us.', 'success');
            redirect('receipt.php?id=' . $first_order_id);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $mysqli->rollback();
            error_log("Checkout error: " . $e->getMessage());
            flash('Unable to complete checkout. Error: ' . $e->getMessage(), 'danger');
        }
    }
}

// Get user address for pre-fill
$user_address = $user['address'] ?? '';
?>

<style>
    .checkout-header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        padding: 40px 0;
        margin-bottom: 40px;
        border-radius: 0 0 20px 20px;
    }
    
    .checkout-title {
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
    }
    
    .checkout-subtitle {
        color: rgba(255,255,255,0.9);
        margin-top: 10px;
    }
    
    .order-summary-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }
    
    .checkout-form-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f59e0b;
        display: inline-block;
    }
    
    .cart-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .cart-item:last-child {
        border-bottom: none;
    }
    
    .cart-item-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .cart-item-price {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .cart-item-total {
        font-weight: 600;
        color: #2563eb;
    }
    
    .discount-badge {
        display: inline-block;
        background: #fee2e2;
        color: #dc2626;
        padding: 1px 8px;
        border-radius: 12px;
        font-size: 0.6rem;
        font-weight: 600;
        margin-left: 6px;
    }
    
    .sale-price {
        color: #dc2626;
        font-weight: 600;
    }
    
    .original-price {
        text-decoration: line-through;
        color: #9ca3af;
        font-size: 0.75rem;
        margin-right: 6px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
    }
    
    .summary-total {
        border-top: 2px solid #e5e7eb;
        padding-top: 15px;
        margin-top: 10px;
        font-size: 1.1rem;
        font-weight: 700;
        color: #2563eb;
    }
    
    .discount-row {
        color: #dc2626;
        font-weight: 600;
    }
    
    .coupon-applied {
        background: #d1fae5;
        color: #059669;
        padding: 8px 12px;
        border-radius: 8px;
        margin-bottom: 10px;
        font-size: 0.8rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .form-label {
        font-weight: 500;
        color: #1f2937;
        margin-bottom: 8px;
        display: block;
    }
    
    .form-label i {
        color: #f59e0b;
        width: 20px;
        margin-right: 8px;
    }
    
    .form-control, .form-select {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    
    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    
    .payment-methods {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .payment-option {
        flex: 1;
        min-width: 100px;
    }
    
    .payment-option input {
        display: none;
    }
    
    .payment-option label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        padding: 15px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }
    
    .payment-option input:checked + label {
        border-color: #2563eb;
        background: #eff6ff;
    }
    
    .payment-option label i {
        font-size: 1.5rem;
        color: #6b7280;
    }
    
    .payment-option input:checked + label i {
        color: #2563eb;
    }
    
    .btn-place-order {
        width: 100%;
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        border: none;
        padding: 15px;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        margin-top: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-place-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
    }
    
    .btn-place-order:disabled {
        opacity: 0.7;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .btn-place-order .spinner-container {
        display: none;
        align-items: center;
        gap: 10px;
    }
    
    .btn-place-order .spinner-container.active {
        display: flex;
    }
    
    .btn-place-order .btn-text {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .btn-place-order .btn-text.hidden {
        display: none;
    }
    
    .spinner-border-sm {
        width: 1.2rem;
        height: 1.2rem;
        border-width: 0.15em;
    }
    
    .back-to-cart {
        display: inline-block;
        margin-top: 15px;
        color: #6b7280;
        text-decoration: none;
    }
    
    .back-to-cart:hover {
        color: #2563eb;
    }
    
    .empty-cart-message {
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 20px;
    }
    
    @media (max-width: 768px) {
        .checkout-title { font-size: 1.5rem; }
        .payment-methods { flex-direction: column; }
        .order-summary-card { position: static; margin-top: 20px; }
    }
</style>

<div class="checkout-header">
    <div class="container">
        <h1 class="checkout-title">Checkout</h1>
        <p class="checkout-subtitle">Review your order and complete payment</p>
    </div>
</div>

<div class="container mb-5">
    <?php if (empty($cart_items)): ?>
        <div class="empty-cart-message">
            <i class="fa-solid fa-cart-shopping fa-4x text-muted mb-3"></i>
            <h3>Your cart is empty</h3>
            <p>Please add items to your cart before checking out.</p>
            <a href="shop.php" class="btn-place-order" style="display: inline-block; width: auto; padding: 12px 30px; background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                <i class="fa-solid fa-store"></i> Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <!-- Checkout Form -->
            <div class="col-lg-7">
                <div class="checkout-form-card">
                    <h3 class="section-title"><i class="fa-regular fa-address-card"></i> Shipping Information</h3>
                    
                    <form method="post" id="checkoutForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fa-regular fa-user"></i> Full Name</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fa-regular fa-envelope"></i> Email Address</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fa-solid fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Enter your phone number" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fa-solid fa-location-dot"></i> Shipping Address *</label>
                            <textarea name="shipping_address" class="form-control" rows="4" placeholder="Enter your complete shipping address" required><?= htmlspecialchars($user_address) ?></textarea>
                        </div>
                        
                        <h3 class="section-title mt-4"><i class="fa-solid fa-credit-card"></i> Payment Method</h3>
                        
                        <div class="payment-methods mb-4">
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="mpesa" value="M-Pesa" checked>
                                <label for="mpesa">
                                    <i class="fa-solid fa-mobile-alt"></i>
                                    <span>M-Pesa</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="card" value="Card">
                                <label for="card">
                                    <i class="fa-regular fa-credit-card"></i>
                                    <span>Card</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="paypal" value="PayPal">
                                <label for="paypal">
                                    <i class="fa-brands fa-paypal"></i>
                                    <span>PayPal</span>
                                </label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_method" id="bank" value="Bank Transfer">
                                <label for="bank">
                                    <i class="fa-solid fa-building-columns"></i>
                                    <span>Bank</span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-place-order" id="placeOrderBtn">
                            <span class="btn-text" id="btnText">
                                <i class="fa-solid fa-check-circle"></i> Place Order
                            </span>
                            <span class="spinner-container" id="btnSpinner">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                                Processing...
                            </span>
                        </button>
                        
                        <a href="cart.php" class="back-to-cart">
                            <i class="fa-solid fa-arrow-left"></i> Back to Cart
                        </a>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="order-summary-card">
                    <h3 class="section-title"><i class="fa-solid fa-receipt"></i> Order Summary</h3>
                    
                    <!-- Coupon Applied -->
                    <?php if (!empty($coupon_code) && $discount_percent > 0): ?>
                        <div class="coupon-applied">
                            <span><i class="fa-solid fa-tag"></i> Coupon applied! <?= $discount_percent ?>% off</span>
                            <span style="color: #059669;">- KSH <?= number_format($coupon_discount) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): 
                            $is_on_sale = $item['is_on_sale'] ?? false;
                            $display_price = $item['display_price'] ?? $item['price'];
                        ?>
                            <div class="cart-item">
                                <div>
                                    <div class="cart-item-name">
                                        <?= htmlspecialchars($item['name']) ?>
                                        <?php if ($is_on_sale): ?>
                                            <span class="discount-badge">-<?= $item['discount_percent_item'] ?? 0 ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="cart-item-price">
                                        <?php if ($is_on_sale): ?>
                                            <span class="original-price">KSH <?= number_format($item['price']) ?></span>
                                            <span class="sale-price">KSH <?= number_format($display_price) ?></span>
                                        <?php else: ?>
                                            KSH <?= number_format($display_price) ?>
                                        <?php endif; ?>
                                        × <?= $item['quantity'] ?>
                                    </div>
                                </div>
                                <div class="cart-item-total">KSH <?= number_format($item['item_total']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-section mt-3">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>KSH <?= number_format($subtotal) ?></span>
                        </div>
                        
                        <?php if ($coupon_discount > 0): ?>
                            <div class="summary-row discount-row">
                                <span>Discount (<?= $discount_percent ?>%)</span>
                                <span>- KSH <?= number_format($coupon_discount) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($coupon_discount > 0): ?>
                            <div class="summary-row">
                                <span>Subtotal after discount</span>
                                <span>KSH <?= number_format($subtotal_after_coupon) ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-row">
                            <span>Shipping</span>
                            <span><?= $shipping_cost > 0 ? 'KSH ' . number_format($shipping_cost) : 'Free' ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (16% VAT)</span>
                            <span>KSH <?= number_format($tax) ?></span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span>KSH <?= number_format($total) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($subtotal < 5000 && $subtotal > 0): ?>
                        <div class="alert alert-warning mt-3" style="font-size: 12px; background: #fef3c7; border: none; border-radius: 12px; padding: 10px;">
                            <i class="fa-solid fa-truck"></i> Add KSH <?= number_format(5000 - $subtotal) ?> more for free shipping!
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const addressField = document.querySelector('textarea[name="shipping_address"]');
    const savedAddress = "<?= htmlspecialchars($user_address) ?>";
    
    if (savedAddress && addressField && !addressField.value) {
        addressField.value = savedAddress;
    }
    
    // Handle form submission
    const form = document.getElementById('checkoutForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Show loading state
            const btn = document.getElementById('placeOrderBtn');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            
            btn.disabled = true;
            btnText.classList.add('hidden');
            btnSpinner.classList.add('active');
            
            // Let the form submit normally
            // The loading will stay until the page redirects
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>