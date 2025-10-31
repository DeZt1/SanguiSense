<?php
include 'functions.php';

// Check if user is logged in, if not redirect to login
function requireLogin() {
    if (!isLoggedIn()) {
        // Determine which portal we're in
        $current_path = $_SERVER['PHP_SELF'];
        if (strpos($current_path, '/hospital/') !== false) {
            redirect('../hospital/login.php');
        } elseif (strpos($current_path, '/bloodbank/') !== false) {
            redirect('../bloodbank/login.php');
        } else {
            redirect('../donor/login.php');
        }
    }
}

// Check if user is hospital admin
function requireHospitalAdmin() {
    if (!isLoggedIn()) {
        redirect('../hospital/login.php');
    }
    
    $user = getUserData($_SESSION['user_id']);
    if ($user['user_type'] != 'hospital_admin') {
        redirect('../hospital/login.php');
    }
}

// Check if user is blood bank admin
function requireBloodBankAdmin() {
    if (!isLoggedIn()) {
        redirect('../bloodbank/login.php');
    }
    
    $user = getUserData($_SESSION['user_id']);
    if ($user['user_type'] != 'bloodbank_admin') {
        redirect('../bloodbank/login.php');
    }
}

// Login function (portal parameter is now optional)
function login($email, $password, $portal = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['user_name'] = $user['name'];
        
        // Redirect based on user type
        if ($user['user_type'] == 'donor') {
            redirect('../donor/dashboard.php');
        } elseif ($user['user_type'] == 'hospital_admin') {
            redirect('../hospital/dashboard.php');
        } elseif ($user['user_type'] == 'bloodbank_admin') {
            redirect('../bloodbank/dashboard.php');
        }
        return true;
    }
    
    return false;
}

// Logout function
function logout() {
    session_destroy();
    // Prefer explicit portal parameter
    if (isset($_GET['portal'])) {
        $portal = $_GET['portal'];
        if ($portal === 'hospital') redirect('../hospital/login.php');
        if ($portal === 'bloodbank') redirect('../bloodbank/login.php');
        if ($portal === 'donor') redirect('../donor/login.php');
    }

    // Fallback to HTTP_REFERER to detect originating portal
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $ref = $_SERVER['HTTP_REFERER'];
        if (strpos($ref, '/hospital/') !== false) redirect('../hospital/login.php');
        if (strpos($ref, '/bloodbank/') !== false) redirect('../bloodbank/login.php');
        if (strpos($ref, '/donor/') !== false) redirect('../donor/login.php');
    }

    // Last fallback: check current script path
    $current_path = $_SERVER['PHP_SELF'];
    if (strpos($current_path, '/hospital/') !== false) {
        redirect('../hospital/login.php');
    } elseif (strpos($current_path, '/bloodbank/') !== false) {
        redirect('../bloodbank/login.php');
    } else {
        redirect('../donor/index.php');
    }
}

// Handle logout request
if (isset($_GET['logout'])) {
    logout();
}
?>