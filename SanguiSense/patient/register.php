<?php
require_once __DIR__ . '/../includes/db_connect.php';
include '../includes/auth.php';

$error = '';
$success = '';

// Use centralized municipalities list
include_once __DIR__ . '/../includes/locations.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $bloodType = trim($_POST['blood_type'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');

    // Basic validation
    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!in_array($city, get_municipalities())) {
        $error = 'Please select a valid city/municipality in Nueva Ecija.';
    } else {
        // Check if email already exists
        $checkQuery = 'SELECT id FROM users WHERE email = ?';
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([$email]);

        if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
            $error = 'Email already registered';
        } else {
            // Hash password and insert
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $insertQuery = "INSERT INTO users (name, email, password, user_type, blood_type, phone, city, is_eligible, created_at)
                VALUES (?, ?, ?, 'patient', ?, ?, ?, TRUE, NOW())";
            $insertStmt = $pdo->prepare($insertQuery);

            try {
                $insertStmt->execute([$name, $email, $hashedPassword, $bloodType ?: null, $phone, $city]);
                $userId = $pdo->lastInsertId();

                // Create empty patient profile (will be filled in later on profile page)
                $profileQuery = 'INSERT INTO patient_profiles (patient_id, created_at) VALUES (?, NOW())';
                $profileStmt = $pdo->prepare($profileQuery);
                $profileStmt->execute([$userId]);

                header('Location: login.php?success=1');
                exit;
            } catch (PDOException $e) {
                $error = 'Error registering patient: ' . $e->getMessage();
                error_log('Registration error: ' . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - SanguiSense</title>
    <link rel="stylesheet" href="css/patient.css">
</head>
<body class="auth-page">
    <div class="background-animation"></div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h2>Create Patient Account</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name <span style="color: red;">*</span></label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address <span style="color: red;">*</span></label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span style="color: red;">*</span></label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm Password <span style="color: red;">*</span></label>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Re-enter your password" required>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label for="bloodType">Blood Type</label>
                        <select id="bloodType" name="blood_type">
                            <option value="">Select Blood Type</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Your phone number">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="city">City / Municipality</label>
                    <select id="city" name="city" required>
                        <option value="">Select City or Municipality</option>
                        <?php foreach (get_municipalities() as $m): ?>
                            <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary full-width">Create Account</button>
            </form>
            
            <div class="auth-link" style="text-align: center; margin-top: 1.5rem; color: #333;">
                Already have an account? <a href="login.php" style="color: #00bcd4; text-decoration: none; font-weight: 600;">Login here</a>
            </div>
            
            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 1rem;">Registering for a different role?</p>
                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;">

                </div>
            </div>
        </div>
    </div>

    <script src="js/patient.js"></script>
</body>
</html>
