<?php
/**
 * London Labels - Categories Page
 */
require_once __DIR__ . '/functions.php';

$page_title = 'Shop by Category';
$meta_description = 'Browse London Labels collections by category. Curated fashion, sourced from the UK, delivered across Nigeria.';

$categories = get_all_categories();

// Attach product counts
foreach ($categories as &$cat) {
    $cat['product_count'] = get_product_count_by_category($cat['category_id']);
}
unset($cat);

include __DIR__ . '/inc_header.php';
?>

<div class="categories-page-wrap">

    <div class="categories-page-intro">
        <h2 class="categories-page-heading">Shop by Category</h2>
        <p class="categories-page-sub">Curated collections, sourced from the UK and delivered to your door across Nigeria.</p>
    </div>

    <?php if (empty($categories)): ?>
        <?php render_empty_state(
            'No Categories Yet',
            'Collections will appear here once products are organised. Check back soon.',
            'Browse All Products',
            BASE_URL . '/shop.php'
        ); ?>
    <?php else: ?>
        <div class="categories-showcase-grid">
            <?php foreach ($categories as $cat): ?>
                <?php $safe_cover_image = sanitize_local_upload_media_url((string)($cat['cover_image'] ?? '')); ?>
                <a href="<?= BASE_URL ?>/shop.php?category=<?= (int)$cat['category_id'] ?>"
                   class="category-showcase-card"
                   aria-label="Browse <?= e($cat['name']) ?> — <?= (int)$cat['product_count'] ?> product<?= $cat['product_count'] !== 1 ? 's' : '' ?>">
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
                            <span class="category-showcase-count"><?= (int)$cat['product_count'] ?> product<?= $cat['product_count'] !== 1 ? 's' : '' ?></span>
                            <span class="category-showcase-cta">Shop now →</span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="categories-page-all">
            <a href="<?= BASE_URL ?>/shop.php" class="btn categories-view-all-btn">View All Products</a>
        </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
