<?php
// Database configuration - detect environment
if (!defined('DB_HOST')) {
    if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
        // Local development
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'sanguisense');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('BASE_URL', 'http://localhost/SanguiSense/');
    } else {
        // Hostinger production
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'u887819627_sanguisense');
        define('DB_USER', 'u887819627_admin');
        define('DB_PASS', '@123SANGuISENSE');
        define('BASE_URL', 'https://sanguisense.io/');
    }
}

// Portal URLs
if (!defined('HOSPITAL_URL')) {
    define('HOSPITAL_URL', BASE_URL . 'hospital/');
    define('PATIENTS_URL', BASE_URL . 'patients/');
    define('DONORS_URL', BASE_URL . 'donors/');
    define('BLOODBANK_URL', BASE_URL . 'bloodbank/');
}

// Create database connection - only if PDO not already created
if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
            DB_USER, 
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session - only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>