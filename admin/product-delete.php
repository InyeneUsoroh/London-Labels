<?php
/**
 * London Labels — Admin Delete Product
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Delete Product';
require_admin();

$product_id = (int)($_GET['id'] ?? 0);
if ($product_id <= 0) {
    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

$product = get_product_by_id($product_id);
if (!$product) {
    header('Location: ' . BASE_URL . '/admin/products.php');
    exit;
}

// POST — confirmed delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        header('Location: ' . BASE_URL . '/admin/product-delete.php?id=' . $product_id);
        exit;
    }

    // Delete physical images first
    $images = get_product_images($product_id);
    foreach ($images as $img) {
        $path = get_local_upload_file_path_from_url((string)($img['image_url'] ?? ''));
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }
    }

    delete_product($product_id);
    header('Location: ' . BASE_URL . '/admin/products.php?notice=' . urlencode('Product deleted.'));
    exit;
}

$images = get_product_images($product_id);

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-delete-compact">

<div class="admin-confirm-shell admin-confirm-shell-sm">
    <div class="admin-alert admin-alert-danger" role="alert">
        <p>You are about to permanently delete <strong><?= e($product['name']) ?></strong> and all its images. This cannot be undone.</p>
    </div>

    <div class="admin-card admin-card-gap">
        <div class="admin-card-body admin-card-body-flush">
            <table class="admin-detail-table">
                <tr>
                    <td class="label">Name</td>
                    <td class="strong"><?= e($product['name']) ?></td>
                </tr>
                <tr>
                    <td class="label">Category</td>
                    <td><?= e($product['category_name']) ?></td>
                </tr>
                <tr>
                    <td class="label">Price</td>
                    <td><?= format_price($product['price']) ?></td>
                </tr>
                <tr>
                    <td class="label">Images</td>
                    <td><?= count($images) ?> will be deleted</td>
                </tr>
            </table>
        </div>
    </div>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="admin-actions-row-tight">
            <button type="submit" class="btn danger">Yes, delete product</button>
            <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $product_id ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
