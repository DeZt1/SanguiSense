<?php
include '../includes/auth.php';
requireHospitalAdmin();

if (isset($_GET['id'])) {
    $inventory_id = $_GET['id'];
    
    global $pdo;
    try {
        $delete_stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $delete_stmt->execute([$inventory_id]);
        
        header("Location: inventory.php?success=1");
        exit();
    } catch(PDOException $e) {
        header("Location: inventory.php?error=Failed to delete inventory item");
        exit();
    }
} else {
    header("Location: inventory.php");
    exit();
}
?>