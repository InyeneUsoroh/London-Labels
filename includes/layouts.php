<?php
/**
 * London Labels - Layout Helpers
 * Common layout functions for consistent page structure
 */

/**
 * Start a standard page layout (includes header)
 * Automatically sets page title from function parameter or global $page_title
 */
function layout_start($pageTitle = null) {
    if ($pageTitle) {
        global $page_title;
        $page_title = $pageTitle;
    }
    
    require_once __DIR__ . '/../inc_header.php';
}

/**
 * End page layout (includes footer)
 */
function layout_end() {
    require_once __DIR__ . '/../inc_footer.php';
}

/**
 * Render a page header with title and optional breadcrumb
 */
function render_page_header($title, $subtitle = '', $breadcrumbs = [], $actions = []) {
    echo '<div class="layout-page-header">';
    
    if (!empty($breadcrumbs)) {
        echo '<nav class="layout-breadcrumbs">';
        foreach ($breadcrumbs as $idx => $crumb) {
            if ($idx > 0) echo ' / ';
            if (isset($crumb['url'])) {
                echo '<a href="' . e($crumb['url']) . '" class="layout-breadcrumb-link">' . e($crumb['label']) . '</a>';
            } else {
                echo e($crumb['label']);
            }
        }
        echo '</nav>';
    }
    
    echo '<div class="layout-page-heading-row">';
    echo '<div class="layout-page-heading-copy">';
    echo '<h1 class="layout-page-title">' . e($title) . '</h1>';
    
    if ($subtitle) {
        echo '<p class="layout-page-subtitle">' . e($subtitle) . '</p>';
    }
    echo '</div>';

    if (!empty($actions)) {
        echo '<div class="layout-page-actions">';
        foreach ($actions as $action) {
            $label = $action['label'] ?? '';
            $url = $action['url'] ?? '';
            $class = trim((string)($action['class'] ?? 'btn'));
            if ($label === '' || $url === '') {
                continue;
            }
            echo '<a href="' . e($url) . '" class="' . e($class) . '">' . e($label) . '</a>';
        }
        echo '</div>';
    }
    echo '</div>';
    
    echo '</div>';
}

/**
 * Render a two-column layout (sidebar + content)
 * Typically used for admin dashboards
 */
function render_two_column_layout($sidebar, $content) {
    echo '<div class="layout-two-column">';
    echo '<aside class="layout-two-column-sidebar">';
    echo $sidebar;
    echo '</aside>';
    echo '<main class="layout-two-column-main">';
    echo $content;
    echo '</main>';
    echo '</div>';
}

/**
 * Render a stats grid (dashboard metrics)
 */
function render_stats_grid($stats) {
    echo '<div class="layout-stats-grid">';
    
    foreach ($stats as $stat) {
        $tone = match($stat['color'] ?? 'blue') {
            'green' => 'green',
            'red' => 'red',
            'yellow' => 'yellow',
            'purple' => 'purple',
            'blue' => 'blue',
            default => 'neutral'
        };

        echo '<div class="layout-stat-card tone-' . $tone . '">';
        echo '<h4 class="layout-stat-label">' . e($stat['label']) . '</h4>';
        echo '<div class="layout-stat-value">' . e($stat['value']) . '</div>';
        if (isset($stat['change'])) {
            $changeColorClass = $stat['change'] > 0 ? 'up' : 'down';
            $icon = $stat['change'] > 0 ? '↑' : '↓';
            echo '<small class="layout-stat-change ' . $changeColorClass . '">' . $icon . ' ' . abs($stat['change']) . '%</small>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Render an empty state (when no results)
 */
function render_empty_state($title, $message = '', $actionText = '', $actionLink = '', $icon = '') {
    echo '<div class="layout-empty-state">';
    if ($icon !== '') {
        echo '<div class="layout-empty-icon">' . e($icon) . '</div>';
    }
    echo '<h3 class="layout-empty-title">' . e($title) . '</h3>';
    if ($message) {
        echo '<p class="layout-empty-message">' . e($message) . '</p>';
    }
    if ($actionText && $actionLink) {
        echo '<a href="' . e($actionLink) . '" class="btn primary layout-empty-action">' . e($actionText) . '</a>';
    }
    echo '</div>';
}

/**
 * Render a list group (like a menu or options)
 */
function render_list_group($items) {
    echo '<ul class="layout-list-group">';
    foreach ($items as $item) {
        $active = $item['active'] ?? false;
        $activeClass = $active ? 'active' : '';
        
        echo '<li>';
        echo '<a href="' . e($item['link']) . '" class="layout-list-link ' . $activeClass . '">';
        echo e($item['label']);
        echo '</a>';
        echo '</li>';
    }
    echo '</ul>';
}
?>
