<?php
$page_title = 'Shopping Cart';
require_once 'includes/header.php';

$current_user = current_user();
$is_logged_in = ($current_user && isset($current_user['id']) && $current_user['id']);

// Handle remove item
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('cart.php');
    }
    
    if ($is_logged_in) {
        // Remove from database
        $sql = "DELETE FROM carts WHERE user_id = ? AND product_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $current_user['id'], $product_id);
        $stmt->execute();
    } else {
        // Remove from session
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
        }
    }
    
    flash('Item removed from cart.', 'success');
    redirect('cart.php');
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('cart.php');
    }
    
    if ($is_logged_in) {
        // Update database
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $quantity = intval($quantity);
            $product_id = intval($product_id);
            
            if ($quantity > 0) {
                $sql = "UPDATE carts SET quantity = ? WHERE user_id = ? AND product_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('iii', $quantity, $current_user['id'], $product_id);
                $stmt->execute();
            } else {
                $sql = "DELETE FROM carts WHERE user_id = ? AND product_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('ii', $current_user['id'], $product_id);
                $stmt->execute();
            }
        }
    } else {
        // Update session
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $quantity = intval($quantity);
            if ($quantity > 0) {
                $_SESSION['cart'][$product_id] = $quantity;
            } else {
                unset($_SESSION['cart'][$product_id]);
            }
        }
    }
    
    flash('Cart updated successfully.', 'success');
    redirect('cart.php');
}

// Handle clear cart
if (isset($_GET['clear'])) {
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('cart.php');
    }
    
    if ($is_logged_in) {
        $sql = "DELETE FROM carts WHERE user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $current_user['id']);
        $stmt->execute();
    } else {
        $_SESSION['cart'] = [];
    }
    
    flash('Cart cleared.', 'success');
    redirect('cart.php');
}

// Handle apply coupon (for future use)
$discount_percent = 0;
$discount_amount = 0;
$coupon_code = '';

if (isset($_POST['apply_coupon'])) {
    $coupon_code = sanitize($_POST['coupon_code'] ?? '');
    
    if (!empty($coupon_code)) {
        // Check if coupon exists (you can expand this)
        $coupon_sql = "SELECT * FROM offers WHERE code = ? AND active = 1 AND expires_at >= CURDATE()";
        $coupon_stmt = $mysqli->prepare($coupon_sql);
        $coupon_stmt->bind_param('s', $coupon_code);
        $coupon_stmt->execute();
        $coupon = $coupon_stmt->get_result()->fetch_assoc();
        $coupon_stmt->close();
        
        if ($coupon) {
            $_SESSION['coupon_code'] = $coupon_code;
            $_SESSION['discount_percent'] = $coupon['discount_percent'];
            flash('Coupon applied successfully!', 'success');
        } else {
            flash('Invalid or expired coupon code.', 'danger');
        }
    }
    redirect('cart.php');
}

// Check for existing coupon in session
if (isset($_SESSION['coupon_code']) && isset($_SESSION['discount_percent'])) {
    $coupon_code = $_SESSION['coupon_code'];
    $discount_percent = $_SESSION['discount_percent'];
}

// Get cart items
$items = [];
$subtotal = 0;

if ($is_logged_in) {
    // Get from database
    $sql = "SELECT c.*, p.name, p.price, p.stock, p.slug,
            p.is_on_sale, p.discounted_price, p.discount_percent,
            (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
            FROM carts c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $current_user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($item = $result->fetch_assoc()) {
        // Check if product has discount
        $display_price = $item['price'];
        $is_on_sale = false;
        $discount_percent_item = 0;
        
        if ($item['is_on_sale'] == 1 && $item['discounted_price'] > 0 && $item['discounted_price'] < $item['price']) {
            $display_price = $item['discounted_price'];
            $is_on_sale = true;
            $discount_percent_item = $item['discount_percent'] ?? 0;
        }
        
        $item['display_price'] = $display_price;
        $item['is_on_sale'] = $is_on_sale;
        $item['discount_percent_item'] = $discount_percent_item;
        $item['item_total'] = $display_price * $item['quantity'];
        $items[] = $item;
        $subtotal += $item['item_total'];
    }
} else {
    // Get from session
    $session_cart = $_SESSION['cart'] ?? [];
    if (!empty($session_cart)) {
        $ids = array_keys($session_cart);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.id, p.name, p.price, p.stock, p.slug,
                p.is_on_sale, p.discounted_price, p.discount_percent,
                (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
                FROM products p 
                WHERE p.id IN ($placeholders) AND p.status = 'approved'";
        
        $stmt = $mysqli->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            // Check if product has discount
            $display_price = $item['price'];
            $is_on_sale = false;
            $discount_percent_item = 0;
            
            if ($item['is_on_sale'] == 1 && $item['discounted_price'] > 0 && $item['discounted_price'] < $item['price']) {
                $display_price = $item['discounted_price'];
                $is_on_sale = true;
                $discount_percent_item = $item['discount_percent'] ?? 0;
            }
            
            $quantity = min($session_cart[$item['id']], $item['stock']);
            $item['quantity'] = $quantity;
            $item['display_price'] = $display_price;
            $item['is_on_sale'] = $is_on_sale;
            $item['discount_percent_item'] = $discount_percent_item;
            $item['item_total'] = $display_price * $quantity;
            $items[] = $item;
            $subtotal += $item['item_total'];
        }
    }
}

// Apply global coupon discount
$coupon_discount = 0;
if ($discount_percent > 0 && $subtotal > 0) {
    $coupon_discount = $subtotal * ($discount_percent / 100);
}

// Remove coupon if subtotal is 0
if ($subtotal == 0) {
    unset($_SESSION['coupon_code']);
    unset($_SESSION['discount_percent']);
    $coupon_code = '';
    $discount_percent = 0;
}

// Calculate totals
$subtotal_after_coupon = $subtotal - $coupon_discount;
$shipping_cost = ($subtotal > 0 && $subtotal < 5000) ? 250 : 0;
$tax = $subtotal_after_coupon * 0.16;
$total = $subtotal_after_coupon + $shipping_cost + $tax;
?>

<style>
    .cart-header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        padding: 40px 0;
        margin-bottom: 40px;
        border-radius: 0 0 20px 20px;
    }
    
    .cart-title {
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
    }
    
    .cart-subtitle {
        color: rgba(255,255,255,0.9);
        margin-top: 10px;
    }
    
    .cart-table-wrapper {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .cart-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .cart-table th {
        background: #f8fafc;
        padding: 15px;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .cart-table td {
        padding: 20px 15px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    
    .product-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .product-image-container {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
        background: #f3f4f6;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .product-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 5px;
    }
    
    .product-image-placeholder {
        font-size: 2rem;
        color: #9ca3af;
    }
    
    .product-name {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
    }
    
    .product-name a {
        color: inherit;
        text-decoration: none;
    }
    
    .product-name a:hover {
        color: #2563eb;
    }
    
    .discount-badge {
        display: inline-block;
        background: #fee2e2;
        color: #dc2626;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: 8px;
    }
    
    .original-price {
        text-decoration: line-through;
        color: #9ca3af;
        font-size: 0.8rem;
        margin-right: 8px;
    }
    
    .sale-price {
        color: #dc2626;
        font-weight: 700;
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .qty-btn {
        width: 30px;
        height: 30px;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .qty-btn:hover {
        background: #f3f4f6;
    }
    
    .qty-input {
        width: 50px;
        height: 30px;
        text-align: center;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
    }
    
    .price-text {
        font-weight: 600;
        color: #2563eb;
    }
    
    .remove-btn {
        background: none;
        border: none;
        color: #ef4444;
        cursor: pointer;
        font-size: 18px;
        transition: all 0.3s ease;
        padding: 5px 10px;
    }
    
    .remove-btn:hover {
        color: #dc2626;
        transform: scale(1.2);
    }
    
    .cart-summary {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }
    
    .summary-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        padding: 4px 0;
    }
    
    .summary-total {
        border-top: 2px solid #e5e7eb;
        padding-top: 15px;
        margin-top: 10px;
    }
    
    .summary-total .summary-label,
    .summary-total .summary-value {
        font-size: 18px;
        font-weight: 700;
        color: #2563eb;
    }
    
    .coupon-section {
        margin: 15px 0;
        display: flex;
        gap: 10px;
    }
    
    .coupon-section input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.9rem;
    }
    
    .coupon-section input:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    
    .coupon-section button {
        padding: 10px 20px;
        background: #f59e0b;
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .coupon-section button:hover {
        background: #d97706;
        transform: translateY(-2px);
    }
    
    .coupon-applied {
        background: #d1fae5;
        color: #059669;
        padding: 10px 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .coupon-applied .remove-coupon {
        color: #dc2626;
        cursor: pointer;
        text-decoration: none;
        font-weight: 600;
    }
    
    .checkout-btn {
        width: 100%;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 15px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        margin-top: 20px;
        transition: all 0.3s ease;
        text-decoration: none;
        display: block;
        text-align: center;
    }
    
    .checkout-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(37,99,235,0.3);
        color: white;
    }
    
    .checkout-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }
    
    .continue-shopping {
        display: block;
        text-align: center;
        color: #2563eb;
        text-decoration: none;
        margin-top: 15px;
    }
    
    .continue-shopping:hover {
        color: #1d4ed8;
    }
    
    .empty-cart {
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 20px;
    }
    
    .empty-cart i {
        font-size: 80px;
        color: #9ca3af;
        margin-bottom: 20px;
    }
    
    .cart-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .clear-cart-btn {
        background: #fee2e2;
        color: #ef4444;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .clear-cart-btn:hover {
        background: #ef4444;
        color: white;
    }
    
    .update-cart-btn {
        background: #f3f4f6;
        color: #374151;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .update-cart-btn:hover {
        background: #e5e7eb;
    }
    
    .stock-status {
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .stock-status.in-stock {
        color: #10b981;
    }
    
    .stock-status.low-stock {
        color: #f59e0b;
    }
    
    .stock-status.out-stock {
        color: #ef4444;
    }
    
    .discount-saved {
        color: #dc2626;
        font-size: 0.85rem;
        font-weight: 600;
    }
    
    @media (max-width: 768px) {
        .product-info {
            flex-direction: column;
            text-align: center;
        }
        .cart-table th:nth-child(2),
        .cart-table td:nth-child(2) {
            display: none;
        }
        .cart-actions {
            flex-direction: column;
            align-items: stretch;
        }
        .product-image-container {
            width: 60px;
            height: 60px;
        }
        .coupon-section {
            flex-direction: column;
        }
    }
</style>

<div class="cart-header">
    <div class="container">
        <h1 class="cart-title">Shopping Cart</h1>
        <p class="cart-subtitle">Review and manage your items before checkout</p>
    </div>
</div>

<div class="container mb-5">
    <?php if (empty($items)): ?>
        <div class="empty-cart">
            <i class="fa-solid fa-cart-shopping"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any items to your cart yet.</p>
            <a href="shop.php" class="checkout-btn" style="display: inline-block; width: auto; padding: 12px 30px;">
                <i class="fa-solid fa-store"></i> Continue Shopping
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="cart-table-wrapper">
                    <form method="post" id="cartForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="update_cart" value="1">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): 
                                    $has_image = !empty($item['image']) && file_exists('uploads/products/' . $item['image']);
                                    $is_on_sale = $item['is_on_sale'] ?? false;
                                    $display_price = $item['display_price'] ?? $item['price'];
                                    $original_price = $item['price'];
                                ?>
                                    <tr>
                                        <td>
                                            <div class="product-info">
                                                <div class="product-image-container">
                                                    <?php if ($has_image): ?>
                                                        <img src="uploads/products/<?= sanitize($item['image']) ?>" 
                                                             class="product-image" 
                                                             alt="<?= sanitize($item['name']) ?>"
                                                             loading="lazy">
                                                    <?php else: ?>
                                                        <div class="product-image-placeholder">
                                                            <i class="fa-solid fa-image"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="product-name">
                                                        <a href="product.php?id=<?= $item['id'] ?>"><?= sanitize($item['name']) ?></a>
                                                        <?php if ($is_on_sale): ?>
                                                            <span class="discount-badge">-<?= $item['discount_percent_item'] ?? 0 ?>% OFF</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if ($item['stock'] > 10): ?>
                                                        <span class="stock-status in-stock"><i class="fa-solid fa-check-circle"></i> In Stock</span>
                                                    <?php elseif ($item['stock'] > 0): ?>
                                                        <span class="stock-status low-stock"><i class="fa-solid fa-exclamation-triangle"></i> Only <?= $item['stock'] ?> left</span>
                                                    <?php else: ?>
                                                        <span class="stock-status out-stock"><i class="fa-solid fa-times-circle"></i> Out of Stock</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($is_on_sale): ?>
                                                <span class="original-price">KSH <?= number_format($original_price) ?></span>
                                                <span class="sale-price">KSH <?= number_format($display_price) ?></span>
                                            <?php else: ?>
                                                <span class="price-text">KSH <?= number_format($display_price) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="quantity-selector">
                                                <button type="button" class="qty-btn" onclick="updateQty('qty_<?= $item['id'] ?>', -1, <?= $item['stock'] ?>)">-</button>
                                                <input type="number" name="quantity[<?= $item['id'] ?>]" id="qty_<?= $item['id'] ?>" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>">
                                                <button type="button" class="qty-btn" onclick="updateQty('qty_<?= $item['id'] ?>', 1, <?= $item['stock'] ?>)">+</button>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="price-text">KSH <?= number_format($item['item_total']) ?></div>
                                            <?php if ($is_on_sale): ?>
                                                <div class="discount-saved">
                                                    <i class="fa-solid fa-tag"></i> Saved: KSH <?= number_format(($original_price - $display_price) * $item['quantity']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="cart.php?remove=<?= $item['id'] ?>&csrf_token=<?= csrf_token() ?>" class="remove-btn" onclick="return confirm('Remove this item from cart?')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
                <div class="cart-actions">
                    <a href="shop.php" class="continue-shopping"><i class="fa-solid fa-arrow-left"></i> Continue Shopping</a>
                    <div class="d-flex gap-2">
                        <a href="cart.php?clear=1&csrf_token=<?= csrf_token() ?>" class="clear-cart-btn" onclick="return confirm('Clear entire cart?')">
                            <i class="fa-solid fa-trash-can"></i> Clear Cart
                        </a>
                        <button type="submit" form="cartForm" class="update-cart-btn">
                            <i class="fa-solid fa-rotate"></i> Update Cart
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    
                    <!-- Coupon Section -->
                    <form method="post" class="coupon-section">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="apply_coupon" value="1">
                        <input type="text" name="coupon_code" placeholder="Enter coupon code" value="<?= sanitize($coupon_code) ?>">
                        <button type="submit"><i class="fa-solid fa-tag"></i> Apply</button>
                    </form>
                    
                    <?php if (!empty($coupon_code) && $discount_percent > 0): ?>
                        <div class="coupon-applied">
                            <span><i class="fa-solid fa-check-circle"></i> Coupon applied! <?= $discount_percent ?>% off</span>
                            <a href="cart.php?remove_coupon=1&csrf_token=<?= csrf_token() ?>" class="remove-coupon">Remove</a>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">KSH <?= number_format($subtotal) ?></span>
                    </div>
                    
                    <?php if ($coupon_discount > 0): ?>
                        <div class="summary-row" style="color: #dc2626;">
                            <span class="summary-label">Discount (<?= $discount_percent ?>%)</span>
                            <span class="summary-value">- KSH <?= number_format($coupon_discount) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-row">
                        <span class="summary-label">Subtotal after discount</span>
                        <span class="summary-value">KSH <?= number_format($subtotal_after_coupon) ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Shipping</span>
                        <span class="summary-value"><?= $shipping_cost > 0 ? 'KSH '.number_format($shipping_cost) : 'Free' ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span class="summary-label">Tax (16% VAT)</span>
                        <span class="summary-value">KSH <?= number_format($tax) ?></span>
                    </div>
                    
                    <div class="summary-row summary-total">
                        <span class="summary-label">Total</span>
                        <span class="summary-value">KSH <?= number_format($total) ?></span>
                    </div>
                    
                    <?php if ($subtotal < 5000 && $subtotal > 0): ?>
                        <div class="alert alert-warning mt-3" style="font-size: 12px; border-radius: 12px; padding: 12px;">
                            <i class="fa-solid fa-truck"></i> Add KSH <?= number_format(5000 - $subtotal) ?> more for free shipping!
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($total > 0): ?>
                        <a href="checkout.php" class="checkout-btn">
                            <i class="fa-solid fa-lock"></i> Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <button class="checkout-btn" disabled>
                            <i class="fa-solid fa-lock"></i> Cart is Empty
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function updateQty(inputId, change, maxStock) {
    const input = document.getElementById(inputId);
    let newValue = parseInt(input.value) + change;
    if (newValue < 1) newValue = 1;
    if (newValue > maxStock) newValue = maxStock;
    input.value = newValue;
}

// Auto-submit on quantity change
document.querySelectorAll('.qty-input').forEach(input => {
    input.addEventListener('change', function() {
        document.getElementById('cartForm').submit();
    });
});

function updateCartCount() {
    fetch('api/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count !== undefined) {
                document.querySelectorAll('.badge-count, #floatCartCount').forEach(el => {
                    if (el) el.textContent = data.count;
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

$(document).ready(function() {
    updateCartCount();
});

// Remove coupon handler
document.querySelector('.remove-coupon')?.addEventListener('click', function(e) {
    if (!confirm('Remove coupon from cart?')) {
        e.preventDefault();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>