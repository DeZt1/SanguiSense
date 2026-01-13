<?php
header('Content-Type: application/json');
include '../../includes/auth.php';

try {
    $bloodType = isset($_POST['blood_type']) ? trim($_POST['blood_type']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';
    
    // Build query for available donors
    $query = "SELECT 
        u.id as donor_id,
        u.name,
        u.blood_type,
        u.phone,
        u.city,
        u.last_donation_date,
        u.is_eligible,
        dl.latitude,
        dl.longitude,
        ROUND(3959 * acos(cos(radians(?) * pi() / 180) * cos(radians(dl.latitude) * pi() / 180) * sin(radians(dl.longitude - ?) * pi() / 180) + sin(radians(?) * pi() / 180) * sin(radians(dl.latitude) * pi() / 180)), 2) as distance_km
    FROM users u
    LEFT JOIN donor_locations dl ON u.id = dl.donor_id
    WHERE u.user_type = 'donor' AND u.is_eligible = TRUE AND dl.is_active = TRUE
    -- Only include donors who have completed a donation at a facility in the last 90 days
    AND EXISTS (
        SELECT 1 FROM donations d
        WHERE d.donor_id = u.id
        AND d.status = 'completed'
        AND d.facility_id IS NOT NULL
        AND d.donation_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    )";
    
    $params = [];
    
    // If no geolocation, use default coordinates for Nueva Ecija, Philippines
    // Central Nueva Ecija approx: lat 14.81, lng 121.45
    $userLat = isset($_POST['lat']) ? floatval($_POST['lat']) : 14.81;
    $userLng = isset($_POST['lng']) ? floatval($_POST['lng']) : 121.45;

    array_push($params, $userLat, $userLng, $userLat);
    
    // Add blood type filter
    if (!empty($bloodType)) {
        $query .= " AND u.blood_type = ?";
        array_push($params, $bloodType);
    }
    
    // Add city filter
    if (!empty($city)) {
        $query .= " AND dl.city LIKE ?";
        array_push($params, '%' . $city . '%');
    }
    
    $query .= " ORDER BY distance_km ASC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process results
    foreach ($donors as &$donor) {
        if (is_null($donor['latitude']) || is_null($donor['longitude'])) {
            // Generate random coordinates near Nueva Ecija default location if not set
            $donor['latitude'] = 14.81 + (rand(-50, 50) / 1000);
            $donor['longitude'] = 121.45 + (rand(-50, 50) / 1000);
            $donor['distance_km'] = rand(1, 80);
        }
    }
    
    echo json_encode([
        'success' => true,
        'donors' => $donors,
        'count' => count($donors)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
