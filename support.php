<?php
$page_title = 'Help & Support';
require_once 'includes/header.php';

// Get user info
$user = current_user();
$is_logged_in = isset($user['id']) && $user['id'];

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('support.php');
    }
    
    $subject = sanitize($_POST['subject'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    if (empty($subject) || empty($category) || empty($message)) {
        flash('Please fill all fields.', 'danger');
    } else {
        // Save to database (create support_tickets table)
        $sql = "INSERT INTO support_tickets (user_id, subject, category, message, status, created_at) 
                VALUES (?, ?, ?, ?, 'open', NOW())";
        $stmt = $mysqli->prepare($sql);
        $user_id = $is_logged_in ? $user['id'] : 0;
        $stmt->bind_param('isss', $user_id, $subject, $category, $message);
        
        if ($stmt->execute()) {
            flash('Your support ticket has been submitted. We\'ll get back to you soon!', 'success');
            redirect('support.php');
        } else {
            flash('Failed to submit ticket. Please try again.', 'danger');
        }
    }
}

// Get user's tickets if logged in
$tickets = [];
if ($is_logged_in) {
    $sql = "SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    $tickets = $stmt->get_result();
}
?>

<style>
/* ============================================
   SUPPORT PAGE - MODERN CLEAN DESIGN
============================================ */

/* ---------- HERO SECTION ---------- */
.support-hero {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    padding: 60px 0;
    border-radius: 0 0 30px 30px;
    margin-bottom: 40px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.support-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(37, 99, 235, 0.1) 0%, transparent 70%);
    border-radius: 50%;
}

.support-hero h1 {
    color: #fff;
    font-size: 2.8rem;
    font-weight: 800;
    position: relative;
    z-index: 1;
}

.support-hero p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

/* ---------- MAIN LAYOUT ---------- */
.support-wrapper {
    display: flex;
    gap: 25px;
}

.support-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.support-content {
    flex: 1;
}

/* ---------- SUPPORT CARD ---------- */
.support-card {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    margin-bottom: 25px;
}

.support-card .card-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.support-card .card-title i {
    color: #2563eb;
}

/* ---------- FORM ---------- */
.support-form .form-label {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 6px;
    display: block;
    font-size: 0.85rem;
}

.support-form .form-label .required {
    color: #ef4444;
}

.support-form .form-control,
.support-form .form-select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.support-form .form-control:focus,
.support-form .form-select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    outline: none;
}

.support-form textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.btn-submit {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    padding: 12px 30px;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

.btn-submit i {
    margin-right: 8px;
}

/* ---------- TICKETS ---------- */
.ticket-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.ticket-item:hover {
    background: #f8fafc;
}

.ticket-item:last-child {
    border-bottom: none;
}

.ticket-info .ticket-subject {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9rem;
}

.ticket-info .ticket-meta {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 2px;
}

.ticket-status {
    padding: 3px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
}

.ticket-status.open { background: #fef3c7; color: #d97706; }
.ticket-status.in-progress { background: #dbeafe; color: #2563eb; }
.ticket-status.resolved { background: #d1fae5; color: #059669; }
.ticket-status.closed { background: #e5e7eb; color: #6b7280; }

.no-tickets {
    text-align: center;
    padding: 30px;
    color: #6b7280;
}

.no-tickets i {
    font-size: 2.5rem;
    color: #9ca3af;
    margin-bottom: 10px;
}

/* ---------- FAQ SECTION ---------- */
.faq-item {
    border-bottom: 1px solid #f1f5f9;
    padding: 12px 0;
}

.faq-item:last-child {
    border-bottom: none;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    font-weight: 600;
    color: #1f2937;
    transition: all 0.3s ease;
}

.faq-question:hover {
    color: #2563eb;
}

.faq-question i {
    transition: transform 0.3s ease;
    color: #6b7280;
}

.faq-question.active i {
    transform: rotate(180deg);
}

.faq-answer {
    display: none;
    padding-top: 8px;
    color: #6b7280;
    font-size: 0.9rem;
    line-height: 1.6;
}

.faq-answer.show {
    display: block;
}

/* ---------- CONTACT INFO ---------- */
.contact-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    background: #f8fafc;
    border-radius: 10px;
}

.contact-item i {
    color: #2563eb;
    font-size: 1.2rem;
    width: 24px;
}

.contact-item .contact-label {
    font-size: 0.7rem;
    color: #6b7280;
}

.contact-item .contact-value {
    font-size: 0.85rem;
    font-weight: 500;
    color: #1f2937;
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 992px) {
    .support-wrapper {
        flex-direction: column;
    }
    
    .support-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .support-hero h1 {
        font-size: 2rem;
    }
    
    .support-card {
        padding: 20px;
    }
    
    .contact-info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .support-card {
        padding: 15px;
    }
    
    .ticket-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }
}
</style>

<!-- ============================================
     HERO SECTION
============================================ -->
<div class="support-hero">
    <div class="container">
        <div class="ai-badge" style="display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px">
            <i class="fa-regular fa-headset"></i> We're Here to Help
        </div>
        <h1>Help & Support</h1>
        <p>Get the help you need. Our support team is ready to assist you.</p>
    </div>
</div>

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="container mb-5">
    <div class="support-wrapper">
        <!-- Sidebar -->
        <div class="support-sidebar">
            <?php require_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="support-content">
            
            <!-- ==========================================
                 CONTACT INFO
            ========================================== -->
            <div class="support-card">
                <div class="card-title">
                    <i class="fa-regular fa-circle-info"></i> Contact Information
                </div>
                <div class="contact-info-grid">
                    <div class="contact-item">
                        <i class="fa-regular fa-envelope"></i>
                        <div>
                            <div class="contact-label">Email</div>
                            <div class="contact-value"><?= ADMIN_EMAIL ?></div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa-solid fa-phone"></i>
                        <div>
                            <div class="contact-label">Phone</div>
                            <div class="contact-value">+254 700 000 000</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa-regular fa-clock"></i>
                        <div>
                            <div class="contact-label">Working Hours</div>
                            <div class="contact-value">Mon-Fri: 8AM - 8PM</div>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fa-regular fa-message"></i>
                        <div>
                            <div class="contact-label">Response Time</div>
                            <div class="contact-value">Within 24 hours</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ==========================================
                 SUBMIT TICKET
            ========================================== -->
            <div class="support-card">
                <div class="card-title">
                    <i class="fa-regular fa-pen-to-square"></i> Submit a Support Ticket
                </div>
                
                <form method="post" class="support-form">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">
                                Subject <span class="required">*</span>
                            </label>
                            <input type="text" name="subject" class="form-control" placeholder="Brief description of your issue" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">
                                Category <span class="required">*</span>
                            </label>
                            <select name="category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="account">Account Issues</option>
                                <option value="order">Order Problems</option>
                                <option value="payment">Payment Issues</option>
                                <option value="seller">Seller Questions</option>
                                <option value="product">Product Issues</option>
                                <option value="shipping">Shipping & Delivery</option>
                                <option value="technical">Technical Issues</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">
                                Message <span class="required">*</span>
                            </label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Describe your issue in detail..." required></textarea>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn-submit">
                                <i class="fa-regular fa-paper-plane"></i> Submit Ticket
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- ==========================================
                 MY TICKETS
            ========================================== -->
            <?php if ($is_logged_in): ?>
            <div class="support-card">
                <div class="card-title">
                    <i class="fa-regular fa-ticket"></i> My Support Tickets
                </div>
                
                <?php if ($tickets && $tickets->num_rows > 0): ?>
                    <?php while ($ticket = $tickets->fetch_assoc()): ?>
                        <div class="ticket-item">
                            <div class="ticket-info">
                                <div class="ticket-subject"><?= sanitize($ticket['subject']) ?></div>
                                <div class="ticket-meta">
                                    <span><?= sanitize($ticket['category']) ?></span>
                                    <span>•</span>
                                    <span><?= date('M d, Y', strtotime($ticket['created_at'])) ?></span>
                                </div>
                            </div>
                            <span class="ticket-status <?= $ticket['status'] ?>">
                                <?= ucfirst($ticket['status']) ?>
                            </span>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-tickets">
                        <i class="fa-regular fa-ticket"></i>
                        <p>You haven't submitted any support tickets yet.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- ==========================================
                 FAQ SECTION
            ========================================== -->
            <div class="support-card">
                <div class="card-title">
                    <i class="fa-regular fa-circle-question"></i> Frequently Asked Questions
                </div>
                
                <?php 
                $faqs = [
                    [
                        'q' => 'How do I reset my password?',
                        'a' => 'Go to the login page and click "Forgot Password". Enter your email address and follow the instructions sent to your email.'
                    ],
                    [
                        'q' => 'How long does shipping take?',
                        'a' => 'Shipping typically takes 2-5 business days within Kenya. Free shipping is available on orders over KSH 5,000.'
                    ],
                    [
                        'q' => 'What payment methods do you accept?',
                        'a' => 'We accept M-Pesa, Credit/Debit Cards, Bank Transfer, and PayPal. All payments are secure and encrypted.'
                    ],
                    [
                        'q' => 'How do I become a seller?',
                        'a' => 'Register as a customer, go to your dashboard, and click "Become a Seller". Fill in your business details and submit for verification.'
                    ],
                    [
                        'q' => 'Can I return a product?',
                        'a' => 'Yes, returns are accepted within 7 days of delivery. Items must be unused with original packaging. Contact the seller first to initiate a return.'
                    ],
                    [
                        'q' => 'How do I track my order?',
                        'a' => 'Go to "My Orders" in your dashboard and click "Track Order" on the specific order. You\'ll see the current status and tracking information.'
                    ]
                ];
                ?>
                
                <?php foreach ($faqs as $index => $faq): ?>
                    <div class="faq-item">
                        <div class="faq-question" onclick="toggleFaq(this)">
                            <span><?= $faq['q'] ?></span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <div class="faq-answer"><?= $faq['a'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        </div>
    </div>
</div>

<script>
// Toggle FAQ answers
function toggleFaq(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('i');
    
    // Close all other FAQs
    document.querySelectorAll('.faq-answer').forEach(function(el) {
        if (el !== answer) {
            el.classList.remove('show');
            el.previousElementSibling.querySelector('i').classList.remove('active');
        }
    });
    
    // Toggle current FAQ
    answer.classList.toggle('show');
    icon.classList.toggle('active');
}

// Open first FAQ by default
document.addEventListener('DOMContentLoaded', function() {
    const firstFaq = document.querySelector('.faq-question');
    if (firstFaq) {
        setTimeout(function() {
            firstFaq.click();
        }, 300);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>