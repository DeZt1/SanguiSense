<?php
/**
 * Migration Runner: Create patient_profiles Table
 * Run this script to create the patient_profiles table in the database
 */

// Include database configuration
require_once '../includes/config.php';

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/2025-11-15_add_patient_profiles_table.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Execute the migration
    $pdo->exec($sql);
    
    echo "<div style='padding: 20px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px;'>";
    echo "<h2>✓ Migration Successful!</h2>";
    echo "<p>The <strong>patient_profiles</strong> table has been created successfully.</p>";
    echo "<p><a href='../patient/register.php' style='color: #0c5460; text-decoration: underline;'>Go to Patient Registration</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;'>";
    echo "<h2>✗ Migration Failed</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
