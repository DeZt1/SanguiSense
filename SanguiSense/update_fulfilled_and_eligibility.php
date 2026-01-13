<?php
require 'includes/db_connect.php';

echo "=== UPDATING DONATIONS AND DONOR ELIGIBILITY ===\n";

// Update 2 more donations to fulfilled (IDs 2 and 3)
$stmt = $pdo->prepare('UPDATE donations SET status = ? WHERE id = ?');
$stmt->execute(['fulfilled', 2]);
echo "Updated donation ID 2 to fulfilled\n";

$stmt->execute(['fulfilled', 3]);
echo "Updated donation ID 3 to fulfilled\n";

// Verify
$fulfilled = $pdo->query('SELECT id, donor_id, status FROM donations WHERE status = "fulfilled" ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo "\nFulfilled donations:\n";
foreach($fulfilled as $d) {
    echo "  - ID: " . $d['id'] . " | Donor: " . $d['donor_id'] . "\n";
}

// Update donor eligibility to eligible (donor 11 is Katrina)
$updateStmt = $pdo->prepare('UPDATE users SET eligibility_status = ? WHERE id = ?');
$updateStmt->execute(['eligible', 11]);
echo "\nUpdated donor 11 (Katrina) eligibility_status to 'eligible'\n";

// Verify donor status
$donor = $pdo->query('SELECT id, name, eligibility_status FROM users WHERE id = 11')->fetch(PDO::FETCH_ASSOC);
echo "Confirmed: " . $donor['name'] . " | Eligibility: " . $donor['eligibility_status'] . "\n";

// Count all fulfilled donations now
$count = $pdo->query('SELECT COUNT(*) as total FROM donations WHERE status = "fulfilled"')->fetch(PDO::FETCH_ASSOC);
echo "\nTotal fulfilled donations: " . $count['total'] . "\n";
