<?php
include 'db.php';
$pdo = get_pdo();
$users = $pdo->query('SELECT user_id, username, email, role FROM Users LIMIT 5')->fetchAll();
print_r($users);
