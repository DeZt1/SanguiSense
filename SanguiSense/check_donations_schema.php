<?php
require 'includes/db_connect.php';

// Check donations table structure
$cols = $pdo->query('SHOW COLUMNS FROM donations')->fetchAll(PDO::FETCH_ASSOC);
echo "=== DONATIONS TABLE STRUCTURE ===\n";
foreach($cols as $c) {
    echo $c['Field'] . " | " . $c['Type'] . "\n";
}

// Check current status values in donations
echo "\n=== CURRENT STATUS VALUES ===\n";
$statuses = $pdo->query('SELECT DISTINCT status FROM donations')->fetchAll(PDO::FETCH_ASSOC);
foreach($statuses as $s) {
    echo "  - '" . $s['status'] . "'\n";
}
