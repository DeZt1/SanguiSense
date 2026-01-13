<?php
include '../includes/auth.php';
requireHospitalAdmin();

$user = getUserData($_SESSION['user_id']);
$facility = getUserFacility($_SESSION['user_id']);

global $pdo;

// Get all facilities for debugging/setup
$allFacilities = $pdo->query("SELECT * FROM facilities WHERE type = 'hospital' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Define hospitals by municipality
$hospitalsByMunicipality = [
    'Cabanatuan City' => ['Premiere Medical Center', 'GoodSam Medical Center', 'Nueva Ecija Doctors Hospital'],
    'Gapan City' => ['GoodSam Medical Center - Gapan Branch'],
    'Palayan City' => ['Palayan City Emergency Hospital'],
    'San Jose City' => ['San Jose City General Hospital'],
    'San Antonio' => ['San Antonio District Hospital'],
    'Guimba' => ['Guimba District Hospital'],
];

// Handle facility assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'assign_existing') {
        $facility_id = intval($_POST['facility_id']);
        try {
            $update = $pdo->prepare("UPDATE facilities SET admin_id = ? WHERE id = ?");
            $update->execute([$_SESSION['user_id'], $facility_id]);
            $success = "Successfully assigned to facility!";
            header("Refresh: 2");
        } catch (Exception $e) {
            $error = "Failed to assign facility: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'create_new') {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        
        if (!$name || !$address || !$city || !$phone || !$email) {
            $error = "All fields are required";
        } else {
            try {
                $insert = $pdo->prepare("INSERT INTO facilities (name, type, address, city, phone, email, admin_id) VALUES (?, 'hospital', ?, ?, ?, ?, ?)");
                $insert->execute([$name, $address, $city, $phone, $email, $_SESSION['user_id']]);
                $success = "Facility created successfully!";
                header("Refresh: 2");
            } catch (Exception $e) {
                $error = "Failed to create facility: " . $e->getMessage();
            }
        }
    }
}

// Refresh facility data
$facility = getUserFacility($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Setup - Hospital Portal</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .facility-setup {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .status-card {
            background: linear-gradient(135deg, var(--hospital-blue) 0%, rgba(33, 150, 243, 0.8) 100%);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 1rem;
        }
        .status-badge.active {
            background: rgba(76, 175, 80, 0.9);
        }
        .status-badge.inactive {
            background: rgba(244, 67, 54, 0.9);
        }
        .form-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-section h3 {
            margin-top: 0;
            color: var(--hospital-blue);
            border-bottom: 2px solid var(--hospital-blue);
            padding-bottom: 1rem;
        }
        .facility-info {
            background: rgba(33, 150, 243, 0.1);
            padding: 1rem;
            border-left: 4px solid var(--hospital-blue);
            border-radius: 4px;
            margin: 1rem 0;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border-left: 4px solid #4CAF50;
            color: #2e7d32;
        }
        .alert-error {
            background: rgba(244, 67, 54, 0.2);
            border-left: 4px solid #f44336;
            color: #c62828;
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container">
        <div class="facility-setup">
            <h1>🏥 Facility Setup</h1>
            <p style="color: #666;">Connect your hospital admin account to a facility</p>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="status-card">
                <h2>Account Status</h2>
                <p><strong>Logged in as:</strong> <?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</p>
                
                <?php if ($facility): ?>
                    <div class="facility-info">
                        <h3>✓ Assigned Facility</h3>
                        <p><strong><?php echo htmlspecialchars($facility['name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($facility['address']); ?>, <?php echo htmlspecialchars($facility['city']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($facility['phone']); ?></p>
                    </div>
                    <span class="status-badge active">✓ FACILITY LINKED</span>
                    <p style="margin-top: 1rem; font-size: 0.9em;">
                        You can now view scheduled donations in <a href="appointments.php" style="color: inherit; text-decoration: underline;">Appointments</a>
                    </p>
                <?php else: ?>
                    <div class="facility-info">
                        <p>⚠️ No facility assigned yet. Please select or create one below.</p>
                    </div>
                    <span class="status-badge inactive">⚠️ NO FACILITY</span>
                <?php endif; ?>
            </div>

            <?php if (!$facility) { ?>
                <!-- Assign existing facility -->
                <div class="form-section">
                    <h3>Option 1: Assign Existing Facility</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="facility_id">Select Hospital</label>
                            <select id="facility_id" name="facility_id" required>
                                <option value="">-- Choose a hospital --</option>
                                <?php 
                                // Group existing facilities by municipality
                                $facilityByMunicipality = [];
                                foreach ($allFacilities as $fac) {
                                    $city = $fac['city'];
                                    if (!isset($facilityByMunicipality[$city])) {
                                        $facilityByMunicipality[$city] = [];
                                    }
                                    $facilityByMunicipality[$city][] = $fac;
                                }
                                
                                // Add predefined hospitals
                                foreach ($hospitalsByMunicipality as $municipality => $hospitals) {
                                    echo '<optgroup label="' . htmlspecialchars($municipality) . '">';
                                    foreach ($hospitals as $hospitalName) {
                                        echo '<option value="">' . htmlspecialchars($hospitalName) . '</option>';
                                    }
                                    // Also show existing facilities for this municipality
                                    $cleanMunicipality = str_replace(' City', '', $municipality);
                                    if (isset($facilityByMunicipality[$cleanMunicipality])) {
                                        foreach ($facilityByMunicipality[$cleanMunicipality] as $fac) {
                                            echo '<option value="' . $fac['id'] . '"> ✓ ' . htmlspecialchars($fac['name']) . '</option>';
                                        }
                                    }
                                    echo '</optgroup>';
                                }
                                
                                // Show any other existing facilities not in the predefined list
                                $otherCities = array_filter(array_keys($facilityByMunicipality), function($city) use ($hospitalsByMunicipality) {
                                    $cityWithSuffix = in_array($city . ' City', array_keys($hospitalsByMunicipality)) ? $city . ' City' : $city;
                                    return !isset($hospitalsByMunicipality[$cityWithSuffix]);
                                });
                                
                                if (!empty($otherCities)) {
                                    foreach ($otherCities as $city) {
                                        echo '<optgroup label="' . htmlspecialchars($city) . '">';
                                        foreach ($facilityByMunicipality[$city] as $fac) {
                                            echo '<option value="' . $fac['id'] . '">' . htmlspecialchars($fac['name']) . '</option>';
                                        }
                                        echo '</optgroup>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" name="action" value="assign_existing">
                        <button type="submit" class="btn btn-primary" style="background: var(--hospital-blue);">Assign Facility</button>
                    </form>
                </div>

                <!-- Create new facility -->
                <div class="form-section">
                    <h3>Option 2: Create New Facility</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label for="name">Hospital Name</label>
                            <input id="name" name="name" placeholder="e.g., City General Hospital" required>
                        </div>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input id="address" name="address" placeholder="Full street address" required>
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <select id="city" name="city" required>
                                <option value="">-- Select City --</option>
                                <option value="Cabanatuan">Cabanatuan</option>
                                <option value="Gapan">Gapan</option>
                                <option value="Palayan">Palayan</option>
                                <option value="San Jose City">San Jose City</option>
                                <option value="San Antonio">San Antonio</option>
                                <option value="Guimba">Guimba</option>
                                <option value="San Fernando">San Fernando</option>
                                <option value="Talugtug">Talugtug</option>
                                <option value="Santo Domingo">Santo Domingo</option>
                                <option value="Pantabangan">Pantabangan</option>
                                <option value="Aliaga">Aliaga</option>
                                <option value="Munoz">Munoz</option>
                                <option value="Llanera">Llanera</option>
                                <option value="Penaranda">Penaranda</option>
                                <option value="Gabaldon">Gabaldon</option>
                                <option value="Carranglan">Carranglan</option>
                                <option value="General Tinio">General Tinio</option>
                                <option value="Baloc">Baloc</option>
                                <option value="Quezon">Quezon</option>
                                <option value="Science City of Munoz">Science City of Munoz</option>
                                <option value="Carangalan">Carangalan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" placeholder="Contact number" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" placeholder="contact@hospital.com" required>
                        </div>
                        <input type="hidden" name="action" value="create_new">
                        <button type="submit" class="btn btn-primary" style="background: var(--hospital-blue);">Create Facility</button>
                    </form>
                </div>
            <?php } ?>

            <!-- System Info -->
            <div class="form-section" style="background: rgba(142, 68, 173, 0.08); border: 2px solid var(--bloodbank-purple); border-radius: 8px; padding: 1.5rem;">
                <h3 style="color: #ffffff; margin-top: 0;">System Information</h3>
                <p style="color: #ffffff; font-weight: 500; margin: 0.75rem 0;"><strong>Your User ID:</strong> <span style="color: #ffd700; font-weight: 600;"><?php echo $_SESSION['user_id']; ?></span></p>
                <p style="color: #ffffff; font-weight: 500; margin: 0.75rem 0;"><strong>Facility Lookup Result:</strong> <span style="color: <?php echo $facility ? '#27ae60' : '#e74c3c'; ?>; font-weight: 600;"><?php echo $facility ? "✓ Found (ID: " . $facility['id'] . ")" : "✗ Not found"; ?></span></p>
                <p style="color: #ffffff; font-weight: 500; margin: 0.75rem 0;"><strong>Total Hospitals in System:</strong> <span style="color: #ffd700; font-weight: 600;"><?php echo count($allFacilities); ?></span></p>
                <p style="font-size: 0.95em; color: #ffffff; margin-top: 1rem; line-height: 1.5;">
                    ℹ️ If you've just registered, you may need to manually assign a facility using the forms above.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
