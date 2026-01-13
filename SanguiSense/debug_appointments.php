<?php
require 'includes/db_connect.php';
require 'includes/functions.php';

// Simulate being logged in as user 13 (Joana)
$_SESSION['user_id'] = 13;

echo "=== DEBUG SCHEDULED APPOINTMENTS ===\n";
echo "Admin ID: " . $_SESSION['user_id'] . "\n";

// Find facilities managed by this admin
$facStmt = $pdo->prepare("SELECT id, name FROM facilities WHERE admin_id = ?");
$facStmt->execute([$_SESSION['user_id']]);
$facilities = $facStmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nFacilities for admin 13:\n";
foreach($facilities as $f) {
    echo "  - ID: " . $f['id'] . " | Name: " . $f['name'] . "\n";
}

$facility_ids = array_column($facilities, 'id');
echo "\nFacility IDs: " . json_encode($facility_ids) . "\n";

$appointments = [];
if ($facility_ids) {
    $in = str_repeat('?,', count($facility_ids) - 1) . '?';
    echo "\nQuery: SELECT d.id, d.donor_id, d.facility_id, d.blood_type, d.donation_date, d.quantity, d.status, d.created_at, u.name, u.phone, u.email, u.city FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.facility_id IN ($in) AND d.status IN ('scheduled','pending','approved') ORDER BY d.donation_date ASC\n";
    echo "Params: " . json_encode($facility_ids) . "\n";
    
    $stmt = $pdo->prepare("SELECT d.id, d.donor_id, d.facility_id, d.blood_type, d.donation_date, d.quantity, d.status, d.created_at, u.name, u.phone, u.email, u.city FROM donations d JOIN users u ON d.donor_id = u.id WHERE d.facility_id IN ($in) AND d.status IN ('scheduled','pending','approved') ORDER BY d.donation_date ASC");
    $stmt->execute($facility_ids);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

echo "\nAppointments found: " . count($appointments) . "\n";
foreach($appointments as $appt) {
    echo "  - ID: " . $appt['id'] . " | Donor: " . $appt['name'] . " | Date: " . $appt['donation_date'] . " | Status: " . $appt['status'] . "\n";
}

// Also check all donations in facility 1
echo "\n\n=== ALL DONATIONS IN FACILITY 1 ===\n";
$allDonations = $pdo->query("SELECT * FROM donations WHERE facility_id = 1")->fetchAll(PDO::FETCH_ASSOC);
echo "Found: " . count($allDonations) . "\n";
foreach($allDonations as $d) {
    echo "  - ID: " . $d['id'] . " | Status: " . $d['status'] . " | Facility: " . $d['facility_id'] . "\n";
}
