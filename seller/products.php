<?php
$page_title = 'Manage Products';
require_once '../includes/header.php';
require_role('seller');

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

// Handle delete
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    
    // Get images to delete files
    $img_result = $mysqli->query("SELECT filename FROM product_images WHERE product_id = $product_id");
    if ($img_result) {
        while ($img = $img_result->fetch_assoc()) {
            $file_path = '../uploads/products/' . $img['filename'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    // Delete product images from database
    $mysqli->query("DELETE FROM product_images WHERE product_id = $product_id");
    
    // Delete product
    $delete_result = $mysqli->query("DELETE FROM products WHERE id = $product_id AND seller_id = {$seller['id']}");
    if ($delete_result) {
        flash('Product deleted successfully.', 'success');
    } else {
        flash('Failed to delete product.', 'danger');
    }
    redirect('seller/products.php');
}

// Search and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = "WHERE seller_id = {$seller['id']}";
if ($search) {
    $search = $mysqli->real_escape_string($search);
    $where .= " AND name LIKE '%$search%'";
}
if ($status_filter) {
    $status_filter = $mysqli->real_escape_string($status_filter);
    $where .= " AND status = '$status_filter'";
}

$total_result = $mysqli->query("SELECT COUNT(*) as total FROM products $where");
if ($total_result) {
    $total_products = $total_result->fetch_assoc()['total'];
} else {
    $total_products = 0;
}
$total_pages = ceil($total_products / $limit);

$products_result = $mysqli->query("SELECT p.*, c.name as category_name 
                            FROM products p 
                            LEFT JOIN categories c ON c.id = p.category_id 
                            $where 
                            ORDER BY p.created_at DESC 
                            LIMIT $offset, $limit");
?>

<style>
    .seller-wrapper { display: flex; gap: 25px; }
    .seller-sidebar { width: 280px; flex-shrink: 0; }
    .seller-content { flex: 1; }
    .filter-bar { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .data-table { width: 100%; border-collapse: collapse; background: white; border-radius: 12px; overflow: hidden; }
    .data-table th, .data-table td { padding: 12px; border-bottom: 1px solid #e5e7eb; text-align: left; }
    .data-table th { background: #f8fafc; font-weight: 600; }
    .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-approved { background: #d1fae5; color: #059669; }
    .status-pending { background: #fef3c7; color: #d97706; }
    .status-rejected { background: #fee2e2; color: #dc2626; }
    .btn-sm { padding: 5px 10px; border-radius: 6px; font-size: 12px; text-decoration: none; display: inline-block; }
    .btn-edit { background: #0dcaf0; color: white; }
    .btn-edit:hover { background: #0bb5d4; color: white; }
    .btn-delete { background: #dc3545; color: white; }
    .btn-delete:hover { background: #c82333; color: white; }
    .btn-view { background: #6c757d; color: white; }
    .btn-view:hover { background: #5a6268; color: white; }
    .search-input { padding: 8px 12px; border: 1px solid #ced4da; border-radius: 8px; width: 200px; }
    .product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; background: #f3f4f6; }
    .product-image-placeholder { width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; border: 1px dashed #d1d5db; }
    .discount-badge-small { display: inline-block; background: #ef4444; color: white; padding: 2px 8px; border-radius: 50px; font-size: 0.6rem; font-weight: 700; margin-left: 4px; }
    .hot-badge-small { display: inline-block; background: #f59e0b; color: white; padding: 2px 8px; border-radius: 50px; font-size: 0.6rem; font-weight: 700; margin-left: 4px; }
    @media (max-width: 992px) { .seller-wrapper { flex-direction: column; } .seller-sidebar { width: 100%; } }
    @media (max-width: 768px) { .data-table { font-size: 0.8rem; } .data-table th, .data-table td { padding: 8px; } .product-image { width: 40px; height: 40px; } }
</style>

<div class="container-fluid">
    <div class="seller-wrapper">
        <div class="seller-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="seller-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-box"></i> My Products</h2>
                <a href="add_product.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Product</a>
            </div>
            
            <div class="filter-bar">
                <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                    <select name="status" class="form-select" style="width: 120px;">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if($search || $status_filter): ?>
                        <a href="products.php" class="btn btn-secondary btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Discount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($products_result && $products_result->num_rows > 0): ?>
                        <?php while($product = $products_result->fetch_assoc()): 
                            $image_result = $mysqli->query("SELECT filename FROM product_images WHERE product_id = {$product['id']} LIMIT 1");
                            $image = $image_result ? $image_result->fetch_assoc() : null;
                            $status_class = $product['status'] == 'approved' ? 'status-approved' : ($product['status'] == 'pending' ? 'status-pending' : 'status-rejected');
                            
                            // Check if product has discount
                            $has_discount = isset($product['is_on_sale']) && $product['is_on_sale'] == 1 && 
                                            $product['discount_percent'] > 0 && 
                                            !empty($product['discount_end_date']) && 
                                            $product['discount_end_date'] > date('Y-m-d H:i:s');
                            $discount = $has_discount ? $product['discount_percent'] : 0;
                        ?>
                        <tr>
                            <td><?= $product['id'] ?></td>
                            <td>
                                <?php if($image && !empty($image['filename'])): 
                                    $has_file = file_exists('../uploads/products/' . $image['filename']);
                                ?>
                                    <?php if($has_file): ?>
                                        <img src="../uploads/products/<?= $image['filename'] ?>" 
                                             class="product-image" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="product-image-placeholder">
                                        <i class="fa-solid fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($product['name']) ?></strong>
                                <?php if($has_discount): ?>
                                    <span class="discount-badge-small">-<?= $discount ?>%</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                            <td>
                                <?php if($has_discount): ?>
                                    <span style="color:#ef4444; font-weight:600; font-size:0.85rem;">KSH <?= number_format($product['discounted_price'] ?? ($product['price'] * (1 - $discount / 100))) ?></span>
                                    <br>
                                    <span style="text-decoration:line-through; color:#9ca3af; font-size:0.65rem;">KSH <?= number_format($product['price']) ?></span>
                                <?php else: ?>
                                    KSH <?= number_format($product['price']) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= $product['stock'] ?></td>
                            <td>
                                <?php if($has_discount): ?>
                                    <span class="discount-badge-small">-<?= $discount ?>%</span>
                                    <br>
                                    <small style="color:#6b7280; font-size:0.6rem;">
                                        Ends: <?= date('M d', strtotime($product['discount_end_date'])) ?>
                                    </small>
                                <?php else: ?>
                                    <span style="color:#6b7280; font-size:0.7rem;">No discount</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="status-badge <?= $status_class ?>"><?= ucfirst($product['status']) ?></span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn-sm btn-edit" title="Edit Product">
                                        <i class="fa-solid fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?= $product['id'] ?>" class="btn-sm btn-delete" onclick="return confirm('Delete this product?')" title="Delete Product">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                    <a href="../product.php?id=<?= $product['id'] ?>" target="_blank" class="btn-sm btn-view" title="View Product">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php else: ?>
                        <tr><td colspan="9" class="text-center py-4">No products found. <a href="add_product.php">Add your first product</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i=1;$i<=$total_pages;$i++): ?>
                        <li class="page-item <?= $i==$page?'active':'' ?>">
                            <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>