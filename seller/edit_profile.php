<?php
$page_title = 'Edit Shop Profile';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT s.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
               FROM sellers s 
               JOIN users u ON u.id = s.user_id 
               WHERE s.user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();
$seller = $seller_result->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shop_name = sanitize($_POST['shop_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $business_id = sanitize($_POST['business_id'] ?? '');
    
    if (empty($shop_name) || empty($phone)) {
        flash('Shop name and phone are required.', 'danger');
    } else {
        // Update seller info
        $update_sql = "UPDATE sellers SET 
                       shop_name = ?, 
                       phone = ?, 
                       location = ?, 
                       description = ?, 
                       business_id = ? 
                       WHERE user_id = ?";
        $update_stmt = $mysqli->prepare($update_sql);
        $update_stmt->bind_param('sssssi', $shop_name, $phone, $location, $description, $business_id, $user_id);
        
        if ($update_stmt->execute()) {
            // Update user phone
            $user_update = "UPDATE users SET phone = ? WHERE id = ?";
            $user_stmt = $mysqli->prepare($user_update);
            $user_stmt->bind_param('si', $phone, $user_id);
            $user_stmt->execute();
            
            // Handle logo upload
            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] == 0) {
                $file = [
                    'name' => $_FILES['shop_logo']['name'],
                    'type' => $_FILES['shop_logo']['type'],
                    'tmp_name' => $_FILES['shop_logo']['tmp_name'],
                    'error' => $_FILES['shop_logo']['error'],
                    'size' => $_FILES['shop_logo']['size']
                ];
                $upload = upload_image($file, 'sellers');
                if ($upload['success']) {
                    // Delete old logo if exists
                    if (!empty($seller['shop_logo']) && file_exists('../uploads/sellers/' . $seller['shop_logo'])) {
                        unlink('../uploads/sellers/' . $seller['shop_logo']);
                    }
                    $logo_update = "UPDATE sellers SET shop_logo = ? WHERE user_id = ?";
                    $logo_stmt = $mysqli->prepare($logo_update);
                    $logo_stmt->bind_param('si', $upload['filename'], $user_id);
                    $logo_stmt->execute();
                }
            }
            
            flash('Shop profile updated successfully!', 'success');
            redirect('seller/edit_profile.php');
        } else {
            flash('Failed to update shop profile.', 'danger');
        }
    }
}
?>

<style>
/* ============================================
   EDIT SHOP PROFILE - MODERN DESIGN
============================================ */
:root {
    --primary: #2563eb;
    --primary-dark: #1d4ed8;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --gray: #6b7280;
    --light-gray: #f3f4f6;
    --white: #ffffff;
    --border: #e5e7eb;
    --shadow: 0 1px 3px rgba(0,0,0,0.08);
    --shadow-hover: 0 8px 25px rgba(0,0,0,0.12);
    --radius: 12px;
}

body {
    background: #f5f7fb;
}

.profile-container {
    max-width: 850px;
    margin: 0 auto;
    background: var(--white);
    border-radius: 16px;
    padding: 35px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.profile-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-gray);
}

.profile-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.profile-header h2 i {
    color: var(--primary);
    margin-right: 10px;
}

.status-badge {
    display: inline-block;
    padding: 5px 16px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-verified { background: #d1fae5; color: #059669; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-rejected { background: #fee2e2; color: #dc2626; }

.form-label {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
    display: block;
    font-size: 0.85rem;
}

.form-label .required {
    color: var(--danger);
    margin-left: 2px;
}

.form-control, .form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: var(--white);
    color: #1f2937;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.form-control::placeholder {
    color: #9ca3af;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

/* Logo Upload */
.logo-upload-section {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
    padding: 20px;
    background: var(--light-gray);
    border-radius: var(--radius);
    margin-bottom: 20px;
}

.logo-preview {
    width: 120px;
    height: 120px;
    border-radius: var(--radius);
    overflow: hidden;
    border: 2px solid var(--border);
    background: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.logo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.logo-preview .no-logo {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 2rem;
}

.logo-preview .no-logo span {
    font-size: 0.7rem;
    margin-top: 4px;
}

.logo-upload-info {
    flex: 1;
}

.logo-upload-info h5 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.logo-upload-info p {
    font-size: 0.8rem;
    color: var(--gray);
    margin: 0 0 10px 0;
}

.custom-file-input {
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    width: 100%;
    max-width: 300px;
}

.custom-file-input::file-selector-button {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    background: var(--primary);
    color: white;
    cursor: pointer;
    margin-right: 10px;
    transition: all 0.3s ease;
}

.custom-file-input::file-selector-button:hover {
    background: var(--primary-dark);
}

/* Buttons */
.btn-submit {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: var(--white);
    padding: 14px;
    border: none;
    border-radius: var(--radius);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    width: 100%;
    transition: all 0.3s ease;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

.btn-submit i {
    margin-right: 8px;
}

.btn-secondary {
    background: var(--light-gray);
    color: #374151;
    padding: 12px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    display: block;
    margin-top: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn-secondary:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.btn-secondary i {
    margin-right: 6px;
}

.text-muted {
    color: var(--gray);
    font-size: 0.8rem;
    margin-top: 4px;
    display: block;
}

/* Responsive */
@media (max-width: 992px) {
    .profile-container { padding: 20px; }
    .form-row { grid-template-columns: 1fr; gap: 0; }
    .profile-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .profile-header h2 { font-size: 1.2rem; }
    .logo-upload-section { flex-direction: column; text-align: center; }
    .logo-preview { width: 100px; height: 100px; }
    .custom-file-input { max-width: 100%; }
}

@media (max-width: 480px) {
    .profile-container { padding: 15px; }
    .logo-preview { width: 80px; height: 80px; }
}
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="profile-container">
                <!-- Header -->
                <div class="profile-header">
                    <h2><i class="fa-regular fa-store"></i> Edit Shop Profile</h2>
                    <span class="status-badge status-<?= $seller['status'] ?>">
                        <i class="fa-regular fa-circle"></i> <?= ucfirst($seller['status']) ?>
                    </span>
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <!-- Logo Upload -->
                    <div class="logo-upload-section">
                        <div class="logo-preview">
                            <?php if (!empty($seller['shop_logo']) && file_exists('../uploads/sellers/' . $seller['shop_logo'])): ?>
                                <img src="../uploads/sellers/<?= $seller['shop_logo'] ?>" alt="Shop Logo">
                            <?php else: ?>
                                <div class="no-logo">
                                    <i class="fa-solid fa-store"></i>
                                    <span>No Logo</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="logo-upload-info">
                            <h5><i class="fa-regular fa-image"></i> Shop Logo</h5>
                            <p>Upload a logo for your shop. Recommended size: 200x200px</p>
                            <input type="file" name="shop_logo" class="custom-file-input" accept="image/*">
                            <small class="text-muted">Max 2MB. JPG, PNG, GIF allowed.</small>
                        </div>
                    </div>
                    
                    <!-- Shop Name -->
                    <div class="mb-3">
                        <label class="form-label">
                            Shop Name <span class="required">*</span>
                        </label>
                        <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($seller['shop_name']) ?>" required placeholder="Enter your shop name">
                    </div>
                    
                    <div class="form-row">
                        <!-- Phone -->
                        <div class="mb-3">
                            <label class="form-label">
                                Phone Number <span class="required">*</span>
                            </label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($seller['phone'] ?? '') ?>" required placeholder="Enter phone number">
                        </div>
                        
                        <!-- Business ID -->
                        <div class="mb-3">
                            <label class="form-label">Business ID</label>
                            <input type="text" name="business_id" class="form-control" value="<?= htmlspecialchars($seller['business_id'] ?? '') ?>" placeholder="Enter business ID">
                            <small class="text-muted">Optional - Your business registration number</small>
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" value="<?= htmlspecialchars($seller['location'] ?? '') ?>" placeholder="Enter your shop location">
                    </div>
                    
                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label">Shop Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Tell customers about your shop..."><?= htmlspecialchars($seller['description'] ?? '') ?></textarea>
                        <small class="text-muted">Describe your shop, products, and what makes you unique.</small>
                    </div>
                    
                    <!-- Shop Info Display -->
                    <div class="mb-3" style="background: var(--light-gray); padding: 15px; border-radius: var(--radius);">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <div style="font-size:0.75rem; color:var(--gray);">Owner Name</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($seller['user_name']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:var(--gray);">Email</div>
                                <div style="font-weight:600;"><?= htmlspecialchars($seller['user_email']) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:var(--gray);">Member Since</div>
                                <div style="font-weight:600;"><?= date('F d, Y', strtotime($seller['created_at'])) ?></div>
                            </div>
                            <div>
                                <div style="font-size:0.75rem; color:var(--gray);">Status</div>
                                <div>
                                    <span class="status-badge status-<?= $seller['status'] ?>" style="font-size:0.65rem;">
                                        <?= ucfirst($seller['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-regular fa-floppy-disk"></i> Update Shop Profile
                    </button>
                    <a href="my_shop.php" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to Shop
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>