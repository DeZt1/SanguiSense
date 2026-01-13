<?php
// database/fix_facility_user_links.php
// Sync facilities and users for admin/city linkage
require_once __DIR__ . '/../includes/db_connect.php';

// 1. Assign admin_id to facilities based on matching user (by name/city/email)
$facilityStmt = $pdo->query("SELECT id, name, city, email FROM facilities");
$facilities = $facilityStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($facilities as $facility) {
    // Try to find a user with matching email or name and city
    $userStmt = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR (name = ? AND city = ?)) AND (user_type = 'hospital_admin' OR user_type = 'bloodbank_admin') LIMIT 1");
    $userStmt->execute([$facility['email'], $facility['name'], $facility['city']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $updateStmt = $pdo->prepare("UPDATE facilities SET admin_id = ? WHERE id = ?");
        $updateStmt->execute([$user['id'], $facility['id']]);
        echo "Linked facility '{$facility['name']}' in {$facility['city']} to admin user ID {$user['id']}\n";
    }
}

// 2. Ensure users have city set (if missing, try to infer from facility)
$userStmt = $pdo->query("SELECT id, name, city, email FROM users WHERE city IS NULL OR city = ''");
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $user) {
    // Try to find a facility with matching admin/email/name
    $facilityStmt = $pdo->prepare("SELECT city FROM facilities WHERE admin_id = ? OR email = ? OR name = ? LIMIT 1");
    $facilityStmt->execute([$user['id'], $user['email'], $user['name']]);
    $facility = $facilityStmt->fetch(PDO::FETCH_ASSOC);
    if ($facility && !empty($facility['city'])) {
        $updateStmt = $pdo->prepare("UPDATE users SET city = ? WHERE id = ?");
        $updateStmt->execute([$facility['city'], $user['id']]);
        echo "Set city for user '{$user['name']}' to {$facility['city']}\n";
    }
}

echo "Facility-user linkage and city sync complete.\n";
