<?php
/**
 * London Labels - Shopping Cart
 */
require_once __DIR__ . '/functions.php';

$page_title = 'Shopping Cart';
$errors = [];
$notice = '';

function get_cart_product_stock_limit(int $product_id): int {
    $product = get_product_by_id($product_id);
    if (!$product) {
        return 0;
    }

    $variants = get_product_variants($product_id);
    if (!empty($variants)) {
        $variant_id = (int)($_SESSION['cart_variant_ids'][$product_id] ?? 0);
        if ($variant_id > 0) {
            foreach ($variants as $variant) {
                if ((int)$variant['variant_id'] === $variant_id) {
                    return max(0, (int)$variant['quantity']);
                }
            }
            return 0;
        }

        return array_sum(array_map(static fn($v) => max(0, (int)($v['quantity'] ?? 0)), $variants));
    }

    return max(0, (int)$product['quantity']);
}


// ── AJAX: add to cart from product cards ───────────────────────
if (isset($_POST['ajax_add'])) {
    header('Content-Type: application/json');
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        echo json_encode(['ok' => false, 'error' => 'Invalid token']);
        exit;
    }
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = max(1, (int)($_POST['qty'] ?? 1));
    $variant_id = (int)($_POST['variant_id'] ?? 0);
    $size_label = trim((string)($_POST['size_label'] ?? ''));

    $product = get_product_by_id($product_id);
    if (!$product) {
        echo json_encode(['ok' => false, 'error' => 'Product not found']);
        exit;
    }

    $variants = get_product_variants($product_id);
    if (!empty($variants)) {
        if ($variant_id <= 0) {
            echo json_encode(['ok' => false, 'error' => 'Please select a size']);
            exit;
        }
        $_SESSION['cart_variant_ids'][$product_id] = $variant_id;
        if ($size_label !== '') {
            $_SESSION['cart_variants'][$product_id] = $size_label;
        }
    } else {
        unset($_SESSION['cart_variant_ids'][$product_id]);
        unset($_SESSION['cart_variants'][$product_id]);
    }

    $existing = (int)($_SESSION['cart'][$product_id] ?? 0);
    $limit = get_cart_product_stock_limit($product_id);
    $_SESSION['cart'][$product_id] = min($existing + $qty, $limit);

    echo json_encode([
        'ok'      => true,
        'message' => e($product['name']) . ($size_label ? " (" . e($size_label) . ")" : "") . ' added to cart',
        'count'   => array_sum($_SESSION['cart'] ?? []),
    ]);
    exit;
}

// ── AJAX: update single item quantity ──────────────────────────
if (isset($_POST['ajax_update_qty'])) {
    header('Content-Type: application/json');
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        echo json_encode(['ok' => false, 'error' => 'Invalid token']);
        exit;
    }
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty        = (int)($_POST['qty'] ?? 0);
    $applied_qty = 0;
    if ($qty <= 0) {
        unset($_SESSION['cart'][$product_id]);
        unset($_SESSION['cart_variants'][$product_id]);
        unset($_SESSION['cart_variant_ids'][$product_id]);
    } else {
        $limit = get_cart_product_stock_limit($product_id);
        $applied_qty = min($qty, $limit);
        $_SESSION['cart'][$product_id] = $applied_qty;
    }
    // Recalculate subtotal
    $subtotal = 0;
    foreach ($_SESSION['cart'] ?? [] as $pid => $q) {
        $p = get_product_by_id((int)$pid);
        if ($p) {
            $item_price = (float)$p['price'];
            // Skip variant modifiers to maintain unified pricing standards
            $subtotal += $item_price * $q;
        }
    }
    echo json_encode([
        'ok'       => true,
        'subtotal' => format_price($subtotal),
        'qty'      => $applied_qty,
        'count'    => array_sum($_SESSION['cart'] ?? []),
    ]);
    exit;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'remove') {
            $product_id = (int)($_POST['product_id'] ?? 0);
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                unset($_SESSION['cart_variants'][$product_id]);
                unset($_SESSION['cart_variant_ids'][$product_id]);
                $notice = 'Item removed from cart.';
            }
        } elseif (isset($_POST['add_to_cart']) || $action === 'add_to_cart') {
            $product_id = (int)($_POST['product_id'] ?? 0);
            $qty = max(1, (int)($_POST['qty'] ?? 1));
            $product = get_product_by_id($product_id);
            $variants = get_product_variants($product_id);
            if (!$product) {
                $errors[] = 'Product not found.';
            } elseif (!empty($variants)) {
                $errors[] = 'Please select your size on the product page before adding this item to cart.';
            } elseif ($product['quantity'] <= 0) {
                $errors[] = 'This product is out of stock.';
            } else {
                $existing = (int)($_SESSION['cart'][$product_id] ?? 0);
                $_SESSION['cart'][$product_id] = min($existing + $qty, (int)$product['quantity']);
                unset($_SESSION['cart_variant_ids'][$product_id]);
                $notice = 'Added to cart.';
            }
        }

        if (isset($_POST['checkout'])) {
            header('Location: ' . BASE_URL . '/checkout.php');
            exit;
        }
    }
}

// Build cart items
$cart_items = [];
$subtotal = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $qty) {
        $product = get_product_by_id($product_id);
        if ($product) {
            $stock_limit = get_cart_product_stock_limit((int)$product_id);
            if ($stock_limit <= 0) {
                unset($_SESSION['cart'][$product_id]);
                unset($_SESSION['cart_variants'][$product_id]);
                unset($_SESSION['cart_variant_ids'][$product_id]);
                continue;
            }
            if ($qty > $stock_limit) {
                $qty = $stock_limit;
                $_SESSION['cart'][$product_id] = $qty;
            }

            $item_price = (float)$product['price'];
            // Skip variant modifiers to maintain unified pricing standards
            $line = $item_price * $qty;
            $size = $_SESSION['cart_variants'][$product_id] ?? null;
            $cart_items[] = [
                'product_id' => $product_id,
                'name'       => $product['name'],
                'price'      => $item_price,
                'quantity'   => $qty,
                'max_qty'    => $stock_limit,
                'subtotal'   => $line,
                'size'       => $size,
                'image_url'  => $product['image_url'] ?? '',
            ];
            $subtotal += $line;
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<div class="cart-page-wrap">

    <div class="cart-page-head">
        <h2 class="cart-page-title">Shopping Cart</h2>
        <?php if (!empty($cart_items)): ?>
            <span class="cart-page-count"><?= count($cart_items) ?> item<?= count($cart_items) !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>

    <?php foreach ($errors as $err): ?>
        <div class="cart-alert cart-alert-error" role="alert"><?= e($err) ?></div>
    <?php endforeach; ?>
    <?php if ($notice): ?>
        <div class="cart-alert cart-alert-success" role="status"><?= e($notice) ?></div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>

        <div class="cart-empty">
            <p class="cart-empty-label">Your cart is empty</p>
            <p class="cart-empty-sub">Browse our collection and add something you love.</p>
            <a href="<?= BASE_URL ?>/shop.php" class="btn primary">Continue Shopping</a>
        </div>

    <?php else: ?>

        <form method="post" class="cart-layout" id="cartForm">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

            <!-- Items column -->
            <div class="cart-items-col">
                <div class="cart-items-list">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item" data-product-id="<?= $item['product_id'] ?>">
                            <div class="cart-item-image">
                                <a href="<?= BASE_URL ?>/product.php?id=<?= $item['product_id'] ?>" tabindex="-1" aria-hidden="true">
                                    <?php 
                                        $raw_img = (string)($item['image_url'] ?? '');
                                        $safe_img = sanitize_local_upload_media_url($raw_img); 
                                        $display_img = $safe_img ?? (BASE_URL . '/assets/images/placeholder.png');
                                    ?>
                                    <img src="<?= e($display_img) ?>"
                                         alt="<?= e($item['name']) ?>"
                                         width="80" height="80" loading="lazy" decoding="async">
                                </a>
                            </div>
                            <div class="cart-item-details">
                                <a href="<?= BASE_URL ?>/product.php?id=<?= $item['product_id'] ?>" class="cart-item-name"><?= e($item['name']) ?></a>
                                <?php if ($item['size']): ?>
                                    <span class="cart-item-size">Size: <?= e($item['size']) ?></span>
                                <?php endif; ?>
                                <span class="cart-item-unit-price"><?= format_price($item['price']) ?> each</span>
                            </div>
                            <div class="cart-item-qty">
                                <label for="qty-<?= $item['product_id'] ?>" class="visually-hidden">Quantity for <?= e($item['name']) ?></label>
                                <input
                                    type="number"
                                    id="qty-<?= $item['product_id'] ?>"
                                    name="quantities[<?= $item['product_id'] ?>]"
                                    value="<?= $item['quantity'] ?>"
                                    min="0"
                                    max="<?= max(0, (int)$item['max_qty']) ?>"
                                    class="cart-qty-input"
                                    data-product-id="<?= $item['product_id'] ?>"
                                    data-csrf="<?= csrf_token() ?>"
                                    aria-label="Quantity for <?= e($item['name']) ?>"
                                >
                                <span class="cart-qty-saving visually-hidden" aria-live="polite">Saving…</span>
                            </div>
                            <div class="cart-item-subtotal" data-item-subtotal="<?= $item['product_id'] ?>">
                                <?= format_price($item['subtotal']) ?>
                            </div>
                            <div class="cart-item-remove">
                                <button type="button" class="cart-remove-btn"
                                    data-product-id="<?= $item['product_id'] ?>"
                                    data-csrf="<?= csrf_token() ?>"
                                    aria-label="Remove <?= e($item['name']) ?> from cart">Remove</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>

            <!-- Summary column -->
            <aside class="cart-summary-col" aria-label="Order summary">
                <div class="cart-summary-card">
                    <h3 class="cart-summary-title">Order Summary</h3>

                    <div class="cart-summary-rows">
                        <div class="cart-summary-row">
                            <span>Subtotal</span>
                            <span id="cart-subtotal"><?= format_price($subtotal) ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Shipping</span>
                            <span class="cart-summary-note">Calculated at checkout</span>
                        </div>
                    </div>

                    <div class="cart-summary-total">
                        <span>Total</span>
                        <strong id="cart-total"><?= format_price($subtotal) ?></strong>
                    </div>

                    <button type="submit" name="checkout" class="btn primary cart-checkout-btn">Proceed to Checkout</button>

                    <p class="cart-summary-trust">Secure checkout. Order tracked in your account.</p>
                </div>
            </aside>

        </form>

        <script>
        (function () {
            var inputs = document.querySelectorAll('.cart-qty-input');
            var debounceTimers = {};

            inputs.forEach(function (input) {
                input.addEventListener('change', function () {
                    updateQty(input);
                });
                input.addEventListener('input', function () {
                    var pid = input.getAttribute('data-product-id');
                    clearTimeout(debounceTimers[pid]);
                    debounceTimers[pid] = setTimeout(function () {
                        updateQty(input);
                    }, 600);
                });
            });

            // Listen for Remove clicks — submit the hidden form
            document.addEventListener('click', function(e) {
                var removeBtn = e.target.closest('.cart-remove-btn');
                if (!removeBtn) return;

                // The button is inside a form — let it submit naturally
                // but also update the UI optimistically
                var card = removeBtn.closest('.cart-item');
                if (card) {
                    card.classList.add('fade-out');
                    if (window.showToast) window.showToast('Item removed from cart', 'success');
                }
            });

            function updateQty(input, forceRemove = false) {
                var pid  = input.getAttribute('data-product-id');
                var csrf = input.getAttribute('data-csrf');
                var qty  = parseInt(input.value, 10) || 0;
                var savingEl = input.parentNode.querySelector('.cart-qty-saving');

                if (savingEl) {
                    savingEl.classList.remove('visually-hidden');
                }

                var row = document.querySelector('[data-product-id="' + pid + '"].cart-item');
                if (qty <= 0 || forceRemove) {
                    if (row) {
                        row.classList.add('fade-out');
                    }
                }

                var body = new URLSearchParams();
                body.append('ajax_update_qty', '1');
                body.append('csrf', csrf);
                body.append('product_id', pid);
                body.append('qty', qty);

                fetch(window.BASE_URL + '/cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString(),
                    credentials: 'same-origin'
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (savingEl) savingEl.classList.add('visually-hidden');
                    if (!data.ok) return;

                    if (typeof data.qty === 'number' && qty > 0) {
                        input.value = data.qty;
                    }

                    // Update subtotal and total displays
                    var subtotalEl = document.getElementById('cart-subtotal');
                    var totalEl    = document.getElementById('cart-total');
                    if (subtotalEl) subtotalEl.textContent = data.subtotal;
                    if (totalEl)    totalEl.textContent    = data.subtotal;

                    // Header badge sync
                    if (window.updateHeaderCartBadge) window.updateHeaderCartBadge(data.count);

                    // If qty is 0, remove the item row after animation
                    if (qty <= 0 || forceRemove) {
                        setTimeout(function() {
                            if (row) row.remove();
                            // If grid empty, reload for PHP empty state
                            var grid = document.querySelector('.cart-items-list');
                            if (grid && !grid.querySelector('.cart-item')) {
                                window.location.reload();
                            }
                        }, 400);
                    }
                })
                .catch(function () {
                    if (savingEl) savingEl.classList.add('visually-hidden');
                    if (row) row.classList.remove('fade-out');
                });
            }
        })();
        </script>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/inc_footer.php'; ?>
