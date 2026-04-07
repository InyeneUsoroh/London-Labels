<?php
/**
 * Reusable Product Card Component — Gallery Edition
 *
 * Industry-standard minimalist card: full click-through to PDP.
 * Shows only: image (dominant), product name, price.
 * No add-to-cart, no wishlist button — all action lives on the PDP.
 *
 * Requires:
 *   - $product (array): product_id, name, price, quantity, category_name
 */

if (!isset($product) || !is_array($product)) {
    throw new Exception('product-card component requires $product array');
}

$in_stock    = (int)($product['quantity'] ?? 0) > 0;
$product_url = BASE_URL . '/product.php?id=' . $product['product_id'];
$show_remove_btn = $show_remove_btn ?? false;
?>

<a href="<?= $product_url ?>" class="product-card<?= !$in_stock ? ' product-card-oos' : '' ?>" aria-label="<?= e($product['name']) ?> — <?= format_price($product['price']) ?>">
    <div class="product-card-image-wrap">
        <?php 
            $raw_img = (string)($product['image_url'] ?? '');
            $safe_img = sanitize_local_upload_media_url($raw_img); 
            $display_img = $safe_img ?? (BASE_URL . '/assets/images/placeholder.png');
        ?>
        <img
            src="<?= e($display_img) ?>"
            alt="<?= e($product['name']) ?>"
            loading="lazy"
            decoding="async"
            width="600"
            height="750"
            class="product-card-img"
        >
        <?php if ($show_remove_btn): ?>
            <?php 
                $is_member = is_logged_in();
                $user_id = $is_member ? (int)current_user_id() : null;
                $wishlist_ids = $is_member ? get_user_wishlist_product_ids($user_id) : get_guest_wishlist_product_ids();
                $is_saved = in_array($product['product_id'], $wishlist_ids, true);
            ?>
            <button 
                type="button" 
                class="btn wishlist-toggle-btn product-card-remove-btn <?= $is_saved ? 'saved' : '' ?>"
                data-product-id="<?= (int)$product['product_id'] ?>"
                data-csrf="<?= csrf_token() ?>"
                data-guest="<?= !$is_member ? 'true' : 'false' ?>"
                aria-label="Remove from wishlist"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        <?php endif; ?>
        <?php if (!$in_stock): ?>
            <span class="product-oos-overlay">Out of stock</span>
        <?php endif; ?>
    </div>
    <div class="product-card-body">
        <p class="product-name"><?= e($product['name']) ?></p>
        <p class="product-price"><?= format_price($product['price']) ?></p>
    </div>
</a>
