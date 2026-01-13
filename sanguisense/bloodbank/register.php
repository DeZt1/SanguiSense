<?php
require_once __DIR__ . '/../includes/db_connect.php';
// Blood Bank Registration
// Clean, secure registration for blood bank admins. Inserts user and links/creates facility.

require_once __DIR__ . '/../includes/auth.php';
global $pdo;

$error = '';
$facilities = [];
$bloodBanksByMunicipality = [
    // example predefined seeds (can be extended)
    'Cabanatuan City' => ['Philippine Red Cross-Nueva Ecija Blood Services']
];

// All municipalities in Nueva Ecija
$all_municipalities = [
    'Aliaga',
    'Bongabon',
    'Cabiao',
    'Carranglan',
    'Cabanatuan City',
    'Cuyapo',
    'Gabaldon',
    'General Mamerto Natividad',
    'General Tinio',
    'Guimba',
    'Jaen',
    'Laur',
    'Licab',
    'Llanera',
    'Lupao',
    'Nampicuan',
    'Pantabangan',
    'Peñaranda',
    'Quezon',
    'Rizal',
    'San Antonio',
    'San Isidro',
    'San Leonardo',
    'Santa Rosa',
    'Santo Domingo',
    'Talavera',
    'Talugtug',
    'Zaragoza'
];

// Load existing blood bank facilities from DB
try {
    $stmt = $pdo->prepare("SELECT id, name, address, city FROM facilities WHERE type = 'blood_bank' ORDER BY name");
    $stmt->execute();
    $facilities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // keep $facilities empty on error
}

// Build municipalities list from facilities + predefined (only existing ones)
$municipalities = [];
foreach ($facilities as $f) {
    if (!empty($f['city']) && !in_array($f['city'], $municipalities)) {
        $municipalities[] = $f['city'];
    }
}
foreach ($bloodBanksByMunicipality as $mun => $_) {
    if (!in_array($mun, $municipalities)) $municipalities[] = $mun;
}
sort($municipalities);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    $facility_id_raw = $_POST['facility_id'] ?? '0';
    $facility_name = trim($_POST['facility_name'] ?? '');
    $facility_address = trim($_POST['facility_address'] ?? '');
    $facility_city = trim($_POST['facility_city'] ?? '');

    // Basic validation - required fields
    if (!$name || !$email || !$password) {
        $error = 'Please fill in all required fields (name, email, password).';
    }

    // Validate name length
    if (empty($error) && (strlen($name) < 3 || strlen($name) > 100)) {
        $error = 'Full name must be between 3 and 100 characters.';
    }

    // Validate email format
    if (empty($error) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    }

    // Validate password strength
    if (empty($error)) {
        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $error = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
        } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $error = 'Password must contain at least one special character (!@#$%^&*).';
        }
    }

    // Validate phone if provided
    if (empty($error) && !empty($phone)) {
        if (!preg_match('/^[0-9\s\-\+\(\)]{10,15}$/', $phone)) {
            $error = 'Phone number must be valid (10-15 digits).';
        }
    }

    // Determine facility selection: numeric id, seed|Name|City or create new (via facility_name)
    $is_seed = is_string($facility_id_raw) && strpos($facility_id_raw, 'seed|') === 0;
    $is_numeric = is_string($facility_id_raw) && ctype_digit($facility_id_raw) && intval($facility_id_raw) > 0;

    if (empty($error)) {
        // If no facility chosen and not creating new -> error
        if (!$is_numeric && !$is_seed && !$facility_name) {
            $error = 'Please select an existing facility or provide details to create a new one.';
        }
    }

    // Validate facility details when creating new
    if (empty($error) && !$is_numeric && !$is_seed) {
        if (strlen($facility_name) < 3 || strlen($facility_name) > 100) {
            $error = 'Blood Bank Name must be between 3 and 100 characters.';
        } elseif (strlen($facility_address) < 5 || strlen($facility_address) > 200) {
            $error = 'Address must be between 5 and 200 characters.';
        } elseif (empty($facility_city)) {
            $error = 'Please select a city/municipality.';
        }
    }

    if (empty($error)) {
        try {
            // Check email uniqueness
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with that email already exists';
            }
        } catch (Exception $e) {
            $error = 'Registration error';
        }
    }

    if (empty($error)) {
        try {
            $pdo->beginTransaction();

            // Create user with city and address
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $user_city = '';
            $user_address = '';
            
            // Determine city and address for user record
            if ($is_numeric) {
                // Get facility details for city/address
                $fac_stmt = $pdo->prepare("SELECT city, address FROM facilities WHERE id = ?");
                $fac_stmt->execute([$facility_id_raw]);
                $fac = $fac_stmt->fetch(PDO::FETCH_ASSOC);
                if ($fac) {
                    $user_city = $fac['city'] ?? '';
                    $user_address = $fac['address'] ?? '';
                }
            } elseif ($is_seed) {
                // seed|Name|City
                $parts = explode('|', $facility_id_raw, 3);
                $user_city = trim($parts[2] ?? '');
                $user_address = '';
            } else {
                // create new facility provided in form
                $user_city = $facility_city;
                $user_address = $facility_address;
            }
            
            $insert = $pdo->prepare('INSERT INTO users (name, email, password, user_type, phone, city, address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            // user_type for blood bank admins
            $insert->execute([$name, $email, $hash, 'bloodbank_admin', $phone, $user_city, $user_address]);
            $user_id = $pdo->lastInsertId();

            $linked_facility_id = 0;

            if ($is_numeric) {
                $linked_facility_id = intval($facility_id_raw);
                // Associate existing facility
                $upd = $pdo->prepare('UPDATE facilities SET admin_id = ? WHERE id = ?');
                $upd->execute([$user_id, $linked_facility_id]);

            } elseif ($is_seed) {
                // seed|Name|City
                $parts = explode('|', $facility_id_raw, 3);
                $seed_name = trim($parts[1] ?? '');
                $seed_city = trim($parts[2] ?? '');

                $ins = $pdo->prepare('INSERT INTO facilities (name, type, address, city, phone, email, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $ins->execute([$seed_name, 'blood_bank', '', $seed_city, $phone ?: null, $email ?: null, $user_id]);
                $linked_facility_id = $pdo->lastInsertId();

            } else {
                // create new facility provided in form
                $ins = $pdo->prepare('INSERT INTO facilities (name, type, address, city, phone, email, admin_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $ins->execute([$facility_name, 'blood_bank', $facility_address, $facility_city, $phone ?: null, $email ?: null, $user_id]);
                $linked_facility_id = $pdo->lastInsertId();
            }

            $pdo->commit();

            // Redirect to login with success flag
            header('Location: login.php?registered=1');
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Blood Bank Registration</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h2>🩸 Blood Bank Admin Registration</h2>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password *</label>
                    <input type="password" id="password" name="password" required onchange="updatePasswordStrength()" oninput="updatePasswordStrength()">
                    <small id="password-strength" style="color: #666; margin-top: 0.5rem; display: block;"></small>
                    <small style="color: #666; margin-top: 0.3rem; display: block;">
                        ✓ At least 8 characters | ✓ Uppercase & lowercase | ✓ Number | ✓ Special character (!@#$%)
                    </small>
                </div>

                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                </div>

                <h3 style="margin-top: 2rem;">Facility Information</h3>
                
                <div class="form-group">
                    <label>Choose Facility Option *</label>
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                        <button type="button" class="toggle-btn active" onclick="toggleFacility('select', event)">Select Existing</button>
                        <button type="button" class="toggle-btn" onclick="toggleFacility('create', event)">Create New</button>
                    </div>
                </div>

                <div id="select-section" class="facility-section active">
                    <div class="form-group">
                        <label for="facility_id">Choose Blood Bank *</label>
                        <select id="facility_id" name="facility_id" onchange="updateButtonState()">
                            <option value="0">-- Select a blood bank --</option>
                            <?php foreach ($municipalities as $city): ?>
                                <optgroup label="<?php echo htmlspecialchars($city); ?>">
                                    <?php
                                    if (!empty($bloodBanksByMunicipality[$city])) {
                                        foreach ($bloodBanksByMunicipality[$city] as $bankName) {
                                            $seedValue = 'seed|' . $bankName . '|' . $city;
                                            echo '<option value="' . htmlspecialchars($seedValue) . '">' . htmlspecialchars($bankName) . '</option>';
                                        }
                                    }
                                    foreach ($facilities as $fac) {
                                        if (($fac['city'] ?? '') === $city) {
                                            echo '<option value="' . intval($fac['id']) . '">' . htmlspecialchars($fac['name']) . '</option>';
                                        }
                                    }
                                    ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div id="create-section" class="facility-section">
                    <div class="form-group">
                        <label for="facility_city">City / Municipality *</label>
                        <select id="facility_city" name="facility_city" onchange="updateButtonState()">
                            <option value="">-- Select city --</option>
                            <?php foreach ($all_municipalities as $city): ?>
                                <option value="<?php echo htmlspecialchars($city); ?>" <?php echo (isset($facility_city) && $facility_city === $city) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="facility_name">Blood Bank Name *</label>
                        <input type="text" id="facility_name" name="facility_name" value="<?php echo isset($facility_name) ? htmlspecialchars($facility_name) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="facility_address">Address *</label>
                        <input type="text" id="facility_address" name="facility_address" value="<?php echo isset($facility_address) ? htmlspecialchars($facility_address) : ''; ?>">
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn btn-primary" disabled>Register</button>
            </form>

            <p class="auth-link">
                Already have an account? <a href="login.php">Login here</a>
            </p>
        </div>
    </div>

    <script>
        function updatePasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthIndicator = document.getElementById('password-strength');
            
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) strength++;
            else feedback.push('Min 8 chars');
            
            if (/[A-Z]/.test(password)) strength++;
            else feedback.push('Uppercase');
            
            if (/[a-z]/.test(password)) strength++;
            else feedback.push('Lowercase');
            
            if (/[0-9]/.test(password)) strength++;
            else feedback.push('Number');
            
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            else feedback.push('Special char');
            
            let message = '';
            let color = '';
            
            if (password.length === 0) {
                message = '';
            } else if (strength < 3) {
                message = '❌ Weak - ' + feedback.join(', ');
                color = '#d32f2f';
            } else if (strength < 5) {
                message = '⚠️ Fair - ' + feedback.join(', ');
                color = '#f57c00';
            } else {
                message = '✓ Strong password';
                color = '#388e3c';
            }
            
            strengthIndicator.textContent = message;
            strengthIndicator.style.color = color;
        }

        function toggleFacility(mode, ev) {
            ev.preventDefault();
            document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
            ev.currentTarget.classList.add('active');

            const selectSection = document.getElementById('select-section');
            const createSection = document.getElementById('create-section');
            if (mode === 'select') {
                selectSection.classList.add('active');
                createSection.classList.remove('active');
            } else {
                selectSection.classList.remove('active');
                createSection.classList.add('active');
            }
            updateButtonState();
        }

        function updateButtonState() {
            const selectActive = document.getElementById('select-section').classList.contains('active');
            const facilityId = document.getElementById('facility_id').value;
            const fname = document.getElementById('facility_name').value.trim();
            const faddr = document.getElementById('facility_address').value.trim();
            const fcity = document.getElementById('facility_city').value.trim();
            const btn = document.getElementById('submit-btn');

            let ok = false;
            if (selectActive) ok = facilityId !== '0' && facilityId !== '';
            else ok = fname !== '' && faddr !== '' && fcity !== '';
            btn.disabled = !ok;
        }

        function validateForm() {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const phone = document.getElementById('phone').value.trim();
            
            // Name validation
            if (!name) {
                alert('Please enter your full name');
                return false;
            }
            if (name.length < 3 || name.length > 100) {
                alert('Full name must be between 3 and 100 characters');
                return false;
            }
            
            // Email validation
            if (!email) {
                alert('Please enter your email');
                return false;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }
            
            // Password validation
            if (!password) {
                alert('Please enter a password');
                return false;
            }
            if (password.length < 8) {
                alert('Password must be at least 8 characters long');
                return false;
            }
            if (!/[A-Z]/.test(password)) {
                alert('Password must contain at least one uppercase letter');
                return false;
            }
            if (!/[a-z]/.test(password)) {
                alert('Password must contain at least one lowercase letter');
                return false;
            }
            if (!/[0-9]/.test(password)) {
                alert('Password must contain at least one number');
                return false;
            }
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                alert('Password must contain at least one special character (!@#$%^&*)');
                return false;
            }
            
            // Phone validation (if provided)
            if (phone && !/^[0-9\s\-\+\(\)]{10,15}$/.test(phone)) {
                alert('Please enter a valid phone number (10-15 digits)');
                return false;
            }
            
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fid = document.getElementById('facility_id');
            if (fid) fid.addEventListener('change', updateButtonState);
            ['facility_name','facility_address','facility_city'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('input', updateButtonState);
            });
            updateButtonState();
        });
    </script>

    <style>
        .toggle-btn {
            flex: 1;
            padding: 0.8rem;
            border: 2px solid #ddd;
            background: var(--gray);
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            color: var(--dark-gray);
            transition: all 0.3s ease;
        }

        .toggle-btn:hover {
            border-color: var(--bloodbank-purple);
            background: rgba(142, 68, 173, 0.1);
        }

        .toggle-btn.active {
            background: var(--bloodbank-purple);
            color: var(--white);
            border-color: var(--bloodbank-purple);
        }

        .facility-section {
            display: none;
            margin-top: 1.5rem;
        }

        .facility-section.active {
            display: block;
        }
    </style>