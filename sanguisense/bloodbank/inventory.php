<?php
include '../includes/auth.php';
requireBloodBankAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get inventory for current blood bank
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
        $blood_type = $_POST['blood_type'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        $expiration_date = $_POST['expiration_date'] ?? '';
        
        // ===== SERVER-SIDE VALIDATION =====
        $validation_errors = [];
        
        // Validate blood type
        $valid_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (empty($blood_type)) {
            $validation_errors[] = "Blood type must be selected.";
        } elseif (!in_array($blood_type, $valid_types)) {
            $validation_errors[] = "Invalid blood type selected.";
        }
        
        // Validate quantity
        if (empty($quantity)) {
            $validation_errors[] = "Quantity is required.";
        } elseif (!is_numeric($quantity) || $quantity < 1) {
            $validation_errors[] = "Quantity must be a positive number.";
        } elseif ($quantity > 1000) {
            $validation_errors[] = "Quantity cannot exceed 1000 units.";
        }
        
        // Validate expiration date
        if (empty($expiration_date)) {
            $validation_errors[] = "Expiration date is required.";
        } else {
            $exp_timestamp = strtotime($expiration_date);
            $today_timestamp = strtotime(date('Y-m-d'));
            if ($exp_timestamp <= $today_timestamp) {
                $validation_errors[] = "Expiration date must be in the future.";
            }
            // Check if date is reasonable (not more than 42 days from now for blood)
            $max_date = strtotime(date('Y-m-d', strtotime('+42 days')));
            if ($exp_timestamp > $max_date) {
                $validation_errors[] = "Expiration date cannot be more than 42 days from now.";
            }
        }
        
        if (empty($validation_errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO inventory (facility_id, blood_type, quantity, expiration_date, status) VALUES (?, ?, ?, ?, 'available')");
                $stmt->execute([$facility['id'], $blood_type, $quantity, $expiration_date]);
                $success = "Blood stock added successfully!";
                header("Location: inventory.php?success=1");
                exit();
            } catch(PDOException $e) {
                $error = "Failed to add blood stock: " . $e->getMessage();
            }
        } else {
            $error = "Please fix the following errors:\n• " . implode("\n• ", $validation_errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Blood Bank</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_bloodbank.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Blood Inventory Management</h1>
            <p>Manage blood collection, storage, and distribution</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Inventory updated successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php 
                if (strpos($error, 'Please fix') === 0) {
                    echo nl2br(htmlspecialchars($error));
                } else {
                    echo htmlspecialchars($error);
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="inventory-actions">
            <button onclick="showAddForm()" class="btn btn-primary" style="background: var(--bloodbank-purple);">Add Blood Collection</button>
            <a href="distribution.php" class="btn btn-secondary">Distribute Blood</a>
        </div>

        <!-- Add Inventory Form -->
        <div id="addInventoryForm" class="admin-form" style="display: none;">
            <h3>Record Blood Collection</h3>
            <form method="POST" id="inventoryForm" onsubmit="return validateInventoryForm()">
                <div class="form-row">
                    <div class="form-group">
                        <label for="blood_type">Blood Type <span class="required">*</span></label>
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
                        <small class="form-error" id="blood_error"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity (Units) <span class="required">*</span></label>
                        <input type="number" id="quantity" name="quantity" min="1" max="1000" required>
                        <small class="form-error" id="qty_error"></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="expiration_date">Expiration Date <span class="required">*</span></label>
                        <input type="date" id="expiration_date" name="expiration_date" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" max="<?php echo date('Y-m-d', strtotime('+42 days')); ?>" required>
                        <small class="form-error" id="exp_error"></small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="add_inventory" class="btn btn-primary" style="background: var(--bloodbank-purple);">Add to Inventory</button>
                    <button type="button" onclick="hideAddForm()" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>

        <div class="inventory-stats">
            <div class="stat-card bloodbank-stat">
                <h3>Total Blood Units</h3>
                <p class="stat-number"><?php echo count($inventory); ?></p>
            </div>
            <div class="stat-card bloodbank-stat">
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
            <div class="stat-card bloodbank-stat">
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
            <h3>Blood Bank Inventory</h3>
            <table>
                <thead>
                    <tr>
                        <th>Blood Type</th>
                        <th>Quantity</th>
                        <th>Expiration Date</th>
                        <th>Days Left</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inventory)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No blood inventory found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($inventory as $item): 
                            $days_left = floor((strtotime($item['expiration_date']) - time()) / (60 * 60 * 24));
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['blood_type']); ?></td>
                                <td>
                                    <span class="<?php echo $item['quantity'] < 10 ? 'low-stock' : ''; ?>">
                                        <?php echo $item['quantity']; ?> units
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $days_left < 7 ? 'expiring-soon' : ''; ?>">
                                        <?php echo date('M j, Y', strtotime($item['expiration_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $days_left < 7 ? 'expiring-soon' : ''; ?>">
                                        <?php echo $days_left > 0 ? $days_left . ' days' : 'Expired'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="distributeBlood(<?php echo $item['id']; ?>)" class="btn btn-small" style="background: var(--bloodbank-purple);">Distribute</button>
                                    <button onclick="editInventory(<?php echo $item['id']; ?>)" class="btn btn-small btn-secondary">Edit</button>
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
        
        function validateInventoryForm() {
            // Clear previous error messages
            document.getElementById('blood_error').textContent = '';
            document.getElementById('qty_error').textContent = '';
            document.getElementById('exp_error').textContent = '';
            
            const bloodType = document.getElementById('blood_type').value;
            const quantity = document.getElementById('quantity').value;
            const expirationDate = document.getElementById('expiration_date').value;
            
            let isValid = true;
            
            // Validate blood type
            if (!bloodType) {
                document.getElementById('blood_error').textContent = 'Blood type must be selected.';
                isValid = false;
            }
            
            // Validate quantity
            if (!quantity || isNaN(quantity)) {
                document.getElementById('qty_error').textContent = 'Quantity must be a valid number.';
                isValid = false;
            } else if (quantity < 1) {
                document.getElementById('qty_error').textContent = 'Quantity must be at least 1 unit.';
                isValid = false;
            } else if (quantity > 1000) {
                document.getElementById('qty_error').textContent = 'Quantity cannot exceed 1000 units.';
                isValid = false;
            }
            
            // Validate expiration date
            if (!expirationDate) {
                document.getElementById('exp_error').textContent = 'Expiration date is required.';
                isValid = false;
            } else {
                const selectedDate = new Date(expirationDate);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                
                if (selectedDate <= today) {
                    document.getElementById('exp_error').textContent = 'Expiration date must be in the future.';
                    isValid = false;
                }
                
                // Check if not more than 42 days
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + 42);
                if (selectedDate > maxDate) {
                    document.getElementById('exp_error').textContent = 'Expiration date cannot be more than 42 days from now.';
                    isValid = false;
                }
            }
            
            return isValid;
        }
        
        function distributeBlood(id) {
            alert('Distribute blood item ' + id + ' - This would open distribution form');
            // window.location.href = 'distribute.php?id=' + id;
        }
        
        function editInventory(id) {
            alert('Edit inventory item ' + id + ' - This would open edit form');
            // window.location.href = 'edit_inventory.php?id=' + id;
        }
    </script>
</body>
</html>