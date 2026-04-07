<?php
require_once __DIR__ . '/../functions.php';

$tasks = [
    __DIR__ . '/cleanup_external_media.php',
    __DIR__ . '/cleanup_orphan_uploads.php',
];

foreach ($tasks as $task) {
    if (!is_file($task)) {
        echo 'Skipped missing task: ' . basename($task) . PHP_EOL;
        continue;
    }
    echo 'Running ' . basename($task) . '...' . PHP_EOL;
    passthru('php ' . escapeshellarg($task), $code);
    if ($code !== 0) {
        echo 'Task failed: ' . basename($task) . PHP_EOL;
        exit($code);
    }
}

echo 'Maintenance finished successfully.' . PHP_EOL;
