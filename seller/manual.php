<?php
$page_title = 'Seller Manual';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT s.*, u.name as user_name FROM sellers s JOIN users u ON u.id = s.user_id WHERE s.user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}
?>

<style>
/* ============================================
   SELLER MANUAL - MODERN CLEAN DESIGN
============================================ */

/* ---------- MAIN CONTAINER ---------- */
.manual-wrapper {
    display: flex;
    gap: 25px;
}

.manual-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.manual-content {
    flex: 1;
}

/* ---------- MANUAL CARD ---------- */
.manual-card {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

.manual-header {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
    margin-bottom: 25px;
}

.manual-header .icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    color: white;
    flex-shrink: 0;
}

.manual-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.manual-header p {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}

/* ---------- SECTION ---------- */
.manual-section {
    margin-bottom: 30px;
}

.manual-section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.manual-section-title i {
    color: #2563eb;
}

.manual-section-title .step-number {
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
    margin-right: 8px;
}

/* ---------- STEP CARDS ---------- */
.step-card {
    display: flex;
    gap: 15px;
    padding: 15px 18px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 12px;
    border-left: 3px solid #2563eb;
    transition: all 0.3s ease;
}

.step-card:hover {
    background: #eff6ff;
    transform: translateX(5px);
}

.step-card .step-icon {
    width: 36px;
    height: 36px;
    background: #dbeafe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #2563eb;
    font-size: 1rem;
    flex-shrink: 0;
}

.step-card .step-content h4 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.step-card .step-content p {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0;
}

/* ---------- TIP BOX ---------- */
.tip-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: 15px 20px;
    margin: 15px 0;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.tip-box i {
    color: #f59e0b;
    font-size: 1.2rem;
    margin-top: 2px;
}

.tip-box .tip-content h5 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #92400e;
    margin: 0 0 4px 0;
}

.tip-box .tip-content p {
    font-size: 0.85rem;
    color: #78350f;
    margin: 0;
}

/* ---------- WARNING BOX ---------- */
.warning-box {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 12px;
    padding: 15px 20px;
    margin: 15px 0;
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.warning-box i {
    color: #ef4444;
    font-size: 1.2rem;
    margin-top: 2px;
}

.warning-box .warning-content h5 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #991b1b;
    margin: 0 0 4px 0;
}

.warning-box .warning-content p {
    font-size: 0.85rem;
    color: #7f1d1d;
    margin: 0;
}

/* ---------- SECTION DIVIDER ---------- */
.section-divider {
    height: 2px;
    background: linear-gradient(90deg, #e5e7eb, transparent);
    margin: 30px 0;
}

/* ---------- QUICK LINKS ---------- */
.quick-links {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 15px;
}

.quick-link-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #f8fafc;
    border-radius: 10px;
    text-decoration: none;
    color: #1f2937;
    transition: all 0.3s ease;
    font-size: 0.85rem;
}

.quick-link-item:hover {
    background: #eff6ff;
    color: #2563eb;
    transform: translateX(5px);
}

.quick-link-item i {
    color: #2563eb;
    width: 18px;
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 992px) {
    .manual-wrapper {
        flex-direction: column;
    }
    
    .manual-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .manual-card {
        padding: 20px;
    }
    
    .manual-header {
        flex-direction: column;
        text-align: center;
    }
    
    .quick-links {
        grid-template-columns: 1fr;
    }
    
    .step-card {
        flex-direction: column;
        text-align: center;
    }
    
    .step-card .step-icon {
        margin: 0 auto;
    }
}

@media (max-width: 480px) {
    .manual-card {
        padding: 15px;
    }
    
    .manual-header h2 {
        font-size: 1.2rem;
    }
    
    .manual-section-title {
        font-size: 1rem;
    }
}
</style>

<div class="container py-4">
    <div class="manual-wrapper">
        <!-- Sidebar -->
        <div class="manual-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="manual-content">
            <div class="manual-card">
                
                <!-- ==========================================
                     HEADER
                ========================================== -->
                <div class="manual-header">
                    <div class="icon">
                        <i class="fa-solid fa-book"></i>
                    </div>
                    <div>
                        <h2>Seller Manual</h2>
                        <p>Complete guide to selling on <?= SITE_NAME ?></p>
                    </div>
                </div>
                
                <!-- ==========================================
                     WELCOME SECTION
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <i class="fa-regular fa-hand"></i> Welcome, <?= sanitize($seller['shop_name']) ?>!
                    </div>
                    <p style="color: #4b5563; line-height: 1.7; margin-bottom: 15px;">
                        This manual will guide you through everything you need to know about selling on <?= SITE_NAME ?>. 
                        From setting up your shop to managing orders and growing your business.
                    </p>
                    
                    <div class="tip-box">
                        <i class="fa-regular fa-lightbulb"></i>
                        <div class="tip-content">
                            <h5>Getting Started Tip</h5>
                            <p>Start by familiarizing yourself with the seller dashboard. It's your central hub for managing all aspects of your shop.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 1: SETTING UP YOUR SHOP
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">1</span>
                        Setting Up Your Shop
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-store"></i></div>
                        <div class="step-content">
                            <h4>Create Your Shop Profile</h4>
                            <p>Go to <strong>My Shop</strong> to view and manage your shop details. Add your shop logo, description, and contact information.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                        <div class="step-content">
                            <h4>Edit Shop Information</h4>
                            <p>Use the <strong>Edit Shop</strong> option to update your shop name, phone number, location, business ID, and description.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-image"></i></div>
                        <div class="step-content">
                            <h4>Upload Shop Logo</h4>
                            <p>Add a professional logo to your shop. This helps build trust and brand recognition with customers.</p>
                        </div>
                    </div>
                    
                    <div class="tip-box">
                        <i class="fa-regular fa-circle-check"></i>
                        <div class="tip-content">
                            <h5>Pro Tip</h5>
                            <p>A complete shop profile with a logo and description gets 40% more customer engagement!</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 2: MANAGING PRODUCTS
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">2</span>
                        Managing Products
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-plus-circle"></i></div>
                        <div class="step-content">
                            <h4>Add New Product</h4>
                            <p>Click <strong>Add New Product</strong> to list a new item. Fill in all details including name, category, price, stock, and description.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-box"></i></div>
                        <div class="step-content">
                            <h4>Manage Products</h4>
                            <p>Use <strong>Manage Products</strong> to view, edit, or delete your products. You can also track product status (pending, approved, rejected).</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-tag"></i></div>
                        <div class="step-content">
                            <h4>Set Discounts & Flash Sales</h4>
                            <p>When adding or editing a product, you can enable discounts and flash sales to boost sales. Set a discount percentage and duration.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-image"></i></div>
                        <div class="step-content">
                            <h4>Product Images</h4>
                            <p>Upload multiple high-quality images for each product. Good images significantly increase conversion rates.</p>
                        </div>
                    </div>
                    
                    <div class="warning-box">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div class="warning-content">
                            <h5>Important</h5>
                            <p>Products must be approved by the admin before they appear in the shop. Make sure all information is accurate and complete.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 3: ORDER MANAGEMENT
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">3</span>
                        Order Management
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="step-content">
                            <h4>View Orders</h4>
                            <p>Go to <strong>Seller Orders</strong> to see all orders placed for your products. Monitor order status and customer details.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-rotate"></i></div>
                        <div class="step-content">
                            <h4>Update Order Status</h4>
                            <p>Update order status as you process them: Pending → Processing → Shipped → Delivered. Keep customers informed.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-message"></i></div>
                        <div class="step-content">
                            <h4>Communicate with Customers</h4>
                            <p>Use the <strong>Messages</strong> section to chat with customers about their orders and answer questions.</p>
                        </div>
                    </div>
                    
                    <div class="tip-box">
                        <i class="fa-regular fa-circle-check"></i>
                        <div class="tip-content">
                            <h5>Pro Tip</h5>
                            <p>Respond to customer messages within 24 hours. Fast responses lead to better ratings and repeat business!</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 4: EARNINGS & ANALYTICS
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">4</span>
                        Earnings & Analytics
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-coins"></i></div>
                        <div class="step-content">
                            <h4>Track Your Earnings</h4>
                            <p>View your total earnings from the <strong>Earnings</strong> section. See revenue breakdown and track your sales performance.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-chart-line"></i></div>
                        <div class="step-content">
                            <h4>Dashboard Analytics</h4>
                            <p>Your <strong>Seller Dashboard</strong> provides an overview of your shop performance including total sales, orders, and product statistics.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-star"></i></div>
                        <div class="step-content">
                            <h4>Customer Reviews</h4>
                            <p>Monitor your ratings and reviews. Positive reviews build trust and help you grow your business.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 5: BEST PRACTICES
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <i class="fa-regular fa-circle-check" style="color: #10b981;"></i>
                        Best Practices for Success
                    </div>
                    
                    <div class="step-card" style="border-left-color: #10b981;">
                        <div class="step-icon" style="background: #d1fae5; color: #10b981;"><i class="fa-regular fa-star"></i></div>
                        <div class="step-content">
                            <h4>1. High-Quality Images</h4>
                            <p>Use clear, well-lit photos from multiple angles. Good images increase conversion rates significantly.</p>
                        </div>
                    </div>
                    
                    <div class="step-card" style="border-left-color: #10b981;">
                        <div class="step-icon" style="background: #d1fae5; color: #10b981;"><i class="fa-regular fa-pen-to-square"></i></div>
                        <div class="step-content">
                            <h4>2. Detailed Descriptions</h4>
                            <p>Write clear, comprehensive product descriptions. Include specifications, features, and benefits.</p>
                        </div>
                    </div>
                    
                    <div class="step-card" style="border-left-color: #10b981;">
                        <div class="step-icon" style="background: #d1fae5; color: #10b981;"><i class="fa-regular fa-clock"></i></div>
                        <div class="step-content">
                            <h4>3. Competitive Pricing</h4>
                            <p>Research similar products and price competitively. Consider flash sales and discounts to attract customers.</p>
                        </div>
                    </div>
                    
                    <div class="step-card" style="border-left-color: #10b981;">
                        <div class="step-icon" style="background: #d1fae5; color: #10b981;"><i class="fa-regular fa-message"></i></div>
                        <div class="step-content">
                            <h4>4. Fast Customer Service</h4>
                            <p>Respond to customer inquiries promptly. Good service leads to positive reviews and repeat customers.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     QUICK LINKS
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <i class="fa-regular fa-link"></i>
                        Quick Links
                    </div>
                    
                    <div class="quick-links">
                        <a href="dashboard.php" class="quick-link-item">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>Seller Dashboard</span>
                        </a>
                        <a href="my_shop.php" class="quick-link-item">
                            <i class="fa-solid fa-store"></i>
                            <span>My Shop</span>
                        </a>
                        <a href="add_product.php" class="quick-link-item">
                            <i class="fa-solid fa-plus-circle"></i>
                            <span>Add New Product</span>
                        </a>
                        <a href="products.php" class="quick-link-item">
                            <i class="fa-solid fa-box"></i>
                            <span>Manage Products</span>
                        </a>
                        <a href="orders.php" class="quick-link-item">
                            <i class="fa-solid fa-truck-fast"></i>
                            <span>Seller Orders</span>
                        </a>
                        <a href="earnings.php" class="quick-link-item">
                            <i class="fa-solid fa-coins"></i>
                            <span>Earnings</span>
                        </a>
                        <a href="edit_profile.php" class="quick-link-item">
                            <i class="fa-solid fa-pen-to-square"></i>
                            <span>Edit Shop</span>
                        </a>
                        <a href="chats.php" class="quick-link-item">
                            <i class="fa-regular fa-message"></i>
                            <span>Messages</span>
                        </a>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     NEED HELP?
                ========================================== -->
                <div class="manual-section" style="margin-bottom: 0;">
                    <div style="background: #eff6ff; border-radius: 12px; padding: 20px; text-align: center;">
                        <i class="fa-regular fa-headset" style="font-size: 2rem; color: #2563eb;"></i>
                        <h4 style="margin: 10px 0 5px 0; color: #1f2937;">Need Help?</h4>
                        <p style="color: #6b7280; margin-bottom: 15px;">
                            If you have any questions or need assistance, feel free to contact our support team.
                        </p>
                        <a href="<?= BASE_URL ?>support.php" class="btn" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 10px 30px; border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-block;">
                            <i class="fa-regular fa-envelope"></i> Contact Support
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>