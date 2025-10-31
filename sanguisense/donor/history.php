<?php
include '../includes/auth.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);

// Get donation history
global $pdo;
$stmt = $pdo->prepare("
    SELECT d.*, f.name as facility_name, f.type as facility_type 
    FROM donations d 
    JOIN facilities f ON d.facility_id = f.id 
    WHERE d.donor_id = ? 
    ORDER BY d.donation_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation History - SanguiSense</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="background-animation"></div>
    
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <h2><a href="index.php" class="logo-link">
                <span class="blood-drop">🩸</span>SanguiSense
            </a></h2>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="profile.php" class="nav-link">Profile</a>
            <a href="schedule.php" class="nav-link">Schedule</a>
            <a href="history.php" class="nav-link">History</a>
            <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
        </div>
    </div>
</nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Donation History</h1>
            <p>Track your blood donation journey</p>
        </div>

        <div class="history-container">
            <div class="history-stats">
                <div class="stat-card">
                    <h3>Total Donations</h3>
                    <p class="stat-number"><?php echo count($donations); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Last Donation</h3>
                    <p class="stat-number">
                        <?php 
                        if ($user['last_donation_date']) {
                            echo date('M j, Y', strtotime($user['last_donation_date']));
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3>Next Eligible</h3>
                    <p class="stat-number">
                        <?php
                        if ($user['last_donation_date']) {
                            $next_date = date('M j, Y', strtotime($user['last_donation_date'] . ' + 56 days'));
                            echo $next_date;
                        } else {
                            echo 'Now';
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="donations-list">
                <h3>Your Donations</h3>
                
                <?php if (empty($donations)): ?>
                    <div class="no-data">
                        <p>You haven't made any donations yet.</p>
                        <a href="schedule.php" class="btn btn-primary">Schedule Your First Donation</a>
                    </div>
                <?php else: ?>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Facility</th>
                                    <th>Blood Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($donations as $donation): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($donation['facility_name']); ?></td>
                                        <td><?php echo htmlspecialchars($donation['blood_type']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $donation['status']; ?>">
                                                <?php echo ucfirst($donation['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($donation['status'] == 'scheduled'): ?>
                                                <button onclick="cancelDonation(<?php echo $donation['id']; ?>)" class="btn btn-small btn-danger">Cancel</button>
                                            <?php else: ?>
                                                <span class="text-muted">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function cancelDonation(donationId) {
            if (confirm('Are you sure you want to cancel this donation?')) {
                window.location.href = 'cancel_donation.php?id=' + donationId;
            }
        }
    </script>
</body>
</html>