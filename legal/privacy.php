<?php
require_once __DIR__ . '/../functions.php';
$page_title = 'Privacy Policy';
include __DIR__ . '/../inc_header.php';
?>

<div class="legal-page-wrap">
    <div class="legal-page-header">
        <h2 class="legal-page-title">Privacy Policy</h2>
        <p class="legal-page-meta">Last updated: March 19, 2026</p>
    </div>

    <div class="legal-page-body">

        <p class="legal-intro">
            London Labels is committed to protecting your personal data. This Privacy Policy explains what
            information we collect, how we use it, who we share it with, and the rights available to you.
            It applies to all personal data collected through this website and in connection with your orders.
        </p>

        <h3>1. Who We Are</h3>
        <p>
            London Labels is a retail business operating from Lagos, Nigeria. Our physical store is located at
            <?= e(STORE_ADDRESS) ?>. We are the data controller for personal data collected through this website.
        </p>
        <p>
            For privacy-related enquiries, contact us at
            <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a> or via our
            <a href="<?= BASE_URL ?>/contact.php">contact form</a>.
        </p>

        <h3>2. What Data We Collect</h3>
        <p>We collect the following categories of personal data:</p>
        <ul class="legal-list">
            <li><strong>Account data:</strong> Username, email address, and hashed password when you create an account</li>
            <li><strong>Profile data:</strong> First name, last name, phone number, and delivery address when you provide them</li>
            <li><strong>Order data:</strong> Items purchased, order value, delivery address, payment method, and order status</li>
            <li><strong>Communication data:</strong> Messages you send us via the contact form, including your name, email, subject, and message content</li>
            <li><strong>Technical data:</strong> IP address, browser type, and session information collected automatically when you use the site</li>
            <li><strong>Security data:</strong> Session tokens and security identifiers used to protect your account and reduce repeated authentication prompts</li>
        </ul>
        <p>
            We do not collect payment card numbers or full bank account details. Payment transactions are
            handled securely and we only retain payment method type and status.
        </p>

        <h3>3. How We Use Your Data</h3>
        <p>We use your personal data for the following purposes:</p>
        <ul class="legal-list">
            <li>To create and manage your account</li>
            <li>To process and fulfil your orders, including arranging delivery</li>
            <li>To send order confirmations, status updates, and delivery notifications</li>
            <li>To respond to your enquiries and support requests</li>
            <li>To send you a verification email when you register or change your email address</li>
            <li>To provide two-factor authentication codes for account security</li>
            <li>To send you a password reset link when requested</li>
            <li>To detect and prevent fraud, abuse, and security incidents</li>
            <li>To comply with our legal obligations</li>
        </ul>
        <p>
            If you have opted in to promotional communications, we may also contact you with offers and
            updates. You can opt out at any time in your
            <a href="<?= BASE_URL ?>/account/edit-profile.php">profile settings</a>.
        </p>

        <h3>4. Legal Basis for Processing</h3>
        <p>
            We process your personal data under the Nigeria Data Protection Regulation (NDPR) on the
            following legal bases:
        </p>
        <ul class="legal-list">
            <li><strong>Contract performance:</strong> Processing necessary to fulfil your orders and manage your account</li>
            <li><strong>Legitimate interests:</strong> Security monitoring, fraud prevention, and improving our service</li>
            <li><strong>Legal obligation:</strong> Where we are required to retain or disclose data by law</li>
            <li><strong>Consent:</strong> For optional communications such as promotional emails, where you have opted in</li>
        </ul>

        <h3>5. Cookies</h3>
        <p>
            We use only essential and functional cookies. We do not use advertising cookies, tracking pixels,
            or third-party analytics services. For full details, see our
            <a href="<?= BASE_URL ?>/legal/cookies.php">Cookie Policy</a>.
        </p>

        <h3>6. Who We Share Your Data With</h3>
        <p>
            We do not sell your personal data. We may share it with the following categories of third parties
            only where necessary:
        </p>
        <ul class="legal-list">
            <li><strong>Delivery partners:</strong> Your name, phone number, and delivery address are shared with courier services to fulfil your order</li>
            <li><strong>Email service provider:</strong> We use Mailtrap SMTP to send transactional emails (order confirmations, password resets, etc.). Your email address and name are processed by this service</li>
            <li><strong>Hosting provider:</strong> Our website and database are hosted on infrastructure that processes data on our behalf</li>
        </ul>
        <p>
            All third parties we work with are required to handle your data securely and only for the
            purposes we specify.
        </p>

        <h3>7. Data Retention</h3>
        <p>
            We retain your personal data for as long as necessary to provide our services and comply with
            legal obligations:
        </p>
        <ul class="legal-list">
            <li><strong>Account data:</strong> Retained while your account is active. You may request deletion at any time</li>
            <li><strong>Order data:</strong> Retained for a minimum of 6 years for tax and legal compliance purposes</li>
            <li><strong>Contact messages:</strong> Retained for up to 2 years to maintain a record of support interactions</li>
            <li><strong>Security tokens:</strong> Session and authentication tokens expire after a short period. Password reset tokens expire after 30 minutes</li>
        </ul>

        <h3>8. Data Security</h3>
        <p>
            We take reasonable technical and organisational measures to protect your personal data, including:
        </p>
        <ul class="legal-list">
            <li>Passwords are stored as one-way hashed values — we cannot read your password</li>
            <li>All data transmission is encrypted over HTTPS</li>
            <li>Session tokens and CSRF tokens are used to protect your account and form submissions</li>
        </ul>
        <p>
            No method of transmission over the internet is completely secure. While we do our best to
            protect your data, we cannot guarantee absolute security.
        </p>

        <h3>9. Your Rights Under the NDPR</h3>
        <p>
            Under the Nigeria Data Protection Regulation (NDPR), you have the following rights regarding
            your personal data:
        </p>
        <ul class="legal-list">
            <li><strong>Right of access:</strong> Request a copy of the personal data we hold about you</li>
            <li><strong>Right to rectification:</strong> Request correction of inaccurate or incomplete data</li>
            <li><strong>Right to erasure:</strong> Request deletion of your personal data where there is no lawful reason to retain it</li>
            <li><strong>Right to restriction:</strong> Request that we limit how we use your data in certain circumstances</li>
            <li><strong>Right to data portability:</strong> Request your data in a structured, machine-readable format</li>
            <li><strong>Right to object:</strong> Object to processing based on legitimate interests or for direct marketing</li>
            <li><strong>Right to withdraw consent:</strong> Where processing is based on consent, you may withdraw it at any time</li>
        </ul>
        <p>
            To exercise any of these rights, please
            <a href="<?= BASE_URL ?>/contact.php">contact us via our contact form</a>
            or email <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>.
            We will respond within 30 days.
        </p>

        <h3>10. Children's Privacy</h3>
        <p>
            Our website is not directed at children under the age of 18. We do not knowingly collect
            personal data from children. If you believe a child has provided us with personal data,
            please contact us and we will delete it promptly.
        </p>

        <h3>11. Third-Party Links</h3>
        <p>
            Our website contains links to third-party services such as Google Maps and WhatsApp. These
            services have their own privacy policies and we are not responsible for their data practices.
            Clicking those links is at your own discretion.
        </p>

        <h3>12. Changes to This Policy</h3>
        <p>
            We may update this Privacy Policy from time to time. Any changes will be posted on this page
            with an updated date. We encourage you to review this page periodically. Continued use of the
            website after changes are posted constitutes acceptance of the revised policy.
        </p>

        <h3>13. Contact Us</h3>
        <p>
            If you have any questions about this Privacy Policy or how we handle your data, please
            <a href="<?= BASE_URL ?>/contact.php">contact us via our contact form</a>
            or email <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>.
        </p>
        <p>
            <?= e(STORE_ADDRESS) ?>
        </p>

    </div>
</div>

<?php include __DIR__ . '/../inc_footer.php'; ?>
