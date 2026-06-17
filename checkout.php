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
$sql = "SELECT c.*, p.id as product_id, p.name, p.price, p.stock, p.slug, s.shop_name, s.id as seller_id
        FROM carts c
        JOIN products p ON p.id = c.product_id
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE c.user_id = ? AND p.status = 'approved'";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['item_total'] = $row['price'] * $row['quantity'];
    $cart_items[] = $row;
    $subtotal += $row['item_total'];
}

// If no items in database, check session
if (empty($cart_items) && isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $session_cart = $_SESSION['cart'];
    $ids = array_keys($session_cart);
    
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.*, p.id as product_id, s.shop_name, s.id as seller_id 
                FROM products p
                LEFT JOIN sellers s ON p.seller_id = s.id
                WHERE p.id IN ($placeholders) AND p.status = 'approved'";
        $stmt = $mysqli->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $quantity = $session_cart[$row['id']];
            $row['quantity'] = $quantity;
            $row['item_total'] = $row['price'] * $quantity;
            $cart_items[] = $row;
            $subtotal += $row['item_total'];
        }
    }
}

if (empty($cart_items)) {
    flash('Your cart is empty. Please add items before checkout.', 'warning');
    redirect('cart.php');
}

// Calculate totals
$shipping_cost = ($subtotal > 0 && $subtotal < 5000) ? 250 : 0;
$tax = $subtotal * 0.16;
$total = $subtotal + $shipping_cost + $tax;

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
                        // Insert order item
                        $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)";
                        $item_stmt = $mysqli->prepare($item_sql);
                        $item_stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
                        
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
            
            // Clear session cart
            unset($_SESSION['cart']);
            
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
    
    /* Success Overlay */
    .success-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .success-overlay.active {
        display: flex;
    }
    
    .success-modal {
        background: white;
        border-radius: 24px;
        padding: 50px;
        max-width: 500px;
        width: 90%;
        text-align: center;
        animation: slideUp 0.5s ease;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    }
    
    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .success-icon {
        font-size: 4rem;
        color: #10b981;
        margin-bottom: 20px;
        animation: scaleIn 0.5s ease 0.3s both;
    }
    
    @keyframes scaleIn {
        from {
            transform: scale(0);
        }
        to {
            transform: scale(1);
        }
    }
    
    .success-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 10px;
    }
    
    .success-message {
        color: #6b7280;
        margin-bottom: 25px;
        line-height: 1.6;
    }
    
    .success-order-number {
        background: #f3f4f6;
        padding: 10px 20px;
        border-radius: 12px;
        font-family: monospace;
        font-weight: 600;
        font-size: 1.1rem;
        color: #2563eb;
        display: inline-block;
        margin-bottom: 25px;
    }
    
    .success-loading-bar {
        width: 100%;
        height: 4px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 20px;
    }
    
    .success-loading-bar .progress {
        height: 100%;
        background: linear-gradient(90deg, #10b981, #059669);
        border-radius: 4px;
        width: 0%;
        animation: progressBar 5s ease forwards;
    }
    
    @keyframes progressBar {
        0% { width: 0%; }
        100% { width: 100%; }
    }
    
    .btn-view-order {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-view-order:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        color: white;
    }
    
    .btn-continue-shop {
        background: #f3f4f6;
        color: #374151;
        border: none;
        padding: 12px 30px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
        margin-left: 10px;
    }
    
    .btn-continue-shop:hover {
        background: #e5e7eb;
    }
    
    @media (max-width: 768px) {
        .checkout-title { font-size: 1.5rem; }
        .payment-methods { flex-direction: column; }
        .order-summary-card { position: static; margin-top: 20px; }
        .success-modal { padding: 30px; }
        .success-title { font-size: 1.4rem; }
        .btn-continue-shop { margin-left: 0; margin-top: 10px; }
    }
</style>

<!-- Success Overlay -->
<div class="success-overlay" id="successOverlay">
    <div class="success-modal">
        <div class="success-icon">
            <i class="fa-regular fa-circle-check"></i>
        </div>
        <h3 class="success-title">Order Placed Successfully!</h3>
        <p class="success-message" id="successMessage">Your order has been placed and is being processed.</p>
        <div class="success-order-number" id="orderNumberDisplay">#ORD-XXXXXX</div>
        <div class="success-loading-bar">
            <div class="progress" id="progressBar"></div>
        </div>
        <div id="actionButtons" style="display: none;">
            <a href="#" class="btn-view-order" id="viewOrderBtn">
                <i class="fa-regular fa-eye"></i> View Order
            </a>
            <a href="shop.php" class="btn-continue-shop">
                <i class="fa-solid fa-store"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>

<!-- Page Header -->
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
                    
                    <div class="cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div>
                                    <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="cart-item-price">KSH <?= number_format($item['price']) ?> × <?= $item['quantity'] ?></div>
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

// Check if order was just placed successfully (from session)
<?php if (isset($_SESSION['last_order_id']) && isset($_GET['show_success'])): 
    $order_id = $_SESSION['last_order_id'];
    // Get order number
    $order_sql = "SELECT order_number FROM orders WHERE id = ?";
    $order_stmt = $mysqli->prepare($order_sql);
    $order_stmt->bind_param('i', $order_id);
    $order_stmt->execute();
    $order_result = $order_stmt->get_result();
    $order_data = $order_result->fetch_assoc();
    $order_number = $order_data['order_number'] ?? 'ORD-' . $order_id;
    unset($_SESSION['last_order_id']);
?>
    // Show success overlay
    showSuccessOverlay(<?= $order_id ?>, '<?= $order_number ?>');
<?php endif; ?>

function showSuccessOverlay(orderId, orderNumber) {
    const overlay = document.getElementById('successOverlay');
    const orderNumberDisplay = document.getElementById('orderNumberDisplay');
    const viewOrderBtn = document.getElementById('viewOrderBtn');
    const actionButtons = document.getElementById('actionButtons');
    const progressBar = document.getElementById('progressBar');
    const successMessage = document.getElementById('successMessage');
    
    // Set order number
    orderNumberDisplay.textContent = '#' + orderNumber;
    
    // Show overlay
    overlay.classList.add('active');
    
    // Start progress bar animation
    progressBar.style.animation = 'progressBar 5s ease forwards';
    
    // Show action buttons after 5 seconds
    setTimeout(function() {
        // Hide progress bar
        document.querySelector('.success-loading-bar').style.display = 'none';
        
        // Show action buttons
        actionButtons.style.display = 'block';
        
        // Set view order link
        viewOrderBtn.href = 'receipt.php?id=' + orderId;
    }, 5000);
}
</script>

<?php require_once 'includes/footer.php'; ?>