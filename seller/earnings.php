<?php
$page_title = 'Earnings';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

// Get earnings summary
$total_earnings = $mysqli->query("SELECT SUM(total_amount) as total FROM orders WHERE seller_id = {$seller['id']} AND status = 'delivered'")->fetch_assoc()['total'] ?? 0;
$pending_earnings = $mysqli->query("SELECT SUM(total_amount) as total FROM orders WHERE seller_id = {$seller['id']} AND status != 'delivered' AND status != 'cancelled'")->fetch_assoc()['total'] ?? 0;
$total_orders = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = {$seller['id']}")->fetch_assoc()['count'];
$completed_orders = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = {$seller['id']} AND status = 'delivered'")->fetch_assoc()['count'];

// Get monthly earnings
$monthly_earnings = $mysqli->query("SELECT MONTH(created_at) as month, SUM(total_amount) as total 
                                    FROM orders 
                                    WHERE seller_id = {$seller['id']} AND status = 'delivered' AND YEAR(created_at) = YEAR(NOW())
                                    GROUP BY MONTH(created_at)");
$monthly_data = array_fill(0, 12, 0);
while($row = $monthly_earnings->fetch_assoc()) {
    $monthly_data[$row['month'] - 1] = $row['total'];
}
?>

<style>
    .seller-wrapper { display: flex; gap: 25px; }
    .seller-sidebar { width: 280px; flex-shrink: 0; }
    .seller-content { flex: 1; }
    .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
    .stat-value { font-size: 1.8rem; font-weight: 700; }
    .stat-label { font-size: 0.8rem; color: #6b7280; }
    @media (max-width: 992px) { .seller-wrapper { flex-direction: column; } .seller-sidebar { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="seller-wrapper">
        <div class="seller-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="seller-content">
            <h2 class="mb-4"><i class="fa-solid fa-chart-line"></i> My Earnings</h2>
            
            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="stat-card"><div class="stat-value">KSH <?= number_format($total_earnings) ?></div><div class="stat-label">Total Earnings</div></div></div>
                <div class="col-md-4"><div class="stat-card"><div class="stat-value">KSH <?= number_format($pending_earnings) ?></div><div class="stat-label">Pending Earnings</div></div></div>
                <div class="col-md-4"><div class="stat-card"><div class="stat-value"><?= $completed_orders ?> / <?= $total_orders ?></div><div class="stat-label">Completed Orders</div></div></div>
            </div>
            
            <div class="card">
                <div class="card-header bg-white"><strong>Monthly Earnings (<?= date('Y') ?>)</strong></div>
                <div class="card-body">
                    <canvas id="earningsChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('earningsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'Earnings (KSH)',
            data: <?= json_encode($monthly_data) ?>,
            backgroundColor: '#2563eb',
            borderRadius: 8
        }]
    },
    options: { responsive: true, maintainAspectRatio: true }
});
</script>

<?php require_once '../includes/footer.php'; ?>