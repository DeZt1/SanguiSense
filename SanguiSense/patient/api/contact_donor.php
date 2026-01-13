<?php
header('Content-Type: application/json');
include '../../includes/auth.php';

requireLogin();

try {
    $patientId = $_SESSION['user_id'];
    $donorId = isset($_POST['donor_id']) ? intval($_POST['donor_id']) : null;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $bloodRequestId = isset($_POST['blood_request_id']) ? intval($_POST['blood_request_id']) : null;
    
    if (!$donorId) {
        throw new Exception('Donor ID is required');
    }
    
    // Check if donor exists
    $donorQuery = "SELECT id FROM users WHERE id = ? AND user_type = 'donor'";
    $donorStmt = $pdo->prepare($donorQuery);
    $donorStmt->execute([$donorId]);
    
    if (!$donorStmt->fetch(PDO::FETCH_ASSOC)) {
        throw new Exception('Donor not found');
    }
    
    // Check if contact request already exists (prevent duplicates)
    $checkQuery = "SELECT id FROM donor_contact_requests 
        WHERE patient_id = ? AND donor_id = ? AND request_status IN ('pending', 'accepted')
        ORDER BY created_at DESC LIMIT 1";
    
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$patientId, $donorId]);
    $existingRequest = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingRequest) {
        throw new Exception('You have already sent a contact request to this donor');
    }
    
    // Insert contact request
    $insertQuery = "INSERT INTO donor_contact_requests 
        (patient_id, donor_id, blood_request_id, message, request_status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())";
    
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->execute([
        $patientId,
        $donorId,
        $bloodRequestId,
        $message
    ]);
    
    $contactRequestId = $pdo->lastInsertId();
    
    // Create notification for the donor
    $patientQuery = "SELECT name FROM users WHERE id = ?";
    $patientStmt = $pdo->prepare($patientQuery);
    $patientStmt->execute([$patientId]);
    $patient = $patientStmt->fetch(PDO::FETCH_ASSOC);
    
    $notifQuery = "INSERT INTO notifications (user_id, title, message, type, created_at)
        VALUES (?, ?, ?, 'alert', NOW())";
    
    $notifStmt = $pdo->prepare($notifQuery);
    $notifStmt->execute([
        $donorId,
        'New Contact Request from Patient',
        $patient['name'] . ' is requesting to get in touch with you regarding blood donation'
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Contact request sent successfully',
        'contact_request_id' => $contactRequestId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
