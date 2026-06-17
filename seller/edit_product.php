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

// Get product images - FIXED: Use correct table name
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
    
    if (empty($name) || $category_id == 0 || $price <= 0) {
        flash('Please fill all required fields.', 'danger');
    } else {
        $sql = "UPDATE products SET name=?, category_id=?, price=?, stock=?, brand=?, short_description=?, description=? WHERE id=? AND seller_id=?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            flash('Database error: ' . $mysqli->error, 'danger');
        } else {
            $stmt->bind_param('sidsissii', $name, $category_id, $price, $stock, $brand, $short_description, $description, $product_id, $seller['id']);
            
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
    // First get the filename to delete the actual file
    $img_result = $mysqli->query("SELECT filename FROM product_images WHERE id = $image_id AND product_id = $product_id");
    if ($img_result && $img_result->num_rows > 0) {
        $img_data = $img_result->fetch_assoc();
        $file_path = '../uploads/products/' . $img_data['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        // Also try to delete from absolute path
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
?>

<style>
    .form-container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .form-label { font-weight: 600; margin-bottom: 8px; display: block; }
    .form-control, .form-select { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 8px; }
    .btn-submit { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37,99,235,0.3); }
    .image-gallery { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
    .image-item { position: relative; width: 100px; }
    .image-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
    .delete-image { position: absolute; top: -8px; right: -8px; background: red; color: white; border-radius: 50%; width: 22px; height: 22px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 12px; }
    .delete-image:hover { background: #dc2626; color: white; }
    .image-preview { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
    .preview-img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
    .btn-secondary { background: #6c757d; color: white; padding: 10px; border: none; border-radius: 8px; cursor: pointer; text-align: center; text-decoration: none; display: block; margin-top: 10px; }
    .btn-secondary:hover { background: #5a6268; color: white; }
    .current-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .status-approved { background: #d1fae5; color: #059669; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .no-image-placeholder { width: 100px; height: 100px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; border: 1px dashed #d1d5db; }
    @media (max-width: 992px) { .seller-wrapper { flex-direction: column; } .seller-sidebar { width: 100%; } }
</style>

<div class="container">
    <div class="row">
        <div class="col-lg-3">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        <div class="col-lg-9">
            <div class="form-container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Edit Product: <?= htmlspecialchars($product['name']) ?></h2>
                    <span class="current-status status-<?= $product['status'] ?>"><?= ucfirst($product['status']) ?></span>
                </div>
                
                <!-- Current Images -->
                <div class="mb-3">
                    <label class="form-label">Current Images</label>
                    <div class="image-gallery">
                        <?php 
                        $images_result = $mysqli->query("SELECT * FROM product_images WHERE product_id = $product_id ORDER BY id ASC");
                        if ($images_result && $images_result->num_rows > 0): 
                            while($img = $images_result->fetch_assoc()): 
                                // Check multiple possible paths
                                $image_paths = [
                                    '../uploads/products/' . $img['filename'],
                                    '../uploads/' . $img['filename'],
                                    '../uploads/products/' . $img['filename'],
                                    UPLOAD_DIR . '/products/' . $img['filename']
                                ];
                                $found_path = null;
                                foreach ($image_paths as $path) {
                                    if (file_exists($path) || file_exists(str_replace('../', '', $path))) {
                                        // Try relative path
                                        if (file_exists('../uploads/products/' . $img['filename'])) {
                                            $found_path = '../uploads/products/' . $img['filename'];
                                        } elseif (file_exists(UPLOAD_DIR . '/products/' . $img['filename'])) {
                                            // Use web accessible path
                                            $found_path = '../uploads/products/' . $img['filename'];
                                        }
                                        break;
                                    }
                                }
                                if (!$found_path) {
                                    $found_path = '../uploads/products/' . $img['filename'];
                                }
                        ?>
                        <div class="image-item">
                            <img src="<?= $found_path ?>" 
                                 alt="Product image" 
                                 onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'100\' viewBox=\'0 0 100 100\'%3E%3Crect width=\'100\' height=\'100\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50\' y=\'45\' font-family=\'Arial\' font-size=\'12\' fill=\'%239ca3af\' text-anchor=\'middle\'%3ENo Image%3C/text%3E%3Ctext x=\'50\' y=\'60\' font-family=\'Arial\' font-size=\'10\' fill=\'%239ca3af\' text-anchor=\'middle\'%3E' + (new URLSearchParams({f: '<?= substr($img['filename'], 0, 10) ?>'})).get('f') + '%3C/text%3E%3C/svg%3E';">
                            <a href="?delete_image=<?= $img['id'] ?>&id=<?= $product_id ?>" class="delete-image" onclick="return confirm('Delete this image?')">×</a>
                        </div>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <div class="no-image-placeholder">
                            <i class="fa-solid fa-image"></i> No images
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php if($categories): ?>
                                <?php while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Brand</label>
                            <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($product['brand']) ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (KSH) *</label>
                            <input type="number" name="price" class="form-control" step="0.01" value="<?= $product['price'] ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock Quantity *</label>
                            <input type="number" name="stock" class="form-control" value="<?= $product['stock'] ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Short Description</label>
                        <input type="text" name="short_description" class="form-control" value="<?= htmlspecialchars($product['short_description']) ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Full Description</label>
                        <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($product['description']) ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Add More Images</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*" onchange="previewImages(this)">
                        <div class="image-preview" id="imagePreview"></div>
                        <small class="text-muted">You can select multiple images at once. Max 5MB per image.</small>
                    </div>
                    
                    <button type="submit" class="btn-submit">Update Product</button>
                    <a href="products.php" class="btn-secondary">Back to Products</a>
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