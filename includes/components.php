<?php
/**
 * London Labels - Reusable UI Components
 * Component rendering functions for consistent UI
 */

/**
 * Render an alert message
 * Types: success, danger, warning, info
 */
function render_alert($type, $message, $dismissible = true) {
    $typeClass = match($type) {
        'success' => 'success',
        'danger' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
        default => 'neutral'
    };

    $alertRole = in_array($type, ['danger', 'warning'], true) ? 'alert' : 'status';
    $liveMode = in_array($type, ['danger', 'warning'], true) ? 'assertive' : 'polite';
    
    echo '<div class="ui-alert ui-alert-' . $typeClass . '" role="' . $alertRole . '" aria-live="' . $liveMode . '">';
    
    if (is_array($message)) {
        echo '<strong>' . ucfirst($type) . ':</strong>';
        echo '<ul class="ui-alert-list">';
        foreach ($message as $msg) {
            echo '<li>' . e($msg) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<strong>' . ucfirst($type) . ':</strong> ' . e($message);
    }
    
    echo '</div>';
}

/**
 * Render a card component
 */
function render_card($title, $content, $options = []) {
    $class = $options['class'] ?? '';
    $padded = $options['padded'] ?? true;

    echo '<div class="ui-card ' . e($class) . '">';
    
    if ($title) {
        echo '<div class="ui-card-header">';
        echo '<h3 class="ui-card-title">' . e($title) . '</h3>';
        echo '</div>';
    }
    
    echo '<div class="ui-card-body' . ($padded ? '' : ' no-pad') . '">';
    echo $content;
    echo '</div>';
    echo '</div>';
}

/**
 * Render a status badge
 * Status options: pending, processing, completed, cancelled
 */
function render_status_badge($status) {
    $tone = match(strtolower($status)) {
        'pending' => 'pending',
        'processing', 'shipped' => 'processing',
        'completed', 'delivered' => 'completed',
        'cancelled', 'failed' => 'cancelled',
        default => 'neutral'
    };
    
    echo '<span class="ui-status-badge ' . $tone . '">';
    echo ucfirst(e($status));
    echo '</span>';
}

/**
 * Render a form group with label and input
 */
function render_form_input($config) {
    $type = $config['type'] ?? 'text';
    $name = $config['name'];
    $label = $config['label'];
    $value = $config['value'] ?? '';
    $required = $config['required'] ?? false;
    $placeholder = $config['placeholder'] ?? '';
    $autocomplete = $config['autocomplete'] ?? '';
    $inputmode = $config['inputmode'] ?? '';
    $pattern = $config['pattern'] ?? '';
    $minlength = $config['minlength'] ?? null;
    $maxlength = $config['maxlength'] ?? null;
    $help = $config['help'] ?? '';
    $rows = $config['rows'] ?? 3;
    $error = $config['error'] ?? '';
    $describedBy = $config['describedby'] ?? '';
    $inputId = htmlspecialchars($name);
    $noteId = $inputId . '-note';
    $describedByAttr = trim((string)$describedBy);

    if ($error || $help) {
        $describedByAttr = trim($describedByAttr . ' ' . $noteId);
    }
    
    echo '<div class="form-group ui-form-group">';
    
    echo '<label for="' . $inputId . '" class="ui-form-label">';
    echo e($label);
    if ($required) {
        echo ' <span class="ui-required">*</span>';
    }
    echo '</label>';
    
    if ($type === 'textarea') {
        echo '<textarea id="' . $inputId . '" name="' . htmlspecialchars($name) . '" ';
        echo 'rows="' . $rows . '" ';
        if ($placeholder) echo 'placeholder="' . htmlspecialchars($placeholder) . '" ';
        if ($autocomplete) echo 'autocomplete="' . htmlspecialchars($autocomplete) . '" ';
        if ($required) echo 'required ';
        if ($error) echo 'aria-invalid="true" ';
        if ($describedByAttr !== '') echo 'aria-describedby="' . htmlspecialchars($describedByAttr) . '" ';
        echo 'class="ui-form-input' . ($error ? ' has-error' : '') . '"';
        echo '>' . e($value) . '</textarea>';
    } else {
        echo '<input type="' . htmlspecialchars($type) . '" id="' . $inputId . '" name="' . htmlspecialchars($name) . '" ';
        if ($placeholder) echo 'placeholder="' . htmlspecialchars($placeholder) . '" ';
        if ($autocomplete) echo 'autocomplete="' . htmlspecialchars($autocomplete) . '" ';
        if ($inputmode) echo 'inputmode="' . htmlspecialchars($inputmode) . '" ';
        if ($pattern) echo 'pattern="' . htmlspecialchars($pattern) . '" ';
        if ($minlength !== null) echo 'minlength="' . (int)$minlength . '" ';
        if ($maxlength !== null) echo 'maxlength="' . (int)$maxlength . '" ';
        echo 'value="' . e($value) . '" ';
        if ($required) echo 'required ';
        if ($error) echo 'aria-invalid="true" ';
        if ($describedByAttr !== '') echo 'aria-describedby="' . htmlspecialchars($describedByAttr) . '" ';
        echo 'class="ui-form-input' . ($error ? ' has-error' : '') . '"';
        echo '>';
    }
    
    if ($error) {
        echo '<small id="' . $noteId . '" class="ui-form-note error">' . e($error) . '</small>';
    } elseif ($help) {
        echo '<small id="' . $noteId . '" class="ui-form-note help">' . e($help) . '</small>';
    }
    
    echo '</div>';
}

/**
 * Render pagination links
 */
function render_pagination($currentPage, $totalPages, $baseUrl, $params = [], $totalItems = null, $perPage = null) {
    if ($totalPages <= 1) return;

    $buildUrl = function (int $targetPage) use ($baseUrl, $params): string {
        $query = array_merge($params, ['page' => $targetPage]);
        return $baseUrl . '?' . http_build_query($query);
    };
    
    echo '<div class="ui-pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        echo '<a href="' . e($buildUrl($currentPage - 1)) . '" class="ui-page-link underline-hover focus-visible">← Previous</a>';
    } else {
        echo '<span class="ui-page-link disabled">← Previous</span>';
    }

    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            echo '<span class="ui-page-link active">' . $i . '</span>';
        } else {
            echo '<a href="' . e($buildUrl($i)) . '" class="ui-page-link underline-hover focus-visible">' . $i . '</a>';
        }
    }

    // Next button
    if ($currentPage < $totalPages) {
        echo '<a href="' . e($buildUrl($currentPage + 1)) . '" class="ui-page-link underline-hover focus-visible">Next →</a>';
    } else {
        echo '<span class="ui-page-link disabled">Next →</span>';
    }
    
    echo '</div>';

    if ($totalItems !== null && $perPage !== null) {
        $start = (($currentPage - 1) * $perPage) + 1;
        $end = min($currentPage * $perPage, (int)$totalItems);
        if ((int)$totalItems === 0) {
            $start = 0;
            $end = 0;
        }
        echo '<p class="ui-pagination-summary">Showing ' . $start . '–' . $end . ' of ' . (int)$totalItems . '</p>';
    }
}

/**
 * Render a data table with consistent styling
 */
function render_table_header($headers) {
    $colCount = max(1, min(8, count($headers)));
    echo '<div class="ui-data-head">';
    echo '<div class="ui-data-grid ui-cols-' . $colCount . '">';
    foreach ($headers as $header) {
        echo '<div class="ui-data-cell head">' . e($header) . '</div>';
    }
    echo '</div>';
    echo '</div>';
}

/**
 * Render a data table row
 */
function render_table_row($cells, $actions = []) {
    $colCount = max(1, min(8, count($cells)));
    echo '<div class="ui-data-grid ui-cols-' . $colCount . ' row">';
    foreach ($cells as $cell) {
        echo '<div class="ui-data-cell">' . $cell . '</div>';
    }
    echo '</div>';
}

/**
 * Render a loading spinner
 */
function render_loader() {
    echo '<div class="ui-loader">';
    echo '<div class="ui-loader-spin"></div>';
    echo '<p class="ui-loader-text">Loading...</p>';
    echo '</div>';
}

/**
 * Render email verification banner
 */
function render_verification_banner() {
    if (!is_logged_in()) {
        return;
    }
    
    require_once __DIR__ . '/../email_verification.php';
    
    if (is_email_verified()) {
        return;
    }
    
    echo '<div class="verification-banner" role="alert">';
    echo '<div class="verification-banner-content">';
    echo '<div class="verification-banner-text">';
    echo '<strong>Verify your email address</strong>';
    echo '<p>Please verify your email to complete purchases. Check your inbox for the verification link.</p>';
    echo '</div>';
    echo '<div class="verification-banner-actions">';
    echo '<a href="' . BASE_URL . '/resend-verification.php" class="btn btn-sm">Resend Email</a>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
?>
