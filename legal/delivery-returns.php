<?php
require_once __DIR__ . '/../functions.php';
$page_title = 'Delivery and Returns Policy';
include __DIR__ . '/../inc_header.php';
?>

<div class="legal-page-wrap">
    <div class="legal-page-header">
        <h2 class="legal-page-title">Delivery and Returns Policy</h2>
        <p class="legal-page-meta">Last updated: April 01, 2026</p>
    </div>

    <div class="legal-page-body">
        <p class="legal-intro">
            This page explains how London Labels handles delivery, pickup, and returns.
            We deliver only within Nigeria and do not offer international shipping.
            It is intended to provide clear expectations before and after purchase.
        </p>

        <h3>1. Delivery Coverage</h3>
        <p>
            We deliver within Lagos and nationwide across Nigeria. Delivery options and applicable fees are
            shown at checkout before payment is completed.
        </p>

        <h3>2. Delivery Timeframes</h3>
        <p>
            Typical delivery windows are shown at checkout and may vary by destination, courier service,
            order volume, weather, or other operational factors.
        </p>
        <ul class="legal-list">
            <li><strong>Lagos:</strong> Faster local delivery options where available</li>
            <li><strong>Outside Lagos:</strong> Nationwide delivery with destination-based timelines</li>
        </ul>

        <h3>3. Pickup Option</h3>
        <p>
            Pickup may be available for selected orders. Where available, pickup details will be communicated
            after order confirmation.
        </p>

        <h3>4. Delivery Fees</h3>
        <p>
            Delivery fees are destination-based and displayed at checkout before you pay. We do not apply
            hidden shipping charges after payment.
        </p>

        <h3>5. Returns Eligibility</h3>
        <p>
            Eligible items can be returned within 14 days of delivery, provided they are unused, in original
            condition, and have all tags/packaging intact.
        </p>

        <h3>6. Non-Returnable Cases</h3>
        <ul class="legal-list">
            <li>Items that have been worn, used, altered, or damaged after delivery</li>
            <li>Items returned without original tags where tags were provided</li>
            <li>Items outside the return window unless required by law</li>
        </ul>

        <h3>7. Return Shipping Responsibility</h3>
        <p>
            Return shipping is paid by the customer unless the item received is faulty, damaged in transit,
            or materially different from what was ordered.
        </p>

        <h3>8. Damaged or Incorrect Items</h3>
        <p>
            If your order arrives damaged or incorrect, contact us within 24 hours of delivery with clear photos
            and your order details so we can resolve the issue quickly.
        </p>

        <h3>9. Refund Processing</h3>
        <p>
            Once a return is inspected and approved, refunds are processed to the original payment channel where
            possible. Processing timelines may vary by payment provider.
        </p>

        <h3>10. Need Help?</h3>
        <p>
            For delivery or returns support, please
            <a href="<?= BASE_URL ?>/contact.php">contact us via our contact form</a>
            or email <a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a>.
        </p>
        <p>
            <?= e(STORE_ADDRESS) ?>
        </p>
    </div>
</div>

<?php include __DIR__ . '/../inc_footer.php'; ?>
