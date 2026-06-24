<?php
$page_title = 'About Us';
require_once 'includes/header.php';

// Get statistics
$total_users = $mysqli->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
$total_sellers = $mysqli->query("SELECT COUNT(*) as count FROM sellers WHERE status = 'verified'")->fetch_assoc()['count'] ?? 0;
$total_products = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE status = 'approved'")->fetch_assoc()['count'] ?? 0;
$total_orders = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE status != 'cancelled'")->fetch_assoc()['count'] ?? 0;
$total_revenue = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'")->fetch_assoc()['total'] ?? 0;

// Get recent testimonials
$testimonials_sql = "SELECT r.*, u.name as customer_name, u.avatar 
                     FROM reviews r
                     JOIN users u ON u.id = r.user_id
                     WHERE r.status = 'approved' AND r.rating >= 4
                     ORDER BY r.created_at DESC
                     LIMIT 4";
$testimonials = $mysqli->query($testimonials_sql);
?>

<style>
    /* ============================================
       ABOUT PAGE - ENHANCED DESIGN
    ============================================ */
    
    .about-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 80px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }
    
    .about-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .about-hero h1 {
        color: #fff;
        font-size: 3rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }
    
    .about-hero h1 i {
        color: #f59e0b;
    }
    
    .about-hero p {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
        max-width: 600px;
    }
    
    .about-section {
        background: white;
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border: 1px solid #e5e7eb;
    }
    
    .about-section .section-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: #2563eb;
        margin-bottom: 15px;
    }
    
    .about-section h2 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 15px;
    }
    
    .about-section h2 i {
        color: #f59e0b;
    }
    
    .about-section p {
        color: #4b5563;
        font-size: 1rem;
        line-height: 1.8;
    }
    
    /* Stats Section */
    .stats-grid-about {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin: 30px 0;
    }
    
    .stat-box {
        background: #f8fafc;
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .stat-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        border-color: #2563eb;
    }
    
    .stat-box .number {
        font-size: 2.2rem;
        font-weight: 800;
        color: #2563eb;
        margin-bottom: 5px;
    }
    
    .stat-box .label {
        font-size: 0.85rem;
        color: #6b7280;
    }
    
    .stat-box .icon {
        font-size: 1.5rem;
        color: #f59e0b;
        margin-bottom: 10px;
    }
    
    /* Values Section */
    .values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    
    .value-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .value-card:hover {
        border-color: #2563eb;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.06);
    }
    
    .value-card .value-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.5rem;
        color: #2563eb;
    }
    
    .value-card h4 {
        font-size: 1rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .value-card p {
        font-size: 0.85rem;
        color: #6b7280;
        line-height: 1.6;
    }
    
    /* Team Section - Enhanced */
    .team-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 25px;
        margin-top: 20px;
    }
    
    .team-member {
        background: #f8fafc;
        border-radius: 16px;
        padding: 25px 20px;
        text-align: center;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .team-member:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
        border-color: #2563eb;
    }
    
    .team-member .member-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #f59e0b;
        color: white;
        font-size: 0.55rem;
        font-weight: 700;
        padding: 2px 12px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .team-member .member-badge.ceo {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }
    
    .team-member .member-badge.lead {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
    }
    
    .team-member .member-badge.support {
        background: linear-gradient(135deg, #10b981, #059669);
    }
    
    .team-member .photo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 15px;
        overflow: hidden;
        border: 4px solid #e5e7eb;
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #2563eb;
        font-weight: 700;
    }
    
    .team-member:hover .photo {
        border-color: #2563eb;
        transform: scale(1.05);
    }
    
    .team-member .photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .team-member .photo .initials {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2563eb;
    }
    
    .team-member h5 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 2px;
    }
    
    .team-member .role {
        font-size: 0.8rem;
        color: #6b7280;
        margin-bottom: 10px;
    }
    
    .team-member .bio {
        font-size: 0.8rem;
        color: #4b5563;
        line-height: 1.5;
        display: none;
    }
    
    .team-member .bio.show {
        display: block;
    }
    
    .team-member .btn-more {
        background: none;
        border: none;
        color: #2563eb;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 600;
        padding: 5px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        margin-top: 8px;
    }
    
    .team-member .btn-more:hover {
        background: #dbeafe;
        transform: translateX(3px);
    }
    
    .team-member .portfolio-link {
        display: inline-block;
        margin-top: 8px;
        padding: 6px 16px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        border-radius: 8px;
        text-decoration: none;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .team-member .portfolio-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(37,99,235,0.3);
        color: white;
    }
    
    .team-member .social-links {
        display: flex;
        gap: 8px;
        justify-content: center;
        margin-top: 10px;
    }
    
    .team-member .social-links a {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        transition: all 0.3s ease;
        text-decoration: none;
        font-size: 0.85rem;
    }
    
    .team-member .social-links a:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-2px);
    }
    
    /* Testimonials */
    .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-top: 20px;
    }
    
    .testimonial-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 25px;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .testimonial-card:hover {
        border-color: #2563eb;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    
    .testimonial-card .stars {
        color: #f59e0b;
        font-size: 0.9rem;
        margin-bottom: 8px;
    }
    
    .testimonial-card .quote {
        font-size: 0.95rem;
        color: #1f2937;
        line-height: 1.7;
        font-style: italic;
    }
    
    .testimonial-card .customer {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px solid #e5e7eb;
    }
    
    .testimonial-card .customer .c-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #2563eb;
        font-size: 1rem;
    }
    
    .testimonial-card .customer .c-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.9rem;
    }
    
    .testimonial-card .customer .c-date {
        font-size: 0.7rem;
        color: #9ca3af;
    }
    
    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 30px;
        margin-top: 20px;
    }
    
    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e5e7eb;
    }
    
    .timeline-item {
        position: relative;
        margin-bottom: 25px;
        padding-left: 25px;
    }
    
    .timeline-item:last-child {
        margin-bottom: 0;
    }
    
    .timeline-item .dot {
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
    
    .timeline-item .year {
        font-weight: 700;
        color: #2563eb;
        font-size: 1.1rem;
    }
    
    .timeline-item .event {
        color: #4b5563;
        font-size: 0.95rem;
        margin-top: 2px;
    }
    
    /* CTA Section */
    .cta-section {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        padding: 50px;
        text-align: center;
        color: white;
        margin-top: 30px;
    }
    
    .cta-section h2 {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 10px;
        color: white;
    }
    
    .cta-section p {
        color: rgba(255,255,255,0.7);
        margin-bottom: 20px;
        font-size: 1.05rem;
    }
    
    .cta-section .btn-cta {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: white;
        padding: 14px 40px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        display: inline-block;
    }
    
    .cta-section .btn-cta:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
        color: white;
    }
    
    .cta-section .btn-cta-secondary {
        background: rgba(255,255,255,0.1);
        color: white;
        padding: 14px 40px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 1.05rem;
        transition: all 0.3s ease;
        display: inline-block;
        margin-left: 10px;
        border: 1px solid rgba(255,255,255,0.2);
    }
    
    .cta-section .btn-cta-secondary:hover {
        background: rgba(255,255,255,0.2);
        color: white;
    }
    
    /* Team Member Modal */
    .team-modal .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }
    
    .team-modal .modal-header {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 20px 25px;
        border: none;
    }
    
    .team-modal .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    .team-modal .modal-body {
        padding: 30px;
    }
    
    .team-modal .modal-photo {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 15px;
        overflow: hidden;
        border: 4px solid #e5e7eb;
        background: linear-gradient(135deg, #dbeafe, #bfdbfe);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: #2563eb;
    }
    
    .team-modal .modal-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .team-modal .modal-photo .initials {
        font-size: 2.5rem;
        font-weight: 700;
        color: #2563eb;
    }
    
    .team-modal .modal-name {
        text-align: center;
        font-size: 1.4rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 2px;
    }
    
    .team-modal .modal-role {
        text-align: center;
        font-size: 0.9rem;
        color: #6b7280;
        margin-bottom: 15px;
    }
    
    .team-modal .modal-bio {
        font-size: 0.95rem;
        color: #4b5563;
        line-height: 1.7;
    }
    
    .team-modal .modal-skills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    
    .team-modal .modal-skills .skill-tag {
        background: #dbeafe;
        color: #1d4ed8;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .stats-grid-about {
            grid-template-columns: repeat(2, 1fr);
        }
        .values-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .team-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .testimonials-grid {
            grid-template-columns: 1fr;
        }
        .about-hero h1 {
            font-size: 2.2rem;
        }
    }
    
    @media (max-width: 768px) {
        .about-hero {
            padding: 50px 0;
        }
        .about-hero h1 {
            font-size: 1.8rem;
        }
        .about-section {
            padding: 25px;
        }
        .stats-grid-about {
            grid-template-columns: 1fr 1fr;
        }
        .values-grid {
            grid-template-columns: 1fr;
        }
        .team-grid {
            grid-template-columns: 1fr 1fr;
        }
        .cta-section {
            padding: 30px;
        }
        .cta-section h2 {
            font-size: 1.5rem;
        }
        .cta-section .btn-cta-secondary {
            margin-left: 0;
            margin-top: 10px;
        }
        .team-member .photo {
            width: 90px;
            height: 90px;
        }
    }
    
    @media (max-width: 480px) {
        .stats-grid-about {
            grid-template-columns: 1fr;
        }
        .team-grid {
            grid-template-columns: 1fr;
        }
        .about-hero h1 {
            font-size: 1.5rem;
        }
        .cta-section .btn-cta, .cta-section .btn-cta-secondary {
            width: 100%;
            text-align: center;
        }
        .team-member .photo {
            width: 80px;
            height: 80px;
        }
    }
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="about-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-regular fa-circle-info"></i> About Us
        </div>
        <h1><i class="fa-solid fa-store"></i> <?= SITE_NAME ?></h1>
        <p>We're on a mission to connect buyers with trusted sellers across Kenya, making online shopping safe, convenient, and enjoyable for everyone.</p>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    
    <!-- Stats Section -->
    <div class="stats-grid-about">
        <div class="stat-box">
            <div class="icon"><i class="fa-solid fa-users"></i></div>
            <div class="number"><?= number_format($total_users) ?></div>
            <div class="label">Happy Customers</div>
        </div>
        <div class="stat-box">
            <div class="icon"><i class="fa-solid fa-store"></i></div>
            <div class="number"><?= number_format($total_sellers) ?></div>
            <div class="label">Trusted Sellers</div>
        </div>
        <div class="stat-box">
            <div class="icon"><i class="fa-solid fa-box"></i></div>
            <div class="number"><?= number_format($total_products) ?></div>
            <div class="label">Products Available</div>
        </div>
        <div class="stat-box">
            <div class="icon"><i class="fa-solid fa-truck"></i></div>
            <div class="number"><?= number_format($total_orders) ?></div>
            <div class="label">Orders Delivered</div>
        </div>
    </div>
    
    <!-- Our Story -->
    <div class="about-section">
        <div class="section-icon"><i class="fa-regular fa-book"></i></div>
        <h2>Our Story</h2>
        <p>
            <?= SITE_NAME ?> was founded with a simple yet powerful vision: to create a trusted online marketplace where Kenyans can buy and sell with confidence. 
            We saw a gap in the market for a platform that truly prioritizes customer trust, seller reliability, and seamless shopping experiences.
        </p>
        <p>
            Starting from humble beginnings, we've grown into a vibrant community of <?= number_format($total_sellers) ?> sellers and <?= number_format($total_users) ?> customers, 
            with <?= number_format($total_products) ?> products available across <?= number_format($total_orders) ?> successful orders. Our journey is powered by the 
            incredible people who choose to shop and sell with us every day.
        </p>
        <div class="timeline">
            <div class="timeline-item">
                <div class="dot"></div>
                <div class="year">2023</div>
                <div class="event"><?= SITE_NAME ?> was founded with a vision to revolutionize online shopping in Kenya.</div>
            </div>
            <div class="timeline-item">
                <div class="dot"></div>
                <div class="year">2024</div>
                <div class="event">Reached <?= number_format($total_users) ?> customers and <?= number_format($total_sellers) ?> sellers, launched the AI Shopping Assistant.</div>
            </div>
            <div class="timeline-item">
                <div class="dot"></div>
                <div class="year">2025</div>
                <div class="event">Expanded product categories, introduced seller subscriptions, and enhanced buyer protection.</div>
            </div>
        </div>
    </div>
    
    <!-- Our Values -->
    <div class="about-section">
        <div class="section-icon"><i class="fa-regular fa-heart"></i></div>
        <h2>Our Values</h2>
        <p>The principles that guide everything we do at <?= SITE_NAME ?>.</p>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon"><i class="fa-solid fa-shield-hart"></i></div>
                <h4>Trust & Safety</h4>
                <p>We prioritize buyer protection, seller verification, and secure transactions. Your safety is our top priority.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fa-solid fa-users"></i></div>
                <h4>Community First</h4>
                <p>We believe in building a strong community of buyers and sellers who support each other and grow together.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fa-solid fa-star"></i></div>
                <h4>Quality Assurance</h4>
                <p>We carefully curate sellers and products to ensure you get nothing but the best quality every time.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fa-solid fa-headset"></i></div>
                <h4>Customer Support</h4>
                <p>Our dedicated support team is always ready to help you with any questions or concerns you may have.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fa-solid fa-robot"></i></div>
                <h4>Innovation</h4>
                <p>We continuously improve our platform with AI-powered features and modern technology to enhance your experience.</p>
            </div>
            <div class="value-card">
                <div class="value-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
                <h4>Social Impact</h4>
                <p>We empower local businesses and entrepreneurs, contributing to the growth of Kenya's digital economy.</p>
            </div>
        </div>
    </div>
    
    <!-- Why Choose Us -->
    <div class="about-section">
        <div class="section-icon"><i class="fa-regular fa-circle-check"></i></div>
        <h2>Why Choose <?= SITE_NAME ?>?</h2>
        
        <div class="row g-4 mt-3">
            <div class="col-md-6">
                <div style="display:flex; align-items:flex-start; gap:15px; padding:12px 0; border-bottom:1px solid #f1f5f9;">
                    <div style="color:#10b981; font-size:1.2rem; width:30px;"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <h6 style="font-weight:600; color:#1f2937;">Verified Sellers</h6>
                        <p style="font-size:0.85rem; color:#6b7280;">All sellers are verified to ensure you get authentic products from trusted sources.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div style="display:flex; align-items:flex-start; gap:15px; padding:12px 0; border-bottom:1px solid #f1f5f9;">
                    <div style="color:#10b981; font-size:1.2rem; width:30px;"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <h6 style="font-weight:600; color:#1f2937;">Secure Payments</h6>
                        <p style="font-size:0.85rem; color:#6b7280;">Multiple secure payment options including M-Pesa, Cards, Bank Transfer, and PayPal.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div style="display:flex; align-items:flex-start; gap:15px; padding:12px 0; border-bottom:1px solid #f1f5f9;">
                    <div style="color:#10b981; font-size:1.2rem; width:30px;"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <h6 style="font-weight:600; color:#1f2937;">Fast Delivery</h6>
                        <p style="font-size:0.85rem; color:#6b7280;">Get your orders delivered quickly and reliably across Kenya with our trusted courier partners.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div style="display:flex; align-items:flex-start; gap:15px; padding:12px 0; border-bottom:1px solid #f1f5f9;">
                    <div style="color:#10b981; font-size:1.2rem; width:30px;"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <h6 style="font-weight:600; color:#1f2937;">24/7 Support</h6>
                        <p style="font-size:0.85rem; color:#6b7280;">Our support team is always available to help you with any questions or issues.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div style="display:flex; align-items:flex-start; gap:15px; padding:12px 0; border-bottom:1px solid #f1f5f9;">
                    <div style="color:#10b981; font-size:1.2rem; width:30px;"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <h6 style="font-weight:600; color:#1f2937;">Buyer Protection</h6>
                        <p style="font-size:0.85rem; color:#6b7280;">Shop with confidence knowing you're protected by our buyer guarantee policy.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div style="display:flex; align-items:flex-start; gap:15px; padding:12px 0; border-bottom:1px solid #f1f5f9;">
                    <div style="color:#10b981; font-size:1.2rem; width:30px;"><i class="fa-solid fa-circle-check"></i></div>
                    <div>
                        <h6 style="font-weight:600; color:#1f2937;">AI Shopping Assistant</h6>
                        <p style="font-size:0.85rem; color:#6b7280;">Get personalized product recommendations and instant answers from our AI assistant.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- ============================================
         TEAM SECTION - ENHANCED
    ============================================ -->
    <div class="about-section">
        <div class="section-icon"><i class="fa-solid fa-users"></i></div>
        <h2>Meet the Team</h2>
        <p>The passionate people behind <?= SITE_NAME ?>.</p>
        
        <div class="team-grid">
            <!-- Jonathan Bosimwenda - Founder & CEO -->
            <div class="team-member">
                <span class="member-badge ceo"><i class="fa-solid fa-star"></i> Founder</span>
                <div class="photo">
                    <img src="jo.jpeg" alt="Jonathan Bosimwenda" onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\'initials\'>JB</span>'">
                </div>
                <h5>Jonathan Bosimwenda</h5>
                <div class="role">Founder, CEO & Lead Developer</div>
                <button class="btn-more" onclick="toggleBio(this)">Read More <i class="fa-solid fa-chevron-down"></i></button>
                <div class="bio">
                    <p>Full-Stack Developer & UI/UX Architect with a strategic business vision. IT student at JKUAT University (Nairobi). Building robust web applications, admin dashboards, and intelligent systems.</p>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px;">
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">PHP</span>
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">MySQL</span>
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">JavaScript</span>
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">UI/UX</span>
                    </div>
                    <div class="social-links">
                        <a href="https://github.com/JonathanBosimwenda" target="_blank"><i class="fa-brands fa-github"></i></a>
                        <a href="https://linkedin.com/in/jonathan-bosimwenda" target="_blank"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="https://jonathanbosimwenda28.netlify.app" target="_blank"><i class="fa-solid fa-globe"></i></a>
                    </div>
                </div>
                <a href="https://jonathanbosimwenda28.netlify.app" target="_blank" class="portfolio-link">
                    <i class="fa-solid fa-briefcase"></i> View Portfolio
                </a>
                <button class="btn-more" onclick="openTeamModal('Jonathan Bosimwenda', 'Founder, CEO & Lead Developer', 'Full-Stack Developer & UI/UX Architect with a strategic business vision. IT student at JKUAT University (Nairobi). Building robust web applications, admin dashboards, and intelligent systems.', ['PHP', 'MySQL', 'JavaScript', 'UI/UX', 'Laravel', 'React'], 'JB', 'ceo')" style="color:#f59e0b; font-weight:700;">
                    <i class="fa-regular fa-circle-info"></i> More About Me
                </button>
            </div>
            
            <!-- Mary Wanjiru - Head of Operations -->
            <div class="team-member">
                <span class="member-badge lead"><i class="fa-solid fa-chart-line"></i> Lead</span>
                <div class="photo">
                    <img src="assets/images/team/mary.jpg" alt="Mary Wanjiru" onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\'initials\'>MW</span>'">
                </div>
                <h5>Mary Wanjiru</h5>
                <div class="role">Head of Operations</div>
                <button class="btn-more" onclick="toggleBio(this)">Read More <i class="fa-solid fa-chevron-down"></i></button>
                <div class="bio">
                    <p>Operations expert with 5+ years of experience in e-commerce logistics and customer experience management. Ensuring smooth operations and customer satisfaction.</p>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px;">
                        <span class="badge" style="background:#d1fae5; color:#059669;">Operations</span>
                        <span class="badge" style="background:#d1fae5; color:#059669;">Logistics</span>
                        <span class="badge" style="background:#d1fae5; color:#059669;">Customer Experience</span>
                    </div>
                </div>
            </div>
            
            <!-- James Kamau - Lead Developer -->
            <div class="team-member">
                <span class="member-badge lead"><i class="fa-solid fa-code"></i> Lead</span>
                <div class="photo">
                    <img src="assets/images/team/james.jpg" alt="James Kamau" onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\'initials\'>JK</span>'">
                </div>
                <h5>James Kamau</h5>
                <div class="role">Lead Developer</div>
                <button class="btn-more" onclick="toggleBio(this)">Read More <i class="fa-solid fa-chevron-down"></i></button>
                <div class="bio">
                    <p>Senior software engineer specializing in scalable web applications. Expert in PHP, JavaScript, and modern frontend frameworks. Passionate about clean code and performance.</p>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px;">
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">PHP</span>
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">JavaScript</span>
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">React</span>
                        <span class="badge" style="background:#dbeafe; color:#1d4ed8;">Laravel</span>
                    </div>
                </div>
            </div>
            
            <!-- Sarah Ochieng - Customer Support -->
            <div class="team-member">
                <span class="member-badge support"><i class="fa-solid fa-headset"></i> Support</span>
                <div class="photo">
                    <img src="assets/images/team/sarah.jpg" alt="Sarah Ochieng" onerror="this.style.display='none'; this.parentElement.innerHTML='<span class=\'initials\'>SO</span>'">
                </div>
                <h5>Sarah Ochieng</h5>
                <div class="role">Customer Support</div>
                <button class="btn-more" onclick="toggleBio(this)">Read More <i class="fa-solid fa-chevron-down"></i></button>
                <div class="bio">
                    <p>Customer support specialist with a passion for helping people. Ensures every customer gets the assistance they need promptly and professionally.</p>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:8px;">
                        <span class="badge" style="background:#fef3c7; color:#d97706;">Customer Service</span>
                        <span class="badge" style="background:#fef3c7; color:#d97706;">Problem Solving</span>
                        <span class="badge" style="background:#fef3c7; color:#d97706;">Communication</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testimonials -->
    <?php if ($testimonials && $testimonials->num_rows > 0): ?>
    <div class="about-section">
        <div class="section-icon"><i class="fa-regular fa-star"></i></div>
        <h2>What Our Customers Say</h2>
        <p>Real reviews from real people who love shopping on <?= SITE_NAME ?>.</p>
        
        <div class="testimonials-grid">
            <?php while ($testimonial = $testimonials->fetch_assoc()): ?>
                <div class="testimonial-card">
                    <div class="stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fa-solid fa-star <?= $i <= $testimonial['rating'] ? '' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="quote">"<?= htmlspecialchars($testimonial['comment'] ?? 'Great experience shopping here! Highly recommend.') ?>"</div>
                    <div class="customer">
                        <div class="c-avatar"><?= strtoupper(substr($testimonial['customer_name'], 0, 1)) ?></div>
                        <div>
                            <div class="c-name"><?= htmlspecialchars($testimonial['customer_name']) ?></div>
                            <div class="c-date"><?= date('M d, Y', strtotime($testimonial['created_at'])) ?></div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Call to Action -->
    <div class="cta-section">
        <h2>Ready to Join <?= SITE_NAME ?>?</h2>
        <p>Whether you're a buyer looking for great deals or a seller ready to grow your business, we're here for you.</p>
        <div>
            <a href="shop.php" class="btn-cta">
                <i class="fa-solid fa-store"></i> Start Shopping
            </a>
            <!-- <a href="become_seller.php" class="btn-cta-secondary">
                <i class="fa-solid fa-store"></i> Become a Seller
            </a> -->
        </div>
    </div>
    
</div>

<!-- ============================================
     TEAM MEMBER DETAIL MODAL
============================================ -->
<div class="modal fade team-modal" id="teamMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-regular fa-user"></i> Team Member Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="modal-photo" id="modalPhoto">
                    <span class="initials" id="modalInitials">JB</span>
                </div>
                <div class="modal-name" id="modalName">Jonathan Bosimwenda</div>
                <div class="modal-role" id="modalRole">Founder, CEO & Lead Developer</div>
                <div class="modal-bio" id="modalBio">
                    Full-Stack Developer & UI/UX Architect with a strategic business vision. IT student at JKUAT University (Nairobi). Building robust web applications, admin dashboards, and intelligent systems.
                </div>
                <div class="modal-skills" id="modalSkills">
                    <span class="skill-tag">PHP</span>
                    <span class="skill-tag">MySQL</span>
                    <span class="skill-tag">JavaScript</span>
                    <span class="skill-tag">UI/UX</span>
                </div>
                <div style="margin-top: 20px;" id="modalPortfolioLink">
                    <a href="https://jonathanbosimwenda28.netlify.app" target="_blank" class="portfolio-link" style="display:inline-block; padding:10px 25px; background:linear-gradient(135deg, #2563eb, #1d4ed8); color:white; border-radius:10px; text-decoration:none; font-weight:600; transition:all 0.3s;">
                        <i class="fa-solid fa-briefcase"></i> View Portfolio
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle bio for team members
function toggleBio(button) {
    const bio = button.parentElement.querySelector('.bio');
    const icon = button.querySelector('i');
    
    if (bio) {
        bio.classList.toggle('show');
        if (bio.classList.contains('show')) {
            button.innerHTML = 'Show Less <i class="fa-solid fa-chevron-up"></i>';
        } else {
            button.innerHTML = 'Read More <i class="fa-solid fa-chevron-down"></i>';
        }
    }
}

// Open team member modal
function openTeamModal(name, role, bio, skills, initials, badgeType) {
    document.getElementById('modalName').textContent = name;
    document.getElementById('modalRole').textContent = role;
    document.getElementById('modalBio').textContent = bio;
    document.getElementById('modalInitials').textContent = initials || name.split(' ').map(n => n[0]).join('');
    
    // Set skills
    const skillsContainer = document.getElementById('modalSkills');
    skillsContainer.innerHTML = '';
    if (skills && skills.length > 0) {
        skills.forEach(skill => {
            const tag = document.createElement('span');
            tag.className = 'skill-tag';
            tag.textContent = skill;
            skillsContainer.appendChild(tag);
        });
    }
    
    // Show portfolio link only for Jonathan
    const portfolioLink = document.getElementById('modalPortfolioLink');
    if (name === 'Jonathan Bosimwenda') {
        portfolioLink.style.display = 'block';
    } else {
        portfolioLink.style.display = 'none';
    }
    
    // Set photo color based on badge type
    const photo = document.getElementById('modalPhoto');
    if (badgeType === 'ceo') {
        photo.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
        photo.style.color = 'white';
    } else if (badgeType === 'lead') {
        photo.style.background = 'linear-gradient(135deg, #2563eb, #1d4ed8)';
        photo.style.color = 'white';
    } else if (badgeType === 'support') {
        photo.style.background = 'linear-gradient(135deg, #10b981, #059669)';
        photo.style.color = 'white';
    } else {
        photo.style.background = 'linear-gradient(135deg, #dbeafe, #bfdbfe)';
        photo.style.color = '#2563eb';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('teamMemberModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>