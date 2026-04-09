<?php
/**
 * London Labels - DB Migration Tool
 * Use this once to update the production database for Super Admin support.
 */
require_once __DIR__ . '/db.php';

// Security check: Only allow this if a specific secret is provided
// This prevents unauthorized users from running migrations.
$secret = 'll_repair_2026';
if (($_GET['secret'] ?? '') !== $secret) {
    die('Unauthorized access. Please provide the correct secret.');
}

$pdo = get_pdo();

try {
    echo "Starting migration...<br>";
    
    // 1. Alter the ENUM to include super_admin
    echo "Updating Users table schema...<br>";
    $pdo->exec("ALTER TABLE Users MODIFY COLUMN role ENUM('admin', 'customer', 'super_admin', 'deleted') NOT NULL DEFAULT 'customer'");
    
    // 2. Promote the primary admin
    echo "Promoting primary user to Super Admin...<br>";
    $pdo->exec("UPDATE Users SET role = 'super_admin' WHERE user_id = 1");
    
    echo "<strong>Success!</strong> The database has been updated.<br>";
    echo "Please delete this file (repair-auth.php) for security.<br>";
    echo "<a href='index.php'>Return to Site</a>";

} catch (Exception $e) {
    echo "<strong>Error during migration:</strong> " . e($e->getMessage());
}
