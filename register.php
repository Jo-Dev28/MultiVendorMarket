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
    $agree_terms = isset($_POST['agree_terms']) ? true : false;
    
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
    if (!$agree_terms) {
        $errors[] = 'You must agree to the Terms and Conditions.';
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
    
    .btn-register:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
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
    
    /* Terms Checkbox Styles */
    .terms-checkbox {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-top: 5px;
    }
    
    .terms-checkbox input[type="checkbox"] {
        width: 18px;
        height: 18px;
        margin-top: 3px;
        cursor: pointer;
        accent-color: #2563eb;
        flex-shrink: 0;
        /* Remove required attribute styling */
    }
    
    .terms-checkbox label {
        font-size: 0.85rem;
        color: #4b5563;
        cursor: pointer;
        line-height: 1.5;
    }
    
    .terms-checkbox label .terms-link {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        cursor: pointer;
    }
    
    .terms-checkbox label .terms-link:hover {
        text-decoration: underline;
    }
    
    .terms-checkbox .required-star {
        color: #ef4444;
    }
    
    .terms-error {
        color: #ef4444;
        font-size: 0.8rem;
        margin-top: 5px;
        display: none;
    }
    
    .terms-error.show {
        display: block;
    }
    
    /* ============================================
       TERMS MODAL - ENHANCED DESIGN
    ============================================ */
    .terms-modal .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
        max-height: 90vh;
        box-shadow: 0 25px 60px rgba(0,0,0,0.3);
    }
    
    .terms-modal .modal-header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        color: white;
        padding: 20px 25px;
        border: none;
        position: relative;
        overflow: hidden;
    }
    
    .terms-modal .modal-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .terms-modal .modal-header .modal-title {
        font-weight: 700;
        font-size: 1.2rem;
        position: relative;
        z-index: 1;
    }
    
    .terms-modal .modal-header .modal-title i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .terms-modal .modal-header .btn-close {
        filter: brightness(0) invert(1);
        position: relative;
        z-index: 1;
    }
    
    .terms-modal .modal-header .modal-subtitle {
        font-size: 0.8rem;
        opacity: 0.7;
        margin-top: 4px;
        position: relative;
        z-index: 1;
    }
    
    .terms-modal .modal-body {
        padding: 25px;
        max-height: 55vh;
        overflow-y: auto;
        background: #fafbfc;
    }
    
    .terms-modal .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    
    .terms-modal .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .terms-modal .modal-body::-webkit-scrollbar-thumb {
        background: #2563eb;
        border-radius: 10px;
    }
    
    .terms-modal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #1d4ed8;
    }
    
    .terms-modal .modal-body .terms-content {
        font-size: 0.9rem;
        color: #4b5563;
        line-height: 1.8;
    }
    
    .terms-modal .modal-body .terms-content .section-number {
        display: inline-block;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 1px 12px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 700;
        margin-right: 8px;
    }
    
    .terms-modal .modal-body .terms-content .section-title {
        color: #1f2937;
        font-weight: 700;
        font-size: 1rem;
        margin-top: 20px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
    }
    
    .terms-modal .modal-body .terms-content .section-title:first-child {
        margin-top: 0;
    }
    
    .terms-modal .modal-body .terms-content .section-title i {
        color: #f59e0b;
        margin-right: 8px;
        font-size: 0.9rem;
    }
    
    .terms-modal .modal-body .terms-content p {
        margin-bottom: 8px;
        padding-left: 28px;
    }
    
    .terms-modal .modal-body .terms-content ul {
        padding-left: 45px;
        margin: 6px 0 10px 0;
    }
    
    .terms-modal .modal-body .terms-content ul li {
        margin-bottom: 4px;
        list-style-type: none;
        position: relative;
        padding-left: 20px;
    }
    
    .terms-modal .modal-body .terms-content ul li::before {
        content: '▸';
        color: #2563eb;
        position: absolute;
        left: 0;
        font-weight: 700;
    }
    
    .terms-modal .modal-body .terms-content .highlight-box {
        background: #eff6ff;
        border-radius: 10px;
        padding: 12px 16px;
        margin: 12px 0;
        border-left: 3px solid #2563eb;
        padding-left: 20px;
    }
    
    .terms-modal .modal-body .terms-content .highlight-box strong {
        color: #1e40af;
    }
    
    .terms-modal .modal-body .terms-content .divider {
        height: 1px;
        background: linear-gradient(90deg, #e5e7eb, transparent);
        margin: 15px 0;
    }
    
    .terms-scroll-indicator {
        text-align: center;
        padding: 10px;
        background: #f8fafc;
        border-radius: 10px;
        margin-top: 15px;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }
    
    .terms-scroll-indicator i {
        margin-right: 6px;
    }
    
    .terms-scroll-indicator.complete {
        background: #f0fdf4;
        border-color: #86efac;
        color: #065f46;
    }
    
    .terms-scroll-indicator.complete i {
        color: #10b981;
    }
    
    .terms-modal .modal-footer {
        padding: 15px 25px;
        border-top: 1px solid #e5e7eb;
        background: white;
        border-radius: 0 0 20px 20px;
    }
    
    .terms-modal .modal-footer .btn-agree {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .terms-modal .modal-footer .btn-agree:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    
    .terms-modal .modal-footer .btn-agree:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }
    
    .terms-modal .modal-footer .btn-close-modal {
        background: #f3f4f6;
        color: #374151;
        border: none;
        padding: 10px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .terms-modal .modal-footer .btn-close-modal:hover {
        background: #e5e7eb;
    }
    
    @media (max-width: 768px) {
        .register-body {
            padding: 25px;
        }
        .register-header h2 {
            font-size: 1.5rem;
        }
        .terms-checkbox {
            align-items: flex-start;
        }
        .terms-modal .modal-body {
            padding: 15px;
            max-height: 50vh;
        }
        .terms-modal .modal-body .terms-content ul {
            padding-left: 25px;
        }
        .terms-modal .modal-body .terms-content p {
            padding-left: 15px;
        }
        .terms-modal .modal-footer {
            flex-direction: column;
            gap: 10px;
        }
        .terms-modal .modal-footer .btn-agree,
        .terms-modal .modal-footer .btn-close-modal {
            width: 100%;
            justify-content: center;
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
                        <form method="post" novalidate id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-regular fa-user"></i> Full Name <span class="text-danger">*</span>
                                </label>
                                <div class="input-group-custom">
                                    <i class="fa-regular fa-user"></i>
                                    <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fa-regular fa-envelope"></i> Email Address <span class="text-danger">*</span>
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
                                    <i class="fa-solid fa-lock"></i> Password <span class="text-danger">*</span>
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
                                    <i class="fa-solid fa-lock"></i> Confirm Password <span class="text-danger">*</span>
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
                            
                            <!-- Terms and Conditions Checkbox - NO REQUIRED ATTRIBUTE -->
                            <div class="form-group">
                                <div class="terms-checkbox">
                                    <input type="checkbox" name="agree_terms" id="agreeTerms" value="1">
                                    <label for="agreeTerms">
                                        I agree to the <span class="terms-link" onclick="openTermsModal()">Terms and Conditions</span> 
                                        and <a href="privacy.php" target="_blank">Privacy Policy</a> 
                                        <span class="required-star">*</span>
                                    </label>
                                </div>
                                <div class="terms-error" id="termsError">
                                    <i class="fa-solid fa-circle-exclamation"></i> Please agree to the Terms and Conditions to continue.
                                </div>
                            </div>
                            
                            <button type="submit" class="btn-register" id="registerBtn">
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

<!-- ============================================
     TERMS AND CONDITIONS MODAL - ENHANCED
============================================ -->
<div class="modal fade terms-modal" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title"><i class="fa-regular fa-file-lines"></i> Terms and Conditions</h5>
                    <div class="modal-subtitle">Please read carefully before creating your account</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="terms-content">
                    <!-- Section 1 -->
                    <div class="section-title">
                        <span class="section-number">1</span>
                        <i class="fa-regular fa-circle-check"></i> Acceptance of Terms
                    </div>
                    <p>By creating an account and using <?= SITE_NAME ?>, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use our platform.</p>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 2 -->
                    <div class="section-title">
                        <span class="section-number">2</span>
                        <i class="fa-regular fa-user"></i> Account Registration
                    </div>
                    <p>You must provide accurate and complete information when creating your account.</p>
                    <ul>
                        <li>You must be at least <strong>18 years old</strong> to create an account</li>
                        <li>You are responsible for all activities under your account</li>
                        <li>Notify us immediately of any unauthorized use</li>
                        <li>Keep your password secure and confidential</li>
                    </ul>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 3 -->
                    <div class="section-title">
                        <span class="section-number">3</span>
                        <i class="fa-solid fa-box"></i> Products and Listings
                    </div>
                    <p>Sellers are responsible for the accuracy of their product listings.</p>
                    <ul>
                        <li>Product descriptions must be <strong>accurate and complete</strong></li>
                        <li>Products must be <strong>authentic</strong> and not counterfeit</li>
                        <li>Prohibited items are <strong>not allowed</strong> on the platform</li>
                        <li>Sellers must ensure product availability</li>
                    </ul>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 4 -->
                    <div class="section-title">
                        <span class="section-number">4</span>
                        <i class="fa-solid fa-truck"></i> Orders and Purchases
                    </div>
                    <p>When you place an order, you enter into a contract with the seller.</p>
                    <ul>
                        <li>Orders may be <strong>cancelled</strong> before processing</li>
                        <li>Disputes must be resolved <strong>directly with the seller</strong></li>
                        <li>You will receive <strong>order confirmation</strong> via email</li>
                        <li>Track your orders in <strong>"My Orders"</strong></li>
                    </ul>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 5 -->
                    <div class="section-title">
                        <span class="section-number">5</span>
                        <i class="fa-solid fa-credit-card"></i> Payments
                    </div>
                    <p>We accept multiple payment methods for your convenience.</p>
                    <ul>
                        <li><strong>M-Pesa</strong> - Mobile money payments</li>
                        <li><strong>Credit/Debit Cards</strong> - Visa and Mastercard</li>
                        <li><strong>Bank Transfer</strong> - Direct bank payments</li>
                        <li><strong>PayPal</strong> - International payments</li>
                    </ul>
                    <div class="highlight-box">
                        <i class="fa-solid fa-lock" style="color:#2563eb;"></i>
                        <strong>All transactions are encrypted and secure.</strong> We do not store your payment information.
                    </div>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 6 -->
                    <div class="section-title">
                        <span class="section-number">6</span>
                        <i class="fa-solid fa-truck-fast"></i> Shipping and Delivery
                    </div>
                    <p>Sellers are responsible for shipping. Delivery times vary by location.</p>
                    <ul>
                        <li><strong>Free shipping</strong> on orders over KSH 5,000</li>
                        <li>Tracking numbers provided for <strong>all shipped orders</strong></li>
                        <li>Ensure your <strong>shipping address is accurate</strong></li>
                        <li>Delivery takes <strong>2-5 business days</strong></li>
                    </ul>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 7 -->
                    <div class="section-title">
                        <span class="section-number">7</span>
                        <i class="fa-solid fa-rotate-left"></i> Returns and Refunds
                    </div>
                    <p>Returns are accepted within <strong>7 days</strong> of delivery.</p>
                    <ul>
                        <li>Items must be <strong>unused and in original packaging</strong></li>
                        <li>Return shipping is the <strong>customer's responsibility</strong></li>
                        <li>Refunds processed <strong>after inspection</strong> (3-5 business days)</li>
                        <li><strong>Non-returnable:</strong> Digital products, perishable items</li>
                    </ul>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 8 -->
                    <div class="section-title">
                        <span class="section-number">8</span>
                        <i class="fa-solid fa-shield-hart"></i> Liability
                    </div>
                    <p><?= SITE_NAME ?> is provided "as is" without warranties of any kind.</p>
                    <ul>
                        <li>We are <strong>not liable</strong> for indirect or consequential damages</li>
                        <li>We do not guarantee <strong>uninterrupted service</strong></li>
                        <li>Third-party links are <strong>not our responsibility</strong></li>
                    </ul>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 9 -->
                    <div class="section-title">
                        <span class="section-number">9</span>
                        <i class="fa-solid fa-lock"></i> Privacy Policy
                    </div>
                    <p>We are committed to protecting your privacy.</p>
                    <ul>
                        <li>Your personal information is <strong>securely stored</strong></li>
                        <li>We do not <strong>sell or share</strong> your data with third parties</li>
                        <li>View our full <a href="privacy.php" target="_blank" style="color:#2563eb; text-decoration:none; font-weight:600;">Privacy Policy</a> for more details</li>
                    </ul>
                    
                    <div class="divider"></div>
                    
                    <!-- Section 10 -->
                    <div class="section-title">
                        <span class="section-number">10</span>
                        <i class="fa-regular fa-headset"></i> Contact Us
                    </div>
                    <p>If you have any questions about these terms, please contact us:</p>
                    <ul>
                        <li><strong>Email:</strong> support@multivendorhub.com</li>
                        <li><strong>Phone:</strong> +254 700 000 000</li>
                        <li><strong>Live Chat:</strong> Available 24/7</li>
                    </ul>
                    
                    <div class="terms-scroll-indicator" id="scrollIndicator">
                        <i class="fa-regular fa-circle-check"></i> Please scroll to the bottom to agree
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-close-modal" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn-agree" id="agreeTermsBtn" disabled>
                    <i class="fa-regular fa-circle-check"></i> I Agree to Terms
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ============================================
// TERMS MODAL FUNCTIONS
// ============================================
let termsAgreed = false;

function openTermsModal() {
    const modal = new bootstrap.Modal(document.getElementById('termsModal'));
    modal.show();
    
    // Reset agree button
    const agreeBtn = document.getElementById('agreeTermsBtn');
    agreeBtn.disabled = true;
    termsAgreed = false;
    
    // Reset the modal body scroll position
    const modalBody = document.querySelector('#termsModal .modal-body');
    if (modalBody) {
        modalBody.scrollTop = 0;
    }
    
    // Reset scroll indicator
    const indicator = document.getElementById('scrollIndicator');
    if (indicator) {
        indicator.classList.remove('complete');
        indicator.innerHTML = '<i class="fa-regular fa-circle-check"></i> Please scroll to the bottom to agree';
    }
}

// Enable agree button when scrolled to bottom
document.addEventListener('DOMContentLoaded', function() {
    const modalBody = document.querySelector('#termsModal .modal-body');
    const agreeBtn = document.getElementById('agreeTermsBtn');
    const termsCheckbox = document.getElementById('agreeTerms');
    const indicator = document.getElementById('scrollIndicator');
    
    if (modalBody) {
        modalBody.addEventListener('scroll', function() {
            // Check if scrolled to bottom (with 20px tolerance)
            const isBottom = this.scrollHeight - this.scrollTop - this.clientHeight < 20;
            if (isBottom) {
                agreeBtn.disabled = false;
                if (indicator) {
                    indicator.classList.add('complete');
                    indicator.innerHTML = '<i class="fa-regular fa-circle-check"></i> You have read the terms. Click "I Agree" to accept.';
                }
            } else {
                agreeBtn.disabled = true;
                if (indicator) {
                    indicator.classList.remove('complete');
                    indicator.innerHTML = '<i class="fa-regular fa-circle-check"></i> Please scroll to the bottom to agree';
                }
            }
        });
    }
    
    // Agree button click handler
    if (agreeBtn) {
        agreeBtn.addEventListener('click', function() {
            termsAgreed = true;
            if (termsCheckbox) {
                termsCheckbox.checked = true;
                // Trigger change event
                termsCheckbox.dispatchEvent(new Event('change'));
                // Also trigger click event to ensure form validation updates
                termsCheckbox.dispatchEvent(new Event('click'));
            }
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('termsModal'));
            if (modal) modal.hide();
            
            // Show success feedback
            Swal.fire({
                icon: 'success',
                title: 'Terms Accepted',
                text: 'You have agreed to the Terms and Conditions.',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
    }
    
    // ============================================
    // FIX: Reset checkbox state when modal is closed without agreeing
    // ============================================
    const termsModal = document.getElementById('termsModal');
    if (termsModal) {
        termsModal.addEventListener('hidden.bs.modal', function() {
            // If user closed modal without agreeing, uncheck the checkbox
            if (!termsAgreed) {
                if (termsCheckbox) {
                    termsCheckbox.checked = false;
                    termsCheckbox.dispatchEvent(new Event('change'));
                }
                // Reset the error message if it was showing
                const termsError = document.getElementById('termsError');
                if (termsError) {
                    termsError.classList.remove('show');
                }
            }
        });
    }
});

// ============================================
// FORM VALIDATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('registerForm');
    const password = document.getElementById('password');
    const confirmPassword = document.querySelector('[name="confirm_password"]');
    const agreeTerms = document.getElementById('agreeTerms');
    const termsError = document.getElementById('termsError');
    
    // Terms checkbox validation on submit
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            
            // Check terms agreement
            if (!agreeTerms.checked) {
                termsError.classList.add('show');
                hasError = true;
            } else {
                termsError.classList.remove('show');
            }
            
            // Check password match
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Password Mismatch',
                    text: 'Your passwords do not match. Please try again.',
                    confirmButtonColor: '#f59e0b'
                });
                return false;
            }
            
            // Check password length
            if (password && password.value.length > 0 && password.value.length < 6) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Password Too Short',
                    text: 'Password must be at least 6 characters long.',
                    confirmButtonColor: '#f59e0b'
                });
                return false;
            }
            
            if (hasError) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Terms Required',
                    text: 'Please agree to the Terms and Conditions to continue.',
                    confirmButtonColor: '#2563eb'
                });
                return false;
            }
        });
    }
    
    // Hide terms error when checkbox is checked
    if (agreeTerms) {
        agreeTerms.addEventListener('change', function() {
            if (this.checked) {
                termsError.classList.remove('show');
            }
        });
    }
    
    // Real-time password match validation
    if (password && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (password.value && this.value) {
                if (password.value !== this.value) {
                    this.style.borderColor = '#ef4444';
                    this.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
                } else {
                    this.style.borderColor = '#10b981';
                    this.style.boxShadow = '0 0 0 3px rgba(16, 185, 129, 0.1)';
                }
            } else {
                this.style.borderColor = '#e5e7eb';
                this.style.boxShadow = 'none';
            }
        });
    }
});

// Open terms modal when clicking the terms link
document.querySelector('.terms-link')?.addEventListener('click', function(e) {
    e.preventDefault();
    openTermsModal();
});
</script>

<?php require_once 'includes/footer.php'; ?>