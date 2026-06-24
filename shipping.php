<?php
$page_title = 'Shipping Information';
require_once 'includes/header.php';
?>

<style>
    /* ============================================
       SHIPPING INFO PAGE - MODERN DESIGN
    ============================================ */
    
    .shipping-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }
    
    .shipping-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .shipping-hero h1 {
        color: #fff;
        font-size: 2.8rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }
    
    .shipping-hero h1 i {
        color: #f59e0b;
    }
    
    .shipping-hero p {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
    }
    
    .shipping-wrapper {
        display: flex;
        gap: 30px;
    }
    
    .shipping-sidebar {
        width: 280px;
        flex-shrink: 0;
        position: sticky;
        top: 100px;
        align-self: flex-start;
    }
    
    .shipping-content {
        flex: 1;
    }
    
    .shipping-section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .shipping-section:hover {
        border-color: #2563eb;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .shipping-section .section-icon {
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
    
    .shipping-section h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .shipping-section h3 i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .shipping-section p {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
    }
    
    .shipping-section ul, .shipping-section ol {
        padding-left: 20px;
        margin: 10px 0;
    }
    
    .shipping-section li {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
        margin-bottom: 6px;
    }
    
    .shipping-section li strong {
        color: #1f2937;
    }
    
    .shipping-section .highlight-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        border-left: 4px solid #2563eb;
        margin: 15px 0;
    }
    
    .shipping-section .highlight-box.warning {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    
    .shipping-section .highlight-box.success {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    
    .shipping-section .highlight-box.danger {
        border-left-color: #ef4444;
        background: #fef2f2;
    }
    
    .shipping-section .highlight-box h5 {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .shipping-section .highlight-box p {
        margin: 0;
        font-size: 0.9rem;
    }
    
    .shipping-method-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        border: 1px solid #e5e7eb;
        margin-bottom: 10px;
        transition: all 0.3s ease;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .shipping-method-card:hover {
        border-color: #2563eb;
        background: #eff6ff;
    }
    
    .shipping-method-card .method-info .method-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.95rem;
    }
    
    .shipping-method-card .method-info .method-desc {
        font-size: 0.8rem;
        color: #6b7280;
    }
    
    .shipping-method-card .method-price {
        font-weight: 700;
        color: #2563eb;
        font-size: 1.1rem;
    }
    
    .shipping-method-card .method-price .free {
        color: #10b981;
    }
    
    .shipping-timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 20px;
    }
    
    .shipping-timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e5e7eb;
    }
    
    .shipping-timeline-item {
        position: relative;
        margin-bottom: 25px;
        padding-left: 25px;
    }
    
    .shipping-timeline-item:last-child {
        margin-bottom: 0;
    }
    
    .shipping-timeline-item .dot {
        position: absolute;
        left: -22px;
        top: 4px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #2563eb;
        border: 3px solid white;
        box-shadow: 0 0 0 2px #2563eb;
    }
    
    .shipping-timeline-item .dot.completed {
        background: #10b981;
        box-shadow: 0 0 0 2px #10b981;
    }
    
    .shipping-timeline-item .step-title {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.95rem;
    }
    
    .shipping-timeline-item .step-desc {
        color: #6b7280;
        font-size: 0.85rem;
        margin-top: 2px;
    }
    
    .shipping-timeline-item .step-time {
        font-size: 0.7rem;
        color: #9ca3af;
        margin-top: 2px;
    }
    
    .shipping-timeline-item .step-icon {
        position: absolute;
        left: -30px;
        top: 2px;
        font-size: 1rem;
        color: #2563eb;
        background: white;
        padding: 0 4px;
        z-index: 1;
    }
    
    /* Sidebar Navigation */
    .shipping-sidebar-nav {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
    
    .shipping-sidebar-nav .nav-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .shipping-sidebar-nav a {
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
    
    .shipping-sidebar-nav a:hover {
        background: #f3f4f6;
        color: #2563eb;
    }
    
    .shipping-sidebar-nav a i {
        width: 20px;
        color: #6b7280;
        font-size: 0.85rem;
    }
    
    .shipping-sidebar-nav a:hover i {
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
        .shipping-wrapper {
            flex-direction: column;
        }
        .shipping-sidebar {
            width: 100%;
            position: static;
        }
        .shipping-hero h1 {
            font-size: 2rem;
        }
        .shipping-method-card {
            flex-direction: column;
            text-align: center;
            gap: 8px;
        }
    }
    
    @media (max-width: 768px) {
        .shipping-hero {
            padding: 40px 0;
        }
        .shipping-hero h1 {
            font-size: 1.8rem;
        }
        .shipping-section {
            padding: 20px;
        }
        .shipping-section h3 {
            font-size: 1.1rem;
        }
        .cta-section {
            padding: 25px;
        }
        .cta-section .btn-support, .cta-section .btn-secondary {
            width: 100%;
            margin: 5px 0;
        }
        .shipping-sidebar-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 12px;
        }
        .shipping-sidebar-nav .nav-title {
            width: 100%;
            margin-bottom: 8px;
        }
        .shipping-sidebar-nav a {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
        .shipping-timeline-item {
            padding-left: 15px;
        }
        .shipping-timeline-item .step-icon {
            left: -22px;
        }
    }
    
    @media (max-width: 480px) {
        .shipping-hero h1 {
            font-size: 1.5rem;
        }
        .shipping-section {
            padding: 15px;
        }
        .shipping-timeline {
            padding-left: 20px;
        }
        .shipping-timeline-item .dot {
            left: -16px;
            width: 10px;
            height: 10px;
        }
    }
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="shipping-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-solid fa-truck"></i> Shipping Information
        </div>
        <h1><i class="fa-solid fa-truck"></i> Shipping & Delivery</h1>
        <p>Everything you need to know about shipping times, costs, and delivery options.</p>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    <div class="shipping-wrapper">
        
        <!-- Sidebar -->
        <div class="shipping-sidebar">
            <div class="shipping-sidebar-nav">
                <div class="nav-title"><i class="fa-regular fa-list"></i> Quick Navigation</div>
                <a href="#overview"><i class="fa-regular fa-circle-info"></i> Overview</a>
                <a href="#delivery-times"><i class="fa-regular fa-clock"></i> Delivery Times</a>
                <a href="#shipping-costs"><i class="fa-solid fa-coins"></i> Shipping Costs</a>
                <a href="#tracking"><i class="fa-solid fa-location-dot"></i> Order Tracking</a>
                <a href="#international"><i class="fa-solid fa-earth-africa"></i> International Shipping</a>
                <a href="#faq"><i class="fa-regular fa-circle-question"></i> FAQ</a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="shipping-content">
            
            <!-- Overview -->
            <div class="shipping-section" id="overview">
                <div class="section-icon"><i class="fa-regular fa-circle-info"></i></div>
                <h3><i class="fa-regular fa-circle-check"></i> Shipping Overview</h3>
                <p>
                    At <strong><?= SITE_NAME ?></strong>, we strive to deliver your orders quickly and reliably. 
                    We partner with trusted courier services across Kenya to ensure your packages arrive safely and on time.
                </p>
                <div class="highlight-box success">
                    <h5><i class="fa-solid fa-truck-fast" style="color:#10b981;"></i> Our Commitment</h5>
                    <p>We offer <strong>free shipping</strong> on all orders over KSH 5,000. Standard delivery takes 2-5 business days.</p>
                </div>
                <div style="display:flex; gap:15px; flex-wrap:wrap; margin-top:10px;">
                    <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; padding:6px 16px; border-radius:50px;">
                        <i class="fa-solid fa-truck" style="color:#2563eb;"></i>
                        <span style="font-size:0.85rem; color:#1f2937;">2-5 Days Delivery</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; padding:6px 16px; border-radius:50px;">
                        <i class="fa-solid fa-coins" style="color:#10b981;"></i>
                        <span style="font-size:0.85rem; color:#1f2937;">Free Shipping Over KSH 5,000</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; background:#f8fafc; padding:6px 16px; border-radius:50px;">
                        <i class="fa-solid fa-location-dot" style="color:#f59e0b;"></i>
                        <span style="font-size:0.85rem; color:#1f2937;">Track Your Order</span>
                    </div>
                </div>
            </div>
            
            <!-- Delivery Times -->
            <div class="shipping-section" id="delivery-times">
                <div class="section-icon"><i class="fa-regular fa-clock"></i></div>
                <h3><i class="fa-regular fa-clock"></i> Delivery Times</h3>
                <p>Estimated delivery times based on your location:</p>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px; margin-top:15px;">
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center; border:1px solid #e5e7eb;">
                        <i class="fa-solid fa-city" style="font-size:1.5rem; color:#2563eb; display:block; margin-bottom:5px;"></i>
                        <div style="font-weight:600; color:#1f2937;">Nairobi &amp; Major Cities</div>
                        <div style="font-size:0.85rem; color:#10b981; font-weight:600;">1-3 Days</div>
                        <div style="font-size:0.7rem; color:#6b7280;">Same day available</div>
                    </div>
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center; border:1px solid #e5e7eb;">
                        <i class="fa-solid fa-people-group" style="font-size:1.5rem; color:#f59e0b; display:block; margin-bottom:5px;"></i>
                        <div style="font-weight:600; color:#1f2937;">Other Towns</div>
                        <div style="font-size:0.85rem; color:#f59e0b; font-weight:600;">2-5 Days</div>
                        <div style="font-size:0.7rem; color:#6b7280;">Standard delivery</div>
                    </div>
                    <div style="background:#f8fafc; border-radius:12px; padding:15px; text-align:center; border:1px solid #e5e7eb;">
                        <i class="fa-solid fa-tree" style="font-size:1.5rem; color:#6b7280; display:block; margin-bottom:5px;"></i>
                        <div style="font-weight:600; color:#1f2937;">Rural Areas</div>
                        <div style="font-size:0.85rem; color:#6b7280; font-weight:600;">3-7 Days</div>
                        <div style="font-size:0.7rem; color:#6b7280;">May take longer</div>
                    </div>
                </div>
                
                <div class="highlight-box warning" style="margin-top:15px;">
                    <h5><i class="fa-regular fa-clock" style="color:#f59e0b;"></i> Important Note</h5>
                    <p>Delivery times are estimates and may vary due to weather, holidays, or other unforeseen circumstances.</p>
                </div>
            </div>
            
            <!-- Shipping Costs -->
            <div class="shipping-section" id="shipping-costs">
                <div class="section-icon"><i class="fa-solid fa-coins"></i></div>
                <h3><i class="fa-solid fa-coins"></i> Shipping Costs</h3>
                <p>Our shipping rates are designed to be affordable and transparent:</p>
                
                <div class="shipping-method-card">
                    <div class="method-info">
                        <div class="method-name">Standard Shipping</div>
                        <div class="method-desc">2-5 business days</div>
                    </div>
                    <div class="method-price">
                        <?php if (defined('STANDARD_SHIPPING_COST')): ?>
                            KSH <?= number_format(STANDARD_SHIPPING_COST) ?>
                        <?php else: ?>
                            KSH 250
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="shipping-method-card">
                    <div class="method-info">
                        <div class="method-name">Express Shipping</div>
                        <div class="method-desc">1-2 business days</div>
                    </div>
                    <div class="method-price">
                        <?php if (defined('EXPRESS_SHIPPING_COST')): ?>
                            KSH <?= number_format(EXPRESS_SHIPPING_COST) ?>
                        <?php else: ?>
                            KSH 500
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="shipping-method-card">
                    <div class="method-info">
                        <div class="method-name">Free Shipping</div>
                        <div class="method-desc">Orders over KSH 5,000</div>
                    </div>
                    <div class="method-price">
                        <span class="free"><i class="fa-regular fa-circle-check"></i> FREE</span>
                    </div>
                </div>
                
                <div class="shipping-method-card">
                    <div class="method-info">
                        <div class="method-name">Same Day Delivery</div>
                        <div class="method-desc">Available in Nairobi (order by 12 PM)</div>
                    </div>
                    <div class="method-price">
                        <?php if (defined('SAME_DAY_SHIPPING_COST')): ?>
                            KSH <?= number_format(SAME_DAY_SHIPPING_COST) ?>
                        <?php else: ?>
                            KSH 800
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="highlight-box success" style="margin-top:15px;">
                    <h5><i class="fa-solid fa-tag" style="color:#10b981;"></i> Save on Shipping</h5>
                    <p>Add items to your cart to reach KSH 5,000 and get <strong>free shipping</strong> on your entire order!</p>
                </div>
            </div>
            
            <!-- Order Tracking -->
            <div class="shipping-section" id="tracking">
                <div class="section-icon"><i class="fa-solid fa-location-dot"></i></div>
                <h3><i class="fa-solid fa-location-dot"></i> Order Tracking</h3>
                <p>Stay informed about your order status with real-time tracking:</p>
                
                <div class="shipping-timeline">
                    <div class="shipping-timeline-item">
                        <div class="dot completed"></div>
                        <div class="step-icon"><i class="fa-regular fa-circle-check" style="color:#10b981;"></i></div>
                        <div class="step-title">Order Confirmed</div>
                        <div class="step-desc">Your order has been received and confirmed.</div>
                        <div class="step-time">Immediately after placing order</div>
                    </div>
                    <div class="shipping-timeline-item">
                        <div class="dot completed"></div>
                        <div class="step-icon"><i class="fa-solid fa-box" style="color:#2563eb;"></i></div>
                        <div class="step-title">Order Processing</div>
                        <div class="step-desc">The seller is preparing your items for shipment.</div>
                        <div class="step-time">1-2 business days</div>
                    </div>
                    <div class="shipping-timeline-item">
                        <div class="dot"></div>
                        <div class="step-icon"><i class="fa-solid fa-truck" style="color:#f59e0b;"></i></div>
                        <div class="step-title">Order Shipped</div>
                        <div class="step-desc">Your order is on its way! You'll receive a tracking number.</div>
                        <div class="step-time">2-5 business days</div>
                    </div>
                    <div class="shipping-timeline-item">
                        <div class="dot"></div>
                        <div class="step-icon"><i class="fa-regular fa-circle-check" style="color:#10b981;"></i></div>
                        <div class="step-title">Order Delivered</div>
                        <div class="step-desc">Your package has been delivered successfully.</div>
                        <div class="step-time">Delivery day</div>
                    </div>
                </div>
                
                <div class="highlight-box success" style="margin-top:15px;">
                    <h5><i class="fa-solid fa-location-dot" style="color:#10b981;"></i> Track Your Order</h5>
                    <p>Go to <strong>"My Orders"</strong> in your dashboard and click <strong>"Track Order"</strong> to see real-time updates.</p>
                </div>
            </div>
            
            <!-- International Shipping -->
            <div class="shipping-section" id="international">
                <div class="section-icon"><i class="fa-solid fa-earth-africa"></i></div>
                <h3><i class="fa-solid fa-earth-africa"></i> International Shipping</h3>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-solid fa-circle-info" style="color:#f59e0b;"></i> Currently Not Available</h5>
                    <p>At this time, <?= SITE_NAME ?> only ships within <strong>Kenya</strong>. We are actively working on expanding our shipping services to other countries.</p>
                </div>
                <p style="margin-top:10px;">
                    If you're interested in international shipping, please contact our support team and we'll keep you updated on our expansion plans.
                </p>
            </div>
            
            <!-- FAQ -->
            <div class="shipping-section" id="faq">
                <div class="section-icon"><i class="fa-regular fa-circle-question"></i></div>
                <h3><i class="fa-regular fa-circle-question"></i> Shipping FAQ</h3>
                
                <div style="margin-top:10px;">
                    <div style="padding:10px 0; border-bottom:1px solid #f1f5f9;">
                        <div style="font-weight:600; color:#1f2937;">How can I track my order?</div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">Go to "My Orders" in your dashboard, find your order, and click "Track Order".</div>
                    </div>
                    <div style="padding:10px 0; border-bottom:1px solid #f1f5f9;">
                        <div style="font-weight:600; color:#1f2937;">Do you offer same-day delivery?</div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">Yes, same-day delivery is available in Nairobi for orders placed before 12 PM. A fee of KSH 800 applies.</div>
                    </div>
                    <div style="padding:10px 0; border-bottom:1px solid #f1f5f9;">
                        <div style="font-weight:600; color:#1f2937;">What if I'm not home when my order arrives?</div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">The courier will attempt delivery twice. If unsuccessful, they'll leave a notification for pick-up at their nearest office.</div>
                    </div>
                    <div style="padding:10px 0; border-bottom:1px solid #f1f5f9;">
                        <div style="font-weight:600; color:#1f2937;">Can I change my shipping address after placing an order?</div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">If the order hasn't been processed, contact the seller immediately to request an address change.</div>
                    </div>
                    <div style="padding:10px 0;">
                        <div style="font-weight:600; color:#1f2937;">What happens if my order is delayed?</div>
                        <div style="color:#6b7280; font-size:0.9rem; margin-top:4px;">We'll notify you of any delays. You can also track your order for real-time updates.</div>
                    </div>
                </div>
            </div>
            
            <!-- Call to Action -->
            <div class="cta-section">
                <h3><i class="fa-regular fa-circle-question"></i> Still Have Questions?</h3>
                <p>Our support team is ready to help you with any shipping-related questions.</p>
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
document.querySelectorAll('.shipping-sidebar-nav a').forEach(function(link) {
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