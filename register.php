<?php
/**
 * London Labels - Create Account
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email_verification.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$page_title = 'Create Account';
$errors     = [];
$form_data  = ['first_name' => '', 'last_name' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token validation failed. Please try again.';
    } else {
        $first_name      = trim($_POST['first_name'] ?? '');
        $last_name       = trim($_POST['last_name'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $password        = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $accept_terms    = !empty($_POST['accept_terms']);

        $form_data = compact('first_name', 'last_name', 'email');

        if (empty($first_name) || strlen($first_name) > 50) {
            $errors[] = 'First name is required and must not exceed 50 characters.';
        }
        if (!empty($last_name) && strlen($last_name) > 50) {
            $errors[] = 'Last name must not exceed 50 characters.';
        }

        validate_email($email, $errors);
        validate_password($password, $errors);

        if ($password !== $password_confirm) {
            $errors[] = 'Passwords do not match.';
        }
        if (!$accept_terms) {
            $errors[] = 'You must accept the Terms of Service and Privacy Policy to continue.';
        }

        if (empty($errors)) {
            try {
                if (get_user_by_email($email)) {
                    $errors[] = 'An account with this email already exists. Please sign in or use a different email.';
                }
            } catch (Exception $e) {
                error_log('Registration check error: ' . $e->getMessage());
                $errors[] = 'Unable to verify account details. Please try again.';
            }
        }

        if (empty($errors)) {
            try {
                $pdo = get_pdo();

                // Auto-generate a unique username from first name + random suffix
                $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name));
                if ($base_username === '') $base_username = 'user';
                $username = $base_username . rand(1000, 9999);
                // Ensure uniqueness
                while (get_user_by_username($username)) {
                    $username = $base_username . rand(1000, 9999);
                }

                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare('
                    INSERT INTO Users (username, email, password_hash, first_name, last_name, role, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ');
                $stmt->execute([
                    $username,
                    $email,
                    $password_hash,
                    $first_name,
                    !empty($last_name) ? $last_name : null,
                    'customer',
                ]);

                $user_id = (int)$pdo->lastInsertId();

                try {
                    send_verification_email($user_id, $email, $first_name);
                } catch (Exception $e) {
                    error_log('Verification email error: ' . $e->getMessage());
                }

                $display_name = trim($first_name . ' ' . $last_name);
                login_user($user_id, $display_name, $email, 'customer');
                update_user_last_login($user_id);

                header('Location: ' . BASE_URL . '/index.php?verify_email=1');
                exit;

            } catch (Exception $e) {
                error_log('Registration error: ' . $e->getMessage());
                $errors[] = 'Something went wrong while creating your account. Please try again.';
            }
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<div class="auth-page-wrap">
    <div class="auth-card auth-card-register">

        <div class="auth-card-header">
            <h2>Create your account</h2>
            <p>Style Without Borders starts here.</p>
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

            <div class="auth-name-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="form-required">*</span></label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= e($form_data['first_name']) ?>"
                        required
                        autocomplete="given-name"
                        placeholder="First name"
                        maxlength="50"
                    >
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name</label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= e($form_data['last_name']) ?>"
                        autocomplete="family-name"
                        placeholder="Last name"
                        maxlength="50"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address <span class="form-required">*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($form_data['email']) ?>"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                    maxlength="255"
                >
            </div>

            <div class="form-group">
                <label for="password">Password <span class="form-required">*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Create a strong password"
                    minlength="8"
                    aria-describedby="pw-reqs"
                >
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
                <input
                    type="password"
                    id="password_confirm"
                    name="password_confirm"
                    required
                    autocomplete="new-password"
                    placeholder="Re-enter your password"
                >
            </div>

            <div class="form-group auth-terms-row">
                <label class="auth-checkbox-label">
                    <input type="checkbox" name="accept_terms" value="1" required>
                    <span>I agree to the <a href="<?= BASE_URL ?>/legal/terms.php" target="_blank" rel="noopener">Terms of Service</a> and <a href="<?= BASE_URL ?>/legal/privacy.php" target="_blank" rel="noopener">Privacy Policy</a></span>
                </label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn primary btn-full">Create Account</button>
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
            <p>Already have an account? <a href="<?= BASE_URL ?>/login.php">Sign In</a></p>
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

        var levels = ['', 'Weak', 'Fair', 'Fair', 'Good', 'Strong'];
        var classes = ['', 'pw-weak', 'pw-fair', 'pw-fair', 'pw-good', 'pw-strong'];
        label.textContent = levels[score] || '';
        label.className   = 'pw-strength ' + (classes[score] || '');
    });
})();
</script>

<?php include __DIR__ . '/inc_footer.php'; ?>
