<?php
/**
 * User Logout Handler
 */
require_once __DIR__ . '/includes/auth.php';

// Unset all session variables
$_SESSION = array();

// If session cookie is used, delete it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session to pass logout notification flash message
session_start();
set_flash('info', 'You have been logged out successfully.');

// Redirect to home page
header("Location: index.php");
exit;
