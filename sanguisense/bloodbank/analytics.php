<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireBloodBankAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Prepare metrics for the analytics page
global $pdo;
$error_msg = '';
$success_msg = '';

if ($facility) {
    try {
        // Inventory summary by blood type
        $inventory_stmt = $pdo->prepare("SELECT blood_type, COALESCE(SUM(quantity),0) as total_units FROM inventory WHERE facility_id = ? AND status = 'available' GROUP BY blood_type ORDER BY total_units DESC");
        $inventory_stmt->execute([$facility['id']]);
        $inventory_summary = $inventory_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching inventory: ' . $e->getMessage();
        $inventory_summary = [];
    }

    // Blood type distribution for charts
    $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $inventory_data = [];
    foreach ($blood_types as $type) {
        $inventory_data[$type] = 0;
    }
    if (!empty($inventory_summary)) {
        foreach ($inventory_summary as $item) {
            if (!empty($item['blood_type'])) {
                $inventory_data[$item['blood_type']] = (int)$item['total_units'];
            }
        }
    }

    try {
        // Monthly donation trends (last 6 months)
        $trends_stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(donation_date, '%Y-%m') as month,
                COUNT(*) as donation_count,
                COALESCE(SUM(quantity), 0) as total_units
            FROM donations 
            WHERE facility_id = ? AND donation_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(donation_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 6
        ");
        $trends_stmt->execute([$facility['id']]);
        $donation_trends = $trends_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching donation trends: ' . $e->getMessage();
        $donation_trends = [];
    }

    try {
        // Distribution statistics
        $distribution_stmt = $pdo->prepare("
            SELECT 
                COALESCE(blood_type, 'Unknown') as blood_type,
                COALESCE(SUM(quantity), 0) as total_distributed,
                COUNT(*) as distribution_count
            FROM distributions 
            WHERE from_facility_id = ?
            GROUP BY blood_type
            ORDER BY total_distributed DESC
        ");
        $distribution_stmt->execute([$facility['id']]);
        $distribution_data = $distribution_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching distribution data: ' . $e->getMessage();
        $distribution_data = [];
    }

    try {
        // Donor demographics
        $demographics_stmt = $pdo->prepare("
            SELECT 
                COALESCE(blood_type, 'Unknown') as blood_type,
                COUNT(*) as donor_count
            FROM users 
            WHERE user_type = 'donor'
            GROUP BY blood_type
        ");
        $demographics_stmt->execute();
        $demographics = $demographics_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching donor demographics: ' . $e->getMessage();
        $demographics = [];
    }

    try {
        // Top donors
        $donors_stmt = $pdo->prepare("
            SELECT 
                u.name,
                COALESCE(u.blood_type, 'Unknown') as blood_type,
                COUNT(d.id) as donation_count,
                COALESCE(SUM(d.quantity), 0) as total_donated
            FROM donations d
            JOIN users u ON d.donor_id = u.id
            WHERE d.facility_id = ? AND d.status = 'completed'
            GROUP BY u.id, u.name, u.blood_type
            ORDER BY total_donated DESC
            LIMIT 10
        ");
        $donors_stmt->execute([$facility['id']]);
        $top_donors = $donors_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching top donors: ' . $e->getMessage();
        $top_donors = [];
    }

    try {
        // Expiration alerts
        $expiring_stmt = $pdo->prepare("
            SELECT 
                blood_type, 
                quantity, 
                expiration_date 
            FROM inventory 
            WHERE facility_id = ? 
                AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                AND status = 'available'
            ORDER BY expiration_date ASC
            LIMIT 10
        ");
        $expiring_stmt->execute([$facility['id']]);
        $expiring_soon = $expiring_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching expiration alerts: ' . $e->getMessage();
        $expiring_soon = [];
    }

    try {
        // Total donations count
        $total_donations = $pdo->prepare("SELECT COUNT(*) as count FROM donations WHERE facility_id = ? AND status = 'completed'");
        $total_donations->execute([$facility['id']]);
        $total_donations_result = $total_donations->fetch(PDO::FETCH_ASSOC);
        $total_donations_count = ($total_donations_result && isset($total_donations_result['count'])) ? (int)$total_donations_result['count'] : 0;
    } catch (Exception $e) {
        $error_msg = 'Error fetching total donations: ' . $e->getMessage();
        $total_donations_count = 0;
    }

} else {
    $error_msg = 'Facility not assigned to this user. Please contact administrator.';
    $inventory_summary = [];
    $inventory_data = array_fill_keys(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], 0);
    $donation_trends = [];
    $distribution_data = [];
    $demographics = [];
    $top_donors = [];
    $expiring_soon = [];
    $total_donations_count = 0;
}

$total_inventory = array_sum($inventory_data);
$total_distributed = !empty($distribution_data) ? array_sum(array_column($distribution_data, 'total_distributed')) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Blood Bank Portal</title>
    <link rel="stylesheet" href="css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .analytics-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }
        
        .analytics-card:hover {
            transform: translateY(-5px);
            border-color: var(--bloodbank-purple);
        }
        
        .analytics-card h3 {
            color: var(--yellow);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            border-bottom: 2px solid var(--bloodbank-purple);
            padding-bottom: 0.5rem;
        }
        
        .chart-container {
            position: relative;
            height: 250px;
            margin-top: 1rem;
        }
        
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .metric-card {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid var(--bloodbank-purple);
        }
        
        .metric-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--yellow);
            margin: 0.5rem 0;
        }
        
        .metric-label {
            font-size: 0.8rem;
            color: #ccc;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .progress-bar {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            height: 8px;
            margin: 0.5rem 0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--bloodbank-purple), #b388a0);
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .blood-type-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .blood-type-item {
            background: rgba(255,255,255,0.1);
            padding: 0.8rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .blood-type-value {
            font-size: 1.2rem;
            font-weight: bold;
            color: var(--yellow);
            margin: 0.3rem 0;
        }
        
        .stats-highlight {
            background: linear-gradient(135deg, var(--bloodbank-purple), #8b4e9a);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
        }
        
        .stats-highlight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .highlight-card {
            text-align: center;
            padding: 1rem;
        }
        
        .highlight-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            margin: 0.5rem 0;
        }
        
        .highlight-label {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_bloodbank.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Analytics & Insights</h1>
            <p>Comprehensive data visualization and performance metrics for <?php echo htmlspecialchars($facility['name'] ?? 'Your Blood Bank'); ?></p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                <strong>⚠️ Note:</strong> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>
        
        <!-- Key Metrics Highlight -->
        <div class="stats-highlight">
            <div class="stats-highlight-grid">
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo $total_inventory; ?></div>
                    <div class="highlight-label">Total Blood Units</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo $total_donations_count; ?></div>
                    <div class="highlight-label">Total Donations</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo $total_distributed; ?></div>
                    <div class="highlight-label">Distributed Units</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo count($top_donors); ?></div>
                    <div class="highlight-label">Top Donors Listed</div>
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            <!-- Blood Inventory Distribution -->
            <div class="analytics-card">
                <h3>📊 Blood Inventory Distribution</h3>
                <div class="chart-container">
                    <canvas id="inventoryChart"></canvas>
                </div>
                <div class="blood-type-grid">
                    <?php foreach ($inventory_data as $type => $count): ?>
                    <div class="blood-type-item">
                        <div style="font-size: 0.8rem; color: #ccc;"><?php echo $type; ?></div>
                        <div class="blood-type-value"><?php echo $count; ?></div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $total_inventory > 0 ? ($count / $total_inventory * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Donation Trends -->
            <div class="analytics-card">
                <h3>📈 Donation Trends (6 Months)</h3>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
                <div class="metric-grid">
                    <div class="metric-card">
                        <div class="metric-label">Monthly Avg</div>
                        <div class="metric-value">
                            <?php 
                            $avg_donations = count($donation_trends) > 0 ? array_sum(array_column($donation_trends, 'donation_count')) / count($donation_trends) : 0;
                            echo round($avg_donations, 1);
                            ?>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-label">Peak Month</div>
                        <div class="metric-value">
                            <?php 
                            $peak = count($donation_trends) > 0 ? max(array_column($donation_trends, 'donation_count')) : 0;
                            echo $peak;
                            ?>
                        </div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-label">Growth</div>
                        <div class="metric-value">
                            <?php 
                            if (count($donation_trends) >= 2) {
                                $current = $donation_trends[0]['donation_count'];
                                $previous = $donation_trends[1]['donation_count'];
                                $growth = $previous > 0 ? (($current - $previous) / $previous * 100) : 0;
                                echo round($growth, 1) . '%';
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distribution Analysis -->
            <div class="analytics-card">
                <h3>🚚 Distribution by Blood Type</h3>
                <div class="chart-container">
                    <canvas id="distributionChart"></canvas>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <div style="color: #ccc; font-size: 0.9rem;">
                        Total Distributed: <?php echo $total_distributed; ?> units
                    </div>
                </div>
            </div>

            <!-- Donor Demographics -->
            <div class="analytics-card">
                <h3>👥 Donor Blood Type Distribution</h3>
                <div class="chart-container">
                    <canvas id="demographicsChart"></canvas>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <div style="color: #ccc; font-size: 0.9rem;">
                        Total Registered Donors: <?php echo array_sum(array_column($demographics, 'donor_count')); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Donors -->
        <div class="content-card">
            <h3>⭐ Top Donors</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Donor Name</th>
                            <th>Blood Type</th>
                            <th>Donations</th>
                            <th>Total Units</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_donors as $donor): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($donor['name']); ?></strong>
                            </td>
                            <td>
                                <span style="font-weight: bold; color: var(--bloodbank-purple);">
                                    <?php echo htmlspecialchars($donor['blood_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-completed">
                                    <?php echo $donor['donation_count']; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $donor['total_donated']; ?> units
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($top_donors)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">
                                No donor data available
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Expiration Alerts -->
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
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($expiring_soon)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center;">No expiring items</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Blood Inventory Chart
        const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
        const inventoryChart = new Chart(inventoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($inventory_data)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($inventory_data)); ?>,
                    backgroundColor: [
                        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', 
                        '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F'
                    ],
                    borderWidth: 2,
                    borderColor: 'rgba(255,255,255,0.1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#fff',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });

        // Donation Trends Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        const trendsChart = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($t) { 
                    return date('M Y', strtotime($t['month'] . '-01')); 
                }, array_reverse($donation_trends))); ?>,
                datasets: [{
                    label: 'Donations',
                    data: <?php echo json_encode(array_map(function($t) { return $t['donation_count']; }, array_reverse($donation_trends))); ?>,
                    borderColor: '#a855c2',
                    backgroundColor: 'rgba(168, 85, 194, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: { color: '#ccc' }
                    },
                    x: {
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: { color: '#ccc' }
                    }
                }
            }
        });

        // Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        const distributionChart = new Chart(distributionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { return $d['blood_type']; }, $distribution_data)); ?>,
                datasets: [{
                    label: 'Units Distributed',
                    data: <?php echo json_encode(array_map(function($d) { return $d['total_distributed']; }, $distribution_data)); ?>,
                    backgroundColor: '#a855c2',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: { color: '#ccc' }
                    },
                    x: {
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        ticks: { color: '#ccc' }
                    }
                }
            }
        });

        // Demographics Chart
        const demographicsCtx = document.getElementById('demographicsChart').getContext('2d');
        const demographicsChart = new Chart(demographicsCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_map(function($d) { return $d['blood_type']; }, $demographics)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_map(function($d) { return $d['donor_count']; }, $demographics)); ?>,
                    backgroundColor: [
                        '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', 
                        '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#fff',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>