<?php
require 'includes/db_connect.php';

echo "=== UPDATING DONATIONS TABLE STATUS ENUM ===\n";

// Alter the status column to include new values
$alterSQL = "ALTER TABLE donations MODIFY COLUMN status ENUM('scheduled','pending','approved','completed','fulfilled','declined','cancelled')";

try {
    $pdo->exec($alterSQL);
    echo "Successfully updated donations.status ENUM\n";
    echo "New allowed values: scheduled, pending, approved, completed, fulfilled, declined, cancelled\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Now update the empty status value to 'scheduled'
$updateSQL = "UPDATE donations SET status = 'scheduled' WHERE status = '' OR status IS NULL";
$pdo->exec($updateSQL);
echo "Updated empty/null status values to 'scheduled'\n";

// Verify
$statuses = $pdo->query('SELECT DISTINCT status FROM donations')->fetchAll(PDO::FETCH_ASSOC);
echo "\nCurrent status values in table:\n";
foreach($statuses as $s) {
    echo "  - '" . $s['status'] . "'\n";
}
