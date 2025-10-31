<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get inventory for current hospital
global $pdo;
$inventory = $pdo->prepare("
    SELECT i.* 
    FROM inventory i 
    WHERE i.facility_id = ? 
    ORDER BY i.expiration_date ASC
");
$inventory->execute([$facility['id']]);
$inventory = $inventory->fetchAll(PDO::FETCH_ASSOC);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_inventory'])) {
        $blood_type = $_POST['blood_type'];
        $quantity = $_POST['quantity'];
        $expiration_date = $_POST['expiration_date'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO inventory (facility_id, blood_type, quantity, expiration_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$facility['id'], $blood_type, $quantity, $expiration_date]);
            $success = "Blood stock added successfully!";
            header("Location: inventory.php?success=1");
            exit();
        } catch(PDOException $e) {
            $error = "Failed to add blood stock: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Inventory - Hospital Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><a href="dashboard.php" class="logo-link">
                    <span class="blood-drop">🏥</span>SanguiSense Hospital
                </a></h2>
            </div>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="inventory.php" class="nav-link active">Blood Inventory</a>
                <a href="donors.php" class="nav-link">Donors</a>
                <a href="appointments.php" class="nav-link">Appointments</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Blood Inventory Management</h1>
            <p>Manage blood stock levels and track expiration dates</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Blood stock updated successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="inventory-actions">
            <button onclick="showAddForm()" class="btn btn-primary" style="background: var(--hospital-blue);">Add Blood Stock</button>
        </div>

        <!-- Add Inventory Form -->
        <div id="addInventoryForm" class="admin-form" style="display: none;">
            <h3>Add New Blood Stock</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_type">Blood Type</label>
                        <select id="blood_type" name="blood_type" required>
                            <option value="">Select Blood Type</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity (Units)</label>
                        <input type="number" id="quantity" name="quantity" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiration_date">Expiration Date</label>
                        <input type="date" id="expiration_date" name="expiration_date" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_inventory" class="btn btn-primary" style="background: var(--hospital-blue);">Add to Inventory</button>
                    <button type="button" onclick="hideAddForm()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>

        <div class="inventory-stats">
            <div class="stat-card hospital-stat">
                <h3>Total Blood Units</h3>
                <p class="stat-number"><?php echo count($inventory); ?></p>
            </div>
            <div class="stat-card hospital-stat">
                <h3>Low Stock (< 10)</h3>
                <p class="stat-number">
                    <?php
                    $low_stock = array_filter($inventory, function($item) {
                        return $item['quantity'] < 10;
                    });
                    echo count($low_stock);
                    ?>
                </p>
            </div>
            <div class="stat-card hospital-stat">
                <h3>Expiring Soon</h3>
                <p class="stat-number">
                    <?php
                    $expiring_soon = array_filter($inventory, function($item) {
                        return strtotime($item['expiration_date']) < strtotime('+7 days');
                    });
                    echo count($expiring_soon);
                    ?>
                </p>
            </div>
        </div>

        <div class="data-table">
            <h3>Current Blood Inventory</h3>
            <table>
                <thead>
                    <tr>
                        <th>Blood Type</th>
                        <th>Quantity</th>
                        <th>Expiration Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No blood inventory found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['blood_type']); ?></td>
                                <td>
                                    <span class="<?php echo $item['quantity'] < 10 ? 'low-stock' : ''; ?>">
                                        <?php echo $item['quantity']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo strtotime($item['expiration_date']) < strtotime('+7 days') ? 'expiring-soon' : ''; ?>">
                                        <?php echo date('M j, Y', strtotime($item['expiration_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="editInventory(<?php echo $item['id']; ?>)" class="btn btn-small" style="background: var(--hospital-blue);">Edit</button>
                                    <button onclick="deleteInventory(<?php echo $item['id']; ?>)" class="btn btn-small btn-danger">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function showAddForm() {
            document.getElementById('addInventoryForm').style.display = 'block';
        }
        
        function hideAddForm() {
            document.getElementById('addInventoryForm').style.display = 'none';
        }
        
        function editInventory(id) {
            alert('Edit inventory item ' + id + ' - This would open an edit form');
            // Implementation for editing
        }
        
        function deleteInventory(id) {
            if (confirm('Are you sure you want to delete this inventory item?')) {
                window.location.href = 'delete_inventory.php?id=' + id;
            }
        }
    </script>
</body>
</html>