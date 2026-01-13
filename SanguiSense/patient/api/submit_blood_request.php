<?php
header('Content-Type: application/json');
include '../../includes/auth.php';

requireLogin();

try {
    $patientId = $_SESSION['user_id'];
    $bloodType = isset($_POST['blood_type']) ? trim($_POST['blood_type']) : '';
    $facilityId = isset($_POST['facility_id']) ? intval($_POST['facility_id']) : null;
    $quantityUnits = isset($_POST['quantity_units']) ? intval($_POST['quantity_units']) : 1;
    $requiredDate = isset($_POST['required_date']) ? trim($_POST['required_date']) : '';
    $urgency = isset($_POST['urgency']) ? trim($_POST['urgency']) : 'routine';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    $facilityType = isset($_POST['facility_type']) ? trim($_POST['facility_type']) : '';
    
    // Validation
    $validation_errors = [];
    
    // Validate blood type
    $validBloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (empty($bloodType)) {
        $validation_errors[] = 'Blood type is required.';
    } elseif (!in_array($bloodType, $validBloodTypes)) {
        $validation_errors[] = 'Invalid blood type selected.';
    }
    
    // Validate facility
    if (empty($facilityId)) {
        $validation_errors[] = 'Facility must be selected.';
    } elseif (!is_numeric($facilityId) || $facilityId < 1) {
        $validation_errors[] = 'Invalid facility selection.';
    }
    
    // Validate quantity
    if (empty($quantityUnits) || !is_numeric($quantityUnits)) {
        $validation_errors[] = 'Quantity must be a valid number.';
    } elseif ($quantityUnits < 1) {
        $validation_errors[] = 'Quantity must be at least 1 unit.';
    } elseif ($quantityUnits > 100) {
        $validation_errors[] = 'Quantity cannot exceed 100 units.';
    }
    
    // Validate required date
    if (empty($requiredDate)) {
        $validation_errors[] = 'Required date is required.';
    } else {
        $requiredDateTime = strtotime($requiredDate);
        if ($requiredDateTime === false) {
            $validation_errors[] = 'Invalid date format.';
        } elseif ($requiredDateTime < strtotime('today')) {
            $validation_errors[] = 'Required date must be today or in the future.';
        }
    }
    
    // Validate urgency
    $validUrgencies = ['routine', 'urgent', 'emergency', 'critical'];
    if (empty($urgency)) {
        $validation_errors[] = 'Urgency level is required.';
    } elseif (!in_array($urgency, $validUrgencies)) {
        $validation_errors[] = 'Invalid urgency level selected.';
    }
    
    // Validate reason (if provided)
    if (!empty($reason) && strlen($reason) > 255) {
        $validation_errors[] = 'Reason must not exceed 255 characters.';
    }
    
    // Validate notes (if provided)
    if (!empty($notes) && strlen($notes) > 1000) {
        $validation_errors[] = 'Notes must not exceed 1000 characters.';
    }
    
    // Return validation errors if any
    if (!empty($validation_errors)) {
        throw new Exception(implode('; ', $validation_errors));
    }
    
    // Check if facility exists and get its type
    $facilityQuery = "SELECT id, type FROM facilities WHERE id = ?";
    $facilityStmt = $pdo->prepare($facilityQuery);
    $facilityStmt->execute([$facilityId]);
    $facility = $facilityStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$facility) {
        throw new Exception('Facility not found');
    }
    
    // Determine hospital_id and bloodbank_id
    $hospitalId = null;
    $bloodbankId = null;
    
    if ($facility['type'] == 'hospital') {
        $hospitalId = $facilityId;
    } else if ($facility['type'] == 'blood_bank') {
        $bloodbankId = $facilityId;
    }
    
    // Insert blood request
    $insertQuery = "INSERT INTO patient_blood_requests 
        (patient_id, hospital_id, bloodbank_id, blood_type, quantity_units, urgency, reason, required_date, notes, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())";
    
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([
        $patientId,
        $hospitalId,
        $bloodbankId,
        $bloodType,
        $quantityUnits,
        $urgency,
        $reason,
        $requiredDate,
        $notes
    ]);
    
    $requestId = $pdo->lastInsertId();
    
    // Log action in request history
    $historyQuery = "INSERT INTO request_history (request_id, action, new_status, created_at)
        VALUES (?, 'created', 'pending', NOW())";
    
    $historyStmt = $pdo->prepare($historyQuery);
    $historyStmt->execute([$requestId]);
    
    // Create notification for the facility
    $facilityAdminQuery = "SELECT admin_id FROM facilities WHERE id = ?";
    $facilityAdminStmt = $pdo->prepare($facilityAdminQuery);
    $facilityAdminStmt->execute([$facilityId]);
    $facilityAdmin = $facilityAdminStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($facilityAdmin && $facilityAdmin['admin_id']) {
        $notifQuery = "INSERT INTO notifications (user_id, title, message, type, created_at)
            VALUES (?, ?, ?, 'alert', NOW())";
        
        $notifStmt = $pdo->prepare($notifQuery);
        $notifStmt->execute([
            $facilityAdmin['admin_id'],
            'New Blood Request Received',
            "Patient has submitted a blood request for {$bloodType} ({$quantityUnits} units) - Urgency: {$urgency}"
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Blood request submitted successfully',
        'request_id' => $requestId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
