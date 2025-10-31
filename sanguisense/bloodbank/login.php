<?php
include '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (login($email, $password, 'bloodbank')) {
        // Redirect handled in login function
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Login - SanguiSense</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h2>🩸 Blood Bank Login</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="background: var(--bloodbank-purple);">Login to Blood Bank</button>
            </form>
            
            <p class="auth-link" style="margin-top:1rem;">
                Don't have an account? <a href="register.php">Register as Blood Bank Admin</a>
            </p>
            <p class="auth-link">
                <a href="index.php">← Back to Blood Bank Portal</a>
            </p>
        </div>
    </div>
</body>
</html>