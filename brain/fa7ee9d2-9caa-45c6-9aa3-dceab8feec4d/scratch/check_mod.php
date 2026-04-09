<?php
include 'db.php';
$pdo = get_pdo();
$product = $pdo->query("SELECT * FROM Products WHERE name LIKE '%New Balance Athletics Crop Tee%'")->fetch();
if ($product) {
    echo "Product ID: " . $product['product_id'] . ", Base Price: " . $product['price'] . "\n";
    $vars = $pdo->query("SELECT * FROM Product_Variants WHERE product_id = " . $product['product_id'])->fetchAll();
    foreach($vars as $v) {
        echo "Size: " . $v['size'] . ", Modifier: " . $v['price_modifier'] . "\n";
    }
} else {
    echo "Product not found\n";
}
