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

// Get seller details for view modal
$seller_details = [];
if (isset($_GET['view_user'])) {
    $view_id = intval($_GET['view_user']);
    $seller_sql = "SELECT * FROM sellers WHERE user_id = ?";
    $seller_stmt = $mysqli->prepare($seller_sql);
    $seller_stmt->bind_param('i', $view_id);
    $seller_stmt->execute();
    $seller_details = $seller_stmt->get_result()->fetch_assoc();
}
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
    }
    
    .view-details-btn {
        background: none;
        border: none;
        color: #2563eb;
        cursor: pointer;
        padding: 5px 10px;
    }
    
    .view-details-btn:hover {
        color: #1d4ed8;
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
    }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <!-- Dashboard Sidebar -->
        <div class="admin-sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2><i class="fa-solid fa-users"></i> Users Management</h2>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus"></i> Add New User
                </button>
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
                        <span class="text-muted">Total Users: <strong><?= number_format($total_users) ?></strong></span>
                    </div>
                </div>
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
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Verified</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user_row = $users->fetch_assoc()): ?>
                                <tr>
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
                                        <div class="d-flex gap-1">
                                            <button class="view-details-btn" onclick="viewUserDetails(<?= $user_row['id'] ?>)" title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <?php if (!$user_row['email_verified']): ?>
                                                <a href="?toggle_status=<?= $user_row['id'] ?>&status=verify" class="btn btn-sm btn-success" onclick="return confirm('Verify this user?')" title="Verify User">
                                                    <i class="fa-solid fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?toggle_status=<?= $user_row['id'] ?>&status=delete" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user?')" title="Delete User">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
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
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewUserDetails(userId) {
    // Show modal
    var modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    modal.show();
    
    // Fetch user details via AJAX
    fetch('ajax/get_user_details.php?id=' + userId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let sellerHtml = '';
                if (data.is_seller) {
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
                                        <span class="badge ${data.seller.status === 'verified' ? 'bg-success' : (data.seller.status === 'pending' ? 'bg-warning' : 'bg-danger')}">
                                            ${data.seller.status || 'N/A'}
                                        </span>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <strong>Applied on:</strong> ${data.seller.created_at ? new Date(data.seller.created_at).toLocaleDateString() : 'N/A'}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
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
                                    <span class="badge ${data.user.role === 'admin' ? 'bg-danger' : (data.user.role === 'seller' ? 'bg-warning' : 'bg-primary')}">
                                        ${data.user.role || 'N/A'}
                                    </span>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Phone:</strong> ${data.user.phone || 'N/A'}
                                </div>
                                <div class="col-md-6 mb-2">
                                    <strong>Email Verified:</strong> 
                                    <span class="badge ${data.user.email_verified ? 'bg-success' : 'bg-warning'}">
                                        ${data.user.email_verified ? 'Verified' : 'Pending'}
                                    </span>
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
            } else {
                document.getElementById('userDetailsContent').innerHTML = `
                    <div class="alert alert-danger">${data.message}</div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('userDetailsContent').innerHTML = `
                <div class="alert alert-danger">Error loading user details.</div>
            `;
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>