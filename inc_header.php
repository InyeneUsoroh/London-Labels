<?php
/**
 * Header Template for London Labels
 * Include this at the top of each page
 */
require_once __DIR__ . '/functions.php';

$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$current_page = basename($current_path);

$is_home = ($current_page === 'index.php' || $current_page === '');
$is_shop = in_array($current_page, ['shop.php', 'product.php', 'cart.php', 'checkout.php', 'order-confirmation.php'], true);
$is_categories = ($current_page === 'categories.php');
$is_contact = ($current_page === 'contact.php');
$is_account_area = str_starts_with($current_path, '/account/');
$is_login = ($current_page === 'login.php');
$is_register = ($current_page === 'register.php');

$meta_description_content = $meta_description ?? 'London Labels — Style Without Borders. Curated fashion, shipped from the UK to your doorstep.';
$meta_keywords_content = $meta_keywords ?? 'fashion, clothing, UK fashion, Lagos, Nigeria, online shopping, style';
$meta_title_content = isset($page_title) ? e($page_title) . ' | ' . SITE_NAME : SITE_NAME;
$canonical_path = $current_path !== '' ? $current_path : '/index.php';
$canonical_url = rtrim(BASE_URL, '/') . $canonical_path;

$style_path = __DIR__ . '/assets/style.css';
$script_path = __DIR__ . '/assets/hamburger-menu.js';
$upload_script_path = __DIR__ . '/assets/upload-progress.js';
$style_version = is_file($style_path) ? (string)filemtime($style_path) : '1';
$script_version = is_file($script_path) ? (string)filemtime($script_path) : '1';
$upload_script_version = is_file($upload_script_path) ? (string)filemtime($upload_script_path) : '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e($meta_description_content) ?>">
    <meta name="keywords" content="<?= e($meta_keywords_content) ?>">
    <meta name="robots" content="index,follow">
    <meta name="theme-color" content="#e8357e">
    <link rel="canonical" href="<?= e($canonical_url) ?>">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e(SITE_NAME) ?>">
    <meta property="og:title" content="<?= $meta_title_content ?>">
    <meta property="og:description" content="<?= e($meta_description_content) ?>">
    <meta property="og:url" content="<?= e($canonical_url) ?>">
    <meta property="og:image" content="<?= e(BASE_URL) ?>/assets/images/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="London Labels — Style Without Borders">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $meta_title_content ?>">
    <meta name="twitter:description" content="<?= e($meta_description_content) ?>">
    <meta name="twitter:image" content="<?= e(BASE_URL) ?>/assets/images/og-image.jpg">
    <meta name="twitter:image:alt" content="London Labels — Style Without Borders">

    <title><?= $meta_title_content ?></title>

    <!-- Favicon: SVG monogram (modern browsers) + PNG fallback -->
    <link rel="icon" type="image/svg+xml" href="<?= e(BASE_URL) ?>/assets/images/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= e(BASE_URL) ?>/assets/images/favicon-96x96.png">
    <link rel="icon" type="image/x-icon" href="<?= e(BASE_URL) ?>/assets/images/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= e(BASE_URL) ?>/assets/images/apple-touch-icon.png">
    <link rel="manifest" href="<?= e(BASE_URL) ?>/assets/site.webmanifest">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css?v=<?= e($style_version) ?>">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/lightbox.css?v=<?= is_file(__DIR__ . '/assets/lightbox.css') ? (string)filemtime(__DIR__ . '/assets/lightbox.css') : '1' ?>">
    <script>
        // Make BASE_URL and CURRENCY available to JavaScript
        window.BASE_URL = '<?= BASE_URL ?>';
        window.CURRENCY_SYMBOL = '<?= addslashes(CURRENCY_SYMBOL) ?>';
    </script>
    <script src="<?= BASE_URL ?>/assets/hamburger-menu.js?v=<?= e($script_version) ?>" defer></script>
    <script src="<?= BASE_URL ?>/assets/upload-progress.js?v=<?= e($upload_script_version) ?>" defer></script>
    <script>
        // Rotating quick strip messages
        (function () {
            document.addEventListener('DOMContentLoaded', function () {
                var messages = document.querySelectorAll('.quick-strip-message');

                if (messages.length < 2) return;
                var current = 0;
                setInterval(function () {
                    messages[current].classList.remove('active');
                    current = (current + 1) % messages.length;
                    messages[current].classList.add('active');
                }, 4000);
            });
        })();
    </script>
</head>
<body>
    <a href="#mainContent" class="skip-link">Skip to main content</a>
    <div class="quick-strip">
        <div class="quick-strip-inner">
            <div class="quick-strip-left">
                <div class="quick-strip-messages" aria-live="polite" aria-label="Promotions">
                    <span class="quick-strip-message active">Style Without Borders &mdash; Curated Fashion, Shipped from the UK</span>
                    <span class="quick-strip-message">Authentic Pieces &bull; Delivered Across Nigeria</span>
                    <span class="quick-strip-message">New Arrivals In &mdash; Shop the Latest Collection</span>
                </div>
            </div>
            <div class="quick-strip-links">
                <a href="<?= e(CONTACT_PHONE_HREF) ?>" class="quick-strip-phone" aria-label="Call us">
                    <?= e(CONTACT_PHONE) ?> &bull; <?= e(CUSTOMER_CARE_HOURS) ?>
                </a>
                <span class="quick-strip-divider" aria-hidden="true">|</span>
                <a href="<?= e(STORE_MAP_URL) ?>" target="_blank" rel="noopener noreferrer" class="quick-store-link quick-link-priority">
                    Visit Our Ajah Store
                </a>
                <?php if (is_logged_in()): ?>
                    <a href="<?= BASE_URL ?>/account/orders.php" class="quick-link-optional">Track Order</a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="quick-link-optional">Track Order</a>
                <?php endif; ?>
                <?php if (defined('WHATSAPP_GROUP_URL') && WHATSAPP_GROUP_URL !== ''): ?>
                    <a href="<?= e(WHATSAPP_GROUP_URL) ?>" target="_blank" rel="noopener noreferrer" class="social-icon-link social-icon-whatsapp" aria-label="Chat with us on WhatsApp">
                        <svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M12 2a10 10 0 0 0-8.66 15l-1.27 4.63 4.76-1.25A10 10 0 1 0 12 2Zm0 18a7.95 7.95 0 0 1-4.08-1.12l-.29-.17-2.82.74.76-2.74-.19-.3A8 8 0 1 1 12 20Zm4.37-5.61c-.24-.12-1.41-.7-1.63-.78-.22-.08-.38-.12-.54.12-.16.24-.62.78-.76.94-.14.16-.28.18-.52.06-.24-.12-1.01-.37-1.93-1.17-.71-.64-1.2-1.42-1.34-1.66-.14-.24-.02-.37.1-.49.11-.11.24-.28.36-.42.12-.14.16-.24.24-.4.08-.16.04-.3-.02-.42-.06-.12-.54-1.3-.74-1.79-.2-.47-.4-.41-.54-.42h-.46c-.16 0-.42.06-.64.3-.22.24-.84.82-.84 2 0 1.18.86 2.32.98 2.48.12.16 1.69 2.58 4.1 3.61.57.25 1.02.4 1.37.51.58.18 1.11.15 1.53.09.47-.07 1.41-.58 1.61-1.15.2-.57.2-1.05.14-1.15-.06-.1-.22-.16-.46-.28Z"/>
                        </svg>
                        <span class="visually-hidden">WhatsApp</span>
                    </a>
                <?php endif; ?>
                <?php if (defined('YOUTUBE_CHANNEL_URL') && YOUTUBE_CHANNEL_URL !== ''): ?>
                    <a href="<?= e(YOUTUBE_CHANNEL_URL) ?>" target="_blank" rel="noopener noreferrer" class="social-icon-link social-icon-youtube" aria-label="Follow us on YouTube">
                        <svg class="social-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                            <path fill="currentColor" d="M23.5 7.2a3.14 3.14 0 0 0-2.2-2.22C19.35 4.5 12 4.5 12 4.5s-7.35 0-9.3.48A3.14 3.14 0 0 0 .5 7.2C0 9.17 0 12 0 12s0 2.83.5 4.8a3.14 3.14 0 0 0 2.2 2.22c1.95.48 9.3.48 9.3.48s7.35 0 9.3-.48a3.14 3.14 0 0 0 2.2-2.22C24 14.83 24 12 24 12s0-2.83-.5-4.8ZM9.6 15.02V8.98L15.4 12l-5.8 3.02Z"/>
                        </svg>
                        <span class="visually-hidden">YouTube</span>
                    </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/contact.php" class="quick-link-optional">Support</a>
            </div>
        </div>
    </div>
    <header class="main-header">
        <nav class="navbar" aria-label="Primary navigation">
            <div class="navbar-brand">
                <h1>
                    <a href="<?= BASE_URL ?>/index.php" class="brand-svg-link" aria-label="<?= e(SITE_NAME) ?> home">
                        <svg class="brand-svg" viewBox="0 0 240 48" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="London Labels">
                            <defs>
                                <!-- Fading underline: magenta to transparent, left to right -->
                                <linearGradient id="underlineFade" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%"   stop-color="#e8357e" stop-opacity="0.9"/>
                                    <stop offset="70%"  stop-color="#e8357e" stop-opacity="0.4"/>
                                    <stop offset="100%" stop-color="#e8357e" stop-opacity="0"/>
                                </linearGradient>
                            </defs>

                            <!-- Wordmark: italic Playfair Display -->
                            <text
                                x="12" y="32"
                                font-family="Playfair Display, Georgia, serif"
                                font-size="28"
                                font-weight="400"
                                font-style="italic"
                                letter-spacing="0.5"
                                fill="#1a1a1a">London Labels</text>

                            <!-- Fading underline beneath the text -->
                            <rect x="12" y="37" width="216" height="1.2" fill="url(#underlineFade)"/>
                        </svg>
                    </a>
                </h1>
            </div>
            <form class="nav-search-form" method="get" action="<?= BASE_URL ?>/shop.php" role="search" aria-label="Sitewide product search">
                <label for="header-search" class="visually-hidden">Search products</label>
                <div class="search-suggest-wrap">
                    <input
                        type="search"
                        id="header-search"
                        name="q"
                        placeholder="Search products, brands..."
                        value="<?= e($_GET['q'] ?? '') ?>"
                        role="combobox"
                        aria-label="Search products"
                        aria-autocomplete="list"
                        aria-expanded="false"
                        aria-haspopup="listbox"
                        aria-controls="header-search-suggest"
                        aria-activedescendant=""
                        aria-describedby="header-search-suggest-status"
                        data-autosuggest="products"
                        data-suggest-list="header-search-suggest"
                        data-suggest-status="header-search-suggest-status"
                        autocomplete="off"
                        spellcheck="false"
                    >
                    <div class="search-suggest-panel" id="header-search-suggest" role="listbox" aria-label="Product suggestions" hidden></div>
                    <div class="search-suggest-status visually-hidden" id="header-search-suggest-status" role="status" aria-live="polite" aria-atomic="true"></div>
                </div>
                <button type="submit" aria-label="Search">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            </form>

            <div class="mob-nav-left" id="mobNavLeft">
                <button type="button" class="mob-icon-btn" id="mobSearchBtn" aria-label="Search" aria-expanded="false">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </div>
            <div class="mob-nav-right" id="mobNavRight">
                <a href="<?= BASE_URL ?>/cart.php" class="mob-icon-btn" aria-label="Cart<?= cart_item_count() > 0 ? ', ' . (int)cart_item_count() . ' items' : '' ?>">
                    <!-- Heroicons shopping-bag: open-top bag with handles — universally recognised -->
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <span class="mob-cart-badge" id="mob-cart-badge" aria-hidden="true" style="<?= cart_item_count() <= 0 ? 'display: none;' : '' ?>"><?= (int)cart_item_count() ?></span>
                </a>
                <button type="button" class="mob-icon-btn" id="mobDrawerBtn" aria-label="Open menu" aria-expanded="false" aria-controls="mobDrawer">
                    <span class="mob-hamburger-bars" aria-hidden="true">
                        <span></span><span></span><span></span>
                    </span>
                </button>
            </div>

            <ul class="navbar-menu" id="primaryNav">
                <li class="hamburger-menu-item">
                    <button type="button" class="hamburger-toggle" aria-expanded="false" aria-haspopup="true" aria-controls="categoriesMenu" aria-label="Open categories menu">
                        <span class="hamburger-label">Shop</span>
                    </button>
                    <div class="hamburger-menu-panel" id="categoriesMenu" role="menu" aria-label="Product categories">
                        <div class="hamburger-menu-header">Shop by Category</div>
                        <!-- Categories will be loaded dynamically -->
                        <div class="hamburger-menu-footer">
                            <a href="<?= BASE_URL ?>/shop.php">View All Products</a>
                        </div>
                    </div>
                </li>
                <li class="dropdown profile-dropdown <?= $is_account_area || $is_login || $is_register ? 'active' : '' ?>">
                    <button type="button" class="profile-toggle-clean nav-cart-icon-link <?= $is_account_area || $is_login || $is_register ? 'active' : '' ?>" aria-expanded="false" aria-haspopup="true" aria-controls="accountNavMenu" aria-label="My Account">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </button>
                    <ul class="dropdown-menu" id="accountNavMenu">
                    <?php if (is_logged_in()): ?>
                        <?php
                            $__nav_user = get_user_by_id((int) current_user_id());
                            $__nav_first = trim((string)($__nav_user['first_name'] ?? ''));
                            $__nav_display = $__nav_first !== '' ? $__nav_first : (current_user_name() ?? 'Account');
                            $__nav_email = current_user_email() ?? '';
                        ?>
                        <li class="dropdown-user-header">
                            <span class="dropdown-user-name"><?= e($__nav_display) ?></span>
                            <?php if ($__nav_email !== ''): ?>
                                <span class="dropdown-user-email"><?= e($__nav_email) ?></span>
                            <?php endif; ?>
                        </li>
                        <li><a href="<?= BASE_URL ?>/account/profile.php" class="dropdown-first-link">My Account</a></li>
                        <li><a href="<?= BASE_URL ?>/account/orders.php">My Orders</a></li>
                        <li><a href="<?= BASE_URL ?>/account/wishlist.php">Wishlist</a></li>
                        <?php if (is_admin()): ?>
                            <li class="dropdown-divider-item"><hr></li>
                            <li><a href="<?= BASE_URL ?>/admin/dashboard.php" class="dropdown-admin-link">Admin</a></li>
                        <?php endif; ?>
                        <li class="dropdown-divider-item"><hr></li>
                        <li>
                            <form method="post" action="<?= BASE_URL ?>/logout.php">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <button type="submit" class="dropdown-logout-btn">Sign Out</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>/login.php" class="dropdown-first-link <?= $is_login ? 'active' : '' ?>" <?= $is_login ? 'aria-current="page"' : '' ?>>Sign In</a></li>
                        <li><a href="<?= BASE_URL ?>/register.php" class="<?= $is_register ? 'active' : '' ?>" <?= $is_register ? 'aria-current="page"' : '' ?>>Create Account</a></li>
                    <?php endif; ?>
                    </ul>
                </li>
                <li class="nav-wishlist-icon-item">
                    <a href="<?= BASE_URL ?>/account/wishlist.php" class="nav-cart-icon-link <?= $current_page === 'wishlist.php' ? 'active' : '' ?>" aria-label="Wishlist">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.8 4.6a5.5 5.5 0 00-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 00-7.8 7.8l1.1 1 7.8 7.8 7.8-7.7 1-1.1a5.5 5.5 0 000-7.8z"/></svg>
                    </a>
                </li>
                <li class="nav-cart-icon-item">
                    <a href="<?= BASE_URL ?>/cart.php" class="nav-cart-icon-link <?= $current_page === 'cart.php' ? 'active' : '' ?>" <?= $current_page === 'cart.php' ? 'aria-current="page"' : '' ?> aria-label="Cart<?= cart_item_count() > 0 ? ', ' . (int)cart_item_count() . ' items' : '' ?>">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        <span class="nav-cart-badge" id="nav-cart-badge" style="<?= cart_item_count() <= 0 ? 'display: none;' : '' ?>"><?= (int)cart_item_count() ?></span>
                    </a>
                </li>
            </ul>
        </nav>
    </header>
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Mobile search bar (slides down from header) -->
    <form class="mob-search-bar" id="mobSearchBar" method="get" action="<?= BASE_URL ?>/shop.php" role="search" aria-label="Search products">
        <div class="search-suggest-wrap">
            <input
                type="search"
                id="mob-search-input"
                name="q"
                placeholder="Search products, brands..."
                value="<?= e($_GET['q'] ?? '') ?>"
                autocomplete="off"
                spellcheck="false"
                aria-label="Search products"
            >
            <div class="search-suggest-panel" id="mob-search-suggest" role="listbox" aria-label="Product suggestions" hidden></div>
        </div>
        <button type="submit" aria-label="Search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
    </form>

    <!-- Mobile slide-in drawer -->
    <nav class="mob-drawer" id="mobDrawer" aria-label="Mobile navigation" aria-hidden="true">
        <div class="mob-drawer-head">
            <p class="mob-drawer-title">London Labels</p>
            <button type="button" class="mob-drawer-close" id="mobDrawerClose" aria-label="Close menu">&times;</button>
        </div>
        <div class="mob-drawer-body">

            <!-- Shop section -->
            <div class="mob-drawer-section-label">Shop</div>
            <button type="button" class="mob-drawer-link" id="mobShopToggle" aria-expanded="false">
                All Categories
                <span class="mob-drawer-link-chevron" aria-hidden="true">&#8250;</span>
            </button>
            <div class="mob-drawer-sub" id="mobCategoriesSub">
                <!-- Populated by JS -->
                <a href="<?= BASE_URL ?>/shop.php">View All Products</a>
            </div>
            <a href="<?= BASE_URL ?>/shop.php" class="mob-drawer-link">Shop All</a>
            <a href="<?= BASE_URL ?>/categories.php" class="mob-drawer-link">Browse Categories</a>

            <div class="mob-drawer-divider"></div>
            <div class="mob-drawer-section-label">My Account</div>

            <?php if (is_logged_in()): ?>
                <!-- Logged-in account section -->
                <div class="mob-drawer-user">
                    <span class="mob-drawer-user-name"><?= e($__nav_display ?? 'Account') ?></span>
                    <?php if (!empty($__nav_email)): ?>
                        <span class="mob-drawer-user-email"><?= e($__nav_email) ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?= BASE_URL ?>/account/profile.php" class="mob-drawer-link <?= $is_account_area ? 'active' : '' ?>">My Profile</a>
                <a href="<?= BASE_URL ?>/account/orders.php" class="mob-drawer-link">My Orders</a>
                <a href="<?= BASE_URL ?>/account/wishlist.php" class="mob-drawer-link">Wishlist</a>
                <?php if (is_admin()): ?>
                    <div class="mob-drawer-divider"></div>
                    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="mob-drawer-link mob-drawer-admin">Admin Panel</a>
                <?php endif; ?>
                <div class="mob-drawer-divider"></div>
                <form method="post" action="<?= BASE_URL ?>/logout.php" style="margin: 0; padding: 0;">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <button type="submit" class="mob-drawer-link mob-drawer-signout">Sign Out</button>
                </form>
            <?php else: ?>
                <!-- Guest section -->
                <a href="<?= BASE_URL ?>/account/wishlist.php" class="mob-drawer-link">Wishlist</a>
                <a href="<?= BASE_URL ?>/login.php" class="mob-drawer-link <?= $is_login ? 'active' : '' ?>">Sign In</a>
                <a href="<?= BASE_URL ?>/register.php" class="mob-drawer-link <?= $is_register ? 'active' : '' ?>">Create Account</a>
            <?php endif; ?>

        </div>
        <div class="mob-drawer-footer">
            <p class="mob-drawer-footer-tagline">Style Without Borders</p>
        </div>
    </nav>

    <?php render_verification_banner(); ?>

    <main class="main-content" id="mainContent" tabindex="-1">
        <?php if (!empty($page_errors)): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <h3>Errors:</h3>
                <ul>
                    <?php foreach ($page_errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($page_notice)): ?>
            <div class="alert alert-success" role="status" aria-live="polite">
                <?= e($page_notice) ?>
            </div>
        <?php endif; ?>
