<?php
/**
 * London Labels - Edit Profile
 */
require_once __DIR__ . '/../functions.php';

$page_title    = 'Edit Profile';
$account_page  = 'profile';
$errors        = [];
$notice        = '';

require_login();

$user           = get_user_by_id(current_user_id());
$original_email = (string)($user['email'] ?? '');

// Populate fields from DB (or POST on validation failure)
$first_name   = $user['first_name']               ?? '';
$last_name    = $user['last_name']                ?? '';
$email        = $user['email']                    ?? '';
$phone        = $user['phone']                    ?? '';
$address      = $user['default_shipping_address'] ?? '';
$address_line2 = $user['default_address_line2']   ?? '';
$city         = $user['default_city']             ?? '';
$state        = $user['default_state']            ?? '';
$postal_code  = $user['default_postal_code']      ?? '';
$country      = $user['default_country']          ?? 'Nigeria';
$del_notes    = $user['delivery_notes']           ?? '';
$comm_orders  = !isset($user['comm_order_updates']) || (int)$user['comm_order_updates'] === 1;
$comm_promos  = isset($user['comm_promos']) && (int)$user['comm_promos'] === 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $first_name  = trim($_POST['first_name']  ?? '');
        $last_name   = trim($_POST['last_name']   ?? '');
        $email       = trim($_POST['email']       ?? '');
        $phone       = trim($_POST['phone']       ?? '');
        $address     = trim($_POST['address']     ?? '');
        $address_line2 = trim($_POST['address_line2'] ?? '');
        $city        = trim($_POST['city']        ?? '');
        $state       = trim($_POST['state']       ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $country     = trim($_POST['country']     ?? 'Nigeria');
        $del_notes   = trim($_POST['delivery_notes'] ?? '');
        $comm_orders = isset($_POST['comm_order_updates']);
        $comm_promos = isset($_POST['comm_promos']);

        // Validate
        validate_email($email, $errors);
        if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number (7–20 digits).';
        }
        $existing = get_user_by_email($email);
        if ($existing && (int)$existing['user_id'] !== (int)current_user_id()) {
            $errors[] = 'That email address is already in use by another account.';
        }

        if (empty($errors)) {
            $data = [
                'username'                 => $user['username'],
                'email'                    => $email,
                'first_name'               => $first_name  !== '' ? $first_name  : null,
                'last_name'                => $last_name   !== '' ? $last_name   : null,
                'phone'                    => $phone       !== '' ? $phone       : null,
                'default_shipping_address' => $address     !== '' ? $address     : null,
                'default_address_line2'    => $address_line2 !== '' ? $address_line2 : null,
                'default_city'             => $city        !== '' ? $city        : null,
                'default_state'            => $state       !== '' ? $state       : null,
                'default_postal_code'      => $postal_code !== '' ? $postal_code : null,
                'default_country'          => $country     !== '' ? $country     : null,
                'delivery_notes'           => $del_notes   !== '' ? $del_notes   : null,
                'comm_order_updates'       => $comm_orders,
                'comm_promos'              => $comm_promos,
            ];

            if (update_user_helper(current_user_id(), $data, $errors)) {
                $redirect = BASE_URL . '/account/profile.php?saved=1';
                if (strcasecmp($original_email, $email) !== 0) {
                    revoke_all_trusted_devices_for_user((int)current_user_id());
                    $redirect = BASE_URL . '/account/profile.php?saved=email';
                }
                header('Location: ' . $redirect);
                exit;
            }
        }
    }
}

include __DIR__ . '/../inc_header.php';
include __DIR__ . '/inc_account_layout.php';
?>

<div class="account-page-head">
    <h2 class="account-page-title">Edit Profile</h2>
</div>

<?php if (!empty($errors)): ?>
    <div class="account-alert account-alert-error" role="alert">
        <?php foreach ($errors as $err): ?>
            <p><?= e($err) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($notice): ?>
    <div class="account-alert account-alert-success" role="status"><?= e($notice) ?></div>
<?php endif; ?>

<form method="post" class="account-edit-form" novalidate autocomplete="on">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <!-- Personal Information -->
    <div class="account-card">
        <h3>Personal Information</h3>

        <div class="account-edit-grid">
            <div class="account-edit-field">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name"
                    value="<?= e($first_name) ?>"
                    autocomplete="given-name"
                    placeholder="Your first name">
            </div>
            <div class="account-edit-field">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name"
                    value="<?= e($last_name) ?>"
                    autocomplete="family-name"
                    placeholder="Your last name">
            </div>
        </div>

        <div class="account-edit-field">
            <label for="email">Email Address <span class="account-edit-required">*</span></label>
            <input type="email" id="email" name="email"
                value="<?= e($email) ?>"
                required
                autocomplete="email"
                placeholder="your@email.com">
            <span class="account-edit-hint">Order confirmations are sent to this address. Changing it will sign you out of other devices.</span>
        </div>

        <div class="account-edit-field">
            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone"
                value="<?= e($phone) ?>"
                autocomplete="tel"
                inputmode="tel"
                placeholder="+234 800 000 0000">
            <span class="account-edit-hint">Used for delivery updates. Optional.</span>
        </div>
    </div>

    <!-- Delivery Address -->
    <div class="account-card">
        <h3>Delivery Address</h3>
        <p class="account-card-subtitle">Save your delivery address once and we'll fill it in at checkout.</p>

        <div class="account-edit-field">
            <label for="address">Street Address</label>
            <input type="text" id="address" name="address"
                value="<?= e($address) ?>"
                autocomplete="street-address"
                placeholder="e.g. 12 Admiralty Way, Lekki Phase 1">
        </div>

        <div class="account-edit-field">
            <label for="address_line2">Address Line 2 <span class="account-edit-hint-inline">(optional)</span></label>
            <input type="text" id="address_line2" name="address_line2"
                value="<?= e($address_line2) ?>"
                autocomplete="address-line2"
                placeholder="e.g. Block C, 4th Floor, Flat 12">
        </div>

        <div class="account-edit-grid">
            <div class="account-edit-field">
                <label for="city">City / Town</label>
                <input type="text" id="city" name="city"
                    value="<?= e($city) ?>"
                    autocomplete="address-level2"
                    placeholder="e.g. Lekki, Victoria Island, Abuja">
            </div>
            <div class="account-edit-field">
                <label for="state">State</label>
                <select id="state" name="state" autocomplete="address-level1">
                    <option value="">Select state</option>
                    <?php
                        $ng_states = [
                            'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue',
                            'Borno','Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu',
                            'FCT - Abuja','Gombe','Imo','Jigawa','Kaduna','Kano','Katsina',
                            'Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo',
                            'Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara',
                        ];
                        foreach ($ng_states as $s):
                    ?>
                        <option value="<?= e($s) ?>" <?= $state === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                    <option value="Other" <?= $state === 'Other' ? 'selected' : '' ?>>Other (outside Nigeria)</option>
                </select>
            </div>
        </div>

        <div class="account-edit-grid">
            <div class="account-edit-field">
                <label for="country">Country</label>
                <select id="country" name="country" autocomplete="country-name">
                    <?php
                        $countries = ['Nigeria', 'Ghana', 'Kenya', 'South Africa', 'United Kingdom', 'United States', 'Canada', 'Other'];
                        foreach ($countries as $c):
                    ?>
                        <option value="<?= e($c) ?>" <?= $country === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="account-edit-field">
                <label for="postal_code">Postal Code <span class="account-edit-hint-inline">(optional)</span></label>
                <input type="text" id="postal_code" name="postal_code"
                    value="<?= e($postal_code) ?>"
                    autocomplete="postal-code"
                    inputmode="numeric"
                    placeholder="e.g. 100001">
                <span class="account-edit-hint">Nigerian postal codes are rarely required but you can add one if you know it.</span>
            </div>
        </div>

        <div class="account-edit-field">
            <label for="delivery_notes">Delivery Notes <span class="account-edit-hint-inline">(optional)</span></label>
            <textarea id="delivery_notes" name="delivery_notes"
                rows="3"
                autocomplete="off"
                placeholder="Add any delivery note you want."><?= e($del_notes) ?></textarea>
        </div>
    </div>

    <!-- Communication Preferences -->
    <div class="account-card" id="communication">
        <h3>Communication Preferences</h3>

        <div class="account-edit-checks">
            <label class="account-edit-check">
                <input type="checkbox" name="comm_order_updates" value="1" <?= $comm_orders ? 'checked' : '' ?>>
                <div>
                    <strong>Order &amp; Delivery Updates</strong>
                    <span>Notifications about your orders, dispatch, and delivery status.</span>
                </div>
            </label>
            <label class="account-edit-check">
                <input type="checkbox" name="comm_promos" value="1" <?= $comm_promos ? 'checked' : '' ?>>
                <div>
                    <strong>New Arrivals &amp; Offers</strong>
                    <span>Be the first to hear about new collections, exclusive deals, and restocks.</span>
                </div>
            </label>
        </div>
    </div>

    <div class="account-edit-actions">
        <button type="submit" class="btn primary">Save Changes</button>
        <a href="<?= BASE_URL ?>/account/profile.php" class="btn">Cancel</a>
    </div>

</form>

<div class="account-danger-zone">
    <p>Need to delete your account? <a href="<?= BASE_URL ?>/account/delete-account.php">Delete account</a></p>
</div>

    </div><!-- /.account-content -->
</div><!-- /.account-shell -->

<?php include __DIR__ . '/../inc_footer.php'; ?>
