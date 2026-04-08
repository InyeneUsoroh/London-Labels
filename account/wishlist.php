<?php
/**
 * London Labels - Wishlist
 */
require_once __DIR__ . '/../functions.php';

$page_title  = 'Wishlist';
$account_page = 'wishlist';

$is_member = is_logged_in();
$user_id = $is_member ? (int)current_user_id() : null;

// Handle remove via POST
if ($is_member && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_product_id'])) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid request.');
    }
    $pid = (int)$_POST['remove_product_id'];
    if ($pid > 0) {
        toggle_wishlist($user_id, $pid); // will remove if present
    }
    header('Location: ' . BASE_URL . '/account/wishlist.php');
    exit;
}

if ($is_member) {
    $items = get_user_wishlist($user_id);
    $wishlist_ids = get_user_wishlist_product_ids($user_id);
} else {
    $wishlist_ids = get_guest_wishlist_product_ids();
    $items = get_guest_wishlist($wishlist_ids);
}

include __DIR__ . '/../inc_header.php';
if ($is_member) {
    include __DIR__ . '/inc_account_layout.php';
} else {
    echo '<div class="account-shell guest-wishlist-shell"><div class="account-content guest-wishlist-content">';
}
?>

<div class="account-page-head">
    <h2 class="account-page-title">Wishlist</h2>
    <?php if (!empty($items)): ?>
        <span class="account-page-count"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></span>
    <?php endif; ?>
</div>

<?php if (empty($items)): ?>
    <?php render_empty_state(
        'Nothing in your wishlist yet',
        'Browse the shop and add items you love to your wishlist — they\'ll appear here.',
        'Browse Shop',
        BASE_URL . '/shop.php'
    ); ?>
<?php else: ?>
    <div class="product-grid wishlist-grid">
        <?php 
            $show_remove_btn = true;
            foreach ($items as $product): 
        ?>
            <?php include __DIR__ . '/../includes/product-card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    </div><!-- /.account-content -->
</div><!-- /.account-shell -->

<?php include __DIR__ . '/../inc_footer.php'; ?>
