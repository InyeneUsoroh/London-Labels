<?php
/**
 * London Labels - Product search suggestions (JSON)
 */
require_once __DIR__ . '/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=30, stale-while-revalidate=60');
header('Vary: Accept');
header('X-Content-Type-Options: nosniff');

$now = time();
$windowSeconds = 60;
$maxRequestsPerWindow = 90;

if (!isset($_SESSION['search_suggest_rate']) || !is_array($_SESSION['search_suggest_rate'])) {
    $_SESSION['search_suggest_rate'] = [];
}

$_SESSION['search_suggest_rate'] = array_values(array_filter(
    $_SESSION['search_suggest_rate'],
    static fn ($timestamp): bool => is_int($timestamp) && ($timestamp > ($now - $windowSeconds))
));

if (count($_SESSION['search_suggest_rate']) >= $maxRequestsPerWindow) {
    http_response_code(429);
    header('Retry-After: 60');
    echo json_encode([
        'query' => '',
        'items' => [],
        'groups' => [],
        'error' => 'Too many requests. Please retry shortly.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['search_suggest_rate'][] = $now;

$query = trim((string)($_GET['q'] ?? ''));
$limit = (int)($_GET['limit'] ?? 8);
$limit = max(1, min(12, $limit));

if (mb_strlen($query) < 2) {
    echo json_encode([
        'query' => $query,
        'items' => [],
        'groups' => [],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rows = get_product_suggestions($query, $limit);

$items = array_map(static function (array $row): array {
    return [
        'id' => (int)$row['product_id'],
        'name' => (string)$row['name'],
        'category' => (string)$row['category_name'],
        'price' => format_price((float)$row['price']),
        'url' => BASE_URL . '/product.php?id=' . (int)$row['product_id'],
    ];
}, $rows);

$grouped = [];
foreach ($items as $item) {
    $category = $item['category'] !== '' ? $item['category'] : 'Other';
    if (!isset($grouped[$category])) {
        $grouped[$category] = [
            'category' => $category,
            'items' => [],
        ];
    }
    $grouped[$category]['items'][] = $item;
}

echo json_encode([
    'query' => $query,
    'items' => $items,
    'groups' => array_values($grouped),
], JSON_UNESCAPED_UNICODE);
