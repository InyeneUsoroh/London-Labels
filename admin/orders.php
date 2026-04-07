<?php
/**
 * London Labels — Admin Orders
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Orders';
require_admin();

$page   = max(1, (int)($_GET['page'] ?? 1));
$status = (string)($_GET['status'] ?? '');
$user_id = (int)($_GET['user_id'] ?? 0);
$limit  = 20;
$offset = ($page - 1) * $limit;

$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

$pdo = get_pdo();
$filtered_user = $user_id > 0 ? get_user_by_id($user_id) : null;
if ($user_id > 0 && !$filtered_user) {
    $user_id = 0;
}

// Status summary counts for the header bar
$counts = [];
foreach ($allowedStatuses as $s) {
    $c = $pdo->prepare('SELECT COUNT(*) FROM Orders WHERE status = ?');
    $c->execute([$s]);
    $counts[$s] = (int)$c->fetchColumn();
}
$counts['all'] = array_sum($counts);

// Filtered query
$where_parts = [];
$params = [];
if ($status !== '') {
    $where_parts[] = 'o.status = ?';
    $params[] = $status;
}
if ($user_id > 0) {
    $where_parts[] = 'o.user_id = ?';
    $params[] = $user_id;
}
$where = !empty($where_parts) ? ' WHERE ' . implode(' AND ', $where_parts) : '';

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM Orders o' . $where);
$countStmt->execute($params);
$total       = (int)$countStmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $limit));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $limit;

$sql = '
    SELECT o.order_id, o.user_id, u.username, u.email,
           o.order_date, o.total_amount, o.status,
           o.payment_method, o.payment_status,
           o.shipping_address, o.city, o.phone
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    ' . $where . '
    ORDER BY o.order_date DESC
    LIMIT ? OFFSET ?
';
$stmt = $pdo->prepare($sql);
// Bind WHERE parameters first (strings)
for ($i = 0; $i < count($params); $i++) {
    $stmt->bindValue($i + 1, $params[$i], PDO::PARAM_STR);
}
// Bind LIMIT and OFFSET as integers
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle">
            <?php if ($user_id > 0 && $filtered_user): ?>
                Showing orders for <strong><?= e(trim(($filtered_user['first_name'] ?? '') . ' ' . ($filtered_user['last_name'] ?? '')) ?: $filtered_user['username']) ?></strong> &middot;
            <?php endif; ?>
            <?= number_format($counts['all']) ?> total &middot;
            <?= $counts['pending'] ?> pending &middot;
            <?= $counts['processing'] ?> processing &middot;
            <?= $counts['shipped'] ?> shipped
        </p>
    </div>
</div>

<div class="admin-orders-compact">

<?php if (!empty($_GET['notice'])): ?>
    <div class="admin-alert admin-alert-success" role="status"><p><?= e($_GET['notice']) ?></p></div>
<?php endif; ?>

<!-- Status summary strip -->
<div class="admin-order-summary-strip">
    <a href="<?= BASE_URL ?>/admin/orders.php<?= $user_id > 0 ? '?user_id=' . $user_id : '' ?>" class="admin-order-summary-item <?= $status === '' ? 'active' : '' ?>">
        <span class="admin-order-summary-count"><?= number_format($counts['all']) ?></span>
        <span class="admin-order-summary-label">All</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/orders.php?status=pending<?= $user_id > 0 ? '&user_id=' . $user_id : '' ?>" class="admin-order-summary-item <?= $status === 'pending' ? 'active' : '' ?> tone-amber">
        <span class="admin-order-summary-count"><?= $counts['pending'] ?></span>
        <span class="admin-order-summary-label">Pending</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/orders.php?status=processing<?= $user_id > 0 ? '&user_id=' . $user_id : '' ?>" class="admin-order-summary-item <?= $status === 'processing' ? 'active' : '' ?> tone-blue">
        <span class="admin-order-summary-count"><?= $counts['processing'] ?></span>
        <span class="admin-order-summary-label">Processing</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/orders.php?status=shipped<?= $user_id > 0 ? '&user_id=' . $user_id : '' ?>" class="admin-order-summary-item <?= $status === 'shipped' ? 'active' : '' ?> tone-purple">
        <span class="admin-order-summary-count"><?= $counts['shipped'] ?></span>
        <span class="admin-order-summary-label">Shipped</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/orders.php?status=delivered<?= $user_id > 0 ? '&user_id=' . $user_id : '' ?>" class="admin-order-summary-item <?= $status === 'delivered' ? 'active' : '' ?> tone-green">
        <span class="admin-order-summary-count"><?= $counts['delivered'] ?></span>
        <span class="admin-order-summary-label">Delivered</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/orders.php?status=cancelled<?= $user_id > 0 ? '&user_id=' . $user_id : '' ?>" class="admin-order-summary-item <?= $status === 'cancelled' ? 'active' : '' ?> tone-red">
        <span class="admin-order-summary-count"><?= $counts['cancelled'] ?></span>
        <span class="admin-order-summary-label">Cancelled</span>
    </a>
</div>

<?php if ($user_id > 0): ?>
    <div class="admin-page-actions admin-page-actions-gap-sm">
        <a href="<?= BASE_URL ?>/admin/orders.php<?= $status !== '' ? '?status=' . urlencode($status) : '' ?>" class="btn admin-mini-btn">Clear User Filter</a>
    </div>
<?php endif; ?>

<?php if (empty($orders)): ?>
    <?php render_empty_state('No Orders Found', 'No orders match the selected filter.', 'Show All Orders', BASE_URL . '/admin/orders.php'); ?>
<?php else: ?>

    <!-- Desktop table -->
    <div class="admin-table-wrap admin-orders-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Delivery</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o):
                    $s_class = match($o['status']) {
                        'delivered','completed' => 'completed',
                        'shipped'    => 'shipped',
                        'processing' => 'processing',
                        'cancelled'  => 'cancelled',
                        default      => 'pending',
                    };
                    $p_class = $o['payment_status'] === 'paid'
                        ? 'completed'
                        : ($o['payment_status'] === 'failed' ? 'cancelled' : 'pending');
                    $pm_label = $o['payment_method'] === 'paystack'
                        ? 'Paystack'
                        : ucfirst(str_replace('_', ' ', $o['payment_method'] ?? ''));
                    $delivery = $o['city'] ? e($o['city']) : '<span class="admin-text-secondary">—</span>';
                ?>
                <tr>
                    <td><strong>#<?= $o['order_id'] ?></strong></td>
                    <td>
                        <div class="admin-text-500"><?= e($o['username']) ?></div>
                        <div class="admin-subtext"><?= e($o['email']) ?></div>
                    </td>
                    <td>
                        <?= $delivery ?>
                        <?php if ($o['phone']): ?>
                            <div class="admin-subtext"><?= e($o['phone']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= date('M d, Y', strtotime($o['order_date'])) ?></div>
                        <div class="admin-subtext"><?= date('H:i', strtotime($o['order_date'])) ?></div>
                    </td>
                    <td><strong><?= format_price($o['total_amount']) ?></strong></td>
                    <td>
                        <div><?= $pm_label ?></div>
                        <span class="admin-status-pill <?= $p_class ?> admin-inline-pill"><?= ucfirst($o['payment_status']) ?></span>
                    </td>
                    <td><span class="admin-status-pill <?= $s_class ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td><a href="<?= BASE_URL ?>/admin/order-edit.php?id=<?= $o['order_id'] ?>" class="btn admin-mini-btn">View</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-mobile-list admin-orders-mobile-list">
        <?php foreach ($orders as $o):
            $s_class = match($o['status']) {
                'delivered','completed' => 'completed',
                'shipped'    => 'shipped',
                'processing' => 'processing',
                'cancelled'  => 'cancelled',
                default      => 'pending',
            };
            $p_class = $o['payment_status'] === 'paid'
                ? 'completed'
                : ($o['payment_status'] === 'failed' ? 'cancelled' : 'pending');
            $pm_label = $o['payment_method'] === 'paystack'
                ? 'Paystack'
                : ucfirst(str_replace('_', ' ', $o['payment_method'] ?? ''));
        ?>
        <div class="admin-mobile-card">
            <div class="admin-mobile-card-top">
                <div>
                    <div class="admin-mobile-card-title">#<?= $o['order_id'] ?> &mdash; <?= e($o['username']) ?></div>
                    <div class="admin-mobile-card-sub"><?= e($o['email']) ?></div>
                    <?php if ($o['city']): ?>
                        <div class="admin-mobile-card-sub"><?= e($o['city']) ?><?= $o['phone'] ? ' &middot; ' . e($o['phone']) : '' ?></div>
                    <?php endif; ?>
                </div>
                <span class="admin-status-pill <?= $s_class ?>"><?= ucfirst($o['status']) ?></span>
            </div>
            <div class="admin-mobile-card-meta">
                <span><?= format_price($o['total_amount']) ?></span>
                <span><?= date('M d, Y', strtotime($o['order_date'])) ?></span>
                <span><?= $pm_label ?> &middot; <span class="admin-status-pill <?= $p_class ?> admin-inline-pill-xs"><?= ucfirst($o['payment_status']) ?></span></span>
            </div>
            <div class="admin-mobile-card-actions">
                <a href="<?= BASE_URL ?>/admin/order-edit.php?id=<?= $o['order_id'] ?>" class="btn admin-mini-btn admin-mobile-card-action-full">View Order</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1):
        $pp = [];
        if ($status !== '') $pp['status'] = $status;
        if ($user_id > 0) $pp['user_id'] = $user_id;
        render_pagination($page, $total_pages, BASE_URL . '/admin/orders.php', $pp, $total, $limit);
    endif; ?>
<?php endif; ?>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
