<?php
$page_title = 'FAQ';
require_once 'includes/header.php';

$faqs = [
    ['q'=>'How do I create an account?','a'=>'Click "Register" in the top right corner, fill in your details, and verify your email.'],
    ['q'=>'How do I place an order?','a'=>'Browse products, add to cart, proceed to checkout, enter shipping details, and complete payment.'],
    ['q'=>'What payment methods are accepted?','a'=>'M-Pesa, Credit/Debit Cards, Bank Transfer, and PayPal.'],
    ['q'=>'How long does shipping take?','a'=>'2-5 business days within Kenya. Free shipping on orders over KSH 5,000.'],
    ['q'=>'Can I return a product?','a'=>'Yes, within 7 days of delivery. Items must be unused with original packaging.'],
    ['q'=>'How do I become a seller?','a'=>'Register, go to dashboard, click "Become a Seller", submit business details, and wait for verification.'],
    ['q'=>'How do I track my order?','a'=>'Go to "My Orders", click "Track Order" on the order.'],
    ['q'=>'Is my payment secure?','a'=>'Yes, all payments are encrypted and secure.'],
];
?>
<style>
.faq-hero{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:60px 0;border-radius:0 0 30px 30px;margin-bottom:40px;text-align:center}
.faq-hero h1{color:#fff;font-size:2.5rem;font-weight:800}
.faq-hero p{color:rgba(255,255,255,0.7);font-size:1.1rem}
.faq-item{background:#fff;border-radius:12px;padding:20px 24px;margin-bottom:12px;box-shadow:0 2px 10px rgba(0,0,0,0.05);cursor:pointer;transition:all .3s}
.faq-item:hover{box-shadow:0 4px 20px rgba(0,0,0,0.08)}
.faq-item .q{font-weight:600;color:#1f2937;display:flex;justify-content:space-between;align-items:center}
.faq-item .q i{transition:transform .3s}
.faq-item .a{display:none;padding-top:12px;color:#6b7280;border-top:1px solid #e5e7eb;margin-top:12px}
.faq-item.active .a{display:block}
.faq-item.active .q i{transform:rotate(180deg)}
@media(max-width:768px){.faq-hero h1{font-size:1.8rem}}
</style>
<div class="faq-hero"><div class="container"><h1>Frequently Asked Questions</h1><p>Find answers to common questions about our platform.</p></div></div>
<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php foreach($faqs as $index => $faq): ?>
            <div class="faq-item" onclick="toggleFaq(this)">
                <div class="q"><?= $faq['q'] ?> <i class="fa-solid fa-chevron-down"></i></div>
                <div class="a"><?= $faq['a'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
function toggleFaq(el){el.classList.toggle('active');}
</script>
<?php require_once 'includes/footer.php'; ?>