<?php
/**
 * Unified locations include for SanguiSense
 *
 * This file centralizes the canonical list of municipalities (cities)
 * and the canonical list of facilities (hospitals and blood banks)
 * so every portal uses the same dropdown options.
 */

// Canonical municipalities / cities (as requested)
$MUNICIPALITIES = [
    'Aliaga', 'Bongabon', 'Cabiao', 'Carranglan', 'Cuyapo', 'Gabaldon',
    'General Mamerto Natividad', 'General Tinio', 'Guimba', 'Jaen', 'Laur',
    'Licab', 'Llanera', 'Lupao', 'Nampicuan', 'Pantabangan', 'Peñaranda',
    'Quezon', 'Rizal', 'San Antonio', 'San Isidro', 'San Leonardo',
    'Santa Rosa', 'Santo Domingo', 'Talavera', 'Talugtug', 'Zaragoza',
    // include common city names used by facilities too
    'Cabanatuan', 'Gapan', 'Palayan', 'San Jose', 'San Fernando', 'Muñoz'
];

// Canonical facilities list (hospitals and blood banks)
$FACILITIES = [
    // Cabanatuan City
    ['name' => 'Premiere Medical Center', 'type' => 'hospital', 'city' => 'Cabanatuan'],
    ['name' => 'GoodSam Medical Center', 'type' => 'hospital', 'city' => 'Cabanatuan'],
    ['name' => 'Nueva Ecija Doctors Hospital', 'type' => 'hospital', 'city' => 'Cabanatuan'],

    // Gapan
    ['name' => 'GoodSam Medical Center (Gapan)', 'type' => 'hospital', 'city' => 'Gapan'],

    // Palayan
    ['name' => 'Palayan City Emergency Hospital', 'type' => 'hospital', 'city' => 'Palayan'],

    // San Jose City
    ['name' => 'San Jose City General Hospital', 'type' => 'hospital', 'city' => 'San Jose'],

    // San Antonio, Guimba
    ['name' => 'San Antonio District Hospital', 'type' => 'hospital', 'city' => 'San Antonio'],
    ['name' => 'Guimba District Hospital', 'type' => 'hospital', 'city' => 'Guimba'],

    // Blood banks
    ['name' => 'Philippine Red Cross - Nueva Ecija Blood Services', 'type' => 'bloodbank', 'city' => 'Cabanatuan']
];

// Helpers
if (!function_exists('get_municipalities')) {
    function get_municipalities()
    {
        global $MUNICIPALITIES;
        return $MUNICIPALITIES;
    }
}

if (!function_exists('get_facilities')) {
    function get_facilities($filters = [])
    {
        global $FACILITIES;
        $results = $FACILITIES;

        if (!empty($filters['city'])) {
            $city = $filters['city'];
            $results = array_values(array_filter($results, function ($f) use ($city) {
                return isset($f['city']) && strcasecmp($f['city'], $city) === 0;
            }));
        }

        if (!empty($filters['type'])) {
            $type = strtolower($filters['type']);
            $results = array_values(array_filter($results, function ($f) use ($type) {
                return isset($f['type']) && strtolower($f['type']) === $type;
            }));
        }

        return $results;
    }
}

// Convenience: return JSON and exit
if (!function_exists('locations_to_json')) {
    function locations_to_json($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

?>
