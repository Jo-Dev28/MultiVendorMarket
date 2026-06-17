<?php
$page_title = 'Payments Management';
require_once '../includes/header.php';

if (($user['role'] ?? '') !== 'admin') { flash('Access denied.', 'danger'); redirect('index.php'); }

// Handle payment status update with duplicate check
if (isset($_POST['update_status'])) {
    $payment_id = intval($_POST['payment_id']);
    $status = sanitize($_POST['status']);
    $transaction_ref = isset($_POST['transaction_reference']) ? sanitize($_POST['transaction_reference']) : '';
    
    // If approving with transaction reference, check for duplicates
    if ($status === 'completed' && !empty($transaction_ref)) {
        // Check if transaction reference already exists in completed payments
        $check_sql = "SELECT id, payment_id FROM payments WHERE transaction_reference = ? AND status = 'completed' AND id != ?";
        $check_stmt = $mysqli->prepare($check_sql);
        $check_stmt->bind_param('si', $transaction_ref, $payment_id);
        $check_stmt->execute();
        $duplicate = $check_stmt->get_result()->fetch_assoc();
        
        if ($duplicate) {
            // Auto-reject this payment
            $mysqli->query("UPDATE payments SET status = 'failed', notes = 'Duplicate transaction reference' WHERE id = $payment_id");
            flash('Payment rejected - Duplicate transaction reference detected!', 'danger');
            redirect('admin/payments.php');
        }
    }
    
    // Update payment status
    $update_sql = "UPDATE payments SET status = ? WHERE id = ?";
    $update_stmt = $mysqli->prepare($update_sql);
    $update_stmt->bind_param('si', $status, $payment_id);
    $update_stmt->execute();
    
    // If payment is completed, activate subscription
    if ($status === 'completed') {
        // Get order details
        $order_sql = "SELECT o.seller_id FROM payments p JOIN orders o ON o.id = p.order_id WHERE p.id = ?";
        $order_stmt = $mysqli->prepare($order_sql);
        $order_stmt->bind_param('i', $payment_id);
        $order_stmt->execute();
        $order = $order_stmt->get_result()->fetch_assoc();
        
        if ($order) {
            // Get pending subscription
            $sub_sql = "SELECT id FROM subscriptions WHERE seller_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1";
            $sub_stmt = $mysqli->prepare($sub_sql);
            $sub_stmt->bind_param('i', $order['seller_id']);
            $sub_stmt->execute();
            $subscription = $sub_stmt->get_result()->fetch_assoc();
            
            if ($subscription) {
                $start_date = date('Y-m-d');
                $expiry_date = date('Y-m-d', strtotime('+30 days'));
                $mysqli->query("UPDATE subscriptions SET status = 'active', starts_at = '$start_date', expires_at = '$expiry_date' WHERE id = {$subscription['id']}");
            }
        }
        flash('Payment approved and subscription activated.', 'success');
    } else {
        flash('Payment status updated.', 'success');
    }
    redirect('admin/payments.php');
}

// Auto-check for duplicate transaction references (cron job or manual check)
if (isset($_GET['check_duplicates'])) {
    // Find duplicate transaction references
    $duplicate_sql = "SELECT transaction_reference, COUNT(*) as count, GROUP_CONCAT(id) as ids 
                      FROM payments 
                      WHERE status = 'pending' AND transaction_reference IS NOT NULL AND transaction_reference != ''
                      GROUP BY transaction_reference 
                      HAVING COUNT(*) > 1";
    $duplicates = $mysqli->query($duplicate_sql);
    
    while ($dup = $duplicates->fetch_assoc()) {
        $ids = explode(',', $dup['ids']);
        // Keep first one as pending, reject others
        for ($i = 1; $i < count($ids); $i++) {
            $mysqli->query("UPDATE payments SET status = 'failed', notes = 'Duplicate transaction reference' WHERE id = {$ids[$i]}");
        }
        flash('Duplicate transaction references have been auto-rejected.', 'warning');
    }
    redirect('admin/payments.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where_clauses = []; 
$params = []; 
$types = '';

if($search){ 
    $where_clauses[] = "(o.order_number LIKE ? OR u.name LIKE ?)"; 
    $p="%$search%"; 
    $params=[$p,$p]; 
    $types='ss'; 
}
if($status_filter){ 
    $where_clauses[] = "p.status=?"; 
    $params[]=$status_filter; 
    $types.='s'; 
}
$where_sql = !empty($where_clauses) ? "WHERE ".implode(" AND ",$where_clauses) : "";

$count_sql = "SELECT COUNT(*) as total FROM payments p 
               JOIN orders o ON o.id = p.order_id 
               JOIN users u ON u.id = o.user_id 
               $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
if(!empty($params)) $count_stmt->bind_param($types,...$params);
$count_stmt->execute();
$total_payments = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_payments / $limit);

$sql = "SELECT p.*, o.order_number, u.name as user_name, o.total_amount
        FROM payments p
        JOIN orders o ON o.id = p.order_id
        JOIN users u ON u.id = o.user_id
        $where_sql
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[]=$limit; 
$params[]=$offset; 
$types.='ii';
$stmt->bind_param($types,...$params);
$stmt->execute();
$payments = $stmt->get_result();

// Get duplicate count for notification
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

<style>
    .admin-content-wrapper { display: flex; gap: 25px; }
    .admin-sidebar-col { width: 280px; flex-shrink: 0; }
    .admin-main-col { flex: 1; }
    .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .search-input { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 15px; width: 250px; }
    .btn-duplicate { background: #ef4444; color: white; border: none; padding: 8px 15px; border-radius: 8px; font-size: 0.8rem; transition: all 0.3s ease; }
    .btn-duplicate:hover { background: #dc2626; transform: translateY(-2px); }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-completed { background: #d1fae5; color: #059669; }
    .status-failed { background: #fee2e2; color: #dc2626; }
    .duplicate-warning { background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 15px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
    @media (max-width: 992px) { .admin-content-wrapper { flex-direction: column; } .admin-sidebar-col { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-credit-card"></i> Payments Management</h2>
                <?php if ($duplicate_count > 0): ?>
                    <a href="?check_duplicates=1" class="btn-duplicate">
                        <i class="fa-solid fa-copy"></i> Check Duplicates (<?= $duplicate_count ?>)
                    </a>
                <?php endif; ?>
            </div>
            
            <?php if ($duplicate_count > 0): ?>
            <div class="duplicate-warning">
                <div>
                    <i class="fa-solid fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> <?= $duplicate_count ?> duplicate transaction reference(s) detected. Click "Check Duplicates" to auto-reject them.
                </div>
            </div>
            <?php endif; ?>
            
            <div class="filter-bar">
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="search" class="search-input" placeholder="Search by order # or customer..." value="<?= htmlspecialchars($search) ?>">
                    <select name="status" class="form-select w-auto">
                        <option value="">All Status</option>
                        <option value="pending" <?=$status_filter=='pending'?'selected':''?>>Pending</option>
                        <option value="completed" <?=$status_filter=='completed'?'selected':''?>>Completed</option>
                        <option value="failed" <?=$status_filter=='failed'?'selected':''?>>Failed</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if($search||$status_filter):?><a href="payments.php" class="btn btn-secondary btn-sm">Clear</a><?php endif;?>
                </form>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction Ref</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($payment = $payments->fetch_assoc()): 
                                    $is_duplicate = false;
                                    if (!empty($payment['transaction_reference']) && $payment['status'] == 'pending') {
                                        $dup_check = $mysqli->query("SELECT id FROM payments WHERE transaction_reference = '{$payment['transaction_reference']}' AND status = 'completed' LIMIT 1");
                                        $is_duplicate = $dup_check->num_rows > 0;
                                    }
                                ?>
                                <tr>
                                    <td><?= $payment['id'] ?></td>
                                    <td><strong><?= $payment['order_number'] ?></strong></td>
                                    <td><?= htmlspecialchars($payment['user_name']) ?></td>
                                    <td>KSH <?= number_format($payment['amount']) ?></td>
                                    <td><?= $payment['method'] ?></td>
                                    <td>
                                        <?php if (!empty($payment['transaction_reference'])): ?>
                                            <code><?= htmlspecialchars($payment['transaction_reference']) ?></code>
                                            <?php if ($is_duplicate): ?>
                                                <span class="badge bg-danger ms-1" title="Duplicate detected!">Duplicate!</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                     </td>
                                    <td>
                                        <?php if ($payment['status'] == 'pending'): ?>
                                            <span class="status-badge status-pending"><i class="fa-regular fa-clock"></i> Pending</span>
                                        <?php elseif ($payment['status'] == 'completed'): ?>
                                            <span class="status-badge status-completed"><i class="fa-solid fa-check-circle"></i> Completed</span>
                                        <?php else: ?>
                                            <span class="status-badge status-failed"><i class="fa-solid fa-times-circle"></i> Failed</span>
                                        <?php endif; ?>
                                        </td>
                                    <td><?= date('M d, Y', strtotime($payment['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#paymentModal<?=$payment['id']?>"><i class="fa-solid fa-eye"></i></button>
                                            <?php if ($payment['status'] == 'pending'): ?>
                                                <form method="post" class="d-inline approve-form" onsubmit="return checkDuplicateRef(<?= $payment['id'] ?>, '<?= addslashes($payment['transaction_reference']) ?>')">
                                                    <input type="hidden" name="payment_id" value="<?=$payment['id']?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <input type="hidden" name="transaction_reference" value="<?= htmlspecialchars($payment['transaction_reference']) ?>">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-success" title="Approve Payment">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="payment_id" value="<?=$payment['id']?>">
                                                    <input type="hidden" name="status" value="failed">
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-danger" onclick="return confirm('Reject this payment?')" title="Reject Payment">
                                                        <i class="fa-solid fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                     </td>
                                            </tr>
                                
                                <!-- Payment Details Modal -->
                                <div class="modal fade" id="paymentModal<?=$payment['id']?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Payment Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Order:</strong> <?=$payment['order_number']?></p>
                                                <p><strong>Customer:</strong> <?=$payment['user_name']?></p>
                                                <p><strong>Amount:</strong> KSH <?=number_format($payment['amount'])?></p>
                                                <p><strong>Method:</strong> <?=$payment['method']?></p>
                                                <p><strong>Transaction Ref:</strong> <?=$payment['transaction_reference'] ?? 'N/A'?></p>
                                                <p><strong>Status:</strong> <?=ucfirst($payment['status'])?></p>
                                                <p><strong>Date:</strong> <?=date('F d, Y h:i A', strtotime($payment['created_at']))?></p>
                                                <?php if (isset($payment['notes']) && !empty($payment['notes'])): ?>
                                                    <p><strong>Notes:</strong> <?=htmlspecialchars($payment['notes'])?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <?php if($payments->num_rows==0):?>
                                <tr><td colspan="9" class="text-center py-4">No payments found.<?php endif;?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if($total_pages>1):?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i=1;$i<=$total_pages;$i++):?>
                        <li class="page-item <?=$i==$page?'active':''?>">
                            <a class="page-link" href="?page=<?=$i?>&search=<?=urlencode($search)?>&status=<?=$status_filter?>"><?=$i?></a>
                        </li>
                    <?php endfor;?>
                </ul>
            </nav>
            <?php endif;?>
        </div>
    </div>
</div>

<script>
function checkDuplicateRef(paymentId, transactionRef) {
    if (!transactionRef || transactionRef === '') {
        return confirm('No transaction reference provided. Approve this payment?');
    }
    
    // Check for duplicates via AJAX
    var result = false;
    $.ajax({
        url: 'ajax/check_duplicate_ref.php',
        method: 'POST',
        async: false,
        data: { transaction_ref: transactionRef, payment_id: paymentId },
        success: function(response) {
            var data = JSON.parse(response);
            if (data.is_duplicate) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Duplicate Transaction Reference!',
                    text: 'This transaction reference has already been used for another payment. Approving this might cause double payment.',
                    showCancelButton: true,
                    confirmButtonText: 'Approve Anyway',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#ef4444'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('.approve-form').submit();
                    }
                });
                result = false;
            } else {
                result = true;
            }
        }
    });
    return result;
}
</script>

<?php require_once '../includes/footer.php'; ?>