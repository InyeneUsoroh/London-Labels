<?php
/**
 * London Labels - Paystack Payment Callback
 * Verifies the transaction, creates the order, clears the cart.
 */
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/mailer.php';

$reference = trim($_GET['reference'] ?? '');

if ($reference === '') {
    header('Location: ' . BASE_URL . '/checkout.php');
    exit;
}

// Verify with Paystack
$ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
    ],
]);
$response = curl_exec($ch);
$curl_err  = curl_error($ch);

if ($curl_err) {
    error_log('Paystack verify curl error: ' . $curl_err);
    header('Location: ' . BASE_URL . '/checkout.php?payment=error');
    exit;
}

$data = json_decode($response, true);

// Must be success and reference must match session
$pending = $_SESSION['pending_order'] ?? null;

if (
    empty($data['status']) ||
    empty($data['data']['status']) ||
    $data['data']['status'] !== 'success' ||
    !$pending ||
    ($pending['reference'] ?? '') !== $reference
) {
    error_log('Paystack verification failed for reference: ' . $reference);
    header('Location: ' . BASE_URL . '/checkout.php?payment=failed');
    exit;
}

$paid_amount_kobo = (int)($data['data']['amount'] ?? 0);
$expected_amount_kobo = (int)round(((float)($pending['subtotal'] ?? 0)) * 100);
if ($paid_amount_kobo !== $expected_amount_kobo) {
    error_log('Paystack amount mismatch for reference: ' . $reference . ' paid=' . $paid_amount_kobo . ' expected=' . $expected_amount_kobo);
    header('Location: ' . BASE_URL . '/checkout.php?payment=failed');
    exit;
}

// Create the order
try {
    $pdo      = get_pdo();
    $pdo->beginTransaction();

    $shippingAddress = trim((string)($pending['address'] ?? ''));
    $addressLine2 = trim((string)($pending['address_line2'] ?? ''));
    if ($addressLine2 !== '') {
        $shippingAddress .= ', ' . $addressLine2;
    }

    $order_id = create_order(
        (int)$pending['user_id'],
        (float)$pending['subtotal'],
        $shippingAddress,
        $pending['city'],
        null,   // postal — not collected
        $pending['phone'],
        'paystack',
        'paid'
    );

    foreach ($pending['items'] as $item) {
        $pid = (int)$item['product_id'];
        $qty = max(1, (int)$item['quantity']);
        $variant_id = (int)($item['variant_id'] ?? 0);

        if ($variant_id > 0) {
            $lockVariant = $pdo->prepare('SELECT quantity FROM Product_Variants WHERE variant_id = ? AND product_id = ? FOR UPDATE');
            $lockVariant->execute([$variant_id, $pid]);
            $variantQty = $lockVariant->fetchColumn();
            if ($variantQty === false || (int)$variantQty < $qty) {
                throw new RuntimeException('Selected size is out of stock during payment finalization.');
            }

            $pdo->prepare('UPDATE Product_Variants SET quantity = quantity - ? WHERE variant_id = ? AND product_id = ?')
                ->execute([$qty, $variant_id, $pid]);
            $pdo->prepare('UPDATE Products SET quantity = (SELECT COALESCE(SUM(quantity),0) FROM Product_Variants WHERE product_id = ?) WHERE product_id = ?')
                ->execute([$pid, $pid]);
        } else {
            $lockProduct = $pdo->prepare('SELECT quantity FROM Products WHERE product_id = ? FOR UPDATE');
            $lockProduct->execute([$pid]);
            $productQty = $lockProduct->fetchColumn();
            if ($productQty === false || (int)$productQty < $qty) {
                throw new RuntimeException('Product is out of stock during payment finalization.');
            }

            $pdo->prepare('UPDATE Products SET quantity = quantity - ? WHERE product_id = ?')
                ->execute([$qty, $pid]);
        }

        add_order_item($order_id, (int)$item['product_id'], (int)$item['quantity'], (float)$item['price']);
    }

    $pdo->commit();

    // Clear cart and pending order
    $_SESSION['cart']          = [];
    $_SESSION['cart_variants'] = [];
    $_SESSION['cart_variant_ids'] = [];
    unset($_SESSION['pending_order']);

    // Send confirmation email to customer (non-fatal)
    try {
        $order = get_order_by_id($order_id);
        send_order_confirmation_email(
            $pending['email'],
            $pending['fullname'],
            $order_id,
            $pending['items'],
            $pending['subtotal']
        );
    } catch (Exception $e) {
        error_log('Order confirmation email failed: ' . $e->getMessage());
    }

    // Send new order notification to admin (non-fatal)
    try {
        send_new_order_admin_notification(
            $order_id,
            $pending['fullname'],
            $pending['email'],
            $pending['items'],
            $pending['subtotal'],
            trim(($pending['address'] ?? '') . (($pending['address_line2'] ?? '') !== '' ? ', ' . $pending['address_line2'] : '') . ', ' . ($pending['city'] ?? '') . ', ' . ($pending['state'] ?? '')),
            $pending['phone']
        );
    } catch (Exception $e) {
        error_log('Admin order notification failed: ' . $e->getMessage());
    }

    header('Location: ' . BASE_URL . '/order-confirmation.php?order_id=' . $order_id);
    exit;

} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order creation failed after payment: ' . $e->getMessage() . ' | Reference: ' . $reference);
    // Payment went through but order failed — flag for manual review
    header('Location: ' . BASE_URL . '/order-confirmation.php?payment_ref=' . urlencode($reference) . '&order_error=1');
    exit;
}
