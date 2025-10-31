<?php
include '../includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    $user = getUserData($_SESSION['user_id']);
    
    if ($user['user_type'] == 'hospital_admin') {
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
    <title>Hospital Portal - SanguiSense</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h2>🏥 Hospital Portal</h2>
            <p style="text-align: center; margin-bottom: 2rem; color: var(--dark-gray);">
                Welcome to SanguiSense Hospital Management System
            </p>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h3 style="color: var(--hospital-blue); margin-bottom: 1rem;">Hospital Admin Access</h3>
                    <div style="text-align: left; font-size: 0.9rem;">
                        <p><strong>Test Account:</strong><br>admin@hospital.com<br>Password: password</p>
                    </div>
                </div>
            </div>

            <div style="display: grid; gap: 1rem;">
                <a href="login.php" class="btn btn-primary" style="text-decoration: none; display: block; background: var(--hospital-blue);">
                    Hospital Login
                </a>
                <a href="../donor/index.php" class="btn btn-secondary" style="text-decoration: none; display: block;">
                    Go to Donor Portal
                </a>
                <a href="../bloodbank/index.php" class="btn btn-secondary" style="text-decoration: none; display: block;">
                    Go to Blood Bank Portal
                </a>
            </div>
        </div>
    </div>
</body>
</html>