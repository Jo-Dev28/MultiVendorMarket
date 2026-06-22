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

$sql = "SELECT p.*, c.name as category_name, s.shop_name, s.shop_logo, s.phone as seller_phone, s.location as seller_location, s.description as seller_description,
        (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
        FROM products p 
        LEFT JOIN categories c ON c.id = p.category_id 
        LEFT JOIN sellers s ON s.id = p.seller_id 
        $where_sql 
        ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[] = $limit; $params[] = $offset; $types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$products = $stmt->get_result();

// Get counts for stats
$total_count = $mysqli->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$pending_count = $mysqli->query("SELECT COUNT(*) as total FROM products WHERE status = 'pending'")->fetch_assoc()['total'];
$approved_count = $mysqli->query("SELECT COUNT(*) as total FROM products WHERE status = 'approved'")->fetch_assoc()['total'];
$rejected_count = $mysqli->query("SELECT COUNT(*) as total FROM products WHERE status = 'rejected'")->fetch_assoc()['total'];
?>

<style>
    .admin-content-wrapper { display: flex; gap: 25px; min-height: calc(100vh - 200px); }
    .admin-sidebar-col { width: 280px; flex-shrink: 0; }
    .admin-main-col { flex: 1; }
    .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .search-input { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 15px; width: 250px; }
    .status-filter { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 15px; }
    .btn-add { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border: none; padding: 10px 20px; border-radius: 12px; transition: all 0.3s ease; text-decoration: none; display: inline-block; }
    .btn-add:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3); color: white; }
    
    /* Stats Cards */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
    .stat-card { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
    .stat-card .number { font-size: 2rem; font-weight: 700; color: #1f2937; }
    .stat-card .label { font-size: 0.85rem; color: #6b7280; margin-top: 4px; }
    .stat-card .icon { font-size: 1.5rem; margin-bottom: 8px; }
    .stat-card.total .icon { color: #2563eb; }
    .stat-card.pending .icon { color: #f59e0b; }
    .stat-card.approved .icon { color: #10b981; }
    .stat-card.rejected .icon { color: #ef4444; }
    
    .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; background: #f3f4f6; }
    .product-image-placeholder { width: 60px; height: 60px; border-radius: 8px; background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 1.5rem; }
    .view-details-btn { background: none; border: none; color: #2563eb; cursor: pointer; padding: 5px 10px; }
    .view-details-btn:hover { color: #1d4ed8; }
    .discount-badge { display: inline-block; padding: 2px 10px; border-radius: 50px; font-size: 0.65rem; font-weight: 600; }
    .discount-badge.sale { background: #fef3c7; color: #d97706; }
    .discount-badge.flash { background: #fee2e2; color: #dc2626; animation: flashPulse 1s infinite; }
    .discount-badge.hot { background: #fef2f2; color: #dc2626; }
    @keyframes flashPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.6; } }
    
    .product-status { padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 600; }
    .product-status.approved { background: #d1fae5; color: #065f46; }
    .product-status.pending { background: #fef3c7; color: #92400e; }
    .product-status.rejected { background: #fee2e2; color: #991b1b; }
    
    /* Seller Card in Modal */
    .seller-card { 
        background: #f8fafc; 
        border-radius: 12px; 
        padding: 15px; 
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 15px;
        margin-top: 10px;
    }
    .seller-card .seller-logo { 
        width: 60px; 
        height: 60px; 
        border-radius: 50%; 
        object-fit: cover; 
        background: #e5e7eb;
        flex-shrink: 0;
    }
    .seller-card .seller-logo-placeholder {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #2563eb;
        flex-shrink: 0;
    }
    .seller-card .seller-info { flex: 1; }
    .seller-card .seller-info .shop-name { font-weight: 700; font-size: 1.1rem; color: #1f2937; }
    .seller-card .seller-info .shop-detail { font-size: 0.85rem; color: #6b7280; }
    .seller-card .seller-info .shop-detail i { width: 18px; color: #2563eb; }
    .seller-card .btn-view-seller { 
        background: #2563eb; 
        color: white; 
        border: none; 
        padding: 6px 16px; 
        border-radius: 8px; 
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.3s;
    }
    .seller-card .btn-view-seller:hover { 
        background: #1d4ed8; 
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        color: white;
    }
    
    .modal-tab-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 10px;
    }
    .modal-tab-nav button {
        background: none;
        border: none;
        padding: 8px 16px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .modal-tab-nav button:hover { background: #f3f4f6; color: #1f2937; }
    .modal-tab-nav button.active { background: #dbeafe; color: #2563eb; }
    .modal-tab-content { display: none; }
    .modal-tab-content.active { display: block; }
    
    @media (max-width: 992px) { 
        .admin-content-wrapper { flex-direction: column; } 
        .admin-sidebar-col { width: 100%; } 
        .search-input { width: 100%; }
        .stats-row { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) { 
        .stats-row { grid-template-columns: 1fr 1fr; }
        .seller-card { flex-direction: column; text-align: center; }
    }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2><i class="fa-solid fa-box"></i> Products Management</h2>
                <a href="../seller/add_product.php" class="btn-add"><i class="fa-solid fa-plus"></i> Add Product</a>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card total">
                    <div class="icon"><i class="fa-solid fa-box"></i></div>
                    <div class="number"><?= number_format($total_count) ?></div>
                    <div class="label">Total Products</div>
                </div>
                <div class="stat-card pending">
                    <div class="icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="number"><?= number_format($pending_count) ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="stat-card approved">
                    <div class="icon"><i class="fa-solid fa-check-circle"></i></div>
                    <div class="number"><?= number_format($approved_count) ?></div>
                    <div class="label">Approved</div>
                </div>
                <div class="stat-card rejected">
                    <div class="icon"><i class="fa-solid fa-times-circle"></i></div>
                    <div class="number"><?= number_format($rejected_count) ?></div>
                    <div class="label">Rejected</div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                        <select name="status" class="status-filter">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter=='pending'?'selected':'' ?>>Pending</option>
                            <option value="approved" <?= $status_filter=='approved'?'selected':'' ?>>Approved</option>
                            <option value="rejected" <?= $status_filter=='rejected'?'selected':'' ?>>Rejected</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <?php if($search||$status_filter): ?>
                            <a href="products.php" class="btn btn-secondary btn-sm">Clear</a>
                        <?php endif; ?>
                    </form>
                    <span class="text-muted">Total Products: <strong><?= number_format($total_products) ?></strong></span>
                </div>
            </div>
            
            <!-- Products Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3"><h5 class="mb-0">All Products</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Product</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Discount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($product = $products->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td>
                                        <?php if (!empty($product['image']) && file_exists('../uploads/products/' . $product['image'])): ?>
                                            <img src="../uploads/products/<?= $product['image'] ?>" class="product-image" alt="<?= htmlspecialchars($product['name']) ?>">
                                        <?php else: ?>
                                            <div class="product-image-placeholder">
                                                <i class="fa-solid fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="font-size:0.9rem;"><?= htmlspecialchars($product['name']) ?></strong>
                                        <br><small class="text-muted"><?= $product['category_name'] ?? '-' ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($product['shop_name'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($product['is_on_sale'] == 1 && $product['discounted_price'] > 0): ?>
                                            <span class="text-muted" style="text-decoration: line-through; font-size:0.7rem;">KSH <?= number_format($product['price']) ?></span>
                                            <br><strong class="text-danger">KSH <?= number_format($product['discounted_price']) ?></strong>
                                        <?php else: ?>
                                        <span class="text-muted" style="text-decoration:  font-size:0.7rem;">KSH <?= number_format($product['price']) ?></span>
                                            <strong>KSH <?= number_format($product['price']) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['stock'] <= 5 && $product['stock'] > 0): ?>
                                            <span class="badge bg-warning text-dark"><?= $product['stock'] ?> left</span>
                                        <?php elseif ($product['stock'] <= 0): ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><?= $product['stock'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($product['is_on_sale'] == 1 && $product['discounted_price'] > 0): ?>
                                            <span class="discount-badge sale">
                                                <i class="fa-solid fa-tag"></i> <?= $product['discount_percent'] ?>% OFF
                                            </span>
                                            <?php if ($product['discount_end_date']): ?>
                                                <br><small class="text-muted">Until <?= date('M d', strtotime($product['discount_end_date'])) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="product-status <?= $product['status'] ?>">
                                            <?= ucfirst($product['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button class="view-details-btn" onclick="viewProductDetails(<?= $product['id'] ?>)" title="View Details">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                            <?php if($product['status'] == 'pending'): ?>
                                                <a href="?action=approve&id=<?= $product['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this product?')" title="Approve">
                                                    <i class="fa-solid fa-check"></i>
                                                </a>
                                                <a href="?action=reject&id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this product?')" title="Reject">
                                                    <i class="fa-solid fa-times"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=delete&id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this product?')" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="../product.php?id=<?= $product['id'] ?>" target="_blank" class="btn btn-sm btn-info" title="View on Site">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Product Modal -->
<div class="modal fade" id="viewProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-box"></i> Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="productDetailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewProductDetails(productId) {
    var modal = new bootstrap.Modal(document.getElementById('viewProductModal'));
    modal.show();
    
    document.getElementById('productDetailsContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 text-muted">Loading product details...</p>
        </div>
    `;
    
    fetch('ajax/get_product_details.php?id=' + productId)
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('Server returned: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayProductDetails(data);
            } else {
                document.getElementById('productDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> 
                        ${data.message || 'Failed to load product details.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('productDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> 
                    Error loading product details. Please try again.
                    <br><small class="text-muted">${error.message}</small>
                </div>
            `;
        });
}

function displayProductDetails(data) {
    const p = data.product;
    const images = data.images || [];
    const seller = data.seller || {};
    
    // Build seller logo
    let sellerLogoHtml = '';
    if (seller.shop_logo && seller.shop_logo !== '') {
        // Check if logo exists
        sellerLogoHtml = `<img src="../uploads/sellers/${seller.shop_logo}" class="seller-logo" alt="${seller.shop_name || 'Shop'}">`;
    } else {
        sellerLogoHtml = `<div class="seller-logo-placeholder"><i class="fa-solid fa-store"></i></div>`;
    }
    
    // Build discount info
    let discountHtml = '<span class="text-muted">No discount</span>';
    let priceDisplay = `<strong>KSH ${Number(p.price).toLocaleString()}</strong>`;
    
    if (p.is_on_sale == 1 && p.discounted_price > 0) {
        discountHtml = `
            <span class="discount-badge sale">
                <i class="fa-solid fa-tag"></i> ${p.discount_percent}% OFF
            </span>
            ${p.discount_start_date ? `<br><small>From: ${new Date(p.discount_start_date).toLocaleDateString()}</small>` : ''}
            ${p.discount_end_date ? `<br><small>Until: ${new Date(p.discount_end_date).toLocaleDateString()}</small>` : ''}
        `;
        priceDisplay = `
            <span class="text-muted" style="text-decoration: line-through;">KSH ${Number(p.price).toLocaleString()}</span>
            <br><strong class="text-danger" style="font-size:1.2rem;">KSH ${Number(p.discounted_price).toLocaleString()}</strong>
        `;
    }
    
    // Build images gallery
    let imagesHtml = '';
    if (images.length > 0) {
        imagesHtml = '<div class="row g-2 mt-2">';
        images.forEach(img => {
            imagesHtml += `
                <div class="col-3">
                    <img src="../uploads/products/${img.filename}" class="img-fluid rounded" style="height:80px; width:100%; object-fit:cover; cursor:pointer;" onclick="window.open('../uploads/products/${img.filename}','_blank')">
                </div>
            `;
        });
        imagesHtml += '</div>';
    } else {
        imagesHtml = '<p class="text-muted">No additional images</p>';
    }
    
    // Build seller card
    let sellerCardHtml = `
        <div class="seller-card">
            ${sellerLogoHtml}
            <div class="seller-info">
                <div class="shop-name">${seller.shop_name || 'Unknown Shop'}</div>
                ${seller.seller_phone ? `<div class="shop-detail"><i class="fa-solid fa-phone"></i> ${seller.seller_phone}</div>` : ''}
                ${seller.seller_location ? `<div class="shop-detail"><i class="fa-solid fa-location-dot"></i> ${seller.seller_location}</div>` : ''}
                ${seller.seller_description ? `<div class="shop-detail"><i class="fa-solid fa-info-circle"></i> ${seller.seller_description.substring(0, 100)}${seller.seller_description.length > 100 ? '...' : ''}</div>` : ''}
            </div>
            <a href="../seller_profile.php?id=${p.seller_id}" target="_blank" class="btn-view-seller">
                <i class="fa-solid fa-store"></i> View Shop
            </a>
        </div>
    `;
    
    // Build full product details with tabs
    document.getElementById('productDetailsContent').innerHTML = `
        <!-- Tabs -->
        <div class="modal-tab-nav">
            <button class="active" onclick="switchTab(event, 'productTab')">
                <i class="fa-solid fa-box"></i> Product
            </button>
            <button onclick="switchTab(event, 'sellerTab')">
                <i class="fa-solid fa-store"></i> Seller
            </button>
            <button onclick="switchTab(event, 'imagesTab')">
                <i class="fa-solid fa-images"></i> Images (${images.length})
            </button>
        </div>
        
        <!-- Product Tab -->
        <div class="modal-tab-content active" id="productTab">
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center">
                        ${p.main_image ? 
                            `<img src="../uploads/products/${p.main_image}" class="img-fluid rounded" style="max-height:250px; width:100%; object-fit:cover; cursor:pointer;" onclick="window.open('../uploads/products/${p.main_image}','_blank')">` :
                            `<div style="height:200px; background:#f3f4f6; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                                <i class="fa-solid fa-image" style="font-size:3rem; color:#9ca3af;"></i>
                            </div>`
                        }
                    </div>
                </div>
                <div class="col-md-8">
                    <h4>${p.name}</h4>
                    <p class="text-muted">${p.short_description || ''}</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th>Price</th><td>${priceDisplay}</td></tr>
                                <tr><th>Stock</th><td>
                                    ${p.stock <= 0 ? '<span class="text-danger">Out of Stock</span>' : 
                                      p.stock <= 5 ? `<span class="text-warning">${p.stock} left</span>` : 
                                      `<span class="text-success">${p.stock}</span>`}
                                </td></tr>
                                <tr><th>Category</th><td>${p.category_name || '-'}</td></tr>
                                <tr><th>Brand</th><td>${p.brand || '-'}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-bordered table-sm">
                                <tr><th>Status</th><td><span class="product-status ${p.status}">${p.status}</span></td></tr>
                                <tr><th>Created</th><td>${new Date(p.created_at).toLocaleString()}</td></tr>
                                <tr><th>Discount</th><td>${discountHtml}</td></tr>
                                <tr><th>Rating</th><td>${p.rating || 0} <i class="fa-solid fa-star text-warning"></i></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6>Description</h6>
                        <div class="p-3 bg-light rounded" style="white-space:pre-wrap;">${p.description || 'No description available.'}</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seller Tab -->
        <div class="modal-tab-content" id="sellerTab">
            <h6><i class="fa-solid fa-store"></i> Seller Information</h6>
            ${sellerCardHtml}
        </div>
        
        <!-- Images Tab -->
        <div class="modal-tab-content" id="imagesTab">
            <h6><i class="fa-solid fa-images"></i> Product Images</h6>
            ${imagesHtml}
        </div>
    `;
}

function switchTab(event, tabId) {
    // Remove active class from all tabs
    document.querySelectorAll('.modal-tab-nav button').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.modal-tab-content').forEach(content => content.classList.remove('active'));
    
    // Add active class to clicked tab
    event.currentTarget.classList.add('active');
    document.getElementById(tabId).classList.add('active');
}
</script>

<?php require_once '../includes/footer.php'; ?>