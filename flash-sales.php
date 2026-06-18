<?php
$page_title = 'Flash Sales';
require_once 'includes/header.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// ============================================
// GET PRODUCTS WITH ACTIVE DISCOUNTS - FETCH ALL DATA
// ============================================
$sql = "SELECT p.*, 
        c.name as category_name, 
        s.shop_name,
        (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image,
        (SELECT COUNT(*) FROM order_items oi 
         JOIN orders o ON o.id = oi.order_id 
         WHERE oi.product_id = p.id AND o.status != 'cancelled') as total_sold
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN sellers s ON p.seller_id = s.id
        WHERE p.status = 'approved' 
        AND p.stock > 0
        AND p.is_on_sale = 1
        AND p.discount_percent > 0
        AND p.discount_end_date > NOW()
        ORDER BY p.discount_percent DESC, p.created_at DESC
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
              AND discount_percent > 0 
              AND discount_end_date > NOW()";
$count_result = $mysqli->query($count_sql);
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $limit);

// If no flash sales, show sample products with random discounts
if ($total_items == 0) {
    $sample_sql = "SELECT p.*, c.name as category_name, s.shop_name,
                   (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image,
                   (SELECT COUNT(*) FROM order_items oi 
                    JOIN orders o ON o.id = oi.order_id 
                    WHERE oi.product_id = p.id AND o.status != 'cancelled') as total_sold
                   FROM products p
                   LEFT JOIN categories c ON c.id = p.category_id
                   LEFT JOIN sellers s ON p.seller_id = s.id
                   WHERE p.status = 'approved' AND p.stock > 0
                   ORDER BY RAND()
                   LIMIT ? OFFSET ?";
    $sample_stmt = $mysqli->prepare($sample_sql);
    $sample_stmt->bind_param('ii', $limit, $offset);
    $sample_stmt->execute();
    $products = $sample_stmt->get_result();
    
    $sample_count = $mysqli->query("SELECT COUNT(*) as total FROM products WHERE status = 'approved' AND stock > 0");
    $total_items = $sample_count->fetch_assoc()['total'];
    $total_pages = ceil($total_items / $limit);
    $show_sample = true;
} else {
    $show_sample = false;
}

// Helper functions
function get_time_remaining($end_date) {
    if (!$end_date) return 'Ending soon';
    $now = new DateTime();
    $end = new DateTime($end_date);
    $diff = $now->diff($end);
    
    if ($diff->days > 1) {
        return $diff->days . ' days left';
    } elseif ($diff->days == 1) {
        return '1 day left';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hours left';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minutes left';
    } else {
        return 'Ending soon!';
    }
}

function get_progress($product) {
    $sold = $product['total_sold'] ?? rand(50, 500);
    $stock = $product['stock'];
    $total = $sold + $stock;
    return min(($sold / $total) * 100, 95);
}
?>

<style>
.flash-hero{
    background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);
    padding:60px 0;
    border-radius:0 0 30px 30px;
    margin-bottom:40px;
    text-align:center;
    position:relative;
    overflow:hidden
}
.flash-hero::before{
    content:'';
    position:absolute;
    top:-50%;right:-20%;
    width:500px;
    height:500px;
    background:radial-gradient(circle,rgba(239,68,68,0.15) 0%,transparent 70%);
    border-radius:50%
}
.flash-hero h1{
    color:#fff;
    font-size:2.8rem;
    font-weight:800;
    position:relative;
    z-index:1
}
.flash-hero p{
    color:rgba(255,255,255,0.7);
    font-size:1.1rem;
    position:relative;
    z-index:1
}
.flash-badge{
    display:inline-block;
    background:#ef4444;
    color:#fff;
    padding:4px 16px;
    border-radius:50px;
    font-size:.75rem;
    font-weight:700;
    text-transform:uppercase;
    animation:pulse 2s infinite
}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.6}}
.back-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;color:rgba(255,255,255,0.8);
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
.product-card .time-badge{
    position:absolute;
    top:12px;
    right:12px;
    background:rgba(0,0,0,0.7);
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
.product-card .product-info{padding:16px}
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
    color:#2563eb}
.product-card .product-seller{
    font-size:.75rem;
    color:#6b7280;
    margin-bottom:4px}
.product-card .product-price{
    display:flex;
    align-items:center;
    gap:10px;
    margin:8px 0
}
.product-card .product-price .current{
    font-size:1rem;
    font-weight:700;
    color:#ef4444}
.product-card .product-price .original{
    font-size:.65rem;
    color:#9ca3af;
    text-decoration:line-through}
.product-card .product-price .save{
    font-size:.7rem;
    background:#d1fae5;
    color:#059669;
    padding:2px 8px;
    border-radius:50px;
    font-weight:600}
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
    background:linear-gradient(90deg,#ef4444,#dc2626);
    border-radius:4px
}
.product-card .sold-info{
    display:flex;
    justify-content:space-between;
    font-size:.7rem;color:#6b7280;
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
    background:linear-gradient(135deg,#ef4444,#dc2626);
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
    box-shadow:0 8px 25px rgba(239,68,68,0.3)
}
.pagination{
    display:flex;
    justify-content:center;
    gap:8px;margin-top:30px;
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
    background:#ef4444;
    color:#fff;
    border-color:#ef4444
}
.pagination .active{
    background:linear-gradient(135deg,#ef4444,#dc2626);
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
@media(max-width:768px){
    .flash-hero h1{
        font-size:2rem
    }
}
</style>

<div class="flash-hero">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <a href="index.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
            <span class="flash-badge"><i class="fa-solid fa-bolt"></i> Limited Time Offer</span>
        </div>
        <h1><i class="fa-solid fa-bolt" style="color:#ef4444;"></i> Flash Sales</h1>
        <p>Hurry! These amazing deals won't last long. Grab them before they're gone!</p>
    </div>
</div>

<div class="container mb-5">
    <?php if ($products->num_rows === 0): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clock"></i>
        <h3>No Flash Sales Right Now</h3>
        <p>Check back soon for amazing deals!</p>
        <a href="shop.php" class="btn btn-primary"><i class="fa-solid fa-store"></i> Browse Products</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php while ($product = $products->fetch_assoc()): 
            // Get ALL product data from database
            $product_id = $product['id'];
            $product_name = $product['name'];
            $product_price = $product['price'];
            $product_stock = $product['stock'];
            $discount = $product['discount_percent'];
            $discounted_price = $product['discounted_price'] ?? ($product_price * (1 - $discount / 100));
            $category = $product['category_name'] ?? 'Uncategorized';
            $seller = $product['shop_name'] ?? 'Unknown Seller';
            $image = $product['image'];
            $has_image = !empty($image) && file_exists('uploads/products/' . $image);
            
            // Get sold count from database or calculate
            $sold = $product['total_sold'] ?? rand(50, 500);
            $remaining = max($product_stock, 0);
            $total = $sold + $remaining;
            $progress = min(($sold / $total) * 100, 95);
            
            // Get time remaining
            $time_left = $product['discount_end_date'] ? get_time_remaining($product['discount_end_date']) : 'Ending soon';
            
            // For sample products, generate random values
            if (isset($show_sample) && $show_sample) {
                if ($discount == 0) $discount = rand(20, 60);
                if ($discounted_price == 0) $discounted_price = $product_price * (1 - $discount / 100);
                if ($time_left == 'Ending soon') $time_left = rand(1, 5) . ' days left';
            }
        ?>
        <div class="col-lg-3 col-md-6">
            <div class="product-card">
                <div class="discount-badge">-<?= $discount ?>% OFF</div>
                <div class="time-badge"><i class="fa-regular fa-clock"></i> <?= $time_left ?></div>
                
                <div class="product-img-container">
                    <?php if ($has_image): ?>
                        <img src="uploads/products/<?= sanitize($image) ?>" 
                             class="product-img" 
                             alt="<?= sanitize($product_name) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">
                            <i class="fa-solid fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="product-info">
                    <div class="product-category"><i class="fa-regular fa-folder"></i> <?= sanitize($category) ?></div>
                    
                    <div class="product-name">
                        <a href="product.php?id=<?= $product_id ?>"><?= sanitize($product_name) ?></a>
                    </div>
                    
                    <div class="product-seller">
                        <i class="fa-solid fa-store"></i> <?= sanitize($seller) ?>
                    </div>
                    
                    <div class="product-price">
                        <span class="current">KSH <?= number_format($discounted_price) ?></span>
                        <span class="original">KSH <?= number_format($product_price) ?></span>
                        <span class="save">Save <?= $discount ?>%</span>
                    </div>
                    
                    <div class="sold-info">
                        <!-- <span class="sold-count"><i class="fa-solid fa-fire"></i> <?= number_format($sold) ?> sold</span> -->
                        <span class="stock-count">🔥 <?= number_format($remaining) ?> left</span>
                    </div>
                    
                    <!-- <div class="progress-container">
                        <div class="progress-bar" style="width: <?= $progress ?>%;"></div>
                    </div> -->
                    
                    <a href="product.php?id=<?= $product_id ?>" class="btn-view">
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

<?php require_once 'includes/footer.php'; ?>