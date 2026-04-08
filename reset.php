<?php
/**
 * London Labels - Reset Password
 * Industry-standard password reset with token validation
 */
require_once __DIR__ . '/functions.php';

$page_title = 'Reset Password';
$errors = [];
$notice = '';
$token = $_GET['token'] ?? '';
$reset = null;
$password_updated = false;

// Validate token
if (empty($token)) {
    $errors[] = 'Missing reset token. Please request a new password reset link.';
} else {
    try {
        $pdo = get_pdo();
        $reset = load_valid_reset($pdo, $token);
        
        if (!$reset) {
            $errors[] = 'This reset link is invalid or has expired.';
        }
    } catch (Exception $e) {
        error_log('Reset token validation error: ' . $e->getMessage());
        $errors[] = 'Could not verify reset link. Please try again.';
    }
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    // CSRF validation
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token validation failed. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        validate_password($password, $errors);

        if ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }
        
        // Update password if no errors
        if (empty($errors)) {
            try {
                $pdo = get_pdo();
                
                // Update password
                update_user_password($pdo, (int)$reset['user_id'], $password);
                
                // Mark reset token as used
                mark_reset_used($pdo, (int)$reset['id']);
                
                // Revoke all trusted devices for security
                revoke_all_trusted_devices_for_user((int)$reset['user_id']);
                
                // Clear current trusted device cookie if exists
                if (function_exists('revoke_current_trusted_device')) {
                    revoke_current_trusted_device();
                }
                
                // Optional: Log password reset event
                // log_password_reset((int)$reset['user_id'], $_SERVER['REMOTE_ADDR']);
                
                $password_updated = true;
                
            } catch (Exception $e) {
                error_log('Password reset error: ' . $e->getMessage());
                $errors[] = 'Failed to update password. Please try again.';
            }
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card">

        <?php if ($password_updated): ?>

            <div class="auth-card-header">
                <h2>Password updated</h2>
                <p>You can now sign in with your new password.</p>
            </div>

            <div class="auth-notice auth-notice-success" role="status">
                <p>All trusted devices have been signed out for your security.</p>
            </div>

            <div class="form-group">
                <a href="<?= BASE_URL ?>/login.php" class="btn primary btn-full">Sign In Now</a>
            </div>

        <?php elseif ($reset): ?>

            <div class="auth-card-header">
                <h2>Create new password</h2>
                <p>Resetting for <?= e($reset['email']) ?></p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="auth-notice auth-notice-error" role="alert">
                    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form" autocomplete="on" novalidate>
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="password">New Password <span class="form-required">*</span></label>
                    <input type="password" id="password" name="password" required
                           autocomplete="new-password" placeholder="Create a strong password"
                           minlength="8" aria-describedby="pw-reqs">
                    <div class="pw-strength" id="pw-strength-label" aria-live="polite"></div>
                    <ul class="pw-requirements" id="pw-reqs" aria-label="Password requirements">
                        <li class="pw-req" id="req-length">At least 8 characters</li>
                        <li class="pw-req" id="req-upper">One uppercase letter</li>
                        <li class="pw-req" id="req-lower">One lowercase letter</li>
                        <li class="pw-req" id="req-number">One number</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password <span class="form-required">*</span></label>
                    <input type="password" id="password_confirm" name="password_confirm" required
                           autocomplete="new-password" placeholder="Re-enter your password">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn primary btn-full">Reset Password</button>
                </div>
            </form>

        <?php else: ?>

            <div class="auth-card-header">
                <h2>Link expired</h2>
                <p>This reset link is no longer valid.</p>
            </div>

            <div class="auth-notice auth-notice-error" role="alert">
                <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
            </div>

            <div class="form-group">
                <a href="<?= BASE_URL ?>/forgot.php" class="btn primary btn-full">Request New Link</a>
            </div>

        <?php endif; ?>

        <div class="auth-card-footer">
            <p>Remember your password? <a href="<?= BASE_URL ?>/login.php">Sign In</a></p>
        </div>

    </div>
</div>

<script>
(function () {
    var pw        = document.getElementById('password');
    var label     = document.getElementById('pw-strength-label');
    var reqLength = document.getElementById('req-length');
    var reqUpper  = document.getElementById('req-upper');
    var reqLower  = document.getElementById('req-lower');
    var reqNumber = document.getElementById('req-number');

    if (!pw || !label) return;

    pw.addEventListener('input', function () {
        var v = pw.value;
        var checks = {
            length: v.length >= 8,
            upper:  /[A-Z]/.test(v),
            lower:  /[a-z]/.test(v),
            number: /[0-9]/.test(v),
        };
        reqLength && reqLength.classList.toggle('pw-met', checks.length);
        reqUpper  && reqUpper.classList.toggle('pw-met',  checks.upper);
        reqLower  && reqLower.classList.toggle('pw-met',  checks.lower);
        reqNumber && reqNumber.classList.toggle('pw-met', checks.number);

        var score = Object.values(checks).filter(Boolean).length;
        if (v.length === 0) { label.textContent = ''; label.className = 'pw-strength'; return; }
        var levels  = ['', 'Weak', 'Fair', 'Fair', 'Good', 'Strong'];
        var classes = ['', 'pw-weak', 'pw-fair', 'pw-fair', 'pw-good', 'pw-strong'];
        label.textContent = levels[score] || '';
        label.className   = 'pw-strength ' + (classes[score] || '');
    });
})();
</script>

<?php include __DIR__ . '/inc_footer.php'; ?>
