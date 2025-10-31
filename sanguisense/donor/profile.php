<?php
include '../includes/auth.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $health_conditions = $_POST['health_conditions'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ?, city = ?, health_conditions = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $address, $city, $health_conditions, $_SESSION['user_id']]);
        
        $success = "Profile updated successfully!";
        $user = getUserData($_SESSION['user_id']); // Refresh user data
    } catch(PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SanguiSense</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="background-animation"></div>
    
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <h2><a href="index.php" class="logo-link">
                <span class="blood-drop">🩸</span>SanguiSense
            </a></h2>
        </div>
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link">Dashboard</a>
            <a href="profile.php" class="nav-link">Profile</a>
            <a href="schedule.php" class="nav-link">Schedule</a>
            <a href="history.php" class="nav-link">History</a>
            <a href="../includes/auth.php?logout=1" class="nav-link logout-btn">Logout</a>
        </div>
    </div>
</nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>My Profile</h1>
            <p>Manage your personal information and donation preferences</p>
        </div>

        <div class="profile-container">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="profile-form">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="blood_type">Blood Type</label>
                            <input type="text" id="blood_type" value="<?php echo htmlspecialchars($user['blood_type']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_donation">Last Donation</label>
                            <input type="text" id="last_donation" value="<?php echo $user['last_donation_date'] ? htmlspecialchars($user['last_donation_date']) : 'Never'; ?>" disabled>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="health_conditions">Health Conditions (Optional)</label>
                        <textarea id="health_conditions" name="health_conditions" placeholder="Any health conditions we should know about..."><?php echo htmlspecialchars($user['health_conditions']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div class="profile-actions">
                <h3>Account Actions</h3>
                <div class="action-buttons">
                    <a href="schedule.php" class="btn btn-secondary">Schedule Donation</a>
                    <button onclick="showDeleteModal()" class="btn btn-danger">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Account Deletion</h3>
            <p>Are you sure you want to delete your account? This action cannot be undone.</p>
            <div class="modal-actions">
                <button onclick="hideDeleteModal()" class="btn btn-secondary">Cancel</button>
                <a href="delete_account.php" class="btn btn-danger">Delete Account</a>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        function showDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
    </script>
</body>
</html>