<?php
$page_title = 'Privacy Policy';
require_once 'includes/header.php';
?>

<style>
    /* ============================================
       PRIVACY POLICY PAGE - MODERN DESIGN
    ============================================ */
    
    .privacy-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 60px 0;
        border-radius: 0 0 40px 40px;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }
    
    .privacy-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(37,99,235,0.1) 0%, transparent 70%);
        border-radius: 50%;
    }
    
    .privacy-hero h1 {
        color: #fff;
        font-size: 2.8rem;
        font-weight: 800;
        position: relative;
        z-index: 1;
    }
    
    .privacy-hero h1 i {
        color: #f59e0b;
    }
    
    .privacy-hero p {
        color: rgba(255,255,255,0.7);
        font-size: 1.1rem;
        position: relative;
        z-index: 1;
        max-width: 600px;
    }
    
    .privacy-hero .last-updated {
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
    
    .privacy-wrapper {
        display: flex;
        gap: 30px;
    }
    
    .privacy-sidebar {
        width: 280px;
        flex-shrink: 0;
        position: sticky;
        top: 100px;
        align-self: flex-start;
    }
    
    .privacy-content {
        flex: 1;
    }
    
    .privacy-section {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 25px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .privacy-section:hover {
        border-color: #2563eb;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    }
    
    .privacy-section .section-number {
        display: inline-block;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        padding: 2px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    
    .privacy-section h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 12px;
    }
    
    .privacy-section h3 i {
        color: #f59e0b;
        margin-right: 8px;
    }
    
    .privacy-section p {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
        margin-bottom: 10px;
    }
    
    .privacy-section ul, .privacy-section ol {
        padding-left: 20px;
        margin: 10px 0;
    }
    
    .privacy-section li {
        color: #4b5563;
        font-size: 0.95rem;
        line-height: 1.8;
        margin-bottom: 6px;
    }
    
    .privacy-section li strong {
        color: #1f2937;
    }
    
    .privacy-section .highlight-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px 20px;
        border-left: 4px solid #2563eb;
        margin: 15px 0;
    }
    
    .privacy-section .highlight-box.warning {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    
    .privacy-section .highlight-box.success {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    
    .privacy-section .highlight-box.danger {
        border-left-color: #ef4444;
        background: #fef2f2;
    }
    
    .privacy-section .highlight-box h5 {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 4px;
    }
    
    .privacy-section .highlight-box p {
        margin: 0;
        font-size: 0.9rem;
    }
    
    /* Sidebar Navigation */
    .privacy-sidebar-nav {
        background: white;
        border-radius: 16px;
        padding: 20px;
        border: 1px solid #e5e7eb;
    }
    
    .privacy-sidebar-nav .nav-title {
        font-size: 0.8rem;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .privacy-sidebar-nav a {
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
    
    .privacy-sidebar-nav a:hover {
        background: #f3f4f6;
        color: #2563eb;
    }
    
    .privacy-sidebar-nav a i {
        width: 20px;
        color: #6b7280;
        font-size: 0.85rem;
    }
    
    .privacy-sidebar-nav a:hover i {
        color: #2563eb;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .privacy-wrapper {
            flex-direction: column;
        }
        .privacy-sidebar {
            width: 100%;
            position: static;
        }
        .privacy-hero h1 {
            font-size: 2rem;
        }
        .privacy-sidebar-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            padding: 12px;
        }
        .privacy-sidebar-nav .nav-title {
            width: 100%;
            margin-bottom: 8px;
        }
        .privacy-sidebar-nav a {
            padding: 6px 12px;
            font-size: 0.8rem;
        }
    }
    
    @media (max-width: 768px) {
        .privacy-hero {
            padding: 40px 0;
        }
        .privacy-hero h1 {
            font-size: 1.8rem;
        }
        .privacy-section {
            padding: 20px;
        }
        .privacy-section h3 {
            font-size: 1.1rem;
        }
    }
    
    @media (max-width: 480px) {
        .privacy-hero h1 {
            font-size: 1.5rem;
        }
        .privacy-section {
            padding: 15px;
        }
    }
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="privacy-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-solid fa-lock"></i> Privacy Policy
        </div>
        <h1><i class="fa-solid fa-lock"></i> Privacy Policy</h1>
        <p>Your privacy matters to us. Learn how we collect, use, and protect your personal information.</p>
        <div class="last-updated">
            <i class="fa-regular fa-calendar"></i> Last Updated: <?= date('F d, Y') ?>
        </div>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    <div class="privacy-wrapper">
        
        <!-- Sidebar -->
        <div class="privacy-sidebar">
            <div class="privacy-sidebar-nav">
                <div class="nav-title"><i class="fa-regular fa-list"></i> Quick Navigation</div>
                <a href="#introduction"><i class="fa-regular fa-circle-info"></i> Introduction</a>
                <a href="#information"><i class="fa-solid fa-database"></i> Information We Collect</a>
                <a href="#usage"><i class="fa-solid fa-chart-line"></i> How We Use Your Data</a>
                <a href="#cookies"><i class="fa-solid fa-cookie"></i> Cookies</a>
                <a href="#sharing"><i class="fa-solid fa-share-nodes"></i> Data Sharing</a>
                <a href="#security"><i class="fa-solid fa-shield-hart"></i> Security</a>
                <a href="#rights"><i class="fa-regular fa-circle-check"></i> Your Rights</a>
                <a href="#contact"><i class="fa-regular fa-headset"></i> Contact Us</a>
            </div>
        </div>
        
        <!-- Content -->
        <div class="privacy-content">
            
            <!-- ==========================================
                 INTRODUCTION
            ========================================== -->
            <div class="privacy-section" id="introduction">
                <span class="section-number">Section 1</span>
                <h3><i class="fa-regular fa-circle-info"></i> Introduction</h3>
                <p>
                    At <strong><?= SITE_NAME ?></strong>, we take your privacy seriously. This Privacy Policy explains how we collect, 
                    use, disclose, and safeguard your information when you use our platform. Please read this privacy policy carefully. 
                    If you do not agree with the terms of this privacy policy, please do not access the site.
                </p>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check" style="color:#10b981;"></i> Our Commitment</h5>
                    <p>We are committed to protecting your personal information and being transparent about our data practices.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 INFORMATION WE COLLECT
            ========================================== -->
            <div class="privacy-section" id="information">
                <span class="section-number">Section 2</span>
                <h3><i class="fa-solid fa-database"></i> Information We Collect</h3>
                <p>We collect information that you provide directly to us, such as when you:</p>
                
                <ul>
                    <li><strong>Create an account:</strong> Name, email address, phone number, and password</li>
                    <li><strong>Make a purchase:</strong> Shipping address, payment information, and order history</li>
                    <li><strong>Contact support:</strong> Your messages, inquiries, and feedback</li>
                    <li><strong>Use our platform:</strong> Browsing activity, search history, and preferences</li>
                </ul>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-regular fa-clock" style="color:#f59e0b;"></i> Payment Information</h5>
                    <p>We do not store your full payment information on our servers. All payments are processed through secure, PCI-compliant payment gateways.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 HOW WE USE YOUR DATA
            ========================================== -->
            <div class="privacy-section" id="usage">
                <span class="section-number">Section 3</span>
                <h3><i class="fa-solid fa-chart-line"></i> How We Use Your Data</h3>
                <p>We use your information to:</p>
                
                <ul>
                    <li><strong>Provide services:</strong> Process orders, facilitate payments, and deliver products</li>
                    <li><strong>Communicate:</strong> Send order confirmations, shipping updates, and promotional offers</li>
                    <li><strong>Personalize:</strong> Tailor your shopping experience and recommend products</li>
                    <li><strong>Improve:</strong> Analyze usage patterns to enhance our platform</li>
                    <li><strong>Comply:</strong> Fulfill legal obligations and prevent fraud</li>
                </ul>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check" style="color:#10b981;"></i> Opt-Out</h5>
                    <p>You can opt out of promotional emails at any time by clicking the "Unsubscribe" link in our emails.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 COOKIES
            ========================================== -->
            <div class="privacy-section" id="cookies">
                <span class="section-number">Section 4</span>
                <h3><i class="fa-solid fa-cookie"></i> Cookies & Tracking</h3>
                <p>
                    We use cookies and similar tracking technologies to enhance your experience on our platform. 
                    Cookies are small text files stored on your device that help us remember your preferences and improve performance.
                </p>
                
                <ul>
                    <li><strong>Essential Cookies:</strong> Necessary for the platform to function properly</li>
                    <li><strong>Analytics Cookies:</strong> Help us understand how users interact with our site</li>
                    <li><strong>Preference Cookies:</strong> Remember your settings and preferences</li>
                    <li><strong>Marketing Cookies:</strong> Used to deliver relevant ads and promotions</li>
                </ul>
                
                <div class="highlight-box warning">
                    <h5><i class="fa-regular fa-clock" style="color:#f59e0b;"></i> Cookie Control</h5>
                    <p>You can manage or disable cookies through your browser settings. However, some features may not function properly without cookies.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 DATA SHARING
            ========================================== -->
            <div class="privacy-section" id="sharing">
                <span class="section-number">Section 5</span>
                <h3><i class="fa-solid fa-share-nodes"></i> Data Sharing</h3>
                <p>We do not sell or rent your personal information to third parties. However, we may share your data with:</p>
                
                <ul>
                    <li><strong>Sellers:</strong> To fulfill your orders and provide customer service</li>
                    <li><strong>Service Providers:</strong> Payment processors, shipping partners, and IT providers</li>
                    <li><strong>Legal Authorities:</strong> When required by law or to protect our rights</li>
                    <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets</li>
                </ul>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check" style="color:#10b981;"></i> Third-Party Compliance</h5>
                    <p>All third-party providers are required to maintain the security and confidentiality of your personal information.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 SECURITY
            ========================================== -->
            <div class="privacy-section" id="security">
                <span class="section-number">Section 6</span>
                <h3><i class="fa-solid fa-shield-hart"></i> Security Measures</h3>
                <p>We implement appropriate technical and organizational measures to protect your personal information:</p>
                
                <ul>
                    <li><strong>Encryption:</strong> All data transmission is encrypted using SSL/TLS protocols</li>
                    <li><strong>Access Control:</strong> Limited access to personal information on a need-to-know basis</li>
                    <li><strong>Secure Storage:</strong> Personal information is stored on secure servers with regular backups</li>
                    <li><strong>Monitoring:</strong> Continuous monitoring for potential security threats</li>
                </ul>
                
                <div class="highlight-box danger">
                    <h5><i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i> Important</h5>
                    <p>While we strive to protect your personal information, no method of transmission over the Internet is 100% secure.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 YOUR RIGHTS
            ========================================== -->
            <div class="privacy-section" id="rights">
                <span class="section-number">Section 7</span>
                <h3><i class="fa-regular fa-circle-check"></i> Your Rights</h3>
                <p>You have the following rights regarding your personal information:</p>
                
                <ul>
                    <li><strong>Access:</strong> Request a copy of your personal information</li>
                    <li><strong>Correction:</strong> Update or correct inaccurate information</li>
                    <li><strong>Deletion:</strong> Request deletion of your personal information</li>
                    <li><strong>Objection:</strong> Object to the processing of your data</li>
                    <li><strong>Portability:</strong> Request transfer of your data to another provider</li>
                </ul>
                
                <div class="highlight-box success">
                    <h5><i class="fa-regular fa-circle-check" style="color:#10b981;"></i> How to Exercise Your Rights</h5>
                    <p>To exercise any of these rights, please contact us at <strong>support@multivendorhub.com</strong>.</p>
                </div>
            </div>
            
            <!-- ==========================================
                 CONTACT US
            ========================================== -->
            <div class="privacy-section" id="contact">
                <span class="section-number">Section 8</span>
                <h3><i class="fa-regular fa-headset"></i> Contact Us</h3>
                <p>
                    If you have any questions, concerns, or requests regarding this Privacy Policy, please contact us:
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
            <div class="privacy-section" style="border:2px solid #10b981; background:#f0fdf4;">
                <div style="text-align:center; padding:10px;">
                    <i class="fa-regular fa-circle-check" style="font-size:2.5rem; color:#10b981; display:block; margin-bottom:10px;"></i>
                    <h3 style="margin-bottom:10px;">Privacy Commitment</h3>
                    <p style="color:#4b5563; max-width:600px; margin:0 auto;">
                        At <?= SITE_NAME ?>, we are committed to protecting your privacy and ensuring your personal information is handled responsibly.
                    </p>
                    <div style="margin-top:20px; display:flex; gap:10px; justify-content:center; flex-wrap:wrap;">
                        <a href="register.php" class="btn" style="background:linear-gradient(135deg, #10b981, #059669); color:white; padding:10px 30px; border-radius:10px; text-decoration:none; font-weight:600;">
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
document.querySelectorAll('.privacy-sidebar-nav a').forEach(function(link) {
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