<?php
$page_title = 'Subscription';
require_once '../includes/header.php';
require_role('seller');
$profile = get_user_by_id($mysqli, $_SESSION['user_id']);
$seller = $mysqli->query('SELECT * FROM sellers WHERE user_id = ' . intval($_SESSION['user_id']))->fetch_assoc();
$plans = [
    ['name' => 'Starter', 'price' => 1999, 'duration' => '30 days'],
    ['name' => 'Business', 'price' => 3999, 'duration' => '30 days'],
    ['name' => 'Premium', 'price' => 6999, 'duration' => '30 days'],
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('seller/subscription.php');
    }
    $plan = sanitize($_POST['plan'] ?? 'Starter');
    $amount = 1999;
    foreach ($plans as $entry) {
        if ($entry['name'] === $plan) {
            $amount = $entry['price'];
        }
    }
    $expires = date('Y-m-d', strtotime('+30 days'));
    $stmt = $mysqli->prepare('INSERT INTO subscriptions (seller_id, plan_name, amount, starts_at, expires_at, created_at) VALUES (?, ?, ?, CURDATE(), ?, NOW())');
    $stmt->bind_param('isds', $seller['id'], $plan, $amount, $expires);
    $stmt->execute();
    $update = $mysqli->prepare('UPDATE sellers SET subscription_status = "active", subscription_expires = ? WHERE id = ?');
    $update->bind_param('si', $expires, $seller['id']);
    $update->execute();
    flash('Subscription purchased successfully.', 'success');
    redirect('seller/subscription.php');
}
?>
<div class="row">
    <div class="col-lg-8">
        <div class="bg-white rounded shadow-sm p-4">
            <h2>Seller Subscription</h2>
            <p class="text-muted">Choose a monthly plan to keep your shop active.</p>
            <?php if ($seller): ?>
                <p><strong>Current status:</strong> <?= sanitize($seller['subscription_status']) ?><?php if ($seller['subscription_expires']): ?> until <?= sanitize($seller['subscription_expires']) ?><?php endif; ?></p>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="row gy-3">
                    <?php foreach ($plans as $plan): ?>
                        <div class="col-md-4">
                            <div class="card border-primary h-100">
                                <div class="card-body">
                                    <h5><?= $plan['name'] ?></h5>
                                    <p class="mb-1">KSH <?= number_format($plan['price']) ?></p>
                                    <p class="text-muted small"><?= $plan['duration'] ?></p>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="plan" value="<?= $plan['name'] ?>" <?= $plan['name'] === 'Starter' ? 'checked' : '' ?>>
                                        <label class="form-check-label">Select</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-primary mt-4">Purchase subscription</button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php';
