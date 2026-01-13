<?php
// Database migration runner for profile_picture column

define('DB_HOST', 'localhost');
define('DB_NAME', 'sanguisense');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if column already exists
    $checkQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_picture'";
    $result = $pdo->query($checkQuery)->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        // Add profile_picture column to users table
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL");
        echo "✓ Added profile_picture column to users table<br>";
    } else {
        echo "✓ profile_picture column already exists in users table<br>";
    }
    
    // Check if logo column exists in facilities
    $checkFacilitiesQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                            WHERE TABLE_NAME = 'facilities' AND COLUMN_NAME = 'logo'";
    $facilResult = $pdo->query($checkFacilitiesQuery)->fetch(PDO::FETCH_ASSOC);
    
    if (!$facilResult) {
        // Add logo column to facilities table
        $pdo->exec("ALTER TABLE facilities ADD COLUMN logo VARCHAR(255) DEFAULT NULL");
        echo "✓ Added logo column to facilities table<br>";
    } else {
        echo "✓ logo column already exists in facilities table<br>";
    }
    
    echo "<br><strong>Migration completed successfully!</strong>";
    
} catch(PDOException $e) {
    die("<strong>Migration Error:</strong> " . $e->getMessage());
}
?>
