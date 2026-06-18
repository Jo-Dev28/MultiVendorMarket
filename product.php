<?php
$page_title = 'Product Details';
require_once 'includes/header.php';

$id = intval($_GET['id'] ?? 0);

// Get product details with seller info
$sql = 'SELECT p.*, c.name AS category_name, c.slug as category_slug, 
        s.shop_name, s.shop_logo, s.id as seller_id, s.location as seller_location,
        s.status AS seller_status, u.name as seller_owner
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN sellers s ON p.seller_id = s.id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE p.id = ? AND p.status = "approved" LIMIT 1';

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    flash('Product not found.', 'warning');
    redirect('shop.php');
}

// Get product images
$images = get_product_images($mysqli, $id);

// Get product reviews
$reviews_sql = "SELECT r.*, u.name as user_name 
                FROM reviews r
                JOIN users u ON u.id = r.user_id
                WHERE r.product_id = ? AND r.status = 'approved'
                ORDER BY r.created_at DESC LIMIT 5";
$stmt = $mysqli->prepare($reviews_sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$reviews = $stmt->get_result();

// Get rating average
$rating_sql = "SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_reviews 
               FROM reviews WHERE product_id = ? AND status = 'approved'";
$stmt = $mysqli->prepare($rating_sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$rating_data = $stmt->get_result()->fetch_assoc();

// Get related products
$related = $mysqli->prepare('SELECT id, name, price, short_description, stock 
                            FROM products 
                            WHERE category_id = ? AND id != ? AND status = "approved" 
                            LIMIT 4');
$related->bind_param('ii', $product['category_id'], $id);
$related->execute();
$relatedProducts = $related->get_result();

// Check if product is in wishlist
$in_wishlist = false;
$current_user = current_user();

if (isset($_SESSION['wishlist']) && in_array($id, $_SESSION['wishlist'])) {
    $in_wishlist = true;
}

if ($current_user['id'] && !$in_wishlist) {
    $wishlist_sql = "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($wishlist_sql);
    $stmt->bind_param('ii', $current_user['id'], $id);
    $stmt->execute();
    $in_wishlist = $stmt->get_result()->num_rows > 0;
}

// Check if product has discount
$has_discount = isset($product['is_on_sale']) && $product['is_on_sale'] == 1 && 
                $product['discount_percent'] > 0 && 
                !empty($product['discount_end_date']) && 
                $product['discount_end_date'] > date('Y-m-d H:i:s');
$discount_percent = $has_discount ? $product['discount_percent'] : 0;
$discounted_price = $has_discount ? ($product['discounted_price'] ?? $product['price'] * (1 - $discount_percent / 100)) : 0;
?>

<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary: #f59e0b;
        --success: #10b981;
        --danger: #ef4444;
        --dark: #1f2937;
        --gray: #6b7280;
        --light-gray: #f3f4f6;
        --white: #ffffff;
        --border: #e5e7eb;
    }
    
    .breadcrumb-custom {
        background: transparent;
        padding: 0;
        margin-bottom: 1.5rem;
    }
    
    .breadcrumb-custom a {
        color: var(--gray);
        text-decoration: none;
    }
    
    .breadcrumb-custom a:hover {
        color: var(--primary);
    }
    
    .product-gallery {
        background: var(--white);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }
    
    .main-image {
        width: 100%;
        height: 400px;
        object-fit: contain;
        border-radius: 12px;
        background: var(--light-gray);
        cursor: pointer;
    }
    
    .thumbnail-list {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }
    
    .thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 10px;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }
    
    .thumbnail.active {
        border-color: var(--primary);
    }
    
    .product-info {
        background: var(--white);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .product-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }
    
    .product-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border);
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        color: var(--gray);
    }
    
    .meta-item i {
        color: var(--secondary);
    }
    
    .stars {
        color: #ffc107;
        font-size: 0.9rem;
    }
    
    /* Price with Discount */
    .product-price-container {
        display: flex;
        align-items: center;
        gap: 15px;
        margin: 1rem 0;
        flex-wrap: wrap;
    }
    
    .product-price {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    .product-price.discounted {
        color: #ef4444;
    }
    
    .product-original-price {
        font-size: 1.2rem;
        color: #9ca3af;
        text-decoration: line-through;
    }
    
    .product-save-badge {
        font-size: 0.8rem;
        background: #d1fae5;
        color: #059669;
        padding: 4px 14px;
        border-radius: 50px;
        font-weight: 700;
    }
    
    .discount-badge-large {
        display: inline-block;
        background: #ef4444;
        color: white;
        padding: 6px 18px;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 700;
        animation: pulse 2s infinite;
        margin-bottom: 0.5rem;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    .stock-status {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
        margin-bottom: 1rem;
    }
    
    .stock-status.in-stock {
        background: #d1fae5;
        color: var(--success);
    }
    
    .stock-status.low-stock {
        background: #fed7aa;
        color: var(--secondary);
    }
    
    .stock-status.out-stock {
        background: #fee2e2;
        color: var(--danger);
    }
    
    .quantity-selector {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 1rem 0;
    }
    
    .quantity-btn {
        width: 35px;
        height: 35px;
        border: 1px solid var(--border);
        background: var(--white);
        border-radius: 10px;
        cursor: pointer;
    }
    
    .quantity-input {
        width: 60px;
        height: 35px;
        text-align: center;
        border: 1px solid var(--border);
        border-radius: 10px;
    }
    
    .action-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }
    
    .btn-add-cart {
        flex: 1;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        padding: 0.8rem;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-add-cart:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    }
    
    .btn-wishlist {
        padding: 0.8rem 1.5rem;
        border: 1px solid var(--border);
        background: var(--white);
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-wishlist.active {
        background: var(--danger);
        color: white;
        border-color: var(--danger);
    }
    
    .btn-wishlist.active i {
        color: white;
    }
    
    .btn-wishlist:hover {
        transform: translateY(-2px);
    }
    
    .btn-compare {
        padding: 0.8rem 1.5rem;
        border: 1px solid var(--border);
        background: var(--white);
        border-radius: 12px;
        cursor: pointer;
    }
    
    .seller-card {
        background: var(--white);
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .seller-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    
    .seller-avatar {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #60a5fa, #2563eb);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .seller-avatar i {
        font-size: 1.5rem;
        color: white;
    }
    
    .seller-name {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
    }
    
    .btn-contact-seller {
        width: 100%;
        background: var(--light-gray);
        border: none;
        padding: 0.6rem;
        border-radius: 10px;
        cursor: pointer;
    }
    
    .description-tabs {
        background: var(--white);
        border-radius: 20px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-top: 2rem;
    }
    
    .nav-tabs-custom {
        border-bottom: 2px solid var(--border);
        margin-bottom: 1.5rem;
        display: flex;
        gap: 0;
    }
    
    .nav-tabs-custom .nav-link {
        border: none;
        color: var(--gray);
        font-weight: 500;
        padding: 0.8rem 1.5rem;
        background: transparent;
        cursor: pointer;
    }
    
    .nav-tabs-custom .nav-link.active {
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .review-card {
        background: var(--light-gray);
        border-radius: 12px;
        padding: 1rem;
        margin-bottom: 1rem;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    
    .review-rating {
        color: #ffc107;
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
    }
    
    .related-section {
        margin-top: 2rem;
    }
    
    .related-product-card {
        background: var(--white);
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .related-product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .related-product-image {
        height: 150px;
        object-fit: cover;
        width: 100%;
        background: var(--light-gray);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .related-product-info {
        padding: 0.8rem;
    }
    
    .related-product-price {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary);
    }
    
    @media (max-width: 768px) {
        .product-title { font-size: 1.3rem; }
        .main-image { height: 250px; }
        .action-buttons { flex-wrap: wrap; }
        .product-price { font-size: 1.5rem; }
    }
</style>

<!-- Breadcrumb -->
<div class="container">
    <nav aria-label="breadcrumb" class="breadcrumb-custom">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <li class="breadcrumb-item"><a href="shop.php?category=<?= $product['category_slug'] ?>"><?= sanitize($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= sanitize($product['name']) ?></li>
        </ol>
    </nav>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Product Gallery -->
        <div class="col-lg-6">
            <div class="product-gallery">
                <?php 
                $first_image = null;
                $images_array = [];
                while ($img = $images->fetch_assoc()) {
                    $images_array[] = $img;
                }
                $first_image = !empty($images_array) ? $images_array[0] : null;
                ?>
                <img id="mainImage" src="uploads/products/<?= sanitize($first_image['filename'] ?? 'placeholder.png') ?>" 
                     class="main-image w-100" alt="<?= sanitize($product['name']) ?>"
                     onerror="this.src='uploads/placeholder.png'">
                
                <?php if (!empty($images_array)): ?>
                <div class="thumbnail-list">
                    <?php foreach ($images_array as $index => $img): ?>
                        <img src="uploads/products/<?= sanitize($img['filename']) ?>" 
                             class="thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                             alt="Thumbnail"
                             onerror="this.src='uploads/placeholder.png'"
                             onclick="changeImage(this, 'uploads/products/<?= sanitize($img['filename']) ?>')">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="product-info">
                <h1 class="product-title"><?= sanitize($product['name']) ?></h1>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <i class="fa-solid fa-store"></i>
                        <span><?= sanitize($product['shop_name']) ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fa-regular fa-folder"></i>
                        <span><?= sanitize($product['category_name']) ?></span>
                    </div>
                </div>
                
                <!-- Rating -->
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="stars">
                        <?php 
                        $avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
                        for($i = 1; $i <= 5; $i++): ?>
                            <?php if($i <= floor($avg_rating)): ?>
                                <i class="fa-solid fa-star"></i>
                            <?php elseif($i - 0.5 <= $avg_rating): ?>
                                <i class="fa-solid fa-star-half-stroke"></i>
                            <?php else: ?>
                                <i class="fa-regular fa-star"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <span>(<?= number_format($avg_rating, 1) ?>, <?= $rating_data['total_reviews'] ?> reviews)</span>
                </div>
                
                <!-- Price with Discount -->
                <?php if($has_discount): ?>
                    <div class="discount-badge-large">
                        <i class="fa-solid fa-bolt"></i> -<?= $discount_percent ?>% OFF
                    </div>
                    <div class="product-price-container">
                        <span class="product-price discounted">KSH <?= number_format($discounted_price) ?></span>
                        <span class="product-original-price">KSH <?= number_format($product['price']) ?></span>
                        <span class="product-save-badge">Save <?= $discount_percent ?>%</span>
                    </div>
                <?php else: ?>
                    <div class="product-price-container">
                        <span class="product-price">KSH <?= number_format($product['price']) ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Stock Status -->
                <?php if ($product['stock'] > 10): ?>
                    <div class="stock-status in-stock">
                        <i class="fa-solid fa-check-circle"></i> In Stock (<?= $product['stock'] ?> available)
                    </div>
                <?php elseif ($product['stock'] > 0): ?>
                    <div class="stock-status low-stock">
                        <i class="fa-solid fa-exclamation-triangle"></i> Low Stock (Only <?= $product['stock'] ?> left)
                    </div>
                <?php else: ?>
                    <div class="stock-status out-stock">
                        <i class="fa-solid fa-times-circle"></i> Out of Stock
                    </div>
                <?php endif; ?>
                
                <!-- Short Description -->
                <p class="text-muted"><?= nl2br(sanitize($product['short_description'])) ?></p>
                
                <!-- Quantity Selector -->
                <?php if ($product['stock'] > 0): ?>
                <div class="quantity-selector">
                    <button type="button" class="quantity-btn" onclick="updateQuantity(-1)">-</button>
                    <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?= $product['stock'] ?>">
                    <button type="button" class="quantity-btn" onclick="updateQuantity(1)">+</button>
                </div>
                <?php endif; ?>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <?php if ($product['stock'] > 0): ?>
                        <button type="button" class="btn-add-cart" onclick="addToCart(<?= $product['id'] ?>)">
                            <i class="fa-solid fa-cart-shopping me-2"></i> Add to Cart
                        </button>
                    <?php endif; ?>
                    <button type="button" class="btn-wishlist <?= $in_wishlist ? 'active' : '' ?>" id="wishlistBtn" onclick="toggleWishlist(<?= $product['id'] ?>)">
                        <i class="fa-<?= $in_wishlist ? 'solid' : 'regular' ?> fa-heart"></i> <span class="wishlist-text"><?= $in_wishlist ? 'Saved' : 'Wishlist' ?></span>
                    </button>
                    <button type="button" class="btn-compare" onclick="window.location.href='compare.php?add=<?= $product['id'] ?>'">
                        <i class="fa-solid fa-chart-simple"></i>
                    </button>
                </div>
            </div>
            
            <!-- Seller Card -->
            <div class="seller-card">
                <div class="seller-header">
                    <div class="seller-avatar">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div>
                        <h4 class="seller-name"><?= sanitize($product['shop_name']) ?></h4>
                        <small class="text-muted">Verified Seller</small>
                    </div>
                </div>
                <button class="btn-contact-seller" onclick="window.location.href='chat.php?seller=<?= $product['seller_id'] ?>'">
                    <i class="fa-regular fa-message me-2"></i> Contact Seller
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabs -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="description-tabs">
                <div class="nav-tabs-custom">
                    <button class="nav-link active" onclick="showTab('description', this)">Description</button>
                    <button class="nav-link" onclick="showTab('specs', this)">Specifications</button>
                    <button class="nav-link" onclick="showTab('reviews', this)">Reviews (<?= $rating_data['total_reviews'] ?>)</button>
                </div>
                
                <div id="description" class="tab-content active">
                    <?= nl2br(sanitize($product['description'] ?? $product['short_description'])) ?>
                </div>
                
                <div id="specs" class="tab-content" style="display:none;">
                    <table class="table table-bordered">
                        <tr><th style="width:200px;">Brand</th><td><?= sanitize($product['brand'] ?? 'Generic') ?></td></tr>
                        <tr><th>Category</th><td><?= sanitize($product['category_name']) ?></td></tr>
                        <tr><th>Stock Status</th><td><?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?></td></tr>
                        <tr><th>SKU</th><td>SKU-<?= str_pad($product['id'], 6, '0', STR_PAD_LEFT) ?></td></tr>
                        <tr><th>Seller</th><td><?= sanitize($product['shop_name']) ?></td></tr>
                    </table>
                </div>
                
                <div id="reviews" class="tab-content" style="display:none;">
                    <?php if ($reviews->num_rows > 0): ?>
                        <?php while ($review = $reviews->fetch_assoc()): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <strong><i class="fa-regular fa-user-circle"></i> <?= sanitize($review['user_name']) ?></strong>
                                    <small><?= date('M d, Y', strtotime($review['created_at'])) ?></small>
                                </div>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= $review['rating']): ?>
                                            <i class="fa-solid fa-star"></i>
                                        <?php else: ?>
                                            <i class="fa-regular fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <p class="mb-0"><?= sanitize($review['comment']) ?></p>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fa-regular fa-star fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No reviews yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Products -->
    <?php if ($relatedProducts->num_rows > 0): ?>
    <div class="related-section">
        <h3 class="mb-3">You May Also Like</h3>
        <div class="row g-4">
            <?php while ($related = $relatedProducts->fetch_assoc()): ?>
                <div class="col-md-3">
                    <div class="related-product-card" onclick="window.location.href='product.php?id=<?= $related['id'] ?>'">
                        <div class="related-product-image">
                            <?php 
                            $rel_img_sql = "SELECT filename FROM product_images WHERE product_id = {$related['id']} LIMIT 1";
                            $rel_img_result = $mysqli->query($rel_img_sql);
                            $rel_img = $rel_img_result ? $rel_img_result->fetch_assoc() : null;
                            ?>
                            <?php if ($rel_img && !empty($rel_img['filename'])): ?>
                                <img src="uploads/products/<?= $rel_img['filename'] ?>" 
                                     style="width:100%; height:150px; object-fit:cover;"
                                     alt="<?= sanitize($related['name']) ?>"
                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fa-solid fa-image fa-2x text-muted\'></i>'">
                            <?php else: ?>
                                <i class="fa-solid fa-image fa-2x text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="related-product-info">
                            <h5 class="related-product-name"><?= sanitize($related['name']) ?></h5>
                            <p class="related-product-price">KSH <?= number_format($related['price']) ?></p>
                            <small class="text-muted"><?= $related['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?></small>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Change main image
function changeImage(element, src) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    element.classList.add('active');
}

// Update quantity
function updateQuantity(change) {
    let quantity = document.getElementById('quantity');
    if (!quantity) return;
    let newValue = parseInt(quantity.value) + change;
    if (newValue < 1) newValue = 1;
    if (newValue > <?= $product['stock'] ?? 0 ?>) newValue = <?= $product['stock'] ?? 0 ?>;
    quantity.value = newValue;
}

// Add to cart
function addToCart(productId) {
    const quantity = document.getElementById('quantity') ? document.getElementById('quantity').value : 1;
    
    Swal.fire({
        title: 'Adding to cart...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('api/add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Added to Cart!',
                text: data.message,
                timer: 2000,
                showConfirmButton: false
            });
            updateCartCount();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong. Please try again.'
        });
    });
}

// Toggle wishlist
function toggleWishlist(productId) {
    const btn = document.getElementById('wishlistBtn');
    const icon = btn.querySelector('i');
    const textSpan = btn.querySelector('.wishlist-text');
    const isActive = btn.classList.contains('active');
    
    Swal.fire({
        title: 'Processing...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('api/toggle_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        
        if (data.success) {
            if (data.added) {
                btn.classList.add('active');
                icon.className = 'fa-solid fa-heart';
                if (textSpan) textSpan.textContent = 'Saved';
                Swal.fire({
                    icon: 'success',
                    title: 'Added to Wishlist!',
                    text: 'Product saved to your wishlist.',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                btn.classList.remove('active');
                icon.className = 'fa-regular fa-heart';
                if (textSpan) textSpan.textContent = 'Wishlist';
                Swal.fire({
                    icon: 'info',
                    title: 'Removed',
                    text: 'Product removed from wishlist.',
                    timer: 1500,
                    showConfirmButton: false
                });
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Login Required',
                text: data.message || 'Please login to manage wishlist.',
                showConfirmButton: true,
                confirmButtonText: 'Login Now'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php?redirect=product.php?id=' + productId;
                }
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Something went wrong.'
        });
    });
}

// Update cart count
function updateCartCount() {
    fetch('api/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count !== undefined) {
                document.querySelectorAll('.badge-count, #floatCartCount').forEach(el => {
                    if (el) el.textContent = data.count;
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

// Tab functionality
function showTab(tabName, element) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
    });
    document.getElementById(tabName).style.display = 'block';
    
    document.querySelectorAll('.nav-link').forEach(btn => {
        btn.classList.remove('active');
    });
    element.classList.add('active');
}

// Image zoom
document.getElementById('mainImage')?.addEventListener('click', function() {
    Swal.fire({
        imageUrl: this.src,
        imageAlt: 'Product Image',
        imageWidth: '80%',
        imageHeight: 'auto',
        showCloseButton: true,
        showConfirmButton: false
    });
});

// Initialize
$(document).ready(function() {
    updateCartCount();
});
</script>

<?php require_once 'includes/footer.php'; ?>