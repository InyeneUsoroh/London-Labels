<?php
/**
 * London Labels - Admin Edit Product
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Edit Product';
$errors  = [];
$notice  = '';

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

$name        = $product['name'];
$sku         = $product['sku'] ?? '';
$description = $product['description'] ?? '';
$tags        = $product['tags'] ?? '';


$source_label = $product['source_label'] ?? 'London, United Kingdom';
$condition_label = $product['condition_label'] ?? 'New';
$category_id = $product['category_id'];
$price       = $product['price'];
$quantity    = $product['quantity'];

$categories     = get_all_categories();
$product_images = get_product_images($product_id);
$variants       = get_product_variants($product_id);
$image_count = count($product_images);
$image_min_recommended = defined('PRODUCT_IMAGES_MIN_RECOMMENDED') ? (int)PRODUCT_IMAGES_MIN_RECOMMENDED : 6;
$image_max_recommended = defined('PRODUCT_IMAGES_MAX_RECOMMENDED') ? (int)PRODUCT_IMAGES_MAX_RECOMMENDED : 8;
$image_max_allowed = defined('PRODUCT_IMAGES_MAX_ALLOWED') ? (int)PRODUCT_IMAGES_MAX_ALLOWED : 8;

// Image feedback from upload handler
$img_notice = trim((string)($_GET['img_notice'] ?? ''));
$img_error  = trim((string)($_GET['img_error']  ?? ''));

// Handle product details POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? 'details';

    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } elseif ($action === 'variants') {
        $sizes = $_POST['variant_size']      ?? [];
        $qtys  = $_POST['variant_qty']       ?? [];
        $types = $_POST['variant_size_type'] ?? [];
        $mods  = $_POST['variant_price_mod'] ?? [];
        $parsed = [];
        foreach ($sizes as $i => $sz) {
            $sz = trim((string)$sz);
            if ($sz !== '') {
                $parsed[] = [
                    'size_type'      => $types[$i] ?? 'clothing',
                    'size'           => $sz,
                    'quantity'       => (int)($qtys[$i] ?? 0),
                    'price_modifier' => (float)($mods[$i] ?? 0),
                ];
            }
        }
        [$normalized_variants, $variant_errors] = validate_and_normalize_product_variants($parsed);
        if (!empty($variant_errors)) {
            $errors = array_merge($errors, $variant_errors);
            $variants = $parsed;
        } else {
            save_product_variants($product_id, $normalized_variants);
            $variants = get_product_variants($product_id);
            $notice   = 'Size variants saved.';
        }
    } else {
        $data = [
            'name'           => trim($_POST['name'] ?? ''),
            'sku'            => trim($_POST['sku'] ?? ''),
            'description'    => trim($_POST['description'] ?? ''),
            'tags'           => trim($_POST['tags'] ?? ''),

            'source_label'   => trim((string)($_POST['source_label'] ?? 'London, United Kingdom')),
            'condition_label' => trim((string)($_POST['condition_label'] ?? 'New')),
            'category_id'    => (int)($_POST['category_id'] ?? 0),
            'price'          => (float)($_POST['price']    ?? 0),
            'quantity'       => (int)($_POST['quantity']   ?? 0),
        ];
        if (empty($errors) && update_product_helper($product_id, $data, $errors)) {
            $notice      = 'Product updated.';
            $product     = get_product_by_id($product_id);
            $name        = $product['name'];
            $sku         = $product['sku'] ?? '';
            $description = $product['description'] ?? '';
            $tags        = $product['tags'] ?? '';

            $source_label = $product['source_label'] ?? 'London, United Kingdom';
            $condition_label = $product['condition_label'] ?? 'New';
            $category_id = $product['category_id'];
            $price       = $product['price'];
            $quantity    = $product['quantity'];
        }
    }
}
include __DIR__ . '/inc_admin_layout.php';

// Completion checklist shown when product is incomplete
$setup_has_image    = !empty($product_images);
$setup_has_variants = !empty($variants);
$setup_has_desc     = trim($description) !== '';
$setup_complete     = $setup_has_image && $setup_has_variants && $setup_has_desc;
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title"><?= e($name) ?></h1>
        <p class="admin-page-subtitle">
            <?= e($product['category_name']) ?> &middot;
            <span class="admin-status-pill <?= $quantity > 0 ? ($quantity <= 5 ? 'pending' : 'completed') : 'cancelled' ?>">
                <?= $quantity > 0 ? $quantity . ' in stock' : 'Out of stock' ?>
            </span>
            <?php if ($sku): ?>
                &middot; <span class="admin-text-small-secondary">SKU: <?= e($sku) ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="admin-page-actions">
        <a href="<?= BASE_URL ?>/admin/product-delete.php?id=<?= $product_id ?>" class="btn danger">Delete</a>
    </div>
</div>

<div class="admin-product-edit-compact">

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-danger" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($notice): ?>
    <div class="admin-alert admin-alert-success" role="status"><p><?= e($notice) ?></p></div>
<?php endif; ?>

<?php if (!$setup_complete): ?>
<div class="admin-setup-checklist" role="region" aria-label="Product setup checklist">
    <p class="admin-setup-checklist-title">Finish setting up this product:</p>
    <ul class="admin-setup-checklist-list">
        <li class="<?= $setup_has_image ? 'done' : '' ?>">
            <?= $setup_has_image ? '&#10003;' : '&#9711;' ?> Upload at least one image
        </li>
        <li class="<?= $setup_has_variants ? 'done' : '' ?>">
            <?= $setup_has_variants ? '&#10003;' : '&#9711;' ?> Add size variants
        </li>
        <li class="<?= $setup_has_desc ? 'done' : '' ?>">
            <?= $setup_has_desc ? '&#10003;' : '&#9711;' ?> Write a product description
        </li>
    </ul>
</div>
<?php endif; ?>

<div class="admin-product-edit-grid">

    <!-- Left: details + variants -->
    <div class="admin-flex-col-gap-20">

        <!-- Product Details -->
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Product Details</h2>
            </div>
            <div class="admin-card-body">
                <form method="post" enctype="multipart/form-data" novalidate autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_action" value="details">

                    <div class="form-group">
                        <label for="name">Product Name <span class="admin-required">*</span></label>
                        <input type="text" id="name" name="name"
                               value="<?= e($name) ?>"
                               minlength="3" maxlength="120" required
                               placeholder="e.g. Nike Air Force 1 Low White">
                    </div>

                    <div class="form-group">
                        <label for="sku">SKU / Product Code</label>
                        <input type="text" id="sku" name="sku"
                               value="<?= e($sku) ?>"
                               maxlength="80" placeholder="e.g. NK-AF1-WHT-42"
                               autocomplete="off">
                        <small class="admin-field-hint">Optional. Useful for inventory tracking and in-store reference.</small>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"
                                  rows="6" maxlength="2000"
                                  placeholder="Describe the product - material, fit, style notes..."><?= e($description) ?></textarea>
                        <small class="admin-field-hint admin-text-small-secondary">
                            <span id="desc-count"><?= strlen($description) ?></span> / 2000
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category <span class="admin-required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"
                                    <?= (int)$category_id === (int)$cat['category_id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-two-col">
                        <div class="form-group">
                            <label for="price">Price (<?= CURRENCY_SYMBOL ?>) <span class="admin-required">*</span></label>
                            <input type="number" id="price" name="price"
                                   value="<?= $price ?>"
                                   min="0.01" step="0.01" inputmode="decimal" required>
                        </div>
                        <div class="form-group">
                            <label for="quantity">Total Stock <span class="admin-required">*</span></label>
                            <input type="number" id="quantity" name="quantity"
                                   value="<?= $quantity ?>"
                                   min="0" step="1" inputmode="numeric" required>
                            <small class="admin-field-hint">Overall stock across all sizes.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags</label>
                        <input type="text" id="tags" name="tags"
                               value="<?= e($tags) ?>"
                               maxlength="500" placeholder="e.g. sneakers, casual, unisex"
                               autocomplete="off">
                        <small class="admin-field-hint">Comma-separated keywords.</small>
                    </div>



                    <div class="admin-two-col">
                        <div class="form-group">
                            <label for="source_label">Sourced From</label>
                            <input type="text" id="source_label" name="source_label"
                                   value="<?= e($source_label) ?>"
                                   maxlength="120" placeholder="e.g. London, United Kingdom"
                                   autocomplete="off">
                            <small class="admin-field-hint">Shown in Product Details on the product page.</small>
                        </div>
                        <div class="form-group">
                            <label for="condition_label">Condition</label>
                            <input type="text" id="condition_label" name="condition_label"
                                   value="<?= e($condition_label) ?>"
                                   maxlength="50" placeholder="e.g. New"
                                   autocomplete="off">
                            <small class="admin-field-hint">Shown in Product Details on the product page.</small>
                        </div>
                    </div>

                    <button type="submit" class="btn primary admin-btn-full">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Size Variants -->
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Size Variants</h2>
                <span class="admin-text-small-secondary"><?= count($variants) ?> size<?= count($variants) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="admin-card-body">
                <form method="post" novalidate id="variants-form">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="_action" value="variants">

                    <div id="variants-list">
                        <?php
                            $variantRows = !empty($variants) ? $variants : [['size_type'=>'clothing','size'=>'','quantity'=>0,'price_modifier'=>0]];
                        ?>
                        <?php foreach ($variantRows as $v): ?>
                            <div class="admin-variant-row" data-variant-row>
                                <select name="variant_size_type[]" class="admin-variant-type-select" onchange="onTypeChange(this)">
                                    <option value="clothing"  <?= ($v['size_type'] ?? 'clothing') === 'clothing'  ? 'selected' : '' ?>>Clothing</option>
                                    <option value="footwear"  <?= ($v['size_type'] ?? '') === 'footwear'  ? 'selected' : '' ?>>Footwear</option>
                                    <option value="one_size"  <?= ($v['size_type'] ?? '') === 'one_size'  ? 'selected' : '' ?>>One Size</option>
                                </select>
                                <?php
                                    $type = $v['size_type'] ?? 'clothing';
                                    $ph = match($type) {
                                        'footwear' => 'e.g. 38, 39, 40, 41, 42',
                                        'one_size' => 'One Size',
                                        default    => 'e.g. XS, S, M, L, XL',
                                    };
                                ?>
                                <input type="text" name="variant_size[]"
                                       value="<?= e((string)($v['size'] ?? '')) ?>"
                                       placeholder="<?= $ph ?>"
                                       class="admin-variant-size-input" maxlength="40">
                                <input type="number" name="variant_qty[]"
                                       value="<?= (int)($v['quantity'] ?? 0) ?>"
                                       placeholder="Qty" min="0" step="1"
                                       class="admin-variant-qty-input" inputmode="numeric">
                                <input type="number" name="variant_price_mod[]"
                                       value="<?= (float)($v['price_modifier'] ?? 0) ?>"
                                       placeholder="NGN +/-" step="0.01"
                                       class="admin-variant-mod-input" inputmode="decimal"
                                       title="Price adjustment from base (e.g. +500 or -200)">
                                <button type="button" class="admin-variant-remove btn danger admin-mini-btn" onclick="removeVariantRow(this)" aria-label="Remove row">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="admin-actions-row-gap-8-top12">
                        <button type="button" class="btn" onclick="addVariantRow()">+ Add Size</button>
                        <button type="submit" class="btn primary">Save Variants</button>
                    </div>
                    <p class="admin-field-hint admin-help-note-top8">
                        Each row is one size. Clothing accepts XXS-XXL. Footwear accepts EU 35-49 (e.g. 40 or EU 40). One Size accepts only "One Size".
                    </p>
                </form>
            </div>
        </div>

    </div>

    <!-- Right: images -->
    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Product Images</h2>
                <span class="admin-text-small-secondary"><?= $image_count ?> / <?= $image_max_allowed ?></span>
            </div>

            <?php if ($img_error): ?>
                <div class="admin-alert admin-alert-danger admin-alert-inline" role="alert">
                    <p><?= e($img_error) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($img_notice): ?>
                <div class="admin-alert admin-alert-success admin-alert-inline" role="status">
                    <p><?= e($img_notice) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($image_count < $image_min_recommended): ?>
                <div class="admin-alert admin-alert-warning admin-alert-inline" role="status">
                    <p>Industry target: use <?= $image_min_recommended ?>-<?= $image_max_recommended ?> images for best conversion. You currently have <?= $image_count ?>.</p>
                </div>
            <?php elseif ($image_count > $image_max_recommended): ?>
                <div class="admin-alert admin-alert-warning admin-alert-inline" role="status">
                    <p>You have <?= $image_count ?> images. Recommended range is <?= $image_min_recommended ?>-<?= $image_max_recommended ?> for cleaner carousel UX.</p>
                </div>
            <?php else: ?>
                <div class="admin-alert admin-alert-success admin-alert-inline" role="status">
                    <p>Great coverage: <?= $image_count ?> images is within the recommended <?= $image_min_recommended ?>-<?= $image_max_recommended ?> range.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($product_images)): ?>
                <div class="admin-img-grid" id="sortable-image-grid">
                    <?php foreach ($product_images as $img): ?>
                        <?php $safe_product_image = sanitize_local_upload_media_url((string)($img['image_url'] ?? '')); ?>
                        <?php if ($safe_product_image === null) continue; ?>
                        <div class="admin-img-item <?= $img['is_primary'] ? 'is-primary' : '' ?>" data-image-id="<?= $img['image_id'] ?>" draggable="true" style="cursor: grab;">
                            <img src="<?= e($safe_product_image) ?>"
                                 alt="Product image"
                                 class="admin-img-thumb"
                                 loading="lazy">
                            <?php if ($img['is_primary']): ?>
                                <span class="admin-img-primary-badge">Primary</span>
                            <?php endif; ?>
                            <div class="admin-img-actions">
                                <?php if (!$img['is_primary']): ?>
                                    <form method="post" action="<?= BASE_URL ?>/admin/product-image-upload.php">
                                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                        <input type="hidden" name="action" value="set_primary">
                                        <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
                                        <button type="submit" class="admin-img-btn">Set primary</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= BASE_URL ?>/admin/product-image-upload.php"
                                      onsubmit="return confirm('Delete this image?');">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                    <input type="hidden" name="action" value="delete_image">
                                    <input type="hidden" name="image_id" value="<?= $img['image_id'] ?>">
                                    <button type="submit" class="admin-img-btn danger">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="admin-card-body">
                    <p class="admin-muted-note">No images yet. Upload the first one below.</p>
                </div>
            <?php endif; ?>

            <?php if ($image_count < $image_max_allowed): ?>
                <div class="admin-img-upload-wrap" id="drop-zone">
                    <form method="post" action="<?= BASE_URL ?>/admin/product-image-upload.php"
                          enctype="multipart/form-data" id="adminImageUploadForm">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        <input type="hidden" name="action" value="upload">
                        <label class="admin-img-upload-label" for="image_file" id="drop-zone-label" style="border: 2px dashed #ccc; border-radius: 8px; cursor: pointer; transition: 0.3s; padding: 30px; text-align: center; display: block;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="margin: 0 auto; display: block;"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                            <span style="display: block; margin-top: 10px; font-weight: bold;">Click to select or drag and drop images</span>
                            <small style="display: block; margin-top: 4px; color: #666;">JPG, PNG, GIF or WEBP &middot; max 5 MB per file &middot; target <?= $image_min_recommended ?>-<?= $image_max_recommended ?> images</small>
                            <input type="file" id="image_file" name="images[]"
                                   accept=".jpg,.jpeg,.png,.gif,.webp,image/*"
                                   class="admin-hidden-input" multiple>
                        </label>
                        <div class="upload-progress-wrap" id="adminImageUploadProgress" hidden style="margin-top: 15px;">
                            <div class="upload-progress-bar" style="background: #eef; height: 10px; border-radius: 5px; overflow: hidden;"><span id="adminImageUploadProgressBar" style="display: block; height: 100%; background: #4caf50; width: 0%; transition: 0.3s;"></span></div>
                            <p class="upload-progress-text" id="adminImageUploadProgressText" style="margin-top: 5px; font-size: 13px; color: #555;">Uploading...</p>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="admin-card-body">
                    <p class="admin-muted-note">Maximum of <?= $image_max_allowed ?> images reached. Remove one to upload another.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

    </div>

<script>
(function () {
    var ta = document.getElementById('description');
    var counter = document.getElementById('desc-count');
    if (!ta || !counter) return;
    ta.addEventListener('input', function () { counter.textContent = ta.value.length; });
})();

function addVariantRow() {
    var list = document.getElementById('variants-list');
    var row = document.createElement('div');
    row.className = 'admin-variant-row';
    row.setAttribute('data-variant-row', '');
    row.innerHTML = '<select name="variant_size_type[]" class="admin-variant-type-select" onchange="onTypeChange(this)">'
        + '<option value="clothing">Clothing</option>'
        + '<option value="footwear">Footwear</option>'
        + '<option value="one_size">One Size</option>'
        + '</select>'
        + '<input type="text" name="variant_size[]" placeholder="e.g. M, L, XL" class="admin-variant-size-input" maxlength="40">'
        + '<input type="number" name="variant_qty[]" placeholder="Qty" min="0" step="1" class="admin-variant-qty-input" inputmode="numeric">'
        + '<input type="number" name="variant_price_mod[]" placeholder="NGN +/-" step="0.01" class="admin-variant-mod-input" inputmode="decimal" title="Price adjustment from base">'
        + '<button type="button" class="admin-variant-remove btn danger admin-mini-btn" onclick="removeVariantRow(this)" aria-label="Remove row">\u00d7</button>';
    list.appendChild(row);
    row.querySelector('.admin-variant-size-input').focus();
}

function onTypeChange(select) {
    var row = select.closest('[data-variant-row]');
    var sizeInput = row.querySelector('.admin-variant-size-input');
    // Only update placeholder - do not overwrite a typed value.
    var placeholders = {
        'footwear': 'e.g. 38, 39, 40, 41, 42',
        'clothing': 'e.g. XS, S, M, L, XL',
        'one_size': 'One Size'
    };
    sizeInput.placeholder = placeholders[select.value] || 'Size';
    if (select.value === 'one_size' && sizeInput.value === '') {
        sizeInput.value = 'One Size';
    }
}

function removeVariantRow(btn) {
    var rows = document.querySelectorAll('[data-variant-row]');
    if (rows.length <= 1) {
        btn.closest('[data-variant-row]').querySelectorAll('input').forEach(function(i){ i.value = ''; });
        return;
    }
    btn.closest('[data-variant-row]').remove();
}
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const dropZoneLabel = document.getElementById('drop-zone-label');
    const fileInput = document.getElementById('image_file');
    const form = document.getElementById('adminImageUploadForm');
    const progressWrap = document.getElementById('adminImageUploadProgress');
    const progressBar = document.getElementById('adminImageUploadProgressBar');
    const progressText = document.getElementById('adminImageUploadProgressText');
    
    if (!dropZoneLabel || !fileInput || !form) return;

    // Build the upload URL explicitly instead of relying on form.action
    const uploadUrl = (window.BASE_URL || '') + '/admin/product-image-upload.php';

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZoneLabel.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZoneLabel.addEventListener(eventName, () => {
            dropZoneLabel.style.borderColor = '#4caf50';
            dropZoneLabel.style.backgroundColor = '#f1fff1';
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZoneLabel.addEventListener(eventName, () => {
            dropZoneLabel.style.borderColor = '#ccc';
            dropZoneLabel.style.backgroundColor = '';
        }, false);
    });

    dropZoneLabel.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', handleFilesEvent, false);

    function handleDrop(e) {
        let dt = e.dataTransfer;
        let files = dt.files;
        uploadFiles(files);
    }

    function handleFilesEvent() {
        uploadFiles(fileInput.files);
    }

    function uploadFiles(files) {
        if (!files.length) return;

        let formData = new FormData(form);
        formData.delete('images[]');
        for (let i = 0; i < files.length; i++) {
            formData.append('images[]', files[i]);
        }

        progressWrap.hidden = false;
        dropZoneLabel.style.display = 'none';

        const xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressBar.style.width = percentComplete + '%';
                progressText.innerText = 'Uploading... ' + Math.round(percentComplete) + '%';
            }
        };

        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    let res = JSON.parse(xhr.responseText);
                    if (res.success) {
                        window.location.reload();
                    } else {
                        progressText.innerText = res.errors.join(", ") || 'Upload failed validation.';
                        progressText.style.color = 'red';
                        progressBar.style.background = 'red';
                    }
                } catch(e) {
                    window.location.reload(); // Fallback if no json
                }
            } else {
                progressText.innerText = 'Upload failed with status ' + xhr.status;
                progressText.style.color = 'red';
                progressBar.style.background = 'red';
            }
        };

        xhr.onerror = function() {
            progressText.innerText = 'Upload entirely failed network error.';
        };

        xhr.send(formData);
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const sortableGrid = document.getElementById('sortable-image-grid');
    if (!sortableGrid) return;

    let draggedItem = null;

    const items = sortableGrid.querySelectorAll('.admin-img-item');
    items.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            draggedItem = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
            setTimeout(() => this.style.opacity = '0.4', 0);
        });

        item.addEventListener('dragover', function(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const rect = this.getBoundingClientRect();
            const midpoint = rect.x + (rect.width / 2);
            if (e.clientX < midpoint) {
                 this.style.borderLeft = '3px solid #3b82f6';
                 this.style.borderRight = '';
            } else {
                 this.style.borderRight = '3px solid #3b82f6';
                 this.style.borderLeft = '';
            }
            return false;
        });

        item.addEventListener('dragleave', function() {
            this.style.borderLeft = '';
            this.style.borderRight = '';
        });

        item.addEventListener('drop', function(e) {
            e.stopPropagation();
            this.style.borderLeft = '';
            this.style.borderRight = '';
            
            if (draggedItem !== this) {
                const rect = this.getBoundingClientRect();
                const midpoint = rect.x + (rect.width / 2);
                if (e.clientX < midpoint) {
                    sortableGrid.insertBefore(draggedItem, this);
                } else {
                    sortableGrid.insertBefore(draggedItem, this.nextSibling);
                }
                saveSortOrder();
            }
            return false;
        });

        item.addEventListener('dragend', function() {
            this.style.opacity = '1';
            items.forEach(i => {
                i.style.borderLeft = '';
                i.style.borderRight = '';
            });
        });
    });

    function saveSortOrder() {
        const sortedItems = sortableGrid.querySelectorAll('.admin-img-item');
        let orderData = [];
        sortedItems.forEach(el => orderData.push(el.getAttribute('data-image-id')));

        const formData = new FormData();
        formData.append('csrf', document.querySelector('input[name="csrf"]').value);
        formData.append('product_id', document.querySelector('input[name="product_id"]').value);
        formData.append('action', 'update_sort');
        formData.append('sort_order_data', JSON.stringify(orderData));

        fetch('<?= BASE_URL ?>/admin/product-image-upload.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(response => {
            if(response.ok) {
                // Flash success (or rely on page logic)
                console.log('Order updated');
            }
        });
    }
});
</script>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>






