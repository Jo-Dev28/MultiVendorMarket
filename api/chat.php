<?php
$page_title = 'Seller Messages';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT id FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

$customer_id = intval($_GET['customer_id'] ?? 0);

// Get existing chat messages
if ($customer_id) {
    $chat_sql = "SELECT c.*, u.name as customer_name 
                 FROM chats c
                 JOIN users u ON u.id = c.user_id
                 WHERE (c.user_id = ? AND c.seller_id = ?) OR (c.seller_id = ? AND c.user_id = ?)
                 ORDER BY c.created_at ASC";
    $chat_stmt = $mysqli->prepare($chat_sql);
    $chat_stmt->bind_param('iiii', $customer_id, $seller['id'], $seller['id'], $customer_id);
    $chat_stmt->execute();
    $messages = $chat_stmt->get_result();
}

// Handle sending new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $message = sanitize($_POST['message']);
    $customer_id = intval($_POST['customer_id']);
    $sender = 'seller';
    
    if (!empty($message) && $customer_id) {
        $insert_sql = "INSERT INTO chats (user_id, seller_id, message, sender, created_at) VALUES (?, ?, ?, ?, NOW())";
        $insert_stmt = $mysqli->prepare($insert_sql);
        $insert_stmt->bind_param('iiss', $customer_id, $seller['id'], $message, $sender);
        $insert_stmt->execute();
        
        flash('Message sent successfully!', 'success');
        redirect('seller/chat.php?customer_id=' . $customer_id);
    }
}

// Get recent customers who messaged
$recent_customers = $mysqli->query("SELECT DISTINCT 
    u.id as customer_id, u.name as customer_name, u.email as customer_email,
    (SELECT message FROM chats WHERE user_id = u.id AND seller_id = {$seller['id']} ORDER BY created_at DESC LIMIT 1) as last_message,
    (SELECT created_at FROM chats WHERE user_id = u.id AND seller_id = {$seller['id']} ORDER BY created_at DESC LIMIT 1) as last_time
    FROM chats c
    JOIN users u ON u.id = c.user_id
    WHERE c.seller_id = {$seller['id']}
    GROUP BY u.id
    ORDER BY last_time DESC");
?>

<style>
    .seller-chat-wrapper { display: flex; gap: 25px; min-height: calc(100vh - 300px); }
    .seller-chat-sidebar { width: 320px; flex-shrink: 0; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .seller-chat-main { flex: 1; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
    .chat-header { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 20px; display: flex; align-items: center; gap: 15px; }
    .customer-avatar { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .chat-messages { flex: 1; padding: 20px; overflow-y: auto; max-height: 500px; min-height: 400px; background: #f9fafb; }
    .message { display: flex; margin-bottom: 20px; }
    .message.sent { justify-content: flex-end; }
    .message.received { justify-content: flex-start; }
    .message-bubble { max-width: 70%; padding: 12px 16px; border-radius: 18px; }
    .message.sent .message-bubble { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border-bottom-right-radius: 4px; }
    .message.received .message-bubble { background: #f3f4f6; color: #1f2937; border-bottom-left-radius: 4px; }
    .message-time { font-size: 0.7rem; margin-top: 5px; opacity: 0.7; display: block; }
    .chat-input-area { padding: 20px; border-top: 1px solid #e5e7eb; }
    .chat-input-group { display: flex; gap: 10px; }
    .chat-input { flex: 1; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 25px; resize: none; }
    .send-btn { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; border: none; padding: 0 25px; border-radius: 25px; cursor: pointer; }
    .customer-list-item { display: flex; align-items: center; gap: 12px; padding: 15px; border-bottom: 1px solid #e5e7eb; cursor: pointer; text-decoration: none; color: inherit; }
    .customer-list-item:hover { background: #f3f4f6; }
    .customer-list-item.active { background: #eff6ff; border-left: 3px solid #2563eb; }
    .customer-avatar-small { width: 45px; height: 45px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; }
    .customer-info { flex: 1; }
    .customer-name { font-weight: 600; }
    .last-message { font-size: 0.7rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; }
    @media (max-width: 992px) { .seller-chat-wrapper { flex-direction: column; } .seller-chat-sidebar { width: 100%; } }
</style>

<div class="container-fluid">
    <div class="seller-chat-wrapper">
        <div class="seller-chat-sidebar">
            <div class="p-3 bg-light border-bottom"><strong><i class="fa-regular fa-message"></i> Conversations</strong></div>
            <?php if($recent_customers && $recent_customers->num_rows > 0): ?>
                <?php while($customer = $recent_customers->fetch_assoc()): ?>
                    <a href="chat.php?customer_id=<?= $customer['customer_id'] ?>" class="customer-list-item <?= $customer_id == $customer['customer_id'] ? 'active' : '' ?>">
                        <div class="customer-avatar-small"><i class="fa-regular fa-user"></i></div>
                        <div class="customer-info">
                            <div class="customer-name"><?= htmlspecialchars($customer['customer_name']) ?></div>
                            <div class="last-message"><?= htmlspecialchars(substr($customer['last_message'], 0, 40)) ?>...</div>
                        </div>
                        <div class="chat-time"><small><?= $customer['last_time'] ? date('M d', strtotime($customer['last_time'])) : '' ?></small></div>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-4 text-muted">No conversations yet</div>
            <?php endif; ?>
        </div>
        
        <div class="seller-chat-main">
            <?php if($customer_id): 
                $customer_info = $mysqli->query("SELECT name, email FROM users WHERE id = $customer_id")->fetch_assoc();
            ?>
            <div class="chat-header">
                <div class="customer-avatar"><i class="fa-regular fa-user"></i></div>
                <div><h3 class="mb-0"><?= htmlspecialchars($customer_info['name']) ?></h3><small><?= htmlspecialchars($customer_info['email']) ?></small></div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <?php if($messages->num_rows > 0): ?>
                    <?php while($msg = $messages->fetch_assoc()): ?>
                        <div class="message <?= $msg['sender'] == 'seller' ? 'sent' : 'received' ?>">
                            <div class="message-bubble">
                                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                <span class="message-time"><?= date('h:i A, M d', strtotime($msg['created_at'])) ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">No messages yet. Start a conversation!</div>
                <?php endif; ?>
            </div>
            
            <div class="chat-input-area">
                <form method="post">
                    <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
                    <div class="chat-input-group">
                        <textarea name="message" class="chat-input" rows="2" placeholder="Type your reply..." required></textarea>
                        <button type="submit" name="send_message" class="send-btn"><i class="fa-solid fa-paper-plane"></i> Send</button>
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="text-center py-5"><i class="fa-regular fa-comments fa-3x mb-3 text-muted"></i><p>Select a conversation to start chatting</p></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function scrollToBottom() { const msgs = document.getElementById('chatMessages'); if(msgs) msgs.scrollTop = msgs.scrollHeight; }
document.addEventListener('DOMContentLoaded', scrollToBottom);
</script>

<?php require_once '../includes/footer.php'; ?>
