<?php
// Ensure session is started before checking session variables
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (isset($_SESSION['user_id'])) {
    // Redirect logged-in users to dashboard
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Portal - SanguiSense Blood Management System</title>
    <link rel="stylesheet" href="css/patient.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php 
    // Session already started above; only include sidebar for logged-in users
    if (isset($_SESSION['user_id'])):
        include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_patient.php';
    endif;
    ?>

    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content" style="max-width: 900px;">
            <h1>Patient Blood Request Portal</h1>
            <p>Find available blood donors, send formal requests to hospitals and blood banks, and manage your blood donation needs all in one place.</p>
            <div class="hero-buttons">
                <a href="login.php" class="btn btn-primary">Login</a>
                <a href="register.php" class="btn btn-secondary">Register Now</a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="about-section" style="background: rgba(255, 255, 255, 0.95); margin: 5rem 0;">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 3rem; font-size: 2.8rem; color: #00bcd4; font-weight: 700;">Why Choose Our Patient Portal?</h2>
            
            <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
                <div class="feature-card" style="background: white; padding: 2.5rem 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border-left: 4px solid #00bcd4; color: #2c3e50;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🗺️</div>
                    <h3 style="color: #00bcd4; margin-bottom: 1rem; font-size: 1.4rem; font-weight: 600;">Interactive Map</h3>
                    <p style="line-height: 1.6;">Browse available donors on our real-time interactive map. Filter by blood type and location to find the perfect match.</p>
                </div>

                <div class="feature-card" style="background: white; padding: 2.5rem 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border-left: 4px solid #00bcd4; color: #2c3e50;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📋</div>
                    <h3 style="color: #00bcd4; margin-bottom: 1rem; font-size: 1.4rem; font-weight: 600;">Formal Requests</h3>
                    <p style="line-height: 1.6;">Submit formal blood requests to hospitals and blood banks with detailed medical information and urgency levels.</p>
                </div>

                <div class="feature-card" style="background: white; padding: 2.5rem 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border-left: 4px solid #00bcd4; color: #2c3e50;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📊</div>
                    <h3 style="color: #00bcd4; margin-bottom: 1rem; font-size: 1.4rem; font-weight: 600;">Track Status</h3>
                    <p style="line-height: 1.6;">Real-time tracking of your requests. Receive instant notifications on approvals and status updates.</p>
                </div>

                <div class="feature-card" style="background: white; padding: 2.5rem 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border-left: 4px solid #00bcd4; color: #2c3e50;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">👥</div>
                    <h3 style="color: #00bcd4; margin-bottom: 1rem; font-size: 1.4rem; font-weight: 600;">Connect with Donors</h3>
                    <p style="line-height: 1.6;">Reach out directly to donors on our platform and build trust through secure communication.</p>
                </div>

                <div class="feature-card" style="background: white; padding: 2.5rem 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border-left: 4px solid #00bcd4; color: #2c3e50;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏥</div>
                    <h3 style="color: #00bcd4; margin-bottom: 1rem; font-size: 1.4rem; font-weight: 600;">Verified Facilities</h3>
                    <p style="line-height: 1.6;">Request blood from verified hospitals and blood banks in your area with complete facility information.</p>
                </div>

                <div class="feature-card" style="background: white; padding: 2.5rem 2rem; border-radius: 15px; text-align: center; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; border-left: 4px solid #00bcd4; color: #2c3e50;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔒</div>
                    <h3 style="color: #00bcd4; margin-bottom: 1rem; font-size: 1.4rem; font-weight: 600;">Secure & Private</h3>
                    <p style="line-height: 1.6;">Your health data is encrypted and protected. Complete privacy and HIPAA-compliant data handling.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- How It Works -->
    <div class="container" style="margin: 5rem auto; color: white; text-align: center;">
        <h2 style="font-size: 2.8rem; margin-bottom: 3rem; color: #00bcd4; font-weight: 700;">How It Works</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem;">
            <div style="background: rgba(0, 188, 212, 0.1); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2);">
                <div style="font-size: 3rem; color: #00bcd4; margin-bottom: 1rem; font-weight: bold;">1</div>
                <h3 style="color: #00bcd4; margin-bottom: 1rem;">Create Your Account</h3>
                <p>Register as a patient and complete your health profile with blood type and medical information.</p>
            </div>

            <div style="background: rgba(0, 188, 212, 0.1); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2);">
                <div style="font-size: 3rem; color: #00bcd4; margin-bottom: 1rem; font-weight: bold;">2</div>
                <h3 style="color: #00bcd4; margin-bottom: 1rem;">Find Donors & Facilities</h3>
                <p>Browse available donors on the map or send requests directly to hospitals and blood banks.</p>
            </div>

            <div style="background: rgba(0, 188, 212, 0.1); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2);">
                <div style="font-size: 3rem; color: #00bcd4; margin-bottom: 1rem; font-weight: bold;">3</div>
                <h3 style="color: #00bcd4; margin-bottom: 1rem;">Submit Requests</h3>
                <p>Submit formal blood requests with urgency levels and medical details for processing.</p>
            </div>

            <div style="background: rgba(0, 188, 212, 0.1); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2);">
                <div style="font-size: 3rem; color: #00bcd4; margin-bottom: 1rem; font-weight: bold;">4</div>
                <h3 style="color: #00bcd4; margin-bottom: 1rem;">Track & Receive</h3>
                <p>Monitor your requests in real-time and receive notifications when approved or fulfilled.</p>
            </div>
        </div>
    </div>

    <!-- CTA Section -->
    <div style="background: linear-gradient(135deg, rgba(0, 188, 212, 0.15), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(10px); padding: 3rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.3); text-align: center; margin: 5rem auto; max-width: 800px; color: white;">
        <h2 style="font-size: 2.2rem; color: #00bcd4; margin-bottom: 1rem;">Ready to Get Started?</h2>
        <p style="margin-bottom: 2rem; font-size: 1.1rem;">Join thousands of patients who trust SanguiSense for their blood management needs.</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="register.php" class="btn btn-primary">Create Account</a>
            <a href="login.php" class="btn btn-secondary">Already Have an Account?</a>
        </div>
    </div>

    <!-- Footer -->
    <div style="background: rgba(26, 26, 26, 0.95); color: white; text-align: center; padding: 2rem 0; margin-top: 5rem; border-top: 2px solid #00bcd4;">
        <p>&copy; 2025 SanguiSense Patient Portal. All rights reserved.</p>
        <p style="font-size: 0.9rem; color: #b0b0b0; margin-top: 0.5rem;">Dedicated to connecting patients with life-saving blood donations.</p>
    </div>

    <script src="js/patient.js"></script>
</body>
</html>
