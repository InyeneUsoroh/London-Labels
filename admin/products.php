<?php
/**
 * London Labels - Admin Products Management
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Products Management';
$errors = [];
$notice = '';

require_admin();

$page        = max(1, (int)($_GET['page'] ?? 1));
$filter_cat  = (int)($_GET['category'] ?? 0);
$limit       = 20;
$offset      = ($page - 1) * $limit;

$all_categories = get_all_categories();

if ($filter_cat > 0) {
    // Filtered by category
    $products = get_all_products($limit, $offset, $filter_cat);
    $total    = get_product_count_by_category($filter_cat);
    $filter_cat_name = '';
    foreach ($all_categories as $c) {
        if ((int)$c['category_id'] === $filter_cat) { $filter_cat_name = $c['name']; break; }
    }
} else {
    $products = get_all_products($limit, $offset);
    $total    = get_product_count();
    $filter_cat_name = '';
}

$total_pages = max(1, ceil($total / $limit));

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle">
            <?php if ($filter_cat > 0 && $filter_cat_name): ?>
                Showing products in <strong><?= e($filter_cat_name) ?></strong>
            <?php else: ?>
                Manage your product catalog and inventory.
            <?php endif; ?>
        </p>
    </div>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>/admin/product-add.php" class="btn primary">+ Add Product</a>
    </div>
</div>

<div class="admin-products-compact">

<?php if (!empty($_GET['notice'])): ?>
    <div class="admin-alert admin-alert-success" role="status"><p><?= e($_GET['notice']) ?></p></div>
<?php endif; ?>

<!-- Category filter bar -->
<form method="get" class="admin-products-filter-bar">
    <label for="cat-filter" class="admin-products-filter-label">Category:</label>
    <select id="cat-filter" name="category" class="admin-products-filter-select" onchange="this.form.submit()">
        <option value="">All Categories</option>
        <?php foreach ($all_categories as $c): ?>
            <option value="<?= $c['category_id'] ?>" <?= $filter_cat === (int)$c['category_id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if ($filter_cat > 0): ?>
        <a href="<?= BASE_URL ?>/admin/products.php" class="btn admin-mini-btn">Clear</a>
    <?php endif; ?>
</form>

<?php if (empty($products)): ?>
    <?php render_empty_state('No Products Found', 'Add your first product to start building your catalog.'); ?>
<?php else: ?>

    <!-- Desktop table -->
    <div class="admin-table-wrap admin-products-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong>#<?= $product['product_id'] ?></strong></td>
                        <td><?= e($product['name']) ?></td>
                        <td><?= e($product['category_name']) ?></td>
                        <td><?= format_price($product['price']) ?></td>
                        <td>
                            <span class="admin-stock-pill <?= $product['quantity'] > 0 ? 'in' : 'out' ?>">
                                <?= $product['quantity'] ?>
                            </span>
                        </td>
                        <td><?= date('M d, Y', strtotime($product['added_at'])) ?></td>
                        <td style="white-space: nowrap;">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $product['product_id'] ?>" class="btn admin-mini-btn">Edit</a>
                                <a href="<?= BASE_URL ?>/admin/product-delete.php?id=<?= $product['product_id'] ?>" class="btn danger admin-mini-btn">Delete</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-mobile-list admin-products-mobile-list">
        <?php foreach ($products as $product): ?>
        <div class="admin-mobile-card">
            <div class="admin-mobile-card-top">
                <div>
                    <div class="admin-mobile-card-title"><?= e($product['name']) ?></div>
                    <div class="admin-mobile-card-sub"><?= e($product['category_name']) ?> &middot; #<?= $product['product_id'] ?></div>
                </div>
                <span class="admin-stock-pill admin-flex-no-shrink <?= $product['quantity'] > 0 ? 'in' : 'out' ?>">
                    <?= $product['quantity'] > 0 ? $product['quantity'] . ' in stock' : 'Out of stock' ?>
                </span>
            </div>
            <div class="admin-mobile-card-meta">
                <span><?= format_price($product['price']) ?></span>
                <span>Added <?= date('M d, Y', strtotime($product['added_at'])) ?></span>
                <?php if ($product['sku'] ?? ''): ?>
                    <span>SKU: <?= e($product['sku']) ?></span>
                <?php endif; ?>
            </div>
            <div class="admin-mobile-card-actions">
                <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $product['product_id'] ?>" class="btn admin-mini-btn admin-mini-btn-fill-center">Edit</a>
                <a href="<?= BASE_URL ?>/admin/product-delete.php?id=<?= $product['product_id'] ?>" class="btn danger admin-mini-btn admin-mini-btn-fill-center">Delete</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

<?php endif; ?>

<?php if ($total_pages > 1): ?>
    <?php
        $pp = $filter_cat > 0 ? ['category' => $filter_cat] : [];
        render_pagination($page, $total_pages, BASE_URL . '/admin/products.php', $pp, $total, $limit);
    ?>
<?php endif; ?>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
