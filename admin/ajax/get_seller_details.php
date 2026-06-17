<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

$seller_id = intval($_GET['id'] ?? 0);
if(!$seller_id){ echo json_encode(['success'=>false,'message'=>'Invalid ID']); exit; }

$seller_sql = "SELECT s.*, u.name, u.email FROM sellers s JOIN users u ON u.id=s.user_id WHERE s.id=?";
$stmt=$mysqli->prepare($seller_sql);
$stmt->bind_param('i',$seller_id);
$stmt->execute();
$seller=$stmt->get_result()->fetch_assoc();

if(!$seller){ echo json_encode(['success'=>false,'message'=>'Seller not found']); exit; }

$product_count = $mysqli->query("SELECT COUNT(*) as c FROM products WHERE seller_id=$seller_id")->fetch_assoc()['c'];
$order_count = $mysqli->query("SELECT COUNT(*) as c FROM orders WHERE seller_id=$seller_id")->fetch_assoc()['c'];
$total_earnings = $mysqli->query("SELECT SUM(total_amount) as t FROM orders WHERE seller_id=$seller_id AND status='delivered'")->fetch_assoc()['t']??0;

echo json_encode(['success'=>true,'seller'=>$seller,'user'=>['name'=>$seller['name'],'email'=>$seller['email']],'product_count'=>$product_count,'order_count'=>$order_count,'total_earnings'=>number_format($total_earnings)]);
?>