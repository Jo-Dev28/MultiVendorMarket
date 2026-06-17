<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ' . BASE_URL . 'admin/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_seller'])) {
    $user_id = intval($_POST['user_id']);
    $shop_name = sanitize($_POST['shop_name']);
    $phone = sanitize($_POST['phone']);
    $business_id = sanitize($_POST['business_id']);
    $location = sanitize($_POST['location'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    if (!$user_id || !$shop_name || !$phone || !$business_id) {
        flash('Please fill all required fields.', 'danger');
        redirect('admin/sellers.php');
    }
    
    // Check if user already has a seller record
    $check = $mysqli->query("SELECT id FROM sellers WHERE user_id = $user_id");
    if ($check->num_rows > 0) {
        flash('This user is already a seller.', 'danger');
        redirect('admin/sellers.php');
    }
    
    // Insert seller
    $sql = "INSERT INTO sellers (user_id, shop_name, phone, business_id, location, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('isssss', $user_id, $shop_name, $phone, $business_id, $location, $description);
    
    if ($stmt->execute()) {
        flash('Seller added successfully. They need approval to become verified.', 'success');
    } else {
        flash('Failed to add seller.', 'danger');
    }
    
    redirect('admin/sellers.php');
} else {
    redirect('admin/sellers.php');
}
?>