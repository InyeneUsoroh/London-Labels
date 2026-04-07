<?php
/**
 * London Labels — Admin Edit User
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Edit User';
$errors  = [];
$notice  = '';

require_admin();

$user_id = (int)($_GET['id'] ?? 0);
if ($user_id <= 0) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$user = get_user_by_id($user_id);
if (!$user) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// Handle POST — update account details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token invalid. Please try again.';
    } else {
        $new_username = trim($_POST['username'] ?? '');
        $new_email    = trim($_POST['email']    ?? '');
        $new_role     = $_POST['role'] ?? 'customer';
        $new_first    = trim($_POST['first_name'] ?? '');
        $new_last     = trim($_POST['last_name']  ?? '');
        $new_phone    = trim($_POST['phone']      ?? '');

        if ($new_username === '')                              $errors[] = 'Username is required.';
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL))   $errors[] = 'A valid email address is required.';
        if (!in_array($new_role, ['admin', 'customer'], true)) $errors[] = 'Invalid role.';

        // Check username/email uniqueness (exclude current user)
        if (empty($errors)) {
            $pdo = get_pdo();

            $dup = $pdo->prepare('SELECT user_id FROM Users WHERE username = ? AND user_id != ?');
            $dup->execute([$new_username, $user_id]);
            if ($dup->fetchColumn()) $errors[] = 'That username is already taken.';

            $dup = $pdo->prepare('SELECT user_id FROM Users WHERE email = ? AND user_id != ?');
            $dup->execute([$new_email, $user_id]);
            if ($dup->fetchColumn()) $errors[] = 'That email is already in use.';
        }

        if (empty($errors)) {
            $pdo = get_pdo();
            $pdo->prepare('
                UPDATE Users SET username = ?, email = ?, role = ?,
                    first_name = ?, last_name = ?, phone = ?
                WHERE user_id = ?
            ')->execute([
                $new_username, $new_email, $new_role,
                $new_first ?: null, $new_last ?: null, $new_phone ?: null,
                $user_id,
            ]);

            $notice = 'Account updated successfully.';
            $user   = get_user_by_id($user_id); // refresh
        }
    }
}

// Load their recent orders
$user_orders = get_user_orders($user_id, 10, 0);
$user_order_count_stmt = get_pdo()->prepare('SELECT COUNT(*) FROM Orders WHERE user_id = ?');
$user_order_count_stmt->execute([$user_id]);
$user_order_total = (int)$user_order_count_stmt->fetchColumn();

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-user-edit-compact">

<div class="admin-page-header">
    <div>
        <h1 class="admin-page-title">
            <?= e(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username']) ?>
        </h1>
        <p class="admin-page-subtitle">
            Customer since <?= date('M Y', strtotime($user['created_at'])) ?>
            <?php if ($user['last_login_at']): ?>
                &middot; Last seen <?= date('M d, Y', strtotime($user['last_login_at'])) ?>
            <?php endif; ?>
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="admin-alert admin-alert-danger" role="alert">
        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($notice): ?>
    <div class="admin-alert admin-alert-success" role="status">
        <p><?= e($notice) ?></p>
    </div>
<?php endif; ?>

<div class="admin-user-edit-grid">

    <!-- Left: edit form -->
    <div>
        <div class="admin-card admin-card-gap-bottom-20">

</div>
            <div class="admin-card-head">
                <h2 class="admin-card-title">Account Details</h2>
            </div>
            <div class="admin-card-body">
                <form method="post" novalidate autocomplete="off">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div class="admin-two-col">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name"
                                value="<?= e($_POST['first_name'] ?? $user['first_name'] ?? '') ?>"
                                maxlength="50" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name"
                                value="<?= e($_POST['last_name'] ?? $user['last_name'] ?? '') ?>"
                                maxlength="50" autocomplete="off">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username <span class="admin-required">*</span></label>
                        <input type="text" id="username" name="username"
                            value="<?= e($_POST['username'] ?? $user['username']) ?>"
                            maxlength="50" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="admin-required">*</span></label>
                        <input type="email" id="email" name="email"
                            value="<?= e($_POST['email'] ?? $user['email']) ?>"
                            maxlength="100" required autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone"
                            value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>"
                            maxlength="20" autocomplete="off">
                    </div>

                    <div class="form-group">
                        <label for="role">Role <span class="admin-required">*</span></label>
                        <select id="role" name="role" <?= $user_id === (int)current_user_id() ? 'disabled' : '' ?>>
                            <option value="customer" <?= ($user['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="admin"    <?= ($user['role'] ?? '') === 'admin'    ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <?php if ($user_id === (int)current_user_id()): ?>
                            <input type="hidden" name="role" value="<?= e($user['role']) ?>">
                            <small class="admin-text-small-secondary">You cannot change your own role.</small>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn primary admin-btn-full">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Meta info -->
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Account Info</h2>
            </div>
            <div class="admin-card-body admin-card-body-no-padding">
                <table class="admin-user-meta-table">
                    <tr>
                        <td class="admin-user-meta-label admin-user-meta-label-first">User ID</td>
                        <td class="admin-user-meta-value admin-user-meta-value-strong">#<?= $user['user_id'] ?></td>
                    </tr>
                    <tr>
                        <td class="admin-user-meta-label">Registered</td>
                        <td class="admin-user-meta-value"><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <td class="admin-user-meta-label">Last Login</td>
                        <td class="admin-user-meta-value">
                            <?= $user['last_login_at'] ? date('M d, Y H:i', strtotime($user['last_login_at'])) : 'Never' ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="admin-user-meta-label">Total Orders</td>
                        <td class="admin-user-meta-value admin-user-meta-value-total"><?= $user_order_total ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: order history -->
    <div>
        <div class="admin-card">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Order History</h2>
                <a href="<?= BASE_URL ?>/admin/orders.php?user_id=<?= $user_id ?>" class="admin-card-link">All orders</a>
            </div>
            <?php if (empty($user_orders)): ?>
                <div class="admin-card-body">
                    <p class="admin-muted-note">No orders placed yet.</p>
                </div>
            <?php else: ?>
                <div class="admin-overflow-x">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_orders as $o): ?>
                                <?php
                                    $s = match($o['status']) {
                                        'delivered','completed' => 'completed',
                                        'shipped'    => 'shipped',
                                        'processing' => 'processing',
                                        'cancelled'  => 'cancelled',
                                        default      => 'pending',
                                    };
                                ?>
                                <tr>
                                    <td><strong>#<?= $o['order_id'] ?></strong></td>
                                    <td><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                                    <td><?= format_price($o['total_amount']) ?></td>
                                    <td><span class="admin-status-pill <?= $s ?>"><?= ucfirst($o['status']) ?></span></td>
                                    <td><a href="<?= BASE_URL ?>/admin/order-edit.php?id=<?= $o['order_id'] ?>" class="btn admin-mini-btn">View</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Danger zone -->
        <div class="admin-card admin-card-danger">
            <div class="admin-card-head">
                <h2 class="admin-card-title">Danger Zone</h2>
            </div>
            <div class="admin-card-body">
                <p class="admin-muted-note">Permanently removes all personal data from this account. Order history is retained but anonymised. This cannot be undone.</p>
                <?php if ($user_id !== (int)current_user_id()): ?>
                    <a href="<?= BASE_URL ?>/admin/user-delete.php?id=<?= $user_id ?>" class="btn danger">Remove Account</a>
                <?php else: ?>
                    <p class="admin-text-small-secondary">You cannot delete your own account from here.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
