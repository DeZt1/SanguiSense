<?php
require 'includes/db_connect.php';

// List all users
echo "=== ALL USERS ===\n";
$users = $pdo->query('SELECT id, name, email, user_type, city FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
foreach($users as $u) {
    echo $u['id'] . ' | ' . $u['name'] . ' | ' . $u['email'] . ' | ' . $u['user_type'] . ' | ' . ($u['city'] ?? 'NULL') . "\n";
}

echo "\n=== ALL FACILITIES ===\n";
$facilities = $pdo->query('SELECT id, name, type, city, admin_id FROM facilities ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
foreach($facilities as $f) {
    echo $f['id'] . ' | ' . $f['name'] . ' | ' . $f['type'] . ' | ' . $f['city'] . ' | admin_id: ' . ($f['admin_id'] ?? 'NULL') . "\n";
}

echo "\n=== ALL DONATIONS ===\n";
$donations = $pdo->query('SELECT id, donor_id, facility_id, blood_type, donation_date, status FROM donations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
foreach($donations as $d) {
    echo $d['id'] . ' | donor: ' . $d['donor_id'] . ' | facility: ' . $d['facility_id'] . ' | ' . $d['blood_type'] . ' | ' . $d['donation_date'] . ' | ' . $d['status'] . "\n";
}
