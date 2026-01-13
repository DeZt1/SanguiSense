<?php
// includes/db_connect.php
// PDO connection for SanguiSense - Hostinger

// Detect environment
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Local development
    $host = 'localhost';
    $db   = 'sanguisense';
    $user = 'root';
    $pass = '';
} else {
    // Hostinger production
    $host = 'localhost';
    $db   = 'u887819627_sanguisense';
    $user = 'u887819627_admin';
    $pass = '@123SANGuISENSE';
}

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    if (!isset($pdo)) {
        $pdo = new PDO($dsn, $user, $pass, $options);
    }
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
