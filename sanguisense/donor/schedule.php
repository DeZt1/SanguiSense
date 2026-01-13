<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);

// Check eligibility - allow if passed or if database shows eligible status
$can_access = false;
if (isset($_SESSION['eligibility_passed']) && $_SESSION['eligibility_passed']) {
    $can_access = true;
} else {
    // Check database for eligibility status
    try {
        $statusStmt = $pdo->prepare("SELECT eligibility_status FROM users WHERE id = ?");
        $statusStmt->execute([$_SESSION['user_id']]);
        $row = $statusStmt->fetch(PDO::FETCH_ASSOC);
        $db_status = $row['eligibility_status'] ?? null;
        if ($db_status === 'passed' || $db_status === 'eligible') {
            $can_access = true;
        }
    } catch (Exception $e) {
        // If query fails, redirect to eligibility check
        $can_access = false;
    }
}

if (!$can_access) {
    header('Location: eligibility_check.php');
    exit();
}

// Check if donor has blood type set
if (!$user['blood_type']) {
    $error_msg = "Please set your blood type in your profile before scheduling a donation";
    header('Location: dashboard.php?error=' . urlencode($error_msg));
    exit();
}

// Get facilities for dropdown
global $pdo;
$facilities = $pdo->query("SELECT * FROM facilities WHERE type = 'blood_bank' OR type = 'hospital'")->fetchAll(PDO::FETCH_ASSOC);

// Check if donor is in 56-day waiting period
$lastDonationStmt = $pdo->prepare("SELECT MAX(donation_date) as last_fulfilled FROM donations WHERE donor_id = ? AND status = 'fulfilled'");
$lastDonationStmt->execute([$_SESSION['user_id']]);
$lastDonationResult = $lastDonationStmt->fetch(PDO::FETCH_ASSOC);
$last_fulfilled_date = $lastDonationResult['last_fulfilled'] ?? null;
$waiting_period_message = null;
$can_schedule = true;

if ($last_fulfilled_date) {
    $next_eligible_timestamp = strtotime($last_fulfilled_date . ' + 56 days');
    if ($next_eligible_timestamp > time()) {
        $can_schedule = false;
        $waiting_period_message = "You recently donated on " . date('M j, Y', strtotime($last_fulfilled_date)) . ". You can schedule your next donation on " . date('M j, Y', $next_eligible_timestamp) . ".";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $facility_id = $_POST['facility_id'] ?? null;
    $donation_date = $_POST['donation_date'] ?? null;
    
    $errors = [];
    
    if (empty($facility_id)) {
        $errors[] = "Please select a facility";
    }
    
    if (empty($donation_date)) {
        $errors[] = "Please select a donation date";
    }
    
    // Check 56-day waiting period
    if (!empty($donation_date) && $last_fulfilled_date) {
        $next_eligible_timestamp = strtotime($last_fulfilled_date . ' + 56 days');
        if (strtotime($donation_date) < $next_eligible_timestamp) {
            $errors[] = "You must wait at least 56 days between donations. You are eligible after " . date('M j, Y', $next_eligible_timestamp) . ".";
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO donations (donor_id, facility_id, blood_type, donation_date, status) VALUES (?, ?, ?, ?, 'scheduled')");
            $stmt->execute([$_SESSION['user_id'], $facility_id, $user['blood_type'], $donation_date]);
            
            addNotification($_SESSION['user_id'], 'Donation Scheduled', "Your blood donation is scheduled for " . date('M j, Y', strtotime($donation_date)), 'info');
            
            $facilityStmt = $pdo->prepare("SELECT admin_id, name FROM facilities WHERE id = ?");
            $facilityStmt->execute([$facility_id]);
            $facility = $facilityStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($facility && !empty($facility['admin_id'])) {
                addNotification($facility['admin_id'], 'New Donor Scheduled', $user['name'] . " has scheduled a blood donation of " . $user['blood_type'] . " on " . date('M j, Y', strtotime($donation_date)) . " at your facility.", 'info');
            }
            
            $success = "Donation scheduled successfully!";
        } catch(PDOException $e) {
            $error = "Error scheduling donation: " . $e->getMessage();
        }
    } else {
        $error = implode(", ", $errors);
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
    
<?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_donor.php'; ?>

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

            <?php if ($waiting_period_message): ?>
                <div class="alert alert-warning">
                    <strong>⏱️ Waiting Period:</strong> <?php echo htmlspecialchars($waiting_period_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!$can_schedule): ?>
                <div style="text-align: center; padding: 3rem; color: #999;">
                    <p>You are currently in your 56-day waiting period after your recent donation.</p>
                    <p>Please check back on the date above to schedule your next donation.</p>
                    <a href="dashboard.php" class="btn btn-secondary" style="margin-top: 1rem;">Back to Dashboard</a>
                </div>
            <?php else: ?>

            <div class="schedule-form">
                <h3>Schedule Your Donation</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="facility_id">Select Facility *</label>
                        <select id="facility_id" name="facility_id" required>
                            <option value="">-- Select a facility --</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?php echo $facility['id']; ?>">
                                    <?php echo htmlspecialchars($facility['name'] . ' - ' . $facility['city']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="donation_date">Donation Date *</label>
                        <input type="date" id="donation_date" name="donation_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Your Blood Type</label>
                        <input type="text" value="<?php echo $user['blood_type'] ?: 'Not Set'; ?>" disabled>
                    </div>

                    <button type="submit" class="btn btn-primary">Schedule Donation</button>
                </form>
            </div>
        </div>
        <?php endif; // End of can_schedule check ?>
    </div>

    <script src="js/script.js"></script>
</body>
</html>