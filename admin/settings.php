<?php
$page_title = 'Settings';
require_once '../includes/header.php';

if (($user['role'] ?? '') !== 'admin') { flash('Access denied.', 'danger'); redirect('index.php'); }

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This is a placeholder - you can store settings in a database table
    flash('Settings saved successfully.', 'success');
    redirect('admin/settings.php');
}
?>

<style>
    .admin-content-wrapper { display: flex; gap: 25px; }
    .admin-sidebar-col { width: 280px; flex-shrink: 0; }
    .admin-main-col { flex: 1; }
    .settings-card { background: white; border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .settings-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f59e0b; display: inline-block; }
    .form-control, .form-select { border-radius: 12px; padding: 10px 15px; }
    @media (max-width: 992px) { .admin-content-wrapper { flex-direction: column; } .admin-sidebar-col { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col"><?php require_once '../includes/dashboard_sidebar.php'; ?></div>
        <div class="admin-main-col">
            <h2 class="mb-4"><i class="fa-solid fa-gear"></i> System Settings</h2>
            
            <form method="post">
                <div class="settings-card">
                    <h5 class="settings-title"><i class="fa-solid fa-globe"></i> General Settings</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Site Name</label><input type="text" name="site_name" class="form-control" value="<?= SITE_NAME ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Site Email</label><input type="email" name="site_email" class="form-control" value="admin@marketplace.com"></div>
                        <div class="col-12 mb-3"><label class="form-label">Site Description</label><textarea name="site_description" class="form-control" rows="2">Multi-vendor marketplace platform</textarea></div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h5 class="settings-title"><i class="fa-solid fa-credit-card"></i> Payment Settings</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Currency</label><select name="currency" class="form-select"><option selected>KSH</option><option>USD</option><option>EUR</option></select></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Tax Rate (%)</label><input type="number" name="tax_rate" class="form-control" value="16"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Free Shipping Minimum</label><input type="number" name="free_shipping_min" class="form-control" value="5000"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Shipping Cost</label><input type="number" name="shipping_cost" class="form-control" value="250"></div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h5 class="settings-title"><i class="fa-solid fa-envelope"></i> Email Settings</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">SMTP Host</label><input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">SMTP Port</label><input type="text" name="smtp_port" class="form-control" placeholder="587"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">SMTP User</label><input type="text" name="smtp_user" class="form-control"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">SMTP Password</label><input type="password" name="smtp_pass" class="form-control"></div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <h5 class="settings-title"><i class="fa-solid fa-shield"></i> Commission Settings</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Admin Commission (%)</label><input type="number" name="commission" class="form-control" value="10"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Seller Payout Days</label><input type="number" name="payout_days" class="form-control" value="14"></div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Save All Settings</button>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>