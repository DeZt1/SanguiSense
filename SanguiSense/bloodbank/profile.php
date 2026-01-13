<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireBloodBankAdmin();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = null;
$errorMessage = '';
$successMessage = '';

try {
    // Fetch blood bank admin user data
    $query = "SELECT * FROM users WHERE id = ? AND user_type = 'bloodbank_admin'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = trim($_POST['name'] ?? $user['name']);
        $email = trim($_POST['email'] ?? $user['email']);
        $phone = trim($_POST['phone'] ?? $user['phone'] ?? '');
        $address = trim($_POST['address'] ?? $user['address'] ?? '');
        $city = trim($_POST['city'] ?? $user['city'] ?? '');
        
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
        
        // Validate city (if provided, must be in Nueva Ecija list)
        $nueva_ecija_cities = [
            'Cabanatuan','Gapan','MuÃ±oz','Palayan','San Jose City','San Jose','Aliaga','Bongabon','Cabiao','Carranglan','Cuyapo','Gabaldon','General Mamerto Natividad','General Tinio','Jaen','Laur','Licab','Llanera','Lupao','Nampicuan','Pantabangan','PeÃ±aranda','Quezon','Rizal','San Antonio','San Isidro','San Leonardo','Santa Rosa','Santo Domingo','Talavera','Talugtug','Zaragoza'
        ];
        
        if (!empty($city) && !in_array($city, $nueva_ecija_cities)) {
            $validation_errors[] = "Please select a valid city/municipality in Nueva Ecija.";
        }
        
        // Validate address (if provided)
        if (!empty($address) && strlen($address) > 255) {
            $validation_errors[] = "Address is too long.";
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
                $updateQuery = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ? WHERE id = ?";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([$name, $email, $phone ?: null, $address ?: null, $city ?: null, $_SESSION['user_id']]);

                // Refresh user data
                $stmt = $pdo->prepare($query);
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                $successMessage = 'Profile updated successfully!';
            } catch (Exception $e) {
                $errorMessage = 'Error updating profile: ' . $e->getMessage();
            }
        } else {
            $errorMessage = "Please fix the following errors:\nâ€¢ " . implode("\nâ€¢ ", $validation_errors);
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
    <title>My Profile - Blood Bank Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_bloodbank.php'; ?>

    <main class="container" style="max-width:1200px; margin:2rem auto;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
            <section class="profile-form" style="padding: 2rem; background: rgba(142, 68, 173, 0.02); border-radius: 12px; border: 1px solid rgba(142, 68, 173, 0.1);">
                <h2 style="color: #e74c3c; font-weight: bold;">My Profile</h2>
                <p>Update your contact information</p>

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
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required minlength="2" maxlength="100">
                            <small class="form-error" id="name_error"></small>
                        </div>

                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <small class="form-error" id="email_error"></small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone (Format: +63 9XX XXX XXXX or 10+ digits)</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+63 9XX XXX XXXX">
                            <small class="form-error" id="phone_error"></small>
                        </div>

                        <div class="form-group">
                            <label for="city">City / Municipality</label>
                            <select id="city" name="city">
                                <option value="">-- Select City --</option>
                                <?php
                                $cities = [
                                    'Aliaga','Bongabon','Cabiao','Carranglan','Cabanatuan City','Cuyapo','Gabaldon','General Mamerto Natividad','General Tinio','Guimba','Jaen','Laur','Licab','Llanera','Lupao','Nampicuan','Pantabangan','Peñaranda','Quezon','Rizal','San Antonio','San Isidro','San Leonardo','Santa Rosa','Santo Domingo','Talavera','Talugtug','Zaragoza'
                                ];
                                foreach ($cities as $c) {
                                    $sel = ($user['city'] ?? '') === $c ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($c) . "\" $sel>" . htmlspecialchars($c) . "</option>\n";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" maxlength="255">
                            <small class="form-error" id="address_error"></small>
                        </div>
                    </div>

                    <!-- Profile picture uploader moved to the right-side profile card to have a single slot -->

                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </section>

            <aside style="padding: 2rem; background: rgba(142, 68, 173, 0.05); border-radius: 12px; border: 1px solid rgba(142, 68, 173, 0.2); height: fit-content; position: sticky; top: 2rem;">
                <div style="text-align: center;">
                    <?php
                    // Display profile picture if exists, otherwise use initials
                    $profilePicUrl = getProfilePictureUrl($user['id']);
                    $initials = '';
                    if (!empty($user['name'])) {
                        $parts = preg_split('/\s+/', trim($user['name']));
                        $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? ''));
                    }
                    // If the helper returned the default avatar, we'll render initials instead
                    $isCustomPic = (strpos($profilePicUrl, 'default-avatar.svg') === false);
                    ?>
                    <!-- Single uploader UI for profile picture (placed in the profile card) -->
                    <form id="pictureForm" method="POST" action="" enctype="multipart/form-data" style="margin:0;">
                        <div id="avatar-uploader" style="display: flex; flex-direction: column; align-items: center; gap: 1rem; cursor: pointer;">
                            <div id="avatar-display" style="position: relative; width: 150px; height: 150px;">
                                <?php if ($isCustomPic): ?>
                                        <img id="preview-image" src="<?php echo $profilePicUrl; ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--bloodbank-purple); display: block;">
                                    <?php else: ?>
                                        <div id="preview-initials" style="font-size: 4rem; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(142, 68, 173, 0.2); border-radius: 50%; border: 2px solid var(--bloodbank-purple);"><?php echo htmlspecialchars($initials ?: 'B'); ?></div>
                                    <?php endif; ?>
                                <div style="position: absolute; bottom: -8px; right: -8px; background: var(--bloodbank-purple); color: white; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                    📷
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <p style="margin: 0; font-weight: 600; color: var(--bloodbank-purple);">Profile Picture</p>
                                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.95rem;">Click the avatar to upload (JPG, PNG, GIF — Max 5MB)</p>
                            </div>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" style="display: none;">
                            <small class="form-error" id="picture_error" style="color: #e74c3c; margin-top: 0.25rem;"></small>
                        </div>
                    </form>
                    <h3 style="margin-top:1rem; color:var(--bloodbank-purple);"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p style="color:#e0e0e0; margin-bottom:0.5rem"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="color:#e0e0e0; margin-bottom:1rem">Blood Bank Administrator</p>

                    <div class="action-buttons" style="justify-content:center; display: flex; flex-direction: column; gap: 0.75rem;">
                        <a href="change_password.php" class="btn btn-secondary btn-small">Change Password</a>
                        <a href="facility_setup.php" class="btn btn-secondary btn-small">Manage Facility</a>
                        <a href="dashboard.php" class="btn btn-primary btn-small">Go to Dashboard</a>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <script src="js/script.js"></script>
</body>
</html>
