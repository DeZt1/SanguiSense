<?php
include 'auth.php';
requireHospitalAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    error_log("Emergency call attempted - Hospital: " . ($input['hospital_id'] ?? 'unkown') . " at " . date('Y-m-d H:i:s'));

    echo json_encode(['status' => 'logged']);
    exit();
}
?>