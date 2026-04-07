<?php
require_once __DIR__ . '/functions.php';

$path = (string)($_GET['path'] ?? '');
$exp = (int)($_GET['exp'] ?? 0);
$sig = (string)($_GET['sig'] ?? '');
$secret = trim((string)(defined('MEDIA_SIGNING_SECRET') ? MEDIA_SIGNING_SECRET : ''));

if ($secret === '' || $path === '' || $exp <= 0 || $sig === '') {
    http_response_code(403);
    exit('Forbidden');
}

if (time() > $exp) {
    http_response_code(403);
    exit('Expired');
}

$expected = hash_hmac('sha256', $path . '|' . $exp, $secret);
if (!hash_equals($expected, $sig)) {
    http_response_code(403);
    exit('Invalid signature');
}

$fullUrl = BASE_URL . $path;
$filePath = get_local_upload_file_path_from_url($fullUrl);
if (!is_string($filePath) || $filePath === '' || !is_file($filePath)) {
    http_response_code(404);
    exit('Not found');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string)$finfo->file($filePath);
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string)filesize($filePath));
header('Cache-Control: private, max-age=300');
readfile($filePath);
exit;
