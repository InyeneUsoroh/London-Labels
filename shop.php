<?php
/**
 * London Labels - Shop Page
 */
require_once __DIR__ . '/functions.php';

$page_title = 'Shop';
$meta_description = 'Shop London Labels — curated fashion sourced from the UK, delivered across Nigeria. Browse all collections.';

$page         = max(1, (int)($_GET['page'] ?? 1));
$category_id  = (int)($_GET['category'] ?? 0);
$search_q     = trim($_GET['q'] ?? ($_GET['search'] ?? ''));
$sort         = (string)($_GET['sort'] ?? 'newest');
$availability = (string)($_GET['availability'] ?? 'all');
$tag_filter   = trim((string)($_GET['tag'] ?? ''));
$min_price_raw = trim((string)($_GET['min_price'] ?? ''));
$max_price_raw = trim((string)($_GET['max_price'] ?? ''));

$min_price = ($min_price_raw !== '' && is_numeric($min_price_raw)) ? max(0, (float)$min_price_raw) : null;
$max_price = ($max_price_raw !== '' && is_numeric($max_price_raw)) ? max(0, (float)$max_price_raw) : null;

if ($min_price !== null && $max_price !== null && $min_price > $max_price) {
    [$min_price, $max_price] = [$max_price, $min_price];
}

$allowedSorts = ['newest', 'price_asc', 'price_desc', 'name_asc'];
if (!in_array($sort, $allowedSorts, true)) $sort = 'newest';
if (!in_array($availability, ['all', 'in_stock'], true)) $availability = 'all';

$activeCategoryId = $category_id > 0 ? $category_id : null;
$limit  = ITEMS_PER_PAGE;
$offset = ($page - 1) * $limit;

$products    = get_catalog_products($activeCategoryId, $search_q, $sort, $availability, $min_price, $max_price, $limit, $offset, $tag_filter);
$total       = count_catalog_products($activeCategoryId, $search_q, $availability, $min_price, $max_price, $tag_filter);
$total_pages = max(1, ceil($total / $limit));
$categories  = get_all_categories();
$all_tags    = get_all_product_tags();

$current_category = ($category_id > 0) ? get_category_by_id($category_id) : null;

$has_filters = ($category_id > 0 || $search_q !== '' || $sort !== 'newest' || $availability !== 'all' || $min_price !== null || $max_price !== null || $tag_filter !== '');

// Count active filters for the badge
$active_filter_count = 0;
if ($category_id > 0) $active_filter_count++;
if ($search_q !== '') $active_filter_count++;
if ($availability !== 'all') $active_filter_count++;
if ($min_price !== null) $active_filter_count++;
if ($max_price !== null) $active_filter_count++;
if ($tag_filter !== '') $active_filter_count++;

// Wishlist state for logged-in users
$wishlist_ids = is_logged_in() ? get_user_wishlist_product_ids((int)current_user_id()) : get_guest_wishlist_product_ids();

include __DIR__ . '/inc_header.php';
?>

<div class="shop-page-wrap">

    <!-- Top bar: heading + filter button (mobile) + sort -->
    <div class="shop-topbar">
        <div class="shop-topbar-left">
            <?php if ($current_category): ?>
                <h2 class="shop-page-heading"><?= e($current_category['name']) ?></h2>
            <?php elseif (!empty($search_q)): ?>
                <h2 class="shop-page-heading">Results for &ldquo;<?= e($search_q) ?>&rdquo;</h2>
            <?php else: ?>
                <h2 class="shop-page-heading">All Products</h2>
            <?php endif; ?>
            <span class="shop-result-count"><?= number_format($total) ?> product<?= $total !== 1 ? 's' : '' ?></span>
        </div>
        <div class="shop-topbar-right">
            <!-- Filter toggle — visible on mobile/minimised only -->
            <button type="button" class="shop-filter-toggle" id="shopFilterToggle" aria-expanded="false" aria-controls="shopFilterDrawer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="4" y1="6" x2="20" y2="6"/><line x1="8" y1="12" x2="16" y2="12"/><line x1="11" y1="18" x2="13" y2="18"/></svg>
                Filter<?php if ($active_filter_count > 0): ?> <span class="shop-filter-badge"><?= $active_filter_count ?></span><?php endif; ?>
            </button>

            <form method="get" class="shop-sort-form" aria-label="Sort products">
                <?php if ($category_id > 0): ?><input type="hidden" name="category" value="<?= $category_id ?>"><?php endif; ?>
                <?php if (!empty($search_q)): ?><input type="hidden" name="q" value="<?= e($search_q) ?>"><?php endif; ?>
                <?php if ($availability !== 'all'): ?><input type="hidden" name="availability" value="<?= e($availability) ?>"><?php endif; ?>
                <?php if ($min_price !== null): ?><input type="hidden" name="min_price" value="<?= e((string)$min_price) ?>"><?php endif; ?>
                <?php if ($max_price !== null): ?><input type="hidden" name="max_price" value="<?= e((string)$max_price) ?>"><?php endif; ?>
                <?php if ($tag_filter !== ''): ?><input type="hidden" name="tag" value="<?= e($tag_filter) ?>"><?php endif; ?>
                <label for="sort" class="visually-hidden">Sort by</label>
                <select name="sort" id="sort" class="shop-sort-select" onchange="this.form.submit()">
                    <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Newest</option>
                    <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                    <option value="name_asc"   <?= $sort === 'name_asc'   ? 'selected' : '' ?>>Name: A–Z</option>
                </select>
            </form>
        </div>
    </div>

    <?php
    // Shared sidebar content — rendered once, used in both desktop sidebar and mobile drawer
    ob_start();
    ?>
    <!-- Search -->
    <div class="shop-sidebar-block">
        <h4 class="shop-sidebar-heading">Search</h4>
        <form method="get" class="shop-search-form" role="search" aria-label="Search products">
            <?php if ($category_id > 0): ?><input type="hidden" name="category" value="<?= $category_id ?>"><?php endif; ?>
            <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
            <label for="shop-search-q" class="visually-hidden">Search products</label>
            <div class="search-suggest-wrap shop-search-suggest-wrap">
                <input type="search" id="shop-search-q" name="q" value="<?= e($search_q) ?>"
                    placeholder="Search products..." class="shop-filter-input"
                    role="combobox" aria-autocomplete="list" aria-expanded="false"
                    aria-haspopup="listbox" aria-controls="shop-search-suggest"
                    aria-activedescendant="" aria-describedby="shop-search-suggest-status"
                    data-autosuggest="products" data-suggest-list="shop-search-suggest"
                    data-suggest-status="shop-search-suggest-status"
                    autocomplete="off" spellcheck="false">
                <div class="search-suggest-panel" id="shop-search-suggest" role="listbox" aria-label="Product suggestions" hidden></div>
                <div class="search-suggest-status visually-hidden" id="shop-search-suggest-status" role="status" aria-live="polite" aria-atomic="true"></div>
            </div>
            <button type="submit" class="btn primary shop-search-btn">Search</button>
        </form>
    </div>

    <!-- Categories -->
    <div class="shop-sidebar-block">
        <h4 class="shop-sidebar-heading">Categories</h4>
        <ul class="shop-cat-list">
            <?php
                $baseParams = [];
                if (!empty($search_q)) $baseParams['q'] = $search_q;
                if ($sort !== 'newest') $baseParams['sort'] = $sort;
                if ($availability !== 'all') $baseParams['availability'] = $availability;
                if ($min_price !== null) $baseParams['min_price'] = $min_price;
                if ($max_price !== null) $baseParams['max_price'] = $max_price;
                $allUrl = BASE_URL . '/shop.php' . (!empty($baseParams) ? '?' . http_build_query($baseParams) : '');
            ?>
            <li><a href="<?= $allUrl ?>" class="shop-cat-link <?= $category_id === 0 ? 'active' : '' ?>">All Products</a></li>
            <?php foreach ($categories as $cat): ?>
                <?php
                    $catParams = array_merge($baseParams, ['category' => $cat['category_id']]);
                    $catUrl = BASE_URL . '/shop.php?' . http_build_query($catParams);
                ?>
                <li><a href="<?= $catUrl ?>" class="shop-cat-link <?= $category_id === (int)$cat['category_id'] ? 'active' : '' ?>"><?= e($cat['name']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Tags -->
    <?php if (!empty($all_tags)): ?>
    <div class="shop-sidebar-block">
        <h4 class="shop-sidebar-heading">Tags</h4>
        <div class="shop-tag-list">
            <?php foreach ($all_tags as $tag): ?>
                <?php
                    $tagParams = [];
                    if ($category_id > 0) $tagParams['category'] = $category_id;
                    if ($search_q !== '') $tagParams['q'] = $search_q;
                    if ($sort !== 'newest') $tagParams['sort'] = $sort;
                    if ($availability !== 'all') $tagParams['availability'] = $availability;
                    if ($min_price !== null) $tagParams['min_price'] = $min_price;
                    if ($max_price !== null) $tagParams['max_price'] = $max_price;
                    $isActive = strtolower($tag_filter) === strtolower($tag);
                    if (!$isActive) $tagParams['tag'] = $tag;
                    $tagUrl = BASE_URL . '/shop.php' . (!empty($tagParams) ? '?' . http_build_query($tagParams) : '');
                ?>
                <a href="<?= $tagUrl ?>" class="shop-tag-pill <?= $isActive ? 'active' : '' ?>"><?= e($tag) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="shop-sidebar-block">
        <h4 class="shop-sidebar-heading">Filter</h4>
        <form method="get" class="shop-filter-form" aria-label="Filter products">
            <?php if ($category_id > 0): ?><input type="hidden" name="category" value="<?= $category_id ?>"><?php endif; ?>
            <?php if (!empty($search_q)): ?><input type="hidden" name="q" value="<?= e($search_q) ?>"><?php endif; ?>
            <?php if ($sort !== 'newest'): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
            <?php if ($tag_filter !== ''): ?><input type="hidden" name="tag" value="<?= e($tag_filter) ?>"><?php endif; ?>

            <label for="availability" class="shop-filter-label">Availability</label>
            <select name="availability" id="availability" class="shop-filter-select">
                <option value="all"      <?= $availability === 'all'      ? 'selected' : '' ?>>All items</option>
                <option value="in_stock" <?= $availability === 'in_stock' ? 'selected' : '' ?>>In stock only</option>
            </select>

            <label for="min_price" class="shop-filter-label">Min price (<?= LND_CURRENCY_SYMBOL ?>)</label>
            <input type="number" id="min_price" name="min_price" min="0" step="1"
                   value="<?= $min_price !== null ? e((string)(int)$min_price) : '' ?>"
                   class="shop-filter-input" inputmode="numeric" placeholder="0">

            <label for="max_price" class="shop-filter-label">Max price (<?= LND_CURRENCY_SYMBOL ?>)</label>
            <input type="number" id="max_price" name="max_price" min="0" step="1"
                   value="<?= $max_price !== null ? e((string)(int)$max_price) : '' ?>"
                   class="shop-filter-input" inputmode="numeric" placeholder="Any">

            <button type="submit" class="btn primary shop-filter-btn">Apply</button>
        </form>
        <?php if ($has_filters): ?>
            <a href="<?= BASE_URL ?>/shop.php" class="shop-reset-link">Clear all filters</a>
        <?php endif; ?>
    </div>
    <?php
        $sidebar_html = ob_get_clean();
        // Mobile drawer renders the same sidebar markup, so remap IDs to keep them unique in the DOM.
        $sidebar_html_mobile = str_replace(
            [
                'for="shop-search-q"',
                'id="shop-search-q"',
                'aria-controls="shop-search-suggest"',
                'data-suggest-list="shop-search-suggest"',
                'aria-describedby="shop-search-suggest-status"',
                'data-suggest-status="shop-search-suggest-status"',
                'id="shop-search-suggest"',
                'id="shop-search-suggest-status"',
                'for="availability"',
                'id="availability"',
                'for="min_price"',
                'id="min_price"',
                'for="max_price"',
                'id="max_price"',
            ],
            [
                'for="shop-search-q-mobile"',
                'id="shop-search-q-mobile"',
                'aria-controls="shop-search-suggest-mobile"',
                'data-suggest-list="shop-search-suggest-mobile"',
                'aria-describedby="shop-search-suggest-status-mobile"',
                'data-suggest-status="shop-search-suggest-status-mobile"',
                'id="shop-search-suggest-mobile"',
                'id="shop-search-suggest-status-mobile"',
                'for="availability-mobile"',
                'id="availability-mobile"',
                'for="min_price-mobile"',
                'id="min_price-mobile"',
                'for="max_price-mobile"',
                'id="max_price-mobile"',
            ],
            $sidebar_html
        );
    ?>

    <div class="shop-layout">

        <!-- Desktop sidebar -->
        <aside class="shop-sidebar" aria-label="Filter products">
            <?= $sidebar_html ?>
        </aside>

        <!-- Product grid -->
        <section class="shop-products-col" aria-label="Products">
            <?php if (empty($products)): ?>
                <?php render_empty_state('No Products Found', 'Try adjusting your search or filters.', 'Clear Filters', BASE_URL . '/shop.php'); ?>
            <?php else: ?>
                <div class="product-grid shop-product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php include __DIR__ . '/includes/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <?php
                        $paginationParams = [];
                        if ($category_id > 0) $paginationParams['category'] = $category_id;
                        if (!empty($search_q)) $paginationParams['q'] = $search_q;
                        if ($sort !== 'newest') $paginationParams['sort'] = $sort;
                        if ($availability !== 'all') $paginationParams['availability'] = $availability;
                        if ($min_price !== null) $paginationParams['min_price'] = $min_price;
                        if ($max_price !== null) $paginationParams['max_price'] = $max_price;
                        if ($tag_filter !== '') $paginationParams['tag'] = $tag_filter;
                        render_pagination($page, $total_pages, BASE_URL . '/shop.php', $paginationParams, $total, $limit);
                    ?>
                <?php endif; ?>
            <?php endif; ?>
        </section>

    </div>
</div>

<!-- Mobile filter drawer -->
<div class="shop-filter-overlay" id="shopFilterOverlay" aria-hidden="true"></div>
<div class="shop-filter-drawer" id="shopFilterDrawer" aria-label="Filter products" aria-hidden="true">
    <div class="shop-filter-drawer-head">
        <span class="shop-filter-drawer-title">Filter &amp; Search</span>
        <button type="button" class="shop-filter-drawer-close" id="shopFilterClose" aria-label="Close filters">&times;</button>
    </div>
    <div class="shop-filter-drawer-body">
        <?= $sidebar_html_mobile ?>
    </div>
</div>

<script>
(function () {
    var toggle  = document.getElementById('shopFilterToggle');
    var drawer  = document.getElementById('shopFilterDrawer');
    var overlay = document.getElementById('shopFilterOverlay');
    var close   = document.getElementById('shopFilterClose');
    if (!toggle || !drawer) return;

    function openDrawer() {
        drawer.classList.add('open');
        overlay.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        overlay.setAttribute('aria-hidden', 'false');
        toggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        drawer.classList.remove('open');
        overlay.classList.remove('open');
        drawer.setAttribute('aria-hidden', 'true');
        overlay.setAttribute('aria-hidden', 'true');
        toggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', openDrawer);
    if (close) close.addEventListener('click', closeDrawer);
    overlay.addEventListener('click', closeDrawer);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
    });
})();
</script>

<?php include __DIR__ . '/inc_footer.php'; ?>
