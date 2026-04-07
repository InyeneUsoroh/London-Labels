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
        
        // Password validation (same as registration)
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (strlen($password) > 255) {
            $errors[] = 'Password is too long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number.';
        }
        
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

<div class="auth-shell">
    <?php if ($password_updated): ?>
        <div class="auth-header">
            <h2>Password Reset Successful!</h2>
            <p class="auth-subtitle">Your password has been updated</p>
        </div>
        
        <div class="password-reset-success">
            <?php render_alert('success', 'Your password has been successfully reset. You can now sign in with your new password.'); ?>
            
            <div class="password-reset-info">
                <h3>Security Notice</h3>
                <ul>
                    <li>All trusted devices have been removed</li>
                    <li>You'll need to sign in again on all devices</li>
                    <li>Your account is now secure with your new password</li>
                </ul>
            </div>
            
            <div class="form-group">
                <a href="<?= BASE_URL ?>/login.php" class="btn primary btn-full btn-lg">Sign In Now</a>
            </div>
        </div>
    <?php elseif ($reset): ?>
        <div class="auth-header">
            <h2>Create New Password</h2>
            <p class="auth-subtitle">Resetting password for <?= e($reset['email']) ?></p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <?php render_alert('danger', $errors); ?>
        <?php endif; ?>
        
        <form method="post" class="form auth-form" autocomplete="on" novalidate id="reset-form">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            
            <?php render_form_input([
                'label' => 'New Password',
                'name' => 'password',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Create a strong password',
                'autocomplete' => 'new-password',
                'minlength' => 8,
                'maxlength' => 255,
                'describedby' => 'password-requirements',
                'error' => ''
            ]); ?>
            
            <div class="form-group">
                <div id="password-strength" class="password-strength" aria-live="polite"></div>
                <ul id="password-requirements" class="password-requirements">
                    <li id="req-length">At least 8 characters</li>
                    <li id="req-uppercase">One uppercase letter</li>
                    <li id="req-lowercase">One lowercase letter</li>
                    <li id="req-number">One number</li>
                </ul>
            </div>
            
            <?php render_form_input([
                'label' => 'Confirm New Password',
                'name' => 'password_confirm',
                'type' => 'password',
                'required' => true,
                'placeholder' => 'Re-enter your password',
                'autocomplete' => 'new-password',
                'error' => ''
            ]); ?>
            
            <div class="form-group">
                <button type="submit" class="btn primary btn-full btn-lg">Reset Password</button>
            </div>
        </form>
    <?php else: ?>
        <div class="auth-header">
            <h2>Invalid Reset Link</h2>
            <p class="auth-subtitle">This link is no longer valid</p>
        </div>
        
        <?php render_alert('danger', $errors); ?>
        
        <div class="password-reset-help">
            <h3>What happened?</h3>
            <p>Password reset links expire after 30 minutes for security reasons. The link may have:</p>
            <ul>
                <li>Expired (older than 30 minutes)</li>
                <li>Already been used</li>
                <li>Been entered incorrectly</li>
            </ul>
            
            <div class="form-group">
                <a href="<?= BASE_URL ?>/forgot.php" class="btn primary btn-full">Request New Reset Link</a>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="auth-footer">
        <p>Remember your password? <a href="<?= BASE_URL ?>/login.php" class="auth-link">Sign In</a></p>
    </div>
</div>

<script>
// Password strength meter (same as registration)
(function() {
    const passwordInput = document.querySelector('input[name="password"]');
    const strengthDiv = document.getElementById('password-strength');
    const requirements = {
        length: document.getElementById('req-length'),
        uppercase: document.getElementById('req-uppercase'),
        lowercase: document.getElementById('req-lowercase'),
        number: document.getElementById('req-number')
    };
    
    if (!passwordInput || !strengthDiv) return;
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let strengthText = '';
        let strengthClass = '';
        
        // Check requirements
        const checks = {
            length: password.length >= 8,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number: /[0-9]/.test(password)
        };
        
        // Update requirement indicators
        Object.keys(checks).forEach(key => {
            if (requirements[key]) {
                if (checks[key]) {
                    requirements[key].classList.add('met');
                    strength++;
                } else {
                    requirements[key].classList.remove('met');
                }
            }
        });
        
        // Calculate strength
        if (password.length === 0) {
            strengthDiv.textContent = '';
            strengthDiv.className = 'password-strength';
            return;
        }
        
        if (strength === 4) {
            strengthText = 'Strong password';
            strengthClass = 'strong';
        } else if (strength === 3) {
            strengthText = 'Good password';
            strengthClass = 'good';
        } else if (strength >= 2) {
            strengthText = 'Fair password';
            strengthClass = 'fair';
        } else {
            strengthText = 'Weak password';
            strengthClass = 'weak';
        }
        
        strengthDiv.textContent = strengthText;
        strengthDiv.className = 'password-strength ' + strengthClass;
    });
})();
</script>

<?php include __DIR__ . '/inc_footer.php'; ?>
