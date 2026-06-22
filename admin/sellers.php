<?php
$page_title = 'Sellers Management';
require_once '../includes/header.php';

// Check if user is admin
if (($user['role'] ?? '') !== 'admin') {
    flash('Access denied. Admin only.', 'danger');
    redirect('index.php');
}

// Function to check duplicate transaction reference
function isDuplicateTransaction($mysqli, $transaction_ref, $exclude_payment_id = 0) {
    if (empty($transaction_ref)) return false;
    
    $sql = "SELECT id FROM payments WHERE transaction_reference = ? AND status = 'completed' AND id != ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $transaction_ref, $exclude_payment_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// Handle seller status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $seller_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $get_user = $mysqli->query("SELECT user_id FROM sellers WHERE id = $seller_id");
        $user_data = $get_user->fetch_assoc();
        $user_id = $user_data['user_id'];
        
        $mysqli->query("UPDATE sellers SET status = 'verified', is_active = 1, rejection_reason = NULL WHERE id = $seller_id");
        $mysqli->query("UPDATE users SET role = 'seller' WHERE id = $user_id");
        
        $pending_payment = $mysqli->query("SELECT p.* FROM payments p 
                                           JOIN orders o ON o.id = p.order_id 
                                           WHERE o.seller_id = $seller_id AND p.status = 'pending' 
                                           ORDER BY p.created_at DESC LIMIT 1")->fetch_assoc();
        
        if ($pending_payment) {
            $transaction_ref = $pending_payment['transaction_reference'] ?? '';
            if (!empty($transaction_ref) && isDuplicateTransaction($mysqli, $transaction_ref, $pending_payment['id'])) {
                $mysqli->query("UPDATE payments SET status = 'failed', notes = 'Duplicate transaction reference detected' WHERE id = {$pending_payment['id']}");
                flash('Seller approved but payment rejected due to duplicate transaction reference!', 'warning');
            } else {
                $mysqli->query("UPDATE payments SET status = 'completed' WHERE id = {$pending_payment['id']}");
                $subscription = $mysqli->query("SELECT id FROM subscriptions WHERE seller_id = $seller_id AND status = 'pending' ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
                if ($subscription) {
                    $start_date = date('Y-m-d');
                    $expiry_date = date('Y-m-d', strtotime('+30 days'));
                    $mysqli->query("UPDATE subscriptions SET status = 'active', starts_at = '$start_date', expires_at = '$expiry_date' WHERE id = {$subscription['id']}");
                }
                flash('Seller approved and payment confirmed successfully!', 'success');
            }
        } else {
            $check_sub = $mysqli->query("SELECT id FROM subscriptions WHERE seller_id = $seller_id AND status = 'pending'");
            if ($check_sub->num_rows == 0) {
                $mysqli->query("INSERT INTO subscriptions (seller_id, plan_name, amount, currency, status, starts_at, expires_at, created_at) 
                                VALUES ($seller_id, 'Pending Selection', 0, 'KSH', 'pending', NULL, NULL, NOW())");
            }
            flash('Seller approved successfully.', 'success');
        }
    } elseif ($action === 'reject') {
        $get_user = $mysqli->query("SELECT user_id FROM sellers WHERE id = $seller_id");
        $user_data = $get_user->fetch_assoc();
        $user_id = $user_data['user_id'];
        
        $mysqli->query("UPDATE sellers SET status = 'rejected', is_active = 0 WHERE id = $seller_id");
        $mysqli->query("UPDATE payments p JOIN orders o ON o.id = p.order_id SET p.status = 'failed', p.notes = 'Seller application rejected' WHERE o.seller_id = $seller_id AND p.status = 'pending'");
        
        flash('Seller rejected.', 'success');
    } elseif ($action === 'delete') {
        $get_user = $mysqli->query("SELECT user_id FROM sellers WHERE id = $seller_id");
        $user_data = $get_user->fetch_assoc();
        $user_id = $user_data['user_id'];
        
        $mysqli->query("DELETE FROM sellers WHERE id = $seller_id");
        $mysqli->query("UPDATE users SET role = 'customer' WHERE id = $user_id");
        
        flash('Seller deleted.', 'success');
    } elseif ($action === 'toggle_active') {
        $get_user = $mysqli->query("SELECT user_id, is_active FROM sellers WHERE id = $seller_id");
        $user_data = $get_user->fetch_assoc();
        $user_id = $user_data['user_id'];
        $current_status = $user_data['is_active'] ?? 1;
        $new_status = $current_status ? 0 : 1;
        
        $mysqli->query("UPDATE sellers SET is_active = $new_status WHERE id = $seller_id");
        flash('Seller ' . ($new_status ? 'activated' : 'deactivated') . ' successfully.', 'success');
    }
    redirect('admin/sellers.php');
}

// Handle document rejection - FIXED
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_document'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('admin/sellers.php');
    }
    
    $seller_id = intval($_POST['seller_id'] ?? 0);
    $document_type = sanitize($_POST['document_type'] ?? '');
    $rejection_reason = sanitize($_POST['rejection_reason'] ?? '');
    
    if ($seller_id <= 0 || empty($document_type) || empty($rejection_reason)) {
        flash('Please fill all required fields.', 'danger');
        redirect('admin/sellers.php');
    }
    
    // Map document type to column
    $column_map = [
        'id_image' => 'id_image',
        'business_license' => 'business_license',
        'tax_compliance' => 'tax_compliance',
        'bank_statement' => 'bank_statement',
        'other_document' => 'other_document'
    ];
    
    if (!isset($column_map[$document_type])) {
        flash('Invalid document type.', 'danger');
        redirect('admin/sellers.php');
    }
    
    $column = $column_map[$document_type];
    
    // Get current filename to delete
    $sql = "SELECT $column as filename FROM sellers WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $seller_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result && !empty($result['filename'])) {
            $filepath = '../uploads/seller_documents/' . $result['filename'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
    }
    
    // Update database - clear document and store rejection reason
    $sql = "UPDATE sellers SET $column = NULL, rejection_reason = ?, rejected_document = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ssi', $rejection_reason, $document_type, $seller_id);
        
        if ($stmt->execute()) {
            // Get user_id for notification - FIXED SECTION
            $user_sql = "SELECT user_id FROM sellers WHERE id = ?";
            $user_stmt = $mysqli->prepare($user_sql);
            if ($user_stmt) {
                $user_stmt->bind_param('i', $seller_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user_data = $user_result->fetch_assoc();
                $user_stmt->close();
                
                if ($user_data && isset($user_data['user_id'])) {
                    $user_id = $user_data['user_id'];
                    $doc_label = str_replace('_', ' ', $document_type);
                    
                    // Create notification for seller
                    $notif_sql = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                                  VALUES (?, 'document_rejected', 'Document Rejected', 
                                  'Your " . $doc_label . " was rejected. Reason: " . $rejection_reason . "', 0, NOW())";
                    $notif_stmt = $mysqli->prepare($notif_sql);
                    if ($notif_stmt) {
                        $notif_stmt->bind_param('i', $user_id);
                        $notif_stmt->execute();
                        $notif_stmt->close();
                    }
                }
            }
            
            flash('Document rejected successfully. Seller has been notified.', 'success');
        } else {
            flash('Failed to reject document.', 'danger');
        }
        $stmt->close();
    } else {
        flash('Database error.', 'danger');
    }
    redirect('admin/sellers.php');
}

// Handle subscription payment confirmation
if (isset($_GET['confirm_payment']) && isset($_GET['id'])) {
    $payment_id = intval($_GET['id']);
    $action = $_GET['confirm_payment'];
    
    if ($action === 'approve') {
        $payment_sql = "SELECT p.*, o.seller_id, o.user_id FROM payments p 
                        JOIN orders o ON o.id = p.order_id 
                        WHERE p.id = ?";
        $payment_stmt = $mysqli->prepare($payment_sql);
        $payment_stmt->bind_param('i', $payment_id);
        $payment_stmt->execute();
        $payment = $payment_stmt->get_result()->fetch_assoc();
        
        if ($payment) {
            $transaction_ref = $payment['transaction_reference'] ?? '';
            if (!empty($transaction_ref) && isDuplicateTransaction($mysqli, $transaction_ref, $payment_id)) {
                $mysqli->query("UPDATE payments SET status = 'failed', notes = 'Duplicate transaction reference detected' WHERE id = $payment_id");
                flash('Payment rejected - Duplicate transaction reference detected!', 'danger');
                redirect('admin/sellers.php?tab=payments');
            }
            
            $mysqli->query("UPDATE payments SET status = 'completed' WHERE id = $payment_id");
            
            $seller_check = $mysqli->query("SELECT id, status, user_id FROM sellers WHERE id = {$payment['seller_id']}");
            $seller = $seller_check->fetch_assoc();
            
            if ($seller && $seller['status'] === 'pending') {
                $mysqli->query("UPDATE sellers SET status = 'verified', is_active = 1 WHERE id = {$seller['id']}");
                $mysqli->query("UPDATE users SET role = 'seller' WHERE id = {$seller['user_id']}");
                flash('Payment confirmed and seller automatically approved!', 'success');
            }
            
            $subscription = $mysqli->query("SELECT id FROM subscriptions WHERE seller_id = {$payment['seller_id']} AND status = 'pending' ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
            if ($subscription) {
                $start_date = date('Y-m-d');
                $expiry_date = date('Y-m-d', strtotime('+30 days'));
                $mysqli->query("UPDATE subscriptions SET status = 'active', starts_at = '$start_date', expires_at = '$expiry_date' WHERE id = {$subscription['id']}");
            }
            
            flash('Payment confirmed and subscription activated.', 'success');
        }
    } elseif ($action === 'reject') {
        $mysqli->query("UPDATE payments SET status = 'failed' WHERE id = $payment_id");
        flash('Payment rejected.', 'success');
    }
    redirect('admin/sellers.php?tab=payments');
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('admin/sellers.php');
    }
    
    $seller_id = intval($_POST['seller_id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';
    $send_email = isset($_POST['send_email']) ? 1 : 0;
    
    if ($seller_id <= 0 || empty($new_password)) {
        flash('Invalid request.', 'danger');
        redirect('admin/sellers.php');
    }
    
    if (strlen($new_password) < 6) {
        flash('Password must be at least 6 characters.', 'danger');
        redirect('admin/sellers.php');
    }
    
    // Get user info
    $user_sql = "SELECT u.id, u.name, u.email FROM sellers s JOIN users u ON u.id = s.user_id WHERE s.id = ?";
    $user_stmt = $mysqli->prepare($user_sql);
    $user_stmt->bind_param('i', $seller_id);
    $user_stmt->execute();
    $user_data = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();
    
    if (!$user_data) {
        flash('Seller not found.', 'danger');
        redirect('admin/sellers.php');
    }
    
    // Update password
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
    $update_stmt = $mysqli->prepare($update_sql);
    $update_stmt->bind_param('si', $hash, $user_data['id']);
    
    if ($update_stmt->execute()) {
        $email_sent = false;
        $email_message = '';
        
        if ($send_email) {
            $site_name = defined('SITE_NAME') ? SITE_NAME : 'Multi-Vendor Marketplace';
            $admin_email = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@marketplace.local';
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $base_url = $protocol . $host . '/multi-vendor';
            
            $subject = "Your Password Has Been Reset - " . $site_name;
            $message_body = "Hello " . $user_data['name'] . ",\n\n";
            $message_body .= "Your password for " . $site_name . " has been reset by the administrator.\n\n";
            $message_body .= "New Password: " . $new_password . "\n\n";
            $message_body .= "Please log in and change your password immediately for security purposes.\n\n";
            $message_body .= "Login URL: " . $base_url . "/login.php\n\n";
            $message_body .= "Thank you,\n";
            $message_body .= $site_name . " Team";
            
            $headers = "From: " . $admin_email . "\r\n";
            $headers .= "Reply-To: " . $admin_email . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            if (mail($user_data['email'], $subject, $message_body, $headers)) {
                $email_sent = true;
            } else {
                $log_dir = __DIR__ . '/../logs/';
                if (!is_dir($log_dir)) {
                    mkdir($log_dir, 0777, true);
                }
                
                $log_file = $log_dir . 'emails.log';
                $log_entry = date('Y-m-d H:i:s') . " | To: {$user_data['email']} | Subject: $subject | Message: " . str_replace("\n", ' ', $message_body) . "\n";
                file_put_contents($log_file, $log_entry, FILE_APPEND);
                $email_message = ' (Logged to logs/emails.log)';
            }
            
            if ($email_sent) {
                flash('Password updated and email sent to seller.', 'success');
            } else {
                flash('Password updated! Email could not be sent. New password: <strong>' . $new_password . '</strong>' . $email_message, 'warning');
            }
        } else {
            flash('Password updated successfully! (Email not sent)', 'success');
        }
    } else {
        flash('Failed to update password.', 'danger');
    }
    $update_stmt->close();
    redirect('admin/sellers.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'sellers';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters for sellers
$where_clauses = [];
$params = [];
$types = '';

if ($search) {
    $where_clauses[] = "(s.shop_name LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR s.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

if ($status_filter) {
    $where_clauses[] = "s.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total sellers
$count_sql = "SELECT COUNT(*) as total FROM sellers s JOIN users u ON u.id = s.user_id $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_sellers = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_sellers / $limit);

// Get sellers
$sql = "SELECT s.*, u.name as user_name, u.email as user_email, u.role as user_role, u.created_at as user_joined
        FROM sellers s 
        JOIN users u ON u.id = s.user_id 
        $where_sql 
        ORDER BY s.created_at DESC 
        LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sellers = $stmt->get_result();

// Get pending payments
$pending_payments = $mysqli->query("SELECT p.*, o.order_number, s.shop_name, u.name as user_name, s.status as seller_status
                                    FROM payments p 
                                    JOIN orders o ON o.id = p.order_id 
                                    JOIN sellers s ON s.id = o.seller_id 
                                    JOIN users u ON u.id = s.user_id 
                                    WHERE p.status = 'pending' AND o.payment_method != 'Subscription'
                                    ORDER BY p.created_at DESC");

// Get duplicate count
$duplicate_count_sql = "SELECT COUNT(*) as count FROM (
                          SELECT transaction_reference 
                          FROM payments 
                          WHERE status = 'pending' AND transaction_reference IS NOT NULL AND transaction_reference != ''
                          GROUP BY transaction_reference 
                          HAVING COUNT(*) > 1
                        ) as duplicates";
$dup_count_result = $mysqli->query($duplicate_count_sql);
$duplicate_count = $dup_count_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        .admin-page-wrapper {
            display: flex;
            gap: 25px;
            min-height: calc(100vh - 200px);
        }
        .admin-page-sidebar {
            width: 280px;
            flex-shrink: 0;
        }
        .admin-page-content {
            flex: 1;
        }
        .filter-bar {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .search-input {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 15px;
            width: 250px;
        }
        .btn-add {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        }
        .btn-duplicate {
            background: #ef4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-duplicate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239,68,68,0.3);
        }
        
        .shop-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
            flex-shrink: 0;
            border: 2px solid #f3f4f6;
        }
        .shop-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .shop-avatar i {
            font-size: 1.2rem;
        }
        
        .role-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .role-customer { background: #3b82f6; color: white; }
        .role-seller { background: #f59e0b; color: white; }
        .role-admin { background: #ef4444; color: white; }
        
        .id-image-preview {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #e5e7eb;
        }
        .id-image-preview:hover {
            opacity: 0.8;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .status-verified { background: #d1fae5; color: #059669; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        
        .active-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .active-badge.active { background: #d1fae5; color: #059669; }
        .active-badge.inactive { background: #fee2e2; color: #dc2626; }
        
        .nav-tabs-custom {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
            display: flex;
            gap: 0;
            flex-wrap: wrap;
        }
        .nav-tab {
            padding: 10px 25px;
            font-weight: 600;
            color: #6b7280;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .nav-tab:hover {
            color: #2563eb;
        }
        .nav-tab.active {
            color: #2563eb;
            border-bottom: 2px solid #2563eb;
            margin-bottom: -2px;
        }
        .duplicate-warning {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }
        .data-table th,
        .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }
        .data-table th {
            background: #f8fafc;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .data-table tbody tr:hover {
            background: #f8fafc;
        }
        .transaction-ref {
            font-family: monospace;
            font-size: 0.75rem;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 6px;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }
        .card-body {
            padding: 0;
        }
        .btn-sm {
            padding: 4px 10px;
            font-size: 0.75rem;
            border-radius: 6px;
        }
        .modal-body .seller-logo-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e5e7eb;
        }
        
        .documents-grid-admin {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .doc-item-admin {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            position: relative;
        }
        .doc-item-admin:hover {
            border-color: #2563eb;
            transform: translateY(-2px);
        }
        .doc-item-admin .doc-icon {
            font-size: 1.5rem;
            margin-bottom: 4px;
        }
        .doc-item-admin .doc-name {
            font-size: 0.7rem;
            color: #6b7280;
        }
        .doc-item-admin .doc-status {
            font-size: 0.6rem;
            font-weight: 600;
            margin-top: 4px;
        }
        .doc-item-admin .doc-status.uploaded { color: #10b981; }
        .doc-item-admin .doc-status.missing { color: #ef4444; }
        .doc-item-admin .doc-status.rejected { color: #ef4444; }
        .doc-item-admin .btn-view-doc {
            margin-top: 5px;
            font-size: 0.65rem;
            padding: 2px 10px;
            border-radius: 4px;
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .doc-item-admin .btn-view-doc:hover {
            background: #1d4ed8;
        }
        .doc-item-admin .btn-reject-doc {
            margin-top: 5px;
            font-size: 0.65rem;
            padding: 2px 10px;
            border-radius: 4px;
            background: #ef4444;
            color: white;
            border: none;
            cursor: pointer;
            display: inline-block;
        }
        .doc-item-admin .btn-reject-doc:hover {
            background: #dc2626;
        }
        .doc-item-admin .rejection-reason {
            font-size: 0.6rem;
            color: #dc2626;
            background: #fee2e2;
            padding: 4px 8px;
            border-radius: 4px;
            margin-top: 4px;
        }
        
        @media (max-width: 992px) {
            .admin-page-wrapper { flex-direction: column; }
            .admin-page-sidebar { width: 100%; }
            .search-input { width: 100%; }
        }
        @media (max-width: 576px) {
            .data-table {
                font-size: 0.8rem;
            }
            .data-table th,
            .data-table td {
                padding: 8px 10px;
            }
            .nav-tab {
                padding: 8px 15px;
                font-size: 0.8rem;
            }
            .documents-grid-admin {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="admin-page-wrapper">
        <div class="admin-page-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="admin-page-content">
            <h2 class="mb-4"><i class="fa-solid fa-store"></i> Sellers Management</h2>
            
            <div class="nav-tabs-custom">
                <a href="?tab=sellers" class="nav-tab <?= $active_tab == 'sellers' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Sellers
                </a>
                <a href="?tab=payments" class="nav-tab <?= $active_tab == 'payments' ? 'active' : '' ?>">
                    <i class="fa-solid fa-credit-card"></i> Pending Payments
                    <?php if ($pending_payments->num_rows > 0): ?>
                        <span class="badge bg-danger ms-1"><?= $pending_payments->num_rows ?></span>
                    <?php endif; ?>
                </a>
                <?php if ($duplicate_count > 0): ?>
                    <a href="?check_duplicates=1&tab=payments" class="btn-duplicate ms-2" style="padding: 8px 15px; text-decoration: none;">
                        <i class="fa-solid fa-copy"></i> Check Duplicates (<?= $duplicate_count ?>)
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($active_tab == 'sellers'): ?>
            <div class="filter-bar">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                        <input type="hidden" name="tab" value="sellers">
                        <input type="text" name="search" class="search-input" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                        <select name="status" class="form-select w-auto" style="width: auto;">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="verified" <?= $status_filter == 'verified' ? 'selected' : '' ?>>Verified</option>
                            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <?php if ($search || $status_filter): ?>
                            <a href="?tab=sellers" class="btn btn-secondary btn-sm">Clear</a>
                        <?php endif; ?>
                    </form>
                    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addSellerModal">
                        <i class="fa-solid fa-plus"></i> Add Seller
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Seller Applications</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Shop</th>
                                    <th>Owner</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Active</th>
                                    <th>Documents</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($seller = $sellers->fetch_assoc()): 
                                    $logo_path = '';
                                    if (!empty($seller['shop_logo']) && file_exists('../uploads/sellers/' . $seller['shop_logo'])) {
                                        $logo_path = '../uploads/sellers/' . $seller['shop_logo'];
                                    }
                                ?>
                                <tr>
                                    <td><?= $seller['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="shop-avatar">
                                                <?php if ($logo_path): ?>
                                                    <img src="<?= $logo_path ?>" alt="<?= htmlspecialchars($seller['shop_name']) ?>">
                                                <?php else: ?>
                                                    <i class="fa-solid fa-store"></i>
                                                <?php endif; ?>
                                            </div>
                                            <strong><?= htmlspecialchars($seller['shop_name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($seller['user_name']) ?></td>
                                    <td><?= htmlspecialchars($seller['user_email']) ?></td>
                                    
                                    <td>
                                        <?php if ($seller['status'] === 'verified'): ?>
                                            <span class="status-badge status-verified"><i class="fa-solid fa-check-circle"></i> Verified</span>
                                        <?php elseif ($seller['status'] === 'pending'): ?>
                                            <span class="status-badge status-pending"><i class="fa-regular fa-clock"></i> Pending</span>
                                        <?php else: ?>
                                            <span class="status-badge status-rejected"><i class="fa-solid fa-times-circle"></i> Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (isset($seller['is_active']) && $seller['is_active']): ?>
                                            <span class="active-badge active"><i class="fa-solid fa-circle-check"></i> Active</span>
                                        <?php else: ?>
                                            <span class="active-badge inactive"><i class="fa-solid fa-circle-xmark"></i> Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewDocuments(<?= $seller['id'] ?>, '<?= htmlspecialchars($seller['shop_name']) ?>')" title="View Documents">
                                            <i class="fa-solid fa-file-lines"></i> Docs
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button class="btn btn-sm btn-info" onclick="viewSellerDetails(<?= $seller['id'] ?>)" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                            <?php if ($seller['status'] === 'pending'): ?>
                                                <a href="?action=approve&id=<?= $seller['id'] ?>&tab=sellers" class="btn btn-sm btn-success" onclick="return confirm('Approve this seller?')" title="Approve"><i class="fa-solid fa-check"></i></a>
                                                <a href="?action=reject&id=<?= $seller['id'] ?>&tab=sellers" class="btn btn-sm btn-danger" onclick="return confirm('Reject this seller?')" title="Reject"><i class="fa-solid fa-times"></i></a>
                                            <?php else: ?>
                                                <a href="?action=toggle_active&id=<?= $seller['id'] ?>&tab=sellers" class="btn btn-sm <?= (isset($seller['is_active']) && $seller['is_active']) ? 'btn-warning' : 'btn-success' ?>" onclick="return confirm('<?= (isset($seller['is_active']) && $seller['is_active']) ? 'Deactivate' : 'Activate' ?> this seller?')" title="<?= (isset($seller['is_active']) && $seller['is_active']) ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="fa-solid <?= (isset($seller['is_active']) && $seller['is_active']) ? 'fa-pause' : 'fa-play' ?>"></i>
                                                </a>
                                                <a href="?action=delete&id=<?= $seller['id'] ?>&tab=sellers" class="btn btn-sm btn-danger" onclick="return confirm('Delete this seller?')" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-warning" onclick="openPasswordModal(<?= $seller['id'] ?>, '<?= htmlspecialchars($seller['user_name']) ?>', '<?= htmlspecialchars($seller['user_email']) ?>')" title="Reset Password">
                                                <i class="fa-solid fa-key"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($sellers->num_rows == 0): ?>
                                <tr>
                                    <td colspan="9" class="text-center py-4">No sellers found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>&tab=sellers"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- Pending Payments -->
            <?php if ($duplicate_count > 0): ?>
            <div class="duplicate-warning">
                <i class="fa-solid fa-exclamation-triangle"></i> 
                <strong>Warning:</strong> <?= $duplicate_count ?> duplicate transaction reference(s) detected.
                <br>
                <small>Click "Check Duplicates" button above to auto-reject duplicate pending payments.</small>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa-solid fa-clock"></i> Pending Subscription Payments</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Order #</th>
                                    <th>Shop</th>
                                    <th>Seller</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction Ref</th>
                                    <th>Seller Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($payment = $pending_payments->fetch_assoc()): 
                                    $is_duplicate = false;
                                    if (!empty($payment['transaction_reference'])) {
                                        $dup_check = $mysqli->query("SELECT id FROM payments WHERE transaction_reference = '{$payment['transaction_reference']}' AND status = 'completed' LIMIT 1");
                                        $is_duplicate = $dup_check->num_rows > 0;
                                    }
                                ?>
                                <tr>
                                    <td><?= $payment['id'] ?></td>
                                    <td><strong><?= $payment['order_number'] ?></strong></td>
                                    <td><?= htmlspecialchars($payment['shop_name']) ?></td>
                                    <td><?= htmlspecialchars($payment['user_name']) ?></td>
                                    <td>KSH <?= number_format($payment['amount']) ?></td>
                                    <td><?= $payment['method'] ?></td>
                                    <td>
                                        <?php if (!empty($payment['transaction_reference'])): ?>
                                            <code class="transaction-ref"><?= htmlspecialchars($payment['transaction_reference']) ?></code>
                                            <?php if ($is_duplicate): ?>
                                                <span class="badge bg-danger ms-1">Duplicate!</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($payment['seller_status'] == 'verified'): ?>
                                            <span class="status-badge status-verified">Verified</span>
                                        <?php elseif ($payment['seller_status'] == 'pending'): ?>
                                            <span class="status-badge status-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="status-badge status-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($payment['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="?confirm_payment=approve&id=<?= $payment['id'] ?>&tab=payments" class="btn btn-sm btn-success" onclick="return confirm('Confirm this payment?')"><i class="fa-solid fa-check"></i> Approve</a>
                                            <a href="?confirm_payment=reject&id=<?= $payment['id'] ?>&tab=payments" class="btn btn-sm btn-danger" onclick="return confirm('Reject this payment?')"><i class="fa-solid fa-times"></i> Reject</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($pending_payments->num_rows == 0): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-4">No pending payments.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Seller Modal -->
<div class="modal fade" id="addSellerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="ajax/add_seller.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Seller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select User</option>
                            <?php 
                            $users = $mysqli->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY name ASC");
                            while($u = $users->fetch_assoc()): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= $u['email'] ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shop Name *</label>
                        <input type="text" name="shop_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Business ID *</label>
                        <input type="text" name="business_id" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID Document</label>
                        <input type="file" name="id_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Shop Logo</label>
                        <input type="file" name="shop_logo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_seller" class="btn btn-primary">Add Seller</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Seller Modal -->
<div class="modal fade" id="viewSellerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-store"></i> Seller Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sellerDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Loading seller details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Documents Modal -->
<div class="modal fade" id="viewDocumentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-file-lines"></i> Seller Documents</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="documentsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Loading documents...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Document Modal -->
<div class="modal fade" id="rejectDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-times-circle"></i> Reject Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="reject_document" value="1">
                    <input type="hidden" name="seller_id" id="rejectSellerId" value="">
                    <input type="hidden" name="document_type" id="rejectDocumentType" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Document</label>
                        <p id="rejectDocumentName" class="fw-bold"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Please provide a reason for rejecting this document..." required></textarea>
                        <small class="text-muted">This reason will be shown to the seller so they can fix the issue.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa-solid fa-times"></i> Reject Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-key"></i> Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="reset_password" value="1">
                    <input type="hidden" name="seller_id" id="passwordSellerId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label">Seller</label>
                        <p id="passwordSellerName" class="fw-bold"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <p id="passwordSellerEmail" class="text-muted"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="text" name="new_password" class="form-control" required minlength="6" placeholder="Enter new password (min 6 characters)">
                        <small class="text-muted">The seller will be able to log in with this password.</small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" name="send_email" class="form-check-input" id="sendEmailCheck" checked>
                        <label class="form-check-label" for="sendEmailCheck">
                            <i class="fa-regular fa-envelope"></i> Send new password to seller via email
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewSellerDetails(sellerId) {
    var modal = new bootstrap.Modal(document.getElementById('viewSellerModal'));
    modal.show();
    document.getElementById('sellerDetailsContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading seller details...</p>
        </div>
    `;
    
    fetch('ajax/get_seller_details.php?id=' + sellerId)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('Server returned: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
                let idImageHtml = '';
                let logoHtml = '';
                
                if(data.seller.shop_logo) {
                    let logoPath = '../uploads/sellers/' + data.seller.shop_logo;
                    logoHtml = `<img src="${logoPath}" class="seller-logo-preview" alt="Shop Logo">`;
                } else {
                    logoHtml = `<div class="seller-logo-preview" style="background: linear-gradient(135deg, #f59e0b, #d97706); display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem;">
                                    <i class="fa-solid fa-store"></i>
                                </div>`;
                }
                
                if(data.seller.id_image) {
                    let imgPath = data.seller.id_image.indexOf('seller_ids/') === 0 ? '../assets/uploads/' + data.seller.id_image : '../assets/uploads/seller_ids/' + data.seller.id_image;
                    idImageHtml = `
                        <div class="row">
                            <div class="col-12 mb-2">
                                <strong>ID Document:</strong>
                                <br>
                                <img src="${imgPath}" class="img-fluid rounded" style="max-width:200px; margin-top:5px; cursor:pointer;" onclick="window.open('${imgPath}', '_blank')">
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById('sellerDetailsContent').innerHTML = `
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            ${logoHtml}
                            <h5 class="mt-2">${data.seller.shop_name}</h5>
                            <span class="status-badge ${data.seller.status=='verified'?'status-verified':(data.seller.status=='pending'?'status-pending':'status-rejected')}">
                                ${data.seller.status}
                            </span>
                        </div>
                        <div class="col-md-9">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white"><strong><i class="fa-regular fa-circle-user"></i> Shop Information</strong></div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-2"><strong>Shop Name:</strong> ${data.seller.shop_name}</div>
                                        <div class="col-md-6 mb-2"><strong>Owner:</strong> ${data.user.name}</div>
                                        <div class="col-md-6 mb-2"><strong>Email:</strong> ${data.user.email}</div>
                                        <div class="col-md-6 mb-2"><strong>Phone:</strong> ${data.seller.phone}</div>
                                        <div class="col-md-6 mb-2"><strong>Business ID:</strong> ${data.seller.business_id}</div>
                                        <div class="col-md-6 mb-2"><strong>Location:</strong> ${data.seller.location || 'N/A'}</div>
                                        <div class="col-md-6 mb-2"><strong>User Role:</strong> <span class="role-badge role-${data.user.role}">${data.user.role}</span></div>
                                        <div class="col-md-6 mb-2"><strong>Applied on:</strong> ${new Date(data.seller.created_at).toLocaleDateString()}</div>
                                        <div class="col-12 mb-2"><strong>Description:</strong> ${data.seller.description || 'N/A'}</div>
                                        ${idImageHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-success text-white"><strong><i class="fa-solid fa-chart-simple"></i> Statistics</strong></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4"><h3>${data.product_count || 0}</h3><small>Products</small></div>
                                <div class="col-md-4"><h3>${data.order_count || 0}</h3><small>Orders</small></div>
                                <div class="col-md-4"><h3>KSH ${data.total_earnings || 0}</h3><small>Earnings</small></div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('sellerDetailsContent').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('sellerDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    Error loading seller details. Please try again.
                    <br><small class="text-muted">${error.message}</small>
                </div>
            `;
        });
}

function viewDocuments(sellerId, shopName) {
    var modal = new bootstrap.Modal(document.getElementById('viewDocumentsModal'));
    modal.show();
    document.getElementById('documentsContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading documents for ${shopName}...</p>
        </div>
    `;
    
    fetch('ajax/get_seller_documents.php?id=' + sellerId)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('Server returned: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if(data.success) {
                let html = `
                    <h6 class="mb-3">Documents for <strong>${data.seller.shop_name}</strong></h6>
                    <div class="documents-grid-admin">
                `;
                
                const documentTypes = {
                    'id_image': { label: 'ID Document', icon: 'fa-regular fa-id-card', color: '#2563eb' },
                    'business_license': { label: 'Business License', icon: 'fa-solid fa-certificate', color: '#f59e0b' },
                    'tax_compliance': { label: 'Tax Compliance', icon: 'fa-solid fa-file-invoice', color: '#10b981' },
                    'bank_statement': { label: 'Bank Statement', icon: 'fa-solid fa-building-columns', color: '#7c3aed' },
                    'other_document': { label: 'Other Document', icon: 'fa-solid fa-file', color: '#6b7280' }
                };
                
                let hasDocuments = false;
                const rejectedDoc = data.seller.rejected_document || null;
                const rejectionReason = data.seller.rejection_reason || null;
                
                for (const [key, doc] of Object.entries(documentTypes)) {
                    const filename = data.seller[key] || null;
                    const hasDoc = filename && filename !== '';
                    const isRejected = rejectedDoc === key;
                    const docPath = '../uploads/seller_documents/' + filename;
                    const isImage = hasDoc && ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(filename.split('.').pop().toLowerCase());
                    
                    if (hasDoc) hasDocuments = true;
                    
                    let statusClass = 'missing';
                    let statusText = '<i class="fa-solid fa-circle-xmark"></i> Missing';
                    let rejectionHtml = '';
                    
                    if (isRejected) {
                        statusClass = 'rejected';
                        statusText = '<i class="fa-solid fa-times-circle"></i> Rejected';
                        rejectionHtml = `<div class="rejection-reason"><i class="fa-solid fa-circle-exclamation"></i> ${rejectionReason || 'No reason provided'}</div>`;
                    } else if (hasDoc) {
                        statusClass = 'uploaded';
                        statusText = '<i class="fa-solid fa-check-circle"></i> Uploaded';
                    }
                    
                    html += `
                        <div class="doc-item-admin">
                            <div class="doc-icon" style="color: ${doc.color};">
                                <i class="${doc.icon}"></i>
                            </div>
                            <div class="doc-name">${doc.label}</div>
                            <div class="doc-status ${statusClass}">
                                ${statusText}
                            </div>
                            ${rejectionHtml}
                            ${hasDoc ? `
                                ${isImage ? 
                                    `<button class="btn-view-doc" onclick="window.open('${docPath}', '_blank')"><i class="fa-regular fa-eye"></i> View</button>` :
                                    `<a href="${docPath}" target="_blank" class="btn-view-doc"><i class="fa-solid fa-download"></i> Download</a>`
                                }
                                ${!isRejected ? `
                                    <button class="btn-reject-doc" onclick="openRejectModal(${sellerId}, '${key}', '${doc.label}')">
                                        <i class="fa-solid fa-times"></i> Reject
                                    </button>
                                ` : ''}
                            ` : `
                                <span class="text-muted" style="font-size:0.6rem;">Not uploaded</span>
                            `}
                        </div>
                    `;
                }
                
                if (!hasDocuments) {
                    html = `
                        <div class="text-center py-4">
                            <i class="fa-regular fa-file-lines" style="font-size:2.5rem; color:#d1d5db; margin-bottom:10px;"></i>
                            <p>No documents uploaded by this seller yet.</p>
                        </div>
                    `;
                } else {
                    html += '</div>';
                }
                
                document.getElementById('documentsContent').innerHTML = html;
            } else {
                document.getElementById('documentsContent').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('documentsContent').innerHTML = `
                <div class="alert alert-danger">
                    Error loading documents. Please try again.
                    <br><small class="text-muted">${error.message}</small>
                </div>
            `;
        });
}

function openRejectModal(sellerId, docType, docName) {
    document.getElementById('rejectSellerId').value = sellerId;
    document.getElementById('rejectDocumentType').value = docType;
    document.getElementById('rejectDocumentName').textContent = docName;
    
    var modal = new bootstrap.Modal(document.getElementById('rejectDocumentModal'));
    modal.show();
}

function openPasswordModal(sellerId, sellerName, sellerEmail) {
    document.getElementById('passwordSellerId').value = sellerId;
    document.getElementById('passwordSellerName').textContent = sellerName;
    document.getElementById('passwordSellerEmail').textContent = sellerEmail;
    
    var modal = new bootstrap.Modal(document.getElementById('passwordModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const passwordForm = document.querySelector('#passwordModal form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resetting...';
                btn.disabled = true;
            }
        });
    }
    
    const rejectForm = document.querySelector('#rejectDocumentModal form');
    if (rejectForm) {
        rejectForm.addEventListener('submit', function() {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) {
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Rejecting...';
                btn.disabled = true;
            }
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>