<?php
$page_title = 'Login';
require_once 'includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    $user = current_user();
    if ($user['role'] === 'admin') {
        redirect('admin/dashboard.php');
    } elseif ($user['role'] === 'seller') {
        redirect('seller/dashboard.php');
    } else {
        redirect('index.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('login.php');
    }
    
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (!$email || !$password) {
        flash('Please enter your email and password.', 'danger');
    } else {
        $user = get_user_by_email($mysqli, $email);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // ============================================
            // CHECK IF SELLER IS ACTIVE - BLOCK INACTIVE SELLERS
            // ============================================
            if ($user['role'] === 'seller') {
                $check_sql = "SELECT is_active FROM sellers WHERE user_id = ?";
                $check_stmt = $mysqli->prepare($check_sql);
                if ($check_stmt) {
                    $check_stmt->bind_param('i', $user['id']);
                    $check_stmt->execute();
                    $result = $check_stmt->get_result();
                    $seller_status = $result->fetch_assoc();
                    $check_stmt->close();
                    
                    if ($seller_status && isset($seller_status['is_active']) && $seller_status['is_active'] == 0) {
                        flash('Your seller account has been deactivated. Please contact support.', 'danger');
                        redirect('login.php');
                    }
                }
            }
            
            // ============================================
            // SET ALL SESSION VARIABLES
            // ============================================
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            // Remember me functionality
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
                
                $update_sql = "UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?";
                $update_stmt = $mysqli->prepare($update_sql);
                $update_stmt->bind_param('ssi', $token, $expires, $user['id']);
                $update_stmt->execute();
                
                setcookie('remember_token', $token, time() + (86400 * 7), '/');
            }
            
            flash('Welcome back, ' . htmlspecialchars($user['name']) . '!', 'success');
            
            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    redirect('admin/dashboard.php');
                    break;
                case 'seller':
                    redirect('seller/dashboard.php');
                    break;
                default:
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect_url = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        redirect($redirect_url);
                    } else {
                        redirect('index.php');
                    }
            }
        } else {
            flash('Invalid email or password.', 'danger');
        }
    }
}
?>

<style>
    .login-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 0;
    }
    
    .login-card {
        background: var(--white);
        border-radius: 24px;
        box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .login-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 40px -12px rgba(0,0,0,0.15);
    }
    
    .login-header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        padding: 30px;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .login-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: rotate 20s linear infinite;
    }
    
    @keyframes rotate {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .login-icon {
        width: 80px;
        height: 80px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        backdrop-filter: blur(10px);
    }
    
    .login-icon i {
        font-size: 2.5rem;
        color: white;
    }
    
    .login-header h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .login-header p {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-top: 8px;
        position: relative;
        z-index: 1;
    }
    
    .login-body {
        padding: 35px;
    }
    
    .form-group {
        margin-bottom: 25px;
    }
    
    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .form-label i {
        color: #f59e0b;
        width: 18px;
    }
    
    .input-group-custom {
        position: relative;
    }
    
    .input-group-custom i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        font-size: 1rem;
    }
    
    .form-control {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }
    
    .remember-forgot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 0.85rem;
        color: #6b7280;
    }
    
    .checkbox-label input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #2563eb;
    }
    
    .forgot-link {
        font-size: 0.85rem;
        color: #2563eb;
        text-decoration: none;
    }
    
    .forgot-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }
    
    .btn-login {
        width: 100%;
        padding: 14px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
    }
    
    .register-link {
        text-align: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .register-link p {
        color: #6b7280;
        font-size: 0.9rem;
    }
    
    .register-link a {
        color: #f59e0b;
        text-decoration: none;
        font-weight: 600;
    }
    
    .register-link a:hover {
        text-decoration: underline;
    }
    
    .demo-credentials {
        background: #fef3c7;
        border-radius: 12px;
        padding: 15px;
        margin-top: 20px;
        cursor: pointer;
    }
    
    .demo-credentials p {
        font-size: 0.75rem;
        color: #92400e;
        margin: 0;
    }
    
    .demo-credentials .demo-title {
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    @media (max-width: 768px) {
        .login-body {
            padding: 25px;
        }
        .login-header h2 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="login-card">
                    <div class="login-header">
                        <div class="login-icon">
                            <i class="fa-solid fa-store"></i>
                        </div>
                        <h2>Welcome Back</h2>
                        <p>Sign in to continue to <?= SITE_NAME ?></p>
                    </div>
                    
                    <div class="login-body">
                        <form method="post" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-regular fa-envelope"></i> Email Address
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-regular fa-envelope"></i>
                                    <input type="email" name="email" class="form-control" 
                                           placeholder="Enter your email" required autofocus>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-lock"></i> Password
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <div class="remember-forgot">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="remember"> 
                                    Remember me
                                </label>
                                <a href="forgot_password.php" class="forgot-link">
                                    Forgot Password?
                                </a>
                            </div>
                            
                            <button type="submit" class="btn-login">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
                            </button>
                        </form>
                        
                        <div class="register-link">
                            <p>Don't have an account? <a href="register.php">Create an account</a></p>
                        </div>
                        
                        <div class="demo-credentials" onclick="fillDemoCredentials()">
                            <p class="demo-title"><i class="fa-regular fa-lightbulb"></i> Demo Credentials (Click to fill)</p>
                            <p><strong>Admin:</strong> josbosimwendaadmin@gmail.com / 281220</p>
                            <p><strong>Seller:</strong> josbosimwenda@gmail.com / 281220</p>
                            <p><strong>Customer:</strong> josbosimwendacustomer@gmail.com / 281220</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function fillDemoCredentials() {
    document.querySelector('input[name="email"]').value = 'josbosimwendaadmin@gmail.com';
    document.querySelector('input[name="password"]').value = '281220';
}
</script>

<?php require_once 'includes/footer.php'; ?>