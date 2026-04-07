<?php
/**
 * London Labels - Delete Account
 */
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../mailer.php';

$page_title   = 'Delete Account';
$account_page = 'profile';
$errors       = [];

require_login();

$user = get_user_auth_by_id((int)current_user_id());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';

        if ($password === '') {
            $errors[] = 'Please enter your password to confirm.';
        } elseif (!password_verify($password, (string)($user['password_hash'] ?? ''))) {
            $errors[] = 'Incorrect password. Please try again.';
        } else {
            // Capture details before wiping
            $email     = (string)($user['email'] ?? '');
            $firstName = (string)($user['first_name'] ?? '');
            $userId    = (int)current_user_id();

            // Revoke all trusted devices
            revoke_all_trusted_devices_for_user($userId);

            // Anonymise the account
            anonymise_user($userId);

            // Send confirmation email (non-fatal if it fails)
            send_account_deleted_email($email, $firstName);

            // Destroy session
            logout_user();

            header('Location: ' . BASE_URL . '/index.php?account=deleted');
            exit;
        }
    }
}

include __DIR__ . '/../inc_header.php';
include __DIR__ . '/inc_account_layout.php';
?>

<div class="account-page-head">
    <h2 class="account-page-title">Delete Account</h2>
</div>

<?php if (!empty($errors)): ?>
    <div class="account-alert account-alert-error" role="alert">
        <?php foreach ($errors as $err): ?>
            <p><?= e($err) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="account-card account-danger-card">
    <h3>Permanently delete your account</h3>
    <p class="account-card-subtitle">This will remove all your personal information from our systems. Your order history will be retained for legal and financial record-keeping, but all personal identifiers will be removed. This cannot be undone.</p>

    <form method="post" class="account-edit-form" novalidate>
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="account-edit-field">
            <label for="password">Confirm your password</label>
            <input type="password" id="password" name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your current password">
        </div>

        <div class="account-edit-actions">
            <button type="submit" class="btn danger">Delete My Account</button>
            <a href="<?= BASE_URL ?>/account/profile.php" class="btn secondary">Cancel</a>
        </div>
    </form>
</div>

    </div><!-- /.account-content -->
</div><!-- /.account-shell -->

<?php include __DIR__ . '/../inc_footer.php'; ?>
