<?php
// Dashboard Sidebar Component
$current_page = basename($_SERVER['PHP_SELF']);
$current_path = $_SERVER['PHP_SELF'];
$user = current_user();
$is_admin = ($user['role'] ?? '') === 'admin';
$is_seller = ($user['role'] ?? '') === 'seller';
$is_customer = ($user['role'] ?? '') === 'customer';

// Get unread chat count for seller
$unread_chats = 0;
if ($is_seller && isset($_SESSION['user_id'])) {
    // First check if chats table exists
    $table_check = $mysqli->query("SHOW TABLES LIKE 'chats'");
    if ($table_check && $table_check->num_rows > 0) {
        // Get seller id
        $seller_id_sql = "SELECT id FROM sellers WHERE user_id = ?";
        $seller_id_stmt = $mysqli->prepare($seller_id_sql);
        if ($seller_id_stmt) {
            $seller_id_stmt->bind_param('i', $_SESSION['user_id']);
            $seller_id_stmt->execute();
            $seller_id_result = $seller_id_stmt->get_result();
            if ($seller_id_result && $seller_id_result->num_rows > 0) {
                $seller_data = $seller_id_result->fetch_assoc();
                $seller_id = $seller_data['id'];
                
                // Check if is_read column exists
                $column_check = $mysqli->query("SHOW COLUMNS FROM chats LIKE 'is_read'");
                if ($column_check && $column_check->num_rows > 0) {
                    $chat_sql = "SELECT COUNT(*) as count FROM chats WHERE seller_id = ? AND is_read = 0";
                    $chat_stmt = $mysqli->prepare($chat_sql);
                    if ($chat_stmt) {
                        $chat_stmt->bind_param('i', $seller_id);
                        $chat_stmt->execute();
                        $chat_result = $chat_stmt->get_result();
                        if ($chat_result) {
                            $unread_chats = $chat_result->fetch_assoc()['count'] ?? 0;
                        }
                        $chat_stmt->close();
                    }
                }
            }
            $seller_id_stmt->close();
        }
    }
}

// Helper function to check if a link is active
function is_active($link, $current_page, $current_path) {
    if (strpos($link, 'seller/') !== false) {
        // For seller pages, check if the current path contains the link
        return strpos($current_path, $link) !== false;
    }
    // For other pages, match the filename
    return $current_page == basename($link);
}
?>

<style>
    .dashboard-sidebar {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 100px;
    }
    
    .user-info-sidebar {
        text-align: center;
        padding-bottom: 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .user-avatar-sidebar {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    }
    
    .user-avatar-sidebar i {
        font-size: 2.5rem;
        color: white;
    }
    
    .user-name-sidebar {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 5px;
    }
    
    .user-email-sidebar {
        font-size: 0.75rem;
        color: #6b7280;
    }
    
    .sidebar-menu-nav {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sidebar-menu-nav li {
        margin-bottom: 5px;
    }
    
    .sidebar-menu-nav li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        color: #1f2937;
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        position: relative;
    }
    
    .sidebar-menu-nav li a i {
        width: 22px;
        font-size: 1rem;
        color: #6b7280;
        transition: all 0.3s ease;
    }
    
    .sidebar-menu-nav li a:hover {
        background: #f3f4f6;
        transform: translateX(5px);
    }
    
    .sidebar-menu-nav li a:hover i {
        color: #2563eb;
    }
    
    .sidebar-menu-nav li a.active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
    }
    
    .sidebar-menu-nav li a.active i {
        color: white;
    }
    
    .sidebar-divider {
        height: 1px;
        background: #e5e7eb;
        margin: 15px 0;
    }
    
    .sidebar-badge {
        background: #fee2e2;
        color: #ef4444;
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: auto;
    }
    
    /* Chat Badge with Pulse Animation */
    .sidebar-badge-chat {
        background: #ef4444;
        color: white;
        font-size: 0.65rem;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: auto;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    /* Small Badges for Menu Items */
    .badge-view {
        font-size: 0.6rem;
        background: #dbeafe;
        color: #2563eb;
        padding: 1px 8px;
        border-radius: 10px;
        margin-left: auto;
    }
    
    .badge-edit {
        font-size: 0.6rem;
        background: #d1fae5;
        color: #059669;
        padding: 1px 8px;
        border-radius: 10px;
        margin-left: auto;
    }
    
    .badge-guide {
        font-size: 0.6rem;
        background: #fef3c7;
        color: #d97706;
        padding: 1px 8px;
        border-radius: 10px;
        margin-left: auto;
    }
    
    /* Role Badges */
    .seller-badge-sidebar {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 8px;
    }
    
    .admin-badge-sidebar {
        background: linear-gradient(135deg, #8b5cf6, #6d28d9);
        color: white;
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 8px;
    }
    
    .customer-badge-sidebar {
        background: linear-gradient(135deg, #6b7280, #4b5563);
        color: white;
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 20px;
        display: inline-block;
        margin-top: 8px;
    }
    
    @media (max-width: 768px) {
        .dashboard-sidebar {
            position: static;
            margin-bottom: 20px;
        }
    }
</style>

<div class="dashboard-sidebar">
    <div class="user-info-sidebar">
        <div class="user-avatar-sidebar">
            <i class="fa-regular fa-circle-user"></i>
        </div>
        <div class="user-name-sidebar"><?= sanitize($user['name'] ?? 'User') ?></div>
        <div class="user-email-sidebar"><?= sanitize($user['email'] ?? '') ?></div>
        <?php if ($is_seller): ?>
            <div class="seller-badge-sidebar">
                <i class="fa-solid fa-store"></i> Seller
            </div>
        <?php elseif ($is_admin): ?>
            <div class="admin-badge-sidebar">
                <i class="fa-solid fa-shield-hart"></i> Admin
            </div>
        <?php else: ?>
            <div class="customer-badge-sidebar">
                <i class="fa-regular fa-user"></i> Customer
            </div>
        <?php endif; ?>
    </div>
    
    <ul class="sidebar-menu-nav">
        <!-- Common Menu Items for All Users -->
        <li>
            <a href="<?= BASE_URL ?>profile.php" class="<?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <i class="fa-regular fa-id-card"></i>
                <span>My Profile</span>
            </a>
        </li>
        
        <!-- Customer Specific -->
        <?php if ($is_customer): ?>
            <li>
                <a href="<?= BASE_URL ?>orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck"></i>
                    <span>My Orders</span>
                </a>
            </li>
            
            <li>
                <a href="<?= BASE_URL ?>wishlist.php" class="<?= $current_page == 'wishlist.php' ? 'active' : '' ?>">
                    <i class="fa-regular fa-heart"></i>
                    <span>My Wishlist</span>
                </a>
            </li>
            
            <li>
                <a href="<?= BASE_URL ?>cart.php">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span>Shopping Cart</span>
                    <span class="sidebar-badge" id="sidebarCartCount">0</span>
                </a>
            </li>
            
            <li class="sidebar-divider"></li>
            
            <li>
                <a href="<?= BASE_URL ?>compare.php">
                    <i class="fa-solid fa-chart-simple"></i>
                    <span>Compare Products</span>
                </a>
            </li>
            
            <li>
                <a href="<?= BASE_URL ?>ai_assistant.php">
                    <i class="fa-solid fa-robot"></i>
                    <span>AI Assistant</span>
                </a>
            </li>
        
            <li class="sidebar-divider"></li>
            <li>
                <a href="<?= BASE_URL ?>become-seller.php">
                    <i class="fa-solid fa-store"></i>
                    <span>Become a Seller</span>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Seller Specific -->
        <?php if ($is_seller): ?>
            <li class="sidebar-divider"></li>
            
            <!-- Seller Dashboard -->
            <li>
                <a href="<?= BASE_URL ?>seller/dashboard.php" class="<?= strpos($current_path, 'seller/dashboard.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Seller Dashboard</span>
                </a>
            </li>
            
            <!-- My Shop - View Shop -->
            <li>
                <a href="<?= BASE_URL ?>seller/my_shop.php" class="<?= strpos($current_path, 'seller/my_shop.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-store"></i>
                    <span>My Shop</span>
                    <span class="badge-view">View</span>
                </a>
            </li>
            
            <!-- Edit Shop -->
            <!-- <li>
                <a href="<?= BASE_URL ?>seller/edit_profile.php" class="<?= strpos($current_path, 'seller/edit_profile.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit Shop</span>
                    <span class="badge-edit">Edit</span>
                </a>
            </li> -->
            
            <!-- Add Product -->
            <li>
                <a href="<?= BASE_URL ?>seller/add_product.php" class="<?= strpos($current_path, 'seller/add_product.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Add New Product</span>
                </a>
            </li>
            
            <!-- Manage Products -->
            <li>
                <a href="<?= BASE_URL ?>seller/products.php" class="<?= strpos($current_path, 'seller/products.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-box"></i>
                    <span>Manage Products</span>
                </a>
            </li>
            
            <!-- Seller Orders -->
            <li>
                <a href="<?= BASE_URL ?>seller/orders.php" class="<?= strpos($current_path, 'seller/orders.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>Seller Orders</span>
                </a>
            </li>
            
            <!-- Messages (Chat) -->
            <li>
                <a href="<?= BASE_URL ?>seller/chats.php" class="<?= strpos($current_path, 'seller/chats.php') !== false ? 'active' : '' ?>">
                    <i class="fa-regular fa-message"></i>
                    <span>Messages</span>
                    <?php if ($unread_chats > 0): ?>
                        <span class="sidebar-badge-chat"><?= $unread_chats ?> new</span>
                    <?php endif; ?>
                </a>
            </li>
            
            <!-- Earnings -->
            <li>
                <a href="<?= BASE_URL ?>seller/earnings.php" class="<?= strpos($current_path, 'seller/earnings.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-coins"></i>
                    <span>Earnings</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>seller/subscription.php" class="<?= strpos($current_path, 'seller/subscriptions.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i>
                <span>Subscription</span>
                </a>
            </li>
            
            <!-- Seller Manual -->
            <li class="sidebar-divider"></li>
            <li>
                <a href="<?= BASE_URL ?>seller/manual.php" class="<?= strpos($current_path, 'seller/manual.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-book"></i>
                    <span>Seller Manual</span>
                    <span class="badge-guide">Guide</span>
                </a>
            </li>
            
        <?php endif; ?>
        
        <!-- Admin Specific -->
        <?php if ($is_admin): ?>
            <li class="sidebar-divider"></li>
            <li>
                <a href="<?= BASE_URL ?>admin/dashboard.php" class="<?= strpos($current_path, 'admin/dashboard.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/users.php" class="<?= strpos($current_path, 'admin/users.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/sellers.php" class="<?= strpos($current_path, 'admin/sellers.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-store"></i>
                    <span>Manage Sellers</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/products.php" class="<?= strpos($current_path, 'admin/products.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-box"></i>
                    <span>Manage Products</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/categories.php" class="<?= strpos($current_path, 'admin/categories.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-folder"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/orders.php" class="<?= strpos($current_path, 'admin/orders.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-truck"></i>
                    <span>All Orders</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/reviews.php" class="<?= strpos($current_path, 'admin/reviews.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-star"></i>
                    <span>Reviews</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/payments.php" class="<?= strpos($current_path, 'admin/payments.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/subscriptions.php" class="<?= strpos($current_path, 'admin/subscriptions.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Subscriptions</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/offers.php" class="<?= strpos($current_path, 'admin/offers.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-tag"></i>
                    <span>Offers & Coupons</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/settings.php" class="<?= strpos($current_path, 'admin/settings.php') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
        <?php endif; ?>
        
        <li class="sidebar-divider"></li>
        
        <li>
            <a href="<?= BASE_URL ?>support.php" class="<?= $current_page == 'support.php' ? 'active' : '' ?>">
                <i class="fa-regular fa-circle-question"></i>
                <span>Help & Support</span>
            </a>
        </li>
        
        <li>
            <a href="<?= BASE_URL ?>logout.php" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<script>
function updateSidebarCartCount() {
    fetch('<?= BASE_URL ?>api/get_cart_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count !== undefined) {
                const sidebarCount = document.getElementById('sidebarCartCount');
                if (sidebarCount) {
                    sidebarCount.textContent = data.count;
                    if (data.count > 0) {
                        sidebarCount.style.display = 'inline-block';
                    } else {
                        sidebarCount.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    updateSidebarCartCount();
});
</script>