<?php
/**
 * London Labels - Change Password
 */
require_once __DIR__ . '/../functions.php';

$page_title   = 'Change Password';
$account_page = 'profile';
$errors       = [];

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        validate_required($current_password, 'Current password', $errors);
        validate_password($new_password, $errors, 'New password');

        if (empty($errors) && $current_password === $new_password) {
            $errors[] = 'Your new password must be different from your current password.';
        }
        if (empty($errors) && $new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match.';
        }

        if (empty($errors)) {
            $user = get_user_auth_by_id((int)current_user_id());
            if (!$user || !password_verify($current_password, (string)($user['password_hash'] ?? ''))) {
                $errors[] = 'Current password is incorrect.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            if (update_user_password((int)current_user_id(), $hash)) {
                revoke_all_trusted_devices_for_user((int)current_user_id());
                header('Location: ' . BASE_URL . '/account/profile.php?saved=password');
                exit;
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

include __DIR__ . '/../inc_header.php';
include __DIR__ . '/inc_account_layout.php';
?>

<div class="account-page-head">
    <h2 class="account-page-title">Change Password</h2>
</div>

<?php if (!empty($errors)): ?>
    <div class="account-alert account-alert-error" role="alert">
        <?php foreach ($errors as $err): ?>
            <p><?= e($err) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="account-card">
    <h3>Update your password</h3>
    <p class="account-card-subtitle">Choose a strong password. You'll be signed out of all other devices once it's changed.</p>

    <form method="post" class="account-edit-form" novalidate autocomplete="on">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

        <div class="account-edit-field">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password"
                required
                autocomplete="current-password"
                placeholder="Enter your current password">
        </div>

        <div class="account-edit-field">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password"
                required
                autocomplete="new-password"
                minlength="8"
                maxlength="255"
                placeholder="Create a strong password">
            <div id="password-strength" class="pw-strength" aria-live="polite"></div>
            <ul class="pw-requirements" id="pw-requirements" aria-label="Password requirements">
                <li id="req-length">At least 8 characters</li>
                <li id="req-upper">One uppercase letter</li>
                <li id="req-lower">One lowercase letter</li>
                <li id="req-number">One number</li>
            </ul>
        </div>

        <div class="account-edit-field">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password"
                required
                autocomplete="new-password"
                placeholder="Re-enter your new password">
        </div>

        <div class="account-edit-actions">
            <button type="submit" class="btn primary">Change Password</button>
            <a href="<?= BASE_URL ?>/account/profile.php" class="btn">Cancel</a>
        </div>
    </form>
</div>

    </div><!-- /.account-content -->
</div><!-- /.account-shell -->

<script>
(function () {
    const input    = document.getElementById('new_password');
    const bar      = document.getElementById('password-strength');
    const reqLen   = document.getElementById('req-length');
    const reqUpper = document.getElementById('req-upper');
    const reqLower = document.getElementById('req-lower');
    const reqNum   = document.getElementById('req-number');

    if (!input || !bar) return;

    input.addEventListener('input', function () {
        const v = this.value;
        const checks = {
            length: v.length >= 8,
            upper:  /[A-Z]/.test(v),
            lower:  /[a-z]/.test(v),
            number: /[0-9]/.test(v),
        };

        reqLen.classList.toggle('pw-met',   checks.length);
        reqUpper.classList.toggle('pw-met', checks.upper);
        reqLower.classList.toggle('pw-met', checks.lower);
        reqNum.classList.toggle('pw-met',   checks.number);

        const score = Object.values(checks).filter(Boolean).length;

        if (v.length === 0) {
            bar.textContent = '';
            bar.className = 'pw-strength';
            return;
        }

        const levels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        const classes = ['', 'pw-weak', 'pw-fair', 'pw-good', 'pw-strong'];
        bar.textContent = levels[score];
        bar.className = 'pw-strength ' + classes[score];
    });
}());
</script>

<?php include __DIR__ . '/../inc_footer.php'; ?>
