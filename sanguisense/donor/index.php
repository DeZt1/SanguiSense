<?php include '../includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SanguiSense - Smart Blood Donation Platform</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <!-- Navbar - Only visible when user is logged in -->
    <?php 
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    if (isset($_SESSION['user_id'])): ?>
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_donor.php'; ?>
    <?php endif; ?>

    <section class="hero">
        <div class="hero-content">
            <h1>Donate Blood, Save Lives</h1>
            <p>SanguiSense connects donors with hospitals and blood banks in need. Join our community of life-savers today.</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn btn-primary">Become a Donor</a>
                <a href="login.php" class="btn btn-secondary">Donor Login</a>
            </div>
        </div>
    </section>

    <section id="about" class="about-section">
        <div class="container">
            <h2>How SanguiSense Works</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <h3>Register</h3>
                    <p>Create your donor profile with blood type and location information</p>
                </div>
                <div class="feature-card">
                    <h3>Get Notified</h3>
                    <p>Receive alerts when your blood type is needed in your area</p>
                </div>
                <div class="feature-card">
                    <h3>Schedule</h3>
                    <p>Book donation appointments at convenient locations</p>
                </div>
                <div class="feature-card">
                    <h3>Save Lives</h3>
                    <p>Your donation helps patients in critical need</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2023 SanguiSense. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>