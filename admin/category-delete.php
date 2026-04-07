<?php
/**
 * London Labels — Admin Delete Category
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Delete Category';
require_admin();

$cat_id = (int)($_GET['id'] ?? 0);
if ($cat_id <= 0) {
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

$category = get_category_by_id($cat_id);
if (!$category) {
    header('Location: ' . BASE_URL . '/admin/categories.php');
    exit;
}

$product_count = get_product_count_by_category($cat_id);

// POST — confirmed delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        header('Location: ' . BASE_URL . '/admin/category-delete.php?id=' . $cat_id);
        exit;
    }

    // Re-check product count at delete time
    if (get_product_count_by_category($cat_id) > 0) {
        header('Location: ' . BASE_URL . '/admin/categories.php?error=has_products');
        exit;
    }

    try {
        delete_category($cat_id);
        header('Location: ' . BASE_URL . '/admin/categories.php?notice=' . urlencode('Category deleted.'));
    } catch (Exception $e) {
        header('Location: ' . BASE_URL . '/admin/categories.php?error=' . urlencode($e->getMessage()));
    }
    exit;
}

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-delete-compact">

<div class="admin-confirm-shell admin-confirm-shell-sm">

    <?php if ($product_count > 0): ?>
        <!-- Blocked — has products -->
        <div class="admin-alert admin-alert-warning" role="alert">
            <p>
                <strong><?= e($category['name']) ?></strong> has
                <?= $product_count ?> <?= $product_count === 1 ? 'product' : 'products' ?> assigned to it.
                You must reassign or delete those products before this category can be removed.
            </p>
        </div>
        <div class="admin-actions-row-tight admin-actions-row-tight-top">
            <a href="<?= BASE_URL ?>/admin/products.php?category=<?= $cat_id ?>" class="btn primary">
                View Products in this Category
            </a>
            <a href="<?= BASE_URL ?>/admin/categories.php" class="btn">Back</a>
        </div>

    <?php else: ?>
        <!-- Safe to delete -->
        <div class="admin-alert admin-alert-danger" role="alert">
            <p>You are about to permanently delete <strong><?= e($category['name']) ?></strong>. This cannot be undone.</p>
        </div>

        <div class="admin-card admin-card-gap">
            <div class="admin-card-body admin-card-body-flush">
                <table class="admin-detail-table">
                    <tr>
                        <td class="label">Name</td>
                        <td class="strong"><?= e($category['name']) ?></td>
                    </tr>
                    <tr>
                        <td class="label">Products</td>
                        <td>0 — safe to delete</td>
                    </tr>
                </table>
            </div>
        </div>

        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <div class="admin-actions-row-tight">
                <button type="submit" class="btn danger">Yes, delete category</button>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn">Cancel</a>
            </div>
        </form>
    <?php endif; ?>

</div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
