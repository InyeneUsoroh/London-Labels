<?php
/**
 * London Labels - Contact Page
 */
require_once __DIR__ . '/functions.php';

$page_title = 'Contact Us';
$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (strlen($name) < 2) {
            $errors[] = 'Please provide your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }
        if (strlen($subject) < 3) {
            $errors[] = 'Please select a subject.';
        }
        if (strlen($message) < 10) {
            $errors[] = 'Message must be at least 10 characters.';
        }

        if (empty($errors)) {
            require_once __DIR__ . '/mailer.php';
            save_contact_message($name, $email, $subject, $message);
            $mailError = null;
            $sent = send_contact_notification($email, $name, $subject, $message, $mailError);
            $notice = $sent ? 'sent' : 'partial';
        }
    }
}

$name_error    = in_array('Please provide your name.', $errors, true);
$email_error   = in_array('Please provide a valid email address.', $errors, true);
$subject_error = in_array('Please select a subject.', $errors, true);
$message_error = in_array('Message must be at least 10 characters.', $errors, true);

include __DIR__ . '/inc_header.php';
?>

<div class="contact-page-wrap">

    <div class="contact-page-intro">
        <h2 class="contact-page-heading">Get in Touch</h2>
        <p class="contact-page-subheading">We're here to help. Whether it's an order question or just a hello — reach out and we'll get back to you. You might also find a quick answer in our <a href="<?= BASE_URL ?>/faq.php">FAQs</a>.</p>
    </div>

    <div class="contact-layout">

        <!-- LEFT: Form or success state -->
        <div class="contact-form-col">

            <?php if ($notice === 'sent' || $notice === 'partial'): ?>
                <div class="contact-success-state">
                    <h3 class="contact-success-title">Message Received</h3>
                    <p>Thanks for reaching out. We've received your message and will get back to you within 24–48 hours.</p>
                    <?php if ($notice === 'partial'): ?>
                        <p class="form-notice form-notice-warning" style="margin-top:20px; font-size: 14px;">
                            <strong>Note:</strong> We saved your message, but our email notification system is currently slow. We will still see your message in our records!
                        </p>
                    <?php endif; ?>
                    <p>In the meantime, you can also reach us on <a href="<?= e(WHATSAPP_GROUP_URL) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a> for a faster response.</p>
                </div>

            <?php else: ?>

                <?php if ($errors): ?>
                    <div class="form-notice form-notice-error" role="alert">
                        <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="form contact-form" novalidate autocomplete="on">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

                    <div class="form-group">
                        <label for="name">Your Name <span class="form-required">*</span></label>
                        <input
                            type="text" id="name" name="name" required
                            autocomplete="name"
                            value="<?= e($_POST['name'] ?? '') ?>"
                            <?= $name_error ? 'aria-invalid="true" aria-describedby="contact-name-error"' : '' ?>
                        >
                        <?php if ($name_error): ?>
                            <small id="contact-name-error" class="form-error-note">Please provide your name.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="form-required">*</span></label>
                        <input
                            type="email" id="email" name="email" required
                            autocomplete="email"
                            value="<?= e($_POST['email'] ?? '') ?>"
                            <?= $email_error ? 'aria-invalid="true" aria-describedby="contact-email-error"' : '' ?>
                        >
                        <?php if ($email_error): ?>
                            <small id="contact-email-error" class="form-error-note">Please provide a valid email address.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject <span class="form-required">*</span></label>
                        <select
                            id="subject" name="subject" required
                            <?= $subject_error ? 'aria-invalid="true" aria-describedby="contact-subject-error"' : '' ?>
                        >
                            <option value="" disabled <?= empty($_POST['subject']) ? 'selected' : '' ?>>Select a topic...</option>
                            <option value="Order Enquiry"      <?= ($_POST['subject'] ?? '') === 'Order Enquiry'      ? 'selected' : '' ?>>Order Enquiry</option>
                            <option value="Delivery Update"    <?= ($_POST['subject'] ?? '') === 'Delivery Update'    ? 'selected' : '' ?>>Delivery Update</option>
                            <option value="Product Question"   <?= ($_POST['subject'] ?? '') === 'Product Question'   ? 'selected' : '' ?>>Product Question</option>
                            <option value="Wholesale / Trade"  <?= ($_POST['subject'] ?? '') === 'Wholesale / Trade'  ? 'selected' : '' ?>>Wholesale / Trade</option>
                            <option value="Feedback"           <?= ($_POST['subject'] ?? '') === 'Feedback'           ? 'selected' : '' ?>>Feedback</option>
                            <option value="Other"              <?= ($_POST['subject'] ?? '') === 'Other'              ? 'selected' : '' ?>>Other</option>
                        </select>
                        <?php if ($subject_error): ?>
                            <small id="contact-subject-error" class="form-error-note">Please select a subject.</small>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="message">Message <span class="form-required">*</span></label>
                        <textarea
                            id="message" name="message" rows="6" required
                            <?= $message_error ? 'aria-invalid="true" aria-describedby="contact-message-error"' : '' ?>
                        ><?= e($_POST['message'] ?? '') ?></textarea>
                        <?php if ($message_error): ?>
                            <small id="contact-message-error" class="form-error-note">Message must be at least 10 characters.</small>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn primary btn-full">Send Message</button>
                </form>

            <?php endif; ?>
        </div>

        <!-- RIGHT: Contact info -->
        <div class="contact-info-col">

            <div class="contact-info-block">
                <h3 class="contact-info-heading">Contact Details</h3>

                <div class="contact-info-row">
                    <span class="contact-info-label">Email</span>
                    <a href="mailto:<?= e(CONTACT_EMAIL) ?>" class="contact-info-value"><?= e(CONTACT_EMAIL) ?></a>
                </div>

                <div class="contact-info-row">
                    <span class="contact-info-label">Phone</span>
                    <a href="<?= e(CONTACT_PHONE_HREF) ?>" class="contact-info-value"><?= e(CONTACT_PHONE) ?></a>
                </div>

                <div class="contact-info-row">
                    <span class="contact-info-label">Hours</span>
                    <span class="contact-info-value"><?= e(CUSTOMER_CARE_HOURS) ?>, Mon – Sat</span>
                </div>

                <div class="contact-info-row">
                    <span class="contact-info-label">Response</span>
                    <span class="contact-info-value">Within 24–48 hours</span>
                </div>
            </div>

            <?php if (defined('WHATSAPP_GROUP_URL') && WHATSAPP_GROUP_URL !== ''): ?>
            <div class="contact-whatsapp-block">
                <strong class="contact-whatsapp-title">Prefer a faster reply?</strong>
                <p class="contact-whatsapp-text">Join our WhatsApp community for quicker responses and updates on new arrivals.</p>
                <a href="<?= e(WHATSAPP_GROUP_URL) ?>" target="_blank" rel="noopener noreferrer" class="btn contact-whatsapp-btn">
                    Chat on WhatsApp
                </a>
            </div>
            <?php endif; ?>

            <div class="contact-info-block">
                <h3 class="contact-info-heading">Visit Our Store</h3>
                <p class="contact-store-address"><?= e(STORE_ADDRESS) ?></p>
                <a href="<?= e(STORE_MAP_URL) ?>" target="_blank" rel="noopener noreferrer" class="contact-map-link">Get Directions</a>
            </div>

            <div class="contact-map-wrap">
                <iframe
                    src="<?= e(STORE_MAP_EMBED_URL) ?>"
                    class="contact-map-frame"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="London Labels store location — Treasure Mall, Ajah, Lagos"
                ></iframe>
            </div>

        </div>
    </div>
</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
