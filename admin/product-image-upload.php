<?php
require_once __DIR__ . '/../functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request.');
}

if (!verify_csrf($_POST['csrf'] ?? '')) {
    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'successCount' => 0, 'errors' => ['Security token invalid. Please refresh the page and try again.']]);
        exit;
    }
    header("Location: " . BASE_URL . "/admin/products.php");
    exit;
}

$action = $_POST['action'] ?? '';
$product_id = (int)($_POST['product_id'] ?? 0);

if ($product_id <= 0) {
    die('Invalid product.');
}

$pdo = get_pdo();

if ($action === 'set_primary') {
    $image_id = (int)($_POST['image_id'] ?? 0);
    // Reset all to 0 for this product
    $pdo->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?")
        ->execute([$product_id]);
    // Set selected to 1
    $pdo->prepare("UPDATE product_images SET is_primary = 1 WHERE image_id = ? AND product_id = ?")
        ->execute([$image_id, $product_id]);
    
    header("Location: " . BASE_URL . "/admin/product-edit.php?id={$product_id}&img_notice=Primary+image+updated");
    exit;
}

if ($action === 'delete_image') {
    $image_id = (int)($_POST['image_id'] ?? 0);

    // Fetch URL
    $stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE image_id = ? AND product_id = ?");
    $stmt->execute([$image_id, $product_id]);
    $img = $stmt->fetch();

    if ($img) {
        $pdo->prepare("DELETE FROM product_images WHERE image_id = ?")->execute([$image_id]);
        
        // Remove physical file if it's local
        $path = str_replace(BASE_URL . '/', '', $img['image_url']);
        $full_path = realpath(__DIR__ . '/../' . $path);
        if ($full_path && file_exists($full_path)) {
            unlink($full_path);
        }
    }
    
    header("Location: " . BASE_URL . "/admin/product-edit.php?id={$product_id}&img_notice=Image+deleted");
    exit;
}

if ($action === 'update_sort') {
    $orderData = json_decode($_POST['sort_order_data'] ?? '[]', true);
    if (is_array($orderData)) {
        foreach ($orderData as $index => $imgId) {
            $pdo->prepare("UPDATE product_images SET sort_order = ? WHERE image_id = ? AND product_id = ?")
                ->execute([$index, (int)$imgId, $product_id]);
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'upload') {
    // Use the correct Uploads directory (capital U, matching UPLOAD_DIR and sanitize_local_upload_media_url)
    $upload_dir = rtrim(UPLOAD_DIR, '/\\') . '/Products/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $err_msg = json_encode(['success' => false, 'successCount' => 0, 'errors' => ['Server error: could not create upload directory.']]);
            header('Content-Type: application/json');
            echo $err_msg;
            exit;
        }
    }

    $files = $_FILES['images'] ?? null;
    $errors = [];
    $success_count = 0;

    if ($files && is_array($files['name'])) {
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                if (!in_array($ext, $allowed)) {
                    $errors[] = "Invalid file type: " . $files['name'][$i];
                    continue;
                }

                if ($files['size'][$i] > MAX_FILE_SIZE) {
                    $errors[] = basename($files['name'][$i]) . " exceeds the 5MB limit.";
                    continue;
                }

                $new_name = 'prod_' . $product_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $dest = $upload_dir . $new_name;

                if (move_uploaded_file($files['tmp_name'][$i], $dest)) {
                    // URL must contain /Uploads/ (capital U) to pass sanitize_local_upload_media_url()
                    $image_url = BASE_URL . '/Uploads/Products/' . $new_name;

                    // First image gets primary flag
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    $is_primary = ($stmt->fetchColumn() == 0) ? 1 : 0;

                    $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)")
                        ->execute([$product_id, $image_url, $is_primary]);

                    $success_count++;
                } else {
                    $errors[] = "Failed to save file: " . basename($files['name'][$i]);
                }
            } elseif ($files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Upload error code " . $files['error'][$i] . " for file " . basename($files['name'][$i]);
            }
        }
    }

    $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($is_ajax) {
        echo json_encode(['success' => $success_count > 0, 'successCount' => $success_count, 'errors' => $errors]);
        exit;
    }

    $url = BASE_URL . "/admin/product-edit.php?id={$product_id}";
    if (!empty($errors)) {
        $url .= "&img_error=" . urlencode(implode(", ", $errors));
    } elseif ($success_count > 0) {
        $url .= "&img_notice=" . urlencode($success_count . " file(s) uploaded successfully.");
    }
    header("Location: " . $url);
    exit;
}

header("Location: " . BASE_URL . "/admin/products.php");
exit;
