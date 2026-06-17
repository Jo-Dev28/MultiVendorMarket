<?php
$page_title = 'Become a Seller';
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];

// Check if user is already a seller
$check_sql = "SELECT id, status, created_at, shop_name FROM sellers WHERE user_id = ?";
$check_stmt = $mysqli->prepare($check_sql);
$check_stmt->bind_param('i', $user_id);
$check_stmt->execute();
$existing_seller = $check_stmt->get_result()->fetch_assoc();

// Check if user has an active subscription
$active_subscription = null;
$pending_payment = null;

if ($existing_seller && isset($existing_seller['id'])) {
    $subscription_sql = "SELECT * FROM subscriptions WHERE seller_id = ? AND status = 'active' AND expires_at >= CURDATE() ORDER BY expires_at DESC LIMIT 1";
    $subscription_stmt = $mysqli->prepare($subscription_sql);
    $subscription_stmt->bind_param('i', $existing_seller['id']);
    $subscription_stmt->execute();
    $active_subscription = $subscription_stmt->get_result()->fetch_assoc();
    
    // Check if user has a pending payment
    $pending_payment_sql = "SELECT p.*, o.order_number FROM payments p 
                            JOIN orders o ON o.id = p.order_id 
                            WHERE o.seller_id = ? AND p.status = 'pending' 
                            LIMIT 1";
    $pending_payment_stmt = $mysqli->prepare($pending_payment_sql);
    $pending_payment_stmt->bind_param('i', $existing_seller['id']);
    $pending_payment_stmt->execute();
    $pending_payment = $pending_payment_stmt->get_result()->fetch_assoc();
}

// If already a verified seller with active subscription
if ($existing_seller && $existing_seller['status'] === 'verified' && $active_subscription) {
    flash('You are already a verified seller with an active subscription!', 'info');
    redirect('seller/dashboard.php');
}

// If application is pending
$is_pending = ($existing_seller && $existing_seller['status'] === 'pending');

// If waiting for payment approval
$waiting_payment = ($existing_seller && $existing_seller['status'] === 'verified' && $pending_payment && !$active_subscription);

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment']) && isset($_SESSION['temp_application'])) {
    $payment_method = sanitize($_POST['payment_method']);
    $transaction_ref = sanitize($_POST['transaction_ref'] ?? '');
    $application = $_SESSION['temp_application'];
    
    if (empty($payment_method)) {
        flash('Please select a payment method.', 'danger');
    } else {
        // Create order for payment
        $order_number = 'SUB-' . strtoupper(uniqid()) . '-' . date('YmdHis');
        $order_sql = "INSERT INTO orders (user_id, seller_id, order_number, total_amount, payment_method, shipping_address, status, created_at) 
                      VALUES (?, ?, ?, ?, ?, 'Subscription Payment', 'pending', NOW())";
        $order_stmt = $mysqli->prepare($order_sql);
        $order_stmt->bind_param('iisds', $user_id, $application['seller_id'], $order_number, $application['amount'], $payment_method);
        $order_stmt->execute();
        $order_id = $mysqli->insert_id;
        
        // Create payment record
        $payment_sql = "INSERT INTO payments (order_id, amount, method, status, transaction_reference, created_at) 
                        VALUES (?, ?, ?, 'pending', ?, NOW())";
        $payment_stmt = $mysqli->prepare($payment_sql);
        $payment_stmt->bind_param('idss', $order_id, $application['amount'], $payment_method, $transaction_ref);
        $payment_stmt->execute();
        
        // Clear temp session
        unset($_SESSION['temp_application']);
        
        flash('Payment submitted successfully! Our admin will verify your payment within 24-48 hours.', 'success');
        redirect('become_seller.php');
    }
}

// Handle new application submission with ID upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application']) && !$is_pending && !$existing_seller) {
    $shop_name = sanitize($_POST['shop_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $business_id = sanitize($_POST['business_id'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $plan_name = sanitize($_POST['selected_plan'] ?? '');
    $plan_amount = floatval($_POST['plan_amount'] ?? 0);
    
    // Handle ID upload
    $id_image = '';
    if (isset($_FILES['id_image']) && $_FILES['id_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = upload_image($_FILES['id_image'], 'seller_ids');
        if ($upload_result['success']) {
            $id_image = $upload_result['filename'];
        }
    }
    
    if (empty($shop_name) || empty($phone) || empty($business_id)) {
        flash('Please fill all required fields.', 'danger');
    } elseif (empty($plan_name)) {
        flash('Please select a subscription plan.', 'danger');
    } else {
        // Insert seller application
        $sql = "INSERT INTO sellers (user_id, shop_name, phone, business_id, description, location, id_image, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('issssss', $user_id, $shop_name, $phone, $business_id, $description, $location, $id_image);
        
        if ($stmt->execute()) {
            $seller_id = $mysqli->insert_id;
            
            // Store application in session for payment
            $_SESSION['temp_application'] = [
                'seller_id' => $seller_id,
                'plan_name' => $plan_name,
                'amount' => $plan_amount
            ];
            
            // Show payment form
            $show_payment_form = true;
        } else {
            flash('Failed to submit application. Please try again.', 'danger');
        }
    }
}

$show_payment_form = isset($_SESSION['temp_application']) && !$is_pending && !$existing_seller;
$temp_app = $_SESSION['temp_application'] ?? null;
?>

<style>
/* Become Seller Page Styles - No conflicts with header */
.become-seller-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

.become-seller-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.become-seller-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
    text-align: center;
}

.become-seller-subtitle {
    text-align: center;
    color: #6b7280;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.become-seller-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.become-seller-label i {
    color: #f59e0b;
    width: 20px;
}

.become-seller-input {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.become-seller-input:focus {
    border-color: #2563eb;
    outline: none;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
}

.become-seller-btn {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9rem;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.become-seller-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245,158,11,0.3);
}

.become-seller-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

/* Plan Cards */
.plan-card-seller {
    border: 2px solid #e5e7eb;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    margin-bottom: 15px;
    height: 100%;
}

.plan-card-seller:hover, .plan-card-seller.selected {
    border-color: #2563eb;
    background: #eff6ff;
    transform: translateY(-3px);
}

.plan-name-seller {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
}

.plan-price-seller {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2563eb;
    margin: 10px 0;
}

.plan-price-seller small {
    font-size: 0.7rem;
    color: #6b7280;
}

.plan-features-seller {
    list-style: none;
    padding: 0;
    margin: 15px 0 0;
    text-align: left;
}

.plan-features-seller li {
    padding: 5px 0;
    font-size: 0.8rem;
    color: #6b7280;
}

.plan-features-seller li i {
    color: #10b981;
    margin-right: 8px;
}

/* Info Box */
.info-box-seller {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-radius: 16px;
    padding: 20px;
    margin-top: 25px;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.info-box-seller i {
    font-size: 1.5rem;
    color: #d97706;
}

.info-box-content-seller h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 5px;
}

.info-box-content-seller p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

/* Benefits Section */
.benefits-seller {
    background: #f3f4f6;
    border-radius: 16px;
    padding: 20px;
    margin-top: 25px;
}

.benefits-title-seller {
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 15px;
}

.benefits-list-seller {
    list-style: none;
    padding: 0;
    margin: 0;
}

.benefits-list-seller li {
    padding: 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #6b7280;
    font-size: 0.8rem;
}

.benefits-list-seller li i {
    color: #10b981;
    width: 20px;
}

/* Pending Card */
.pending-card-seller {
    background: white;
    border-radius: 20px;
    padding: 40px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.pending-icon-seller {
    width: 80px;
    height: 80px;
    background: #fef3c7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.pending-icon-seller i {
    font-size: 2rem;
    color: #f59e0b;
}

.pending-title-seller {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 10px;
}

.pending-message-seller {
    color: #6b7280;
    margin-bottom: 20px;
}

.pending-details-seller {
    background: #f3f4f6;
    border-radius: 12px;
    padding: 15px;
    margin-top: 20px;
    text-align: left;
}

.pending-detail-row-seller {
    display: flex;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
}

.pending-detail-row-seller:last-child {
    border-bottom: none;
}

.pending-detail-label-seller {
    width: 120px;
    font-weight: 600;
    color: #1f2937;
}

.pending-detail-value-seller {
    flex: 1;
    color: #6b7280;
}

/* Payment Card */
.payment-card-seller {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.payment-icon-seller {
    width: 70px;
    height: 70px;
    background: #dbeafe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.payment-icon-seller i {
    font-size: 1.8rem;
    color: #2563eb;
}

.payment-method-card-seller {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 12px 15px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 15px;
}

.payment-method-card-seller:hover, .payment-method-card-seller.selected {
    border-color: #2563eb;
    background: #eff6ff;
}

.payment-method-icon-seller {
    width: 45px;
    height: 45px;
    background: #f3f4f6;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.payment-instructions-seller {
    background: #fef3c7;
    border-radius: 12px;
    padding: 15px;
    margin-top: 15px;
    text-align: left;
    font-size: 0.8rem;
}

.payment-details-seller {
    background: #f3f4f6;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    text-align: left;
}

.btn-pay-seller {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 10px;
    font-weight: 600;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-pay-seller:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(16,185,129,0.3);
}

.required-star {
    color: #ef4444;
    font-size: 0.7rem;
}

@media (max-width: 768px) {
    .become-seller-card, .pending-card-seller, .payment-card-seller {
        padding: 20px;
    }
    .pending-detail-row-seller {
        flex-direction: column;
    }
    .pending-detail-label-seller {
        width: 100%;
        margin-bottom: 5px;
    }
}
</style>

<div class="become-seller-wrapper container mb-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <?php if ($is_pending): ?>
                <!-- Pending Application -->
                <div class="pending-card-seller">
                    <div class="pending-icon-seller"><i class="fa-regular fa-clock"></i></div>
                    <h2 class="pending-title-seller">Application Pending Review</h2>
                    <p class="pending-message-seller">Your seller application has been submitted and is waiting for admin approval.</p>
                    <div class="pending-details-seller">
                        <div class="pending-detail-row-seller">
                            <div class="pending-detail-label-seller">Shop Name:</div>
                            <div class="pending-detail-value-seller"><?= htmlspecialchars($existing_seller['shop_name']) ?></div>
                        </div>
                        <div class="pending-detail-row-seller">
                            <div class="pending-detail-label-seller">Application Date:</div>
                            <div class="pending-detail-value-seller"><?= date('F d, Y', strtotime($existing_seller['created_at'])) ?></div>
                        </div>
                        <div class="pending-detail-row-seller">
                            <div class="pending-detail-label-seller">Status:</div>
                            <div class="pending-detail-value-seller"><span class="badge bg-warning">Pending Approval</span></div>
                        </div>
                    </div>
                    <div class="info-box-seller mt-3">
                        <i class="fa-regular fa-circle-question"></i>
                        <div class="info-box-content-seller">
                            <h4>What's next?</h4>
                            <p>Our admin team will review your application within 24-48 hours. Once approved, you can start selling.</p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>profile.php" class="btn btn-outline-primary mt-3 d-inline-block">Back to Profile</a>
                </div>
                
            <?php elseif ($waiting_payment): ?>
                <!-- Waiting for Payment Confirmation -->
                <div class="pending-card-seller">
                    <div class="pending-icon-seller" style="background: #dbeafe;"><i class="fa-regular fa-clock" style="color: #2563eb;"></i></div>
                    <h2 class="pending-title-seller">Payment Pending Confirmation</h2>
                    <p class="pending-message-seller">Your subscription payment is being verified by our admin team.</p>
                    <div class="pending-details-seller">
                        <div class="pending-detail-row-seller">
                            <div class="pending-detail-label-seller">Order #:</div>
                            <div class="pending-detail-value-seller"><?= htmlspecialchars($pending_payment['order_number']) ?></div>
                        </div>
                        <div class="pending-detail-row-seller">
                            <div class="pending-detail-label-seller">Amount:</div>
                            <div class="pending-detail-value-seller">KSH <?= number_format($pending_payment['amount']) ?></div>
                        </div>
                        <div class="pending-detail-row-seller">
                            <div class="pending-detail-label-seller">Status:</div>
                            <div class="pending-detail-value-seller"><span class="badge bg-info">Pending Verification</span></div>
                        </div>
                    </div>
                    <div class="info-box-seller mt-3">
                        <i class="fa-regular fa-clock"></i>
                        <div class="info-box-content-seller">
                            <h4>Processing Time</h4>
                            <p>Payment verification typically takes 24-48 hours. You'll receive an email once confirmed.</p>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>profile.php" class="btn btn-outline-primary mt-3 d-inline-block">Back to Profile</a>
                </div>
                
            <?php elseif ($show_payment_form && $temp_app): ?>
                <!-- Payment Form -->
                <div class="payment-card-seller">
                    <div class="payment-icon-seller"><i class="fa-solid fa-credit-card"></i></div>
                    <h2 class="become-seller-title">Complete Payment</h2>
                    <p class="become-seller-subtitle">Pay KSH <?= number_format($temp_app['amount']) ?> for <?= $temp_app['plan_name'] ?> Plan</p>
                    
                    <div class="payment-details-seller">
                        <p><strong>Application Submitted!</strong> Complete payment to activate your subscription.</p>
                        <hr>
                        <p><strong>Plan:</strong> <?= $temp_app['plan_name'] ?></p>
                        <p><strong>Amount:</strong> KSH <?= number_format($temp_app['amount']) ?></p>
                        <p><strong>Duration:</strong> 30 days</p>
                    </div>
                    
                    <form method="post">
                        <h4 class="mb-3">Select Payment Method</h4>
                        
                        <div class="payment-method-card-seller" onclick="selectPaymentMethod(this, 'M-Pesa')">
                            <input type="radio" name="payment_method" value="M-Pesa" class="d-none" required>
                            <div class="payment-method-icon-seller"><i class="fa-solid fa-mobile-alt"></i></div>
                            <div class="flex-grow-1 text-start">
                                <strong>M-Pesa</strong>
                                <p class="text-muted small mb-0">Pay using M-Pesa</p>
                            </div>
                        </div>
                        
                        <div class="payment-method-card-seller" onclick="selectPaymentMethod(this, 'Card')">
                            <input type="radio" name="payment_method" value="Card" class="d-none" required>
                            <div class="payment-method-icon-seller"><i class="fa-regular fa-credit-card"></i></div>
                            <div class="flex-grow-1 text-start">
                                <strong>Credit/Debit Card</strong>
                                <p class="text-muted small mb-0">Visa, Mastercard</p>
                            </div>
                        </div>
                        
                        <div class="payment-method-card-seller" onclick="selectPaymentMethod(this, 'Bank Transfer')">
                            <input type="radio" name="payment_method" value="Bank Transfer" class="d-none" required>
                            <div class="payment-method-icon-seller"><i class="fa-solid fa-building-columns"></i></div>
                            <div class="flex-grow-1 text-start">
                                <strong>Bank Transfer</strong>
                                <p class="text-muted small mb-0">Direct bank transfer</p>
                            </div>
                        </div>
                        
                        <div class="payment-method-card-seller" onclick="selectPaymentMethod(this, 'PayPal')">
                            <input type="radio" name="payment_method" value="PayPal" class="d-none" required>
                            <div class="payment-method-icon-seller"><i class="fa-brands fa-paypal"></i></div>
                            <div class="flex-grow-1 text-start">
                                <strong>PayPal</strong>
                                <p class="text-muted small mb-0">Pay with PayPal</p>
                            </div>
                        </div>
                        
                        <div id="mpesaInstructions" class="payment-instructions-seller" style="display: none;">
                            <i class="fa-solid fa-phone"></i> <strong>M-Pesa Instructions:</strong><br>
                            1. Go to M-Pesa → Lipa na M-Pesa → Pay Bill<br>
                            2. Business Number: <strong>123456</strong><br>
                            3. Account Number: <strong>SELLER<?= $user_id ?></strong><br>
                            4. Amount: <strong>KSH <?= number_format($temp_app['amount']) ?></strong><br>
                            5. Enter transaction code below
                        </div>
                        
                        <div id="bankInstructions" class="payment-instructions-seller" style="display: none;">
                            <i class="fa-solid fa-building-columns"></i> <strong>Bank Transfer Instructions:</strong><br>
                            Bank: <strong>KCB Bank Kenya</strong><br>
                            Account Name: <strong><?= SITE_NAME ?> Ltd</strong><br>
                            Account Number: <strong>1234567890</strong><br>
                            Reference: <strong>SUB-<?= date('Ymd') ?>-<?= $user_id ?></strong>
                        </div>
                        
                        <div id="transactionRefDiv" class="mt-3" style="display: none;">
                            <label class="form-label">Transaction Reference / M-Pesa Code</label>
                            <input type="text" name="transaction_ref" class="become-seller-input" placeholder="Enter transaction reference number">
                        </div>
                        
                        <button type="submit" name="make_payment" class="btn-pay-seller mt-3" id="payBtn" disabled>
                            Select Payment Method
                        </button>
                    </form>
                </div>
                
            <?php else: ?>
                <!-- Seller Application Form -->
                <div class="become-seller-card">
                    <h2 class="become-seller-title"><i class="fa-solid fa-store"></i> Become a Seller</h2>
                    <p class="become-seller-subtitle">Fill in your details and choose a subscription plan</p>
                    
                    <form method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="become-seller-label"><i class="fa-solid fa-shop"></i> Shop Name <span class="required-star">*</span></label>
                                <input type="text" name="shop_name" class="become-seller-input" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="become-seller-label"><i class="fa-solid fa-phone"></i> Phone Number <span class="required-star">*</span></label>
                                <input type="text" name="phone" class="become-seller-input" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="become-seller-label"><i class="fa-solid fa-id-card"></i> Business ID <span class="required-star">*</span></label>
                                <input type="text" name="business_id" class="become-seller-input" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="become-seller-label"><i class="fa-solid fa-image"></i> Upload ID Document</label>
                                <input type="file" name="id_image" class="become-seller-input">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="become-seller-label"><i class="fa-solid fa-location-dot"></i> Location</label>
                                <input type="text" name="location" class="become-seller-input">
                            </div>
                            <div class="col-12 mb-3">
                                <label class="become-seller-label"><i class="fa-solid fa-align-left"></i> Shop Description</label>
                                <textarea name="description" class="become-seller-input" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <h4 class="mb-3">Select Subscription Plan</h4>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="plan-card-seller" onclick="selectPlanCard(this, 'Basic', 999)">
                                    <div class="plan-name-seller">Basic Plan</div>
                                    <div class="plan-price-seller">KSH 999 <small>/month</small></div>
                                    <ul class="plan-features-seller">
                                        <li><i class="fa-solid fa-check"></i> Up to 50 products</li>
                                        <li><i class="fa-solid fa-check"></i> Basic analytics</li>
                                        <li><i class="fa-solid fa-check"></i> Email support</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="plan-card-seller" onclick="selectPlanCard(this, 'Professional', 1999)">
                                    <div class="plan-name-seller">Professional</div>
                                    <div class="plan-price-seller">KSH 1,999 <small>/month</small></div>
                                    <ul class="plan-features-seller">
                                        <li><i class="fa-solid fa-check"></i> Up to 200 products</li>
                                        <li><i class="fa-solid fa-check"></i> Advanced analytics</li>
                                        <li><i class="fa-solid fa-check"></i> Priority support</li>
                                        <li><i class="fa-solid fa-check"></i> Featured listings</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="plan-card-seller" onclick="selectPlanCard(this, 'Enterprise', 4999)">
                                    <div class="plan-name-seller">Enterprise</div>
                                    <div class="plan-price-seller">KSH 4,999 <small>/month</small></div>
                                    <ul class="plan-features-seller">
                                        <li><i class="fa-solid fa-check"></i> Unlimited products</li>
                                        <li><i class="fa-solid fa-check"></i> Real-time analytics</li>
                                        <li><i class="fa-solid fa-check"></i> 24/7 support</li>
                                        <li><i class="fa-solid fa-check"></i> Priority placement</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="selected_plan" id="selected_plan">
                        <input type="hidden" name="plan_amount" id="plan_amount">
                        
                        <button type="submit" name="submit_application" class="become-seller-btn mt-4" id="submitBtn" disabled>
                            Submit Application & Proceed to Payment
                        </button>
                    </form>
                    
                    <div class="info-box-seller mt-4">
                        <i class="fa-regular fa-circle-question"></i>
                        <div class="info-box-content-seller">
                            <h4>How it works</h4>
                            <p>1. Fill shop details → 2. Select plan → 3. Submit application → 4. Complete payment → 5. Admin approval → 6. Start selling!</p>
                        </div>
                    </div>
                    
                    <div class="benefits-seller">
                        <div class="benefits-title-seller"><i class="fa-solid fa-gem"></i> Seller Benefits</div>
                        <ul class="benefits-list-seller">
                            <li><i class="fa-solid fa-check-circle"></i> Reach thousands of customers</li>
                            <li><i class="fa-solid fa-check-circle"></i> Easy product management</li>
                            <li><i class="fa-solid fa-check-circle"></i> Real-time sales analytics</li>
                            <li><i class="fa-solid fa-check-circle"></i> Secure payment processing</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
let selectedPlanAmount = 0;
let selectedPlanName = '';
let selectedPaymentMethod = null;

function selectPlanCard(card, planName, amount) {
    document.querySelectorAll('.plan-card-seller').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    
    selectedPlanName = planName;
    selectedPlanAmount = amount;
    
    document.getElementById('selected_plan').value = planName;
    document.getElementById('plan_amount').value = amount;
    document.getElementById('submitBtn').disabled = false;
    document.getElementById('submitBtn').innerHTML = 'Submit Application & Pay KSH ' + amount.toLocaleString();
}

function selectPaymentMethod(card, method) {
    document.querySelectorAll('.payment-method-card-seller').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');
    
    selectedPaymentMethod = method;
    
    const radio = card.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
    
    // Show instructions
    document.getElementById('mpesaInstructions').style.display = method === 'M-Pesa' ? 'block' : 'none';
    document.getElementById('bankInstructions').style.display = method === 'Bank Transfer' ? 'block' : 'none';
    document.getElementById('transactionRefDiv').style.display = method === 'M-Pesa' || method === 'Bank Transfer' ? 'block' : 'none';
    
    const payBtn = document.getElementById('payBtn');
    payBtn.disabled = false;
    payBtn.innerHTML = 'Pay KSH <?= number_format($temp_app['amount'] ?? 0) ?> via ' + method;
}

// Phone formatting
const phoneField = document.querySelector('input[name="phone"]');
if (phoneField) {
    phoneField.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 0 && !value.startsWith('0')) value = '0' + value;
        if (value.length > 10) value = value.slice(0, 10);
        e.target.value = value;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>