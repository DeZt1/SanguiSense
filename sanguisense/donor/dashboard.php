<?php
require_once __DIR__ . '/../includes/db_connect.php';
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
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_donor.php'; ?>    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo $user['name']; ?></h1>
            <p>Blood Type: <?php echo $user['blood_type']; ?></p>
        </div>

        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Quick Actions</h3>
                <div class="action-buttons">
                    <a href="eligibility_check.php" class="btn btn-primary">Check Eligibility</a>
                    <a href="schedule.php" class="btn btn-secondary">Schedule Donation</a>
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

            <?php
            // Eligibility status UI removed; keep survey prompt when not completed
            $eligibility_status = null;
            if (isset($_SESSION['eligibility_status'])) {
                $eligibility_status = $_SESSION['eligibility_status'];
            } else {
                try {
                    global $pdo;
                    $stmt = $pdo->prepare("SELECT eligibility_status FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $eligibility_status = $row['eligibility_status'] ?? null;
                } catch (Exception $e) {
                    $eligibility_status = null;
                }
            }

            if ($eligibility_status === null): ?>
                <div class="dashboard-card">
                    <h3>Complete Eligibility Survey</h3>
                    <p>You must complete the eligibility survey before we can determine if you're eligible to donate.</p>
                    <a href="eligibility_check.php" class="btn btn-primary" style="margin-top: 15px; display: inline-block;">Take Survey Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>