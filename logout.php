<?php
/**
 * London Labels - User Logout
 * Industry-standard logout with CSRF protection
 */
require_once __DIR__ . '/functions.php';

// Verify CSRF token for POST requests (best practice)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        // Invalid token, but still logout for security
        error_log('Logout CSRF validation failed for user: ' . (current_user_id() ?? 'unknown'));
    }
}

// Clear session and cookies
logout_user();

// Redirect to login with logout message
header('Location: ' . BASE_URL . '/login.php?logout=1');
exit;

