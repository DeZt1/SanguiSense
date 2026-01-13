<?php
include '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verify user is a patient
$query = "SELECT * FROM users WHERE id = ? AND user_type = 'patient'";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php');
    exit;
}

// Fetch all requests with facility information
$requestsQuery = "SELECT 
    pr.*,
    COALESCE(h.name, bb.name) as facility_name,
    COALESCE(h.type, bb.type) as facility_type
    FROM patient_blood_requests pr
    LEFT JOIN facilities h ON pr.hospital_id = h.id
    LEFT JOIN facilities bb ON pr.bloodbank_id = bb.id
    WHERE pr.patient_id = ?
    ORDER BY pr.created_at DESC";

$requestsStmt = $pdo->prepare($requestsQuery);
$requestsStmt->execute([$_SESSION['user_id']]);
$requests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$totalRequests = count($requests);
$pendingRequests = count(array_filter($requests, function($r) { return $r['status'] == 'pending'; }));
$approvedRequests = count(array_filter($requests, function($r) { return $r['status'] == 'approved'; }));
$fulfilledRequests = count(array_filter($requests, function($r) { return $r['status'] == 'fulfilled'; }));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request History - SanguiSense Patient Portal</title>
    <link rel="stylesheet" href="css/patient.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_patient.php'; ?>

    <!-- Main Content -->
    <div class="patient-dashboard">
        <div class="dashboard-header">
            <h1>Your Blood Request History</h1>
            <p>Track and manage all your blood requests</p>
        </div>

        <!-- Statistics -->
        <div class="dashboard-grid">
            <div class="dashboard-card" style="text-align: center;">
                <h3 style="text-align: left;">Total Requests</h3>
                <div style="font-size: 3rem; color: var(--patient-teal); font-weight: bold; margin: 1rem 0;">
                    <?php echo $totalRequests; ?>
                </div>
            </div>
            
            <div class="dashboard-card" style="text-align: center;">
                <h3 style="text-align: left;">Pending</h3>
                <div style="font-size: 3rem; color: #ffc107; font-weight: bold; margin: 1rem 0;">
                    <?php echo $pendingRequests; ?>
                </div>
            </div>
            
            <div class="dashboard-card" style="text-align: center;">
                <h3 style="text-align: left;">Approved</h3>
                <div style="font-size: 3rem; color: #17a2b8; font-weight: bold; margin: 1rem 0;">
                    <?php echo $approvedRequests; ?>
                </div>
            </div>
            
            <div class="dashboard-card" style="text-align: center;">
                <h3 style="text-align: left;">Fulfilled</h3>
                <div style="font-size: 3rem; color: #28a745; font-weight: bold; margin: 1rem 0;">
                    <?php echo $fulfilledRequests; ?>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div style="background: linear-gradient(135deg, rgba(0, 188, 212, 0.1), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2); margin-top: 2rem;">
            <h2 style="color: var(--patient-teal); margin-bottom: 1.5rem; font-size: 1.6rem;">All Requests</h2>
            
            <?php if (!empty($requests)): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(0, 188, 212, 0.3);">
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">ID</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Blood Type</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Facility</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Quantity</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Required Date</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Urgency</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Status</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Created</th>
                                <th style="padding: 1.2rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); color: #e0e0e0;">
                                    <td style="padding: 1rem;">#<?php echo $request['id']; ?></td>
                                    <td style="padding: 1rem;">
                                        <span class="blood-type-badge" style="background: linear-gradient(135deg, #c8102e, #8b0000); color: white;">
                                            <?php echo htmlspecialchars($request['blood_type']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem;"><?php echo htmlspecialchars($request['facility_name'] ?? 'N/A'); ?></td>
                                    <td style="padding: 1rem;"><?php echo $request['quantity_units']; ?> unit(s)</td>
                                    <td style="padding: 1rem;"><?php echo date('M j, Y', strtotime($request['required_date'])); ?></td>
                                    <td style="padding: 1rem;">
                                        <span style="padding: 0.3rem 0.8rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600;
                                            <?php 
                                            $urgencyColor = [
                                                'routine' => 'background: #e3f2fd; color: #0066b2;',
                                                'urgent' => 'background: #fff3cd; color: #856404;',
                                                'emergency' => 'background: #ffe6e6; color: #dc3545;',
                                                'critical' => 'background: #ffcccc; color: #8b0000;'
                                            ];
                                            echo $urgencyColor[$request['urgency']] ?? '';
                                            ?>">
                                            <?php echo ucfirst($request['urgency']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem;">
                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td style="padding: 1rem;"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                    <td style="padding: 1rem;">
                                        <button class="btn btn-secondary btn-small" onclick="viewRequestDetails(<?php echo $request['id']; ?>)">
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem; color: #e0e0e0;">
                    <p style="font-size: 1.1rem; margin-bottom: 1.5rem;">You haven't submitted any blood requests yet.</p>
                    <a href="send_request.php" class="btn btn-primary">Create Your First Request</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Legend -->
        <div style="background: linear-gradient(135deg, rgba(0, 188, 212, 0.1), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2); margin-top: 2rem;">
            <h3 style="color: var(--patient-teal); margin-bottom: 1.5rem;">Status Guide</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div>
                    <span class="status-badge status-pending"></span>
                    <p style="color: #e0e0e0; margin-top: 0.5rem;">Request submitted, awaiting facility review</p>
                </div>
                <div>
                    <span class="status-badge status-approved"></span>
                    <p style="color: #e0e0e0; margin-top: 0.5rem;">Request approved by facility</p>
                </div>
                <div>
                    <span class="status-badge status-fulfilled"></span>
                    <p style="color: #e0e0e0; margin-top: 0.5rem;">Blood delivered successfully</p>
                </div>
                <div>
                    <span class="status-badge status-cancelled"></span>
                    <p style="color: #e0e0e0; margin-top: 0.5rem;">Request cancelled</p>
                </div>
                <div>
                    <span class="status-badge status-rejected"></span>
                    <p style="color: #e0e0e0; margin-top: 0.5rem;">Request rejected by facility</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div id="requestDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="modalRequestContent">
                <!-- Content loaded by JavaScript -->
            </div>
        </div>
    </div>

    <script src="js/patient.js"></script>
</body>
</html>
