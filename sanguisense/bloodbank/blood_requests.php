<?php
include '../includes/auth.php';
requireBloodBankAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']); // this is the blood bank facility

global $pdo;

// Fetch hospital blood requests (pending and recent)
$requests_stmt = $pdo->prepare("SELECT br.*, p.name as patient_name, p.medical_record_number, d.name as doctor_name, f.name as hospital_name, f.city as hospital_city, f.email as hospital_email, f.admin_id as hospital_admin_id FROM blood_requests br LEFT JOIN patients p ON br.patient_id = p.id LEFT JOIN doctors d ON br.doctor_id = d.id LEFT JOIN facilities f ON br.facility_id = f.id WHERE f.type = 'hospital' ORDER BY CASE br.urgency WHEN 'critical' THEN 4 WHEN 'emergency' THEN 3 WHEN 'urgent' THEN 2 ELSE 1 END DESC, br.created_at DESC");
$requests_stmt->execute();
$requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle actions from blood bank: fulfill or cancel
if (isset($_GET['action']) && isset($_GET['id'])) {
    $req_id = (int)$_GET['id'];
    $action = $_GET['action'];
    try {
        if ($action == 'fulfill') {
            $update = $pdo->prepare("UPDATE blood_requests SET status = 'fulfilled', fulfilled_at = NOW() WHERE id = ?");
            $update->execute([$req_id]);

            // Notify hospital admin if available
            $fac_stmt = $pdo->prepare("SELECT facility_id FROM blood_requests WHERE id = ?");
            $fac_stmt->execute([$req_id]);
            $row = $fac_stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $admin_stmt = $pdo->prepare("SELECT admin_id, name FROM facilities WHERE id = ?");
                $admin_stmt->execute([$row['facility_id']]);
                $fac = $admin_stmt->fetch(PDO::FETCH_ASSOC);
                if ($fac && !empty($fac['admin_id'])) {
                    addNotification($fac['admin_id'], 'Blood Request Fulfilled', "Your blood request #BR" . str_pad($req_id,4,'0',STR_PAD_LEFT) . " has been fulfilled by " . ($facility['name'] ?? 'the blood bank') . ".", 'info');
                }
            }

            $success = "Request marked fulfilled.";
        } elseif ($action == 'cancel') {
            $update = $pdo->prepare("UPDATE blood_requests SET status = 'cancelled' WHERE id = ?");
            $update->execute([$req_id]);
            $success = "Request cancelled.";
        }

        header('Location: blood_requests.php?success=1');
        exit();
    } catch (PDOException $e) {
        $error = "Failed to update request: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Blood Requests - Blood Bank Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><a href="dashboard.php" class="logo-link">
                    <span class="blood-drop">🩸</span>SanguiSense Blood Bank
                </a></h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="inventory.php" class="nav-link">Inventory</a>
                <a href="donations.php" class="nav-link">Donations</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="distribution.php" class="nav-link">Distribution</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Hospital Blood Requests</h1>
            <p>View and manage blood requests coming from hospitals</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Operation completed successfully!</div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h3>All Hospital Requests</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Hospital</th>
                            <th>Patient</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Urgency</th>
                            <th>Required Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>#BR<?php echo str_pad($r['id'],4,'0',STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($r['hospital_name'] . ' (' . $r['hospital_city'] . ')'); ?></td>
                            <td><?php echo htmlspecialchars($r['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($r['blood_type']); ?></td>
                            <td><?php echo (int)$r['quantity']; ?> units</td>
                            <td><span class="status-badge urgency-<?php echo $r['urgency']; ?>"><?php echo ucfirst($r['urgency']); ?></span></td>
                            <td><?php echo date('M j, Y', strtotime($r['required_date'])); ?></td>
                            <td><span class="status-badge status-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td>
                                <?php if ($r['status'] == 'pending'): ?>
                                    <a href="blood_requests.php?action=fulfill&id=<?php echo $r['id']; ?>" class="btn btn-small btn-success" onclick="return confirm('Mark this request as fulfilled?');">Fulfill</a>
                                    <a href="blood_requests.php?action=cancel&id=<?php echo $r['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Cancel this request?');">Cancel</a>
                                    <?php if (!empty($r['hospital_email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($r['hospital_email']); ?>?subject=Regarding%20Request%20BR<?php echo $r['id']; ?>" class="btn btn-small">Contact Hospital</a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted"><?php echo ucfirst($r['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No hospital requests found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
