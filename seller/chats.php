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

$seller_id = $seller['id'];

// ============================================
// HANDLE PHOTO UPLOAD
// ============================================
function handlePhotoUpload($file, $upload_dir) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Upload failed: ' . $file['error']];
    }
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['error' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File too large. Max 5MB allowed.'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'chat_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath
        ];
    }
    
    return ['error' => 'Failed to save file.'];
}

// ============================================
// HANDLE SENDING PHOTO
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_photo'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('seller/chats.php');
    }
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    
    if ($customer_id == 0) {
        flash('Invalid customer.', 'danger');
        redirect('seller/chats.php');
    }
    
    if (!isset($_FILES['photo_file']) || $_FILES['photo_file']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['photo_file']['error'] ?? 0;
        $error_messages = [
            1 => 'File exceeds upload_max_filesize',
            2 => 'File exceeds MAX_FILE_SIZE',
            3 => 'File was only partially uploaded',
            4 => 'No file was selected',
            6 => 'Missing temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'File upload stopped by extension'
        ];
        $error_msg = $error_messages[$error_code] ?? 'Unknown upload error';
        flash('Error uploading photo: ' . $error_msg, 'danger');
        redirect('seller/chats.php?customer=' . $customer_id);
    }
    
    $upload_result = handlePhotoUpload($_FILES['photo_file'], 'uploads/chat_photos/');
    
    if (isset($upload_result['error'])) {
        flash('Error uploading photo: ' . $upload_result['error'], 'danger');
        redirect('seller/chats.php?customer=' . $customer_id);
    }
    
    $photo_url = 'uploads/chat_photos/' . $upload_result['filename'];
    $message = "📸 Photo Shared\n";
    $message .= "📷 View photo: " . $photo_url;
    
    $table_check = $mysqli->query("SHOW TABLES LIKE 'chats'");
    if ($table_check && $table_check->num_rows > 0) {
        $sql = "INSERT INTO chats (user_id, seller_id, message, sender, is_read, created_at) VALUES (?, ?, ?, 'seller', 0, NOW())";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('iis', $customer_id, $seller_id, $message);
            if ($stmt->execute()) {
                flash('Photo sent successfully!', 'success');
                redirect('seller/chats.php?customer=' . $customer_id);
            }
            $stmt->close();
        }
    }
}

// ============================================
// HANDLE SHARING RECEIPT (Seller shares with customer)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_receipt'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('seller/chats.php');
    }
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $order_id = intval($_POST['order_id'] ?? 0);
    
    if ($customer_id == 0 || $order_id == 0) {
        flash('Invalid data.', 'danger');
        redirect('seller/chats.php');
    }
    
    // Get full order details
    $order_sql = "SELECT o.*, u.name as customer_name, s.shop_name 
                  FROM orders o 
                  JOIN users u ON u.id = o.user_id 
                  JOIN sellers s ON s.id = o.seller_id 
                  WHERE o.id = ? AND o.user_id = ? AND o.seller_id = ?";
    $order_stmt = $mysqli->prepare($order_sql);
    $order_stmt->bind_param('iii', $order_id, $customer_id, $seller_id);
    $order_stmt->execute();
    $order = $order_stmt->get_result()->fetch_assoc();
    $order_stmt->close();
    
    if (!$order) {
        flash('Order not found.', 'danger');
        redirect('seller/chats.php?customer=' . $customer_id);
    }
    
    // Get order items
    $items_sql = "SELECT oi.*, p.name as product_name 
                  FROM order_items oi 
                  JOIN products p ON p.id = oi.product_id 
                  WHERE oi.order_id = ?";
    $items_stmt = $mysqli->prepare($items_sql);
    $items_stmt->bind_param('i', $order_id);
    $items_stmt->execute();
    $items = $items_stmt->get_result();
    $items_stmt->close();
    
    // Build receipt message
    $message = "🧾 ORDER RECEIPT\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Order #: " . $order['order_number'] . "\n";
    $message .= "Date: " . date('d M Y, h:i A', strtotime($order['created_at'])) . "\n";
    $message .= "Customer: " . $order['customer_name'] . "\n";
    $message .= "Shop: " . $order['shop_name'] . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "ITEMS:\n";
    
    while ($item = $items->fetch_assoc()) {
        $message .= "  • " . $item['product_name'] . "\n";
        $message .= "    Qty: " . $item['quantity'] . " x KSH " . number_format($item['unit_price'], 2) . "\n";
        $message .= "    Subtotal: KSH " . number_format($item['quantity'] * $item['unit_price'], 2) . "\n";
    }
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "TOTAL: KSH " . number_format($order['total_amount'], 2) . "\n";
    $message .= "Payment: " . $order['payment_method'] . "\n";
    $message .= "Status: " . strtoupper($order['status']) . "\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "Thank you for shopping!";
    
    $sql = "INSERT INTO chats (user_id, seller_id, message, sender, is_read, created_at) 
            VALUES (?, ?, ?, 'seller', 0, NOW())";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iis', $customer_id, $seller_id, $message);
        if ($stmt->execute()) {
            flash('Receipt shared successfully!', 'success');
            redirect('seller/chats.php?customer=' . $customer_id);
        }
        $stmt->close();
    }
}

// ============================================
// HANDLE SHARING PRODUCT (Seller shares with customer)
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_product'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('seller/chats.php');
    }
    
    $customer_id = intval($_POST['customer_id'] ?? 0);
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($customer_id == 0 || $product_id == 0) {
        flash('Invalid data.', 'danger');
        redirect('seller/chats.php');
    }
    
    // Verify product belongs to this seller
    $product_sql = "SELECT p.* FROM products p 
                    WHERE p.id = ? AND p.seller_id = ? AND p.status = 'approved'";
    $product_stmt = $mysqli->prepare($product_sql);
    $product_stmt->bind_param('ii', $product_id, $seller_id);
    $product_stmt->execute();
    $product = $product_stmt->get_result()->fetch_assoc();
    $product_stmt->close();
    
    if (!$product) {
        flash('Product not found.', 'danger');
        redirect('seller/chats.php?customer=' . $customer_id);
    }
    
    $display_price = $product['price'];
    $sale_text = '';
    if (!empty($product['discounted_price']) && $product['discounted_price'] > 0 && $product['is_on_sale'] == 1) {
        $display_price = $product['discounted_price'];
        $sale_text = " 🔥 ON SALE!";
    }
    
    $message = "🛍️ PRODUCT SHARED\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "📦 " . $product['name'] . "\n";
    $message .= "💰 Price: KSH " . number_format($display_price, 2) . $sale_text . "\n";
    if (!empty($product['short_description'])) {
        $message .= "📝 " . $product['short_description'] . "\n";
    }
    $message .= "📊 Stock: " . $product['stock'] . " units\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "🔗 View: product.php?id=" . $product['id'];
    
    $sql = "INSERT INTO chats (user_id, seller_id, message, sender, is_read, created_at) 
            VALUES (?, ?, ?, 'seller', 0, NOW())";
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('iis', $customer_id, $seller_id, $message);
        if ($stmt->execute()) {
            flash('Product shared successfully!', 'success');
            redirect('seller/chats.php?customer=' . $customer_id);
        }
        $stmt->close();
    }
}

// ============================================
// HANDLE SENDING MESSAGE
// ============================================
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
        $table_check = $mysqli->query("SHOW TABLES LIKE 'chats'");
        if ($table_check && $table_check->num_rows > 0) {
            $sql = "INSERT INTO chats (user_id, seller_id, message, sender, is_read, created_at) VALUES (?, ?, ?, 'seller', 0, NOW())";
            $stmt = $mysqli->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('iis', $customer_id, $seller_id, $message);
                
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
        } else {
            flash('Chat system is not set up yet.', 'warning');
        }
    }
}

// ============================================
// GET ALL CUSTOMERS WITH UNREAD COUNT
// ============================================
$customers = null;
$selected_customer_id = intval($_GET['customer'] ?? 0);
$selected_customer = null;
$messages = null;

$table_check = $mysqli->query("SHOW TABLES LIKE 'chats'");
if ($table_check && $table_check->num_rows > 0) {
    $customers_sql = "SELECT DISTINCT u.id, u.name, u.email,
                      (SELECT COUNT(*) FROM chats WHERE user_id = u.id AND seller_id = ? AND sender = 'user' AND is_read = 0) as unread_count,
                      (SELECT message FROM chats WHERE user_id = u.id AND seller_id = ? ORDER BY created_at DESC LIMIT 1) as last_message,
                      (SELECT created_at FROM chats WHERE user_id = u.id AND seller_id = ? ORDER BY created_at DESC LIMIT 1) as last_message_time
                      FROM chats c
                      JOIN users u ON u.id = c.user_id
                      WHERE c.seller_id = ?
                      GROUP BY u.id
                      ORDER BY last_message_time DESC";
    $customers_stmt = $mysqli->prepare($customers_sql);
    if ($customers_stmt) {
        $customers_stmt->bind_param('iiii', $seller_id, $seller_id, $seller_id, $seller_id);
        $customers_stmt->execute();
        $customers = $customers_stmt->get_result();
        $customers_stmt->close();
    }
    
    if ($selected_customer_id > 0) {
        $customer_sql = "SELECT id, name, email FROM users WHERE id = ?";
        $customer_stmt = $mysqli->prepare($customer_sql);
        if ($customer_stmt) {
            $customer_stmt->bind_param('i', $selected_customer_id);
            $customer_stmt->execute();
            $selected_customer = $customer_stmt->get_result()->fetch_assoc();
            $customer_stmt->close();
        }
        
        if ($selected_customer) {
            $messages_sql = "SELECT c.*, u.name as sender_name
                             FROM chats c
                             JOIN users u ON u.id = c.user_id
                             WHERE c.user_id = ? AND c.seller_id = ?
                             ORDER BY c.created_at ASC";
            $messages_stmt = $mysqli->prepare($messages_sql);
            if ($messages_stmt) {
                $messages_stmt->bind_param('ii', $selected_customer_id, $seller_id);
                $messages_stmt->execute();
                $messages = $messages_stmt->get_result();
                $messages_stmt->close();
            }
            
            $col_check = $mysqli->query("SHOW COLUMNS FROM chats LIKE 'is_read'");
            if ($col_check && $col_check->num_rows > 0) {
                $update_sql = "UPDATE chats SET is_read = 1 WHERE user_id = ? AND seller_id = ? AND sender = 'user' AND is_read = 0";
                $update_stmt = $mysqli->prepare($update_sql);
                if ($update_stmt) {
                    $update_stmt->bind_param('ii', $selected_customer_id, $seller_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
        }
    }
}

// ============================================
// GET ORDERS FOR RECEIPT SHARING
// ============================================
$orders = [];
if ($selected_customer_id > 0) {
    $orders_sql = "SELECT o.* FROM orders o 
                   WHERE o.user_id = ? AND o.seller_id = ? AND o.status NOT IN ('cancelled')
                   ORDER BY o.created_at DESC LIMIT 20";
    $orders_stmt = $mysqli->prepare($orders_sql);
    if ($orders_stmt) {
        $orders_stmt->bind_param('ii', $selected_customer_id, $seller_id);
        $orders_stmt->execute();
        $orders_result = $orders_stmt->get_result();
        while ($row = $orders_result->fetch_assoc()) {
            $orders[] = $row;
        }
        $orders_stmt->close();
    }
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

// ============================================
// GET UNREAD COUNT FOR SIDEBAR
// ============================================
$unread_count = 0;
$col_check = $mysqli->query("SHOW COLUMNS FROM chats LIKE 'is_read'");
if ($col_check && $col_check->num_rows > 0) {
    $count_sql = "SELECT COUNT(*) as count FROM chats WHERE seller_id = ? AND sender = 'user' AND is_read = 0";
    $count_stmt = $mysqli->prepare($count_sql);
    if ($count_stmt) {
        $count_stmt->bind_param('i', $seller_id);
        $count_stmt->execute();
        $unread_count = $count_stmt->get_result()->fetch_assoc()['count'] ?? 0;
        $count_stmt->close();
    }
}
?>

<style>
/* ============================================
   SELLER CHAT - ENHANCED
============================================ */
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
    padding: 12px 18px;
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
    font-size: .9rem;
}
.chat-customers-header .unread-badge {
    background: #ef4444;
    color: #fff;
    padding: 2px 10px;
    border-radius: 50px;
    font-size: .65rem;
}

.customer-item {
    padding: 10px 16px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: all .3s ease;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
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
    font-size: .85rem;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.customer-item .customer-email {
    font-size: .65rem;
    color: #6b7280;
}
.customer-item .last-message {
    font-size: .7rem;
    color: #9ca3af;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 130px;
}
.customer-item .unread-badge-small {
    background: #ef4444;
    color: #fff;
    padding: 1px 7px;
    border-radius: 50px;
    font-size: .55rem;
    font-weight: 600;
    animation: pulse 2s infinite;
}
.customer-item .unread-dot {
    width: 8px;
    height: 8px;
    background: #ef4444;
    border-radius: 50%;
    flex-shrink: 0;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .6; }
}
.customer-item .chat-time {
    font-size: .55rem;
    color: #9ca3af;
    flex-shrink: 0;
    margin-left: 6px;
}
.no-customers {
    padding: 30px 16px;
    text-align: center;
    color: #6b7280;
}
.no-customers i {
    font-size: 2rem;
    color: #d1d5db;
    margin-bottom: 8px;
}

/* ---------- CHAT AREA ---------- */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}
.chat-area-header {
    padding: 10px 18px;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-shrink: 0;
}
.chat-area-header .customer-name {
    font-weight: 600;
    color: #1f2937;
    font-size: .9rem;
}
.chat-area-header .customer-email {
    font-size: .7rem;
    color: #6b7280;
}

/* ---------- MESSAGES ---------- */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
    background: #fafbfc;
}
.chat-message {
    margin-bottom: 6px;
    max-width: 78%;
    padding: 6px 12px;
    border-radius: 10px;
    position: relative;
    word-wrap: break-word;
    font-size: .85rem;
    line-height: 1.4;
}
.chat-message.sent {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    margin-left: auto;
    border-bottom-right-radius: 3px;
}
.chat-message.received {
    background: #fff;
    color: #1f2937;
    border: 1px solid #e5e7eb;
    margin-right: auto;
    border-bottom-left-radius: 3px;
}
.chat-message.received.new-message {
    border-color: #ef4444;
    background: #fff5f5;
    animation: highlightNew 2s ease;
}
@keyframes highlightNew {
    0% { background: #fee2e2; transform: scale(1.02); }
    100% { background: #fff; transform: scale(1); }
}
.chat-message .message-time {
    font-size: .5rem;
    opacity: .7;
    margin-top: 2px;
    display: block;
    text-align: right;
}
.chat-message.sent .message-time {
    color: rgba(255,255,255,0.8);
}
.chat-message.received .message-time {
    color: #9ca3af;
}
.chat-message .sender-name {
    font-size: .6rem;
    font-weight: 600;
    margin-bottom: 2px;
    display: block;
}
.chat-message.received .sender-name {
    color: #2563eb;
}
.chat-message .new-badge {
    display: inline-block;
    background: #ef4444;
    color: #fff;
    font-size: .5rem;
    padding: 1px 7px;
    border-radius: 50px;
    margin-left: 4px;
    animation: pulse 2s infinite;
    font-weight: 600;
}

/* Receipt & Product Boxes */
.chat-message .receipt-box {
    background: #f0fdf4;
    border: 1px solid #d1fae5;
    border-radius: 5px;
    padding: 6px 10px;
    margin: 3px 0;
    font-family: 'Courier New', monospace;
    font-size: .7rem;
    line-height: 1.4;
    white-space: pre-wrap;
    max-width: 100%;
    overflow-x: auto;
}
.chat-message.sent .receipt-box {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    color: #fff;
}
.chat-message .product-box {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 5px;
    padding: 6px 10px;
    margin: 3px 0;
    font-family: 'Courier New', monospace;
    font-size: .7rem;
    line-height: 1.4;
    white-space: pre-wrap;
    max-width: 100%;
    overflow-x: auto;
}
.chat-message.sent .product-box {
    background: rgba(255,255,255,0.1);
    border-color: rgba(255,255,255,0.2);
    color: #fff;
}
.chat-message .photo-link {
    display: inline-block;
    background: #2563eb;
    color: #fff;
    padding: 2px 10px;
    border-radius: 5px;
    text-decoration: none;
    font-size: .7rem;
    margin-top: 2px;
}
.chat-message.sent .photo-link {
    background: rgba(255,255,255,0.2);
}

.no-messages {
    text-align: center;
    padding: 30px;
    color: #6b7280;
}
.no-messages i {
    font-size: 2.5rem;
    color: #d1d5db;
    margin-bottom: 10px;
}

/* ---------- CHAT INPUT ---------- */
.chat-input-area {
    padding: 8px 12px;
    border-top: 1px solid #e5e7eb;
    background: #fff;
    flex-shrink: 0;
}
.chat-input-area .input-wrapper {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f1f5f9;
    border-radius: 10px;
    padding: 3px 3px 3px 12px;
    border: 2px solid transparent;
    transition: border-color .3s;
}
.chat-input-area .input-wrapper:focus-within {
    border-color: #2563eb;
    background: #fff;
}
.chat-input-area .input-wrapper input[type="text"] {
    flex: 1;
    padding: 7px 8px;
    border: none;
    background: transparent;
    font-size: .85rem;
    outline: none;
    color: #1f2937;
    min-width: 0;
}
.chat-input-area .input-wrapper input[type="text"]::placeholder {
    color: #9ca3af;
}

/* Action Buttons */
.chat-input-area .action-buttons {
    display: flex;
    align-items: center;
    gap: 2px;
    flex-shrink: 0;
}
.chat-input-area .action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    transition: all .3s;
    font-size: .9rem;
    position: relative;
}
.chat-input-area .action-btn:hover {
    background: #e5e7eb;
    color: #1f2937;
}
.chat-input-area .action-btn.photo-btn {
    color: #2563eb;
}
.chat-input-area .action-btn.photo-btn:hover {
    background: #dbeafe;
}
.chat-input-area .action-btn.receipt-btn {
    color: #10b981;
}
.chat-input-area .action-btn.receipt-btn:hover {
    background: #d1fae5;
}
.chat-input-area .action-btn.product-btn {
    color: #f59e0b;
}
.chat-input-area .action-btn.product-btn:hover {
    background: #fef3c7;
}
.chat-input-area .action-btn .badge {
    position: absolute;
    top: -3px;
    right: -3px;
    background: #ef4444;
    color: #fff;
    font-size: .45rem;
    padding: 1px 5px;
    border-radius: 50px;
    min-width: 14px;
    text-align: center;
    font-weight: 700;
}
.chat-input-area .action-btn .badge.green {
    background: #10b981;
}
.chat-input-area .action-btn .badge.blue {
    background: #2563eb;
}
.chat-input-area .btn-send {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all .3s;
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: .8rem;
    flex-shrink: 0;
}
.chat-input-area .btn-send:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37,99,235,0.3);
}

/* ============================================
   MODAL
============================================ */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.modal-overlay.active {
    display: flex;
}
.modal-content {
    background: #fff;
    border-radius: 16px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    padding: 18px;
    animation: modalSlideIn .3s ease;
}
@keyframes modalSlideIn {
    0% { transform: translateY(-20px); opacity: 0; }
    100% { transform: translateY(0); opacity: 1; }
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 8px;
}
.modal-header h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0 4px;
}
.modal-close:hover {
    color: #1f2937;
}
.share-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 6px;
    margin-top: 8px;
    max-height: 250px;
    overflow-y: auto;
}
.share-item {
    background: #f8fafc;
    border-radius: 6px;
    padding: 8px 12px;
    cursor: pointer;
    transition: all .3s;
    border: 2px solid transparent;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.share-item:hover {
    background: #eff6ff;
    border-color: #2563eb;
}
.share-item.selected {
    background: #dbeafe;
    border-color: #2563eb;
}
.share-item .item-left .item-name {
    font-weight: 600;
    color: #1f2937;
    font-size: .85rem;
}
.share-item .item-left .item-details {
    font-size: .7rem;
    color: #6b7280;
}
.share-item .item-right .item-price {
    font-weight: 700;
    color: #2563eb;
    font-size: .85rem;
}
.share-item .item-right .item-status {
    font-size: .6rem;
    color: #6b7280;
    display: block;
    text-align: right;
}
.share-item .sale-badge {
    background: #ef4444;
    color: #fff;
    font-size: .55rem;
    padding: 1px 8px;
    border-radius: 50px;
    margin-left: 4px;
}
.share-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 14px;
    border-radius: 6px;
    font-size: .75rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all .3s;
}
.share-btn-primary {
    background: #2563eb;
    color: #fff;
}
.share-btn-primary:hover {
    background: #1d4ed8;
}
.share-btn-success {
    background: #10b981;
    color: #fff;
}
.share-btn-success:hover {
    background: #059669;
}
.share-btn-secondary {
    background: #6b7280;
    color: #fff;
}
.share-btn-secondary:hover {
    background: #4b5563;
}
.modal-footer {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px solid #e5e7eb;
}

/* ============================================
   RESPONSIVE
============================================ */
@media (max-width: 992px) {
    .chat-wrapper { flex-direction: column; }
    .chat-sidebar { width: 100%; }
}
@media (max-width: 768px) {
    .chat-container { flex-direction: column; height: 550px; }
    .chat-customers { width: 100%; height: 180px; border-right: none; border-bottom: 1px solid #e5e7eb; }
    .chat-message { max-width: 90%; font-size: .8rem; padding: 5px 10px; }
    .chat-input-area .input-wrapper { background: #fff; border: 1px solid #e5e7eb; padding: 3px; }
    .chat-input-area .input-wrapper:focus-within { border-color: #2563eb; }
    .chat-input-area .action-btn { width: 30px; height: 30px; font-size: .8rem; }
    .chat-input-area .btn-send { padding: 6px 12px; font-size: .75rem; }
}
@media (max-width: 480px) {
    .chat-customers { height: 140px; }
    .chat-message { max-width: 95%; font-size: .75rem; padding: 4px 8px; }
    .chat-message .receipt-box { font-size: .65rem; padding: 4px 8px; }
    .chat-message .product-box { font-size: .65rem; padding: 4px 8px; }
}
</style>

<div class="container-fluid py-4">
    <div class="chat-wrapper">
        <div class="chat-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="chat-content">
            <div class="chat-container">
                
                <!-- ========== CUSTOMERS LIST ========== -->
                <div class="chat-customers">
                    <div class="chat-customers-header">
                        <span><i class="fa-regular fa-message"></i> Conversations</span>
                        <?php if ($unread_count > 0): ?>
                            <span class="unread-badge"><?= $unread_count ?> new</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($customers && $customers->num_rows > 0): ?>
                        <?php while ($customer = $customers->fetch_assoc()): 
                            $has_unread = isset($customer['unread_count']) && $customer['unread_count'] > 0;
                        ?>
                            <div class="customer-item <?= $selected_customer_id == $customer['id'] ? 'active' : '' ?>" 
                                 onclick="window.location.href='chats.php?customer=<?= $customer['id'] ?>'">
                                <div class="customer-info">
                                    <div class="customer-name">
                                        <?= sanitize($customer['name']) ?>
                                        <?php if ($has_unread): ?>
                                            <span class="unread-badge-small"><?= $customer['unread_count'] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="customer-email"><?= sanitize($customer['email']) ?></div>
                                    <div class="last-message"><?= sanitize(substr($customer['last_message'] ?? '', 0, 50)) ?></div>
                                </div>
                                <?php if ($customer['last_message_time']): ?>
                                    <span class="chat-time"><?= date('h:i A', strtotime($customer['last_message_time'])) ?></span>
                                <?php endif; ?>
                                <?php if ($has_unread): ?>
                                    <span class="unread-dot"></span>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-customers">
                            <i class="fa-regular fa-inbox"></i>
                            <p style="font-size:.85rem;">No conversations yet.</p>
                            <p style="font-size:.7rem;">When customers message you, they'll appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- ========== CHAT AREA ========== -->
                <div class="chat-area">
                    <?php if ($selected_customer): ?>
                        <!-- Chat Header -->
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
                                <?php while ($msg = $messages->fetch_assoc()): 
                                    $is_sent = $msg['sender'] == 'seller';
                                    $message_text = $msg['message'];
                                    
                                    $is_receipt = strpos($message_text, 'ORDER RECEIPT') !== false;
                                    $is_product = strpos($message_text, 'PRODUCT SHARED') !== false;
                                    $is_photo = strpos($message_text, 'Photo Shared') !== false;
                                    
                                    $is_new = false;
                                    if ($msg['sender'] == 'user') {
                                        $col_check = $mysqli->query("SHOW COLUMNS FROM chats LIKE 'is_read'");
                                        if ($col_check && $col_check->num_rows > 0) {
                                            $is_new = isset($msg['is_read']) && $msg['is_read'] == 0;
                                        }
                                    }
                                ?>
                                    <div class="chat-message <?= $is_sent ? 'sent' : 'received' ?> <?= $is_new ? 'new-message' : '' ?>">
                                        <?php if ($msg['sender'] == 'user'): ?>
                                            <span class="sender-name">
                                                <?= sanitize($msg['sender_name']) ?>
                                                <?php if ($is_new): ?>
                                                    <span class="new-badge">New</span>
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($is_receipt): ?>
                                            <div class="receipt-box"><?= htmlspecialchars($message_text) ?></div>
                                        <?php elseif ($is_product): ?>
                                            <div class="product-box"><?= htmlspecialchars($message_text) ?></div>
                                        <?php elseif ($is_photo): ?>
                                            <?php 
                                            if (preg_match('/View photo: (.*?)$/', $message_text, $matches)) {
                                                $photo_path = trim($matches[1]);
                                            } else {
                                                $photo_path = '';
                                            }
                                            ?>
                                            <div>📸 Photo Shared</div>
                                            <?php if ($photo_path): ?>
                                                <a href="<?= $photo_path ?>" target="_blank" class="photo-link">
                                                    <i class="fa-regular fa-image"></i> View Photo
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?= nl2br(sanitize($message_text)) ?>
                                        <?php endif; ?>
                                        
                                        <span class="message-time">
                                            <?= date('h:i A', strtotime($msg['created_at'])) ?>
                                            <?php if ($is_new): ?>
                                                <i class="fa-regular fa-circle" style="color:#ef4444;font-size:0.5rem;margin-left:4px;"></i>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="no-messages">
                                    <i class="fa-regular fa-message"></i>
                                    <p style="font-size:.9rem;">No messages yet.</p>
                                    <p style="font-size:.8rem;">Start the conversation with <?= sanitize($selected_customer['name']) ?>.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Chat Input -->
                        <div class="chat-input-area">
                            <form method="post" id="chatForm">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="customer_id" value="<?= $selected_customer['id'] ?>">
                                <input type="hidden" name="send_message" value="1">
                                <div class="input-wrapper">
                                    <input type="text" name="message" placeholder="Type your message..." autocomplete="off" id="chatInput">
                                    
                                    <div class="action-buttons">
                                        <!-- Photo Upload -->
                                        <button type="button" class="action-btn photo-btn" title="Upload Photo" onclick="document.getElementById('photoInput').click()">
                                            <i class="fa-regular fa-image"></i>
                                        </button>
                                        
                                        <!-- Share Receipt -->
                                        <?php if (!empty($orders)): ?>
                                        <button type="button" class="action-btn receipt-btn" title="Share Receipt" onclick="openShareModal('receipt')">
                                            <i class="fa fa-receipt"></i>
                                            <span class="badge green"><?= count($orders) ?></span>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="action-btn receipt-btn" title="No orders" onclick="alert('This customer has no orders.')">
                                            <i class="fa-regular fa-receipt"></i>
                                        </button>
                                        <?php endif; ?>
                                        
                                        <!-- Share Product -->
                                        <?php if (!empty($products)): ?>
                                        <button type="button" class="action-btn product-btn" title="Share Product" onclick="openShareModal('product')">
                                            <i class="fa-solid fa-tag"></i>
                                            <span class="badge blue"><?= count($products) ?></span>
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="action-btn product-btn" title="No products" onclick="alert('You have no approved products.')">
                                            <i class="fa-solid fa-tag"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="submit" class="btn-send" title="Send Message">
                                        <i class="fa-regular fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Hidden Photo Form -->
                            <form method="post" enctype="multipart/form-data" id="photoForm" style="display:none;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="send_photo" value="1">
                                <input type="hidden" name="customer_id" value="<?= $selected_customer['id'] ?>">
                                <input type="file" id="photoInput" name="photo_file" accept="image/*">
                            </form>
                        </div>
                        
                    <?php else: ?>
                        <!-- No Customer Selected -->
                        <div style="display:flex; align-items:center; justify-content:center; height:100%; flex-direction:column; color:#6b7280; padding:20px;">
                            <i class="fa-regular fa-message" style="font-size:3.5rem; color:#d1d5db; margin-bottom:12px;"></i>
                            <h4 style="color:#1f2937; font-size:1.1rem;">Select a Conversation</h4>
                            <p style="font-size:.85rem;">Choose a customer from the left to start chatting.</p>
                            <?php if ($customers && $customers->num_rows == 0): ?>
                                <p style="font-size:.75rem; margin-top:8px;">You don't have any messages yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
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
    const customerId = <?= $selected_customer_id ?: 0 ?>;
    
    if (type === 'receipt') {
        title.innerHTML = '<i class="fa-regular fa-receipt" style="color:#10b981;"></i> Share Receipt';
        <?php if (!empty($orders)): ?>
        let html = `
            <p style="color:#6b7280;font-size:.8rem;margin-bottom:10px;">
                Select an order to share the receipt:
            </p>
            <form method="post" id="shareReceiptForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="share_receipt" value="1">
                <input type="hidden" name="customer_id" value="${customerId}">
                <input type="hidden" name="order_id" id="selectedOrderId" value="">
                <div class="share-grid">
                    <?php foreach ($orders as $order): ?>
                    <div class="share-item" onclick="selectReceipt(<?= $order['id'] ?>, this)">
                        <div class="item-left">
                            <div class="item-name">Order #<?= $order['order_number'] ?></div>
                            <div class="item-details"><?= date('d M Y', strtotime($order['created_at'])) ?></div>
                        </div>
                        <div class="item-right">
                            <div class="item-price">KSH <?= number_format($order['total_amount'], 2) ?></div>
                            <div class="item-status"><?= ucfirst($order['status']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
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
            <div style="text-align:center;padding:20px;color:#6b7280;">
                <i class="fa-regular fa-receipt" style="font-size:2rem;color:#d1d5db;margin-bottom:8px;display:block;"></i>
                <p style="font-size:.85rem;">No orders available to share.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="share-btn share-btn-secondary" onclick="closeShareModal()">Close</button>
            </div>
        `;
        <?php endif; ?>
    } else if (type === 'product') {
        title.innerHTML = '<i class="fa-solid fa-tag" style="color:#f59e0b;"></i> Share Product';
        <?php if (!empty($products)): ?>
        let html = `
            <p style="color:#6b7280;font-size:.8rem;margin-bottom:10px;">
                Select a product to share:
            </p>
            <form method="post" id="shareProductForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="share_product" value="1">
                <input type="hidden" name="customer_id" value="${customerId}">
                <input type="hidden" name="product_id" id="selectedProductId" value="">
                <div class="share-grid">
                    <?php foreach ($products as $product): 
                        $display_price = $product['discounted_price'] ?? $product['price'];
                        $is_on_sale = $product['is_on_sale'] ?? 0;
                    ?>
                    <div class="share-item" onclick="selectProduct(<?= $product['id'] ?>, this)">
                        <div class="item-left">
                            <div class="item-name">
                                <?= sanitize($product['name']) ?>
                                <?php if ($is_on_sale): ?>
                                    <span class="sale-badge">🔥 SALE</span>
                                <?php endif; ?>
                            </div>
                            <div class="item-details">Stock: <?= $product['stock'] ?></div>
                        </div>
                        <div class="item-right">
                            <div class="item-price">KSH <?= number_format($display_price, 2) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
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
            <div style="text-align:center;padding:20px;color:#6b7280;">
                <i class="fa-solid fa-box" style="font-size:2rem;color:#d1d5db;margin-bottom:8px;display:block;"></i>
                <p style="font-size:.85rem;">No products available to share.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="share-btn share-btn-secondary" onclick="closeShareModal()">Close</button>
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

// ============================================
// PHOTO UPLOAD
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const photoInput = document.getElementById('photoInput');
    const photoForm = document.getElementById('photoForm');
    
    if (photoInput && photoForm) {
        photoInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                const btn = document.querySelector('.photo-btn');
                if (btn) {
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
                    btn.disabled = true;
                }
                photoForm.submit();
            }
        });
    }
    
    scrollToBottom();
    
    const form = document.querySelector('.chat-input-area form');
    if (form) {
        form.addEventListener('submit', function() {
            setTimeout(scrollToBottom, 100);
        });
    }
    
    // Handle share forms
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

// ============================================
// CLOSE MODAL ON OUTSIDE CLICK
// ============================================
document.getElementById('shareModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeShareModal();
});

// ============================================
// AUTO REFRESH
// ============================================
setInterval(function() {
    if (!document.getElementById('shareModal')?.classList.contains('active')) {
        location.reload();
    }
}, 10000);
</script>

<?php require_once '../includes/footer.php'; ?>