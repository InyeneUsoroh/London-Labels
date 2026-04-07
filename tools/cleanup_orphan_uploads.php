<?php
require_once __DIR__ . '/../functions.php';

$pdo = get_pdo();

$referenced = [];
$queries = [
    'SELECT image_url FROM Product_Images WHERE image_url IS NOT NULL AND TRIM(image_url) <> ""',
    'SELECT media_url AS image_url FROM Product_Reviews WHERE media_url IS NOT NULL AND TRIM(media_url) <> ""',
    'SELECT cover_image AS image_url FROM Categories WHERE cover_image IS NOT NULL AND TRIM(cover_image) <> ""',

    'SELECT profile_image_url AS image_url FROM Users WHERE profile_image_url IS NOT NULL AND TRIM(profile_image_url) <> ""',
];

foreach ($queries as $sql) {
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $path = get_local_upload_file_path_from_url((string)($row['image_url'] ?? ''));
        if (is_string($path) && $path !== '') {
            $referenced[realpath($path) ?: $path] = true;
            $thumb = dirname($path) . '/thumbs/' . basename($path);
            $referenced[realpath($thumb) ?: $thumb] = true;
        }
    }
}

$roots = [
    rtrim(UPLOAD_DIR, '/\\') . '/products',
    rtrim(UPLOAD_DIR, '/\\') . '/Reviews',
    rtrim(UPLOAD_DIR, '/\\') . '/categories',
    rtrim(UPLOAD_DIR, '/\\') . '/videos',
];

$deleted = 0;
$scanned = 0;

foreach ($roots as $root) {
    if (!is_dir($root)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($it as $node) {
        $path = $node->getPathname();
        if ($node->isDir()) {
            // Remove empty directories only.
            @rmdir($path);
            continue;
        }
        $scanned++;
        $norm = realpath($path) ?: $path;
        if (!isset($referenced[$norm])) {
            @unlink($path);
            $deleted++;
        }
    }
}

echo "Orphan cleanup complete.\n";
echo "Scanned files: {$scanned}\n";
echo "Deleted files: {$deleted}\n";

