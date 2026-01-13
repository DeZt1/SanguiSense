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
            </p>
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