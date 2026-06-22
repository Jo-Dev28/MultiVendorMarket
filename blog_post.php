<?php
$page_title = 'Blog Post';
require_once 'includes/header.php';

// ============================================
// CREATE BLOG TABLES IF NOT EXISTS
// ============================================
$table_check = $mysqli->query("SHOW TABLES LIKE 'blog_posts'");
if ($table_check && $table_check->num_rows == 0) {
    // Create blog_posts table
    $create_posts = "CREATE TABLE IF NOT EXISTS `blog_posts` (
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
        UNIQUE KEY `slug` (`slug`),
        KEY `author_id` (`author_id`),
        KEY `category_id` (`category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $mysqli->query($create_posts);
    
    // Create blog_categories table
    $create_categories = "CREATE TABLE IF NOT EXISTS `blog_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `slug` varchar(120) NOT NULL,
        `description` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $mysqli->query($create_categories);
    
    // Create blog_comments table
    $create_comments = "CREATE TABLE IF NOT EXISTS `blog_comments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `post_id` int(11) NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `name` varchar(100) NOT NULL,
        `email` varchar(190) NOT NULL,
        `comment` text NOT NULL,
        `status` enum('pending','approved','spam') DEFAULT 'pending',
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        KEY `post_id` (`post_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    $mysqli->query($create_comments);
    
    // Insert sample categories
    $sample_categories = "INSERT INTO `blog_categories` (`name`, `slug`) VALUES
        ('Marketplace News', 'marketplace-news'),
        ('Shopping Tips', 'shopping-tips'),
        ('Product Reviews', 'product-reviews'),
        ('Seller Spotlight', 'seller-spotlight'),
        ('Tech & Gadgets', 'tech-gadgets'),
        ('Fashion & Style', 'fashion-style')";
    $mysqli->query($sample_categories);
}

$post_id = intval($_GET['id'] ?? 0);

if ($post_id == 0) {
    flash('Post not found.', 'danger');
    redirect('blog.php');
}

// Get blog post
$sql = "SELECT b.*, u.name as author_name, u.id as author_id,
        c.name as category_name
        FROM blog_posts b
        JOIN users u ON u.id = b.author_id
        LEFT JOIN blog_categories c ON c.id = b.category_id
        WHERE b.id = ? AND b.status = 'published'";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    flash('Database error.', 'danger');
    redirect('blog.php');
}
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    flash('Post not found.', 'danger');
    redirect('blog.php');
}

// Update views
$views_sql = "UPDATE blog_posts SET views = views + 1 WHERE id = ?";
$views_stmt = $mysqli->prepare($views_sql);
if ($views_stmt) {
    $views_stmt->bind_param('i', $post_id);
    $views_stmt->execute();
    $views_stmt->close();
}

// Get comments
$comments_sql = "SELECT c.*, u.name as user_name 
                 FROM blog_comments c
                 LEFT JOIN users u ON u.id = c.user_id
                 WHERE c.post_id = ? AND c.status = 'approved'
                 ORDER BY c.created_at ASC";
$comments_stmt = $mysqli->prepare($comments_sql);
if ($comments_stmt) {
    $comments_stmt->bind_param('i', $post_id);
    $comments_stmt->execute();
    $comments = $comments_stmt->get_result();
    $comments_stmt->close();
} else {
    $comments = null;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('blog_post.php?id=' . $post_id);
    }
    
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $comment = sanitize($_POST['comment'] ?? '');
    
    if (empty($name) || !$email || empty($comment)) {
        flash('Please fill all fields.', 'danger');
    } else {
        $user_id = is_logged_in() ? $_SESSION['user_id'] : 0;
        
        $comment_sql = "INSERT INTO blog_comments (post_id, user_id, name, email, comment, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        $comment_stmt = $mysqli->prepare($comment_sql);
        if ($comment_stmt) {
            $comment_stmt->bind_param('iisss', $post_id, $user_id, $name, $email, $comment);
            if ($comment_stmt->execute()) {
                flash('Your comment has been submitted and is awaiting approval.', 'success');
                redirect('blog_post.php?id=' . $post_id);
            } else {
                flash('Failed to submit comment.', 'danger');
            }
            $comment_stmt->close();
        }
    }
}
?>

<style>
    .post-wrapper {
        display: flex;
        gap: 30px;
    }
    .post-sidebar {
        width: 300px;
        flex-shrink: 0;
    }
    .post-content {
        flex: 1;
    }
    
    .post-header {
        margin-bottom: 25px;
    }
    .post-header .category-tag {
        display: inline-block;
        background: #dbeafe;
        color: #1d4ed8;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-bottom: 10px;
    }
    .post-header h1 {
        font-size: 2rem;
        font-weight: 800;
        color: #1f2937;
        margin: 0 0 12px 0;
    }
    .post-header .meta {
        display: flex;
        gap: 20px;
        font-size: 0.85rem;
        color: #6b7280;
        flex-wrap: wrap;
    }
    .post-header .meta i {
        margin-right: 4px;
    }
    
    .post-featured-image {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        border-radius: 16px;
        margin-bottom: 25px;
        background: #f3f4f6;
    }
    
    .post-body {
        font-size: 1.05rem;
        line-height: 1.8;
        color: #1f2937;
    }
    .post-body img {
        max-width: 100%;
        border-radius: 8px;
        margin: 15px 0;
    }
    .post-body h2, .post-body h3 {
        margin-top: 30px;
    }
    .post-body ul, .post-body ol {
        margin: 15px 0;
        padding-left: 25px;
    }
    .post-body blockquote {
        border-left: 4px solid #2563eb;
        padding: 15px 20px;
        background: #f8fafc;
        border-radius: 8px;
        margin: 20px 0;
        font-style: italic;
    }
    
    .comments-section {
        margin-top: 40px;
        padding-top: 30px;
        border-top: 2px solid #e5e7eb;
    }
    .comments-section h4 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 20px;
    }
    .comment-item {
        display: flex;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .comment-item .avatar {
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2563eb;
        font-weight: 700;
        flex-shrink: 0;
    }
    .comment-item .comment-body .name {
        font-weight: 600;
        color: #1f2937;
    }
    .comment-item .comment-body .date {
        font-size: 0.7rem;
        color: #9ca3af;
        margin-left: 10px;
    }
    .comment-item .comment-body .text {
        margin-top: 4px;
        color: #4b5563;
    }
    
    .comment-form .form-control {
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        padding: 10px 15px;
    }
    .comment-form .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    .btn-submit-comment {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-submit-comment:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    
    .sidebar-widget {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        margin-bottom: 20px;
    }
    .sidebar-widget .widget-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f59e0b;
    }
    .sidebar-widget .widget-title i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .recent-post-item {
        display: flex;
        gap: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .recent-post-item:last-child {
        border-bottom: none;
    }
    .recent-post-item .post-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        background: #f3f4f6;
        flex-shrink: 0;
    }
    .recent-post-item .post-info {
        flex: 1;
    }
    .recent-post-item .post-info .title {
        font-size: 0.85rem;
        font-weight: 600;
        color: #1f2937;
        text-decoration: none;
    }
    .recent-post-item .post-info .title:hover {
        color: #2563eb;
    }
    .recent-post-item .post-info .date {
        font-size: 0.7rem;
        color: #9ca3af;
    }
    
    .author-avatar {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 2rem;
        color: #2563eb;
        font-weight: 700;
    }
    
    @media (max-width: 992px) {
        .post-wrapper {
            flex-direction: column;
        }
        .post-sidebar {
            width: 100%;
        }
        .post-header h1 {
            font-size: 1.6rem;
        }
    }
    @media (max-width: 768px) {
        .comment-item {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="container py-4">
    <div class="post-wrapper">
        <!-- Main Content -->
        <div class="post-content">
            
            <!-- Post Header -->
            <div class="post-header">
                <?php if (!empty($post['category_name'])): ?>
                    <span class="category-tag"><?= sanitize($post['category_name']) ?></span>
                <?php endif; ?>
                <h1><?= sanitize($post['title']) ?></h1>
                <div class="meta">
                    <span><i class="fa-regular fa-user"></i> <?= sanitize($post['author_name']) ?></span>
                    <span><i class="fa-regular fa-calendar"></i> <?= date('F d, Y', strtotime($post['created_at'])) ?></span>
                    <span><i class="fa-regular fa-clock"></i> <?= date('h:i A', strtotime($post['created_at'])) ?></span>
                    <span><i class="fa-regular fa-eye"></i> <?= number_format($post['views']) ?> views</span>
                </div>
            </div>
            
            <!-- Featured Image -->
            <?php if (!empty($post['featured_image']) && file_exists('uploads/blog/' . $post['featured_image'])): ?>
                <img src="uploads/blog/<?= $post['featured_image'] ?>" class="post-featured-image" alt="<?= sanitize($post['title']) ?>">
            <?php endif; ?>
            
            <!-- Post Body -->
            <div class="post-body">
                <?= $post['content'] ?>
            </div>
            
            <!-- Comments Section -->
            <div class="comments-section">
                <h4><i class="fa-regular fa-comments"></i> Comments (<?= $comments ? $comments->num_rows : 0 ?>)</h4>
                
                <?php if ($comments && $comments->num_rows > 0): ?>
                    <?php while ($comment = $comments->fetch_assoc()): ?>
                    <div class="comment-item">
                        <div class="avatar">
                            <?= strtoupper(substr($comment['name'], 0, 1)) ?>
                        </div>
                        <div class="comment-body">
                            <span class="name"><?= sanitize($comment['name']) ?></span>
                            <span class="date"><?= date('M d, Y h:i A', strtotime($comment['created_at'])) ?></span>
                            <div class="text"><?= nl2br(sanitize($comment['comment'])) ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No comments yet. Be the first to comment!</p>
                <?php endif; ?>
                
                <!-- Comment Form -->
                <div class="mt-4">
                    <h5>Leave a Comment</h5>
                    <form method="post" class="comment-form">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="submit_comment" value="1">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="name" class="form-control" placeholder="Your Name *" required>
                            </div>
                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Your Email *" required>
                            </div>
                            <div class="col-12">
                                <textarea name="comment" class="form-control" rows="4" placeholder="Your Comment *" required></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-submit-comment">
                                    <i class="fa-regular fa-paper-plane"></i> Post Comment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="post-sidebar">
            
            <!-- Author Info -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fa-regular fa-user"></i> About Author</div>
                <div style="text-align:center;">
                    <div class="author-avatar">
                        <?= strtoupper(substr($post['author_name'], 0, 1)) ?>
                    </div>
                    <h6 style="font-weight:700; margin-bottom:4px;"><?= sanitize($post['author_name']) ?></h6>
                    <p style="font-size:0.8rem; color:#6b7280;">Blog Author</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">
                        <i class="fa-regular fa-eye"></i> View All Posts
                    </a>
                </div>
            </div>
            
            <!-- Recent Posts -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fa-regular fa-clock"></i> Recent Posts</div>
                <?php
                $recent_sql = "SELECT id, title, featured_image, created_at 
                               FROM blog_posts 
                               WHERE status = 'published' AND id != ? 
                               ORDER BY created_at DESC LIMIT 5";
                $recent_stmt = $mysqli->prepare($recent_sql);
                if ($recent_stmt) {
                    $recent_stmt->bind_param('i', $post_id);
                    $recent_stmt->execute();
                    $recent_posts = $recent_stmt->get_result();
                    if ($recent_posts && $recent_posts->num_rows > 0):
                ?>
                    <?php while ($rp = $recent_posts->fetch_assoc()): ?>
                    <div class="recent-post-item">
                        <img src="<?= !empty($rp['featured_image']) ? 'uploads/blog/' . $rp['featured_image'] : 'assets/images/blog-placeholder.jpg' ?>" 
                             class="post-img" alt="<?= sanitize($rp['title']) ?>">
                        <div class="post-info">
                            <a href="blog_post.php?id=<?= $rp['id'] ?>" class="title"><?= sanitize(substr($rp['title'], 0, 40)) ?>...</a>
                            <div class="date"><i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($rp['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No recent posts</p>
                <?php endif;
                    $recent_stmt->close();
                } ?>
            </div>
            
            <!-- Share Widget -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fa-solid fa-share-nodes"></i> Share This Post</div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="btn btn-primary" style="border-radius:50%; width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="btn btn-info" style="border-radius:50%; width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center; color:white;">
                        <i class="fa-brands fa-twitter"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="btn btn-primary" style="border-radius:50%; width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center; background:#0a66c2; border-color:#0a66c2;">
                        <i class="fa-brands fa-linkedin-in"></i>
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode($post['title'] . ' - ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" target="_blank" class="btn btn-success" style="border-radius:50%; width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-brands fa-whatsapp"></i>
                    </a>
                    <a href="mailto:?subject=<?= urlencode($post['title']) ?>&body=<?= urlencode('Check out this post: ' . 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" class="btn btn-secondary" style="border-radius:50%; width:40px; height:40px; padding:0; display:flex; align-items:center; justify-content:center;">
                        <i class="fa-regular fa-envelope"></i>
                    </a>
                </div>
            </div>
            
            <!-- Tags -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fa-solid fa-tags"></i> Tags</div>
                <div style="display:flex; flex-wrap:wrap; gap:6px;">
                    <span class="badge bg-secondary">Marketplace</span>
                    <span class="badge bg-primary">Shopping</span>
                    <span class="badge bg-success">Deals</span>
                    <span class="badge bg-warning">Tips</span>
                    <span class="badge bg-info">Products</span>
                    <span class="badge bg-danger">Flash Sale</span>
                </div>
            </div>
            
            <!-- Back to Blog -->
            <a href="blog.php" class="btn btn-outline-primary" style="width:100%; border-radius:10px; padding:10px;">
                <i class="fa-solid fa-arrow-left"></i> Back to Blog
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>