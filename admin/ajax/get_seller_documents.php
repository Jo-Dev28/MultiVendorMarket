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

// Check if user is admin
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
    exit;
}

$seller_id = intval($_GET['id'] ?? 0);

if (!$seller_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid seller ID']);
    exit;
}

// Get seller details with all document fields and rejection info
$sql = "SELECT id, shop_name, id_image, business_license, tax_compliance, bank_statement, other_document, 
        rejection_reason, rejected_document 
        FROM sellers WHERE id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param('i', $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$seller) {
    echo json_encode(['success' => false, 'message' => 'Seller not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'seller' => $seller
]);
?>