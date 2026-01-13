<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';

global $pdo;

// Get list of facilities for dropdown (New Ecija hospitals)
$facilities = [];
$stmt = $pdo->prepare("SELECT id, name, address, city FROM facilities WHERE type = 'hospital' ORDER BY name");
$stmt->execute();
$facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define hospitals by municipality
$hospitalsByMunicipality = [
    'Cabanatuan City' => ['Premiere Medical Center', 'GoodSam Medical Center', 'Nueva Ecija Doctors Hospital'],
    'Gapan City' => ['GoodSam Medical Center - Gapan Branch'],
    'Palayan City' => ['Palayan City Emergency Hospital'],
    'San Jose City' => ['San Jose City General Hospital'],
    'San Antonio' => ['San Antonio District Hospital'],
    'Guimba' => ['Guimba District Hospital'],
];

// If the facilities table is currently empty (e.g., data not imported),
// fall back to the canonical in-memory list so the dropdown shows options.
$use_fallback_facilities = false;
if (empty($facilities)) {
    // get_facilities comes from includes/locations.php via functions.php
    $fallback = get_facilities(['type' => 'hospital']);
    if (!empty($fallback)) {
        $use_fallback_facilities = true;
        $facilities = $fallback; // note: these entries don't have DB ids
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $facility_id_raw = $_POST['facility_id'] ?? '0';
    $facility_name = trim($_POST['facility_name'] ?? '');
    $facility_address = trim($_POST['facility_address'] ?? '');
    $facility_city = trim($_POST['facility_city'] ?? '');
    // seed values for canonical fallback (if used)
    $seed_name = '';
    $seed_city = '';

    // Determine facility_id: numeric DB id, or a seeded selection string like 'seed|Name|City'
    $facility_id = 0;
    if (is_string($facility_id_raw) && ctype_digit($facility_id_raw)) {
        $facility_id = intval($facility_id_raw);
    } elseif (is_string($facility_id_raw) && strpos($facility_id_raw, 'seed|') === 0) {
        // Format: seed|Name|City - remember seed data, actual DB insert will be done inside the transaction
        $parts = explode('|', $facility_id_raw, 3);
        $seed_name = '';
        $seed_city = '';
        if (count($parts) === 3) {
            $seed_name = trim($parts[1]);
            $seed_city = trim($parts[2]);
        }
    } else {
        // fallback to integer conversion
        $facility_id = intval($facility_id_raw);
    }

    if (!$name || !$email || !$password) {
        $error = 'Please fill in all required fields';
    } elseif ($facility_id == 0 && empty($seed_name) && (!$facility_name || !$facility_address || !$facility_city)) {
        $error = 'Please select an existing facility or provide details for a new one';
    } else {
        // check existing email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists';
        } else {
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Insert user with city/address if available
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $user_city = '';
                $user_address = '';
                
                // Determine city and address for user record
                if ($facility_id > 0) {
                    // Get facility details for city/address
                    $fac_stmt = $pdo->prepare("SELECT city, address FROM facilities WHERE id = ?");
                    $fac_stmt->execute([$facility_id]);
                    $fac = $fac_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($fac) {
                        $user_city = $fac['city'] ?? '';
                        $user_address = $fac['address'] ?? '';
                    }
                } elseif (!empty($seed_name) && !empty($seed_city)) {
                    $user_city = $seed_city;
                    $user_address = '';
                } else {
                    $user_city = $facility_city;
                    $user_address = $facility_address;
                }
                
                $insert = $pdo->prepare("INSERT INTO users (name, email, password, user_type, phone, city, address, created_at) VALUES (?, ?, ?, 'hospital_admin', ?, ?, ?, NOW())");
                $insert->execute([$name, $email, $hash, $phone, $user_city, $user_address]);
                $user_id = $pdo->lastInsertId();
                
                // Handle facility
                if ($facility_id > 0) {
                    // Link existing facility to admin
                    $update = $pdo->prepare("UPDATE facilities SET admin_id = ? WHERE id = ?");
                    $update->execute([$user_id, $facility_id]);
                } elseif (!empty($seed_name) && !empty($seed_city)) {
                    // Create facility based on canonical seed selected from fallback list
                    $insert_fac = $pdo->prepare("INSERT INTO facilities (name, type, address, city, phone, email, admin_id) VALUES (?, 'hospital', ?, ?, ?, ?, ?)");
                    $insert_fac->execute([$seed_name, $facility_address ?: $seed_city, $seed_city, $phone, $email, $user_id]);
                } else {
                    // Create new facility from form inputs
                    $insert_fac = $pdo->prepare("INSERT INTO facilities (name, type, address, city, phone, email, admin_id) VALUES (?, 'hospital', ?, ?, ?, ?, ?)");
                    $insert_fac->execute([$facility_name, $facility_address, $facility_city, $phone, $email, $user_id]);
                }
                
                $pdo->commit();
                header('Location: login.php?registered=1');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register - Hospital Portal</title>
    <link rel="stylesheet" href="css/auth.css">
    <style>
        .facility-toggle {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(0,0,0,0.15);
            border-radius: 8px;
        }
        .facility-toggle button {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid transparent;
            background: #cccccc;
            color: #666666;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
            z-index: 5;
            font-weight: 700;
            font-size: 1rem;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .facility-toggle button:hover {
            background: #bbbbbb;
            border-color: #999999;
        }
        .facility-toggle button.active {
            border-color: var(--hospital-blue, #1e88e5);
            background: var(--hospital-blue, #1e88e5);
            color: #ffffff;
            box-shadow: 0 4px 8px rgba(30, 136, 229, 0.4);
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }
        .facility-section {
            display: none;
            position: relative;
            z-index: 1;
            width: 100%;
            overflow: visible;
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(30, 136, 229, 0.02);
            border-radius: 8px;
            border: 1px solid rgba(30, 136, 229, 0.05);
        }
        .facility-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <script>
        function toggleFacility(mode, ev) {
            // safe event handling and defensive DOM updates
            try {
                document.querySelectorAll('.facility-toggle button').forEach(b => b.classList.remove('active'));
                if (ev && ev.currentTarget) ev.currentTarget.classList.add('active');
            } catch (e) { /* ignore */ }
            document.querySelectorAll('.facility-section').forEach(s => s.classList.remove('active'));
            var el = document.getElementById(mode + '-section');
            if (el) el.classList.add('active');
        }
    </script>
</head>
<body>
    <div class="background-animation"></div>
    <div class="auth-container">
        <div class="auth-form">
            <h2>🏥 Hospital Admin Register</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" required>
                </div>

                <label style="margin: 1rem 0; display: block;">Facility</label>
                <div class="facility-toggle">
                    <button type="button" class="active" onclick="toggleFacility('existing', event)">Select Existing</button>
                    <button type="button" onclick="toggleFacility('new', event)">Create New</button>
                </div>

                <div id="existing-section" class="facility-section active">
                    <div class="form-group">
                        <label for="facility_id">Hospital</label>
                        <select id="facility_id" name="facility_id" onchange="document.querySelector('button[type=submit]').disabled = this.value == 0">
                            <option value="0">-- Select a Hospital --</option>
                            <?php 
                            // Group existing facilities by municipality
                            $facilityByMunicipality = [];
                            foreach ($facilities as $fac) {
                                $city = $fac['city'];
                                if (!isset($facilityByMunicipality[$city])) {
                                    $facilityByMunicipality[$city] = [];
                                }
                                $facilityByMunicipality[$city][] = $fac;
                            }
                            
                            // Add predefined hospitals, avoiding duplicates
                            foreach ($hospitalsByMunicipality as $municipality => $hospitals) {
                                echo '<optgroup label="' . htmlspecialchars($municipality) . '">';
                                
                                // Collect all hospital names we've already added (to avoid duplicates)
                                $addedHospitals = [];
                                
                                // First, add predefined hospitals
                                foreach ($hospitals as $hospitalName) {
                                    echo '<option value="seed|' . htmlspecialchars($hospitalName) . '|' . htmlspecialchars($municipality) . '">' . htmlspecialchars($hospitalName) . '</option>';
                                    $addedHospitals[] = strtolower(trim($hospitalName));
                                }
                                
                                // Then add existing facilities for this municipality (if not already added)
                                $cleanMunicipality = str_replace(' City', '', $municipality);
                                if (isset($facilityByMunicipality[$cleanMunicipality])) {
                                    foreach ($facilityByMunicipality[$cleanMunicipality] as $fac) {
                                        $facNameLower = strtolower(trim($fac['name']));
                                        // Only add if it's not a duplicate
                                        if (!in_array($facNameLower, $addedHospitals)) {
                                            echo '<option value="' . $fac['id'] . '">✓ ' . htmlspecialchars($fac['name']) . '</option>';
                                            $addedHospitals[] = $facNameLower;
                                        }
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
                                        if (isset($fac['id']) && is_numeric($fac['id'])) {
                                            echo '<option value="' . $fac['id'] . '">' . htmlspecialchars($fac['name']) . '</option>';
                                        } else {
                                            echo '<option value="seed|' . htmlspecialchars($fac['name']) . '|' . htmlspecialchars($fac['city']) . '">' . htmlspecialchars($fac['name']) . '</option>';
                                        }
                                    }
                                    echo '</optgroup>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <div id="new-section" class="facility-section">
                    <div class="form-group">
                        <label for="facility_name">Hospital Name</label>
                        <input id="facility_name" name="facility_name">
                    </div>
                    <div class="form-group">
                        <label for="facility_address">Address</label>
                        <input id="facility_address" name="facility_address">
                    </div>
                    <div class="form-group">
                        <label for="facility_city">City (Nueva Ecija)</label>
                                <select id="facility_city" name="facility_city">
                                    <option value="">-- Select City --</option>
                                    <?php foreach (get_municipalities() as $m): ?>
                                        <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                                    <?php endforeach; ?>
                                </select>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit" style="background: var(--hospital-blue);">Register</button>
            </form>

            <p class="auth-link" style="margin-top:1rem;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>
</body>
</html>