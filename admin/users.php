<?php
/**
 * London Labels — Admin Users
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Users';
require_admin();

$page   = max(1, (int)($_GET['page'] ?? 1));
$role   = (string)($_GET['role'] ?? '');
$search = trim((string)($_GET['q'] ?? ''));
$limit  = 20;

$allowedRoles = ['admin', 'customer'];
if (!in_array($role, $allowedRoles, true)) {
    $role = '';
}

$pdo = get_pdo();

// Summary counts
$total_customers = (int)$pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'customer'")->fetchColumn();
$total_admins    = (int)$pdo->query("SELECT COUNT(*) FROM Users WHERE role = 'admin'")->fetchColumn();
$total_all       = $total_customers + $total_admins;

// Build WHERE
$where  = ["u.role != 'deleted'"];
$params = [];

if ($role !== '') {
    $where[]  = 'u.role = ?';
    $params[] = $role;
}

if ($search !== '') {
    $where[]  = '(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)';
    $like     = '%' . $search . '%';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM Users u $whereSql");
$countStmt->execute($params);
$total       = (int)$countStmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $limit));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $limit;

// Data — include order count and last login
$dataSql = "
    SELECT u.user_id, u.username, u.email, u.first_name, u.last_name,
           u.role, u.created_at, u.last_login_at,
           COUNT(o.order_id) AS order_count
    FROM Users u
    LEFT JOIN Orders o ON o.user_id = u.user_id
    $whereSql
    GROUP BY u.user_id
    ORDER BY u.user_id DESC
    LIMIT ? OFFSET ?
";
$dataStmt = $pdo->prepare($dataSql);
$i = 1;
foreach ($params as $p) {
    $dataStmt->bindValue($i++, $p, PDO::PARAM_STR);
}
$dataStmt->bindValue($i++, $limit,  PDO::PARAM_INT);
$dataStmt->bindValue($i,   $offset, PDO::PARAM_INT);
$dataStmt->execute();
$users = $dataStmt->fetchAll();

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle">
            <?= number_format($total_all) ?> total &middot;
            <?= number_format($total_customers) ?> customers &middot;
            <?= number_format($total_admins) ?> admins
        </p>
    </div>
</div>

<div class="admin-users-compact">

<!-- Summary strip -->
<div class="admin-order-summary-strip admin-order-summary-strip-spaced">
    <a href="<?= BASE_URL ?>/admin/users.php<?= $search ? '?q=' . urlencode($search) : '' ?>"
       class="admin-order-summary-item <?= $role === '' ? 'active' : '' ?>">
        <span class="admin-order-summary-count"><?= number_format($total_all) ?></span>
        <span class="admin-order-summary-label">All</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/users.php?role=customer<?= $search ? '&q=' . urlencode($search) : '' ?>"
       class="admin-order-summary-item <?= $role === 'customer' ? 'active' : '' ?> tone-blue">
        <span class="admin-order-summary-count"><?= number_format($total_customers) ?></span>
        <span class="admin-order-summary-label">Customers</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/users.php?role=admin<?= $search ? '&q=' . urlencode($search) : '' ?>"
       class="admin-order-summary-item <?= $role === 'admin' ? 'active' : '' ?> tone-magenta">
        <span class="admin-order-summary-count"><?= number_format($total_admins) ?></span>
        <span class="admin-order-summary-label">Admins</span>
    </a>
</div>

<!-- Search -->
<form method="get" class="admin-search-form admin-users-search" role="search">
    <?php if ($role): ?><input type="hidden" name="role" value="<?= e($role) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($search) ?>"
           placeholder="Search by name, username or email…"
           class="admin-search-input" autocomplete="off">
    <button type="submit" class="btn">Search</button>
    <?php if ($search): ?>
        <a href="<?= BASE_URL ?>/admin/users.php<?= $role ? '?role=' . urlencode($role) : '' ?>" class="btn">Clear</a>
    <?php endif; ?>
</form>

<?php if (!empty($_GET['notice'])): ?>
    <div class="admin-alert admin-alert-success admin-spaced-16" role="status">
        <p><?= e($_GET['notice']) ?></p>
    </div>
<?php endif; ?>

<?php if (empty($users)): ?>
    <?php render_empty_state(
        $search ? 'No results for "' . e($search) . '"' : 'No Users Found',
        $search ? 'Try a different name or email.' : 'No accounts match the selected filter.',
        'Show All Users',
        BASE_URL . '/admin/users.php'
    ); ?>
<?php else: ?>
    <!-- Desktop table -->
    <div class="admin-table-wrap admin-users-table-wrap admin-table-wrap-spaced">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Orders</th>
                    <th>Last Login</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u):
                    $full = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                ?>
                <tr>
                    <td>
                        <div class="admin-text-500">
                            <?= $full ? e($full) : e($u['username']) ?>
                        </div>
                        <?php if ($full): ?>
                            <div class="admin-subtext"><?= e($u['username']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <span class="admin-status-pill <?= $u['role'] === 'admin' ? 'completed' : 'processing' ?>">
                            <?= ucfirst($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['order_count'] > 0): ?>
                            <a href="<?= BASE_URL ?>/admin/orders.php?user_id=<?= (int)$u['user_id'] ?>" class="admin-link-emphasis">
                                <?= (int)$u['order_count'] ?>
                            </a>
                        <?php else: ?>
                            <span class="admin-text-secondary">0</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['last_login_at']): ?>
                            <div><?= date('M d, Y', strtotime($u['last_login_at'])) ?></div>
                            <div class="admin-subtext"><?= date('H:i', strtotime($u['last_login_at'])) ?></div>
                        <?php else: ?>
                            <span class="admin-text-secondary">Never</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/admin/user-edit.php?id=<?= $u['user_id'] ?>"
                           class="btn admin-mini-btn">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Mobile card list -->
    <div class="admin-mobile-list admin-mobile-list-spaced">
        <?php foreach ($users as $u):
            $full = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            $display = $full ?: $u['username'];
            $initials = strtoupper(substr($display, 0, 1));
            if (strpos($display, ' ') !== false) {
                $parts = explode(' ', $display);
                $initials = strtoupper(substr($parts[0], 0, 1) . substr(end($parts), 0, 1));
            }
        ?>
        <div class="admin-mobile-card">
            <div class="admin-mobile-card-top">
                <div class="admin-flex-center-gap-12">
                    <div class="admin-user-avatar" aria-hidden="true"><?= e($initials) ?></div>
                    <div>
                        <div class="admin-mobile-card-title"><?= $full ? e($full) : e($u['username']) ?></div>
                        <?php if ($full): ?><div class="admin-mobile-card-sub"><?= e($u['username']) ?></div><?php endif; ?>
                        <div class="admin-mobile-card-sub"><?= e($u['email']) ?></div>
                    </div>
                </div>
                <span class="admin-status-pill <?= $u['role'] === 'admin' ? 'completed' : 'processing' ?>">
                    <?= ucfirst($u['role']) ?>
                </span>
            </div>
            <div class="admin-mobile-card-meta">
                <span><?= (int)$u['order_count'] ?> order<?= $u['order_count'] != 1 ? 's' : '' ?></span>
                <span>Joined <?= date('M d, Y', strtotime($u['created_at'])) ?></span>
                <?php if ($u['last_login_at']): ?>
                    <span>Last login <?= date('M d', strtotime($u['last_login_at'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="admin-mobile-card-actions">
                <a href="<?= BASE_URL ?>/admin/user-edit.php?id=<?= $u['user_id'] ?>" class="btn admin-mini-btn">View Profile</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1):
        $pp = [];
        if ($role)   $pp['role'] = $role;
        if ($search) $pp['q']    = $search;
        render_pagination($page, $total_pages, BASE_URL . '/admin/users.php', $pp, $total, $limit);
    endif; ?>
<?php endif; ?>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
