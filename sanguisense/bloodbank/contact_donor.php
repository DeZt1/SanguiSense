<?php
include '../includes/auth.php';
requireBloodBankAdmin();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit();
}

if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $donor_id = (int)$_POST['id'];
    $admin = getUserData($_SESSION['user_id']);
    $title = 'Contact from Blood Bank';
    $message = 'You were contacted by ' . ($admin['name'] ?? 'a Blood Bank admin') . ' on ' . date('M j, Y H:i');

    try {
        $ok = addNotification($donor_id, $title, $message, 'info');
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'DB insert failed']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Missing id']);
?>
