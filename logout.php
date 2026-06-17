<?php
// logout.php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear cart session
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}

// Clear wishlist session
if (isset($_SESSION['wishlist'])) {
    unset($_SESSION['wishlist']);
}

// Destroy session
session_destroy();

// Redirect to home page
header('Location: index.php?logout=success');
exit();
?>