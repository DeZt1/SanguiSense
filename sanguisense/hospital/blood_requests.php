<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get blood requests for this hospital
global $pdo;
if ($facility) {
    $blood_requests = $pdo->prepare("
        SELECT br.*, 
               p.name as patient_name,
               p.medical_record_number,
               d.name as doctor_name
        FROM blood_requests br
        LEFT JOIN patients p ON br.patient_id = p.id
        LEFT JOIN doctors d ON br.doctor_id = d.id
        WHERE br.facility_id = ? 
        -- Order by urgency mapped to priority: critical > emergency > urgent > routine
        ORDER BY CASE br.urgency 
                 WHEN 'critical' THEN 4
                 WHEN 'emergency' THEN 3
                 WHEN 'urgent' THEN 2
                 ELSE 1
                 END DESC, br.created_at DESC
    ");
    $blood_requests->execute([$facility['id']]);
    $blood_requests = $blood_requests->fetchAll(PDO::FETCH_ASSOC);
} else {
    $blood_requests = [];
}

// Handle new blood request form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_request'])) {
    $patient_name = $_POST['patient_name'];
    $blood_type = $_POST['blood_type'];
    $quantity = $_POST['quantity'];
    $urgency = $_POST['urgency'];
    $purpose = $_POST['purpose'];
    $required_date = $_POST['required_date'];
    $doctor_name = $_POST['doctor_name'];
    $medical_record_number = $_POST['medical_record_number'];
    
    try {
        // First, check if patient exists or create new
        $patient_stmt = $pdo->prepare("INSERT INTO patients (name, medical_record_number, facility_id) VALUES (?, ?, ?)");
        $patient_stmt->execute([$patient_name, $medical_record_number, $facility['id']]);
        $patient_id = $pdo->lastInsertId();
        
        // Check if doctor exists or create new
        $doctor_stmt = $pdo->prepare("INSERT INTO doctors (name, facility_id) VALUES (?, ?)");
        $doctor_stmt->execute([$doctor_name, $facility['id']]);
        $doctor_id = $pdo->lastInsertId();
        
        // Create blood request
        $request_stmt = $pdo->prepare("
            INSERT INTO blood_requests 
            (facility_id, patient_id, doctor_id, blood_type, quantity, urgency, purpose, required_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $request_stmt->execute([$facility['id'], $patient_id, $doctor_id, $blood_type, $quantity, $urgency, $purpose, $required_date]);
        
        // Check inventory availability
        $inventory_check = $pdo->prepare("
            SELECT SUM(quantity) as available_quantity 
            FROM inventory 
            WHERE facility_id = ? AND blood_type = ? AND status = 'available' AND expiration_date > CURDATE()
        ");
        $inventory_check->execute([$facility['id'], $blood_type]);
        $available = $inventory_check->fetch(PDO::FETCH_ASSOC);
        
        if ($available['available_quantity'] >= $quantity) {
            $message = "Blood request created successfully! Sufficient inventory available.";
        } else {
            $message = "Blood request created successfully! <strong>Low inventory alert:</strong> Only " . ($available['available_quantity'] ?? 0) . " units available.";
        }
        
        $success = $message;
        header("Location: blood_requests.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to create blood request: " . $e->getMessage();
    }
}

// Handle request actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $action = $_GET['action'];
    
    try {
        if ($action == 'fulfill') {
            $update_stmt = $pdo->prepare("UPDATE blood_requests SET status = 'fulfilled', fulfilled_at = NOW() WHERE id = ?");
            $update_stmt->execute([$request_id]);
            $success = "Blood request marked as fulfilled!";
        } elseif ($action == 'cancel') {
            $update_stmt = $pdo->prepare("UPDATE blood_requests SET status = 'cancelled' WHERE id = ?");
            $update_stmt->execute([$request_id]);
            $success = "Blood request cancelled!";
        }
        header("Location: blood_requests.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to update request: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Requests - Hospital Portal</title>
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
                <a href="appointments.php" class="nav-link">Appointments</a>
                <a href="blood_requests.php" class="nav-link active">Blood Requests</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Blood Requests Management</h1>
            <p>Manage blood requests and emergency needs for patients</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Operation completed successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h3>Create New Blood Request</h3>
                <div class="admin-form">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="patient_name">Patient Name</label>
                                <input type="text" id="patient_name" name="patient_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="medical_record_number">Medical Record #</label>
                                <input type="text" id="medical_record_number" name="medical_record_number" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="blood_type">Blood Type Required</label>
                                <select id="blood_type" name="blood_type" required>
                                    <option value="">Select Blood Type</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">Quantity (Units)</label>
                                <input type="number" id="quantity" name="quantity" min="1" max="10" value="1" required>
                                <small>1 unit = 450ml of blood</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="urgency">Urgency Level</label>
                                <select id="urgency" name="urgency" required>
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="required_date">Required Date</label>
                                <input type="date" id="required_date" name="required_date" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor_name">Requesting Doctor</label>
                                <input type="text" id="doctor_name" name="doctor_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="purpose">Purpose</label>
                                <select id="purpose" name="purpose" required>
                                    <option value="surgery">Surgery</option>
                                    <option value="trauma">Trauma</option>
                                    <option value="chronic_anemia">Chronic Anemia</option>
                                    <option value="cancer_treatment">Cancer Treatment</option>
                                    <option value="childbirth">Childbirth</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="create_request" class="btn btn-primary" style="background: var(--hospital-blue);">Create Blood Request</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="content-card">
                <h3>Quick Stats</h3>
                <div class="stats-grid" style="grid-template-columns: 1fr; gap: 1rem;">
                    <?php
                    $pending_count = array_filter($blood_requests, function($req) { return $req['status'] == 'pending'; });
                    $urgent_count = array_filter($blood_requests, function($req) { return $req['urgency'] == 'urgent' || $req['urgency'] == 'emergency' || $req['urgency'] == 'critical'; });
                    $fulfilled_count = array_filter($blood_requests, function($req) { return $req['status'] == 'fulfilled'; });
                    ?>
                    
                    <div class="stat-card hospital-stat">
                        <h3>Pending Requests</h3>
                        <p class="stat-number"><?php echo count($pending_count); ?></p>
                    </div>
                    <div class="stat-card hospital-stat">
                        <h3>Urgent/Critical</h3>
                        <p class="stat-number"><?php echo count($urgent_count); ?></p>
                    </div>
                    <div class="stat-card hospital-stat">
                        <h3>Fulfilled</h3>
                        <p class="stat-number"><?php echo count($fulfilled_count); ?></p>
                    </div>
                </div>
                
                <div style="margin-top: 2rem;">
                    <h4>Emergency Contacts</h4>
                    <div style="background: rgba(255,255,255,0.1); padding: 1rem; border-radius: 8px;">
                        <p><strong>Blood Bank:</strong> (555) 123-4567</p>
                        <p><strong>Emergency Line:</strong> (555) 987-6543</p>
                        <p><strong>On-call Hematologist:</strong> Dr. Smith - (555) 456-7890</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3>All Blood Requests</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Patient</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Urgency</th>
                            <th>Purpose</th>
                            <th>Required Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blood_requests as $request): ?>
                        <tr>
                            <td>#BR<?php echo str_pad($request['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo htmlspecialchars($request['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['blood_type']); ?></td>
                            <td><?php echo $request['quantity']; ?> units</td>
                            <td>
                                <span class="status-badge urgency-<?php echo $request['urgency']; ?>">
                                    <?php echo ucfirst($request['urgency']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $request['purpose'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($request['required_date'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($request['status'] == 'pending'): ?>
                                    <button onclick="fulfillRequest(<?php echo $request['id']; ?>)" class="btn btn-small btn-success">Fulfill</button>
                                    <button onclick="cancelRequest(<?php echo $request['id']; ?>)" class="btn btn-small btn-danger">Cancel</button>
                                <?php else: ?>
                                    <span class="text-muted">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($blood_requests)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No blood requests found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-card">
            <h3>Urgent Requests</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Urgency</th>
                            <th>Time Remaining</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $urgent_requests = array_filter($blood_requests, function($req) { 
                            return ($req['urgency'] == 'urgent' || $req['urgency'] == 'emergency' || $req['urgency'] == 'critical') && $req['status'] == 'pending'; 
                        });
                        ?>
                        <?php foreach ($urgent_requests as $request): 
                            $time_remaining = strtotime($request['required_date']) - time();
                            $days_remaining = floor($time_remaining / (60 * 60 * 24));
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['patient_name']); ?></td>
                            <td><?php echo htmlspecialchars($request['blood_type']); ?></td>
                            <td><?php echo $request['quantity']; ?> units</td>
                            <td>
                                <span class="status-badge urgency-<?php echo $request['urgency']; ?>">
                                    <?php echo ucfirst($request['urgency']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="<?php echo $days_remaining < 2 ? 'expiring-soon' : ''; ?>">
                                    <?php echo $days_remaining > 0 ? $days_remaining . ' days' : 'OVERDUE'; ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="fulfillRequest(<?php echo $request['id']; ?>)" class="btn btn-small btn-success">Fulfill Now</button>
                                <button onclick="contactBloodBank(<?php echo $request['id']; ?>)" class="btn btn-small" style="background: var(--hospital-blue);">Contact Blood Bank</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($urgent_requests)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No urgent requests</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Set default required date to today
        document.getElementById('required_date').valueAsDate = new Date();
        
        // Auto-calculate priority based on urgency
        document.getElementById('urgency').addEventListener('change', function() {
            const urgency = this.value;
            let priority = 'medium';
            
            if (urgency === 'critical') priority = 'high';
            else if (urgency === 'emergency') priority = 'high';
            else if (urgency === 'urgent') priority = 'medium-high';
            else priority = 'medium';
            
            console.log('Priority set to:', priority);
        });

        function fulfillRequest(id) {
            if (confirm('Mark this blood request as fulfilled?')) {
                window.location.href = 'blood_requests.php?action=fulfill&id=' + id;
            }
        }
        
        function cancelRequest(id) {
            if (confirm('Cancel this blood request?')) {
                window.location.href = 'blood_requests.php?action=cancel&id=' + id;
            }
        }
        
        function contactBloodBank(id) {
            alert('Contacting blood bank for request #' + id + '\nThis would open a contact form or dial the emergency line.');
            // Implementation for contacting blood bank
        }
        
        // Auto-fill current date for required date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('required_date').min = today;
        });
    </script>
    
    <style>
        .urgency-routine { background: #28a745; color: white; }
        .urgency-urgent { background: #ffc107; color: black; }
        .urgency-emergency { background: #fd7e14; color: white; }
        .urgency-critical { background: #dc3545; color: white; animation: blink 2s infinite; }
        
        @keyframes blink {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</body>
</html>