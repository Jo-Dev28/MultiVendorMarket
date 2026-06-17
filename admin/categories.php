<?php
$page_title = 'Categories Management';
require_once '../includes/header.php';

if (($user['role'] ?? '') !== 'admin') { flash('Access denied.', 'danger'); redirect('index.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $slug = strtolower(str_replace(' ', '-', sanitize($_POST['slug'] ?? $name)));
    $description = sanitize($_POST['description'] ?? '');
    
    if (isset($_POST['add_category'])) {
        $mysqli->query("INSERT INTO categories (name, slug, description, created_at) VALUES ('$name', '$slug', '$description', NOW())");
        flash('Category added.', 'success');
    } elseif (isset($_POST['edit_category'])) {
        $id = intval($_POST['category_id']);
        $mysqli->query("UPDATE categories SET name='$name', slug='$slug', description='$description' WHERE id=$id");
        flash('Category updated.', 'success');
    }
    redirect('admin/categories.php');
}

if (isset($_GET['delete'])) { $id=intval($_GET['delete']); $mysqli->query("DELETE FROM categories WHERE id=$id"); flash('Category deleted.', 'success'); redirect('admin/categories.php'); }

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$where = $search ? "WHERE name LIKE '%$search%'" : "";
$total_result = $mysqli->query("SELECT COUNT(*) as total FROM categories $where");
$total_categories = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_categories / $limit);

$categories = $mysqli->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON p.category_id = c.id $where GROUP BY c.id ORDER BY c.name ASC LIMIT $offset, $limit");
?>

<style>
    .admin-content-wrapper { display: flex; gap: 25px; }
    .admin-sidebar-col { width: 280px; flex-shrink: 0; }
    .admin-main-col { flex: 1; }
    .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; }
    .search-input { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 15px; width: 250px; }
    @media (max-width: 992px) { .admin-content-wrapper { flex-direction: column; } .admin-sidebar-col { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col"><?php require_once '../includes/dashboard_sidebar.php'; ?></div>
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-folder"></i> Categories Management</h2>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addCategoryModal"><i class="fa-solid fa-plus"></i> Add Category</button>
            </div>
            
            <div class="filter-bar">
                <form method="GET" class="d-flex gap-2"><input type="text" name="search" class="search-input" placeholder="Search categories..." value="<?= htmlspecialchars($search) ?>"><button type="submit" class="btn btn-primary btn-sm">Search</button><?php if($search):?><a href="categories.php" class="btn btn-secondary btn-sm">Clear</a><?php endif;?></form>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>ID</th><th>Name</th><th>Slug</th><th>Products</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php while($cat=$categories->fetch_assoc()): ?>
                            <tr>
                                <td><?= $cat['id'] ?></td><td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                <td><?= htmlspecialchars($cat['slug']) ?></td><td><?= $cat['product_count'] ?></td>
                                <td><?= date('M d, Y', strtotime($cat['created_at'])) ?></td>
                                <td><button class="btn btn-sm btn-warning edit-category" data-id="<?=$cat['id']?>" data-name="<?=htmlspecialchars($cat['name'])?>" data-slug="<?=htmlspecialchars($cat['slug'])?>" data-description="<?=htmlspecialchars($cat['description']??'')?>" data-bs-toggle="modal" data-bs-target="#editCategoryModal"><i class="fa-solid fa-edit"></i></button> <a href="?delete=<?=$cat['id']?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fa-solid fa-trash"></i></a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($total_pages>1):?><nav class="mt-4"><ul class="pagination justify-content-center"><?php for($i=1;$i<=$total_pages;$i++):?><li class="page-item <?=$i==$page?'active':''?>"><a class="page-link" href="?page=<?=$i?>&search=<?=urlencode($search)?>"><?=$i?></a></li><?php endfor;?></ul></nav><?php endif;?>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><div class="modal-header"><h5 class="modal-title">Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div><div class="mb-3"><label>Slug</label><input type="text" name="slug" class="form-control" placeholder="auto"></div><div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="add_category" class="btn btn-primary">Add</button></div></form></div></div></div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="category_id" id="edit_id"><div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label>Name</label><input type="text" name="name" id="edit_name" class="form-control" required></div><div class="mb-3"><label>Slug</label><input type="text" name="slug" id="edit_slug" class="form-control"></div><div class="mb-3"><label>Description</label><textarea name="description" id="edit_description" class="form-control" rows="3"></textarea></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="edit_category" class="btn btn-primary">Save</button></div></form></div></div></div>

<script>
document.querySelectorAll('.edit-category').forEach(btn=>{btn.addEventListener('click',function(){document.getElementById('edit_id').value=this.dataset.id;document.getElementById('edit_name').value=this.dataset.name;document.getElementById('edit_slug').value=this.dataset.slug;document.getElementById('edit_description').value=this.dataset.description;});});
</script>

<?php require_once '../includes/footer.php'; ?>