<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in and is seller
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Get seller info
$seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $_SESSION['user_id']);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    echo json_encode(['success' => false, 'message' => 'Seller not found']);
    exit;
}

// Get order details with customer email and phone
$order_sql = "SELECT o.*, 
              u.name as customer_name, 
              u.email as customer_email, 
              u.phone as customer_phone
              FROM orders o 
              JOIN users u ON u.id = o.user_id 
              WHERE o.id = ? AND o.seller_id = ?";
$order_stmt = $mysqli->prepare($order_sql);
$order_stmt->bind_param('ii', $order_id, $seller['id']);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Get order items with product details
$items_sql = "SELECT oi.*, p.name, p.slug, 
              (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
              FROM order_items oi
              LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ?";
$items_stmt = $mysqli->prepare($items_sql);
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    if (empty($item['name'])) {
        $item['name'] = 'Product #' . $item['product_id'];
    }
    $items[] = $item;
}

// Build response
$response = [
    'success' => true,
    'order' => [
        'id' => $order['id'],
        'order_number' => $order['order_number'],
        'total_amount' => $order['total_amount'],
        'payment_method' => $order['payment_method'],
        'status' => $order['status'],
        'shipping_address' => $order['shipping_address'],
        'created_at' => $order['created_at'],
        'user_id' => $order['user_id'],
        'customer_name' => $order['customer_name'] ?? 'N/A',
        'customer_email' => $order['customer_email'] ?? 'N/A',
        'customer_phone' => $order['customer_phone'] ?? 'N/A'
    ],
    'items' => $items
];

echo json_encode($response);
?>