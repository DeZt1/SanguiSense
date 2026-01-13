<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get scheduled donations (appointments) for this facility
global $pdo;
if ($facility) {
    $appointments_stmt = $pdo->prepare("SELECT d.*, u.id as donor_id, u.name as donor_name, u.email as donor_email, u.phone as donor_phone, u.city as donor_city, u.blood_type as donor_blood_type, u.eligibility_status FROM donations d LEFT JOIN users u ON d.donor_id = u.id WHERE d.facility_id = ? AND d.status = 'scheduled' ORDER BY d.donation_date ASC, d.created_at DESC");
    $appointments_stmt->execute([$facility['id']]);
    $appointments = $appointments_stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $appointments = [];
}

// Handle appointment actions (complete or cancel) via POST (safer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $appt_id = $_POST['id'];
    $action = $_POST['action'];
    try {
        // Get donation details before updating
        $donationStmt = $pdo->prepare("SELECT d.*, u.name as donor_name FROM donations d LEFT JOIN users u ON d.donor_id = u.id WHERE d.id = ?");
        $donationStmt->execute([$appt_id]);
        $donation = $donationStmt->fetch(PDO::FETCH_ASSOC);

        if ($action == 'complete') {
            $update = $pdo->prepare("UPDATE donations SET status = 'fulfilled' WHERE id = ?");
            $update->execute([$appt_id]);

            // Notify donor that donation was fulfilled
            if ($donation && $donation['donor_id']) {
                addNotification($donation['donor_id'], 'Donation Fulfilled', "Your blood donation (" . $donation['blood_type'] . ") on " . date('M j, Y', strtotime($donation['donation_date'])) . " has been fulfilled successfully. Thank you for your donation!", 'success');
            }

            $success = "Appointment marked as fulfilled.";
        } elseif ($action == 'cancel') {
            $update = $pdo->prepare("UPDATE donations SET status = 'cancelled' WHERE id = ?");
            $update->execute([$appt_id]);

            // Notify donor that donation was cancelled
            if ($donation && $donation['donor_id']) {
                addNotification($donation['donor_id'], 'Donation Appointment Cancelled', "Your blood donation appointment (" . $donation['blood_type'] . ") on " . date('M j, Y', strtotime($donation['donation_date'])) . " has been cancelled. Please reschedule or contact us for more information.", 'warning');
            }

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
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

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

            <div class="appointments-grid">
                <?php if (!empty($appointments)): ?>
                    <?php foreach ($appointments as $appt): ?>
                        <div class="appointment-card">
                            <div class="appointment-card-header">
                                <div class="appointment-id">#D<?php echo str_pad($appt['id'], 4, '0', STR_PAD_LEFT); ?></div>
                                <div class="appointment-status">
                                    <span class="status-badge status-<?php echo $appt['status']; ?>"><?php echo ucfirst($appt['status']); ?></span>
                                </div>
                            </div>

                            <div class="appointment-body">
                                <div class="appt-row">
                                    <div class="label">Donor</div>
                                    <div class="value">
                                        <strong><?php echo htmlspecialchars($appt['donor_name']); ?></strong>
                                        <div class="muted small"><?php echo htmlspecialchars($appt['donor_email']); ?></div>
                                    </div>
                                </div>

                                <div class="appt-row split">
                                    <div>
                                        <div class="label">Blood Type</div>
                                        <div class="value"><?php echo htmlspecialchars($appt['donor_blood_type']); ?></div>
                                    </div>
                                    <div>
                                        <div class="label">Phone</div>
                                        <div class="value"><?php echo htmlspecialchars($appt['donor_phone'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>

                                <div class="appt-row split">
                                    <div>
                                        <div class="label">City</div>
                                        <div class="value"><?php echo htmlspecialchars($appt['donor_city'] ?: 'N/A'); ?></div>
                                    </div>
                                    <div>
                                        <div class="label">Donation Date</div>
                                        <div class="value"><?php echo date('M j, Y', strtotime($appt['donation_date'])); ?></div>
                                    </div>
                                </div>

                                <div class="appt-row">
                                    <div class="label">Quantity</div>
                                    <div class="value"><?php echo (int)$appt['quantity']; ?> unit(s)</div>
                                </div>
                            </div>

                            <div class="appointment-card-actions">
                                <a href="contact_donor.php?id=<?php echo $appt['donor_id']; ?>" class="btn btn-small btn-secondary" title="Contact Donor">Contact</a>
                                <form method="POST" action="appointments.php" style="display:inline; margin:0;">
                                    <input type="hidden" name="id" value="<?php echo $appt['id']; ?>">
                                    <input type="hidden" name="action" value="complete">
                                    <button type="submit" class="btn btn-small btn-success" onclick="return confirm('Mark appointment as fulfilled?');">Fulfill</button>
                                </form>
                                <form method="POST" action="appointments.php" style="display:inline; margin:0;">
                                    <input type="hidden" name="id" value="<?php echo $appt['id']; ?>">
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Cancel this appointment?');">Cancel</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-appointments">No scheduled appointments found</div>
                <?php endif; ?>
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