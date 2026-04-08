<?php
/**
 * London Labels - Admin Reviews Moderation
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Reviews Moderation';
require_admin();

$status = trim((string)($_GET['status'] ?? 'all'));
$search = trim((string)($_GET['search'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$page_errors = [];
$page_notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $page_errors[] = 'Invalid CSRF token.';
    } else {
        $review_id = (int)($_POST['review_id'] ?? 0);
        $action = trim((string)($_POST['moderation_action'] ?? ''));

        if ($review_id <= 0) {
            $page_errors[] = 'Invalid review selection.';
        } else {
            if ($action === 'approve') {
                if (update_product_review_status($review_id, 'approved', (int)current_user_id())) {
                    $page_notice = 'Review approved.';
                }
            } elseif ($action === 'reject') {
                if (update_product_review_status($review_id, 'rejected', (int)current_user_id())) {
                    $page_notice = 'Review rejected.';
                }
            } elseif ($action === 'pending') {
                if (update_product_review_status($review_id, 'pending', (int)current_user_id())) {
                    $page_notice = 'Review moved to pending.';
                }
            } elseif ($action === 'delete') {
                if (delete_product_review($review_id)) {
                    $page_notice = 'Review deleted.';
                }
            } else {
                $page_errors[] = 'Invalid moderation action.';
            }
        }
    }
}

$reviews = get_admin_product_reviews($status, $search, $limit, $offset);
$total = count_admin_product_reviews($status, $search);
$total_pages = max(1, (int)ceil($total / $limit));

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle">Moderate customer product reviews.</p>
    </div>
</div>

<div class="admin-reviews-compact">

<form method="get" class="admin-products-filter-bar">
    <label for="status-filter" class="admin-products-filter-label">Status:</label>
    <select id="status-filter" name="status" class="admin-products-filter-select">
        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
    </select>
    <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search by product, user, title..." class="admin-products-filter-select admin-search-input-wide">
    <button class="btn admin-mini-btn" type="submit">Filter</button>
    <a class="btn admin-mini-btn" href="<?= BASE_URL ?>/admin/reviews.php">Reset</a>
</form>

<?php if (empty($reviews)): ?>
    <?php render_empty_state('No Reviews Found', 'No reviews match your current filters.'); ?>
<?php else: ?>
    <div class="admin-table-wrap admin-products-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reviews as $r): ?>
                    <tr>
                        <td>#<?= (int)$r['review_id'] ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/product.php?id=<?= (int)$r['product_id'] ?>" target="_blank" rel="noopener"><?= e($r['product_name']) ?></a>
                        </td>
                        <td><?= e($r['username']) ?></td>
                        <td><?= (int)$r['rating'] ?>/5</td>
                        <td>
                            <?php if (trim((string)$r['title']) !== ''): ?>
                                <strong><?= e($r['title']) ?></strong><br>
                            <?php endif; ?>
                            <?php
                                $review_preview = trim((string)$r['review_text']);
                                if (strlen($review_preview) > 110) {
                                    $review_preview = substr($review_preview, 0, 107) . '...';
                                }
                            ?>
                            <span><?= e($review_preview) ?></span>
                        </td>
                        <td><?= e(ucfirst((string)$r['status'])) ?></td>
                        <td><?= date('M d, Y', strtotime((string)$r['created_at'])) ?></td>
                        <td>
                            <form method="post" class="admin-review-actions-form">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="review_id" value="<?= (int)$r['review_id'] ?>">
                                <button class="btn admin-mini-btn" type="submit" name="moderation_action" value="approve">Approve</button>
                                <button class="btn admin-mini-btn" type="submit" name="moderation_action" value="reject">Reject</button>
                                <button class="btn admin-mini-btn" type="submit" name="moderation_action" value="pending">Pending</button>
                                <button class="btn danger admin-mini-btn admin-confirm-delete-btn" type="submit" name="moderation_action" value="delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php if ($total_pages > 1): ?>
    <?php
        $pp = [];
        if ($status !== 'all') $pp['status'] = $status;
        if ($search !== '') $pp['search'] = $search;
        render_pagination($page, $total_pages, BASE_URL . '/admin/reviews.php', $pp, $total, $limit);
    ?>
<?php endif; ?>

</div>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
