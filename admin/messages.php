<?php
/**
 * London Labels — Admin: Contact Messages Inbox
 */
require_once __DIR__ . '/../functions.php';

$page_title = 'Messages';
require_admin();

$per_page   = 20;
$status_filter = trim($_GET['status'] ?? '');
if (!in_array($status_filter, ['unread', 'read', 'archived'], true)) {
    $status_filter = '';
}
$current_page = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($current_page - 1) * $per_page;

// Handle actions
$notice = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = trim($_POST['action'] ?? '');
        $msg_id = (int)($_POST['msg_id'] ?? 0);

        if ($msg_id > 0) {
            if ($action === 'mark_read') {
                set_contact_message_status($msg_id, 'read');
                $notice = 'Marked as read.';
            } elseif ($action === 'mark_unread') {
                set_contact_message_status($msg_id, 'unread');
                $notice = 'Marked as unread.';
            } elseif ($action === 'archive') {
                set_contact_message_status($msg_id, 'archived');
                $notice = 'Message archived.';
            } elseif ($action === 'delete') {
                delete_contact_message($msg_id);
                $notice = 'Message deleted.';
            }
        }
        header('Location: ' . BASE_URL . '/admin/messages.php' . ($status_filter !== '' ? '?status=' . urlencode($status_filter) : ''));
        exit;
    }
}

$total    = count_contact_messages($status_filter);
$messages = get_contact_messages($per_page, $offset, $status_filter);
$total_pages = max(1, (int)ceil($total / $per_page));

$unread_count = count_contact_messages('unread');

include __DIR__ . '/inc_admin_layout.php';
?>

<div class="admin-page-header">
    <div>
        <p class="admin-page-subtitle">Messages submitted via the contact form.</p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="admin-alert admin-alert-danger" role="alert">
        <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e, ENT_QUOTES) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($notice !== ''): ?>
    <div class="admin-alert admin-alert-success" role="status"><p><?= htmlspecialchars($notice, ENT_QUOTES) ?></p></div>
<?php endif; ?>

<!-- Filter tabs -->
<div class="admin-tab-bar" style="margin-bottom: 20px;">
    <a href="<?= BASE_URL ?>/admin/messages.php" class="admin-tab-link <?= $status_filter === '' ? 'active' : '' ?>">
        All <span class="admin-tab-count"><?= number_format(count_contact_messages()) ?></span>
    </a>
    <a href="<?= BASE_URL ?>/admin/messages.php?status=unread" class="admin-tab-link <?= $status_filter === 'unread' ? 'active' : '' ?>">
        Unread <?php if ($unread_count > 0): ?><span class="admin-tab-count admin-tab-count-alert"><?= $unread_count ?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>/admin/messages.php?status=read" class="admin-tab-link <?= $status_filter === 'read' ? 'active' : '' ?>">
        Read
    </a>
    <a href="<?= BASE_URL ?>/admin/messages.php?status=archived" class="admin-tab-link <?= $status_filter === 'archived' ? 'active' : '' ?>">
        Archived
    </a>
</div>

<?php if (empty($messages)): ?>
    <div class="admin-card">
        <div class="admin-card-body admin-card-body-empty">
            <p class="admin-muted-note">No messages<?= $status_filter !== '' ? ' in this view' : '' ?>.</p>
        </div>
    </div>
<?php else: ?>
    <div class="admin-messages-list">
        <?php foreach ($messages as $msg): ?>
            <?php $is_unread = ($msg['status'] ?? 'unread') === 'unread'; ?>
            <div class="admin-message-card <?= $is_unread ? 'admin-message-unread' : '' ?>">
                <div class="admin-message-meta">
                    <div class="admin-message-sender">
                        <span class="admin-message-name"><?= htmlspecialchars($msg['name'], ENT_QUOTES) ?></span>
                        <?php if ($is_unread): ?>
                            <span class="admin-message-badge">New</span>
                        <?php endif; ?>
                        <?php if (($msg['status'] ?? '') === 'archived'): ?>
                            <span class="admin-message-badge admin-message-badge-muted">Archived</span>
                        <?php endif; ?>
                    </div>
                    <span class="admin-message-date"><?= date('M d, Y · g:ia', strtotime($msg['created_at'])) ?></span>
                </div>

                <div class="admin-message-contact">
                    <a href="mailto:<?= htmlspecialchars($msg['email'], ENT_QUOTES) ?>?subject=Re%3A%20<?= rawurlencode($msg['subject']) ?>" class="admin-message-email">
                        <?= htmlspecialchars($msg['email'], ENT_QUOTES) ?>
                    </a>
                    <span class="admin-message-subject-tag"><?= htmlspecialchars($msg['subject'], ENT_QUOTES) ?></span>
                </div>

                <p class="admin-message-body"><?= nl2br(htmlspecialchars($msg['message'], ENT_QUOTES)) ?></p>

                <div class="admin-message-actions">
                    <!-- Reply -->
                    <a href="mailto:<?= htmlspecialchars($msg['email'], ENT_QUOTES) ?>?subject=Re%3A%20<?= rawurlencode($msg['subject']) ?>" class="btn admin-mini-btn">
                        Reply
                    </a>

                    <!-- Mark read/unread -->
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                        <input type="hidden" name="msg_id" value="<?= (int)$msg['id'] ?>">
                        <input type="hidden" name="action" value="<?= $is_unread ? 'mark_read' : 'mark_unread' ?>">
                        <button type="submit" class="btn admin-mini-btn">
                            <?= $is_unread ? 'Mark Read' : 'Mark Unread' ?>
                        </button>
                    </form>

                    <!-- Archive (only if not already archived) -->
                    <?php if (($msg['status'] ?? '') !== 'archived'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                            <input type="hidden" name="msg_id" value="<?= (int)$msg['id'] ?>">
                            <input type="hidden" name="action" value="archive">
                            <button type="submit" class="btn admin-mini-btn">Archive</button>
                        </form>
                    <?php endif; ?>

                    <!-- Delete -->
                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this message permanently?');">
                        <input type="hidden" name="csrf"   value="<?= csrf_token() ?>">
                        <input type="hidden" name="msg_id" value="<?= (int)$msg['id'] ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="btn admin-mini-btn admin-mini-btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="admin-pagination">
            <?php
                $base = BASE_URL . '/admin/messages.php?' . ($status_filter !== '' ? 'status=' . urlencode($status_filter) . '&' : '');
            ?>
            <?php if ($current_page > 1): ?>
                <a href="<?= $base ?>page=<?= $current_page - 1 ?>" class="admin-page-btn">&lsaquo; Prev</a>
            <?php endif; ?>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php if ($p === $current_page): ?>
                    <span class="admin-page-btn active"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= $base ?>page=<?= $p ?>" class="admin-page-btn"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($current_page < $total_pages): ?>
                <a href="<?= $base ?>page=<?= $current_page + 1 ?>" class="admin-page-btn">Next &rsaquo;</a>
            <?php endif; ?>
        </div>
        <p class="admin-pagination-summary">
            Showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> of <?= $total ?> messages
        </p>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/inc_admin_layout_end.php'; ?>
