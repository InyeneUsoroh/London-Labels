<?php
require 'bootstrap.php';
try {
    get_pdo()->exec("ALTER TABLE Orders MODIFY COLUMN user_id INT NULL");
    echo "Success\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
