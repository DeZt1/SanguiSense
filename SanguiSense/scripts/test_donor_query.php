<?php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Simulate facility_id = 1 (like a hospital admin would have)
    $facility_id = 1;
    
    // The updated query from hospital/donors.php
    $donors_query = "SELECT DISTINCT u.* 
        FROM users u
        JOIN donations d ON u.id = d.donor_id
        WHERE u.user_type = 'donor' 
        AND d.facility_id = ? 
        AND d.status IN ('fulfilled','completed')
        AND (u.eligibility_status IS NULL OR u.eligibility_status != 'ineligible')
        ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($donors_query);
    $stmt->execute([$facility_id]);
    $donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Donors with fulfilled or completed donations at facility $facility_id:\n\n";
    foreach ($donors as $donor) {
        echo "ID: " . $donor['id'] . " | Name: " . $donor['name'] . " | Blood Type: " . $donor['blood_type'] . "\n";
    }
    
    echo "\nTotal: " . count($donors) . " donors\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
