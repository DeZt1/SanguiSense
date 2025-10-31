<?php
include '../includes/auth.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);

// Get facilities for dropdown
global $pdo;
$facilities = $pdo->query("SELECT * FROM facilities WHERE type = 'blood_bank' OR type = 'hospital'")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $facility_id = $_POST['facility_id'];
    $donation_date = $_POST['donation_date'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO donations (donor_id, facility_id, blood_type, donation_date, status) VALUES (?, ?, ?, ?, 'scheduled')");
        $stmt->execute([$_SESSION['user_id'], $facility_id, $user['blood_type'], $donation_date]);
        
        // Add notification
        addNotification($_SESSION['user_id'], 'Donation Scheduled', "Your blood donation is scheduled for " . date('M j, Y', strtotime($donation_date)), 'info');
        
        $success = "Donation scheduled successfully!";
    } catch(PDOException $e) {
        $error = "Scheduling failed: " . $e->getMessage();
        // Log full PDO exception message for diagnosis
        $logPath = __DIR__ . '/../logs/sql_errors.log';
        @file_put_contents($logPath, date('c') . " - " . $e->getMessage() . PHP_EOL, FILE_APPEND);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Donation - SanguiSense</title>
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
            <h1>Schedule Donation</h1>
            <p>Book your next blood donation appointment</p>
        </div>

        <div class="schedule-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="schedule-form">
                <form method="POST">
                    <div class="form-group">
                        <label for="facility_id">Donation Center</label>
                        <select id="facility_id" name="facility_id" required>
                            <option value="">Select a facility</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['id']; ?>">
                                    <?php echo htmlspecialchars($facility['name'] . ' - ' . $facility['city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="donation_date">Donation Date</label>
                        <input type="date" id="donation_date" name="donation_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Your Blood Type</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['blood_type']); ?>" disabled>
                    </div>

                    <div class="eligibility-check">
                        <h3>Eligibility Check</h3>
                        <?php
                        $last_donation = $user['last_donation_date'];
                        $eligible = true;
                        
                        if ($last_donation) {
                            $next_donation_date = date('Y-m-d', strtotime($last_donation . ' + 56 days'));
                            if (strtotime($next_donation_date) > time()) {
                                $eligible = false;
                                echo "<div class='eligibility-status not-eligible'>";
                                echo "<p>You can donate again after " . date('M j, Y', strtotime($next_donation_date)) . "</p>";
                                echo "</div>";
                            }
                        }
                        
                        if ($eligible) {
                            echo "<div class='eligibility-status eligible'>";
                            echo "<p>You are eligible to donate blood!</p>";
                            echo "</div>";
                        }
                        ?>
                    </div>

                    <button type="submit" class="btn btn-primary" <?php echo !$eligible ? 'disabled' : ''; ?>>
                        Schedule Donation
                    </button>
                </form>
            </div>

            <div class="facilities-list">
                <h3>Available Donation Centers</h3>
                <div class="facilities-grid">
                    <?php foreach ($facilities as $facility): ?>
                        <div class="facility-card">
                            <h4><?php echo htmlspecialchars($facility['name']); ?></h4>
                            <p><?php echo htmlspecialchars($facility['type']); ?></p>
                            <p><?php echo htmlspecialchars($facility['address']); ?></p>
                            <p><?php echo htmlspecialchars($facility['city']); ?></p>
                            <p>Phone: <?php echo htmlspecialchars($facility['phone']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>