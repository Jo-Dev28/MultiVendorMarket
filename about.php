<?php
$page_title = 'About Us';
require_once 'includes/header.php';
?>
<style>
.about-hero{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:60px 0;border-radius:0 0 30px 30px;margin-bottom:40px;text-align:center}
.about-hero h1{color:#fff;font-size:2.5rem;font-weight:800}
.about-hero p{color:rgba(255,255,255,0.7);font-size:1.1rem;max-width:600px;margin:0 auto}
.mission-card{background:#fff;border-radius:16px;padding:30px;box-shadow:0 4px 20px rgba(0,0,0,0.08);text-align:center;height:100%;transition:all .3s}
.mission-card:hover{transform:translateY(-5px);box-shadow:0 8px 30px rgba(0,0,0,0.12)}
.mission-card .icon{width:64px;height:64px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:1.8rem;color:#2563eb}
.team-card{background:#fff;border-radius:16px;padding:24px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
.team-card .avatar{width:100px;height:100px;border-radius:50%;margin:0 auto 12px;background:linear-gradient(135deg,#2563eb,#1d4ed8);display:flex;align-items:center;justify-content:center;font-size:2.5rem;color:#fff}
.value-card{background:#fff;border-radius:16px;padding:24px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.08);height:100%}
.value-card .icon{font-size:2.5rem;color:#2563eb;margin-bottom:12px}
@media(max-width:768px){.about-hero h1{font-size:1.8rem}}
</style>
<div class="about-hero">
    <div class="container">
        <h1>About <?= SITE_NAME ?></h1>
        <p>Your trusted multi-vendor marketplace connecting buyers with sellers across Kenya.</p>
    </div>
</div>
<div class="container mb-5">
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <h2>Who We Are</h2>
            <p><?= SITE_NAME ?> is a leading multi-vendor marketplace in Kenya, connecting thousands of buyers with trusted sellers across various categories including electronics, fashion, home & living, and beauty products.</p>
            <p>Founded with the mission to make online shopping accessible, secure, and enjoyable for everyone in Kenya.</p>
        </div>
        <div class="col-lg-6">
            <h2>Our Vision</h2>
            <p>To become the most trusted and innovative marketplace in Africa, empowering local businesses and providing customers with the best shopping experience.</p>
        </div>
    </div>
    <h3 class="text-center mb-4">Our Mission</h3>
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="mission-card"><div class="icon"><i class="fa-solid fa-store"></i></div><h5>Empower Sellers</h5><p>Provide tools for sellers to grow their business online.</p></div>
        </div>
        <div class="col-md-4">
            <div class="mission-card"><div class="icon"><i class="fa-solid fa-cart-shopping"></i></div><h5>Delight Customers</h5><p>Offer quality products at competitive prices.</p></div>
        </div>
        <div class="col-md-4">
            <div class="mission-card"><div class="icon"><i class="fa-solid fa-shield-heart"></i></div><h5>Build Trust</h5><p>Create a secure and transparent marketplace.</p></div>
        </div>
    </div>
    <h3 class="text-center mb-4">Why Choose Us</h3>
    <div class="row g-4">
        <div class="col-md-3"><div class="value-card"><div class="icon"><i class="fa-solid fa-shield"></i></div><h5>Secure Payments</h5><p>Multiple secure payment options</p></div></div>
        <div class="col-md-3"><div class="value-card"><div class="icon"><i class="fa-solid fa-truck"></i></div><h5>Fast Delivery</h5><p>Delivery in 2-5 business days</p></div></div>
        <div class="col-md-3"><div class="value-card"><div class="icon"><i class="fa-solid fa-headset"></i></div><h5>24/7 Support</h5><p>Dedicated customer service</p></div></div>
        <div class="col-md-3"><div class="value-card"><div class="icon"><i class="fa-solid fa-robot"></i></div><h5>AI Assistant</h5><p>Smart product recommendations</p></div></div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>