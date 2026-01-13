<?php
/**
 * API Endpoint: Get Hospitals by Municipality
 * 
 * Endpoint: /sanguisense/api/get_hospitals_by_municipality.php
 * Method: GET
 * Query Parameters:
 *   - municipality (required): Name of the municipality
 * 
 * Returns JSON:
 * {
 *     "success": true,
 *     "municipality": "Cabanatuan",
 *     "hospitals": [
 *         {
 *             "name": "Premiere Medical Center",
 *             "municipality": "Cabanatuan"
 *         },
 *         ...
 *     ]
 * }
 */

header('Content-Type: application/json');

include '../includes/locations.php';

try {
    $municipality = isset($_GET['municipality']) ? trim($_GET['municipality']) : '';

    if (empty($municipality)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Municipality parameter is required'
        ]);
        exit;
    }

    if (!in_array($municipality, get_municipalities())) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid municipality'
        ]);
        exit;
    }

    $hospitals = get_facilities(['type' => 'hospital', 'city' => $municipality]);
    $result = [];

    foreach ($hospitals as $h) {
        $result[] = [
            'name' => $h['name'],
            'municipality' => $municipality
        ];
    }

    echo json_encode([
        'success' => true,
        'municipality' => $municipality,
        'hospitals' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
