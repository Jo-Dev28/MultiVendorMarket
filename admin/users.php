<?php
$page_title = 'Users Management';
require_once '../includes/header.php';

// Check if user is admin
if (($user['role'] ?? '') !== 'admin') {
    flash('Access denied. Admin only.', 'danger');
    redirect('index.php');
}

// Handle Add User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'customer');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    $errors = [];
    if (!$name) $errors[] = 'Name is required';
    if (!$email) $errors[] = 'Valid email is required';
    if (!$password) $errors[] = 'Password is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    
    if (get_user_by_email($mysqli, $email)) {
        $errors[] = 'Email already exists';
    }
    
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (name, email, password_hash, role, phone, address, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssssss', $name, $email, $hash, $role, $phone, $address);
        
        if ($stmt->execute()) {
            flash('User added successfully.', 'success');
            redirect('admin/users.php');
        } else {
            flash('Failed to add user.', 'danger');
        }
    } else {
        foreach ($errors as $error) {
            flash($error, 'danger');
        }
    }
}

// Handle user status toggle (verify/delete)
if (isset($_GET['toggle_status'])) {
    $user_id = intval($_GET['toggle_status']);
    $status = $_GET['status'] ?? '';
    
    if ($status === 'verify') {
        $mysqli->query("UPDATE users SET email_verified = 1 WHERE id = $user_id");
        flash('User verified successfully.', 'success');
    } elseif ($status === 'delete') {
        $mysqli->query("DELETE FROM users WHERE id = $user_id");
        flash('User deleted successfully.', 'success');
    }
    redirect('admin/users.php');
}

// Handle Bulk Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_bulk_message'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('admin/users.php');
    }
    
    $selected_users = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    $message_subject = sanitize($_POST['bulk_subject'] ?? '');
    $message_body = sanitize($_POST['bulk_message'] ?? '');
    $send_type = sanitize($_POST['send_type'] ?? 'email');
    
    if (empty($selected_users)) {
        flash('Please select at least one user.', 'danger');
    } elseif (empty($message_subject) || empty($message_body)) {
        flash('Please fill in both subject and message.', 'danger');
    } else {
        $ids = implode(',', array_map('intval', $selected_users));
        $users_sql = "SELECT id, name, email, phone FROM users WHERE id IN ($ids) AND role = 'customer'";
        $users_result = $mysqli->query($users_sql);
        
        $success_count = 0;
        while ($user_data = $users_result->fetch_assoc()) {
            $success_count++;
        }
        
        flash("Message sent to $success_count customers successfully.", 'success');
        redirect('admin/users.php');
    }
}

// Search functionality
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_clauses = [];
$params = [];
$types = '';

if ($search) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($role_filter) {
    $where_clauses[] = "role = ?";
    $params[] = $role_filter;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total users
$count_sql = "SELECT COUNT(*) as total FROM users $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_users = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Get users
$sql = "SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$users = $stmt->get_result();

// Get counts
$customer_count_sql = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$customer_count = $mysqli->query($customer_count_sql)->fetch_assoc()['total'];

$seller_count_sql = "SELECT COUNT(*) as total FROM users WHERE role = 'seller'";
$seller_count = $mysqli->query($seller_count_sql)->fetch_assoc()['total'];

$admin_count_sql = "SELECT COUNT(*) as total FROM users WHERE role = 'admin'";
$admin_count = $mysqli->query($admin_count_sql)->fetch_assoc()['total'];
?>

<style>
    .admin-content-wrapper {
        display: flex;
        gap: 25px;
        min-height: calc(100vh - 200px);
    }
    
    .admin-sidebar-col {
        width: 280px;
        flex-shrink: 0;
    }
    
    .admin-main-col {
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
    
    .role-filter {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 10px 15px;
    }
    
    .btn-add {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        transition: all 0.3s ease;
    }
    
    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    }
    
    .btn-bulk {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .btn-bulk:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245, 158, 11, 0.3);
        color: white;
    }
    
    .user-avatar-small {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    
    /* Stats Cards */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
    }
    
    .stat-card .number {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-card .label {
        font-size: 0.85rem;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .stat-card .icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    
    .stat-card.customers .icon { color: #2563eb; }
    .stat-card.sellers .icon { color: #f59e0b; }
    .stat-card.admins .icon { color: #ef4444; }
    .stat-card.total .icon { color: #10b981; }
    
    /* Action Buttons */
    .action-btn-group {
        display: flex;
        gap: 4px;
        flex-wrap: wrap;
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 0.8rem;
        color: white;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
    }
    
    .action-btn.btn-view { background: #2563eb; }
    .action-btn.btn-view:hover { background: #1d4ed8; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }
    .action-btn.btn-email { background: #7c3aed; }
    .action-btn.btn-email:hover { background: #6d28d9; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3); }
    .action-btn.btn-whatsapp { background: #25D366; }
    .action-btn.btn-whatsapp:hover { background: #1da851; box-shadow: 0 4px 12px rgba(37, 211, 102, 0.3); }
    .action-btn.btn-sms { background: #f59e0b; }
    .action-btn.btn-sms:hover { background: #d97706; box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
    .action-btn.btn-verify { background: #10b981; }
    .action-btn.btn-verify:hover { background: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
    .action-btn.btn-delete { background: #ef4444; }
    .action-btn.btn-delete:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3); }
    
    .action-btn .tooltip-text {
        display: none;
        position: absolute;
        background: #1f2937;
        color: white;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.65rem;
        white-space: nowrap;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
    }
    
    .action-btn:hover .tooltip-text {
        display: block;
    }
    
    .action-btn-wrapper {
        position: relative;
        display: inline-block;
    }
    
    /* Bulk Actions Bar */
    .bulk-actions-bar {
        background: #f3f4f6;
        padding: 12px 16px;
        border-radius: 12px;
        display: none;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    
    .bulk-actions-bar.show {
        display: flex;
    }
    
    .bulk-actions-bar .selected-count {
        font-weight: 600;
        color: #1f2937;
    }
    
    .bulk-actions-bar .btn-bulk-action {
        padding: 6px 16px;
        border-radius: 8px;
        border: none;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        color: white;
    }
    
    .bulk-actions-bar .btn-bulk-action.btn-email-bulk { background: #7c3aed; }
    .bulk-actions-bar .btn-bulk-action.btn-email-bulk:hover { background: #6d28d9; }
    .bulk-actions-bar .btn-bulk-action.btn-whatsapp-bulk { background: #25D366; }
    .bulk-actions-bar .btn-bulk-action.btn-whatsapp-bulk:hover { background: #1da851; }
    .bulk-actions-bar .btn-bulk-action.btn-sms-bulk { background: #f59e0b; }
    .bulk-actions-bar .btn-bulk-action.btn-sms-bulk:hover { background: #d97706; }
    .bulk-actions-bar .btn-bulk-action.btn-clear-selection { background: #e5e7eb; color: #4b5563; }
    .bulk-actions-bar .btn-bulk-action.btn-clear-selection:hover { background: #d1d5db; }
    
    .user-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #2563eb;
    }
    
    .select-all-checkbox {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #2563eb;
    }
    
    @media (max-width: 992px) {
        .admin-content-wrapper {
            flex-direction: column;
        }
        .admin-sidebar-col {
            width: 100%;
        }
        .filter-bar .d-flex {
            flex-direction: column;
        }
        .search-input {
            width: 100%;
        }
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .stats-row {
            grid-template-columns: 1fr 1fr;
        }
        .bulk-actions-bar {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2><i class="fa-solid fa-users"></i> Users Management</h2>
                <div class="d-flex gap-2">
                    <button class="btn-bulk" data-bs-toggle="modal" data-bs-target="#bulkMessageModal">
                        <i class="fa-regular fa-paper-plane"></i> Send Bulk Message
                    </button>
                    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="fa-solid fa-user-plus"></i> Add New User
                    </button>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card customers">
                    <div class="icon"><i class="fa-regular fa-user"></i></div>
                    <div class="number"><?= number_format($customer_count) ?></div>
                    <div class="label">Customers</div>
                </div>
                <div class="stat-card sellers">
                    <div class="icon"><i class="fa-solid fa-store"></i></div>
                    <div class="number"><?= number_format($seller_count) ?></div>
                    <div class="label">Sellers</div>
                </div>
                <div class="stat-card admins">
                    <div class="icon"><i class="fa-solid fa-user-shield"></i></div>
                    <div class="number"><?= number_format($admin_count) ?></div>
                    <div class="label">Admins</div>
                </div>
                <div class="stat-card total">
                    <div class="icon"><i class="fa-solid fa-users"></i></div>
                    <div class="number"><?= number_format($total_users) ?></div>
                    <div class="label">Total Users</div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="d-flex gap-3 flex-wrap">
                        <form method="GET" action="" class="d-flex gap-2">
                            <input type="text" name="search" class="search-input" placeholder="Search by name, email or phone..." value="<?= htmlspecialchars($search) ?>">
                            <select name="role" class="role-filter">
                                <option value="">All Roles</option>
                                <option value="customer" <?= $role_filter == 'customer' ? 'selected' : '' ?>>Customer</option>
                                <option value="seller" <?= $role_filter == 'seller' ? 'selected' : '' ?>>Seller</option>
                                <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            <?php if ($search || $role_filter): ?>
                                <a href="users.php" class="btn btn-secondary btn-sm">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div>
                        <span class="text-muted">Showing: <strong><?= $users->num_rows ?></strong> users</span>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Actions Bar -->
            <div class="bulk-actions-bar" id="bulkActionsBar">
                <span class="selected-count" id="selectedCount">0 users selected</span>
                <button class="btn-bulk-action btn-email-bulk" onclick="sendBulkAction('email')">
                    <i class="fa-regular fa-envelope"></i> Email Selected
                </button>
                <button class="btn-bulk-action btn-whatsapp-bulk" onclick="sendBulkAction('whatsapp')">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Selected
                </button>
                <button class="btn-bulk-action btn-sms-bulk" onclick="sendBulkAction('sms')">
                    <i class="fa-solid fa-message"></i> SMS Selected
                </button>
                <button class="btn-bulk-action btn-clear-selection" onclick="clearSelection()">
                    <i class="fa-solid fa-times"></i> Clear
                </button>
            </div>
            
            <!-- Users Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0">All Registered Users</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>
                                        <input type="checkbox" class="select-all-checkbox" id="selectAll" onchange="toggleAllUsers()">
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Verified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user_row = $users->fetch_assoc()): 
                                    $is_customer = $user_row['role'] === 'customer';
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($is_customer): ?>
                                            <input type="checkbox" class="user-checkbox" data-user-id="<?= $user_row['id'] ?>" onchange="updateSelection()">
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $user_row['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar-small">
                                                <?= strtoupper(substr($user_row['name'], 0, 1)) ?>
                                            </div>
                                            <strong><?= htmlspecialchars($user_row['name']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($user_row['email']) ?></td>
                                    <td>
                                        <span class="badge <?= $user_row['role'] === 'admin' ? 'bg-danger' : ($user_row['role'] === 'seller' ? 'bg-warning' : 'bg-primary') ?>">
                                            <?= ucfirst($user_row['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user_row['email_verified']): ?>
                                            <span class="badge bg-success"><i class="fa-solid fa-check-circle"></i> Verified</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><i class="fa-regular fa-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-btn-group">
                                            <!-- View Details -->
                                            <div class="action-btn-wrapper">
                                                <button class="action-btn btn-view" onclick="viewUserDetails(<?= $user_row['id'] ?>)" title="View Details">
                                                    <i class="fa-solid fa-eye"></i>
                                                    <span class="tooltip-text">View Details</span>
                                                </button>
                                            </div>
                                            
                                            <!-- Send Email (Only for Customers) -->
                                            <?php if ($is_customer): ?>
                                            <div class="action-btn-wrapper">
                                                <a href="mailto:<?= $user_row['email'] ?>?subject=Message from <?= SITE_NAME ?>" class="action-btn btn-email" target="_blank" title="Send Email">
                                                    <i class="fa-regular fa-envelope"></i>
                                                    <span class="tooltip-text">Send Email</span>
                                                </a>
                                            </div>
                                            
                                            <!-- Send WhatsApp (Only for Customers with phone) -->
                                            <?php if (!empty($user_row['phone'])): ?>
                                            <div class="action-btn-wrapper">
                                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $user_row['phone']) ?>?text=Hello%20<?= urlencode($user_row['name']) ?>%2C%20this%20is%20a%20message%20from%20<?= urlencode(SITE_NAME) ?>%20support." class="action-btn btn-whatsapp" target="_blank" title="Send WhatsApp">
                                                    <i class="fa-brands fa-whatsapp"></i>
                                                    <span class="tooltip-text">WhatsApp</span>
                                                </a>
                                            </div>
                                            
                                            <!-- Send SMS (Only for Customers with phone) -->
                                            <div class="action-btn-wrapper">
                                                <a href="sms:<?= $user_row['phone'] ?>?body=Hello%20<?= urlencode($user_row['name']) ?>%2C%20this%20is%20a%20message%20from%20<?= urlencode(SITE_NAME) ?>%20support." class="action-btn btn-sms" title="Send SMS">
                                                    <i class="fa-solid fa-message"></i>
                                                    <span class="tooltip-text">Send SMS</span>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <!-- Verify User -->
                                            <?php if (!$user_row['email_verified']): ?>
                                            <div class="action-btn-wrapper">
                                                <a href="?toggle_status=<?= $user_row['id'] ?>&status=verify" class="action-btn btn-verify" onclick="return confirm('Verify this user?')" title="Verify User">
                                                    <i class="fa-solid fa-check"></i>
                                                    <span class="tooltip-text">Verify User</span>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Delete User -->
                                            <div class="action-btn-wrapper">
                                                <a href="?toggle_status=<?= $user_row['id'] ?>&status=delete" class="action-btn btn-delete" onclick="return confirm('Delete this user?')" title="Delete User">
                                                    <i class="fa-solid fa-trash"></i>
                                                    <span class="tooltip-text">Delete User</span>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= $role_filter ?>"><i class="fa-solid fa-chevron-left"></i> Previous</a></li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&role=<?= $role_filter ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= $role_filter ?>">Next <i class="fa-solid fa-chevron-right"></i></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-user-plus"></i> Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="customer">Customer</option>
                            <option value="seller">Seller</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="0712345678">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="User address"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Message Modal -->
<div class="modal fade" id="bulkMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-regular fa-paper-plane"></i> Send Bulk Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="send_bulk_message" value="1">
                    
                    <div class="alert alert-info" id="bulkMessageInfo">
                        <i class="fa-regular fa-circle-info"></i> 
                        This will send messages to <strong>all customers</strong>.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Send Via</label>
                        <select name="send_type" class="form-select" required>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="sms">SMS</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subject / Title</label>
                        <input type="text" name="bulk_subject" class="form-control" placeholder="Message subject..." required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="bulk_message" class="form-control" rows="6" placeholder="Type your message here..." required></textarea>
                        <small class="text-muted">Variables: {name}, {email}</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Preview</label>
                        <div id="messagePreview" class="p-3 bg-light rounded" style="white-space: pre-wrap;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Send this message to all customers?')">
                        <i class="fa-regular fa-paper-plane"></i> Send to All Customers
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View User Details Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-user"></i> User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="d-flex gap-2 flex-wrap" id="modalActionButtons"></div>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// BULK SELECTION FUNCTIONS
// ============================================
let selectedUsers = [];

function toggleAllUsers() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    
    checkboxes.forEach(cb => {
        cb.checked = selectAll.checked;
    });
    
    updateSelection();
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    selectedUsers = Array.from(checkboxes).map(cb => parseInt(cb.dataset.userId));
    
    const count = selectedUsers.length;
    const bar = document.getElementById('bulkActionsBar');
    const countDisplay = document.getElementById('selectedCount');
    
    if (count > 0) {
        bar.classList.add('show');
        countDisplay.textContent = count + ' user' + (count > 1 ? 's' : '') + ' selected';
    } else {
        bar.classList.remove('show');
    }
    
    const allCheckboxes = document.querySelectorAll('.user-checkbox');
    const checkedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectAll = document.getElementById('selectAll');
    
    if (allCheckboxes.length > 0 && checkedCheckboxes.length === allCheckboxes.length) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else if (checkedCheckboxes.length > 0) {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    }
}

function clearSelection() {
    document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
    selectedUsers = [];
    document.getElementById('bulkActionsBar').classList.remove('show');
}

function sendBulkAction(type) {
    if (selectedUsers.length === 0) {
        alert('Please select at least one customer.');
        return;
    }
    
    const count = selectedUsers.length;
    const actionNames = {
        'email': 'Email',
        'whatsapp': 'WhatsApp',
        'sms': 'SMS'
    };
    
    const infoDiv = document.querySelector('#bulkMessageModal .alert-info');
    if (infoDiv) {
        infoDiv.innerHTML = `
            <i class="fa-regular fa-circle-info"></i> 
            This will send messages to <strong>${count} selected customers</strong> via ${actionNames[type]}.
        `;
    }
    
    document.querySelector('select[name="send_type"]').value = type;
    
    var modal = new bootstrap.Modal(document.getElementById('bulkMessageModal'));
    modal.show();
}

// ============================================
// VIEW USER DETAILS - FIXED VERSION
// ============================================
function viewUserDetails(userId) {
    document.getElementById('userDetailsContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading user details...</p>
        </div>
    `;
    
    var modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    modal.show();
    
    fetch('../admin/ajax/get_user_details.php?id=' + userId)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('Server returned: ' + response.status + ' - ' + text.substring(0, 100));
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayUserDetails(data);
            } else {
                document.getElementById('userDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> 
                        ${data.message || 'Failed to load user details.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> 
                    Error loading user details. Please try again.
                    <br><small class="text-muted">${error.message}</small>
                </div>
            `;
        });
}

function displayUserDetails(data) {
    let sellerHtml = '';
    let actionButtons = '';
    
    if (data.is_seller && data.seller) {
        const statusBadge = data.seller.status === 'verified' ? 'bg-success' : 
                           (data.seller.status === 'pending' ? 'bg-warning' : 'bg-danger');
        const statusText = data.seller.status || 'N/A';
        
        sellerHtml = `
            <div class="card mt-3">
                <div class="card-header bg-warning text-white">
                    <strong><i class="fa-solid fa-store"></i> Seller Information</strong>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Shop Name:</strong> ${data.seller.shop_name || 'N/A'}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Business ID:</strong> ${data.seller.business_id || 'N/A'}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Seller Phone:</strong> ${data.seller.phone || 'N/A'}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Location:</strong> ${data.seller.location || 'N/A'}
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Shop Description:</strong> ${data.seller.description || 'N/A'}
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Status:</strong> 
                            <span class="badge ${statusBadge}">${statusText}</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Applied on:</strong> ${data.seller.created_at ? new Date(data.seller.created_at).toLocaleDateString() : 'N/A'}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    if (data.user.role === 'customer') {
        const siteName = '<?= defined('SITE_NAME') ? SITE_NAME : 'Marketplace' ?>';
        actionButtons = `
            <a href="mailto:${data.user.email}?subject=Message from ${siteName}" class="btn btn-primary" target="_blank">
                <i class="fa-regular fa-envelope"></i> Email
            </a>
            ${data.user.phone ? `
                <a href="https://wa.me/${data.user.phone.replace(/[^0-9]/g, '')}?text=Hello%20${encodeURIComponent(data.user.name)}%2C%20this%20is%20a%20message%20from%20${encodeURIComponent(siteName)}%20support." class="btn btn-success" target="_blank">
                    <i class="fa-brands fa-whatsapp"></i> WhatsApp
                </a>
                <a href="sms:${data.user.phone}?body=Hello%20${encodeURIComponent(data.user.name)}%2C%20this%20is%20a%20message%20from%20${encodeURIComponent(siteName)}%20support." class="btn btn-warning" target="_blank">
                    <i class="fa-solid fa-message"></i> SMS
                </a>
            ` : ''}
        `;
    }
    
    document.getElementById('modalActionButtons').innerHTML = actionButtons;
    
    const verifiedBadge = data.user.email_verified ? 
        '<span class="badge bg-success"><i class="fa-solid fa-check-circle"></i> Verified</span>' : 
        '<span class="badge bg-warning"><i class="fa-regular fa-clock"></i> Pending</span>';
    
    const roleBadge = data.user.role === 'admin' ? 'bg-danger' : 
                     (data.user.role === 'seller' ? 'bg-warning' : 'bg-primary');
    
    document.getElementById('userDetailsContent').innerHTML = `
        <div class="card">
            <div class="card-header bg-primary text-white">
                <strong><i class="fa-regular fa-circle-user"></i> Basic Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <strong>User ID:</strong> ${data.user.id}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Full Name:</strong> ${data.user.name || 'N/A'}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Email:</strong> ${data.user.email || 'N/A'}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Role:</strong> 
                        <span class="badge ${roleBadge}">${data.user.role || 'N/A'}</span>
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Phone:</strong> ${data.user.phone || 'N/A'}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Email Verified:</strong> ${verifiedBadge}
                    </div>
                    <div class="col-12 mb-2">
                        <strong>Address:</strong> ${data.user.address || 'N/A'}
                    </div>
                    <div class="col-md-6 mb-2">
                        <strong>Member Since:</strong> ${data.user.created_at ? new Date(data.user.created_at).toLocaleDateString() : 'N/A'}
                    </div>
                </div>
            </div>
        </div>
        ${sellerHtml}
    `;
}

// ============================================
// MESSAGE PREVIEW
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const subjectInput = document.querySelector('input[name="bulk_subject"]');
    const messageInput = document.querySelector('textarea[name="bulk_message"]');
    const previewDiv = document.getElementById('messagePreview');
    
    function updatePreview() {
        let message = messageInput.value || 'Your message will appear here...';
        message = message.replace(/{name}/g, '[Customer Name]');
        message = message.replace(/{email}/g, 'customer@email.com');
        previewDiv.textContent = message;
    }
    
    if (subjectInput) subjectInput.addEventListener('input', updatePreview);
    if (messageInput) messageInput.addEventListener('input', updatePreview);
    updatePreview();
});
</script>

<?php require_once '../includes/footer.php'; ?>