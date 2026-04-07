<?php
/**
 * London Labels - Paystack Webhook
 * Handles server-to-server payment events from Paystack.
 * Register this URL in your Paystack dashboard:
 * https://dashboard.paystack.com/#/settings/developer → Webhooks
 * URL: https://yourdomain.com/paystack-webhook.php
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Read raw body
$body = file_get_contents('php://input');
if ($body === false || $body === '') {
    http_response_code(400);
    exit;
}

// Verify Paystack signature
$sig = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
$expected = hash_hmac('sha512', $body, PAYSTACK_SECRET_KEY);
if (!hash_equals($expected, $sig)) {
    http_response_code(401);
    error_log('Paystack webhook: invalid signature');
    exit;
}

$event = json_decode($body, true);
if (!is_array($event)) {
    http_response_code(400);
    exit;
}

// Acknowledge immediately — Paystack expects a 200 fast
http_response_code(200);

// Only handle successful charge events
if (($event['event'] ?? '') !== 'charge.success') {
    exit;
}

$data      = $event['data'] ?? [];
$reference = trim((string)($data['reference'] ?? ''));
$status    = (string)($data['status'] ?? '');

if ($reference === '' || $status !== 'success') {
    exit;
}

// Check if order already exists for this reference (idempotency)
$pdo  = get_pdo();
$stmt = $pdo->prepare('SELECT order_id FROM Orders WHERE paystack_reference = ? LIMIT 1');

// Ensure column exists
try {
    $stmt->execute([$reference]);
    if ($stmt->fetchColumn()) {
        // Already processed
        exit;
    }
} catch (PDOException $e) {
    // Column doesn't exist yet — add it
    $pdo->exec('ALTER TABLE Orders ADD COLUMN paystack_reference VARCHAR(100) NULL, ADD INDEX idx_orders_paystack_ref (paystack_reference)');
    // Re-check
    $stmt = $pdo->prepare('SELECT order_id FROM Orders WHERE paystack_reference = ? LIMIT 1');
    $stmt->execute([$reference]);
    if ($stmt->fetchColumn()) {
        exit;
    }
}

// Look for a pending order that was created by the callback but not yet confirmed,
// OR build from metadata if the callback never fired
$meta     = $data['metadata'] ?? [];
$user_id  = (int)($meta['user_id'] ?? 0);
$email    = (string)($data['customer']['email'] ?? '');
$amount   = (float)($data['amount'] ?? 0) / 100; // convert kobo to naira

if ($user_id <= 0 || $email === '') {
    error_log('Paystack webhook: missing user_id or email in metadata for ref ' . $reference);
    exit;
}

// Check if callback already created the order — if so, just stamp the reference
$stmt = $pdo->prepare('
    SELECT order_id FROM Orders
    WHERE user_id = ? AND payment_method = ? AND payment_status = ? AND paystack_reference IS NULL
    ORDER BY order_date DESC LIMIT 1
');
$stmt->execute([$user_id, 'paystack', 'paid']);
$existing = $stmt->fetchColumn();

if ($existing) {
    $pdo->prepare('UPDATE Orders SET paystack_reference = ? WHERE order_id = ?')
        ->execute([$reference, (int)$existing]);
    exit;
}

// Callback never fired — we need to create the order from session data stored in metadata
// Paystack metadata can carry arbitrary fields; we stored shipping in the checkout initialise call
// If not present, log for manual review
$fullname = (string)($meta['fullname'] ?? '');
$phone    = (string)($meta['phone']    ?? '');

if ($fullname === '' || $phone === '') {
    error_log('Paystack webhook: cannot auto-create order — missing shipping metadata. Ref: ' . $reference . ' User: ' . $user_id);
    exit;
}

try {
    $order_id = create_order(
        $user_id,
        $amount,
        '', // address not in metadata — flag for manual follow-up
        '',
        null,
        $phone,
        'paystack',
        'paid'
    );

    $pdo->prepare('UPDATE Orders SET paystack_reference = ? WHERE order_id = ?')
        ->execute([$reference, $order_id]);

    // Note: item-level metadata not stored — stock decrement skipped for webhook-only orders.
    // These are edge cases where the callback never fired; flag for manual stock review.
    error_log('Paystack webhook: created order #' . $order_id . ' (no items metadata) for ref ' . $reference);

    // Send confirmation email
    try {
        send_order_confirmation_email($email, $fullname, $order_id, [], $amount);
    } catch (Exception $e) {
        error_log('Webhook order email failed: ' . $e->getMessage());
    }

} catch (Exception $e) {
    error_log('Paystack webhook: order creation failed for ref ' . $reference . ' — ' . $e->getMessage());
}
