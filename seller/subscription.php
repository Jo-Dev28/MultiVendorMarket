<?php
$page_title = 'My Subscription';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT id, shop_name FROM sellers WHERE user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}
?>

<style>
/* ============================================
   SUBSCRIPTION PAGE - COMING SOON
============================================ */

.subscription-wrapper {
    display: flex;
    gap: 25px;
}

.subscription-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.subscription-content {
    flex: 1;
}

/* ---------- COMING SOON CARD ---------- */
.coming-soon-card {
    background: #fff;
    border-radius: 16px;
    padding: 50px 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    text-align: center;
}

.coming-soon-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 3rem;
    color: #f59e0b;
}

.coming-soon-card h2 {
    font-size: 2rem;
    font-weight: 800;
    color: #1f2937;
    margin-bottom: 10px;
}

.coming-soon-card .subtitle {
    font-size: 1.1rem;
    color: #6b7280;
    margin-bottom: 8px;
}

.coming-soon-card .description {
    color: #9ca3af;
    max-width: 500px;
    margin: 0 auto 25px;
    line-height: 1.7;
}

/* ---------- FEATURES PREVIEW ---------- */
.features-preview {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    max-width: 700px;
    margin: 30px auto;
}

.feature-preview-item {
    background: #f8fafc;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}

.feature-preview-item i {
    font-size: 1.8rem;
    color: #2563eb;
    margin-bottom: 8px;
}

.feature-preview-item h5 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.feature-preview-item p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
}

/* ---------- NOTIFY BUTTON ---------- */
.btn-notify {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    border: none;
    padding: 12px 35px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-notify:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 992px) {
    .subscription-wrapper {
        flex-direction: column;
    }
    
    .subscription-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .coming-soon-card {
        padding: 30px 20px;
    }
    
    .coming-soon-card h2 {
        font-size: 1.5rem;
    }
    
    .features-preview {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .coming-soon-icon {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .coming-soon-card {
        padding: 20px 15px;
    }
    
    .coming-soon-card h2 {
        font-size: 1.2rem;
    }
    
    .btn-notify {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="container py-4">
    <div class="subscription-wrapper">
        <!-- Sidebar -->
        <div class="subscription-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="subscription-content">
            <div class="coming-soon-card">
                
                <!-- Icon -->
                <div class="coming-soon-icon">
                    <i class="fa-solid fa-rocket"></i>
                </div>
                
                <!-- Title -->
                <h2>Subscription System</h2>
                <p class="subtitle">Coming Soon! 🚀</p>
                <p class="description">
                    We're working hard to bring you an amazing subscription system for sellers.
                    Soon you'll be able to choose from different plans and unlock premium features.
                </p>
                
                <!-- Features Preview -->
                <div class="features-preview">
                    <div class="feature-preview-item">
                        <i class="fa-solid fa-store"></i>
                        <h5>Multiple Plans</h5>
                        <p>Choose from Basic, Standard, or Premium</p>
                    </div>
                    <div class="feature-preview-item">
                        <i class="fa-solid fa-credit-card"></i>
                        <h5>Easy Payment</h5>
                        <p>M-Pesa, Card, Bank Transfer</p>
                    </div>
                    <div class="feature-preview-item">
                        <i class="fa-solid fa-chart-line"></i>
                        <h5>Premium Features</h5>
                        <p>More products, better visibility</p>
                    </div>
                </div>
                
                <!-- Notify Button -->
                <button class="btn-notify" onclick="notifyMe()">
                    <i class="fa-regular fa-bell"></i> Notify Me When Available
                </button>
                
                <!-- Back to Dashboard -->
                <div style="margin-top: 20px;">
                    <a href="dashboard.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">
                        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function notifyMe() {
    Swal.fire({
        icon: 'success',
        title: 'You\'re on the list!',
        text: 'We\'ll notify you when the subscription system is ready.',
        timer: 3000,
        showConfirmButton: false,
        position: 'top-end'
    });
}

// SweetAlert2 is already loaded in header
document.addEventListener('DOMContentLoaded', function() {
    // Check if SweetAlert is available
    if (typeof Swal === 'undefined') {
        // Load SweetAlert if not available
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        document.head.appendChild(script);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>