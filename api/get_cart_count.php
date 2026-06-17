<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';

$user = current_user();
$is_logged_in = ($user && isset($user['id']) && $user['id']);

$count = 0;

if ($is_logged_in) {
    // Get from database for logged-in users
    $sql = "SELECT SUM(quantity) as total FROM carts WHERE user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = intval($row['total'] ?? 0);
} else {
    // Get from session for guests
    $cart = $_SESSION['cart'] ?? [];
    $count = array_sum($cart);
}

echo json_encode(['success' => true, 'count' => $count]);
?>