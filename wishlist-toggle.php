<?php
/**
 * Wishlist toggle endpoint — POST only, JSON response
 */
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthenticated']);
    exit;
}

if (!verify_csrf($_POST['csrf'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$product_id = (int)($_POST['product_id'] ?? 0);
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid product']);
    exit;
}

$action = toggle_wishlist((int)current_user_id(), $product_id);
echo json_encode(['action' => $action]);
