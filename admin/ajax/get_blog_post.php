<?php
// Start output buffering
ob_start();

// Include header - path from admin/ajax to includes
require_once '../../includes/header.php';

// Clear any output
ob_clean();

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if (($_SESSION['user_role'] ?? '') !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Admin only']);
    exit;
}

$post_id = intval($_GET['id'] ?? 0);

if ($post_id == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

// Get post details
$sql = "SELECT b.*, u.name as author_name, c.name as category_name 
        FROM blog_posts b
        JOIN users u ON u.id = b.author_id
        LEFT JOIN blog_categories c ON c.id = b.category_id
        WHERE b.id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $mysqli->error]);
    exit;
}
$stmt->bind_param('i', $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    exit;
}

// Get comments for this post
$comments = [];
$comments_sql = "SELECT c.*, u.name as user_name 
                 FROM blog_comments c
                 LEFT JOIN users u ON u.id = c.user_id
                 WHERE c.post_id = ?
                 ORDER BY c.created_at DESC";
$comments_stmt = $mysqli->prepare($comments_sql);
if ($comments_stmt) {
    $comments_stmt->bind_param('i', $post_id);
    $comments_stmt->execute();
    $result = $comments_stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $comments_stmt->close();
}

echo json_encode([
    'success' => true,
    'post' => $post,
    'comments' => $comments
]);
?>