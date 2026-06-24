<?php
$page_title = 'Return Policy';
require_once 'includes/header.php';
?>

<style>
    /* ============================================
       RETURN POLICY PAGE - MODERN DESIGN
    ============================================ */
    
    .policy-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }
    
    .policy-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .policy-hero h1 {
        color: #fff;
        font-size: 2.8rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }
    
    .policy-hero h1 i {
        color: #f59e0b;
    }
    
    .policy-hero p {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
    }
    
    .policy-wrapper {
        display: flex;
        gap: 30px;
    }
    
    .policy-sidebar {
        width: 280px;
        flex-shrink: 0;
        position: sticky;
        top: 100px;
        align-self: flex-start;
    }
    
    .policy-content {
        flex: 1;
    }
    
    .policy-section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .policy-section:hover {
        border-color: #2563eb;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .policy-section .section-icon {
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
    
    .policy-section h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .policy-section h3 i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .policy-section p {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
    }
    
    .policy-section ul, .policy-section ol {
        padding-left: 20px;
        margin: 10px 0;
    }
    
    .policy-section li {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
        margin-bottom: 6px;
    }
    
    .policy-section li strong {
        color: #1f2937;
    }
    
    .policy-section .highlight-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        border-left: 4px solid #2563eb;
        margin: 15px 0;
    }
    
    .policy-section .highlight-box.warning {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    
    .policy-section .highlight-box.success {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    
    .policy-section .highlight-box.danger {
        border-left-color: #ef4444;
        background: #fef2f2;
    }
    
    .policy-section .highlight-box h5 {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .policy-section .highlight-box p {
        margin: 0;
        font-size: 0.9rem;
    }
    
    .policy-section .step-number {
        display: inline-block;
        width: 30px;
        height: 30px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border-radius: 50%;
        text-align: center;
        line-height: 30px;
        font-weight: 700;
        font-size: 0.85rem;
        margin-right: 10px;
    }
    
    .policy-section .step-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 8px 0;
    }
    
    .policy-section .step-item .step-content {
        flex: 1;
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.6;
    }
    
    .policy-section .step-item .step-content strong {
        color: #1f2937;
    }
    
    .policy-section .timeframe {
        display: inline-block;
        background: #fef3c7;
        color: #d97706;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .policy-section .badge-policy {
        display: inline-block;
        background: #dbeafe;
        color: #1d4ed8;
        padding: 2px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        margin-left: 8px;
    }
    
    /* Sidebar Navigation */
    .policy-sidebar-nav {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
    
    .policy-sidebar-nav .nav-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .policy-sidebar-nav a {
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
    
    .policy-sidebar-nav a:hover {
        background: #f3f4f6;
        color: #2563eb;
    }
    
    .policy-sidebar-nav a i {
        width: 20px;
        color: #6b7280;
        font-size: 0.85rem;
    }
    
    .policy-sidebar-nav a:hover i {
        color: #2563eb;
    }
    
    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        padding: 40px;
        text-align: center;
        color: white;
        margin-top: 30px;
    }
    
    .cta-section h3 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 8px;
        color: white;
    }
    
    .cta-section p {
        color: rgba(255,255,255,0.7);
        margin-bottom: 15px;
    }
    
    .cta-section .btn-support {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 12px 35px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: all 0.3s ease;
        margin: 0 5px;
    }
    
    .cta-section .btn-support:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(245,158,11,0.3);
        color: white;
    }
    
    .cta-section .btn-secondary {
        background: rgba(255,255,255,0.1);
        color: white;
        padding: 12px 35px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        display: inline-block;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.2);
        margin: 0 5px;
    }
    
    .cta-section .btn-secondary:hover {
        background: rgba(255,255,255,0.2);
        color: white;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .policy-wrapper {
            flex-direction: column;
        }
        .policy-sidebar {
            width: 100%;
            position: static;
        }
        .policy-hero h1 {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 768px) {
        .policy-hero {
            padding: 40px 0;
        }
        .policy-hero h1 {
            font-size: 1.8rem;
        }
        .policy-section {
            padding: 20px;
        }
        .policy-section h3 {
            font-size: 1.1rem;
        }
        .policy-section .step-item {
            flex-direction: column;
            align-items: flex-start;
        }
        .cta-section {
            padding: 25px;
        }
        .cta-section .btn-support, .cta-section .btn-secondary {
            width: 100%;
            margin: 5px 0;
        }
        .policy-sidebar-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 12px;
        }
        .policy-sidebar-nav .nav-title {
            width: 100%;
            margin-bottom: 8px;
        }
        .policy-sidebar-nav a {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 480px) {
        .policy-hero h1 {
            font-size: 1.5rem;
        }
        .policy-section {
            padding: 15px;
        }
        .policy-section .step-number {
            width: 25px;
            height: 25px;
            line-height: 25px;
            font-size: 0.75rem;
        }
    }
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="policy-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-solid fa-rotate-left"></i> Return Policy
        </div>
        <h1><i class="fa-solid fa-rotate-left"></i> Return & Refund Policy</h1>
        <p>Our commitment to your satisfaction. Learn about our return process, conditions, and refund timeline.</p>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    <div class="policy-wrapper">
        
        <!-- Sidebar -->
        <div class="policy-sidebar">
            <div class="policy-sidebar-nav">
                <div class="nav-title"><i class="fa-regular fa-list"></i> Quick Navigation</div>
                <a href="#overview"><i class="fa-regular fa-circle-info"></i> Overview</a>
                <a href="#conditions"><i class="fa-solid fa-list-check"></i> Return Conditions</a>
                <a href="#process"><i class="fa-solid fa-arrows-rotate"></i> Return Process</a>
                <a href="#refunds"><i class="fa-solid fa-money-bill-wave"></i> Refunds</a>
                <a href="#exceptions"><i class="fa-solid fa-triangle-exclamation"></i> Exceptions</a>
                <a href="#contact"><i class="fa-regular fa-headset"></i> Need Help?</a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="policy-content">
            
            <!-- Overview -->
            <div class="policy-section" id="overview">
                <div class="section-icon"><i class="fa-regular fa-circle-info"></i></div>
                <h3><i class="fa-regular fa-circle-check"></i> Overview</h3>
                <p>
                    At <strong><?= SITE_NAME ?></strong>, we want you to be completely satisfied with your purchase. 
                    If you're not happy with your order for any reason, we're here to help.
                </p>
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check" style="color:#10b981;"></i> Our Promise</h5>
                    <p>We offer a <strong>7-day return policy</strong> on most items. If you're not satisfied, 
                    you can return the item within 7 days of delivery for a full refund or exchange.</p>
                </div>
                <div style="display:flex; gap:15px; flex-wrap:wrap; margin-top:10px;">
                    <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; padding:6px 16px; border-radius:50px;">
                        <i class="fa-regular fa-clock" style="color:#2563eb;"></i>
                        <span style="font-size:0.85rem; color:#1f2937;">7 Days Return</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; padding:6px 16px; border-radius:50px;">
                        <i class="fa-regular fa-money-bill-1" style="color:#10b981;"></i>
                        <span style="font-size:0.85rem; color:#1f2937;">Full Refund</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; padding:6px 16px; border-radius:50px;">
                        <i class="fa-regular fa-handshake" style="color:#f59e0b;"></i>
                        <span style="font-size:0.85rem; color:#1f2937;">Easy Exchange</span>
                    </div>
                </div>
            </div>
            
            <!-- Return Conditions -->
            <div class="policy-section" id="conditions">
                <div class="section-icon"><i class="fa-solid fa-list-check"></i></div>
                <h3><i class="fa-solid fa-list-check"></i> Return Conditions</h3>
                <p>To be eligible for a return, please ensure the following conditions are met:</p>
                
                <ul>
                    <li><strong>Item Condition:</strong> Items must be <strong>unused, undamaged, and in their original packaging</strong> with all tags and accessories attached.</li>
                    <li><strong>Return Window:</strong> Returns must be initiated within <strong>7 days</strong> of the delivery date.</li>
                    <li><strong>Proof of Purchase:</strong> A valid <strong>order number or receipt</strong> is required for all returns.</li>
                    <li><strong>Original Packaging:</strong> Items must be returned in their original packaging to ensure safe transit.</li>
                    <li><strong>Return Shipping:</strong> Customers are responsible for return shipping costs unless the item is damaged or incorrect.</li>
                </ul>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-solid fa-circle-exclamation" style="color:#f59e0b;"></i> Important Note</h5>
                    <p>Items that have been used, washed, or damaged by the customer will not be eligible for return.</p>
                </div>
            </div>
            
            <!-- Return Process -->
            <div class="policy-section" id="process">
                <div class="section-icon"><i class="fa-solid fa-arrows-rotate"></i></div>
                <h3><i class="fa-solid fa-arrows-rotate"></i> How to Return an Item</h3>
                <p>Follow these simple steps to return your item:</p>
                
                <div class="step-item">
                    <span class="step-number">1</span>
                    <div class="step-content">
                        <strong>Contact the Seller</strong>
                        <p>Go to your order details and click "Contact Seller" to initiate the return process.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">2</span>
                    <div class="step-content">
                        <strong>Get Return Authorization</strong>
                        <p>Wait for the seller to approve your return request. You'll receive instructions on how to return the item.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">3</span>
                    <div class="step-content">
                        <strong>Package the Item</strong>
                        <p>Pack the item securely in its original packaging. Include the order number and your contact information.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">4</span>
                    <div class="step-content">
                        <strong>Ship the Item</strong>
                        <p>Send the item back to the seller using a trackable shipping method.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <span class="step-number">5</span>
                    <div class="step-content">
                        <strong>Get Your Refund</strong>
                        <p>Once the seller receives and inspects the item, your refund will be processed within <strong>3-5 business days</strong>.</p>
                    </div>
                </div>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-clock" style="color:#10b981;"></i> Processing Time</h5>
                    <p>Refunds are typically processed within 3-5 business days after the item is received and inspected.</p>
                </div>
            </div>
            
            <!-- Refunds -->
            <div class="policy-section" id="refunds">
                <div class="section-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                <h3><i class="fa-solid fa-money-bill-wave"></i> Refund Information</h3>
                
                <p>Here's what you need to know about refunds:</p>
                
                <ul>
                    <li><strong>Full Refund:</strong> You'll receive a full refund for the purchase price of the item.</li>
                    <li><strong>Shipping Costs:</strong> Original shipping costs are non-refundable unless the return is due to a seller error.</li>
                    <li><strong>Return Shipping:</strong> Customers are responsible for return shipping costs.</li>
                    <li><strong>Refund Method:</strong> Refunds are issued to the original payment method used for the purchase.</li>
                    <li><strong>Processing Time:</strong> Refunds may take <strong>3-5 business days</strong> to appear in your account.</li>
                </ul>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-regular fa-clock" style="color:#f59e0b;"></i> Refund Timeline</h5>
                    <p>Refund processing times vary by payment method. M-Pesa refunds are typically faster than bank transfers.</p>
                </div>
            </div>
            
            <!-- Exceptions -->
            <div class="policy-section" id="exceptions">
                <div class="section-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <h3><i class="fa-solid fa-triangle-exclamation"></i> Return Exceptions</h3>
                
                <p>The following items cannot be returned:</p>
                
                <ul>
                    <li><strong>Perishable Goods:</strong> Food items, flowers, and other perishable products.</li>
                    <li><strong>Personal Care Items:</strong> Cosmetics, skincare, and personal hygiene products.</li>
                    <li><strong>Digital Products:</strong> Software, e-books, and digital downloads.</li>
                    <li><strong>Custom Orders:</strong> Personalized or custom-made items.</li>
                    <li><strong>Sale Items:</strong> Items purchased during clearance or flash sales (unless defective).</li>
                </ul>
                
                <div class="highlight-box danger">
                    <h5><i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> Non-Refundable Items</h5>
                    <p>Please check the product description carefully before purchasing to confirm return eligibility.</p>
                </div>
            </div>
            
            <!-- Need Help -->
            <div class="policy-section" id="contact">
                <div class="section-icon"><i class="fa-regular fa-headset"></i></div>
                <h3><i class="fa-regular fa-headset"></i> Need Help?</h3>
                <p>If you have any questions about our return policy or need assistance with a return, our support team is here to help.</p>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top:15px;">
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center;">
                        <i class="fa-regular fa-envelope" style="font-size:1.5rem; color:#2563eb; display:block; margin-bottom:5px;"></i>
                        <div style="font-size:0.85rem; color:#6b7280;">Email Support</div>
                        <div style="font-weight:600; color:#1f2937;">support@multivendorhub.com</div>
                    </div>
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center;">
                        <i class="fa-regular fa-message" style="font-size:1.5rem; color:#10b981; display:block; margin-bottom:5px;"></i>
                        <div style="font-size:0.85rem; color:#6b7280;">Live Chat</div>
                        <div style="font-weight:600; color:#1f2937;">Available 24/7</div>
                    </div>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div class="cta-section">
                <h3><i class="fa-regular fa-circle-question"></i> Still Have Questions?</h3>
                <p>Our support team is ready to help you with any questions about returns and refunds.</p>
                <div>
                    <a href="support.php" class="btn-support">
                        <i class="fa-regular fa-headset"></i> Contact Support
                    </a>
                    <a href="faq.php" class="btn-secondary">
                        <i class="fa-regular fa-circle-question"></i> Visit FAQ
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</div>

<script>
// Smooth scroll for sidebar links
document.querySelectorAll('.policy-sidebar-nav a').forEach(function(link) {
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