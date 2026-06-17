<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Get order details with seller info
$order_sql = "SELECT o.*, u.name as customer_name,
              s.id as seller_id, s.shop_name, s.location, s.phone, s.status as seller_status,
              us.name as owner_name, us.email as seller_email
              FROM orders o 
              JOIN users u ON u.id = o.user_id 
              LEFT JOIN sellers s ON s.id = o.seller_id
              LEFT JOIN users us ON us.id = s.user_id
              WHERE o.id = ? AND o.user_id = ?";
$order_stmt = $mysqli->prepare($order_sql);
$order_stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Get order items
$items_sql = "SELECT oi.*, p.name, p.slug,
              (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
              FROM order_items oi
              LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ?";
$items_stmt = $mysqli->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Build seller array
$seller = null;
if ($order['seller_id']) {
    $seller = [
        'id' => $order['seller_id'],
        'shop_name' => $order['shop_name'] ?? null,
        'location' => $order['location'] ?? null,
        'phone' => $order['phone'] ?? null,
        'status' => $order['seller_status'] ?? null,
        'owner_name' => $order['owner_name'] ?? null,
        'email' => $order['seller_email'] ?? null
    ];
}

echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items,
    'seller' => $seller
]);
?>