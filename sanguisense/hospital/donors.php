<?php
include '../includes/auth.php';
requireHospitalAdmin();

// Get all donors
global $pdo;
$donors = $pdo->query("SELECT * FROM users WHERE user_type = 'donor' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get donation statistics
$donation_stats = $pdo->query("
    SELECT u.id, u.name, COUNT(d.id) as donation_count, MAX(d.donation_date) as last_donation
    FROM users u 
    LEFT JOIN donations d ON u.id = d.donor_id 
    WHERE u.user_type = 'donor' 
    GROUP BY u.id
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Management - Hospital Portal</title>
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
                <a href="inventory.php" class="nav-link">Blood Inventory</a>
                <a href="donors.php" class="nav-link active">Donors</a>
                <a href="appointments.php" class="nav-link">Appointments</a>
                <a href="blood_requests.php" class="nav-link">Blood Requests</a>
                <a href="analytics.php" class="nav-link">Analytics</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Donor Management</h1>
            <p>Manage donor information and track donation history</p>
        </div>

        <div class="donor-stats">
            <div class="stat-card hospital-stat">
                <h3>Total Donors</h3>
                <p class="stat-number"><?php echo count($donors); ?></p>
            </div>
            <div class="stat-card hospital-stat">
                <h3>Active Donors</h3>
                <p class="stat-number">
                    <?php
                    $active_donors = array_filter($donation_stats, function($stat) {
                        return $stat['donation_count'] > 0;
                    });
                    echo count($active_donors);
                    ?>
                </p>
            </div>
            <div class="stat-card hospital-stat">
                <h3>New This Month</h3>
                <p class="stat-number">
                    <?php
                    $new_this_month = array_filter($donors, function($donor) {
                        return strtotime($donor['created_at']) >= strtotime('first day of this month');
                    });
                    echo count($new_this_month);
                    ?>
                </p>
            </div>
        </div>

        <div class="data-table">
            <h3>All Donors</h3>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Blood Type</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th>Donations</th>
                        <th>Last Donation</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($donors)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center;">No donors found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($donors as $donor): 
                            $stats = current(array_filter($donation_stats, function($stat) use ($donor) {
                                return $stat['id'] == $donor['id'];
                            }));
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donor['name']); ?></td>
                                <td><?php echo htmlspecialchars($donor['email']); ?></td>
                                <td><?php echo htmlspecialchars($donor['blood_type']); ?></td>
                                <td><?php echo htmlspecialchars($donor['phone']); ?></td>
                                <td><?php echo htmlspecialchars($donor['city']); ?></td>
                                <td><?php echo $stats['donation_count'] ?? 0; ?></td>
                                <td>
                                    <?php 
                                    if ($stats['last_donation']) {
                                        echo date('M j, Y', strtotime($stats['last_donation']));
                                    } else {
                                        echo 'Never';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $last_donation = $stats['last_donation'] ?? null;
                                    $eligible = true;
                                    
                                    if ($last_donation) {
                                        $next_donation_date = date('Y-m-d', strtotime($last_donation . ' + 56 days'));
                                        if (strtotime($next_donation_date) > time()) {
                                            $eligible = false;
                                        }
                                    }
                                    ?>
                                    <span class="status-badge status-<?php echo $eligible ? 'eligible' : 'ineligible'; ?>">
                                        <?php echo $eligible ? 'Eligible' : 'Ineligible'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="viewDonor(<?php echo $donor['id']; ?>)" class="btn btn-small" style="background: var(--hospital-blue);">View</button>
                                    <button onclick="contactDonor(<?php echo $donor['id']; ?>)" class="btn btn-small btn-secondary">Contact</button>
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
        function viewDonor(id) {
            alert('View donor details ' + id);
            // window.location.href = 'donor_details.php?id=' + id;
        }
        
        function contactDonor(id) {
            alert('Contact donor ' + id);
            // window.location.href = 'contact_donor.php?id=' + id;
        }
    </script>
</body>
</html>