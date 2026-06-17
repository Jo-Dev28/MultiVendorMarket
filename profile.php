<?php
$page_title = 'My Profile';
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('profile.php');
    }
    
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($name)) {
        flash('Name is required.', 'danger');
    } else {
        $update_sql = 'UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?';
        $update_stmt = $mysqli->prepare($update_sql);
        $update_stmt->bind_param('sssi', $name, $phone, $address, $user_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['user_name'] = $name;
            flash('Profile updated successfully!', 'success');
            redirect('profile.php');
        } else {
            flash('Unable to update profile.', 'danger');
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('profile.php');
    }
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        flash('All password fields are required.', 'danger');
    } elseif ($new_password !== $confirm_password) {
        flash('New passwords do not match.', 'danger');
    } elseif (strlen($new_password) < 6) {
        flash('Password must be at least 6 characters.', 'danger');
    } else {
        $check_sql = "SELECT password_hash FROM users WHERE id = ?";
        $check_stmt = $mysqli->prepare($check_sql);
        $check_stmt->bind_param('i', $user_id);
        $check_stmt->execute();
        $user_data = $check_stmt->get_result()->fetch_assoc();
        
        if (password_verify($current_password, $user_data['password_hash'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET password_hash = ? WHERE id = ?";
            $update_stmt = $mysqli->prepare($update_sql);
            $update_stmt->bind_param('si', $new_hash, $user_id);
            if ($update_stmt->execute()) {
                flash('Password changed successfully.', 'success');
                redirect('profile.php');
            } else {
                flash('Failed to change password.', 'danger');
            }
        } else {
            flash('Current password is incorrect.', 'danger');
        }
    }
}

// Get user data - DIRECT QUERY
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Set display variables
$display_name = isset($user['name']) ? $user['name'] : 'Not set';
$display_email = isset($user['email']) ? $user['email'] : 'Not set';
$display_phone = (isset($user['phone']) && !empty($user['phone'])) ? $user['phone'] : 'Not set';
$display_address = (isset($user['address']) && !empty($user['address'])) ? $user['address'] : 'Not set';
$display_member_since = isset($user['created_at']) ? date('F d, Y', strtotime($user['created_at'])) : 'Not set';
$display_role = isset($user['role']) ? ucfirst($user['role']) : 'Customer';
$display_verified = ($user['email_verified'] ?? 0) ? 'Verified' : 'Not Verified';
?>

<style>
    .profile-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .profile-header {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        color: white;
    }
    
    .profile-avatar {
        display: flex;
        align-items: center;
        gap: 20px;
    }
    
    .avatar-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .avatar-icon i {
        font-size: 2.5rem;
    }
    
    .profile-name h2 {
        font-size: 1.5rem;
        margin: 0 0 5px 0;
    }
    
    .profile-name p {
        margin: 0;
        opacity: 0.9;
    }
    
    .profile-stats {
        display: flex;
        gap: 30px;
        margin-top: 25px;
        flex-wrap: wrap;
    }
    
    .stat-box {
        background: rgba(255,255,255,0.15);
        padding: 10px 20px;
        border-radius: 12px;
        text-align: center;
    }
    
    .stat-number {
        font-size: 0.9rem;
        font-weight: 600;
    }
    
    .stat-label {
        font-size: 0.7rem;
        opacity: 0.8;
        margin-top: 5px;
    }
    
    .profile-card {
        background: white;
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .card-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f59e0b;
        display: inline-block;
    }
    
    .info-grid {
        display: flex;
        flex-wrap: wrap;
    }
    
    .info-item {
        flex: 0 0 50%;
        padding: 12px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-item.full-width {
        flex: 0 0 100%;
    }
    
    .info-label {
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    
    .info-value {
        font-size: 1rem;
        font-weight: 500;
        color: #1f2937;
    }
    
    .phone-value {
        color: #10b981;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.9rem;
    }
    
    .form-control:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    
    .form-control:disabled {
        background: #f3f4f6;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    
    .btn-change-password {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        background: #3b82f6;
        color: white;
    }
    
    .row-flex {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .col-3 {
        flex: 0 0 calc(25% - 15px);
    }
    
    .col-9 {
        flex: 0 0 calc(75% - 5px);
    }
    
    @media (max-width: 992px) {
        .col-3, .col-9 { flex: 0 0 100%; }
        .info-item { flex: 0 0 100%; }
    }
    
    @media (max-width: 768px) {
        .profile-avatar { flex-direction: column; text-align: center; }
        .profile-stats { justify-content: center; }
    }
</style>

<div class="profile-container">
    <div class="row-flex">
        <div class="col-3">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="col-9">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <div class="avatar-icon">
                        <i class="fa-regular fa-circle-user"></i>
                    </div>
                    <div class="profile-name">
                        <h2><?= htmlspecialchars($display_name) ?></h2>
                        <p><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($display_email) ?></p>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-box">
                        <div class="stat-number"><?= $display_member_since ?></div>
                        <div class="stat-label">Member Since</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $display_role ?></div>
                        <div class="stat-label">Account Type</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number"><?= $display_verified ?></div>
                        <div class="stat-label">Email Status</div>
                    </div>
                </div>
            </div>
            
            <!-- Current Information Card -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fa-regular fa-circle-info"></i> Current Information</h3>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?= htmlspecialchars($display_name) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?= htmlspecialchars($display_email) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value">
                            <?php if ($display_phone !== 'Not set'): ?>
                                <span class="phone-value"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($display_phone) ?></span>
                            <?php else: ?>
                                <span style="color: #dc2626;">Not set</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Account Role</div>
                        <div class="info-value"><span class="role-badge"><?= $display_role ?></span></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Member Since</div>
                        <div class="info-value"><?= $display_member_since ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Verification</div>
                        <div class="info-value"><?= $display_verified ?></div>
                    </div>
                    <div class="info-item full-width">
                        <div class="info-label">Address</div>
                        <div class="info-value">
                            <?php if ($display_address !== 'Not set'): ?>
                                <?= nl2br(htmlspecialchars($display_address)) ?>
                            <?php else: ?>
                                <span style="color: #dc2626;">Not set</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Profile Form -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fa-regular fa-pen-to-square"></i> Edit Profile</h3>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="row-flex">
                        <div style="flex: 0 0 calc(50% - 10px);">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                            </div>
                        </div>
                        <div style="flex: 0 0 calc(50% - 10px);">
                            <div class="form-group">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                            </div>
                        </div>
                        <div style="flex: 0 0 calc(50% - 10px);">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="0712345678">
                                <small class="text-muted">Example: 0712345678</small>
                            </div>
                        </div>
                        <div style="flex: 0 0 calc(50% - 10px);">
                            <div class="form-group">
                                <label class="form-label">Member Since</label>
                                <input type="text" class="form-control" value="<?= $display_member_since ?>" disabled>
                            </div>
                        </div>
                        <div style="flex: 0 0 100%;">
                            <div class="form-group">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="3" placeholder="Your address"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save">
                        <i class="fa-regular fa-floppy-disk"></i> Save Changes
                    </button>
                </form>
            </div>
            
            <!-- Change Password -->
            <div class="profile-card">
                <h3 class="card-title"><i class="fa-solid fa-lock"></i> Change Password</h3>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="row-flex">
                        <div style="flex: 0 0 calc(50% - 10px);">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" required>
                                <small class="text-muted">Minimum 6 characters</small>
                            </div>
                        </div>
                        <div style="flex: 0 0 calc(50% - 10px);">
                            <div class="form-group">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-change-password">
                        <i class="fa-solid fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>