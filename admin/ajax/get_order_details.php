<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user is admin - FIXED ROLE CHECK
$user_id = $_SESSION['user_id'];

// Get user role from database
$role_sql = "SELECT role FROM users WHERE id = ?";
$role_stmt = $mysqli->prepare($role_sql);
$role_stmt->bind_param('i', $user_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();
$user_data = $role_result->fetch_assoc();

if (!$user_data || $user_data['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin only.']);
    exit;
}

$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Get order details with seller info - NO user_id restriction for admin
$order_sql = "SELECT o.*, 
              u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
              s.id as seller_id, s.shop_name, s.location as seller_location, 
              s.phone as seller_phone, s.status as seller_status,
              us.name as seller_owner_name, us.email as seller_email
              FROM orders o 
              JOIN users u ON u.id = o.user_id 
              LEFT JOIN sellers s ON s.id = o.seller_id
              LEFT JOIN users us ON us.id = s.user_id
              WHERE o.id = ?";
$order_stmt = $mysqli->prepare($order_sql);
$order_stmt->bind_param('i', $order_id);
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
$items_result = $items_stmt->get_result();

$items = [];
while ($item = $items_result->fetch_assoc()) {
    // If product name is null, use a fallback
    if (empty($item['name'])) {
        $item['name'] = 'Product #' . $item['product_id'];
    }
    $items[] = $item;
}

// Build seller array
$seller = null;
if ($order['seller_id']) {
    $seller = [
        'id' => $order['seller_id'],
        'shop_name' => $order['shop_name'] ?? 'N/A',
        'location' => $order['seller_location'] ?? 'N/A',
        'phone' => $order['seller_phone'] ?? 'N/A',
        'status' => $order['seller_status'] ?? 'pending',
        'owner_name' => $order['seller_owner_name'] ?? 'N/A',
        'email' => $order['seller_email'] ?? 'N/A'
    ];
}

echo json_encode([
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
    'items' => $items,
    'seller' => $seller
]);
?>