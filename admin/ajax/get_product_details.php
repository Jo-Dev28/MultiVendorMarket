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

$product_id = intval($_GET['id'] ?? 0);

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Get product details with seller info
$sql = "SELECT p.*, c.name as category_name, s.shop_name, s.shop_logo, s.phone as seller_phone, 
        s.location as seller_location, s.description as seller_description, s.id as seller_id,
        (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as main_image
        FROM products p 
        LEFT JOIN categories c ON c.id = p.category_id 
        LEFT JOIN sellers s ON s.id = p.seller_id 
        WHERE p.id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Get all product images
$images_sql = "SELECT filename FROM product_images WHERE product_id = ? ORDER BY id ASC";
$images_stmt = $mysqli->prepare($images_sql);
$images_stmt->bind_param('i', $product_id);
$images_stmt->execute();
$images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$images_stmt->close();

echo json_encode([
    'success' => true,
    'product' => $product,
    'images' => $images,
    'seller' => [
        'shop_name' => $product['shop_name'],
        'shop_logo' => $product['shop_logo'],
        'seller_phone' => $product['seller_phone'],
        'seller_location' => $product['seller_location'],
        'seller_description' => $product['seller_description'],
        'seller_id' => $product['seller_id']
    ]
]);
?>