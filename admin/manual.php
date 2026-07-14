<?php
$page_title = 'Admin Manual';
require_once '../includes/header.php';
require_role('admin');
?>

<style>
    .manual-wrapper {
        display: flex;
        gap: 25px;
        min-height: calc(100vh - 200px);
    }
    .manual-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
    .manual-content {
        flex: 1;
    }
    
    .manual-card {
        background: white;
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        margin-bottom: 25px;
    }
    
    .manual-card .card-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #2563eb;
        margin-bottom: 12px;
    }
    
    .manual-card h3 {
        font-size: 1.2rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .manual-card h3 i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .manual-card p {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.7;
    }
    
    .manual-card ul, .manual-card ol {
        padding-left: 20px;
        margin: 10px 0;
    }
    
    .manual-card li {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
        margin-bottom: 6px;
    }
    
    .manual-card li strong {
        color: #1f2937;
    }
    
    .manual-card .step-number {
        display: inline-block;
        width: 28px;
        height: 28px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border-radius: 50%;
        text-align: center;
        line-height: 28px;
        font-size: 0.8rem;
        font-weight: 700;
        margin-right: 10px;
    }
    
    .manual-card .step-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .manual-card .step-item:last-child {
        border-bottom: none;
    }
    
    .manual-card .step-item .step-content {
        flex: 1;
    }
    
    .manual-card .step-item .step-content strong {
        color: #1f2937;
        display: block;
        margin-bottom: 2px;
    }
    
    .manual-card .step-item .step-content p {
        margin: 0;
        font-size: 0.9rem;
        color: #6b7280;
    }
    
    .manual-card .highlight-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        border-left: 4px solid #2563eb;
        margin: 15px 0;
    }
    
    .manual-card .highlight-box.warning {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    
    .manual-card .highlight-box.success {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    
    .manual-card .highlight-box.danger {
        border-left-color: #ef4444;
        background: #fef2f2;
    }
    
    .manual-card .highlight-box h5 {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .manual-card .highlight-box p {
        margin: 0;
        font-size: 0.9rem;
    }
    
    .manual-card .tip-box {
        background: #eff6ff;
        border-radius: 12px;
        padding: 12px 16px;
        margin: 10px 0;
        border: 1px solid #bfdbfe;
    }
    
    .manual-card .tip-box .tip-title {
        font-weight: 600;
        color: #1d4ed8;
        font-size: 0.85rem;
    }
    
    .manual-card .tip-box p {
        margin: 0;
        font-size: 0.9rem;
        color: #4b5563;
    }
    
    .manual-nav {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
        position: sticky;
        top: 100px;
    }
    
    .manual-nav .nav-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .manual-nav a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        color: #4b5563;
        text-decoration: none;
        border-radius: 8px;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }
    
    .manual-nav a:hover {
        background: #f3f4f6;
        color: #2563eb;
    }
    
    .manual-nav a i {
        width: 20px;
        color: #6b7280;
    }
    
    .manual-nav a:hover i {
        color: #2563eb;
    }
    
    .manual-nav a .badge-nav {
        margin-left: auto;
        background: #dbeafe;
        color: #1d4ed8;
        font-size: 0.55rem;
        padding: 1px 8px;
        border-radius: 50px;
    }
    
    @media (max-width: 992px) {
        .manual-wrapper {
            flex-direction: column;
        }
        .manual-sidebar {
            width: 100%;
        }
        .manual-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 12px;
            position: static;
        }
        .manual-nav .nav-title {
            width: 100%;
            margin-bottom: 8px;
        }
        .manual-nav a {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .manual-nav a .badge-nav {
            display: none;
        }
        .manual-card .step-item {
            flex-direction: column;
            gap: 4px;
        }
    }
    
    @media (max-width: 768px) {
        .manual-card {
            padding: 20px;
        }
        .manual-card h3 {
            font-size: 1.1rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="manual-wrapper">
        <div class="manual-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="manual-content">
            
            <!-- Header -->
            <div class="manual-card" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: white; border: none;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div style="width: 60px; height: 60px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; color: #f59e0b;">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <div>
                        <h1 style="color: white; font-size: 1.8rem; font-weight: 700; margin: 0;">Admin Manual</h1>
                        <p style="color: rgba(255,255,255,0.6); margin: 4px 0 0 0;">Complete guide to managing your marketplace</p>
                    </div>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 1: DASHBOARD
            ========================================== -->
            <div class="manual-card" id="dashboard">
                <div class="card-icon"><i class="fa-solid fa-gauge-high"></i></div>
                <h3><i class="fa-solid fa-gauge-high"></i> Dashboard Overview</h3>
                <p>
                    The admin dashboard gives you a complete overview of your marketplace performance. 
                    Here you can monitor key metrics and access all management tools.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Statistics Cards</strong>
                        <p>View total users, sellers, products, orders, and revenue at a glance.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Sales Chart</strong>
                        <p>Track monthly sales performance with interactive charts.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Recent Activity</strong>
                        <p>See the latest user registrations, seller applications, and orders.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Quick Actions</strong>
                        <p>Access frequently used admin tools with one click.</p>
                    </div>
                </div>
                
                <div class="tip-box">
                    <div class="tip-title"><i class="fa-regular fa-lightbulb"></i> Pro Tip</div>
                    <p>Check the dashboard daily to stay on top of your marketplace performance and identify trends early.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 2: USER MANAGEMENT
            ========================================== -->
            <div class="manual-card" id="users">
                <div class="card-icon"><i class="fa-solid fa-users"></i></div>
                <h3><i class="fa-solid fa-users"></i> User Management</h3>
                <p>
                    Manage all users on your platform, including customers, sellers, and administrators.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>View Users</strong>
                        <p>See all registered users with their roles, email, and verification status.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Search & Filter</strong>
                        <p>Search by name, email, or phone. Filter by role (customer, seller, admin).</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Add New User</strong>
                        <p>Create new user accounts manually with custom roles.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Actions</strong>
                        <p>Verify users, reset passwords, or delete accounts.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">5</span>
                    <div class="step-content">
                        <strong>Bulk Actions</strong>
                        <p>Send emails, WhatsApp messages, or SMS to selected customers.</p>
                    </div>
                </div>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-solid fa-triangle-exclamation"></i> Important</h5>
                    <p>Only delete user accounts when absolutely necessary. Consider deactivating sellers instead.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 3: SELLER MANAGEMENT
            ========================================== -->
            <div class="manual-card" id="sellers">
                <div class="card-icon"><i class="fa-solid fa-store"></i></div>
                <h3><i class="fa-solid fa-store"></i> Seller Management</h3>
                <p>
                    Manage seller applications, verify sellers, and monitor their performance.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Pending Applications</strong>
                        <p>Review and approve or reject new seller applications.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Verify Sellers</strong>
                        <p>Check submitted documents and verify seller identities.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Manage Documents</strong>
                        <p>View, approve, or reject seller documents (ID, business license, etc.).</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Activate/Deactivate</strong>
                        <p>Toggle seller accounts on or off. Inactive sellers cannot log in.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">5</span>
                    <div class="step-content">
                        <strong>View Details</strong>
                        <p>See seller statistics including products, orders, and earnings.</p>
                    </div>
                </div>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check"></i> Best Practice</h5>
                    <p>Verify sellers thoroughly before approval to maintain platform quality and trust.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 4: PRODUCT MANAGEMENT
            ========================================== -->
            <div class="manual-card" id="products">
                <div class="card-icon"><i class="fa-solid fa-box"></i></div>
                <h3><i class="fa-solid fa-box"></i> Product Management</h3>
                <p>
                    Manage all products listed on the marketplace.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Pending Products</strong>
                        <p>Review and approve or reject products submitted by sellers.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Product Details</strong>
                        <p>View full product information including images, pricing, and stock.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Discount Management</strong>
                        <p>Monitor and manage product discounts and promotions.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Remove Products</strong>
                        <p>Delete products that violate marketplace policies.</p>
                    </div>
                </div>
                
                <div class="tip-box">
                    <div class="tip-title"><i class="fa-regular fa-lightbulb"></i> Pro Tip</div>
                    <p>Regularly review products to ensure quality standards are maintained and no prohibited items are listed.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 5: ORDER MANAGEMENT
            ========================================== -->
            <div class="manual-card" id="orders">
                <div class="card-icon"><i class="fa-solid fa-truck"></i></div>
                <h3><i class="fa-solid fa-truck"></i> Order Management</h3>
                <p>
                    Monitor and manage all orders across the platform.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>View All Orders</strong>
                        <p>See all orders with customer and seller information.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Order Status</strong>
                        <p>Track orders through their lifecycle: Pending → Processing → Shipped → Delivered.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Order Details</strong>
                        <p>View full order details including items, shipping address, and payment.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Dispute Resolution</strong>
                        <p>Mediate disputes between customers and sellers.</p>
                    </div>
                </div>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-solid fa-triangle-exclamation"></i> Important</h5>
                    <p>Orders can only be cancelled before they are shipped. Once shipped, the seller must handle returns.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 6: PAYMENT MANAGEMENT
            ========================================== -->
            <div class="manual-card" id="payments">
                <div class="card-icon"><i class="fa-solid fa-credit-card"></i></div>
                <h3><i class="fa-solid fa-credit-card"></i> Payment Management</h3>
                <p>
                    Manage payments, subscriptions, and financial transactions.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Pending Payments</strong>
                        <p>Review and confirm pending subscription payments.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Payment History</strong>
                        <p>View all completed and failed payment transactions.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Subscription Management</strong>
                        <p>Manage seller subscriptions and plan assignments.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Refunds</strong>
                        <p>Process refunds when necessary.</p>
                    </div>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 7: CONTENT MANAGEMENT
            ========================================== -->
            <div class="manual-card" id="content">
                <div class="card-icon"><i class="fa-solid fa-newspaper"></i></div>
                <h3><i class="fa-solid fa-newspaper"></i> Content Management</h3>
                <p>
                    Manage blog posts, FAQ, and other platform content.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Blog Management</strong>
                        <p>Create, edit, publish, and delete blog posts.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Manage Comments</strong>
                        <p>Approve, reject, or delete blog comments.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>FAQ Management</strong>
                        <p>Create and organize frequently asked questions.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Pages</strong>
                        <p>Manage static pages like About, Terms, and Privacy Policy.</p>
                    </div>
                </div>
                
                <div class="tip-box">
                    <div class="tip-title"><i class="fa-regular fa-lightbulb"></i> Pro Tip</div>
                    <p>Regular blog updates improve SEO and keep customers engaged with your marketplace.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 8: SUPPORT & CONTACT
            ========================================== -->
            <div class="manual-card" id="support">
                <div class="card-icon"><i class="fa-regular fa-headset"></i></div>
                <h3><i class="fa-regular fa-headset"></i> Support & Contact</h3>
                <p>
                    Manage customer support tickets and contact messages.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Support Tickets</strong>
                        <p>View and respond to customer support tickets.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Ticket Status</strong>
                        <p>Update ticket status: Open → In Progress → Resolved → Closed.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Contact Messages</strong>
                        <p>View and respond to contact form submissions.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Chat Management</strong>
                        <p>Monitor customer-seller conversations when needed.</p>
                    </div>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 9: AI LOGS
            ========================================== -->
            <div class="manual-card" id="ai">
                <div class="card-icon"><i class="fa-solid fa-robot"></i></div>
                <h3><i class="fa-solid fa-robot"></i> AI Logs</h3>
                <p>
                    Monitor AI assistant interactions and improve responses.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>View AI Conversations</strong>
                        <p>See all questions and responses from the AI assistant.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Search Logs</strong>
                        <p>Search for specific questions or responses.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Usage Analytics</strong>
                        <p>Track AI usage patterns and identify common questions.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Clear Logs</strong>
                        <p>Remove old logs to free up database space.</p>
                    </div>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 10: BACKUP & RESTORE
            ========================================== -->
            <div class="manual-card" id="backup">
                <div class="card-icon"><i class="fa-solid fa-database"></i></div>
                <h3><i class="fa-solid fa-database"></i> Backup & Restore</h3>
                <p>
                    Protect your data with regular backups and restore when needed.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Database Backup</strong>
                        <p>Create a full SQL backup of your database.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>File Backup</strong>
                        <p>Create a ZIP backup of all files and uploads.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Restore Database</strong>
                        <p>Restore from a previous backup (use with caution).</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Download Backups</strong>
                        <p>Download backups to your computer for extra safety.</p>
                    </div>
                </div>
                
                <div class="highlight-box danger">
                    <h5><i class="fa-solid fa-triangle-exclamation"></i> Warning</h5>
                    <p>Restoring a database will overwrite all current data. Always create a backup before restoring.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 11: OFFERS & COUPONS
            ========================================== -->
            <div class="manual-card" id="offers">
                <div class="card-icon"><i class="fa-solid fa-tag"></i></div>
                <h3><i class="fa-solid fa-tag"></i> Offers & Coupons</h3>
                <p>
                    Create and manage promotional offers and discount codes.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Create Offer</strong>
                        <p>Set up discount codes with percentage or fixed amount.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Set Expiration</strong>
                        <p>Define start and end dates for each offer.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Activate/Deactivate</strong>
                        <p>Toggle offers on and off as needed.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Track Usage</strong>
                        <p>Monitor how many times each coupon has been used.</p>
                    </div>
                </div>
            </div>
            
            <!-- ==========================================
                 SECTION 12: SETTINGS
            ========================================== -->
            <div class="manual-card" id="settings">
                <div class="card-icon"><i class="fa-solid fa-gear"></i></div>
                <h3><i class="fa-solid fa-gear"></i> System Settings</h3>
                <p>
                    Configure platform settings and preferences.
                </p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Site Settings</strong>
                        <p>Update site name, description, and contact information.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Payment Settings</strong>
                        <p>Configure payment gateways and currency settings.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Email Settings</strong>
                        <p>Set up email templates and SMTP configuration.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>AI Assistant</strong>
                        <p>Configure AI assistant responses and behavior.</p>
                    </div>
                </div>
            </div>
            
            <!-- ==========================================
                 QUICK REFERENCE
            ========================================== -->
            <div class="manual-card" style="background: #f0fdf4; border-color: #86efac;">
                <div class="card-icon" style="background: #d1fae5; color: #059669;"><i class="fa-solid fa-bolt"></i></div>
                <h3 style="color: #065f46;"><i class="fa-solid fa-bolt"></i> Quick Reference</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Users:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Manage customers, sellers, admins</p>
                    </div>
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Sellers:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Approve, verify, manage sellers</p>
                    </div>
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Products:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Approve, reject, manage products</p>
                    </div>
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Orders:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Monitor all orders on the platform</p>
                    </div>
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Payments:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Manage payments and subscriptions</p>
                    </div>
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Content:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Blog, FAQ, pages management</p>
                    </div>
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Support:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Manage tickets and contact messages</p>
                    </div>
                    <div style="background: white; border-radius: 10px; padding: 12px 15px; border: 1px solid #e5e7eb;">
                        <strong style="color: #2563eb;">Backup:</strong>
                        <p style="font-size: 0.85rem; color: #6b7280; margin: 4px 0 0 0;">Create and restore backups</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
// Smooth scroll for sidebar links
document.querySelectorAll('.manual-nav a').forEach(function(link) {
    link.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>