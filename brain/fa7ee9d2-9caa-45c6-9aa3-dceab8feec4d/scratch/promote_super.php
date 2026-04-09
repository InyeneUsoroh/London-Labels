<?php
include 'db.php';
$pdo = get_pdo();
$stmt = $pdo->prepare('UPDATE Users SET role = "super_admin" WHERE user_id = 1');
if ($stmt->execute()) {
    echo "User 1 promoted to super_admin\n";
} else {
    echo "Promotion failed\n";
}
