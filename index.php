<?php
/**
 * London Labels - Homepage
 */
require_once __DIR__ . '/functions.php';

$page_title = 'Home';
$page_errors = [];
$page_notice = '';

include __DIR__ . '/inc_header.php';

// Featured: admin-selected products only (curated by admin, no auto-fill)
$featured_products = get_featured_products(4);

// New arrivals: manually marked OR added in last 30 days (auto-fresh, manual override)
$new_arrivals = get_new_arrival_products(4);
$categories = array_slice(get_all_categories(), 0, 4);

// Wishlist state for logged-in users
$wishlist_ids = is_logged_in() ? get_user_wishlist_product_ids((int)current_user_id()) : get_guest_wishlist_product_ids();
?>

<?php if (($_GET['account'] ?? '') === 'deleted'): ?>
<div class="site-notice site-notice-info">Your account has been deleted. We're sorry to see you go.</div>
<?php endif; ?>

<section class="hero-commerce">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="hero-badge">Style Without Borders</div>
        <h2>Where London<br>Meets Lagos</h2>
        <p>Premium fashion sourced from the UK, delivered to your door across Nigeria.</p>
        <div class="hero-buttons">
            <a href="<?= BASE_URL ?>/shop.php" class="btn primary hero-btn-primary">Shop Collection</a>
            <a href="<?= BASE_URL ?>/categories.php" class="btn hero-btn-secondary">Explore Categories</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <strong>UK</strong>
                <span>Sourced &amp; Authentic</span>
            </div>
            <div class="hero-stat">
                <strong>Lagos</strong>
                <span>Stocked &amp; Delivered</span>
            </div>
            <div class="hero-stat">
                <strong>100%</strong>
                <span>Genuine Items</span>
            </div>
        </div>
    </div>
</section>

<section class="home-trust-strip" aria-label="Why shop with us">
    <div class="home-trust-item">
        <strong>UK Sourced</strong>
        <span>Authentic pieces brought in from London.</span>
    </div>
    <div class="home-trust-item">
        <strong>Secure Checkout</strong>
        <span>Protected payments and account security.</span>
    </div>
    <div class="home-trust-item">
        <strong>Real Support</strong>
        <span>A team that actually picks up and responds.</span>
    </div>
</section>

<?php if (!empty($categories)): ?>
<section class="categories-preview">
    <div class="home-section-head">
        <h3>Shop by Category</h3>
        <a href="<?= BASE_URL ?>/categories.php" class="home-section-link">View all</a>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
            <?php $safe_cover_image = sanitize_local_upload_media_url((string)($cat['cover_image'] ?? '')); ?>
            <a href="<?= BASE_URL ?>/shop.php?category=<?= $cat['category_id'] ?>"
               class="category-showcase-card"
               aria-label="Browse <?= e($cat['name']) ?>">
                <?php if ($safe_cover_image !== null): ?>
                    <div class="category-showcase-img-wrap">
                        <img src="<?= e($safe_cover_image) ?>" alt="<?= e($cat['name']) ?>" class="category-showcase-img" loading="lazy" decoding="async">
                    </div>
                <?php else: ?>
                    <div class="category-showcase-img-wrap category-showcase-placeholder">
                        <span class="category-showcase-initial"><?= e(mb_strtoupper(mb_substr($cat['name'], 0, 1))) ?></span>
                    </div>
                <?php endif; ?>
                <div class="category-showcase-overlay">
                    <div class="category-showcase-body">
                        <h3 class="category-showcase-name"><?= e($cat['name']) ?></h3>
                        <?php if (!empty($cat['description'])): ?>
                            <p class="category-showcase-desc"><?= e($cat['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="category-showcase-footer">
                        <span class="category-showcase-cta">Shop now →</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($featured_products)): ?>
<section class="featured-products">
    <div class="home-section-head">
        <h3>Featured Products</h3>
        <a href="<?= BASE_URL ?>/shop.php" class="home-section-link">View all</a>
    </div>
    <div class="product-grid">
        <?php foreach ($featured_products as $product): ?>
            <?php include __DIR__ . '/includes/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($new_arrivals)): ?>
<section class="featured-products new-arrivals-section">
    <div class="home-section-head">
        <h3>New Arrivals</h3>
        <a href="<?= BASE_URL ?>/shop.php" class="home-section-link">Shop now</a>
    </div>
    <div class="product-grid">
        <?php foreach ($new_arrivals as $product): ?>
            <?php include __DIR__ . '/includes/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/inc_footer.php'; ?>
