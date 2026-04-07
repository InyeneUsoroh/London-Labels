<?php
/**
 * London Labels - FAQ (public)
 */
require_once __DIR__ . '/functions.php';

$page_title = 'FAQs';
$meta_description = 'Answers to common questions about ordering, delivery, payments, and your account at London Labels.';

include __DIR__ . '/inc_header.php';
?>

<div class="legal-page-wrap faq-page-wrap">

    <div class="legal-page-header">
        <h2 class="legal-page-title">Frequently Asked Questions</h2>
        <p class="legal-page-meta">Can't find what you're looking for? <a href="<?= BASE_URL ?>/contact.php">Contact us</a> and we'll get back to you within 24–48 hours.</p>
    </div>

    <div class="legal-page-body">

        <h3>Orders &amp; Delivery</h3>
        <div class="faq-group">
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
                <p>Yes. You can collect from our store at <?= e(STORE_ADDRESS) ?>. Pickup is arranged directly with our team, so please <a href="<?= BASE_URL ?>/contact.php">contact us</a> after placing your order.</p>
            </details>
            <details class="help-faq">
                <summary>How can I track my order?</summary>
                <p>Once you're signed in, visit your <a href="<?= BASE_URL ?>/account/orders.php">Orders page</a> to view the current status of all your orders. Once your order is out for delivery, you will be notified with tracking details where available.</p>
            </details>
            <details class="help-faq">
                <summary>Can I cancel an order?</summary>
                <p>You can request a cancellation within 24 hours of placing your order, provided it hasn't been prepared for dispatch yet. <a href="<?= BASE_URL ?>/contact.php">Contact us</a> as soon as possible and we'll do our best to help.</p>
            </details>
        </div>

        <h3>Payment &amp; Pricing</h3>
        <div class="faq-group">
            <details class="help-faq">
                <summary>What payment methods do you accept?</summary>
                <p>We use Paystack for secure online payments. You can pay by card, bank transfer, or USSD — all within the Paystack checkout. Your payment details are never stored on our servers.</p>
            </details>
            <details class="help-faq">
                <summary>Are your prices in Naira?</summary>
                <p>Yes. All prices are displayed in Nigerian Naira (&#8358;). No hidden conversion fees.</p>
            </details>
            <details class="help-faq">
                <summary>Is my payment information secure?</summary>
                <p>Yes. All transactions are processed over encrypted HTTPS connections. We do not store full card or bank details on our servers.</p>
            </details>
        </div>

        <h3>Account &amp; Security</h3>
        <div class="faq-group">
            <details class="help-faq">
                <summary>How do I create an account?</summary>
                <p><a href="<?= BASE_URL ?>/register.php">Create an account</a> to track orders, add items to your wishlist, and check out faster. It only takes a minute.</p>
            </details>
            <details class="help-faq">
                <summary>How do I reset my password?</summary>
                <p>On the <a href="<?= BASE_URL ?>/login.php">Sign In page</a>, click "Forgot password?" and we'll send a reset link to your email address.</p>
            </details>
            <details class="help-faq">
                <summary>How do I update my email address?</summary>
                <p>Sign in and go to your <a href="<?= BASE_URL ?>/account/edit-profile.php">profile settings</a>. After updating your email you'll be signed out and asked to verify the new address.</p>
            </details>
            <details class="help-faq">
                <summary>Why am I being asked to verify my identity when signing in?</summary>
                <p>When you sign in from a new device or location, we send a one-time verification code to your email as an extra security step. This helps protect your account from unauthorised access.</p>
            </details>
        </div>

        <h3>Products &amp; Stock</h3>
        <div class="faq-group">
            <details class="help-faq">
                <summary>Where are your products sourced from?</summary>
                <p>All our pieces are sourced from the United Kingdom and stocked at our Lagos store. Every item is authentic — no replicas, no grey market goods.</p>
            </details>
            <details class="help-faq">
                <summary>What if an item I want is out of stock?</summary>
                <p>Stock levels update regularly. <a href="<?= BASE_URL ?>/contact.php">Contact us</a> if there's a specific item you're after and we'll let you know when it's back.</p>
            </details>
        </div>

        <div class="faq-still-need-help">
            <h3>Still have a question?</h3>
            <p>Our support team is available <?= e(CUSTOMER_CARE_HOURS) ?>, Mon – Sat. We aim to respond within 24–48 hours.</p>
            <a href="<?= BASE_URL ?>/contact.php" class="btn primary">Contact Us</a>
        </div>

    </div>
</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
