<?php
/**
 * London Labels - Order Confirmation Page
 */
require_once __DIR__ . '/functions.php';

$page_title = 'Order Confirmed';
$errors = [];

require_login();

$order_id = (int)($_GET['order_id'] ?? 0);

// Edge case: payment succeeded but order creation failed
if (isset($_GET['order_error']) && isset($_GET['payment_ref'])) {
    $ref = e(trim($_GET['payment_ref'] ?? ''));
    include __DIR__ . '/inc_header.php';
    ?>
    <div class="order-missing-box">
        <h2>Payment Received — Order Pending</h2>
        <p class="order-missing-text">Your payment was successful but we encountered an issue creating your order. Please <a href="<?= BASE_URL ?>/contact.php">contact us</a> with your payment reference: <strong><?= $ref ?></strong> and we will resolve it immediately.</p>
        <a href="<?= BASE_URL ?>/contact.php" class="btn primary order-missing-link">Contact Support</a>
    </div>
    <?php
    include __DIR__ . '/inc_footer.php';
    exit;
}

if ($order_id <= 0) {
    $errors[] = 'Invalid order ID.';
} else {
    $order = get_order_by_id($order_id);
    if (!$order || $order['user_id'] !== current_user_id()) {
        $errors[] = 'Order not found or you do not have permission to view it.';
    }
}

include __DIR__ . '/inc_header.php';

function render_order_timeline(string $status): string {
    if ($status === 'cancelled') {
        return '<div class="order-cancelled-note">Order has been cancelled.</div>';
    }

    $steps = ['pending', 'processing', 'shipped', 'delivered'];
    $labels = [
        'pending'    => 'Order Placed',
        'processing' => 'Processing',
        'shipped'    => 'Out for Delivery',
        'delivered'  => 'Delivered',
    ];
    $currentIndex = array_search($status, $steps, true);
    if ($currentIndex === false) {
        $currentIndex = 0;
    }

    $html = '<div class="order-timeline">';
    foreach ($steps as $idx => $step) {
        $done = $idx <= $currentIndex;
        $html .= '<span class="order-timeline-pill ' . ($done ? 'done' : 'pending') . '">'
            . e($labels[$step])
            . '</span>';
    }
    $html .= '</div>';

    return $html;
}

if (!empty($order)): ?>

    <div class="order-confirm-hero">
        <h2 class="order-confirm-hero-title">Order Confirmed</h2>
        <p class="order-confirm-hero-text">Thank you for your purchase</p>
        <div class="order-confirm-hero-card">
            <p class="order-confirm-hero-label">Order Number</p>
            <h3 class="order-confirm-hero-number">#<?= $order['order_id'] ?></h3>
        </div>
    </div>

    <div class="order-confirm-layout">
        <div>
            <div class="order-confirm-card">
                <h3>Order Details</h3>
                
                <div class="order-details-list">
                    <div class="order-details-row">
                        <span>Order Date:</span>
                        <strong><?= date('M d, Y H:i', strtotime($order['order_date'])) ?></strong>
                    </div>
                    <div class="order-details-row">
                        <span>Status:</span>
                        <strong class="order-status-strong"><?= e(ucfirst($order['status'])) ?></strong>
                    </div>
                    <div class="order-details-block">
                        <span class="order-progress-label">Order Progress:</span>
                        <?= render_order_timeline($order['status']) ?>
                    </div>
                    <div class="order-details-row order-details-row-last">
                        <span>Total Amount:</span>
                        <strong class="order-total-strong"><?= format_price($order['total_amount']) ?></strong>
                    </div>
                    <?php if (!empty($order['shipping_address'])): ?>
                    <div class="order-details-row">
                        <span>Shipping:</span>
                        <span><?= e($order['shipping_address']) ?>, <?= e($order['city']) ?> <?= e($order['postal_code']) ?></span>
                    </div>
                    <div class="order-details-row order-details-row-last">
                        <span>Phone:</span>
                        <span><?= e($order['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="order-details-row order-details-row-last">
                        <span>Payment Method:</span>
                        <span><?= e(ucfirst(str_replace('_', ' ', $order['payment_method']))) ?></span>
                    </div>
                    <div class="order-details-row order-payment-status-row">
                        <span>Payment Status:</span>
                        <span><?= e(ucfirst($order['payment_status'])) ?></span>
                    </div>
                </div>
            </div>

            <div class="order-confirm-card">
                <h3>Items Ordered</h3>
                
                <?php
                $order_items = get_order_items($order['order_id']);
                foreach ($order_items as $item):
                ?>
                    <div class="order-item-row">
                        <div>
                            <strong><?= e($item['name']) ?></strong>
                            <div class="order-item-meta">Qty: <?= $item['quantity'] ?></div>
                        </div>
                        <div class="order-item-price-block">
                            <div><?= format_price($item['price'] * $item['quantity']) ?></div>
                            <div class="order-item-meta"><?= format_price($item['price']) ?> each</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="order-next-box">
                <strong class="order-next-title">What happens next?</strong>
                <ul class="order-next-list">
                    <li>You'll receive a confirmation email shortly</li>
                    <li>Our team will prepare your order at our Lagos store</li>
                    <li>A delivery agent will bring it to your address — Lagos orders typically arrive in 1–3 days, nationwide in 3–7 days</li>
                </ul>
            </div>

            <div class="order-confirm-actions">
                <a href="<?= BASE_URL ?>/account/orders.php" class="btn primary order-action-btn">View My Orders</a>
                <a href="<?= BASE_URL ?>/shop.php" class="btn order-action-btn">Continue Shopping</a>
            </div>
        </div>

        <div>
            <div class="order-confirm-side-card">
                <h3>Delivery To</h3>
                
                <div class="order-side-inner-card">
                    <p class="order-side-name"><?= e(current_user_name()) ?></p>
                    <?php if (!empty($order['shipping_address'])): ?>
                        <p class="order-side-text"><?= e($order['shipping_address']) ?></p>
                        <?php if (!empty($order['city'])): ?>
                            <p class="order-side-text"><?= e($order['city']) ?><?= !empty($order['postal_code']) ? ', ' . e($order['postal_code']) : '' ?></p>
                        <?php endif; ?>
                        <?php if (!empty($order['phone'])): ?>
                            <p class="order-side-text"><?= e($order['phone']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="order-side-text">Delivery address will be confirmed by our team.</p>
                    <?php endif; ?>
                </div>

                <h3 class="order-billing-title">Billing Details</h3>
                
                <div class="order-side-inner-card">
                    <p><strong>Email:</strong></p>
                    <p class="order-side-text"><?= e(current_user_email()) ?></p>
                    
                    <p class="order-payment-label"><strong>Payment Method:</strong></p>
                    <p class="order-side-text">
                        <?php
                            $pm = $order['payment_method'] ?? '';
                            echo e(match($pm) {
                                'paystack'      => 'Paystack',
                                'cash'          => 'Cash on Delivery',
                                'card'          => 'Card on Delivery',
                                'transfer', 'bank_transfer' => 'Bank Transfer',
                                default         => ucfirst(str_replace('_', ' ', $pm)),
                            });
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="order-missing-box">
        <h2>Order Not Found</h2>
        <p class="order-missing-text">We couldn't find the order you're looking for.</p>
        <a href="<?= BASE_URL ?>/account/orders.php" class="btn primary order-missing-link">
            View My Orders
        </a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/inc_footer.php'; ?>
