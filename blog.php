<?php
$page_title = 'Blog - Latest News & Updates';
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
        `created_at` datetime DEFAULT NULL,
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
    $sample_categories = "INSERT INTO `blog_categories` (`name`, `slug`, `created_at`) VALUES
        ('Technology & Gadgets', 'tech-gadgets', NOW()),
        ('Shopping Tips', 'shopping-tips', NOW()),
        ('Product Reviews', 'product-reviews', NOW()),
        ('Seller Spotlight', 'seller-spotlight', NOW()),
        ('Marketplace News', 'marketplace-news', NOW()),
        ('Fashion & Style', 'fashion-style', NOW())";
    $mysqli->query($sample_categories);
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Build where clause
$where_clauses = ["b.status = 'published'"];
$params = [];
$types = '';

if (!empty($search)) {
    $where_clauses[] = "(b.title LIKE ? OR b.content LIKE ? OR b.excerpt LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if ($category_filter > 0) {
    $where_clauses[] = "b.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

// Get total posts count
$count_sql = "SELECT COUNT(*) as total FROM blog_posts b $where_sql";
$count_stmt = $mysqli->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $limit);
$count_stmt->close();

// Get blog posts
$sql = "SELECT b.*, u.name as author_name, 
        (SELECT COUNT(*) FROM blog_comments WHERE post_id = b.id AND status = 'approved') as comment_count,
        c.name as category_name
        FROM blog_posts b
        JOIN users u ON u.id = b.author_id
        LEFT JOIN blog_categories c ON c.id = b.category_id
        $where_sql
        ORDER BY b.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $mysqli->prepare($sql);
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';
$stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result();
$stmt->close();

// Get categories with post count
$categories_sql = "SELECT c.*, 
                   (SELECT COUNT(*) FROM blog_posts WHERE category_id = c.id AND status = 'published') as post_count
                   FROM blog_categories c
                   ORDER BY c.name";
$categories = $mysqli->query($categories_sql);

// Get recent posts for sidebar
$recent_sql = "SELECT b.*, u.name as author_name 
               FROM blog_posts b
               JOIN users u ON u.id = b.author_id
               WHERE b.status = 'published'
               ORDER BY b.created_at DESC
               LIMIT 5";
$recent_posts = $mysqli->query($recent_sql);

// Get featured post
$featured_sql = "SELECT b.*, u.name as author_name 
                 FROM blog_posts b
                 JOIN users u ON u.id = b.author_id
                 WHERE b.status = 'published'
                 ORDER BY b.created_at DESC
                 LIMIT 1";
$featured_result = $mysqli->query($featured_sql);
$featured_post = $featured_result->fetch_assoc();
?>

<style>
    /* ============================================
       BLOG PAGE - MODERN DESIGN
    ============================================ */
    
    /* Hero Section */
    .blog-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .blog-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    .blog-hero h1 {
        color: #fff;
        font-size: 2.8rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }
    .blog-hero h1 i {
        color: #f59e0b;
    }
    .blog-hero p {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
    }
    
    /* Blog Wrapper */
    .blog-wrapper {
        display: flex;
        gap: 30px;
    }
    .blog-main {
        flex: 1;
    }
    .blog-sidebar {
        width: 320px;
        flex-shrink: 0;
    }
    
    /* Featured Post */
    .featured-post {
        background: #1e293b;
        border-radius: 20px;
        overflow: hidden;
        margin-bottom: 30px;
        position: relative;
        min-height: 300px;
        display: flex;
        align-items: flex-end;
        background-size: cover;
        background-position: center;
    }
    .featured-post .overlay {
        background: linear-gradient(0deg, rgba(0,0,0,0.85) 0%, rgba(0,0,0,0.1) 100%);
        padding: 30px;
        width: 100%;
        position: relative;
        z-index: 1;
    }
    .featured-post .featured-badge {
        display: inline-block;
        background: #f59e0b;
        color: #1f2937;
        padding: 3px 14px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }
    .featured-post h2 {
        color: white;
        font-size: 1.8rem;
        font-weight: 700;
        margin: 0 0 8px 0;
    }
    .featured-post h2 a {
        color: white;
        text-decoration: none;
    }
    .featured-post h2 a:hover {
        color: #f59e0b;
    }
    .featured-post .meta {
        color: rgba(255,255,255,0.7);
        font-size: 0.8rem;
    }
    .featured-post .meta i {
        margin-right: 4px;
    }
    .featured-post .excerpt {
        color: rgba(255,255,255,0.85);
        margin-top: 10px;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    .featured-post .read-more {
        display: inline-block;
        margin-top: 12px;
        color: #f59e0b;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
    }
    .featured-post .read-more:hover {
        color: #d97706;
        transform: translateX(5px);
    }
    
    /* Blog Card */
    .blog-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .blog-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.1);
        border-color: #2563eb;
    }
    .blog-card .card-image {
        height: 220px;
        object-fit: cover;
        width: 100%;
        background: #f3f4f6;
    }
    .blog-card .card-image-placeholder {
        height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        color: #2563eb;
    }
    .blog-card .card-body {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .blog-card .category-tag {
        display: inline-block;
        background: #dbeafe;
        color: #1d4ed8;
        padding: 2px 12px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
        margin-bottom: 8px;
        align-self: flex-start;
    }
    .blog-card h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 8px 0;
        line-height: 1.4;
    }
    .blog-card h3 a {
        color: #1f2937;
        text-decoration: none;
    }
    .blog-card h3 a:hover {
        color: #2563eb;
    }
    .blog-card .excerpt {
        color: #6b7280;
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 12px;
        flex: 1;
    }
    .blog-card .meta {
        display: flex;
        gap: 15px;
        font-size: 0.75rem;
        color: #9ca3af;
        flex-wrap: wrap;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
    }
    .blog-card .meta i {
        margin-right: 4px;
    }
    .blog-card .read-more {
        color: #2563eb;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.85rem;
        margin-top: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .blog-card .read-more:hover {
        color: #1d4ed8;
        gap: 10px;
    }
    
    /* Sidebar Widgets */
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
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sidebar-widget .widget-title i {
        color: #f59e0b;
    }
    
    /* Search Widget */
    .search-widget form {
        display: flex;
        gap: 8px;
    }
    .search-widget input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.85rem;
        outline: none;
        transition: all 0.3s;
    }
    .search-widget input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    .search-widget button {
        padding: 10px 18px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .search-widget button:hover {
        background: #1d4ed8;
    }
    
    /* Categories */
    .category-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .category-list li {
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .category-list li:last-child {
        border-bottom: none;
    }
    .category-list a {
        color: #4b5563;
        text-decoration: none;
        font-size: 0.85rem;
        display: flex;
        justify-content: space-between;
        transition: all 0.3s;
    }
    .category-list a:hover {
        color: #2563eb;
    }
    .category-list a .count {
        color: #9ca3af;
        font-size: 0.75rem;
        background: #f3f4f6;
        padding: 0 8px;
        border-radius: 50px;
    }
    
    /* Recent Posts */
    .recent-post-item {
        display: flex;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        align-items: center;
    }
    .recent-post-item:last-child {
        border-bottom: none;
    }
    .recent-post-item .post-img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 10px;
        background: #f3f4f6;
        flex-shrink: 0;
    }
    .recent-post-item .post-img-placeholder {
        width: 60px;
        height: 60px;
        border-radius: 10px;
        background: linear-gradient(135deg, #dbeafe, #eff6ff);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
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
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .recent-post-item .post-info .title:hover {
        color: #2563eb;
    }
    .recent-post-item .post-info .date {
        font-size: 0.7rem;
        color: #9ca3af;
    }
    
    /* Subscribe Box */
    .subscribe-box {
        background: linear-gradient(135deg, #fef3c7, #fde68a);
        border-radius: 16px;
        padding: 25px;
        text-align: center;
    }
    .subscribe-box h4 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    .subscribe-box p {
        font-size: 0.85rem;
        color: #6b7280;
        margin-bottom: 15px;
    }
    .subscribe-box .input-group {
        display: flex;
        gap: 10px;
    }
    .subscribe-box input {
        flex: 1;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.85rem;
        outline: none;
    }
    .subscribe-box input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    .subscribe-box button {
        padding: 10px 20px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .subscribe-box button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245,158,11,0.3);
    }
    
    /* Tags */
    .tags-cloud {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .tags-cloud .tag {
        background: #f3f4f6;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        color: #4b5563;
        text-decoration: none;
        transition: all 0.3s;
    }
    .tags-cloud .tag:hover {
        background: #2563eb;
        color: white;
    }
    
    /* No Posts */
    .no-posts {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    .no-posts i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 15px;
    }
    .no-posts h4 {
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    /* Pagination */
    .pagination-custom {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin-top: 30px;
        flex-wrap: wrap;
    }
    .pagination-custom a, .pagination-custom span {
        padding: 8px 16px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.3s;
    }
    .pagination-custom .page-link {
        background: white;
        color: #4b5563;
        border: 1px solid #e5e7eb;
    }
    .pagination-custom .page-link:hover {
        background: #f3f4f6;
        border-color: #2563eb;
    }
    .pagination-custom .active {
        background: #2563eb;
        color: white;
        border-color: #2563eb;
    }
    .pagination-custom .disabled {
        opacity: 0.5;
        pointer-events: none;
    }
    
    @media (max-width: 992px) {
        .blog-wrapper {
            flex-direction: column;
        }
        .blog-sidebar {
            width: 100%;
        }
        .featured-post {
            min-height: 200px;
        }
        .featured-post h2 {
            font-size: 1.4rem;
        }
    }
    @media (max-width: 768px) {
        .blog-hero h1 {
            font-size: 1.8rem;
        }
        .blog-hero {
            padding: 40px 0;
        }
        .blog-card .card-image {
            height: 180px;
        }
        .blog-card .card-image-placeholder {
            height: 180px;
        }
        .subscribe-box .input-group {
            flex-direction: column;
        }
    }
    @media (max-width: 480px) {
        .blog-card .card-image {
            height: 150px;
        }
        .blog-card .card-image-placeholder {
            height: 150px;
        }
        .featured-post .overlay {
            padding: 20px;
        }
    }
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="blog-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-regular fa-newspaper"></i> Latest Articles
        </div>
        <h1><i class="fa-solid fa-blog"></i> Blog</h1>
        <p>Stay updated with the latest news, tips, and stories from our marketplace.</p>
        <?php if (!empty($search)): ?>
            <div style="margin-top:15px;color:rgba(255,255,255,0.8);font-size:0.95rem;">
                <i class="fa-solid fa-search"></i> Showing results for: <strong>"<?= sanitize($search) ?>"</strong>
                <a href="blog.php" class="btn btn-sm btn-outline-light ms-2">
                    <i class="fa-solid fa-times"></i> Clear
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    <div class="blog-wrapper">
        
        <!-- ==========================================
             MAIN CONTENT
        ========================================== -->
        <div class="blog-main">
            
            <!-- Featured Post -->
            <?php if ($featured_post && $page == 1 && empty($search) && $category_filter == 0): ?>
            <div class="featured-post" style="background-image: url('<?= !empty($featured_post['featured_image']) ? 'uploads/blog/' . $featured_post['featured_image'] : 'assets/images/blog-placeholder.jpg' ?>');">
                <div class="overlay">
                    <span class="featured-badge"><i class="fa-solid fa-star"></i> Featured Article</span>
                    <h2><a href="blog_post.php?id=<?= $featured_post['id'] ?>"><?= sanitize($featured_post['title']) ?></a></h2>
                    <div class="meta">
                        <span><i class="fa-regular fa-user"></i> <?= sanitize($featured_post['author_name']) ?></span>
                        <span><i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($featured_post['created_at'])) ?></span>
                        <span><i class="fa-regular fa-eye"></i> <?= number_format($featured_post['views']) ?> views</span>
                    </div>
                    <div class="excerpt"><?= sanitize(substr(strip_tags($featured_post['content']), 0, 200)) ?>...</div>
                    <a href="blog_post.php?id=<?= $featured_post['id'] ?>" class="read-more">
                        Read Full Article <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Blog Posts Grid -->
            <?php if ($posts && $posts->num_rows > 0): ?>
                <div class="row g-4">
                    <?php while ($post = $posts->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="blog-card">
                            <?php if (!empty($post['featured_image']) && file_exists('uploads/blog/' . $post['featured_image'])): ?>
                                <img src="uploads/blog/<?= $post['featured_image'] ?>" class="card-image" alt="<?= sanitize($post['title']) ?>">
                            <?php else: ?>
                                <div class="card-image-placeholder">
                                    <?php 
                                    $icons = ['📱', '🛍️', '🔒', '🤖', '💡', '🚀', '🎯', '📊'];
                                    $random_icon = $icons[array_rand($icons)];
                                    echo $random_icon;
                                    ?>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <?php if (!empty($post['category_name'])): ?>
                                    <span class="category-tag"><?= sanitize($post['category_name']) ?></span>
                                <?php endif; ?>
                                <h3><a href="blog_post.php?id=<?= $post['id'] ?>"><?= sanitize($post['title']) ?></a></h3>
                                <p class="excerpt"><?= sanitize(substr(strip_tags($post['content']), 0, 120)) ?>...</p>
                                <div class="meta">
                                    <span><i class="fa-regular fa-user"></i> <?= sanitize($post['author_name']) ?></span>
                                    <span><i class="fa-regular fa-calendar"></i> <?= date('M d', strtotime($post['created_at'])) ?></span>
                                    <span><i class="fa-regular fa-comment"></i> <?= $post['comment_count'] ?? 0 ?></span>
                                </div>
                                <a href="blog_post.php?id=<?= $post['id'] ?>" class="read-more">
                                    Read More <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination-custom">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>" class="page-link">
                            <i class="fa-solid fa-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="page-link disabled"><i class="fa-solid fa-chevron-left"></i> Previous</span>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-link active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>" class="page-link"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>" class="page-link">
                            Next <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-link disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="no-posts">
                    <i class="fa-regular fa-newspaper"></i>
                    <h4>No Articles Found</h4>
                    <p><?= !empty($search) ? "No results found for '<strong>" . sanitize($search) . "</strong>'. Try a different search term." : "Check back soon for the latest news and updates." ?></p>
                    <a href="blog.php" class="btn btn-primary mt-3">
                        <i class="fa-solid fa-arrow-left"></i> Back to Blog
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- ==========================================
             SIDEBAR
        ========================================== -->
        <div class="blog-sidebar">
            
            <!-- Search Widget -->
            <div class="sidebar-widget search-widget">
                <div class="widget-title"><i class="fa-solid fa-search"></i> Search Articles</div>
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search blog..." value="<?= sanitize($search) ?>">
                    <button type="submit"><i class="fa-solid fa-search"></i></button>
                </form>
            </div>
            
            <!-- Categories Widget -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fa-solid fa-folder-open"></i> Categories</div>
                <ul class="category-list">
                    <?php if ($categories && $categories->num_rows > 0): ?>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                        <li>
                            <a href="?category=<?= $cat['id'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">
                                <?= sanitize($cat['name']) ?>
                                <span class="count"><?= $cat['post_count'] ?? 0 ?></span>
                            </a>
                        </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <li class="text-muted">No categories found</li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Recent Posts Widget -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fa-regular fa-clock"></i> Recent Posts</div>
                <?php if ($recent_posts && $recent_posts->num_rows > 0): ?>
                    <?php while ($post = $recent_posts->fetch_assoc()): ?>
                    <div class="recent-post-item">
                        <?php if (!empty($post['featured_image']) && file_exists('uploads/blog/' . $post['featured_image'])): ?>
                            <img src="uploads/blog/<?= $post['featured_image'] ?>" class="post-img" alt="<?= sanitize($post['title']) ?>">
                        <?php else: ?>
                            <div class="post-img-placeholder">📄</div>
                        <?php endif; ?>
                        <div class="post-info">
                            <a href="blog_post.php?id=<?= $post['id'] ?>" class="title"><?= sanitize(substr($post['title'], 0, 40)) ?>...</a>
                            <div class="date"><i class="fa-regular fa-calendar"></i> <?= date('M d, Y', strtotime($post['created_at'])) ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-muted">No recent posts</p>
                <?php endif; ?>
            </div>
            
            <!-- Subscribe Widget -->
            <div class="subscribe-box">
                <h4><i class="fa-regular fa-envelope"></i> Subscribe</h4>
                <p>Get the latest articles delivered to your inbox.</p>
                <div class="input-group">
                    <input type="email" placeholder="Your email address">
                    <button>Subscribe</button>
                </div>
            </div>
            
            <!-- Tags Widget -->
            <div class="sidebar-widget">
                <div class="widget-title"><i class="fa-solid fa-tags"></i> Popular Tags</div>
                <div class="tags-cloud">
                    <a href="#" class="tag">Marketplace</a>
                    <a href="#" class="tag">Shopping</a>
                    <a href="#" class="tag">Deals</a>
                    <a href="#" class="tag">Tips</a>
                    <a href="#" class="tag">Products</a>
                    <a href="#" class="tag">Flash Sale</a>
                    <a href="#" class="tag">Tech</a>
                    <a href="#" class="tag">Fashion</a>
                    <a href="#" class="tag">Reviews</a>
                    <a href="#" class="tag">Sellers</a>
                </div>
            </div>
            
            <!-- Ad Space -->
            <div class="sidebar-widget" style="text-align:center; padding:30px; background:#f8fafc; border:2px dashed #d1d5db;">
                <p class="text-muted mb-0" style="font-size:0.8rem;">Advertisement</p>
                <div style="height:200px; display:flex; align-items:center; justify-content:center; color:#9ca3af;">
                    <i class="fa-solid fa-ad" style="font-size:2rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>