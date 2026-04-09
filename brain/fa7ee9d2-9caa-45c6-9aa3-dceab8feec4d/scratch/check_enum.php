<?php
include 'db.php';
$pdo = get_pdo();
$stmt = $pdo->query("SHOW COLUMNS FROM Users LIKE 'role'");
print_r($stmt->fetch());
