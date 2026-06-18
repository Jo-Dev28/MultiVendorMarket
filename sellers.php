<?php
$page_title = 'Top Sellers';
require_once 'includes/header.php';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get total sellers count
$count_sql = "SELECT COUNT(*) as total FROM sellers WHERE status = 'verified'";
$count_result = $mysqli->query($count_sql);
$total_sellers = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_sellers / $limit);

// Get sellers with their stats
$sellers_sql = "SELECT s.*, u.name as owner_name, u.email as owner_email,
                COUNT(DISTINCT p.id) as product_count,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(DISTINCT r.id) as review_count
                FROM sellers s
                JOIN users u ON u.id = s.user_id
                LEFT JOIN products p ON p.seller_id = s.id AND p.status = 'approved'
                LEFT JOIN reviews r ON r.product_id = p.id
                WHERE s.status = 'verified'
                GROUP BY s.id
                ORDER BY product_count DESC, avg_rating DESC
                LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sellers_sql);
$stmt->bind_param('ii', $limit, $offset);
$stmt->execute();
$sellers = $stmt->get_result();
?>

<style>
/* ============================================
   TOP SELLERS PAGE - CLEAN MODERN DESIGN
============================================ */

/* ---------- HERO SECTION ---------- */
.sellers-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 60px 0;
    border-radius: 0 0 30px 30px;
    margin-bottom: 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.sellers-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.sellers-hero h1 {
    color: #fff;
    font-size: 2.8rem;
    font-weight: 800;
    position: relative;
    z-index: 1;
}

.sellers-hero p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

/* ---------- BACK BUTTON ---------- */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    padding: 8px 20px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

/* ---------- AI BADGE ---------- */
.ai-badge {
    display: inline-block;
    background: rgba(37, 99, 235, 0.3);
    color: #60a5fa;
    padding: 4px 16px;
    border-radius: 50px;
    font-size: 0.8rem;
    font-weight: 600;
    border: 1px solid rgba(37, 99, 235, 0.3);
}

/* ---------- SELLER CARD ---------- */
.seller-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
    border: 1px solid #f1f5f9;
    height: 100%;
    position: relative;
    overflow: hidden;
}

.seller-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
    border-color: #2563eb;
}

/* Rank Badge */
.seller-card .rank-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
}

/* Avatar / Logo */
.seller-card .avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 3px solid #e5e7eb;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.seller-card:hover .avatar {
    border-color: #2563eb;
    transform: scale(1.05);
}

.seller-card .avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.seller-card .avatar .no-logo {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    color: #2563eb;
}

/* Shop Name */
.seller-card .shop-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
    margin-top: 8px;
}

/* Owner Name */
.seller-card .owner-name {
    color: #6b7280;
    font-size: 0.85rem;
    margin-bottom: 8px;
}

/* Rating */
.seller-card .rating {
    color: #f59e0b;
    font-size: 0.9rem;
    margin-bottom: 6px;
}

/* Stats */
.seller-card .stats {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 12px 0;
    padding: 12px 0;
    border-top: 1px solid #f1f5f9;
    border-bottom: 1px solid #f1f5f9;
}

.seller-card .stats .stat-item {
    text-align: center;
}

.seller-card .stats .stat-number {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2563eb;
}

.seller-card .stats .stat-label {
    font-size: 0.7rem;
    color: #6b7280;
}

/* Visit Button */
.seller-card .btn-visit {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
}

.seller-card .btn-visit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
    color: #fff;
}

/* ---------- EMPTY STATE ---------- */
.empty-state {
    text-align: center;
    padding: 60px;
    background: #fff;
    border-radius: 20px;
}

.empty-state i {
    font-size: 80px;
    color: #9ca3af;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: #1f2937;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6b7280;
    margin-bottom: 25px;
}

/* ---------- PAGINATION ---------- */
.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 30px;
    flex-wrap: wrap;
}

.pagination a,
.pagination span {
    padding: 8px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    text-decoration: none;
    color: #374151;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}

.pagination .active {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border-color: transparent;
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 768px) {
    .sellers-hero h1 {
        font-size: 2rem;
    }
    
    .seller-card .stats {
        flex-direction: column;
        gap: 8px;
    }
    
    .seller-card .avatar {
        width: 70px;
        height: 70px;
    }
}

@media (max-width: 480px) {
    .seller-card .avatar {
        width: 60px;
        height: 60px;
    }
    
    .seller-card .avatar .no-logo {
        font-size: 1.8rem;
    }
}
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="sellers-hero">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
            <a href="index.php" class="back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Home
            </a>
            <span class="ai-badge">
                <i class="fa-solid fa-store"></i> Trusted Sellers
            </span>
        </div>
        <h1>Top Sellers</h1>
        <p>Discover the most trusted and top-rated sellers on our platform.</p>
    </div>
</div>

<!-- ============================================
     SELLERS GRID
============================================ -->
<div class="container mb-5">
    <?php if ($sellers->num_rows === 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-store"></i>
            <h3>No Sellers Yet</h3>
            <p>Be the first to become a seller on our platform.</p>
            <a href="become-seller.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Become a Seller
            </a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php 
            $rank = $offset + 1;
            while ($seller = $sellers->fetch_assoc()): 
                $rating = round($seller['avg_rating'] ?? 0, 1);
                
                // Generate star rating
                $stars = '';
                for ($i = 1; $i <= 5; $i++) {
                    if ($i <= floor($rating)) {
                        $stars .= '<i class="fa-solid fa-star"></i>';
                    } elseif ($i - 0.5 <= $rating) {
                        $stars .= '<i class="fa-solid fa-star-half-stroke"></i>';
                    } else {
                        $stars .= '<i class="fa-regular fa-star"></i>';
                    }
                }
                
                // Check if logo exists
                $has_logo = !empty($seller['shop_logo']) && file_exists('uploads/sellers/' . $seller['shop_logo']);
            ?>
                <div class="col-lg-3 col-md-6">
                    <div class="seller-card">
                        <div class="rank-badge"><?= $rank ?></div>
                        
                        <div class="avatar">
                            <?php if ($has_logo): ?>
                                <img src="uploads/sellers/<?= $seller['shop_logo'] ?>" 
                                     alt="<?= sanitize($seller['shop_name']) ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <div class="no-logo">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="shop-name"><?= sanitize($seller['shop_name']) ?></div>
                        <div class="owner-name">
                            <i class="fa-regular fa-user"></i> <?= sanitize($seller['owner_name']) ?>
                        </div>
                        
                        <div class="rating">
                            <?= $stars ?> <?= number_format($rating, 1) ?>
                        </div>
                        
                        <div class="stats">
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($seller['product_count'] ?? 0) ?></div>
                                <div class="stat-label">Products</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number"><?= number_format($seller['review_count'] ?? 0) ?></div>
                                <div class="stat-label">Reviews</div>
                            </div>
                        </div>
                        
                        <a href="seller.php?id=<?= $seller['id'] ?>" class="btn-visit">
                            <i class="fa-regular fa-eye"></i> Visit Store
                        </a>
                    </div>
                </div>
            <?php 
                $rank++; 
            endwhile; 
            ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>">
                        <i class="fa-solid fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>">
                        Next <i class="fa-solid fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>