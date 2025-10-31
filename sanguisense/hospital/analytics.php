<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Prepare metrics for the analytics page
global $pdo;
if ($facility) {
    // Inventory summary by blood type
    $inventory_stmt = $pdo->prepare("SELECT blood_type, COALESCE(SUM(quantity),0) as total_units FROM inventory WHERE facility_id = ? AND status = 'available' GROUP BY blood_type ORDER BY total_units DESC");
    $inventory_stmt->execute([$facility['id']]);
    $inventory_summary = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pending and urgent requests counts
    $requests_stmt = $pdo->prepare("SELECT status, urgency, COUNT(*) as cnt FROM blood_requests WHERE facility_id = ? GROUP BY status, urgency");
    $requests_stmt->execute([$facility['id']]);
    $requests_counts_rows = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
    $pending_count = 0;
    $urgent_count = 0;
    foreach ($requests_counts_rows as $r) {
        if ($r['status'] == 'pending') $pending_count += $r['cnt'];
        if (in_array($r['urgency'], ['urgent','emergency','critical'])) $urgent_count += $r['cnt'];
    }

    // Recent donations
    $donations_stmt = $pdo->prepare("SELECT d.*, u.name as donor_name FROM donations d LEFT JOIN users u ON d.donor_id = u.id WHERE d.facility_id = ? ORDER BY d.donation_date DESC LIMIT 8");
    $donations_stmt->execute([$facility['id']]);
    $recent_donations = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Demand forecasts
    $forecast_stmt = $pdo->prepare("SELECT blood_type, predicted_demand, forecast_date, confidence_level FROM demand_forecasts WHERE facility_id = ? ORDER BY forecast_date DESC LIMIT 8");
    $forecast_stmt->execute([$facility['id']]);
    $forecasts = $forecast_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $inventory_summary = [];
    $pending_count = 0;
    $urgent_count = 0;
    $recent_donations = [];
    $forecasts = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Hospital Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><a href="dashboard.php" class="logo-link">
                    <span class="blood-drop">🏥</span>SanguiSense Hospital
                </a></h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="inventory.php" class="nav-link">Blood Inventory</a>
                <a href="donors.php" class="nav-link">Donors</a>
                <a href="appointments.php" class="nav-link">Appointments</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="analytics.php" class="nav-link active">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Analytics & Reports</h1>
            <p>View detailed analytics and generate reports</p>
        </div>
        
        <div class="content-card">
            <h3>Overview</h3>
            <p>Key metrics for <?php echo htmlspecialchars($facility['name'] ?? 'Your Facility'); ?></p>

            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                <div class="stat-card hospital-stat">
                    <h4>Total Inventory Units</h4>
                    <p class="stat-number"><?php
                        $total_units = array_sum(array_map(function($r){ return (int)$r['total_units']; }, $inventory_summary));
                        echo $total_units;
                    ?></p>
                </div>
                <div class="stat-card hospital-stat">
                    <h4>Pending Requests</h4>
                    <p class="stat-number"><?php echo (int)$pending_count; ?></p>
                </div>
                <div class="stat-card hospital-stat">
                    <h4>Urgent/Critical Requests</h4>
                    <p class="stat-number"><?php echo (int)$urgent_count; ?></p>
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <h4>Inventory by Blood Type</h4>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr><th>Blood Type</th><th>Units Available</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory_summary as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['blood_type']); ?></td>
                                    <td><?php echo (int)$row['total_units']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($inventory_summary)): ?>
                                <tr><td colspan="2" style="text-align:center;">No inventory data</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h4>Demand Forecasts</h4>
                    <div class="data-table">
                        <table>
                            <thead><tr><th>Blood Type</th><th>Predicted Demand</th><th>Date</th><th>Confidence</th></tr></thead>
                            <tbody>
                                <?php foreach ($forecasts as $f): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($f['blood_type']); ?></td>
                                    <td><?php echo (int)$f['predicted_demand']; ?></td>
                                    <td><?php echo date('M j, Y', strtotime($f['forecast_date'])); ?></td>
                                    <td><?php echo ($f['confidence_level']*100) . '%'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($forecasts)): ?>
                                <tr><td colspan="4" style="text-align:center;">No forecasts available</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <h4>Recent Donations</h4>
                <div class="data-table">
                    <table>
                        <thead><tr><th>Donor</th><th>Blood Type</th><th>Quantity</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent_donations as $d): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($d['donor_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars($d['blood_type']); ?></td>
                                <td><?php echo (int)$d['quantity']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($d['donation_date'])); ?></td>
                                <td><?php echo ucfirst($d['status']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recent_donations)): ?>
                            <tr><td colspan="5" style="text-align:center;">No recent donations</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="margin-top:1rem;">
                <a href="dashboard.php" class="btn" style="background: var(--hospital-blue); color: white;">Back to Dashboard</a>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>