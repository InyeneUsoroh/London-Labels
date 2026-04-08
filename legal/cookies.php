<?php
require_once __DIR__ . '/../functions.php';
$page_title = 'Cookie Policy';
include __DIR__ . '/../inc_header.php';
?>

<div class="legal-page-wrap">
    <div class="legal-page-header">
        <h2 class="legal-page-title">Cookie Policy</h2>
        <p class="legal-page-meta">Last updated: March 19, 2026</p>
    </div>

    <div class="legal-page-body">

        <p class="legal-intro">
            This Cookie Policy explains what cookies are, which cookies London Labels uses on this website,
            and how you can manage them. By continuing to use our site, you consent to our use of cookies
            as described below.
        </p>

        <h3>What Are Cookies?</h3>
        <p>
            Cookies are small text files stored on your device (computer, phone, or tablet) when you visit a website.
            They allow the site to remember information about your visit — such as your login session or cart contents —
            so you don't have to re-enter it every time you return.
        </p>

        <h3>Cookies We Use</h3>
        <p>
            London Labels only uses cookies that are strictly necessary for the website to function. We do not use
            advertising cookies, tracking pixels, or third-party analytics services.
        </p>

        <div class="legal-table-wrap">
            <table class="legal-table">
                <thead>
                    <tr>
                        <th>Cookie</th>
                        <th>Purpose</th>
                        <th>Type</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>PHPSESSID</code></td>
                        <td>Maintains your login session and cart across pages</td>
                        <td>Essential</td>
                        <td>Session (deleted when browser closes)</td>
                    </tr>
                    <tr>
                        <td><code>ll_trusted_device</code></td>
                        <td>Remembers a verified device to reduce repeated sign-in verification prompts</td>
                        <td>Functional / Security</td>
                        <td>30 days</td>
                    </tr>
                    <tr>
                        <td><code>(session CSRF token)</code></td>
                        <td>Protects forms against cross-site request forgery using a server-side session token</td>
                        <td>Essential / Security</td>
                        <td>Session</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h3>What We Do Not Use</h3>
        <ul class="legal-list">
            <li>We do not use Google Analytics, Facebook Pixel, or any third-party tracking tools</li>
            <li>We do not use advertising or retargeting cookies</li>
            <li>We do not sell or share cookie data with third parties</li>
            <li>We do not use cookies to build profiles or track you across other websites</li>
        </ul>

        <h3>Third-Party Services</h3>
        <p>
            Our site links to external services such as Google Maps (for store directions) and WhatsApp.
            If you click those links, those services may set their own cookies subject to their own privacy policies.
            We have no control over those cookies and they are not set by London Labels.
        </p>

        <h3>Managing Cookies</h3>
        <p>
            You can control and delete cookies through your browser settings. The links below explain how to
            manage cookies in the most common browsers:
        </p>
        <ul class="legal-list">
            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener noreferrer">Google Chrome</a></li>
            <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" target="_blank" rel="noopener noreferrer">Mozilla Firefox</a></li>
            <li><a href="https://support.apple.com/en-gb/guide/safari/sfri11471/mac" target="_blank" rel="noopener noreferrer">Apple Safari</a></li>
            <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge-63947406-40ac-c3b8-57b9-2a946a29ae09" target="_blank" rel="noopener noreferrer">Microsoft Edge</a></li>
        </ul>
        <p>
            Please note that disabling essential cookies (such as <code>PHPSESSID</code>) will prevent you from
            signing in, maintaining your cart, or completing a purchase.
        </p>

        <h3>Regulatory Framework</h3>
        <p>
            London Labels operates primarily in Nigeria and complies with the Nigeria Data Protection Regulation (NDPR)
            issued by the National Information Technology Development Agency (NITDA). Our use of cookies is limited
            to what is necessary to provide our service, consistent with the NDPR's data minimisation principle.
        </p>

        <h3>Changes to This Policy</h3>
        <p>
            We may update this Cookie Policy from time to time. Any changes will be posted on this page with an
            updated date. We encourage you to review this page periodically.
        </p>

        <h3>Contact Us</h3>
        <p>
            If you have any questions about how we use cookies, please contact us at
            <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>
            or visit us at <?= e(STORE_ADDRESS) ?>.
        </p>

    </div>
</div>

<?php include __DIR__ . '/../inc_footer.php'; ?>
