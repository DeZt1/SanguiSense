<?php
// Hostinger Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'u887819627_admin');
define('DB_PASS', '@123SANGuISENSE');
define('DB_NAME', 'u887819627_sanguisense');

// Hostinger Base URLs - Root level
define('BASE_URL', 'https://sanguisense.io/');
define('HOSPITAL_URL', BASE_URL . 'hospital/');
define('PATIENTS_URL', BASE_URL . 'patients/');
define('DONORS_URL', BASE_URL . 'donors/');
define('BLOODBANK_URL', BASE_URL . 'bloodbank/');

// Paths for file uploads
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Production error handling - DO NOT show errors on live
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', $_SERVER['DOCUMENT_ROOT'] . '/error.log');
}

// Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
