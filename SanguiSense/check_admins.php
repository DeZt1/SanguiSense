<?php
require 'includes/db_connect.php';

echo "=== CHECKING YOUR LOGIN SESSION ===\n";

// Check which hospital admins don't have facilities assigned
$adminsWithoutFacilities = $pdo->query("SELECT u.id, u.name, u.email FROM users u WHERE u.user_type = 'hospital_admin' AND u.id NOT IN (SELECT DISTINCT admin_id FROM facilities WHERE admin_id IS NOT NULL)")->fetchAll(PDO::FETCH_ASSOC);

echo "Hospital Admins WITHOUT facilities assigned:\n";
foreach($adminsWithoutFacilities as $a) {
    echo "  - ID: " . $a['id'] . " | Name: " . $a['name'] . " | Email: " . $a['email'] . "\n";
}

echo "\n\nHospital Admins WITH facilities assigned:\n";
$adminsWithFacilities = $pdo->query("SELECT DISTINCT u.id, u.name, u.email, GROUP_CONCAT(f.id) as facility_ids FROM users u LEFT JOIN facilities f ON u.id = f.admin_id WHERE u.user_type = 'hospital_admin' AND f.id IS NOT NULL GROUP BY u.id")->fetchAll(PDO::FETCH_ASSOC);
foreach($adminsWithFacilities as $a) {
    echo "  - ID: " . $a['id'] . " | Name: " . $a['name'] . " | Email: " . $a['email'] . " | Facilities: " . $a['facility_ids'] . "\n";
}

echo "\n\n=== SOLUTION ===\n";
echo "If you're logged in as an admin without facilities, the appointments won't show.\n";
echo "Log in as: Joana (jo@example.com) or Joanna Tulio (joo21@ex.com)\n";
