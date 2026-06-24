<?php
// Start output buffering
ob_start();

// Include header - correct path from seller/ajax
require_once '../../includes/header.php';

// Clear any output
ob_clean();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$review_id = intval($_GET['id'] ?? 0);

if ($review_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid review ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get seller ID
$seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
if (!$seller_stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();
$seller = $seller_result->fetch_assoc();
$seller_stmt->close();

if (!$seller) {
    echo json_encode(['success' => false, 'message' => 'Seller not found']);
    exit;
}

$seller_id = $seller['id'];

// Get review details with product and user info - only if product belongs to this seller
$sql = "SELECT r.*, p.name as product_name, p.seller_id,
        u.name as customer_name, u.email as customer_email
        FROM reviews r
        JOIN products p ON p.id = r.product_id
        JOIN users u ON u.id = r.user_id
        WHERE r.id = ? AND p.seller_id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param('ii', $review_id, $seller_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$review) {
    echo json_encode(['success' => false, 'message' => 'Review not found or you do not have permission']);
    exit;
}

echo json_encode([
    'success' => true,
    'review' => $review
]);
?>