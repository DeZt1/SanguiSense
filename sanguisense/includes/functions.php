<?php
include 'config.php';

// Function to redirect users
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get user data
function getUserData($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to add notification
function addNotification($user_id, $title, $message, $type = 'info') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

// Function to get notifications - FIXED VERSION
function getNotifications($user_id, $limit = 5) {
    global $pdo;
    // Use integer directly in SQL for LIMIT with MariaDB
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get user's facility (for admins)
function getUserFacility($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT f.* FROM facilities f WHERE f.admin_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get hospital stats
function getHospitalStats($facility_id) {
    global $pdo;
    $stats = [];
    
    // Total donors
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_donors FROM users WHERE user_type = 'donor'");
    $stmt->execute();
    $stats['total_donors'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_donors'];
    
    // Pending donations for this hospital
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_donations FROM donations WHERE status = 'scheduled' AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['pending_donations'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_donations'];
    
    // Recent donations for this hospital
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_donations FROM donations WHERE donation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['recent_donations'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_donations'];
    
    // Blood requests for this hospital
    $stmt = $pdo->prepare("SELECT COUNT(*) as blood_requests FROM demand_forecasts WHERE predicted_demand > 0 AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['blood_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['blood_requests'];
    
    return $stats;
}

// Function to get blood bank stats
function getBloodBankStats($facility_id) {
    global $pdo;
    $stats = [];
    
    // Total inventory for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_inventory FROM inventory WHERE facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['total_inventory'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_inventory'];
    
    // Low stock items for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as low_stock FROM inventory WHERE quantity < 10 AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];
    
    // Expiring soon for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as expiring_soon FROM inventory WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['expiring_soon'] = $stmt->fetch(PDO::FETCH_ASSOC)['expiring_soon'];
    
    // Total donations for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_donations FROM donations WHERE status = 'completed' AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['total_donations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_donations'];
    
    return $stats;
}
?>