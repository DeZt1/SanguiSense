<?php
include '../includes/auth.php';
requireBloodBankAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get analytics data
global $pdo;

// Blood type distribution in inventory
if ($facility) {
    $blood_type_stats = $pdo->prepare("
        SELECT blood_type, SUM(quantity) as total_quantity 
        FROM inventory 
        WHERE facility_id = ? AND status = 'available'
        GROUP BY blood_type
        ORDER BY total_quantity DESC
    ");
    $blood_type_stats->execute([$facility['id']]);
    $blood_type_stats = $blood_type_stats->fetchAll(PDO::FETCH_ASSOC);
} else {
    $blood_type_stats = [];
}

// Monthly donations
if ($facility) {
    $monthly_donations = $pdo->prepare("
        SELECT 
            DATE_FORMAT(donation_date, '%Y-%m') as month,
            COUNT(*) as donation_count,
            SUM(quantity) as total_quantity
        FROM donations 
        WHERE facility_id = ? AND status = 'completed'
        GROUP BY DATE_FORMAT(donation_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthly_donations->execute([$facility['id']]);
    $monthly_donations = $monthly_donations->fetchAll(PDO::FETCH_ASSOC);
} else {
    $monthly_donations = [];
}

// Distribution statistics
if ($facility) {
    $distribution_stats = $pdo->prepare("
        SELECT 
            blood_type,
            SUM(quantity) as total_distributed,
            COUNT(*) as distribution_count
        FROM distributions 
        WHERE from_facility_id = ?
        GROUP BY blood_type
        ORDER BY total_distributed DESC
    ");
    $distribution_stats->execute([$facility['id']]);
    $distribution_stats = $distribution_stats->fetchAll(PDO::FETCH_ASSOC);
} else {
    $distribution_stats = [];
}

// Expiration alerts
if ($facility) {
    $expiring_soon = $pdo->prepare("
        SELECT blood_type, quantity, expiration_date 
        FROM inventory 
        WHERE facility_id = ? AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        ORDER BY expiration_date ASC
        LIMIT 10
    ");
    $expiring_soon->execute([$facility['id']]);
    $expiring_soon = $expiring_soon->fetchAll(PDO::FETCH_ASSOC);
} else {
    $expiring_soon = [];
}

// Top donors
if ($facility) {
    $top_donors = $pdo->prepare("
        SELECT 
            u.name,
            u.blood_type,
            COUNT(d.id) as donation_count,
            SUM(d.quantity) as total_donated
        FROM donations d
        JOIN users u ON d.donor_id = u.id
        WHERE d.facility_id = ? AND d.status = 'completed'
        GROUP BY u.id, u.name, u.blood_type
        ORDER BY total_donated DESC
        LIMIT 10
    ");
    $top_donors->execute([$facility['id']]);
    $top_donors = $top_donors->fetchAll(PDO::FETCH_ASSOC);
} else {
    $top_donors = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Blood Bank Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><a href="dashboard.php" class="logo-link">
                    <span class="blood-drop">🩸</span>SanguiSense Blood Bank
                </a></h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="inventory.php" class="nav-link">Inventory</a>
                <a href="donations.php" class="nav-link">Donations</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="distribution.php" class="nav-link">Distribution</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Analytics & Reports</h1>
            <p>Data-driven insights for blood bank management</p>
        </div>

        <div class="analytics-grid">
            <div class="analytics-card">
                <h3>📊 Blood Type Distribution</h3>
                <div class="chart-container">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Blood Type</th>
                                <th>Quantity</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_quantity = array_sum(array_column($blood_type_stats, 'total_quantity'));
                            foreach ($blood_type_stats as $stat):
                                $percentage = $total_quantity > 0 ? ($stat['total_quantity'] / $total_quantity) * 100 : 0;
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($stat['blood_type']); ?></td>
                                    <td><?php echo $stat['total_quantity']; ?> units</td>
                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($blood_type_stats)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">No inventory data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="analytics-card">
                <h3>📈 Monthly Donations</h3>
                <div class="chart-container">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Donations</th>
                                <th>Total Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_donations as $monthly): ?>
                                <tr>
                                    <td><?php echo date('M Y', strtotime($monthly['month'] . '-01')); ?></td>
                                    <td><?php echo $monthly['donation_count']; ?></td>
                                    <td><?php echo $monthly['total_quantity']; ?> units</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($monthly_donations)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">No donation data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="analytics-card">
                <h3>🚚 Distribution Statistics</h3>
                <div class="chart-container">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Blood Type</th>
                                <th>Distributed</th>
                                <th>Shipments</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($distribution_stats as $dist): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($dist['blood_type']); ?></td>
                                    <td><?php echo $dist['total_distributed']; ?> units</td>
                                    <td><?php echo $dist['distribution_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($distribution_stats)): ?>
                                <tr>
                                    <td colspan="3" style="text-align: center;">No distribution data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="analytics-card">
                <h3>⭐ Top Donors</h3>
                <div class="chart-container">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Donor</th>
                                <th>Blood Type</th>
                                <th>Donations</th>
                                <th>Total Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_donors as $donor): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($donor['name']); ?></td>
                                    <td><?php echo htmlspecialchars($donor['blood_type']); ?></td>
                                    <td><?php echo $donor['donation_count']; ?></td>
                                    <td><?php echo $donor['total_donated']; ?> units</td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($top_donors)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No donor data</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <h3>⚠️ Expiration Alerts (Next 14 Days)</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Blood Type</th>
                                <th>Quantity</th>
                                <th>Expiration Date</th>
                                <th>Days Left</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiring_soon as $item): 
                                $days_left = floor((strtotime($item['expiration_date']) - time()) / (60 * 60 * 24));
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['blood_type']); ?></td>
                                    <td class="expiring-soon"><?php echo $item['quantity']; ?> units</td>
                                    <td class="expiring-soon"><?php echo date('M j, Y', strtotime($item['expiration_date'])); ?></td>
                                    <td class="expiring-soon"><?php echo $days_left; ?> days</td>
                                    <td>
                                        <button onclick="prioritizeDistribution(<?php echo $item['id']; ?>)" class="btn btn-small" style="background: var(--bloodbank-purple);">Prioritize</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($expiring_soon)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No expiring items</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-card">
                <h3>📋 Quick Stats</h3>
                <div class="stats-grid" style="grid-template-columns: 1fr;">
                    <?php
                    // Calculate some quick stats
                    $total_donations = $pdo->prepare("SELECT COUNT(*) as count FROM donations WHERE facility_id = ? AND status = 'completed'");
                    $total_donations->execute([$facility['id']]);
                    $total_donations = $total_donations->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

                    $total_distributed = $pdo->prepare("SELECT SUM(quantity) as total FROM distributions WHERE from_facility_id = ?");
                    $total_distributed->execute([$facility['id']]);
                    $total_distributed = $total_distributed->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

                    $active_donors = $pdo->prepare("SELECT COUNT(DISTINCT donor_id) as count FROM donations WHERE facility_id = ? AND donation_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)");
                    $active_donors->execute([$facility['id']]);
                    $active_donors = $active_donors->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                    ?>
                    
                    <div class="stat-card bloodbank-stat">
                        <h3>Total Donations</h3>
                        <p class="stat-number"><?php echo $total_donations; ?></p>
                    </div>
                    <div class="stat-card bloodbank-stat">
                        <h3>Total Distributed</h3>
                        <p class="stat-number"><?php echo $total_distributed; ?> units</p>
                    </div>
                    <div class="stat-card bloodbank-stat">
                        <h3>Active Donors</h3>
                        <p class="stat-number"><?php echo $active_donors; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="analytics-actions">
            <h3>Report Generation</h3>
            <div class="action-buttons">
                <button onclick="generateReport('monthly')" class="btn btn-primary" style="background: var(--bloodbank-purple);">Monthly Report</button>
                <button onclick="generateReport('inventory')" class="btn btn-secondary">Inventory Report</button>
                <button onclick="generateReport('donations')" class="btn btn-secondary">Donations Report</button>
                <button onclick="generateReport('distribution')" class="btn btn-secondary">Distribution Report</button>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function prioritizeDistribution(id) {
            alert('Prioritizing distribution for item ' + id);
            window.location.href = 'distribution.php?prioritize=' + id;
        }

        function generateReport(type) {
            alert('Generating ' + type + ' report... This would download a PDF file.');
            // In a real implementation, this would make an AJAX call to generate and download the report
        }
    </script>
</body>
</html>