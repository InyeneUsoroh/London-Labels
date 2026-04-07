<?php
/**
 * London Labels - Forgot Password
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Reset Password';
$errors     = [];
$email_sent = false;
$dev_reset_link = null;

if (!isset($_SESSION['pwd_reset_last'])) {
    $_SESSION['pwd_reset_last'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token validation failed. Please try again.';
    } else {
        $now             = time();
        $time_since_last = $now - (int)$_SESSION['pwd_reset_last'];

        if ($time_since_last < 20) {
            $wait = 20 - $time_since_last;
            $errors[] = "Please wait {$wait} seconds before requesting another link.";
        } else {
            $_SESSION['pwd_reset_last'] = $now;
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $errors[] = 'Email address is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } else {
                try {
                    $pdo  = get_pdo();
                    $user = find_user_by_email($pdo, $email);

                    if ($user) {
                        $token      = create_password_reset($pdo, (int)$user['id'], 30);
                        $reset_link = build_reset_link($token);
                        $mail_error = null;
                        $sent       = send_reset_email($email, $user['name'] ?? '', $reset_link, $mail_error);

                        if (!$sent) {
                            $dev_reset_link = $reset_link;
                            error_log('Password reset email failed: ' . ($mail_error ?? 'Unknown error'));
                        }
                    }

                    // Always show success — don't reveal whether the email exists
                    $email_sent = true;

                } catch (Exception $e) {
                    error_log('Forgot password error: ' . $e->getMessage());
                    $errors[] = 'Something went wrong. Please try again.';
                }
            }
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card">

        <?php if ($email_sent): ?>

            <div class="auth-card-header">
                <h2>Check your inbox</h2>
                <p>If an account exists for that email, we've sent a reset link. It's valid for 30 minutes.</p>
            </div>

            <?php if ($dev_reset_link): ?>
                <div class="auth-notice auth-notice-error" role="status">
                    <p><strong>Dev mode:</strong> Email not configured. <a href="<?= e($dev_reset_link) ?>">Click here to reset your password.</a></p>
                </div>
            <?php endif; ?>

            <p class="auth-sent-note">Didn't get it? Check your spam folder, or <a href="<?= BASE_URL ?>/forgot.php">try again</a> after 20 seconds.</p>

        <?php else: ?>

            <div class="auth-card-header">
                <h2>Reset your password</h2>
                <p>Enter the email address on your account and we'll send you a link to get back in.</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="auth-notice auth-notice-error" role="alert">
                    <?php foreach ($errors as $err): ?>
                        <p><?= e($err) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form" autocomplete="on" novalidate>
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= e($_POST['email'] ?? '') ?>"
                        required
                        autocomplete="email"
                        placeholder="you@example.com"
                    >
                </div>

                <div class="form-group">
                    <button type="submit" class="btn primary btn-full">Send Reset Link</button>
                </div>
            </form>

            <div class="auth-card-footer">
                <p>Remember it? <a href="<?= BASE_URL ?>/login.php">Sign In</a></p>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
