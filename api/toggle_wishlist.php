<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
$user = current_user();

if (!$user || !$user['id']) {
    echo json_encode(['success' => false, 'message' => 'Please login to manage wishlist.']);
    exit;
}

$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Check if already in wishlist
$sql = "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $user['id'], $product_id);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();

if ($exists) {
    // Remove from wishlist
    $sql = "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user['id'], $product_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'added' => false, 'message' => 'Removed from wishlist']);
} else {
    // Add to wishlist
    $sql = "INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user['id'], $product_id);
    $stmt->execute();
    echo json_encode(['success' => true, 'added' => true, 'message' => 'Added to wishlist']);
}
?>