<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$review_id = intval($_GET['id'] ?? 0);
if (!$review_id) { echo json_encode(['success' => false, 'message' => 'Invalid ID']); exit; }

$sql = "SELECT r.*, u.name as user_name, p.name as product_name FROM reviews r JOIN users u ON u.id = r.user_id JOIN products p ON p.id = r.product_id WHERE r.id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $review_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();

if (!$review) { echo json_encode(['success' => false, 'message' => 'Review not found']); exit; }

echo json_encode(['success' => true, 'review' => $review]);
?>