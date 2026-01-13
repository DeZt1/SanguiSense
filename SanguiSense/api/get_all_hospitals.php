<?php
/**
 * API Endpoint: Get All Hospitals
 * 
 * Endpoint: /sanguisense/api/get_all_hospitals.php
 * Method: GET
 * 
 * Returns JSON:
 * {
 *     "success": true,
 *     "hospitals": [
 *         {
 *             "name": "Premiere Medical Center",
 *             "municipality": "Cabanatuan"
 *         },
 *         ...
 *     ],
 *     "count": 8
 * }
 */

header('Content-Type: application/json');

include '../includes/locations.php';

try {
    $hospitals = get_facilities(['type' => 'hospital']);
    $result = [];

    foreach ($hospitals as $h) {
        $result[] = [
            'name' => $h['name'],
            'municipality' => $h['city']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'hospitals' => $result,
        'count' => count($result)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
