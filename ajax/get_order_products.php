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

$order_id = intval($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($order_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

// Verify order belongs to user
$check_sql = "SELECT id FROM orders WHERE id = ? AND user_id = ?";
$check_stmt = $mysqli->prepare($check_sql);
if (!$check_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$check_stmt->bind_param('ii', $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();
$order_exists = $result->fetch_assoc();
$check_stmt->close();

if (!$order_exists) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Get products with review status
$sql = "SELECT oi.*, p.name as product_name, p.id as product_id,
        p.price as original_price,
        p.discounted_price,
        p.is_on_sale,
        p.discount_percent,
        (SELECT id FROM reviews WHERE user_id = ? AND product_id = p.id LIMIT 1) as review_id,
        (SELECT rating FROM reviews WHERE user_id = ? AND product_id = p.id LIMIT 1) as review_rating
        FROM order_items oi
        JOIN products p ON p.id = oi.product_id
        WHERE oi.order_id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('iii', $user_id, $user_id, $order_id);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

echo json_encode([
    'success' => true,
    'products' => $products
]);
?>