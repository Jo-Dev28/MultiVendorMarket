<?php
$page_title = 'Manage Support Tickets';
require_once '../includes/header.php';
require_role('admin');

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('admin/support.php');
    }
    
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $status = sanitize($_POST['status'] ?? '');
    $admin_reply = sanitize($_POST['admin_reply'] ?? '');
    
    if ($ticket_id > 0 && !empty($status)) {
        $sql = "UPDATE support_tickets SET status = ?, admin_reply = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $status, $admin_reply, $ticket_id);
        if ($stmt->execute()) {
            flash('Ticket updated successfully.', 'success');
        } else {
            flash('Failed to update ticket.', 'danger');
        }
        $stmt->close();
    }
    redirect('admin/support.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('admin/support.php');
    }
    
    $sql = "DELETE FROM support_tickets WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        flash('Ticket deleted successfully.', 'success');
    } else {
        flash('Failed to delete ticket.', 'danger');
    }
    $stmt->close();
    redirect('admin/support.php');
}

// Get all tickets
$sql = "SELECT t.*, u.name as user_name, u.email as user_email 
        FROM support_tickets t
        LEFT JOIN users u ON u.id = t.user_id
        ORDER BY CASE 
            WHEN t.status = 'open' THEN 1
            WHEN t.status = 'in-progress' THEN 2
            WHEN t.status = 'resolved' THEN 3
            WHEN t.status = 'closed' THEN 4
        END, t.created_at DESC";
$tickets = $mysqli->query($sql);

// Get ticket counts
$count_sql = "SELECT 
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress_count,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                COUNT(*) as total_count
              FROM support_tickets";
$counts = $mysqli->query($count_sql)->fetch_assoc();
?>

<style>
    .support-container {
        display: flex;
        gap: 25px;
    }
    .support-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .support-content {
        flex: 1;
    }
    .stats-row {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 18px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
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
    .stat-card .label i {
        margin-right: 4px;
    }
    .stat-card.open .number { color: #2563eb; }
    .stat-card.in-progress .number { color: #f59e0b; }
    .stat-card.resolved .number { color: #10b981; }
    .stat-card.closed .number { color: #6b7280; }
    .stat-card.total .number { color: #1f2937; }
    
    .tickets-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }
    .tickets-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .tickets-table th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.8rem;
        border-bottom: 2px solid #e5e7eb;
    }
    .tickets-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .tickets-table tr:hover {
        background: #f8fafc;
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
    }
    .status-badge.open { background: #dbeafe; color: #1d4ed8; }
    .status-badge.in-progress { background: #fef3c7; color: #92400e; }
    .status-badge.resolved { background: #d1fae5; color: #065f46; }
    .status-badge.closed { background: #e5e7eb; color: #4b5563; }
    
    .category-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 500;
        background: #f3f4f6;
        color: #4b5563;
    }
    .category-badge.account { background: #dbeafe; color: #1d4ed8; }
    .category-badge.payment { background: #d1fae5; color: #065f46; }
    .category-badge.order { background: #fef3c7; color: #92400e; }
    .category-badge.product { background: #fce4ec; color: #b91c1c; }
    .category-badge.other { background: #f3f4f6; color: #4b5563; }
    
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
    }
    .btn-action.btn-view { background: #dbeafe; color: #1d4ed8; }
    .btn-action.btn-view:hover { background: #bfdbfe; }
    .btn-action.btn-delete { background: #fee2e2; color: #dc2626; }
    .btn-action.btn-delete:hover { background: #fecaca; }
    .btn-action.btn-edit { background: #fef3c7; color: #92400e; }
    .btn-action.btn-edit:hover { background: #fde68a; }
    
    .empty-state {
        text-align: center;
        padding: 60px;
        color: #6b7280;
    }
    .empty-state i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 15px;
    }
    
    .ticket-message {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #4b5563;
    }
    
    @media (max-width: 992px) {
        .support-container {
            flex-direction: column;
        }
        .support-sidebar {
            width: 100%;
        }
        .stats-row {
            grid-template-columns: repeat(2, 1fr);
        }
        .tickets-table {
            overflow-x: auto;
        }
        .tickets-table table {
            min-width: 700px;
        }
    }
    @media (max-width: 576px) {
        .stats-row {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="support-container">
        <div class="support-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="support-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fa-solid fa-headset"></i> Support Tickets</h4>
                <span class="badge bg-primary rounded-pill"><?= $counts['total_count'] ?? 0 ?> Total</span>
            </div>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-card open">
                    <div class="number"><?= $counts['open_count'] ?? 0 ?></div>
                    <div class="label"><i class="fa-regular fa-circle"></i> Open</div>
                </div>
                <div class="stat-card in-progress">
                    <div class="number"><?= $counts['in_progress_count'] ?? 0 ?></div>
                    <div class="label"><i class="fa-solid fa-spinner"></i> In Progress</div>
                </div>
                <div class="stat-card resolved">
                    <div class="number"><?= $counts['resolved_count'] ?? 0 ?></div>
                    <div class="label"><i class="fa-regular fa-check-circle"></i> Resolved</div>
                </div>
                <div class="stat-card closed">
                    <div class="number"><?= $counts['closed_count'] ?? 0 ?></div>
                    <div class="label"><i class="fa-regular fa-circle-check"></i> Closed</div>
                </div>
                <div class="stat-card total">
                    <div class="number"><?= $counts['total_count'] ?? 0 ?></div>
                    <div class="label"><i class="fa-regular fa-ticket"></i> Total</div>
                </div>
            </div>
            
            <!-- Tickets Table -->
            <div class="tickets-table">
                <?php if ($tickets && $tickets->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ticket = $tickets->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $ticket['id'] ?></td>
                            <td>
                                <div style="font-weight:600; color:#1f2937;"><?= sanitize($ticket['user_name'] ?? 'Guest') ?></div>
                                <div style="font-size:0.7rem; color:#6b7280;"><?= sanitize($ticket['user_email'] ?? 'No email') ?></div>
                            </td>
                            <td>
                                <div style="font-weight:500; color:#1f2937;"><?= sanitize($ticket['subject']) ?></div>
                                <div class="ticket-message"><?= sanitize(substr($ticket['message'], 0, 60)) ?></div>
                            </td>
                            <td>
                                <span class="category-badge <?= $ticket['category'] ?>">
                                    <?= ucfirst($ticket['category']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= $ticket['status'] ?>">
                                    <?= ucfirst($ticket['status']) ?>
                                </span>
                            </td>
                            <td style="font-size:0.75rem; color:#6b7280;">
                                <?= date('M d, Y', strtotime($ticket['created_at'])) ?>
                                <br>
                                <small><?= date('h:i A', strtotime($ticket['created_at'])) ?></small>
                            </td>
                            <td>
                                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                    <button class="btn-action btn-view" onclick="viewTicket(<?= $ticket['id'] ?>, '<?= addslashes($ticket['subject']) ?>', '<?= addslashes($ticket['message']) ?>', '<?= addslashes($ticket['user_name'] ?? 'Guest') ?>', '<?= addslashes($ticket['user_email'] ?? '') ?>', '<?= $ticket['category'] ?>', '<?= $ticket['status'] ?>', '<?= addslashes($ticket['admin_reply'] ?? '') ?>')">
                                        <i class="fa-regular fa-eye"></i> View
                                    </button>
                                    <?php if ($ticket['status'] != 'closed' && $ticket['status'] != 'resolved'): ?>
                                    <button class="btn-action btn-edit" onclick="openReplyModal(<?= $ticket['id'] ?>, '<?= addslashes($ticket['subject']) ?>', '<?= addslashes($ticket['message']) ?>', '<?= addslashes($ticket['user_name'] ?? 'Guest') ?>', '<?= $ticket['status'] ?>')">
                                        <i class="fa fa-reply"></i> Reply
                                    </button>
                                    <?php endif; ?>
                                    <a href="support.php?delete=<?= $ticket['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-action btn-delete" onclick="return confirm('Delete this ticket?')">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-ticket"></i>
                    <p>No support tickets yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Ticket Modal -->
<div class="modal fade" id="viewTicketModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-ticket"></i> Ticket Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="fw-bold">Subject:</label>
                        <p id="viewSubject" class="mb-0"></p>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold">Category:</label>
                        <p id="viewCategory" class="mb-0"></p>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold">Status:</label>
                        <p id="viewStatus" class="mb-0"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">From:</label>
                    <p id="viewUser" class="mb-0"></p>
                    <p id="viewEmail" class="text-muted small"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Message:</label>
                    <p id="viewMessage" class="mb-0" style="background: #f8fafc; padding: 12px; border-radius: 8px; white-space: pre-wrap;"></p>
                </div>
                <div class="mb-3" id="viewReplySection" style="display:none;">
                    <label class="fw-bold">Admin Reply:</label>
                    <p id="viewReply" class="mb-0" style="background: #f0fdf4; padding: 12px; border-radius: 8px; border-left: 4px solid #10b981; white-space: pre-wrap;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-regular fa-reply"></i> Reply to Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="ticket_id" id="replyTicketId" value="">
                    
                    <div class="mb-3">
                        <label class="fw-bold">From:</label>
                        <p id="replyUser" class="mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Subject:</label>
                        <p id="replySubject" class="mb-0"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Original Message:</label>
                        <p id="replyOriginal" style="background: #f8fafc; padding: 10px; border-radius: 8px; font-size: 0.85rem; max-height: 100px; overflow-y: auto;"></p>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Admin Reply <span class="text-danger">*</span></label>
                        <textarea name="admin_reply" class="form-control" rows="5" placeholder="Type your reply here..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="fw-bold">Update Status</label>
                        <select name="status" class="form-select">
                            <option value="in-progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-regular fa-paper-plane"></i> Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewTicket(id, subject, message, userName, userEmail, category, status, adminReply) {
    document.getElementById('viewSubject').textContent = subject;
    document.getElementById('viewMessage').textContent = message;
    document.getElementById('viewUser').textContent = userName || 'Guest';
    document.getElementById('viewEmail').textContent = userEmail || 'No email provided';
    document.getElementById('viewCategory').textContent = category.charAt(0).toUpperCase() + category.slice(1);
    document.getElementById('viewStatus').innerHTML = `<span class="status-badge ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    
    const replySection = document.getElementById('viewReplySection');
    if (adminReply && adminReply.trim() !== '') {
        replySection.style.display = 'block';
        document.getElementById('viewReply').textContent = adminReply;
    } else {
        replySection.style.display = 'none';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('viewTicketModal'));
    modal.show();
}

function openReplyModal(id, subject, message, userName, status) {
    document.getElementById('replyTicketId').value = id;
    document.getElementById('replyUser').textContent = userName || 'Guest';
    document.getElementById('replySubject').textContent = subject;
    document.getElementById('replyOriginal').textContent = message;
    
    // Set default status
    const statusSelect = document.querySelector('select[name="status"]');
    if (status === 'open') {
        statusSelect.value = 'in-progress';
    } else {
        statusSelect.value = status;
    }
    
    const modal = new bootstrap.Modal(document.getElementById('replyModal'));
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>