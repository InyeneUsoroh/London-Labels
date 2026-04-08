<?php
/**
 * London Labels — Admin Add Product
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Add Product';
$errors = [];

require_admin();

$categories = get_all_categories();

// Field values (sticky on error)
$name        = '';
$description = '';
$category_id = 0;
$price       = '';
$quantity    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $name        = trim($_POST['name']        ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price       = $_POST['price']    ?? '';
        $quantity    = $_POST['quantity'] ?? '';
        $add_another = isset($_POST['add_another']);

        $data = [
            'name'        => $name,
            'description' => $description,
            'category_id' => $category_id,
            'price'       => (float)$price,
            'quantity'    => (int)$quantity,
        ];

        $product_id = create_product_helper($data, $errors);

        if ($product_id) {
            if ($add_another) {
                header('Location: ' . BASE_URL . '/admin/product-add.php?added=' . urlencode($name));
            } else {
                header('Location: ' . BASE_URL . '/admin/product-edit.php?id=' . $product_id . '&img_notice=Product+created.+Add+images+below.');
            }
            exit;
        }
    }
}

$just_added = trim((string)($_GET['added'] ?? ''));

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Add Product</h1>
        <p class="admin-page-subtitle">New product will be live in the shop immediately.</p>
    </div>
</div>

<?php if ($just_added): ?>
    <div class="admin-alert admin-alert-success" role="status">
        <p>"<?= e($just_added) ?>" added. Fill in the next product below.</p>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-danger" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="admin-product-add-grid">

    <!-- Main form -->
    <div class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Product Details</h2>
        </div>
        <div class="admin-card-body">
            <form method="post" novalidate autocomplete="off" id="addProductForm">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="add_another" id="addAnotherFlag" value="">

                <div class="form-group">
                    <label for="name">Product Name <span class="admin-required">*</span></label>
                    <input type="text" id="name" name="name"
                           value="<?= e($name) ?>"
                           minlength="3" maxlength="120" required
                           placeholder="e.g. Vintage Linen Blazer">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"
                              rows="6" maxlength="2000"
                              placeholder="Describe the item — fabric, fit, condition, sizing notes..."><?= e($description) ?></textarea>
                    <small class="admin-form-hint">
                        <span id="desc-count"><?= strlen($description) ?></span> / 2000
                    </small>
                </div>

                <div class="form-group">
                    <label for="category_id">Category <span class="admin-required">*</span></label>
                    <select id="category_id" name="category_id" required>
                        <option value="">-- Select category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['category_id'] ?>"
                                <?= $category_id === (int)$cat['category_id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($categories)): ?>
                        <small class="admin-form-hint admin-form-hint-warning">
                            No categories yet. <a href="<?= BASE_URL ?>/admin/categories.php">Add one first.</a>
                        </small>
                    <?php endif; ?>
                </div>

                <div class="admin-two-col">
                    <div class="form-group">
                        <label for="price">Price (<?= CURRENCY_SYMBOL ?>) <span class="admin-required">*</span></label>
                        <input type="number" id="price" name="price"
                               value="<?= e($price) ?>"
                               min="0.01" step="0.01" inputmode="decimal" required
                               placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label for="quantity">Stock Quantity <span class="admin-required">*</span></label>
                        <input type="number" id="quantity" name="quantity"
                               value="<?= e($quantity) ?>"
                               min="0" step="1" inputmode="numeric" required
                               placeholder="0">
                    </div>
                </div>

                <div class="admin-product-add-actions">
                    <button type="submit" class="btn primary admin-btn-flex">
                        Add Product &amp; Edit Images
                    </button>
                    <button type="button" class="btn secondary admin-btn-flex" id="addAnotherBtn">
                        Add &amp; Add Another
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Sidebar hint -->
    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">What happens next</h2>
            </div>
            <div class="admin-card-body">
                <div class="admin-add-hint-list">
                    <div class="admin-add-hint-item">
                        <span class="admin-add-hint-num">1</span>
                        <div>
                            <strong>Fill in the details</strong>
                            <p>Name, description, category, price and stock quantity.</p>
                        </div>
                    </div>
                    <div class="admin-add-hint-item">
                        <span class="admin-add-hint-num">2</span>
                        <div>
                            <strong>Add Product &amp; Edit Images</strong>
                            <p>Saves the product and takes you straight to the image upload screen.</p>
                        </div>
                    </div>
                    <div class="admin-add-hint-item">
                        <span class="admin-add-hint-num">3</span>
                        <div>
                            <strong>Add &amp; Add Another</strong>
                            <p>Saves and returns here with a fresh form — useful when adding a new shipment in bulk.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($categories)): ?>
        <div class="admin-card admin-card-spaced-top">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Categories</h2>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="admin-card-link">Manage</a>
            </div>
            <div class="admin-card-body admin-card-body-flush">
                <?php foreach ($categories as $cat): ?>
                    <div class="admin-category-list-item">
                        <?= e($cat['name']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
(function () {
    var ta = document.getElementById('description');
    var counter = document.getElementById('desc-count');
    if (ta && counter) {
        ta.addEventListener('input', function () {
            counter.textContent = ta.value.length;
        });
    }

    var addAnotherBtn = document.getElementById('addAnotherBtn');
    var addAnotherFlag = document.getElementById('addAnotherFlag');
    var form = document.getElementById('addProductForm');
    if (addAnotherBtn && addAnotherFlag && form) {
        addAnotherBtn.addEventListener('click', function () {
            addAnotherFlag.value = '1';
            form.submit();
        });
    }
})();
</script>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
