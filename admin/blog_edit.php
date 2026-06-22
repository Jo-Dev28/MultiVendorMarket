<?php
$page_title = 'Edit Blog Post';
require_once '../includes/header.php';
require_role('admin');

$post_id = intval($_GET['id'] ?? 0);

if ($post_id == 0) {
    flash('Invalid post ID.', 'danger');
    redirect('admin/blog.php');
}

// Get the post
$sql = "SELECT b.*, u.name as author_name, c.name as category_name 
        FROM blog_posts b
        JOIN users u ON u.id = b.author_id
        LEFT JOIN blog_categories c ON c.id = b.category_id
        WHERE b.id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    flash('Post not found.', 'danger');
    redirect('admin/blog.php');
}

// Get categories
$categories = $mysqli->query("SELECT * FROM blog_categories ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('admin/blog_edit.php?id=' . $post_id);
    }
    
    $title = sanitize($_POST['title'] ?? '');
    $content = $_POST['content'] ?? '';
    $excerpt = sanitize($_POST['excerpt'] ?? '');
    $category_id = intval($_POST['category_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? 'draft');
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Handle image upload
    $featured_image = $post['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/blog/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['featured_image']['type'], $allowed)) {
            // Delete old image
            if (!empty($post['featured_image']) && file_exists($upload_dir . $post['featured_image'])) {
                unlink($upload_dir . $post['featured_image']);
            }
            
            $ext = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $filename = 'blog_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $filename)) {
                $featured_image = $filename;
            }
        }
    }
    
    // Remove image
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        if (!empty($post['featured_image']) && file_exists('../uploads/blog/' . $post['featured_image'])) {
            unlink('../uploads/blog/' . $post['featured_image']);
        }
        $featured_image = '';
    }
    
    if (empty($title) || empty($content)) {
        flash('Please fill in title and content.', 'danger');
    } else {
        $sql = "UPDATE blog_posts SET 
                title = ?, 
                slug = ?, 
                excerpt = ?, 
                content = ?, 
                featured_image = ?, 
                category_id = ?, 
                status = ?, 
                updated_at = NOW() 
                WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssssisi', $title, $slug, $excerpt, $content, $featured_image, $category_id, $status, $post_id);
        
        if ($stmt->execute()) {
            flash('Blog post updated successfully!', 'success');
            redirect('admin/blog.php');
        } else {
            flash('Failed to update post.', 'danger');
        }
        $stmt->close();
    }
}
?>

<style>
    .blog-edit-wrapper {
        display: flex;
        gap: 25px;
    }
    .blog-edit-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .blog-edit-content {
        flex: 1;
    }
    .blog-edit-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
    }
    .blog-edit-card .form-label {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 6px;
        display: block;
    }
    .blog-edit-card .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    .blog-edit-card .form-control:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        outline: none;
    }
    .blog-edit-card textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }
    .blog-edit-card .content-editor {
        min-height: 400px;
        font-family: monospace;
        font-size: 0.9rem;
        line-height: 1.6;
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
    .current-image {
        margin-top: 10px;
        padding: 10px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    .current-image img {
        max-width: 120px;
        max-height: 80px;
        border-radius: 6px;
    }
    .post-info-box {
        background: #f8fafc;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 15px;
        border: 1px solid #e5e7eb;
    }
    .post-info-box .info-row {
        display: flex;
        padding: 4px 0;
        font-size: 0.85rem;
    }
    .post-info-box .info-row .label {
        font-weight: 600;
        width: 100px;
        color: #4b5563;
    }
    .post-info-box .info-row .value {
        color: #1f2937;
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
    @media (max-width: 992px) {
        .blog-edit-wrapper { flex-direction: column; }
        .blog-edit-sidebar { width: 100%; }
    }
</style>

<div class="container-fluid">
    <div class="blog-edit-wrapper">
        <div class="blog-edit-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="blog-edit-content">
            <div class="blog-edit-card">
                <h4><i class="fa-solid fa-pen-to-square"></i> Edit Blog Post</h4>
                <hr>
                
                <!-- Post Info -->
                <div class="post-info-box">
                    <div class="info-row">
                        <span class="label">Post ID:</span>
                        <span class="value">#<?= $post['id'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Author:</span>
                        <span class="value"><?= sanitize($post['author_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Created:</span>
                        <span class="value"><?= date('F d, Y h:i A', strtotime($post['created_at'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Status:</span>
                        <span class="value">
                            <span class="status-badge <?= $post['status'] ?>">
                                <?= ucfirst($post['status']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Views:</span>
                        <span class="value"><?= number_format($post['views']) ?></span>
                    </div>
                </div>
                
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="update_post" value="1">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea name="content" class="form-control content-editor" rows="15" required><?= htmlspecialchars($post['content']) ?></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Excerpt (Short Summary)</label>
                            <textarea name="excerpt" class="form-control" rows="3"><?= htmlspecialchars($post['excerpt']) ?></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control">
                                <option value="0">Uncategorized</option>
                                <?php 
                                $categories->data_seek(0);
                                while ($cat = $categories->fetch_assoc()): 
                                    $selected = ($cat['id'] == $post['category_id']) ? 'selected' : '';
                                ?>
                                    <option value="<?= $cat['id'] ?>" <?= $selected ?>><?= sanitize($cat['name']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="draft" <?= $post['status'] == 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= $post['status'] == 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= $post['status'] == 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Featured Image</label>
                            
                            <?php if (!empty($post['featured_image']) && file_exists('../uploads/blog/' . $post['featured_image'])): ?>
                                <div class="current-image">
                                    <img src="../uploads/blog/<?= $post['featured_image'] ?>" alt="Current featured image">
                                    <div>
                                        <p class="mb-0" style="font-size:0.85rem;">
                                            <strong>Current Image:</strong> <?= $post['featured_image'] ?>
                                        </p>
                                        <label class="form-check-label mt-1">
                                            <input type="checkbox" name="remove_image" value="1"> 
                                            <span style="color:#ef4444;">Remove this image</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <input type="file" name="featured_image" class="form-control mt-2" accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Recommended: 1200x630px. JPG, PNG, GIF, WebP</small>
                            <div id="imagePreview"></div>
                        </div>
                        
                        <div class="col-12 d-flex gap-3">
                            <button type="submit" class="btn-submit">
                                <i class="fa-regular fa-floppy-disk"></i> Update Post
                            </button>
                            <a href="blog.php" class="btn-cancel">
                                <i class="fa-solid fa-times"></i> Cancel
                            </a>
                            <a href="../blog_post.php?id=<?= $post['id'] ?>" target="_blank" class="btn-cancel" style="background:#2563eb; color:white;">
                                <i class="fa-regular fa-eye"></i> View Post
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