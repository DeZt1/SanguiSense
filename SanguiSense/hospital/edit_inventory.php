<?php
include '../includes/auth.php';
requireHospitalAdmin();

if (!isset($_GET['id'])) {
    header("Location: inventory.php");
    exit();
}

$inventory_id = $_GET['id'];

// Get inventory item details
global $pdo;
$inventory_stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
$inventory_stmt->execute([$inventory_id]);
$inventory = $inventory_stmt->fetch(PDO::FETCH_ASSOC);

if (!$inventory) {
    header("Location: inventory.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_inventory'])) {
    $blood_type = $_POST['blood_type'];
    $quantity = $_POST['quantity'];
    $expiration_date = $_POST['expiration_date'];
    $status = $_POST['status'];
    
    try {
        $update_stmt = $pdo->prepare("
            UPDATE inventory 
            SET blood_type = ?, quantity = ?, expiration_date = ?, status = ?
            WHERE id = ?
        ");
        $update_stmt->execute([$blood_type, $quantity, $expiration_date, $status, $inventory_id]);
        
        header("Location: inventory.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to update inventory: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Blood Inventory - Hospital Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Edit Blood Inventory</h1>
            <p>Update blood stock information</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h3>Edit Inventory Item</h3>
            <div class="admin-form">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="blood_type">Blood Type</label>
                            <select id="blood_type" name="blood_type" required>
                                <option value="">Select Blood Type</option>
                                <option value="A+" <?php echo $inventory['blood_type'] == 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo $inventory['blood_type'] == 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo $inventory['blood_type'] == 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo $inventory['blood_type'] == 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo $inventory['blood_type'] == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo $inventory['blood_type'] == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo $inventory['blood_type'] == 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo $inventory['blood_type'] == 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity (Units)</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="<?php echo htmlspecialchars($inventory['quantity']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiration_date">Expiration Date</label>
                            <input type="date" id="expiration_date" name="expiration_date" value="<?php echo htmlspecialchars($inventory['expiration_date']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <option value="available" <?php echo $inventory['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="used" <?php echo $inventory['status'] == 'used' ? 'selected' : ''; ?>>Used</option>
                                <option value="expired" <?php echo $inventory['status'] == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_inventory" class="btn btn-primary" style="background: var(--hospital-blue);">Update Inventory</button>
                        <a href="inventory.php" class="btn btn-secondary" class="btn btn-secondary" style="background:#ffffff;color:var(--dark-red);border:2px solid var(--dark-red);box-shadow:0 4px 10px rgba(0,0,0,0.1);">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>