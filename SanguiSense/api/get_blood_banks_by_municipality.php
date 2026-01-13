<?php
/**
 * API Endpoint: Get Blood Banks by Municipality
 * 
 * Endpoint: /sanguisense/api/get_blood_banks_by_municipality.php
 * Method: GET
 * Query Parameters:
 *   - municipality (required): Name of the municipality
 * 
 * Returns JSON:
 * {
 *     "success": true,
 *     "municipality": "Cabanatuan",
 *     "blood_banks": [
 *         {
 *             "name": "Philippine Red Cross-Nueva Ecija Blood Services",
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

    $bloodBanks = get_facilities(['type' => 'bloodbank', 'city' => $municipality]);
    $result = [];

    foreach ($bloodBanks as $b) {
        $result[] = [
            'name' => $b['name'],
            'municipality' => $municipality
        ];
    }

    echo json_encode([
        'success' => true,
        'municipality' => $municipality,
        'blood_banks' => $result
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>
