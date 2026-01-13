<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireHospitalAdmin();

// Get facility for this admin
$facility = getUserFacility($_SESSION['user_id']);

// Filtering & Pagination
$appointments = [];
$q = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 9;
$offset = ($page - 1) * $per_page;

if ($facility) {
    // Build WHERE clauses
    $where = "d.facility_id = ? AND d.status IN ('scheduled','pending','approved')";
    $params = [$facility['id']];

    if ($status_filter !== 'all') {
        $where = "d.facility_id = ? AND d.status = ?";
        $params = [$facility['id'], $status_filter];
    }

    if ($q !== '') {
        $where .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? )";
        $like = '%' . $q . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Count total
    $countSql = "SELECT COUNT(*) FROM donations d JOIN users u ON d.donor_id = u.id WHERE " . $where;
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    // Fetch paginated rows
    $sql = "SELECT d.id, d.donor_id, d.facility_id, d.donation_date, d.blood_type AS donation_blood_type, d.status, d.quantity, u.name AS donor_name, u.email AS donor_email, u.phone AS donor_phone, u.city AS donor_city, u.blood_type AS donor_blood_type, u.eligibility_status FROM donations d JOIN users u ON d.donor_id = u.id WHERE " . $where . " ORDER BY d.donation_date ASC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    // append limit/offset to params
    $execParams = $params;
    $execParams[] = $per_page;
    $execParams[] = $offset;
    $stmt->execute($execParams);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_pages = ($per_page > 0) ? (int) ceil($total / $per_page) : 1;
} else {
    $total = 0;
    $total_pages = 1;
}

// Handle accept/decline actions (same small handler as other file)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['action'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $action = $_POST['action'];
    if ($action === 'accept') {
        $updateStmt = $pdo->prepare("UPDATE donations SET status = 'approved' WHERE id = ?");
        $updateStmt->execute([$appointment_id]);
    } elseif ($action === 'decline') {
        $updateStmt = $pdo->prepare("UPDATE donations SET status = 'declined' WHERE id = ?");
        $updateStmt->execute([$appointment_id]);
    }
    header('Location: schedules_appointment.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Appointments - Hospital</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container schedules-container">
        <div class="dashboard-header">
            <h1>Scheduled Appointments</h1>
            <p>Manage scheduled donor appointments — balanced table view</p>
        </div>

        <div class="data-table">
            <h3>Scheduled Appointments</h3>

            <form method="GET" class="search-filter" style="margin-bottom:0.5rem;">
                <input type="text" name="q" placeholder="Search name, email or phone" value="<?php echo htmlspecialchars($q); ?>">
                <select name="status">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                </select>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>

            <?php if (empty($appointments)): ?>
                <div style="text-align:center; padding:2rem; color:#666;">No scheduled appointments found.</div>
            <?php else: ?>
                <div class="donors-grid" style="margin-top:1rem;">
                    <?php foreach ($appointments as $appt): ?>
                        <div class="donor-card">
                            <div class="donor-card-header">
                                <div class="donor-info">
                                    <h4><?php echo htmlspecialchars($appt['donor_name']); ?></h4>
                                    <p class="donor-blood-type"><?php echo htmlspecialchars($appt['donor_blood_type']); ?></p>
                                </div>
                                <span class="status-badge status-<?php echo $appt['status']; ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </div>

                            <div class="donor-card-body">
                                <div class="donor-detail">
                                    <span class="label">Email</span>
                                    <span class="value"><?php echo htmlspecialchars($appt['donor_email']); ?></span>
                                </div>
                                <div class="donor-detail">
                                    <span class="label">Phone</span>
                                    <span class="value"><?php echo htmlspecialchars($appt['donor_phone'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="donor-detail">
                                    <span class="label">City</span>
                                    <span class="value"><?php echo htmlspecialchars($appt['donor_city'] ?: 'N/A'); ?></span>
                                </div>
                                <div class="donor-stats-row">
                                    <div class="stat">
                                        <span class="stat-label">Donation Date</span>
                                        <span class="stat-value"><?php echo date('M j, Y', strtotime($appt['donation_date'])); ?></span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-label">Quantity</span>
                                        <span class="stat-value"><?php echo htmlspecialchars($appt['quantity'] ?? 1); ?> unit(s)</span>
                                    </div>
                                </div>
                            </div>

                            <div class="donor-card-actions">
                                <?php if ($appt['status'] === 'scheduled' || $appt['status'] === 'pending'): ?>
                                    <form method="POST" style="display: flex; gap: 0.5rem; width: 100%;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>">
                                        <button type="submit" name="action" value="accept" class="btn btn-primary" style="flex: 1;">Accept</button>
                                        <button type="submit" name="action" value="decline" class="btn btn-secondary" style="flex: 1;">Decline</button>
                                    </form>
                                <?php elseif ($appt['status'] === 'approved'): ?>
                                    <button class="btn btn-secondary" disabled style="width: 100%;">Approved</button>
                                <?php elseif ($appt['status'] === 'declined'): ?>
                                    <button class="btn btn-secondary" disabled style="width: 100%;">Declined</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php
                    $baseUrl = strtok($_SERVER['REQUEST_URI'], '?');
                    parse_str($_SERVER['QUERY_STRING'] ?? '', $qs);
                    for ($p = 1; $p <= max(1, $total_pages); $p++):
                        $qs['page'] = $p;
                        $link = $baseUrl . '?' . http_build_query($qs);
                    ?>
                        <?php if ($p === $page): ?>
                            <span class="current"><?php echo $p; ?></span>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($link); ?>"><?php echo $p; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
