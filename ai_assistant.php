<?php
$page_title = 'AI Shopping Assistant';
require_once 'includes/header.php';

// ============================================
// GEMINI AI CLASS
// ============================================
class GeminiAI {
    private $api_key;
    private $model;
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/';
    
    public function __construct() {
        $this->api_key = GEMINI_API_KEY;
        $this->model = GEMINI_MODEL;
    }
    
    public function ask($question, $context = null) {
        $prompt = $this->buildPrompt($question, $context);
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
                'topP' => 1,
                'topK' => 1
            ]
        ];
        $url = $this->api_url . $this->model . ':generateContent?key=' . $this->api_key;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $this->cleanResponse($result['candidates'][0]['content']['parts'][0]['text']);
            }
        }
        if ($httpCode === 429) return $this->cleanResponse("I'm currently experiencing high demand. Please try again in a moment.");
        if ($httpCode === 0) return $this->cleanResponse("Network error. Please check your internet connection.");
        return $this->cleanResponse("Sorry, I encountered an error (HTTP $httpCode). Please try again later.");
    }
    
    private function cleanResponse($text) {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/\s+([.,!?;:])/', '$1', $text);
        $text = preg_replace('/([.,!?;:])\s+/', '$1 ', $text);
        $text = preg_replace('/^\s+/', '', $text);
        $text = preg_replace('/\s+$/', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        $text = preg_replace('/<br>\s+/', '<br>', $text);
        $text = preg_replace('/\s+<br>/', '<br>', $text);
        return trim($text);
    }
    
    private function buildPrompt($question, $context) {
        $prompt = "You are an AI shopping assistant for " . SITE_NAME . ". 
        You can see what's on the current page and help the user with what they're viewing.
        
        Platform Information:
        - Website: " . SITE_NAME . "
        - Email: " . ADMIN_EMAIL . "
        - Currency: KSH (Kenyan Shilling)
        
        Current Page Context:
        " . $context . "
        
        Rules:
        1. Be helpful, friendly, and professional
        2. Reference products currently on the page if relevant
        3. Provide accurate product recommendations
        4. Format responses with proper HTML for display (use <br> for line breaks)
        5. If you don't know something, say so politely
        6. Keep responses concise and easy to read
        7. Remove unnecessary spaces in your response
        
        User Question: " . $question;
        return $prompt;
    }
}

// ============================================
// GET FRONTEND CONTEXT
// ============================================
function get_frontend_context($mysqli) {
    $context = [];
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Page type
    $page_types = [
        'index.php' => 'Homepage - Shows featured products and categories',
        'shop.php' => 'Shop Page - Browse all products',
        'product.php' => 'Product Details Page - View specific product',
        'cart.php' => 'Shopping Cart Page',
        'checkout.php' => 'Checkout Page',
        'orders.php' => 'My Orders Page',
        'wishlist.php' => 'Wishlist Page',
        'ai_assistant.php' => 'AI Assistant Page'
    ];
    $context[] = "Current Page: " . ($page_types[$current_page] ?? $current_page);
    
    // Get products on the current page
    $product_ids = [];
    
    // If on product page, get that specific product
    if ($current_page === 'product.php' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "SELECT name, price, short_description, rating FROM products WHERE id = $id AND status = 'approved'";
        $result = $mysqli->query($sql);
        if ($row = $result->fetch_assoc()) {
            $context[] = "Current Product: " . $row['name'] . " (KSH " . number_format($row['price']) . ") - Rating: " . ($row['rating'] ?? 0) . "/5";
        }
    }
    
    // Get category being viewed
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $cat_id = intval($_GET['category']);
        $sql = "SELECT name FROM categories WHERE id = $cat_id";
        $result = $mysqli->query($sql);
        if ($row = $result->fetch_assoc()) {
            $context[] = "Category: " . $row['name'];
        }
    }
    
    // Get search query
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $context[] = "Search Query: " . sanitize($_GET['search']);
    }
    
    // Get products in cart (if user is logged in)
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT COUNT(*) as count FROM carts WHERE user_id = $user_id";
        $result = $mysqli->query($sql);
        $count = $result->fetch_assoc()['count'];
        if ($count > 0) {
            $context[] = "Cart Items: $count items in cart";
        }
    }
    
    // Get user info (if logged in)
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT name, email FROM users WHERE id = $user_id";
        $result = $mysqli->query($sql);
        if ($row = $result->fetch_assoc()) {
            $context[] = "User: " . $row['name'] . " (" . $row['email'] . ")";
        }
    }
    
    // Get system stats
    $stats = [];
    $result = $mysqli->query("SELECT COUNT(*) as count FROM products WHERE status = 'approved'");
    $stats['products'] = $result->fetch_assoc()['count'];
    $result = $mysqli->query("SELECT COUNT(*) as count FROM sellers WHERE status = 'verified'");
    $stats['sellers'] = $result->fetch_assoc()['count'];
    $context[] = "Total Products: " . number_format($stats['products']);
    $context[] = "Total Sellers: " . number_format($stats['sellers']);
    
    return implode("\n", $context);
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
$response = null;
$asked_question = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? '')) {
        flash('Invalid security token.', 'danger');
        redirect('ai_assistant.php');
    }
    $question = sanitize($_POST['question'] ?? '');
    $asked_question = $question;
    if ($question) {
        // Get frontend context
        $frontend_context = get_frontend_context($mysqli);
        
        // Get product database context
        $product_context = get_product_context($mysqli, $question);
        
        // Combine contexts
        $full_context = "=== Frontend Context ===\n" . $frontend_context . "\n\n=== Product Database ===\n" . $product_context;
        
        $gemini = new GeminiAI();
        $response = $gemini->ask($question, $full_context);
        
        $log_sql = "INSERT INTO ai_logs (user_id, question, response, created_at) VALUES (?, ?, ?, NOW())";
        $log_stmt = $mysqli->prepare($log_sql);
        $user_id = $_SESSION['user_id'] ?? null;
        $log_stmt->bind_param('iss', $user_id, $question, $response);
        $log_stmt->execute();
    }
}

function get_product_context($mysqli, $query = null) {
    $context = [];
    $sql = "SELECT p.name, p.price, p.short_description, c.name as category, s.shop_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            LEFT JOIN sellers s ON p.seller_id = s.id 
            WHERE p.status = 'approved'";
    if ($query && strlen($query) > 2) {
        $search = '%' . $query . '%';
        $sql .= " AND (p.name LIKE ? OR p.short_description LIKE ? OR c.name LIKE ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sss', $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $sql .= " ORDER BY p.rating DESC LIMIT 10";
        $result = $mysqli->query($sql);
    }
    if (!$result) return "No products found.";
    while ($row = $result->fetch_assoc()) {
        $context[] = "- " . $row['name'] . " (KSH " . number_format($row['price']) . ") - " . $row['category'] . " - Seller: " . $row['shop_name'];
    }
    return implode("\n", $context);
}
?>
<style>
.ai-header{background:linear-gradient(135deg,#1e293b 0%,#0f172a 100%);padding:60px 0 40px;border-radius:0 0 30px 30px;margin-bottom:40px;position:relative;overflow:hidden}
.ai-header::before{content:'';position:absolute;top:-50%;right:-20%;width:500px;height:500px;background:radial-gradient(circle,rgba(37,99,235,0.1) 0%,transparent 70%);border-radius:50%}
.ai-header .ai-title{color:#fff;font-size:2.5rem;font-weight:800;position:relative;z-index:1}
.ai-header .ai-subtitle{color:rgba(255,255,255,0.7);font-size:1.1rem;position:relative;z-index:1}
.ai-badge{display:inline-block;background:rgba(37,99,235,0.3);color:#60a5fa;padding:4px 16px;border-radius:50px;font-size:.8rem;font-weight:600;border:1px solid rgba(37,99,235,0.3);margin-bottom:12px}
.ai-chat-container{background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden;min-height:500px}
.ai-chat-header{background:linear-gradient(135deg,#2563eb,#1d4ed8);padding:20px 25px;color:#fff;display:flex;align-items:center;gap:12px}
.ai-chat-header .ai-avatar{width:45px;height:45px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.5rem}
.ai-chat-header .ai-status{font-size:.75rem;opacity:.8;display:flex;align-items:center;gap:6px}
.ai-chat-header .ai-status .dot{width:8px;height:8px;background:#10b981;border-radius:50%;display:inline-block;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}
.ai-chat-body{padding:30px;min-height:300px}
.ai-response{background:#f8fafc;border-radius:16px;padding:20px 25px;border-left:4px solid #2563eb;animation:fadeIn .5s ease;line-height:1.8}
.ai-response h4{color:#1f2937;margin-bottom:12px;display:flex;align-items:center;gap:10px}
.ai-response h4 i{color:#2563eb}
.ai-response p{color:#374151;margin-bottom:8px}
.ai-response a{color:#2563eb;text-decoration:none;font-weight:600}
.ai-response a:hover{text-decoration:underline}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
.ai-form-container{background:#f8fafc;padding:20px 25px;border-top:1px solid #e5e7eb}
.ai-form-container textarea{border-radius:12px;border:1px solid #e5e7eb;resize:vertical;min-height:80px;font-size:.95rem}
.ai-form-container textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,0.1)}
.ai-form-container .btn-send{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;padding:12px 30px;border-radius:12px;font-weight:600;transition:all .3s ease;height:100%;min-height:60px}
.ai-form-container .btn-send:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(37,99,235,0.3)}
.ai-form-container .btn-send:disabled{opacity:.5;cursor:not-allowed;transform:none!important}
.quick-suggestions{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.quick-suggestion{background:#f1f5f9;padding:6px 16px;border-radius:50px;font-size:.8rem;cursor:pointer;transition:all .3s ease;border:1px solid transparent;color:#374151}
.quick-suggestion:hover{background:#2563eb;color:#fff;border-color:#2563eb;transform:translateY(-2px)}
.ai-sidebar{position:sticky;top:100px}
.ai-sidebar .card{border-radius:16px;border:none;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow:hidden}
.ai-sidebar .card-header{background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;padding:15px 20px;font-weight:600;border:none}
.ai-sidebar .card-body{padding:20px}
.ai-sidebar .tip-item{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9}
.ai-sidebar .tip-item:last-child{border-bottom:none}
.ai-sidebar .tip-item .tip-icon{width:30px;height:30px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#2563eb;flex-shrink:0}
.ai-sidebar .tip-item .tip-text{font-size:.9rem;color:#374151}
.ai-response .context-info{background:#e8f0fe;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.8rem;color:#1a56db}
@media(max-width:768px){.ai-header .ai-title{font-size:1.8rem}.ai-chat-body{padding:20px}.quick-suggestions{justify-content:center}.ai-sidebar{position:static;margin-top:20px}.ai-form-container .btn-send{min-height:50px}}
</style>
<div class="ai-header">
    <div class="container">
        <div class="ai-badge"><i class="fa-solid fa-sparkles"></i> AI-Powered Assistant</div>
        <h1 class="ai-title"><i class="fa-solid fa-robot"></i> AI Shopping Assistant</h1>
        <p class="ai-subtitle">I can see what's on this page. Ask me about the products you're viewing!</p>
    </div>
</div>
<div class="container mb-5">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="ai-chat-container">
                <div class="ai-chat-header">
                    <div class="ai-avatar"><i class="fa-solid fa-robot"></i></div>
                    <div>
                        <div style="font-weight:600;">AI Assistant</div>
                        <div class="ai-status"><span class="dot"></span> Online • I can see this page!</div>
                    </div>
                </div>
                <div class="ai-chat-body" id="aiChatBody">
                    <div class="quick-suggestions">
                        <span class="quick-suggestion" onclick="askQuestion('Tell me about what I see on this page')">👁️ What's on this page?</span>
                        <span class="quick-suggestion" onclick="askQuestion('Show me laptops under 50,000 KSH')">💻 Laptops under 50k</span>
                        <span class="quick-suggestion" onclick="askQuestion('What is the shipping policy?')">🚚 Shipping policy</span>
                        <span class="quick-suggestion" onclick="askQuestion('How do I become a seller?')">🏪 Become a seller</span>
                        <span class="quick-suggestion" onclick="askQuestion('Best phone under 30,000 KSH')">📱 Best phone under 30k</span>
                    </div>
                    <?php if ($response): ?>
                    <div class="ai-response" id="aiResponse">
                        <h4><i class="fa-regular fa-message"></i> <?= htmlspecialchars($asked_question) ?></h4>
                        <?= nl2br($response) ?>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center;padding:40px 20px;color:#6b7280;">
                        <i class="fa-solid fa-robot" style="font-size:4rem;color:#2563eb;opacity:.3;margin-bottom:16px;"></i>
                        <h4 style="color:#1f2937;">I can see what you're viewing!</h4>
                        <p>Ask me about the products on this page, or any other question about the platform.</p>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ai-form-container">
                    <form method="post" id="aiForm">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                        <div class="row g-2">
                            <div class="col-10">
                                <textarea name="question" id="aiQuestion" class="form-control" rows="2" placeholder="Ask me about anything on this page..." required><?= isset($_POST['question']) ? sanitize($_POST['question']) : '' ?></textarea>
                            </div>
                            <div class="col-2">
                                <button type="submit" class="btn-send w-100" id="sendBtn"><i class="fa-solid fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="ai-sidebar">
                <div class="card">
                    <div class="card-header"><i class="fa-regular fa-lightbulb"></i> I Can See...</div>
                    <div class="card-body">
                        <div class="tip-item">
                            <div class="tip-icon"><i class="fa-solid fa-eye"></i></div>
                            <div class="tip-text">The current page you're on</div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon"><i class="fa-solid fa-box"></i></div>
                            <div class="tip-text">Products displayed on this page</div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                            <div class="tip-text">Your search queries</div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                            <div class="tip-text">Items in your cart</div>
                        </div>
                        <div class="tip-item">
                            <div class="tip-icon"><i class="fa-solid fa-user"></i></div>
                            <div class="tip-text">Your logged-in status</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function askQuestion(q){document.getElementById('aiQuestion').value=q;document.getElementById('aiForm').submit();}
document.getElementById('aiQuestion')?.addEventListener('keydown',function(e){if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();document.getElementById('aiForm').submit();}});
document.getElementById('aiForm')?.addEventListener('submit',function(){const b=document.getElementById('sendBtn');b.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i>';b.disabled=true;});
console.log('🤖 AI Assistant - I can see your frontend!');
</script>
<?php require_once 'includes/footer.php'; ?>