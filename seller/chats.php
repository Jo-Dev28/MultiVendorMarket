<?php
$page_title = 'Messages';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT id, shop_name FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
if (!$seller_stmt) {
    flash('Database error: ' . $mysqli->error, 'danger');
    redirect('index.php');
}
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('seller/chats.php');
    }
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($message)) {
        flash('Please enter a message.', 'danger');
    } elseif ($customer_id == 0) {
        flash('Invalid customer.', 'danger');
    } else {
        $sql = "INSERT INTO chats (user_id, seller_id, message, sender, created_at) VALUES (?, ?, ?, 'seller', NOW())";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iis', $customer_id, $seller['id'], $message);
            
            if ($stmt->execute()) {
                // Create notification for customer
                $notif_sql = "INSERT INTO notifications (user_id, type, title, message, created_at) 
                              VALUES (?, 'chat', 'New Message', 'You have a new message from seller.', NOW())";
                $notif_stmt = $mysqli->prepare($notif_sql);
                if ($notif_stmt) {
                    $notif_stmt->bind_param('i', $customer_id);
                    $notif_stmt->execute();
                    $notif_stmt->close();
                }
                
                flash('Message sent successfully!', 'success');
                redirect('seller/chats.php?customer=' . $customer_id);
            } else {
                flash('Failed to send message.', 'danger');
            }
            $stmt->close();
        } else {
            flash('Database error: ' . $mysqli->error, 'danger');
        }
    }
}

// Get all customers who have messaged this seller
$customers_sql = "SELECT DISTINCT u.id, u.name, u.email,
                  (SELECT COUNT(*) FROM chats WHERE user_id = u.id AND seller_id = ? AND sender = 'user') as unread_count,
                  (SELECT message FROM chats WHERE user_id = u.id AND seller_id = ? ORDER BY created_at DESC LIMIT 1) as last_message,
                  (SELECT created_at FROM chats WHERE user_id = u.id AND seller_id = ? ORDER BY created_at DESC LIMIT 1) as last_message_time
                  FROM chats c
                  JOIN users u ON u.id = c.user_id
                  WHERE c.seller_id = ?
                  GROUP BY u.id
                  ORDER BY last_message_time DESC";
$customers_stmt = $mysqli->prepare($customers_sql);
if ($customers_stmt) {
    $customers_stmt->bind_param('iiii', $seller['id'], $seller['id'], $seller['id'], $seller['id']);
    $customers_stmt->execute();
    $customers = $customers_stmt->get_result();
    $customers_stmt->close();
} else {
    $customers = null;
}

// Get selected customer's messages
$selected_customer_id = intval($_GET['customer'] ?? 0);
$selected_customer = null;
$messages = [];

if ($selected_customer_id > 0) {
    // Get customer info
    $customer_sql = "SELECT id, name, email FROM users WHERE id = ?";
    $customer_stmt = $mysqli->prepare($customer_sql);
    if ($customer_stmt) {
        $customer_stmt->bind_param('i', $selected_customer_id);
        $customer_stmt->execute();
        $selected_customer = $customer_stmt->get_result()->fetch_assoc();
        $customer_stmt->close();
    }
    
    if ($selected_customer) {
        // Get messages
        $messages_sql = "SELECT c.*, u.name as sender_name 
                         FROM chats c
                         JOIN users u ON u.id = c.user_id
                         WHERE c.user_id = ? AND c.seller_id = ?
                         ORDER BY c.created_at ASC";
        $messages_stmt = $mysqli->prepare($messages_sql);
        if ($messages_stmt) {
            $messages_stmt->bind_param('ii', $selected_customer_id, $seller['id']);
            $messages_stmt->execute();
            $messages = $messages_stmt->get_result();
            $messages_stmt->close();
        }
    }
}

// Get unread count for sidebar
$unread_count = 0;
$count_sql = "SELECT COUNT(*) as count FROM chats WHERE seller_id = ? AND sender = 'user'";
$count_stmt = $mysqli->prepare($count_sql);
if ($count_stmt) {
    $count_stmt->bind_param('i', $seller['id']);
    $count_stmt->execute();
    $unread_count = $count_stmt->get_result()->fetch_assoc()['count'];
    $count_stmt->close();
}
?>

<style>
/* ============================================
   MESSAGES PAGE - MODERN CLEAN DESIGN
============================================ */

/* ---------- MAIN CONTAINER ---------- */
.chat-wrapper {
    display: flex;
    gap: 25px;
    min-height: 600px;
}

.chat-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.chat-content {
    flex: 1;
}

/* ---------- CHAT LAYOUT ---------- */
.chat-container {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    display: flex;
    height: 650px;
}

/* ---------- CUSTOMERS LIST ---------- */
.chat-customers {
    width: 300px;
    border-right: 1px solid #e5e7eb;
    overflow-y: auto;
    flex-shrink: 0;
}

.chat-customers-header {
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 600;
    color: #1f2937;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 5;
}

.chat-customers-header .unread-badge {
    background: #ef4444;
    color: white;
    padding: 2px 10px;
    border-radius: 50px;
    font-size: 0.7rem;
}

.customer-item {
    padding: 12px 20px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.customer-item:hover {
    background: #f8fafc;
}

.customer-item.active {
    background: #eff6ff;
    border-left: 3px solid #2563eb;
}

.customer-item .customer-info {
    flex: 1;
    min-width: 0;
}

.customer-item .customer-name {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9rem;
}

.customer-item .customer-email {
    font-size: 0.7rem;
    color: #6b7280;
}

.customer-item .last-message {
    font-size: 0.75rem;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 150px;
}

.customer-item .unread-badge-small {
    background: #ef4444;
    color: white;
    padding: 2px 8px;
    border-radius: 50px;
    font-size: 0.6rem;
    font-weight: 600;
    margin-left: 8px;
    flex-shrink: 0;
}

.customer-item .chat-time {
    font-size: 0.6rem;
    color: #9ca3af;
    flex-shrink: 0;
    margin-left: 8px;
}

.no-customers {
    padding: 40px 20px;
    text-align: center;
    color: #6b7280;
}

.no-customers i {
    font-size: 2.5rem;
    color: #d1d5db;
    margin-bottom: 10px;
}

/* ---------- CHAT AREA ---------- */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-area-header {
    padding: 15px 20px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-area-header .customer-name {
    font-weight: 600;
    color: #1f2937;
}

.chat-area-header .customer-email {
    font-size: 0.75rem;
    color: #6b7280;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #fafbfc;
}

.chat-message {
    margin-bottom: 12px;
    max-width: 70%;
    padding: 10px 16px;
    border-radius: 12px;
    position: relative;
    word-wrap: break-word;
}

.chat-message.sent {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}

.chat-message.received {
    background: white;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    margin-right: auto;
    border-bottom-left-radius: 4px;
}

.chat-message .message-time {
    font-size: 0.6rem;
    opacity: 0.7;
    margin-top: 4px;
    display: block;
}

.chat-message.sent .message-time {
    color: rgba(255,255,255,0.8);
}

.chat-message.received .message-time {
    color: #9ca3af;
}

.chat-message .sender-name {
    font-size: 0.7rem;
    font-weight: 600;
    margin-bottom: 4px;
    display: block;
}

.chat-message.received .sender-name {
    color: #2563eb;
}

.no-messages {
    text-align: center;
    padding: 40px;
    color: #6b7280;
}

.no-messages i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 15px;
}

/* ---------- MESSAGE INPUT ---------- */
.chat-input-area {
    padding: 15px 20px;
    border-top: 1px solid #e5e7eb;
    background: white;
}

.chat-input-area form {
    display: flex;
    gap: 10px;
}

.chat-input-area input[type="text"] {
    flex: 1;
    padding: 10px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.chat-input-area input[type="text"]:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.chat-input-area .btn-send {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.chat-input-area .btn-send:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

.chat-input-area .btn-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 992px) {
    .chat-wrapper {
        flex-direction: column;
    }
    
    .chat-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .chat-container {
        flex-direction: column;
        height: 550px;
    }
    
    .chat-customers {
        width: 100%;
        height: 200px;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .chat-message {
        max-width: 85%;
    }
    
    .chat-input-area form {
        flex-direction: column;
    }
    
    .chat-input-area .btn-send {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .chat-customers {
        height: 150px;
    }
    
    .chat-message {
        max-width: 95%;
        font-size: 0.85rem;
        padding: 8px 12px;
    }
}
</style>

<div class="container-fluid py-4">
    <div class="chat-wrapper">
        <!-- Sidebar -->
        <div class="chat-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Chat Main -->
        <div class="chat-content">
            <div class="chat-container">
                
                <!-- Customers List -->
                <div class="chat-customers">
                    <div class="chat-customers-header">
                        <span><i class="fa-regular fa-message"></i> Conversations</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="unread-badge"><?= $unread_count ?> new</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($customers && $customers->num_rows > 0): ?>
                        <?php while ($customer = $customers->fetch_assoc()): ?>
                            <div class="customer-item <?= $selected_customer_id == $customer['id'] ? 'active' : '' ?>" 
                                 onclick="window.location.href='chats.php?customer=<?= $customer['id'] ?>'">
                                <div class="customer-info">
                                    <div class="customer-name">
                                        <?= sanitize($customer['name']) ?>
                                        <?php if ($customer['unread_count'] > 0): ?>
                                            <span class="unread-badge-small"><?= $customer['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="customer-email"><?= sanitize($customer['email']) ?></div>
                                    <div class="last-message">
                                        <?= sanitize(substr($customer['last_message'] ?? '', 0, 50)) ?>
                                    </div>
                                </div>
                                <?php if ($customer['last_message_time']): ?>
                                    <span class="chat-time">
                                        <?= date('h:i A', strtotime($customer['last_message_time'])) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-customers">
                            <i class="fa-regular fa-inbox"></i>
                            <p>No conversations yet.</p>
                            <p style="font-size:0.8rem;">When customers message you, they'll appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Area -->
                <div class="chat-area">
                    <?php if ($selected_customer): ?>
                        <!-- Header -->
                        <div class="chat-area-header">
                            <div>
                                <div class="customer-name">
                                    <i class="fa-regular fa-user-circle"></i> <?= sanitize($selected_customer['name']) ?>
                                </div>
                                <div class="customer-email"><?= sanitize($selected_customer['email']) ?></div>
                            </div>
                            <div>
                                <a href="../profile.php?id=<?= $selected_customer['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fa-regular fa-user"></i>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <div class="chat-messages" id="chatMessages">
                            <?php if ($messages && $messages->num_rows > 0): ?>
                                <?php while ($msg = $messages->fetch_assoc()): ?>
                                    <div class="chat-message <?= $msg['sender'] == 'seller' ? 'sent' : 'received' ?>">
                                        <?php if ($msg['sender'] == 'user'): ?>
                                            <span class="sender-name"><?= sanitize($msg['sender_name']) ?></span>
                                        <?php endif; ?>
                                        <?= nl2br(sanitize($msg['message'])) ?>
                                        <span class="message-time">
                                            <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-messages">
                                    <i class="fa-regular fa-message"></i>
                                    <p>No messages yet.</p>
                                    <p style="font-size:0.85rem;">Start the conversation with <?= sanitize($selected_customer['name']) ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Input -->
                        <div class="chat-input-area">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="customer_id" value="<?= $selected_customer['id'] ?>">
                                <input type="hidden" name="send_message" value="1">
                                <input type="text" name="message" placeholder="Type your message..." required autocomplete="off">
                                <button type="submit" class="btn-send">
                                    <i class="fa-regular fa-paper-plane"></i> Send
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- No Customer Selected -->
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; flex-direction:column; color:#6b7280;">
                            <i class="fa-regular fa-message" style="font-size:4rem; color:#d1d5db; margin-bottom:15px;"></i>
                            <h4 style="color:#1f2937;">Select a Conversation</h4>
                            <p>Choose a customer from the left to start chatting.</p>
                            <?php if ($customers && $customers->num_rows == 0): ?>
                                <p style="font-size:0.85rem; margin-top:10px;">You don't have any messages yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script>
// Auto-scroll to bottom of messages
function scrollToBottom() {
    const container = document.getElementById('chatMessages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Scroll to bottom on page load
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    
    // Auto-scroll when new message is sent
    const form = document.querySelector('.chat-input-area form');
    if (form) {
        form.addEventListener('submit', function() {
            setTimeout(scrollToBottom, 100);
        });
    }
});

// Refresh page every 30 seconds to check for new messages
setInterval(function() {
    const selectedCustomer = <?= $selected_customer_id ?: 0 ?>;
    if (selectedCustomer > 0) {
        // Optional: AJAX refresh here
    }
}, 30000);
</script>

<?php require_once '../includes/footer.php'; ?>