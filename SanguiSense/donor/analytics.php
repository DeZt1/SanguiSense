<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);

// Prepare metrics for the analytics page
global $pdo;
$error_msg = '';

if ($_SESSION['user_type'] === 'donor') {
    try {
        // Donor's donation history
        $donations_stmt = $pdo->prepare("
            SELECT 
                d.id,
                d.donation_date,
                d.quantity,
                d.blood_type,
                d.status,
                f.name as facility_name
            FROM donations d
            LEFT JOIN facilities f ON d.facility_id = f.id
            WHERE d.donor_id = ?
            ORDER BY d.donation_date DESC
        ");
        $donations_stmt->execute([$_SESSION['user_id']]);
        $donation_history = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching donation history: ' . $e->getMessage();
        $donation_history = [];
    }

    try {
        // Donation statistics
        $stats_stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_donations,
                COALESCE(SUM(quantity), 0) as total_units,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_donations,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_donations,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_donations,
                MAX(donation_date) as last_donation_date
            FROM donations
            WHERE donor_id = ?
        ");
        $stats_stmt->execute([$_SESSION['user_id']]);
        $donation_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching statistics: ' . $e->getMessage();
        $donation_stats = [
            'total_donations' => 0,
            'total_units' => 0,
            'completed_donations' => 0,
            'pending_donations' => 0,
            'cancelled_donations' => 0,
            'last_donation_date' => null
        ];
    }

    try {
        // Blood type distribution of this donor's donations
        $blood_dist_stmt = $pdo->prepare("
            SELECT 
                blood_type,
                COUNT(*) as count,
                SUM(quantity) as total_quantity
            FROM donations
            WHERE donor_id = ?
            GROUP BY blood_type
            ORDER BY count DESC
        ");
        $blood_dist_stmt->execute([$_SESSION['user_id']]);
        $blood_distribution = $blood_dist_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching blood distribution: ' . $e->getMessage();
        $blood_distribution = [];
    }

    try {
        // Monthly donation trends (last 12 months)
        $trends_stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(donation_date, '%Y-%m') as month,
                COUNT(*) as donation_count,
                COALESCE(SUM(quantity), 0) as total_units
            FROM donations 
            WHERE donor_id = ? AND donation_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(donation_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12
        ");
        $trends_stmt->execute([$_SESSION['user_id']]);
        $donation_trends = $trends_stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_msg = 'Error fetching donation trends: ' . $e->getMessage();
        $donation_trends = [];
    }

} else {
    $error_msg = 'This page is only accessible to donors.';
    $donation_history = [];
    $donation_stats = [];
    $blood_distribution = [];
    $donation_trends = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Donation Analytics - Donor Portal</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
            border-color: #ffd700;
        }
        
        .analytics-card h3 {
            color: #ffd700;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 0.5rem;
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #ffd700;
            margin: 1rem 0;
        }
        
        .metric-label {
            font-size: 0.9rem;
            color: #ccc;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stats-highlight {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(255, 215, 0, 0.3);
        }
        
        .stats-highlight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .highlight-card {
            background: rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
        }
        
        .highlight-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #1a1a1a;
            margin: 0.5rem 0;
        }
        
        .highlight-label {
            color: rgba(0, 0, 0, 0.7);
            font-size: 0.9rem;
        }

        .chart-container {
            position: relative;
            height: 250px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_donor.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>My Donation Analytics</h1>
            <p>Track your donation history and contribution statistics</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                <strong>ℹ️ Note:</strong> <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($donation_stats)): ?>
        <!-- Key Metrics Highlight -->
        <div class="stats-highlight">
            <div class="stats-highlight-grid">
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo (int)$donation_stats['total_donations']; ?></div>
                    <div class="highlight-label">Total Donations</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo (int)$donation_stats['total_units']; ?></div>
                    <div class="highlight-label">Units Donated</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo (int)$donation_stats['completed_donations']; ?></div>
                    <div class="highlight-label">Completed</div>
                </div>
                <div class="highlight-card">
                    <div class="highlight-value"><?php echo (int)$donation_stats['pending_donations']; ?></div>
                    <div class="highlight-label">Pending</div>
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            <!-- Donation Trends -->
            <div class="analytics-card">
                <h3>📈 Donation Trends (12 Months)</h3>
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>

            <!-- Blood Type Distribution -->
            <div class="analytics-card">
                <h3>🩸 Blood Type Distribution</h3>
                <div class="chart-container">
                    <canvas id="bloodDistChart"></canvas>
                </div>
            </div>

            <!-- Donation Status -->
            <div class="analytics-card">
                <h3>✓ Donation Status</h3>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Last Donation Info -->
        <?php if (!empty($donation_stats['last_donation_date'])): ?>
        <div class="content-card">
            <h3>🕒 Last Donation</h3>
            <p style="font-size: 1.1rem; margin: 1rem 0;">
                <strong><?php echo date('F j, Y \a\t g:i A', strtotime($donation_stats['last_donation_date'])); ?></strong>
            </p>
            <p style="color: #ccc;">
                You're making a great difference! Keep up the good work.
            </p>
        </div>
        <?php endif; ?>

        <!-- Donation History -->
        <div class="content-card">
            <h3>📋 Donation History</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Facility</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donation_history as $donation): ?>
                        <tr>
                            <td>
                                <strong><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></strong>
                                <br>
                                <small style="color: #666;"><?php echo date('g:i A', strtotime($donation['donation_date'])); ?></small>
                            </td>
                            <td>
                                <span style="font-weight: bold; color: #ffd700;">
                                    <?php echo htmlspecialchars($donation['blood_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-completed">
                                    <?php echo (int)$donation['quantity']; ?> units
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($donation['facility_name'] ?? 'Unknown Facility'); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($donation['status']); ?>">
                                    <?php echo ucfirst($donation['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($donation_history)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">
                                No donation history found. Schedule your first donation!
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="js/script.js"></script>
    <script>
        <?php if (!empty($donation_trends)): ?>
        // Donation Trends Chart
        const trendsCtx = document.getElementById('trendsChart');
        if (trendsCtx) {
            const trendsChart = new Chart(trendsCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_map(function($t) { 
                        return date('M Y', strtotime($t['month'] . '-01')); 
                    }, array_reverse($donation_trends))); ?>,
                    datasets: [{
                        label: 'Donations',
                        data: <?php echo json_encode(array_map(function($t) { return $t['donation_count']; }, array_reverse($donation_trends))); ?>,
                        borderColor: '#ffd700',
                        backgroundColor: 'rgba(255, 215, 0, 0.1)',
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
        }
        <?php endif; ?>

        <?php if (!empty($blood_distribution)): ?>
        // Blood Distribution Chart
        const bloodCtx = document.getElementById('bloodDistChart');
        if (bloodCtx) {
            const bloodChart = new Chart(bloodCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_map(function($d) { return $d['blood_type']; }, $blood_distribution)); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_map(function($d) { return $d['count']; }, $blood_distribution)); ?>,
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
                            labels: { color: '#fff', font: { size: 11 } }
                        }
                    }
                }
            });
        }
        <?php endif; ?>

        // Status Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            const statusChart = new Chart(statusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending', 'Cancelled'],
                    datasets: [{
                        data: [
                            <?php echo (int)($donation_stats['completed_donations'] ?? 0); ?>,
                            <?php echo (int)($donation_stats['pending_donations'] ?? 0); ?>,
                            <?php echo (int)($donation_stats['cancelled_donations'] ?? 0); ?>
                        ],
                        backgroundColor: ['#4ECDC4', '#FFC107', '#FF6B6B'],
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
                            labels: { color: '#fff', font: { size: 11 } }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>
