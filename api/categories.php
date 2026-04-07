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
    // Get categories from database
    $categories = get_all_categories();
    
    // Format categories for frontend consumption
    $formatted_categories = array_map(function($category) {
        return [
            'id' => (int)$category['category_id'],
            'name' => $category['name'],
            'slug' => strtolower(str_replace([' ', '&', "'", '/'], ['-', 'and', '', '-'], $category['name'])),
            'description' => $category['description'] ?? ''
        ];
    }, $categories);
    
    // Return the actual categories from database
    echo json_encode($formatted_categories);
    
} catch (Exception $e) {
    // Log error for debugging
    error_log('Categories API Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to load categories',
        'message' => 'Please try again later'
    ]);
}
?>