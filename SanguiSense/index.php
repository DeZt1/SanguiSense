<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SanguiSense - Blood Bank Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #2c3e50;
        }

        /* Navigation */
        nav {
            background: linear-gradient(135deg, #c41e3a 0%, #8b1428 100%);
            padding: 1.2rem 0;
            position: sticky;
            top: 0;
            box-shadow: 0 8px 32px rgba(196, 30, 58, 0.15);
            z-index: 100;
            backdrop-filter: blur(10px);
        }

        nav .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav .logo {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            letter-spacing: -0.5px;
        }

        nav .nav-links {
            display: flex;
            gap: 2.5rem;
            list-style: none;
        }

        nav a {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.5px;
        }

        nav a:hover {
            opacity: 0.8;
            transform: translateY(-2px);
        }

        /* Header */
        .hero {
            background: linear-gradient(135deg, #c41e3a 0%, #8b1428 100%);
            color: white;
            padding: 120px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero .container {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
            font-family: 'Playfair Display', serif;
            letter-spacing: -1px;
            line-height: 1.1;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 2rem;
            opacity: 0.95;
            font-weight: 300;
            letter-spacing: 0.5px;
        }

        .cta-button {
            display: inline-block;
            background: white;
            color: #c41e3a;
            padding: 14px 35px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            letter-spacing: 0.5px;
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Portals Section */
        .portals {
            padding: 100px 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f2f5 100%);
        }

        .portals h2 {
            text-align: center;
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .portals h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #c41e3a 0%, #8b1428 100%);
            margin: 1rem auto 3rem;
            border-radius: 2px;
        }

        .portals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .portal-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: #2c3e50;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .portal-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(196, 30, 58, 0.15);
        }

        .portal-header {
            background: linear-gradient(135deg, #c41e3a 0%, #8b1428 100%);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .portal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .portal-header h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .portal-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .portal-body {
            padding: 2.5rem;
        }

        .portal-body p {
            margin-bottom: 1.5rem;
            color: #555;
            line-height: 1.8;
            font-size: 0.95rem;
        }

        .portal-link {
            display: inline-block;
            background: linear-gradient(135deg, #c41e3a 0%, #8b1428 100%);
            color: white;
            padding: 11px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(196, 30, 58, 0.25);
        }

        .portal-link:hover {
            transform: translateX(4px);
            box-shadow: 0 8px 20px rgba(196, 30, 58, 0.35);
        }

        /* Features Section */
        .features {
            padding: 100px 20px;
            background: white;
        }

        .features h2 {
            text-align: center;
            font-size: 2.8rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .features h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #c41e3a 0%, #8b1428 100%);
            margin: 1rem auto 3rem;
            border-radius: 2px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .feature-item {
            text-align: center;
            padding: 1.5rem;
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .feature-item:hover {
            background: rgba(196, 30, 58, 0.05);
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
        }

        .feature-item h3 {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            color: #c41e3a;
            font-weight: 700;
        }

        .feature-item p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.7;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: white;
            text-align: center;
            padding: 3rem 2rem;
            margin-top: 0;
        }

        footer p {
            opacity: 0.9;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            letter-spacing: 0.3px;
        }

        footer p:first-child {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .portals h2,
            .features h2 {
                font-size: 2rem;
            }

            nav .nav-links {
                gap: 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="/" class="logo">🩸 SanguiSense</a>
            <ul class="nav-links">
                <li><a href="#portals">Portals</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Welcome to SanguiSense</h1>
            <p>Advanced Blood Bank Management System</p>
            <p style="font-size: 1rem; opacity: 0.9;">Connecting Donors, Hospitals, Blood Banks, and Patients</p>
        </div>
    </section>

    <!-- Portals Section -->
    <section class="portals" id="portals">
        <div class="container">
            <h2>Access Your Portal</h2>
            <div class="portals-grid">
                <!-- Hospital Portal -->
                <a href="hospital/login.php" class="portal-card">
                    <div class="portal-header">
                        <div class="portal-icon">🏥</div>
                        <h3>Hospital Portal</h3>
                    </div>
                    <div class="portal-body">
                        <p>Manage blood requests, patient profiles, and coordinate with blood banks for timely blood supply.</p>
                        <a href="hospital/login.php" class="portal-link">Access Portal →</a>
                    </div>
                </a>

                <!-- Patient Portal -->
                <a href="patient/login.php" class="portal-card">
                    <div class="portal-header">
                        <div class="portal-icon">👤</div>
                        <h3>Patient Portal</h3>
                    </div>
                    <div class="portal-body">
                        <p>Track your blood requests, view donation history, and receive notifications about blood availability.</p>
                        <a href="patient/login.php" class="portal-link">Access Portal →</a>
                    </div>
                </a>

                <!-- Donor Portal -->
                <a href="donor/login.php" class="portal-card">
                    <div class="portal-header">
                        <div class="portal-icon">💪</div>
                        <h3>Donor Portal</h3>
                    </div>
                    <div class="portal-body">
                        <p>Register as a donor, track your donations, schedule appointments, and help save lives.</p>
                        <a href="donor/login.php" class="portal-link">Access Portal →</a>
                    </div>
                </a>

                <!-- Blood Bank Portal -->
                <a href="bloodbank/login.php" class="portal-card">
                    <div class="portal-header">
                        <div class="portal-icon">🧬</div>
                        <h3>Blood Bank Portal</h3>
                    </div>
                    <div class="portal-body">
                        <p>Manage inventory, process donations, track blood units, and fulfill hospital requests efficiently.</p>
                        <a href="bloodbank/login.php" class="portal-link">Access Portal →</a>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <h2>Key Features</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <h3>Real-time Analytics</h3>
                    <p>Track blood inventory, donation trends, and hospital requests in real-time with comprehensive dashboards.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔐</div>
                    <h3>Secure Access</h3>
                    <p>Role-based access control ensures data security and privacy for all users.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📱</div>
                    <h3>Responsive Design</h3>
                    <p>Access the system from any device - desktop, tablet, or mobile phone.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🔔</div>
                    <h3>Notifications</h3>
                    <p>Get instant alerts about donation appointments, blood availability, and request updates.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🌍</div>
                    <h3>Multi-Location Support</h3>
                    <p>Manage multiple blood banks and hospitals across different locations.</p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📈</div>
                    <h3>Detailed Reporting</h3>
                    <p>Generate comprehensive reports on donations, inventory, and hospital demands.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact">
        <div class="container">
            <p>&copy; 2026 SanguiSense. All rights reserved.</p>
            <p>Advanced Blood Bank Management System</p>
        </div>
    </footer>
</body>
</html>
