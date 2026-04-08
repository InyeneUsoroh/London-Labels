<?php
/**
 * London Labels - Bootstrap
 * Centralized runtime initialization.
 */

// Start output buffering immediately — prevents "headers already sent" from
// stray whitespace or BOM in any included file before session_start().
if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/components.php';
require_once __DIR__ . '/includes/layouts.php';
require_once __DIR__ . '/db_functions.php';

