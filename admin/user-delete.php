<?php
/**
 * London Labels - Admin Delete User
 */
require_once __DIR__ . '/../functions.php';

require_admin();

$user_id = (int)($_GET['id'] ?? 0);

// Prevent deleting yourself or invalid IDs
if ($user_id <= 0 || $user_id === (int) current_user_id()) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

$user = get_user_by_id($user_id);
if (!$user) {
    header('Location: ' . BASE_URL . '/admin/users.php');
    exit;
}

// Security: Prevent deleting a Super Admin or unauthorized deletion
if ($user['role'] === 'super_admin' && !is_super_admin()) {
    header('Location: ' . BASE_URL . '/admin/users.php?error=unauthorized');
    exit;
}

// Require POST + CSRF for actual deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        header('Location: ' . BASE_URL . '/admin/users.php?error=csrf');
        exit;
    }
    delete_user($user_id);
    header('Location: ' . BASE_URL . '/admin/users.php?notice=User+deleted');
    exit;
}

// GET: show confirmation page
$page_title = 'Delete User';
include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-delete-compact">

<div class="admin-form-shell">
    <div class="admin-card">
        <div class="admin-card-body">
            <div class="admin-alert admin-alert-danger" role="alert">
                <p>You are about to remove <strong><?= e($user['username']) ?></strong> (<?= e($user['email']) ?>).</p>
                <p>All personal data will be permanently wiped. Their order history is retained for financial records but fully anonymised. This cannot be undone.</p>
            </div>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <div class="admin-actions-row-tight admin-actions-row-tight-top">
                    <button type="submit" class="btn danger">Yes, delete account</button>
                    <a href="<?= BASE_URL ?>/admin/users.php" class="btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
