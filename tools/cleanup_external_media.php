<?php
require_once __DIR__ . '/../functions.php';

$pdo = get_pdo();

$targets = [
    ['table' => 'Categories', 'pk' => 'category_id', 'col' => 'cover_image'],

    ['table' => 'Product_Images', 'pk' => 'image_id', 'col' => 'image_url'],
    ['table' => 'Product_Reviews', 'pk' => 'review_id', 'col' => 'media_url'],
    ['table' => 'Users', 'pk' => 'user_id', 'col' => 'profile_image_url'],
];

$summary = [];
$totalScanned = 0;
$totalCleaned = 0;

$pdo->beginTransaction();

try {
    foreach ($targets as $t) {
        $table = $t['table'];
        $pk = $t['pk'];
        $col = $t['col'];

        $selectSql = "SELECT {$pk} AS id, {$col} AS media FROM {$table} WHERE {$col} IS NOT NULL AND TRIM({$col}) <> ''";
        $rows = $pdo->query($selectSql)->fetchAll(PDO::FETCH_ASSOC);

        $scanned = 0;
        $cleaned = 0;

        $updateSql = "UPDATE {$table} SET {$col} = NULL WHERE {$pk} = ?";
        $updateStmt = $pdo->prepare($updateSql);

        foreach ($rows as $row) {
            $scanned++;
            $media = (string)($row['media'] ?? '');
            $safe = sanitize_local_upload_media_url($media);
            if ($safe === null) {
                $updateStmt->execute([(int)$row['id']]);
                $cleaned++;
            }
        }

        $summary[] = [
            'table' => $table,
            'column' => $col,
            'scanned' => $scanned,
            'cleaned' => $cleaned,
        ];

        $totalScanned += $scanned;
        $totalCleaned += $cleaned;
    }

    $pdo->commit();

    echo "Cleanup complete.\n";
    foreach ($summary as $row) {
        echo "- {$row['table']}.{$row['column']}: scanned {$row['scanned']}, cleaned {$row['cleaned']}\n";
    }
    echo "Total scanned: {$totalScanned}\n";
    echo "Total cleaned: {$totalCleaned}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Cleanup failed: " . $e->getMessage() . "\n");
    exit(1);
}

