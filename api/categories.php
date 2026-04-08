<?php
/**
 * Categories API Endpoint for London Labels
 * Provides category data for the hamburger menu
 */

require_once __DIR__ . '/../functions.php';

// Set JSON headers
header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // 5 minutes cache

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    $categories = get_all_categories();

    $formatted_categories = array_map(function($category) {
        return [
            'id'          => (int)$category['category_id'],
            'name'        => $category['name'],
            'slug'        => strtolower(str_replace([' ', '&', "'", '/'], ['-', 'and', '', '-'], $category['name'])),
            'description' => $category['description'] ?? ''
        ];
    }, $categories);

    echo json_encode($formatted_categories);

} catch (\Throwable $e) {
    error_log('Categories API Error: ' . $e->getMessage());
    // Return empty array — the dropdown still renders with just "View All Products"
    // rather than a 500 that the JS catch silently swallows, leaving the panel blank.
    echo json_encode([]);
}
?>