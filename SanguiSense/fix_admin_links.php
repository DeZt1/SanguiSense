<?php
require 'includes/db_connect.php';

// Determine which admin is currently "logged in" by checking which one we should use
// Based on the data, user 13 (Joana) and user 15 (Joanna Tulio) are hospital admins in Cabanatuan
// Let's update facility 1 (GoodSam - Cabanatuan with scheduled donations) to be managed by user 13

$updateStmt = $pdo->prepare("UPDATE facilities SET admin_id = ? WHERE id = ?");

// Update facility 1 (GoodSam in Cabanatuan with the scheduled donations) to user 13
$updateStmt->execute([13, 1]);
echo "Updated facility 1 (GoodSam Medical Center - Cabanatuan) to admin_id: 13\n";

// Also clean up the duplicate facilities and consolidate them
$updateStmt->execute([13, 22]);
echo "Updated facility 22 to admin_id: 13\n";

$updateStmt->execute([13, 23]);
echo "Updated facility 23 to admin_id: 13\n";

$updateStmt->execute([13, 24]);
echo "Updated facility 24 to admin_id: 13\n";

echo "\nFacility updates complete. Now checking donations for facility 1:\n";
$donations = $pdo->query('SELECT id, donor_id, facility_id, blood_type, donation_date, status FROM donations WHERE facility_id = 1')->fetchAll(PDO::FETCH_ASSOC);
echo "Found " . count($donations) . " donations for facility 1\n";
foreach($donations as $d) {
    echo "  - Donation ID " . $d['id'] . " | donor " . $d['donor_id'] . " | " . $d['blood_type'] . " | " . $d['donation_date'] . " | " . $d['status'] . "\n";
}
