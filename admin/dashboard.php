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

// Churned (self-deleted) accounts
$churn_stmt = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'deleted'");
$deleted_accounts = (int) $churn_stmt->fetchColumn();

// Revenue: sum of orders where payment was confirmed
$rev_stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM Orders WHERE payment_status = 'paid'");
$total_revenue = (float) $rev_stmt->fetchColumn();

// Pending orders count
$pend_stmt = $pdo->query("SELECT COUNT(*) FROM Orders WHERE status = 'pending'");
$pending_orders = (int) $pend_stmt->fetchColumn();

// Homepage curation status
$featured_count = count_featured_products();
$new_arrival_count = count_new_arrival_products();
$image_min_recommended = defined('PRODUCT_IMAGES_MIN_RECOMMENDED') ? (int)PRODUCT_IMAGES_MIN_RECOMMENDED : 6;

// Products below recommended image coverage
$img_kpi_stmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM (
         SELECT p.product_id, COUNT(pi.image_id) AS image_count
         FROM Products p
         LEFT JOIN Product_Images pi ON pi.product_id = p.product_id
         GROUP BY p.product_id
         HAVING COUNT(pi.image_id) < ?
     ) t'
);
$img_kpi_stmt->execute([$image_min_recommended]);
$products_below_image_min = (int)$img_kpi_stmt->fetchColumn();

$img_gap_stmt = $pdo->prepare(
    'SELECT p.product_id, p.name, COUNT(pi.image_id) AS image_count
     FROM Products p
     LEFT JOIN Product_Images pi ON pi.product_id = p.product_id
     GROUP BY p.product_id, p.name
     HAVING COUNT(pi.image_id) < ?
     ORDER BY image_count ASC, p.name ASC
     LIMIT 10'
);
$img_gap_stmt->execute([$image_min_recommended]);
$image_gap_products = $img_gap_stmt->fetchAll();

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
        <p class="admin-stat-value"><?= format_price($total_revenue) ?></p>
        <p class="admin-stat-sub">Confirmed payments</p>
    </div>
    <div class="admin-stat-card tone-blue">
        <p class="admin-stat-label">Total Orders</p>
        <p class="admin-stat-value"><?= number_format($total_orders) ?></p>
        <?php if ($pending_orders > 0): ?>
            <p class="admin-stat-sub"><?= $pending_orders ?> pending</p>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card tone-green <?= ($count_low_stock = count($low_stock)) > 0 || $products_below_image_min > 0 ? '' : 'admin-stat-card-compact' ?>">
        <p class="admin-stat-label">Products</p>
        <p class="admin-stat-value"><?= number_format($total_products) ?></p>
        <?php if ($count_low_stock > 0): ?>
            <p class="admin-stat-sub admin-stat-sub-danger"><?= $count_low_stock ?> low stock</p>
        <?php endif; ?>
        <?php if ($products_below_image_min > 0): ?>
            <p class="admin-stat-sub"><?= number_format($products_below_image_min) ?> below <?= $image_min_recommended ?> images</p>
        <?php endif; ?>
    </div>
    <div class="admin-stat-card tone-amber">
        <p class="admin-stat-label">Customers</p>
        <p class="admin-stat-value"><?= number_format($total_users) ?></p>
        <?php if ($deleted_accounts > 0): ?>
            <p class="admin-stat-sub"><?= $deleted_accounts ?> churned</p>
        <?php endif; ?>
    </div>
</div>

<!-- Homepage Curation Status -->
<div class="admin-card admin-card-spaced-top admin-home-curation-card">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Homepage Curation Status</h2>
        <span class="admin-text-small-secondary">Auto + Manual Rules</span>
    </div>
    <div class="admin-card-body admin-home-curation-card-body-tight">
        <div class="admin-home-curation-grid">
            <div>
                <p class="admin-home-curation-kicker">Featured Products</p>
                <p class="admin-home-curation-count"><?= $featured_count ?><span class="admin-home-curation-count-max">/4</span></p>
                <p class="admin-home-curation-caption">Manual curation only</p>
                <a href="<?= BASE_URL ?>/admin/homepage-curation.php?section=featured&scope=current" class="admin-home-curation-link">Manage →</a>
            </div>
            <div>
                <p class="admin-home-curation-kicker">New Arrivals</p>
                <p class="admin-home-curation-count"><?= $new_arrival_count ?><span class="admin-home-curation-count-max">/4</span></p>
                <p class="admin-home-curation-caption">Manual + auto (30 days)</p>
                <a href="<?= BASE_URL ?>/admin/homepage-curation.php?section=new_arrivals&scope=current" class="admin-home-curation-link">Manage →</a>
            </div>
        </div>
        <p class="admin-home-curation-note admin-home-curation-note-tight">
            <strong>Featured:</strong> Manually curated products only — no auto-fill when empty.
            <br><strong>New Arrivals:</strong> Combines manual picks with products added within 30 days for freshness.
        </p>
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
            <div class="admin-table-wrap admin-table-wrap-flat">
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
                                <td><strong>#<?= $o['order_id'] ?></strong></td>
                                <td>
                                    <div><?= e($o['username']) ?></div>
                                    <div class="admin-subtext"><?= e($o['email']) ?></div>
                                </td>
                                <td><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                                <td><strong><?= format_price($o['total_amount']) ?></strong></td>
                                <td><span class="admin-status-pill <?= $p_class ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                                <td><span class="admin-status-pill <?= $s_class ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><a href="<?= BASE_URL ?>/admin/order-edit.php?id=<?= $o['order_id'] ?>" class="btn admin-mini-btn">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($low_stock)): ?>
        <!-- Low Stock -->
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Low Stock</h2>
                <a href="<?= BASE_URL ?>/admin/products.php" class="admin-card-link">Manage</a>
            </div>
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
        </div>
    <?php endif; ?>

    <?php if (!empty($image_gap_products)): ?>
        <!-- Media Coverage -->
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Media Coverage</h2>
                <a href="<?= BASE_URL ?>/admin/products.php" class="admin-card-link">Manage</a>
            </div>
            <div class="admin-card-body">
                <p class="admin-muted-note">Target: <?= $image_min_recommended ?>-<?= (defined('PRODUCT_IMAGES_MAX_RECOMMENDED') ? (int)PRODUCT_IMAGES_MAX_RECOMMENDED : 8) ?> images per product.</p>
            </div>
            <ul class="admin-low-stock-list">
                <?php foreach ($image_gap_products as $p): ?>
                    <li class="admin-low-stock-item">
                        <a href="<?= BASE_URL ?>/admin/product-edit.php?id=<?= (int)$p['product_id'] ?>" class="admin-low-stock-name admin-link-clean">
                            <?= e($p['name']) ?>
                        </a>
                        <span class="admin-low-stock-qty warning">
                            <?= (int)$p['image_count'] ?> / <?= $image_min_recommended ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="admin-card">
        <div class="admin-card-head">
            <h2 class="admin-card-title">Quick Actions</h2>
        </div>
        <div class="admin-card-body admin-quick-links">
            <a href="<?= BASE_URL ?>/admin/product-add.php" class="btn primary">Add Product</a>
            <a href="<?= BASE_URL ?>/admin/orders.php?status=pending" class="btn">View Pending Orders</a>
            <a href="<?= BASE_URL ?>/admin/categories.php" class="btn">Manage Categories</a>
            <a href="<?= BASE_URL ?>/admin/users.php" class="btn">View Customers</a>
            <?php
                $dash_unread = count_contact_messages('unread');
            ?>
            <a href="<?= BASE_URL ?>/admin/messages.php?status=unread" class="btn" style="position:relative;">
                Messages<?php if ($dash_unread > 0): ?> <span style="display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;background:#e8357e;color:#fff;font-size:10px;font-weight:700;border-radius:999px;margin-left:4px;"><?= $dash_unread ?></span><?php endif; ?>
            </a>
        </div>
    </div>

</div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
