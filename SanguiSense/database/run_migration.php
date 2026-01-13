<?php
// Database migration script for patient portal
include '../includes/config.php';

try {
    // Read the migration SQL file
    $sql = file_get_contents(__DIR__ . '/patient_portal_fix.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            // Skip comment lines
            if (strpos($statement, '--') === 0) {
                continue;
            }
            
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
            echo "✓ Success\n\n";
        }
    }
    
    echo "\n✓ Migration completed successfully!\n";
    echo "\nYour database is now ready for patient portal registration.\n";
    echo "You can now register patient accounts at: http://localhost/sanguisense/patient/register.php\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    echo "\nError Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
