<?php
$page_title = 'Offers & Coupons';
require_once '../includes/header.php';

if (($user['role'] ?? '') !== 'admin') { flash('Access denied.', 'danger'); redirect('index.php'); }

// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $description = sanitize($_POST['description'] ?? '');
    $discount_percent = intval($_POST['discount_percent'] ?? 0);
    $expires_at = sanitize($_POST['expires_at'] ?? '');
    
    if (isset($_POST['add_offer'])) {
        $mysqli->query("INSERT INTO offers (code, description, discount_percent, expires_at, active, created_at) VALUES ('$code', '$description', $discount_percent, '$expires_at', 1, NOW())");
        flash('Offer added successfully.', 'success');
    } elseif (isset($_POST['edit_offer'])) {
        $id = intval($_POST['offer_id']);
        $mysqli->query("UPDATE offers SET code='$code', description='$description', discount_percent=$discount_percent, expires_at='$expires_at' WHERE id=$id");
        flash('Offer updated successfully.', 'success');
    }
    redirect('admin/offers.php');
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $active = $_GET['active'] == '1' ? 0 : 1;
    $mysqli->query("UPDATE offers SET active = $active WHERE id = $id");
    flash('Offer status updated.', 'success');
    redirect('admin/offers.php');
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $mysqli->query("DELETE FROM offers WHERE id = $id");
    flash('Offer deleted.', 'success');
    redirect('admin/offers.php');
}

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$where = $search ? "WHERE code LIKE '%$search%' OR description LIKE '%$search%'" : "";
$total_result = $mysqli->query("SELECT COUNT(*) as total FROM offers $where");
$total_offers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_offers / $limit);

$offers = $mysqli->query("SELECT * FROM offers $where ORDER BY created_at DESC LIMIT $offset, $limit");
?>

<style>
    .admin-content-wrapper { display: flex; gap: 25px; }
    .admin-sidebar-col { width: 280px; flex-shrink: 0; }
    .admin-main-col { flex: 1; }
    .filter-bar { background: white; border-radius: 16px; padding: 20px; margin-bottom: 20px; }
    .search-input { border: 1px solid #e5e7eb; border-radius: 12px; padding: 10px 15px; width: 250px; }
    .offer-code { font-family: monospace; font-weight: bold; background: #f3f4f6; padding: 4px 8px; border-radius: 6px; }
    @media (max-width: 992px) { .admin-content-wrapper { flex-direction: column; } .admin-sidebar-col { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="admin-content-wrapper">
        <div class="admin-sidebar-col"><?php require_once '../includes/dashboard_sidebar.php'; ?></div>
        <div class="admin-main-col">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fa-solid fa-tag"></i> Offers & Coupons</h2>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addOfferModal"><i class="fa-solid fa-plus"></i> Add Offer</button>
            </div>
            
            <div class="filter-bar">
                <form method="GET" class="d-flex gap-2">
                    <input type="text" name="search" class="search-input" placeholder="Search by code or description..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    <?php if($search):?><a href="offers.php" class="btn btn-secondary btn-sm">Clear</a><?php endif;?>
                </form>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>ID</th><th>Code</th><th>Description</th><th>Discount</th><th>Expires</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php while($offer=$offers->fetch_assoc()): ?>
                            <tr>
                                <td><?= $offer['id'] ?></td>
                                <td><span class="offer-code"><?= $offer['code'] ?></span></td>
                                <td><?= htmlspecialchars($offer['description']) ?></td>
                                <td><span class="badge bg-success"><?= $offer['discount_percent'] ?>% OFF</span></td>
                                <td><?= date('M d, Y', strtotime($offer['expires_at'])) ?></td>
                                <td><?= $offer['active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?></td>
                                <td><?= date('M d, Y', strtotime($offer['created_at'])) ?></td>
                                <td>
                                    <a href="?toggle=<?= $offer['id'] ?>&active=<?= $offer['active'] ?>" class="btn btn-sm btn-<?= $offer['active'] ? 'warning' : 'success' ?>"><i class="fa-solid fa-<?= $offer['active'] ? 'pause' : 'play' ?>"></i></a>
                                    <button class="btn btn-sm btn-warning edit-offer" data-id="<?=$offer['id']?>" data-code="<?=$offer['code']?>" data-description="<?=htmlspecialchars($offer['description'])?>" data-discount="<?=$offer['discount_percent']?>" data-expires="<?=$offer['expires_at']?>" data-bs-toggle="modal" data-bs-target="#editOfferModal"><i class="fa-solid fa-edit"></i></button>
                                    <a href="?delete=<?= $offer['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this offer?')"><i class="fa-solid fa-trash"></i></a>
                                 </td
                             </tr
                            <?php endwhile; ?>
                            <?php if($offers->num_rows==0):?><tr><td colspan="8" class="text-center py-4">No offers found. </tr><?php endif;?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($total_pages>1):?><nav class="mt-4"><ul class="pagination justify-content-center"><?php for($i=1;$i<=$total_pages;$i++):?><li class="page-item <?=$i==$page?'active':''?>"><a class="page-link" href="?page=<?=$i?>&search=<?=urlencode($search)?>"><?=$i?></a></li><?php endfor;?></ul></nav><?php endif;?>
        </div>
    </div>
</div>

<!-- Add Offer Modal -->
<div class="modal fade" id="addOfferModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><div class="modal-header"><h5 class="modal-title">Add Offer</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label>Coupon Code</label><input type="text" name="code" class="form-control" placeholder="SUMMER20" required></div><div class="mb-3"><label>Description</label><input type="text" name="description" class="form-control" placeholder="20% off summer sale"></div><div class="mb-3"><label>Discount (%)</label><input type="number" name="discount_percent" class="form-control" min="1" max="100" required></div><div class="mb-3"><label>Expiry Date</label><input type="date" name="expires_at" class="form-control" required></div></div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="add_offer" class="btn btn-primary">Add Offer</button></div></form></div></div></div>

<!-- Edit Offer Modal -->
<div class="modal fade" id="editOfferModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><form method="post"><input type="hidden" name="offer_id" id="edit_id"><div class="modal-header"><h5 class="modal-title">Edit Offer</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="mb-3"><label>Coupon Code</label><input type="text" name="code" id="edit_code" class="form-control" required></div><div class="mb-3"><label>Description</label><input type="text" name="description" id="edit_description" class="form-control"></div><div class="mb-3"><label>Discount (%)</label><input type="number" name="discount_percent" id="edit_discount" class="form-control" min="1" max="100" required></div><div class="mb-3"><label>Expiry Date</label><input type="date" name="expires_at" id="edit_expires" class="form-control" required></div></div><div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="edit_offer" class="btn btn-primary">Save Changes</button></div></form></div></div></div>

<script>
document.querySelectorAll('.edit-offer').forEach(btn=>{btn.addEventListener('click',function(){document.getElementById('edit_id').value=this.dataset.id;document.getElementById('edit_code').value=this.dataset.code;document.getElementById('edit_description').value=this.dataset.description;document.getElementById('edit_discount').value=this.dataset.discount;document.getElementById('edit_expires').value=this.dataset.expires;});});
</script>

<?php require_once '../includes/footer.php'; ?>