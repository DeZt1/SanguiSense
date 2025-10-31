<?php
include '../includes/auth.php';
requireBloodBankAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

// Get donations for this blood bank
global $pdo;
if ($facility) {
    $donations = $pdo->prepare("
        SELECT d.*, u.name as donor_name, u.blood_type, u.phone 
        FROM donations d 
        JOIN users u ON d.donor_id = u.id 
        WHERE d.facility_id = ? 
        ORDER BY d.donation_date DESC
    ");
    $donations->execute([$facility['id']]);
    $donations = $donations->fetchAll(PDO::FETCH_ASSOC);
} else {
    $donations = [];
}

// Handle new donation form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_donation'])) {
    $donor_id = $_POST['donor_id'];
    $blood_type = $_POST['blood_type'];
    $donation_date = $_POST['donation_date'];
    $quantity = $_POST['quantity'];
    
    try {
        // Record the donation
        $stmt = $pdo->prepare("INSERT INTO donations (donor_id, facility_id, blood_type, donation_date, quantity, status) VALUES (?, ?, ?, ?, ?, 'completed')");
        $stmt->execute([$donor_id, $facility['id'], $blood_type, $donation_date, $quantity]);
        
        // Update donor's last donation date
        $update_stmt = $pdo->prepare("UPDATE users SET last_donation_date = ? WHERE id = ?");
        $update_stmt->execute([$donation_date, $donor_id]);
        
        // Add to inventory
        $inventory_stmt = $pdo->prepare("INSERT INTO inventory (facility_id, blood_type, quantity, expiration_date) VALUES (?, ?, ?, DATE_ADD(?, INTERVAL 42 DAY))");
        $inventory_stmt->execute([$facility['id'], $blood_type, $quantity, $donation_date]);
        
        $success = "Donation recorded successfully and added to inventory!";
        header("Location: donations.php?success=1");
        exit();
    } catch(PDOException $e) {
        $error = "Failed to record donation: " . $e->getMessage();
    }
}

// Get donors for dropdown
$all_donors = $pdo->query("SELECT id, name, blood_type FROM users WHERE user_type = 'donor' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - Blood Bank Portal</title>
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
            <h1>Donation Management</h1>
            <p>Record and manage blood donations</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Donation recorded successfully!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h3>Record New Donation</h3>
                <div class="admin-form">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="donor_id">Donor</label>
                                <select id="donor_id" name="donor_id" required>
                                    <option value="">Select Donor</option>
                                    <?php foreach ($all_donors as $donor): ?>
                                        <option value="<?php echo $donor['id']; ?>">
                                            <?php echo htmlspecialchars($donor['name'] . ' (' . $donor['blood_type'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
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
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="donation_date">Donation Date</label>
                                <input type="date" id="donation_date" name="donation_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="quantity">Quantity (Units)</label>
                                <input type="number" id="quantity" name="quantity" min="1" max="2" value="1" required>
                                <small>Typically 1 unit (450ml) per donation</small>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" name="record_donation" class="btn btn-primary" style="background: var(--bloodbank-purple);">Record Donation</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="content-card">
                <h3>Recent Donations</h3>
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Donor</th>
                                <th>Blood Type</th>
                                <th>Date</th>
                                <th>Quantity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($donations, 0, 5) as $donation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                                <td><?php echo htmlspecialchars($donation['blood_type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                                <td><?php echo $donation['quantity']; ?> unit(s)</td>
                                <td>
                                    <span class="status-badge status-<?php echo $donation['status']; ?>">
                                        <?php echo ucfirst($donation['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($donations)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No donations recorded</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (count($donations) > 5): ?>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="donations.php?view=all" class="btn btn-secondary">View All Donations</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-card">
            <h3>All Donations</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Donor</th>
                            <th>Blood Type</th>
                            <th>Donation Date</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td>#<?php echo $donation['id']; ?></td>
                            <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                            <td><?php echo htmlspecialchars($donation['blood_type']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                            <td><?php echo $donation['quantity']; ?> unit(s)</td>
                            <td>
                                <span class="status-badge status-<?php echo $donation['status']; ?>">
                                    <?php echo ucfirst($donation['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button onclick="viewDonation(<?php echo $donation['id']; ?>)" class="btn btn-small" style="background: var(--bloodbank-purple);">View</button>
                                <button onclick="editDonation(<?php echo $donation['id']; ?>)" class="btn btn-small btn-secondary">Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($donations)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No donations found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Auto-fill blood type when donor is selected
        document.getElementById('donor_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const donorText = selectedOption.text;
                const bloodType = donorText.match(/\(([^)]+)\)/);
                if (bloodType) {
                    document.getElementById('blood_type').value = bloodType[1];
                }
            }
        });

        function viewDonation(id) {
            alert('View donation details ' + id);
            // window.location.href = 'donation_details.php?id=' + id;
        }
        
        function editDonation(id) {
            alert('Edit donation ' + id);
            // window.location.href = 'edit_donation.php?id=' + id;
        }
    </script>
</body>
</html>