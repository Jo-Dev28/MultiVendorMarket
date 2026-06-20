<?php
$page_title = 'Manage Chats';
require_once '../includes/header.php';
require_role('admin');

$user_id = $_SESSION['user_id'];

// Get all chat conversations with seller and customer info
$sql = "SELECT 
            c.seller_id,
            c.user_id as customer_id,
            s.shop_name,
            s.shop_logo,
            u.name as customer_name,
            u.email as customer_email,
            COUNT(*) as total_messages,
            SUM(CASE WHEN c.is_read = 0 AND c.sender = 'user' THEN 1 ELSE 0 END) as unread_count,
            MAX(c.created_at) as last_message_time,
            (SELECT message FROM chats WHERE seller_id = c.seller_id AND user_id = c.user_id ORDER BY created_at DESC LIMIT 1) as last_message
        FROM chats c
        JOIN sellers s ON s.id = c.seller_id
        JOIN users u ON u.id = c.user_id
        GROUP BY c.seller_id, c.user_id
        ORDER BY last_message_time DESC";
$conversations = $mysqli->query($sql);

// Get selected conversation
$selected_seller_id = intval($_GET['seller_id'] ?? 0);
$selected_customer_id = intval($_GET['customer_id'] ?? 0);
$messages = null;

if ($selected_seller_id > 0 && $selected_customer_id > 0) {
    $messages_sql = "SELECT c.*, 
                     u.name as customer_name,
                     s.shop_name,
                     CASE WHEN c.sender = 'user' THEN u.name ELSE s.shop_name END as sender_name
                     FROM chats c
                     JOIN users u ON u.id = c.user_id
                     JOIN sellers s ON s.id = c.seller_id
                     WHERE c.seller_id = ? AND c.user_id = ?
                     ORDER BY c.created_at ASC";
    $messages_stmt = $mysqli->prepare($messages_sql);
    $messages_stmt->bind_param('ii', $selected_seller_id, $selected_customer_id);
    $messages_stmt->execute();
    $messages = $messages_stmt->get_result();
    $messages_stmt->close();
}

// Handle delete conversation
if (isset($_GET['delete']) && $_GET['delete'] == 'conversation') {
    $seller_id = intval($_GET['seller_id'] ?? 0);
    $customer_id = intval($_GET['customer_id'] ?? 0);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    if (!csrf_validate($csrf_token)) {
        flash('Invalid security token.', 'danger');
        redirect('chats.php');
    }
    
    if ($seller_id > 0 && $customer_id > 0) {
        $sql = "DELETE FROM chats WHERE seller_id = ? AND user_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $seller_id, $customer_id);
        if ($stmt->execute()) {
            flash('Conversation deleted successfully.', 'success');
        } else {
            flash('Failed to delete conversation.', 'danger');
        }
        $stmt->close();
    }
    redirect('chats.php');
}

// Handle mark all as read
if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
    $seller_id = intval($_GET['seller_id'] ?? 0);
    $customer_id = intval($_GET['customer_id'] ?? 0);
    
    if ($seller_id > 0 && $customer_id > 0) {
        $sql = "UPDATE chats SET is_read = 1 WHERE seller_id = ? AND user_id = ? AND sender = 'user'";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $seller_id, $customer_id);
        $stmt->execute();
        $stmt->close();
    }
    redirect('chats.php?seller_id=' . $seller_id . '&customer_id=' . $customer_id);
}
?>

<style>
    .chats-container {
        display: flex;
        gap: 25px;
        min-height: 600px;
    }
    .chats-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .chats-content {
        flex: 1;
    }
    .chats-wrapper {
        background: white;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        display: flex;
        height: 650px;
    }
    .conversations-list {
        width: 350px;
        border-right: 1px solid #e5e7eb;
        overflow-y: auto;
        flex-shrink: 0;
    }
    .conversations-list-header {
        padding: 15px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        color: #1f2937;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .conversation-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .conversation-item:hover {
        background: #f8fafc;
    }
    .conversation-item.active {
        background: #eff6ff;
        border-left: 3px solid #2563eb;
    }
    .conversation-item .avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        color: #2563eb;
        flex-shrink: 0;
        overflow: hidden;
    }
    .conversation-item .avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .conversation-item .info {
        flex: 1;
        min-width: 0;
    }
    .conversation-item .info .shop-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.9rem;
    }
    .conversation-item .info .customer-name {
        font-size: 0.75rem;
        color: #6b7280;
    }
    .conversation-item .info .last-msg {
        font-size: 0.75rem;
        color: #9ca3af;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .conversation-item .unread-badge {
        background: #ef4444;
        color: white;
        padding: 2px 8px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        animation: pulse 2s infinite;
    }
    .conversation-item .time {
        font-size: 0.6rem;
        color: #9ca3af;
        flex-shrink: 0;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    .chat-area-admin {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .chat-area-header {
        padding: 12px 18px;
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }
    .chat-area-header .header-info .shop-name {
        font-weight: 600;
        color: #1f2937;
    }
    .chat-area-header .header-info .customer-name {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .chat-messages-admin {
        flex: 1;
        overflow-y: auto;
        padding: 15px 18px;
        background: #fafbfc;
    }
    .chat-message {
        margin-bottom: 6px;
        max-width: 75%;
        padding: 8px 14px;
        border-radius: 10px;
        position: relative;
        word-wrap: break-word;
        font-size: 0.85rem;
        line-height: 1.4;
    }
    .chat-message.sent {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        margin-left: auto;
        border-bottom-right-radius: 3px;
    }
    .chat-message.received {
        background: white;
        color: #1f2937;
        border: 1px solid #e5e7eb;
        margin-right: auto;
        border-bottom-left-radius: 3px;
    }
    .chat-message .message-time {
        font-size: 0.5rem;
        opacity: 0.7;
        margin-top: 2px;
        display: block;
        text-align: right;
    }
    .chat-message .sender-name {
        font-size: 0.6rem;
        font-weight: 600;
        margin-bottom: 2px;
        display: block;
        color: #2563eb;
    }
    .chat-message.sent .sender-name {
        color: rgba(255,255,255,0.8);
    }
    
    .no-conversation {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        flex-direction: column;
        color: #6b7280;
    }
    .no-conversation i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 12px;
    }
    .no-conversation h5 {
        color: #1f2937;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #6b7280;
    }
    .empty-state i {
        font-size: 2.5rem;
        color: #d1d5db;
        margin-bottom: 10px;
    }
    
    @media (max-width: 992px) {
        .chats-container {
            flex-direction: column;
        }
        .chats-sidebar {
            width: 100%;
        }
        .chats-wrapper {
            flex-direction: column;
            height: 600px;
        }
        .conversations-list {
            width: 100%;
            height: 200px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="chats-container">
        <div class="chats-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="chats-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4><i class="fa-regular fa-message"></i> Chat Management</h4>
                <span class="badge bg-primary rounded-pill"><?= $conversations->num_rows ?> Conversations</span>
            </div>
            
            <div class="chats-wrapper">
                <!-- Conversations List -->
                <div class="conversations-list">
                    <div class="conversations-list-header">
                        <i class="fa-regular fa-comment-dots"></i> Conversations
                    </div>
                    
                    <?php if ($conversations && $conversations->num_rows > 0): ?>
                        <?php while ($conv = $conversations->fetch_assoc()): 
                            $has_unread = isset($conv['unread_count']) && $conv['unread_count'] > 0;
                            $is_active = ($selected_seller_id == $conv['seller_id'] && $selected_customer_id == $conv['customer_id']);
                        ?>
                        <div class="conversation-item <?= $is_active ? 'active' : '' ?>" 
                             onclick="window.location.href='chats.php?seller_id=<?= $conv['seller_id'] ?>&customer_id=<?= $conv['customer_id'] ?>'">
                            <div class="avatar">
                                <?php if (!empty($conv['shop_logo']) && file_exists('../uploads/sellers/' . $conv['shop_logo'])): ?>
                                    <img src="../uploads/sellers/<?= $conv['shop_logo'] ?>" alt="<?= sanitize($conv['shop_name']) ?>">
                                <?php else: ?>
                                    <i class="fa-solid fa-store"></i>
                                <?php endif; ?>
                            </div>
                            <div class="info">
                                <div class="shop-name">
                                    <?= sanitize($conv['shop_name']) ?>
                                    <?php if ($has_unread): ?>
                                        <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="customer-name">Customer: <?= sanitize($conv['customer_name']) ?></div>
                                <div class="last-msg"><?= sanitize(substr($conv['last_message'] ?? '', 0, 60)) ?></div>
                            </div>
                            <div class="time"><?= date('h:i A', strtotime($conv['last_message_time'])) ?></div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-inbox"></i>
                            <p>No chat conversations yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Area -->
                <div class="chat-area-admin">
                    <?php if ($selected_seller_id > 0 && $selected_customer_id > 0 && $messages): 
                        // Get conversation details
                        $conv_sql = "SELECT s.shop_name, u.name as customer_name FROM sellers s JOIN users u ON u.id = ? WHERE s.id = ?";
                        $conv_stmt = $mysqli->prepare($conv_sql);
                        $conv_stmt->bind_param('ii', $selected_customer_id, $selected_seller_id);
                        $conv_stmt->execute();
                        $conv_details = $conv_stmt->get_result()->fetch_assoc();
                        $conv_stmt->close();
                    ?>
                        <!-- Header -->
                        <div class="chat-area-header">
                            <div class="header-info">
                                <div class="shop-name">
                                    <i class="fa-solid fa-store"></i> <?= sanitize($conv_details['shop_name'] ?? 'Unknown Shop') ?>
                                </div>
                                <div class="customer-name">
                                    Customer: <?= sanitize($conv_details['customer_name'] ?? 'Unknown Customer') ?>
                                </div>
                            </div>
                            <div>
                                <a href="chats.php?mark_read=all&seller_id=<?= $selected_seller_id ?>&customer_id=<?= $selected_customer_id ?>" class="btn btn-sm btn-outline-success" title="Mark all as read">
                                    <i class="fa fa-check-double"></i> Mark Read
                                </a>
                                <a href="chats.php?delete=conversation&seller_id=<?= $selected_seller_id ?>&customer_id=<?= $selected_customer_id ?>&csrf_token=<?= csrf_token() ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this entire conversation?')">
                                    <i class="fa fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <div class="chat-messages-admin" id="adminChatMessages">
                            <?php while ($msg = $messages->fetch_assoc()): 
                                $is_sent = $msg['sender'] == 'seller';
                            ?>
                                <div class="chat-message <?= $is_sent ? 'sent' : 'received' ?>">
                                    <span class="sender-name">
                                        <?= $is_sent ? 'Seller' : sanitize($msg['customer_name']) ?>
                                    </span>
                                    <?= nl2br(sanitize($msg['message'])) ?>
                                    <span class="message-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></span>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                    <?php else: ?>
                        <div class="no-conversation">
                            <i class="fa-regular fa-comment-dots"></i>
                            <h5>Select a Conversation</h5>
                            <p>Choose a conversation from the left to view messages.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function scrollToBottom() {
    const container = document.getElementById('adminChatMessages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}
document.addEventListener('DOMContentLoaded', scrollToBottom);
</script>

<?php require_once '../includes/footer.php'; ?>