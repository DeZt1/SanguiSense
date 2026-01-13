<?php
// Facilities Update Script - Add Nueva Ecija Facilities and Locations
include '../includes/config.php';

echo "<h2>Facilities Migration to Nueva Ecija</h2>";
echo "<p>This script will:</p>";
echo "<ul>";
echo "<li>Update existing facilities to Nueva Ecija locations</li>";
echo "<li>Create facility_locations table</li>";
echo "<li>Add coordinates for all facilities</li>";
echo "<li>Add additional Nueva Ecija facilities</li>";
echo "</ul>";
echo "<hr>";

try {
    // Step 1: Update existing facilities
    echo "<h3>Step 1: Updating Existing Facilities to Nueva Ecija</h3>";
    
    $updates = [
        [
            'id' => 1,
            'address' => 'Maharlika Highway, Cabanatuan',
            'city' => 'Cabanatuan',
            'name' => 'City General Hospital'
        ],
        [
            'id' => 2,
            'address' => '456 San Fernando Road, Gapan',
            'city' => 'Gapan',
            'name' => 'Central Blood Bank'
        ],
        [
            'id' => 3,
            'address' => '789 Health Center Blvd, San Fernando',
            'city' => 'San Fernando',
            'name' => 'Town Medical Center'
        ]
    ];
    
    foreach ($updates as $facility) {
        $stmt = $pdo->prepare("UPDATE facilities SET address = ?, city = ? WHERE id = ? AND name = ?");
        $stmt->execute([$facility['address'], $facility['city'], $facility['id'], $facility['name']]);
        echo "✓ Updated {$facility['name']} to {$facility['city']}<br>";
    }
    
    // Step 2: Create facility_locations table
    echo "<h3>Step 2: Creating facility_locations Table</h3>";
    
    $createTableSQL = "CREATE TABLE IF NOT EXISTS facility_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        facility_id INT NOT NULL UNIQUE,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
        INDEX idx_facility_active (facility_id, is_active)
    )";
    
    $pdo->exec($createTableSQL);
    echo "✓ facility_locations table created/verified<br>";
    
    // Step 3: Insert facility locations
    echo "<h3>Step 3: Adding Facility Coordinates</h3>";
    
    $locations = [
        [
            'facility_id' => 1,
            'lat' => 14.79950,
            'lng' => 121.49360,
            'city' => 'Cabanatuan'
        ],
        [
            'facility_id' => 2,
            'lat' => 14.83330,
            'lng' => 121.56670,
            'city' => 'Gapan'
        ],
        [
            'facility_id' => 3,
            'lat' => 14.81660,
            'lng' => 121.43330,
            'city' => 'San Fernando'
        ]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO facility_locations (facility_id, latitude, longitude, is_active) 
        VALUES (?, ?, ?, TRUE)
        ON DUPLICATE KEY UPDATE latitude = ?, longitude = ?, is_active = TRUE");
    
    foreach ($locations as $loc) {
        $stmt->execute([$loc['facility_id'], $loc['lat'], $loc['lng'], $loc['lat'], $loc['lng']]);
        echo "✓ Added coordinates for {$loc['city']} facility<br>";
    }
    
    // Step 4: Add additional Nueva Ecija facilities
    echo "<h3>Step 4: Adding Additional Nueva Ecija Facilities</h3>";
    
    // Check if facilities already exist
    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM facilities WHERE city = 'Palayan'");
    $checkStmt->execute();
    $result = $checkStmt->fetch();
    
    if ($result['count'] == 0) {
        $stmt = $pdo->prepare("INSERT INTO facilities (name, type, address, city, phone, email) VALUES (?, ?, ?, ?, ?, ?)");
        
        $newFacilities = [
            ['Palayan Medical Center', 'hospital', 'Maharlika Highway, Palayan', 'Palayan', '09123456789', 'contact@palayemedical.com'],
            ['Nueva Ecija Blood Donation Center', 'blood_bank', 'Doña Remedios Trinidad Highway, San Jose City', 'San Jose City', '09234567890', 'contact@nejbloodcenter.com'],
            ['Cabanatuan City Hospital', 'hospital', 'Maharlika Avenue, Cabanatuan', 'Cabanatuan', '09345678901', 'contact@cabhosp.com']
        ];
        
        foreach ($newFacilities as $fac) {
            $stmt->execute($fac);
            echo "✓ Added new facility: {$fac[0]} in {$fac[3]}<br>";
        }
        
        // Add locations for new facilities
        $facilityStmt = $pdo->prepare("SELECT id, name, city FROM facilities WHERE city IN ('Palayan', 'San Jose City', 'Cabanatuan') AND name IN ('Palayan Medical Center', 'Nueva Ecija Blood Donation Center', 'Cabanatuan City Hospital')");
        $facilityStmt->execute();
        $newFacilitiesList = $facilityStmt->fetchAll();
        
        $locationStmt = $pdo->prepare("INSERT IGNORE INTO facility_locations (facility_id, latitude, longitude, is_active) VALUES (?, ?, ?, TRUE)");
        
        $coordinates = [
            'Palayan Medical Center' => [14.8583, 121.7667],
            'Nueva Ecija Blood Donation Center' => [14.9000, 121.3333],
            'Cabanatuan City Hospital' => [14.8000, 121.5000]
        ];
        
        foreach ($newFacilitiesList as $newFac) {
            if (isset($coordinates[$newFac['name']])) {
                $coords = $coordinates[$newFac['name']];
                $locationStmt->execute([$newFac['id'], $coords[0], $coords[1]]);
                echo "✓ Added coordinates for {$newFac['name']}<br>";
            }
        }
    } else {
        echo "✓ Nueva Ecija facilities already exist<br>";
    }
    
    // Step 5: Create view for easier access
    echo "<h3>Step 5: Creating Facility Locations View</h3>";
    
    $viewSQL = "CREATE OR REPLACE VIEW facility_locations_view AS
        SELECT 
            f.id,
            f.name,
            f.type,
            f.address,
            f.city,
            f.phone,
            f.email,
            fl.latitude,
            fl.longitude,
            fl.is_active,
            f.admin_id
        FROM facilities f
        LEFT JOIN facility_locations fl ON f.id = fl.facility_id";
    
    $pdo->exec($viewSQL);
    echo "✓ facility_locations_view created<br>";
    
    // Final verification
    echo "<h3>Final Verification</h3>";
    
    $verifyStmt = $pdo->query("SELECT f.id, f.name, f.city, fl.latitude, fl.longitude FROM facilities f LEFT JOIN facility_locations fl ON f.id = fl.facility_id ORDER BY f.city");
    $facilities = $verifyStmt->fetchAll();
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr><th>ID</th><th>Facility Name</th><th>City</th><th>Latitude</th><th>Longitude</th></tr>";
    
    foreach ($facilities as $fac) {
        echo "<tr>";
        echo "<td>{$fac['id']}</td>";
        echo "<td>{$fac['name']}</td>";
        echo "<td>{$fac['city']}</td>";
        echo "<td>" . ($fac['latitude'] ? number_format($fac['latitude'], 6) : 'N/A') . "</td>";
        echo "<td>" . ($fac['longitude'] ? number_format($fac['longitude'], 6) : 'N/A') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>✅ Migration Completed Successfully!</h3>";
    echo "<p>All facilities are now in Nueva Ecija and should appear on the patient map.</p>";
    echo "<p><a href='../patient/find_donors.php' style='padding: 10px 20px; background: #00bcd4; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>View Patient Map</a></p>";
    
} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ Migration Failed</h3>";
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'><strong>Code:</strong> " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Error</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
