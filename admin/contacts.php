<?php
$page_title = 'Manage Contacts';
require_once '../includes/header.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('contacts.php');
    }
    
    $sql = "DELETE FROM contacts WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        flash('Contact message deleted successfully.', 'success');
    } else {
        flash('Failed to delete contact message.', 'danger');
    }
    $stmt->close();
    redirect('contacts.php');
}

// Handle mark as read
if (isset($_GET['read']) && is_numeric($_GET['read'])) {
    $id = intval($_GET['read']);
    $sql = "UPDATE contacts SET is_read = 1 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    redirect('contacts.php');
}

// Get all contacts
$sql = "SELECT * FROM contacts ORDER BY created_at DESC";
$result = $mysqli->query($sql);
?>

<style>
    .contacts-container {
        display: flex;
        gap: 25px;
    }
    .contacts-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .contacts-content {
        flex: 1;
    }
    .contacts-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }
    .contacts-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .contacts-table th {
        background: #f8fafc;
        padding: 14px 18px;
        text-align: left;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.85rem;
        border-bottom: 2px solid #e5e7eb;
    }
    .contacts-table td {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .contacts-table tr:hover {
        background: #f8fafc;
    }
    .contacts-table tr.unread {
        background: #eff6ff;
    }
    .contacts-table tr.unread td:first-child::before {
        content: '●';
        color: #2563eb;
        margin-right: 8px;
        font-size: 0.6rem;
    }
    .contact-name {
        font-weight: 600;
        color: #1f2937;
    }
    .contact-email {
        color: #6b7280;
        font-size: 0.8rem;
    }
    .contact-subject {
        font-weight: 500;
        color: #1f2937;
    }
    .contact-message {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #4b5563;
    }
    .contact-date {
        font-size: 0.8rem;
        color: #6b7280;
        white-space: nowrap;
    }
    .badge-unread {
        background: #2563eb;
        color: white;
        padding: 2px 10px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
    }
    .badge-read {
        background: #d1d5db;
        color: #4b5563;
        padding: 2px 10px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
    }
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
    }
    .btn-action.btn-view {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .btn-action.btn-view:hover {
        background: #bfdbfe;
    }
    .btn-action.btn-delete {
        background: #fee2e2;
        color: #dc2626;
    }
    .btn-action.btn-delete:hover {
        background: #fecaca;
    }
    .btn-action.btn-read {
        background: #d1fae5;
        color: #065f46;
    }
    .btn-action.btn-read:hover {
        background: #a7f3d0;
    }
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
    @media (max-width: 992px) {
        .contacts-container {
            flex-direction: column;
        }
        .contacts-sidebar {
            width: 100%;
        }
        .contacts-table {
            overflow-x: auto;
        }
        .contacts-table table {
            min-width: 700px;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="contacts-container">
        <div class="contacts-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="contacts-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fa-regular fa-envelope"></i> Contact Messages</h4>
                <span class="badge bg-primary rounded-pill"><?= $result->num_rows ?> Total</span>
            </div>
            
            <div class="contacts-table">
                <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name / Email</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($contact = $result->fetch_assoc()): 
                            $is_unread = !isset($contact['is_read']) || $contact['is_read'] == 0;
                        ?>
                        <tr class="<?= $is_unread ? 'unread' : '' ?>">
                            <td>
                                <div class="contact-name"><?= sanitize($contact['name']) ?></div>
                                <div class="contact-email"><?= sanitize($contact['email']) ?></div>
                            </td>
                            <td class="contact-subject"><?= sanitize($contact['subject']) ?></td>
                            <td class="contact-message"><?= sanitize($contact['message']) ?></td>
                            <td class="contact-date">
                                <?= date('M d, Y', strtotime($contact['created_at'])) ?>
                                <br>
                                <small><?= date('h:i A', strtotime($contact['created_at'])) ?></small>
                            </td>
                            <td>
                                <?php if ($is_unread): ?>
                                    <span class="badge-unread">Unread</span>
                                <?php else: ?>
                                    <span class="badge-read">Read</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                    <?php if ($is_unread): ?>
                                    <a href="contacts.php?read=<?= $contact['id'] ?>" class="btn-action btn-read" title="Mark as read">
                                        <i class="fa fa-check"></i> Read
                                    </a>
                                    <?php endif; ?>
                                    <button class="btn-action btn-view" onclick="viewMessage(<?= $contact['id'] ?>, '<?= addslashes($contact['name']) ?>', '<?= addslashes($contact['email']) ?>', '<?= addslashes($contact['subject']) ?>', '<?= addslashes($contact['message']) ?>')">
                                        <i class="fa-regular fa-eye"></i> View
                                    </button>
                                    <a href="contacts.php?delete=<?= $contact['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-action btn-delete" onclick="return confirm('Delete this message?')">
                                        <i class="fa fa-trash"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fa-regular fa-envelope-open"></i>
                    <p>No contact messages yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Message Modal -->
<div class="modal fade" id="viewMessageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-envelope"></i> Message Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold">From:</label>
                    <p id="modalName" class="mb-0"></p>
                    <p id="modalEmail" class="text-muted small"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Subject:</label>
                    <p id="modalSubject" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Message:</label>
                    <p id="modalMessage" class="mb-0" style="background: #f8fafc; padding: 12px; border-radius: 8px; white-space: pre-wrap;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewMessage(id, name, email, subject, message) {
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalEmail').textContent = email;
    document.getElementById('modalSubject').textContent = subject;
    document.getElementById('modalMessage').textContent = message;
    
    // Mark as read automatically
    fetch('contacts.php?read=' + id);
    
    const modal = new bootstrap.Modal(document.getElementById('viewMessageModal'));
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>