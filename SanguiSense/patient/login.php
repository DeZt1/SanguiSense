<?php
include '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $userType = 'patient';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Check credentials
        $query = "SELECT * FROM users WHERE email = ? AND user_type = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email, $userType]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Login - SanguiSense</title>
    <link rel="stylesheet" href="css/patient.css">
</head>
<body class="auth-page">
    <div class="background-animation"></div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h2>Patient Login</h2>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Registration successful! Please login.</div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="Enter your email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary full-width">Login</button>
            </form>
            
            <div class="auth-link" style="text-align: center; margin-top: 1.5rem; color: #333;">
                Don't have an account? <a href="register.php" style="color: #00bcd4; text-decoration: none; font-weight: 600;">Register here</a>
            </div>
            
            
                </div>
            </div>
        </div>
    </div>

    <script src="js/patient.js"></script>
</body>
</html>
