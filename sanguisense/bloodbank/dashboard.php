<?php
include '../includes/auth.php';
requireBloodBankAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Check if facility exists before getting stats
if ($facility) {
    $stats = getBloodBankStats($facility['id']);
} else {
    // Default stats if no facility found
    $stats = [
        'total_inventory' => 0,
        'low_stock' => 0,
        'expiring_soon' => 0,
        'total_donations' => 0
    ];
}

// Get low stock items for this blood bank
global $pdo;
if ($facility) {
    $low_stock_items = $pdo->prepare("
        SELECT * FROM inventory 
        WHERE quantity < 10 AND facility_id = ?
        ORDER BY quantity ASC 
        LIMIT 5
    ");
    $low_stock_items->execute([$facility['id']]);
    $low_stock_items = $low_stock_items->fetchAll(PDO::FETCH_ASSOC);
} else {
    $low_stock_items = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Dashboard - SanguiSense</title>
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
            <h1>Blood Bank Management Dashboard</h1>
            <p>Welcome, <?php echo $user['name']; ?> | <?php echo $facility ? $facility['name'] : 'No Facility Assigned'; ?> | Manage blood collection and distribution</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card bloodbank-stat">
                <div class="stat-icon">💊</div>
                <h3>Total Inventory</h3>
                <p class="stat-number"><?php echo $stats['total_inventory']; ?></p>
            </div>
            <div class="stat-card bloodbank-stat">
                <div class="stat-icon">⚠️</div>
                <h3>Low Stock Items</h3>
                <p class="stat-number"><?php echo $stats['low_stock']; ?></p>
            </div>
            <div class="stat-card bloodbank-stat">
                <div class="stat-icon">⏰</div>
                <h3>Expiring Soon</h3>
                <p class="stat-number"><?php echo $stats['expiring_soon']; ?></p>
            </div>
            <div class="stat-card bloodbank-stat">
                <div class="stat-icon">🩸</div>
                <h3>Total Donations</h3>
                <p class="stat-number"><?php echo $stats['total_donations']; ?></p>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="content-grid">
                <div class="content-card">
                    <h3>Low Stock Alert</h3>
                    <div class="data-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Blood Type</th>
                                    <th>Quantity</th>
                                    <th>Expiration</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['blood_type']); ?></td>
                                    <td class="low-stock"><?php echo $item['quantity']; ?> units</td>
                                    <td><?php echo date('M j, Y', strtotime($item['expiration_date'])); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $item['status']; ?>">
                                            <?php echo ucfirst($item['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick="restockItem(<?php echo $item['id']; ?>)" class="btn btn-small" style="background: var(--bloodbank-purple);">Restock</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($low_stock_items)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No low stock items</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="content-card">
                    <h3>Quick Actions</h3>
                    <div class="action-buttons-vertical">
                        <a href="inventory.php?action=add" class="btn btn-primary" style="background: var(--bloodbank-purple);">Update Inventory</a>
                            <a href="blood_requests.php" class="btn btn-secondary">Blood Requests</a>
                            <a href="donations.php" class="btn btn-secondary">Record Donation</a>
                        <a href="distribution.php" class="btn btn-secondary">Distribute Blood</a>
                        <a href="donors.php" class="btn btn-secondary">View Donors</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function restockItem(id) {
            alert('Restock item ' + id + ' - This would open a restock form');
            // Implementation for restocking
        }
    </script>
</body>
</html>