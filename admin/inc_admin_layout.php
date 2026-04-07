<?php
/**
 * London Labels — Admin Layout Shell
 * Standalone layout for all admin pages. Does NOT use the public header/footer.
 * Include at the top of every admin page AFTER setting $page_title and calling require_admin().
 */
if (!defined('BASE_URL')) require_once __DIR__ . '/../functions.php';

$_admin_user     = get_user_by_id((int) current_user_id());
$_admin_first    = trim((string)($_admin_user['first_name'] ?? ''));
$_admin_display  = $_admin_first !== '' ? $_admin_first : (current_user_name() ?? 'Admin');
$_admin_email    = current_user_email() ?? '';

$_admin_page     = basename($_SERVER['PHP_SELF'] ?? '');
$_admin_dir      = basename(dirname($_SERVER['PHP_SELF'] ?? ''));

function _admin_active(string ...$pages): string {
    global $_admin_page;
    return in_array($_admin_page, $pages, true) ? 'active' : '';
}

$_meta_title = isset($page_title) ? htmlspecialchars($page_title, ENT_QUOTES) . ' — Admin' : 'Admin — London Labels';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= $_meta_title ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/assets/images/favicon.svg">
    <link rel="preload" href="<?= BASE_URL ?>/assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>"></noscript>
    <link rel="preload" href="<?= BASE_URL ?>/assets/admin.css?v=<?= filemtime(__DIR__ . '/../assets/admin.css') ?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?= BASE_URL ?>/assets/admin.css?v=<?= filemtime(__DIR__ . '/../assets/admin.css') ?>"></noscript>
    <script>window.BASE_URL = '<?= BASE_URL ?>';</script>
</head>
<body class="admin-body">

<div class="admin-shell">

    <!-- ===== SIDEBAR ===== -->
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
        <div class="admin-sidebar-brand">
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-brand-link">
                <span class="admin-brand-name">London Labels</span>
                <span class="admin-brand-tag">Admin</span>
            </a>
        </div>

        <nav class="admin-nav" aria-label="Admin menu">
            <div class="admin-nav-section-label">Overview</div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-nav-link <?= _admin_active('dashboard.php') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>

            <div class="admin-nav-section-label">Catalogue</div>
            <a href="<?= BASE_URL ?>/admin/products.php" class="admin-nav-link <?= _admin_active('products.php', 'product-add.php', 'product-edit.php', 'product-delete.php') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                Products
            </a>
            <a href="<?= BASE_URL ?>/admin/homepage-curation.php" class="admin-nav-link <?= _admin_active('homepage-curation.php') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5L12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M8 12.5h8"/><path d="M8 15.5h8"/><path d="M8 18.5h5"/></svg>
                Homepage Curation
            </a>
            <a href="<?= BASE_URL ?>/admin/categories.php" class="admin-nav-link <?= _admin_active('categories.php') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
                Categories
            </a>

            <div class="admin-nav-section-label">Sales</div>
            <a href="<?= BASE_URL ?>/admin/orders.php" class="admin-nav-link <?= _admin_active('orders.php', 'order-edit.php') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Orders
            </a>
            <a href="<?= BASE_URL ?>/admin/reviews.php" class="admin-nav-link <?= _admin_active('reviews.php') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                Reviews
            </a>

            <div class="admin-nav-section-label">People</div>
            <a href="<?= BASE_URL ?>/admin/users.php" class="admin-nav-link <?= _admin_active('users.php', 'user-edit.php', 'user-delete.php') ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                Users
            </a>

            <div class="admin-nav-divider"></div>

            <a href="<?= BASE_URL ?>/index.php" class="admin-nav-link admin-nav-link-muted" target="_blank" rel="noopener">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                View Storefront
            </a>
        </nav>
    </aside>

    <!-- ===== MAIN AREA ===== -->
    <div class="admin-main">

        <!-- Top bar -->
        <header class="admin-topbar">
            <button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Toggle sidebar" aria-expanded="false" aria-controls="adminSidebar">
                <span></span><span></span><span></span>
            </button>
            <div class="admin-topbar-title"><?= htmlspecialchars($page_title ?? 'Admin', ENT_QUOTES) ?></div>
            <div class="admin-topbar-right">
                <span class="admin-topbar-user">
                    <?= htmlspecialchars($_admin_display, ENT_QUOTES) ?>
                </span>
                <form method="post" action="<?= BASE_URL ?>/logout.php" class="admin-topbar-signout-form">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <button type="submit" class="admin-topbar-signout">Sign Out</button>
                </form>
            </div>
        </header>

        <!-- Page content -->
        <main class="admin-content" id="adminContent">
            <?php if (!empty($page_errors)): ?>
                <div class="admin-alert admin-alert-danger" role="alert">
                    <?php foreach ($page_errors as $e): ?><p><?= htmlspecialchars($e, ENT_QUOTES) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($page_notice)): ?>
                <div class="admin-alert admin-alert-success" role="status">
                    <p><?= htmlspecialchars($page_notice, ENT_QUOTES) ?></p>
                </div>
            <?php endif; ?>
