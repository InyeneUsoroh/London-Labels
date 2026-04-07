<?php
/**
 * London Labels — Admin Delete Order
 * Only permitted on cancelled orders.
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Delete Order';
require_admin();

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

$order = get_order_by_id($order_id);
if (!$order) {
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// Only cancelled orders may be deleted
if ($order['status'] !== 'cancelled') {
    header('Location: ' . BASE_URL . '/admin/order-edit.php?id=' . $order_id . '&error=not_cancelled');
    exit;
}

$items    = get_order_items($order_id);
$customer = get_user_by_id((int)$order['user_id']);

// POST — confirmed delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        header('Location: ' . BASE_URL . '/admin/order-delete.php?id=' . $order_id);
        exit;
    }
    delete_order($order_id);
    header('Location: ' . BASE_URL . '/admin/orders.php?notice=Order+%23' . $order_id . '+deleted');
    exit;
}

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-delete-compact">

<div class="admin-confirm-shell">
    <div class="admin-alert admin-alert-danger" role="alert">
        <p>You are about to permanently delete this order. This cannot be undone.</p>
    </div>

    <div class="admin-card admin-card-gap">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Order Summary</h2>
        </div>
        <div class="admin-card-body admin-card-body-flush">
            <table class="admin-detail-table">
                <tr>
                    <td class="label">Order ID</td>
                    <td class="strong">#<?= $order_id ?></td>
                </tr>
                <tr>
                    <td class="label">Customer</td>
                    <td>
                        <?= $customer ? e($customer['username']) . ' — ' . e($customer['email']) : 'Unknown' ?>
                    </td>
                </tr>
                <tr>
                    <td class="label">Date</td>
                    <td><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></td>
                </tr>
                <tr>
                    <td class="label">Total</td>
                    <td class="strong"><?= format_price($order['total_amount']) ?></td>
                </tr>
                <tr>
                    <td class="label">Items</td>
                    <td>
                        <?php if (empty($items)): ?>
                            <span class="admin-text-secondary">No items recorded</span>
                        <?php else: ?>
                            <?= implode(', ', array_map(fn($i) => e($i['name']) . ' ×' . $i['quantity'], $items)) ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <div class="admin-actions-row-tight">
            <button type="submit" class="btn danger">Yes, delete permanently</button>
            <a href="<?= BASE_URL ?>/admin/order-edit.php?id=<?= $order_id ?>" class="btn">Cancel</a>
        </div>
    </form>
</div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
