<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['unread_count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$user = current_user();
$is_seller = ($user['role'] ?? '') === 'seller';
$unread_count = 0;

// Check if chats table exists
$table_check = $mysqli->query("SHOW TABLES LIKE 'chats'");
if ($table_check && $table_check->num_rows > 0) {
    // Check if is_read column exists
    $column_check = $mysqli->query("SHOW COLUMNS FROM chats LIKE 'is_read'");
    
    if ($is_seller) {
        // Get seller id
        $seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
        $seller_stmt = $mysqli->prepare($seller_sql);
        if ($seller_stmt) {
            $seller_stmt->bind_param('i', $user_id);
            $seller_stmt->execute();
            $seller_result = $seller_stmt->get_result();
            if ($seller_result && $seller_result->num_rows > 0) {
                $seller_data = $seller_result->fetch_assoc();
                $seller_id = $seller_data['id'];
                
                if ($column_check && $column_check->num_rows > 0) {
                    $count_sql = "SELECT COUNT(*) as count FROM chats WHERE seller_id = ? AND sender = 'user' AND is_read = 0";
                    $count_stmt = $mysqli->prepare($count_sql);
                    if ($count_stmt) {
                        $count_stmt->bind_param('i', $seller_id);
                        $count_stmt->execute();
                        $count_result = $count_stmt->get_result();
                        if ($count_result) {
                            $unread_count = $count_result->fetch_assoc()['count'] ?? 0;
                        }
                        $count_stmt->close();
                    }
                } else {
                    $count_sql = "SELECT COUNT(*) as count FROM chats WHERE seller_id = ? AND sender = 'user'";
                    $count_stmt = $mysqli->prepare($count_sql);
                    if ($count_stmt) {
                        $count_stmt->bind_param('i', $seller_id);
                        $count_stmt->execute();
                        $count_result = $count_stmt->get_result();
                        if ($count_result) {
                            $unread_count = $count_result->fetch_assoc()['count'] ?? 0;
                        }
                        $count_stmt->close();
                    }
                }
            }
            $seller_stmt->close();
        }
    } else {
        // Customer
        if ($column_check && $column_check->num_rows > 0) {
            $count_sql = "SELECT COUNT(*) as count FROM chats WHERE user_id = ? AND sender = 'seller' AND is_read = 0";
            $count_stmt = $mysqli->prepare($count_sql);
            if ($count_stmt) {
                $count_stmt->bind_param('i', $user_id);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                if ($count_result) {
                    $unread_count = $count_result->fetch_assoc()['count'] ?? 0;
                }
                $count_stmt->close();
            }
        } else {
            $count_sql = "SELECT COUNT(*) as count FROM chats WHERE user_id = ? AND sender = 'seller'";
            $count_stmt = $mysqli->prepare($count_sql);
            if ($count_stmt) {
                $count_stmt->bind_param('i', $user_id);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                if ($count_result) {
                    $unread_count = $count_result->fetch_assoc()['count'] ?? 0;
                }
                $count_stmt->close();
            }
        }
    }
}

echo json_encode(['unread_count' => $unread_count]);
?>