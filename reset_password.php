<?php
$page_title = 'Set New Password';
require_once 'includes/header.php';
$token = $_GET['token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('reset_password.php?token=' . urlencode($token));
    }
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$password || !$confirm) {
        flash('Complete both password fields.', 'danger');
    } elseif ($password !== $confirm) {
        flash('Passwords do not match.', 'danger');
    } else {
        $sql = 'SELECT id, reset_expires FROM users WHERE reset_token = ? LIMIT 1';
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if ($user && strtotime($user['reset_expires']) > time()) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = 'UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?';
            $stmt2 = $mysqli->prepare($update);
            $stmt2->bind_param('si', $hash, $user['id']);
            $stmt2->execute();
            flash('Password updated successfully.', 'success');
            redirect('login.php');
        } else {
            flash('Reset token expired or invalid.', 'danger');
            redirect('forgot_password.php');
        }
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <h2 class="mb-4">Set New Password</h2>
                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php';
