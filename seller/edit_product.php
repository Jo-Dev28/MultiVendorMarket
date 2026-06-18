<?php
$page_title = 'Edit Product';
require_once '../includes/header.php';
require_role('seller');

$product_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
if (!$seller_stmt) {
    flash('Database error: ' . $mysqli->error, 'danger');
    redirect('index.php');
}
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

// Get product
$product_sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.id = ? AND p.seller_id = ?";
$product_stmt = $mysqli->prepare($product_sql);
if (!$product_stmt) {
    flash('Database error: ' . $mysqli->error, 'danger');
    redirect('seller/products.php');
}
$product_stmt->bind_param('ii', $product_id, $seller['id']);
$product_stmt->execute();
$product = $product_stmt->get_result()->fetch_assoc();

if (!$product) {
    flash('Product not found.', 'danger');
    redirect('seller/products.php');
}

// Get categories
$categories = $mysqli->query("SELECT id, name FROM categories ORDER BY name");
if (!$categories) {
    $categories = [];
}

// Get product images
$images_result = $mysqli->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY id ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $brand = sanitize($_POST['brand'] ?? '');
    $short_description = sanitize($_POST['short_description'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    
    // Discount fields
    $enable_discount = isset($_POST['enable_discount']) ? 1 : 0;
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $discount_days = intval($_POST['discount_days'] ?? 7);
    
    if (empty($name) || $category_id == 0 || $price <= 0) {
        flash('Please fill all required fields.', 'danger');
    } else {
        // Calculate discounted price
        $discounted_price = null;
        $discount_start_date = null;
        $discount_end_date = null;
        $is_on_sale = 0;
        
        if ($enable_discount && $discount_percent > 0 && $discount_percent <= 99) {
            $is_on_sale = 1;
            $discounted_price = $price * (1 - $discount_percent / 100);
            $discount_start_date = date('Y-m-d H:i:s');
            $discount_end_date = date('Y-m-d H:i:s', strtotime("+$discount_days days"));
        }
        
        $sql = "UPDATE products SET 
                name=?, category_id=?, price=?, stock=?, brand=?, 
                short_description=?, description=?,
                is_on_sale=?, discount_percent=?, discounted_price=?,
                discount_start_date=?, discount_end_date=?
                WHERE id=? AND seller_id=?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            flash('Database error: ' . $mysqli->error, 'danger');
        } else {
            $stmt->bind_param('sidsissiidssii', 
                $name, $category_id, $price, $stock, $brand, 
                $short_description, $description,
                $is_on_sale, $discount_percent, $discounted_price,
                $discount_start_date, $discount_end_date,
                $product_id, $seller['id']
            );
            
            if ($stmt->execute()) {
                // Handle new images
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['images']['error'][$key] == 0) {
                            $file = [
                                'name' => $_FILES['images']['name'][$key],
                                'type' => $_FILES['images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['images']['error'][$key],
                                'size' => $_FILES['images']['size'][$key]
                            ];
                            $upload = upload_image($file, 'products');
                            if ($upload['success']) {
                                $img_sql = "INSERT INTO product_images (product_id, filename, created_at) VALUES (?, ?, NOW())";
                                $img_stmt = $mysqli->prepare($img_sql);
                                if ($img_stmt) {
                                    $img_stmt->bind_param('is', $product_id, $upload['filename']);
                                    $img_stmt->execute();
                                    $img_stmt->close();
                                }
                            }
                        }
                    }
                }
                
                flash('Product updated successfully.', 'success');
                redirect('seller/edit_product.php?id=' . $product_id);
            } else {
                flash('Failed to update product: ' . $stmt->error, 'danger');
            }
            $stmt->close();
        }
    }
}

// Handle image delete
if (isset($_GET['delete_image'])) {
    $image_id = intval($_GET['delete_image']);
    $img_result = $mysqli->query("SELECT filename FROM product_images WHERE id = $image_id AND product_id = $product_id");
    if ($img_result && $img_result->num_rows > 0) {
        $img_data = $img_result->fetch_assoc();
        $file_path = '../uploads/products/' . $img_data['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $abs_path = UPLOAD_DIR . '/products/' . $img_data['filename'];
        if (file_exists($abs_path)) {
            unlink($abs_path);
        }
    }
    
    $delete_result = $mysqli->query("DELETE FROM product_images WHERE id = $image_id AND product_id = $product_id");
    if ($delete_result) {
        flash('Image deleted successfully.', 'success');
    } else {
        flash('Failed to delete image.', 'danger');
    }
    redirect('seller/edit_product.php?id=' . $product_id);
}

// Check if product has active discount
$has_discount = isset($product['is_on_sale']) && $product['is_on_sale'] == 1 && 
                $product['discount_percent'] > 0 && 
                !empty($product['discount_end_date']) && 
                $product['discount_end_date'] > date('Y-m-d H:i:s');

// Get discount info for display
$discount_percent = $product['discount_percent'] ?? 0;
$discounted_price = $product['discounted_price'] ?? 0;
$discount_end_date = $product['discount_end_date'] ?? null;
?>

<style>
/* ============================================
   EDIT PRODUCT PAGE - MODERN CLEAN DESIGN
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

.form-container {
    max-width: 850px;
    margin: 0 auto;
    background: var(--white);
    border-radius: 16px;
    padding: 35px;
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid var(--light-gray);
}

.form-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.form-header h2 i {
    color: var(--primary);
    margin-right: 10px;
}

.current-status {
    display: inline-block;
    padding: 5px 16px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-approved { background: #d1fae5; color: #059669; }
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
    min-height: 120px;
}

/* Discount Section */
.discount-section {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: var(--radius);
    padding: 20px;
    margin-bottom: 20px;
}

.discount-section .discount-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.discount-section .discount-header .toggle-label {
    font-weight: 600;
    color: #1f2937;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.discount-section .discount-header .toggle-label i {
    color: var(--danger);
}

.discount-active-badge {
    background: #d1fae5;
    color: #059669;
    padding: 2px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
}

.discount-inactive-badge {
    background: #fef3c7;
    color: #d97706;
    padding: 2px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
}

/* Toggle Switch */
.switch {
    position: relative;
    display: inline-block;
    width: 48px;
    height: 26px;
    flex-shrink: 0;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #ccc;
    transition: .3s;
    border-radius: 26px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 4px;
    bottom: 4px;
    background: white;
    transition: .3s;
    border-radius: 50%;
}

.switch input:checked + .slider {
    background: var(--danger);
}

.switch input:checked + .slider:before {
    transform: translateX(22px);
}

.discount-fields {
    display: none;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding-top: 15px;
    border-top: 1px solid #fecaca;
}

.discount-fields.active {
    display: grid;
}

.discount-fields .discount-info {
    grid-column: 1 / -1;
    background: #fff5f5;
    padding: 10px 14px;
    border-radius: var(--radius);
    font-size: 0.85rem;
    color: #991b1b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.discount-fields .discount-info i {
    font-size: 1.1rem;
}

.discount-preview {
    grid-column: 1 / -1;
    background: #d1fae5;
    padding: 10px 14px;
    border-radius: var(--radius);
    font-size: 0.9rem;
    color: #065f46;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}

.discount-preview .preview-price {
    font-weight: 700;
    font-size: 1.1rem;
    color: #ef4444;
}

.discount-preview .preview-original {
    text-decoration: line-through;
    color: #6b7280;
}

/* Image Gallery */
.image-section-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.85rem;
    margin-bottom: 10px;
    display: block;
}

.image-gallery {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 15px;
    padding: 15px;
    background: #f8fafc;
    border-radius: var(--radius);
    border: 1px dashed var(--border);
    min-height: 100px;
}

.image-item {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: var(--radius);
    overflow: hidden;
    border: 2px solid var(--border);
    background: var(--white);
    transition: all 0.3s ease;
}

.image-item:hover {
    border-color: var(--primary);
    transform: scale(1.02);
}

.image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.image-item .delete-image {
    position: absolute;
    top: -6px;
    right: -6px;
    background: var(--danger);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.3s ease;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.image-item .delete-image:hover {
    background: #dc2626;
    transform: scale(1.1);
}

.no-image-placeholder {
    width: 100px;
    height: 100px;
    background: var(--light-gray);
    border-radius: var(--radius);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    border: 1px dashed var(--border);
}

.no-image-placeholder i {
    font-size: 2rem;
    margin-bottom: 4px;
}

.no-image-placeholder span {
    font-size: 0.7rem;
}

/* Image Preview */
.image-preview {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.preview-img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: var(--radius);
    border: 1px solid var(--border);
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

/* File Input */
.custom-file-input {
    padding: 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    width: 100%;
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

/* Two Column Layout */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.text-muted {
    color: #6b7280;
    font-size: 0.8rem;
    margin-top: 6px;
    display: block;
}

/* Responsive */
@media (max-width: 992px) {
    .form-container { padding: 20px; }
    .form-row { grid-template-columns: 1fr; gap: 0; }
    .form-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    .form-header h2 { font-size: 1.2rem; }
    .image-gallery { justify-content: center; }
    .image-item { width: 80px; height: 80px; }
    .no-image-placeholder { width: 80px; height: 80px; }
    .discount-fields { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .form-container { padding: 15px; }
    .image-item { width: 70px; height: 70px; }
    .no-image-placeholder { width: 70px; height: 70px; }
    .no-image-placeholder i { font-size: 1.5rem; }
}
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="form-container">
                <!-- Header -->
                <div class="form-header">
                    <h2><i class="fa-solid fa-pen-to-square"></i> Edit Product</h2>
                    <span class="current-status status-<?= $product['status'] ?>">
                        <i class="fa-regular fa-circle"></i> <?= ucfirst($product['status']) ?>
                    </span>
                </div>
                
                <!-- Current Images -->
                <div>
                    <label class="form-label">
                        <i class="fa-regular fa-image"></i> Current Images
                    </label>
                    <div class="image-gallery">
                        <?php 
                        $images_result = $mysqli->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY id ASC");
                        if ($images_result && $images_result->num_rows > 0): 
                            while($img = $images_result->fetch_assoc()): 
                                $found_path = '../uploads/products/' . $img['filename'];
                                if (!file_exists($found_path) && !file_exists(str_replace('../', '', $found_path))) {
                                    $found_path = null;
                                }
                        ?>
                        <div class="image-item">
                            <img src="<?= $found_path ?: 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50\' y=\'50\' font-family=\'Arial\' font-size=\'12\' fill=\'%239ca3af\' text-anchor=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E' ?>" 
                                 alt="Product image"
                                 loading="lazy">
                            <a href="?delete_image=<?= $img['id'] ?>&id=<?= $product_id ?>" class="delete-image" onclick="return confirm('Delete this image?')">×</a>
                        </div>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fa-solid fa-image"></i>
                            <span>No images</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Form -->
                <form method="post" enctype="multipart/form-data" id="editProductForm">
                    <div class="mb-3">
                        <label class="form-label">
                            Product Name <span class="required">*</span>
                        </label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required placeholder="Enter product name">
                    </div>
                    
                    <div class="form-row">
                        <div class="mb-3">
                            <label class="form-label">
                                Category <span class="required">*</span>
                            </label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php if($categories): ?>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($product['brand']) ?>" placeholder="Enter brand name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="mb-3">
                            <label class="form-label">
                                Price (KSH) <span class="required">*</span>
                            </label>
                            <input type="number" name="price" id="productPrice" class="form-control" step="0.01" value="<?= $product['price'] ?>" required placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Stock Quantity <span class="required">*</span>
                            </label>
                            <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required placeholder="0">
                        </div>
                    </div>
                    
                    <!-- Discount Section -->
                    <div class="discount-section">
                        <div class="discount-header">
                            <label class="switch">
                                <input type="checkbox" name="enable_discount" id="enableDiscount" <?= $has_discount ? 'checked' : '' ?>>
                                <span class="slider"></span>
                            </label>
                            <label class="toggle-label" for="enableDiscount">
                                <i class="fa-solid fa-tag"></i> Enable Discount / Flash Sale
                            </label>
                            <?php if($has_discount): ?>
                                <span class="discount-active-badge">
                                    <i class="fa-regular fa-circle-check"></i> Active
                                </span>
                            <?php else: ?>
                                <span class="discount-inactive-badge">
                                    <i class="fa-regular fa-circle"></i> Inactive
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="discount-fields <?= $has_discount ? 'active' : '' ?>" id="discountFields">
                            <div class="discount-info">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Set a discount percentage and duration. The discounted price will be calculated automatically.</span>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label" style="font-size:0.8rem;">Discount Percentage (%)</label>
                                <input type="number" name="discount_percent" id="discountPercent" class="form-control" 
                                       min="1" max="99" 
                                       value="<?= $has_discount ? $discount_percent : '' ?>" 
                                       placeholder="e.g. 30">
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label" style="font-size:0.8rem;">Duration (Days)</label>
                                <input type="number" name="discount_days" id="discountDays" class="form-control" 
                                       min="1" max="30" 
                                       value="<?php 
                                           if($has_discount && !empty($discount_end_date)) {
                                               $now = new DateTime();
                                               $end = new DateTime($discount_end_date);
                                               $diff = $now->diff($end);
                                               echo $diff->days > 0 ? $diff->days : 1;
                                           } else {
                                               echo '7';
                                           }
                                       ?>" 
                                       placeholder="7">
                            </div>
                            
                            <!-- Live Preview -->
                            <div class="discount-preview" id="discountPreview" style="<?= $has_discount && $discount_percent > 0 ? 'display:flex;' : 'display:none;' ?>">
                                <span>
                                    <i class="fa-regular fa-eye"></i> Preview:
                                    <span class="preview-original">KSH <?= number_format($product['price']) ?></span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                    <span class="preview-price">KSH <?= number_format($discounted_price > 0 ? $discounted_price : $product['price']) ?></span>
                                </span>
                                <span style="font-size:0.8rem; color:#6b7280;">
                                    Save <?= $has_discount ? $discount_percent : 0 ?>%
                                </span>
                            </div>
                            
                            <?php if($has_discount && !empty($discount_end_date)): ?>
                            <div style="grid-column:1/-1; background:#dbeafe; padding:8px 14px; border-radius:var(--radius); font-size:0.85rem; color:#1e40af; display:flex; align-items:center; gap:8px;">
                                <i class="fa-regular fa-clock"></i>
                                <span>Discount ends: <strong><?= date('F d, Y h:i A', strtotime($discount_end_date)) ?></strong></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Short Description</label>
                        <input type="text" name="short_description" class="form-control" value="<?= htmlspecialchars($product['short_description']) ?>" placeholder="Brief product description">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Description</label>
                        <textarea name="description" class="form-control" rows="6" placeholder="Detailed product description"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Add More Images
                        </label>
                        <input type="file" name="images[]" class="custom-file-input" multiple accept="image/*" onchange="previewImages(this)">
                        <div class="image-preview" id="imagePreview"></div>
                        <small class="text-muted">
                            <i class="fa-regular fa-circle-info"></i> You can select multiple images at once. Max 5MB per image.
                        </small>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-regular fa-floppy-disk"></i> Update Product
                    </button>
                    <a href="products.php" class="btn-secondary">
                        <i class="fa-solid fa-arrow-left"></i> Back to Products
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview
function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    if (input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-img';
                preview.appendChild(img);
            }
            reader.readAsDataURL(file);
        });
    }
}

// Toggle discount fields
document.getElementById('enableDiscount')?.addEventListener('change', function() {
    const fields = document.getElementById('discountFields');
    const preview = document.getElementById('discountPreview');
    const badge = this.closest('.discount-section').querySelector('.discount-active-badge, .discount-inactive-badge');
    
    if (this.checked) {
        fields.classList.add('active');
        if (badge) {
            badge.className = 'discount-active-badge';
            badge.innerHTML = '<i class="fa-regular fa-circle-check"></i> Active';
        }
        updateDiscountPreview();
    } else {
        fields.classList.remove('active');
        if (badge) {
            badge.className = 'discount-inactive-badge';
            badge.innerHTML = '<i class="fa-regular fa-circle"></i> Inactive';
        }
        preview.style.display = 'none';
    }
});

// Update discount preview
function updateDiscountPreview() {
    const price = parseFloat(document.getElementById('productPrice').value) || 0;
    const percent = parseFloat(document.getElementById('discountPercent').value) || 0;
    const preview = document.getElementById('discountPreview');
    const previewPrice = preview.querySelector('.preview-price');
    const previewOriginal = preview.querySelector('.preview-original');
    const saveSpan = preview.querySelector('span:last-child');
    
    if (percent > 0 && percent <= 99 && price > 0) {
        const discounted = price * (1 - percent / 100);
        previewPrice.textContent = 'KSH ' + discounted.toFixed(2);
        previewOriginal.textContent = 'KSH ' + price.toFixed(2);
        if (saveSpan) {
            saveSpan.textContent = 'Save ' + percent + '%';
        }
        preview.style.display = 'flex';
    } else {
        preview.style.display = 'none';
    }
}

// Real-time preview updates
document.getElementById('productPrice')?.addEventListener('input', updateDiscountPreview);
document.getElementById('discountPercent')?.addEventListener('input', updateDiscountPreview);

// Initial preview update
setTimeout(updateDiscountPreview, 100);

// Validate discount percentage
document.getElementById('discountPercent')?.addEventListener('input', function() {
    if (this.value > 99) {
        this.value = 99;
    }
    if (this.value < 0) {
        this.value = 0;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>