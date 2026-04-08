<?php
require_once __DIR__ . '/config.php';

// Include PHPMailer classes manually
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Shared branded email shell for all London Labels transactional emails.
 * Wraps $innerHtml in the standard header/footer layout.
 */
function _ll_email_wrap(string $siteName, string $innerHtml): string {
    $year = date('Y');
    return "<!DOCTYPE html>
<html lang='en'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'></head>
<body style='margin:0;padding:0;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;background:#f7f4f6;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#f7f4f6;padding:40px 20px;'>
    <tr><td align='center'>
      <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,0.08);'>
        <tr>
          <td style='padding:28px 40px 20px;border-bottom:3px solid #e8357e;'>
            <span style='font-size:20px;font-weight:700;color:#1a1a1a;letter-spacing:-0.5px;'>{$siteName}</span>
          </td>
        </tr>
        <tr><td style='padding:32px 40px;'>{$innerHtml}</td></tr>
        <tr>
          <td style='padding:20px 40px;background:#f9fafb;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;'>
                        <p style='margin:0;font-size:12px;color:#6b7280;text-align:center;'>Style Without Borders &mdash; &copy; {$year} {$siteName}. All rights reserved.</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>";
}

function send_reset_email(string $toEmail, string $toName, string $resetLink, ?string &$error = null): bool {
    $mail = new PHPMailer(true);
    $siteName = SITE_NAME;
    $safeName = htmlspecialchars($toName ?: $toEmail, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        $mail->Timeout    = 10; // Prevent long hangs

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Reset your password — ' . $siteName;
        $mail->Body = _ll_email_wrap($siteName, "
            <h2 style='margin:0 0 16px;font-size:20px;color:#1a1a1a;font-weight:600;'>Reset your password</h2>
            <p style='margin:0 0 20px;font-size:15px;color:#4b5563;line-height:1.7;'>Hi {$safeName}, we received a request to reset your password. Click the button below — this link is valid for 30 minutes.</p>
            <table width='100%' cellpadding='0' cellspacing='0'><tr><td style='padding:8px 0 28px;'>
                <a href='{$safeLink}' style='display:inline-block;padding:12px 28px;background:#e8357e;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;'>Reset Password</a>
            </td></tr></table>
            <p style='margin:0 0 8px;font-size:13px;color:#6b7280;'>Or copy this link into your browser:</p>
            <p style='margin:0 0 20px;padding:10px 14px;background:#fff5fa;border-radius:8px;font-size:13px;word-break:break-all;'><a href='{$safeLink}' style='color:#e8357e;text-decoration:none;'>{$safeLink}</a></p>
            <p style='margin:0;font-size:13px;color:#6b7280;'>If you did not request this, you can safely ignore this email. Your password will not change.</p>
        ");
        $mail->AltBody = "Hi {$safeName},\n\nReset your password (valid 30 minutes):\n{$resetLink}\n\nIf you did not request this, ignore this email.\n\n— {$siteName}";

        session_write_close(); // Unblock other requests from this user while we wait for SMTP
        $mail->send();
        return true;
    } catch (Exception $ex) {
        $error = $mail->ErrorInfo ?: $ex->getMessage();
        return false;
    }
}

function send_twofactor_email(string $toEmail, string $toName, string $code, ?string &$error = null): bool {
    $siteName = SITE_NAME;
    $safeName = htmlspecialchars($toName ?: $toEmail, ENT_QUOTES, 'UTF-8');
    $safeCode = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your sign-in code — ' . $siteName;
        $mail->Body = _ll_email_wrap($siteName, "
            <h2 style='margin:0 0 16px;font-size:20px;color:#1a1a1a;font-weight:600;'>Your sign-in code</h2>
            <p style='margin:0 0 24px;font-size:15px;color:#4b5563;line-height:1.7;'>Hi {$safeName}, use the code below to complete your sign-in. It expires in 5 minutes.</p>
            <div style='text-align:center;padding:24px;background:#fff5fa;border-radius:8px;border-left:3px solid #e8357e;margin-bottom:24px;'>
                <span style='font-size:36px;font-weight:700;letter-spacing:0.15em;color:#1a1a1a;'>{$safeCode}</span>
            </div>
            <p style='margin:0;font-size:13px;color:#6b7280;'>If you did not attempt to sign in, please change your password immediately.</p>
        ");
        $mail->AltBody = "Hi {$safeName},\n\nYour sign-in code: {$code}\n\nExpires in 5 minutes.\n\n— {$siteName}";

        $mail->send();
        return true;
    } catch (Exception $ex) {
        $error = $mail->ErrorInfo ?: $ex->getMessage();
        return false;
    }
}

function send_order_confirmation_email(string $toEmail, string $toName, int $orderId, array $orderItems, float $total, ?string &$error = null): bool {
    $mail = new PHPMailer(true);
    $siteName = SITE_NAME;
    $safeName = htmlspecialchars($toName ?: $toEmail, ENT_QUOTES, 'UTF-8');

    $itemsHtml = '';
    foreach ($orderItems as $item) {
        $itemName     = htmlspecialchars($item['name'],                        ENT_QUOTES, 'UTF-8');
        $itemQty      = htmlspecialchars((string)$item['quantity'],            ENT_QUOTES, 'UTF-8');
        $itemSubtotal = htmlspecialchars(number_format($item['subtotal'], 2),  ENT_QUOTES, 'UTF-8');
        $itemsHtml .= "
            <tr>
                <td style='padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:14px;color:#374151;'>{$itemName}</td>
                <td style='padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:14px;color:#6b7280;text-align:center;'>{$itemQty}</td>
                <td style='padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:14px;color:#1a1a1a;font-weight:500;text-align:right;'>&#8358;{$itemSubtotal}</td>
            </tr>";
    }

    $safeTotal = htmlspecialchars(number_format($total, 2), ENT_QUOTES, 'UTF-8');
    $contactEmail = htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8');

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = "Order #{$orderId} confirmed — {$siteName}";
        $mail->Body = _ll_email_wrap($siteName, "
            <h2 style='margin:0 0 8px;font-size:20px;color:#1a1a1a;font-weight:600;'>Order confirmed</h2>
            <p style='margin:0 0 24px;font-size:15px;color:#4b5563;line-height:1.7;'>Hi {$safeName}, thank you for your order. We will be in touch once it is on its way.</p>
            <div style='background:#fff5fa;border-radius:8px;border-left:3px solid #e8357e;padding:16px 20px;margin-bottom:24px;'>
                <p style='margin:0;font-size:13px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;'>Order number</p>
                <p style='margin:4px 0 0;font-size:20px;font-weight:700;color:#1a1a1a;'>#{$orderId}</p>
            </div>
            <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:8px;'>
                <thead>
                    <tr>
                        <th style='padding:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;text-align:left;font-weight:500;'>Item</th>
                        <th style='padding:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;text-align:center;font-weight:500;'>Qty</th>
                        <th style='padding:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;text-align:right;font-weight:500;'>Subtotal</th>
                    </tr>
                </thead>
                <tbody>{$itemsHtml}</tbody>
                <tfoot>
                    <tr>
                        <td colspan='2' style='padding:12px 0 0;font-size:15px;font-weight:600;color:#1a1a1a;'>Total</td>
                        <td style='padding:12px 0 0;font-size:15px;font-weight:700;color:#e8357e;text-align:right;'>&#8358;{$safeTotal}</td>
                    </tr>
                </tfoot>
            </table>
            <p style='margin:24px 0 0;font-size:13px;color:#6b7280;'>Questions? Reply to this email or contact us at <a href='mailto:{$contactEmail}' style='color:#e8357e;text-decoration:none;'>{$contactEmail}</a>.</p>
        ");
        $mail->AltBody = "Hi {$safeName},\n\nOrder #{$orderId} confirmed. Total: ₦{$safeTotal}.\n\nThank you for shopping with {$siteName}.";

        $mail->send();
        return true;
    } catch (Exception $ex) {
        $error = $mail->ErrorInfo ?: $ex->getMessage();
        return false;
    }
}

/**
 * Send contact form notification to admin + auto-reply to user.
 * Returns true if the admin notification was sent successfully.
 */
function send_contact_notification(string $fromEmail, string $fromName, string $subject, string $message, ?string &$error = null): bool {
    $siteName  = SITE_NAME;
    $adminEmail = CONTACT_EMAIL; // support@londonlabels.com
    $safeName    = htmlspecialchars($fromName,  ENT_QUOTES, 'UTF-8');
    $safeEmail   = htmlspecialchars($fromEmail, ENT_QUOTES, 'UTF-8');
    $safeSubject = htmlspecialchars($subject,   ENT_QUOTES, 'UTF-8');
    $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    $safeHours   = htmlspecialchars(CUSTOMER_CARE_HOURS, ENT_QUOTES, 'UTF-8');

    // ── 1. Admin notification ──────────────────────────────────────────────
    $adminMail = new PHPMailer(true);
    try {
        $adminMail->isSMTP();
        $adminMail->Host       = MAIL_HOST;
        $adminMail->SMTPAuth   = true;
        $adminMail->Username   = MAIL_USERNAME;
        $adminMail->Password   = MAIL_PASSWORD;
        $adminMail->SMTPSecure = MAIL_ENCRYPTION;
        $adminMail->Port       = MAIL_PORT;
        $adminMail->Timeout    = 10;

        $adminMail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $adminMail->addAddress($adminEmail, $siteName . ' Support');
        // Reply-To set to the customer so you can reply directly from your inbox
        $adminMail->addReplyTo($fromEmail, $fromName);

        $adminMail->isHTML(true);
        $adminMail->Subject = "[Contact Form] {$subject}";
        $adminMail->Body = _ll_email_wrap($siteName . ' — Support', "
            <p style='margin:0 0 4px;font-size:12px;color:#e8357e;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;'>New contact message</p>
            <h2 style='margin:0 0 24px;font-size:20px;color:#1a1a1a;font-weight:600;'>{$safeSubject}</h2>
            <div style='background:#fff5fa;border-radius:8px;border-left:3px solid #e8357e;padding:20px 24px;margin-bottom:24px;'>
                <p style='margin:0 0 4px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;'>From</p>
                <p style='margin:0;font-size:16px;color:#1a1a1a;font-weight:600;'>{$safeName}</p>
                <p style='margin:4px 0 0;font-size:14px;'><a href='mailto:{$safeEmail}' style='color:#e8357e;text-decoration:none;'>{$safeEmail}</a></p>
            </div>
            <p style='margin:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;'>Message</p>
            <div style='font-size:15px;color:#374151;line-height:1.7;background:#fff5fa;border-radius:8px;padding:20px 24px;margin-bottom:28px;'>{$safeMessage}</div>
            <a href='mailto:{$safeEmail}?subject=Re%3A%20{$safeSubject}' style='display:inline-block;padding:12px 28px;background:#e8357e;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;'>Reply to {$safeName}</a>
        ");
        $adminMail->AltBody = "New contact message from {$fromName} ({$fromEmail})\n\nSubject: {$subject}\n\n{$message}";

        session_write_close(); 
        $adminMail->send();
    } catch (Exception $ex) {
        $error = $adminMail->ErrorInfo ?: $ex->getMessage();
        return false;
    }

    // ── 2. Auto-reply to user ──────────────────────────────────────────────
    // Non-critical — don't fail the whole operation if this bounces
    try {
        $replyMail = new PHPMailer(true);
        $replyMail->isSMTP();
        $replyMail->Host       = MAIL_HOST;
        $replyMail->SMTPAuth   = true;
        $replyMail->Username   = MAIL_USERNAME;
        $replyMail->Password   = MAIL_PASSWORD;
        $replyMail->SMTPSecure = MAIL_ENCRYPTION;
        $replyMail->Port       = MAIL_PORT;

        $replyMail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $replyMail->addAddress($fromEmail, $fromName);

        $replyMail->isHTML(true);
        $replyMail->Subject = "We received your message — {$siteName}";
        $replyMail->Body = _ll_email_wrap($siteName, "
            <h2 style='margin:0 0 16px;font-size:20px;color:#1a1a1a;font-weight:600;'>Thanks for reaching out, {$safeName}.</h2>
            <p style='margin:0 0 20px;font-size:15px;color:#4b5563;line-height:1.7;'>We've received your message and our team will get back to you within <strong>24–48 hours</strong>. We're available {$safeHours}.</p>
            <div style='background:#fff5fa;border-radius:8px;border-left:3px solid #e8357e;padding:20px 24px;margin-bottom:24px;'>
                <p style='margin:0 0 6px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;'>Your message</p>
                <p style='margin:0 0 6px;font-size:14px;color:#1a1a1a;font-weight:600;'>{$safeSubject}</p>
                <p style='margin:0;font-size:14px;color:#6b7280;line-height:1.6;'>{$safeMessage}</p>
            </div>
            <p style='margin:0;font-size:13px;color:#6b7280;'>If your query is urgent, reach us directly at <a href='mailto:{$adminEmail}' style='color:#e8357e;text-decoration:none;'>{$adminEmail}</a>.</p>
        ");
        $replyMail->AltBody = "Hi {$fromName},\n\nWe've received your message and will respond within 24–48 hours ({$safeHours}).\n\nYour message:\n{$subject}\n{$message}\n\n— {$siteName}";
        $replyMail->send();
    } catch (Exception) {
        // Auto-reply failure is non-fatal — admin was already notified
    }

    return true;
}

function send_account_deleted_email(string $toEmail, string $toName, ?string &$error = null): bool {
    $mail     = new PHPMailer(true);
    $siteName = SITE_NAME;
    $safeName = htmlspecialchars($toName ?: 'there', ENT_QUOTES, 'UTF-8');
    $contactEmail = htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8');

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your account has been deleted — ' . $siteName;
        $mail->Body = _ll_email_wrap($siteName, "
            <h2 style='margin:0 0 16px;font-size:20px;color:#1a1a1a;font-weight:600;'>Account deleted</h2>
            <p style='margin:0 0 20px;font-size:15px;color:#4b5563;line-height:1.7;'>Hi {$safeName}, your {$siteName} account and personal data have been permanently deleted as requested.</p>
            <p style='margin:0 0 20px;font-size:15px;color:#4b5563;line-height:1.7;'>Your order history has been retained for legal and financial record-keeping purposes, but all personal identifiers have been removed.</p>
            <p style='margin:0;font-size:13px;color:#6b7280;'>If you did not request this, please contact us immediately at <a href='mailto:{$contactEmail}' style='color:#e8357e;text-decoration:none;'>{$contactEmail}</a>.</p>
        ");
        $mail->AltBody = "Hi {$safeName},\n\nYour {$siteName} account and personal data have been permanently deleted.\n\nIf you did not request this, contact us at " . CONTACT_EMAIL . "\n\n— {$siteName}";

        $mail->send();
        return true;
    } catch (Exception $ex) {
        $error = $mail->ErrorInfo ?: $ex->getMessage();
        return false;
    }
}

/**
 * Send new order notification to admin.
 * Fires after a successful payment — non-fatal if it fails.
 */
function send_new_order_admin_notification(int $orderId, string $customerName, string $customerEmail, array $orderItems, float $total, string $shippingAddress, string $phone): bool {
    $siteName   = SITE_NAME;
    $adminEmail = CONTACT_EMAIL;

    $itemsHtml = '';
    foreach ($orderItems as $item) {
        $itemName     = htmlspecialchars($item['name'],                       ENT_QUOTES, 'UTF-8');
        $itemQty      = (int)$item['quantity'];
        $itemSize     = !empty($item['size_label']) ? ' (' . htmlspecialchars((string)$item['size_label'], ENT_QUOTES, 'UTF-8') . ')' : '';
        $itemSubtotal = number_format((float)$item['subtotal'], 2);
        $itemsHtml .= "
            <tr>
                <td style='padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:14px;color:#374151;'>{$itemName}{$itemSize}</td>
                <td style='padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:14px;color:#6b7280;text-align:center;'>{$itemQty}</td>
                <td style='padding:10px 0;border-bottom:1px solid #e5e7eb;font-size:14px;color:#1a1a1a;font-weight:500;text-align:right;'>&#8358;{$itemSubtotal}</td>
            </tr>";
    }

    $safeCustomer = htmlspecialchars($customerName,    ENT_QUOTES, 'UTF-8');
    $safeEmail    = htmlspecialchars($customerEmail,   ENT_QUOTES, 'UTF-8');
    $safeAddress  = htmlspecialchars($shippingAddress, ENT_QUOTES, 'UTF-8');
    $safePhone    = htmlspecialchars($phone,           ENT_QUOTES, 'UTF-8');
    $safeTotal    = number_format($total, 2);
    $adminUrl     = (defined('BASE_URL') ? BASE_URL : '') . '/admin/orders.php';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        $mail->Timeout    = 10;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($adminEmail, $siteName . ' Admin');

        $mail->isHTML(true);
        $mail->Subject = "[New Order #{$orderId}] {$customerName} — ₦{$safeTotal}";
        $mail->Body = _ll_email_wrap($siteName . ' — New Order', "
            <p style='margin:0 0 4px;font-size:12px;color:#e8357e;font-weight:600;text-transform:uppercase;letter-spacing:0.1em;'>New order received</p>
            <h2 style='margin:0 0 24px;font-size:20px;color:#1a1a1a;font-weight:600;'>Order #{$orderId}</h2>

            <div style='background:#fff5fa;border-radius:8px;border-left:3px solid #e8357e;padding:20px 24px;margin-bottom:24px;'>
                <p style='margin:0 0 4px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;'>Customer</p>
                <p style='margin:0;font-size:16px;color:#1a1a1a;font-weight:600;'>{$safeCustomer}</p>
                <p style='margin:4px 0 2px;font-size:14px;'><a href='mailto:{$safeEmail}' style='color:#e8357e;text-decoration:none;'>{$safeEmail}</a></p>
                <p style='margin:2px 0 2px;font-size:13px;color:#6b7280;'>{$safePhone}</p>
                <p style='margin:2px 0 0;font-size:13px;color:#6b7280;'>{$safeAddress}</p>
            </div>

            <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:8px;'>
                <thead>
                    <tr>
                        <th style='padding:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;text-align:left;font-weight:500;'>Item</th>
                        <th style='padding:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;text-align:center;font-weight:500;'>Qty</th>
                        <th style='padding:0 0 8px;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;text-align:right;font-weight:500;'>Subtotal</th>
                    </tr>
                </thead>
                <tbody>{$itemsHtml}</tbody>
                <tfoot>
                    <tr>
                        <td colspan='2' style='padding:12px 0 0;font-size:15px;font-weight:600;color:#1a1a1a;'>Total</td>
                        <td style='padding:12px 0 0;font-size:15px;font-weight:700;color:#e8357e;text-align:right;'>&#8358;{$safeTotal}</td>
                    </tr>
                </tfoot>
            </table>

            <div style='margin-top:28px;'>
                <a href='{$adminUrl}' style='display:inline-block;padding:12px 28px;background:#1a1a1a;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:600;'>View in Admin Panel</a>
            </div>
        ");
        $mail->AltBody = "New order #{$orderId} from {$customerName} ({$customerEmail})\nTotal: ₦{$safeTotal}\nPhone: {$phone}\nAddress: {$shippingAddress}\n\nView: {$adminUrl}";

        $mail->send();
        return true;
    } catch (Exception $ex) {
        error_log('Admin order notification failed: ' . ($mail->ErrorInfo ?: $ex->getMessage()));
        return false;
    }
}

function send_order_status_update_email(string $toEmail, string $toName, int $orderId, string $status, string $paymentStatus, ?string &$error = null): bool {
    $mail = new PHPMailer(true);
    $siteName    = SITE_NAME;
    $safeName    = htmlspecialchars($toName ?: $toEmail, ENT_QUOTES, 'UTF-8');
    $safeStatus  = htmlspecialchars(ucfirst($status),        ENT_QUOTES, 'UTF-8');
    $safePayment = htmlspecialchars(ucfirst($paymentStatus), ENT_QUOTES, 'UTF-8');
    $contactEmail = htmlspecialchars(CONTACT_EMAIL, ENT_QUOTES, 'UTF-8');

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = "Order #{$orderId} update — {$siteName}";
        $mail->Body = _ll_email_wrap($siteName, "
            <h2 style='margin:0 0 16px;font-size:20px;color:#1a1a1a;font-weight:600;'>Order update</h2>
            <p style='margin:0 0 24px;font-size:15px;color:#4b5563;line-height:1.7;'>Hi {$safeName}, your order has been updated.</p>
            <div style='background:#fff5fa;border-radius:8px;border-left:3px solid #e8357e;padding:20px 24px;margin-bottom:24px;'>
                <p style='margin:0 0 12px;font-size:13px;color:#6b7280;text-transform:uppercase;letter-spacing:0.08em;'>Order #{$orderId}</p>
                <table width='100%' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td style='font-size:14px;color:#6b7280;padding-bottom:6px;'>Order status</td>
                        <td style='font-size:14px;color:#1a1a1a;font-weight:600;text-align:right;padding-bottom:6px;'>{$safeStatus}</td>
                    </tr>
                    <tr>
                        <td style='font-size:14px;color:#6b7280;'>Payment status</td>
                        <td style='font-size:14px;color:#1a1a1a;font-weight:600;text-align:right;'>{$safePayment}</td>
                    </tr>
                </table>
            </div>
            <p style='margin:0;font-size:13px;color:#6b7280;'>Questions? Contact us at <a href='mailto:{$contactEmail}' style='color:#e8357e;text-decoration:none;'>{$contactEmail}</a>.</p>
        ");
        $mail->AltBody = "Hi {$safeName},\n\nOrder #{$orderId} update.\nStatus: {$status}\nPayment: {$paymentStatus}\n\n— {$siteName}";

        $mail->send();
        return true;
    } catch (Exception $ex) {
        $error = $mail->ErrorInfo ?: $ex->getMessage();
        return false;
    }
}
