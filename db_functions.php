<?php
/**
 * London Labels E-Commerce Application
 * Database Query Helper Functions
 * 
 * This file contains all database operations for Users, Products, Categories, Orders, etc.
 */

// ===== USERS FUNCTIONS =====

function ensure_user_profile_columns(): void {
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo = get_pdo();
    $schema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($schema === '') {
        return;
    }

    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Users'");
    $stmt->execute([$schema]);
    $columns = array_flip(array_map(static fn($row) => (string)$row['COLUMN_NAME'], $stmt->fetchAll()));

    $additions = [
        'default_shipping_address' => 'ADD COLUMN default_shipping_address VARCHAR(255) NULL',
        'default_address_line2' => 'ADD COLUMN default_address_line2 VARCHAR(255) NULL',
        'default_city' => 'ADD COLUMN default_city VARCHAR(100) NULL',
        'default_state' => 'ADD COLUMN default_state VARCHAR(100) NULL',
        'default_postal_code' => 'ADD COLUMN default_postal_code VARCHAR(20) NULL',
        'default_country' => 'ADD COLUMN default_country VARCHAR(50) NULL',
        'delivery_notes' => 'ADD COLUMN delivery_notes TEXT NULL',
        'comm_order_updates' => 'ADD COLUMN comm_order_updates TINYINT(1) NOT NULL DEFAULT 1',
        'comm_promos' => 'ADD COLUMN comm_promos TINYINT(1) NOT NULL DEFAULT 0',
        'last_login_at' => 'ADD COLUMN last_login_at DATETIME NULL',
    ];

    foreach ($additions as $column => $ddl) {
        if (!isset($columns[$column])) {
            $pdo->exec('ALTER TABLE Users ' . $ddl);
        }
    }

    // Ensure the 'deleted' role value exists in the ENUM
    try {
        $pdo->exec("ALTER TABLE Users MODIFY COLUMN role ENUM('admin','customer','deleted') NOT NULL DEFAULT 'customer'");
    } catch (PDOException $e) {
        // Already has the value - ignore.
    }

    $ensured = true;
}

/**
 * Get user by ID
 */
function get_user_by_id(int $user_id): ?array {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT user_id, username, email, first_name, last_name, phone, role, created_at, last_login_at, default_shipping_address, default_address_line2, default_city, default_state, default_postal_code, default_country, delivery_notes, comm_order_updates, comm_promos FROM Users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get user by ID including password hash (for auth-sensitive flows)
 */
function get_user_auth_by_id(int $user_id): ?array {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT user_id, username, email, first_name, last_name, password_hash, role, created_at, last_login_at FROM Users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get user by email
 */
function get_user_by_email(string $email): ?array {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT user_id, username, email, first_name, last_name, phone, password_hash, role, created_at, last_login_at FROM Users WHERE email = ?');
    $stmt->execute([$email]);
    return $stmt->fetch() ?: null;
}

/**
 * Get user by username
 */
function get_user_by_username(string $username): ?array {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT user_id, username, email, first_name, last_name, phone, password_hash, role FROM Users WHERE username = ?');
    $stmt->execute([$username]);
    return $stmt->fetch() ?: null;
}

/**
 * Create new user
 */
function create_user(string $username, string $email, string $password, string $role = 'customer'): int {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare('INSERT INTO Users (username, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $stmt->execute([$username, $email, $password_hash, $role]);
    
    return (int)$pdo->lastInsertId();
}

/**
 * Get all users with pagination
 */
function get_all_users(int $limit = 20, int $offset = 0): array {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT user_id, username, email, first_name, last_name, phone, role, created_at, last_login_at FROM Users ORDER BY user_id DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get total user count
 */
function get_user_count(): int {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'customer'");
    return (int)$stmt->fetchColumn();
}

/**
 * Delete user by ID.
 * Since Orders has ON DELETE RESTRICT, we anonymise the account and mark it
 * as 'deleted' so it's hidden from the admin UI. The row stays to satisfy the FK.
 */
/**
 * Delete user by ID.
 * Since Orders has ON DELETE RESTRICT, we anonymise the account and mark it
 * as 'deleted' so it's hidden from the admin UI. The row stays to satisfy the FK.
 */
function delete_user(int $user_id): bool {
    ensure_user_profile_columns(); // ensures 'deleted' ENUM value exists

    $anon_email    = 'deleted_' . $user_id . '_' . time() . '@deleted.invalid';
    $anon_username = 'deleted_' . $user_id;

    $stmt = get_pdo()->prepare("
        UPDATE Users SET
            username                 = ?,
            email                    = ?,
            password_hash            = '',
            first_name               = NULL,
            last_name                = NULL,
            phone                    = NULL,
            default_shipping_address = NULL,
            default_address_line2    = NULL,
            default_city             = NULL,
            default_state            = NULL,
            default_postal_code      = NULL,
            default_country          = NULL,
            delivery_notes           = NULL,
            comm_order_updates       = 0,
            comm_promos              = 0,
            role                     = 'deleted'
        WHERE user_id = ?
    ");
    return $stmt->execute([$anon_username, $anon_email, $user_id]);
}

/**
 * Update user profile
 */
function update_user(int $user_id, string $username, string $email): bool {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE Users SET username = ?, email = ? WHERE user_id = ?');
    return $stmt->execute([$username, $email, $user_id]);
}

function update_user_profile(
    int $user_id,
    string $username,
    string $email,
    ?string $first_name = null,
    ?string $last_name = null,
    ?string $phone = null,
    ?string $default_shipping_address = null,
    ?string $default_address_line2 = null,
    ?string $default_city = null,
    ?string $default_state = null,
    ?string $default_postal_code = null,
    ?string $default_country = null,
    ?string $delivery_notes = null,
    bool $comm_order_updates = true,
    bool $comm_promos = false
): bool {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE Users SET username = ?, email = ?, first_name = ?, last_name = ?, phone = ?, default_shipping_address = ?, default_address_line2 = ?, default_city = ?, default_state = ?, default_postal_code = ?, default_country = ?, delivery_notes = ?, comm_order_updates = ?, comm_promos = ? WHERE user_id = ?');
    return $stmt->execute([
        $username,
        $email,
        $first_name,
        $last_name,
        $phone,
        $default_shipping_address,
        $default_address_line2,
        $default_city,
        $default_state,
        $default_postal_code,
        $default_country,
        $delivery_notes,
        $comm_order_updates ? 1 : 0,
        $comm_promos ? 1 : 0,
        $user_id,
    ]);
}

/**
 * Anonymise a user account on self-deletion.
 * Delegates to delete_user() which wipes personal data and sets role = 'deleted'.
 */
function anonymise_user(int $user_id): bool {
    return delete_user($user_id);
}

function update_user_last_login(int $user_id): void {
    ensure_user_profile_columns();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE Users SET last_login_at = NOW() WHERE user_id = ?');
    $stmt->execute([$user_id]);
}

// ===== CATEGORIES FUNCTIONS =====

/**
 * Ensure Categories table has cover_image column.
 */
function ensure_category_extras(): void {
    static $ensured = false;
    if ($ensured) return;
    try {
        $pdo = get_pdo();
        $schema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
        if ($schema !== '') {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Categories'");
            $stmt->execute([$schema]);
            $cols = array_flip(array_column($stmt->fetchAll(), 'COLUMN_NAME'));
            if (!isset($cols['cover_image'])) {
                $pdo->exec("ALTER TABLE Categories ADD COLUMN cover_image VARCHAR(255) NULL");
            }
        }
    } catch (\Throwable $e) {
        // ALTER TABLE may fail on restricted DB users (e.g. Railway). Safe to continue —
        // get_all_categories() will still work; cover_image simply won't be available.
        error_log('ensure_category_extras: ' . $e->getMessage());
    }
    $ensured = true;
}

/**
 * Get all categories
 */
function get_all_categories(): array {
    ensure_category_extras();
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT category_id, name, description, cover_image FROM Categories ORDER BY name ASC');
    return $stmt->fetchAll();
}

/**
 * Get category by ID
 */
function get_category_by_id(int $category_id): ?array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT category_id, name, description FROM Categories WHERE category_id = ?');
    $stmt->execute([$category_id]);
    return $stmt->fetch() ?: null;
}

/**
 * Create category
 */
function create_category(string $name, string $description = ''): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO Categories (name, description) VALUES (?, ?)');
    $stmt->execute([$name, $description]);
    return (int)$pdo->lastInsertId();
}

/**
 * Update category
 */
function update_category(int $category_id, string $name, string $description): bool {
    ensure_category_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE Categories SET name = ?, description = ? WHERE category_id = ?');
    return $stmt->execute([$name, $description, $category_id]);
}

/**
 * Update category cover image
 */
function update_category_cover(int $category_id, string $image_url): bool {
    ensure_category_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE Categories SET cover_image = ? WHERE category_id = ?');
    return $stmt->execute([$image_url, $category_id]);
}

/**
 * Delete category
 */
function delete_category(int $category_id): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM Categories WHERE category_id = ?');
    return $stmt->execute([$category_id]);
}

// ===== PRODUCTS FUNCTIONS =====

/**
 * Ensure Products table has sku and tags columns, and Product_Variants table exists.
 * Uses the same lazy-migration pattern as ensure_user_profile_columns().
 */
function ensure_product_extras(): void {
    static $ensured = false;
    if ($ensured) return;

    $pdo = get_pdo();

    // Add sku / tags columns to Products if missing
    $schema = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($schema !== '') {
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Products'");
        $stmt->execute([$schema]);
        $cols = array_flip(array_column($stmt->fetchAll(), 'COLUMN_NAME'));

        if (!isset($cols['sku'])) {
            $pdo->exec("ALTER TABLE Products ADD COLUMN sku VARCHAR(80) NULL AFTER name");
        }
                if (!isset($cols['tags'])) {
            $pdo->exec("ALTER TABLE Products ADD COLUMN tags VARCHAR(500) NULL AFTER description");
        }
        if (isset($cols['video_url'])) {
            $pdo->exec("ALTER TABLE Products DROP COLUMN video_url");
        }
        if (!isset($cols['source_label'])) {
            $pdo->exec("ALTER TABLE Products ADD COLUMN source_label VARCHAR(120) NOT NULL DEFAULT 'London, United Kingdom' AFTER tags");
        }
        if (!isset($cols['condition_label'])) {
            $pdo->exec("ALTER TABLE Products ADD COLUMN condition_label VARCHAR(50) NOT NULL DEFAULT 'New' AFTER source_label");
        }
        if (!isset($cols['is_featured'])) {
            $pdo->exec("ALTER TABLE Products ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER tags");
        }
        if (!isset($cols['is_new_arrival'])) {
            $pdo->exec("ALTER TABLE Products ADD COLUMN is_new_arrival TINYINT(1) NOT NULL DEFAULT 0 AFTER is_featured");
        }
        if (!isset($cols['exclude_from_new_arrival'])) {
            $pdo->exec("ALTER TABLE Products ADD COLUMN exclude_from_new_arrival TINYINT(1) NOT NULL DEFAULT 0 AFTER is_new_arrival");
        }
    }

    // Create Product_Variants table if missing
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Product_Variants (
            variant_id      INT AUTO_INCREMENT PRIMARY KEY,
            product_id      INT NOT NULL,
            size_type       ENUM('clothing','footwear','one_size') NOT NULL DEFAULT 'clothing',
            size            VARCHAR(40) NOT NULL,
            quantity        INT NOT NULL DEFAULT 0,
            price_modifier  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            INDEX idx_pv_product (product_id),
            CONSTRAINT fk_pv_product
                FOREIGN KEY (product_id) REFERENCES Products(product_id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Add missing columns to existing Product_Variants table
    $schema2 = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($schema2 !== '') {
        $stmt2 = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Product_Variants'");
        $stmt2->execute([$schema2]);
        $vcols = array_flip(array_column($stmt2->fetchAll(), 'COLUMN_NAME'));
        if (!isset($vcols['size_type'])) {
            $pdo->exec("ALTER TABLE Product_Variants ADD COLUMN size_type ENUM('clothing','footwear','one_size') NOT NULL DEFAULT 'clothing' AFTER product_id");
        }
        if (!isset($vcols['price_modifier'])) {
            $pdo->exec("ALTER TABLE Product_Variants ADD COLUMN price_modifier DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER quantity");
        }
    }

    $ensured = true;
}

/**
 * Get variants for a product
 */
function get_product_variants(int $product_id): array {
    ensure_product_extras();
    $stmt = get_pdo()->prepare('SELECT variant_id, size_type, size, quantity, price_modifier FROM Product_Variants WHERE product_id = ? ORDER BY size_type, variant_id ASC');
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

/**
 * Validate and normalize admin-entered variants.
 * Returns [normalized_variants, errors].
 */
function validate_and_normalize_product_variants(array $variants): array {
    $errors = [];
    $normalized = [];
    $allowed_types = ['clothing', 'footwear', 'one_size'];
    $allowed_clothing = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL'];
    $clothing_aliases = [
        'EXTRASMALL' => 'XS',
        'SMALL' => 'S',
        'MEDIUM' => 'M',
        'LARGE' => 'L',
        'EXTRALARGE' => 'XL',
        'XXLARGE' => 'XXL',
    ];

    foreach ($variants as $i => $v) {
        $row_no = $i + 1;
        $type = (string)($v['size_type'] ?? 'clothing');
        $type = in_array($type, $allowed_types, true) ? $type : 'clothing';

        $size_raw = trim((string)($v['size'] ?? ''));
        if ($size_raw === '') {
            continue;
        }

        $qty = max(0, (int)($v['quantity'] ?? 0));
        $mod = round((float)($v['price_modifier'] ?? 0), 2);
        $size_normalized = $size_raw;

        if ($type === 'one_size') {
            $compact = strtoupper((string)preg_replace('/[^A-Z]/', '', $size_raw));
            if (!in_array($compact, ['ONESIZE', 'OS'], true)) {
                $errors[] = 'Variant row ' . $row_no . ': One Size type must use "One Size".';
                continue;
            }
            $size_normalized = 'One Size';
        } elseif ($type === 'clothing') {
            $upper = strtoupper($size_raw);
            $compact = strtoupper((string)preg_replace('/[^A-Z]/', '', $size_raw));
            if (isset($clothing_aliases[$compact])) {
                $upper = $clothing_aliases[$compact];
            }
            if (!in_array($upper, $allowed_clothing, true)) {
                $errors[] = 'Variant row ' . $row_no . ': Clothing size must be one of XXS, XS, S, M, L, XL, XXL.';
                continue;
            }
            $size_normalized = $upper;
        } else {
            $upper = strtoupper($size_raw);
            $eu = null;
            if (preg_match('/\b(3[5-9]|4[0-9])\b/', $upper, $m) === 1) {
                $eu = (int)$m[1];
            } elseif (ctype_digit($upper)) {
                $n = (int)$upper;
                if ($n >= 35 && $n <= 49) {
                    $eu = $n;
                }
            }

            if ($eu === null) {
                $errors[] = 'Variant row ' . $row_no . ': Footwear size must be EU 35-49 (e.g. 40 or EU 40).';
                continue;
            }

            $size_normalized = (string)$eu;
        }

        $normalized[] = [
            'size_type' => $type,
            'size' => $size_normalized,
            'quantity' => $qty,
            'price_modifier' => $mod,
        ];
    }

    if (empty($normalized)) {
        $errors[] = 'Add at least one valid size variant before saving.';
    }

    return [$normalized, $errors];
}

/**
 * Replace all variants for a product (delete + re-insert).
 * $variants = [['size_type' => 'footwear', 'size' => '42', 'quantity' => 5, 'price_modifier' => 0], ...]
 */
function save_product_variants(int $product_id, array $variants): void {
    ensure_product_extras();
    $pdo = get_pdo();
    $pdo->prepare('DELETE FROM Product_Variants WHERE product_id = ?')->execute([$product_id]);
    $stmt = $pdo->prepare('INSERT INTO Product_Variants (product_id, size_type, size, quantity, price_modifier) VALUES (?, ?, ?, ?, ?)');
    $allowed_types = ['clothing', 'footwear', 'one_size'];
    foreach ($variants as $v) {
        $size = trim((string)($v['size'] ?? ''));
        $qty  = max(0, (int)($v['quantity'] ?? 0));
        $type = in_array($v['size_type'] ?? '', $allowed_types, true) ? $v['size_type'] : 'clothing';
        $mod  = round((float)($v['price_modifier'] ?? 0), 2);
        if ($size !== '') {
            $stmt->execute([$product_id, $type, $size, $qty, $mod]);
        }
    }
}

/**
 * Get featured products for homepage (industry standard)
 * 
 * Returns manually selected featured products (limited to 4).
 * If fewer than 4 manual selections exist, does NOT auto-fill.
 * Admin must manually curate to maintain homepage quality.
 */
function get_featured_products(int $limit = 4): array {
    ensure_product_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT p.product_id, p.name, p.sku, p.description, p.tags, p.price, p.quantity,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name, p.added_at,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.is_featured = 1 AND p.is_active = 1
        ORDER BY p.product_id DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get count of featured products for admin warnings
 */
function count_featured_products(): int {
    ensure_product_extras();
    $pdo = get_pdo();
    return (int)$pdo->query('SELECT COUNT(*) FROM Products WHERE is_featured = 1 AND is_active = 1')->fetchColumn();
}

/**
 * Get new arrival products for homepage (industry standard)
 * 
 * Returns products that are either:
 * 1. Manually marked as new_arrival (manual curation)
 * 2. Added within last 30 days (auto-inclusion for freshness)
 * Limited to 4 total products.
 * 
 * This balances admin curation with automatic freshness.
 */
function get_new_arrival_products(int $limit = 4): array {
    ensure_product_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT p.product_id, p.name, p.sku, p.description, p.tags, p.price, p.quantity,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name, p.added_at,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.is_active = 1
                    AND p.exclude_from_new_arrival = 0
          AND (
            p.is_new_arrival = 1
            OR p.added_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          )
        ORDER BY p.added_at DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get count of new arrival products for admin warnings
 */
function count_new_arrival_products(): int {
    ensure_product_extras();
    $pdo = get_pdo();
    return (int)$pdo->query('
        SELECT COUNT(*) FROM Products WHERE is_active = 1
          AND exclude_from_new_arrival = 0
          AND (is_new_arrival = 1 OR added_at >= DATE_SUB(NOW(), INTERVAL 30 DAY))
    ')->fetchColumn();
}

/**
 * Admin: get products for homepage curation page.
 *
 * $section: 'featured'|'new_arrivals'
 * $scope:   'current'|'all'
 */
function get_admin_curation_products(
    string $section,
    string $scope = 'current',
    int $limit = 20,
    int $offset = 0,
    int $category_id = 0,
    string $search = ''
): array {
    ensure_product_extras();
    $pdo = get_pdo();

    $section = ($section === 'new_arrivals') ? 'new_arrivals' : 'featured';
    $scope = ($scope === 'all') ? 'all' : 'current';
    $search = trim($search);

    $where = ['p.is_active = 1'];
    $params = [];

    if ($category_id > 0) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = [$category_id, PDO::PARAM_INT];
    }

    if ($search !== '') {
        $where[] = '(p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)';
        $params[':search'] = ['%' . $search . '%', PDO::PARAM_STR];
    }

    $inSectionExpr = $section === 'featured'
        ? '(p.is_featured = 1)'
        : '((p.is_new_arrival = 1 OR p.added_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AND p.exclude_from_new_arrival = 0)';

    if ($scope === 'current') {
        $where[] = $inSectionExpr;
    }

    $sql = '
        SELECT p.product_id, p.name, p.sku, p.price, p.quantity, p.added_at,
               p.is_featured, p.is_new_arrival, p.exclude_from_new_arrival,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name,
               CASE WHEN p.added_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END as is_recent,
               CASE WHEN ' . $inSectionExpr . ' THEN 1 ELSE 0 END as is_in_section
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY is_in_section DESC, p.added_at DESC
        LIMIT :limit OFFSET :offset
    ';

    $stmt = $pdo->prepare($sql);
    foreach ($params as $param => [$value, $type]) {
        $stmt->bindValue($param, $value, $type);
    }
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function count_admin_curation_products(
    string $section,
    string $scope = 'current',
    int $category_id = 0,
    string $search = ''
): int {
    ensure_product_extras();
    $pdo = get_pdo();

    $section = ($section === 'new_arrivals') ? 'new_arrivals' : 'featured';
    $scope = ($scope === 'all') ? 'all' : 'current';
    $search = trim($search);

    $where = ['p.is_active = 1'];
    $params = [];

    if ($category_id > 0) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = [$category_id, PDO::PARAM_INT];
    }

    if ($search !== '') {
        $where[] = '(p.name LIKE :search OR p.description LIKE :search OR p.sku LIKE :search)';
        $params[':search'] = ['%' . $search . '%', PDO::PARAM_STR];
    }

    $inSectionExpr = $section === 'featured'
        ? '(p.is_featured = 1)'
        : '((p.is_new_arrival = 1 OR p.added_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AND p.exclude_from_new_arrival = 0)';

    if ($scope === 'current') {
        $where[] = $inSectionExpr;
    }

    $sql = 'SELECT COUNT(*) FROM Products p WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare($sql);
    foreach ($params as $param => [$value, $type]) {
        $stmt->bindValue($param, $value, $type);
    }
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

/**
 * Admin: set homepage curation membership.
 *
 * For featured: add/remove via is_featured.
 * For new_arrivals: add => include + manual mark, remove => unmark + exclude.
 */
function set_product_curation_membership(int $product_id, string $section, bool $enabled): bool {
    ensure_product_extras();
    $pdo = get_pdo();

    if ($section === 'featured') {
        $stmt = $pdo->prepare('UPDATE Products SET is_featured = ? WHERE product_id = ?');
        return $stmt->execute([$enabled ? 1 : 0, $product_id]);
    }

    if ($section === 'new_arrivals') {
        if ($enabled) {
            $stmt = $pdo->prepare('UPDATE Products SET is_new_arrival = 1, exclude_from_new_arrival = 0 WHERE product_id = ?');
            return $stmt->execute([$product_id]);
        }
        $stmt = $pdo->prepare('UPDATE Products SET is_new_arrival = 0, exclude_from_new_arrival = 1 WHERE product_id = ?');
        return $stmt->execute([$product_id]);
    }

    return false;
}

/**
 * Get all products with pagination
 */
function get_all_products(int $limit = ITEMS_PER_PAGE, int $offset = 0, int $category_id = 0): array {
    ensure_product_extras();
    $pdo = get_pdo();
    $where = $category_id > 0 ? 'WHERE p.category_id = ?' : '';
    $stmt = $pdo->prepare('
        SELECT p.product_id, p.name, p.sku, p.description, p.tags, p.price, p.quantity,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name, p.added_at,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        ' . $where . '
        ORDER BY p.product_id DESC
        LIMIT ? OFFSET ?
    ');

    $i = 1;
    if ($category_id > 0) $stmt->bindValue($i++, $category_id, PDO::PARAM_INT);
    $stmt->bindValue($i++, $limit, PDO::PARAM_INT);
    $stmt->bindValue($i,   $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_catalog_products(
    ?int $category_id,
    string $search,
    string $sort,
    string $availability,
    ?float $min_price = null,
    ?float $max_price = null,
    int $limit = ITEMS_PER_PAGE,
    int $offset = 0,
    string $tag = ''
): array {
    $pdo = get_pdo();
    $search = trim($search);

    $orderBy = match ($sort) {
        'price_asc' => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name_asc' => 'p.name ASC',
        default => 'p.product_id DESC',
    };

    $where = [];
    $params = [];

    if ($category_id !== null && $category_id > 0) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = [$category_id, PDO::PARAM_INT];
    }

    if ($search !== '') {
        $where[] = '(p.name LIKE :search OR p.description LIKE :search)';
        $params[':search'] = ['%' . $search . '%', PDO::PARAM_STR];

        if ($sort === 'newest') {
            $orderBy = 'CASE
                WHEN p.name = :search_exact THEN 0
                WHEN p.name LIKE :search_prefix THEN 1
                WHEN p.description LIKE :search_prefix THEN 2
                ELSE 3
            END, p.product_id DESC';
            $params[':search_exact'] = [$search, PDO::PARAM_STR];
            $params[':search_prefix'] = [$search . '%', PDO::PARAM_STR];
        }
    }

    if ($availability === 'in_stock') {
        $where[] = 'p.quantity > 0';
    }

    if ($min_price !== null) {
        $where[] = 'p.price >= :min_price';
        $params[':min_price'] = [$min_price, PDO::PARAM_STR];
    }

    if ($max_price !== null) {
        $where[] = 'p.price <= :max_price';
        $params[':max_price'] = [$max_price, PDO::PARAM_STR];
    }

    $tag = trim($tag);
    if ($tag !== '') {
        $where[] = 'FIND_IN_SET(:tag, REPLACE(p.tags, \', \', \',\')) > 0';
        $params[':tag'] = [$tag, PDO::PARAM_STR];
    }

    $sql = '
        SELECT p.product_id, p.name, p.sku, p.description, p.tags, p.price, p.quantity,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name, p.added_at,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
    ';

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset';

    $stmt = $pdo->prepare($sql);

    foreach ($params as $param => [$value, $type]) {
        $stmt->bindValue($param, $value, $type);
    }

    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function count_catalog_products(?int $category_id, string $search, string $availability, ?float $min_price = null, ?float $max_price = null, string $tag = ''): int {
    $pdo = get_pdo();

    $where = [];
    $params = [];

    if ($category_id !== null && $category_id > 0) {
        $where[] = 'p.category_id = :category_id';
        $params[':category_id'] = [$category_id, PDO::PARAM_INT];
    }

    $search = trim($search);
    if ($search !== '') {
        $where[] = '(p.name LIKE :search OR p.description LIKE :search)';
        $params[':search'] = ['%' . $search . '%', PDO::PARAM_STR];
    }

    if ($availability === 'in_stock') {
        $where[] = 'p.quantity > 0';
    }

    if ($min_price !== null) {
        $where[] = 'p.price >= :min_price';
        $params[':min_price'] = [$min_price, PDO::PARAM_STR];
    }

    if ($max_price !== null) {
        $where[] = 'p.price <= :max_price';
        $params[':max_price'] = [$max_price, PDO::PARAM_STR];
    }

    $tag = trim($tag);
    if ($tag !== '') {
        $where[] = 'FIND_IN_SET(:tag, REPLACE(p.tags, \', \', \',\')) > 0';
        $params[':tag'] = [$tag, PDO::PARAM_STR];
    }

    $sql = 'SELECT COUNT(*) FROM Products p';
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $param => [$value, $type]) {
        $stmt->bindValue($param, $value, $type);
    }
    $stmt->execute();

    return (int)$stmt->fetchColumn();
}

/**
 * Get all distinct tags across all products as a flat sorted array.
 */
function get_all_product_tags(): array {
    ensure_product_extras();
    $stmt = get_pdo()->query("SELECT tags FROM Products WHERE tags IS NOT NULL AND tags != ''");
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tags = [];
    foreach ($rows as $raw) {
        foreach (explode(',', (string)$raw) as $t) {
            $t = trim($t);
            if ($t !== '') $tags[strtolower($t)] = $t;
        }
    }
    ksort($tags);
    return array_values($tags);
}

/**
 * Get products by category
 */
function get_products_by_category(int $category_id, int $limit = ITEMS_PER_PAGE, int $offset = 0): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT p.product_id, p.name, p.description, p.price, p.quantity, 
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name, p.added_at,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.category_id = ?
        ORDER BY p.product_id DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get related products from same category (excluding current product)
 */
function get_related_products(int $product_id, int $category_id, int $limit = 4): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT p.product_id, p.name, p.description, p.price, p.quantity,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name, p.added_at,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.category_id = ? AND p.product_id != ?
        ORDER BY p.product_id DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $category_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $product_id, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get product by ID with details
 */
function get_product_by_id(int $product_id): ?array {
    ensure_product_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
           SELECT p.product_id, p.name, p.sku, p.description, p.tags, p.source_label, p.condition_label, p.price, p.quantity,
               p.is_featured, p.is_new_arrival,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name, p.added_at,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.product_id = ?
    ');
    $stmt->execute([$product_id]);
    return $stmt->fetch() ?: null;
}

/**
 * Search products
 */
function search_products(string $query, int $limit = ITEMS_PER_PAGE, int $offset = 0): array {
    $pdo = get_pdo();
    $search = '%' . $query . '%';
    $safeLimit = max(1, $limit);
    $safeOffset = max(0, $offset);
    $stmt = $pdo->prepare('
        SELECT p.product_id, p.name, p.description, p.price, p.quantity, 
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.name LIKE ? OR p.description LIKE ?
        ORDER BY p.product_id DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, $search, PDO::PARAM_STR);
    $stmt->bindValue(2, $search, PDO::PARAM_STR);
    $stmt->bindValue(3, $safeLimit, PDO::PARAM_INT);
    $stmt->bindValue(4, $safeOffset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get product suggestions for autosuggest search
 */
function get_product_suggestions(string $query, int $limit = 8): array {
    $pdo = get_pdo();
    $search = trim($query);

    if ($search === '') {
        return [];
    }

    $limit = max(1, min(12, $limit));
    $like = '%' . $search . '%';
    $prefix = $search . '%';

    $sql = '
        SELECT p.product_id, p.name, p.price,
               c.category_id, COALESCE(c.name, \'Uncategorized\') as category_name,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.name LIKE :like OR p.description LIKE :like
        ORDER BY CASE
            WHEN p.name = :exact THEN 0
            WHEN p.name LIKE :prefix THEN 1
            WHEN p.description LIKE :prefix THEN 2
            ELSE 3
        END, p.product_id DESC
        LIMIT :limit
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':like', $like, PDO::PARAM_STR);
    $stmt->bindValue(':exact', $search, PDO::PARAM_STR);
    $stmt->bindValue(':prefix', $prefix, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function count_search_products(string $query): int {
    $pdo = get_pdo();
    $search = '%' . $query . '%';
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Products WHERE name LIKE ? OR description LIKE ?');
    $stmt->execute([$search, $search]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get total product count
 */
function get_product_count(): int {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT COUNT(*) FROM Products');
    return (int)$stmt->fetchColumn();
}

/**
 * Get product count by category
 */
function get_product_count_by_category(int $category_id): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Products WHERE category_id = ?');
    $stmt->execute([$category_id]);
    return (int)$stmt->fetchColumn();
}

/**
 * Create product
 */
function create_product(string $name, string $description, int $category_id, float $price, int $quantity, ?string $sku = null, ?string $tags = null, string $source_label = 'London, United Kingdom', string $condition_label = 'New'): int {
    ensure_product_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        INSERT INTO Products (name, sku, description, tags, source_label, condition_label, category_id, price, quantity)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([$name, $sku ?: null, $description, $tags ?: null, $source_label, $condition_label, $category_id, $price, $quantity]);
    return (int)$pdo->lastInsertId();
}

/**
 * Update product
 */
function update_product(int $product_id, string $name, string $description, int $category_id, float $price, int $quantity, ?string $sku = null, ?string $tags = null, string $source_label = 'London, United Kingdom', string $condition_label = 'New', bool $is_featured = false, bool $is_new_arrival = false): bool {
    ensure_product_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        UPDATE Products
        SET name = ?, sku = ?, description = ?, tags = ?, source_label = ?, condition_label = ?, category_id = ?, price = ?, quantity = ?, is_featured = ?, is_new_arrival = ?
        WHERE product_id = ?
    ');
    return $stmt->execute([$name, $sku ?: null, $description, $tags ?: null, $source_label, $condition_label, $category_id, $price, $quantity, $is_featured ? 1 : 0, $is_new_arrival ? 1 : 0, $product_id]);
}

/**
 * Update product details without changing homepage curation flags.
 */
function update_product_core(int $product_id, string $name, string $description, int $category_id, float $price, int $quantity, ?string $sku = null, ?string $tags = null, string $source_label = 'London, United Kingdom', string $condition_label = 'New'): bool {
    ensure_product_extras();
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        UPDATE Products
        SET name = ?, sku = ?, description = ?, tags = ?, source_label = ?, condition_label = ?, category_id = ?, price = ?, quantity = ?
        WHERE product_id = ?
    ');
    return $stmt->execute([$name, $sku ?: null, $description, $tags ?: null, $source_label, $condition_label, $category_id, $price, $quantity, $product_id]);
}

/**
 * Delete product
 */
function delete_product(int $product_id): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM Products WHERE product_id = ?');
    return $stmt->execute([$product_id]);
}

// ===== PRODUCT IMAGES FUNCTIONS =====

/**
 * Get product images
 */
function get_product_images(int $product_id): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT image_id, image_url, is_primary, sort_order FROM Product_Images WHERE product_id = ? ORDER BY sort_order ASC, is_primary DESC, uploaded_at ASC');
    $stmt->execute([$product_id]);
    return $stmt->fetchAll();
}

/**
 * Get primary product image
 */
function get_primary_product_image(int $product_id): ?string {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT image_url FROM Product_Images WHERE product_id = ? AND is_primary = TRUE LIMIT 1');
    $stmt->execute([$product_id]);
    $result = $stmt->fetch();
    return $result ? $result['image_url'] : null;
}

/**
 * Add product image
 */
function add_product_image(int $product_id, string $image_url, bool $is_primary = false): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO Product_Images (product_id, image_url, is_primary) VALUES (?, ?, ?)');
    $stmt->execute([$product_id, $image_url, (int)$is_primary]);
    return (int)$pdo->lastInsertId();
}

/**
 * Delete product image
 */
function delete_product_image(int $image_id): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM Product_Images WHERE image_id = ?');
    return $stmt->execute([$image_id]);
}

// ===== ORDERS FUNCTIONS =====

/**
 * Get order by ID
 */
function get_order_by_id(int $order_id): ?array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT order_id, user_id, order_date, total_amount, status,
               shipping_address, city, postal_code, phone,
               payment_method, payment_status
        FROM Orders
        WHERE order_id = ?
    ');
    $stmt->execute([$order_id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get user's orders
 */
function get_user_orders(int $user_id, int $limit = 20, int $offset = 0): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT order_id, user_id, order_date, total_amount, status,
               shipping_address, city, postal_code, phone,
               payment_method, payment_status
        FROM Orders
        WHERE user_id = ?
        ORDER BY order_date DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get all orders (admin)
 */
function get_all_orders(int $limit = 20, int $offset = 0): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT o.order_id, o.user_id, u.username, u.email, o.order_date, o.total_amount, o.status
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        ORDER BY o.order_date DESC
        LIMIT ? OFFSET ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Create order
 */
function create_order(?int $user_id, float $total_amount, ?string $shipping_address = null, ?string $city = null, ?string $postal_code = null, ?string $phone = null, string $payment_method = 'cash', string $payment_status = 'pending'): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('INSERT INTO Orders (user_id, total_amount, status, shipping_address, city, postal_code, phone, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$user_id, $total_amount, 'pending', $shipping_address, $city, $postal_code, $phone, $payment_method, $payment_status]);
    return (int)$pdo->lastInsertId();
}

/**
 * Update order status
 */
function update_order_status(int $order_id, string $status): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE Orders SET status = ? WHERE order_id = ?');
    return $stmt->execute([$status, $order_id]);
}

/**
 * Update order payment status
 */
function update_order_payment_status(int $order_id, string $payment_status): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('UPDATE Orders SET payment_status = ? WHERE order_id = ?');
    return $stmt->execute([$payment_status, $order_id]);
}

/**
 * Delete an order and its items.
 * Only call this on cancelled orders - paid orders should be retained for financial records.
 * Order_Items has ON DELETE CASCADE so items are removed automatically.
 */
function delete_order(int $order_id): bool {
    $pdo  = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM Orders WHERE order_id = ?');
    return $stmt->execute([$order_id]);
}

/**
 * Get total order count
 */
function get_order_count(): int {
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT COUNT(*) FROM Orders');
    return (int)$stmt->fetchColumn();
}

// ===== ORDER ITEMS FUNCTIONS =====

/**
 * Get order items
 */
function get_order_items(int $order_id): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT oi.order_item_id, oi.product_id, p.name, oi.quantity, oi.price
        FROM Order_Items oi
        JOIN Products p ON oi.product_id = p.product_id
        WHERE oi.order_id = ?
    ');
    $stmt->execute([$order_id]);
    return $stmt->fetchAll();
}

/**
 * Add item to order
 */
function add_order_item(int $order_id, int $product_id, int $quantity, float $price): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        INSERT INTO Order_Items (order_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$order_id, $product_id, $quantity, $price]);
    return (int)$pdo->lastInsertId();
}

/**
 * Delete order item
 */
function delete_order_item(int $order_item_id): bool {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM Order_Items WHERE order_item_id = ?');
    return $stmt->execute([$order_item_id]);
}

/**
 * Get total category count
 */
function get_category_count(): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM Categories');
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

/**
 * Get recent orders with pagination
 */
function get_recent_orders(int $limit = 5): array {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        SELECT o.order_id, o.user_id, o.total_amount, o.status, o.order_date,
               u.username, u.email
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        ORDER BY o.order_date DESC
        LIMIT ?
    ');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ===== CONTACT MESSAGES =====

function ensure_contact_messages_table(): void {
    static $ensured = false;
    if ($ensured) return;
    get_pdo()->exec('
        CREATE TABLE IF NOT EXISTS contact_messages (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            name        VARCHAR(120)  NOT NULL,
            email       VARCHAR(255)  NOT NULL,
            subject     VARCHAR(255)  NOT NULL,
            message     TEXT          NOT NULL,
            ip_address  VARCHAR(45)   NULL,
            created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_contact_email (email),
            INDEX idx_contact_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $ensured = true;
}

// ===== PRODUCT REVIEWS =====

function ensure_product_reviews_table(): void {
    static $ensured = false;
    if ($ensured) return;

    $pdo = get_pdo();
    $pdo->exec('
        CREATE TABLE IF NOT EXISTS Product_Reviews (
            review_id            INT AUTO_INCREMENT PRIMARY KEY,
            product_id           INT NOT NULL,
            user_id              INT NOT NULL,
            rating               TINYINT NOT NULL,
            title                VARCHAR(120) NULL,
            review_text          TEXT NOT NULL,
            media_url            VARCHAR(255) NULL,
            status               ENUM("pending","approved","rejected") NOT NULL DEFAULT "pending",
            is_verified_purchase TINYINT(1) NOT NULL DEFAULT 1,
            approved_by          INT NULL,
            approved_at          DATETIME NULL,
            created_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_product_user_review (product_id, user_id),
            INDEX idx_reviews_product_status (product_id, status, created_at),
            INDEX idx_reviews_status (status),
            INDEX idx_reviews_user (user_id),
            CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES Products(product_id) ON DELETE CASCADE,
            CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
            CONSTRAINT fk_reviews_approved_by FOREIGN KEY (approved_by) REFERENCES Users(user_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    try {
        $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'Product_Reviews'");
        $stmt->execute([DB_NAME]);
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('media_url', $cols, true)) {
            $pdo->exec('ALTER TABLE Product_Reviews ADD COLUMN media_url VARCHAR(255) NULL AFTER review_text');
        }
    } catch (Throwable $e) {
        // Non-fatal: table already exists or migration race.
    }

    $ensured = true;
}

function ensure_product_review_helpful_table(): void {
    static $ensured = false;
    if ($ensured) return;

    ensure_product_reviews_table();
    get_pdo()->exec('
        CREATE TABLE IF NOT EXISTS Product_Review_Helpful (
            vote_id      INT AUTO_INCREMENT PRIMARY KEY,
            review_id    INT NOT NULL,
            user_id      INT NOT NULL,
            is_helpful   TINYINT(1) NOT NULL DEFAULT 1,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_review_user_vote (review_id, user_id),
            INDEX idx_helpful_review (review_id, is_helpful),
            INDEX idx_helpful_user (user_id),
            CONSTRAINT fk_helpful_review FOREIGN KEY (review_id) REFERENCES Product_Reviews(review_id) ON DELETE CASCADE,
            CONSTRAINT fk_helpful_user FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');

    $ensured = true;
}

function has_user_purchased_product(int $user_id, int $product_id): bool {
    $stmt = get_pdo()->prepare('
        SELECT 1
        FROM Orders o
        JOIN Order_Items oi ON oi.order_id = o.order_id
        WHERE o.user_id = ?
          AND oi.product_id = ?
          AND o.status IN ("processing", "shipped", "delivered")
        LIMIT 1
    ');
    $stmt->execute([$user_id, $product_id]);
    return (bool)$stmt->fetchColumn();
}

function get_product_review_summary(int $product_id): array {
    ensure_product_reviews_table();
    $stmt = get_pdo()->prepare('
        SELECT
            COUNT(*) AS review_count,
            COALESCE(AVG(rating), 0) AS average_rating
        FROM Product_Reviews
        WHERE product_id = ? AND status = "approved"
    ');
    $stmt->execute([$product_id]);
    $row = $stmt->fetch() ?: [];

    return [
        'review_count' => (int)($row['review_count'] ?? 0),
        'average_rating' => (float)($row['average_rating'] ?? 0),
    ];
}

function get_product_review_breakdown(int $product_id): array {
    ensure_product_reviews_table();

    $ratings = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $stmt = get_pdo()->prepare('
        SELECT rating, COUNT(*) AS rating_count
        FROM Product_Reviews
        WHERE product_id = ? AND status = "approved"
        GROUP BY rating
    ');
    $stmt->execute([$product_id]);

    foreach ($stmt->fetchAll() as $row) {
        $rating = (int)($row['rating'] ?? 0);
        if ($rating >= 1 && $rating <= 5) {
            $ratings[$rating] = (int)($row['rating_count'] ?? 0);
        }
    }

    $total = array_sum($ratings);
    $percentages = [5 => 0.0, 4 => 0.0, 3 => 0.0, 2 => 0.0, 1 => 0.0];
    if ($total > 0) {
        foreach ($ratings as $rating => $count) {
            $percentages[$rating] = round(($count / $total) * 100, 1);
        }
    }

    return [
        'total' => $total,
        'ratings' => $ratings,
        'percentages' => $percentages,
    ];
}

function get_product_reviews(
    int $product_id,
    int $limit = 10,
    int $offset = 0,
    string $sort = 'most_recent',
    int $rating_filter = 0,
    bool $with_media_only = false,
    bool $verified_only = false,
    string $search = ''
): array {
    ensure_product_reviews_table();
    ensure_product_review_helpful_table();

    $sort_map = [
        'most_recent' => 'r.created_at DESC',
        'most_helpful' => 'COALESCE(h.helpful_count, 0) DESC, r.created_at DESC',
        'highest_rating' => 'r.rating DESC, r.created_at DESC',
        'lowest_rating' => 'r.rating ASC, r.created_at DESC',
    ];
    $order_by = $sort_map[$sort] ?? $sort_map['most_recent'];

    $sql = '
        SELECT r.review_id, r.rating, r.title, r.review_text, r.media_url, r.created_at,
               r.is_verified_purchase, u.user_id, u.username,
               COALESCE(h.helpful_count, 0) AS helpful_count
        FROM Product_Reviews r
        JOIN Users u ON u.user_id = r.user_id
        LEFT JOIN (
            SELECT review_id, COUNT(*) AS helpful_count
            FROM Product_Review_Helpful
            WHERE is_helpful = 1
            GROUP BY review_id
        ) h ON h.review_id = r.review_id
        WHERE r.product_id = :product_id AND r.status = "approved"
    ';

    if ($rating_filter >= 1 && $rating_filter <= 5) {
        $sql .= ' AND r.rating = :rating_filter';
    }
    if ($with_media_only) {
        $sql .= ' AND r.media_url IS NOT NULL AND TRIM(r.media_url) <> ""';
    }
    if ($verified_only) {
        $sql .= ' AND r.is_verified_purchase = 1';
    }
    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (r.title LIKE :search OR r.review_text LIKE :search)';
    }

    $sql .= ' ORDER BY ' . $order_by . ' LIMIT :limit OFFSET :offset';

    $stmt = get_pdo()->prepare($sql);
    $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
    if ($rating_filter >= 1 && $rating_filter <= 5) {
        $stmt->bindValue(':rating_filter', $rating_filter, PDO::PARAM_INT);
    }
    if ($search !== '') {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function count_product_reviews(
    int $product_id,
    int $rating_filter = 0,
    bool $with_media_only = false,
    bool $verified_only = false,
    string $search = ''
): int {
    ensure_product_reviews_table();

    $sql = '
        SELECT COUNT(*)
        FROM Product_Reviews r
        WHERE r.product_id = :product_id
          AND r.status = "approved"
    ';
    if ($rating_filter >= 1 && $rating_filter <= 5) {
        $sql .= ' AND r.rating = :rating_filter';
    }
    if ($with_media_only) {
        $sql .= ' AND r.media_url IS NOT NULL AND TRIM(r.media_url) <> ""';
    }
    if ($verified_only) {
        $sql .= ' AND r.is_verified_purchase = 1';
    }
    $search = trim($search);
    if ($search !== '') {
        $sql .= ' AND (r.title LIKE :search OR r.review_text LIKE :search)';
    }

    $stmt = get_pdo()->prepare($sql);
    $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
    if ($rating_filter >= 1 && $rating_filter <= 5) {
        $stmt->bindValue(':rating_filter', $rating_filter, PDO::PARAM_INT);
    }
    if ($search !== '') {
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function get_user_helpful_votes_for_reviews(int $user_id, array $review_ids): array {
    ensure_product_review_helpful_table();
    $review_ids = array_values(array_unique(array_map('intval', $review_ids)));
    if (empty($review_ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($review_ids), '?'));
    $sql = '
        SELECT review_id
        FROM Product_Review_Helpful
        WHERE user_id = ?
          AND is_helpful = 1
          AND review_id IN (' . $placeholders . ')
    ';

    $stmt = get_pdo()->prepare($sql);
    $stmt->execute(array_merge([$user_id], $review_ids));

    $voted = [];
    foreach ($stmt->fetchAll() as $row) {
        $voted[(int)$row['review_id']] = true;
    }
    return $voted;
}

function mark_review_helpful_vote(int $review_id, int $user_id, int $product_id): bool {
    ensure_product_review_helpful_table();

    $stmt = get_pdo()->prepare('
        INSERT INTO Product_Review_Helpful (review_id, user_id, is_helpful)
        SELECT r.review_id, ?, 1
        FROM Product_Reviews r
        WHERE r.review_id = ?
          AND r.product_id = ?
          AND r.status = "approved"
          AND r.user_id <> ?
        ON DUPLICATE KEY UPDATE
            is_helpful = VALUES(is_helpful),
            created_at = CURRENT_TIMESTAMP
    ');

    return $stmt->execute([$user_id, $review_id, $product_id, $user_id]);
}

function get_user_product_review(int $user_id, int $product_id): ?array {
    ensure_product_reviews_table();
    $stmt = get_pdo()->prepare('
        SELECT review_id, rating, title, review_text, media_url, status, created_at, updated_at
        FROM Product_Reviews
        WHERE user_id = ? AND product_id = ?
        LIMIT 1
    ');
    $stmt->execute([$user_id, $product_id]);
    return $stmt->fetch() ?: null;
}

function upsert_product_review(int $user_id, int $product_id, int $rating, string $title, string $review_text, ?string $media_url = null): void {
    ensure_product_reviews_table();
    $verified = has_user_purchased_product($user_id, $product_id) ? 1 : 0;
    $title = trim($title);
    $media_url = trim((string)$media_url);
    if ($media_url === '') {
        $media_url = null;
    }

    $stmt = get_pdo()->prepare('
        INSERT INTO Product_Reviews (user_id, product_id, rating, title, review_text, media_url, status, is_verified_purchase)
        VALUES (?, ?, ?, ?, ?, ?, "pending", ?)
        ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            title = VALUES(title),
            review_text = VALUES(review_text),
            media_url = VALUES(media_url),
            status = "pending",
            is_verified_purchase = VALUES(is_verified_purchase),
            approved_by = NULL,
            approved_at = NULL,
            updated_at = CURRENT_TIMESTAMP
    ');

    $stmt->execute([
        $user_id,
        $product_id,
        $rating,
        $title !== '' ? $title : null,
        $review_text,
        $media_url,
        $verified,
    ]);
}

function get_admin_product_reviews(string $status = 'all', string $search = '', int $limit = 30, int $offset = 0): array {
    ensure_product_reviews_table();

    $where = [];
    $params = [];
    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $where[] = 'r.status = :status';
        $params[':status'] = [$status, PDO::PARAM_STR];
    }

    $search = trim($search);
    if ($search !== '') {
        $where[] = '(p.name LIKE :search OR u.username LIKE :search OR r.title LIKE :search OR r.review_text LIKE :search)';
        $params[':search'] = ['%' . $search . '%', PDO::PARAM_STR];
    }

    $sql = '
        SELECT r.review_id, r.rating, r.title, r.review_text, r.status, r.is_verified_purchase,
               r.created_at, r.updated_at, p.product_id, p.name AS product_name,
               u.user_id, u.username
        FROM Product_Reviews r
        JOIN Products p ON p.product_id = r.product_id
        JOIN Users u ON u.user_id = r.user_id
    ';

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset';

    $stmt = get_pdo()->prepare($sql);
    foreach ($params as $param => [$value, $type]) {
        $stmt->bindValue($param, $value, $type);
    }
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->bindValue(':offset', max(0, $offset), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function count_admin_product_reviews(string $status = 'all', string $search = ''): int {
    ensure_product_reviews_table();

    $where = [];
    $params = [];
    if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
        $where[] = 'r.status = :status';
        $params[':status'] = [$status, PDO::PARAM_STR];
    }

    $search = trim($search);
    if ($search !== '') {
        $where[] = '(p.name LIKE :search OR u.username LIKE :search OR r.title LIKE :search OR r.review_text LIKE :search)';
        $params[':search'] = ['%' . $search . '%', PDO::PARAM_STR];
    }

    $sql = '
        SELECT COUNT(*)
        FROM Product_Reviews r
        JOIN Products p ON p.product_id = r.product_id
        JOIN Users u ON u.user_id = r.user_id
    ';
    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $stmt = get_pdo()->prepare($sql);
    foreach ($params as $param => [$value, $type]) {
        $stmt->bindValue($param, $value, $type);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function update_product_review_status(int $review_id, string $status, int $admin_user_id): bool {
    ensure_product_reviews_table();
    if (!in_array($status, ['pending', 'approved', 'rejected'], true)) {
        return false;
    }

    if ($status === 'approved') {
        $stmt = get_pdo()->prepare('UPDATE Product_Reviews SET status = ?, approved_by = ?, approved_at = NOW() WHERE review_id = ?');
        return $stmt->execute([$status, $admin_user_id, $review_id]);
    }

    $stmt = get_pdo()->prepare('UPDATE Product_Reviews SET status = ?, approved_by = NULL, approved_at = NULL WHERE review_id = ?');
    return $stmt->execute([$status, $review_id]);
}

function delete_product_review(int $review_id): bool {
    ensure_product_reviews_table();
    $stmt = get_pdo()->prepare('DELETE FROM Product_Reviews WHERE review_id = ?');
    return $stmt->execute([$review_id]);
}

// ===== WISHLIST FUNCTIONS =====

function ensure_wishlist_table(): void {
    static $ensured = false;
    if ($ensured) return;
    get_pdo()->exec('
        CREATE TABLE IF NOT EXISTS Wishlist (
            wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NOT NULL,
            product_id  INT NOT NULL,
            added_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_wishlist (user_id, product_id),
            INDEX idx_wishlist_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ');
    $ensured = true;
}

function is_in_wishlist(int $user_id, int $product_id): bool {
    ensure_wishlist_table();
    $stmt = get_pdo()->prepare('SELECT 1 FROM Wishlist WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$user_id, $product_id]);
    return (bool)$stmt->fetchColumn();
}

function toggle_wishlist(int $user_id, int $product_id): string {
    ensure_wishlist_table();
    if (is_in_wishlist($user_id, $product_id)) {
        get_pdo()->prepare('DELETE FROM Wishlist WHERE user_id = ? AND product_id = ?')->execute([$user_id, $product_id]);
        return 'removed';
    }
    get_pdo()->prepare('INSERT IGNORE INTO Wishlist (user_id, product_id) VALUES (?, ?)')->execute([$user_id, $product_id]);
    return 'added';
}

function get_user_wishlist(int $user_id): array {
    ensure_wishlist_table();
    $stmt = get_pdo()->prepare('
        SELECT w.wishlist_id, w.added_at,
               p.product_id, p.name, p.price, p.quantity,
               COALESCE(c.name, \'Uncategorized\') AS category_name,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Wishlist w
        JOIN Products p ON w.product_id = p.product_id
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
    ');
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}


function get_user_wishlist_product_ids(int $user_id): array {
    ensure_wishlist_table();
    $stmt = get_pdo()->prepare('SELECT product_id FROM Wishlist WHERE user_id = ?');
    $stmt->execute([$user_id]);
    return array_column($stmt->fetchAll(), 'product_id');
}

function get_guest_wishlist(array $product_ids): array {
    if (empty($product_ids)) return [];
    $placeholders = str_repeat('?,', count($product_ids) - 1) . '?';
    $stmt = get_pdo()->prepare("
        SELECT p.product_id, p.name, p.price, p.quantity,
               COALESCE(c.name, 'Uncategorized') AS category_name,
               (SELECT image_url FROM Product_Images WHERE product_id = p.product_id ORDER BY is_primary DESC, image_id ASC LIMIT 1) AS image_url
        FROM Products p
        LEFT JOIN Categories c ON p.category_id = c.category_id
        WHERE p.product_id IN ($placeholders)
    ");
    $stmt->execute($product_ids);
    return $stmt->fetchAll();
}

// ===== CONTACT MESSAGES =====

function decrement_product_stock(int $product_id, int $qty): void {
    $pdo = get_pdo();
    $pdo->prepare('UPDATE Products SET quantity = MAX(0, quantity - ?) WHERE product_id = ?')
        ->execute([$qty, $product_id]);
}

function save_contact_message(string $name, string $email, string $subject, string $message): int {
    ensure_contact_messages_table();
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $stmt = get_pdo()->prepare('INSERT INTO contact_messages (name, email, subject, message, ip_address) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$name, $email, $subject, $message, $ip ?: null]);
    return (int)get_pdo()->lastInsertId();
}


