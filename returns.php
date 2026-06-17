<?php
$page_title = 'Returns & Refunds';
require_once 'includes/header.php';
?>
<style>
.returns-hero{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:60px 0;border-radius:0 0 30px 30px;margin-bottom:40px;text-align:center}
.returns-hero h1{color:#fff;font-size:2.5rem;font-weight:800}
.returns-steps{display:flex;gap:20px;flex-wrap:wrap;justify-content:center}
.return-step{background:#fff;border-radius:16px;padding:24px;text-align:center;flex:1;min-width:150px;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
.return-step .num{width:40px;height:40px;background:#2563eb;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;font-weight:700}
.return-policy{background:#fff;border-radius:16px;padding:30px;box-shadow:0 4px 20px rgba(0,0,0,0.08)}
@media(max-width:768px){.returns-hero h1{font-size:1.8rem}.return-step{min-width:100%}}
</style>
<div class="returns-hero"><div class="container"><h1>Returns & Refunds</h1></div></div>
<div class="container mb-5">
    <h4 class="text-center mb-4">How to Return an Item</h4>
    <div class="returns-steps mb-5">
        <div class="return-step"><div class="num">1</div><h5>Contact Seller</h5><p>Reach out to the seller</p></div>
        <div class="return-step"><div class="num">2</div><h5>Package Item</h5><p>Keep original packaging</p></div>
        <div class="return-step"><div class="num">3</div><h5>Ship Back</h5><p>Return to seller's address</p></div>
        <div class="return-step"><div class="num">4</div><h5>Get Refund</h5><p>Refund processed</p></div>
    </div>
    <div class="return-policy">
        <h4>Return Policy</h4>
        <ul class="list-unstyled">
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> <strong>Return Window:</strong> 7 days from delivery</li>
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> <strong>Condition:</strong> Items must be unused with original packaging</li>
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> <strong>Return Shipping:</strong> Buyer pays return shipping</li>
            <li class="py-2"><i class="fa-solid fa-check-circle text-success me-2"></i> <strong>Refund:</strong> Processed within 3-5 business days</li>
        </ul>
        <div class="alert alert-warning mt-3">Non-returnable items: Digital products, perishable items, and personalized products</div>
    </div>
</div>
<?php require_once 'includes/footer.php'; ?>