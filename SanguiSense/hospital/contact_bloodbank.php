<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get blood request details if provided
$request_id = $_GET['request_id'] ?? null;
$blood_request = null;

if ($request_id) {
    global $pdo;
    $request_stmt = $pdo->prepare("
        SELECT br.*, p.name as patient_name, p.medical_record_number
        FROM blood_requests br
        LEFT JOIN patients p ON br.patient_id = p.id
        WHERE br.id = ? AND br.facility_id = ?
    ");
    $request_stmt->execute([$request_id, $facility['id']]);
    $blood_request = $request_stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_request'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $urgency = $_POST['urgency'];
    $blood_type = $_POST['blood_type'];
    $quantity = $_POST['quantity'];
    $required_date = $_POST['required_date'];
    
    if (!$subject || !$message || !$blood_type || !$quantity) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            // Store the blood bank request in database
            $insert_stmt = $pdo->prepare("
                INSERT INTO bloodbank_requests 
                (hospital_id, hospital_name, request_id, subject, message, blood_type, quantity, urgency, required_date, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $insert_stmt->execute([
                $facility['id'],
                $facility['name'],
                $request_id,
                $subject,
                $message,
                $blood_type,
                $quantity,
                $urgency,
                $required_date
            ]);
            
            $request_number = $pdo->lastInsertId();
            
            // In a real system, you would:
            // 1. Send email to blood bank
            // 2. Send SMS alert for urgent requests
            // 3. Integrate with blood bank API
            
            $success = "Blood bank request sent successfully! Request #BBR" . str_pad($request_number, 4, '0', STR_PAD_LEFT);
            
        } catch(PDOException $e) {
            $error = "Failed to send blood bank request: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Blood Bank - Hospital Portal</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .action-buttons-vertical {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .action-buttons-vertical .btn {
            text-align: left;
            padding: 0.8rem;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        
        @media (max-width: 768px) {
            .action-buttons-vertical {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Contact Blood Bank</h1>
            <p>Request emergency blood supply from regional blood banks</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <!-- Hospital Information -->
            <div class="content-card">
                <h3>🏥 Hospital Information</h3>
                <div class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Hospital Name</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px; font-weight: bold;">
                                <?php echo htmlspecialchars($facility['name']); ?>
                            </p>
                        </div>
                        <div class="form-group">
                            <label>Contact Person</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Hospital Phone</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;">
                                <?php echo htmlspecialchars($facility['phone'] ?: '(555) 123-4567'); ?>
                            </p>
                        </div>
                        <div class="form-group">
                            <label>Hospital Email</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Blood Bank Contacts -->
                <div style="margin-top: 2rem;">
                    <h4>📞 Regional Blood Banks</h4>
                    <div style="background: rgba(255,255,255,0.1); padding: 1rem; border-radius: 8px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <strong>Central Blood Bank</strong>
                                <p style="margin: 0.5rem 0; font-size: 0.9rem;">
                                    📞 (555) 987-6543<br>
                                    📧 emergency@centralbb.org<br>
                                    🕒 24/7 Emergency Line
                                </p>
                            </div>
                            <div>
                                <strong>Regional Blood Center</strong>
                                <p style="margin: 0.5rem 0; font-size: 0.9rem;">
                                    📞 (555) 456-7890<br>
                                    📧 urgent@regionalbc.org<br>
                                    🕒 6AM - 10PM
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Information -->
            <div class="content-card">
                <h3>🩸 Request Details</h3>
                
                <?php if ($blood_request): ?>
                <div style="background: rgba(255,193,7,0.1); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #ffc107;">
                    <h4>Linked to Blood Request #BR<?php echo str_pad($blood_request['id'], 4, '0', STR_PAD_LEFT); ?></h4>
                    <p style="margin: 0.5rem 0;">
                        <strong>Patient:</strong> <?php echo htmlspecialchars($blood_request['patient_name']); ?><br>
                        <strong>Current Urgency:</strong> 
                        <span class="status-badge urgency-<?php echo $blood_request['urgency']; ?>">
                            <?php echo ucfirst($blood_request['urgency']); ?>
                        </span>
                    </p>
                </div>
                <?php endif; ?>

                <div style="margin-bottom: 1.5rem;">
    <h4>🚀 Quick Templates</h4>
    <div class="action-buttons-vertical">
        <button type="button" onclick="loadTemplate('emergency')" class="btn btn-secondary">
            🚨 Emergency Request
        </button>
        <button type="button" onclick="loadTemplate('scheduled')" class="btn btn-secondary">
            📅 Scheduled Surgery
        </button>
        <button type="button" onclick="loadTemplate('rare')" class="btn btn-secondary">
            🔍 Rare Blood Type
        </button>
        <button type="button" onclick="loadTemplate('bulk')" class="btn btn-secondary">
            📦 Bulk Supply
        </button>
        <button type="button" onclick="loadTemplate('trauma')" class="btn btn-secondary">
            🏥 Trauma Case
        </button>
        <button type="button" onclick="loadTemplate('pediatric')" class="btn btn-secondary">
            👶 Pediatric Need
        </button>
    </div>
</div>
                
                <!-- Request Status -->
                <div style="margin-top: 2rem;">
                    <h4>📊 Recent Blood Bank Requests</h4>
                    <?php
                    $recent_requests_stmt = $pdo->prepare("
                        SELECT * FROM bloodbank_requests 
                        WHERE hospital_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    $recent_requests_stmt->execute([$facility['id']]);
                    $recent_requests = $recent_requests_stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($recent_requests)): ?>
                        <p style="color: #666; text-align: center;">No recent blood bank requests</p>
                    <?php else: ?>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <?php foreach ($recent_requests as $req): ?>
                                <div style="border-left: 3px solid var(--hospital-blue); padding: 0.5rem 1rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">
                                    <strong>#BBR<?php echo str_pad($req['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                    - <?php echo htmlspecialchars($req['blood_type']); ?> 
                                    (<?php echo $req['quantity']; ?> units)
                                    <br>
                                    <small>
                                        Status: 
                                        <span class="status-badge status-<?php echo $req['status']; ?>">
                                            <?php echo ucfirst($req['status']); ?>
                                        </span>
                                        - <?php echo date('M j, Y', strtotime($req['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Blood Request Form -->
        <div class="content-card">
            <h3>✍️ Blood Bank Request Form</h3>
            <div class="admin-form">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="blood_type">Blood Type Needed</label>
                            <select id="blood_type" name="blood_type" required>
                                <option value="">Select Blood Type</option>
                                <option value="A+" <?php echo $blood_request && $blood_request['blood_type'] == 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo $blood_request && $blood_request['blood_type'] == 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo $blood_request && $blood_request['blood_type'] == 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo $blood_request && $blood_request['blood_type'] == 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo $blood_request && $blood_request['blood_type'] == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo $blood_request && $blood_request['blood_type'] == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo $blood_request && $blood_request['blood_type'] == 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo $blood_request && $blood_request['blood_type'] == 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity Needed (Units)</label>
                            <input type="number" id="quantity" name="quantity" min="1" max="50" 
                                   value="<?php echo $blood_request ? $blood_request['quantity'] : '1'; ?>" required>
                            <small>1 unit = 450ml of blood. Maximum 50 units per request.</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="urgency">Urgency Level</label>
                            <select id="urgency" name="urgency" required>
                                <option value="routine">Routine</option>
                                <option value="urgent" <?php echo $blood_request && $blood_request['urgency'] == 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                <option value="emergency" <?php echo $blood_request && $blood_request['urgency'] == 'emergency' ? 'selected' : ''; ?>>Emergency</option>
                                <option value="critical" <?php echo $blood_request && $blood_request['urgency'] == 'critical' ? 'selected' : ''; ?>>Critical (Life-threatening)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="required_date">Required By Date</label>
                            <input type="datetime-local" id="required_date" name="required_date" 
                                   value="<?php echo $blood_request ? date('Y-m-d\TH:i', strtotime($blood_request['required_date'])) : date('Y-m-d\TH:i', strtotime('+2 hours')); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Request Subject</label>
                        <input type="text" id="subject" name="subject" placeholder="Enter request subject" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Detailed Request Message</label>
                        <textarea id="message" name="message" rows="6" placeholder="Provide details about the patient condition, surgery schedule, or emergency situation..." required></textarea>
                        <small>Include patient details, procedure information, and any special requirements.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="send_request" class="btn btn-primary" style="background: var(--hospital-blue);">
                            🚀 Send to Blood Bank
                        </button>
                        <a href="blood_requests.php" class="btn btn-secondary"style="background:#ffffff;color:var(--dark-red);border:2px solid var(--dark-red);box-shadow:0 4px 10px rgba(0,0,0,0.1);">Back to Blood Requests</a>
                        <button type="button" onclick="makeEmergencyCall()" class="btn btn-danger"style="background:#ffffff;color:var(--dark-red);border:2px solid var(--dark-red);box-shadow:0 4px 10px rgba(0,0,0,0.1);">
                            📞 Emergency Call
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Template messages for different scenarios
        const templates = {
    emergency: {
        subject: "🚨 EMERGENCY BLOOD REQUEST - CRITICAL PATIENT",
        message: `URGENT BLOOD REQUIREMENT - CRITICAL SITUATION

Hospital: ${"<?php echo htmlspecialchars($facility['name']); ?>"}
Contact: ${"<?php echo htmlspecialchars($user['name']); ?>"} - ${"<?php echo htmlspecialchars($user['email']); ?>"}
Emergency Phone: ${"<?php echo htmlspecialchars($facility['phone'] ?: '(555) 123-4567'); ?>"}

CRITICAL EMERGENCY:
We have a critical patient requiring immediate blood transfusion. The situation is life-threatening and requires your urgent assistance.

PATIENT DETAILS:
- Condition: Critical emergency - immediate transfusion required
- Blood Type Needed: [AUTO-FILL]
- Quantity Required: [AUTO-FILL] units
- Required Within: 1-2 hours MAXIMUM
- Patient Status: Unstable, vital signs critical

URGENT ACTION REQUIRED:
Please confirm availability and estimated delivery time immediately. This is a life-or-death situation.

We await your immediate response.`
    },
    scheduled: {
        subject: "🩺 Scheduled Surgery Blood Requirement",
        message: `SCHEDULED SURGERY BLOOD REQUEST

Hospital: ${"<?php echo htmlspecialchars($facility['name']); ?>"}
Contact: ${"<?php echo htmlspecialchars($user['name']); ?>"} - ${"<?php echo htmlspecialchars($user['email']); ?>"}

SCHEDULED PROCEDURE:
We have a scheduled major surgery requiring blood support. Please arrange for the following:

PROCEDURE DETAILS:
- Surgery Type: Major elective surgery
- Procedure Date: [AUTO-FILL]
- Blood Type Needed: [AUTO-FILL]
- Quantity Required: [AUTO-FILL] units
- Cross-match Required: Yes
- Patient Status: Stable, scheduled procedure

DELIVERY INSTRUCTIONS:
Please deliver to: Hospital Blood Bank, Main Building
Contact on arrival: (555) 123-4567

Please confirm availability and delivery arrangements at your earliest convenience.`
    },
    rare: {
        subject: "🔍 Rare Blood Type Request - Special Need",
        message: `RARE BLOOD TYPE REQUEST

Hospital: ${"<?php echo htmlspecialchars($facility['name']); ?>"}
Contact: ${"<?php echo htmlspecialchars($user['name']); ?>"} - ${"<?php echo htmlspecialchars($user['email']); ?>"}

SPECIAL REQUIREMENT:
We require rare blood type for a patient with special needs. This is a challenging requirement that we hope you can assist with.

PATIENT INFORMATION:
- Blood Type Needed: [AUTO-FILL] (Rare type)
- Quantity Required: [AUTO-FILL] units
- Required By: [AUTO-FILL]
- Patient Condition: Stable but requires scheduled transfusion
- Special Requirements: May need additional units on standby

ADDITIONAL NOTES:
Patient has antibodies requiring special cross-matching. Please advise if you need additional patient samples for testing.

We understand this may require special coordination and appreciate your assistance.`
    },
    bulk: {
        subject: "📦 Bulk Blood Supply Request - Inventory Restock",
        message: `BULK BLOOD SUPPLY REQUEST

Hospital: ${"<?php echo htmlspecialchars($facility['name']); ?>"}
Contact: ${"<?php echo htmlspecialchars($user['name']); ?>"} - ${"<?php echo htmlspecialchars($user['email']); ?>"}

INVENTORY RESTOCKING:
We are requesting bulk blood supply to restock our hospital inventory and prepare for expected high demand.

REQUEST DETAILS:
- Blood Type Needed: [AUTO-FILL]
- Quantity Required: [AUTO-FILL] units
- Required By: [AUTO-FILL]
- Purpose: Hospital inventory restocking and emergency preparedness
- Delivery Preference: Scheduled delivery over next 2-3 days

INVENTORY STATUS:
Our current stock of this blood type is running low. This order will help maintain our 7-day buffer supply.

Please advise delivery schedule, pricing for bulk order, and any special considerations.`
    },
    trauma: {
        subject: "🏥 Trauma Case - Multiple Transfusion Required",
        message: `TRAUMA CASE - MULTIPLE TRANSFUSION REQUIREMENT

Hospital: ${"<?php echo htmlspecialchars($facility['name']); ?>"}
Contact: ${"<?php echo htmlspecialchars($user['name']); ?>"} - ${"<?php echo htmlspecialchars($user['email']); ?>"}

TRAUMA EMERGENCY:
We are treating a major trauma case requiring multiple blood products and ongoing transfusion support.

TRAUMA DETAILS:
- Patient: Trauma victim, multiple injuries
- Blood Type Needed: [AUTO-FILL]
- Initial Quantity: [AUTO-FILL] units
- Additional Needs: May require more units throughout treatment
- Required: Immediately and on standby
- Situation: Critical but stable with ongoing resuscitation

SPECIAL CONSIDERATIONS:
Please keep additional units available as this case may require ongoing blood support throughout the night.

We appreciate your immediate attention to this trauma case.`
    },
    pediatric: {
        subject: "👶 Pediatric Blood Request - Special Handling",
        message: `PEDIATRIC BLOOD REQUEST - SPECIAL HANDLING REQUIRED

Hospital: ${"<?php echo htmlspecialchars($facility['name']); ?>"}
Contact: ${"<?php echo htmlspecialchars($user['name']); ?>"} - ${"<?php echo htmlspecialchars($user['email']); ?>"}

PEDIATRIC PATIENT:
We require blood for a pediatric patient with special handling requirements.

PATIENT INFORMATION:
- Age: Pediatric patient
- Blood Type Needed: [AUTO-FILL]
- Quantity Required: [AUTO-FILL] units (pediatric packs preferred)
- Required By: [AUTO-FILL]
- Condition: Stable but requires scheduled transfusion

SPECIAL REQUIREMENTS:
- Pediatric blood packs preferred (smaller volumes)
- Irradiated blood products if available
- CMV-negative preferred for this age group
- Fresh blood (<7 days) if possible

Please confirm availability of pediatric-specific blood products and any special handling instructions.`
    }
};

        function loadTemplate(templateKey) {
            const template = templates[templateKey];
            if (template) {
                document.getElementById('subject').value = template.subject;
                document.getElementById('message').value = template.message;
                
                // Auto-set urgency based on template
                if (templateKey === 'emergency') {
                    document.getElementById('urgency').value = 'critical';
                }
            }
        }

        function makeEmergencyCall() {
            if (confirm('This will initiate an emergency call to the Central Blood Bank. Continue?')) {
                // In a real application, this would:
                // 1. Dial the emergency number
                // 2. Log the emergency call
                // 3. Send high-priority alerts
                
                alert('📞 Calling Central Blood Bank Emergency Line: (555) 987-6543');
                
                // Log the emergency call attempt
                fetch('log_emergency_call.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        hospital_id: <?php echo $facility['id']; ?>,
                        request_id: <?php echo $request_id ?: 'null'; ?>,
                        timestamp: new Date().toISOString()
                    })
                });
            }
        }

        // Auto-fill required date to 2 hours from now
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            now.setHours(now.getHours() + 2);
            document.getElementById('required_date').min = new Date().toISOString().slice(0, 16);
        });

        // Update quantity limits based on urgency
        document.getElementById('urgency').addEventListener('change', function() {
            const urgency = this.value;
            const quantityInput = document.getElementById('quantity');
            
            if (urgency === 'critical' || urgency === 'emergency') {
                quantityInput.max = 100; // Allow more units for emergencies
                quantityInput.value = Math.max(quantityInput.value, 5); // Minimum 5 for emergencies
            } else {
                quantityInput.max = 50;
            }
        });
    </script>
</body>
</html>