<?php
include 'db.php';
$pdo = get_pdo();
$res = $pdo->query('SELECT product_id, size, price_modifier FROM Product_Variants WHERE price_modifier != 0 LIMIT 20');
while($row = $res->fetch(PDO::FETCH_ASSOC)) {
    echo "Product ID: " . $row['product_id'] . ", Size: " . $row['size'] . ", Modifier: " . $row['price_modifier'] . "\n";
}
