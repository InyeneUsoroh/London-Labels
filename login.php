<?php
/**
 * London Labels - Sign In
 */
require_once __DIR__ . '/functions.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Sign In';
$errors = [];
$notice = '';

if (isset($_GET['registered'])) {
    $notice = 'Account created. Welcome to London Labels — please sign in.';
} elseif (isset($_GET['reset'])) {
    $notice = 'Password reset successfully. Sign in with your new password.';
} elseif (isset($_GET['logout'])) {
    $notice = 'You have been signed out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token validation failed. Please try again.';
    } else {
        $email       = trim($_POST['email'] ?? '');
        $password    = $_POST['password'] ?? '';
        $remember_me = !empty($_POST['remember_me']);

        validate_email($email, $errors);
        validate_required($password, 'Password', $errors);

        if (empty($errors)) {
            try {
                $pdo  = get_pdo();
                $stmt = $pdo->prepare('SELECT user_id, username, email, first_name, last_name, password_hash, role FROM Users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if (!$user || !password_verify($password, $user['password_hash'])) {
                    $errors[] = 'Invalid email or password.';
                } else {
                    $user_id      = (int)$user['user_id'];
                    $display_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                    if ($display_name === '') {
                        $display_name = $user['username'] ?? $user['email'];
                    }

                    login_user($user_id, $display_name, $user['email'], $user['role']);
                    update_user_last_login($user_id);

                    if ($remember_me) {
                        remember_trusted_device($user_id);
                    }

                    $redirect = $_GET['redirect'] ?? '';
                    // Guard against open redirect: only allow same-origin paths
                    $base = rtrim(BASE_URL, '/');
                    if (
                        $redirect === '' ||
                        ($base !== '' && !str_starts_with($redirect, $base . '/')) ||
                        ($base === '' && (!str_starts_with($redirect, '/') || str_starts_with($redirect, '//')))
                    ) {
                        $redirect = BASE_URL . '/index.php';
                    }

                    header('Location: ' . $redirect);
                    exit;
                }
            } catch (Exception $e) {
                error_log('Login error: ' . $e->getMessage());
                $errors[] = 'Something went wrong. Please try again.';
            }
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card">

        <div class="auth-card-header">
            <h2>Welcome back</h2>
            <p>Your style is waiting. Sign in to continue.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="auth-notice auth-notice-error" role="alert">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($notice)): ?>
            <div class="auth-notice auth-notice-success" role="status">
                <p><?= e($notice) ?></p>
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
                <div class="auth-label-row">
                    <label for="password">Password</label>
                    <a href="<?= BASE_URL ?>/forgot.php" class="auth-forgot-link">Forgot password?</a>
                </div>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >
            </div>

            <div class="form-group auth-remember-row">
                <label class="auth-checkbox-label">
                    <input type="checkbox" name="remember_me" value="1" <?= isset($_POST['remember_me']) ? 'checked' : '' ?>>
                    <span>Keep me signed in</span>
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn primary btn-full">Sign In</button>
            </div>

            <div class="auth-divider"><span>or</span></div>

            <div class="form-group">
                <a href="<?= BASE_URL ?>/auth/google.php" class="btn btn-full auth-google-btn">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </a>
            </div>
        </form>

        <div class="auth-card-footer">
            <p>Don't have an account? <a href="<?= BASE_URL ?>/register.php">Create Account</a></p>
        </div>

    </div>
</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
