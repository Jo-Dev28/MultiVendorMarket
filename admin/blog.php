<?php
$page_title = 'Manage Blog';
require_once '../includes/header.php';
require_role('admin');

// ============================================
// CREATE TABLES IF NOT EXISTS
// ============================================
$table_check = $mysqli->query("SHOW TABLES LIKE 'blog_posts'");
if ($table_check && $table_check->num_rows == 0) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS `blog_posts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `author_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `slug` varchar(255) NOT NULL,
        `excerpt` text DEFAULT NULL,
        `content` longtext NOT NULL,
        `featured_image` varchar(255) DEFAULT NULL,
        `category_id` int(11) DEFAULT NULL,
        `status` enum('draft','published','archived') DEFAULT 'draft',
        `views` int(11) DEFAULT 0,
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        `published_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS `blog_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `slug` varchar(120) NOT NULL,
        `description` text DEFAULT NULL,
        `created_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    
    $mysqli->query("CREATE TABLE IF NOT EXISTS `blog_comments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `post_id` int(11) NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `name` varchar(100) NOT NULL,
        `email` varchar(190) NOT NULL,
        `comment` text NOT NULL,
        `status` enum('pending','approved','spam') DEFAULT 'pending',
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
}

// Handle Delete Post
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('admin/blog.php');
    }
    
    // Get image to delete
    $img_sql = "SELECT featured_image FROM blog_posts WHERE id = ?";
    $img_stmt = $mysqli->prepare($img_sql);
    $img_stmt->bind_param('i', $id);
    $img_stmt->execute();
    $img = $img_stmt->get_result()->fetch_assoc();
    $img_stmt->close();
    
    if ($img && !empty($img['featured_image']) && file_exists('../uploads/blog/' . $img['featured_image'])) {
        unlink('../uploads/blog/' . $img['featured_image']);
    }
    
    $sql = "DELETE FROM blog_posts WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        flash('Post deleted successfully.', 'success');
    } else {
        flash('Failed to delete post.', 'danger');
    }
    $stmt->close();
    redirect('admin/blog.php');
}

// Handle Status Update
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status = sanitize($_GET['status']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('admin/blog.php');
    }
    
    $sql = "UPDATE blog_posts SET status = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('si', $status, $id);
    if ($stmt->execute()) {
        flash('Post status updated.', 'success');
    } else {
        flash('Failed to update status.', 'danger');
    }
    $stmt->close();
    redirect('admin/blog.php');
}

// ============================================
// COMMENT MANAGEMENT
// ============================================

// Approve Comment
if (isset($_GET['approve_comment']) && is_numeric($_GET['approve_comment'])) {
    $comment_id = intval($_GET['approve_comment']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('admin/blog.php');
    }
    
    $sql = "UPDATE blog_comments SET status = 'approved' WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $comment_id);
    if ($stmt->execute()) {
        flash('Comment approved successfully.', 'success');
    } else {
        flash('Failed to approve comment.', 'danger');
    }
    $stmt->close();
    redirect('admin/blog.php');
}

// Reject Comment
if (isset($_GET['reject_comment']) && is_numeric($_GET['reject_comment'])) {
    $comment_id = intval($_GET['reject_comment']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('admin/blog.php');
    }
    
    $sql = "DELETE FROM blog_comments WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $comment_id);
    if ($stmt->execute()) {
        flash('Comment rejected and deleted.', 'success');
    } else {
        flash('Failed to reject comment.', 'danger');
    }
    $stmt->close();
    redirect('admin/blog.php');
}

// Get all posts
$sql = "SELECT b.*, u.name as author_name, c.name as category_name,
        (SELECT COUNT(*) FROM blog_comments WHERE post_id = b.id) as comment_count
        FROM blog_posts b
        JOIN users u ON u.id = b.author_id
        LEFT JOIN blog_categories c ON c.id = b.category_id
        ORDER BY b.created_at DESC";
$posts = $mysqli->query($sql);

// Get all pending comments
$pending_comments_sql = "SELECT c.*, b.title as post_title, u.name as user_name 
                         FROM blog_comments c
                         LEFT JOIN blog_posts b ON b.id = c.post_id
                         LEFT JOIN users u ON u.id = c.user_id
                         WHERE c.status = 'pending'
                         ORDER BY c.created_at DESC";
$pending_comments = $mysqli->query($pending_comments_sql);

// Get counts
$total_posts = $mysqli->query("SELECT COUNT(*) as count FROM blog_posts")->fetch_assoc()['count'];
$published_posts = $mysqli->query("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'")->fetch_assoc()['count'];
$draft_posts = $mysqli->query("SELECT COUNT(*) as count FROM blog_posts WHERE status = 'draft'")->fetch_assoc()['count'];
$pending_comments_count = $mysqli->query("SELECT COUNT(*) as count FROM blog_comments WHERE status = 'pending'")->fetch_assoc()['count'];
$approved_comments_count = $mysqli->query("SELECT COUNT(*) as count FROM blog_comments WHERE status = 'approved'")->fetch_assoc()['count'];
?>

<style>
    .blog-admin-wrapper {
        display: flex;
        gap: 25px;
        min-height: calc(100vh - 200px);
    }
    .blog-admin-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .blog-admin-content {
        flex: 1;
    }
    
    .stats-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .stat-card .number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
    }
    .stat-card .label {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 4px;
    }
    .stat-card .icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    .stat-card.total .icon { color: #2563eb; }
    .stat-card.published .icon { color: #10b981; }
    .stat-card.drafts .icon { color: #f59e0b; }
    .stat-card.comments .icon { color: #7c3aed; }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        border-radius: 16px;
        overflow: hidden;
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
    .data-table tr:hover td {
        background: #f8fafc;
    }
    .data-table .post-image {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }
    .data-table .post-image-placeholder {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-badge.published { background: #d1fae5; color: #059669; }
    .status-badge.draft { background: #fef3c7; color: #d97706; }
    .status-badge.archived { background: #e5e7eb; color: #4b5563; }
    
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
    }
    .btn-sm.btn-primary { background: #2563eb; color: white; }
    .btn-sm.btn-success { background: #10b981; color: white; }
    .btn-sm.btn-warning { background: #f59e0b; color: white; }
    .btn-sm.btn-danger { background: #ef4444; color: white; }
    .btn-sm.btn-info { background: #3b82f6; color: white; }
    .btn-sm.btn-secondary { background: #6b7280; color: white; }
    .btn-sm:hover { opacity: 0.9; transform: translateY(-1px); }
    
    .btn-add {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-add:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(37,99,235,0.3); color: white; }
    
    /* Modal Styles */
    .view-modal-body {
        max-height: 500px;
        overflow-y: auto;
    }
    .view-modal-body .post-content-preview {
        background: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        max-height: 300px;
        overflow-y: auto;
        font-size: 0.9rem;
        line-height: 1.6;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    .comment-item {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px 15px;
        margin-bottom: 10px;
        border-left: 3px solid #e5e7eb;
    }
    .comment-item .comment-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4px;
        flex-wrap: wrap;
    }
    .comment-item .comment-author {
        font-weight: 600;
        color: #1f2937;
    }
    .comment-item .comment-date {
        font-size: 0.7rem;
        color: #9ca3af;
    }
    .comment-item .comment-text {
        color: #4b5563;
        font-size: 0.9rem;
    }
    .comment-item .comment-status {
        font-size: 0.65rem;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 50px;
    }
    .comment-item .comment-status.pending { background: #fef3c7; color: #d97706; }
    .comment-item .comment-status.approved { background: #d1fae5; color: #059669; }
    
    .comment-item.pending-comment {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    .comment-item.approved-comment {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    
    .pending-comments-section {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 2px solid #e5e7eb;
    }
    
    @media (max-width: 992px) {
        .blog-admin-wrapper { flex-direction: column; }
        .blog-admin-sidebar { width: 100%; }
        .stats-row { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
        .stats-row { grid-template-columns: 1fr 1fr; }
        .data-table { font-size: 0.8rem; }
        .data-table th, .data-table td { padding: 8px 10px; }
    }
</style>

<div class="container-fluid">
    <div class="blog-admin-wrapper">
        <div class="blog-admin-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="blog-admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <h2><i class="fa-solid fa-newspaper"></i> Blog Management</h2>
                <div class="d-flex gap-2">
                    <a href="blog_add.php" class="btn-add">
                        <i class="fa-solid fa-plus"></i> Add New Post
                    </a>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card total">
                    <div class="icon"><i class="fa-solid fa-newspaper"></i></div>
                    <div class="number"><?= $total_posts ?></div>
                    <div class="label">Total Posts</div>
                </div>
                <div class="stat-card published">
                    <div class="icon"><i class="fa-solid fa-check-circle"></i></div>
                    <div class="number"><?= $published_posts ?></div>
                    <div class="label">Published</div>
                </div>
                <div class="stat-card drafts">
                    <div class="icon"><i class="fa-regular fa-pen-to-square"></i></div>
                    <div class="number"><?= $draft_posts ?></div>
                    <div class="label">Drafts</div>
                </div>
                <div class="stat-card comments">
                    <div class="icon"><i class="fa-regular fa-comments"></i></div>
                    <div class="number"><?= $pending_comments_count ?></div>
                    <div class="label">Pending Comments</div>
                </div>
            </div>
            
            <!-- Pending Comments Section -->
            <?php if ($pending_comments && $pending_comments->num_rows > 0): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-3" style="background:#fef3c7 !important; border-radius:16px 16px 0 0;">
                    <h5 class="mb-0">
                        <i class="fa-regular fa-clock" style="color:#d97706;"></i> 
                        Pending Comments (<?= $pending_comments_count ?>)
                        <span class="badge bg-warning ms-2">Needs Review</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Comment</th>
                                    <th>Post</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($comment = $pending_comments->fetch_assoc()): ?>
                                <tr style="background:#fffbeb;">
                                    <td>
                                        <div style="font-weight:600;"><?= sanitize($comment['name']) ?></div>
                                        <div style="font-size:0.8rem; color:#6b7280;"><?= sanitize($comment['email']) ?></div>
                                        <div style="font-size:0.85rem; color:#4b5563; margin-top:4px;"><?= sanitize(substr($comment['comment'], 0, 100)) ?>...</div>
                                    </td>
                                    <td>
                                        <a href="../blog_post.php?id=<?= $comment['post_id'] ?>" target="_blank" style="text-decoration:none; color:#2563eb;">
                                            <?= sanitize(substr($comment['post_title'] ?? 'Unknown', 0, 40)) ?>
                                        </a>
                                    </td>
                                    <td style="font-size:0.75rem; color:#6b7280;">
                                        <?= date('M d, Y h:i A', strtotime($comment['created_at'])) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="?approve_comment=<?= $comment['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-success" title="Approve Comment">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </a>
                                            <a href="?reject_comment=<?= $comment['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-danger" onclick="return confirm('Reject and delete this comment?')" title="Reject Comment">
                                                <i class="fa-solid fa-times"></i> Reject
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
            <?php endif; ?>
            
            <!-- Posts Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="mb-0">All Blog Posts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Comments</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($posts && $posts->num_rows > 0): ?>
                                    <?php while ($post = $posts->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($post['featured_image']) && file_exists('../uploads/blog/' . $post['featured_image'])): ?>
                                                <img src="../uploads/blog/<?= $post['featured_image'] ?>" class="post-image">
                                            <?php else: ?>
                                                <div class="post-image-placeholder">📄</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?= sanitize($post['title']) ?></strong>
                                            <br><small class="text-muted"><?= substr(strip_tags($post['content']), 0, 50) ?>...</small>
                                        </td>
                                        <td><?= sanitize($post['author_name']) ?></td>
                                        <td><?= sanitize($post['category_name'] ?? '-') ?></td>
                                        <td>
                                            <span class="status-badge <?= $post['status'] ?>">
                                                <?= ucfirst($post['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $post['comment_count'] ?></td>
                                        <td style="font-size:0.75rem; color:#6b7280;">
                                            <?= date('M d, Y', strtotime($post['created_at'])) ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1 flex-wrap">
                                                <button class="btn-sm btn-secondary" onclick="viewPost(<?= $post['id'] ?>)" title="View Full Post">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                                <a href="blog_edit.php?id=<?= $post['id'] ?>" class="btn-sm btn-primary" title="Edit">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="../blog_post.php?id=<?= $post['id'] ?>" target="_blank" class="btn-sm btn-info" title="View on Site">
                                                    <i class="fa-solid fa-globe"></i>
                                                </a>
                                                <?php if ($post['status'] == 'draft'): ?>
                                                    <a href="?status=published&id=<?= $post['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-success" title="Publish">
                                                        <i class="fa-solid fa-check"></i>
                                                    </a>
                                                <?php elseif ($post['status'] == 'published'): ?>
                                                    <a href="?status=draft&id=<?= $post['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-warning" title="Unpublish">
                                                        <i class="fa-solid fa-undo"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?delete=<?= $post['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this post?')" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fa-regular fa-newspaper" style="font-size:2rem; color:#d1d5db; display:block; margin-bottom:10px;"></i>
                                            <p>No blog posts yet. <a href="blog_add.php">Create your first post</a></p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Post Modal -->
<div class="modal fade" id="viewPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-eye"></i> View Post & Comments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body view-modal-body" id="viewPostContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div>
                    <p class="mt-2 text-muted">Loading post...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewPost(postId) {
    var modal = new bootstrap.Modal(document.getElementById('viewPostModal'));
    modal.show();
    
    document.getElementById('viewPostContent').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2 text-muted">Loading post...</p>
        </div>
    `;
    
    fetch('ajax/get_blog_post.php?id=' + postId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const p = data.post;
                const comments = data.comments || [];
                const statusBadge = p.status === 'published' ? 'bg-success' : (p.status === 'draft' ? 'bg-warning' : 'bg-secondary');
                
                let commentsHtml = '';
                if (comments.length > 0) {
                    commentsHtml = `
                        <hr>
                        <h6><i class="fa-regular fa-comments"></i> Comments (${comments.length})</h6>
                        <div style="max-height:300px; overflow-y:auto;">
                    `;
                    comments.forEach(c => {
                        const statusClass = c.status === 'approved' ? 'approved' : 'pending';
                        const statusLabel = c.status === 'approved' ? '✅ Approved' : '⏳ Pending';
                        commentsHtml += `
                            <div class="comment-item ${statusClass}-comment">
                                <div class="comment-header">
                                    <span class="comment-author">${c.name}</span>
                                    <span>
                                        <span class="comment-status ${statusClass}">${statusLabel}</span>
                                        <span class="comment-date">${new Date(c.created_at).toLocaleString()}</span>
                                    </span>
                                </div>
                                <div class="comment-text">${c.comment}</div>
                            </div>
                        `;
                    });
                    commentsHtml += '</div>';
                } else {
                    commentsHtml = `
                        <hr>
                        <p class="text-muted"><i class="fa-regular fa-comment"></i> No comments yet.</p>
                    `;
                }
                
                document.getElementById('viewPostContent').innerHTML = `
                    <div class="row">
                        <div class="col-12">
                            <h4>${p.title}</h4>
                            <div class="mb-2">
                                <span class="badge ${statusBadge}">${p.status}</span>
                                <span class="badge bg-primary ms-1">${p.category_name || 'Uncategorized'}</span>
                                <span class="badge bg-secondary ms-1"><i class="fa-regular fa-user"></i> ${p.author_name}</span>
                                <span class="badge bg-secondary ms-1"><i class="fa-regular fa-calendar"></i> ${new Date(p.created_at).toLocaleDateString()}</span>
                                <span class="badge bg-secondary ms-1"><i class="fa-regular fa-eye"></i> ${p.views} views</span>
                            </div>
                            ${p.featured_image ? `<img src="../uploads/blog/${p.featured_image}" class="img-fluid rounded mb-3" style="max-height:300px; width:100%; object-fit:cover;">` : ''}
                            <h6>Excerpt:</h6>
                            <p class="text-muted">${p.excerpt || 'No excerpt provided.'}</p>
                            <hr>
                            <h6>Full Content:</h6>
                            <div class="post-content-preview">${p.content}</div>
                            ${commentsHtml}
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('viewPostContent').innerHTML = `
                    <div class="alert alert-danger">${data.message || 'Error loading post.'}</div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('viewPostContent').innerHTML = `
                <div class="alert alert-danger">Error loading post. Please try again.</div>
            `;
        });
}
</script>

<?php require_once '../includes/footer.php'; ?>