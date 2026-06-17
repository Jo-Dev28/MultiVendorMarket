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
        
        $mysqli->query("UPDATE sellers SET status = 'verified' WHERE id = $seller_id");
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
        
        $mysqli->query("UPDATE sellers SET status = 'rejected' WHERE id = $seller_id");
        $mysqli->query("UPDATE payments p JOIN orders o ON o.id = p.order_id SET p.status = 'failed', p.notes = 'Seller application rejected' WHERE o.seller_id = $seller_id AND p.status = 'pending'");
        
        flash('Seller rejected.', 'success');
    } elseif ($action === 'delete') {
        $get_user = $mysqli->query("SELECT user_id FROM sellers WHERE id = $seller_id");
        $user_data = $get_user->fetch_assoc();
        $user_id = $user_data['user_id'];
        
        $mysqli->query("DELETE FROM sellers WHERE id = $seller_id");
        $mysqli->query("UPDATE users SET role = 'customer' WHERE id = $user_id");
        
        flash('Seller deleted.', 'success');
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
                $mysqli->query("UPDATE sellers SET status = 'verified' WHERE id = {$seller['id']}");
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

// Auto-check for duplicate transaction references
if (isset($_GET['check_duplicates'])) {
    $duplicate_sql = "SELECT transaction_reference, COUNT(*) as count, GROUP_CONCAT(id) as ids 
                      FROM payments 
                      WHERE status = 'pending' AND transaction_reference IS NOT NULL AND transaction_reference != ''
                      GROUP BY transaction_reference 
                      HAVING COUNT(*) > 1";
    $duplicates = $mysqli->query($duplicate_sql);
    
    while ($dup = $duplicates->fetch_assoc()) {
        $ids = explode(',', $dup['ids']);
        for ($i = 1; $i < count($ids); $i++) {
            $mysqli->query("UPDATE payments SET status = 'failed', notes = 'Duplicate transaction reference' WHERE id = {$ids[$i]}");
        }
        flash('Duplicate transaction references have been auto-rejected.', 'warning');
    }
    redirect('admin/sellers.php?tab=payments');
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
        }
        .shop-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
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
        .nav-tabs-custom {
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 20px;
            display: flex;
            gap: 0;
        }
        .nav-tab {
            padding: 10px 25px;
            font-weight: 600;
            color: #6b7280;
            text-decoration: none;
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
        }
        .data-table th {
            background: #f8fafc;
            font-weight: 600;
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
        }
        .card-header {
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: white;
        }
        .card-body {
            padding: 0;
        }
        @media (max-width: 992px) {
            .admin-page-wrapper { flex-direction: column; }
            .admin-page-sidebar { width: 100%; }
            .search-input { width: 100%; }
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
            
            <!-- Tabs -->
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
            <!-- Sellers List -->
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
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Shop</th>
                                    <th>Owner</th>
                                    <th>Email</th>
                                    <th>User Role</th>
                                    <th>Status</th>
                                    <th>ID Document</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($seller = $sellers->fetch_assoc()): ?>
                                <?php 
                                $id_image_path = '';
                                if (!empty($seller['id_image'])) {
                                    if (strpos($seller['id_image'], 'seller_ids/') === 0) {
                                        $id_image_path = '../assets/uploads/' . $seller['id_image'];
                                    } else {
                                        $id_image_path = '../assets/uploads/seller_ids/' . $seller['id_image'];
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= $seller['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="shop-avatar"><i class="fa-solid fa-store"></i></div>
                                            <strong><?= htmlspecialchars($seller['shop_name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($seller['user_name']) ?></td>
                                    <td><?= htmlspecialchars($seller['user_email']) ?></td>
                                    <td>
                                        <span class="role-badge role-<?= $seller['user_role'] ?>">
                                            <?= ucfirst($seller['user_role']) ?>
                                        </span>
                                    </td>
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
                                        <?php if (!empty($seller['id_image']) && file_exists(str_replace('../', '', $id_image_path))): ?>
                                            <img src="<?= $id_image_path ?>" class="id-image-preview" alt="ID" onclick="window.open('<?= $id_image_path ?>', '_blank')" style="width:45px;height:45px;object-fit:cover;border-radius:8px;cursor:pointer;">
                                        <?php else: ?>
                                            <span class="text-muted">No ID</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info" onclick="viewSellerDetails(<?= $seller['id'] ?>)" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                            <?php if ($seller['status'] === 'pending'): ?>
                                                <a href="?action=approve&id=<?= $seller['id'] ?>&tab=sellers" class="btn btn-sm btn-success" onclick="return confirm('Approve this seller?')"><i class="fa-solid fa-check"></i></a>
                                                <a href="?action=reject&id=<?= $seller['id'] ?>&tab=sellers" class="btn btn-sm btn-danger" onclick="return confirm('Reject this seller?')"><i class="fa-solid fa-times"></i></a>
                                            <?php else: ?>
                                                <a href="?action=delete&id=<?= $seller['id'] ?>&tab=sellers" class="btn btn-sm btn-danger" onclick="return confirm('Delete this seller?')"><i class="fa-solid fa-trash"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if ($sellers->num_rows == 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No sellers found.</td>
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
                                        <div class="d-flex gap-1">
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
                <h5 class="modal-title">Seller Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="sellerDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function viewSellerDetails(sellerId) {
    var modal = new bootstrap.Modal(document.getElementById('viewSellerModal'));
    modal.show();
    fetch('ajax/get_seller_details.php?id=' + sellerId)
        .then(response => response.json())
        .then(data => {
            if(data.success){
                let idImageHtml = '';
                if(data.seller.id_image){
                    let imgPath = data.seller.id_image.indexOf('seller_ids/') === 0 ? '../assets/uploads/' + data.seller.id_image : '../assets/uploads/seller_ids/' + data.seller.id_image;
                    idImageHtml = '<div class="row"><div class="col-12 mb-2"><strong>ID Document:</strong><br><img src="' + imgPath + '" style="max-width:200px;border-radius:8px;margin-top:5px;cursor:pointer;" onclick="window.open(\'' + imgPath + '\', \'_blank\')"></div></div>';
                }
                document.getElementById('sellerDetailsContent').innerHTML = `
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white"><strong>Shop Information</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2"><strong>Shop Name:</strong> ${data.seller.shop_name}</div>
                                <div class="col-md-6 mb-2"><strong>Owner:</strong> ${data.user.name}</div>
                                <div class="col-md-6 mb-2"><strong>Email:</strong> ${data.user.email}</div>
                                <div class="col-md-6 mb-2"><strong>Phone:</strong> ${data.seller.phone}</div>
                                <div class="col-md-6 mb-2"><strong>Business ID:</strong> ${data.seller.business_id}</div>
                                <div class="col-md-6 mb-2"><strong>Location:</strong> ${data.seller.location || 'N/A'}</div>
                                <div class="col-md-6 mb-2"><strong>Status:</strong> <span class="badge ${data.seller.status=='verified'?'bg-success':(data.seller.status=='pending'?'bg-warning':'bg-danger')}">${data.seller.status}</span></div>
                                <div class="col-md-6 mb-2"><strong>User Role:</strong> <span class="role-badge role-${data.user.role}">${data.user.role}</span></div>
                                <div class="col-md-6 mb-2"><strong>Applied on:</strong> ${new Date(data.seller.created_at).toLocaleDateString()}</div>
                                <div class="col-12 mb-2"><strong>Description:</strong> ${data.seller.description || 'N/A'}</div>
                                ${idImageHtml}
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header bg-success text-white"><strong>Statistics</strong></div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-md-4"><h3>${data.product_count}</h3><small>Products</small></div>
                                <div class="col-md-4"><h3>${data.order_count}</h3><small>Orders</small></div>
                                <div class="col-md-4"><h3>KSH ${data.total_earnings}</h3><small>Earnings</small></div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('sellerDetailsContent').innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('sellerDetailsContent').innerHTML = '<div class="alert alert-danger">Error loading details.</div>';
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>