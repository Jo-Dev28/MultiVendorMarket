<?php
// Start output buffering
ob_start();

// Include header - path from admin/ajax to includes
require_once '../../includes/header.php';

// Clear any output
ob_clean();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if user is admin - using 'user_role' as in your login system
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
    exit;
}

$user_id = intval($_GET['id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Get user details
$sql = "SELECT id, name, email, role, phone, address, email_verified, created_at FROM users WHERE id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// Get seller details if user is a seller
$seller = null;
$is_seller = false;

if ($user['role'] === 'seller') {
    $seller_sql = "SELECT * FROM sellers WHERE user_id = ?";
    $seller_stmt = $mysqli->prepare($seller_sql);
    if ($seller_stmt) {
        $seller_stmt->bind_param('i', $user_id);
        $seller_stmt->execute();
        $seller = $seller_stmt->get_result()->fetch_assoc();
        $seller_stmt->close();
        $is_seller = true;
    }
}

echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'phone' => $user['phone'],
        'address' => $user['address'],
        'email_verified' => (bool)$user['email_verified'],
        'created_at' => $user['created_at']
    ],
    'is_seller' => $is_seller,
    'seller' => $seller
]);
?>