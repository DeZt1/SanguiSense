<?php
include '../includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $user = getUserData($_SESSION['user_id']);
    
    if ($user['user_type'] == 'bloodbank_admin') {
        redirect('dashboard.php');
    } else {
        redirect('login.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Bank Portal - SanguiSense</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h2>🩸 Blood Bank Portal</h2>
            <p style="text-align: center; margin-bottom: 2rem; color: var(--dark-gray);">
                Welcome to SanguiSense Blood Bank Management System
            </p>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="background: #f3e5f5; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h3 style="color: var(--bloodbank-purple); margin-bottom: 1rem;">Blood Bank Admin Access</h3>
                    <div style="text-align: left; font-size: 0.9rem;">
                        <p><strong>Test Account:</strong><br>admin@bloodbank.com<br>Password: password</p>
                    </div>
                </div>
            </div>

            <div style="display: grid; gap: 1rem;">
                <a href="login.php" class="btn btn-primary" style="text-decoration: none; display: block; background: var(--bloodbank-purple);">
                    Blood Bank Login
                </a>
                <a href="../donor/index.php" class="btn btn-secondary" style="text-decoration: none; display: block;">
                    Go to Donor Portal
                </a>
                <a href="../hospital/index.php" class="btn btn-secondary" style="text-decoration: none; display: block;">
                    Go to Hospital Portal
                </a>
            </div>
        </div>
    </div>
</body>
</html>