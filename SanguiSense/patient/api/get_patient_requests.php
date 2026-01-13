<?php
header('Content-Type: application/json');
include '../../includes/auth.php';

requireLogin();

try {
    $patientId = $_SESSION['user_id'];
    
    // Fetch patient's requests with facility information
    $query = "SELECT 
        pr.*,
        COALESCE(h.name, bb.name) as facility_name,
        COALESCE(h.type, bb.type) as facility_type
    FROM patient_blood_requests pr
    LEFT JOIN facilities h ON pr.hospital_id = h.id
    LEFT JOIN facilities bb ON pr.bloodbank_id = bb.id
    WHERE pr.patient_id = ?
    ORDER BY pr.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$patientId]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'count' => count($requests)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
