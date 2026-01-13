<?php
header('Content-Type: application/json');
include '../../includes/auth.php';

try {
    // Get all facilities in Nueva Ecija with locations
    $query = "SELECT 
        f.id as facility_id,
        f.name,
        f.type,
        f.address,
        f.city,
        f.phone,
        f.email,
        fl.latitude,
        fl.longitude,
        COALESCE(fl.is_active, TRUE) as is_active
    FROM facilities f
    LEFT JOIN facility_locations fl ON f.id = fl.facility_id
    WHERE f.city IN ('Cabanatuan', 'Gapan', 'San Fernando', 'Palayan', 'San Jose City', 'Muñez', 'General Tinio', 'Aliaga')
    AND fl.is_active = TRUE
    ORDER BY f.city, f.type DESC";
    
    $stmt = $pdo->query($query);
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Optional blood type filter from query string
    $bloodTypeFilter = isset($_GET['blood_type']) ? trim($_GET['blood_type']) : '';

    // For each facility, get recent completed donations
    $filtered = [];
    foreach ($facilities as $facility) {
        // Get count of completed donations in last 7 days
        $donationQuery = "SELECT COUNT(*) as recent_donations, GROUP_CONCAT(DISTINCT blood_type) as blood_types FROM donations WHERE facility_id = ? AND status = 'completed' AND donation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $donationStmt = $pdo->prepare($donationQuery);
        $donationStmt->execute([$facility['facility_id']]);
        $donationData = $donationStmt->fetch(PDO::FETCH_ASSOC);
        
    $facility['recent_donations'] = $donationData['recent_donations'] ?? 0;
    $facility['available_blood_types'] = $donationData['blood_types'] ? array_values(array_filter(array_map('trim', explode(',', $donationData['blood_types'])))) : [];
        
    if (is_null($facility['latitude']) || is_null($facility['longitude'])) {
            // Default Nueva Ecija coordinates based on city
            $defaultCoords = [
                'Cabanatuan' => [14.7995, 121.4936],
                'Gapan' => [14.8333, 121.5667],
                'San Fernando' => [14.8166, 121.4333],
                'Palayan' => [14.8583, 121.7667],
                'San Jose City' => [14.9000, 121.3333],
                'Muñez' => [14.8500, 121.6000],
                'General Tinio' => [14.7833, 121.5500],
                'Aliaga' => [14.9167, 121.5333]
            ];
            
            if (isset($defaultCoords[$facility['city']])) {
                $facility['latitude'] = $defaultCoords[$facility['city']][0];
                $facility['longitude'] = $defaultCoords[$facility['city']][1];
            } else {
                // Default to Cabanatuan if city not found
                $facility['latitude'] = 14.7995;
                $facility['longitude'] = 121.4936;
            }
        }

        // If a blood type filter is provided, only include facilities that have that blood type in recent donations
        if ($bloodTypeFilter) {
            if (in_array($bloodTypeFilter, $facility['available_blood_types'])) {
                $filtered[] = $facility;
            }
        } else {
            $filtered[] = $facility;
        }
    }

    echo json_encode([
        'success' => true,
        'facilities' => $filtered,
        'count' => count($filtered),
        'region' => 'Region 3, Nueva Ecija, Philippines'
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
