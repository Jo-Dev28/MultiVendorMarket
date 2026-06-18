<?php
$page_title = 'Add Product';
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

// Get categories
$categories = $mysqli->query("SELECT id, name FROM categories ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $brand = sanitize($_POST['brand']);
    $short_description = sanitize($_POST['short_description']);
    $description = sanitize($_POST['description']);
    
    // Discount fields
    $enable_discount = isset($_POST['enable_discount']) ? 1 : 0;
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $discount_days = intval($_POST['discount_days'] ?? 7);
    
    // Generate slug
    $slug = strtolower(str_replace(' ', '-', $name)) . '-' . uniqid();
    
    if (empty($name) || $category_id == 0 || $price <= 0) {
        flash('Please fill all required fields.', 'danger');
    } else {
        // Calculate discount values
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
        
        $sql = "INSERT INTO products (
                    seller_id, category_id, name, slug, short_description, description, 
                    price, stock, brand, status, created_at,
                    is_on_sale, discount_percent, discounted_price, discount_start_date, discount_end_date
                ) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iisssdissiidsss', 
            $seller['id'], $category_id, $name, $slug, $short_description, $description, 
            $price, $stock, $brand,
            $is_on_sale, $discount_percent, $discounted_price,
            $discount_start_date, $discount_end_date
        );
        
        if ($stmt->execute()) {
            $product_id = $mysqli->insert_id;
            
            // Handle image upload
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
                            $img_stmt->bind_param('is', $product_id, $upload['filename']);
                            $img_stmt->execute();
                        }
                    }
                }
            }
            
            flash('Product added successfully! Awaiting admin approval.', 'success');
            redirect('seller/products.php');
        } else {
            flash('Failed to add product: ' . $stmt->error, 'danger');
        }
    }
}
?>

<style>
/* ============================================
   ADD PRODUCT PAGE - MODERN CLEAN DESIGN
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
    display: none;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}

.discount-preview.active {
    display: flex;
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
    .discount-fields { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
    .form-container { padding: 15px; }
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
                    <h2><i class="fa-solid fa-plus"></i> Add New Product</h2>
                </div>
                
                <!-- Form -->
                <form method="post" enctype="multipart/form-data" id="addProductForm">
                    <div class="mb-3">
                        <label class="form-label">
                            Product Name <span class="required">*</span>
                        </label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter product name">
                    </div>
                    
                    <div class="form-row">
                        <div class="mb-3">
                            <label class="form-label">
                                Category <span class="required">*</span>
                            </label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" placeholder="Enter brand name">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="mb-3">
                            <label class="form-label">
                                Price (KSH) <span class="required">*</span>
                            </label>
                            <input type="number" name="price" id="productPrice" class="form-control" step="0.01" required placeholder="0.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Stock Quantity <span class="required">*</span>
                            </label>
                            <input type="number" name="stock" class="form-control" required placeholder="0">
                        </div>
                    </div>
                    
                    <!-- Discount Section -->
                    <div class="discount-section">
                        <div class="discount-header">
                            <label class="switch">
                                <input type="checkbox" name="enable_discount" id="enableDiscount">
                                <span class="slider"></span>
                            </label>
                            <label class="toggle-label" for="enableDiscount">
                                <i class="fa-solid fa-tag"></i> Enable Discount / Flash Sale
                            </label>
                        </div>
                        
                        <div class="discount-fields" id="discountFields">
                            <div class="discount-info">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Set a discount percentage and duration. The discounted price will be calculated automatically.</span>
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label" style="font-size:0.8rem;">Discount Percentage (%)</label>
                                <input type="number" name="discount_percent" id="discountPercent" class="form-control" 
                                       min="1" max="99" placeholder="e.g. 30">
                            </div>
                            
                            <div class="mb-2">
                                <label class="form-label" style="font-size:0.8rem;">Duration (Days)</label>
                                <input type="number" name="discount_days" id="discountDays" class="form-control" 
                                       min="1" max="30" value="7" placeholder="7">
                            </div>
                            
                            <!-- Live Preview -->
                            <div class="discount-preview" id="discountPreview">
                                <span>
                                    <i class="fa-regular fa-eye"></i> Preview:
                                    <span class="preview-original">KSH 0.00</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                    <span class="preview-price">KSH 0.00</span>
                                </span>
                                <span style="font-size:0.8rem; color:#6b7280;">
                                    Save 0%
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Short Description</label>
                        <input type="text" name="short_description" class="form-control" placeholder="Brief product description">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Description</label>
                        <textarea name="description" class="form-control" rows="6" placeholder="Detailed product description"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Product Images
                        </label>
                        <input type="file" name="images[]" class="custom-file-input" multiple accept="image/*" onchange="previewImages(this)">
                        <div class="image-preview" id="imagePreview"></div>
                        <small class="text-muted">
                            <i class="fa-regular fa-circle-info"></i> You can select multiple images at once. Max 5MB per image.
                        </small>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fa-regular fa-floppy-disk"></i> Add Product
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
    
    if (this.checked) {
        fields.classList.add('active');
        updateDiscountPreview();
    } else {
        fields.classList.remove('active');
        preview.classList.remove('active');
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
        preview.classList.add('active');
    } else {
        preview.classList.remove('active');
    }
}

// Real-time preview updates
document.getElementById('productPrice')?.addEventListener('input', updateDiscountPreview);
document.getElementById('discountPercent')?.addEventListener('input', updateDiscountPreview);

// Validate discount percentage
document.getElementById('discountPercent')?.addEventListener('input', function() {
    if (this.value > 99) {
        this.value = 99;
    }
    if (this.value < 0) {
        this.value = 0;
    }
});

// Initial preview check
setTimeout(updateDiscountPreview, 100);
</script>

<?php require_once '../includes/footer.php'; ?>