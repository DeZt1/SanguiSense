<?php
header('Content-Type: application/json');
include '../../includes/auth.php';

requireLogin();

try {
    $patientId = $_SESSION['user_id'];
    $requestId = isset($_GET['id']) ? intval($_GET['id']) : null;
    
    if (!$requestId) {
        throw new Exception('Request ID is required');
    }
    
    // Fetch request details
    $query = "SELECT 
        pr.*,
        COALESCE(h.name, bb.name) as facility_name,
        COALESCE(h.type, bb.type) as facility_type,
        COALESCE(h.address, bb.address) as facility_address,
        COALESCE(h.city, bb.city) as facility_city,
        COALESCE(h.phone, bb.phone) as facility_phone,
        COALESCE(h.email, bb.email) as facility_email
    FROM patient_blood_requests pr
    LEFT JOIN facilities h ON pr.hospital_id = h.id
    LEFT JOIN facilities bb ON pr.bloodbank_id = bb.id
    WHERE pr.id = ? AND pr.patient_id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$requestId, $patientId]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        throw new Exception('Request not found');
    }
    
    // Fetch request history
    $historyQuery = "SELECT * FROM request_history WHERE request_id = ? ORDER BY created_at DESC";
    $historyStmt = $pdo->prepare($historyQuery);
    $historyStmt->execute([$requestId]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'request' => $request,
        'history' => $history
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
