<?php
require_once __DIR__ . '/../functions.php';
$page_title = 'Terms of Service';
include __DIR__ . '/../inc_header.php';
?>

<div class="legal-page-wrap">
    <div class="legal-page-header">
        <h2 class="legal-page-title">Terms of Service</h2>
        <p class="legal-page-meta">Last updated: March 19, 2026</p>
    </div>

    <div class="legal-page-body">

        <p class="legal-intro">
            Please read these Terms of Service carefully before using the London Labels website or placing an order.
            By accessing our site or making a purchase, you agree to be bound by these terms.
            If you do not agree, please do not use our website.
        </p>

        <h3>1. About London Labels</h3>
        <p>
            London Labels is a retail business operating from Lagos, Nigeria, selling fashion and lifestyle products
            sourced from the United Kingdom and internationally. Our physical store is located at
            <?= e(STORE_ADDRESS) ?>.
        </p>

        <h3>2. Eligibility</h3>
        <p>
            By using this website, you confirm that you are at least 18 years old, or are using the site under the
            supervision of a parent or legal guardian. You must provide accurate, current, and complete information
            when creating an account or placing an order.
        </p>

        <h3>3. Your Account</h3>
        <p>
            When you create an account, you are responsible for maintaining the confidentiality of your login
            credentials and for all activity that occurs under your account. You must notify us immediately if you
            suspect unauthorised access to your account.
        </p>
        <p>
            We reserve the right to suspend or terminate accounts that violate these terms, engage in fraudulent
            activity, or are used in a manner that harms other users or our business.
        </p>

        <h3>4. Products and Pricing</h3>
        <p>
            All prices are displayed in Nigerian Naira (&#8358;) and are inclusive of applicable taxes unless
            otherwise stated. We reserve the right to change prices at any time without prior notice. Price changes
            will not affect orders that have already been confirmed.
        </p>
        <p>
            We make every effort to display product descriptions and images accurately. However, we do not warrant
            that descriptions are error-free or that images perfectly represent the physical product. If a product
            you receive is materially different from its description, please contact us.
        </p>
        <p>
            Product availability is not guaranteed. If an item becomes unavailable after your order is placed,
            we will notify you and offer a full refund or an alternative where possible.
        </p>

        <h3>5. Orders and Payment</h3>
        <p>
            Placing an order constitutes an offer to purchase. Your order is confirmed when you receive an order
            confirmation email from us. We reserve the right to refuse or cancel any order at our discretion,
            including in cases of pricing errors, suspected fraud, or stock unavailability.
        </p>
        <p>
            Payment is due at the time of order. We use Paystack as our payment processor — payments are handled securely and we do not store card or bank details on our servers.
        </p>

        <h3>6. Delivery</h3>
        <p>
            All products are sourced from the United Kingdom and stocked at our physical store in Lagos, Nigeria.
            All orders are fulfilled and dispatched from our Lagos store — we do not ship directly from the UK
            to customers.
        </p>
        <p>
            We deliver statewide within Lagos and nationwide across Nigeria. Delivery fees and estimated
            timeframes are shown at checkout and vary by destination. Estimated delivery times are indicative
            only and may vary due to courier availability or circumstances beyond our control.
        </p>
        <p>
            You may also collect your order in person from our store at <?= e(STORE_ADDRESS) ?>.
            Risk of loss and title for items pass to you upon delivery or collection.
        </p>

        <h3>7. Cancellations</h3>
        <p>
            You may request to cancel an order within 24 hours of placement, provided it has not yet been
            prepared for delivery or collected by a courier. Once an order is out for delivery, it cannot be
            cancelled. Please <a href="<?= BASE_URL ?>/contact.php">contact us</a> as soon as possible if you need to cancel.
        </p>

        <h3>8. Prohibited Use</h3>
        <p>You agree not to use this website to:</p>
        <ul class="legal-list">
            <li>Engage in any unlawful, fraudulent, or harmful activity</li>
            <li>Impersonate any person or entity, or misrepresent your affiliation with any person or entity</li>
            <li>Attempt to gain unauthorised access to any part of the website or its systems</li>
            <li>Transmit any unsolicited commercial communications or spam</li>
            <li>Scrape, crawl, or systematically extract data from the website without our written consent</li>
            <li>Interfere with or disrupt the integrity or performance of the website</li>
        </ul>

        <h3>9. Intellectual Property</h3>
        <p>
            All content on this website — including text, images, graphics, logos, and design — is owned by
            London Labels or used under licence. You may not reproduce, republish, distribute, or commercially
            exploit any content without our prior written permission.
        </p>
        <p>
            "London Labels" and related marks are proprietary identifiers of the business. Third-party brand names
            and marks referenced on this site remain the property of their respective owners.
        </p>

        <h3>10. Third-Party Links and Services</h3>
        <p>
            Our website may contain links to third-party websites or services (such as Google Maps or WhatsApp).
            These are provided for convenience only. We have no control over their content or practices and accept
            no responsibility for them. Visiting third-party sites is at your own risk.
        </p>

        <h3>11. Limitation of Liability</h3>
        <p>
            To the fullest extent permitted by applicable law, London Labels shall not be liable for any indirect,
            incidental, special, or consequential damages arising from your use of this website or any products
            purchased through it. Our total liability to you for any claim shall not exceed the amount you paid
            for the relevant order.
        </p>
        <p>
            Nothing in these terms limits our liability for death or personal injury caused by our negligence,
            fraud, or any other liability that cannot be excluded by law.
        </p>

        <h3>12. Disclaimer of Warranties</h3>
        <p>
            This website and its content are provided on an "as is" basis. We make no warranties, express or
            implied, regarding the accuracy, completeness, or fitness for purpose of any content or product
            information on this site, except as required by applicable law.
        </p>

        <h3>13. Privacy and Data Protection</h3>
        <p>
            Your use of this website is also governed by our
            <a href="<?= BASE_URL ?>/legal/privacy.php">Privacy Policy</a>.
            We process personal data in accordance with the Nigeria Data Protection Regulation (NDPR) and
            applicable data protection law.
        </p>

        <h3>14. Governing Law</h3>
        <p>
            These Terms of Service are governed by and construed in accordance with the laws of the Federal
            Republic of Nigeria. Any disputes arising from these terms or your use of our website shall be
            subject to the jurisdiction of courts of competent jurisdiction in Nigeria.
        </p>

        <h3>15. Changes to These Terms</h3>
        <p>
            We may update these Terms of Service from time to time. Changes will be posted on this page with an
            updated date. Continued use of the website after changes are posted constitutes your acceptance of
            the revised terms.
        </p>

        <h3>16. Contact Us</h3>
        <p>
            If you have any questions about these Terms of Service, please
            <a href="<?= BASE_URL ?>/contact.php">contact us via our contact form</a>
            or email <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>.
        </p>
        <p>
            <?= e(STORE_ADDRESS) ?>
        </p>

    </div>
</div>

<?php include __DIR__ . '/../inc_footer.php'; ?>
