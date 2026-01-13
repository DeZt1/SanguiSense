<?php
require_once __DIR__ . '/../includes/db_connect.php';
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

// Fetch hospitals and blood banks
$facilitiesQuery = "SELECT id, name, type, city FROM facilities ORDER BY name";
$facilitiesStmt = $pdo->prepare($facilitiesQuery);
$facilitiesStmt->execute();
$facilities = $facilitiesStmt->fetchAll(PDO::FETCH_ASSOC);

$hospitals = array_filter($facilities, function($f) { return $f['type'] == 'hospital'; });
$bloodbanks = array_filter($facilities, function($f) { return $f['type'] == 'blood_bank'; });
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Blood Request - SanguiSense Patient Portal</title>
    <link rel="stylesheet" href="css/patient.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_patient.php'; ?>

    <!-- Main Content -->
    <div class="patient-dashboard">
        <div class="dashboard-header">
            <h1>Send Blood Request</h1>
            <p>Submit a formal blood request to hospitals or blood banks</p>
        </div>

        <!-- Blood Request Form -->
        <div class="request-form">
            <h2>Blood Request Details</h2>
            
            <form id="bloodRequestForm" onsubmit="submitBloodRequest(event)">
                <!-- Row 1: Blood Type and Quantity -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="bloodType">Blood Type <span style="color: red;">*</span></label>
                        <select id="bloodType" name="blood_type" required>
                            <option value="">Select Blood Type</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantityUnits">Quantity (Units) <span style="color: red;">*</span></label>
                        <input type="number" id="quantityUnits" name="quantity_units" min="1" max="100" value="1" required>
                    </div>
                </div>

                <!-- Row 2: Facility Type and Selection -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="facilityType">Request to <span style="color: red;">*</span></label>
                        <select id="facilityType" name="facility_type" onchange="updateFacilityList()" required>
                            <option value="">Select Type</option>
                            <option value="hospital">Hospital</option>
                            <option value="blood_bank">Blood Bank</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="facilityId">Choose Facility <span style="color: red;">*</span></label>
                        <select id="facilityId" name="facility_id" required>
                            <option value="">Select a facility</option>
                        </select>
                    </div>
                </div>

                <!-- Row 3: Required Date and Urgency -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="requiredDate">Required Date <span style="color: red;">*</span></label>
                        <input type="date" id="requiredDate" name="required_date" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="urgency">Urgency Level <span style="color: red;">*</span></label>
                        <select id="urgency" name="urgency" required>
                            <option value="routine">Routine</option>
                            <option value="urgent">Urgent</option>
                            <option value="emergency">Emergency</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>

                <!-- Row 4: Reason and Notes -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="reason">Reason for Blood Request</label>
                        <input type="text" id="reason" name="reason" placeholder="e.g., Scheduled Surgery, Anemia Treatment">
                    </div>
                </div>

                <div class="form-group">
                    <label for="notes">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="5" placeholder="Add any special instructions or medical information..."></textarea>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Blood Request</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <!-- Information Section -->
        <div style="background: linear-gradient(135deg, rgba(0, 188, 212, 0.1), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2); margin-top: 2rem;">
            <h2 style="color: var(--patient-teal); margin-bottom: 1.5rem; font-size: 1.6rem;">Request Guidelines</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
                <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 10px; border-left: 4px solid var(--patient-teal);">
                    <h4 style="color: var(--patient-teal); margin-bottom: 0.8rem;">📋 Required Information</h4>
                    <ul style="color: #e0e0e0; line-height: 1.8; list-style-position: inside;">
                        <li>Blood Type (Must match your actual type)</li>
                        <li>Quantity in units (1-100)</li>
                        <li>Target facility</li>
                        <li>Required delivery date</li>
                        <li>Urgency level</li>
                    </ul>
                </div>

                <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 10px; border-left: 4px solid var(--patient-teal);">
                    <h4 style="color: var(--patient-teal); margin-bottom: 0.8rem;">⏱️ Urgency Levels</h4>
                    <ul style="color: #e0e0e0; line-height: 1.8;">
                        <li><strong>Routine:</strong> Standard procedure, no rush</li>
                        <li><strong>Urgent:</strong> Needed within 24 hours</li>
                        <li><strong>Emergency:</strong> Needed within 6-12 hours</li>
                        <li><strong>Critical:</strong> Immediate need (ASAP)</li>
                    </ul>
                </div>

                <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 10px; border-left: 4px solid var(--patient-teal);">
                    <h4 style="color: var(--patient-teal); margin-bottom: 0.8rem;">✓ What Happens Next</h4>
                    <ol style="color: #e0e0e0; line-height: 1.8; list-style-position: inside;">
                        <li>Your request is submitted to the selected facility</li>
                        <li>Facility reviews and verifies your request</li>
                        <li>You receive status updates via notifications</li>
                        <li>Once approved, blood is allocated and delivered</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <script src="js/patient.js"></script>
    <script>
        // Populate facility list based on type
        function updateFacilityList() {
            const facilityType = document.getElementById('facilityType').value;
            const facilityId = document.getElementById('facilityId');
            
            facilityId.innerHTML = '<option value="">Select a facility</option>';
            
            const facilities = <?php echo json_encode($facilities); ?>;
            
            const filtered = facilities.filter(f => f.type === facilityType);
            
            filtered.forEach(facility => {
                const option = document.createElement('option');
                option.value = facility.id;
                option.textContent = `${facility.name} (${facility.city})`;
                facilityId.appendChild(option);
            });
        }

        // Set minimum date to today and validate form
        document.addEventListener('DOMContentLoaded', function() {
            const requiredDateInput = document.getElementById('requiredDate');
            const today = new Date().toISOString().split('T')[0];
            requiredDateInput.setAttribute('min', today);
            
            // Override submitBloodRequest to add more validation
            const originalSubmit = window.submitBloodRequest;
            window.submitBloodRequest = function(event) {
                if (event) {
                    event.preventDefault();
                }
                
                const form = event?.target || document.getElementById('bloodRequestForm');
                if (!form) return;
                
                // Clear previous errors
                document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
                
                const bloodType = document.getElementById('bloodType').value.trim();
                const facilityType = document.getElementById('facilityType').value.trim();
                const facilityId = document.getElementById('facilityId').value.trim();
                const quantity = document.getElementById('quantityUnits').value.trim();
                const requiredDate = document.getElementById('requiredDate').value.trim();
                const urgency = document.getElementById('urgency').value.trim();
                
                let isValid = true;
                
                // Validate blood type
                if (!bloodType) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Blood type is required.';
                    document.getElementById('bloodType').parentElement.appendChild(err);
                    isValid = false;
                }
                
                // Validate facility type
                if (!facilityType) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Facility type must be selected.';
                    document.getElementById('facilityType').parentElement.appendChild(err);
                    isValid = false;
                }
                
                // Validate facility
                if (!facilityId) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Facility must be selected.';
                    document.getElementById('facilityId').parentElement.appendChild(err);
                    isValid = false;
                }
                
                // Validate quantity
                if (!quantity || isNaN(quantity)) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Quantity must be a valid number.';
                    document.getElementById('quantityUnits').parentElement.appendChild(err);
                    isValid = false;
                } else if (quantity < 1) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Quantity must be at least 1 unit.';
                    document.getElementById('quantityUnits').parentElement.appendChild(err);
                    isValid = false;
                } else if (quantity > 100) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Quantity cannot exceed 100 units.';
                    document.getElementById('quantityUnits').parentElement.appendChild(err);
                    isValid = false;
                }
                
                // Validate required date
                if (!requiredDate) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Required date is required.';
                    document.getElementById('requiredDate').parentElement.appendChild(err);
                    isValid = false;
                } else {
                    const selectedDate = new Date(requiredDate);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    if (selectedDate < today) {
                        const err = document.createElement('small');
                        err.className = 'form-error';
                        err.textContent = 'Required date must be today or in the future.';
                        document.getElementById('requiredDate').parentElement.appendChild(err);
                        isValid = false;
                    }
                }
                
                // Validate urgency
                if (!urgency) {
                    const err = document.createElement('small');
                    err.className = 'form-error';
                    err.textContent = 'Urgency level is required.';
                    document.getElementById('urgency').parentElement.appendChild(err);
                    isValid = false;
                }
                
                if (!isValid) {
                    showAlert('Please fix the validation errors below.', 'error');
                    return false;
                }
                
                // Call original validation if all basic checks pass
                return originalSubmit.call(this, event);
            };
        });
    </script>
</body>
</html>
