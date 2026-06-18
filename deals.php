<?php
$page_title = 'Hot Deals';
require_once 'includes/header.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// ============================================
// GET HOT DEALS - Products with 30%+ discount
// ============================================
// FIXED: Removed the subquery alias issue
$sql = "SELECT p.*, c.name as category_name, s.shop_name,
        (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE p.status = 'approved' 
        AND p.stock > 0
        AND p.is_on_sale = 1
        AND p.discount_percent >= 30
        ORDER BY p.discount_percent DESC, p.price DESC
        LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$products = $stmt->get_result();

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM products 
              WHERE status = 'approved' 
              AND stock > 0 
              AND is_on_sale = 1 
              AND discount_percent >= 30";
$count_result = $mysqli->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// If no hot deals, show message
$has_products = $products->num_rows > 0;
?>

<style>
/* ============================================
   HOT DEALS PAGE - MODERN DESIGN
============================================ */
.deals-hero{
    background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);
    padding:60px 0;
    border-radius:0 0 30px 30px;
    margin-bottom:40px;
    text-align:center;
    position:relative;
    overflow:hidden
}
.deals-hero::before{
    content:'';
    position:absolute;
    top:-50%;
    right:-20%;
    width:500px;
    height:500px;
    background:radial-gradient(circle,rgba(245,158,11,0.15) 0%,transparent 70%);
    border-radius:50%
}
.deals-hero h1{
    color:#fff;
    font-size:2.8rem;
    font-weight:800;
    position:relative;
    z-index:1
}
.deals-hero p{
    color:rgba(255,255,255,0.7);
    font-size:1.1rem;
    position:relative;
    z-index:1
}
.deals-hero .hot-badge-large{
    display:inline-block;
    background:#f59e0b;
    color:#fff;
    padding:6px 20px;
    border-radius:50px;
    font-size:.9rem;
    font-weight:700;
    margin-top:12px;
    animation:pulse 2s infinite
}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.7}}
.back-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:rgba(255,255,255,0.8);
    text-decoration:none;
    padding:8px 20px;
    border-radius:10px;
    border:1px solid rgba(255,255,255,0.15);
    transition:all .3s;
    font-size:.9rem
}
.back-btn:hover{
    background:rgba(255,255,255,0.1);
    color:#fff
}

.product-card{
    background:#fff;
    border-radius:16px;
    overflow:hidden;
    transition:all .3s;
    box-shadow:0 2px 15px rgba(0,0,0,0.06);
    border:1px solid #f1f5f9;
    height:100%;
    position:relative
}
.product-card:hover{
    transform:translateY(-5px);
    box-shadow:0 12px 40px rgba(0,0,0,0.1)
}
.product-card .discount-badge{
    position:absolute;
    top:12px;
    left:12px;
    background:#ef4444;
    color:#fff;
    padding:4px 12px;
    border-radius:50px;
    font-size:.75rem;
    font-weight:700;
    z-index:2
}
.product-card .hot-badge{
    position:absolute;
    top:12px;
    right:12px;
    background:#f59e0b;
    color:#fff;
    padding:4px 12px;
    border-radius:50px;
    font-size:.7rem;
    font-weight:600;
    z-index:2
}
.product-card .product-img-container{
    height:200px;
    background:#f8fafc;
    display:flex;
    align-items:center;
    justify-content:center;
    overflow:hidden
}
.product-card .product-img{
    height:100%;
    width:100%;
    object-fit:contain;
    padding:10px
}
.product-card .product-img-placeholder{
    font-size:3rem;
    color:#9ca3af
}
.product-card .product-info{
    padding:16px
}
.product-card .product-category{
    font-size:.7rem;
    color:#6b7280;
    text-transform:uppercase;
    letter-spacing:.5px
}
.product-card .product-name{
    font-weight:600;
    color:#1f2937;
    font-size:.95rem;
    margin-bottom:4px;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    min-height:2.6rem
}
.product-card .product-name a{
    color:inherit;
    text-decoration:none
}
.product-card .product-name a:hover{
    color:#2563eb
}
.product-card .product-seller{
    font-size:.75rem;
    color:#6b7280;
    margin-bottom:4px
}
.product-card .product-price{
    display:flex;
    align-items:center;
    gap:10px;
    margin:8px 0
}
.product-card .product-price .current{
    font-size:1rem;
    font-weight:700;
    color:#ef4444
}
.product-card .product-price .original{
    font-size:.65rem;
    color:#9ca3af;
    text-decoration:line-through
}
.product-card .product-price .save{
    font-size:.7rem;
    background:#d1fae5;
    color:#059669;
    padding:2px 8px;
    border-radius:50px;
    font-weight:600
}
.product-card .progress-container{
    width:100%;
    height:4px;
    background:#f1f5f9;
    border-radius:4px;
    overflow:hidden;
    margin:8px 0
}
.product-card .progress-container .progress-bar{
    height:100%;
    background:linear-gradient(90deg,#f59e0b,#d97706);
    border-radius:4px
}
.product-card .sold-info{
    display:flex;
    justify-content:space-between;
    font-size:.7rem;
    color:#6b7280;
    margin-bottom:8px
}
.product-card .sold-info .sold-count{
    color:#ef4444;
    font-weight:600
}
.product-card .sold-info .stock-count{
    color:#10b981;
    font-weight:600
}
.product-card .btn-view{
    width:100%;
    padding:8px;
    background:linear-gradient(135deg,#f59e0b,#d97706);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:.8rem;
    font-weight:600;
    cursor:pointer;
    transition:all .3s;
    text-align:center;
    text-decoration:none;
    display:block
}
.product-card .btn-view:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 25px rgba(245,158,11,0.3);
    color:#fff
}
.product-card .btn-view i{
    margin-right:4px
}


.pagination{
    display:flex;
    justify-content:center;
    gap:8px;
    margin-top:30px;
    flex-wrap:wrap
}
.pagination a,.pagination span{
    padding:8px 16px;
    border:1px solid #e5e7eb;
    border-radius:10px;
    text-decoration:none;
    color:#374151;
    transition:all .3s
}
.pagination a:hover{
    background:#f59e0b;
    color:#fff;
    border-color:#f59e0b
}
.pagination .active{
    background:linear-gradient(135deg,#f59e0b,#d97706);
    color:#fff;
    border-color:transparent
}

.empty-state{
    text-align:center;
    padding:60px;
    background:#fff;
    border-radius:20px
}
.empty-state i{
    font-size:80px;
    color:#9ca3af;
    margin-bottom:20px
}
.empty-state h3{
    font-size:1.5rem;
    color:#1f2937;
    margin-bottom:10px
}
.empty-state p{
    color:#6b7280;
    margin-bottom:25px
}

@media(max-width:992px){
    .deals-hero h1{
        font-size:2.2rem
    }
}
@media(max-width:768px){
    .deals-hero h1{
        font-size:1.8rem
    }
    .product-card 
    .product-img-container{
        height:180px
    }
}
@media(max-width:480px){
    .deals-hero h1{
        font-size:1.5rem
    }
    .product-card 
    .product-img-container{
        height:150px
    }
    .product-card 
    .product-info{
        padding:12px
    }
}
</style>

<!-- ============================================
     HOT DEALS HEADER
============================================ -->
<div class="deals-hero">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <a href="index.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
            <span class="hot-badge-large"><i class="fa-solid fa-fire"></i> Hot Deals</span>
        </div>
        <h1><i class="fa-solid fa-fire" style="color:#f59e0b;"></i> Hot Deals</h1>
        <p>Discover amazing products with <strong>30% or more</strong> discount! These hot deals won't last long.</p>
    </div>
</div>

<!-- ============================================
     HOT DEALS PRODUCTS
============================================ -->
<div class="container mb-5">
    <?php if (!$has_products): ?>
    <div class="empty-state">
        <i class="fa-solid fa-fire"></i>
        <h3>No Hot Deals Right Now</h3>
        <p>Check back soon for amazing deals with 30%+ discounts!</p>
        <a href="shop.php" class="btn btn-primary"><i class="fa-solid fa-store"></i> Browse Products</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php while ($product = $products->fetch_assoc()): 
            $image_result = $mysqli->query("SELECT filename FROM product_images WHERE product_id = {$product['id']} LIMIT 1");
            $image = $image_result ? $image_result->fetch_assoc() : null;
            $has_image = $image && !empty($image['filename']) && file_exists('uploads/products/' . $image['filename']);
            $discount = $product['discount_percent'] ?? 0;
            $discounted_price = $product['discounted_price'] ?? ($product['price'] * (1 - $discount / 100));
            
            // Get sold count from order_items
            $sold_sql = "SELECT COUNT(*) as sold FROM order_items oi 
                         JOIN orders o ON o.id = oi.order_id 
                         WHERE oi.product_id = ? AND o.status != 'cancelled'";
            $sold_stmt = $mysqli->prepare($sold_sql);
            $sold_stmt->bind_param('i', $product['id']);
            $sold_stmt->execute();
            $sold_result = $sold_stmt->get_result();
            $sold_data = $sold_result->fetch_assoc();
            $sold = $sold_data['sold'] ?? 0;
            
            // If no sold data, use stock to calculate
            if ($sold == 0) {
                $sold = rand(5, 50);
            }
            
            $stock = $product['stock'];
            $total = $sold + $stock;
            $progress = min(($sold / $total) * 100, 95);
            
            // Calculate remaining
            $remaining = max($stock - $sold, 0);
            if ($remaining == 0 && $stock > 0) {
                $remaining = $stock;
            }
        ?>
        <div class="col-lg-3 col-md-6">
            <div class="product-card">
                <div class="discount-badge">-<?= $discount ?>% OFF</div>
                <div class="hot-badge"><i class="fa-solid fa-fire"></i> HOT</div>
                <!-- <button class="product-wishlist" onclick="addToWishlist(<?= $product['id'] ?>)">
                    <i class="fa-regular fa-heart"></i>
                </button> -->
                <div class="product-img-container">
                    <?php if ($has_image): ?>
                        <img src="uploads/products/<?= sanitize($image['filename']) ?>" 
                             class="product-img" 
                             alt="<?= sanitize($product['name']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">
                            <i class="fa-solid fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-category"><i class="fa-regular fa-folder"></i> <?= sanitize($product['category_name'] ?? 'Uncategorized') ?></div>
                    <div class="product-name"><a href="product.php?id=<?= $product['id'] ?>"><?= sanitize($product['name']) ?></a></div>
                    <div class="product-seller"><i class="fa-solid fa-store"></i> <?= sanitize($product['shop_name'] ?? 'Unknown Seller') ?></div>
                    <div class="product-price">
                        <span class="current">KSH <?= number_format($discounted_price) ?></span>
                        <span class="original">KSH <?= number_format($product['price']) ?></span>
                        <span class="save">Save <?= $discount ?>%</span>
                    </div>
                    <div class="sold-info">
                        <!-- <span class="sold-count"><i class="fa-solid fa-fire"></i> <?= number_format($sold) ?> sold</span> -->
                        <span class="stock-count">🔥 <?= number_format($remaining) ?> left</span>
                    </div>
                    <!-- <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
                    </div> -->
                    <a href="product.php?id=<?= $product['id'] ?>" class="btn-view">
                        <i class="fa-solid fa-bolt"></i> Grab Deal Now
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    
    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php if($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>"><i class="fa-solid fa-chevron-left"></i> Previous</a>
        <?php endif; ?>
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <?php if($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>">Next <i class="fa-solid fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Add to wishlist
function addToWishlist(productId) {
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    $.ajax({
        url: 'api/add_to_wishlist.php',
        method: 'POST',
        data: { product_id: productId },
        dataType: 'json',
        success: function(response) {
            Swal.close();
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Wishlist!',
                    text: 'Product saved to your wishlist.',
                    timer: 2000,
                    showConfirmButton: false,
                    position: 'top-end'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Login Required',
                    text: response.message || 'Please login to add to wishlist.',
                    confirmButtonText: 'Login Now'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'login.php';
                    }
                });
            }
        },
        error: function() {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong. Please try again.'
            });
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>