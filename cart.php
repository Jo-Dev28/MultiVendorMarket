<?php
$page_title = 'Shopping Cart';
require_once 'includes/header.php';

$current_user = current_user();
$is_logged_in = ($current_user && isset($current_user['id']) && $current_user['id']);

// Handle remove item
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    
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

// Get cart items
$items = [];
$subtotal = 0;

if ($is_logged_in) {
    // Get from database
    $sql = "SELECT c.*, p.name, p.price, p.stock, p.slug,
            (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
            FROM carts c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $current_user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($item = $result->fetch_assoc()) {
        $item['item_total'] = $item['price'] * $item['quantity'];
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
                (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
                FROM products p 
                WHERE p.id IN ($placeholders) AND p.status = 'approved'";
        
        $stmt = $mysqli->prepare($sql);
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($item = $result->fetch_assoc()) {
            $quantity = min($session_cart[$item['id']], $item['stock']);
            $item['quantity'] = $quantity;
            $item['item_total'] = $item['price'] * $quantity;
            $items[] = $item;
            $subtotal += $item['item_total'];
        }
    }
}

// Calculate totals
$shipping_cost = ($subtotal > 0 && $subtotal < 5000) ? 250 : 0;
$tax = $subtotal * 0.16;
$total = $subtotal + $shipping_cost + $tax;
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
        margin-bottom: 15px;
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
                                        <td class="price-text">KSH <?= number_format($item['price']) ?></td>
                                        <td>
                                            <div class="quantity-selector">
                                                <button type="button" class="qty-btn" onclick="updateQty('qty_<?= $item['id'] ?>', -1, <?= $item['stock'] ?>)">-</button>
                                                <input type="number" name="quantity[<?= $item['id'] ?>]" id="qty_<?= $item['id'] ?>" class="qty-input" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>">
                                                <button type="button" class="qty-btn" onclick="updateQty('qty_<?= $item['id'] ?>', 1, <?= $item['stock'] ?>)">+</button>
                                            </div>
                                        </td>
                                        <td class="price-text">KSH <?= number_format($item['item_total']) ?></td>
                                        <td>
                                            <a href="cart.php?remove=<?= $item['id'] ?>" class="remove-btn" onclick="return confirm('Remove this item?')">
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
                        <button type="button" class="clear-cart-btn" onclick="if(confirm('Clear entire cart?')) window.location.href='cart.php?clear=1'">
                            <i class="fa-solid fa-trash-can"></i> Clear Cart
                        </button>
                        <button type="submit" form="cartForm" name="update_cart" class="update-cart-btn">
                            <i class="fa-solid fa-rotate"></i> Update Cart
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h3 class="summary-title">Order Summary</h3>
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">KSH <?= number_format($subtotal) ?></span>
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
                    <a href="checkout.php" class="checkout-btn">
                        <i class="fa-solid fa-lock"></i> Proceed to Checkout
                    </a>
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
</script>

<?php require_once 'includes/footer.php'; ?>