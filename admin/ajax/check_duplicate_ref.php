<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$transaction_ref = $_POST['transaction_ref'] ?? '';
$payment_id = intval($_POST['payment_id'] ?? 0);

if (empty($transaction_ref)) {
    echo json_encode(['is_duplicate' => false]);
    exit;
}

// Check if transaction reference exists in completed payments
$sql = "SELECT id FROM payments WHERE transaction_reference = ? AND status = 'completed' AND id != ? LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('si', $transaction_ref, $payment_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode(['is_duplicate' => $result->num_rows > 0]);
?>