<?php
include '../includes/auth.php';
requireBloodBankAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get available inventory for distribution
global $pdo;
if ($facility) {
    $available_inventory = $pdo->prepare("
        SELECT * FROM inventory 
        WHERE facility_id = ? AND status = 'available' AND quantity > 0 AND expiration_date > CURDATE()
        ORDER BY expiration_date ASC
    ");
    $available_inventory->execute([$facility['id']]);
    $available_inventory = $available_inventory->fetchAll(PDO::FETCH_ASSOC);
} else {
    $available_inventory = [];
}

// Get hospitals for distribution
$hospitals = $pdo->query("SELECT * FROM facilities WHERE type = 'hospital' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle distribution form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['distribute_blood'])) {
    $inventory_id = $_POST['inventory_id'];
    $hospital_id = $_POST['hospital_id'];
    $quantity = $_POST['quantity'];
    $distribution_date = $_POST['distribution_date'];
    $purpose = $_POST['purpose'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get current inventory item
        $inventory_stmt = $pdo->prepare("SELECT * FROM inventory WHERE id = ?");
        $inventory_stmt->execute([$inventory_id]);
        $inventory_item = $inventory_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inventory_item && $inventory_item['quantity'] >= $quantity) {
            // Update current inventory (reduce quantity)
            $update_stmt = $pdo->prepare("UPDATE inventory SET quantity = quantity - ? WHERE id = ?");
            $update_stmt->execute([$quantity, $inventory_id]);
            
            // Add to hospital inventory
            $hospital_inventory_stmt = $pdo->prepare("INSERT INTO inventory (facility_id, blood_type, quantity, expiration_date, status) VALUES (?, ?, ?, ?, 'available')");
            $hospital_inventory_stmt->execute([$hospital_id, $inventory_item['blood_type'], $quantity, $inventory_item['expiration_date']]);
            
            // Record distribution
            $distribution_stmt = $pdo->prepare("INSERT INTO distributions (from_facility_id, to_facility_id, blood_type, quantity, distribution_date, purpose) VALUES (?, ?, ?, ?, ?, ?)");
            $distribution_stmt->execute([$facility['id'], $hospital_id, $inventory_item['blood_type'], $quantity, $distribution_date, $purpose]);
            
            $pdo->commit();
            $success = "Blood distributed successfully to hospital!";
            header("Location: distribution.php?success=1");
            exit();
        } else {
            $error = "Insufficient inventory quantity available";
            $pdo->rollBack();
        }
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "Failed to distribute blood: " . $e->getMessage();
    }
}

// Get distribution history
if ($facility) {
    $distribution_history = $pdo->prepare("
        SELECT d.*, f.name as hospital_name 
        FROM distributions d 
        JOIN facilities f ON d.to_facility_id = f.id 
        WHERE d.from_facility_id = ? 
        ORDER BY d.distribution_date DESC 
        LIMIT 10
    ");
    $distribution_history->execute([$facility['id']]);
    $distribution_history = $distribution_history->fetchAll(PDO::FETCH_ASSOC);
} else {
    $distribution_history = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distribution - Blood Bank Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <h2><a href="dashboard.php" class="logo-link">
                    <span class="blood-drop">🩸</span>SanguiSense Blood Bank
                </a></h2>
            </div>
             <div class="nav-menu">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="inventory.php" class="nav-link">Inventory</a>
                <a href="donations.php" class="nav-link">Donations</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="distribution.php" class="nav-link">Distribution</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Blood Distribution</h1>
            <p>Manage blood distribution to hospitals and medical facilities</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Blood distributed successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h3>Distribute Blood</h3>
                <div class="admin-form">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="inventory_id">Blood Inventory</label>
                                <select id="inventory_id" name="inventory_id" required>
                                    <option value="">Select Blood Inventory</option>
                                    <?php foreach ($available_inventory as $item): ?>
                                        <option value="<?php echo $item['id']; ?>" data-quantity="<?php echo $item['quantity']; ?>" data-type="<?php echo $item['blood_type']; ?>">
                                            <?php echo htmlspecialchars($item['blood_type'] . ' - ' . $item['quantity'] . ' units (Exp: ' . date('M j, Y', strtotime($item['expiration_date'])) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="hospital_id">Hospital</label>
                                <select id="hospital_id" name="hospital_id" required>
                                    <option value="">Select Hospital</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?php echo $hospital['id']; ?>">
                                            <?php echo htmlspecialchars($hospital['name'] . ' - ' . $hospital['city']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="quantity">Quantity to Distribute</label>
                                <input type="number" id="quantity" name="quantity" min="1" required>
                                <small id="quantity-help">Maximum available: <span id="max-quantity">0</span> units</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="distribution_date">Distribution Date</label>
                                <input type="date" id="distribution_date" name="distribution_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="purpose">Purpose</label>
                            <select id="purpose" name="purpose" required>
                                <option value="">Select Purpose</option>
                                <option value="routine_supply">Routine Supply</option>
                                <option value="emergency">Emergency</option>
                                <option value="scheduled_surgery">Scheduled Surgery</option>
                                <option value="critical_care">Critical Care</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="distribute_blood" class="btn btn-primary" style="background: var(--bloodbank-purple);">Distribute Blood</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="content-card">
                <h3>Available Inventory</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Blood Type</th>
                                <th>Quantity</th>
                                <th>Expiration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($available_inventory as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['blood_type']); ?></td>
                                <td><?php echo $item['quantity']; ?> units</td>
                                <td><?php echo date('M j, Y', strtotime($item['expiration_date'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($available_inventory)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No available inventory</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3>Distribution History</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Hospital</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Purpose</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($distribution_history as $distribution): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($distribution['distribution_date'])); ?></td>
                            <td><?php echo htmlspecialchars($distribution['hospital_name']); ?></td>
                            <td><?php echo htmlspecialchars($distribution['blood_type']); ?></td>
                            <td><?php echo $distribution['quantity']; ?> units</td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $distribution['purpose'])); ?></td>
                            <td>
                                <span class="status-badge status-completed">Completed</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($distribution_history)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No distribution history</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Update max quantity when inventory item is selected
        document.getElementById('inventory_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const maxQuantity = selectedOption.getAttribute('data-quantity');
                document.getElementById('quantity').max = maxQuantity;
                document.getElementById('quantity').value = Math.min(1, maxQuantity);
                document.getElementById('max-quantity').textContent = maxQuantity;
            } else {
                document.getElementById('max-quantity').textContent = '0';
            }
        });

        // Initialize max quantity display
        document.addEventListener('DOMContentLoaded', function() {
            const inventorySelect = document.getElementById('inventory_id');
            if (inventorySelect.value) {
                const selectedOption = inventorySelect.options[inventorySelect.selectedIndex];
                const maxQuantity = selectedOption.getAttribute('data-quantity');
                document.getElementById('max-quantity').textContent = maxQuantity;
            }
        });
    </script>
</body>
</html>