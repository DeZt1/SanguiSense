<?php
include '../includes/auth.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);
$notifications = getNotifications($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SanguiSense</title>
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
            <h1>Welcome, <?php echo $user['name']; ?></h1>
            <p>Blood Type: <?php echo $user['blood_type']; ?></p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="schedule.php" class="btn btn-primary">Schedule Donation</a>
                    <a href="profile.php" class="btn btn-secondary">Update Profile</a>
                </div>
            </div>

            <div class="dashboard-card">
                <h3>Notifications</h3>
                <?php if (empty($notifications)): ?>
                    <p>No new notifications</p>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="notification-item <?php echo $notification['type']; ?>">
                                <h4><?php echo $notification['title']; ?></h4>
                                <p><?php echo $notification['message']; ?></p>
                                <small><?php echo date('M j, Y g:i A', strtotime($notification['created_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h3>Donation Eligibility</h3>
                <?php
                $last_donation = $user['last_donation_date'];
                $eligible = true;
                $message = "You are eligible to donate blood";
                
                if ($last_donation) {
                    $next_donation_date = date('Y-m-d', strtotime($last_donation . ' + 56 days')); // 8 weeks
                    if (strtotime($next_donation_date) > time()) {
                        $eligible = false;
                        $message = "You can donate again after " . date('M j, Y', strtotime($next_donation_date));
                    }
                }
                ?>
                <div class="eligibility-status <?php echo $eligible ? 'eligible' : 'not-eligible'; ?>">
                    <p><?php echo $message; ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>