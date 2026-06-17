<?php
$page_title = 'Make Payment';
require_once 'includes/header.php';
require_login();

// Check if user has selected a plan
if (!isset($_SESSION['selected_plan'])) {
    flash('Please select a subscription plan first.', 'warning');
    redirect('become_seller.php');
}

$selected_plan = $_SESSION['selected_plan'];
$seller_id = $selected_plan['seller_id'];
$plan_name = $selected_plan['plan_name'];
$amount = $selected_plan['amount'];

// Check if seller is verified
$seller_check = $mysqli->query("SELECT status FROM sellers WHERE id = $seller_id");
$seller = $seller_check->fetch_assoc();

if (!$seller || $seller['status'] !== 'verified') {
    flash('Your seller account is not verified yet.', 'danger');
    unset($_SESSION['selected_plan']);
    redirect('become_seller.php');
}

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = sanitize($_POST['payment_method']);
    $transaction_reference = sanitize($_POST['transaction_reference'] ?? '');
    
    if (empty($payment_method)) {
        flash('Please select a payment method.', 'danger');
    } else {
        // Create a pending order for the subscription
        $order_number = 'SUB-' . strtoupper(uniqid()) . '-' . date('YmdHis');
        $start_date = date('Y-m-d');
        $expiry_date = date('Y-m-d', strtotime('+30 days'));
        
        // Insert subscription
        $sub_sql = "INSERT INTO subscriptions (seller_id, plan_name, amount, currency, status, starts_at, expires_at, created_at) 
                    VALUES (?, ?, ?, 'KSH', 'pending', ?, ?, NOW())";
        $sub_stmt = $mysqli->prepare($sub_sql);
        $sub_stmt->bind_param('isiss', $seller_id, $plan_name, $amount, $start_date, $expiry_date);
        $sub_stmt->execute();
        $subscription_id = $mysqli->insert_id;
        
        // Create order for payment
        $order_sql = "INSERT INTO orders (user_id, seller_id, order_number, total_amount, payment_method, shipping_address, status, created_at) 
                      VALUES (?, ?, ?, ?, ?, 'Subscription Payment', 'pending', NOW())";
        $order_stmt = $mysqli->prepare($order_sql);
        $order_stmt->bind_param('iisds', $_SESSION['user_id'], $seller_id, $order_number, $amount, $payment_method);
        $order_stmt->execute();
        $order_id = $mysqli->insert_id;
        
        // Create payment record
        $payment_sql = "INSERT INTO payments (order_id, amount, method, status, transaction_reference, created_at) 
                        VALUES (?, ?, ?, 'pending', ?, NOW())";
        $payment_stmt = $mysqli->prepare($payment_sql);
        $payment_stmt->bind_param('idss', $order_id, $amount, $payment_method, $transaction_reference);
        $payment_stmt->execute();
        
        // Clear session
        unset($_SESSION['selected_plan']);
        
        flash('Payment submitted successfully! Our admin will verify your payment within 24 hours.', 'success');
        redirect('payment_status.php');
    }
}
?>

<style>
    .payment-container {
        max-width: 600px;
        margin: 0 auto;
    }
    .payment-card {
        background: white;
        border-radius: 24px;
        padding: 35px;
        box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
    }
    .payment-method {
        border: 2px solid #e5e7eb;
        border-radius: 16px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .payment-method:hover, .payment-method.selected {
        border-color: #2563eb;
        background: #eff6ff;
    }
    .payment-method input[type="radio"] {
        margin-right: 10px;
    }
    .info-text {
        background: #fef3c7;
        border-radius: 12px;
        padding: 15px;
        margin: 20px 0;
    }
</style>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="payment-container">
                <div class="payment-card">
                    <h2 class="text-center mb-4"><i class="fa-solid fa-credit-card"></i> Complete Payment</h2>
                    
                    <div class="info-text">
                        <i class="fa-solid fa-store"></i>
                        <strong>Plan:</strong> <?= htmlspecialchars($plan_name) ?> <br>
                        <strong>Amount:</strong> KSH <?= number_format($amount) ?> <br>
                        <strong>Subscription Period:</strong> 30 days
                    </div>
                    
                    <form method="post">
                        <h5 class="mb-3">Select Payment Method</h5>
                        
                        <div class="payment-method" onclick="selectPayment('M-Pesa')">
                            <label>
                                <input type="radio" name="payment_method" value="M-Pesa" required>
                                <i class="fa-solid fa-mobile-alt"></i> M-Pesa
                            </label>
                            <p class="text-muted small mt-2 mb-0">Pay using M-Pesa - You'll receive a prompt on your phone</p>
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment('Card')">
                            <label>
                                <input type="radio" name="payment_method" value="Card" required>
                                <i class="fa-regular fa-credit-card"></i> Credit/Debit Card
                            </label>
                            <p class="text-muted small mt-2 mb-0">Visa, Mastercard, American Express</p>
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment('Bank Transfer')">
                            <label>
                                <input type="radio" name="payment_method" value="Bank Transfer" required>
                                <i class="fa-solid fa-building-columns"></i> Bank Transfer
                            </label>
                            <p class="text-muted small mt-2 mb-0">Direct bank transfer. Use order number as reference.</p>
                        </div>
                        
                        <div class="payment-method" onclick="selectPayment('PayPal')">
                            <label>
                                <input type="radio" name="payment_method" value="PayPal" required>
                                <i class="fa-brands fa-paypal"></i> PayPal
                            </label>
                            <p class="text-muted small mt-2 mb-0">Pay securely with your PayPal account</p>
                        </div>
                        
                        <div class="mt-4">
                            <label class="form-label">Transaction Reference (Optional)</label>
                            <input type="text" name="transaction_reference" class="form-control" placeholder="Enter transaction ID or reference">
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <i class="fa-solid fa-clock"></i> <strong>Note:</strong> Your subscription will be activated after admin confirms your payment. This usually takes 24-48 hours.
                        </div>
                        
                        <button type="submit" class="btn-submit mt-4">Pay KSH <?= number_format($amount) ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectPayment(method) {
    document.querySelectorAll('.payment-method').forEach(el => {
        el.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}
</script>

<?php require_once 'includes/footer.php'; ?>