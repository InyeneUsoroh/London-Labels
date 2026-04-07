<?php
/**
 * London Labels - Admin Homepage Curation
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Homepage Curation';
$page_errors = [];
$page_notice = '';

require_admin();

$section = ($_GET['section'] ?? 'featured') === 'new_arrivals' ? 'new_arrivals' : 'featured';
$category_id = max(0, (int)($_GET['category'] ?? 0));
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$all_categories = get_all_categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $page_errors[] = 'Security token invalid. Please try again.';
    } else {
        $section_post = ($_POST['section'] ?? 'featured') === 'new_arrivals' ? 'new_arrivals' : 'featured';
        $action = $_POST['action'] ?? 'single';

        if ($action === 'bulk') {
            $enabled = (($_POST['enabled'] ?? '0') === '1');
            $product_ids = array_values(array_unique(array_filter(
                array_map('intval', (array)($_POST['product_ids'] ?? [])),
                static fn(int $id): bool => $id > 0
            )));

            if (empty($product_ids)) {
                $page_errors[] = 'Select at least one product to continue.';
            } else {
                $success = 0;
                foreach ($product_ids as $pid) {
                    if (set_product_curation_membership($pid, $section_post, $enabled)) {
                        $success++;
                    }
                }

                $label = $section_post === 'featured' ? 'Featured Products' : 'New Arrivals';
                if ($success > 0) {
                    $page_notice = $enabled
                        ? $success . ' product(s) added to ' . $label . '.'
                        : $success . ' product(s) removed from ' . $label . '.';
                }
                if ($success !== count($product_ids)) {
                    $page_errors[] = 'Some selected products could not be updated.';
                }
            }
        } else {
            $product_id = (int)($_POST['product_id'] ?? 0);
            $enabled = (($_POST['enabled'] ?? '0') === '1');

            if ($product_id <= 0) {
                $page_errors[] = 'Invalid product selection.';
            } elseif (set_product_curation_membership($product_id, $section_post, $enabled)) {
                $label = $section_post === 'featured' ? 'Featured Products' : 'New Arrivals';
                $page_notice = $enabled
                    ? 'Product added to ' . $label . '.'
                    : 'Product removed from ' . $label . '.';
            } else {
                $page_errors[] = 'Could not update curation status. Please try again.';
            }
        }
    }
}

$products = get_admin_curation_products($section, 'all', $limit, $offset, $category_id, '');
$total = count_admin_curation_products($section, 'all', $category_id, '');
$total_pages = max(1, (int)ceil($total / $limit));

$section_title = $section === 'featured' ? 'Featured Products' : 'New Arrivals';

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle">Add or remove products for homepage sections.</p>
    </div>
</div>

<div class="admin-homepage-curation-compact">

<div class="admin-card admin-card-gap-bottom-20">
    <div class="admin-card-body">
        <div class="admin-curation-tabs" role="tablist" aria-label="Curation sections">
            <a class="admin-curation-tab <?= $section === 'featured' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/homepage-curation.php?section=featured&category=<?= $category_id ?>">Featured Products</a>
            <a class="admin-curation-tab <?= $section === 'new_arrivals' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/homepage-curation.php?section=new_arrivals&category=<?= $category_id ?>">New Arrivals</a>
        </div>

        <form method="get" class="admin-products-filter-bar admin-spaced-16">
            <input type="hidden" name="section" value="<?= e($section) ?>">
            <label for="category" class="admin-products-filter-label">Category:</label>
            <select id="category" name="category" class="admin-products-filter-select" onchange="this.form.submit()">
                <option value="0">All Categories</option>
                <?php foreach ($all_categories as $cat): ?>
                    <option value="<?= (int)$cat['category_id'] ?>" <?= $category_id === (int)$cat['category_id'] ? 'selected' : '' ?>>
                        <?= e($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($category_id > 0): ?>
                <a href="<?= BASE_URL ?>/admin/homepage-curation.php?section=<?= e($section) ?>" class="btn admin-mini-btn">Clear</a>
            <?php endif; ?>
        </form>

        <form id="bulk-curation-form" method="post" class="admin-actions-row-tight admin-actions-row-tight-top">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="bulk">
            <input type="hidden" name="section" value="<?= e($section) ?>">
            <button type="submit" name="enabled" value="1" class="btn primary admin-mini-btn">Add Selected</button>
            <button type="submit" name="enabled" value="0" class="btn admin-mini-btn">Remove Selected</button>
        </form>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-card-title"><?= e($section_title) ?></h2>
        <span class="admin-text-small-secondary"><?= number_format($total) ?> product<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <?php if (empty($products)): ?>
        <div class="admin-card-body">
            <p class="admin-muted-note">No products found.</p>
        </div>
    <?php else: ?>
        <div class="admin-table-wrap admin-table-wrap-flat admin-curation-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th class="admin-th-narrow admin-th-center">
                            <input type="checkbox" id="select-all-curation" aria-label="Select all products">
                        </th>
                        <th>Product</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <?php $in_section = (int)$product['is_in_section'] === 1; ?>
                        <tr>
                            <td class="admin-td-center">
                                <input
                                    type="checkbox"
                                    class="admin-curation-row-check"
                                    name="product_ids[]"
                                    value="<?= (int)$product['product_id'] ?>"
                                    form="bulk-curation-form"
                                    aria-label="Select <?= e($product['name']) ?>"
                                >
                            </td>
                            <td>
                                <div class="admin-text-600"><?= e($product['name']) ?></div>
                                <div class="admin-subtext">#<?= (int)$product['product_id'] ?> &middot; <?= e($product['category_name']) ?></div>
                            </td>
                            <td>
                                <form method="post">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="single">
                                    <input type="hidden" name="section" value="<?= e($section) ?>">
                                    <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
                                    <input type="hidden" name="enabled" value="<?= $in_section ? '0' : '1' ?>">
                                    <button type="submit" class="btn <?= $in_section ? '' : 'primary' ?> admin-mini-btn">
                                        <?= $in_section ? 'Remove' : 'Add' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-mobile-list admin-curation-mobile-list">
            <?php foreach ($products as $product): ?>
                <?php $in_section = (int)$product['is_in_section'] === 1; ?>
                <div class="admin-mobile-card">
                    <div class="admin-mobile-card-top">
                        <div>
                            <div class="admin-mobile-card-title"><?= e($product['name']) ?></div>
                            <div class="admin-mobile-card-sub">#<?= (int)$product['product_id'] ?> &middot; <?= e($product['category_name']) ?></div>
                        </div>
                        <input
                            type="checkbox"
                            class="admin-curation-row-check"
                            name="product_ids[]"
                            value="<?= (int)$product['product_id'] ?>"
                            form="bulk-curation-form"
                            aria-label="Select <?= e($product['name']) ?>"
                        >
                    </div>
                    <div class="admin-mobile-card-actions">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="action" value="single">
                            <input type="hidden" name="section" value="<?= e($section) ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
                            <input type="hidden" name="enabled" value="<?= $in_section ? '0' : '1' ?>">
                            <button type="submit" class="btn <?= $in_section ? '' : 'primary' ?> admin-mini-btn admin-mini-btn-fill-center">
                                <?= $in_section ? 'Remove' : 'Add' ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($total_pages > 1): ?>
    <?php
        $params = [
            'section' => $section,
        ];
        if ($category_id > 0) {
            $params['category'] = $category_id;
        }
        render_pagination($page, $total_pages, BASE_URL . '/admin/homepage-curation.php', $params, $total, $limit);
    ?>
<?php endif; ?>

</div>

<script>
(() => {
    const master = document.getElementById('select-all-curation');
    const boxes = Array.from(document.querySelectorAll('.admin-curation-row-check'));
    if (!master || boxes.length === 0) return;

    master.addEventListener('change', () => {
        const checked = master.checked;
        boxes.forEach((box) => { box.checked = checked; });
    });

    boxes.forEach((box) => {
        box.addEventListener('change', () => {
            master.checked = boxes.every((b) => b.checked);
        });
    });
})();
</script>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
