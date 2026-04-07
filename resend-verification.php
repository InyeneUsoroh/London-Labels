<?php
/**
 * London Labels - Resend Verification Email
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email_verification.php';

require_login();

$page_title = 'Resend Verification Email';
$errors = [];
$notice = '';
$from_checkout = !empty($_SESSION['checkout_redirect']);
unset($_SESSION['checkout_redirect']);

// Check if already verified
if (is_email_verified()) {
    header('Location: ' . BASE_URL . '/account/profile.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token validation failed.';
    } else {
        try {
            $user_id = current_user_id();
            $email = current_user_email();
            $username = current_user_name();
            
            if (send_verification_email($user_id, $email, $username)) {
                $notice = 'Verification email sent! Please check your inbox.';
            } else {
                $errors[] = 'Failed to send verification email. Please try again.';
            }
        } catch (Exception $e) {
            error_log('Resend verification error: ' . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<div class="auth-shell">
    <div class="auth-header">
        <h2>Verify Your Email</h2>
        <p class="auth-subtitle">We'll send you a verification link</p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <?php render_alert('danger', $errors); ?>
    <?php endif; ?>
    
    <?php if (!empty($notice)): ?>
        <?php render_alert('success', $notice); ?>
    <?php endif; ?>
    
    <?php if ($from_checkout): ?>
        <?php render_alert('info', 'Please verify your email address to complete your purchase.'); ?>
    <?php endif; ?>
    
    <div class="verification-info">
        <p>Your email address <strong><?= e(current_user_email()) ?></strong> needs to be verified.</p>
        <?php if ($from_checkout): ?>
            <p>Email verification is required to complete checkout and place orders.</p>
        <?php endif; ?>
        <p>Click the button below to receive a new verification email.</p>
    </div>
    
    <form method="post" class="form auth-form">
        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        
        <div class="form-group">
            <button type="submit" class="btn primary btn-full btn-lg">Send Verification Email</button>
        </div>
    </form>
    
</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
