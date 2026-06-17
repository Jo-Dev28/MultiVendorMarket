<!-- ============================================
     END OF MAIN CONTENT
============================================ -->
</main>

<!-- ============================================
     MODERN FOOTER - FULL WIDTH
============================================ -->
<footer class="footer-modern">
    <div class="container">
        <div class="row g-4">
            <!-- Brand Column -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    <h3 class="footer-logo">
                        <i class="fa-solid fa-store"></i> <?= SITE_NAME ?>
                    </h3>
                    <p class="footer-desc">
                        Your ultimate multi-vendor marketplace with AI-powered shopping assistance. 
                        Discover thousands of products from trusted sellers across Kenya.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-link" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                        <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="social-link" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                        <a href="#" class="social-link" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>index.php"><i class="fa-solid fa-chevron-right"></i> Home</a></li>
                    <li><a href="<?= BASE_URL ?>shop.php"><i class="fa-solid fa-chevron-right"></i> Shop</a></li>
                    <li><a href="<?= BASE_URL ?>about.php"><i class="fa-solid fa-chevron-right"></i> About Us</a></li>
                    <li><a href="<?= BASE_URL ?>contact.php"><i class="fa-solid fa-chevron-right"></i> Contact</a></li>
                    <li><a href="<?= BASE_URL ?>blog.php"><i class="fa-solid fa-chevron-right"></i> Blog</a></li>
                </ul>
            </div>

            <!-- Customer Service -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Customer Service</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>faq.php"><i class="fa-solid fa-chevron-right"></i> FAQ</a></li>
                    <li><a href="<?= BASE_URL ?>track-order.php"><i class="fa-solid fa-chevron-right"></i> Track Order</a></li>
                    <li><a href="<?= BASE_URL ?>returns.php"><i class="fa-solid fa-chevron-right"></i> Returns Policy</a></li>
                    <li><a href="<?= BASE_URL ?>shipping.php"><i class="fa-solid fa-chevron-right"></i> Shipping Info</a></li>
                    <li><a href="<?= BASE_URL ?>terms.php"><i class="fa-solid fa-chevron-right"></i> Terms & Conditions</a></li>
                </ul>
            </div>

            <!-- Seller & Contact -->
            <div class="col-lg-4 col-md-6">
                <h5 class="footer-title">Sell With Us</h5>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>seller/register.php"><i class="fa-solid fa-chevron-right"></i> Become a Seller</a></li>
                    <li><a href="<?= BASE_URL ?>seller/login.php"><i class="fa-solid fa-chevron-right"></i> Seller Login</a></li>
                    <li><a href="<?= BASE_URL ?>seller/dashboard.php"><i class="fa-solid fa-chevron-right"></i> Seller Dashboard</a></li>
                </ul>
                <div class="footer-contact mt-3">
                    <p><i class="fa-solid fa-envelope"></i> <?= ADMIN_EMAIL ?></p>
                    <p><i class="fa-solid fa-phone"></i> +254 700 000 000</p>
                    <!-- <p><i class="fa-solid fa-location-dot"></i> Nairobi, Kenya</p> -->
                </div>
            </div>
        </div>

        <!-- Divider -->
        <hr class="footer-divider">

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="footer-payments">
                        <i class="fa-brands fa-cc-visa" title="Visa"></i>
                        <i class="fa-brands fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fa-brands fa-cc-paypal" title="PayPal"></i>
                        <i class="fa-solid fa-mobile-screen-button" title="M-Pesa"></i>
                        <span class="payment-badge">Secure Payments</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    /* ============================================
       MODERN MARKETPLACE FOOTER - FULL WIDTH
    ============================================ */

    .footer-modern {
        background: linear-gradient(180deg, #071129, #0f172a);
        color: #94a3b8;
        padding: 70px 0 20px;
        margin-top: 0;
        position: relative;
        overflow: hidden;
        width: 100%;
        left: 0;
        right: 0;
    }

    .footer-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 1px;
        background: linear-gradient(
            90deg,
            transparent,
            rgba(255,255,255,.15),
            transparent
        );
    }

    /* Brand */
    .footer-logo {
        color: #fff;
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .footer-logo i {
        color: #f59e0b;
        margin-right: 8px;
    }

    .footer-desc {
        color: #cbd5e1;
        line-height: 1.8;
        font-size: .95rem;
        max-width: 380px;
    }

    /* Social */
    .footer-social {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .social-link {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        color: #fff;
        background: rgba(255,255,255,.08);
        transition: .3s;
    }

    .social-link:hover {
        background: #2563eb;
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(37,99,235,.4);
    }

    /* Titles */
    .footer-title {
        color: #fff;
        font-size: 1.1rem;
        margin-bottom: 25px;
        font-weight: 600;
        position: relative;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        left: 0;
        bottom: -8px;
        width: 40px;
        height: 3px;
        background: #f59e0b;
        border-radius: 50px;
    }

    /* Links */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 12px;
    }

    .footer-links a {
        text-decoration: none;
        color: #cbd5e1;
        transition: .3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .footer-links a i {
        color: #f59e0b;
        font-size: .75rem;
    }

    .footer-links a:hover {
        color: #fff;
        transform: translateX(5px);
    }

    /* Contact */
    .footer-contact p {
        margin-bottom: 10px;
        color: #cbd5e1;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .footer-contact i {
        color: #f59e0b;
        width: 18px;
    }

    /* Divider */
    .footer-divider {
        border-color: rgba(255,255,255,.08);
        margin: 40px 0 25px;
    }

    /* Bottom */
    .footer-bottom {
        border-top: 1px solid rgba(255,255,255,.08);
        padding-top: 20px;
    }

    .footer-bottom p {
        margin: 0;
        color: #94a3b8;
    }

    /* Payments */
    .footer-payments {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }

    .footer-payments i {
        font-size: 1.8rem;
        color: #cbd5e1;
        transition: .3s;
    }

    .footer-payments i:hover {
        color: #fff;
        transform: scale(1.1);
    }

    .payment-badge {
        background: #0b5ed7;
        color: #fff;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: .75rem;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 991px) {
        .footer-modern {
            text-align: center;
            padding: 50px 0 20px;
        }

        .footer-desc {
            max-width: 100%;
        }

        .footer-social {
            justify-content: center;
        }

        .footer-title::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .footer-links a {
            justify-content: center;
        }

        .footer-contact p {
            justify-content: center;
        }

        .footer-payments {
            justify-content: center;
            margin-top: 15px;
        }
    }

    @media (max-width: 768px) {
        .footer-modern {
            padding: 40px 0 15px;
        }

        .footer-logo {
            font-size: 1.5rem;
        }

        .footer-payments i {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .footer-modern {
            padding: 30px 0 10px;
        }

        .social-link {
            width: 36px;
            height: 36px;
        }

        .payment-badge {
            font-size: 0.65rem;
            padding: 4px 10px;
        }

        .footer-logo {
            font-size: 1.3rem;
        }
    }
</style>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.8.0/dist/sweetalert2.all.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>