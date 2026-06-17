<?php
$page_title = 'My Wishlist';
require_once 'includes/header.php';
require_login();

$userId = $_SESSION['user_id'];

// Handle remove from wishlist
if (isset($_GET['remove'])) {
    $productId = intval($_GET['remove']);
    
    $stmt = $mysqli->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?');
    $stmt->bind_param('ii', $userId, $productId);
    $stmt->execute();
    
    // Also remove from session
    if (isset($_SESSION['wishlist'])) {
        $key = array_search($productId, $_SESSION['wishlist']);
        if ($key !== false) {
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
        }
    }
    
    flash('Item removed from wishlist.', 'success');
    redirect('wishlist.php');
}

// Handle clear all
if (isset($_GET['clear'])) {
    $stmt = $mysqli->prepare('DELETE FROM wishlists WHERE user_id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $_SESSION['wishlist'] = [];
    flash('Wishlist cleared.', 'success');
    redirect('wishlist.php');
}

// Get wishlist items
$stmt = $mysqli->prepare('SELECT w.id, w.created_at, p.id AS product_id, p.name, p.price, p.slug, p.stock, p.rating,
                          (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
                          FROM wishlists w 
                          JOIN products p ON w.product_id = p.id 
                          WHERE w.user_id = ? 
                          ORDER BY w.created_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$wishlist = $stmt->get_result();
?>

<style>
    .wishlist-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        height: 100%;
    }
    
    .wishlist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
    }
    
    .product-image {
        height: 200px;
        width: 100%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    
    .product-image img {
        height: 200px;
        width: 100%;
        object-fit: cover;
    }
    
    .product-image .placeholder-icon {
        color: #9ca3af;
        font-size: 3rem;
    }
    
    .product-info {
        padding: 1rem;
    }
    
    .product-title {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.8rem;
    }
    
    .product-title a {
        color: #1f2937;
        text-decoration: none;
    }
    
    .product-title a:hover {
        color: #2563eb;
    }
    
    .product-price {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2563eb;
        margin-bottom: 0.5rem;
    }
    
    .stock-status {
        font-size: 0.85rem;
    }
    
    .stock-status.in-stock {
        color: #10b981;
    }
    
    .stock-status.out-stock {
        color: #ef4444;
    }
    
    .wishlist-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }
    
    .btn-view {
        flex: 1;
        padding: 0.5rem;
        border: 1px solid #e5e7eb;
        background: white;
        border-radius: 10px;
        text-align: center;
        text-decoration: none;
        color: #1f2937;
        font-size: 0.8rem;
        transition: all 0.3s ease;
    }
    
    .btn-view:hover {
        border-color: #2563eb;
        color: #2563eb;
    }
    
    .btn-remove {
        padding: 0.5rem 1rem;
        background: #fee2e2;
        color: #ef4444;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.8rem;
    }
    
    .btn-remove:hover {
        background: #ef4444;
        color: white;
    }
    
    .empty-wishlist {
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 20px;
    }
    
    .empty-wishlist i {
        font-size: 80px;
        color: #9ca3af;
        margin-bottom: 20px;
    }
    
    .empty-wishlist h3 {
        font-size: 1.5rem;
        color: #1f2937;
        margin-bottom: 10px;
    }
    
    .empty-wishlist p {
        color: #6b7280;
        margin-bottom: 25px;
    }
    
    .shop-now-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 12px 30px;
        border-radius: 10px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
    }
    
    .shop-now-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        color: white;
    }
    
    .wishlist-header-bar {
        background: white;
        border-radius: 16px;
        padding: 15px 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .wishlist-header-bar .count-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .wishlist-header-bar .count-info i {
        color: #ef4444;
        font-size: 1.2rem;
    }
    
    .clear-wishlist-btn {
        background: #fee2e2;
        color: #ef4444;
        border: none;
        padding: 8px 20px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .clear-wishlist-btn:hover {
        background: #ef4444;
        color: white;
    }
    
    @media (max-width: 768px) {
        .wishlist-header-bar {
            flex-direction: column;
            align-items: flex-start;
        }
        .empty-wishlist {
            padding: 30px;
        }
        .empty-wishlist i {
            font-size: 50px;
        }
    }
</style>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Wishlist Main Content -->
        <div class="col-lg-9">
            <?php if ($wishlist->num_rows === 0): ?>
                <div class="empty-wishlist">
                    <i class="fa-regular fa-heart"></i>
                    <h3>Your wishlist is empty</h3>
                    <p>Save your favorite products here to buy them later.</p>
                    <a href="<?= BASE_URL ?>shop.php" class="shop-now-btn">
                        <i class="fa-solid fa-store"></i> Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <div class="wishlist-header-bar">
                    <div class="count-info">
                        <i class="fa-solid fa-heart"></i>
                        <strong><?= $wishlist->num_rows ?></strong> items in your wishlist
                    </div>
                    <button class="clear-wishlist-btn" onclick="clearWishlist()">
                        <i class="fa-solid fa-trash-can"></i> Clear All
                    </button>
                </div>
                
                <div class="row g-4">
                    <?php while ($item = $wishlist->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="wishlist-card">
                                <div class="product-image">
                                    <?php if ($item['image'] && !empty($item['image'])): ?>
                                        <!-- FIXED: Changed from assets/uploads/ to uploads/products/ -->
                                        <img src="uploads/products/<?= sanitize($item['image']) ?>" 
                                             alt="<?= sanitize($item['name']) ?>"
                                             onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fa-solid fa-image placeholder-icon\'></i>'">
                                    <?php else: ?>
                                        <i class="fa-solid fa-image placeholder-icon"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h5 class="product-title">
                                        <a href="<?= BASE_URL ?>product.php?id=<?= $item['product_id'] ?>"><?= sanitize($item['name']) ?></a>
                                    </h5>
                                    <div class="product-price">KSH <?= number_format($item['price']) ?></div>
                                    <div class="stock-status <?= $item['stock'] > 0 ? 'in-stock' : 'out-stock' ?>">
                                        <i class="fa-<?= $item['stock'] > 0 ? 'solid fa-check-circle' : 'solid fa-times-circle' ?>"></i>
                                        <?= $item['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
                                    </div>
                                    <div class="wishlist-actions">
                                        <a href="<?= BASE_URL ?>product.php?id=<?= $item['product_id'] ?>" class="btn-view">
                                            <i class="fa-regular fa-eye"></i> View
                                        </a>
                                        <button class="btn-remove" onclick="removeFromWishlist(<?= $item['product_id'] ?>)">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function removeFromWishlist(productId) {
    Swal.fire({
        title: 'Remove Item?',
        text: 'Are you sure you want to remove this item from your wishlist?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, remove',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= BASE_URL ?>wishlist.php?remove=' + productId;
        }
    });
}

function clearWishlist() {
    Swal.fire({
        title: 'Clear Wishlist?',
        text: 'Are you sure you want to remove all items from your wishlist?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, clear all',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= BASE_URL ?>wishlist.php?clear=1';
        }
    });
}

// Auto-hide flash messages
$(document).ready(function() {
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>

<?php require_once 'includes/footer.php'; ?>