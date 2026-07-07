<?php
/**
 * London Labels — Admin Dashboard
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Dashboard';
require_admin();

$pdo = get_pdo();

// Stats
$total_users    = get_user_count();
$total_products = get_product_count();
$total_orders   = get_order_count();

// Revenue: sum of orders where payment was confirmed
$rev_stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM Orders WHERE payment_status = 'paid'");
$total_revenue = (float) $rev_stmt->fetchColumn();

// Pending orders count
$pend_stmt = $pdo->query("SELECT COUNT(*) FROM Orders WHERE status = 'pending'");
$pending_orders = (int) $pend_stmt->fetchColumn();

// Homepage curation status
$featured_count = count_featured_products();
$new_arrival_count = count_new_arrival_products();

// Recent orders (last 8)
$recent_stmt = $pdo->query("
    SELECT o.order_id, u.username, u.email, o.total_amount, o.status, o.payment_status, o.order_date
    FROM Orders o
    JOIN Users u ON o.user_id = u.user_id
    ORDER BY o.order_date DESC
    LIMIT 8
");
$recent_orders = $recent_stmt->fetchAll();

// Low stock products (quantity <= 5)
$low_stmt = $pdo->query("
    SELECT product_id, name, quantity
    FROM Products
    WHERE quantity <= 5
    ORDER BY quantity ASC
    LIMIT 10
");
$low_stock = $low_stmt->fetchAll();

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle">Store overview at a glance.</p>
    </div>
</div>

<div class="admin-dashboard-compact">
<!-- Stat cards -->
<div class="admin-stats-grid">
    <div class="admin-stat-card tone-magenta">
        <p class="admin-stat-label">Total Revenue</p>
        <p class="admin-stat-value"><?= $total_revenue > 0 ? format_price($total_revenue) : '—' ?></p>
        <p class="admin-stat-sub"><?= $total_revenue > 0 ? 'Confirmed payments' : 'No confirmed payments yet' ?></p>
    </div>
    <div class="admin-stat-card tone-blue">
        <p class="admin-stat-label">Total Orders</p>
        <p class="admin-stat-value"><?= number_format($total_orders) ?></p>
        <?php if ($pending_orders > 0): ?>
            <p class="admin-stat-sub"><?= $pending_orders ?> pending</p>
        <?php endif; ?>
    </div>
    <?php $count_low_stock = count($low_stock); ?>
    <div class="admin-stat-card tone-green <?= $count_low_stock > 0 ? '' : 'admin-stat-card-compact' ?>">
        <p class="admin-stat-label">Products</p>
        <p class="admin-stat-value"><?= number_format($total_products) ?></p>
        <?php if ($count_low_stock > 0): ?>
            <p class="admin-stat-sub admin-stat-sub-danger"><?= $count_low_stock ?> low stock</p>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card tone-amber">
        <p class="admin-stat-label">Customers</p>
        <p class="admin-stat-value"><?= number_format($total_users) ?></p>
    </div>
</div>

<div class="admin-dash-grid">

    <!-- Recent Orders -->
    <div class="admin-card admin-dash-grid-full">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Recent Orders</h2>
            <a href="<?= BASE_URL ?>/admin/orders.php" class="admin-card-link">View all</a>
        </div>
        <?php if (empty($recent_orders)): ?>
            <div class="admin-card-body admin-card-body-empty">
                <p class="admin-muted-note">No orders yet.</p>
            </div>
        <?php else: ?>
            <div class="admin-table-wrap admin-table-wrap-flat admin-table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $o): ?>
                            <?php
                                $s_class = match($o['status']) {
                                    'delivered', 'completed' => 'completed',
                                    'shipped'    => 'shipped',
                                    'processing' => 'processing',
                                    'cancelled'  => 'cancelled',
                                    default      => 'pending',
                                };
                                $p_class = $o['payment_status'] === 'paid' ? 'completed' : ($o['payment_status'] === 'failed' ? 'cancelled' : 'pending');
                            ?>
                            <tr>
                                <td data-label="Order"><strong>#<?= $o['order_id'] ?></strong></td>
                                <td data-label="Customer">
                                    <div><?= e($o['username']) ?></div>
                                    <div class="admin-subtext"><?= e($o['email']) ?></div>
                                </td>
                                <td data-label="Date"><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                                <td data-label="Total"><strong><?= format_price($o['total_amount']) ?></strong></td>
                                <td data-label="Payment"><span class="admin-status-pill <?= $p_class ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                                <td data-label="Status"><span class="admin-status-pill <?= $s_class ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td data-label="Action"><a href="<?= BASE_URL ?>/admin/order-edit.php?id=<?= $o['order_id'] ?>" class="btn admin-mini-btn">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Low Stock Warnings -->
    <div class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Low Stock Warnings</h2>
            <a href="<?= BASE_URL ?>/admin/products.php" class="admin-card-link">Manage</a>
        </div>
        <?php if (empty($low_stock)): ?>
            <div class="admin-card-body">
                <p class="admin-muted-note" style="margin: 0; color: #166534; font-weight: 500;">✓ All products are well stocked.</p>
            </div>
        <?php else: ?>
            <ul class="admin-low-stock-list">
                <?php foreach ($low_stock as $p): ?>
                    <li class="admin-low-stock-item">
                        <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= $p['product_id'] ?>" class="admin-low-stock-name admin-link-clean">
                            <?= e($p['name']) ?>
                        </a>
                        <span class="admin-low-stock-qty <?= $p['quantity'] == 0 ? 'critical' : 'warning' ?>">
                            <?= $p['quantity'] === 0 ? 'Out of stock' : $p['quantity'] . ' left' ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Homepage Curation -->
    <div class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Homepage Curation</h2>
            <a href="<?= BASE_URL ?>/admin/homepage-curation.php" class="admin-card-link">Manage</a>
        </div>
        <ul class="admin-low-stock-list">
            <li class="admin-low-stock-item">
                <span class="admin-low-stock-name">Featured Products</span>
                <span class="admin-low-stock-qty <?= $featured_count < 4 ? 'warning' : 'success' ?>">
                    <?= $featured_count ?> / 4
                </span>
            </li>
            <li class="admin-low-stock-item">
                <span class="admin-low-stock-name">New Arrivals</span>
                <span class="admin-low-stock-qty <?= $new_arrival_count < 4 ? 'warning' : 'success' ?>">
                    <?= $new_arrival_count ?> / 4
                </span>
            </li>
        </ul>
    </div>

</div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
