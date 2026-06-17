<?php
$page_title = 'Contact Seller';
require_once 'includes/header.php';
require_login();

$seller_id = intval($_GET['seller_id'] ?? 0);
$product_id = intval($_GET['product_id'] ?? 0);

// If no seller_id is provided, show list of sellers to chat with
if (!$seller_id) {
    // Get all sellers that the user has chatted with or all verified sellers
    $sellers_sql = "SELECT DISTINCT s.id, s.shop_name, s.shop_logo, u.name as owner_name,
                    (SELECT message FROM chats WHERE seller_id = s.id AND user_id = {$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM chats WHERE seller_id = s.id AND user_id = {$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 1) as last_time
                    FROM sellers s
                    JOIN users u ON u.id = s.user_id
                    LEFT JOIN chats c ON c.seller_id = s.id AND c.user_id = {$_SESSION['user_id']}
                    WHERE s.status = 'verified'
                    GROUP BY s.id
                    ORDER BY last_time DESC, s.shop_name ASC";
    $sellers_result = $mysqli->query($sellers_sql);
} else {
    // Get specific seller
    $seller_sql = "SELECT s.*, u.name as seller_name, u.email as seller_email 
                   FROM sellers s 
                   JOIN users u ON u.id = s.user_id 
                   WHERE s.id = ? AND s.status = 'verified'";
    $seller_stmt = $mysqli->prepare($seller_sql);
    $seller_stmt->bind_param('i', $seller_id);
    $seller_stmt->execute();
    $seller = $seller_stmt->get_result()->fetch_assoc();
    
    if (!$seller) {
        flash('Seller not found.', 'danger');
        redirect('shop.php');
    }
    
    // Get product info if provided
    $product = null;
    if ($product_id) {
        $product_sql = "SELECT name, id, price FROM products WHERE id = ? AND seller_id = ? AND status = 'approved'";
        $product_stmt = $mysqli->prepare($product_sql);
        $product_stmt->bind_param('ii', $product_id, $seller_id);
        $product_stmt->execute();
        $product = $product_stmt->get_result()->fetch_assoc();
    }
    
    // Get existing chat messages
    $chat_sql = "SELECT c.*, u.name as sender_name 
                 FROM chats c
                 JOIN users u ON u.id = c.user_id
                 WHERE (c.user_id = ? AND c.seller_id = ?) OR (c.seller_id = ? AND c.user_id = ?)
                 ORDER BY c.created_at ASC";
    $chat_stmt = $mysqli->prepare($chat_sql);
    $chat_stmt->bind_param('iiii', $_SESSION['user_id'], $seller_id, $seller_id, $_SESSION['user_id']);
    $chat_stmt->execute();
    $messages = $chat_stmt->get_result();
    
    // Handle sending new message
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
        $message = sanitize($_POST['message']);
        $sender = 'user';
        
        if (!empty($message)) {
            $insert_sql = "INSERT INTO chats (user_id, seller_id, message, sender, created_at) VALUES (?, ?, ?, ?, NOW())";
            $insert_stmt = $mysqli->prepare($insert_sql);
            $insert_stmt->bind_param('iiss', $_SESSION['user_id'], $seller_id, $message, $sender);
            $insert_stmt->execute();
            
            // Create notification for seller
            $notify_sql = "INSERT INTO notifications (user_id, type, title, message, is_read, created_at) 
                           VALUES (?, 'chat', 'New Message', 'You have a new message from a customer.', 0, NOW())";
            $notify_stmt = $mysqli->prepare($notify_sql);
            $notify_stmt->bind_param('i', $seller['user_id']);
            $notify_stmt->execute();
            
            flash('Message sent successfully!', 'success');
            redirect('chat.php?seller_id=' . $seller_id . ($product_id ? '&product_id=' . $product_id : ''));
        } else {
            flash('Please enter a message.', 'danger');
        }
    }
}
?>

<style>
    .chat-wrapper {
        display: flex;
        gap: 25px;
        min-height: calc(100vh - 300px);
    }
    .chat-sidebar {
        width: 320px;
        flex-shrink: 0;
    }
    .chat-main {
        flex: 1;
        background: white;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
    }
    .chat-header {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .seller-avatar {
        width: 60px;
        height: 60px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
    .seller-info h3 {
        margin: 0;
        font-size: 1.2rem;
    }
    .seller-info p {
        margin: 5px 0 0;
        font-size: 0.8rem;
        opacity: 0.9;
    }
    .chat-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        max-height: 500px;
        min-height: 400px;
        background: #f9fafb;
    }
    .message {
        display: flex;
        margin-bottom: 20px;
    }
    .message.sent {
        justify-content: flex-end;
    }
    .message.received {
        justify-content: flex-start;
    }
    .message-bubble {
        max-width: 70%;
        padding: 12px 16px;
        border-radius: 18px;
        position: relative;
    }
    .message.sent .message-bubble {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border-bottom-right-radius: 4px;
    }
    .message.received .message-bubble {
        background: white;
        color: #1f2937;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .message-time {
        font-size: 0.7rem;
        margin-top: 5px;
        opacity: 0.7;
        display: block;
    }
    .message.sent .message-time {
        text-align: right;
    }
    .chat-input-area {
        padding: 20px;
        border-top: 1px solid #e5e7eb;
        background: white;
    }
    .chat-input-group {
        display: flex;
        gap: 10px;
    }
    .chat-input {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #e5e7eb;
        border-radius: 25px;
        resize: none;
        font-family: inherit;
    }
    .chat-input:focus {
        outline: none;
        border-color: #2563eb;
    }
    .send-btn {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 0 25px;
        border-radius: 25px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .send-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    .product-card {
        background: #f3f4f6;
        border-radius: 12px;
        padding: 12px;
        margin: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .product-card img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 8px;
    }
    .product-card .product-name {
        font-weight: 600;
        font-size: 0.9rem;
    }
    .product-card .product-price {
        font-size: 0.8rem;
        color: #2563eb;
    }
    .no-messages {
        text-align: center;
        padding: 50px;
        color: #6b7280;
    }
    .chat-list {
        background: white;
        border-radius: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .chat-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px;
        border-bottom: 1px solid #e5e7eb;
        cursor: pointer;
        transition: background 0.3s ease;
        text-decoration: none;
        color: inherit;
    }
    .chat-list-item:hover {
        background: #f3f4f6;
    }
    .chat-list-item.active {
        background: #eff6ff;
        border-left: 3px solid #2563eb;
    }
    .chat-avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    .chat-info {
        flex: 1;
    }
    .chat-name {
        font-weight: 600;
        margin-bottom: 3px;
    }
    .chat-last-message {
        font-size: 0.75rem;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }
    .chat-time {
        font-size: 0.7rem;
        color: #9ca3af;
    }
    .start-chat-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .seller-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .seller-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 1px solid #e5e7eb;
    }
    .seller-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        border-color: #2563eb;
    }
    .seller-avatar-large {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        color: white;
        font-size: 2rem;
    }
    .seller-name-large {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 5px;
    }
    .btn-chat {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border: none;
        padding: 8px 20px;
        border-radius: 25px;
        margin-top: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn-chat:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
    }
    @media (max-width: 992px) {
        .chat-wrapper {
            flex-direction: column;
        }
        .chat-sidebar {
            width: 100%;
        }
        .message-bubble {
            max-width: 85%;
        }
        .seller-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container mb-5">
    <?php if (!$seller_id): ?>
        <!-- Show list of sellers to chat with -->
        <div class="start-chat-card">
            <h3><i class="fa-regular fa-comments"></i> Start a Conversation</h3>
            <p class="text-muted">Choose a seller to chat with</p>
            
            <?php if ($sellers_result && $sellers_result->num_rows > 0): ?>
                <div class="seller-grid">
                    <?php while($seller_item = $sellers_result->fetch_assoc()): ?>
                        <div class="seller-card" onclick="window.location.href='chat.php?seller_id=<?= $seller_item['id'] ?>'">
                            <div class="seller-avatar-large">
                                <i class="fa-solid fa-store"></i>
                            </div>
                            <div class="seller-name-large"><?= htmlspecialchars($seller_item['shop_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($seller_item['owner_name']) ?></div>
                            <button class="btn-chat">Message Seller</button>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fa-regular fa-store fa-3x text-muted mb-3"></i>
                    <p>No sellers available at the moment.</p>
                    <a href="shop.php" class="btn btn-primary">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Chat Area -->
        <div class="chat-wrapper">
            <!-- Chat List Sidebar -->
            <div class="chat-sidebar">
                <div class="chat-list">
                    <div class="p-3 bg-light border-bottom">
                        <strong><i class="fa-regular fa-message"></i> Recent Chats</strong>
                    </div>
                    <?php
                    $recent_chats = $mysqli->query("SELECT DISTINCT 
                        s.id as seller_id, s.shop_name, 
                        (SELECT message FROM chats WHERE seller_id = s.id AND user_id = {$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 1) as last_message,
                        (SELECT created_at FROM chats WHERE seller_id = s.id AND user_id = {$_SESSION['user_id']} ORDER BY created_at DESC LIMIT 1) as last_time
                        FROM chats c
                        JOIN sellers s ON s.id = c.seller_id
                        WHERE c.user_id = {$_SESSION['user_id']}
                        GROUP BY s.id
                        ORDER BY last_time DESC");
                    ?>
                    <?php if($recent_chats && $recent_chats->num_rows > 0): ?>
                        <?php while($chat = $recent_chats->fetch_assoc()): ?>
                            <a href="chat.php?seller_id=<?= $chat['seller_id'] ?>" class="chat-list-item <?= $seller_id == $chat['seller_id'] ? 'active' : '' ?>">
                                <div class="chat-avatar">
                                    <i class="fa-solid fa-store"></i>
                                </div>
                                <div class="chat-info">
                                    <div class="chat-name"><?= htmlspecialchars($chat['shop_name']) ?></div>
                                    <div class="chat-last-message"><?= htmlspecialchars(substr($chat['last_message'] ?? '', 0, 40)) ?>...</div>
                                </div>
                                <div class="chat-time">
                                    <?= $chat['last_time'] ? date('M d', strtotime($chat['last_time'])) : '' ?>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fa-regular fa-comments fa-2x mb-2 d-block"></i>
                            No conversations yet
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Area -->
            <div class="chat-main">
                <div class="chat-header">
                    <div class="seller-avatar">
                        <i class="fa-solid fa-store"></i>
                    </div>
                    <div class="seller-info">
                        <h3><?= htmlspecialchars($seller['shop_name']) ?></h3>
                        <p><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($seller['seller_email']) ?></p>
                    </div>
                </div>
                
                <?php if($product): ?>
                <div class="product-card">
                    <i class="fa-solid fa-box fa-2x text-muted"></i>
                    <div>
                        <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                        <div class="product-price">KSH <?= number_format($product['price']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="chat-messages" id="chatMessages">
                    <?php if(isset($messages) && $messages->num_rows > 0): ?>
                        <?php while($msg = $messages->fetch_assoc()): ?>
                            <div class="message <?= $msg['sender'] == 'user' ? 'sent' : 'received' ?>">
                                <div class="message-bubble">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    <span class="message-time"><?= date('h:i A, M d', strtotime($msg['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-messages">
                            <i class="fa-regular fa-comment-dots fa-3x mb-3 d-block text-muted"></i>
                            <p>No messages yet. Start a conversation with the seller!</p>
                            <small class="text-muted">Ask about products, shipping, or any questions you have.</small>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input-area">
                    <form method="post" id="chatForm">
                        <div class="chat-input-group">
                            <textarea name="message" class="chat-input" rows="2" placeholder="Type your message here..." required></textarea>
                            <button type="submit" name="send_message" class="send-btn">
                                <i class="fa-solid fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function scrollToBottom() {
    const messages = document.getElementById('chatMessages');
    if (messages) {
        messages.scrollTop = messages.scrollHeight;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
});

// Auto-submit on enter (but allow shift+enter for new line)
const chatInput = document.querySelector('.chat-input');
if (chatInput) {
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            document.getElementById('chatForm').submit();
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>