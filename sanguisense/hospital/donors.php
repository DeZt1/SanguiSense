<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireHospitalAdmin();

// Get facility for this admin
$facility = getUserFacility($_SESSION['user_id']);

// Get only donors who have fulfilled (or legacy 'completed') donations at THIS facility
global $pdo;
$donors_query = "SELECT DISTINCT u.* 
    FROM users u
    JOIN donations d ON u.id = d.donor_id
    WHERE u.user_type = 'donor' 
    AND d.facility_id = ? 
    AND d.status IN ('fulfilled','completed')
    AND (u.eligibility_status IS NULL OR u.eligibility_status != 'ineligible')
    ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($donors_query);
$stmt->execute([$facility['id']]);
$donors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get donation statistics (treat both 'fulfilled' and legacy 'completed' as fulfilled)
$stats_query = "SELECT u.id, u.name,
           COUNT(CASE WHEN d.status IN ('fulfilled','completed') THEN 1 END) as donation_count,
           MAX(CASE WHEN d.status IN ('fulfilled','completed') THEN d.donation_date ELSE NULL END) as last_donation,
           COALESCE(SUM(CASE WHEN d.status IN ('fulfilled','completed') THEN d.quantity ELSE 0 END), 0) as total_quantity
    FROM users u
    LEFT JOIN donations d ON u.id = d.donor_id AND d.facility_id = ?
    WHERE u.user_type = 'donor'
    GROUP BY u.id";

$statsStmt = $pdo->prepare($stats_query);
$statsStmt->execute([$facility['id']]);
$donation_stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
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
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

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
            
            <?php if (empty($donors)): ?>
                <div style="text-align: center; padding: 3rem; color: #999;">
                    <p>No donors found</p>
                </div>
            <?php else: ?>
                <div class="donors-grid">
                    <?php foreach ($donors as $donor): 
                        $stats = current(array_filter($donation_stats, function($stat) use ($donor) {
                            return $stat['id'] == $donor['id'];
                        }));
                        
                        $last_donation = $stats['last_donation'] ?? null;
                        
                        // Check eligibility_status from database first
                        $eligible = true;
                        if ($donor['eligibility_status'] === 'ineligible') {
                            $eligible = false;
                        } else if ($last_donation && $donor['eligibility_status'] !== 'eligible') {
                            // Only check 56-day rule if eligibility_status is not explicitly set to 'eligible'
                            $next_donation_date = date('Y-m-d', strtotime($last_donation . ' + 56 days'));
                            if (strtotime($next_donation_date) > time()) {
                                $eligible = false;
                            }
                        }
                    ?>
                        <div class="donor-card">
                            <div class="donor-card-header">
                                <div class="donor-info">
                                    <h4><?php echo htmlspecialchars($donor['name']); ?></h4>
                                    <p class="donor-blood-type"><?php echo htmlspecialchars($donor['blood_type']); ?></p>
                                </div>
                            </div>
                            
                            <div class="donor-card-body">
                                <div class="donor-detail">
                                    <span class="label">Email</span>
                                    <span class="value"><?php echo htmlspecialchars($donor['email']); ?></span>
                                </div>
                                <div class="donor-detail">
                                    <span class="label">Phone</span>
                                    <span class="value"><?php echo htmlspecialchars($donor['phone']); ?></span>
                                </div>
                                <div class="donor-detail">
                                    <span class="label">City</span>
                                    <span class="value"><?php echo htmlspecialchars($donor['city']); ?></span>
                                </div>
                                <div class="donor-stats-row">
                                    <div class="stat">
                                        <span class="stat-label">Donations</span>
                                        <span class="stat-value"><?php echo $stats['donation_count'] ?? 0; ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Last Donation</span>
                                        <span class="stat-value"><?php echo ($stats['last_donation'] ? date('M j, Y', strtotime($stats['last_donation'])) : 'Never'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="donor-card-actions">
                                <button onclick="viewDonor(<?php echo $donor['id']; ?>)" class="btn btn-small btn-primary">View Details</button>
                                <button onclick="contactDonor(<?php echo $donor['id']; ?>)" class="btn btn-small btn-secondary">Contact</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function viewDonor(id) {
    window.location.href = 'donor_details.php?id=' + id;
}
        
        function contactDonor(id) {
    window.location.href = 'contact_donor.php?id=' + id;
}
    </script>
</body>
</html>