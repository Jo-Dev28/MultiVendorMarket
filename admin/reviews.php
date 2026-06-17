<?php
$page_title = 'Reviews Management';
require_once '../includes/header.php';

// Check if user is admin
if (($user['role'] ?? '') !== 'admin') {
    flash('Access denied. Admin only.', 'danger');
    redirect('index.php');
}

// Handle review status update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $review_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action === 'approve') {
        $mysqli->query("UPDATE reviews SET status = 'approved' WHERE id = $review_id");
        flash('Review approved successfully.', 'success');
    } elseif ($action === 'reject') {
        $mysqli->query("UPDATE reviews SET status = 'rejected' WHERE id = $review_id");
        flash('Review rejected.', 'success');
    } elseif ($action === 'delete') {
        $mysqli->query("DELETE FROM reviews WHERE id = $review_id");
        flash('Review deleted.', 'success');
    }
    redirect('admin/reviews.php');
}

// Search and filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// Build query with filters
$where_clauses = [];
$params = [];
$types = '';

if ($search) {
    $where_clauses[] = "(u.name LIKE ? OR p.name LIKE ? OR r.comment LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($status_filter) {
    $where_clauses[] = "r.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Get total reviews
$count_sql = "SELECT COUNT(*) as total FROM reviews r
              JOIN users u ON u.id = r.user_id
              JOIN products p ON p.id = r.product_id
              $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_reviews = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_reviews / $limit);

// Get reviews
$sql = "SELECT r.*, u.name as user_name, p.name as product_name, p.slug as product_slug
        FROM reviews r
        JOIN users u ON u.id = r.user_id
        JOIN products p ON p.id = r.product_id
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
?>

<style>
    .admin-content-wrapper { display: flex; gap: 25px; min-height: calc(100vh - 200px); }
    .admin-sidebar-col { width: 280px; flex-shrink: 0; }
    .admin-main-col { flex: 1; }
    .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .search-input { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 15px; width: 250px; }
    .rating-stars { color: #ffc107; font-size: 0.8rem; }
    .review-comment { max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    @media (max-width: 992px) { .admin-content-wrapper { flex-direction: column; } .admin-sidebar-col { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-star"></i> Reviews Management</h2>
            </div>
            
            <div class="filter-bar">
                <form method="GET" action="" class="d-flex gap-2 flex-wrap">
                    <input type="text" name="search" class="search-input" placeholder="Search by user, product or comment..." value="<?= htmlspecialchars($search) ?>">
                    <select name="status" class="form-select w-auto">
                        <option value="">All Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status_filter == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status_filter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if ($search || $status_filter): ?>
                        <a href="reviews.php" class="btn btn-secondary btn-sm">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>ID</th><th>Product</th><th>User</th><th>Rating</th><th>Comment</th><th>Status</th><th>Date</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($review = $reviews->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $review['id'] ?></td>
                                    <td><a href="../product.php?id=<?= $review['product_id'] ?>" target="_blank"><?= htmlspecialchars($review['product_name']) ?></a></td>
                                    <td><?= htmlspecialchars($review['user_name']) ?></td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <?php if($i <= $review['rating']): ?>
                                                    <i class="fa-solid fa-star"></i>
                                                <?php else: ?>
                                                    <i class="fa-regular fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td><div class="review-comment" title="<?= htmlspecialchars($review['comment']) ?>"><?= htmlspecialchars($review['comment']) ?></div></td>
                                    <td>
                                        <?php if ($review['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif ($review['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Rejected</span>
                                        <?php endif; ?>
                                     </td
                                    <td><?= date('M d, Y', strtotime($review['created_at'])) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-info" onclick="viewReviewDetails(<?= $review['id'] ?>)" title="View Details"><i class="fa-solid fa-eye"></i></button>
                                            <?php if ($review['status'] === 'pending'): ?>
                                                <a href="?action=approve&id=<?= $review['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this review?')"><i class="fa-solid fa-check"></i></a>
                                                <a href="?action=reject&id=<?= $review['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this review?')"><i class="fa-solid fa-times"></i></a>
                                            <?php else: ?>
                                                <a href="?action=delete&id=<?= $review['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this review?')"><i class="fa-solid fa-trash"></i></a>
                                            <?php endif; ?>
                                        </div>
                                     </td
                                 </tr
                                <?php endwhile; ?>
                                <?php if ($reviews->num_rows == 0): ?>
                                    <tr><td colspan="8" class="text-center py-4">No reviews found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <?php if ($total_pages > 1): ?>
            <nav class="mt-4"><ul class="pagination justify-content-center"><?php for($i=1;$i<=$total_pages;$i++): ?><li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= $status_filter ?>"><?= $i ?></a></li><?php endfor; ?></ul></nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- View Review Modal -->
<div class="modal fade" id="viewReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Review Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="reviewDetailsContent"><div class="text-center py-4"><div class="spinner-border text-primary"></div></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<script>
function viewReviewDetails(reviewId) {
    var modal = new bootstrap.Modal(document.getElementById('viewReviewModal'));
    modal.show();
    fetch('ajax/get_review_details.php?id=' + reviewId).then(r=>r.json()).then(data=>{
        if(data.success){
            let stars = '';
            for(let i=1; i<=5; i++) stars += i <= data.review.rating ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
            document.getElementById('reviewDetailsContent').innerHTML = `<div class="card"><div class="card-body"><p><strong>Product:</strong> ${data.review.product_name}</p><p><strong>User:</strong> ${data.review.user_name}</p><p><strong>Rating:</strong> ${stars}</p><p><strong>Comment:</strong><br>${data.review.comment}</p><p><strong>Status:</strong> ${data.review.status}</p><p><strong>Date:</strong> ${new Date(data.review.created_at).toLocaleString()}</p></div></div>`;
        } else { document.getElementById('reviewDetailsContent').innerHTML = `<div class="alert alert-danger">${data.message}</div>`; }
    }).catch(()=>{ document.getElementById('reviewDetailsContent').innerHTML = `<div class="alert alert-danger">Error loading details.</div>`; });
}
</script>

<?php require_once '../includes/footer.php'; ?>