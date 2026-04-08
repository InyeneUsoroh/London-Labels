<?php
/**
 * London Labels - My Orders
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'My Orders';
$account_page = 'orders';

require_login();

$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 10;
$offset   = ($page - 1) * $limit;

$orders      = get_user_orders(current_user_id(), $limit, $offset);
$total       = count_user_orders((int)current_user_id());
$total_pages = max(1, (int)ceil($total / $limit));

// order_progress_steps() is defined in functions.php

include __DIR__ . '/../inc_header.php';
include __DIR__ . '/inc_account_layout.php';
?>

<div class="account-page-head">
    <h2 class="account-page-title">My Orders</h2>
</div>

<?php if (empty($orders)): ?>
    <?php render_empty_state('No Orders Yet', 'You have not placed an order yet.', 'Shop Products', BASE_URL . '/shop.php'); ?>
<?php else: ?>
    <div class="account-orders-list">
        <?php foreach ($orders as $order): ?>
            <?php
                $items      = get_order_items($order['order_id']);
                $item_count = count($items);
                $status     = $order['status'];
                $cancelled  = $status === 'cancelled';
            ?>
            <div class="account-order-card">
                <div class="account-order-card-head">
                    <div class="account-order-meta">
                        <span class="account-order-id">Order #<?= $order['order_id'] ?></span>
                        <span class="account-order-date"><?= date('M d, Y', strtotime($order['order_date'])) ?></span>
                        <span class="account-order-items"><?= $item_count ?> item<?= $item_count !== 1 ? 's' : '' ?></span>
                    </div>
                    <div class="account-order-card-right">
                        <span class="account-order-total"><?= format_price($order['total_amount']) ?></span>
                        <?php
                            $sc = $cancelled ? 'cancelled' : ($status === 'delivered' ? 'delivered' : 'pending');
                        ?>
                        <span class="account-order-status <?= $sc ?>"><?= ucfirst($status) ?></span>
                        <a href="<?= BASE_URL ?>/order-confirmation.php?order_id=<?= $order['order_id'] ?>" class="btn account-order-view-btn">View</a>
                    </div>
                </div>

                <?php if ($item_count > 0): ?>
                    <div class="account-order-items-preview">
                        <?php foreach (array_slice($items, 0, 3) as $item): ?>
                            <span class="account-order-item-name"><?= e($item['name']) ?></span>
                        <?php endforeach; ?>
                        <?php if ($item_count > 3): ?>
                            <span class="account-order-item-more">+<?= $item_count - 3 ?> more</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$cancelled): ?>
                    <div class="account-order-progress">
                        <?php foreach (order_progress_steps($status) as $s): ?>
                            <div class="account-order-step <?= $s['done'] ? 'done' : '' ?> <?= $s['active'] ? 'active' : '' ?>">
                                <div class="account-order-step-dot"></div>
                                <span><?= e($s['name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="account-order-cancelled-note">This order was cancelled.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <?php render_pagination($page, $total_pages, BASE_URL . '/account/orders.php', [], $total, $limit); ?>
    <?php endif; ?>
<?php endif; ?>

    </div><!-- /.account-content -->
</div><!-- /.account-shell -->

<?php include __DIR__ . '/../inc_footer.php'; ?>
