<?php
$page_title = 'Product Reviews & Ratings';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller_result = $seller_stmt->get_result();
$seller = $seller_result->fetch_assoc();
$seller_stmt->close();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

$seller_id = $seller['id'];

// Handle review status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = intval($_GET['id']);
    $action = $_GET['action'];
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('seller/reviews.php');
    }
    
    if ($action === 'approve') {
        $sql = "UPDATE reviews SET status = 'approved' WHERE id = ? AND product_id IN (SELECT id FROM products WHERE seller_id = ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $review_id, $seller_id);
        if ($stmt->execute()) {
            flash('Review approved successfully.', 'success');
        } else {
            flash('Failed to approve review.', 'danger');
        }
        $stmt->close();
    } elseif ($action === 'reject') {
        $sql = "UPDATE reviews SET status = 'rejected' WHERE id = ? AND product_id IN (SELECT id FROM products WHERE seller_id = ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $review_id, $seller_id);
        if ($stmt->execute()) {
            flash('Review rejected.', 'success');
        } else {
            flash('Failed to reject review.', 'danger');
        }
        $stmt->close();
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM reviews WHERE id = ? AND product_id IN (SELECT id FROM products WHERE seller_id = ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $review_id, $seller_id);
        if ($stmt->execute()) {
            flash('Review deleted.', 'success');
        } else {
            flash('Failed to delete review.', 'danger');
        }
        $stmt->close();
    }
    redirect('seller/reviews.php');
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build where clause
$where_clauses = ["p.seller_id = ?"];
$params = [$seller_id];
$types = 'i';

if (!empty($status_filter)) {
    $where_clauses[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($rating_filter > 0) {
    $where_clauses[] = "r.rating = ?";
    $params[] = $rating_filter;
    $types .= 'i';
}

if (!empty($search)) {
    $where_clauses[] = "(p.name LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Get total reviews count
$count_sql = "SELECT COUNT(*) as total FROM reviews r
              JOIN products p ON p.id = r.product_id
              JOIN users u ON u.id = r.user_id
              $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $limit);
$count_stmt->close();

// Get reviews
$sql = "SELECT r.*, p.name as product_name, p.id as product_id, 
        u.name as customer_name, u.email as customer_email
        FROM reviews r
        JOIN products p ON p.id = r.product_id
        JOIN users u ON u.id = r.user_id
        $where_sql
        ORDER BY r.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$reviews = $stmt->get_result();
$stmt->close();

// Get statistics
$stats_sql = "SELECT 
                COUNT(*) as total_reviews,
                AVG(r.rating) as avg_rating,
                SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
              FROM reviews r
              JOIN products p ON p.id = r.product_id
              WHERE p.seller_id = ?";
$stats_stmt = $mysqli->prepare($stats_sql);
$stats_stmt->bind_param('i', $seller_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
$stats_stmt->close();

$avg_rating = round($stats['avg_rating'] ?? 0, 1);
$total_reviews_count = $stats['total_reviews'] ?? 0;
$pending_count = $stats['pending_count'] ?? 0;
$approved_count = $stats['approved_count'] ?? 0;
$rejected_count = $stats['rejected_count'] ?? 0;

// Get top rated products
$top_products_sql = "SELECT p.id, p.name, AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                     FROM products p
                     LEFT JOIN reviews r ON r.product_id = p.id AND r.status = 'approved'
                     WHERE p.seller_id = ?
                     GROUP BY p.id
                     HAVING review_count > 0
                     ORDER BY avg_rating DESC
                     LIMIT 5";
$top_products_stmt = $mysqli->prepare($top_products_sql);
$top_products_stmt->bind_param('i', $seller_id);
$top_products_stmt->execute();
$top_products = $top_products_stmt->get_result();
$top_products_stmt->close();
?>

<style>
    .reviews-wrapper {
        display: flex;
        gap: 25px;
        min-height: calc(100vh - 200px);
    }
    .reviews-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .reviews-content {
        flex: 1;
    }
    
    .filter-bar {
        background: white;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 18px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .stat-card .number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
    }
    
    .stat-card .label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 4px;
    }
    
    .stat-card .icon {
        font-size: 1.3rem;
        margin-bottom: 6px;
    }
    
    .stat-card.avg .icon { color: #f59e0b; }
    .stat-card.total .icon { color: #2563eb; }
    .stat-card.pending .icon { color: #f59e0b; }
    .stat-card.approved .icon { color: #10b981; }
    .stat-card.rejected .icon { color: #ef4444; }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }
    
    .data-table th,
    .data-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }
    
    .data-table th {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.8rem;
        color: #4b5563;
    }
    
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .star-display {
        display: inline-flex;
        gap: 2px;
    }
    
    .star-display .star {
        color: #d1d5db;
        font-size: 0.9rem;
    }
    
    .star-display .star.active {
        color: #f59e0b;
    }
    
    .rating-value {
        font-weight: 700;
        color: #1f2937;
        margin-right: 4px;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
    }
    
    .status-badge.pending { background: #fef3c7; color: #d97706; }
    .status-badge.approved { background: #d1fae5; color: #059669; }
    .status-badge.rejected { background: #fee2e2; color: #dc2626; }
    
    .btn-sm {
        padding: 4px 10px;
        font-size: 0.7rem;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: all 0.3s ease;
    }
    
    .btn-sm:hover {
        transform: translateY(-1px);
    }
    
    .btn-sm.btn-success { background: #10b981; color: white; }
    .btn-sm.btn-danger { background: #ef4444; color: white; }
    .btn-sm.btn-warning { background: #f59e0b; color: white; }
    .btn-sm.btn-secondary { background: #6b7280; color: white; }
    .btn-sm.btn-primary { background: #2563eb; color: white; }
    .btn-sm.btn-info { background: #3b82f6; color: white; }
    
    .search-input {
        padding: 8px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.85rem;
        outline: none;
        width: 200px;
        transition: all 0.3s ease;
    }
    
    .search-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    
    .filter-select {
        padding: 8px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.85rem;
        outline: none;
        background: white;
        transition: all 0.3s ease;
    }
    
    .filter-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    
    .top-product-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .top-product-item:last-child {
        border-bottom: none;
    }
    
    .top-product-item .rank {
        font-weight: 700;
        color: #2563eb;
        margin-right: 10px;
        font-size: 0.9rem;
    }
    
    .review-comment {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    
    .pagination a, .pagination span {
        padding: 6px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        text-decoration: none;
        color: #374151;
        transition: all 0.3s ease;
        font-size: 0.85rem;
    }
    
    .pagination a:hover {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }
    
    .pagination .active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border-color: transparent;
    }
    
    /* Modal Styles */
    .modal-content {
        border-radius: 16px;
        border: none;
        overflow: hidden;
    }
    
    .modal-header {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 18px 25px;
        border: none;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .modal-body {
        padding: 25px;
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .review-detail-item {
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .review-detail-item:last-child {
        border-bottom: none;
    }
    
    .review-detail-item .label {
        font-weight: 600;
        color: #6b7280;
        font-size: 0.8rem;
        display: block;
        margin-bottom: 2px;
    }
    
    .review-detail-item .value {
        color: #1f2937;
        font-size: 0.95rem;
    }
    
    .review-detail-item .value .stars {
        font-size: 1.2rem;
    }
    
    @media (max-width: 992px) {
        .reviews-wrapper {
            flex-direction: column;
        }
        .reviews-sidebar {
            width: 100%;
        }
        .stats-row {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    
    @media (max-width: 768px) {
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .filter-bar .d-flex {
            flex-direction: column;
            gap: 10px;
        }
        .search-input {
            width: 100%;
        }
        .data-table {
            font-size: 0.8rem;
        }
        .data-table th, .data-table td {
            padding: 8px 10px;
        }
    }
    
    @media (max-width: 480px) {
        .stats-row {
            grid-template-columns: 1fr 1fr;
        }
        .data-table {
            font-size: 0.7rem;
        }
        .data-table th, .data-table td {
            padding: 6px 8px;
        }
    }
</style>

<div class="container-fluid">
    <div class="reviews-wrapper">
        <div class="reviews-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="reviews-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2><i class="fa-solid fa-star"></i> Product Reviews & Ratings</h2>
                <?php if ($pending_count > 0): ?>
                    <span class="badge" style="background:#ef4444; color:white; padding:6px 16px; border-radius:50px; font-size:0.85rem;">
                        <i class="fa-regular fa-clock"></i> <?= $pending_count ?> Pending Reviews
                    </span>
                <?php endif; ?>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card avg">
                    <div class="icon"><i class="fa-solid fa-star"></i></div>
                    <div class="number"><?= $avg_rating ?></div>
                    <div class="label">Average Rating</div>
                    <?php if ($total_reviews_count > 0): ?>
                        <div style="font-size:0.65rem; color:#6b7280;">
                            <?= $total_reviews_count ?> reviews
                        </div>
                    <?php endif; ?>
                </div>
                <div class="stat-card total">
                    <div class="icon"><i class="fa-regular fa-star"></i></div>
                    <div class="number"><?= $total_reviews_count ?></div>
                    <div class="label">Total Reviews</div>
                </div>
                <div class="stat-card pending">
                    <div class="icon"><i class="fa-regular fa-clock"></i></div>
                    <div class="number"><?= $pending_count ?></div>
                    <div class="label">Pending</div>
                </div>
                <div class="stat-card approved">
                    <div class="icon"><i class="fa-regular fa-circle-check"></i></div>
                    <div class="number"><?= $approved_count ?></div>
                    <div class="label">Approved</div>
                </div>
                <div class="stat-card rejected">
                    <div class="icon"><i class="fa-solid fa-circle-xmark"></i></div>
                    <div class="number"><?= $rejected_count ?></div>
                    <div class="label">Rejected</div>
                </div>
            </div>
            
            <!-- Top Rated Products -->
            <?php if ($top_products && $top_products->num_rows > 0): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0"><i class="fa-solid fa-trophy" style="color:#f59e0b;"></i> Top Rated Products</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Rating</th>
                                    <th>Reviews</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                                    <tr>
                                        <td style="font-weight:700; color:#2563eb;"><?= $rank++ ?></td>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td>
                                            <div class="star-display">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?= $i <= round($product['avg_rating']) ? 'active' : '' ?>">
                                                        <i class="fa-solid fa-star"></i>
                                                    </span>
                                                <?php endfor; ?>
                                                <span style="margin-left:8px; font-weight:600; color:#1f2937;">
                                                    <?= number_format($product['avg_rating'], 1) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td><?= $product['review_count'] ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                        <input type="text" name="search" class="search-input" placeholder="Search product or customer..." value="<?= htmlspecialchars($search) ?>">
                        <select name="status" class="filter-select">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                        <select name="rating" class="filter-select">
                            <option value="0">All Ratings</option>
                            <option value="5" <?= $rating_filter == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (5)</option>
                            <option value="4" <?= $rating_filter == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ (4)</option>
                            <option value="3" <?= $rating_filter == 3 ? 'selected' : '' ?>>⭐⭐⭐ (3)</option>
                            <option value="2" <?= $rating_filter == 2 ? 'selected' : '' ?>>⭐⭐ (2)</option>
                            <option value="1" <?= $rating_filter == 1 ? 'selected' : '' ?>>⭐ (1)</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                        <?php if ($search || $status_filter || $rating_filter): ?>
                            <a href="reviews.php" class="btn btn-secondary btn-sm">Clear</a>
                        <?php endif; ?>
                    </form>
                    <span class="text-muted">Total: <strong><?= $total_reviews ?></strong> reviews</span>
                </div>
            </div>
            
            <!-- Reviews Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0">All Reviews</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Customer</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($reviews && $reviews->num_rows > 0): ?>
                                    <?php while ($review = $reviews->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <a href="../product.php?id=<?= $review['product_id'] ?>" target="_blank" style="text-decoration:none; color:#2563eb;">
                                                    <?= htmlspecialchars($review['product_name']) ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div style="font-weight:500;"><?= htmlspecialchars($review['customer_name']) ?></div>
                                                <div style="font-size:0.7rem; color:#6b7280;"><?= htmlspecialchars($review['customer_email']) ?></div>
                                            </td>
                                            <td>
                                                <div class="star-display">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <span class="star <?= $i <= $review['rating'] ? 'active' : '' ?>">
                                                            <i class="fa-solid fa-star"></i>
                                                        </span>
                                                    <?php endfor; ?>
                                                    <span style="margin-left:4px; font-weight:600; color:#1f2937; font-size:0.85rem;">
                                                        <?= $review['rating'] ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="review-comment" title="<?= htmlspecialchars($review['comment'] ?? 'No comment') ?>">
                                                    <?= htmlspecialchars($review['comment'] ?? 'No comment') ?>
                                                </div>
                                                <button class="btn-sm btn-secondary" onclick="viewReview(<?= $review['id'] ?>)" style="font-size:0.6rem; padding:2px 8px; margin-top:4px;">
                                                    <i class="fa-regular fa-eye"></i> View
                                                </button>
                                            </td>
                                            <td>
                                                <span class="status-badge <?= $review['status'] ?>">
                                                    <?= ucfirst($review['status']) ?>
                                                </span>
                                            </td>
                                            <td style="font-size:0.75rem; color:#6b7280;">
                                                <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                                <br>
                                                <small><?= date('h:i A', strtotime($review['created_at'])) ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1 flex-wrap">
                                                    <?php if ($review['status'] == 'pending'): ?>
                                                        <a href="?action=approve&id=<?= $review['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-success" onclick="return confirm('Approve this review?')" title="Approve">
                                                            <i class="fa-solid fa-check"></i>
                                                        </a>
                                                        <a href="?action=reject&id=<?= $review['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-danger" onclick="return confirm('Reject this review?')" title="Reject">
                                                            <i class="fa-solid fa-times"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?action=delete&id=<?= $review['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this review?')" title="Delete">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fa-regular fa-star" style="font-size:2rem; color:#d1d5db; display:block; margin-bottom:10px;"></i>
                                            <p style="color:#6b7280;">No reviews found.</p>
                                            <?php if ($search || $status_filter || $rating_filter): ?>
                                                <p style="font-size:0.85rem; color:#9ca3af;">Try adjusting your search filters.</p>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&rating=<?= $rating_filter ?>&search=<?= urlencode($search) ?>">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&status=<?= $status_filter ?>&rating=<?= $rating_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&rating=<?= $rating_filter ?>&search=<?= urlencode($search) ?>">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Review Modal -->
<div class="modal fade" id="viewReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-star"></i> Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewReviewBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Loading review details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewReview(reviewId) {
    var modal = new bootstrap.Modal(document.getElementById('viewReviewModal'));
    modal.show();
    
    document.getElementById('viewReviewBody').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading review details...</p>
        </div>
    `;
    
    // Use correct path with BASE_URL
    fetch('ajax/get_review_details.php?id=' + reviewId)
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
                const r = data.review;
                let starsHtml = '';
                for (let i = 1; i <= 5; i++) {
                    starsHtml += `<span class="star ${i <= r.rating ? 'active' : ''}"><i class="fa-solid fa-star"></i></span>`;
                }
                
                document.getElementById('viewReviewBody').innerHTML = `
                    <div class="review-detail-item">
                        <span class="label">Product</span>
                        <div class="value"><strong>${r.product_name}</strong></div>
                    </div>
                    <div class="review-detail-item">
                        <span class="label">Customer</span>
                        <div class="value">${r.customer_name}</div>
                        <div style="font-size:0.8rem; color:#6b7280;">${r.customer_email}</div>
                    </div>
                    <div class="review-detail-item">
                        <span class="label">Rating</span>
                        <div class="value">
                            <div class="stars">${starsHtml}</div>
                            <span style="font-weight:600; margin-left:8px;">${r.rating}/5</span>
                        </div>
                    </div>
                    <div class="review-detail-item">
                        <span class="label">Comment</span>
                        <div class="value" style="background:#f8fafc; padding:10px; border-radius:8px; white-space:pre-wrap;">
                            ${r.comment || 'No comment provided.'}
                        </div>
                    </div>
                    <div class="review-detail-item">
                        <span class="label">Status</span>
                        <div class="value">
                            <span class="status-badge ${r.status}">${r.status.toUpperCase()}</span>
                        </div>
                    </div>
                    <div class="review-detail-item">
                        <span class="label">Date</span>
                        <div class="value">${new Date(r.created_at).toLocaleString()}</div>
                    </div>
                `;
            } else {
                document.getElementById('viewReviewBody').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-circle-exclamation"></i> 
                        ${data.message || 'Failed to load review details.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('viewReviewBody').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fa-solid fa-circle-exclamation"></i> 
                    Error loading review details. Please try again.
                    <br><small class="text-muted">${error.message}</small>
                </div>
            `;
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>