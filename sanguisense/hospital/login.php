<?php
include '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    if (login($email, $password)) {
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
    <title>Hospital Login - SanguiSense</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <div class="auth-container">
        <div class="auth-form">
            <h2>🏥 Hospital Login</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;">
                    <h3 style="color: var(--hospital-blue); margin-bottom: 1rem;">Test Account</h3>
                    <div style="text-align: left; font-size: 0.9rem;">
                        <p><strong>Email:</strong> admin@hospital.com</p>
                        <p><strong>Password:</strong> password</p>
                    </div>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="background: var(--hospital-blue);">Login to Hospital Portal</button>
            </form>
            
            <p class="auth-link" style="margin-top:1rem;">
                Don't have an account? <a href="register.php">Register as Hospital Admin</a>
            </p>
            <p class="auth-link">
                <a href="index.php">← Back to Hospital Portal</a>
            </p>
            <p class="auth-link">
                <a href="../donor/index.php">Go to Donor Portal</a> | 
                <a href="../bloodbank/index.php">Go to Blood Bank</a>
            </p>
        </div>
    </div>

    <script>
        // Simple form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields');
            }
        });
    </script>
</body>
</html>