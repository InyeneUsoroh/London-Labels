<?php
/**
 * London Labels - Email Verification Functions
 * Industry-standard email verification system
 */

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

/**
 * Check if user's email is verified
 */
function is_email_verified(?int $user_id = null): bool {
    if ($user_id === null) {
        $user_id = current_user_id();
    }
    
    if ($user_id === null) {
        return false;
    }
    
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT email_verified FROM Users WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    
    return $result && (int)$result['email_verified'] === 1;
}

/**
 * Generate and store email verification token
 */
function create_email_verification_token(int $user_id, int $ttl_hours = 24): string {
    $pdo = get_pdo();
    
    // Generate secure token
    $token = bin2hex(random_bytes(32)); // 64 character hex string
    $expires_at = date('Y-m-d H:i:s', time() + ($ttl_hours * 3600));
    
    // Invalidate any existing unverified tokens for this user
    $pdo->prepare('
        UPDATE email_verifications 
        SET verified_at = NOW() 
        WHERE user_id = ? AND verified_at IS NULL
    ')->execute([$user_id]);
    
    // Create new verification token
    $stmt = $pdo->prepare('
        INSERT INTO email_verifications (user_id, token, expires_at)
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$user_id, $token, $expires_at]);
    
    return $token;
}

/**
 * Verify email token and mark user as verified
 */
function verify_email_token(string $token): array {
    $pdo = get_pdo();
    
    // Find token
    $stmt = $pdo->prepare('
        SELECT ev.id, ev.user_id, ev.expires_at, ev.verified_at,
               u.email, u.username, u.email_verified
        FROM email_verifications ev
        JOIN Users u ON ev.user_id = u.user_id
        WHERE ev.token = ?
        LIMIT 1
    ');
    $stmt->execute([$token]);
    $verification = $stmt->fetch();
    
    if (!$verification) {
        return ['success' => false, 'message' => 'Invalid verification link.'];
    }
    
    // Check if already verified
    if ($verification['verified_at'] !== null) {
        return ['success' => false, 'message' => 'This email has already been verified.'];
    }
    
    // Check if expired
    if (strtotime($verification['expires_at']) < time()) {
        return ['success' => false, 'message' => 'This verification link has expired. Please request a new one.'];
    }
    
    // Check if user already verified through another token
    if ((int)$verification['email_verified'] === 1) {
        return ['success' => false, 'message' => 'Your email is already verified.'];
    }
    
    // Mark token as used
    $pdo->prepare('
        UPDATE email_verifications 
        SET verified_at = NOW() 
        WHERE id = ?
    ')->execute([$verification['id']]);
    
    // Mark user as verified
    $pdo->prepare('
        UPDATE Users 
        SET email_verified = 1, email_verified_at = NOW() 
        WHERE user_id = ?
    ')->execute([$verification['user_id']]);
    
    return [
        'success' => true,
        'message' => 'Email verified successfully!',
        'user_id' => (int)$verification['user_id']
    ];
}

/**
 * Send verification email
 */
function send_verification_email(int $user_id, string $email, string $username): bool {
    // Generate token
    $token = create_email_verification_token($user_id);
    
    // Build verification URL
    $verification_url = build_verification_url($token);
    
    // Email subject
    $subject = 'Verify Your Email - ' . SITE_NAME;
    
    // Email body (HTML)
    $html_body = render_verification_email_html($username, $verification_url);
    
    // Email body (Plain text fallback)
    $text_body = render_verification_email_text($username, $verification_url);
    
    // Send email
    return send_email($email, $subject, $html_body, $text_body);
}

/**
 * Build verification URL
 */
function build_verification_url(string $token): string {
    $path = BASE_URL . '/verify-email.php?token=' . urlencode($token);
    
    // Build full URL with protocol and host
    if (PHP_SAPI !== 'cli' && !empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $scheme . '://' . $_SERVER['HTTP_HOST'] . $path;
    }
    
    return $path;
}

/**
 * Render verification email HTML
 */
function render_verification_email_html(string $username, string $verification_url): string {
    require_once __DIR__ . '/mailer.php';

    $site_name     = SITE_NAME;
    $username_safe = htmlspecialchars($username,         ENT_QUOTES, 'UTF-8');
    $url_safe      = htmlspecialchars($verification_url, ENT_QUOTES, 'UTF-8');

    $inner = "
        <h2 style='margin:0 0 16px;font-size:20px;color:#1a1a1a;font-weight:600;'>Verify your email address</h2>
        <p style='margin:0 0 20px;font-size:15px;color:#4b5563;line-height:1.7;'>Hi {$username_safe}, thanks for joining {$site_name}. One quick step — click the button below to verify your email and start shopping.</p>
        <table width='100%' cellpadding='0' cellspacing='0'><tr><td style='padding:8px 0 28px;'>
            <a href='{$url_safe}' style='display:inline-block;padding:12px 28px;background:#e8357e;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;'>Verify Email Address</a>
        </td></tr></table>
        <p style='margin:0 0 8px;font-size:13px;color:#6b7280;'>Or copy this link into your browser:</p>
        <p style='margin:0 0 20px;padding:10px 14px;background:#fff5fa;border-radius:8px;font-size:13px;word-break:break-all;'><a href='{$url_safe}' style='color:#e8357e;text-decoration:none;'>{$url_safe}</a></p>
        <p style='margin:0 0 8px;font-size:13px;color:#6b7280;'>This link expires in 24 hours.</p>
        <p style='margin:0;font-size:13px;color:#6b7280;'>If you did not create an account with {$site_name}, you can safely ignore this email.</p>
    ";

    return _ll_email_wrap($site_name, $inner);
}

/**
 * Render verification email plain text
 */
function render_verification_email_text(string $username, string $verification_url): string {
    $site_name = SITE_NAME;
    $year      = date('Y');

    return <<<TEXT
Verify your email address

Hi {$username},

Thanks for joining {$site_name}. Click the link below to verify your email address and start shopping:

{$verification_url}

This link expires in 24 hours.

If you did not create an account with {$site_name}, you can safely ignore this email.

— {$site_name} · Style Without Borders · © {$year}
TEXT;
}

/**
 * Check if user needs to verify email for checkout
 */
function require_email_verification_for_checkout(): bool {
    if (!is_logged_in()) {
        return false;
    }
    
    return !is_email_verified();
}

/**
 * Get verification status message for user
 */
function get_verification_status_message(): ?string {
    if (!is_logged_in() || is_email_verified()) {
        return null;
    }
    
    return 'Please verify your email address to complete purchases.';
}

/**
 * Clean up expired verification tokens (run periodically)
 */
function cleanup_expired_verification_tokens(): int {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('
        DELETE FROM email_verifications 
        WHERE expires_at < NOW() AND verified_at IS NULL
    ');
    $stmt->execute();
    return $stmt->rowCount();
}
