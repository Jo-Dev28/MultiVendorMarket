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
    
    // Generate slug
    $slug = strtolower(str_replace(' ', '-', $name)) . '-' . uniqid();
    
    if (empty($name) || $category_id == 0 || $price <= 0) {
        flash('Please fill all required fields.', 'danger');
    } else {
        $sql = "INSERT INTO products (seller_id, category_id, name, slug, short_description, description, price, stock, brand, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iisssdiss', $seller['id'], $category_id, $name, $slug, $short_description, $description, $price, $stock, $brand);
        
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
            flash('Failed to add product.', 'danger');
        }
    }
}
?>

<style>
    .form-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .form-label { font-weight: 600; margin-bottom: 8px; display: block; }
    .form-control, .form-select { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 8px; }
    .btn-submit { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600; }
    .image-preview { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
    .preview-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
</style>

<div class="container">
    <div class="row">
        <div class="col-lg-3">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="form-container">
                <h2 class="mb-4">Add New Product</h2>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (KSH) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock Quantity *</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Short Description</label>
                        <input type="text" name="short_description" class="form-control" placeholder="Brief product description">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Description</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Detailed product description"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Product Images</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*" onchange="previewImages(this)">
                        <div class="image-preview" id="imagePreview"></div>
                        <small class="text-muted">You can upload multiple images</small>
                    </div>
                    
                    <button type="submit" class="btn-submit">Add Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>

<?php require_once '../includes/footer.php'; ?>