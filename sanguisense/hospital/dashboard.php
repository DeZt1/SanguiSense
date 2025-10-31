<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Check if facility exists before getting stats
if ($facility) {
    $stats = getHospitalStats($facility['id']);
} else {
    // Default stats if no facility found
    $stats = [
        'total_donors' => 0,
        'pending_donations' => 0,
        'recent_donations' => 0,
        'blood_requests' => 0
    ];
}

// Get recent donations for this hospital
global $pdo;
if ($facility) {
    $recent_donations = $pdo->prepare("
        SELECT d.*, u.name as donor_name, u.blood_type 
        FROM donations d 
        JOIN users u ON d.donor_id = u.id 
        WHERE d.facility_id = ? 
        ORDER BY d.created_at DESC 
        LIMIT 5
    ");
    $recent_donations->execute([$facility['id']]);
    $recent_donations = $recent_donations->fetchAll(PDO::FETCH_ASSOC);
} else {
    $recent_donations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Dashboard - SanguiSense</title>
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
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="inventory.php" class="nav-link">Blood Inventory</a>
                <a href="donors.php" class="nav-link">Donors</a>
                <a href="appointments.php" class="nav-link">Appointments</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Hospital Management Dashboard</h1>
            <p>Welcome, <?php echo $user['name']; ?> | <?php echo $facility ? $facility['name'] : 'No Facility Assigned'; ?> | Manage blood supply and patient needs</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card hospital-stat">
                <div class="stat-icon">👥</div>
                <h3>Total Donors</h3>
                <p class="stat-number"><?php echo $stats['total_donors']; ?></p>
            </div>
            <div class="stat-card hospital-stat">
                <div class="stat-icon">📅</div>
                <h3>Pending Appointments</h3>
                <p class="stat-number"><?php echo $stats['pending_donations']; ?></p>
            </div>
            <div class="stat-card hospital-stat">
                <div class="stat-icon">💉</div>
                <h3>Recent Donations</h3>
                <p class="stat-number"><?php echo $stats['recent_donations']; ?></p>
            </div>
            <div class="stat-card hospital-stat">
                <div class="stat-icon">🆘</div>
                <h3>Blood Requests</h3>
                <p class="stat-number"><?php echo $stats['blood_requests']; ?></p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="content-grid">
                <div class="content-card">
                    <h3>Recent Donations</h3>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Donor</th>
                                    <th>Blood Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_donations as $donation): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                                    <td><?php echo htmlspecialchars($donation['blood_type']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $donation['status']; ?>">
                                            <?php echo ucfirst($donation['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_donations)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">No recent donations</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="content-card">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons-vertical">
                        <a href="inventory.php?action=add" class="btn btn-primary" style="background: var(--hospital-blue);">Add Blood Stock</a>
                        <a href="blood_requests.php" class="btn btn-secondary">Create Blood Request</a>
                        <a href="appointments.php" class="btn btn-secondary">Schedule Donation</a>
                        <a href="donors.php" class="btn btn-secondary">Manage Donors</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>