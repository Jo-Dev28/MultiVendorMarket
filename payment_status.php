<?php
$page_title = 'Payment Status';
require_once 'includes/header.php';
require_login();

// Get latest payment for the user
$sql = "SELECT p.*, o.order_number, sub.plan_name, sub.amount, sub.status as sub_status
        FROM payments p
        JOIN orders o ON o.id = p.order_id
        JOIN subscriptions sub ON sub.seller_id = o.seller_id
        WHERE o.user_id = ?
        ORDER BY p.created_at DESC LIMIT 1";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
?>

<style>
    .status-container {
        max-width: 600px;
        margin: 0 auto;
    }
    .status-card {
        background: white;
        border-radius: 24px;
        padding: 40px;
        text-align: center;
        box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
    }
    .status-icon {
        width: 100px;
        height: 100px;
        background: #fef3c7;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
    }
    .status-icon i {
        font-size: 3rem;
        color: #f59e0b;
    }
    .status-title {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 15px;
    }
    .home-btn {
        display: inline-block;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 12px 30px;
        border-radius: 12px;
        text-decoration: none;
        margin-top: 20px;
    }
</style>

<div class="container mb-5">
    <div class="status-container">
        <div class="status-card">
            <div class="status-icon">
                <i class="fa-regular fa-clock"></i>
            </div>
            <h2 class="status-title">Payment Under Review</h2>
            <p>Your payment has been submitted and is awaiting admin confirmation.</p>
            
            <?php if ($payment): ?>
                <div class="text-start bg-light p-3 rounded mt-3">
                    <p><strong>Order Number:</strong> <?= $payment['order_number'] ?></p>
                    <p><strong>Plan:</strong> <?= $payment['plan_name'] ?></p>
                    <p><strong>Amount:</strong> KSH <?= number_format($payment['amount']) ?></p>
                    <p><strong>Status:</strong> <span class="badge bg-warning">Pending</span></p>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info mt-3">
                <i class="fa-solid fa-circle-info"></i> Once your payment is confirmed, your subscription will be activated and you can start selling.
            </div>
            
            <a href="profile.php" class="home-btn">Go to Profile</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>