<?php
include '../includes/config.php';

// Region 3 Nueva Ecija cities
$region3_cities = [
    'Cabanatuan',
    'Gapan',
    'San Fernando',
    'Muñez',
    'Palayan',
    'General Tinio',
    'Aliaga',
    'Guimba',
    'Jaen',
    'Licab',
    'Nampicuan',
    'Peñaranda',
    'Quezon',
    'San Antonio de Nueva Ecija',
    'San Isidro',
    'San Jose City',
    'Santa Cruz',
    'Santa Rosa',
    'Talugtug',
    'Taon',
    'Umingan'
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $blood_type = $_POST['blood_type'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($blood_type) || empty($phone) || empty($address) || empty($city)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    } elseif (!in_array($city, $region3_cities)) {
        $error = "Sorry, we only accept donors from Region 3, Nueva Ecija. Please select a valid city from the list.";
    } else {
        // Check if email already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        
        if ($checkStmt->fetch()) {
            $error = "Email already registered";
        } else {
            try {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, name, user_type, blood_type, phone, address, city, is_eligible, created_at) VALUES (?, ?, ?, 'donor', ?, ?, ?, ?, TRUE, NOW())");
                $stmt->execute([$email, $hashedPassword, $name, $blood_type, $phone, $address, $city]);
                
                // Redirect to login
                header("Location: login.php?success=1");
                exit();
            } catch(PDOException $e) {
                $error = "Registration failed: " . $e->getMessage();
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
    <title>Register - SanguiSense</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_donor.php'; ?>

    <div class="auth-container">
        <div class="auth-form">
            <h2>Become a Donor</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" required>
                </div>
                
                <div class="form-group">
                    <label for="blood_type">Blood Type</label>
                    <select id="blood_type" name="blood_type" required>
                        <option value="">Select Blood Type</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="city">City (Region 3, Nueva Ecija)</label>
                    <select id="city" name="city" required>
                        <option value="">-- Select City --</option>
                        <option value="Cabanatuan">Cabanatuan</option>
                         <option value="Cuyapo">Cuyapo</option>
                        <option value="Gapan">Gapan</option>
                        <option value="San Fernando">San Fernando</option>
                        <option value="Muñoz">Muñoz</option>
                        <option value="Palayan">Palayan</option>
                        <option value="General Tinio">General Tinio</option>
                        <option value="Aliaga">Aliaga</option>
                        <option value="Guimba">Guimba</option>
                        <option value="Jaen">Jaen</option>
                        <option value="Licab">Licab</option>
                        <option value="Nampicuan">Nampicuan</option>
                        <option value="Peñaranda">Peñaranda</option>
                        <option value="Quezon">Quezon</option>
                        <option value="San Antonio de Nueva Ecija">San Antonio de Nueva Ecija</option>
                        <option value="San Isidro">San Isidro</option>
                        <option value="San Jose City">San Jose City</option>
                        <option value="Santa Cruz">Santa Cruz</option>
                        <option value="Santa Rosa">Santa Rosa</option>
                        <option value="Talugtug">Talugtug</option>
                        <option value="Taon">Taon</option>
                        <option value="Umingan">Umingan</option>
                    </select>
                    <small style="color: #999; display: block; margin-top: 5px;">We only accept donors from Region 3, Nueva Ecija</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <p class="auth-link">Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>