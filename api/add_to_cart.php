<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Check if product exists and has stock
$sql = "SELECT stock FROM products WHERE id = ? AND status = 'approved'";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
    exit;
}

// Check if user is logged in
$user = current_user();
$is_logged_in = ($user && isset($user['id']) && $user['id']);

if ($is_logged_in) {
    // For logged-in users - use database
    // Check if already in cart
    $sql = "SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user['id'], $product_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        $new_quantity = $existing['quantity'] + $quantity;
        $sql = "UPDATE carts SET quantity = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $new_quantity, $existing['id']);
    } else {
        $sql = "INSERT INTO carts (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iii', $user['id'], $product_id, $quantity);
    }
    
    if ($stmt->execute()) {
        // Get updated cart count
        $count_sql = "SELECT SUM(quantity) as count FROM carts WHERE user_id = ?";
        $count_stmt = $mysqli->prepare($count_sql);
        $count_stmt->bind_param('i', $user['id']);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $cart_count = intval($count_result['count'] ?? 0);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added to cart',
            'cart_count' => $cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
} else {
    // For guests - use session
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    $cart_count = array_sum($_SESSION['cart']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Product added to cart',
        'cart_count' => $cart_count
    ]);
}
?>