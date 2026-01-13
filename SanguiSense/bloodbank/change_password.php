<?php
include '../includes/config.php';
// config.php starts the session, avoid calling session_start() again
include '../includes/auth.php';

requireLogin();

$message = '';
$error = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            // Get current user password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = 'User not found.';
            } elseif (!password_verify($old_password, $user['password'])) {
                $error = 'Old password is incorrect.';
            } else {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);

                $message = 'Password changed successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Blood Bank Admin</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .change-password-container {
            max-width: 500px;
            margin: 80px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .change-password-container h1 {
            color: var(--bloodbank-purple, #8e44ad);
            margin-bottom: 30px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--bloodbank-purple, #8e44ad);
            box-shadow: 0 0 5px rgba(142, 68, 173, 0.3);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--bloodbank-purple, #8e44ad);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-submit:hover {
            background: #7d3c98;
        }

        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--bloodbank-purple, #8e44ad);
            text-decoration: none;
            font-weight: 600;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <h1>Change Password</h1>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="old_password">Old Password *</label>
                <input 
                    type="password" 
                    id="old_password" 
                    name="old_password" 
                    required
                    placeholder="Enter your current password"
                >
            </div>

            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    required
                    placeholder="Enter new password (min. 8 characters)"
                    minlength="8"
                >
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    required
                    placeholder="Re-enter new password"
                    minlength="8"
                >
            </div>

            <button type="submit" class="btn-submit">Change Password</button>
        </form>

        <div class="back-link">
            <a href="profile.php">← Back to Profile</a>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
