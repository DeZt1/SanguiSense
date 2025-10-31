<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get scheduled donations (appointments) for this facility
global $pdo;
if ($facility) {
    $appointments_stmt = $pdo->prepare("SELECT d.*, u.name as donor_name, u.email as donor_email FROM donations d LEFT JOIN users u ON d.donor_id = u.id WHERE d.facility_id = ? AND d.status = 'scheduled' ORDER BY d.donation_date ASC, d.created_at DESC");
    $appointments_stmt->execute([$facility['id']]);
    $appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $appointments = [];
}

// Handle appointment actions (complete or cancel)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = $_GET['id'];
    $action = $_GET['action'];
    try {
        if ($action == 'complete') {
            $update = $pdo->prepare("UPDATE donations SET status = 'completed' WHERE id = ?");
            $update->execute([$appt_id]);
            $success = "Appointment marked as completed.";
        } elseif ($action == 'cancel') {
            $update = $pdo->prepare("UPDATE donations SET status = 'cancelled' WHERE id = ?");
            $update->execute([$appt_id]);
            $success = "Appointment cancelled.";
        }
        header('Location: appointments.php?success=1');
        exit();
    } catch (PDOException $e) {
        $error = "Failed to update appointment: " . $e->getMessage();
    }
}

// No admin-side appointment creation; donors schedule via donor/schedule.php
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Hospital Portal</title>
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="inventory.php" class="nav-link">Blood Inventory</a>
                <a href="donors.php" class="nav-link">Donors</a>
                <a href="appointments.php" class="nav-link active">Appointments</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Appointment Management</h1>
            <p>Manage donor appointments and schedules</p>
        </div>
        
        <div class="content-card">
            <h3>Scheduled Donor Appointments</h3>
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Operation completed successfully!</div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Donors schedule appointments via donor/schedule.php; no admin creation UI here -->

            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Appointment ID</th>
                            <th>Donor</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Donation Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td>#D<?php echo str_pad($appt['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($appt['donor_name']); ?> <br><small><?php echo htmlspecialchars($appt['donor_email']); ?></small></td>
                            <td><?php echo htmlspecialchars($appt['blood_type']); ?></td>
                            <td><?php echo (int)$appt['quantity']; ?> unit(s)</td>
                            <td><?php echo date('M j, Y', strtotime($appt['donation_date'])); ?></td>
                            <td><span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                            <td>
                                <a href="appointments.php?action=complete&id=<?php echo $appt['id']; ?>" class="btn btn-small btn-success" onclick="return confirm('Mark appointment as completed?');">Complete</a>
                                <a href="appointments.php?action=cancel&id=<?php echo $appt['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Cancel this appointment?');">Cancel</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($appointments)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No scheduled appointments found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:1rem;">
                <a href="dashboard.php" class="btn" style="background: var(--hospital-blue); color: white;">Back to Dashboard</a>
            </div>
        </div>
        <!-- No nearby/assign section: hospitals only view donor-created scheduled appointments for their facility -->
    </div>

    <script src="js/script.js"></script>
</body>
</html>