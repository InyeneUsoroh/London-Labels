<?php
/**
 * London Labels - My Account
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'My Account';
$account_page = 'profile';

require_login();

$user       = get_user_by_id((int)current_user_id());
$full_name  = $user ? trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? '')) : '';

// Fetch most recent order for the dashboard summary
$recent_orders = get_user_orders(current_user_id(), 1, 0);
$recent_order  = $recent_orders[0] ?? null;

include __DIR__ . '/../inc_header.php';
include __DIR__ . '/inc_account_layout.php';
?>

<div class="account-page-head">
    <h2 class="account-page-title">
        <?= $full_name !== '' ? 'Welcome back, ' . e(explode(' ', $full_name)[0]) : 'My Account' ?>
    </h2>
    <a href="<?= BASE_URL ?>/account/edit-profile.php" class="btn account-page-edit-btn">Edit Profile</a>
</div>

<?php if (!$user): ?>
    <?php render_empty_state('Profile Not Available', 'We could not load your profile details right now.', 'Return to Home', BASE_URL . '/index.php'); ?>
<?php else: ?>

    <?php
        $saved = $_GET['saved'] ?? '';
        if ($saved === '1'): ?>
        <div class="account-alert account-alert-success" role="status">Your profile has been updated.</div>
    <?php elseif ($saved === 'email'): ?>
        <div class="account-alert account-alert-success" role="status">Your profile has been updated. Your email was changed — you have been signed out of all other devices.</div>
    <?php elseif ($saved === 'password'): ?>
        <div class="account-alert account-alert-success" role="status">Your password has been changed. You have been signed out of all other devices.</div>
    <?php endif; ?>

    <?php if ($recent_order): ?>
    <!-- Recent order summary -->
    <div class="account-card account-card-highlight">
        <div class="account-card-header">
            <h3>Most Recent Order</h3>
            <a href="<?= BASE_URL ?>/account/orders.php" class="account-card-link">View all orders</a>
        </div>
        <div class="account-order-summary-row">
            <div class="account-order-summary-col">
                <span class="account-order-summary-label">Order</span>
                <strong>#<?= $recent_order['order_id'] ?></strong>
            </div>
            <div class="account-order-summary-col">
                <span class="account-order-summary-label">Date</span>
                <strong><?= date('M d, Y', strtotime($recent_order['order_date'])) ?></strong>
            </div>
            <div class="account-order-summary-col">
                <span class="account-order-summary-label">Total</span>
                <strong><?= format_price($recent_order['total_amount']) ?></strong>
            </div>
            <div class="account-order-summary-col">
                <span class="account-order-summary-label">Status</span>
                <?php
                    $s = $recent_order['status'];
                    $sc = $s === 'delivered' ? 'delivered' : ($s === 'cancelled' ? 'cancelled' : 'pending');
                ?>
                <span class="account-order-status <?= $sc ?>"><?= ucfirst($s) ?></span>
            </div>
            <div class="account-order-summary-col">
                <a href="<?= BASE_URL ?>/order-confirmation.php?order_id=<?= $recent_order['order_id'] ?>" class="btn account-order-view-btn">View Order</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Personal Information -->
    <div class="account-card">
        <h3>Personal Information</h3>
        <div class="account-info-list">
            <div class="account-info-row">
                <span>Name</span>
                <strong><?= $full_name !== '' ? e($full_name) : '<span class="account-info-unset">Not set</span>' ?></strong>
            </div>
            <div class="account-info-row">
                <span>Email</span>
                <strong><?= e($user['email']) ?></strong>
            </div>
            <div class="account-info-row">
                <span>Phone</span>
                <strong><?= !empty($user['phone']) ? e($user['phone']) : '<span class="account-info-unset">Not set</span>' ?></strong>
            </div>
            <div class="account-info-row account-info-row-last">
                <span>Member Since</span>
                <strong><?= date('M Y', strtotime($user['created_at'])) ?></strong>
            </div>
        </div>
    </div>

    <!-- Delivery Address -->
    <div class="account-card">
        <h3>Delivery Address</h3>
        <?php if (!empty($user['default_shipping_address']) || !empty($user['default_address_line2']) || !empty($user['default_city'])): ?>
            <div class="account-address-inline">
                <?= e($user['default_shipping_address'] ?? '') ?>
                <?php if (!empty($user['default_address_line2'])): ?>, <?= e($user['default_address_line2']) ?><?php endif; ?>
                <?php if (!empty($user['default_city'])): ?>, <?= e($user['default_city']) ?><?php endif; ?>
                <?php if (!empty($user['default_state'])): ?>, <?= e($user['default_state']) ?><?php endif; ?>
                <?php if (!empty($user['default_country'])): ?>, <?= e($user['default_country']) ?><?php endif; ?>
            </div>
        <?php else: ?>
            <p class="account-info-unset">No delivery address saved. Edit your profile to add one and speed up checkout.</p>
        <?php endif; ?>
    </div>

    <!-- Security -->
    <div class="account-card">
        <div class="account-card-header">
            <h3>Login &amp; Security</h3>
        </div>
        <div class="account-info-list">
            <div class="account-info-row account-info-row-last">
                <span>Password</span>
                <strong>••••••••</strong>
            </div>
        </div>
        <div class="account-actions">
            <a href="<?= BASE_URL ?>/account/change-password.php" class="btn primary account-action-btn">Change Password</a>
        </div>
    </div>

<?php endif; ?>

    </div><!-- /.account-content -->
</div><!-- /.account-shell -->

<?php include __DIR__ . '/../inc_footer.php'; ?>
