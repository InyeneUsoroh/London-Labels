<?php
/**
 * London Labels — Admin Order Detail
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../mailer.php';

$page_title = 'Order Details';
$errors  = [];
$notice  = '';
$email_sent = false;

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

$customer = get_user_by_id((int)$order['user_id']);
$items    = get_order_items($order_id);

$status_value         = $order['status'];
$payment_status_value = $order['payment_status'];

// Handle POST — status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $new_status  = $_POST['status']         ?? '';
        $new_payment = $_POST['payment_status'] ?? '';

        $valid_statuses  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $valid_payments  = ['pending', 'paid', 'failed'];

        if (!in_array($new_status,  $valid_statuses,  true)) $errors[] = 'Invalid order status.';
        if (!in_array($new_payment, $valid_payments, true))  $errors[] = 'Invalid payment status.';

        if (empty($errors)) {
            $prev_status  = $order['status'];
            $prev_payment = $order['payment_status'];

            update_order_status($order_id, $new_status);
            update_order_payment_status($order_id, $new_payment);

            $order                = get_order_by_id($order_id);
            $status_value         = $order['status'];
            $payment_status_value = $order['payment_status'];
            $notice               = 'Order updated.';

            // Email customer if anything changed
            if ($customer && ($new_status !== $prev_status || $new_payment !== $prev_payment)) {
                $mailError = null;
                send_order_status_update_email(
                    $customer['email'],
                    $customer['username'],
                    $order_id,
                    $new_status,
                    $new_payment,
                    $mailError
                );
                $email_sent = ($mailError === null);
            }
        }
    }
}

// Derived display values
$pm_label = match($order['payment_method'] ?? '') {
    'paystack' => 'Paystack',
    default    => ucfirst(str_replace('_', ' ', $order['payment_method'] ?? 'N/A')),
};

$s_class = match($status_value) {
    'delivered','completed' => 'completed',
    'shipped'    => 'shipped',
    'processing' => 'processing',
    'cancelled'  => 'cancelled',
    default      => 'pending',
};

$p_class = $payment_status_value === 'paid'
    ? 'completed'
    : ($payment_status_value === 'failed' ? 'cancelled' : 'pending');

// Order total from items (more accurate than stored total)
$items_total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-order-edit-compact">

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">Order #<?= $order_id ?></h1>
        <p class="admin-page-subtitle">
            Placed <?= date('M d, Y \a\t H:i', strtotime($order['order_date'])) ?>
            &middot;
            <span class="admin-status-pill <?= $s_class ?> admin-status-pill-middle"><?= ucfirst($status_value) ?></span>
            <span class="admin-status-pill <?= $p_class ?> admin-status-pill-middle"><?= ucfirst($payment_status_value) ?></span>
        </p>
    </div>
    <div class="admin-page-actions">
        <?php if ($order['status'] === 'cancelled'): ?>
            <a href="<?= BASE_URL ?>/admin/order-delete.php?id=<?= $order_id ?>" class="btn danger">Delete Order</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-danger" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($notice): ?>
    <div class="admin-alert admin-alert-success" role="status">
        <p><?= e($notice) ?><?= $email_sent ? ' Customer notified by email.' : '' ?></p>
    </div>
<?php endif; ?>

<div class="admin-order-detail-grid">

    <!-- Left column: info + items -->
    <div class="admin-order-detail-main">

        <!-- Items -->
        <div class="admin-card admin-card-gap">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Items Ordered</h2>
                <span class="admin-text-600"><?= format_price($order['total_amount']) ?></span>
            </div>
            <?php if (empty($items)): ?>
                <div class="admin-card-body"><p class="admin-muted-note">No item records for this order.</p></div>
            <?php else: ?>
                <div class="admin-overflow-x">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="admin-th-center">Qty</th>
                                <th class="admin-th-right">Unit Price</th>
                                <th class="admin-th-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $item['product_id'] ?>"
                                           class="admin-link-emphasis">
                                            <?= e($item['name']) ?>
                                        </a>
                                    </td>
                                    <td class="admin-td-center"><?= (int)$item['quantity'] ?></td>
                                    <td class="admin-td-right"><?= format_price($item['price']) ?></td>
                                    <td class="admin-td-right admin-text-600"><?= format_price($item['price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="admin-row-muted">
                                <td colspan="3" class="admin-total-label">Order Total</td>
                                <td class="admin-total-value"><?= format_price($order['total_amount']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Delivery details -->
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Delivery Details</h2>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($order['shipping_address'])): ?>
                    <div class="admin-delivery-block">
                        <div class="admin-delivery-row">
                            <span>Address</span>
                            <strong><?= e($order['shipping_address']) ?></strong>
                        </div>
                        <?php if ($order['city']): ?>
                        <div class="admin-delivery-row">
                            <span>City</span>
                            <strong><?= e($order['city']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($order['postal_code']): ?>
                        <div class="admin-delivery-row">
                            <span>Postal Code</span>
                            <strong><?= e($order['postal_code']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($order['phone']): ?>
                        <div class="admin-delivery-row">
                            <span>Phone</span>
                            <strong>
                                <a href="tel:<?= e($order['phone']) ?>" class="admin-link-clean"><?= e($order['phone']) ?></a>
                            </strong>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="admin-muted-note">No delivery address recorded for this order.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right column: status update + customer -->
    <div class="admin-order-detail-side">

        <!-- Status update form -->
        <div class="admin-card admin-card-gap">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Update Status</h2>
            </div>
            <div class="admin-card-body">
                <form method="post" novalidate autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div class="form-group">
                        <label for="status">Order Status</label>
                        <select id="status" name="status">
                            <?php foreach (['pending','processing','shipped','delivered','cancelled'] as $s): ?>
                                <option value="<?= $s ?>" <?= $status_value === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="payment_status">Payment Status</label>
                        <select id="payment_status" name="payment_status">
                            <?php foreach (['pending','paid','failed'] as $ps): ?>
                                <option value="<?= $ps ?>" <?= $payment_status_value === $ps ? 'selected' : '' ?>><?= ucfirst($ps) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <p class="admin-text-small-secondary admin-help-note">
                        Saving will email the customer if the status changes.
                    </p>

                    <button type="submit" class="btn primary admin-btn-full">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Payment info -->
        <div class="admin-card admin-card-gap">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Payment</h2>
            </div>
            <div class="admin-card-body admin-card-body-flush">
                <table class="admin-detail-table">
                    <tr>
                        <td class="label label-wide">Method</td>
                        <td class="admin-text-500"><?= e($pm_label) ?></td>
                    </tr>
                    <tr>
                        <td class="label label-wide">Status</td>
                        <td>
                            <span class="admin-status-pill <?= $p_class ?>"><?= ucfirst($payment_status_value) ?></span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Customer -->
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Customer</h2>
                <?php if ($customer): ?>
                    <a href="<?= BASE_URL ?>/admin/user-edit.php?id=<?= $customer['user_id'] ?>" class="admin-card-link">View profile</a>
                <?php endif; ?>
            </div>
            <div class="admin-card-body">
                <?php if ($customer): ?>
                    <p class="admin-text-600 admin-margin-bottom-4">
                        <?= e(trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')) ?: $customer['username']) ?>
                    </p>
                    <p class="admin-muted-note admin-margin-bottom-8"><?= e($customer['email']) ?></p>
                    <?php if (!empty($customer['phone'])): ?>
                        <p class="admin-muted-note admin-margin-none">
                            <a href="tel:<?= e($customer['phone']) ?>" class="admin-link-clean"><?= e($customer['phone']) ?></a>
                        </p>
                    <?php endif; ?>
                    <p class="admin-text-small-secondary admin-help-note-tight">
                        Customer since <?= date('M Y', strtotime($customer['created_at'])) ?>
                    </p>
                <?php else: ?>
                    <p class="admin-muted-note">Customer account no longer exists.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
