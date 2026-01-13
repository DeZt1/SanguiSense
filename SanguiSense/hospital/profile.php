<?php
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';
requireHospitalAdmin();

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}

$user = null;
$errorMessage = '';
$successMessage = '';

try {
  // Fetch hospital admin user data
  $query = "SELECT * FROM users WHERE id = ? AND user_type = 'hospital_admin'";
  $stmt = $pdo->prepare($query);
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    header('Location: login.php');
    exit;
  }

  // Handle form submission (profile fields)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_FILES['profile_picture']['name'])) {
    $name = trim($_POST['name'] ?? $user['name']);
    $email = trim($_POST['email'] ?? $user['email']);
    $phone = trim($_POST['phone'] ?? $user['phone'] ?? '');
    $address = trim($_POST['address'] ?? $user['address'] ?? '');
    $city = trim($_POST['city'] ?? $user['city'] ?? '');

    // basic server-side validation
    $validation_errors = [];
    if ($name === '') {
      $validation_errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $validation_errors[] = 'A valid email is required.';
    } else {
      // check uniqueness
      $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
      $emailCheck->execute([$email, $_SESSION['user_id']]);
      if ($emailCheck->fetch(PDO::FETCH_ASSOC)) {
        $validation_errors[] = 'Email is already in use by another account.';
      }
    }

    if (!empty($phone)) {
      $phone_digits = preg_replace('/\D/', '', $phone);
      if (strlen($phone_digits) < 10) $validation_errors[] = 'Phone number must be at least 10 digits.';
      if (strlen($phone_digits) > 15) $validation_errors[] = 'Phone number is too long.';
    }

    if (!empty($address) && strlen($address) > 255) {
      $validation_errors[] = 'Address is too long.';
    }

    if (empty($validation_errors)) {
      try {
        $update = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, city = ? WHERE id = ?";
        $uStmt = $pdo->prepare($update);
        $uStmt->execute([$name, $email, $phone ?: null, $address ?: null, $city ?: null, $_SESSION['user_id']]);
        $successMessage = 'Profile updated successfully.';

        // refresh
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
      } catch (Exception $e) {
        $errorMessage = 'Error updating profile.';
      }
    } else {
      $errorMessage = "Please fix the following errors:\n• " . implode("\n• ", $validation_errors);
    }
  }

  // Handle profile picture upload (separate form submit)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['profile_picture']['name'])) {
    try {
      uploadProfilePicture($_FILES['profile_picture'], $_SESSION['user_id']);
      // refresh
      $stmt->execute([$_SESSION['user_id']]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      $successMessage = 'Profile picture updated.';
    } catch (Exception $e) {
      $errorMessage = 'Profile picture upload failed: ' . $e->getMessage();
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
  <title>My Profile - Hospital Admin</title>
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="../includes/sidebar.css">
  <style>
    :root { --hospital-blue: #1e88e5; }
    .profile-container { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; max-width:1200px; margin:2rem auto; }
    .profile-form { padding: 2rem; background: rgba(30,136,229,0.02); border-radius: 12px; border: 1px solid rgba(30,136,229,0.05); }
    .profile-card { padding: 2rem; background: rgba(30,136,229,0.05); border-radius: 12px; border: 1px solid rgba(30,136,229,0.12); }
    .btn { display:inline-block; padding:0.5rem 1rem; border-radius:6px; text-decoration:none; }
    .btn-primary { background: var(--hospital-blue); color: #fff; }
    .btn-secondary { background: #fff; color: var(--hospital-blue); border:1px solid rgba(30,136,229,0.15); }
    .error, .success { padding:0.5rem; margin-bottom:0.75rem; border-radius:4px; }
    .error { background:#ffe6e6; color:#b00020; }
    .success { background:#e6ffed; color:#006400; }
    .form-row { display:flex; gap:1rem; }
    .form-group { flex:1; display:flex; flex-direction:column; }
    .form-group input, .form-group select { padding:0.5rem; border:1px solid #ddd; border-radius:6px; }
    .form-error { color:#e74c3c; font-size:0.9rem; }
  </style>
</head>
<body>
  <div class="background-animation"></div>
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

  <main class="container">
    <div class="profile-container">
      <section class="profile-form">
        <h2 style="color: #e74c3c; font-weight: bold;">My Profile</h2>
        <p>Update your contact information</p>

        <?php if ($errorMessage): ?>
          <div class="error"><?php echo nl2br(htmlspecialchars($errorMessage)); ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
          <div class="success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="profileForm" onsubmit="return validateProfileForm()">
          <div class="form-row">
            <div class="form-group">
              <label for="name">Full Name <span style="color:#b00020">*</span></label>
              <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required minlength="2" maxlength="100">
              <small class="form-error" id="name_error"></small>
            </div>
            <div class="form-group">
              <label for="email">Email <span style="color:#b00020">*</span></label>
              <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
              <small class="form-error" id="email_error"></small>
            </div>
          </div>

          <div class="form-row" style="margin-top:1rem;">
            <div class="form-group">
              <label for="phone">Phone</label>
              <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
              <small class="form-error" id="phone_error"></small>
            </div>
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
          </div>

          <div class="form-row" style="margin-top:1rem;">
            <div class="form-group">
              <label for="address">Address</label>
              <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" maxlength="255">
              <small class="form-error" id="address_error"></small>
            </div>
            <div style="flex:0 0 200px"></div>
          </div>

          <div style="margin-top:1rem;">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
          </div>
        </form>
      </section>

      <aside class="profile-card">
        <div style="text-align:center;">
          <?php
          $profilePicUrl = getProfilePictureUrl($user['id']);
          $initials = '';
          if (!empty($user['name'])) {
            $parts = preg_split('/\s+/', trim($user['name']));
            $initials = strtoupper(($parts[0][0] ?? '') . ($parts[1][0] ?? ''));
          }
          $isCustomPic = (strpos($profilePicUrl, 'default-avatar.svg') === false);
          ?>

          <form id="pictureForm" method="POST" action="" enctype="multipart/form-data" style="margin:0;">
            <div id="avatar-uploader" style="display:flex;flex-direction:column;align-items:center;gap:1rem;cursor:pointer;">
              <div id="avatar-display" style="position:relative;width:150px;height:150px;">
                <?php if ($isCustomPic): ?>
                  <img id="preview-image" src="<?php echo $profilePicUrl; ?>" alt="Profile" style="width:100%;height:100%;border-radius:50%;object-fit:cover;border:4px solid var(--hospital-blue);display:block;">
                <?php else: ?>
                  <div id="preview-initials" style="font-size:4rem;display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:rgba(30,136,229,0.12);border-radius:50%;border:2px solid var(--hospital-blue);"><?php echo htmlspecialchars($initials ?: 'H'); ?></div>
                <?php endif; ?>
                <div style="position:absolute;bottom:-8px;right:-8px;background:var(--hospital-blue);color:#fff;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,0.15);">📷</div>
              </div>
              <div style="text-align:center;">
                <p style="margin:0;font-weight:600;color:var(--hospital-blue);">Profile Picture</p>
                <p style="margin:0.25rem 0 0 0;color:#666;font-size:0.95rem;">Click the avatar to upload (JPG, PNG, GIF — Max 5MB)</p>
              </div>
              <input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif" style="display:none;">
              <small class="form-error" id="picture_error" style="color:#e74c3c;margin-top:0.25rem;"></small>
            </div>
          </form>

          <h3 style="margin-top:1rem;color:var(--hospital-blue);"><?php echo htmlspecialchars($user['name']); ?></h3>
          <p style="color:#666;margin:0 0 0.5rem 0"><?php echo htmlspecialchars($user['email']); ?></p>

          <div style="display:flex;flex-direction:column;gap:0.75rem;align-items:center;">
            <a href="facility_setup.php" class="btn btn-secondary">Manage Facility</a>
            <a href="change_password.php" class="btn btn-secondary">Change Password</a>
            <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
          </div>
        </div>
      </aside>
    </div>
  </main>

  <script src="js/script.js"></script>
  <script>
    // Avatar Uploader Setup
    const avatarUploader = document.getElementById('avatar-uploader');
    const profilePictureInput = document.getElementById('profile_picture');
    const pictureError = document.getElementById('picture_error');
    avatarUploader.addEventListener('click', () => profilePictureInput.click());
    profilePictureInput.addEventListener('change', function() {
      if (this.files.length > 0) {
        const file = this.files[0];
        pictureError.textContent = '';
        if (file.size > 5 * 1024 * 1024) {
          pictureError.textContent = '❌ File size exceeds 5MB limit.';
          this.value = '';
          return;
        }
        const validTypes = ['image/jpeg','image/png','image/gif'];
        if (!validTypes.includes(file.type)) {
          pictureError.textContent = '❌ Invalid file type. Only JPG, PNG, and GIF are allowed.';
          this.value = '';
          return;
        }
        const reader = new FileReader();
        reader.onload = function(e) {
          const previewImage = document.getElementById('preview-image');
          const previewInitials = document.getElementById('preview-initials');
          if (previewInitials) previewInitials.remove();
          if (!previewImage) {
            const img = document.createElement('img');
            img.id = 'preview-image';
            img.src = e.target.result;
            img.style.cssText = 'width:100%;height:100%;border-radius:50%;object-fit:cover;border:3px solid var(--hospital-blue);';
            document.getElementById('avatar-display').appendChild(img);
          } else {
            previewImage.src = e.target.result;
          }
          avatarUploader.style.borderColor = 'var(--hospital-blue)';
          avatarUploader.style.background = 'rgba(30,136,229,0.05)';
          const pictureForm = document.getElementById('pictureForm');
          try { if (pictureForm) pictureForm.submit(); } catch (e) { console.warn('Auto-submit failed', e); }
        };
        reader.readAsDataURL(file);
      }
    });
    avatarUploader.addEventListener('dragover', (e) => { e.preventDefault(); avatarUploader.style.background = 'rgba(30,136,229,0.08)'; });
    avatarUploader.addEventListener('dragleave', () => { avatarUploader.style.background = 'transparent'; });
    avatarUploader.addEventListener('drop', (e) => { e.preventDefault(); avatarUploader.style.background = 'transparent'; if (e.dataTransfer.files.length>0){ profilePictureInput.files = e.dataTransfer.files; profilePictureInput.dispatchEvent(new Event('change',{bubbles:true})); } });

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
      if (!name) { document.getElementById('name_error').textContent = 'Full name is required.'; isValid=false; }
      else if (name.length < 2) { document.getElementById('name_error').textContent = 'Full name must be at least 2 characters.'; isValid=false; }
      else if (name.length > 100) { document.getElementById('name_error').textContent = 'Full name must not exceed 100 characters.'; isValid=false; }
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email) { document.getElementById('email_error').textContent = 'Email is required.'; isValid=false; }
      else if (!emailRegex.test(email)) { document.getElementById('email_error').textContent = 'Invalid email format.'; isValid=false; }
      if (phone) { const pd = phone.replace(/\D/g,''); if (pd.length<10){ document.getElementById('phone_error').textContent='Phone must be at least 10 digits.'; isValid=false; } if (pd.length>15){ document.getElementById('phone_error').textContent='Phone is too long.'; isValid=false; } }
      if (address && address.length>255){ document.getElementById('address_error').textContent='Address is too long.'; isValid=false; }
      return isValid;
    }
  </script>
</body>
</html>