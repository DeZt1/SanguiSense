<?php
require 'includes/db_connect.php';

// Update first donation to fulfilled
$stmt = $pdo->prepare('UPDATE donations SET status = ? WHERE id = ?');
$stmt->execute(['fulfilled', 1]);
echo "Updated donation ID 1 to fulfilled status\n";

// Verify
$check = $pdo->query('SELECT id, donor_id, status FROM donations WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
echo 'Confirmed: Donation ' . $check['id'] . ' | Donor: ' . $check['donor_id'] . ' | Status: ' . $check['status'] . "\n";

// Show all donors with fulfilled donations for facility 1
echo "\n=== DONORS WITH FULFILLED DONATIONS AT FACILITY 1 ===\n";
$donors = $pdo->query("SELECT DISTINCT u.id, u.name, u.phone, u.blood_type FROM users u JOIN donations d ON u.id = d.donor_id WHERE d.facility_id = 1 AND d.status = 'fulfilled'")->fetchAll(PDO::FETCH_ASSOC);
echo "Found: " . count($donors) . "\n";
foreach($donors as $d) {
    echo "  - " . $d['name'] . " | Phone: " . $d['phone'] . " | Blood Type: " . $d['blood_type'] . "\n";
}
