<?php
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->query("SELECT id, donor_id, facility_id, donation_date, status, quantity FROM donations WHERE status = 'fulfilled' ORDER BY donation_date DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "No fulfilled donations found\n";
        exit(0);
    }

    echo "Fulfilled donations:\n";
    foreach ($rows as $r) {
        echo sprintf("ID: %s | donor_id: %s | facility_id: %s | date: %s | status: %s | quantity: %s\n",
            $r['id'], $r['donor_id'], $r['facility_id'], $r['donation_date'], $r['status'], $r['quantity']);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
