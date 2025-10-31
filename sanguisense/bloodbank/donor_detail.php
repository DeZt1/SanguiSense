<?php
include '../includes/auth.php';
requireBloodBankAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('donors.php');
}

$donor_id = (int)$_GET['id'];
global $pdo;
$donor = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'donor'");
$donor->execute([$donor_id]);
$donor = $donor->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    redirect('donors.php');
}

// Get donation history
$history = $pdo->prepare("SELECT * FROM donations WHERE donor_id = ? ORDER BY donation_date DESC");
$history->execute([$donor_id]);
$history = $history->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Details - <?php echo htmlspecialchars($donor['name']); ?></title>
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
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="donors.php" class="nav-link">Donors</a>
                <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Donor Details</h1>
            <p>Details and donation history for <?php echo htmlspecialchars($donor['name']); ?></p>
        </div>

        <div class="content-grid">
            <div class="content-card">
                <h3>Profile</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($donor['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($donor['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($donor['phone']); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($donor['city']); ?></p>
                <p><strong>Blood Type:</strong> <?php echo htmlspecialchars($donor['blood_type']); ?></p>
                <div style="margin-top:1rem;">
                    <button onclick="contactDonor(<?php echo $donor['id']; ?>, '<?php echo htmlspecialchars($donor['email'], ENT_QUOTES); ?>')" class="btn btn-primary">Contact Donor</button>
                    <a href="donors.php" class="btn btn-secondary">Back to Donors</a>
                </div>
            </div>

            <div class="content-card">
                <h3>Donation History</h3>
                <div class="data-table">
                    <table>
                        <thead><tr><th>Date</th><th>Facility</th><th>Blood Type</th><th>Quantity</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if (empty($history)): ?>
                                <tr><td colspan="5" style="text-align:center;">No donation history</td></tr>
                            <?php else: ?>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($h['donation_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($h['facility_id']); ?></td>
                                        <td><?php echo htmlspecialchars($h['blood_type']); ?></td>
                                        <td><?php echo (int)$h['quantity']; ?></td>
                                        <td><?php echo ucfirst($h['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function contactDonor(id, email) {
            if (email) {
                const subject = encodeURIComponent('Regarding your blood donation');
                const body = encodeURIComponent('Hello,\n\nWe would like to contact you regarding your blood donations and upcoming opportunities.\n\nRegards,\nSanguiSense Blood Bank');
                window.open('mailto:' + email + '?subject=' + subject + '&body=' + body, '_blank');
            }

            try {
                const resp = await fetch('contact_donor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + encodeURIComponent(id)
                });
                const data = await resp.json();
                if (data.success) {
                    alert('Contact recorded and notification sent to donor.');
                    location.reload();
                } else {
                    alert('Failed to record contact: ' + (data.message || 'unknown'));
                }
            } catch (err) {
                console.error(err);
                alert('Failed to contact donor (network error).');
            }
        }
    </script>
</body>
</html>
