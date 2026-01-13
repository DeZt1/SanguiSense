<?php
/**
 * API Endpoint: Get All Municipalities
 * 
 * Endpoint: /sanguisense/api/get_municipalities.php
 * Method: GET
 * 
 * Returns JSON:
 * {
 *     "success": true,
 *     "municipalities": [
 *         "Aliaga",
 *         "Bongabon",
 *         ...
 *     ],
 *     "count": 33
 * }
 */

header('Content-Type: application/json');

include '../includes/locations.php';

try {
    $municipalities = get_municipalities();
    
    echo json_encode([
        'success' => true,
        'municipalities' => $municipalities,
        'count' => count($municipalities)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
