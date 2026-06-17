<?php
$page_title = 'Compare Products';
require_once 'includes/header.php';

// Handle adding products to comparison
if (isset($_GET['add'])) {
    $id = intval($_GET['add']);
    $comparison = $_SESSION['compare'] ?? [];
    
    if (!in_array($id, $comparison, true)) {
        if (count($comparison) >= 4) {
            flash('You can compare up to 4 products at a time. Remove a product first.', 'warning');
        } else {
            $comparison[] = $id;
            $_SESSION['compare'] = $comparison;
            flash('Product added to comparison.', 'success');
        }
    } else {
        flash('Product is already in comparison list.', 'info');
    }
    redirect('compare.php');
}

// Handle removing products
if (isset($_GET['remove'])) {
    $id = intval($_GET['remove']);
    $comparison = $_SESSION['compare'] ?? [];
    $comparison = array_filter($comparison, function($item) use ($id) {
        return $item != $id;
    });
    $_SESSION['compare'] = array_values($comparison);
    flash('Product removed from comparison.', 'success');
    redirect('compare.php');
}

// Handle clearing all
if (isset($_GET['clear'])) {
    $_SESSION['compare'] = [];
    flash('Comparison list cleared.', 'success');
    redirect('compare.php');
}

// Get products for comparison
$products = [];
$comparison = $_SESSION['compare'] ?? [];

if (!empty($comparison)) {
    $placeholders = implode(',', array_fill(0, count($comparison), '?'));
    $sql = "SELECT p.*, c.name AS category_name, c.slug as category_slug, 
            s.shop_name, s.shop_logo, s.id as seller_id,
            (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            WHERE p.id IN ($placeholders) AND p.status = 'approved'";
    
    $stmt = $mysqli->prepare($sql);
    $types = str_repeat('i', count($comparison));
    $stmt->bind_param($types, ...$comparison);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
}

// Get AI summary
$ai_summary = !empty($products) ? ai_compare_products($products) : '';
?>

<style>
    /* Color Variables */
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --primary-light: #60a5fa;
        --secondary: #f59e0b;
        --success: #10b981;
        --danger: #ef4444;
        --dark: #1f2937;
        --gray: #6b7280;
        --light-gray: #f3f4f6;
        --white: #ffffff;
        --border: #e5e7eb;
    }
    
    /* Page Header */
    .page-header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 20px 20px;
        position: relative;
        overflow: hidden;
    }
    
    .page-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
        background-size: cover;
        opacity: 0.3;
    }
    
    .page-title {
        color: white;
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    .page-subtitle {
        color: rgba(255,255,255,0.9);
        margin: 0;
        position: relative;
        z-index: 1;
    }
    
    /* Comparison Controls */
    .compare-controls {
        background: var(--white);
        border-radius: 16px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .compare-info {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .compare-badge {
        background: var(--secondary);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .clear-btn {
        color: var(--danger);
        text-decoration: none;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .clear-btn:hover {
        color: #dc2626;
        transform: scale(1.05);
    }
    
    .add-more-btn {
        background: var(--light-gray);
        color: var(--dark);
        padding: 0.5rem 1rem;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .add-more-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateY(-2px);
    }
    
    /* Comparison Table */
    .comparison-wrapper {
        background: var(--white);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        margin-bottom: 2rem;
    }
    
    .comparison-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .comparison-table th,
    .comparison-table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border);
        vertical-align: top;
    }
    
    .comparison-table th {
        background: var(--light-gray);
        font-weight: 600;
        color: var(--dark);
        width: 180px;
    }
    
    .comparison-table td {
        background: var(--white);
    }
    
    /* Product Card in Table */
    .product-compare-card {
        text-align: center;
    }
    
    .product-image-wrapper {
        position: relative;
        margin-bottom: 1rem;
    }
    
    .product-compare-image {
        width: 100%;
        max-height: 180px;
        object-fit: cover;
        border-radius: 12px;
    }
    
    .remove-product {
        position: absolute;
        top: -10px;
        right: -10px;
        background: var(--danger);
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .remove-product:hover {
        transform: scale(1.1);
        background: #dc2626;
    }
    
    .product-compare-name {
        font-size: 1rem;
        font-weight: 600;
        margin: 0.5rem 0;
        color: var(--dark);
    }
    
    .product-compare-name a {
        color: inherit;
        text-decoration: none;
    }
    
    .product-compare-name a:hover {
        color: var(--primary);
    }
    
    .product-compare-category {
        font-size: 0.75rem;
        color: var(--gray);
        margin-bottom: 0.5rem;
    }
    
    .product-compare-price {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--primary);
        margin: 0.5rem 0;
    }
    
    .product-compare-seller {
        font-size: 0.8rem;
        color: var(--gray);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }
    
    /* Rating Stars */
    .rating-stars {
        display: inline-flex;
        gap: 2px;
        color: #ffc107;
        font-size: 0.8rem;
    }
    
    /* Stock Badge */
    .stock-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    
    .stock-badge.in-stock {
        background: #d1fae5;
        color: var(--success);
    }
    
    .stock-badge.low-stock {
        background: #fed7aa;
        color: var(--secondary);
    }
    
    .stock-badge.out-stock {
        background: #fee2e2;
        color: var(--danger);
    }
    
    /* Feature Highlights */
    .feature-highlight {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }
    
    .feature-tag {
        background: var(--light-gray);
        padding: 0.2rem 0.6rem;
        border-radius: 15px;
        font-size: 0.7rem;
        color: var(--gray);
    }
    
    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin-top: 1rem;
    }
    
    .btn-view-product {
        padding: 0.4rem 1rem;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.8rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.3s ease;
    }
    
    .btn-view-product:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    }
    
    .btn-cart {
        padding: 0.4rem 1rem;
        background: var(--secondary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .btn-cart:hover {
        background: var(--secondary-dark);
        transform: translateY(-2px);
    }
    
    /* AI Recommendation Box */
    .ai-recommendation {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 20px;
        padding: 1.5rem;
        margin-top: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .ai-recommendation::before {
        content: '🤖';
        position: absolute;
        bottom: 10px;
        right: 20px;
        font-size: 5rem;
        opacity: 0.1;
    }
    
    .ai-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        background: var(--primary);
        border-radius: 50%;
        margin-bottom: 1rem;
    }
    
    .ai-icon i {
        font-size: 1.5rem;
        color: white;
    }
    
    .ai-title {
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 0.5rem;
    }
    
    .ai-summary {
        color: var(--dark);
        line-height: 1.6;
        white-space: pre-line;
    }
    
    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 4rem;
        background: var(--white);
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .empty-state i {
        font-size: 4rem;
        color: var(--gray);
        margin-bottom: 1rem;
    }
    
    .empty-state h3 {
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
        color: var(--dark);
    }
    
    .empty-state p {
        color: var(--gray);
        margin-bottom: 1.5rem;
    }
    
    .shop-now-btn {
        padding: 0.75rem 2rem;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border: none;
        border-radius: 10px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .shop-now-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
    }
    
    /* Winner Badge */
    .winner-badge {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-block;
        margin-top: 0.5rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .comparison-table {
            display: block;
            overflow-x: auto;
        }
        
        .comparison-table th {
            width: 120px;
        }
        
        .compare-controls {
            flex-direction: column;
            text-align: center;
        }
        
        .page-title {
            font-size: 1.5rem;
        }
    }
</style>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <h1 class="page-title">Compare Products</h1>
        <p class="page-subtitle">Compare features, prices, and specifications side by side</p>
    </div>
</div>

<div class="container mb-5">
    <?php if (empty($products)): ?>
        <!-- Empty State -->
        <div class="empty-state">
            <i class="fa-solid fa-chart-simple"></i>
            <h3>No Products to Compare</h3>
            <p>Add products to comparison by clicking the "Compare" button on any product.</p>
            <a href="shop.php" class="shop-now-btn">
                <i class="fa-solid fa-shop"></i> Browse Products
            </a>
        </div>
    <?php else: ?>
        <!-- Compare Controls -->
        <div class="compare-controls">
            <div class="compare-info">
                <span class="compare-badge">
                    <i class="fa-solid fa-chart-line"></i> Comparing <?= count($products) ?> product(s)
                </span>
                <span class="text-muted small">
                    <i class="fa-regular fa-lightbulb"></i> Max 4 products
                </span>
            </div>
            <div>
                <a href="?clear=1" class="clear-btn" onclick="return confirm('Clear all products from comparison?')">
                    <i class="fa-solid fa-trash-can"></i> Clear All
                </a>
                <?php if (count($products) < 4): ?>
                    <a href="shop.php" class="add-more-btn ms-2">
                        <i class="fa-solid fa-plus"></i> Add More Products
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Comparison Table -->
        <div class="comparison-wrapper">
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Product Details</th>
                        <?php foreach ($products as $index => $product): ?>
                            <td class="product-compare-card">
                                <div class="product-image-wrapper">
                                    <img src="assets/uploads/<?= sanitize($product['image'] ?? 'placeholder.png') ?>" 
                                         class="product-compare-image" 
                                         alt="<?= sanitize($product['name']) ?>">
                                    <a href="?remove=<?= $product['id'] ?>" class="remove-product" onclick="return confirm('Remove this product?')">
                                        <i class="fa-solid fa-times"></i>
                                    </a>
                                </div>
                                <div class="product-compare-name">
                                    <a href="product.php?id=<?= $product['id'] ?>"><?= sanitize($product['name']) ?></a>
                                </div>
                                <div class="product-compare-category">
                                    <i class="fa-regular fa-folder"></i> <?= sanitize($product['category_name'] ?? 'Uncategorized') ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <!-- Price -->
                    <tr>
                        <th><i class="fa-solid fa-tag"></i> Price</th>
                        <?php foreach ($products as $product): ?>
                            <td class="product-compare-price">
                                KSH <?= number_format($product['price']) ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Seller -->
                    <tr>
                        <th><i class="fa-solid fa-store"></i> Seller</th>
                        <?php foreach ($products as $product): ?>
                            <td class="product-compare-seller">
                                <i class="fa-solid fa-shop"></i> <?= sanitize($product['shop_name'] ?? 'Unknown Seller') ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Rating -->
                    <tr>
                        <th><i class="fa-solid fa-star"></i> Rating</th>
                        <?php foreach ($products as $product): ?>
                            <td>
                                <div class="rating-stars">
                                    <?php 
                                    $rating = round($product['rating'] ?? 0, 1);
                                    for($i = 1; $i <= 5; $i++): ?>
                                        <?php if($i <= floor($rating)): ?>
                                            <i class="fa-solid fa-star"></i>
                                        <?php elseif($i - 0.5 <= $rating): ?>
                                            <i class="fa-solid fa-star-half-stroke"></i>
                                        <?php else: ?>
                                            <i class="fa-regular fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-muted small">(<?= number_format($rating, 1) ?>)</span>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Stock Status -->
                    <tr>
                        <th><i class="fa-solid fa-box"></i> Stock Status</th>
                        <?php foreach ($products as $product): ?>
                            <td>
                                <?php if ($product['stock'] > 10): ?>
                                    <span class="stock-badge in-stock">
                                        <i class="fa-solid fa-check"></i> In Stock (<?= $product['stock'] ?>)
                                    </span>
                                <?php elseif ($product['stock'] > 0): ?>
                                    <span class="stock-badge low-stock">
                                        <i class="fa-solid fa-exclamation-triangle"></i> Low Stock (<?= $product['stock'] ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="stock-badge out-stock">
                                        <i class="fa-solid fa-xmark"></i> Out of Stock
                                    </span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Description -->
                    <tr>
                        <th><i class="fa-solid fa-align-left"></i> Description</th>
                        <?php foreach ($products as $product): ?>
                            <td><?= sanitize($product['short_description'] ?? 'No description available') ?></td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Features -->
                    <tr>
                        <th><i class="fa-solid fa-list-check"></i> Key Features</th>
                        <?php foreach ($products as $product): ?>
                            <td>
                                <div class="feature-highlight">
                                    <?php 
                                    $features = explode(',', $product['brand'] ?? '');
                                    if (empty($features[0])) {
                                        $features = ['Quality Product', 'Best Seller', 'Verified Seller'];
                                    }
                                    foreach(array_slice($features, 0, 3) as $feature): 
                                    ?>
                                        <span class="feature-tag">
                                            <i class="fa-solid fa-check"></i> <?= trim($feature) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- Actions -->
                    <tr>
                        <th><i class="fa-solid fa-cart-shopping"></i> Actions</th>
                        <?php foreach ($products as $product): ?>
                            <td>
                                <div class="action-buttons">
                                    <a href="product.php?id=<?= $product['id'] ?>" class="btn-view-product">
                                        <i class="fa-regular fa-eye"></i> View
                                    </a>
                                    <?php if($product['stock'] > 0): ?>
                                        <button onclick="addToCart(<?= $product['id'] ?>)" class="btn-cart">
                                            <i class="fa-solid fa-cart-plus"></i> Cart
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- AI Recommendation -->
        <?php if ($ai_summary): ?>
            <div class="ai-recommendation">
                <div class="ai-icon">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <h3 class="ai-title">
                    <i class="fa-regular fa-lightbulb"></i> AI Shopping Assistant
                </h3>
                <div class="ai-summary">
                    <?= nl2br(sanitize($ai_summary)) ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recommendation Note -->
        <div class="text-center mt-4">
            <small class="text-muted">
                <i class="fa-regular fa-circle-info"></i> 
                The AI recommendation is based on price, rating, stock availability, and seller reputation.
            </small>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function addToCart(productId) {
    $.ajax({
        url: 'api/add_to_cart.php',
        method: 'POST',
        data: { product_id: productId, quantity: 1 },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Added to Cart!',
                        text: 'Product has been added to your cart.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    updateCartCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Failed to add product to cart.'
                    });
                }
            } catch(e) {
                console.error('Error:', e);
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please login to add items to cart.'
            });
        }
    });
}

function updateCartCount() {
    $.ajax({
        url: 'api/get_cart_count.php',
        method: 'GET',
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.count) {
                    $('.badge-count').text(result.count);
                }
            } catch(e) {
                console.error('Error:', e);
            }
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>