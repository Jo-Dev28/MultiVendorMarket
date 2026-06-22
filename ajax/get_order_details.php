<?php
// Start output buffering
ob_start();

// Include header - correct path
require_once '../includes/header.php';

// Clear any output
ob_clean();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$order_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($order_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Get order details
$sql = "SELECT o.*, s.id as seller_id, s.shop_name, s.location, s.status as seller_status,
        u.name as owner_name, u.email, u.phone
        FROM orders o
        LEFT JOIN sellers s ON o.seller_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE o.id = ? AND o.user_id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Get order items with discount info
$items_sql = "SELECT oi.*, p.name, p.price as original_price, p.discounted_price, p.is_on_sale, p.discount_percent
              FROM order_items oi
              LEFT JOIN products p ON p.id = oi.product_id
              WHERE oi.order_id = ?";
$items_stmt = $mysqli->prepare($items_sql);
if (!$items_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$items_stmt->bind_param('i', $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$items_stmt->close();

echo json_encode([
    'success' => true,
    'order' => $order,
    'seller' => [
        'id' => $order['seller_id'],
        'shop_name' => $order['shop_name'],
        'location' => $order['location'],
        'status' => $order['seller_status'],
        'owner_name' => $order['owner_name'],
        'email' => $order['email'],
        'phone' => $order['phone']
    ],
    'items' => $items
]);
?>