<?php
// Start output buffering at the very first line — before any include can
// accidentally output whitespace. This is the true entry point for all pages.
if (!ob_get_level()) {
    ob_start();
}

function trusted_device_user_agent(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}
require_once __DIR__ . '/bootstrap.php';

// ===== CSRF PROTECTION =====

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(string $token): bool {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

// ===== HTML ESCAPING =====
// ===== GENERIC FORM VALIDATION =====

function validate_required(string $value, string $fieldName, array &$errors): void {
    if (trim($value) === '') {
        $errors[] = "$fieldName is required.";
    }
}

function validate_email(string $email, array &$errors, string $fieldName = 'Email'): void {
    if (trim($email) === '') {
        $errors[] = "$fieldName is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid $fieldName.";
    }
}

function validate_length(string $value, int $min, int $max, array &$errors, string $fieldName): void {
    $len = strlen($value);
    if ($len < $min) {
        $errors[] = "$fieldName must be at least $min characters.";
    } elseif ($len > $max) {
        $errors[] = "$fieldName must not exceed $max characters.";
    }
}

function validate_password(string $password, array &$errors, string $fieldName = 'Password'): void {
    if ($password === '') {
        $errors[] = "$fieldName is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "$fieldName must be at least 8 characters.";
    } elseif (strlen($password) > 255) {
        $errors[] = "$fieldName is too long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "$fieldName must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "$fieldName must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "$fieldName must contain at least one number.";
    }
}

// ===== GENERIC CRUD HELPERS =====
// These wrap db_functions.php for easier use in forms

function create_user_helper(array $data, array &$errors): ?int {
    validate_required($data['username'] ?? '', 'Username', $errors);
    validate_email($data['email'] ?? '', $errors);
    validate_password($data['password'] ?? '', $errors);
    if (!empty($errors)) return null;
    return create_user($data['username'], $data['email'], $data['password']);
}

function update_user_helper(int $user_id, array $data, array &$errors): bool {
    validate_email($data['email'] ?? '', $errors);
    validate_length($data['username'] ?? '', 3, 50, $errors, 'Username');
    if (!empty($errors)) return false;
    return update_user_profile(
        $user_id,
        $data['username'],
        $data['email'],
        $data['first_name'] ?? null,
        $data['last_name'] ?? null,
        $data['phone'] ?? null,
        $data['default_shipping_address'] ?? null,
        $data['default_address_line2'] ?? null,
        $data['default_city'] ?? null,
        $data['default_state'] ?? null,
        $data['default_postal_code'] ?? null,
        $data['default_country'] ?? null,
        $data['delivery_notes'] ?? null,
        $data['comm_order_updates'] ?? true,
        $data['comm_promos'] ?? false
    );
}

function create_product_helper(array $data, array &$errors): ?int {
    validate_length($data['name'] ?? '', 3, 120, $errors, 'Product Name');
    if (($data['category_id'] ?? 0) <= 0) {
        $errors[] = 'Please select a category.';
    }
    if (($data['price'] ?? 0) <= 0) {
        $errors[] = 'Price must be greater than 0.';
    }
    if (($data['quantity'] ?? 0) < 0) {
        $errors[] = 'Quantity cannot be negative.';
    }
    $sku = trim($data['sku'] ?? '');
    if ($sku !== '' && strlen($sku) > 80) {
        $errors[] = 'SKU must not exceed 80 characters.';
    }
    

    $source_label = trim((string)($data['source_label'] ?? 'London, United Kingdom'));
    if ($source_label === '') {
        $source_label = 'London, United Kingdom';
    }
    if (strlen($source_label) > 120) {
        $errors[] = 'Source label must not exceed 120 characters.';
    }
    $condition_label = trim((string)($data['condition_label'] ?? 'New'));
    if ($condition_label === '') {
        $condition_label = 'New';
    }
    if (strlen($condition_label) > 50) {
        $errors[] = 'Condition label must not exceed 50 characters.';
    }
    if (!empty($errors)) return null;
    $tags = trim($data['tags'] ?? '');
    return create_product($data['name'], $data['description'] ?? '', $data['category_id'], $data['price'], $data['quantity'], $sku ?: null, $tags ?: null, $source_label, $condition_label);
}

function update_product_helper(int $product_id, array $data, array &$errors): bool {
    validate_length($data['name'] ?? '', 3, 120, $errors, 'Product Name');
    if (($data['category_id'] ?? 0) <= 0) {
        $errors[] = 'Please select a category.';
    }
    if (($data['price'] ?? 0) <= 0) {
        $errors[] = 'Price must be greater than 0.';
    }
    if (($data['quantity'] ?? 0) < 0) {
        $errors[] = 'Quantity cannot be negative.';
    }
    $sku = trim($data['sku'] ?? '');
    if ($sku !== '' && strlen($sku) > 80) {
        $errors[] = 'SKU must not exceed 80 characters.';
    }
    

    $source_label = trim((string)($data['source_label'] ?? 'London, United Kingdom'));
    if ($source_label === '') {
        $source_label = 'London, United Kingdom';
    }
    if (strlen($source_label) > 120) {
        $errors[] = 'Source label must not exceed 120 characters.';
    }
    $condition_label = trim((string)($data['condition_label'] ?? 'New'));
    if ($condition_label === '') {
        $condition_label = 'New';
    }
    if (strlen($condition_label) > 50) {
        $errors[] = 'Condition label must not exceed 50 characters.';
    }
    if (!empty($errors)) return false;
    $tags = trim($data['tags'] ?? '');
    return update_product_core(
        $product_id,
        $data['name'],
        $data['description'] ?? '',
        $data['category_id'],
        $data['price'],
        $data['quantity'],
        $sku ?: null,
        $tags ?: null,
        $source_label,
        $condition_label
    );
}

function resolve_media_base_url(): string {
    $cdnBase = rtrim((string)(defined('MEDIA_CDN_BASE_URL') ? MEDIA_CDN_BASE_URL : ''), '/');
    if ($cdnBase !== '') {
        return $cdnBase;
    }
    return BASE_URL . '/Uploads';
}

function sanitize_local_upload_media_url(?string $url): ?string {
    $url = trim((string)$url);
    if ($url === '') {
        return null;
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return null;
    }

    $scheme = strtolower((string)($parts['scheme'] ?? ''));
    if ($scheme !== '' && !in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    $host = strtolower((string)($parts['host'] ?? ''));
    if ($host !== '') {
        $base_host = strtolower((string)parse_url(BASE_URL, PHP_URL_HOST));
        $request_host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($host !== $base_host && $host !== $request_host) {
            return null;
        }
    }

    $path = str_replace('\\', '/', (string)($parts['path'] ?? ''));
    if (stripos($path, '/Uploads/') === false) {
        return null;
    }

    return $url;
}

function get_local_upload_file_path_from_url(?string $url): ?string {
    $safe = sanitize_local_upload_media_url($url);
    if ($safe === null) {
        return null;
    }
    $path = (string)parse_url($safe, PHP_URL_PATH);
    $uploadsPos = stripos($path, '/Uploads/');
    if ($uploadsPos === false) {
        return null;
    }
    $relative = ltrim(substr($path, $uploadsPos + 9), '/');
    if ($relative === '') {
        return null;
    }
    return rtrim(UPLOAD_DIR, '/\\') . '/' . str_replace('\\', '/', $relative);
}

function ensure_upload_audit_table(): void {
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;
    get_pdo()->exec('CREATE TABLE IF NOT EXISTS Upload_Audit (
        upload_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        ip_address VARCHAR(64) NOT NULL,
        scope VARCHAR(64) NOT NULL,
        file_size INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_upload_audit_scope_time (scope, created_at),
        INDEX idx_upload_audit_user_scope_time (user_id, scope, created_at),
        INDEX idx_upload_audit_ip_scope_time (ip_address, scope, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function can_upload_media(string $scope, array &$errors, int $maxPerHour = 0, int $maxPerDay = 0): bool {
    ensure_upload_audit_table();
    $pdo = get_pdo();
    if ($maxPerHour <= 0) {
        $maxPerHour = defined('UPLOAD_MAX_PER_HOUR') ? UPLOAD_MAX_PER_HOUR : 20;
    }
    if ($maxPerDay <= 0) {
        $maxPerDay = defined('UPLOAD_MAX_PER_DAY') ? UPLOAD_MAX_PER_DAY : 80;
    }
    $now = new DateTimeImmutable('now');
    $hourAgo = $now->modify('-1 hour')->format('Y-m-d H:i:s');
    $dayAgo = $now->modify('-1 day')->format('Y-m-d H:i:s');
    $uid = is_logged_in() ? (int)current_user_id() : null;
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

    if ($uid !== null && $uid > 0) {
        $hourStmt = $pdo->prepare('SELECT COUNT(*) FROM Upload_Audit WHERE user_id = ? AND scope = ? AND created_at >= ?');
        $hourStmt->execute([$uid, $scope, $hourAgo]);
        $dayStmt = $pdo->prepare('SELECT COUNT(*) FROM Upload_Audit WHERE user_id = ? AND scope = ? AND created_at >= ?');
        $dayStmt->execute([$uid, $scope, $dayAgo]);
    } else {
        $hourStmt = $pdo->prepare('SELECT COUNT(*) FROM Upload_Audit WHERE ip_address = ? AND scope = ? AND created_at >= ?');
        $hourStmt->execute([$ip, $scope, $hourAgo]);
        $dayStmt = $pdo->prepare('SELECT COUNT(*) FROM Upload_Audit WHERE ip_address = ? AND scope = ? AND created_at >= ?');
        $dayStmt->execute([$ip, $scope, $dayAgo]);
    }

    $hourCount = (int)$hourStmt->fetchColumn();
    $dayCount = (int)$dayStmt->fetchColumn();
    if ($hourCount >= $maxPerHour) {
        $errors[] = 'Upload limit reached for this hour. Please try again later.';
        return false;
    }
    if ($dayCount >= $maxPerDay) {
        $errors[] = 'Daily upload quota reached. Please try again tomorrow.';
        return false;
    }

    return true;
}

function record_upload_event(string $scope, int $fileSize): void {
    ensure_upload_audit_table();
    $uid = is_logged_in() ? (int)current_user_id() : null;
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    $stmt = get_pdo()->prepare('INSERT INTO Upload_Audit (user_id, ip_address, scope, file_size) VALUES (?, ?, ?, ?)');
    $stmt->execute([$uid > 0 ? $uid : null, $ip, $scope, max(0, $fileSize)]);
}

function scan_uploaded_file_for_malware(string $tmpPath, array &$errors): bool {
    $scanner = trim((string)(defined('CLAMSCAN_BIN') ? CLAMSCAN_BIN : ''));
    if ($scanner === '' || !is_file($tmpPath)) {
        return true;
    }
    $cmd = escapeshellarg($scanner) . ' --no-summary ' . escapeshellarg($tmpPath) . ' 2>&1';
    $output = [];
    $code = 0;
    @exec($cmd, $output, $code);
    if ($code === 1) {
        $errors[] = 'Upload blocked by malware scanner.';
        return false;
    }
    if ($code > 1) {
        $errors[] = 'Upload scanner is unavailable. Please try again later.';
        return false;
    }
    return true;
}

function build_signed_media_url(string $url, int $ttlSeconds = 900): string {
    $secret = trim((string)(defined('MEDIA_SIGNING_SECRET') ? MEDIA_SIGNING_SECRET : ''));
    $safe = sanitize_local_upload_media_url($url);
    if ($secret === '' || $safe === null) {
        return $url;
    }

    $expires = time() + max(60, $ttlSeconds);
    $path = (string)parse_url($safe, PHP_URL_PATH);
    $sig = hash_hmac('sha256', $path . '|' . $expires, $secret);
    return BASE_URL . '/media.php?path=' . rawurlencode($path) . '&exp=' . $expires . '&sig=' . $sig;
}

/**
 * Validate, sanitise, and store an uploaded image file.
 *
 * Orchestrates the full upload pipeline:
 *   1. PHP upload error check
 *   2. File-size guard (against $maxBytes)
 *   3. MIME-type + extension whitelist (jpg, png, webp, gif)
 *   4. getimagesize() content verification (must be a real image)
 *   5. Optional ClamAV malware scan (via scan_uploaded_file_for_malware)
 *   6. Rate-limit check (via can_upload_media)
 *   7. Unique filename generation and move to UPLOAD_DIR/$scope/
 *   8. Audit log (via record_upload_event)
 *
 * @param array  $file       The $_FILES entry (keys: name, tmp_name, error, size, type)
 * @param string $scope      Sub-folder inside UPLOAD_DIR, e.g. 'Reviews', 'categories'
 * @param string $filePrefix Safe filename prefix, e.g. 'review_42_7'
 * @param array  &$errors    Error messages are appended here on failure
 * @param int    $maxBytes   Maximum allowed file size in bytes
 *
 * @return array{url:string,path:string}|null  Stored file info, or null on failure
 */
function normalize_and_store_uploaded_image(
    array $file,
    string $scope,
    string $filePrefix,
    array &$errors,
    int $maxBytes
): ?array {

    // ── 1. PHP upload error ───────────────────────────────────────────────────
    $uploadError = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError !== UPLOAD_ERR_OK) {
        $phpUploadMessages = [
            UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the server size limit.',
            UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the form size limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE    => 'No file was selected for upload.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server upload directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write the uploaded file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A server extension blocked the upload.',
        ];
        $errors[] = $phpUploadMessages[$uploadError] ?? 'An unknown upload error occurred.';
        return null;
    }

    $tmpPath  = (string)($file['tmp_name'] ?? '');
    $origName = basename((string)($file['name'] ?? ''));
    $fileSize = (int)($file['size'] ?? 0);

    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        $errors[] = 'Invalid upload. Please try again.';
        return null;
    }

    // ── 2. File size ──────────────────────────────────────────────────────────
    if ($fileSize <= 0 || $fileSize > $maxBytes) {
        $mb = round($maxBytes / (1024 * 1024), 1);
        $errors[] = "Image must be smaller than {$mb} MB.";
        return null;
    }

    // ── 3. Extension + MIME whitelist ─────────────────────────────────────────
    $allowedExts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    $origExt = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($origExt, $allowedExts, true)) {
        $errors[] = 'Only JPG, PNG, WebP, and GIF images are allowed.';
        return null;
    }

    // Use finfo for server-side MIME check (not trusting $_FILES['type'])
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = (string)$finfo->file($tmpPath);
    if (!in_array($mimeType, $allowedMimes, true)) {
        $errors[] = 'The uploaded file does not appear to be a valid image.';
        return null;
    }

    // ── 4. Real image content verification ───────────────────────────────────
    $imgInfo = @getimagesize($tmpPath);
    if ($imgInfo === false || empty($imgInfo[0]) || empty($imgInfo[1])) {
        $errors[] = 'The uploaded file could not be read as an image.';
        return null;
    }

    // ── 5. Malware scan (ClamAV – skipped gracefully if not configured) ───────
    if (!scan_uploaded_file_for_malware($tmpPath, $errors)) {
        return null;
    }

    // ── 6. Rate limit ─────────────────────────────────────────────────────────
    if (!can_upload_media($scope, $errors)) {
        return null;
    }

    // ── 7. Build destination path and move file ───────────────────────────────
    $cleanPrefix  = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filePrefix);
    $uniqueSuffix = bin2hex(random_bytes(8));
    $ext          = ($origExt === 'jpg') ? 'jpg' : $origExt; // normalise jpeg → jpg
    $filename     = $cleanPrefix . '_' . $uniqueSuffix . '.' . $ext;

    $destDir = rtrim(UPLOAD_DIR, '/\\') . '/' . trim($scope, '/\\') . '/';
    if (!is_dir($destDir)) {
        if (!mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            $errors[] = 'Could not create upload directory. Please contact support.';
            return null;
        }
    }

    $destPath = $destDir . $filename;
    if (!move_uploaded_file($tmpPath, $destPath)) {
        $errors[] = 'Failed to save the uploaded image. Please try again.';
        return null;
    }

    // ── 8. Audit log ──────────────────────────────────────────────────────────
    record_upload_event($scope, $fileSize);

    // Build the public URL using the same resolver used across the site
    $publicUrl = resolve_media_base_url() . '/' . trim($scope, '/') . '/' . $filename;

    return [
        'url'  => $publicUrl,
        'path' => $destPath,
    ];
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a price value with the site currency symbol.
 * e.g. format_price(15000) -> "{CURRENCY_SYMBOL}15,000.00"
 */
function format_price(float $amount): string {
    // Explicit separators prevent locale-dependent number_format behaviour on Railway
    return LND_CURRENCY_SYMBOL . number_format($amount, 2, '.', ',');
}

/**
 * Build order progress steps for display on orders/confirmation pages.
 */
function order_progress_steps(string $status): array {
    $all    = ['pending', 'processing', 'shipped', 'delivered'];
    $labels = [
        'pending'    => 'Order Placed',
        'processing' => 'Processing',
        'shipped'    => 'Out for Delivery',
        'delivered'  => 'Delivered',
    ];
    $idx = array_search($status, $all, true);
    if ($idx === false) $idx = -1;

    $steps = [];
    foreach ($all as $i => $step) {
        $steps[] = [
            'name'   => $labels[$step],
            'done'   => $idx >= $i,
            'active' => $idx === $i,
        ];
    }
    return $steps;
}

// ===== AUTHENTICATION HELPERS =====

// ===== ADMIN CHECK =====
if (!function_exists('is_admin')) {
    function is_admin(): bool {
        return !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

function is_logged_in(): bool {
    if (!empty($_SESSION['user_id'])) {
        // Treat deleted accounts as logged out
        if (($_SESSION['user_role'] ?? '') === 'deleted') {
            session_destroy();
            return false;
        }
        return true;
    }
    // Persistent login: check 'Remember me' cookie
    if (!empty($_COOKIE['ll_remember_me'])) {
        $_SESSION['user_id'] = (int)$_COOKIE['ll_remember_me'];
        return true;
    }
    return false;
}

function is_remembered(): bool {
    return !empty($_COOKIE['ll_remember_me']);
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function get_guest_wishlist_product_ids(): array {
    if (empty($_COOKIE['ll_guest_wishlist'])) return [];
    $ids = explode(',', $_COOKIE['ll_guest_wishlist']);
    $valid = [];
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($id > 0) $valid[] = $id;
    }
    return $valid;
}

function get_bundle_analytics_summary(?string $dateFilter = null): array {
    $default = [
        'views' => 0,
        'adds' => 0,
        'conversion_rate' => 0.0,
        'top_bundles' => [],
        'events_total' => 0,
    ];

    $logPath = __DIR__ . '/Uploads/analytics/events.log';
    if (!is_file($logPath) || !is_readable($logPath)) {
        return $default;
    }

    $supported = ['bundle_view_click', 'bundle_add_click'];
    $views = 0;
    $adds = 0;
    $eventsTotal = 0;
    $perBundle = [];

    $startDate = null;
    if ($dateFilter === '7d') {
        $startDate = strtotime('-7 days');
    } elseif ($dateFilter === '30d') {
        $startDate = strtotime('-30 days');
    }

    try {
        $file = new SplFileObject($logPath, 'r');
        while (!$file->eof()) {
            $line = trim((string)$file->fgets());
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true);
            if (!is_array($row)) {
                continue;
            }

            // Date filter: skip if before startDate
            if ($startDate !== null) {
                $ts = isset($row['ts']) ? strtotime($row['ts']) : null;
                if ($ts === false || $ts === null || $ts < $startDate) {
                    continue;
                }
            }

            $event = trim((string)($row['event'] ?? ''));
            if (!in_array($event, $supported, true)) {
                continue;
            }

            $eventsTotal++;
            $bundleId = trim((string)($row['bundle_id'] ?? 'unknown'));
            if ($bundleId === '') {
                $bundleId = 'unknown';
            }

            if (!isset($perBundle[$bundleId])) {
                $perBundle[$bundleId] = [
                    'bundle_id' => $bundleId,
                    'views' => 0,
                    'adds' => 0,
                ];
            }

            if ($event === 'bundle_view_click') {
                $views++;
                $perBundle[$bundleId]['views']++;
            }

            if ($event === 'bundle_add_click') {
                $adds++;
                $perBundle[$bundleId]['adds']++;
            }
        }
    } catch (Throwable $error) {
        return $default;
    }

    $conversionRate = $views > 0 ? round(($adds / $views) * 100, 2) : 0.0;

    $topBundles = array_values($perBundle);
    usort($topBundles, static function (array $a, array $b): int {
        if ($a['adds'] === $b['adds']) {
            return $b['views'] <=> $a['views'];
        }
        return $b['adds'] <=> $a['adds'];
    });

    return [
        'views' => $views,
        'adds' => $adds,
        'conversion_rate' => $conversionRate,
        'top_bundles' => array_slice($topBundles, 0, 5),
        'events_total' => $eventsTotal,
    ];
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

function login_user(int $id, string $username, string $email, string $role = 'customer'): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $username;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_role'] = $role;
}

function trusted_device_cookie_name(): string {
    return 'll_trusted_device';
}

function trusted_device_ttl_days(): int {
    return 30;
}

function trusted_device_cookie_path(): string {
    $path = parse_url(BASE_URL, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . $path;
    }
    return rtrim($path, '/') ?: '/';
}

function trusted_device_cookie_options(int $expiresAt): array {
    return [
        'expires' => $expiresAt,
        'path' => trusted_device_cookie_path(),
        'domain' => '',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
}

function clear_trusted_device_cookie(): void {
    $options = trusted_device_cookie_options(time() - 3600);
    setcookie(trusted_device_cookie_name(), '', $options);
    unset($_COOKIE[trusted_device_cookie_name()]);
}

function trusted_device_ip_prefix(string $ip): string {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        return implode('.', array_slice($parts, 0, 3));
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 4));
    }
    return '';
}

function trusted_device_user_agent_family(string $ua): string {
    $ua = strtolower($ua);
    if (str_contains($ua, 'chrome')) return 'chrome';
    if (str_contains($ua, 'firefox')) return 'firefox';
    if (str_contains($ua, 'safari')) return 'safari';
    if (str_contains($ua, 'edge')) return 'edge';
    if (str_contains($ua, 'opera')) return 'opera';
    return 'other';
}

function trusted_device_risk_score(array $deviceRow): int {
    $score = 0;

    $currentIp = trusted_device_ip_address();
    $currentUa = trusted_device_user_agent();

    $savedIp = trim((string)($deviceRow['ip_address'] ?? ''));
    $savedUa = trim((string)($deviceRow['user_agent'] ?? ''));

    if ($savedIp !== '' && $currentIp !== '') {
        if (!hash_equals($savedIp, $currentIp)) {
            $score += 20;
            if (trusted_device_ip_prefix($savedIp) !== trusted_device_ip_prefix($currentIp)) {
                $score += 20;
            }
        }
    }

    if ($savedUa !== '' && $currentUa !== '') {
        if (!hash_equals($savedUa, $currentUa)) {
            $score += 15;
            if (trusted_device_user_agent_family($savedUa) !== trusted_device_user_agent_family($currentUa)) {
                $score += 25;
            }
        }
    }

    $lastUsedAt = strtotime((string)($deviceRow['last_used_at'] ?? ''));
    if ($lastUsedAt !== false) {
        $daysSinceLastUse = (time() - $lastUsedAt) / 86400;
        if ($daysSinceLastUse > 21) {
            $score += 10;
        }
    }

    return $score;
}

function trusted_device_requires_step_up(array $deviceRow): bool {
    return trusted_device_risk_score($deviceRow) >= 50;
}

function trusted_device_cookie_parts(): ?array {
    $raw = (string)($_COOKIE[trusted_device_cookie_name()] ?? '');
    if (!preg_match('/^[a-f0-9]{24}:[a-f0-9]{64}$/i', $raw)) {
        return null;
    }

    [$selector, $validator] = explode(':', $raw, 2);
    return [
        'selector' => strtolower($selector),
        'validator' => strtolower($validator),
    ];
}

function ensure_trusted_devices_table(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS user_trusted_devices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            selector CHAR(24) NOT NULL,
            validator_hash CHAR(64) NOT NULL,
            ip_address VARCHAR(45) NULL,
            user_agent VARCHAR(255) NULL,
            last_used_at DATETIME NULL,
            expires_at DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_user_trusted_selector (selector),
            KEY idx_user_trusted_user_id (user_id),
            KEY idx_user_trusted_expires (expires_at),
            CONSTRAINT fk_user_trusted_devices_user
                FOREIGN KEY (user_id) REFERENCES Users(user_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function remember_trusted_device(int $userId): bool {
if (!function_exists('trusted_device_ip_address')) {
    function trusted_device_ip_address(): string {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded !== '') {
            $parts = explode(',', $forwarded);
            $candidate = trim((string)($parts[0] ?? ''));
            if ($candidate !== '') {
                return mb_substr($candidate, 0, 45);
            }
        }
        return mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    }
}
    $pdo = get_pdo();
    ensure_trusted_devices_table($pdo);

    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expiresAt = time() + (trusted_device_ttl_days() * 86400);
    $expiresAtSql = date('Y-m-d H:i:s', $expiresAt);

    $pdo->prepare('DELETE FROM user_trusted_devices WHERE expires_at <= NOW()')->execute();

    $stmt = $pdo->prepare('INSERT INTO user_trusted_devices (user_id, selector, validator_hash, ip_address, user_agent, last_used_at, expires_at) VALUES (?, ?, ?, ?, ?, NOW(), ?)');
    $ok = $stmt->execute([
        $userId,
        $selector,
        $validatorHash,
        trusted_device_ip_address(),
        trusted_device_user_agent(),
        $expiresAtSql,
    ]);

    if (!$ok) {
        return false;
    }

    $pdo->prepare('DELETE FROM user_trusted_devices WHERE user_id = ? AND id NOT IN (SELECT id FROM (SELECT id FROM user_trusted_devices WHERE user_id = ? ORDER BY last_used_at DESC, created_at DESC LIMIT 10) AS keep_ids)')
        ->execute([$userId, $userId]);

    setcookie(
        trusted_device_cookie_name(),
        $selector . ':' . $validator,
        trusted_device_cookie_options($expiresAt)
    );
    $_COOKIE[trusted_device_cookie_name()] = $selector . ':' . $validator;
    return true;
}

function can_skip_twofactor_for_user(int $userId): bool {
    $parts = trusted_device_cookie_parts();
    if ($parts === null) {
        return false;
    }

    $pdo = get_pdo();
    ensure_trusted_devices_table($pdo);

    $stmt = $pdo->prepare('SELECT id, selector, validator_hash, ip_address, user_agent, last_used_at, expires_at FROM user_trusted_devices WHERE user_id = ? AND selector = ? LIMIT 1');
    $stmt->execute([$userId, $parts['selector']]);
    $row = $stmt->fetch();

    if (!$row) {
        clear_trusted_device_cookie();
        return false;
    }

    if (strtotime((string)$row['expires_at']) <= time()) {
        $pdo->prepare('DELETE FROM user_trusted_devices WHERE id = ?')->execute([(int)$row['id']]);
        clear_trusted_device_cookie();
        return false;
    }

    $candidateHash = hash('sha256', $parts['validator']);
    if (!hash_equals((string)$row['validator_hash'], $candidateHash)) {
        $pdo->prepare('DELETE FROM user_trusted_devices WHERE id = ?')->execute([(int)$row['id']]);
        clear_trusted_device_cookie();
        return false;
    }

    if (trusted_device_requires_step_up($row)) {
        return false;
    }

    $newValidator = bin2hex(random_bytes(32));
    $newHash = hash('sha256', $newValidator);
    $expiresAt = time() + (trusted_device_ttl_days() * 86400);
    $expiresAtSql = date('Y-m-d H:i:s', $expiresAt);

    $pdo->prepare('UPDATE user_trusted_devices SET validator_hash = ?, ip_address = ?, user_agent = ?, last_used_at = NOW(), expires_at = ? WHERE id = ?')
        ->execute([
            $newHash,
            trusted_device_ip_address(),
            trusted_device_user_agent(),
            $expiresAtSql,
            (int)$row['id'],
        ]);

    $cookieValue = $parts['selector'] . ':' . $newValidator;
    setcookie(trusted_device_cookie_name(), $cookieValue, trusted_device_cookie_options($expiresAt));
    $_COOKIE[trusted_device_cookie_name()] = $cookieValue;
    return true;
}

function revoke_current_trusted_device(): void {
    $parts = trusted_device_cookie_parts();
    if ($parts !== null) {
        $pdo = get_pdo();
        ensure_trusted_devices_table($pdo);
        $pdo->prepare('DELETE FROM user_trusted_devices WHERE selector = ?')->execute([$parts['selector']]);
    }
    clear_trusted_device_cookie();
}

function revoke_all_trusted_devices_for_user(int $userId): void {
    $pdo = get_pdo();
    ensure_trusted_devices_table($pdo);
    $pdo->prepare('DELETE FROM user_trusted_devices WHERE user_id = ?')->execute([$userId]);

    if ((int)(current_user_id() ?? 0) === $userId) {
        clear_trusted_device_cookie();
    }
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    // Clear persistent login cookie
    setcookie('ll_remember_me', '', time() - 42000, '/');
    session_destroy();
}

function current_user_id(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function current_user_name(): ?string {
    return $_SESSION['user_name'] ?? null;
}

function current_user_email(): ?string {
    return $_SESSION['user_email'] ?? null;
}

function current_user_role(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function cart_item_count(): int {
    $count = 0;
    foreach (($_SESSION['cart'] ?? []) as $qty) {
        $count += (int)$qty;
    }
    return $count;
}




function random_token(int $len = 32): string {
    // returns a hex string (64 chars when len=32)
    return bin2hex(random_bytes($len));
}

function ensure_password_resets_table(PDO $pdo): void {
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_password_resets_user_id (user_id),
            INDEX idx_password_resets_token (token),
            CONSTRAINT fk_password_resets_user
                FOREIGN KEY (user_id) REFERENCES Users(user_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}


function find_user_by_email(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare('SELECT user_id AS id, username AS name, email, password_hash FROM Users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function create_password_reset(PDO $pdo, int $userId, int $ttlMinutes = 30): string {
    ensure_password_resets_table($pdo);

    $token = random_token(32); // 64 hex chars
    $expires = (new DateTimeImmutable("+{$ttlMinutes} minutes"))->format('Y-m-d H:i:s');

    // Optionally invalidate previous active tokens for this user
    $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()')
        ->execute([$userId]);

    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $token, $expires]);
    return $token;
}

function load_valid_reset(PDO $pdo, string $token): ?array {
    ensure_password_resets_table($pdo);

    $stmt = $pdo->prepare('SELECT pr.id, pr.user_id, pr.token, pr.expires_at, pr.used_at, u.email, u.username AS name
                           FROM password_resets pr
                           JOIN Users u ON u.user_id = pr.user_id
                           WHERE pr.token = ? LIMIT 1');
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    if (!$row) return null;
    if (!empty($row['used_at'])) return null;
    if (new DateTimeImmutable($row['expires_at']) < new DateTimeImmutable('now')) return null;
    return $row;
}

function mark_reset_used(PDO $pdo, int $resetId): void {
    $stmt = $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ? AND used_at IS NULL');
    $stmt->execute([$resetId]);
}

function update_user_password(int $userId, string $rawPassword): bool {
    $hash = password_hash($rawPassword, PASSWORD_DEFAULT);
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('UPDATE Users SET password_hash = ? WHERE user_id = ?');
    return $stmt->execute([$hash, $userId]);
}

// Optional: fake mailer for local dev (logs link on screen or to a file)
function build_reset_link(string $token): string {
    $path = BASE_URL . '/reset.php?token=' . urlencode($token);

    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $path;
    }

    return $path;
}

function revoke_trusted_device_for_user(int $userId, string $selector): void {
    if (!preg_match('/^[a-f0-9]{24}$/i', $selector)) {
        return;
    }

    $pdo = get_pdo();
    ensure_trusted_devices_table($pdo);
    $pdo->prepare('DELETE FROM user_trusted_devices WHERE user_id = ? AND selector = ?')->execute([$userId, strtolower($selector)]);

    $parts = trusted_device_cookie_parts();
    if ($parts !== null && hash_equals($parts['selector'], strtolower($selector))) {
        clear_trusted_device_cookie();
    }
}

function get_trusted_devices_for_user(int $userId): array {
    $pdo = get_pdo();
    ensure_trusted_devices_table($pdo);
    $pdo->prepare('DELETE FROM user_trusted_devices WHERE expires_at <= NOW()')->execute();

    $stmt = $pdo->prepare('SELECT selector, ip_address, user_agent, last_used_at, expires_at, created_at FROM user_trusted_devices WHERE user_id = ? ORDER BY last_used_at DESC, created_at DESC');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll() ?: [];

    $currentSelector = trusted_device_cookie_parts()['selector'] ?? '';
    foreach ($rows as &$row) {
        $row['is_current'] = $currentSelector !== '' && hash_equals((string)$row['selector'], $currentSelector);
    }
    unset($row);

    return $rows;
}


