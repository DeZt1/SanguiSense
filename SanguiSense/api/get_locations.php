<?php
// API to return municipalities and facilities
// Usage:
//  - api/get_locations.php?type=municipalities
//  - api/get_locations.php?type=facilities
//  - api/get_locations.php?type=both&city=Cabanatuan&facility_type=hospital

require_once __DIR__ . '/../includes/locations.php';

$type = $_GET['type'] ?? 'both';
$city = $_GET['city'] ?? null;
$facility_type = $_GET['facility_type'] ?? null; // 'hospital' or 'bloodbank'

$out = [];
if ($type === 'municipalities' || $type === 'both') {
    $out['municipalities'] = get_municipalities();
}

if ($type === 'facilities' || $type === 'both') {
    $filters = [];
    if ($city) $filters['city'] = $city;
    if ($facility_type) $filters['type'] = $facility_type;
    $out['facilities'] = get_facilities($filters);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_UNESCAPED_UNICODE);
exit;
