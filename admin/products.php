<?php
$page_title = 'Products Management';
require_once '../includes/header.php';

if (($user['role'] ?? '') !== 'admin') { flash('Access denied.', 'danger'); redirect('index.php'); }

if (isset($_GET['action']) && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $action = $_GET['action'];
    if ($action === 'approve') { $mysqli->query("UPDATE products SET status = 'approved' WHERE id = $product_id"); flash('Product approved.', 'success'); }
    elseif ($action === 'reject') { $mysqli->query("UPDATE products SET status = 'rejected' WHERE id = $product_id"); flash('Product rejected.', 'success'); }
    elseif ($action === 'delete') { $mysqli->query("DELETE FROM products WHERE id = $product_id"); flash('Product deleted.', 'success'); }
    redirect('admin/products.php');
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where_clauses = []; $params = []; $types = '';
if ($search) { $where_clauses[] = "(p.name LIKE ? OR s.shop_name LIKE ?)"; $p = "%$search%"; $params[] = $p; $params[] = $p; $types .= 'ss'; }
if ($status_filter) { $where_clauses[] = "p.status = ?"; $params[] = $status_filter; $types .= 's'; }
$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$count_sql = "SELECT COUNT(*) as total FROM products p LEFT JOIN sellers s ON s.id = p.seller_id $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
if(!empty($params)) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_products = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);

$sql = "SELECT p.*, c.name as category_name, s.shop_name FROM products p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN sellers s ON s.id = p.seller_id $where_sql ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[] = $limit; $params[] = $offset; $types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();
?>

<style>
    .admin-content-wrapper { display: flex; gap: 25px; min-height: calc(100vh - 200px); }
    .admin-sidebar-col { width: 280px; flex-shrink: 0; }
    .admin-main-col { flex: 1; }
    .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .search-input { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 15px; width: 250px; }
    .product-image { width: 50px; height: 50px; object-fit: cover; border-radius: 8px; background: #f3f4f6; }
    .view-details-btn { background: none; border: none; color: #2563eb; cursor: pointer; padding: 5px 10px; }
    @media (max-width: 992px) { .admin-content-wrapper { flex-direction: column; } .admin-sidebar-col { width: 100%; } .search-input { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col"><?php require_once '../includes/dashboard_sidebar.php'; ?></div>
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2><i class="fa-solid fa-box"></i> Products Management</h2>
                <a href="../seller/add_product.php" class="btn-add" style="text-decoration:none;"><i class="fa-solid fa-plus"></i> Add Product</a>
            </div>
            
            <div class="filter-bar">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        <select name="status" class="status-filter"><option value="">All Status</option><option value="pending" <?= $status_filter=='pending'?'selected':''?>>Pending</option><option value="approved" <?= $status_filter=='approved'?'selected':''?>>Approved</option><option value="rejected" <?= $status_filter=='rejected'?'selected':''?>>Rejected</option></select>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <?php if($search||$status_filter):?><a href="products.php" class="btn btn-secondary btn-sm">Clear</a><?php endif;?>
                    </form>
                    <span class="text-muted">Total Products: <strong><?= number_format($total_products) ?></strong></span>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3"><h5 class="mb-0">All Products</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light"><tr><th>ID</th><th>Image</th><th>Product</th><th>Seller</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php while($product = $products->fetch_assoc()): $img = $mysqli->query("SELECT filename FROM product_images WHERE product_id={$product['id']} LIMIT 1")->fetch_assoc(); ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td><img src="../assets/uploads/<?= $img['filename']??'placeholder.png' ?>" class="product-image"></td>
                                    <td><strong><?= htmlspecialchars($product['name']) ?></strong><br><small class="text-muted"><?= $product['category_name']??'-' ?></small></td>
                                    <td><?= htmlspecialchars($product['shop_name']??'-') ?></td>
                                    <td>KSH <?= number_format($product['price']) ?></td>
                                    <td><?= $product['stock'] ?></td>
                                    <td><?php if($product['status']=='approved'):?><span class="badge bg-success">Approved</span><?php elseif($product['status']=='pending'):?><span class="badge bg-warning">Pending</span><?php else:?><span class="badge bg-danger">Rejected</span><?php endif;?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="view-details-btn" onclick="viewProductDetails(<?= $product['id'] ?>)" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                            <?php if($product['status']=='pending'):?>
                                                <a href="?action=approve&id=<?= $product['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve?')"><i class="fa-solid fa-check"></i></a>
                                                <a href="?action=reject&id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject?')"><i class="fa-solid fa-times"></i></a>
                                            <?php else:?>
                                                <a href="?action=delete&id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></a>
                                            <?php endif;?>
                                            <a href="../product.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-info"><i class="fa-solid fa-eye"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if($total_pages>1):?><nav class="mt-4"><ul class="pagination justify-content-center"><?php for($i=1;$i<=$total_pages;$i++):?><li class="page-item <?=$i==$page?'active':''?>"><a class="page-link" href="?page=<?=$i?>&search=<?=urlencode($search)?>&status=<?=$status_filter?>"><?=$i?></a></li><?php endfor;?></ul></nav><?php endif;?>
        </div>
    </div>
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Product Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="productDetailsContent"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div></div>

<script>
function viewProductDetails(productId){
    var modal=new bootstrap.Modal(document.getElementById('viewProductModal'));
    modal.show();
    fetch('ajax/get_product_details.php?id='+productId).then(r=>r.json()).then(data=>{
        if(data.success){
            document.getElementById('productDetailsContent').innerHTML=`
                <div class="row"><div class="col-md-4"><img src="../assets/uploads/${data.product.image||'placeholder.png'}" class="img-fluid rounded"></div>
                <div class="col-md-8"><h4>${data.product.name}</h4><p class="text-muted">${data.product.short_description||''}</p>
                <table class="table table-bordered"><tr><th>Price</th><td>KSH ${data.product.price}</td><th>Stock</th><td>${data.product.stock}</td></tr>
                <tr><th>Category</th><td>${data.product.category_name||'-'}</td><th>Brand</th><td>${data.product.brand||'-'}</td></tr>
                <tr><th>Seller</th><td>${data.product.shop_name||'-'}</td><th>Status</th><td><span class="badge ${data.product.status=='approved'?'bg-success':(data.product.status=='pending'?'bg-warning':'bg-danger')}">${data.product.status}</span></td></tr>
                <tr><th>Created</th><td colspan="3">${new Date(data.product.created_at).toLocaleString()}</td></tr>
                <tr><th>Description</th><td colspan="3">${data.product.description||'No description'}</td></tr></table></div></div>`;
        } else { document.getElementById('productDetailsContent').innerHTML=`<div class="alert alert-danger">${data.message}</div>`; }
    }).catch(()=>{ document.getElementById('productDetailsContent').innerHTML=`<div class="alert alert-danger">Error loading details.</div>`; });
}
</script>

<?php require_once '../includes/footer.php'; ?>