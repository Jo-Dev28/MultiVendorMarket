<?php
$page_title = 'My Shop';
require_once '../includes/header.php';
require_role('seller');

$user_id = $_SESSION['user_id'];

// Get seller info
$seller_sql = "SELECT s.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
               FROM sellers s 
               JOIN users u ON u.id = s.user_id 
               WHERE s.user_id = ?";
$seller_stmt = $mysqli->prepare($seller_sql);
$seller_stmt->bind_param('i', $user_id);
$seller_stmt->execute();
$seller = $seller_stmt->get_result()->fetch_assoc();

if (!$seller) {
    flash('Seller account not found.', 'danger');
    redirect('index.php');
}

// Ensure upload directory exists
$upload_dir = '../uploads/seller_documents/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle Document Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_document'])) {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('my_shop.php');
    }
    
    $document_type = sanitize($_POST['document_type'] ?? '');
    $document_notes = sanitize($_POST['document_notes'] ?? '');
    
    if (empty($document_type)) {
        flash('Please select a document type.', 'danger');
        redirect('seller/my_shop.php');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
        $error_code = $_FILES['document_file']['error'] ?? 0;
        $error_messages = [
            1 => 'File exceeds upload_max_filesize (2MB)',
            2 => 'File exceeds MAX_FILE_SIZE',
            3 => 'File was only partially uploaded',
            4 => 'No file was selected',
            6 => 'Missing temporary folder',
            7 => 'Failed to write file to disk',
            8 => 'File upload stopped by extension'
        ];
        $error_msg = $error_messages[$error_code] ?? 'Unknown upload error';
        flash('Error uploading: ' . $error_msg, 'danger');
        redirect('seller/my_shop.php');
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($_FILES['document_file']['type'], $allowed_types)) {
        flash('Invalid file type. Allowed: JPG, PNG, GIF, WebP, PDF', 'danger');
        redirect('seller/my_shop.php');
    }
    
    if ($_FILES['document_file']['size'] > $max_size) {
        flash('File too large. Max 5MB allowed.', 'danger');
        redirect('seller/my_shop.php');
    }
    
    // Generate unique filename
    $ext = pathinfo($_FILES['document_file']['name'], PATHINFO_EXTENSION);
    $filename = 'seller_doc_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($_FILES['document_file']['tmp_name'], $filepath)) {
        // Map document type to database column
        $column_map = [
            'business_license' => 'business_license',
            'id_document' => 'id_image',
            'tax_compliance' => 'tax_compliance',
            'bank_statement' => 'bank_statement',
            'other' => 'other_document'
        ];
        
        // If it's ID document, use id_image column
        if ($document_type === 'id_document') {
            $column = 'id_image';
        } else {
            $column = $column_map[$document_type] ?? 'other_document';
        }
        
        // Clear rejection reason for this document when re-uploading
        $clear_rejection = "UPDATE sellers SET rejected_document = NULL, rejection_reason = NULL WHERE id = ?";
        $clear_stmt = $mysqli->prepare($clear_rejection);
        $clear_stmt->bind_param('i', $seller['id']);
        $clear_stmt->execute();
        $clear_stmt->close();
        
        // Update database
        $sql = "UPDATE sellers SET $column = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('si', $filename, $seller['id']);
        
        if ($stmt->execute()) {
            flash('Document uploaded successfully!', 'success');
        } else {
            flash('Failed to save document to database.', 'danger');
        }
        $stmt->close();
    } else {
        flash('Failed to move uploaded file. Check folder permissions.', 'danger');
    }
    redirect('seller/my_shop.php');
}

// Get product count
$product_count = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'approved'")->fetch_assoc()['count'];

// Get pending products count
$pending_products = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'pending'")->fetch_assoc()['count'];

// Get rejected products count
$rejected_products = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE seller_id = {$seller['id']} AND status = 'rejected'")->fetch_assoc()['count'];

// Get order count
$order_count = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE seller_id = {$seller['id']}")->fetch_assoc()['count'];

// Get total earnings
$earnings = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE seller_id = {$seller['id']} AND status != 'cancelled'")->fetch_assoc()['total'];

// Get rating
$rating_data = $mysqli->query("SELECT COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(*) as review_count 
                               FROM reviews r 
                               JOIN products p ON p.id = r.product_id 
                               WHERE p.seller_id = {$seller['id']} AND r.status = 'approved'")->fetch_assoc();
$rating = round($rating_data['avg_rating'] ?? 0, 1);
$review_count = $rating_data['review_count'] ?? 0;
?>

<style>
/* ============================================
   MY SHOP PAGE - MODERN CLEAN DESIGN
============================================ */

/* ---------- MAIN CONTAINER ---------- */
.shop-wrapper {
    display: flex;
    gap: 25px;
}

.shop-sidebar {
    width: 280px;
    flex-shrink: 0;
}

.shop-content {
    flex: 1;
}

/* ---------- SHOP PREVIEW CARD ---------- */
.shop-preview-card {
    background: #fff;
    border-radius: 16px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    margin-bottom: 20px;
}

/* ---------- SHOP HEADER ---------- */
.shop-header {
    display: flex;
    align-items: center;
    gap: 25px;
    flex-wrap: wrap;
    padding-bottom: 20px;
    border-bottom: 2px solid #f1f5f9;
    margin-bottom: 20px;
}

/* Shop Logo */
.shop-logo {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #e5e7eb;
    flex-shrink: 0;
    background: #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
}

.shop-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.shop-logo .no-logo {
    font-size: 2.5rem;
    color: #9ca3af;
}

/* Shop Title */
.shop-title {
    flex: 1;
}

.shop-title h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px 0;
}

.shop-title .owner {
    color: #6b7280;
    font-size: 0.9rem;
}

.shop-title .status-badge {
    display: inline-block;
    padding: 3px 12px;
    border-radius: 50px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 6px;
}

.status-verified { background: #d1fae5; color: #059669; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-rejected { background: #fee2e2; color: #dc2626; }

/* ---------- SHOP STATS ---------- */
.shop-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin: 20px 0;
}

.shop-stat {
    background: #f8fafc;
    padding: 15px;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s ease;
}

.shop-stat:hover {
    background: #eff6ff;
    transform: translateY(-2px);
}

.shop-stat .number {
    font-size: 1.3rem;
    font-weight: 700;
    color: #2563eb;
}

.shop-stat .label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 4px;
}

/* ---------- SHOP DESCRIPTION ---------- */
.shop-description {
    background: #f8fafc;
    padding: 15px 20px;
    border-radius: 12px;
    margin: 15px 0;
}

.shop-description p {
    margin: 0;
    color: #4b5563;
    line-height: 1.6;
}

/* ---------- SHOP INFO GRID ---------- */
.shop-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 15px;
}

.shop-info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    background: #f8fafc;
    border-radius: 10px;
}

.shop-info-item i {
    color: #2563eb;
    width: 18px;
}

.shop-info-item .label {
    color: #6b7280;
    font-size: 0.8rem;
}

.shop-info-item .value {
    color: #1f2937;
    font-weight: 500;
    font-size: 0.85rem;
}

/* ---------- SHOP ACTIONS ---------- */
.shop-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.btn-edit-shop {
    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-edit-shop:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
    color: #fff;
}

.btn-view-shop {
    background: #f1f5f9;
    color: #1f2937;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-view-shop:hover {
    background: #e5e7eb;
    color: #1f2937;
}

.btn-add-product {
    background: #f59e0b;
    color: #1f2937;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-add-product:hover {
    background: #d97706;
    transform: translateY(-2px);
    color: #1f2937;
}

.btn-manage-products {
    background: #10b981;
    color: #fff;
    padding: 10px 24px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-manage-products:hover {
    background: #059669;
    transform: translateY(-2px);
    color: #fff;
}

/* ---------- PRODUCT STATUS BADGES ---------- */
.product-status-badges {
    display: flex;
    gap: 15px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.status-badge-small {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge-small.approved { background: #d1fae5; color: #059669; }
.status-badge-small.pending { background: #fef3c7; color: #d97706; }
.status-badge-small.rejected { background: #fee2e2; color: #dc2626; }

/* ---------- DOCUMENTS SECTION ---------- */
.documents-section {
    background: #fff;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
}

.documents-section .section-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.documents-section .section-title i {
    color: #2563eb;
}

.documents-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.document-card {
    background: #f8fafc;
    border-radius: 12px;
    padding: 15px;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
    text-align: center;
    position: relative;
}

.document-card:hover {
    border-color: #2563eb;
    transform: translateY(-2px);
}

.document-card .doc-icon {
    font-size: 2.5rem;
    margin-bottom: 8px;
}

.document-card .doc-type {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.9rem;
}

.document-card .doc-status {
    font-size: 0.7rem;
    margin-top: 4px;
    font-weight: 500;
}

.document-card .doc-status.uploaded { color: #10b981; }
.document-card .doc-status.missing { color: #ef4444; }
.document-card .doc-status.rejected { color: #dc2626; }

/* Rejection Reason Box */
.document-card .rejection-box {
    background: #fee2e2;
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 8px 10px;
    margin-top: 8px;
    text-align: left;
}

.document-card .rejection-box .rejection-label {
    font-size: 0.6rem;
    font-weight: 700;
    color: #dc2626;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.document-card .rejection-box .rejection-reason {
    font-size: 0.8rem;
    color: #991b1b;
    margin-top: 2px;
}

.document-card .rejection-box .rejection-action {
    font-size: 0.7rem;
    color: #6b7280;
    margin-top: 4px;
}

.document-card .doc-actions {
    margin-top: 10px;
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
}

.document-card .doc-actions a,
.document-card .doc-actions button {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.7rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.document-card .doc-actions .btn-view-doc {
    background: #2563eb;
    color: #fff;
}

.document-card .doc-actions .btn-view-doc:hover {
    background: #1d4ed8;
}

.document-card .doc-actions .btn-upload-doc {
    background: #10b981;
    color: #fff;
}

.document-card .doc-actions .btn-upload-doc:hover {
    background: #059669;
}

.document-card .doc-actions .btn-reupload-doc {
    background: #f59e0b;
    color: #fff;
}

.document-card .doc-actions .btn-reupload-doc:hover {
    background: #d97706;
}

.empty-documents {
    text-align: center;
    padding: 30px;
    color: #6b7280;
}

.empty-documents i {
    font-size: 2.5rem;
    color: #d1d5db;
    margin-bottom: 10px;
}

/* Notification Banner */
.notification-banner {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.notification-banner i {
    color: #d97706;
    font-size: 1.2rem;
    margin-top: 2px;
}

.notification-banner .notification-content {
    flex: 1;
}

.notification-banner .notification-title {
    font-weight: 600;
    color: #92400e;
}

.notification-banner .notification-message {
    color: #78350f;
    font-size: 0.85rem;
}

/* ---------- RESPONSIVE ---------- */
@media (max-width: 992px) {
    .shop-wrapper {
        flex-direction: column;
    }
    
    .shop-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .shop-header {
        flex-direction: column;
        text-align: center;
    }
    
    .shop-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .shop-info-grid {
        grid-template-columns: 1fr;
    }
    
    .shop-actions {
        justify-content: center;
    }
    
    .shop-logo {
        width: 80px;
        height: 80px;
    }
    
    .documents-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 480px) {
    .shop-stats {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    
    .shop-stat {
        padding: 10px;
    }
    
    .shop-stat .number {
        font-size: 1.1rem;
    }
    
    .shop-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .shop-actions a {
        width: 100%;
        justify-content: center;
    }
    
    .documents-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container py-4">
    <div class="shop-wrapper">
        <!-- Sidebar -->
        <div class="shop-sidebar">
            <?php require_once '../includes/dashboard_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="shop-content">
            
            <!-- ==========================================
                 NOTIFICATION BANNER - IF DOCUMENT REJECTED
            ========================================== -->
            <?php if (!empty($seller['rejection_reason']) && !empty($seller['rejected_document'])): 
                $doc_labels = [
                    'id_image' => 'ID Document',
                    'business_license' => 'Business License',
                    'tax_compliance' => 'Tax Compliance',
                    'bank_statement' => 'Bank Statement',
                    'other_document' => 'Other Document'
                ];
                $doc_label = $doc_labels[$seller['rejected_document']] ?? 'Document';
            ?>
                <div class="notification-banner">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <div class="notification-content">
                        <div class="notification-title">📄 <?= $doc_label ?> Rejected</div>
                        <div class="notification-message">
                            <strong>Reason:</strong> <?= htmlspecialchars($seller['rejection_reason']) ?>
                            <br>
                            <small>Please upload a new document to replace the rejected one.</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="shop-preview-card">
                
                <!-- ==========================================
                     SHOP HEADER
                ========================================== -->
                <div class="shop-header">
                    <div class="shop-logo">
                        <?php if (!empty($seller['shop_logo']) && file_exists('../uploads/sellers/' . $seller['shop_logo'])): ?>
                            <img src="../uploads/sellers/<?= $seller['shop_logo'] ?>" alt="Shop Logo">
                        <?php else: ?>
                            <div class="no-logo">
                                <i class="fa-solid fa-store"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="shop-title">
                        <h2><?= sanitize($seller['shop_name']) ?></h2>
                        <div class="owner">
                            <i class="fa-regular fa-user"></i> <?= sanitize($seller['user_name']) ?>
                        </div>
                        <span class="status-badge status-<?= $seller['status'] ?>">
                            <i class="fa-regular fa-circle"></i> <?= ucfirst($seller['status']) ?>
                        </span>
                    </div>
                </div>
                
                <!-- ==========================================
                     SHOP STATS
                ========================================== -->
                <div class="shop-stats">
                    <div class="shop-stat">
                        <div class="number"><?= $product_count ?></div>
                        <div class="label">Products</div>
                    </div>
                    <div class="shop-stat">
                        <div class="number"><?= $order_count ?></div>
                        <div class="label">Orders</div>
                    </div>
                    <div class="shop-stat">
                        <div class="number">KSH <?= number_format($earnings) ?></div>
                        <div class="label">Earnings</div>
                    </div>
                    <div class="shop-stat">
                        <div class="number"><?= $rating ?></div>
                        <div class="label">Rating</div>
                    </div>
                </div>
                
                <!-- Product Status Breakdown -->
                <div class="product-status-badges">
                    <span class="status-badge-small approved">
                        <i class="fa-regular fa-circle-check"></i> <?= $product_count ?> Approved
                    </span>
                    <span class="status-badge-small pending">
                        <i class="fa-regular fa-clock"></i> <?= $pending_products ?> Pending
                    </span>
                    <span class="status-badge-small rejected">
                        <i class="fa-solid fa-circle-xmark"></i> <?= $rejected_products ?> Rejected
                    </span>
                    <span class="status-badge-small" style="background:#e0e7ff; color:#4338ca;">
                        <i class="fa-regular fa-star"></i> <?= $review_count ?> Reviews
                    </span>
                </div>
                
                <!-- ==========================================
                     SHOP DESCRIPTION
                ========================================== -->
                <?php if (!empty($seller['description'])): ?>
                    <div class="shop-description">
                        <p><?= nl2br(sanitize($seller['description'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- ==========================================
                     SHOP INFO GRID
                ========================================== -->
                <div class="shop-info-grid">
                    <div class="shop-info-item">
                        <i class="fa-solid fa-phone"></i>
                        <div>
                            <span class="label">Phone:</span> 
                            <span class="value"><?= sanitize($seller['phone'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-solid fa-location-dot"></i>
                        <div>
                            <span class="label">Location:</span> 
                            <span class="value"><?= sanitize($seller['location'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-envelope"></i>
                        <div>
                            <span class="label">Email:</span> 
                            <span class="value"><?= sanitize($seller['user_email']) ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-calendar"></i>
                        <div>
                            <span class="label">Member Since:</span> 
                            <span class="value"><?= date('M d, Y', strtotime($seller['created_at'])) ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-id-card"></i>
                        <div>
                            <span class="label">Business ID:</span> 
                            <span class="value"><?= sanitize($seller['business_id'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <div class="shop-info-item">
                        <i class="fa-regular fa-circle-check"></i>
                        <div>
                            <span class="label">Status:</span> 
                            <span class="value" style="color:#10b981;"><?= ucfirst($seller['status']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- ==========================================
                     SHOP ACTIONS
                ========================================== -->
                <div class="shop-actions">
                    <a href="edit_profile.php" class="btn-edit-shop">
                        <i class="fa-solid fa-pen-to-square"></i> Edit Shop
                    </a>
                    <a href="<?= BASE_URL ?>seller.php?id=<?= $seller['id'] ?>" target="_blank" class="btn-view-shop">
                        <i class="fa-regular fa-eye"></i> View Shop
                    </a>
                    <a href="add_product.php" class="btn-add-product">
                        <i class="fa-solid fa-plus"></i> Add Product
                    </a>
                    <a href="products.php" class="btn-manage-products">
                        <i class="fa-solid fa-box"></i> Manage Products
                    </a>
                </div>
                
            </div>
            
            <!-- ==========================================
                 DOCUMENTS SECTION
            ========================================== -->
            <div class="documents-section">
                <div class="section-title">
                    <i class="fa-solid fa-file-lines"></i> Shop Documents
                    <span class="text-muted" style="font-size:0.8rem; font-weight:400;">
                        (Documents cannot be deleted once uploaded)
                    </span>
                </div>
                
                <!-- Documents Grid -->
                <div class="documents-grid">
                    <?php 
                    $documents = [
                        'business_license' => ['label' => 'Business License', 'icon' => 'fa-solid fa-certificate', 'color' => '#f59e0b'],
                        'id_document' => ['label' => 'ID Document', 'icon' => 'fa-regular fa-id-card', 'color' => '#2563eb'],
                        'tax_compliance' => ['label' => 'Tax Compliance', 'icon' => 'fa-solid fa-file-invoice', 'color' => '#10b981'],
                        'bank_statement' => ['label' => 'Bank Statement', 'icon' => 'fa-solid fa-building-columns', 'color' => '#7c3aed'],
                        'other' => ['label' => 'Other Document', 'icon' => 'fa-solid fa-file', 'color' => '#6b7280']
                    ];
                    
                    $column_map = [
                        'business_license' => 'business_license',
                        'id_document' => 'id_image',
                        'tax_compliance' => 'tax_compliance',
                        'bank_statement' => 'bank_statement',
                        'other' => 'other_document'
                    ];
                    
                    foreach ($documents as $key => $doc):
                        $column = $column_map[$key];
                        $filename = $seller[$column] ?? null;
                        $has_document = !empty($filename);
                        $doc_path = '../uploads/seller_documents/' . $filename;
                        $is_image = $has_document && in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                        
                        // Check if this document was rejected
                        $is_rejected = ($seller['rejected_document'] == $key);
                        $rejection_reason = $seller['rejection_reason'] ?? null;
                    ?>
                        <div class="document-card">
                            <div class="doc-icon" style="color: <?= $doc['color'] ?>;">
                                <i class="<?= $doc['icon'] ?>"></i>
                            </div>
                            <div class="doc-type"><?= $doc['label'] ?></div>
                            
                            <?php if ($is_rejected): ?>
                                <div class="doc-status rejected">
                                    <i class="fa-solid fa-times-circle"></i> Rejected
                                </div>
                                <div class="rejection-box">
                                    <div class="rejection-label"><i class="fa-solid fa-circle-exclamation"></i> Rejection Reason</div>
                                    <div class="rejection-reason"><?= htmlspecialchars($rejection_reason ?? 'No reason provided') ?></div>
                                    <div class="rejection-action">📤 Please upload a new document to replace this</div>
                                </div>
                            <?php elseif ($has_document): ?>
                                <div class="doc-status uploaded">
                                    <i class="fa-solid fa-check-circle"></i> Uploaded
                                </div>
                            <?php else: ?>
                                <div class="doc-status missing">
                                    <i class="fa-solid fa-circle-xmark"></i> Not Uploaded
                                </div>
                            <?php endif; ?>
                            
                            <div class="doc-actions">
                                <?php if ($has_document): ?>
                                    <?php if ($is_image): ?>
                                        <a href="<?= $doc_path ?>" target="_blank" class="btn-view-doc">
                                            <i class="fa-regular fa-eye"></i> View
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= $doc_path ?>" target="_blank" class="btn-view-doc">
                                            <i class="fa-solid fa-download"></i> Download
                                        </a>
                                    <?php endif; ?>
                                    
                                    <!-- Re-upload button (always available, no delete) -->
                                    <button class="btn-upload-doc" onclick="openUploadModal('<?= $key ?>', '<?= $doc['label'] ?>')">
                                        <i class="fa-solid fa-rotate"></i> Re-Upload
                                    </button>
                                    
                                <?php else: ?>
                                    <button class="btn-upload-doc" onclick="openUploadModal('<?= $key ?>', '<?= $doc['label'] ?>')">
                                        <i class="fa-solid fa-upload"></i> Upload
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($has_document && !$is_rejected): ?>
                                <div style="font-size:0.55rem; color:#9ca3af; margin-top:6px;">
                                    <i class="fa-regular fa-clock"></i> Uploaded
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" action="">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <input type="hidden" name="upload_document" value="1">
                <input type="hidden" name="document_type" id="docTypeInput" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-upload"></i> Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Document Type</label>
                        <p id="docTypeLabel" class="fw-bold text-primary"></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select File <span class="text-danger">*</span></label>
                        <input type="file" name="document_file" class="form-control" accept="image/*,application/pdf" required>
                        <small class="text-muted">Max 5MB. Allowed: JPG, PNG, GIF, WebP, PDF</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <input type="text" name="document_notes" class="form-control" placeholder="Additional notes about this document">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <i class="fa-solid fa-upload"></i> Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openUploadModal(docType, docLabel) {
    document.getElementById('docTypeInput').value = docType;
    document.getElementById('docTypeLabel').textContent = docLabel;
    
    var modal = new bootstrap.Modal(document.getElementById('uploadModal'));
    modal.show();
}

// Prevent double submission
document.addEventListener('DOMContentLoaded', function() {
    const uploadForm = document.querySelector('#uploadModal form');
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            const btn = document.getElementById('uploadBtn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...';
            btn.disabled = true;
        });
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>