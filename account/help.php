<?php
/**
 * London Labels - Help Center
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Help';
$account_page = 'help';

require_login();

include __DIR__ . '/../inc_header.php';
include __DIR__ . '/inc_account_layout.php';
?>

<div class="account-page-head">
    <h2 class="account-page-title">Help Center</h2>
</div>

<div class="account-card">
    <h3>Orders &amp; Delivery</h3>
    <div class="account-help-faqs">
        <details class="help-faq">
            <summary>How can I track my order?</summary>
            <p>Visit your <a href="<?= BASE_URL ?>/account/orders.php">Orders page</a> to view the current status of all your orders. Once your order is out for delivery, you will be notified with tracking details where available.</p>
        </details>
        <details class="help-faq">
            <summary>How long does delivery take?</summary>
            <p>We deliver from our store in Ajah, Lagos. Lagos orders are typically fulfilled within 1–3 business days. Deliveries to other states across Nigeria may take 3–7 business days. Delivery fees apply and will be shown at checkout.</p>
        </details>
        <details class="help-faq">
            <summary>Do you deliver outside Lagos?</summary>
            <p>Yes — we deliver nationwide across Nigeria. If you have a question about delivery to your area, <a href="<?= BASE_URL ?>/contact.php">contact us</a>.</p>
        </details>
        <details class="help-faq">
            <summary>Can I pick up my order in store?</summary>
            <p>Yes. You can collect from our store at <?= e(STORE_ADDRESS) ?>. Select in-store pickup at checkout or <a href="<?= BASE_URL ?>/contact.php">contact us</a> to arrange collection.</p>
        </details>
    </div>
</div>

<div class="account-card">
    <h3>Account &amp; Security</h3>
    <div class="account-help-faqs">
        <details class="help-faq">
            <summary>How do I change my password?</summary>
            <p>Visit your <a href="<?= BASE_URL ?>/account/change-password.php">Change Password page</a>. You will need to enter your current password to confirm the change.</p>
        </details>
        <details class="help-faq">
            <summary>What happens when I sign in from a new device?</summary>
            <p>When you sign in from a new device or location, we send a one-time verification code to your email as an extra security step. This helps keep your account protected. If you change your password or email, you will be signed out of all devices automatically.</p>
        </details>
        <details class="help-faq">
            <summary>How do I update my email address?</summary>
            <p>Update your email in your <a href="<?= BASE_URL ?>/account/edit-profile.php">profile settings</a>. You will be signed out after the change and will need to verify your new email.</p>
        </details>
    </div>
</div>

<div class="account-card">
    <h3>Payment &amp; Pricing</h3>
    <div class="account-help-faqs">
        <details class="help-faq">
            <summary>What payment methods do you accept?</summary>
            <p>We use Paystack for secure online payments. You can pay by card, bank transfer, or USSD — all within the Paystack checkout. Your payment details are never stored on our servers.</p>
        </details>
        <details class="help-faq">
            <summary>Are your prices in Naira?</summary>
            <p>Yes. All prices are displayed in Nigerian Naira (₦). No hidden conversion fees.</p>
        </details>
        <details class="help-faq">
            <summary>Is my payment information secure?</summary>
            <p>Yes. All transactions are processed over encrypted HTTPS connections. We do not store full card or bank details on our servers.</p>
        </details>
    </div>
</div>

<div class="account-card">
    <h3>Still Need Help?</h3>
    <p class="account-card-subtitle">Our support team is here for you.</p>
    <div class="account-actions">
        <a href="<?= BASE_URL ?>/contact.php" class="btn primary account-action-btn">Contact Support</a>
    </div>
    <div class="account-help-contact-info">
        <p><strong>Email:</strong> <a href="mailto:<?= e(CONTACT_EMAIL) ?>"><?= e(CONTACT_EMAIL) ?></a></p>
        <p><strong>Hours:</strong> <?= e(CUSTOMER_CARE_HOURS) ?></p>
        <p><strong>Response time:</strong> Within 24–48 hours</p>
    </div>
</div>

    </div><!-- /.account-content -->
</div><!-- /.account-shell -->

<?php include __DIR__ . '/../inc_footer.php'; ?>
