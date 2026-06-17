<?php
// functions.php - Complete functions file for Multi-Vendor Marketplace

// ============================================
// SECURITY & VALIDATION FUNCTIONS
// ============================================

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_validate($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize($value) {
    if ($value === null) return '';
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function sanitize_array($array) {
    if (!is_array($array)) return sanitize($array);
    return array_map('sanitize', $array);
}

// ============================================
// SESSION & AUTHENTICATION FUNCTIONS
// ============================================

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user() {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'customer'
        ];
    }
    return [
        'id' => null,
        'role' => 'guest',
        'email' => null,
        'name' => null
    ];
}

function require_login() {
    if (!is_logged_in()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please login to continue.']);
            exit;
        }
        flash('Please login to access this page.', 'danger');
        redirect('login.php');
        exit;
    }
}

function require_role($role) {
    require_login();
    $user = current_user();
    if ($user['role'] !== $role) {
        flash('You do not have permission to access this page.', 'danger');
        redirect('index.php');
        exit;
    }
}

function require_admin() {
    require_role('admin');
}

function require_seller() {
    require_role('seller');
}

// ============================================
// FLASH MESSAGES
// ============================================

function flash($message = null, $type = 'success') {
    if ($message === null) {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_display() {
    $flash = flash();
    if ($flash && isset($flash['message'])) {
        return $flash;
    }
    return null;
}

// ============================================
// REDIRECT & URL FUNCTIONS
// ============================================

function redirect($url) {
    // Clean any existing output buffers
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Build full URL
    $fullUrl = rtrim(BASE_URL, '/') . '/' . ltrim($url, '/');
    
    // Try to send header
    if (!headers_sent()) {
        header('Location: ' . $fullUrl);
        exit;
    }
    
    // Fallback JavaScript redirect
    echo '<script>window.location.href="' . $fullUrl . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $fullUrl . '"></noscript>';
    exit;
}

function build_url($path) {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

// ============================================
// DATABASE QUERY FUNCTIONS
// ============================================

function get_user_by_email($mysqli, $email) {
    if (!$email) return null;
    
    $sql = 'SELECT * FROM users WHERE email = ? LIMIT 1';
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_user_by_id($mysqli, $id) {
    if (!$id) return null;
    
    $sql = 'SELECT * FROM users WHERE id = ? LIMIT 1';
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_seller_by_user_id($mysqli, $user_id) {
    if (!$user_id) return null;
    
    $sql = 'SELECT * FROM sellers WHERE user_id = ? LIMIT 1';
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_seller_by_id($mysqli, $seller_id) {
    if (!$seller_id) return null;
    
    $sql = 'SELECT s.*, u.name as owner_name, u.email as owner_email 
            FROM sellers s 
            JOIN users u ON u.id = s.user_id 
            WHERE s.id = ? LIMIT 1';
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return null;
    
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// ============================================
// PRODUCT FUNCTIONS
// ============================================

function get_products($mysqli, $limit = 8, $offset = 0, $filters = []) {
    $sql = "SELECT p.*, c.name as category_name, s.shop_name 
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN sellers s ON s.id = p.seller_id
            WHERE p.status = 'approved'";
    
    // Apply filters
    if (!empty($filters['category'])) {
        $sql .= " AND c.slug = '" . $mysqli->real_escape_string($filters['category']) . "'";
    }
    if (!empty($filters['search'])) {
        $sql .= " AND (p.name LIKE '%" . $mysqli->real_escape_string($filters['search']) . "%' 
                 OR p.short_description LIKE '%" . $mysqli->real_escape_string($filters['search']) . "%')";
    }
    if (!empty($filters['min_price'])) {
        $sql .= " AND p.price >= " . floatval($filters['min_price']);
    }
    if (!empty($filters['max_price'])) {
        $sql .= " AND p.price <= " . floatval($filters['max_price']);
    }
    if (!empty($filters['seller_id'])) {
        $sql .= " AND p.seller_id = " . intval($filters['seller_id']);
    }
    
    $sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    return $stmt->get_result();
}

function get_products_count($mysqli, $filters = []) {
    $sql = "SELECT COUNT(*) as total FROM products p WHERE p.status = 'approved'";
    
    if (!empty($filters['category'])) {
        $sql .= " AND p.category_id = (SELECT id FROM categories WHERE slug = '" . $mysqli->real_escape_string($filters['category']) . "')";
    }
    if (!empty($filters['search'])) {
        $sql .= " AND (p.name LIKE '%" . $mysqli->real_escape_string($filters['search']) . "%')";
    }
    if (!empty($filters['seller_id'])) {
        $sql .= " AND p.seller_id = " . intval($filters['seller_id']);
    }
    
    $result = $mysqli->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function get_product_by_id($mysqli, $id) {
    if (!$id) return null;
    
    $sql = 'SELECT p.*, c.name as category_name, c.slug as category_slug, s.shop_name, s.id as seller_id, u.name as seller_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN sellers s ON p.seller_id = s.id
            LEFT JOIN users u ON s.user_id = u.id
            WHERE p.id = ? AND p.status = "approved" LIMIT 1';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function get_product_images($mysqli, $product_id) {
    if (!$product_id) return new mysqli_result();
    
    $sql = 'SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC';
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    return $stmt->get_result();
}

function get_featured_products($mysqli, $limit = 8) {
    $sql = "SELECT p.*, c.name as category_name, s.shop_name 
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN sellers s ON s.id = p.seller_id
            WHERE p.status = 'approved' AND p.rating >= 4.0
            ORDER BY p.rating DESC, p.created_at DESC
            LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================================
// CATEGORY FUNCTIONS
// ============================================

function get_categories($mysqli) {
    $sql = "SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id AND p.status = 'approved'
            GROUP BY c.id
            ORDER BY c.name ASC";
    return $mysqli->query($sql);
}

function get_category_by_slug($mysqli, $slug) {
    if (!$slug) return null;
    
    $sql = "SELECT * FROM categories WHERE slug = ? LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function get_featured_categories($mysqli, $limit = 4) {
    $sql = "SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON p.category_id = c.id AND p.status = 'approved'
            GROUP BY c.id 
            ORDER BY product_count DESC 
            LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================================
// SELLER FUNCTIONS
// ============================================

function get_top_sellers($mysqli, $limit = 4) {
    $sql = "SELECT s.id, s.shop_name, s.shop_logo, u.name as owner_name, 
            COUNT(DISTINCT p.id) as product_count,
            COALESCE(ROUND(AVG(r.rating), 1), 0) as avg_rating
            FROM sellers s
            JOIN users u ON u.id = s.user_id
            LEFT JOIN products p ON p.seller_id = s.id AND p.status = 'approved'
            LEFT JOIN reviews r ON r.product_id = p.id
            WHERE s.status = 'verified'
            GROUP BY s.id
            ORDER BY product_count DESC
            LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function get_seller_products($mysqli, $seller_id, $limit = 10, $offset = 0) {
    if (!$seller_id) return new mysqli_result();
    
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.seller_id = ? AND p.status = 'approved'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $seller_id, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result();
}

function is_seller_subscription_active($mysqli, $seller_id) {
    if (!$seller_id) return false;
    
    $sql = "SELECT * FROM subscriptions 
            WHERE seller_id = ? AND status = 'active' AND expires_at >= CURDATE()
            ORDER BY expires_at DESC LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// ============================================
// CART FUNCTIONS
// ============================================

function get_cart_items($mysqli, $user_id) {
    if (!$user_id) return new mysqli_result();
    
    $sql = "SELECT c.*, p.name, p.price, p.slug, p.stock,
            (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
            FROM carts c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function get_cart_total($mysqli, $user_id) {
    if (!$user_id) return 0;
    
    $sql = "SELECT SUM(c.quantity * p.price) as total 
            FROM carts c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

function get_cart_count($mysqli, $user_id) {
    if (!$user_id) return 0;
    
    $sql = "SELECT SUM(quantity) as count FROM carts WHERE user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return intval($row['count'] ?? 0);
}

function add_to_cart($mysqli, $user_id, $product_id, $quantity = 1) {
    if (!$user_id || !$product_id) return false;
    
    // Check if product already in cart
    $sql = "SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    
    if ($existing) {
        // Update quantity
        $new_quantity = $existing['quantity'] + $quantity;
        $sql = "UPDATE carts SET quantity = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ii', $new_quantity, $existing['id']);
    } else {
        // Insert new
        $sql = "INSERT INTO carts (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iii', $user_id, $product_id, $quantity);
    }
    
    return $stmt->execute();
}

function remove_from_cart($mysqli, $user_id, $product_id) {
    if (!$user_id || !$product_id) return false;
    
    $sql = "DELETE FROM carts WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $product_id);
    return $stmt->execute();
}

function clear_cart($mysqli, $user_id) {
    if (!$user_id) return false;
    
    $sql = "DELETE FROM carts WHERE user_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    return $stmt->execute();
}

// ============================================
// ORDER FUNCTIONS
// ============================================

function generate_unique_order_number($mysqli) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $max_attempts = 10;
    $attempt = 0;
    
    do {
        $random = '';
        for ($i = 0; $i < 6; $i++) {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
        $order_number = 'ORD-' . $random; // 10 characters total
        
        $check_sql = "SELECT id FROM orders WHERE order_number = ?";
        $check_stmt = $mysqli->prepare($check_sql);
        $check_stmt->bind_param('s', $order_number);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $exists = $result->num_rows > 0;
        $check_stmt->close();
        
        $attempt++;
        if ($attempt >= $max_attempts) {
            // Fallback
            $order_number = 'ORD-' . date('ymd') . chr(rand(65, 90));
            $order_number = substr($order_number, 0, 10);
            break;
        }
    } while ($exists);
    
    return $order_number;
}

function create_order($mysqli, $user_id, $seller_id, $total_amount, $payment_method, $shipping_address) {
    $order_number = generate_order_number();
    $sql = "INSERT INTO orders (user_id, seller_id, order_number, total_amount, payment_method, shipping_address, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iisdss', $user_id, $seller_id, $order_number, $total_amount, $payment_method, $shipping_address);
    $stmt->execute();
    return $mysqli->insert_id;
}

function get_user_orders($mysqli, $user_id, $limit = 10, $offset = 0) {
    if (!$user_id) return new mysqli_result();
    
    $sql = "SELECT o.*, s.shop_name 
            FROM orders o
            JOIN sellers s ON s.id = o.seller_id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iii', $user_id, $limit, $offset);
    $stmt->execute();
    return $stmt->get_result();
}

function get_order_items($mysqli, $order_id) {
    if (!$order_id) return new mysqli_result();
    
    $sql = "SELECT oi.*, p.name, p.slug,
            (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    return $stmt->get_result();
}

// ============================================
// REVIEW FUNCTIONS
// ============================================

function get_product_reviews($mysqli, $product_id, $limit = 5) {
    if (!$product_id) return new mysqli_result();
    
    $sql = "SELECT r.*, u.name as user_name 
            FROM reviews r
            JOIN users u ON u.id = r.user_id
            WHERE r.product_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
            LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $product_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function get_average_rating($mysqli, $product_id) {
    if (!$product_id) return ['avg_rating' => 0, 'total_reviews' => 0];
    
    $sql = "SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_reviews 
            FROM reviews 
            WHERE product_id = ? AND status = 'approved'";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function add_review($mysqli, $user_id, $product_id, $rating, $comment) {
    $sql = "INSERT INTO reviews (user_id, product_id, rating, comment, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('iiis', $user_id, $product_id, $rating, $comment);
    return $stmt->execute();
}

// ============================================
// WISHLIST FUNCTIONS
// ============================================

function get_wishlist_items($mysqli, $user_id) {
    if (!$user_id) return new mysqli_result();
    
    $sql = "SELECT w.*, p.name, p.price, p.slug, p.stock,
            (SELECT filename FROM product_images WHERE product_id = p.id LIMIT 1) as image
            FROM wishlists w
            JOIN products p ON p.id = w.product_id
            WHERE w.user_id = ? AND p.status = 'approved'
            ORDER BY w.created_at DESC";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function add_to_wishlist($mysqli, $user_id, $product_id) {
    if (!$user_id || !$product_id) return false;
    
    // Check if already exists
    $sql = "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false;
    }
    
    $sql = "INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $product_id);
    return $stmt->execute();
}

function remove_from_wishlist($mysqli, $user_id, $product_id) {
    if (!$user_id || !$product_id) return false;
    
    $sql = "DELETE FROM wishlists WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $product_id);
    return $stmt->execute();
}

function is_in_wishlist($mysqli, $user_id, $product_id) {
    if (!$user_id || !$product_id) return false;
    
    $sql = "SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

// ============================================
// AI FUNCTIONS
// ============================================

function ai_compare_products($products) {
    if (empty($products)) {
        return "No products selected for comparison. Please select at least 2 products to compare.";
    }
    
    $summary = [];
    foreach ($products as $product) {
        $price_advice = "";
        if ($product['price'] < 1000) {
            $price_advice = "Budget-friendly option";
        } elseif ($product['price'] < 5000) {
            $price_advice = "Mid-range pricing with good value";
        } else {
            $price_advice = "Premium product with advanced features";
        }
        
        $stock_status = $product['stock'] > 10 ? "Well-stocked" : ($product['stock'] > 0 ? "Limited stock" : "Out of stock");
        
        $summary[] = sprintf(
            "• %s: %s (KSH %s) - Rating: %s/5 - %s - %s",
            $product['name'],
            $product['short_description'] ?? 'No description',
            number_format($product['price']),
            number_format($product['rating'] ?? 0, 1),
            $stock_status,
            $price_advice
        );
    }
    
    $recommendation = "\n\n🎯 Recommendation: ";
    $best_price = min(array_column($products, 'price'));
    $best_rating = max(array_column($products, 'rating'));
    
    $recommendation .= "Based on price and ratings, ";
    foreach ($products as $product) {
        if ($product['price'] == $best_price && ($product['rating'] ?? 0) == $best_rating) {
            $recommendation .= "{$product['name']} offers the best balance of price and quality.";
            break;
        } elseif ($product['price'] == $best_price) {
            $recommendation .= "{$product['name']} is the most affordable option.";
            break;
        } elseif (($product['rating'] ?? 0) == $best_rating) {
            $recommendation .= "{$product['name']} has the highest customer satisfaction rating.";
            break;
        }
    }
    
    return implode("\n", $summary) . $recommendation;
}

function ai_answer_question($question, $context = null) {
    $question = strtolower(trim($question));
    
    $responses = [
        'best' => "To find the best product, I recommend:\n1. Check products with 4+ star ratings\n2. Read recent customer reviews\n3. Compare prices across different sellers\n4. Look for verified purchase reviews",
        'recommend' => "Please tell me:\n• Your budget range\n• Preferred category\n• Any specific features you need",
        'battery' => "🔋 Look for 4000mAh+ battery, fast charging support, and positive battery life reviews.",
        'price' => "💰 Set a realistic budget, check flash sales, compare at least 3 sellers, and look for coupon codes.",
        'shipping' => "🚚 Free shipping on orders over KSH 5,000. Delivery takes 2-5 business days.",
        'return' => "🔄 7-day return window for most products. Items must be unused with original packaging.",
        'payment' => "💳 We accept M-Pesa, Credit/Debit Cards, PayPal, and Bank Transfer.",
        'seller' => "🏪 Register as customer, go to dashboard, click 'Become a Seller', submit documents for verification."
    ];
    
    foreach ($responses as $keyword => $response) {
        if (strpos($question, $keyword) !== false) {
            return $response;
        }
    }
    
    if ($context && isset($context['product_name'])) {
        return "About {$context['product_name']}:\n" . ai_analyze_product($context);
    }
    
    return "🤖 I'm your AI shopping assistant! Ask me about products, prices, shipping, or become a seller.";
}

function ai_analyze_product($product) {
    $analysis = [];
    
    if ($product['price'] < 1000) {
        $analysis[] = "✓ Budget-friendly option";
    } elseif ($product['price'] < 5000) {
        $analysis[] = "✓ Mid-range pricing - good value";
    } else {
        $analysis[] = "✓ Premium product with advanced features";
    }
    
    if ($product['stock'] > 50) {
        $analysis[] = "✓ Well-stocked";
    } elseif ($product['stock'] > 10) {
        $analysis[] = "⚠️ Limited stock available";
    } elseif ($product['stock'] > 0) {
        $analysis[] = "⚠️ Low stock - order soon!";
    } else {
        $analysis[] = "❌ Currently out of stock";
    }
    
    if (($product['rating'] ?? 0) >= 4.5) {
        $analysis[] = "✓ Excellent customer rating!";
    } elseif (($product['rating'] ?? 0) >= 4.0) {
        $analysis[] = "✓ Good customer satisfaction";
    } else {
        $analysis[] = "⚠️ Check customer reviews";
    }
    
    return implode("\n", $analysis);
}

// ============================================
// NOTIFICATION FUNCTIONS
// ============================================

function create_notification($mysqli, $user_id, $type, $title, $message) {
    if (!$user_id) return false;
    
    $sql = "INSERT INTO notifications (user_id, type, title, message, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('isss', $user_id, $type, $title, $message);
    return $stmt->execute();
}

function get_user_notifications($mysqli, $user_id, $limit = 10) {
    if (!$user_id) return new mysqli_result();
    
    $sql = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ii', $user_id, $limit);
    $stmt->execute();
    return $stmt->get_result();
}

function mark_notification_read($mysqli, $notification_id) {
    if (!$notification_id) return false;
    
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $notification_id);
    return $stmt->execute();
}

// ============================================
// OFFER/COUPON FUNCTIONS
// ============================================

function get_active_offers($mysqli) {
    $sql = "SELECT * FROM offers WHERE active = 1 AND expires_at >= CURDATE() ORDER BY created_at DESC";
    return $mysqli->query($sql);
}

function validate_coupon($mysqli, $code) {
    if (!$code) return null;
    
    $sql = "SELECT * FROM offers WHERE code = ? AND active = 1 AND expires_at >= CURDATE() LIMIT 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $code);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

// ============================================
// FILE UPLOAD FUNCTIONS
// ============================================

function upload_image($file, $subfolder = 'products') {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['success' => false, 'message' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowedTypes, true)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP'];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Max 5MB'];
    }
    
    // Generate unique filename
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('img_', true) . '.' . $extension;
    
    // Create upload directory if it doesn't exist
    $uploadPath = UPLOAD_DIR . '/' . $subfolder;
    if (!file_exists($uploadPath)) {
        if (!mkdir($uploadPath, 0777, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory'];
        }
    }
    
    // Move file
    $targetPath = $uploadPath . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to save file. Check folder permissions.'];
    }
    
    // Return ONLY the filename, not the full path
    return ['success' => true, 'filename' => $filename];
}

// ============================================
// FORMATTING FUNCTIONS
// ============================================

function format_price($price) {
    return CURRENCY_SYMBOL . ' ' . number_format($price, 2);
}

function format_date($date, $format = 'M d, Y') {
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

function time_ago($datetime) {
    if (!$datetime) return 'Unknown';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M d, Y', $timestamp);
}

// ============================================
// VALIDATION FUNCTIONS
// ============================================

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    return preg_match('/^[0-9+\-\s()]{10,15}$/', $phone);
}

function validate_password($password) {
    return strlen($password) >= 6;
}

// ============================================
// DASHBOARD STATS FUNCTIONS
// ============================================

function get_admin_stats($mysqli) {
    $stats = [];
    
    $result = $mysqli->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    $result = $mysqli->query("SELECT COUNT(*) as count FROM sellers");
    $stats['total_sellers'] = $result->fetch_assoc()['count'];
    
    $result = $mysqli->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $result->fetch_assoc()['count'];
    
    $result = $mysqli->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $result->fetch_assoc()['count'];
    
    $result = $mysqli->query("SELECT SUM(total_amount) as total FROM orders WHERE status != 'cancelled'");
    $stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
    
    $result = $mysqli->query("SELECT COUNT(*) as count FROM sellers WHERE status = 'pending'");
    $stats['pending_sellers'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

function get_seller_stats($mysqli, $seller_id) {
    if (!$seller_id) return [];
    
    $stats = [];
    
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ?");
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $stats['total_products'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM orders WHERE seller_id = ?");
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $stats['total_orders'] = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt = $mysqli->prepare("SELECT SUM(total_amount) as total FROM orders WHERE seller_id = ? AND status != 'cancelled'");
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $stats['total_earnings'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM products WHERE seller_id = ? AND stock < 10 AND status = 'approved'");
    $stmt->bind_param('i', $seller_id);
    $stmt->execute();
    $stats['low_stock'] = $stmt->get_result()->fetch_assoc()['count'];
    
    return $stats;
}
?>