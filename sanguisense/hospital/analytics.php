<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireHospitalAdmin();

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
        // Monthly donation trends (last 6 months) - only fulfilled donations
        $trends_stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(donation_date, '%Y-%m') as month,
                COUNT(*) as donation_count,
                COALESCE(SUM(quantity), 0) as total_units
            FROM donations 
            WHERE facility_id = ? AND donation_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND status = 'fulfilled'
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
        // Request statistics
        $requests_stmt = $pdo->prepare("
            SELECT 
                COALESCE(urgency, 'normal') as urgency,
                COALESCE(status, 'pending') as status,
                COUNT(*) as count
            FROM blood_requests 
            WHERE facility_id = ?
            GROUP BY urgency, status
        ");
        $requests_stmt->execute([$facility['id']]);
        $requests_data = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching blood requests: ' . $e->getMessage();
        $requests_data = [];
    }

    // Calculate request metrics
    $pending_requests = 0;
    $urgent_requests = 0;
    $fulfilled_requests = 0;
    
    foreach ($requests_data as $request) {
        if ($request['status'] == 'pending') $pending_requests += $request['count'];
        if (in_array($request['urgency'], ['urgent','emergency','critical'])) $urgent_requests += $request['count'];
        if ($request['status'] == 'fulfilled') $fulfilled_requests += $request['count'];
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
        // Recent completed (fulfilled) donations for table
        $donations_stmt = $pdo->prepare("SELECT d.*, u.name as donor_name, u.phone, u.blood_type FROM donations d LEFT JOIN users u ON d.donor_id = u.id WHERE d.facility_id = ? AND d.status = 'fulfilled' ORDER BY d.donation_date DESC LIMIT 8");
        $donations_stmt->execute([$facility['id']]);
        $recent_donations = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching recent donations: ' . $e->getMessage();
        $recent_donations = [];
    }

} else {
    $error_msg = 'Facility not assigned to this user. Please contact administrator.';
    $inventory_summary = [];
    $inventory_data = array_fill_keys(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'], 0);
    $donation_trends = [];
    $requests_data = [];
    $pending_requests = 0;
    $urgent_requests = 0;
    $fulfilled_requests = 0;
    $demographics = [];
    $recent_donations = [];
}

$total_inventory = array_sum($inventory_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Hospital Portal</title>
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
            border-color: var(--hospital-blue);
        }
        
        .analytics-card h3 {
            color: var(--yellow);
            margin-bottom: 1rem;
            font-size: 1.2rem;
            border-bottom: 2px solid var(--hospital-blue);
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
            border-left: 4px solid var(--hospital-blue);
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
            background: linear-gradient(90deg, var(--hospital-blue), #4fc3f7);
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
        
        .trend-indicator {
            display: inline-flex;
            align-items: center;
            font-size: 0.8rem;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            margin-left: 0.5rem;
        }
        
        .trend-up {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        
        .trend-down {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
        
        .stats-highlight {
            background: linear-gradient(135deg, var(--hospital-blue), #1565c0);
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
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Analytics & Insights</h1>
            <p>Comprehensive data visualization and performance metrics for <?php echo htmlspecialchars($facility['name'] ?? 'Your Facility'); ?></p>
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
                    <div class="highlight-value"><?php echo $pending_requests; ?></div>
                    <div class="highlight-label">Pending Requests</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo $urgent_requests; ?></div>
                    <div class="highlight-label">Urgent Cases</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo count($recent_donations); ?></div>
                    <div class="highlight-label">Recent Donations</div>
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

            <!-- Request Analysis -->
            <div class="analytics-card">
                <h3>🆘 Blood Request Analysis</h3>
                <div class="chart-container">
                    <canvas id="requestsChart"></canvas>
                </div>
                <div class="metric-grid">
                    <div class="metric-card">
                        <div class="metric-label">Pending</div>
                        <div class="metric-value"><?php echo $pending_requests; ?></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-label">Urgent</div>
                        <div class="metric-value"><?php echo $urgent_requests; ?></div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-label">Fulfilled</div>
                        <div class="metric-value"><?php echo $fulfilled_requests; ?></div>
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

        <!-- Recent Activity -->
        <div class="content-card">
            <h3>🕒 Recent Donation Activity</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Donor</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Facility</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_donations as $d): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($d['donor_name'] ?? 'Unknown'); ?></strong>
                            </td>
                            <td>
                                <span style="font-weight: bold; color: var(--hospital-blue);">
                                    <?php echo htmlspecialchars($d['blood_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-completed">
                                    <?php echo (int)$d['quantity']; ?> units
                                </span>
                            </td>
                            <td>
                                <?php echo date('M j, Y', strtotime($d['donation_date'])); ?>
                                <br>
                                <small style="color: #666;"><?php echo date('g:i A', strtotime($d['donation_date'])); ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $d['status']; ?>">
                                    <?php echo ucfirst($d['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($facility['name'] ?? 'Current Facility'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent_donations)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666;">
                                No recent donation activity
                            </td>
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
                    borderColor: '#1e88e5',
                    backgroundColor: 'rgba(30, 136, 229, 0.1)',
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

        // Requests Chart
        const requestsCtx = document.getElementById('requestsChart').getContext('2d');
        const requestsChart = new Chart(requestsCtx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Urgent', 'Fulfilled'],
                datasets: [{
                    data: [<?php echo $pending_requests; ?>, <?php echo $urgent_requests; ?>, <?php echo $fulfilled_requests; ?>],
                    backgroundColor: ['#FFC107', '#FF6B6B', '#4ECDC4'],
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