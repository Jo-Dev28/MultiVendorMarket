<?php
$page_title = 'Customer Manual';
require_once 'includes/header.php';

// Get user info
$user = current_user();
$is_logged_in = isset($user['id']) && $user['id'];
?>

<style>
/* ============================================
   CUSTOMER MANUAL - MODERN CLEAN DESIGN
============================================ */

/* ---------- MAIN LAYOUT ---------- */
.manual-wrapper {
    display: flex;
    gap: 25px;
    min-height: 600px;
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
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
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

/* ---------- FEATURES GRID ---------- */
.features-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-top: 15px;
}
.feature-item {
    background: #f8fafc;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}
.feature-item:hover {
    background: #eff6ff;
    transform: translateY(-3px);
}
.feature-item i {
    font-size: 2rem;
    color: #2563eb;
    margin-bottom: 8px;
}
.feature-item h5 {
    font-size: 0.9rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}
.feature-item p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 992px) {
    .manual-wrapper {
        flex-direction: column;
    }
    .manual-sidebar {
        width: 100%;
    }
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
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
    .features-grid {
        grid-template-columns: 1fr 1fr;
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
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container py-4">
    <div class="manual-wrapper">
        <!-- Sidebar -->
        <div class="manual-sidebar">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="manual-content">
            <div class="manual-card">
                
                <!-- ==========================================
                     HEADER
                ========================================== -->
                <div class="manual-header">
                    <div class="icon">
                        <i class="fa-regular fa-circle-question"></i>
                    </div>
                    <div>
                        <h2>Customer Guide</h2>
                        <p>Everything you need to know about shopping on <?= SITE_NAME ?></p>
                    </div>
                </div>
                
                <!-- ==========================================
                     WELCOME SECTION
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <i class="fa-regular fa-hand"></i> Welcome to <?= SITE_NAME ?>!
                    </div>
                    <p style="color: #4b5563; line-height: 1.7; margin-bottom: 15px;">
                        This guide will help you navigate <?= SITE_NAME ?>, find the best products, and make secure purchases 
                        from our trusted sellers. Whether you're a first-time shopper or a regular customer, 
                        you'll find everything you need to know here.
                    </p>
                    
                    <div class="tip-box">
                        <i class="fa-regular fa-lightbulb"></i>
                        <div class="tip-content">
                            <h5>Getting Started Tip</h5>
                            <p>Start by exploring our categories or using the AI Assistant to find exactly what you're looking for.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 1: ACCOUNT SETUP
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">1</span>
                        Account Setup
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-user"></i></div>
                        <div class="step-content">
                            <h4>Create an Account</h4>
                            <p>Click "Register" in the top right corner. Fill in your name, email, and password to create your account.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-envelope"></i></div>
                        <div class="step-content">
                            <h4>Verify Your Email</h4>
                            <p>Check your email for a verification link. Click it to verify your account and start shopping.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-id-card"></i></div>
                        <div class="step-content">
                            <h4>Complete Your Profile</h4>
                            <p>Go to <strong>My Profile</strong> to add your phone number and shipping address for faster checkout.</p>
                        </div>
                    </div>
                    
                    <div class="tip-box">
                        <i class="fa-regular fa-circle-check"></i>
                        <div class="tip-content">
                            <h5>Pro Tip</h5>
                            <p>Saving your address and phone number makes checkout faster and easier!</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 2: FINDING PRODUCTS
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">2</span>
                        Finding Products
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div class="step-content">
                            <h4>Search for Products</h4>
                            <p>Use the search bar at the top of the page to find products by name, category, or seller.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-filter"></i></div>
                        <div class="step-content">
                            <h4>Filter & Sort Results</h4>
                            <p>On the shop page, use filters to narrow down by category, price range, and sort by newest, price, or rating.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-robot"></i></div>
                        <div class="step-content">
                            <h4>AI Assistant</h4>
                            <p>Ask the AI Assistant for product recommendations. Just type your question and get smart suggestions.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-star"></i></div>
                        <div class="step-content">
                            <h4>Check Ratings & Reviews</h4>
                            <p>Always check product ratings and read customer reviews before making a purchase.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 3: MAKING A PURCHASE
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">3</span>
                        Making a Purchase
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-cart-plus"></i></div>
                        <div class="step-content">
                            <h4>Add to Cart</h4>
                            <p>On any product page, select the quantity and click "Add to Cart". You can also add items from the shop page.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-heart"></i></div>
                        <div class="step-content">
                            <h4>Save to Wishlist</h4>
                            <p>Click the heart icon on any product to save it to your wishlist for later.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                        <div class="step-content">
                            <h4>Review Your Cart</h4>
                            <p>Go to your cart to review items, update quantities, or remove products before checkout.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-lock"></i></div>
                        <div class="step-content">
                            <h4>Checkout Securely</h4>
                            <p>Enter your shipping address, select a payment method, and confirm your order. All payments are secure.</p>
                        </div>
                    </div>
                    
                    <div class="warning-box">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div class="warning-content">
                            <h5>Important</h5>
                            <p>Always double-check your shipping address and order details before placing your order.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 4: PAYMENT & SHIPPING
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">4</span>
                        Payment & Shipping
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-credit-card"></i></div>
                        <div class="step-content">
                            <h4>Payment Methods</h4>
                            <p>We accept M-Pesa, Credit/Debit Cards, Bank Transfer, and PayPal. Choose what works best for you.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <div class="step-content">
                            <h4>Shipping</h4>
                            <p>Delivery takes 2-5 business days. Free shipping on orders over KSH 5,000.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-receipt"></i></div>
                        <div class="step-content">
                            <h4>Order Confirmation</h4>
                            <p>After placing your order, you'll receive a confirmation email with order details and tracking information.</p>
                        </div>
                    </div>
                    
                    <div class="tip-box">
                        <i class="fa-regular fa-circle-check"></i>
                        <div class="tip-content">
                            <h5>Pro Tip</h5>
                            <p>Combine multiple items from the same seller to save on shipping costs.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 5: TRACKING ORDERS
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">5</span>
                        Tracking Orders
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-truck"></i></div>
                        <div class="step-content">
                            <h4>View Your Orders</h4>
                            <p>Go to <strong>My Orders</strong> in your dashboard to see all your past and current orders.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-location-dot"></i></div>
                        <div class="step-content">
                            <h4>Track Order Status</h4>
                            <p>Click "Track Order" on any order to see its current status: Pending → Processing → Shipped → Delivered.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-clock"></i></div>
                        <div class="step-content">
                            <h4>Cancel an Order</h4>
                            <p>You can cancel pending or processing orders. Go to "My Orders" and click "Cancel Order".</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-star"></i></div>
                        <div class="step-content">
                            <h4>Review Your Purchase</h4>
                            <p>After receiving your order, rate and review the product to help other shoppers.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 6: RETURNS & REFUNDS
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <span class="step-number">6</span>
                        Returns & Refunds
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-rotate-left"></i></div>
                        <div class="step-content">
                            <h4>Return Policy</h4>
                            <p>Items can be returned within 7 days of delivery. Products must be unused with original packaging.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-regular fa-message"></i></div>
                        <div class="step-content">
                            <h4>Contact the Seller</h4>
                            <p>For returns or issues, first contact the seller through the chat system. They'll guide you through the process.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                        <div class="step-content">
                            <h4>Refund Process</h4>
                            <p>Once the seller confirms return, refunds are processed within 3-5 business days.</p>
                        </div>
                    </div>
                    
                    <div class="warning-box">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div class="warning-content">
                            <h5>Important</h5>
                            <p>Digital products, perishable items, and personalized products are non-returnable.</p>
                        </div>
                    </div>
                </div>
                
                <div class="section-divider"></div>
                
                <!-- ==========================================
                     SECTION 7: FEATURES OVERVIEW
                ========================================== -->
                <div class="manual-section">
                    <div class="manual-section-title">
                        <i class="fa-regular fa-star"></i>
                        Platform Features
                    </div>
                    
                    <div class="features-grid">
                        <div class="feature-item">
                            <i class="fa-solid fa-robot"></i>
                            <h5>AI Assistant</h5>
                            <p>Get smart product recommendations</p>
                        </div>
                        <div class="feature-item">
                            <i class="fa-regular fa-heart"></i>
                            <h5>Wishlist</h5>
                            <p>Save products for later</p>
                        </div>
                        <div class="feature-item">
                            <i class="fa-solid fa-chart-simple"></i>
                            <h5>Compare Products</h5>
                            <p>Compare prices and features</p>
                        </div>
                        <div class="feature-item">
                            <i class="fa-regular fa-message"></i>
                            <h5>Chat with Sellers</h5>
                            <p>Ask questions before buying</p>
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
                        <a href="shop.php" class="quick-link-item">
                            <i class="fa-solid fa-store"></i>
                            <span>Shop Products</span>
                        </a>
                        <a href="cart.php" class="quick-link-item">
                            <i class="fa-solid fa-cart-shopping"></i>
                            <span>Shopping Cart</span>
                        </a>
                        <a href="orders.php" class="quick-link-item">
                            <i class="fa-solid fa-truck"></i>
                            <span>My Orders</span>
                        </a>
                        <a href="wishlist.php" class="quick-link-item">
                            <i class="fa-regular fa-heart"></i>
                            <span>My Wishlist</span>
                        </a>
                        <a href="profile.php" class="quick-link-item">
                            <i class="fa-regular fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="ai_assistant.php" class="quick-link-item">
                            <i class="fa-solid fa-robot"></i>
                            <span>AI Assistant</span>
                        </a>
                        <a href="support.php" class="quick-link-item">
                            <i class="fa-regular fa-headset"></i>
                            <span>Help & Support</span>
                        </a>
                        <a href="become-seller.php" class="quick-link-item">
                            <i class="fa-solid fa-store"></i>
                            <span>Become a Seller</span>
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
                            If you have any questions or need assistance, our support team is here to help.
                        </p>
                        <a href="support.php" class="btn" style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 10px 30px; border-radius: 10px; text-decoration: none; font-weight: 600; display: inline-block;">
                            <i class="fa-regular fa-envelope"></i> Contact Support
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>