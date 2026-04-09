<?php
/**
 * London Labels - Cart State API
 * Returns the current cart count for UI synchronization (e.g. back button handling)
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');

// Ensure session is started if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo json_encode([
    'ok' => true,
    'count' => cart_item_count()
]);
