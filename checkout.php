<?php
/**
 * London Labels - Checkout
 * Collects shipping details, then hands off to Paystack for payment.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/email_verification.php';

$page_title = 'Checkout';
$errors     = [];

// Payment failure redirects from callback
if (($_GET['payment'] ?? '') === 'failed') {
    $errors[] = 'Your payment could not be verified. Please try again.';
} elseif (($_GET['payment'] ?? '') === 'error') {
    $errors[] = 'We could not connect to the payment provider. Please try again.';
}

if (is_logged_in() && !is_email_verified()) {
    $_SESSION['checkout_redirect'] = true;
    header('Location: ' . BASE_URL . '/resend-verification.php');
    exit;
}

// Empty cart → back to shop
if (empty($_SESSION['cart'])) {
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

// Build cart
$cart_items = [];
$subtotal   = 0;

foreach ($_SESSION['cart'] as $product_id => $qty) {
    $product = get_product_by_id((int)$product_id);
    if (!$product) {
        $errors[] = 'One or more items in your cart are no longer available. Please update your cart.';
        continue;
    }

    $variants = get_product_variants((int)$product_id);
    $variant_id = (int)($_SESSION['cart_variant_ids'][$product_id] ?? 0);
    $size_label = trim((string)($_SESSION['cart_variants'][$product_id] ?? ''));
    $available_qty = max(0, (int)$product['quantity']);

    if (!empty($variants)) {
        if ($variant_id <= 0) {
            $errors[] = 'A size selection is missing for one of your items. Please update your cart.';
            continue;
        }

        $selected_variant = null;
        foreach ($variants as $variant) {
            if ((int)$variant['variant_id'] === $variant_id) {
                $selected_variant = $variant;
                break;
            }
        }

        if (!$selected_variant) {
            $errors[] = 'A selected size is no longer available. Please update your cart.';
            continue;
        }

        $available_qty = max(0, (int)$selected_variant['quantity']);
        if ($size_label === '') {
            $size_label = (string)$selected_variant['size'];
        }
    }

    if ($available_qty < $qty || $qty <= 0) {
        $errors[] = 'One or more items in your cart exceeded available stock. Please update your cart.';
        continue;
    }

    $line         = $product['price'] * $qty;
    $cart_items[] = [
        'product_id' => (int)$product_id,
        'variant_id' => $variant_id > 0 ? $variant_id : null,
        'size_label' => $size_label !== '' ? $size_label : null,
        'name'       => $product['name'],
        'price'      => $product['price'],
        'quantity'   => $qty,
        'subtotal'   => $line,
    ];
    $subtotal += $line;
}

// Pre-fill from profile
$user            = is_logged_in() ? get_user_by_id((int)current_user_id()) : null;
$default_name    = trim((string)($user['first_name'] ?? '') . ' ' . (string)($user['last_name'] ?? ''));
if ($default_name === '' && is_logged_in()) $default_name = (string)current_user_name();
$default_email   = is_logged_in() ? (string)current_user_email() : '';
$default_address = (string)($user['default_shipping_address'] ?? '');
$default_address_line2 = (string)($user['default_address_line2'] ?? '');
$default_city    = (string)($user['default_city'] ?? '');
$default_state   = (string)($user['default_state'] ?? '');
$default_phone   = (string)($user['phone'] ?? '');

// POST: validate and initialise Paystack
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Security token validation failed. Please try again.';
    } else {
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $address  = trim($_POST['address']  ?? '');
        $address_line2 = trim($_POST['address_line2'] ?? '');
        $city     = trim($_POST['city']     ?? '');
        $state    = trim($_POST['state']    ?? '');
        $phone    = trim($_POST['phone']    ?? '');

        if ($fullname === '')                              $errors[] = 'Full name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'A valid email address is required.';
        if ($address === '')                               $errors[] = 'Delivery address is required.';
        if ($city === '')                                  $errors[] = 'City is required.';
        if ($state === '')                                 $errors[] = 'State is required.';
        if ($phone === '')                                 $errors[] = 'Phone number is required.';
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number (7–20 digits).';
        }

        if (empty($errors)) {
            // Stash shipping details in session for callback to use
            $_SESSION['pending_order'] = [
                'user_id'  => is_logged_in() ? (int)current_user_id() : null,
                'fullname' => $fullname,
                'email'    => $email,
                'address'  => $address,
                'address_line2' => $address_line2,
                'city'     => $city,
                'state'    => $state,
                'phone'    => $phone,
                'items'    => $cart_items,
                'subtotal' => $subtotal,
            ];

            // Initialise Paystack transaction
            $amount_kobo = (int)round($subtotal * 100); // Paystack uses kobo
            $reference   = 'LL-' . time() . '-' . current_user_id();
            $callback    = BASE_URL . '/paystack-callback.php';

            $payload = json_encode([
                'email'     => $email,
                'amount'    => $amount_kobo,
                'reference' => $reference,
                'currency'  => 'NGN',
                'callback_url' => $callback,
                'metadata'  => [
                    'user_id'  => is_logged_in() ? current_user_id() : null,
                    'fullname' => $fullname,
                    'phone'    => $phone,
                ],
            ]);

            $ch = curl_init('https://api.paystack.co/transaction/initialize');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
                    'Content-Type: application/json',
                ],
            ]);
            $response = curl_exec($ch);
            $curl_err = curl_error($ch);

            if ($curl_err) {
                $errors[] = 'Could not connect to payment provider. Please try again.';
            } else {
                $data = json_decode($response, true);
                if (!empty($data['status']) && !empty($data['data']['authorization_url'])) {
                    $_SESSION['pending_order']['reference'] = $reference;
                    header('Location: ' . $data['data']['authorization_url']);
                    exit;
                } else {
                    $msg = $data['message'] ?? 'Payment initialisation failed. Please try again.';
                    $errors[] = $msg;
                }
            }
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<div class="checkout-wrap">

    <div class="checkout-main">

        <div class="checkout-page-head">
            <h2>Checkout</h2>
            <div class="checkout-steps" aria-label="Checkout steps">
                <span class="checkout-step done">Cart</span>
                <span class="checkout-step-sep"></span>
                <span class="checkout-step active">Details</span>
                <span class="checkout-step-sep"></span>
                <span class="checkout-step">Payment</span>
                <span class="checkout-step-sep"></span>
                <span class="checkout-step">Confirmation</span>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="checkout-notice checkout-notice-error" role="alert">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="checkout-form" novalidate>
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <h3 class="checkout-section-heading">Delivery Details</h3>

            <div class="checkout-form-row">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname"
                        value="<?= e($_POST['fullname'] ?? $default_name) ?>"
                        autocomplete="name" required placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                        value="<?= e($_POST['phone'] ?? $default_phone) ?>"
                        autocomplete="tel" inputmode="tel" required placeholder="e.g. 08012345678">
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email"
                    value="<?= e($_POST['email'] ?? $default_email) ?>"
                    autocomplete="email" required placeholder="Order confirmation will be sent here">
            </div>

            <div class="form-group">
                <label for="address">Delivery Address</label>
                <input type="text" id="address" name="address"
                    value="<?= e($_POST['address'] ?? $default_address) ?>"
                    autocomplete="street-address" required placeholder="House number, street name">
            </div>

            <div class="form-group">
                <label for="address_line2">Address Line 2 <span class="account-edit-hint-inline">(optional)</span></label>
                <input type="text" id="address_line2" name="address_line2"
                    value="<?= e($_POST['address_line2'] ?? $default_address_line2) ?>"
                    autocomplete="address-line2" placeholder="Building, floor, apartment, landmark">
            </div>

            <div class="checkout-form-row">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city"
                        value="<?= e($_POST['city'] ?? $default_city) ?>"
                        autocomplete="address-level2" required placeholder="e.g. Lagos">
                </div>
                <div class="form-group">
                    <label for="state">State</label>
                    <input type="text" id="state" name="state"
                        value="<?= e($_POST['state'] ?? $default_state) ?>"
                        autocomplete="address-level1" required placeholder="e.g. Lagos State">
                </div>
            </div>

            <div class="checkout-paystack-note">
                <p>You will be redirected to Paystack to complete your payment securely. We accept cards, bank transfers, and USSD.</p>
            </div>

            <button type="submit" class="btn primary checkout-pay-btn">
                Continue to Payment &mdash; <?= format_price($subtotal) ?>
            </button>

        </form>

    </div>

    <aside class="checkout-sidebar" aria-label="Order summary">
        <div class="checkout-summary-card">
            <h3>Order Summary</h3>
            <div class="checkout-summary-items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="checkout-summary-row">
                        <span>
                            <?= e($item['name']) ?>
                            <?php if (!empty($item['size_label'])): ?>
                                <span class="checkout-summary-qty">(<?= e((string)$item['size_label']) ?>)</span>
                            <?php endif; ?>
                            <span class="checkout-summary-qty">x<?= $item['quantity'] ?></span>
                        </span>
                        <span><?= format_price($item['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="checkout-summary-divider"></div>
            <div class="checkout-summary-row checkout-summary-row-total">
                <strong>Total</strong>
                <strong><?= format_price($subtotal) ?></strong>
            </div>
            <p class="checkout-summary-note">Delivery fee calculated at dispatch and communicated before shipping.</p>
        </div>
    </aside>

</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
