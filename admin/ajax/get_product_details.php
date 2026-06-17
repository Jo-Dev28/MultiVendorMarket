<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

$product_id = intval($_GET['id'] ?? 0);
if(!$product_id){ echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }

$sql = "SELECT p.*, c.name as category_name, s.shop_name FROM products p LEFT JOIN categories c ON c.id=p.category_id LEFT JOIN sellers s ON s.id=p.seller_id WHERE p.id=?";
$stmt=$mysqli->prepare($sql);
$stmt->bind_param('i',$product_id);
$stmt->execute();
$product=$stmt->get_result()->fetch_assoc();

if(!$product){ echo json_encode(['success'=>false,'message'=>'Product not found']); exit; }

$img = $mysqli->query("SELECT filename FROM product_images WHERE product_id=$product_id LIMIT 1")->fetch_assoc();
$product['image']=$img['filename']??null;

echo json_encode(['success'=>true,'product'=>$product]);
?>