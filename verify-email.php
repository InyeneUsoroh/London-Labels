<?php
/**
 * London Labels - Email Verification Page
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email_verification.php';

$page_title = 'Verify Email';
$token = $_GET['token'] ?? '';
$result = null;

if (!empty($token)) {
    $result = verify_email_token($token);
}

include __DIR__ . '/inc_header.php';
?>

<div class="auth-shell">
    <div class="auth-header">
        <h2><?= $result && $result['success'] ? 'Email Verified!' : 'Email Verification' ?></h2>
    </div>
    
    <?php if ($result): ?>
        <?php if ($result['success']): ?>
            <?php render_alert('success', $result['message']); ?>
            <div class="verification-success">
                <p>Your email has been successfully verified. You can now:</p>
                <ul>
                    <li>Complete purchases and checkout</li>
                    <li>Receive order updates</li>
                    <li>Access all account features</li>
                </ul>
            </div>
            
            <div class="form-group">
                <a href="<?= BASE_URL ?>/index.php" class="btn primary btn-full">Start Shopping</a>
            </div>
            
            <?php if (is_logged_in()): ?>
                <div class="form-group">
                    <a href="<?= BASE_URL ?>/account/profile.php" class="btn btn-full">Go to My Account</a>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-full">Sign In</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php render_alert('danger', $result['message']); ?>
            
            <?php if (is_logged_in()): ?>
                <div class="verification-help">
                    <p>Need a new verification link?</p>
                    <a href="<?= BASE_URL ?>/resend-verification.php" class="btn primary btn-full">Resend Verification Email</a>
                </div>
            <?php else: ?>
                <div class="verification-help">
                    <p>Please sign in to request a new verification link.</p>
                    <a href="<?= BASE_URL ?>/login.php" class="btn primary btn-full">Sign In</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php else: ?>
        <?php render_alert('danger', 'Invalid or missing verification token.'); ?>
        <div class="verification-help">
            <p>If you need a new verification link, please sign in to your account.</p>
            <a href="<?= BASE_URL ?>/login.php" class="btn primary btn-full">Sign In</a>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
