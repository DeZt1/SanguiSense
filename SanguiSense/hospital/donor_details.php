<?php
include '../includes/auth.php';
requireHospitalAdmin();

if (!isset($_GET['id'])) {
    header("Location: donors.php");
    exit();
}

$donor_id = $_GET['id'];

// Get donor details
global $pdo;
$donor_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'donor'");
$donor_stmt->execute([$donor_id]);
$donor = $donor_stmt->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    header("Location: donors.php");
    exit();
}

// Get donation history
$donations_stmt = $pdo->prepare("
    SELECT * FROM donations 
    WHERE donor_id = ? 
    ORDER BY donation_date DESC
");
$donations_stmt->execute([$donor_id]);
$donations = $donations_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get donation statistics
$stats_stmt = $pdo->prepare("
    SELECT COUNT(*) as total_donations, 
           MAX(donation_date) as last_donation,
           SUM(quantity) as total_units
    FROM donations 
    WHERE donor_id = ? AND status = 'fulfilled'
");
$stats_stmt->execute([$donor_id]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Details - Hospital Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Donor Details</h1>
            <p>View detailed information about donor and donation history</p>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <h3>Donor Information</h3>
                <div class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;"><?php echo htmlspecialchars($donor['name']); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;"><?php echo htmlspecialchars($donor['email']); ?></p>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Blood Type</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;"><?php echo htmlspecialchars($donor['blood_type']); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;"><?php echo htmlspecialchars($donor['phone']); ?></p>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>City</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;"><?php echo htmlspecialchars($donor['city']); ?></p>
                        </div>
                        <div class="form-group">
                            <label>Registration Date</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;"><?php echo date('M j, Y', strtotime($donor['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h3>Donation Statistics</h3>
                <div class="stats-grid" style="grid-template-columns: 1fr; gap: 1rem;">
                    <div class="stat-card hospital-stat">
                        <h3>Total Donations</h3>
                        <p class="stat-number"><?php echo $stats['total_donations'] ?? 0; ?></p>
                    </div>
                    <div class="stat-card hospital-stat">
                        <h3>Total Units Donated</h3>
                        <p class="stat-number"><?php echo $stats['total_units'] ?? 0; ?></p>
                    </div>
                    <div class="stat-card hospital-stat">
                        <h3>Last Donation</h3>
                        <p class="stat-number" style="font-size: 1.2rem;">
                            <?php 
                            if ($stats['last_donation']) {
                                echo date('M j, Y', strtotime($stats['last_donation']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                
                <!-- Eligibility status removed from UI (kept server-side logic intact) -->
            </div>
        </div>

        <div class="content-card">
            <h3>Donation History</h3>
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Donation ID</th>
                            <th>Date</th>
                            <th>Blood Type</th>
                            <th>Quantity</th>
                            <th>Facility</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                        <tr>
                            <td>#D<?php echo str_pad($donation['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo date('M j, Y', strtotime($donation['donation_date'])); ?></td>
                            <td><?php echo htmlspecialchars($donation['blood_type']); ?></td>
                            <td><?php echo $donation['quantity']; ?> units</td>
                            <td>
                                <?php
                                $facility_stmt = $pdo->prepare("SELECT name FROM facilities WHERE id = ?");
                                $facility_stmt->execute([$donation['facility_id']]);
                                $facility = $facility_stmt->fetch(PDO::FETCH_ASSOC);
                                echo $facility ? htmlspecialchars($facility['name']) : 'Unknown';
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $donation['status']; ?>">
                                    <?php echo ucfirst($donation['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($donations)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No donation history found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="form-actions">
            <a href="donors.php" class="btn btn-secondary">Back to Donors</a>
            <button onclick="contactDonor(<?php echo $donor_id; ?>)" class="btn btn-primary" style="background: var(--hospital-blue);">Contact Donor</button>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function contactDonor(id) {
    window.location.href = 'contact_donor.php?id=' + id;
}
    </script>
</body>
</html>