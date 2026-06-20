<?php
$page_title = 'AI Logs';
require_once '../includes/header.php';
require_role('admin');

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('ai_logs.php');
    }
    
    $sql = "DELETE FROM ai_logs WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        flash('AI log deleted successfully.', 'success');
    } else {
        flash('Failed to delete AI log.', 'danger');
    }
    $stmt->close();
    redirect('ai_logs.php');
}

// Handle clear all
if (isset($_GET['clear_all'])) {
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('ai_logs.php');
    }
    
    $sql = "TRUNCATE TABLE ai_logs";
    if ($mysqli->query($sql)) {
        flash('All AI logs cleared successfully.', 'success');
    } else {
        flash('Failed to clear AI logs.', 'danger');
    }
    redirect('ai_logs.php');
}

// Get filter
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$user_filter = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Build query
$sql = "SELECT l.*, u.name as user_name, u.email as user_email 
        FROM ai_logs l
        LEFT JOIN users u ON u.id = l.user_id
        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (l.question LIKE ? OR l.response LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if ($user_filter > 0) {
    $sql .= " AND l.user_id = ?";
    $params[] = $user_filter;
    $types .= "i";
}

$sql .= " ORDER BY l.created_at DESC";

// Prepare and execute
$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM ai_logs";
$count_result = $mysqli->query($count_sql);
$total_logs = $count_result->fetch_assoc()['total'] ?? 0;

// Get unique users
$users_sql = "SELECT DISTINCT u.id, u.name, u.email 
              FROM ai_logs l 
              JOIN users u ON u.id = l.user_id 
              ORDER BY u.name";
$users = $mysqli->query($users_sql);
?>

<style>
    .ai-logs-container {
        display: flex;
        gap: 25px;
    }
    .ai-logs-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .ai-logs-content {
        flex: 1;
    }
    
    .ai-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }
    .ai-stat-card {
        background: white;
        border-radius: 12px;
        padding: 18px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }
    .ai-stat-card .number {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
    }
    .ai-stat-card .label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 4px;
    }
    .ai-stat-card .label i {
        margin-right: 4px;
    }
    .ai-stat-card.blue .number { color: #2563eb; }
    .ai-stat-card.green .number { color: #10b981; }
    .ai-stat-card.purple .number { color: #7c3aed; }
    .ai-stat-card.orange .number { color: #f59e0b; }
    
    .ai-logs-table {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }
    .ai-logs-table table {
        width: 100%;
        border-collapse: collapse;
    }
    .ai-logs-table th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.8rem;
        border-bottom: 2px solid #e5e7eb;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .ai-logs-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .ai-logs-table tr:hover {
        background: #f8fafc;
    }
    .ai-logs-table .question-cell {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #1f2937;
        font-weight: 500;
    }
    .ai-logs-table .response-cell {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #4b5563;
    }
    
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
    
    .filter-bar {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .filter-bar input, .filter-bar select {
        padding: 8px 14px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.85rem;
        outline: none;
        transition: all 0.3s;
    }
    .filter-bar input:focus, .filter-bar select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }
    .filter-bar .btn-filter {
        padding: 8px 20px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .filter-bar .btn-filter:hover {
        background: #1d4ed8;
    }
    .filter-bar .btn-clear {
        padding: 8px 20px;
        background: #f3f4f6;
        color: #4b5563;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
    }
    .filter-bar .btn-clear:hover {
        background: #e5e7eb;
    }
    
    @media (max-width: 992px) {
        .ai-logs-container {
            flex-direction: column;
        }
        .ai-logs-sidebar {
            width: 100%;
        }
        .ai-stats {
            grid-template-columns: repeat(2, 1fr);
        }
        .ai-logs-table {
            overflow-x: auto;
        }
        .ai-logs-table table {
            min-width: 700px;
        }
    }
    @media (max-width: 576px) {
        .ai-stats {
            grid-template-columns: 1fr 1fr;
        }
        .filter-bar {
            flex-direction: column;
        }
        .filter-bar input, .filter-bar select {
            width: 100%;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="ai-logs-container">
        <div class="ai-logs-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="ai-logs-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fa-solid fa-robot"></i> AI Chat Logs</h4>
                <div>
                    <a href="ai_logs.php?clear_all=1&csrf_token=<?= csrf_token() ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete all AI logs? This cannot be undone.')">
                        <i class="fa-regular fa-trash-can"></i> Clear All
                    </a>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="ai-stats">
                <div class="ai-stat-card blue">
                    <div class="number"><?= $total_logs ?></div>
                    <div class="label"><i class="fa-regular fa-message"></i> Total Queries</div>
                </div>
                <div class="ai-stat-card green">
                    <div class="number"><?= $logs->num_rows ?></div>
                    <div class="label"><i class="fa-regular fa-clock"></i> Showing</div>
                </div>
                <div class="ai-stat-card purple">
                    <div class="number"><?= $users->num_rows ?></div>
                    <div class="label"><i class="fa-regular fa-users"></i> Unique Users</div>
                </div>
                <div class="ai-stat-card orange">
                    <div class="number"><?= date('d M Y') ?></div>
                    <div class="label"><i class="fa-regular fa-calendar"></i> Today</div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="get" style="display:flex; gap:12px; flex-wrap:wrap; width:100%;">
                    <input type="text" name="search" placeholder="Search questions or responses..." value="<?= sanitize($search) ?>" style="flex:1; min-width:200px;">
                    <select name="user_id">
                        <option value="0">All Users</option>
                        <?php 
                        $users->data_seek(0);
                        while ($user = $users->fetch_assoc()): 
                        ?>
                            <option value="<?= $user['id'] ?>" <?= $user_filter == $user['id'] ? 'selected' : '' ?>>
                                <?= sanitize($user['name']) ?> (<?= sanitize($user['email']) ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn-filter"><i class="fa-solid fa-search"></i> Filter</button>
                    <a href="ai_logs.php" class="btn-clear"><i class="fa-solid fa-times"></i> Clear</a>
                </form>
            </div>
            
            <!-- Logs Table -->
            <div class="ai-logs-table" style="max-height: 600px; overflow-y: auto;">
                <?php if ($logs && $logs->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Question</th>
                            <th>Response</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                        <tr>
                            <td>#<?= $log['id'] ?></td>
                            <td>
                                <div style="font-weight:600; color:#1f2937;"><?= sanitize($log['user_name'] ?? 'Unknown') ?></div>
                                <div style="font-size:0.7rem; color:#6b7280;"><?= sanitize($log['user_email'] ?? 'No email') ?></div>
                            </td>
                            <td class="question-cell" title="<?= sanitize($log['question']) ?>">
                                <?= sanitize($log['question']) ?>
                            </td>
                            <td class="response-cell" title="<?= sanitize(strip_tags($log['response'])) ?>">
                                <?= sanitize(substr(strip_tags($log['response']), 0, 100)) ?>
                                <?php if (strlen(strip_tags($log['response'])) > 100): ?>...<?php endif; ?>
                            </td>
                            <td style="font-size:0.75rem; color:#6b7280; white-space:nowrap;">
                                <?= date('M d, Y', strtotime($log['created_at'])) ?>
                                <br>
                                <small><?= date('h:i A', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                                    <button class="btn-action btn-view" onclick="viewLog(<?= $log['id'] ?>, '<?= addslashes($log['user_name'] ?? 'Unknown') ?>', '<?= addslashes($log['user_email'] ?? 'No email') ?>', '<?= addslashes($log['question']) ?>', '<?= addslashes($log['response']) ?>', '<?= $log['created_at'] ?>')">
                                        <i class="fa-regular fa-eye"></i> View
                                    </button>
                                    <a href="ai_logs.php?delete=<?= $log['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn-action btn-delete" onclick="return confirm('Delete this log entry?')">
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
                    <i class="fa-regular fa-robot"></i>
                    <p>No AI logs found.</p>
                    <p style="font-size:0.85rem;"><?= !empty($search) ? 'Try adjusting your search filter.' : 'AI logs will appear here when users interact with the AI assistant.' ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Log Modal -->
<div class="modal fade" id="viewLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-robot"></i> AI Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="fw-bold">User:</label>
                    <p id="logUser" class="mb-0"></p>
                    <p id="logEmail" class="text-muted small"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Date:</label>
                    <p id="logDate" class="mb-0"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Question:</label>
                    <p id="logQuestion" class="mb-0" style="background: #eff6ff; padding: 12px; border-radius: 8px; border-left: 4px solid #2563eb;"></p>
                </div>
                <div class="mb-3">
                    <label class="fw-bold">Response:</label>
                    <p id="logResponse" class="mb-0" style="background: #f0fdf4; padding: 12px; border-radius: 8px; border-left: 4px solid #10b981; white-space: pre-wrap;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewLog(id, userName, userEmail, question, response, date) {
    document.getElementById('logUser').textContent = userName || 'Unknown';
    document.getElementById('logEmail').textContent = userEmail || 'No email provided';
    document.getElementById('logDate').textContent = date ? new Date(date).toLocaleString() : 'Unknown';
    document.getElementById('logQuestion').textContent = question;
    document.getElementById('logResponse').innerHTML = response;
    
    const modal = new bootstrap.Modal(document.getElementById('viewLogModal'));
    modal.show();
}
</script>

<?php require_once '../includes/footer.php'; ?>