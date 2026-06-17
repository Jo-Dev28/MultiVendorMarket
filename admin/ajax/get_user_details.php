<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

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
    $seller_stmt->bind_param('i', $user_id);
    $seller_stmt->execute();
    $seller = $seller_stmt->get_result()->fetch_assoc();
    $is_seller = true;
}

echo json_encode([
    'success' => true,
    'user' => $user,
    'is_seller' => $is_seller,
    'seller' => $seller
]);
?>