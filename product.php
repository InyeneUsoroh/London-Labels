<?php
/**
 * London Labels - Product Details Page
 */
require_once __DIR__ . '/functions.php';


$page_title = 'Product Details';
$errors = [];
$notice = '';
$wishlist_ids = is_logged_in() ? get_user_wishlist_product_ids((int)current_user_id()) : get_guest_wishlist_product_ids();
$review_summary = ['review_count' => 0, 'average_rating' => 0.0];
$review_breakdown = ['total' => 0, 'ratings' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0], 'percentages' => [5 => 0.0, 4 => 0.0, 3 => 0.0, 2 => 0.0, 1 => 0.0]];
$product_reviews = [];
$user_helpful_votes = [];
$user_review = null;
$can_review_product = false;
$size_chart_rows = [];
$share_url = '';
$share_text = '';
$review_sort = trim((string)($_GET['review_sort'] ?? ''));
$has_explicit_review_sort = isset($_GET['review_sort']);
$review_rating_filter = (int)($_GET['review_rating'] ?? 0);
$review_media_only = (int)($_GET['review_media'] ?? 0) === 1;
$review_verified_only = (int)($_GET['review_verified'] ?? 0) === 1;
$review_search = trim((string)($_GET['review_q'] ?? ''));
$review_page = max(1, (int)($_GET['review_page'] ?? 1));
$reviews_per_page = 8;
$review_total = 0;
$review_total_pages = 1;
$review_offset = 0;
$allowed_review_sorts = ['most_recent', 'most_helpful', 'highest_rating', 'lowest_rating'];
if (!in_array($review_sort, $allowed_review_sorts, true)) {
    $review_sort = 'most_recent';
}
if ($review_rating_filter < 1 || $review_rating_filter > 5) {
    $review_rating_filter = 0;
}
if (strlen($review_search) > 120) {
    $review_search = substr($review_search, 0, 120);
}

$product_id = (int)($_GET['id'] ?? 0);
$has_variants = false;
$display_stock_qty = 0;
$is_low_stock = false;

if ($product_id <= 0) {
    $errors[] = 'Invalid product ID.';
} else {
    $product = get_product_by_id($product_id);
    if (!$product) {
        $errors[] = 'Product not found.';
    } else {
        $page_title = $product['name'];
        $product_images_raw = get_product_images($product_id);
        $product_images = [];
        foreach ($product_images_raw as $img_row) {
            $safe_image_url = sanitize_local_upload_media_url((string)($img_row['image_url'] ?? ''));
            if ($safe_image_url !== null) {
                $img_row['image_url'] = $safe_image_url;
                $product_images[] = $img_row;
            }
        }
        $related_default_limit = 4;
        $related_max_limit = 8;
        $related_fetch_limit = min($related_default_limit + 1, $related_max_limit);
        $related_products_raw = get_related_products($product_id, (int)$product['category_id'], $related_fetch_limit);
        $has_more_related = count($related_products_raw) > $related_default_limit;
        $related_products = array_slice($related_products_raw, 0, $related_default_limit);
        $variants         = get_product_variants($product_id);
        $has_variants     = !empty($variants);
        if ($has_variants) {
            $display_stock_qty = array_sum(array_map(static fn($v) => max(0, (int)($v['quantity'] ?? 0)), $variants));
        } else {
            $display_stock_qty = max(0, (int)$product['quantity']);
        }
        $is_low_stock = $display_stock_qty > 0 && $display_stock_qty <= 5;
        $review_summary   = get_product_review_summary($product_id);
        if (!$has_explicit_review_sort) {
            $review_sort = (int)$review_summary['review_count'] >= 15 ? 'most_helpful' : 'most_recent';
        }
        $review_breakdown = get_product_review_breakdown($product_id);
        $review_total = count_product_reviews($product_id, $review_rating_filter, $review_media_only, $review_verified_only, $review_search);
        $review_total_pages = max(1, (int)ceil($review_total / $reviews_per_page));
        if ($review_page > $review_total_pages) {
            $review_page = $review_total_pages;
        }
        $review_offset = ($review_page - 1) * $reviews_per_page;
        $product_reviews  = get_product_reviews($product_id, $reviews_per_page, $review_offset, $review_sort, $review_rating_filter, $review_media_only, $review_verified_only, $review_search);

        if (is_logged_in()) {
            $current_uid = (int)current_user_id();
            $user_review = get_user_product_review($current_uid, $product_id);
            $can_review_product = has_user_purchased_product($current_uid, $product_id);
            if (!empty($product_reviews)) {
                $review_ids = array_map(static fn($r) => (int)$r['review_id'], $product_reviews);
                $user_helpful_votes = get_user_helpful_votes_for_reviews($current_uid, $review_ids);
            }
        }

        $category_name = strtolower(trim((string)($product['category_name'] ?? '')));
        $clothing_map = [
            'XXS' => ['UK 4-6', 'Bust 79-84 cm'],
            'XS' => ['UK 6-8', 'Bust 84-89 cm'],
            'S' => ['UK 8-10', 'Bust 89-94 cm'],
            'M' => ['UK 10-12', 'Bust 94-99 cm'],
            'L' => ['UK 12-14', 'Bust 99-105 cm'],
            'XL' => ['UK 14-16', 'Bust 105-111 cm'],
            'XXL' => ['UK 16-18', 'Bust 111-118 cm'],
        ];
        $clothing_aliases = [
            'EXTRASMALL' => 'XS',
            'SMALL' => 'S',
            'MEDIUM' => 'M',
            'LARGE' => 'L',
            'EXTRALARGE' => 'XL',
            'XXLARGE' => 'XXL',
            'ONESIZE' => 'ONE SIZE',
            'ONESZ' => 'ONE SIZE',
            'OS' => 'ONE SIZE',
        ];
        $footwear_map = [
            '35' => ['UK 2', 'Foot length 22.5 cm'],
            '36' => ['UK 3', 'Foot length 23.0 cm'],
            '37' => ['UK 4', 'Foot length 23.8 cm'],
            '38' => ['UK 5', 'Foot length 24.2 cm'],
            '39' => ['UK 6', 'Foot length 24.2 cm'],
            '40' => ['UK 7', 'Foot length 25.0 cm'],
            '41' => ['UK 8', 'Foot length 25.8 cm'],
            '42' => ['UK 9', 'Foot length 26.7 cm'],
            '43' => ['UK 10', 'Foot length 27.5 cm'],
            '44' => ['UK 11', 'Foot length 28.3 cm'],
            '45' => ['UK 12', 'Foot length 29.2 cm'],
            '46' => ['UK 13', 'Foot length 30.0 cm'],
            '47' => ['UK 14', 'Foot length 30.8 cm'],
            '48' => ['UK 15', 'Foot length 31.6 cm'],
        ];
        $product_fit_context = strtolower(trim(
            (string)($product['name'] ?? '') . ' ' .
            (string)($product['tags'] ?? '') . ' ' .
            (string)($product['description'] ?? '') . ' ' .
            (string)($product['category_name'] ?? '')
        ));
        $is_fashion_category = in_array($category_name, ['fashion', 'clothing', 'apparel', 'footwear', 'shoes'], true);
        $material_label = 'Not specified';
        $care_label = 'Hand wash cold or dry clean (check garment label).';
        $fit_label = 'Regular fit';
        $model_reference = '';

        $material_map = [
            'Cotton' => '/\bcotton\b/',
            'Linen' => '/\blinen\b/',
            'Denim' => '/\bdenim\b/',
            'Silk' => '/\bsilk\b/',
            'Wool' => '/\bwool\b/',
            'Polyester' => '/\bpolyester\b/',
            'Leather' => '/\bleather\b/',
            'Satin' => '/\bsatin\b/',
            'Chiffon' => '/\bchiffon\b/',
            'Knit' => '/\bknit\b/',
        ];
        foreach ($material_map as $label => $pattern) {
            if (preg_match($pattern, $product_fit_context) === 1) {
                $material_label = $label;
                break;
            }
        }

        if (preg_match('/\b(machine wash|machine-wash)\b/', $product_fit_context) === 1) {
            $care_label = 'Machine wash cold, gentle cycle.';
        } elseif (preg_match('/\bdry clean\b/', $product_fit_context) === 1) {
            $care_label = 'Dry clean recommended.';
        } elseif (preg_match('/\bhand wash\b/', $product_fit_context) === 1) {
            $care_label = 'Hand wash only.';
        }

        if (preg_match('/\bslim fit|bodycon|fitted\b/', $product_fit_context) === 1) {
            $fit_label = 'Fitted silhouette';
        } elseif (preg_match('/\bover(size|sized)|relaxed|loose fit\b/', $product_fit_context) === 1) {
            $fit_label = 'Relaxed fit';
        }

        $desc_for_model = trim((string)($product['description'] ?? ''));
        if (preg_match('/\bmodel\b[^.]{0,80}\b(\d{2,3})\s*cm\b/i', $desc_for_model, $m) === 1) {
            $model_reference = 'Model height: ' . $m[1] . ' cm';
        }

        if (preg_match('/dress|gown|jumpsuit|romper/', $product_fit_context) === 1) {
            $one_size_row = ['One Size', 'UK 8-14 (approx.)', 'Best for bust 86-102 cm, waist 66-86 cm'];
        } elseif (preg_match('/top|blouse|shirt|tee|t-shirt|tshirt/', $product_fit_context) === 1) {
            $one_size_row = ['One Size', 'UK 8-14 (approx.)', 'Best for bust 86-102 cm'];
        } elseif (preg_match('/trouser|pants|jeans|skirt|shorts/', $product_fit_context) === 1) {
            $one_size_row = ['One Size', 'UK 8-14 (approx.)', 'Best for waist 66-86 cm, hips 92-108 cm'];
        } elseif (preg_match('/jacket|coat|hoodie|sweater|cardigan/', $product_fit_context) === 1) {
            $one_size_row = ['One Size', 'UK 8-16 (approx.)', 'Relaxed fit for layering'];
        } elseif (preg_match('/bag|belt|hat|cap|scarf|accessor/', $product_fit_context) === 1) {
            $one_size_row = ['One Size', 'Universal', 'Not body-size dependent'];
        } else {
            $one_size_row = ['One Size', 'UK 8-14 (approx.)', 'Best for bust 86-102 cm'];
        }

        if (!empty($variants)) {
            $size_entries = [];
            foreach ($variants as $variant) {
                $original_size = trim((string)($variant['size'] ?? ''));
                $raw_size = strtoupper($original_size);
                if ($raw_size === '') {
                    continue;
                }

                $normalized = preg_replace('/[^A-Z0-9]/', '', $raw_size);
                $canonical_clothing = $clothing_aliases[$normalized] ?? $raw_size;

                $eu_size = null;
                if (preg_match('/\b(3[5-9]|4[0-9])\b/', $raw_size, $m) === 1) {
                    $eu_size = $m[1];
                } elseif (ctype_digit($normalized)) {
                    $n = (int)$normalized;
                    if ($n >= 35 && $n <= 49) {
                        $eu_size = (string)$n;
                    }
                }

                $size_entries[$raw_size] = [
                    'raw' => $raw_size,
                    'original' => $original_size,
                    'canonical_clothing' => $canonical_clothing,
                    'eu_size' => $eu_size,
                ];
            }

            $variant_sizes = array_values($size_entries);
            $all_footwear_like = !empty($variant_sizes) && count(array_filter($variant_sizes, static fn($s) => !empty($s['eu_size']))) === count($variant_sizes);

            if ($all_footwear_like) {
                usort($variant_sizes, static fn($a, $b) => (int)$a['eu_size'] <=> (int)$b['eu_size']);
                foreach ($variant_sizes as $entry) {
                    $eu = (string)$entry['eu_size'];
                    if (isset($footwear_map[$eu])) {
                        $size_chart_rows[] = ['EU ' . $eu, $footwear_map[$eu][0], $footwear_map[$eu][1]];
                    } else {
                        $uk_approx = 'UK ' . max(1, ((int)$eu - 33));
                        $size_chart_rows[] = ['EU ' . $eu, $uk_approx . ' (approx.)', 'Foot length may vary by brand'];
                    }
                }
            } else {
                $order = array_keys($clothing_map);
                usort($variant_sizes, static function ($a, $b) use ($order) {
                    $ia = array_search($a['canonical_clothing'], $order, true);
                    $ib = array_search($b['canonical_clothing'], $order, true);
                    if ($ia === false && $ib === false) return strcmp((string)$a['raw'], (string)$b['raw']);
                    if ($ia === false) return 1;
                    if ($ib === false) return -1;
                    return $ia <=> $ib;
                });

                foreach ($variant_sizes as $entry) {
                    $size_key = (string)$entry['canonical_clothing'];
                    $size_label = (string)$entry['raw'];
                    if ($size_key === 'ONE SIZE') {
                        $size_chart_rows[] = $one_size_row;
                    } elseif (isset($clothing_map[$size_key])) {
                        $size_chart_rows[] = [$size_label, $clothing_map[$size_key][0], $clothing_map[$size_key][1]];
                    } else {
                        $size_chart_rows[] = [$size_label, 'UK sizing varies by cut', 'Use nearest body measurement in cm'];
                    }
                }
            }
        } elseif (in_array($category_name, ['fashion', 'clothing', 'apparel'], true)) {
            $size_chart_rows = [
                ['XS', 'UK 6-8', 'Bust 84-89 cm'],
                ['S', 'UK 8-10', 'Bust 89-94 cm'],
                ['M', 'UK 10-12', 'Bust 94-99 cm'],
                ['L', 'UK 12-14', 'Bust 99-105 cm'],
                ['XL', 'UK 14-16', 'Bust 105-111 cm'],
            ];
        } elseif (in_array($category_name, ['footwear', 'shoes'], true)) {
            $size_chart_rows = [
                ['EU 40', 'UK 7', 'Foot length 25.0 cm'],
                ['EU 41', 'UK 8', 'Foot length 25.8 cm'],
                ['EU 42', 'UK 9', 'Foot length 26.7 cm'],
                ['EU 43', 'UK 10', 'Foot length 27.5 cm'],
                ['EU 44', 'UK 11', 'Foot length 28.3 cm'],
            ];
        } else {
            $size_chart_rows = [$one_size_row];
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $share_url = ($host !== '' ? ($scheme . '://' . $host) : '') . BASE_URL . '/product.php?id=' . (int)$product['product_id'];
        $share_text = 'Check out ' . $product['name'] . ' on London Labels';
    }
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $post_action = trim((string)($_POST['action'] ?? 'add_to_cart'));
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        if ($post_action === 'mark_review_helpful') {
            if (!is_logged_in()) {
                header('Location: ' . BASE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
                exit;
            }
            $current_uid = (int)current_user_id();
            $review_id = (int)($_POST['review_id'] ?? 0);
            if ($review_id <= 0) {
                $errors[] = 'Invalid review selection.';
            } elseif (!mark_review_helpful_vote($review_id, $current_uid, $product_id)) {
                $errors[] = 'Could not register your helpful vote. Please try again.';
            } else {
                $notice = 'Thanks for your feedback.';
            }
        } elseif ($post_action === 'submit_review') {
            if (!is_logged_in()) {
                header('Location: ' . BASE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/'));
                exit;
            }
            $current_uid = (int)current_user_id();
            $rating = (int)($_POST['rating'] ?? 0);
            $title = trim((string)($_POST['review_title'] ?? ''));
            $review_text = trim((string)($_POST['review_text'] ?? ''));
            $uploaded_review_media_url = null;

            if ($rating < 1 || $rating > 5) {
                $errors[] = 'Please select a rating between 1 and 5.';
            }
            if ($review_text === '' || strlen($review_text) < 10) {
                $errors[] = 'Please enter a review of at least 10 characters.';
            }
            if (strlen($title) > 120) {
                $errors[] = 'Review title must not exceed 120 characters.';
            }

            if (isset($_FILES['review_media_file']) && is_array($_FILES['review_media_file'])) {
                $review_media_file = $_FILES['review_media_file'];
                $upload_error = (int)($review_media_file['error'] ?? UPLOAD_ERR_NO_FILE);

                if ($upload_error !== UPLOAD_ERR_NO_FILE) {
                    $uploadErrors = [];
                    $stored = normalize_and_store_uploaded_image($review_media_file, 'Reviews', 'review_' . (int)$product_id . '_' . (int)$current_uid, $uploadErrors, MAX_FILE_SIZE);
                    if ($stored !== null) {
                        $uploaded_review_media_url = (string)$stored['url'];
                    } elseif (!empty($uploadErrors)) {
                        $errors[] = $uploadErrors[0];
                    }
                }
            }

            if (!has_user_purchased_product($current_uid, $product_id)) {
                $errors[] = 'Only customers who purchased this product can submit a review.';
            }

            if (empty($errors)) {
                $existing_media_url = trim((string)($user_review['media_url'] ?? ''));
                $final_media_url = $uploaded_review_media_url ?? ($existing_media_url !== '' ? $existing_media_url : null);
                upsert_product_review($current_uid, $product_id, $rating, $title, $review_text, $final_media_url);

                if ($uploaded_review_media_url !== null && $existing_media_url !== '' && $existing_media_url !== $uploaded_review_media_url) {
                    $oldReviewPath = get_local_upload_file_path_from_url($existing_media_url);
                    if (is_string($oldReviewPath) && $oldReviewPath !== '' && is_file($oldReviewPath)) {
                        @unlink($oldReviewPath);
                        $oldReviewThumb = dirname($oldReviewPath) . '/thumbs/' . basename($oldReviewPath);
                        if (is_file($oldReviewThumb)) {
                            @unlink($oldReviewThumb);
                        }
                    }
                }
                $notice = 'Thanks for your review. It is pending approval.';
            }
        } else {
            $qty        = (int)($_POST['qty'] ?? 1);
            $variant_id = (int)($_POST['variant_id'] ?? 0);
            $size_label = trim($_POST['size_label'] ?? '');

            if ($has_variants) {
                $selected_variant = null;
                foreach ($variants as $variant) {
                    if ((int)$variant['variant_id'] === $variant_id) {
                        $selected_variant = $variant;
                        break;
                    }
                }

                if ($variant_id <= 0 || !$selected_variant) {
                    $errors[] = 'Please select a size.';
                } else {
                    $variant_qty = max(0, (int)($selected_variant['quantity'] ?? 0));
                    if ($variant_qty <= 0) {
                        $errors[] = 'Selected size is out of stock.';
                    } elseif ($qty <= 0 || $qty > $variant_qty) {
                        $errors[] = 'Invalid quantity for selected size. Available: ' . $variant_qty . '.';
                    }
                }
            }

            if (empty($errors) && !$has_variants && ($qty <= 0 || $qty > max(0, (int)$product['quantity']))) {
                $errors[] = 'Invalid quantity.';
            }

            if (empty($errors)) {
                if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
                if (!isset($_SESSION['cart_variants'])) $_SESSION['cart_variants'] = [];
                if (!isset($_SESSION['cart_variant_ids'])) $_SESSION['cart_variant_ids'] = [];

                $existing = (int)($_SESSION['cart'][$product_id] ?? 0);
                if ($has_variants) {
                    $variant_qty = 0;
                    foreach ($variants as $variant) {
                        if ((int)$variant['variant_id'] === $variant_id) {
                            $variant_qty = max(0, (int)$variant['quantity']);
                            if ($size_label === '') {
                                $size_label = (string)$variant['size'];
                            }
                            break;
                        }
                    }
                    $_SESSION['cart'][$product_id] = min($existing + $qty, $variant_qty);
                    $_SESSION['cart_variant_ids'][$product_id] = $variant_id;
                } else {
                    $_SESSION['cart'][$product_id] = min($existing + $qty, max(0, (int)$product['quantity']));
                    unset($_SESSION['cart_variant_ids'][$product_id]);
                }

                // Store selected size label for display in cart
                if ($size_label !== '') {
                    $_SESSION['cart_variants'][$product_id] = $size_label;
                } else {
                    unset($_SESSION['cart_variants'][$product_id]);
                }

                $notice = 'Added to cart.';
            }
        }
    }
}

if (!empty($product)) {
    $review_summary = get_product_review_summary($product_id);
    if (!$has_explicit_review_sort) {
        $review_sort = (int)$review_summary['review_count'] >= 15 ? 'most_helpful' : 'most_recent';
    }
    $review_breakdown = get_product_review_breakdown($product_id);
    $review_total = count_product_reviews($product_id, $review_rating_filter, $review_media_only, $review_verified_only, $review_search);
    $review_total_pages = max(1, (int)ceil($review_total / $reviews_per_page));
    if ($review_page > $review_total_pages) {
        $review_page = $review_total_pages;
    }
    $review_offset = ($review_page - 1) * $reviews_per_page;
    $product_reviews = get_product_reviews($product_id, $reviews_per_page, $review_offset, $review_sort, $review_rating_filter, $review_media_only, $review_verified_only, $review_search);

    if (is_logged_in()) {
        $current_uid = (int)current_user_id();
        $user_review = get_user_product_review($current_uid, $product_id);
        $can_review_product = has_user_purchased_product($current_uid, $product_id);
        if (!empty($product_reviews)) {
            $review_ids = array_map(static fn($r) => (int)$r['review_id'], $product_reviews);
            $user_helpful_votes = get_user_helpful_votes_for_reviews($current_uid, $review_ids);
        }
    }
}

include __DIR__ . '/inc_header.php';
?>

<?php

if (!empty($errors)) {
    render_alert('danger', $errors);
}

if (!empty($notice)) {
    render_alert('success', $notice);
}

if (!empty($product)): ?>

    <!-- Desktop: Full breadcrumb trail -->
    <nav class="product-breadcrumb product-breadcrumb--desktop" aria-label="Breadcrumb">
        <a href="<?= BASE_URL ?>/index.php">Home</a>
        <span aria-hidden="true">/</span>
        <a href="<?= BASE_URL ?>/shop.php">Shop</a>
        <span aria-hidden="true">/</span>
        <a href="<?= BASE_URL ?>/shop.php?category=<?= $product['category_id'] ?>"><?= e($product['category_name']) ?></a>
        <span aria-hidden="true">/</span>
        <span><?= e($product['name']) ?></span>
    </nav>

    <!-- Mobile: Single back-link to parent category -->
    <nav class="product-breadcrumb product-breadcrumb--mobile" aria-label="Back to category">
        <a href="<?= BASE_URL ?>/shop.php?category=<?= $product['category_id'] ?>" class="product-breadcrumb-back">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <?= e($product['category_name']) ?>
        </a>
    </nav>

    <div class="product-detail-layout">
        <div>
            <?php
                $gallery_images = [];
                foreach ($product_images as $img) {
                    $src = trim((string)($img['image_url'] ?? ''));
                    if ($src !== '') {
                        $gallery_images[] = $src;
                    }
                }
                $gallery_media = [];
                foreach ($gallery_images as $src) {
                    $gallery_media[] = [
                        'type' => 'image',
                        'src' => $src,
                        'mime' => '',
                        'thumb' => $src,
                    ];
                }
                $has_gallery_media = !empty($gallery_media);
                $has_gallery_navigation = count($gallery_media) > 1;
                $mainMedia = $gallery_media[0] ?? null;
                $mainImage = ($mainMedia && $mainMedia['type'] === 'image') ? (string)$mainMedia['src'] : '';
            ?>
            <div class="product-gallery-wrap">
                <?php if ($has_gallery_navigation): ?>
                    <div class="product-thumb-strip">
                        <?php foreach ($gallery_media as $index => $media): ?>
                            <button type="button" class="product-thumb-btn<?= $index === 0 ? ' active' : '' ?>" data-gallery-index="<?= $index ?>" data-main-src="<?= e((string)$media['src']) ?>" data-media-type="<?= e((string)$media['type']) ?>" data-video-mime="<?= e((string)$media['mime']) ?>" aria-label="View image <?= $index + 1 ?>" aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
                                <img src="<?= e((string)$media['thumb']) ?>" alt="<?= e($product['name']) ?>" class="product-thumb-image" loading="lazy" decoding="async" width="200" height="200">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="product-gallery" id="productGallery" data-gallery-count="<?= count($gallery_media) ?>">
                    <div class="product-main-image-wrap">
                        <?php if ($mainImage !== ''): ?>
                            <img src="<?= e($mainImage) ?>" alt="<?= e($product['name']) ?>" class="product-main-image" id="productMainImage" loading="eager" fetchpriority="high" decoding="async" width="1200" height="1200">
                        <?php else: ?>
                            <img src="<?= BASE_URL ?>/assets/images/placeholder.png" alt="<?= e($product['name']) ?>" class="product-main-image" id="productMainImage" loading="eager" decoding="async" width="1200" height="1200">
                        <?php endif; ?>

                        <?php if ($has_gallery_navigation): ?>
                            <button type="button" class="product-gallery-nav prev" id="productGalleryPrev" aria-label="Previous image">&#8249;</button>
                            <button type="button" class="product-gallery-nav next" id="productGalleryNext" aria-label="Next image">&#8250;</button>
                        <?php endif; ?>

                    </div>

                    <?php if ($has_gallery_navigation): ?>
                        <div class="product-gallery-dots" id="productGalleryDots" aria-label="Gallery pagination">
                            <?php foreach ($gallery_media as $index => $media): ?>
                                <button
                                    type="button"
                                    class="product-gallery-dot<?= $index === 0 ? ' active' : '' ?>"
                                    data-gallery-index="<?= $index ?>"
                                    aria-label="Go to image <?= $index + 1 ?>"
                                    aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                                ></button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="product-detail-info">
            <h1 class="product-detail-title"><?= e($product['name']) ?></h1>
            
            <div class="product-meta-row">
                <span class="product-category-chip">
                    <?= e($product['category_name']) ?>
                </span>
            </div>


            <div class="product-rating-and-price">
                <div class="product-rating">
                    <?php if (($review_summary['review_count'] ?? 0) > 0): ?>
                        <?php
                            $avg_rating_value = (float)$review_summary['average_rating'];
                            $avg_rating_fill_pct = max(0, min(100, ($avg_rating_value / 5) * 100));
                        ?>
                        <span class="product-stars" style="--rating-fill: <?= number_format($avg_rating_fill_pct, 1, '.', '') ?>%;" aria-label="Average rating <?= number_format($avg_rating_value, 1) ?> out of 5">
                            <span class="product-stars-base" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                            <span class="product-stars-fill" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                        </span>
                        <span class="product-review-count"><?= number_format((float)$review_summary['average_rating'], 1) ?> (<?= (int)$review_summary['review_count'] ?>)</span>
                    <?php else: ?>
                        <span class="product-review-count">No reviews yet <a href="#product-reviews" class="product-review-link">Be the first to review</a></span>
                    <?php endif; ?>
                </div>
                <div class="product-detail-price">
                    <?= format_price($product['price']) ?>
                </div>
            </div>


         <!-- Status & Action Center -->
            <div class="product-status-row">
                <div class="product-stock-status">
                    <?php if ($display_stock_qty > 0): ?>
                        <p class="product-stock-note" id="productStockNote" data-product-id="<?= (int)$product['product_id'] ?>">
                            <?php if ($is_low_stock): ?>
                                <span class="stock-urgent">Only <?= $display_stock_qty ?> left</span>
                            <?php else: ?>
                                <span class="stock-available">Available</span>
                            <?php endif; ?>
                        </p>
                    <?php else: ?>
                        <span class="stock-unavailable">Out of Stock</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($display_stock_qty > 0): ?>
                <form method="post" id="add-to-cart-form">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">
                    <input type="hidden" name="variant_id" id="selected-variant-id" value="">
                    <input type="hidden" name="size_label" id="selected-size-label" value="">

                    <?php if (!empty($variants)): ?>
                    <div class="product-size-wrap">
                        <div class="product-size-label-row">
                            <span class="product-size-heading">Size</span>
                        </div>
                        <div class="product-size-grid" role="group" aria-label="Select size">
                            <?php foreach ($variants as $v):
                                $available = (int)$v['quantity'] > 0;
                                $mod = (float)$v['price_modifier'];
                            ?>
                                <button type="button"
                                    class="product-size-btn<?= !$available ? ' oos' : '' ?>"
                                    data-variant-id="<?= $v['variant_id'] ?>"
                                    data-size="<?= e($v['size']) ?>"
                                    data-qty="<?= max(0, (int)$v['quantity']) ?>"
                                    data-price-mod="<?= $mod ?>"
                                    <?= !$available ? 'disabled aria-disabled="true"' : '' ?>
                                    aria-label="Size <?= e($v['size']) ?><?= !$available ? ', out of stock' : '' ?>">
                                    <?= e($v['size']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="product-qty-wrap">
                        <label class="qty-label">Quantity</label>
                        <div class="qty-stepper">
                            <button type="button" class="qty-stepper-btn" id="qty-minus" aria-label="Decrease quantity">−</button>
                            <input type="number" id="qty" name="qty" value="1" min="1" max="<?= $display_stock_qty ?>" class="product-qty-input" aria-label="Quantity" readonly>
                            <button type="button" class="qty-stepper-btn" id="qty-plus" aria-label="Increase quantity">+</button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <div class="product-purchase-actions">
                <?php if ($display_stock_qty > 0): ?>
                    <button type="submit" form="add-to-cart-form" id="add-to-cart-btn-main" class="btn primary product-detail-cta product-detail-cta-sync"<?= !empty($variants) ? ' disabled aria-disabled="true"' : '' ?>>
                        Add to Cart
                    </button>
                <?php else: ?>
                    <button class="btn product-detail-cta" disabled>Out of Stock</button>
                <?php endif; ?>
                
                <div class="product-secondary-actions">
                    <?php $is_saved = in_array($product['product_id'], $wishlist_ids, true); ?>
                    <button
                        type="button"
                        class="btn wishlist-toggle-btn product-action-secondary-btn <?= $is_saved ? 'saved' : '' ?>"
                        data-product-id="<?= (int)$product['product_id'] ?>"
                        data-csrf="<?= csrf_token() ?>"
                        data-guest="<?= !is_logged_in() ? 'true' : 'false' ?>"
                        data-wishlist-icon="1"
                        aria-label="<?= $is_saved ? 'Remove from wishlist' : 'Add to wishlist' ?>"
                        aria-pressed="<?= $is_saved ? 'true' : 'false' ?>"
                    >
                        <svg class="product-wishlist-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                            <path d="M20.8 4.6a5.5 5.5 0 00-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 00-7.8 7.8l1.1 1 7.8 7.8 7.8-7.7 1-1.1a5.5 5.5 0 000-7.8z" <?= $is_saved ? 'fill="currentColor"' : '' ?>></path>
                        </svg>
                    </button>

                    <button type="button" class="btn product-action-secondary-btn product-share-trigger" data-share-url="<?= e($share_url) ?>" data-share-title="<?= e($product['name']) ?>" aria-label="Share">
                        <svg class="product-share-trigger-icon" width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                            <polyline points="16 6 12 2 8 6"></polyline>
                            <line x1="12" y1="2" x2="12" y2="15"></line>
                        </svg>
                    </button>
                </div>
            </div>

            </div>

        </div>

        <div class="product-mobile-buybar" id="productMobileBuybar" aria-label="Purchase actions">
            <div class="product-mobile-buybar-actions">
                <?php $is_saved = in_array($product['product_id'], $wishlist_ids, true); ?>
                <button
                    type="button"
                    class="btn wishlist-toggle-btn mobile-buybar-action-btn <?= $is_saved ? 'saved' : '' ?>"
                    data-product-id="<?= (int)$product['product_id'] ?>"
                    data-csrf="<?= csrf_token() ?>"
                    data-guest="<?= !is_logged_in() ? 'true' : 'false' ?>"
                    data-wishlist-icon="1"
                    aria-label="Wishlist"
                >
                    <svg class="product-wishlist-icon-svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                        <path d="M20.8 4.6a5.5 5.5 0 00-7.7 0l-1.1 1-1.1-1a5.5 5.5 0 00-7.8 7.8l1.1 1 7.8 7.8 7.8-7.7 1-1.1a5.5 5.5 0 000-7.8z" <?= $is_saved ? 'fill="currentColor"' : '' ?>></path>
                    </svg>
                </button>

                <button type="button" class="btn mobile-buybar-action-btn product-share-trigger" data-share-url="<?= e($share_url) ?>" data-share-title="<?= e($product['name']) ?>" aria-label="Share">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"></path>
                        <polyline points="16 6 12 2 8 6"></polyline>
                        <line x1="12" y1="2" x2="12" y2="15"></line>
                    </svg>
                </button>

                <?php if ($display_stock_qty > 0): ?>
                    <button
                        type="submit"
                        form="add-to-cart-form"
                        id="mobile-add-to-cart-btn"
                        class="btn primary mobile-buybar-primary-btn product-detail-cta-sync"
                        <?= !empty($variants) ? 'disabled aria-disabled="true"' : '' ?>
                    >
                        Add to Cart
                    </button>
                <?php else: ?>
                    <button class="btn mobile-buybar-primary-btn" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="product-gallery-support">
            <details class="product-info-accordion" open>
                <summary class="accordion-header">
                    <span>Product Details</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="accordion-icon"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </summary>
                <div class="accordion-content">
                    <p class="product-detail-description">
                        <?= e($product['description']) ?>
                    </p>
                    <div class="specs-grid">
                        <div class="spec-item">
                            <span class="spec-label">Condition</span>
                            <span class="spec-value"><?= e($product['condition_label'] ?? 'New') ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Source</span>
                            <span class="spec-value"><?= e($product['source_label'] ?? 'London, UK') ?></span>
                        </div>
                    </div>
                </div>
            </details>

            <details class="product-info-accordion">
                <summary class="accordion-header">
                    <span>How to Find Your Size</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="accordion-icon"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </summary>
                <div class="accordion-content">
                    <p class="size-guide-help">Measure bust/chest, waist, and hips in cm, then match the closest range. For shoes, use heel-to-toe foot length in cm.</p>
                    <div class="size-chart-wrap" role="region" aria-label="Category size chart">
                        <table class="size-chart-table">
                            <thead>
                                <tr>
                                    <th>Size</th>
                                    <th>UK / Intl</th>
                                    <th>Guide</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($size_chart_rows as $row): ?>
                                    <tr>
                                        <td><?= e((string)$row[0]) ?></td>
                                        <td><?= e((string)$row[1]) ?></td>
                                        <td><?= e((string)$row[2]) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </details>

            <details class="product-info-accordion">
                <summary class="accordion-header">
                    <span>Shipping & Returns</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="accordion-icon"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </summary>
                <div class="accordion-content">
                    <p>Nigeria Nationwide Delivery: 7-14 business days.</p>
                    <p>Same-day pickup available at selected Lagos hubs.</p>
                    <p>Easy Returns: 14 days from delivery for a full refund (item must be unworn and labels intact).</p>
                </div>
            </details>
        </div>
    </div>

    <section class="product-reviews product-reviews-standalone" id="product-reviews" aria-label="Customer reviews">
        <h3 class="product-section-title">Customer Reviews</h3>

        <?php $has_reviews = ((int)($review_summary['review_count'] ?? 0)) > 0; ?>
        <?php if ($has_reviews): ?>
            <div class="review-breakdown" aria-label="Rating distribution">
                <?php for ($star = 5; $star >= 1; $star--): ?>
                    <?php
                        $count = (int)($review_breakdown['ratings'][$star] ?? 0);
                        $pct = (float)($review_breakdown['percentages'][$star] ?? 0);
                    ?>
                    <div class="review-breakdown-row">
                        <span class="review-breakdown-label"><?= $star ?> star</span>
                        <div class="review-breakdown-bar" aria-hidden="true">
                            <span style="width: <?= number_format($pct, 1, '.', '') ?>%;"></span>
                        </div>
                        <span class="review-breakdown-count"><?= $count ?></span>
                    </div>
                <?php endfor; ?>
            </div>

            <form method="get" class="review-controls" aria-label="Sort and filter reviews">
                <input type="hidden" name="id" value="<?= (int)$product['product_id'] ?>">
                <label for="review-sort">Sort</label>
                <select id="review-sort" name="review_sort">
                    <option value="most_recent" <?= $review_sort === 'most_recent' ? 'selected' : '' ?>>Most recent</option>
                    <option value="most_helpful" <?= $review_sort === 'most_helpful' ? 'selected' : '' ?>>Most helpful</option>
                    <option value="highest_rating" <?= $review_sort === 'highest_rating' ? 'selected' : '' ?>>Highest rating</option>
                    <option value="lowest_rating" <?= $review_sort === 'lowest_rating' ? 'selected' : '' ?>>Lowest rating</option>
                </select>

                <label for="review-rating">Filter</label>
                <select id="review-rating" name="review_rating">
                    <option value="0" <?= $review_rating_filter === 0 ? 'selected' : '' ?>>All ratings</option>
                    <?php for ($star = 5; $star >= 1; $star--): ?>
                        <option value="<?= $star ?>" <?= $review_rating_filter === $star ? 'selected' : '' ?>><?= $star ?> star</option>
                    <?php endfor; ?>
                </select>

                <label class="review-control-check"><input type="checkbox" name="review_media" value="1" <?= $review_media_only ? 'checked' : '' ?>> With photos/videos</label>
                <label class="review-control-check"><input type="checkbox" name="review_verified" value="1" <?= $review_verified_only ? 'checked' : '' ?>> Verified purchase only</label>

                <label for="review-q" class="visually-hidden">Search reviews</label>
                <input id="review-q" type="search" name="review_q" value="<?= e($review_search) ?>" placeholder="Search reviews" aria-label="Search reviews">

                <button type="submit" class="btn">Apply</button>
                <a href="<?= BASE_URL ?>/product.php?id=<?= (int)$product['product_id'] ?>" class="btn">Reset</a>
            </form>

            <?php
                $chip_base = ['id' => (int)$product['product_id']];
                if ($review_sort !== '') $chip_base['review_sort'] = $review_sort;
                if ($review_rating_filter > 0) $chip_base['review_rating'] = $review_rating_filter;
                if ($review_media_only) $chip_base['review_media'] = 1;
                if ($review_verified_only) $chip_base['review_verified'] = 1;
                if ($review_search !== '') $chip_base['review_q'] = $review_search;
            ?>
            <?php if (count($chip_base) > 1): ?>
                <div class="review-active-filters" aria-label="Active review filters">
                    <?php
                        $clear_all_url = BASE_URL . '/product.php?id=' . (int)$product['product_id'];
                    ?>
                    <?php if ($review_sort !== ''): ?>
                        <?php
                            $params = $chip_base;
                            unset($params['review_sort'], $params['review_page']);
                        ?>
                        <a class="review-filter-chip" href="<?= e(BASE_URL . '/product.php?' . http_build_query($params)) ?>">Sort: <?= e(str_replace('_', ' ', $review_sort)) ?> &times;</a>
                    <?php endif; ?>
                    <?php if ($review_rating_filter > 0): ?>
                        <?php
                            $params = $chip_base;
                            unset($params['review_rating'], $params['review_page']);
                        ?>
                        <a class="review-filter-chip" href="<?= e(BASE_URL . '/product.php?' . http_build_query($params)) ?>">Rating: <?= (int)$review_rating_filter ?> star &times;</a>
                    <?php endif; ?>
                    <?php if ($review_media_only): ?>
                        <?php
                            $params = $chip_base;
                            unset($params['review_media'], $params['review_page']);
                        ?>
                        <a class="review-filter-chip" href="<?= e(BASE_URL . '/product.php?' . http_build_query($params)) ?>">With media &times;</a>
                    <?php endif; ?>
                    <?php if ($review_verified_only): ?>
                        <?php
                            $params = $chip_base;
                            unset($params['review_verified'], $params['review_page']);
                        ?>
                        <a class="review-filter-chip" href="<?= e(BASE_URL . '/product.php?' . http_build_query($params)) ?>">Verified only &times;</a>
                    <?php endif; ?>
                    <?php if ($review_search !== ''): ?>
                        <?php
                            $params = $chip_base;
                            unset($params['review_q'], $params['review_page']);
                        ?>
                        <a class="review-filter-chip" href="<?= e(BASE_URL . '/product.php?' . http_build_query($params)) ?>">Search: <?= e($review_search) ?> &times;</a>
                    <?php endif; ?>
                    <a class="review-filter-clear-all" href="<?= e($clear_all_url) ?>">Clear all</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p class="review-empty review-empty-intro">No customer reviews yet. Purchase this item to share the first review.</p>
        <?php endif; ?>

        <?php if (!empty($product_reviews)): ?>
            <div class="review-list">
                <?php foreach ($product_reviews as $review): ?>
                    <article class="review-item">
                        <div class="review-head">
                            <strong class="review-author"><?= e($review['username']) ?></strong>
                            <?php
                                $review_rating = max(1, min(5, (int)$review['rating']));
                                $review_fill_pct = max(0, min(100, ($review_rating / 5) * 100));
                            ?>
                            <span class="review-rating" style="--rating-fill: <?= number_format($review_fill_pct, 1, '.', '') ?>%;" aria-label="Rating <?= $review_rating ?> out of 5">
                                <span class="review-rating-base" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                                <span class="review-rating-fill" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                            </span>
                        </div>
                        <?php if (trim((string)($review['title'] ?? '')) !== ''): ?>
                            <h4 class="review-title"><?= e($review['title']) ?></h4>
                        <?php endif; ?>
                        <?php if ((int)($review['is_verified_purchase'] ?? 0) === 1): ?>
                            <p class="review-verified">Verified Purchase</p>
                        <?php endif; ?>
                        <p class="review-text"><?= nl2br(e($review['review_text'])) ?></p>
                        <?php
                            $review_media_url = (string)(sanitize_local_upload_media_url((string)($review['media_url'] ?? '')) ?? '');
                            $review_media_path = strtolower((string)parse_url($review_media_url, PHP_URL_PATH));
                            $review_media_ext = strtolower(pathinfo($review_media_path, PATHINFO_EXTENSION));
                            $review_media_is_image = in_array($review_media_ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true);
                        ?>
                        <?php if ($review_media_url !== ''): ?>
                            <?php $signed_review_media_url = build_signed_media_url($review_media_url, 1800); ?>
                            <?php if ($review_media_is_image): ?>
                                <a class="review-media-thumb-link" href="<?= e($signed_review_media_url) ?>" target="_blank" rel="noopener">
                                    <img class="review-media-thumb" src="<?= e($signed_review_media_url) ?>" alt="Customer photo for this review" loading="lazy" decoding="async" width="240" height="240">
                                </a>
                            <?php else: ?>
                                <p class="review-media"><a href="<?= e($signed_review_media_url) ?>" target="_blank" rel="noopener">Watch customer video/media</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                        <p class="review-meta">Reviewed on <?= date('M d, Y', strtotime($review['created_at'])) ?></p>
                        <div class="review-helpful-row">
                            <p class="review-helpful-count"><?= (int)($review['helpful_count'] ?? 0) ?> found this helpful</p>
                            <?php if (is_logged_in() && (int)$review['user_id'] !== (int)current_user_id()): ?>
                                <?php $already_voted = !empty($user_helpful_votes[(int)$review['review_id']]); ?>
                                <form method="post" class="review-helpful-form">
                                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                    <input type="hidden" name="action" value="mark_review_helpful">
                                    <input type="hidden" name="review_id" value="<?= (int)$review['review_id'] ?>">
                                    <button type="submit" class="btn review-helpful-btn" <?= $already_voted ? 'disabled' : '' ?>>
                                        <?= $already_voted ? 'Marked Helpful' : 'Helpful' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($review_total_pages > 1): ?>
                <?php
                    $base_review_params = [
                        'id' => (int)$product['product_id'],
                        'review_sort' => $review_sort,
                    ];
                    if ($review_rating_filter > 0) {
                        $base_review_params['review_rating'] = $review_rating_filter;
                    }
                    if ($review_media_only) {
                        $base_review_params['review_media'] = 1;
                    }
                    if ($review_verified_only) {
                        $base_review_params['review_verified'] = 1;
                    }
                    if ($review_search !== '') {
                        $base_review_params['review_q'] = $review_search;
                    }
                    $build_review_page_url = static function (int $target_page) use ($base_review_params): string {
                        return BASE_URL . '/product.php?' . http_build_query(array_merge($base_review_params, ['review_page' => $target_page]));
                    };
                ?>
                <div class="review-pagination" aria-label="Review pages">
                    <?php if ($review_page > 1): ?>
                        <a class="review-page-link" href="<?= e($build_review_page_url($review_page - 1)) ?>">Previous</a>
                    <?php else: ?>
                        <span class="review-page-link disabled">Previous</span>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= $review_total_pages; $p++): ?>
                        <?php if ($p === $review_page): ?>
                            <span class="review-page-link active"><?= $p ?></span>
                        <?php else: ?>
                            <a class="review-page-link" href="<?= e($build_review_page_url($p)) ?>"><?= $p ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($review_page < $review_total_pages): ?>
                        <a class="review-page-link" href="<?= e($build_review_page_url($review_page + 1)) ?>">Next</a>
                    <?php else: ?>
                        <span class="review-page-link disabled">Next</span>
                    <?php endif; ?>
                </div>
                <p class="review-page-summary">
                    Showing <?= (int)$review_offset + 1 ?> to <?= min((int)$review_offset + count($product_reviews), (int)$review_total) ?> of <?= (int)$review_total ?> reviews
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p class="review-empty">Be the first to review this product.</p>
        <?php endif; ?>

        <div class="review-form-wrap">
            <?php if (!is_logged_in()): ?>
                <div class="review-guest-prompt">
                    <p class="review-help">Purchased this item? <a href="<?= BASE_URL ?>/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/') ?>" class="review-write-link">Write a review</a> to share your experience.</p>
                </div>
            <?php elseif (!$can_review_product): ?>
                <div class="review-exclusive-prompt" style="padding: 1.5rem; border: 1px solid var(--border-color); border-radius: 4px; text-align: center; margin-bottom: 2rem;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-color);">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span style="font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; font-size: 0.75rem;">Verified Access Only</span>
                    </div>
                    <p class="review-help" style="margin: 0; font-style: italic; color: var(--text-secondary);">Our review community is exclusive to verified owners of this piece.</p>
                </div>
            <?php else: ?>
                <form method="post" class="review-form" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="submit_review">

                    <div class="review-form-row">
                        <label for="rating">Rating (1 to 5)</label>
                        <select id="rating" name="rating" required>
                            <?php $selected_rating = (int)($user_review['rating'] ?? 0); ?>
                            <option value="">Select rating</option>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= $selected_rating === $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="review-form-row">
                        <label for="review_title">Title (optional)</label>
                        <input type="text" id="review_title" name="review_title" maxlength="120" value="<?= e((string)($user_review['title'] ?? '')) ?>">
                    </div>

                    <div class="review-form-row">
                        <label for="review_text">Your review</label>
                        <textarea id="review_text" name="review_text" rows="4" minlength="10" required><?= e((string)($user_review['review_text'] ?? '')) ?></textarea>
                    </div>

                    <div class="review-form-row">
                        <label for="review_media_file">Upload photo (optional)</label>
                        <input type="file" id="review_media_file" name="review_media_file" accept="image/jpeg,image/png,image/webp,image/gif">
                        <p class="review-help" id="reviewUploadStatusText">Upload JPG, PNG, WEBP, or GIF (max 5MB).</p>
                    </div>

                    <button type="submit" class="btn primary"><?= $user_review ? 'Update Review' : 'Submit Review' ?></button>

                    <?php if (!empty($user_review)): ?>
                        <p class="review-help">Current review status: <?= e(ucfirst((string)$user_review['status'])) ?>.</p>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </section>

    <div class="review-lightbox" id="reviewLightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Review image preview">
        <div class="review-lightbox-backdrop" data-review-lightbox-close></div>
        <div class="review-lightbox-dialog" role="document">
            <button type="button" class="review-lightbox-close" data-review-lightbox-close aria-label="Close image preview">Close</button>
            <img id="reviewLightboxImage" class="review-lightbox-image" src="" alt="Expanded customer review image">
        </div>
    </div>

    <?php if (!empty($related_products)): ?>
        <section class="related-products-section">
            <div class="related-products-head">
                <h3 class="related-products-title">Related Products</h3>
                <?php if ($has_more_related): ?>
                    <a class="related-products-more" href="<?= BASE_URL ?>/shop.php?category=<?= $product['category_id'] ?>">View More</a>
                <?php endif; ?>
            </div>
            <div class="product-grid related-products-grid">
                <?php foreach ($related_products as $product): ?>
                    <?php include __DIR__ . '/includes/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

<?php else: ?>
    <div class="product-missing">
        <h2>Product Not Found</h2>
        <p>The product you're looking for doesn't exist.</p>
        <a href="<?= BASE_URL ?>/shop.php" class="btn primary product-missing-link">
            Return to Shop
        </a>
    </div>
<?php endif; ?>

<div class="product-gallery-lightbox" id="productGalleryLightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Product image gallery">
    <div class="product-gallery-lightbox-backdrop" data-gallery-lightbox-close></div>
    <div class="product-gallery-lightbox-dialog" role="document">
        <button type="button" class="product-gallery-lightbox-close" data-gallery-lightbox-close aria-label="Close fullscreen gallery">Close</button>
        <button type="button" class="product-gallery-lightbox-nav prev" id="productGalleryLightboxPrev" aria-label="Previous image">&#8249;</button>
        <img id="productGalleryLightboxImage" class="product-gallery-lightbox-image" src="" alt="Fullscreen product image">
        <button type="button" class="product-gallery-lightbox-nav next" id="productGalleryLightboxNext" aria-label="Next image">&#8250;</button>
    </div>
</div>

<script>
    // Product gallery carousel + touch swipe + fullscreen lightbox
    (function () {
        var galleryWrap = document.querySelector('.product-gallery-wrap');
        if (!galleryWrap) return;

        var gallery = document.getElementById('productGallery');
        if (!gallery) return;

        var mainImage = document.getElementById('productMainImage');
        var mainVideo = document.getElementById('productMainVideo');
        var mainVideoSource = document.getElementById('productMainVideoSource');
        var mediaWrap = gallery.querySelector('.product-main-image-wrap');
        var prevBtn = document.getElementById('productGalleryPrev');
        var nextBtn = document.getElementById('productGalleryNext');
        var fullscreenBtn = document.getElementById('productGalleryFullscreen');
        var lightbox = document.getElementById('productGalleryLightbox');
        var lightboxImage = document.getElementById('productGalleryLightboxImage');
        var lightboxPrev = document.getElementById('productGalleryLightboxPrev');
        var lightboxNext = document.getElementById('productGalleryLightboxNext');
        var lightboxClosers = Array.prototype.slice.call(document.querySelectorAll('[data-gallery-lightbox-close]'));

        if (!mainImage) return;

        var thumbs = Array.prototype.slice.call(galleryWrap.querySelectorAll('.product-thumb-btn'));
        var dots = Array.prototype.slice.call(galleryWrap.querySelectorAll('.product-gallery-dot'));
        var mediaItems = thumbs.map(function (thumb) {
            return {
                type: (thumb.getAttribute('data-media-type') || 'image').toLowerCase(),
                src: thumb.getAttribute('data-main-src') || '',
                mime: thumb.getAttribute('data-video-mime') || 'video/mp4'
            };
        }).filter(function (item) { return item.src !== ''; });

        if (!mediaItems.length) return;

        var currentIndex = 0;

        function updateActiveState(targetIndex) {
            currentIndex = targetIndex;
            var currentMedia = mediaItems[currentIndex];
            var isVideo = currentMedia.type === 'video';

            if (isVideo) {
                if (mainImage) mainImage.hidden = true;
                if (mainVideo && mainVideoSource) {
                    mainVideo.hidden = false;
                    var currentSrc = mainVideoSource.getAttribute('src') || '';
                    if (currentSrc !== currentMedia.src) {
                        mainVideo.pause();
                        mainVideoSource.setAttribute('src', currentMedia.src);
                        mainVideoSource.setAttribute('type', currentMedia.mime || 'video/mp4');
                        mainVideo.load();
                    }
                }
                if (fullscreenBtn) fullscreenBtn.hidden = true;
                gallery.classList.add('is-video-slide');
            } else {
                if (mainVideo) {
                    mainVideo.pause();
                    mainVideo.hidden = true;
                }
                if (mainImage) {
                    mainImage.hidden = false;
                    mainImage.setAttribute('src', currentMedia.src);
                }
                if (fullscreenBtn) fullscreenBtn.hidden = false;
                gallery.classList.remove('is-video-slide');
            }

            thumbs.forEach(function (item, index) {
                var active = index === currentIndex;
                item.classList.toggle('active', active);
                item.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            dots.forEach(function (dot, index) {
                var active = index === currentIndex;
                dot.classList.toggle('active', active);
                dot.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        function goTo(delta) {
            var nextIndex = (currentIndex + delta + mediaItems.length) % mediaItems.length;
            updateActiveState(nextIndex);
        }

        thumbs.forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                var targetIndex = parseInt(thumb.getAttribute('data-gallery-index') || '0', 10);
                if (Number.isNaN(targetIndex)) return;
                updateActiveState(targetIndex);
            });
        });

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var targetIndex = parseInt(dot.getAttribute('data-gallery-index') || '0', 10);
                if (Number.isNaN(targetIndex)) return;
                updateActiveState(targetIndex);
            });
        });

        if (prevBtn) prevBtn.addEventListener('click', function () { goTo(-1); });
        if (nextBtn) nextBtn.addEventListener('click', function () { goTo(1); });

        var touchStartX = 0;
        var touchEndX = 0;

        if (mediaWrap) {
            mediaWrap.addEventListener('touchstart', function (event) {
                touchStartX = event.changedTouches[0].screenX;
            }, { passive: true });

            mediaWrap.addEventListener('touchend', function (event) {
                touchEndX = event.changedTouches[0].screenX;
                var diff = touchEndX - touchStartX;
                if (Math.abs(diff) < 40) return;
                if (diff > 0) goTo(-1);
                else goTo(1);
            }, { passive: true });
        }

        function openLightbox() {
            if (!lightbox || !lightboxImage) return;
            var currentMedia = mediaItems[currentIndex];
            if (currentMedia.type !== 'image') return;
            lightboxImage.setAttribute('src', currentMedia.src);
            lightbox.setAttribute('aria-hidden', 'false');
            lightbox.classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            if (!lightbox) return;
            lightbox.setAttribute('aria-hidden', 'true');
            lightbox.classList.remove('open');
            document.body.style.overflow = '';
        }

        function syncLightboxImage() {
            if (lightbox && lightbox.classList.contains('open') && lightboxImage) {
                var currentMedia = mediaItems[currentIndex];
                if (currentMedia.type === 'image') {
                    lightboxImage.setAttribute('src', currentMedia.src);
                } else {
                    closeLightbox();
                }
            }
        }

        if (fullscreenBtn) fullscreenBtn.addEventListener('click', openLightbox);
        if (mainImage) mainImage.addEventListener('click', openLightbox);
        if (lightboxPrev) lightboxPrev.addEventListener('click', function () { goTo(-1); syncLightboxImage(); });
        if (lightboxNext) lightboxNext.addEventListener('click', function () { goTo(1); syncLightboxImage(); });

        var lightboxStartX = 0;
        var lightboxEndX = 0;
        if (lightbox) {
            lightbox.addEventListener('touchstart', function (event) {
                lightboxStartX = event.changedTouches[0].screenX;
            }, { passive: true });
            lightbox.addEventListener('touchend', function (event) {
                lightboxEndX = event.changedTouches[0].screenX;
                var diff = lightboxEndX - lightboxStartX;
                if (Math.abs(diff) < 50) return;
                if (diff > 0) goTo(-1);
                else goTo(1);
                syncLightboxImage();
            }, { passive: true });
        }
        lightboxClosers.forEach(function (node) { node.addEventListener('click', closeLightbox); });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') {
                goTo(-1);
                syncLightboxImage();
            } else if (event.key === 'ArrowRight') {
                goTo(1);
                syncLightboxImage();
            } else if (event.key === 'Escape') {
                closeLightbox();
            }
        });

        updateActiveState(0);
    })();

    // Share is handled globally by initQuickShare() in hamburger-menu.js

    // Size selector
    (function () {
        var sizeBtns = Array.prototype.slice.call(document.querySelectorAll('.product-size-btn'));
        if (!sizeBtns.length) return;

        var variantInput  = document.getElementById('selected-variant-id');
        var sizeLabelInput = document.getElementById('selected-size-label');
        var selectedText  = document.getElementById('size-selected-text');
        var ctaButtons = Array.prototype.slice.call(document.querySelectorAll('.product-detail-cta-sync'));
        var qtyInput = document.getElementById('qty');
        var stockNote = document.getElementById('productStockNote');
        var stockLabel = document.querySelector('.product-stock-label');

        function applySizeSelection(btn) {
            sizeBtns.forEach(function (b) { b.classList.remove('selected'); });
            btn.classList.add('selected');

            var vid  = btn.getAttribute('data-variant-id');
            var size = btn.getAttribute('data-size');
            var vqty = parseInt(btn.getAttribute('data-qty') || '0', 10) || 0;

            if (variantInput)   variantInput.value   = vid;
            if (sizeLabelInput) sizeLabelInput.value = size;
            if (selectedText)   selectedText.textContent = size;
            if (qtyInput) {
                qtyInput.max = String(Math.max(1, vqty));
                if (parseInt(qtyInput.value || '1', 10) > vqty) {
                    qtyInput.value = String(Math.max(1, vqty));
                }
            }
            ctaButtons.forEach(function (btn) {
                if (vqty > 0) {
                    btn.disabled = false;
                    btn.removeAttribute('aria-disabled');
                } else {
                    btn.disabled = true;
                    btn.setAttribute('aria-disabled', 'true');
                }
            });
            if (stockLabel) {
                stockLabel.classList.remove('in', 'low', 'out');
                if (vqty <= 0) {
                    stockLabel.textContent = 'Out of Stock';
                    stockLabel.classList.add('out');
                } else if (vqty <= 5) {
                    stockLabel.textContent = 'Low Stock';
                    stockLabel.classList.add('low');
                } else {
                    stockLabel.textContent = 'In Stock';
                    stockLabel.classList.add('in');
                }
            }
            if (stockNote) {
                stockNote.textContent = vqty <= 0 ? 'Unavailable for selected size' : (vqty <= 5 ? 'Only ' + vqty + ' left in this size' : vqty + ' available in this size');
            }
        }

        sizeBtns.forEach(function (btn) {
            btn.addEventListener('pointerdown', function (event) {
                if (event.pointerType === 'touch') {
                    applySizeSelection(btn);
                }
            });

            btn.addEventListener('click', function () {
                applySizeSelection(btn);
            });
        });
    })();

    // Live stock updater
    (function () {
        var stockNote = document.getElementById('productStockNote');
        var stockLabel = document.querySelector('.product-stock-label');
        var ctaButtons = Array.prototype.slice.call(document.querySelectorAll('.product-detail-cta-sync'));
        var selectedVariantInput = document.getElementById('selected-variant-id');
        if (!stockNote || !stockLabel) return;

        var productId = stockNote.getAttribute('data-product-id');
        if (!productId) return;

        function refreshStock() {
            var url = '<?= BASE_URL ?>/api/product-stock.php?product_id=' + encodeURIComponent(productId);
            var selectedVariantId = selectedVariantInput ? (selectedVariantInput.value || '') : '';
            if (selectedVariantId) {
                url += '&variant_id=' + encodeURIComponent(selectedVariantId);
            }
            fetch(url, { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || !data.ok) return;

                    stockLabel.textContent = data.stock_label || stockLabel.textContent;
                    stockLabel.classList.remove('in', 'low', 'out');
                    if (data.stock_label === 'Low Stock') {
                        stockLabel.classList.add('low');
                    } else {
                        stockLabel.classList.add(data.in_stock ? 'in' : 'out');
                    }

                    if (stockNote) {
                        stockNote.textContent = data.stock_note || '';
                    }

                    ctaButtons.forEach(function (btn) {
                        if (data.in_stock) {
                            var hasVariants = document.querySelectorAll('.product-size-btn').length > 0;
                            var hasSelectedVariant = !!(selectedVariantInput && selectedVariantInput.value);
                            if (!hasVariants || hasSelectedVariant) {
                                btn.disabled = false;
                                btn.removeAttribute('aria-disabled');
                            } else {
                                btn.disabled = true;
                                btn.setAttribute('aria-disabled', 'true');
                            }
                        } else {
                            btn.disabled = true;
                            btn.setAttribute('aria-disabled', 'true');
                        }
                    });
                })
                .catch(function () {
                    // Keep current UI state on transient network failures.
                });
        }

        window.setInterval(refreshStock, 30000);
    })();

    // Mobile sticky buy bar body offset
    (function () {
        var buyBar = document.getElementById('productMobileBuybar');
        if (!buyBar) return;
        document.body.classList.add('product-page-has-buybar');
    })();

    // Review media lightbox
    (function () {
        var lightbox = document.getElementById('reviewLightbox');
        var lightboxImage = document.getElementById('reviewLightboxImage');
        if (!lightbox || !lightboxImage) return;

        var closeTriggers = Array.prototype.slice.call(lightbox.querySelectorAll('[data-review-lightbox-close]'));
        var thumbLinks = Array.prototype.slice.call(document.querySelectorAll('.review-media-thumb-link'));
        if (!thumbLinks.length) return;

        function closeLightbox() {
            lightbox.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('review-lightbox-open');
            lightboxImage.setAttribute('src', '');
        }

        thumbLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                var imageHref = link.getAttribute('href') || '';
                if (!imageHref) return;

                lightboxImage.setAttribute('src', imageHref);
                lightbox.setAttribute('aria-hidden', 'false');
                document.body.classList.add('review-lightbox-open');
            });
        });

        closeTriggers.forEach(function (btn) {
            btn.addEventListener('click', closeLightbox);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && lightbox.getAttribute('aria-hidden') === 'false') {
                closeLightbox();
            }
        });
    })();

    // Share is handled globally by initQuickShare() in hamburger-menu.js

    // Quantity stepper
    (function () {
        var input    = document.getElementById('qty');
        var minusBtn = document.getElementById('qty-minus');
        var plusBtn  = document.getElementById('qty-plus');
        if (!input || !minusBtn || !plusBtn) return;

        function update() {
            var val = parseInt(input.value, 10) || 1;
            var min = parseInt(input.min, 10) || 1;
            var max = parseInt(input.max, 10) || 99;
            minusBtn.disabled = val <= min;
            plusBtn.disabled  = val >= max;
        }

        minusBtn.addEventListener('click', function () {
            var val = parseInt(input.value, 10) || 1;
            var min = parseInt(input.min, 10) || 1;
            if (val > min) { input.value = val - 1; update(); }
        });

        plusBtn.addEventListener('click', function () {
            var val = parseInt(input.value, 10) || 1;
            var max = parseInt(input.max, 10) || 99;
            if (val < max) { input.value = val + 1; update(); }
        });

        update();
    })();
</script>

<?php include __DIR__ . '/inc_footer.php'; ?>





