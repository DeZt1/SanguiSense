<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = null;
$profile = null;
$errorMessage = '';
$successMessage = '';

try {
    // Fetch user data
    $query = "SELECT * FROM users WHERE id = ? AND user_type = 'patient'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    
    // Fetch patient profile if exists
    $profileQuery = "SELECT * FROM patient_profiles WHERE patient_id = ?";
    $profileStmt = $pdo->prepare($profileQuery);
    $profileStmt->execute([$_SESSION['user_id']]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = trim($_POST['name'] ?? $user['name']);
        $email = trim($_POST['email'] ?? $user['email']);
        $phone = trim($_POST['phone'] ?? '');
        $bloodType = trim($_POST['blood_type'] ?? '');
        $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
        $dateOfBirth = trim($_POST['date_of_birth'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $weight = trim($_POST['weight_kg'] ?? '');
        $healthConditions = trim($_POST['health_conditions'] ?? '');
        $allergies = trim($_POST['allergies'] ?? '');
        $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
        $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');
        
        // ===== SERVER-SIDE VALIDATION =====
        $validation_errors = [];
        
        // Validate name
        if (empty($name)) {
            $validation_errors[] = "Full name is required.";
        } elseif (strlen($name) < 2) {
            $validation_errors[] = "Full name must be at least 2 characters.";
        } elseif (strlen($name) > 100) {
            $validation_errors[] = "Full name must not exceed 100 characters.";
        }
        
        // Validate email
        if (empty($email)) {
            $validation_errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Invalid email address format.";
        } else {
            // Check if email is unique (excluding current user)
            $emailCheck = "SELECT id FROM users WHERE email = ? AND id != ?";
            $emailStmt = $pdo->prepare($emailCheck);
            $emailStmt->execute([$email, $_SESSION['user_id']]);
            if ($emailStmt->fetch(PDO::FETCH_ASSOC)) {
                $validation_errors[] = "Email is already in use by another account.";
            }
        }
        
        // Validate phone (if provided)
        if (!empty($phone)) {
            $phone_digits = preg_replace('/\D/', '', $phone);
            if (strlen($phone_digits) < 10) {
                $validation_errors[] = "Phone number must be at least 10 digits.";
            } elseif (strlen($phone_digits) > 15) {
                $validation_errors[] = "Phone number is too long.";
            }
        }
        
        // Validate address (if provided)
        if (!empty($address) && strlen($address) > 255) {
            $validation_errors[] = "Address is too long.";
        }
        
        // Validate city (if provided, must be in Nueva Ecija list)
        $nueva_ecija_cities = [
            'Cabanatuan','Gapan','Muñoz','Palayan','San Jose City','San Jose','Aliaga','Bongabon','Cabiao','Carranglan','Cuyapo','Gabaldon','General Mamerto Natividad','General Tinio','Jaen','Laur','Licab','Llanera','Lupao','Nampicuan','Pantabangan','Peñaranda','Quezon','Rizal','San Antonio','San Isidro','San Leonardo','Santa Rosa','Santo Domingo','Talavera','Talugtug','Zaragoza'
        ];
        
        if (!empty($city) && !in_array($city, $nueva_ecija_cities)) {
            $validation_errors[] = "Please select a valid city/municipality in Nueva Ecija.";
        }
        
        // Handle profile picture upload (if provided)
        if (!empty($_FILES['profile_picture']['name'])) {
            try {
                uploadProfilePicture($_FILES['profile_picture'], $_SESSION['user_id']);
            } catch (Exception $e) {
                $validation_errors[] = "Profile picture upload failed: " . $e->getMessage();
            }
        }
        
        // If validation passes, update database
        if (empty($validation_errors)) {
            try {
                // Update user data
                $updateQuery = "UPDATE users SET name = ?, email = ?, phone = ?, blood_type = ?, address = ?, city = ? WHERE id = ?";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([$name, $email, $phone, $bloodType, $address, $city, $_SESSION['user_id']]);
                
                // Update or create patient profile
                if ($profile) {
                    $profileUpdateQuery = "UPDATE patient_profiles SET 
                        date_of_birth = ?, gender = ?, weight_kg = ?, health_conditions = ?, 
                        allergies = ?, emergency_contact_name = ?, emergency_contact_phone = ?, updated_at = NOW()
                        WHERE patient_id = ?";
                    $profileUpdateStmt = $pdo->prepare($profileUpdateQuery);
                    $profileUpdateStmt->execute([
                        $dateOfBirth ?: null,
                        $gender ?: null,
                        $weight ?: null,
                        $healthConditions,
                        $allergies,
                        $emergencyContactName,
                        $emergencyContactPhone,
                        $_SESSION['user_id']
                    ]);
                } else {
                    $profileInsertQuery = "INSERT INTO patient_profiles 
                        (patient_id, date_of_birth, gender, weight_kg, health_conditions, allergies, emergency_contact_name, emergency_contact_phone, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    $profileInsertStmt = $pdo->prepare($profileInsertQuery);
                    $profileInsertStmt->execute([
                        $_SESSION['user_id'],
                        $dateOfBirth ?: null,
                        $gender ?: null,
                        $weight ?: null,
                        $healthConditions,
                        $allergies,
                        $emergencyContactName,
                        $emergencyContactPhone
                    ]);
                    
                    // Fetch updated profile
                    $profileStmt = $pdo->prepare($profileQuery);
                    $profileStmt->execute([$_SESSION['user_id']]);
                    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
                }
                
                // Refresh user data
                $stmt = $pdo->prepare($query);
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $successMessage = 'Profile updated successfully!';
            } catch (Exception $e) {
                $errorMessage = 'Error updating profile: ' . $e->getMessage();
            }
        } else {
            $errorMessage = "Please fix the following errors:\n• " . implode("\n• ", $validation_errors);
        }
    }
} catch (Exception $e) {
    $errorMessage = 'Error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SanguiSense Patient Portal</title>
    <link rel="stylesheet" href="css/patient.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_patient.php'; ?>

    <!-- Main Content: donor-style layout adapted for patient theme -->
    <main class="container" style="max-width:1200px; margin:2rem auto;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
            <section class="profile-form" style="padding: 2rem; background: rgba(0, 188, 212, 0.02); border-radius: 12px; border: 1px solid rgba(0, 188, 212, 0.05);">
                <h2 style="color: #e74c3c; font-weight: bold;">My Profile</h2>
                <p>Manage your personal and health information</p>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error">
                        <?php 
                        if (strpos($errorMessage, 'Please fix') === 0) {
                            echo nl2br(htmlspecialchars($errorMessage));
                        } else {
                            echo htmlspecialchars($errorMessage);
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="profileForm" onsubmit="return validateProfileForm()" enctype="multipart/form-data">
                    <!-- Personal Information -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required minlength="2" maxlength="100">
                            <small class="form-error" id="name_error"></small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <small class="form-error" id="email_error"></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+63 9XX XXX XXXX">
                            <small class="form-error" id="phone_error"></small>
                        </div>

                        <div class="form-group">
                            <label for="city">City / Municipality</label>
                            <select id="city" name="city">
                                <option value="">-- Select City --</option>
                                <?php
                                foreach ($nueva_ecija_cities as $c) {
                                    $sel = ($user['city'] ?? '') === $c ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($c) . "\" $sel>" . htmlspecialchars($c) . "</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        <small class="form-error" id="address_error"></small>
                    </div>


                    <!-- Health Information -->
                    <h3 style="color: var(--patient-teal); margin: 1.5rem 0 1rem 0; padding-bottom: 0.5rem; border-bottom: 2px solid var(--patient-teal);">Health Information</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="bloodType">Blood Type</label>
                            <select id="bloodType" name="blood_type">
                                <option value="">Not specified</option>
                                <?php
                                $types = ['O+','O-','A+','A-','B+','B-','AB+','AB-'];
                                foreach ($types as $t) {
                                    $sel = ($user['blood_type'] ?? '') === $t ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($t) . "\" $sel>" . htmlspecialchars($t) . "</option>\n";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="dateOfBirth">Date of Birth</label>
                            <input type="date" id="dateOfBirth" name="date_of_birth" value="<?php echo htmlspecialchars($profile['date_of_birth'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <select id="gender" name="gender">
                                <option value="">Not specified</option>
                                <option value="M" <?php echo ($profile['gender'] ?? '') == 'M' ? 'selected' : ''; ?>>Male</option>
                                <option value="F" <?php echo ($profile['gender'] ?? '') == 'F' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($profile['gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="weight">Weight (kg)</label>
                            <input type="number" id="weight" name="weight_kg" step="0.1" value="<?php echo htmlspecialchars($profile['weight_kg'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="healthConditions">Health Conditions</label>
                        <textarea id="healthConditions" name="health_conditions" rows="3" placeholder="List any chronic or significant health conditions..."><?php echo htmlspecialchars($profile['health_conditions'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="allergies">Allergies</label>
                        <textarea id="allergies" name="allergies" rows="3" placeholder="List any known allergies (medications, blood products, etc.)..."><?php echo htmlspecialchars($profile['allergies'] ?? ''); ?></textarea>
                    </div>

                    <!-- Emergency Contact -->
                    <h3 style="color: var(--patient-teal); margin: 1.5rem 0 1rem 0; padding-bottom: 0.5rem; border-bottom: 2px solid var(--patient-teal);">Emergency Contact</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="emergencyContactName">Emergency Contact Name</label>
                            <input type="text" id="emergencyContactName" name="emergency_contact_name" value="<?php echo htmlspecialchars($profile['emergency_contact_name'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="emergencyContactPhone">Emergency Contact Phone</label>
                            <input type="tel" id="emergencyContactPhone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($profile['emergency_contact_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div style="margin-top:1rem; display:flex; gap:1rem; justify-content:flex-end;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </section>

            <!-- Profile Card (donor-style with patient theme) -->
            <aside style="padding: 2rem; background: rgba(0, 188, 212, 0.05); border-radius: 12px; border: 1px solid rgba(0, 188, 212, 0.15); height: fit-content; position: sticky; top: 2rem;">
                <div style="text-align: center;">
                    <?php
                    // Display profile picture if exists, otherwise initials
                    $profilePicUrl = getProfilePictureUrl($user['id']);
                    $initials = '';
                    if (!empty($user['name'])) {
                        $parts = preg_split('/\s+/', trim($user['name']));
                        $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? ''));
                    }
                    $isCustomPic = (strpos($profilePicUrl, 'default-avatar.svg') === false);
                    ?>

                    <form id="pictureForm" method="POST" action="" enctype="multipart/form-data" style="margin:0;">
                        <div id="avatar-uploader" style="display: flex; flex-direction: column; align-items: center; gap: 1rem; cursor: pointer;">
                            <div id="avatar-display" style="position: relative; width: 150px; height: 150px;">
                                <?php if ($isCustomPic): ?>
                                    <img id="preview-image" src="<?php echo $profilePicUrl; ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--patient-teal); display: block;">
                                <?php else: ?>
                                    <div id="preview-initials" style="font-size: 4rem; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(0, 188, 212, 0.2); border-radius: 50%; border: 2px solid var(--patient-teal);"><?php echo htmlspecialchars($initials ?: 'P'); ?></div>
                                <?php endif; ?>
                                <div style="position: absolute; bottom: -8px; right: -8px; background: var(--patient-teal); color: #fff; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                    📷
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <p style="margin: 0; font-weight: 600; color:var(--patient-teal);">Profile Picture</p>
                                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.95rem;">Click the avatar to upload (JPG, PNG, GIF — Max 5MB)</p>
                            </div>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" style="display: none;">
                            <small class="form-error" id="picture_error" style="color: #e74c3c; margin-top: 0.25rem;"></small>
                        </div>
                    </form>

                    <h3 style="margin-top:1rem; color:var(--patient-teal);"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p style="color:#e0e0e0; margin-bottom:0.5rem"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="color:#e0e0e0; margin-bottom:1rem">Blood Recipient</p>

                    <div class="action-buttons" style="display:flex; flex-direction:column; gap:0.5rem; align-items:center;">
                        <a href="change_password.php" class="btn btn-secondary btn-small">Change Password</a>
                        <a href="dashboard.php" class="btn btn-primary btn-small">Go to Dashboard</a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script src="js/patient.js"></script>
    <script>
        // Avatar Uploader (donor-style) adapted for patient theme
        const avatarUploader = document.getElementById('avatar-uploader');
        const profilePictureInput = document.getElementById('profile_picture');
        const pictureError = document.getElementById('picture_error');

        if (avatarUploader) {
            avatarUploader.addEventListener('click', () => profilePictureInput.click());

            profilePictureInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    pictureError.textContent = '';

                    // Validate file size
                    if (file.size > 5 * 1024 * 1024) {
                        pictureError.textContent = '❌ File size exceeds 5MB limit.';
                        this.value = '';
                        return;
                    }

                    // Validate file type
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    if (!validTypes.includes(file.type)) {
                        pictureError.textContent = '❌ Invalid file type. Only JPG, PNG, and GIF are allowed.';
                        this.value = '';
                        return;
                    }

                    // Show preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewImage = document.getElementById('preview-image');
                        const previewInitials = document.getElementById('preview-initials');

                        if (previewInitials) previewInitials.remove();

                        if (!previewImage) {
                            const img = document.createElement('img');
                            img.id = 'preview-image';
                            img.src = e.target.result;
                            img.style.cssText = 'width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--patient-teal);';
                            document.getElementById('avatar-display').appendChild(img);
                        } else {
                            previewImage.src = e.target.result;
                        }

                        // Auto-submit to upload if server-side handler exists
                        const pictureForm = document.getElementById('pictureForm');
                        try { if (pictureForm) pictureForm.submit(); } catch (e) { console.warn('Auto submit failed', e); }
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Drag & drop UX
            avatarUploader.addEventListener('dragover', (e) => { e.preventDefault(); avatarUploader.style.background = 'rgba(0,188,212,0.12)'; });
            avatarUploader.addEventListener('dragleave', () => { avatarUploader.style.background = 'transparent'; });
            avatarUploader.addEventListener('drop', (e) => {
                e.preventDefault(); avatarUploader.style.background = 'transparent';
                if (e.dataTransfer.files.length > 0) {
                    profilePictureInput.files = e.dataTransfer.files;
                    const ev = new Event('change', { bubbles: true });
                    profilePictureInput.dispatchEvent(ev);
                }
            });
        }

        // Keep client-side validation function (unchanged)
        function validateProfileForm() {
            document.getElementById('name_error').textContent = '';
            document.getElementById('email_error').textContent = '';
            document.getElementById('phone_error').textContent = '';
            document.getElementById('address_error').textContent = '';

            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();

            let isValid = true;

            if (!name) { document.getElementById('name_error').textContent = 'Full name is required.'; isValid = false; }
            else if (name.length < 2) { document.getElementById('name_error').textContent = 'Full name must be at least 2 characters.'; isValid = false; }
            else if (name.length > 100) { document.getElementById('name_error').textContent = 'Full name must not exceed 100 characters.'; isValid = false; }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) { document.getElementById('email_error').textContent = 'Email is required.'; isValid = false; }
            else if (!emailRegex.test(email)) { document.getElementById('email_error').textContent = 'Invalid email address format.'; isValid = false; }

            if (phone) {
                const phoneDigits = phone.replace(/\D/g, '');
                if (phoneDigits.length < 10) { document.getElementById('phone_error').textContent = 'Phone number must be at least 10 digits.'; isValid = false; }
                else if (phoneDigits.length > 15) { document.getElementById('phone_error').textContent = 'Phone number is too long.'; isValid = false; }
            }

            if (address && address.length > 255) { document.getElementById('address_error').textContent = 'Address is too long.'; isValid = false; }

            return isValid;
        }
    </script>
</body>
</html>
