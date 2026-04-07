<?php
/**
 * London Labels — Admin Categories
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Categories';
$errors  = [];
$notice  = '';

require_admin();

$pdo = get_pdo();

// Which category is being edited (inline)
$edit_id = (int)($_GET['edit'] ?? 0);

// ── POST handlers ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {

        // ADD
        if (isset($_POST['add_category'])) {
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if (strlen($name) < 2) {
                $errors[] = 'Category name must be at least 2 characters.';
            } else {
                try {
                    $new_cat_id = create_category($name, $desc);

                    // Handle cover image upload
                    if (!empty($_FILES['cover_image']['tmp_name'])) {
                        $uploadErrors = [];
                        $stored = normalize_and_store_uploaded_image($_FILES['cover_image'], 'categories', 'cat_' . $new_cat_id, $uploadErrors, MAX_FILE_SIZE);
                        if ($stored !== null) {
                            update_category_cover($new_cat_id, (string)$stored['url']);
                        } elseif (!empty($uploadErrors)) {
                            $errors[] = $uploadErrors[0];
                        }
                    }

                    $notice = "Category \"{$name}\" added.";
                } catch (Exception $e) {
                    $errors[] = str_contains($e->getMessage(), 'Duplicate')
                        ? "A category named \"{$name}\" already exists."
                        : 'Could not add category: ' . $e->getMessage();
                }
            }
        }

        // EDIT / RENAME
        if (isset($_POST['edit_category'])) {
            $cat_id  = (int)($_POST['category_id'] ?? 0);
            $name    = trim($_POST['name'] ?? '');
            $desc    = trim($_POST['description'] ?? '');
            if ($cat_id <= 0)          $errors[] = 'Invalid category.';
            if (strlen($name) < 2)     $errors[] = 'Category name must be at least 2 characters.';
            if (empty($errors)) {
                try {
                    update_category($cat_id, $name, $desc);

                    // Handle cover image upload
                    if (!empty($_FILES['cover_image']['tmp_name'])) {
                        $uploadErrors = [];
                        $stored = normalize_and_store_uploaded_image($_FILES['cover_image'], 'categories', 'cat_' . $cat_id, $uploadErrors, MAX_FILE_SIZE);
                        if ($stored !== null) {
                            $oldCoverStmt = $pdo->prepare('SELECT cover_image FROM Categories WHERE category_id = ?');
                            $oldCoverStmt->execute([$cat_id]);
                            $oldCover = (string)($oldCoverStmt->fetchColumn() ?: '');

                            $imageUrl = (string)$stored['url'];
                            update_category_cover($cat_id, $imageUrl);

                            $oldPath = get_local_upload_file_path_from_url($oldCover);
                            if (is_string($oldPath) && $oldPath !== '' && is_file($oldPath)) {
                                @unlink($oldPath);
                                $oldThumb = dirname($oldPath) . '/thumbs/' . basename($oldPath);
                                if (is_file($oldThumb)) {
                                    @unlink($oldThumb);
                                }
                            }
                        } elseif (!empty($uploadErrors)) {
                            $errors[] = $uploadErrors[0];
                        }
                    }

                    $notice  = "Category updated.";
                    $edit_id = 0;
                } catch (Exception $e) {
                    $errors[] = str_contains($e->getMessage(), 'Duplicate')
                        ? "A category named \"{$name}\" already exists."
                        : 'Could not update category: ' . $e->getMessage();
                    $edit_id = $cat_id;
                }
            }
        }
    }
}

// Load categories with product counts
$cats_stmt = $pdo->query('
    SELECT c.category_id, c.name, c.description, c.cover_image,
           COUNT(p.product_id) AS product_count
    FROM Categories c
    LEFT JOIN Products p ON p.category_id = c.category_id
    GROUP BY c.category_id
    ORDER BY c.name ASC
');
$categories = $cats_stmt->fetchAll();

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle"><?= count($categories) ?> <?= count($categories) === 1 ? 'category' : 'categories' ?> &middot; organise your storefront</p>
    </div>
</div>

<div class="admin-categories-compact">

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-danger" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($notice): ?>
    <div class="admin-alert admin-alert-success" role="status">
        <p><?= e($notice) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['notice'])): ?>
    <div class="admin-alert admin-alert-success" role="status">
        <p><?= e($_GET['notice']) ?></p>
    </div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
    <div class="admin-alert admin-alert-danger" role="alert">
        <p><?= $_GET['error'] === 'has_products' ? 'That category still has products assigned — reassign them first.' : e($_GET['error']) ?></p>
    </div>
<?php endif; ?>

<div class="admin-categories-grid <?= $edit_id ? 'admin-categories-grid-editing' : '' ?>">

    <!-- ── Category list ── -->
    <div class="admin-categories-list-col">
        <?php if (empty($categories)): ?>
            <?php render_empty_state('No Categories Yet', 'Add your first category to start organising products.'); ?>
        <?php else: ?>
            <!-- Desktop table -->
            <div class="admin-table-wrap admin-cats-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="admin-th-narrow"></th>
                            <th>Category</th>
                            <th class="admin-th-center">Products</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <?php $safe_cover_image = sanitize_local_upload_media_url((string)($cat['cover_image'] ?? '')); ?>
                            <tr <?= (int)$cat['product_count'] === 0 ? 'class="admin-cat-row-empty"' : '' ?>>
                                <?php if ($edit_id === (int)$cat['category_id']): ?>
                                    <td colspan="4" class="admin-cat-edit-cell">
                                        <form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
                                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                            <input type="hidden" name="edit_category" value="1">
                                            <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">

                                            <label class="admin-cat-edit-label" for="edit_name_<?= $cat['category_id'] ?>">Name</label>
                                            <input
                                                type="text"
                                                id="edit_name_<?= $cat['category_id'] ?>"
                                                name="name"
                                                value="<?= e($_POST['name'] ?? $cat['name']) ?>"
                                                minlength="2" maxlength="80" required
                                                class="admin-cat-edit-input"
                                                autofocus>

                                            <label class="admin-cat-edit-label" for="edit_desc_<?= $cat['category_id'] ?>">Description</label>
                                            <textarea
                                                id="edit_desc_<?= $cat['category_id'] ?>"
                                                name="description"
                                                rows="2"
                                                maxlength="500"
                                                placeholder="Optional — shown on the categories page"
                                                class="admin-cat-edit-input admin-cat-edit-textarea"><?= e($_POST['description'] ?? ($cat['description'] ?? '')) ?></textarea>

                                            <label class="admin-cat-edit-label" for="edit_cover_<?= $cat['category_id'] ?>">Cover Image</label>
                                            <?php if ($safe_cover_image !== null): ?>
                                                <div class="admin-cover-preview-lg">
                                                    <img src="<?= e($safe_cover_image) ?>" alt="Current cover">
                                                </div>
                                            <?php endif; ?>
                                            <input type="file" id="edit_cover_<?= $cat['category_id'] ?>" name="cover_image"
                                                   accept=".jpg,.jpeg,.png,.gif,image/*"
                                                   class="admin-cat-edit-input admin-file-input-pad">
                                            <small class="admin-field-hint">JPG, PNG or GIF · max 5MB. Replaces existing image.</small>

                                            <div class="admin-cat-edit-actions">
                                                <button type="submit" class="btn primary admin-mini-btn">Save</button>
                                                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn admin-mini-btn">Cancel</a>
                                            </div>
                                        </form>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <?php if ($safe_cover_image !== null): ?>
                                            <img src="<?= e($safe_cover_image) ?>" alt="" class="admin-cat-thumb" loading="lazy">
                                        <?php else: ?>
                                            <div class="admin-cat-thumb-placeholder"><?= e(mb_strtoupper(mb_substr($cat['name'], 0, 1))) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="admin-text-600"><?= e($cat['name']) ?></div>
                                        <?php if (!empty($cat['description'])): ?>
                                            <div class="admin-subtext"><?= e($cat['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="admin-td-center">
                                        <?php if ($cat['product_count'] > 0): ?>
                                            <a href="<?= BASE_URL ?>/admin/products.php?category=<?= $cat['category_id'] ?>"
                                               class="admin-link-count">
                                                <?= (int)$cat['product_count'] ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="admin-cat-zero-badge">No products</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="admin-actions-row-end">
                                            <a href="<?= BASE_URL ?>/admin/categories.php?edit=<?= $cat['category_id'] ?>"
                                               class="btn admin-mini-btn">Edit</a>
                                            <a href="<?= BASE_URL ?>/admin/category-delete.php?id=<?= $cat['category_id'] ?>"
                                               class="btn danger admin-mini-btn">Delete</a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile card list -->
            <div class="admin-mobile-list admin-cats-mobile-list">
                <?php foreach ($categories as $cat): ?>
                    <?php $safe_cover_image = sanitize_local_upload_media_url((string)($cat['cover_image'] ?? '')); ?>
                    <?php if ($edit_id === (int)$cat['category_id']): ?>
                        <div class="admin-mobile-card">
                            <form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="edit_category" value="1">
                                <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                <div class="form-group admin-form-group-spaced-10">
                                    <label class="admin-cat-edit-label" for="m_edit_name_<?= $cat['category_id'] ?>">Name</label>
                                    <input type="text" id="m_edit_name_<?= $cat['category_id'] ?>"
                                           name="name" value="<?= e($_POST['name'] ?? $cat['name']) ?>"
                                           minlength="2" maxlength="80" required
                                           class="admin-cat-edit-input" autofocus>
                                </div>
                                <div class="form-group admin-form-group-spaced-10">
                                    <label class="admin-cat-edit-label" for="m_edit_desc_<?= $cat['category_id'] ?>">Description</label>
                                    <textarea id="m_edit_desc_<?= $cat['category_id'] ?>"
                                              name="description" rows="2" maxlength="500"
                                              class="admin-cat-edit-input admin-cat-edit-textarea"><?= e($_POST['description'] ?? ($cat['description'] ?? '')) ?></textarea>
                                </div>
                                <div class="form-group admin-form-group-spaced-10">
                                    <label class="admin-cat-edit-label">Cover Image</label>
                                    <?php if ($safe_cover_image !== null): ?>
                                        <img src="<?= e($safe_cover_image) ?>" alt="Current cover" class="admin-cover-preview-sm">
                                    <?php endif; ?>
                                    <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.gif,image/*"
                                           class="admin-cat-edit-input admin-file-input-pad">
                                    <small class="admin-field-hint">JPG, PNG or GIF · max 5MB.</small>
                                </div>
                                <div class="admin-cat-edit-actions">
                                    <button type="submit" class="btn primary admin-mini-btn">Save</button>
                                    <a href="<?= BASE_URL ?>/admin/categories.php" class="btn admin-mini-btn">Cancel</a>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="admin-mobile-card">
                            <div class="admin-mobile-card-top">
                                <div class="admin-flex-center-gap-10">
                                    <?php if ($safe_cover_image !== null): ?>
                                                <img src="<?= e($safe_cover_image) ?>" alt="" class="admin-cat-thumb" loading="lazy">
                                    <?php else: ?>
                                        <div class="admin-cat-thumb-placeholder"><?= e(mb_strtoupper(mb_substr($cat['name'], 0, 1))) ?></div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="admin-mobile-card-title"><?= e($cat['name']) ?></div>
                                        <?php if (!empty($cat['description'])): ?>
                                            <div class="admin-mobile-card-sub"><?= e($cat['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ((int)$cat['product_count'] > 0): ?>
                                    <span class="admin-mobile-card-badge">
                                        <?= (int)$cat['product_count'] ?> product<?= $cat['product_count'] != 1 ? 's' : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="admin-cat-zero-badge">No products</span>
                                <?php endif; ?>
                            </div>
                            <div class="admin-mobile-card-actions">
                                <a href="<?= BASE_URL ?>/admin/categories.php?edit=<?= $cat['category_id'] ?>" class="btn admin-mini-btn">Edit</a>
                                <a href="<?= BASE_URL ?>/admin/category-delete.php?id=<?= $cat['category_id'] ?>" class="btn danger admin-mini-btn">Delete</a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Add form — hidden when editing ── -->
    <?php if (!$edit_id): ?>
    <div class="admin-categories-form-col">
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Add Category</h2>
            </div>
            <div class="admin-card-body">
                <form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="add_category" value="1">

                    <div class="form-group">
                        <label for="name">Name <span class="admin-required">*</span></label>
                        <input type="text" id="name" name="name"
                               value="<?= e($_POST['name'] ?? '') ?>"
                               minlength="2" maxlength="80" required
                               placeholder="e.g. Women's Clothing">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                                  rows="3" maxlength="500"
                                  placeholder="Optional — shown on the categories page"><?= e($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="cover_image_new">Cover Image</label>
                        <input type="file" id="cover_image_new" name="cover_image"
                               accept=".jpg,.jpeg,.png,.gif,image/*"
                               class="admin-file-input-pad">
                        <small class="admin-field-hint">JPG, PNG or GIF · max 5MB. You can add or change this later.</small>
                    </div>

                    <button type="submit" class="btn primary admin-btn-full">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

 </div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
