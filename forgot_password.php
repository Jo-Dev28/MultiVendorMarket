<?php
$page_title = 'Reset Password';
require_once 'includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('forgot_password.php');
    }
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        flash('Enter a valid email address.', 'danger');
    } else {
        $user = get_user_by_email($mysqli, $email);
        if ($user) {
            $resetToken = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            $sql = 'UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ssi', $resetToken, $expires, $user['id']);
            $stmt->execute();
            flash('Password reset instructions have been sent to your email.', 'success');
            $_SESSION['reset_simulated'] = "Use token: $resetToken";
            redirect('login.php');
        } else {
            flash('No account matches that email.', 'danger');
        }
    }
}
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-5">
                <h2 class="mb-4">Reset Password</h2>
                <form method="post" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Send reset link</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'includes/footer.php';
