<?php
include 'db.php';
$pdo = get_pdo();
try {
    $pdo->exec("ALTER TABLE Users MODIFY COLUMN role ENUM('admin', 'customer', 'super_admin', 'deleted') NOT NULL DEFAULT 'customer'");
    echo "Table altered successfully.\n";
    $pdo->exec("UPDATE Users SET role = 'super_admin' WHERE user_id = 1");
    echo "User 1 promoted to super_admin.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
