<?php
$page_title = 'Register';
require_once 'includes/header.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('register.php');
    }
    
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    
    $errors = [];
    
    if (!$name) {
        $errors[] = 'Please enter your full name.';
    }
    if (!$email) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!$password) {
        $errors[] = 'Please enter a password.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    
    if (empty($errors)) {
        if (get_user_by_email($mysqli, $email)) {
            flash('Email already in use. Please use a different email or login.', 'danger');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(16));
            
            $sql = 'INSERT INTO users (name, email, password_hash, phone, address, role, email_verified, verification_token, created_at) 
                    VALUES (?, ?, ?, ?, ?, "customer", 0, ?, NOW())';
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ssssss', $name, $email, $hash, $phone, $address, $token);
            
            if ($stmt->execute()) {
                // Store verification token in session for demo
                $_SESSION['verification_simulated'] = "Verification token: $token";
                
                flash('Registration successful! Please check your email to verify your account.', 'success');
                redirect('login.php');
            } else {
                flash('Unable to create account. Please try again.', 'danger');
            }
        }
    } else {
        foreach ($errors as $error) {
            flash($error, 'danger');
        }
    }
}
?>

<style>
    .register-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 0;
    }
    
    .register-card {
        background: white;
        border-radius: 24px;
        box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .register-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 25px 40px -12px rgba(0,0,0,0.15);
    }
    
    .register-header {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        padding: 30px;
        text-align: center;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .register-header::before {
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
    
    .register-icon {
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
    
    .register-icon i {
        font-size: 2.5rem;
        color: white;
    }
    
    .register-header h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .register-header p {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-top: 8px;
        position: relative;
        z-index: 1;
    }
    
    .register-body {
        padding: 35px;
    }
    
    .form-group {
        margin-bottom: 20px;
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
    
    textarea.form-control {
        padding-left: 15px;
        resize: vertical;
    }
    
    .btn-register {
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
        margin-top: 10px;
    }
    
    .btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
    }
    
    .login-link {
        text-align: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }
    
    .login-link p {
        color: #6b7280;
        font-size: 0.9rem;
    }
    
    .login-link a {
        color: #f59e0b;
        text-decoration: none;
        font-weight: 600;
    }
    
    .login-link a:hover {
        text-decoration: underline;
    }
    
    .password-requirements {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 5px;
    }
    
    .password-requirements i {
        margin-right: 5px;
    }
    
    @media (max-width: 768px) {
        .register-body {
            padding: 25px;
        }
        .register-header h2 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="register-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7 col-lg-6">
                <div class="register-card">
                    <div class="register-header">
                        <div class="register-icon">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <h2>Create Account</h2>
                        <p>Join <?= SITE_NAME ?> and start shopping</p>
                    </div>
                    
                    <div class="register-body">
                        <form method="post" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-regular fa-user"></i> Full Name
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-regular fa-user"></i>
                                    <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-regular fa-envelope"></i> Email Address
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-regular fa-envelope"></i>
                                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-phone"></i> Phone Number
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-solid fa-phone"></i>
                                    <input type="tel" name="phone" class="form-control" placeholder="0712345678">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-lock"></i> Password
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
                                </div>
                                <div class="password-requirements">
                                    <i class="fa-regular fa-circle-check"></i> Minimum 6 characters
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-lock"></i> Confirm Password
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-solid fa-lock"></i>
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-solid fa-location-dot"></i> Address
                                </label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Your address (optional)"></textarea>
                            </div>
                            
                            <button type="submit" class="btn-register">
                                <i class="fa-solid fa-arrow-right-to-bracket"></i> Create Account
                            </button>
                        </form>
                        
                        <div class="login-link">
                            <p>Already have an account? <a href="login.php">Sign In</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password confirmation validation
const form = document.querySelector('form');
const password = document.getElementById('password');
const confirmPassword = document.querySelector('[name="confirm_password"]');

if (form && password && confirmPassword) {
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Password Mismatch',
                text: 'Your passwords do not match. Please try again.',
                confirmButtonColor: '#f59e0b'
            });
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>