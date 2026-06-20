<?php
$page_title = 'Chat with Seller';
require_once 'includes/header.php';
require_login();

$user_id = $_SESSION['user_id'];
$seller_id = intval($_GET['seller'] ?? 0);
$user_role = $_SESSION['role'] ?? 'customer';

// ============================================
// DEBUG - Uncomment to see what's happening
// ============================================
// error_log("User ID: $user_id, Seller ID: $seller_id, Role: $user_role");

// ============================================
// HANDLE SHARING RECEIPT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_receipt'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('chat.php?seller=' . $seller_id);
    }
    
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($order_id <= 0) {
        flash('Please select a valid order.', 'danger');
        redirect('chat.php?seller=' . $seller_id);
    }
    
    // Verify order
    $order_sql = "SELECT o.* FROM orders o 
                  WHERE o.id = ? AND o.user_id = ? AND o.seller_id = ?";
    $order_stmt = $mysqli->prepare($order_sql);
    $order_stmt->bind_param('iii', $order_id, $user_id, $seller_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
    $order_stmt->close();
    
    if (!$order) {
        flash('Order not found.', 'danger');
        redirect('chat.php?seller=' . $seller_id);
    }
    
    // Create message
    $message = "📄 Order Receipt Shared\n";
    $message .= "Order #: " . $order['order_number'] . "\n";
    $message .= "Total: KSH " . number_format($order['total_amount'], 2) . "\n";
    $message .= "Status: " . ucfirst($order['status']);
    
    $sql = "INSERT INTO chats (user_id, seller_id, message, sender, is_read, created_at) 
            VALUES (?, ?, ?, 'user', 0, NOW())";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iis', $user_id, $seller_id, $message);
        if ($stmt->execute()) {
            flash('Receipt shared successfully!', 'success');
            redirect('chat.php?seller=' . $seller_id);
        }
        $stmt->close();
    }
}

// ============================================
// HANDLE SHARING PRODUCT
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_product'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('chat.php?seller=' . $seller_id);
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id <= 0) {
        flash('Please select a product.', 'danger');
        redirect('chat.php?seller=' . $seller_id);
    }
    
    // Verify product belongs to this seller
    $product_sql = "SELECT p.*, s.shop_name FROM products p 
                    JOIN sellers s ON s.id = p.seller_id 
                    WHERE p.id = ? AND p.seller_id = ? AND p.status = 'approved'";
    $product_stmt = $mysqli->prepare($product_sql);
    $product_stmt->bind_param('ii', $product_id, $seller_id);
    $product_stmt->execute();
    $product = $product_stmt->get_result()->fetch_assoc();
    $product_stmt->close();
    
    if (!$product) {
        flash('Product not found.', 'danger');
        redirect('chat.php?seller=' . $seller_id);
    }
    
    // Create message
    $message = "🛍️ Product Shared\n";
    $message .= "📦 " . $product['name'] . "\n";
    $message .= "💰 Price: KSH " . number_format($product['price'], 2) . "\n";
    $message .= "🔗 View: product.php?id=" . $product['id'];
    
    $sql = "INSERT INTO chats (user_id, seller_id, message, sender, is_read, created_at) 
            VALUES (?, ?, ?, 'user', 0, NOW())";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iis', $user_id, $seller_id, $message);
        if ($stmt->execute()) {
            flash('Product shared successfully!', 'success');
            redirect('chat.php?seller=' . $seller_id);
        }
        $stmt->close();
    }
}

// ============================================
// IF NO SELLER SELECTED - SHOW SELLER LIST
// ============================================
if ($seller_id == 0) {
    // Get all sellers the customer has chatted with
    $sellers_sql = "SELECT DISTINCT s.id, s.shop_name, s.shop_logo,
                    (SELECT message FROM chats WHERE user_id = ? AND seller_id = s.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM chats WHERE user_id = ? AND seller_id = s.id ORDER BY created_at DESC LIMIT 1) as last_message_time
                    FROM chats c
                    JOIN sellers s ON s.id = c.seller_id
                    WHERE c.user_id = ?
                    GROUP BY s.id
                    ORDER BY last_message_time DESC";
    $sellers_stmt = $mysqli->prepare($sellers_sql);
    $sellers = null;
    if ($sellers_stmt) {
        $sellers_stmt->bind_param('iii', $user_id, $user_id, $user_id);
        $sellers_stmt->execute();
        $sellers = $sellers_stmt->get_result();
        $sellers_stmt->close();
    }
    
    // Get all verified sellers
    $all_sellers_sql = "SELECT s.id, s.shop_name, s.shop_logo, s.location 
                        FROM sellers s 
                        WHERE s.status = 'verified' 
                        ORDER BY s.shop_name ASC";
    $all_sellers = $mysqli->query($all_sellers_sql);
    ?>
    <style>
    .chat-wrapper{display:flex;gap:25px;min-height:600px}
    .chat-sidebar{width:280px;flex-shrink:0}
    .chat-content{flex:1}
    .chat-list-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 10px rgba(0,0,0,0.05);border:1px solid #e5e7eb;margin-bottom:20px}
    .chat-list-card .card-title{font-size:1.1rem;font-weight:700;color:#1f2937;margin-bottom:15px}
    .seller-item{display:flex;align-items:center;gap:15px;padding:12px 15px;border-bottom:1px solid #f1f5f9;transition:all .3s;cursor:pointer;text-decoration:none;color:inherit}
    .seller-item:hover{background:#f8fafc;transform:translateX(5px)}
    .seller-item:last-child{border-bottom:none}
    .seller-item .avatar{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;font-size:1.5rem;color:#2563eb;flex-shrink:0;overflow:hidden}
    .seller-item .avatar img{width:100%;height:100%;object-fit:cover}
    .seller-item .info{flex:1;min-width:0}
    .seller-item .info .name{font-weight:600;color:#1f2937}
    .seller-item .info .last-msg{font-size:.8rem;color:#9ca3af;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .seller-item .info .time{font-size:.7rem;color:#9ca3af}
    .seller-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-top:10px}
    .seller-grid-item{background:#f8fafc;border-radius:12px;padding:15px;text-align:center;transition:all .3s;cursor:pointer;text-decoration:none;color:inherit}
    .seller-grid-item:hover{background:#eff6ff;transform:translateY(-3px);box-shadow:0 5px 15px rgba(0,0,0,0.08)}
    .seller-grid-item .avatar{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:1.8rem;color:#2563eb;overflow:hidden}
    .seller-grid-item .avatar img{width:100%;height:100%;object-fit:cover}
    .seller-grid-item .name{font-weight:600;color:#1f2937;font-size:.9rem}
    .seller-grid-item .location{font-size:.75rem;color:#6b7280}
    .empty-state{text-align:center;padding:40px;color:#6b7280}
    .empty-state i{font-size:3rem;color:#d1d5db;margin-bottom:10px}
    @media(max-width:992px){.chat-wrapper{flex-direction:column}.chat-sidebar{width:100%}.seller-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.seller-grid{grid-template-columns:1fr}}
    </style>
    
    <div class="container py-4">
        <div class="chat-wrapper">
            <div class="chat-sidebar">
                <?php require_once 'includes/dashboard_sidebar.php'; ?>
            </div>
            <div class="chat-content">
                <div class="chat-list-card">
                    <div class="card-title"><i class="fa-regular fa-clock"></i> Recent Conversations</div>
                    <?php if ($sellers && $sellers->num_rows > 0): ?>
                        <?php while ($seller = $sellers->fetch_assoc()): ?>
                            <a href="chat.php?seller=<?= $seller['id'] ?>" class="seller-item">
                                <div class="avatar">
                                    <?php if (!empty($seller['shop_logo']) && file_exists('uploads/sellers/' . $seller['shop_logo'])): ?>
                                        <img src="uploads/sellers/<?= $seller['shop_logo'] ?>" alt="<?= sanitize($seller['shop_name']) ?>">
                                    <?php else: ?>
                                        <i class="fa-solid fa-store"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="info">
                                    <div class="name"><?= sanitize($seller['shop_name']) ?></div>
                                    <div class="last-msg"><?= sanitize(substr($seller['last_message'] ?? 'No messages yet', 0, 50)) ?></div>
                                </div>
                                <?php if ($seller['last_message_time']): ?>
                                    <span class="time"><?= date('h:i A', strtotime($seller['last_message_time'])) ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fa-regular fa-comment-dots"></i>
                            <p>You haven't chatted with any sellers yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="chat-list-card">
                    <div class="card-title"><i class="fa-solid fa-store"></i> All Sellers</div>
                    <div class="seller-grid">
                        <?php if ($all_sellers && $all_sellers->num_rows > 0): ?>
                            <?php while ($seller = $all_sellers->fetch_assoc()): ?>
                                <a href="chat.php?seller=<?= $seller['id'] ?>" class="seller-grid-item">
                                    <div class="avatar">
                                        <?php if (!empty($seller['shop_logo']) && file_exists('uploads/sellers/' . $seller['shop_logo'])): ?>
                                            <img src="uploads/sellers/<?= $seller['shop_logo'] ?>" alt="<?= sanitize($seller['shop_name']) ?>">
                                        <?php else: ?>
                                            <i class="fa-solid fa-store"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="name"><?= sanitize($seller['shop_name']) ?></div>
                                    <div class="location"><i class="fa-regular fa-location-dot"></i> <?= sanitize($seller['location'] ?? 'Online') ?></div>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state" style="grid-column:1/-1;">
                                <p>No sellers available.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once 'includes/footer.php'; ?>
    <?php exit;
}

// ============================================
// GET SELLER INFO
// ============================================
$seller_sql = "SELECT s.*, u.name as owner_name FROM sellers s JOIN users u ON u.id = s.user_id WHERE s.id = ? AND s.status = 'verified'";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $seller_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();
$seller_stmt->close();

if (!$seller) {
    flash('Seller not found.', 'danger');
    redirect('chat.php');
}

// ============================================
// HANDLE SENDING MESSAGE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('chat.php?seller=' . $seller_id);
    }
    
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($message)) {
        flash('Please enter a message.', 'danger');
    } else {
        $sql = "INSERT INTO chats (user_id, seller_id, message, sender, is_read, created_at) VALUES (?, ?, ?, 'user', 0, NOW())";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iis', $user_id, $seller_id, $message);
            if ($stmt->execute()) {
                flash('Message sent successfully!', 'success');
                redirect('chat.php?seller=' . $seller_id);
            }
            $stmt->close();
        }
    }
}

// ============================================
// GET MESSAGES
// ============================================
$messages = null;
$messages_sql = "SELECT c.*, u.name as sender_name FROM chats c
                 JOIN users u ON u.id = c.user_id
                 WHERE c.user_id = ? AND c.seller_id = ?
                 ORDER BY c.created_at ASC";
$messages_stmt = $mysqli->prepare($messages_sql);
if ($messages_stmt) {
    $messages_stmt->bind_param('ii', $user_id, $seller_id);
    $messages_stmt->execute();
    $messages = $messages_stmt->get_result();
    $messages_stmt->close();
}

// Mark messages as read
$update_sql = "UPDATE chats SET is_read = 1 WHERE user_id = ? AND seller_id = ? AND sender = 'seller' AND is_read = 0";
$update_stmt = $mysqli->prepare($update_sql);
if ($update_stmt) {
    $update_stmt->bind_param('ii', $user_id, $seller_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// ============================================
// GET ORDERS FOR RECEIPT SHARING
// ============================================
$orders = [];
$orders_sql = "SELECT o.* FROM orders o 
               WHERE o.user_id = ? AND o.seller_id = ? AND o.status NOT IN ('cancelled')
               ORDER BY o.created_at DESC LIMIT 20";
$orders_stmt = $mysqli->prepare($orders_sql);
if ($orders_stmt) {
    $orders_stmt->bind_param('ii', $user_id, $seller_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
    while ($row = $orders_result->fetch_assoc()) {
        $orders[] = $row;
    }
    $orders_stmt->close();
}

// ============================================
// GET PRODUCTS FOR SHARING
// ============================================
$products = [];
$products_sql = "SELECT p.* FROM products p 
                 WHERE p.seller_id = ? AND p.status = 'approved' AND p.stock > 0
                 ORDER BY p.created_at DESC LIMIT 30";
$products_stmt = $mysqli->prepare($products_sql);
if ($products_stmt) {
    $products_stmt->bind_param('i', $seller_id);
    $products_stmt->execute();
    $products_result = $products_stmt->get_result();
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
    $products_stmt->close();
}

// DEBUG - Uncomment to see data
// echo "<!-- Orders: " . count($orders) . " -->";
// echo "<!-- Products: " . count($products) . " -->";
?>

<style>
.chat-wrapper{display:flex;gap:25px;min-height:600px}
.chat-sidebar{width:280px;flex-shrink:0}
.chat-content{flex:1}
.chat-container{background:#fff;border-radius:16px;border:1px solid #e5e7eb;overflow:hidden;display:flex;flex-direction:column;height:700px}

.chat-header{background:#f8fafc;padding:15px 20px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:15px;flex-shrink:0;flex-wrap:wrap}
.chat-header .avatar{width:45px;height:45px;border-radius:50%;background:linear-gradient(135deg,#dbeafe,#bfdbfe);display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#2563eb;overflow:hidden;flex-shrink:0}
.chat-header .avatar img{width:100%;height:100%;object-fit:cover}
.chat-header .info .name{font-weight:600;color:#1f2937;font-size:1rem}
.chat-header .info .status{font-size:.75rem;color:#10b981;display:flex;align-items:center;gap:4px}
.chat-header .info .status .dot{width:6px;height:6px;background:#10b981;border-radius:50%;display:inline-block}
.chat-header .action-buttons{display:flex;gap:8px;margin-left:auto;flex-wrap:wrap}

.share-btn{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-size:.8rem;font-weight:600;border:none;cursor:pointer;transition:all .3s;text-decoration:none}
.share-btn-primary{background:#2563eb;color:#fff}
.share-btn-primary:hover{background:#1d4ed8;transform:translateY(-2px);box-shadow:0 4px 12px rgba(37,99,235,0.3)}
.share-btn-success{background:#10b981;color:#fff}
.share-btn-success:hover{background:#059669;transform:translateY(-2px);box-shadow:0 4px 12px rgba(16,185,129,0.3)}
.share-btn-secondary{background:#6b7280;color:#fff}
.share-btn-secondary:hover{background:#4b5563}

.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center}
.modal-overlay.active{display:flex}
.modal-content{background:#fff;border-radius:16px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;padding:24px;animation:modalSlideIn .3s ease}
@keyframes modalSlideIn{0%{transform:translateY(-20px);opacity:0}100%{transform:translateY(0);opacity:1}}
.modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;border-bottom:1px solid #e5e7eb;padding-bottom:12px}
.modal-header h3{font-size:1.2rem;font-weight:700;color:#1f2937;margin:0}
.modal-close{background:none;border:none;font-size:1.5rem;color:#6b7280;cursor:pointer;padding:0 8px}
.modal-close:hover{color:#1f2937}

.share-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-top:12px;max-height:300px;overflow-y:auto}
.share-item{background:#f8fafc;border-radius:10px;padding:12px;cursor:pointer;transition:all .3s;border:2px solid transparent;text-align:center}
.share-item:hover{background:#eff6ff;border-color:#2563eb;transform:translateY(-2px)}
.share-item.selected{background:#dbeafe;border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.2)}
.share-item .item-price{font-weight:700;color:#2563eb;font-size:1.1rem}
.share-item .item-name{font-weight:600;color:#1f2937;font-size:.9rem;margin:4px 0}
.share-item .item-status{font-size:.7rem;color:#6b7280}
.share-item .sale-badge{display:inline-block;background:#ef4444;color:#fff;font-size:.6rem;padding:1px 8px;border-radius:50px;margin-top:4px}

.chat-messages{flex:1;overflow-y:auto;padding:20px;background:#fafbfc}
.chat-message{margin-bottom:12px;max-width:75%;padding:10px 16px;border-radius:12px;position:relative;word-wrap:break-word}
.chat-message.sent{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;margin-left:auto;border-bottom-right-radius:4px}
.chat-message.received{background:#fff;color:#1f2937;border:1px solid #e5e7eb;margin-right:auto;border-bottom-left-radius:4px}
.chat-message .message-time{font-size:.6rem;opacity:.7;margin-top:4px;display:block}
.chat-message.sent .message-time{color:rgba(255,255,255,0.8)}
.chat-message.received .message-time{color:#9ca3af}
.chat-message .sender-name{font-size:.7rem;font-weight:600;margin-bottom:4px;display:block}
.chat-message.received .sender-name{color:#2563eb}

.chat-input-area{padding:15px 20px;border-top:1px solid #e5e7eb;background:#fff;flex-shrink:0}
.chat-input-area .input-wrapper{display:flex;gap:10px}
.chat-input-area input[type="text"]{flex:1;padding:10px 16px;border:1px solid #e5e7eb;border-radius:10px;font-size:.9rem;transition:all .3s}
.chat-input-area input[type="text"]:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1);outline:none}
.chat-input-area .btn-send{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:10px 24px;border-radius:10px;font-weight:600;cursor:pointer;transition:all .3s;display:flex;align-items:center;gap:6px}
.chat-input-area .btn-send:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(37,99,235,0.3)}
.no-messages{text-align:center;padding:40px;color:#6b7280}
.no-messages i{font-size:3rem;color:#d1d5db;margin-bottom:15px}
.no-items-badge{display:inline-block;background:#f3f4f6;color:#6b7280;padding:4px 12px;border-radius:50px;font-size:.7rem}

@media(max-width:992px){.chat-wrapper{flex-direction:column}.chat-sidebar{width:100%}}
@media(max-width:768px){.chat-container{height:550px}.chat-message{max-width:85%}.share-grid{grid-template-columns:1fr}.chat-header .action-buttons .share-btn span{display:none}.chat-input-area .input-wrapper{flex-wrap:wrap}.chat-input-area .btn-send{width:100%;justify-content:center}}
</style>

<div class="container py-4">
    <div class="chat-wrapper">
        <div class="chat-sidebar">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="chat-content">
            <div class="chat-container">
                
                <!-- Chat Header -->
                <div class="chat-header">
                    <a href="chat.php" class="back-btn" title="Back">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                    <div class="avatar">
                        <?php if (!empty($seller['shop_logo']) && file_exists('uploads/sellers/' . $seller['shop_logo'])): ?>
                            <img src="uploads/sellers/<?= $seller['shop_logo'] ?>" alt="<?= sanitize($seller['shop_name']) ?>">
                        <?php else: ?>
                            <i class="fa-solid fa-store"></i>
                        <?php endif; ?>
                    </div>
                    <div class="info">
                        <div class="name"><?= sanitize($seller['shop_name']) ?></div>
                        <div class="status"><span class="dot"></span> Online</div>
                    </div>
                    <div class="action-buttons">
                        <!-- Share Product Button -->
                        <button class="share-btn share-btn-primary" onclick="openShareModal('product')">
                            <i class="fa-solid fa-tag"></i> <span>Share Product</span>
                            <?php if (empty($products)): ?>
                                <span class="no-items-badge">No products</span>
                            <?php else: ?>
                                <span class="no-items-badge" style="background:#dbeafe;color:#2563eb;"><?= count($products) ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <!-- Share Receipt Button -->
                        <button class="share-btn share-btn-success" onclick="openShareModal('receipt')">
                            <i class="fa-regular fa-receipt"></i> <span>Share Receipt</span>
                            <?php if (empty($orders)): ?>
                                <span class="no-items-badge">No orders</span>
                            <?php else: ?>
                                <span class="no-items-badge" style="background:#d1fae5;color:#059669;"><?= count($orders) ?></span>
                            <?php endif; ?>
                        </button>
                    </div>
                </div>
                
                <!-- Messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if ($messages && $messages->num_rows > 0): ?>
                        <?php while ($msg = $messages->fetch_assoc()): 
                            $is_sent = $msg['sender'] == 'user';
                        ?>
                            <div class="chat-message <?= $is_sent ? 'sent' : 'received' ?>">
                                <?php if (!$is_sent): ?>
                                    <span class="sender-name"><?= sanitize($msg['sender_name']) ?></span>
                                <?php endif; ?>
                                <?= nl2br(sanitize($msg['message'])) ?>
                                <span class="message-time"><?= date('h:i A', strtotime($msg['created_at'])) ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-messages">
                            <i class="fa-regular fa-message"></i>
                            <p>No messages yet.</p>
                            <p style="font-size:0.85rem;">Start the conversation with <?= sanitize($seller['shop_name']) ?>.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Chat Input -->
                <div class="chat-input-area">
                    <form method="post" id="chatForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <input type="hidden" name="send_message" value="1">
                        <div class="input-wrapper">
                            <input type="text" name="message" placeholder="Type your message..." required autocomplete="off" id="chatInput">
                            <button type="submit" class="btn-send">
                                <i class="fa-regular fa-paper-plane"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- ============================================
   SHARE MODAL
============================================ -->
<div class="modal-overlay" id="shareModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Share</h3>
            <button class="modal-close" onclick="closeShareModal()">&times;</button>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

<script>
// ============================================
// SHARE MODAL FUNCTIONS
// ============================================
function openShareModal(type) {
    const modal = document.getElementById('shareModal');
    const title = document.getElementById('modalTitle');
    const body = document.getElementById('modalBody');
    
    if (type === 'receipt') {
        title.innerHTML = '<i class="fa-regular fa-receipt"></i> Share Receipt';
        <?php if (!empty($orders)): ?>
        let html = `
            <p style="color:#6b7280;font-size:.9rem;margin-bottom:15px;">
                Select an order to share the receipt:
            </p>
            <form method="post" id="shareReceiptForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="share_receipt" value="1">
                <input type="hidden" name="order_id" id="selectedOrderId" value="">
                <div class="share-grid">
                    <?php foreach ($orders as $order): ?>
                    <div class="share-item" onclick="selectReceipt(<?= $order['id'] ?>, this)">
                        <div class="item-name">Order #<?= $order['order_number'] ?></div>
                        <div class="item-price">KSH <?= number_format($order['total_amount'], 2) ?></div>
                        <div class="item-status">Status: <?= ucfirst($order['status']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:15px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="share-btn share-btn-secondary" onclick="closeShareModal()">Cancel</button>
                    <button type="submit" class="share-btn share-btn-success" id="shareReceiptBtn" disabled>
                        <i class="fa-regular fa-share-from-square"></i> Share Receipt
                    </button>
                </div>
            </form>
        `;
        body.innerHTML = html;
        <?php else: ?>
        body.innerHTML = `
            <div style="text-align:center;padding:30px;color:#6b7280;">
                <i class="fa-regular fa-receipt" style="font-size:3rem;color:#d1d5db;margin-bottom:15px;display:block;"></i>
                <p>No orders available to share.</p>
                <p style="font-size:.85rem;">You need to have orders with this seller first.</p>
            </div>
        `;
        <?php endif; ?>
    } else if (type === 'product') {
        title.innerHTML = '<i class="fa-solid fa-tag"></i> Share Product';
        <?php if (!empty($products)): ?>
        let html = `
            <p style="color:#6b7280;font-size:.9rem;margin-bottom:15px;">
                Select a product to share:
            </p>
            <form method="post" id="shareProductForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="share_product" value="1">
                <input type="hidden" name="product_id" id="selectedProductId" value="">
                <div class="share-grid">
                    <?php foreach ($products as $product): ?>
                    <div class="share-item" onclick="selectProduct(<?= $product['id'] ?>, this)">
                        <div class="item-name"><?= sanitize($product['name']) ?></div>
                        <div class="item-price">KSH <?= number_format($product['price'], 2) ?></div>
                        <div class="item-status">Stock: <?= $product['stock'] ?></div>
                        <?php if ($product['is_on_sale'] ?? 0): ?>
                            <div class="sale-badge">🔥 ON SALE</div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top:15px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" class="share-btn share-btn-secondary" onclick="closeShareModal()">Cancel</button>
                    <button type="submit" class="share-btn share-btn-primary" id="shareProductBtn" disabled>
                        <i class="fa-regular fa-share-from-square"></i> Share Product
                    </button>
                </div>
            </form>
        `;
        body.innerHTML = html;
        <?php else: ?>
        body.innerHTML = `
            <div style="text-align:center;padding:30px;color:#6b7280;">
                <i class="fa-solid fa-box" style="font-size:3rem;color:#d1d5db;margin-bottom:15px;display:block;"></i>
                <p>No products available to share.</p>
                <p style="font-size:.85rem;">This seller has no approved products yet.</p>
            </div>
        `;
        <?php endif; ?>
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeShareModal() {
    document.getElementById('shareModal').classList.remove('active');
    document.body.style.overflow = '';
}

function selectReceipt(orderId, element) {
    document.querySelectorAll('.share-item').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedOrderId').value = orderId;
    document.getElementById('shareReceiptBtn').disabled = false;
}

function selectProduct(productId, element) {
    document.querySelectorAll('.share-item').forEach(el => el.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selectedProductId').value = productId;
    document.getElementById('shareProductBtn').disabled = false;
}

function scrollToBottom() {
    const container = document.getElementById('chatMessages');
    if (container) container.scrollTop = container.scrollHeight;
}

document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'shareReceiptForm') {
            const orderId = document.getElementById('selectedOrderId').value;
            if (!orderId) {
                e.preventDefault();
                alert('Please select an order.');
                return false;
            }
        }
        if (e.target.id === 'shareProductForm') {
            const productId = document.getElementById('selectedProductId').value;
            if (!productId) {
                e.preventDefault();
                alert('Please select a product.');
                return false;
            }
        }
    });
});

document.getElementById('shareModal').addEventListener('click', function(e) {
    if (e.target === this) closeShareModal();
});

// Auto refresh every 15 seconds
setInterval(function() {
    if (!document.getElementById('shareModal').classList.contains('active')) {
        location.reload();
    }
}, 15000);
</script>

<?php require_once 'includes/footer.php'; ?>