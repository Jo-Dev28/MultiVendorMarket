<?php
$page_title = 'Contact Us';
require_once 'includes/header.php';

$message_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('contact.php');
    }
    
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $subject = sanitize($_POST['subject'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        flash('Please fill all fields.', 'danger');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Invalid email address.', 'danger');
    } else {
        $to = ADMIN_EMAIL;
        $headers = "From: $email\r\n";
        $headers .= "Reply-To: $email\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        $email_body = "
        <html>
        <head><title>New Contact Message</title></head>
        <body>
            <h2>New Contact Message</h2>
            <p><strong>Name:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong><br>" . nl2br($message) . "</p>
            <p><small>Sent from " . SITE_NAME . " contact form</small></p>
        </body>
        </html>
        ";
        
        mail($to, "Contact: $subject", $email_body, $headers);
        
        $sql = "INSERT INTO contacts (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssss', $name, $email, $subject, $message);
        $stmt->execute();
        
        flash('Thank you for contacting us! We will get back to you soon.', 'success');
        $message_sent = true;
        redirect('contact.php');
    }
}
?>
<style>
/* ============================================
   CONTACT PAGE - CLEAN MODERN DESIGN
============================================ */
.contact-hero{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:60px 0;border-radius:0 0 30px 30px;margin-bottom:40px;text-align:center;position:relative;overflow:hidden}
.contact-hero::before{content:'';position:absolute;top:-50%;right:-20%;width:500px;height:500px;background:radial-gradient(circle,rgba(37,99,235,0.1) 0%,transparent 70%);border-radius:50%}
.contact-hero h1{color:#fff;font-size:2.8rem;font-weight:800;position:relative;z-index:1}
.contact-hero p{color:rgba(255,255,255,0.7);font-size:1.1rem;position:relative;z-index:1}
.contact-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:40px}
.contact-card{background:#fff;border-radius:16px;padding:24px;text-align:center;box-shadow:0 2px 15px rgba(0,0,0,0.06);transition:all .3s;border:1px solid #f1f5f9}
.contact-card:hover{transform:translateY(-6px);box-shadow:0 8px 30px rgba(0,0,0,0.1);border-color:#2563eb}
.contact-card .icon{width:56px;height:56px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#2563eb;margin:0 auto 12px;transition:all .3s}
.contact-card:hover .icon{background:#2563eb;color:#fff}
.contact-card h5{color:#1f2937;font-weight:600;margin-bottom:4px}
.contact-card p{color:#6b7280;font-size:.9rem;margin:0}
.form-wrapper{background:#fff;border-radius:20px;padding:40px;box-shadow:0 2px 15px rgba(0,0,0,0.06);border:1px solid #f1f5f9}
.form-wrapper .form-title{font-size:1.5rem;font-weight:700;color:#1f2937;margin-bottom:6px}
.form-wrapper .form-subtitle{color:#6b7280;margin-bottom:24px}
.form-wrapper .form-control{border-radius:12px;border:1px solid #e5e7eb;padding:14px 16px;font-size:.95rem;transition:all .3s;background:#fafbfc}
.form-wrapper .form-control:focus{border-color:#2563eb;box-shadow:0 0 0 4px rgba(37,99,235,0.08);background:#fff;outline:none}
.form-wrapper .form-control::placeholder{color:#94a3b8}
.form-wrapper textarea.form-control{resize:vertical;min-height:120px}
.form-wrapper .btn-send{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:14px 36px;border-radius:12px;font-weight:600;font-size:1rem;transition:all .3s;width:100%;cursor:pointer}
.form-wrapper .btn-send:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(37,99,235,0.3)}
.form-wrapper .btn-send:disabled{opacity:.6;cursor:not-allowed;transform:none!important}
@media(max-width:992px){.contact-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:768px){.contact-hero h1{font-size:2rem}.contact-grid{grid-template-columns:1fr}.form-wrapper{padding:24px}}
</style>
<div class="contact-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-regular fa-headset"></i> We're Here to Help
        </div>
        <h1>Get in Touch</h1>
        <p>Have questions or feedback? We'd love to hear from you.</p>
    </div>
</div>
<div class="container mb-5">
    <div class="contact-grid">
        <div class="contact-card"><div class="icon"><i class="fa-solid fa-location-dot"></i></div><h5>Visit Us</h5><p>Nairobi, Kenya</p></div>
        <div class="contact-card"><div class="icon"><i class="fa-solid fa-phone"></i></div><h5>Call Us</h5><p>+254 700 000 000</p></div>
        <div class="contact-card"><div class="icon"><i class="fa-solid fa-envelope"></i></div><h5>Email Us</h5><p><?= ADMIN_EMAIL ?></p></div>
        <div class="contact-card"><div class="icon"><i class="fa-regular fa-clock"></i></div><h5>Working Hours</h5><p>Mon-Fri: 8AM - 8PM</p></div>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="form-wrapper">
                <h3 class="form-title"><i class="fa-regular fa-pen-to-square" style="color:#2563eb"></i> Send a Message</h3>
                <p class="form-subtitle">Fill in the form below and we'll get back to you as soon as possible.</p>
                <form method="post" id="contactForm">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="name" class="form-control" placeholder="Your Full Name" required>
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="email" class="form-control" placeholder="Your Email Address" required>
                        </div>
                        <div class="col-12">
                            <input type="text" name="subject" class="form-control" placeholder="Subject" required>
                        </div>
                        <div class="col-12">
                            <textarea name="message" class="form-control" rows="5" placeholder="Your Message..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-send" id="sendBtn">
                                <span id="btnText"><i class="fa-regular fa-paper-plane"></i> Send Message</span>
                                <span id="btnLoading" style="display:none;"><i class="fa-solid fa-spinner fa-spin"></i> Sending...</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('contactForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('sendBtn');
    const text = document.getElementById('btnText');
    const loading = document.getElementById('btnLoading');
    btn.disabled = true;
    text.style.display = 'none';
    loading.style.display = 'inline';
});
</script>
<?php require_once 'includes/footer.php'; ?>