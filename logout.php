<?php
// Start session only if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout attempt for debugging
error_log("Logout attempt by user: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'unknown'));

// Clear all session variables
$_SESSION = array();

// Delete the session cookie if it exists
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Start a new clean session for the logout message
session_start();
$_SESSION['logout_message'] = 'You have been successfully logged out.';

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Log successful logout
error_log("Logout successful, redirecting to login");

// Redirect to login page
header("Location: login.php");
exit();
?>