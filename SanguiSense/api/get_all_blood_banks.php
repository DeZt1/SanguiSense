<?php
/**
 * API Endpoint: Get All Blood Banks
 * 
 * Endpoint: /sanguisense/api/get_all_blood_banks.php
 * Method: GET
 * 
 * Returns JSON:
 * {
 *     "success": true,
 *     "blood_banks": [
 *         {
 *             "name": "Philippine Red Cross-Nueva Ecija Blood Services",
 *             "municipality": "Cabanatuan"
 *         },
 *         ...
 *     ],
 *     "count": 1
 * }
 */

header('Content-Type: application/json');

include '../includes/locations.php';

try {
    $bloodBanks = get_facilities(['type' => 'bloodbank']);
    $result = [];

    foreach ($bloodBanks as $b) {
        $result[] = [
            'name' => $b['name'],
            'municipality' => $b['city']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'blood_banks' => $result,
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
