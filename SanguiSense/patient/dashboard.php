<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';

// Check if user is logged in and is a patient
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user data
$query = "SELECT * FROM users WHERE id = ? AND user_type = 'patient'";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php');
    exit;
}

// Fetch patient profile if exists
$profileQuery = "SELECT * FROM patient_profiles WHERE patient_id = ?";
$profileStmt = $pdo->prepare($profileQuery);
$profileStmt->execute([$_SESSION['user_id']]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent blood requests
$requestsQuery = "SELECT * FROM patient_blood_requests WHERE patient_id = ? ORDER BY created_at DESC LIMIT 5";
$requestsStmt = $pdo->prepare($requestsQuery);
$requestsStmt->execute([$_SESSION['user_id']]);
$recentRequests = $requestsStmt->fetchAll(PDO::FETCH_ASSOC);

// Count pending requests
$countQuery = "SELECT COUNT(*) as count FROM patient_blood_requests WHERE patient_id = ? AND status = 'pending'";
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute([$_SESSION['user_id']]);
$pendingCount = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - SanguiSense</title>
    <link rel="stylesheet" href="css/patient.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_patient.php'; ?>

    <!-- Main Content -->
    <div class="patient-dashboard">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?></h1>
            <p>Patient Portal - Blood Request Management System</p>
        </div>

        <div class="dashboard-grid">
            <!-- Quick Actions -->
            <div class="dashboard-card">
                <h3>Quick Actions</h3>
                <div class="action-buttons-vertical">
                    <a href="find_donors.php" class="btn btn-primary">Find Available Donors</a>
                    <a href="send_request.php" class="btn btn-primary">Send Blood Request</a>
                    <a href="request_history.php" class="btn btn-secondary">View My Requests</a>
                </div>
            </div>

            <!-- Patient Status -->
            <div class="dashboard-card">
                <h3>Your Health Profile</h3>
                <div style="color: #e0e0e0; line-height: 1.8;">
                    <p><strong>Blood Type:</strong> <?php echo htmlspecialchars($user['blood_type'] ?? 'Not specified'); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?? 'Not specified'); ?></p>
                    <p><strong>City:</strong> <?php echo htmlspecialchars($user['city'] ?? 'Not specified'); ?></p>
                    <?php if ($profile): ?>
                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($profile['gender'] ?? 'Not specified'); ?></p>
                    <?php endif; ?>
                    <a href="profile.php" class="btn btn-secondary btn-small" style="margin-top: 1rem; display: inline-block;">Update Profile</a>
                </div>
            </div>

            <!-- Request Statistics -->
            <div class="dashboard-card">
                <h3>Request Status</h3>
                <div style="color: #e0e0e0; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--patient-teal); font-weight: bold; margin-bottom: 0.5rem;">
                        <?php echo $pendingCount; ?>
                    </div>
                    <p>Pending Requests</p>
                    <p style="font-size: 0.9rem; margin-top: 1rem;">Total Requests: <?php echo count($recentRequests); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Requests -->
        <div style="background: linear-gradient(135deg, rgba(0, 188, 212, 0.1), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2); margin-top: 2rem;">
            <h2 style="color: var(--patient-teal); margin-bottom: 1.5rem; font-size: 1.6rem;">Recent Blood Requests</h2>
            
            <?php if (!empty($recentRequests)): ?>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid rgba(0, 188, 212, 0.3);">
                                <th style="padding: 1rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Blood Type</th>
                                <th style="padding: 1rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Quantity</th>
                                <th style="padding: 1rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Required Date</th>
                                <th style="padding: 1rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Status</th>
                                <th style="padding: 1rem; text-align: left; color: var(--patient-teal); font-weight: 600;">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $request): ?>
                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); color: #e0e0e0;">
                                    <td style="padding: 1rem;"><span class="blood-type-badge" style="background: linear-gradient(135deg, #c8102e, #8b0000); color: white;"><?php echo htmlspecialchars($request['blood_type']); ?></span></td>
                                    <td style="padding: 1rem;"><?php echo $request['quantity_units']; ?> unit(s)</td>
                                    <td style="padding: 1rem;"><?php echo date('M j, Y', strtotime($request['required_date'])); ?></td>
                                    <td style="padding: 1rem;"><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                    <td style="padding: 1rem;"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: center; margin-top: 1.5rem;">
                    <a href="request_history.php" class="btn btn-primary">View All Requests</a>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #e0e0e0; padding: 2rem;">No blood requests yet. <a href="send_request.php" style="color: var(--patient-teal); text-decoration: none; font-weight: 600;">Create one now</a></p>
            <?php endif; ?>
        </div>

        <!-- Information Section -->
        <div style="background: linear-gradient(135deg, rgba(0, 188, 212, 0.1), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2); margin-top: 2rem;">
            <h2 style="color: var(--patient-teal); margin-bottom: 1.5rem; font-size: 1.6rem;">How It Works</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 10px; border-left: 4px solid var(--patient-teal);">
                    <h4 style="color: var(--patient-teal); margin-bottom: 0.8rem;">1. Find Donors</h4>
                    <p style="color: #e0e0e0; line-height: 1.6;">Browse available donors on our interactive map. Filter by blood type and location to find the best match for your needs.</p>
                </div>
                <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 10px; border-left: 4px solid var(--patient-teal);">
                    <h4 style="color: var(--patient-teal); margin-bottom: 0.8rem;">2. Send Request</h4>
                    <p style="color: #e0e0e0; line-height: 1.6;">Submit a formal blood request to hospitals or blood banks with specific details about your medical needs and urgency.</p>
                </div>
                <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 10px; border-left: 4px solid var(--patient-teal);">
                    <h4 style="color: var(--patient-teal); margin-bottom: 0.8rem;">3. Track Status</h4>
                    <p style="color: #e0e0e0; line-height: 1.6;">Monitor your requests in real-time and receive updates as your blood request is processed and fulfilled.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="js/patient.js"></script>
</body>
</html>
