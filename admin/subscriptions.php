<?php
$page_title = 'Subscriptions Management';
require_once '../includes/header.php';

if (($user['role'] ?? '') !== 'admin') { 
    flash('Access denied. Admin only.', 'danger'); 
    redirect('index.php'); 
}

// Function to get subscription status color
function getSubscriptionStatusClass($status) {
    switch($status) {
        case 'active': return 'bg-success';
        case 'expired': return 'bg-warning';
        case 'cancelled': return 'bg-danger';
        case 'pending': return 'bg-info';
        default: return 'bg-secondary';
    }
}

// Handle subscription status update
if (isset($_POST['update_status'])) {
    $sub_id = intval($_POST['subscription_id']);
    $status = sanitize($_POST['status']);
    
    if ($status === 'active') {
        $start_date = date('Y-m-d');
        $expiry_date = date('Y-m-d', strtotime('+30 days'));
        $mysqli->query("UPDATE subscriptions SET status = '$status', starts_at = '$start_date', expires_at = '$expiry_date' WHERE id = $sub_id");
        flash('Subscription activated for 30 days from today!', 'success');
    } else {
        $mysqli->query("UPDATE subscriptions SET status = '$status' WHERE id = $sub_id");
        flash('Subscription status updated.', 'success');
    }
    redirect('admin/subscriptions.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = "";
$params = [];

if($search){ 
    $where = "WHERE (s.shop_name LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if($status_filter){ 
    if($where) {
        $where .= " AND sub.status = ?";
    } else {
        $where = "WHERE sub.status = ?";
    }
    $params[] = $status_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM subscriptions sub 
               JOIN sellers s ON s.id = sub.seller_id 
               JOIN users u ON u.id = s.user_id 
               $where";
$count_stmt = $mysqli->prepare($count_sql);
if(!empty($params)) {
    $count_stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$count_stmt->execute();
$total_subs = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_subs / $limit);

// Get subscriptions
$sql = "SELECT sub.*, s.shop_name, u.name as user_name, u.email as user_email
        FROM subscriptions sub
        JOIN sellers s ON s.id = sub.seller_id
        JOIN users u ON u.id = s.user_id
        $where
        ORDER BY sub.created_at DESC
        LIMIT $offset, $limit";
$stmt = $mysqli->prepare($sql);
if(!empty($params)) {
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);
}
$stmt->execute();
$subscriptions = $stmt->get_result();
?>

<style>
    .admin-wrapper {
        display: flex;
        gap: 25px;
    }
    .sidebar-col {
        width: 280px;
        flex-shrink: 0;
    }
    .content-col {
        flex: 1;
    }
    .filter-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .data-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    .data-table th,
    .data-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    .data-table th {
        background: #f8fafc;
        font-weight: 600;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        color: white;
    }
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.75rem;
        border-radius: 6px;
        border: none;
        cursor: pointer;
    }
    .btn-info { background: #0dcaf0; color: white; }
    .btn-success { background: #198754; color: white; }
    .btn-danger { background: #dc3545; color: white; }
    .btn-primary { background: #0d6efd; color: white; }
    .form-select-sm {
        padding: 4px 8px;
        font-size: 0.75rem;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }
    .search-input {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 8px;
        width: 250px;
    }
    @media (max-width: 992px) {
        .admin-wrapper { flex-direction: column; }
        .sidebar-col { width: 100%; }
    }
</style>

<div class="container-fluid">
    <div class="admin-wrapper">
        <div class="sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="content-col">
            <h2 class="mb-4"><i class="fa-solid fa-calendar-check"></i> Subscriptions Management</h2>
            
            <!-- Filter Bar -->
            <div class="filter-card">
                <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="text" name="search" class="search-input" placeholder="Search by shop or seller..." value="<?= htmlspecialchars($search) ?>">
                    <select name="status" class="form-select" style="width: 120px;">
                        <option value="">All Status</option>
                        <option value="active" <?= $status_filter == 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="expired" <?= $status_filter == 'expired' ? 'selected' : '' ?>>Expired</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if($search || $status_filter): ?>
                        <a href="subscriptions.php" class="btn btn-secondary btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Subscriptions Table -->
            <div class="data-card">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Shop</th>
                                <th>Seller</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Start Date</th>
                                <th>Expiry Date</th>
                                <th>Days Left</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($sub = $subscriptions->fetch_assoc()): 
                                $status_class = getSubscriptionStatusClass($sub['status']);
                                
                                // Calculate days remaining
                                $days_left = '-';
                                if ($sub['status'] == 'active' && $sub['expires_at'] && $sub['expires_at'] != '0000-00-00') {
                                    $today = new DateTime();
                                    $expiry = new DateTime($sub['expires_at']);
                                    if ($today < $expiry) {
                                        $days_left = $today->diff($expiry)->days . ' days';
                                    } else {
                                        $days_left = 'Expired';
                                    }
                                } elseif ($sub['status'] == 'expired') {
                                    $days_left = 'Expired';
                                }
                            ?>
                            <tr>
                                <td><?= $sub['id'] ?></td>
                                <td><strong><?= htmlspecialchars($sub['shop_name']) ?></strong></td>
                                <td><?= htmlspecialchars($sub['user_name']) ?> <small>(<?= htmlspecialchars($sub['user_email']) ?>)</small></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($sub['plan_name']) ?></span></td>
                                <td>
                                    <span class="status-badge <?= $status_class ?>">
                                        <?= ucfirst($sub['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($sub['starts_at'] && $sub['starts_at'] != '0000-00-00'): ?>
                                        <?= date('M d, Y', strtotime($sub['starts_at'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                    </td>
                                <td>
                                    <?php if ($sub['expires_at'] && $sub['expires_at'] != '0000-00-00'): ?>
                                        <?= date('M d, Y', strtotime($sub['expires_at'])) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                    </td>
                                <td><?= $days_left ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="subscription_id" value="<?= $sub['id'] ?>">
                                            <select name="status" class="form-select-sm" style="width: 90px;" onchange="this.form.submit()">
                                                <option value="active" <?= $sub['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                                <option value="expired" <?= $sub['status'] == 'expired' ? 'selected' : '' ?>>Expired</option>
                                                <option value="cancelled" <?= $sub['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                <option value="pending" <?= $sub['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                            </select>
                                            <input type="submit" name="update_status" class="d-none">
                                        </form>
                                        <button class="btn-sm btn-info" onclick="showDetails(<?= htmlspecialchars(json_encode($sub)) ?>)">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                    </div>
                                    </td>
                             </tr>
                            <?php endwhile; ?>
                            <?php if($subscriptions->num_rows == 0): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">No subscriptions found. </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subscription Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content loaded via JS -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(sub) {
    let statusClass = '';
    if (sub.status === 'active') statusClass = 'bg-success';
    else if (sub.status === 'expired') statusClass = 'bg-warning';
    else if (sub.status === 'cancelled') statusClass = 'bg-danger';
    else statusClass = 'bg-info';
    
    document.getElementById('modalBody').innerHTML = `
        <p><strong>Shop:</strong> ${sub.shop_name}</p>
        <p><strong>Seller:</strong> ${sub.user_name} (${sub.user_email})</p>
        <p><strong>Plan:</strong> ${sub.plan_name}</p>
        <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${sub.status}</span></p>
        <p><strong>Start Date:</strong> ${sub.starts_at && sub.starts_at !== '0000-00-00' ? new Date(sub.starts_at).toLocaleDateString() : 'Not set'}</p>
        <p><strong>Expiry Date:</strong> ${sub.expires_at && sub.expires_at !== '0000-00-00' ? new Date(sub.expires_at).toLocaleDateString() : 'Not set'}</p>
        <p><strong>Created:</strong> ${new Date(sub.created_at).toLocaleDateString()}</p>
    `;
    
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>