<?php
include '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');

    if (!$name || !$email || !$password) {
        $error = 'Please fill in all required fields';
    } else {
        global $pdo;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'An account with that email already exists';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $insert = $pdo->prepare("INSERT INTO users (name, email, password, user_type, phone, created_at) VALUES (?, ?, ?, 'bloodbank_admin', ?, NOW())");
            $ok = $insert->execute([$name, $email, $hash, $phone]);
            if ($ok) {
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed, please try again';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Register - Blood Bank Portal</title>
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <div class="background-animation"></div>
    <div class="auth-container">
        <div class="auth-form">
            <h2>🩸 Blood Bank Admin Register</h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="name">Full name</label>
                    <input id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone (optional)</label>
                    <input id="phone" name="phone">
                </div>

                <button class="btn btn-primary" type="submit" style="background: var(--bloodbank-purple);">Register</button>
            </form>

            <p class="auth-link" style="margin-top:1rem;">
                Already have an account? <a href="login.php">Login here</a>
            </p>
            <p class="auth-link">
                <a href="index.php">← Back to Blood Bank Portal</a>
            </p>
        </div>
    </div>
</body>
</html>
