<?php
$page_title = 'Terms and Conditions';
require_once 'includes/header.php';
?>

<style>
    /* ============================================
       TERMS & CONDITIONS PAGE - MODERN DESIGN
    ============================================ */
    
    .terms-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }
    
    .terms-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .terms-hero h1 {
        color: #fff;
        font-size: 2.8rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }
    
    .terms-hero h1 i {
        color: #f59e0b;
    }
    
    .terms-hero p {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
        max-width: 600px;
    }
    
    .terms-hero .last-updated {
        display: inline-block;
        background: rgba(255,255,255,0.1);
        color: rgba(255,255,255,0.7);
        padding: 4px 16px;
        border-radius: 50px;
        font-size: 0.8rem;
        margin-top: 15px;
        position: relative;
        z-index: 1;
    }
    
    .terms-wrapper {
        display: flex;
        gap: 30px;
    }
    
    .terms-sidebar {
        width: 280px;
        flex-shrink: 0;
        position: sticky;
        top: 100px;
        align-self: flex-start;
    }
    
    .terms-content {
        flex: 1;
    }
    
    .terms-section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .terms-section:hover {
        border-color: #2563eb;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .terms-section .section-number {
        display: inline-block;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .terms-section h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .terms-section h3 i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .terms-section p {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
        margin-bottom: 10px;
    }
    
    .terms-section ul, .terms-section ol {
        padding-left: 20px;
        margin: 10px 0;
    }
    
    .terms-section li {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
        margin-bottom: 6px;
    }
    
    .terms-section li strong {
        color: #1f2937;
    }
    
    .terms-section .highlight-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        border-left: 4px solid #2563eb;
        margin: 15px 0;
    }
    
    .terms-section .highlight-box.warning {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    
    .terms-section .highlight-box.success {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    
    .terms-section .highlight-box.danger {
        border-left-color: #ef4444;
        background: #fef2f2;
    }
    
    .terms-section .highlight-box h5 {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .terms-section .highlight-box p {
        margin: 0;
        font-size: 0.9rem;
    }
    
    /* Sidebar Navigation */
    .terms-sidebar-nav {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
    
    .terms-sidebar-nav .nav-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .terms-sidebar-nav a {
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
    
    .terms-sidebar-nav a:hover {
        background: #f3f4f6;
        color: #2563eb;
    }
    
    .terms-sidebar-nav a i {
        width: 20px;
        color: #6b7280;
        font-size: 0.85rem;
    }
    
    .terms-sidebar-nav a:hover i {
        color: #2563eb;
    }
    
    /* Acceptance Section */
    .acceptance-section {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-radius: 16px;
        padding: 25px 30px;
        border: 1px solid #bfdbfe;
        margin-top: 25px;
    }
    
    .acceptance-section h4 {
        font-weight: 700;
        color: #1e40af;
        margin-bottom: 8px;
    }
    
    .acceptance-section p {
        color: #1e40af;
        font-size: 0.95rem;
        margin: 0;
    }
    
    .acceptance-section .btn-accept {
        display: inline-block;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 10px 30px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        margin-top: 10px;
        transition: all 0.3s ease;
    }
    
    .acceptance-section .btn-accept:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        color: white;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .terms-wrapper {
            flex-direction: column;
        }
        .terms-sidebar {
            width: 100%;
            position: static;
        }
        .terms-hero h1 {
            font-size: 2rem;
        }
        .terms-sidebar-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 12px;
        }
        .terms-sidebar-nav .nav-title {
            width: 100%;
            margin-bottom: 8px;
        }
        .terms-sidebar-nav a {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 768px) {
        .terms-hero {
            padding: 40px 0;
        }
        .terms-hero h1 {
            font-size: 1.8rem;
        }
        .terms-section {
            padding: 20px;
        }
        .terms-section h3 {
            font-size: 1.1rem;
        }
        .acceptance-section {
            padding: 20px;
        }
    }
    
    @media (max-width: 480px) {
        .terms-hero h1 {
            font-size: 1.5rem;
        }
        .terms-section {
            padding: 15px;
        }
    }
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="terms-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-regular fa-file-lines"></i> Legal
        </div>
        <h1><i class="fa-regular fa-file-lines"></i> Terms & Conditions</h1>
        <p>Please read these terms carefully before using <?= SITE_NAME ?>. By using our platform, you agree to these terms.</p>
        <div class="last-updated">
            <i class="fa-regular fa-calendar"></i> Last Updated: <?= date('F d, Y') ?>
        </div>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    <div class="terms-wrapper">
        
        <!-- Sidebar -->
        <div class="terms-sidebar">
            <div class="terms-sidebar-nav">
                <div class="nav-title"><i class="fa-regular fa-list"></i> Quick Navigation</div>
                <a href="#acceptance"><i class="fa-regular fa-circle-check"></i> Acceptance</a>
                <a href="#account"><i class="fa-regular fa-user"></i> Account</a>
                <a href="#products"><i class="fa-solid fa-box"></i> Products</a>
                <a href="#orders"><i class="fa-solid fa-truck"></i> Orders</a>
                <a href="#payments"><i class="fa-solid fa-credit-card"></i> Payments</a>
                <a href="#shipping"><i class="fa-solid fa-truck-fast"></i> Shipping</a>
                <a href="#returns"><i class="fa-solid fa-rotate-left"></i> Returns</a>
                <a href="#liability"><i class="fa-solid fa-shield-hart"></i> Liability</a>
                <a href="#privacy"><i class="fa-solid fa-lock"></i> Privacy</a>
                <a href="#contact"><i class="fa-regular fa-headset"></i> Contact</a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="terms-content">
            
            <!-- ==========================================
                 ACCEPTANCE OF TERMS
            ========================================== -->
            <div class="terms-section" id="acceptance">
                <span class="section-number">Section 1</span>
                <h3><i class="fa-regular fa-circle-check"></i> Acceptance of Terms</h3>
                <p>
                    By creating an account, accessing, or using <?= SITE_NAME ?> (the "Platform"), you agree to be bound by these 
                    Terms and Conditions. If you do not agree to these terms, please do not use the Platform.
                </p>
                <p>
                    We reserve the right to update or modify these terms at any time without prior notice. Your continued use 
                    of the Platform constitutes acceptance of the updated terms.
                </p>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-solid fa-circle-info" style="color:#f59e0b;"></i> Important</h5>
                    <p>These terms apply to all users of the Platform, including customers, sellers, and visitors.</p>
                </div>
                
                <div class="acceptance-section">
                    <h4><i class="fa-regular fa-circle-check"></i> By using <?= SITE_NAME ?>, you agree to:</h4>
                    <ul style="color:#1e40af; margin-top:8px;">
                        <li>Provide accurate and complete information</li>
                        <li>Maintain the security of your account</li>
                        <li>Comply with all applicable laws and regulations</li>
                        <li>Respect the rights of other users and sellers</li>
                    </ul>
                </div>
            </div>
            
            <!-- ==========================================
                 ACCOUNT REGISTRATION
            ========================================== -->
            <div class="terms-section" id="account">
                <span class="section-number">Section 2</span>
                <h3><i class="fa-regular fa-user"></i> Account Registration</h3>
                <p>
                    To access certain features of the Platform, you must create an account. You are responsible for maintaining 
                    the confidentiality of your account credentials and for all activities that occur under your account.
                </p>
                
                <ul>
                    <li><strong>Accuracy:</strong> You must provide accurate and complete information when creating your account.</li>
                    <li><strong>Security:</strong> You are responsible for maintaining the security of your password and account.</li>
                    <li><strong>Notification:</strong> You must notify us immediately of any unauthorized use of your account.</li>
                    <li><strong>Age Requirement:</strong> You must be at least 18 years old to create an account.</li>
                </ul>
                
                <div class="highlight-box danger">
                    <h5><i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> Security Notice</h5>
                    <p>We are not responsible for any loss or damage arising from unauthorized access to your account.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 PRODUCTS & LISTINGS
            ========================================== -->
            <div class="terms-section" id="products">
                <span class="section-number">Section 3</span>
                <h3><i class="fa-solid fa-box"></i> Products & Listings</h3>
                <p>
                    Sellers are responsible for the accuracy and completeness of their product listings. <?= SITE_NAME ?> is a 
                    marketplace that connects buyers and sellers but does not guarantee the quality, safety, or legality of 
                    products listed on the Platform.
                </p>
                
                <ul>
                    <li><strong>Product Descriptions:</strong> Sellers must provide accurate product descriptions, including price, condition, and specifications.</li>
                    <li><strong>Product Availability:</strong> Sellers are responsible for ensuring that products listed are available for purchase.</li>
                    <li><strong>Authenticity:</strong> Sellers must ensure that products are authentic and not counterfeit.</li>
                    <li><strong>Prohibited Items:</strong> Certain items are prohibited from being sold on the Platform.</li>
                </ul>
            </div>
            
            <!-- ==========================================
                 ORDERS & PURCHASES
            ========================================== -->
            <div class="terms-section" id="orders">
                <span class="section-number">Section 4</span>
                <h3><i class="fa-solid fa-truck"></i> Orders & Purchases</h3>
                <p>
                    When you place an order on <?= SITE_NAME ?>, you are entering into a legally binding contract with the seller. 
                    The seller is responsible for fulfilling the order and ensuring that the product is delivered as described.
                </p>
                
                <ul>
                    <li><strong>Order Acceptance:</strong> Orders are accepted at the seller's discretion.</li>
                    <li><strong>Order Cancellation:</strong> You may cancel an order before it has been processed by the seller.</li>
                    <li><strong>Disputes:</strong> Any disputes regarding orders must be resolved directly with the seller.</li>
                    <li><strong>Order Confirmation:</strong> You will receive a confirmation email after placing an order.</li>
                </ul>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check" style="color:#10b981;"></i> Buyer Protection</h5>
                    <p>If you encounter any issues with your order, please contact the seller first. If the issue is not resolved, you can contact our support team for assistance.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 PAYMENTS
            ========================================== -->
            <div class="terms-section" id="payments">
                <span class="section-number">Section 5</span>
                <h3><i class="fa-solid fa-credit-card"></i> Payments</h3>
                <p>
                    <?= SITE_NAME ?> facilitates payments between buyers and sellers. All payments are processed through secure 
                    payment gateways. We do not store your payment information on our servers.
                </p>
                
                <ul>
                    <li><strong>Payment Methods:</strong> We accept M-Pesa, Credit/Debit Cards, Bank Transfer, and PayPal.</li>
                    <li><strong>Payment Security:</strong> All transactions are encrypted and secure.</li>
                    <li><strong>Refunds:</strong> Refunds are processed by the seller in accordance with their refund policy.</li>
                    <li><strong>Fees:</strong> Sellers may be subject to transaction fees as outlined in their seller agreement.</li>
                </ul>
                
                <div class="highlight-box success">
                    <h5><i class="fa-solid fa-lock" style="color:#10b981;"></i> Secure Payments</h5>
                    <p>All payment transactions are processed through PCI-compliant gateways to ensure your financial information is protected.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SHIPPING & DELIVERY
            ========================================== -->
            <div class="terms-section" id="shipping">
                <span class="section-number">Section 6</span>
                <h3><i class="fa-solid fa-truck-fast"></i> Shipping & Delivery</h3>
                <p>
                    Sellers are responsible for shipping and delivery of products. Shipping times and costs vary by seller and location.
                </p>
                
                <ul>
                    <li><strong>Shipping Times:</strong> Delivery times are estimates and may vary.</li>
                    <li><strong>Shipping Costs:</strong> Shipping costs are displayed at checkout.</li>
                    <li><strong>Free Shipping:</strong> Orders over KSH 5,000 qualify for free standard shipping.</li>
                    <li><strong>Tracking:</strong> You will receive a tracking number once your order has been shipped.</li>
                </ul>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-regular fa-clock" style="color:#f59e0b;"></i> Delivery Notice</h5>
                    <p>Please ensure that your shipping address is accurate. We are not responsible for deliveries to incorrect addresses.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 RETURNS & REFUNDS
            ========================================== -->
            <div class="terms-section" id="returns">
                <span class="section-number">Section 7</span>
                <h3><i class="fa-solid fa-rotate-left"></i> Returns & Refunds</h3>
                <p>
                    Each seller has their own return and refund policy. Please review the seller's policy before making a purchase.
                </p>
                
                <ul>
                    <li><strong>Return Window:</strong> Most sellers accept returns within 7 days of delivery.</li>
                    <li><strong>Condition:</strong> Items must be unused and in original packaging.</li>
                    <li><strong>Refund Process:</strong> Refunds are processed by the seller upon receiving and inspecting the returned item.</li>
                    <li><strong>Non-Returnable Items:</strong> Digital products, perishable goods, and personalized items are typically non-returnable.</li>
                </ul>
                
                <div class="highlight-box danger">
                    <h5><i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> Important</h5>
                    <p>You are responsible for return shipping costs unless the item is defective or incorrect.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 LIABILITY & DISCLAIMERS
            ========================================== -->
            <div class="terms-section" id="liability">
                <span class="section-number">Section 8</span>
                <h3><i class="fa-solid fa-shield-hart"></i> Limitation of Liability</h3>
                <p>
                    <?= SITE_NAME ?> is provided "as is" and "as available" without warranties of any kind. We do not guarantee 
                    that the Platform will be uninterrupted or error-free.
                </p>
                
                <ul>
                    <li><strong>No Warranty:</strong> We do not warrant the accuracy, completeness, or reliability of any content on the Platform.</li>
                    <li><strong>Third-Party Links:</strong> We are not responsible for the content of third-party websites linked from the Platform.</li>
                    <li><strong>Limitation:</strong> To the maximum extent permitted by law, we are not liable for any indirect, incidental, or consequential damages.</li>
                </ul>
            </div>
            
            <!-- ==========================================
                 PRIVACY POLICY
            ========================================== -->
            <div class="terms-section" id="privacy">
                <span class="section-number">Section 9</span>
                <h3><i class="fa-solid fa-lock"></i> Privacy Policy</h3>
                <p>
                    We are committed to protecting your privacy. Our Privacy Policy outlines how we collect, use, and safeguard 
                    your personal information.
                </p>
                
                <ul>
                    <li><strong>Data Collection:</strong> We collect only the information necessary to provide our services.</li>
                    <li><strong>Data Security:</strong> We implement security measures to protect your personal information.</li>
                    <li><strong>Cookies:</strong> We use cookies to enhance your browsing experience.</li>
                    <li><strong>Third-Party Sharing:</strong> We do not sell or share your personal information with third parties without your consent.</li>
                </ul>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check" style="color:#10b981;"></i> Your Privacy Matters</h5>
                    <p>We are committed to protecting your personal information. Read our full <a href="privacy-policy.php" style="color:#2563eb; text-decoration:none; font-weight:600;">Privacy Policy</a> for more details.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 CONTACT INFORMATION
            ========================================== -->
            <div class="terms-section" id="contact">
                <span class="section-number">Section 10</span>
                <h3><i class="fa-regular fa-headset"></i> Contact Information</h3>
                <p>
                    If you have any questions or concerns about these Terms and Conditions, please contact us:
                </p>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center;">
                        <i class="fa-regular fa-envelope" style="font-size:1.5rem; color:#2563eb; display:block; margin-bottom:5px;"></i>
                        <div style="font-size:0.85rem; color:#6b7280;">Email</div>
                        <div style="font-weight:600; color:#1f2937;">support@multivendorhub.com</div>
                    </div>
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center;">
                        <i class="fa-solid fa-phone" style="font-size:1.5rem; color:#10b981; display:block; margin-bottom:5px;"></i>
                        <div style="font-size:0.85rem; color:#6b7280;">Phone</div>
                        <div style="font-weight:600; color:#1f2937;">+254 700 000 000</div>
                    </div>
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center;">
                        <i class="fa-regular fa-message" style="font-size:1.5rem; color:#f59e0b; display:block; margin-bottom:5px;"></i>
                        <div style="font-size:0.85rem; color:#6b7280;">Live Chat</div>
                        <div style="font-weight:600; color:#1f2937;">Available 24/7</div>
                    </div>
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center;">
                        <i class="fa-regular fa-circle-question" style="font-size:1.5rem; color:#7c3aed; display:block; margin-bottom:5px;"></i>
                        <div style="font-size:0.85rem; color:#6b7280;">Support</div>
                        <div style="font-weight:600; color:#1f2937;"><a href="support.php" style="color:#2563eb; text-decoration:none;">Submit a Ticket</a></div>
                    </div>
                </div>
                
                <div style="margin-top:20px; padding-top:20px; border-top:1px solid #e5e7eb;">
                    <p style="font-size:0.85rem; color:#6b7280;">
                        <strong>Mailing Address:</strong> <br>
                        <?= SITE_NAME ?><br>
                        Nairobi, Kenya
                    </p>
                </div>
            </div>
            
            <!-- ==========================================
                 ACKNOWLEDGMENT
            ========================================== -->
            <div class="terms-section" style="border:2px solid #2563eb; background:#f8fafc;">
                <div style="text-align:center; padding:10px;">
                    <i class="fa-regular fa-circle-check" style="font-size:2.5rem; color:#2563eb; display:block; margin-bottom:10px;"></i>
                    <h3 style="margin-bottom:10px;">Acknowledgment</h3>
                    <p style="color:#4b5563; max-width:600px; margin:0 auto;">
                        By using <?= SITE_NAME ?>, you acknowledge that you have read, understood, and agree to be bound by 
                        these Terms and Conditions.
                    </p>
                    <div style="margin-top:20px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                        <a href="register.php" class="btn" style="background:linear-gradient(135deg, #2563eb, #1d4ed8); color:white; padding:10px 30px; border-radius:10px; text-decoration:none; font-weight:600;">
                            <i class="fa-regular fa-user"></i> Create Account
                        </a>
                        <a href="index.php" class="btn" style="background:#f3f4f6; color:#374151; padding:10px 30px; border-radius:10px; text-decoration:none; font-weight:600;">
                            <i class="fa-solid fa-house"></i> Back to Home
                        </a>
                    </div>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
// Smooth scroll for sidebar links
document.querySelectorAll('.terms-sidebar-nav a').forEach(function(link) {
    link.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>