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
    $patient_name = $_POST['patient_name'] ?? '';
    $blood_type = $_POST['blood_type'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $urgency = $_POST['urgency'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $required_date = $_POST['required_date'] ?? '';
    $doctor_name = $_POST['doctor_name'] ?? '';
    $medical_record_number = $_POST['medical_record_number'] ?? '';
    
    // ===== SERVER-SIDE VALIDATION =====
    $validation_errors = [];
    
    // Validate patient name
    if (empty($patient_name)) {
        $validation_errors[] = "Patient name is required.";
    } elseif (strlen($patient_name) < 2) {
        $validation_errors[] = "Patient name must be at least 2 characters.";
    } elseif (strlen($patient_name) > 100) {
        $validation_errors[] = "Patient name must not exceed 100 characters.";
    }
    
    // Validate medical record number
    if (empty($medical_record_number)) {
        $validation_errors[] = "Medical record number is required.";
    }
    
    // Validate blood type
    if (empty($blood_type)) {
        $validation_errors[] = "Blood type must be selected.";
    } else {
        $valid_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (!in_array($blood_type, $valid_types)) {
            $validation_errors[] = "Invalid blood type selected.";
        }
    }
    
    // Validate quantity
    if (empty($quantity)) {
        $validation_errors[] = "Quantity is required.";
    } elseif (!is_numeric($quantity) || $quantity < 1 || $quantity > 10) {
        $validation_errors[] = "Quantity must be between 1 and 10 units.";
    }
    
    // Validate urgency
    if (empty($urgency)) {
        $validation_errors[] = "Urgency level must be selected.";
    } else {
        $valid_urgencies = ['routine', 'urgent', 'emergency', 'critical'];
        if (!in_array($urgency, $valid_urgencies)) {
            $validation_errors[] = "Invalid urgency level selected.";
        }
    }
    
    // Validate required date
    if (empty($required_date)) {
        $validation_errors[] = "Required date is required.";
    } else {
        $req_timestamp = strtotime($required_date);
        $today_timestamp = strtotime(date('Y-m-d'));
        if ($req_timestamp < $today_timestamp) {
            $validation_errors[] = "Required date must be today or in the future.";
        }
    }
    
    // Validate doctor name
    if (empty($doctor_name)) {
        $validation_errors[] = "Doctor name is required.";
    } elseif (strlen($doctor_name) < 2) {
        $validation_errors[] = "Doctor name must be at least 2 characters.";
    } elseif (strlen($doctor_name) > 100) {
        $validation_errors[] = "Doctor name must not exceed 100 characters.";
    }
    
    // Validate purpose
    if (empty($purpose)) {
        $validation_errors[] = "Purpose must be selected.";
    } else {
        $valid_purposes = ['surgery', 'trauma', 'chronic_anemia', 'cancer_treatment', 'childbirth', 'other'];
        if (!in_array($purpose, $valid_purposes)) {
            $validation_errors[] = "Invalid purpose selected.";
        }
    }
    
    if (empty($validation_errors)) {
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
    } else {
        $error = "Please fix the following errors:\n• " . implode("\n• ", $validation_errors);
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
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Blood Requests Management</h1>
            <p>Manage blood requests and emergency needs for patients</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Operation completed successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php 
                if (strpos($error, 'Please fix') === 0) {
                    echo nl2br(htmlspecialchars($error));
                } else {
                    echo htmlspecialchars($error);
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h3>Create New Blood Request</h3>
                <div class="admin-form">
                    <form method="POST" id="bloodRequestForm" onsubmit="return validateBloodRequestForm()">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="patient_name">Patient Name <span class="required">*</span></label>
                                <input type="text" id="patient_name" name="patient_name" required minlength="2" maxlength="100">
                                <small class="form-error" id="patient_error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="medical_record_number">Medical Record # <span class="required">*</span></label>
                                <input type="text" id="medical_record_number" name="medical_record_number" required>
                                <small class="form-error" id="mrn_error"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="blood_type">Blood Type Required <span class="required">*</span></label>
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
                                <small class="form-error" id="blood_error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">Quantity (Units) <span class="required">*</span></label>
                                <input type="number" id="quantity" name="quantity" min="1" max="10" value="1" required>
                                <small>1 unit = 450ml of blood</small>
                                <small class="form-error" id="qty_error"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="urgency">Urgency Level <span class="required">*</span></label>
                                <select id="urgency" name="urgency" required>
                                    <option value="">Select Urgency</option>
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="critical">Critical</option>
                                </select>
                                <small class="form-error" id="urgency_error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="required_date">Required Date <span class="required">*</span></label>
                                <input type="date" id="required_date" name="required_date" required>
                                <small class="form-error" id="date_error"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor_name">Requesting Doctor <span class="required">*</span></label>
                                <input type="text" id="doctor_name" name="doctor_name" required minlength="2" maxlength="100">
                                <small class="form-error" id="doctor_error"></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="purpose">Purpose <span class="required">*</span></label>
                                <select id="purpose" name="purpose" required>
                                    <option value="">Select Purpose</option>
                                    <option value="surgery">Surgery</option>
                                    <option value="trauma">Trauma</option>
                                    <option value="chronic_anemia">Chronic Anemia</option>
                                    <option value="cancer_treatment">Cancer Treatment</option>
                                    <option value="childbirth">Childbirth</option>
                                    <option value="other">Other</option>
                                </select>
                                <small class="form-error" id="purpose_error"></small>
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
    window.location.href = 'contact_bloodbank.php?request_id=' + id;
}
        
        // Auto-fill current date for required date
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('required_date').min = today;
        });
        
        function validateBloodRequestForm() {
            // Clear previous error messages
            document.getElementById('patient_error').textContent = '';
            document.getElementById('mrn_error').textContent = '';
            document.getElementById('blood_error').textContent = '';
            document.getElementById('qty_error').textContent = '';
            document.getElementById('urgency_error').textContent = '';
            document.getElementById('date_error').textContent = '';
            document.getElementById('doctor_error').textContent = '';
            document.getElementById('purpose_error').textContent = '';
            
            const patientName = document.getElementById('patient_name').value.trim();
            const mrn = document.getElementById('medical_record_number').value.trim();
            const bloodType = document.getElementById('blood_type').value;
            const quantity = document.getElementById('quantity').value;
            const urgency = document.getElementById('urgency').value;
            const requiredDate = document.getElementById('required_date').value;
            const doctorName = document.getElementById('doctor_name').value.trim();
            const purpose = document.getElementById('purpose').value;
            
            let isValid = true;
            
            // Validate patient name
            if (!patientName) {
                document.getElementById('patient_error').textContent = 'Patient name is required.';
                isValid = false;
            } else if (patientName.length < 2) {
                document.getElementById('patient_error').textContent = 'Patient name must be at least 2 characters.';
                isValid = false;
            }
            
            // Validate MRN
            if (!mrn) {
                document.getElementById('mrn_error').textContent = 'Medical record number is required.';
                isValid = false;
            }
            
            // Validate blood type
            if (!bloodType) {
                document.getElementById('blood_error').textContent = 'Blood type must be selected.';
                isValid = false;
            }
            
            // Validate quantity
            if (!quantity || quantity < 1 || quantity > 10) {
                document.getElementById('qty_error').textContent = 'Quantity must be between 1 and 10 units.';
                isValid = false;
            }
            
            // Validate urgency
            if (!urgency) {
                document.getElementById('urgency_error').textContent = 'Urgency level must be selected.';
                isValid = false;
            }
            
            // Validate required date
            if (!requiredDate) {
                document.getElementById('date_error').textContent = 'Required date is required.';
                isValid = false;
            } else {
                const selectedDate = new Date(requiredDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    document.getElementById('date_error').textContent = 'Required date must be today or in the future.';
                    isValid = false;
                }
            }
            
            // Validate doctor name
            if (!doctorName) {
                document.getElementById('doctor_error').textContent = 'Doctor name is required.';
                isValid = false;
            } else if (doctorName.length < 2) {
                document.getElementById('doctor_error').textContent = 'Doctor name must be at least 2 characters.';
                isValid = false;
            }
            
            // Validate purpose
            if (!purpose) {
                document.getElementById('purpose_error').textContent = 'Purpose must be selected.';
                isValid = false;
            }
            
            return isValid;
        }
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