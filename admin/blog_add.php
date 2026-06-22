<?php
$page_title = 'Add Blog Post';
require_once '../includes/header.php';
require_role('admin');

// Get categories
$categories = $mysqli->query("SELECT * FROM blog_categories ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_post'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('admin/blog_add.php');
    }
    
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'draft');
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Handle image upload
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/blog/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['featured_image']['type'], $allowed)) {
            $ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $filename = 'blog_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $filename)) {
                $featured_image = $filename;
            }
        }
    }
    
    if (empty($title) || empty($content)) {
        flash('Please fill in title and content.', 'danger');
    } else {
        $sql = "INSERT INTO blog_posts (author_id, title, slug, excerpt, content, featured_image, category_id, status, created_at, published_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";
        $stmt = $mysqli->prepare($sql);
        $published_at = $status == 'published' ? date('Y-m-d H:i:s') : null;
        $stmt->bind_param('isssssiss', $_SESSION['user_id'], $title, $slug, $excerpt, $content, $featured_image, $category_id, $status, $published_at);
        
        if ($stmt->execute()) {
            flash('Blog post created successfully!', 'success');
            redirect('admin/blog.php');
        } else {
            flash('Failed to create post.', 'danger');
        }
        $stmt->close();
    }
}
?>

<style>
    .blog-form-wrapper {
        display: flex;
        gap: 25px;
    }
    .blog-form-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .blog-form-content {
        flex: 1;
    }
    .blog-form-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .blog-form-card .form-label {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 6px;
        display: block;
    }
    .blog-form-card .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    .blog-form-card .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        outline: none;
    }
    .blog-form-card textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    .blog-form-card .content-editor {
        min-height: 300px;
        font-family: monospace;
    }
    .btn-submit {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    .btn-cancel {
        background: #f3f4f6;
        color: #4b5563;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    .btn-cancel:hover {
        background: #e5e7eb;
    }
    .image-preview {
        max-width: 200px;
        max-height: 150px;
        border-radius: 8px;
        margin-top: 10px;
        border: 1px solid #e5e7eb;
    }
    @media (max-width: 992px) {
        .blog-form-wrapper {
            flex-direction: column;
        }
        .blog-form-sidebar {
            width: 100%;
        }
    }
</style>

<div class="container-fluid">
    <div class="blog-form-wrapper">
        <div class="blog-form-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="blog-form-content">
            <div class="blog-form-card">
                <h4><i class="fa-solid fa-pen-to-square"></i> Add New Blog Post</h4>
                <hr>
                
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="add_post" value="1">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="Enter post title" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control content-editor" rows="12" placeholder="Write your blog content here..." required></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Excerpt (Short Summary)</label>
                            <textarea name="excerpt" class="form-control" rows="3" placeholder="Brief summary of the post"></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control">
                                <option value="0">Uncategorized</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Featured Image</label>
                            <input type="file" name="featured_image" class="form-control" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Recommended: 1200x630px. JPG, PNG, GIF, WebP</small>
                            <div id="imagePreview"></div>
                        </div>
                        
                        <div class="col-12 d-flex gap-3">
                            <button type="submit" class="btn-submit">
                                <i class="fa-regular fa-paper-plane"></i> Publish Post
                            </button>
                            <a href="blog.php" class="btn-cancel">
                                <i class="fa-solid fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="image-preview">`;
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = '';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>