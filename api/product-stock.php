<?php
/**
 * London Labels - Live Product Stock API
 */
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');

$product_id = (int)($_GET['product_id'] ?? 0);
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid product ID']);
    exit;
}

$product = get_product_by_id($product_id);
if (!$product) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Product not found']);
    exit;
}

$variant_id = (int)($_GET['variant_id'] ?? 0);
$variants = get_product_variants($product_id);
$has_variants = !empty($variants);

if ($has_variants) {
    if ($variant_id > 0) {
        $selected = null;
        foreach ($variants as $variant) {
            if ((int)$variant['variant_id'] === $variant_id) {
                $selected = $variant;
                break;
            }
        }
        $qty = $selected ? max(0, (int)$selected['quantity']) : 0;
    } else {
        $qty = array_sum(array_map(static fn($v) => max(0, (int)($v['quantity'] ?? 0)), $variants));
    }
} else {
    $qty = max(0, (int)$product['quantity']);
}

$stock_label = $qty <= 0 ? 'Out of Stock' : ($qty <= 5 ? 'Low Stock' : 'In Stock');
if ($qty <= 0) {
    $stock_note = $has_variants && $variant_id > 0 ? 'Unavailable for selected size' : 'Unavailable';
} else {
    if ($has_variants && $variant_id > 0) {
        $stock_note = $qty <= 5 ? 'Only ' . $qty . ' left in this size' : $qty . ' available in this size';
    } elseif ($has_variants) {
        $stock_note = $qty <= 5 ? 'Only ' . $qty . ' left across sizes' : $qty . ' available across sizes';
    } else {
        $stock_note = $qty <= 5 ? 'Only ' . $qty . ' left' : $qty . ' available';
    }
}

echo json_encode([
    'ok' => true,
    'product_id' => (int)$product['product_id'],
    'quantity' => $qty,
    'variant_id' => $variant_id > 0 ? $variant_id : null,
    'in_stock' => $qty > 0,
    'stock_label' => $stock_label,
    'stock_note' => $stock_note,
]);
