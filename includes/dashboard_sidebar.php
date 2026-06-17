<?php
// Dashboard Sidebar Component
$current_page = basename($_SERVER['PHP_SELF']);
$user = current_user();
$is_admin = ($user['role'] ?? '') === 'admin';
$is_seller = ($user['role'] ?? '') === 'seller';
$is_customer = ($user['role'] ?? '') === 'customer';
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
            <li>
                <a href="<?= BASE_URL ?>seller/dashboard.php">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Seller Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>seller/add_product.php">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>Add New Product</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>seller/products.php">
                    <i class="fa-solid fa-box"></i>
                    <span>Manage Products</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>seller/orders.php">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>Seller Orders</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>seller/earnings.php">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Earnings</span>
                </a>
            </li>
        <?php endif; ?>
        
        <!-- Admin Specific - Full Admin Menu -->
        <?php if ($is_admin): ?>
            <li class="sidebar-divider"></li>
            <li>
                <a href="<?= BASE_URL ?>admin/dashboard.php" class="<?= $current_page == 'dashboard.php' && strpos($_SERVER['PHP_SELF'], 'admin/') !== false ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge-high"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/users.php">
                    <i class="fa-solid fa-users"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/sellers.php">
                    <i class="fa-solid fa-store"></i>
                    <span>Manage Sellers</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/products.php">
                    <i class="fa-solid fa-box"></i>
                    <span>Manage Products</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/categories.php">
                    <i class="fa-solid fa-folder"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/orders.php">
                    <i class="fa-solid fa-truck"></i>
                    <span>All Orders</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/reviews.php">
                    <i class="fa-solid fa-star"></i>
                    <span>Reviews</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/payments.php">
                    <i class="fa-solid fa-credit-card"></i>
                    <span>Payments</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/subscriptions.php">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span>Subscriptions</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/offers.php">
                    <i class="fa-solid fa-tag"></i>
                    <span>Offers & Coupons</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>admin/settings.php">
                    <i class="fa-solid fa-gear"></i>
                    <span>Settings</span>
                </a>
            </li>
        <?php endif; ?>
        
        <li class="sidebar-divider"></li>
        
        <li>
            <a href="<?= BASE_URL ?>support.php">
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