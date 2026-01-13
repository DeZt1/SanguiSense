<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';
requireLogin();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = null;
$errorMessage = '';
$successMessage = '';

try {
    // Fetch donor user data
    $query = "SELECT * FROM users WHERE id = ? AND user_type = 'donor'";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch approved and upcoming appointments
    $appointments = [];
    $apptStmt = $pdo->prepare("SELECT d.*, f.name AS facility_name FROM donations d JOIN facilities f ON d.facility_id = f.id WHERE d.donor_id = ? AND d.status IN ('approved','fulfilled') ORDER BY d.donation_date ASC");
    $apptStmt->execute([$_SESSION['user_id']]);
    $appointments = $apptStmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: login.php');
        exit;
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $name = trim($_POST['name'] ?? $user['name']);
        $email = trim($_POST['email'] ?? $user['email']);
        $phone = trim($_POST['phone'] ?? $user['phone'] ?? '');
        $bloodType = trim($_POST['blood_type'] ?? $user['blood_type'] ?? '');
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
            // Remove non-numeric characters for validation
            $phone_digits = preg_replace('/\D/', '', $phone);
            if (strlen($phone_digits) < 10) {
                $validation_errors[] = "Phone number must be at least 10 digits.";
            } elseif (strlen($phone_digits) > 15) {
                $validation_errors[] = "Phone number is too long.";
            }
            // Standardize phone number format (keep it as user entered if valid)
        }
        
        // Validate blood type (if provided, must be valid)
        if (!empty($bloodType)) {
            $valid_types = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
            if (!in_array($bloodType, $valid_types)) {
                $validation_errors[] = "Invalid blood type selected.";
            }
        }
        
        // Validate city (if provided, must be in Nueva Ecija list)
        $nueva_ecija_cities = [
            'Cabanatuan','Gapan','Muñoz','Palayan','San Jose City','San Jose','Aliaga','Bongabon','Cabiao','Carranglan','Cuyapo','Gabaldon','General Mamerto Natividad','General Tinio','Jaen','Laur','Licab','Llanera','Lupao','Nampicuan','Pantabangan','Peñaranda','Quezon','Rizal','San Antonio','San Isidro','San Leonardo','Santa Rosa','Santo Domingo','Talavera','Talugtug','Zaragoza'
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
                $updateQuery = "UPDATE users SET name = ?, email = ?, phone = ?, blood_type = ?, address = ?, city = ? WHERE id = ?";
                $updateStmt = $pdo->prepare($updateQuery);
                $updateStmt->execute([$name, $email, $phone ?: null, $bloodType ?: null, $address ?: null, $city ?: null, $_SESSION['user_id']]);

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

    // Handle mark as fulfilled
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_fulfilled'], $_POST['fulfill_id'])) {
        $fulfill_id = intval($_POST['fulfill_id']);
        $updateStmt = $pdo->prepare("UPDATE donations SET status = 'fulfilled' WHERE id = ? AND donor_id = ?");
        $updateStmt->execute([$fulfill_id, $_SESSION['user_id']]);
        header('Location: profile.php');
        exit;
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
    <title>My Profile - Donor Portal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="background-animation"></div>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_donor.php'; ?>

    <main class="container" style="max-width:1200px; margin:2rem auto;">
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
            <section class="profile-form" style="padding: 2rem; background: rgba(255, 215, 0, 0.02); border-radius: 12px; border: 1px solid rgba(255, 215, 0, 0.1);">
                <h2 style="color: #e74c3c; font-weight: bold;">My Profile</h2>
                <p>Update your contact and donation information</p>

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
                            <label for="phone">Phone (Format: +63 9XX XXX XXXX)</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+63 9XX XXX XXXX">
                            <small class="form-error" id="phone_error"></small>
                        </div>

                        <div class="form-group">
                            <label for="blood_type">Blood Type</label>
                            <select id="blood_type" name="blood_type">
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
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City / Municipality</label>
                            <select id="city" name="city">
                                <option value="">-- Select City --</option>
                                <?php
                                $cities = [
                                    'Cabanatuan','Gapan','Muñoz','Palayan','San Jose City','San Jose','Aliaga','Bongabon','Cabiao','Carranglan','Cuyapo','Gabaldon','General Mamerto Natividad','General Tinio','Jaen','Laur','Licab','Llanera','Lupao','Nampicuan','Pantabangan','Peñaranda','Quezon','Rizal','San Antonio','San Isidro','San Leonardo','Santa Rosa','Santo Domingo','Talavera','Talugtug','Zaragoza'
                                ];
                                foreach ($cities as $c) {
                                    $sel = ($user['city'] ?? '') === $c ? 'selected' : '';
                                    echo "<option value=\"" . htmlspecialchars($c) . "\" $sel>" . htmlspecialchars($c) . "</option>\n";
                                }
                                ?>
                            </select>
                        </div>

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

            <aside style="padding: 2rem; background: rgba(255, 215, 0, 0.05); border-radius: 12px; border: 1px solid rgba(255, 215, 0, 0.2); height: fit-content; position: sticky; top: 2rem;">
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
                                        <img id="preview-image" src="<?php echo $profilePicUrl; ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 4px solid var(--yellow); display: block;">
                                    <?php else: ?>
                                        <div id="preview-initials" style="font-size: 4rem; display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; background: rgba(255, 215, 0, 0.2); border-radius: 50%; border: 2px solid var(--yellow);"><?php echo htmlspecialchars($initials ?: 'D'); ?></div>
                                    <?php endif; ?>
                                <div style="position: absolute; bottom: -8px; right: -8px; background: var(--yellow); color: #333; border-radius: 50%; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                    📷
                                </div>
                            </div>
                            <div style="text-align: center;">
                                <p style="margin: 0; font-weight: 600; color: var(--yellow);">Profile Picture</p>
                                <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.95rem;">Click the avatar to upload (JPG, PNG, GIF — Max 5MB)</p>
                            </div>
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" style="display: none;">
                            <small class="form-error" id="picture_error" style="color: #e74c3c; margin-top: 0.25rem;"></small>
                        </div>
                    </form>
                    <h3 style="margin-top:1rem; color:var(--yellow);"><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p style="color:#e0e0e0; margin-bottom:0.5rem"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="color:#e0e0e0; margin-bottom:1rem">Blood Type: <strong><?php echo htmlspecialchars($user['blood_type'] ?? 'N/A'); ?></strong></p>

                    <div class="action-buttons" style="justify-content:center;">
                        <a href="change_password.php" class="btn btn-secondary btn-small">Change Password</a>
                        <a href="dashboard.php" class="btn btn-primary btn-small">Go to Dashboard</a>
                    </div>
                </div>
            </aside>
        </div>

        <h2 style="margin-top:2rem; color:var(--yellow);">My Approved Appointments</h2>
        
        <?php
        // Calculate when donor can donate again (56 days after last fulfilled donation)
        $lastFulfilledStmt = $pdo->prepare("SELECT MAX(donation_date) as last_fulfilled FROM donations WHERE donor_id = ? AND status = 'fulfilled'");
        $lastFulfilledStmt->execute([$_SESSION['user_id']]);
        $lastFulfilled = $lastFulfilledStmt->fetch(PDO::FETCH_ASSOC)['last_fulfilled'];
        $canDonateAgainDate = null;
        $canDonateNow = true;
        
        if ($lastFulfilled) {
            $canDonateAgainDate = strtotime($lastFulfilled . ' + 56 days');
            if ($canDonateAgainDate > time()) {
                $canDonateNow = false;
            }
        }
        ?>
        
        <?php if (!$canDonateNow): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 1rem; border-radius: 4px; margin-bottom: 1rem;">
                <strong>⏱️ Waiting Period:</strong> You can schedule your next donation on <strong><?= date('M j, Y', $canDonateAgainDate) ?></strong> (56 days after your last donation)
            </div>
        <?php endif; ?>
        
        <table class="appointments-table">
            <thead>
                <tr>
                    <th>Facility</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td><?= htmlspecialchars($appt['facility_name']) ?></td>
                        <td><?= htmlspecialchars(date('M j, Y', strtotime($appt['donation_date']))) ?></td>
                        <td><?= htmlspecialchars($appt['status']) ?></td>
                        <td>
                            <?php if ($appt['status'] === 'approved'): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="fulfill_id" value="<?= $appt['id'] ?>">
                                    <button type="submit" name="mark_fulfilled" value="1">Mark as Fulfilled</button>
                                </form>
                            <?php elseif ($appt['status'] === 'fulfilled'): ?>
                                <span>Fulfilled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <script src="js/script.js"></script>
    <script>
        // Avatar Uploader Setup
        const avatarUploader = document.getElementById('avatar-uploader');
        const profilePictureInput = document.getElementById('profile_picture');
        const pictureError = document.getElementById('picture_error');
        
        // Make uploader clickable
        avatarUploader.addEventListener('click', () => profilePictureInput.click());
        
        // Handle file selection
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
                    
                    if (previewInitials) {
                        previewInitials.remove();
                    }
                    
                    if (!previewImage) {
                        const img = document.createElement('img');
                        img.id = 'preview-image';
                        img.src = e.target.result;
                        img.style.cssText = 'width: 100%; height: 100%; border-radius: 50%; object-fit: cover; border: 3px solid var(--yellow);';
                        document.getElementById('avatar-display').appendChild(img);
                    } else {
                        previewImage.src = e.target.result;
                    }
                    
                    // Add visual feedback
                    avatarUploader.style.borderColor = 'var(--yellow)';
                    avatarUploader.style.background = 'rgba(255, 215, 0, 0.1)';

                    // Auto-submit the small picture form so the upload is processed immediately
                    const pictureForm = document.getElementById('pictureForm');
                    try {
                        if (pictureForm) pictureForm.submit();
                    } catch (e) {
                        // fallback: do nothing if submit fails
                        console.warn('Automatic picture upload submit failed', e);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Drag and drop support
        avatarUploader.addEventListener('dragover', (e) => {
            e.preventDefault();
            avatarUploader.style.background = 'rgba(255, 215, 0, 0.2)';
        });
        
        avatarUploader.addEventListener('dragleave', () => {
            avatarUploader.style.background = 'rgba(255, 215, 0, 0.05)';
        });
        
        avatarUploader.addEventListener('drop', (e) => {
            e.preventDefault();
            avatarUploader.style.background = 'rgba(255, 215, 0, 0.05)';
            
            if (e.dataTransfer.files.length > 0) {
                profilePictureInput.files = e.dataTransfer.files;
                const event = new Event('change', { bubbles: true });
                profilePictureInput.dispatchEvent(event);
            }
        });
        
        function validateProfileForm() {
            // Clear previous error messages
            document.getElementById('name_error').textContent = '';
            document.getElementById('email_error').textContent = '';
            document.getElementById('phone_error').textContent = '';
            document.getElementById('address_error').textContent = '';
            
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            
            let isValid = true;
            
            // Validate name
            if (!name) {
                document.getElementById('name_error').textContent = 'Full name is required.';
                isValid = false;
            } else if (name.length < 2) {
                document.getElementById('name_error').textContent = 'Full name must be at least 2 characters.';
                isValid = false;
            } else if (name.length > 100) {
                document.getElementById('name_error').textContent = 'Full name must not exceed 100 characters.';
                isValid = false;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email) {
                document.getElementById('email_error').textContent = 'Email is required.';
                isValid = false;
            } else if (!emailRegex.test(email)) {
                document.getElementById('email_error').textContent = 'Invalid email address format.';
                isValid = false;
            }
            
            // Validate phone (if provided)
            if (phone) {
                const phoneDigits = phone.replace(/\D/g, '');
                if (phoneDigits.length < 10) {
                    document.getElementById('phone_error').textContent = 'Phone number must be at least 10 digits.';
                    isValid = false;
                } else if (phoneDigits.length > 15) {
                    document.getElementById('phone_error').textContent = 'Phone number is too long.';
                    isValid = false;
                }
            }
            
            // Validate address (if provided)
            if (address && address.length > 255) {
                document.getElementById('address_error').textContent = 'Address is too long.';
                isValid = false;
            }
            
            return isValid;
        }
    </script>
</body>
</html>