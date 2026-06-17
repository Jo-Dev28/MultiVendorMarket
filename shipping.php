<?php
$page_title = 'Shipping Information';
require_once 'includes/header.php';
?>
<style>
.shipping-hero{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:60px 0;border-radius:0 0 30px 30px;margin-bottom:40px;text-align:center}
.shipping-hero h1{color:#fff;font-size:2.5rem;font-weight:800}
.shipping-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 4px 20px rgba(0,0,0,0.08);height:100%;transition:all .3s}
.shipping-card:hover{transform:translateY(-5px)}
.shipping-card .icon{font-size:2.5rem;color:#2563eb;margin-bottom:12px}
@media(max-width:768px){.shipping-hero h1{font-size:1.8rem}}
</style>
<div class="shipping-hero"><div class="container"><h1>Shipping Information</h1><p>Learn about our shipping and delivery policies.</p></div></div>
<div class="container mb-5">
    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="shipping-card"><div class="icon"><i class="fa-solid fa-truck-fast"></i></div><h5>Free Shipping</h5><p>On all orders over KSH 5,000</p></div></div>
        <div class="col-md-4"><div class="shipping-card"><div class="icon"><i class="fa-regular fa-clock"></i></div><h5>Delivery Time</h5><p>2-5 business days</p></div></div>
        <div class="col-md-4"><div class="shipping-card"><div class="icon"><i class="fa-solid fa-location-dot"></i></div><h5>Coverage</h5><p>Nationwide delivery in Kenya</p></div></div>
    </div>
    <div class="bg-white p-4 rounded-4 shadow-sm">
        <h4>Delivery Information</h4>
        <ul class="list-unstyled">
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Orders are processed within 24 hours</li>
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Tracking number provided after dispatch</li>
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Signature required for delivery</li>
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> Contact seller for specific delivery questions</li>
        </ul>
        <div class="alert alert-info mt-3">For any shipping inquiries, contact us at <?= ADMIN_EMAIL ?></div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>