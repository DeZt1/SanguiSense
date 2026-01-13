<?php
include '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Verify user is a patient
$query = "SELECT * FROM users WHERE id = ? AND user_type = 'patient'";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Donors - SanguiSense Patient Portal</title>
    <link rel="stylesheet" href="css/patient.css">
    <!-- Leaflet CSS for Maps -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <style>
        /* Leaflet map override styles */
        .leaflet-popup-content-wrapper {
            border-radius: 10px;
        }
        .leaflet-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_patient.php'; ?>

    <!-- Main Content -->
    <div class="patient-dashboard">
        <div class="dashboard-header">
            <h1>Find Available Donors</h1>
            <p>Browse donor locations and availability on our interactive map</p>
        </div>

        <!-- Filter Panel -->
        <div class="filter-panel">
            <h3>Filter Donors</h3>
            <div class="filter-group">
                <div class="form-group">
                    <label for="filterBloodType">Blood Type</label>
                    <select id="filterBloodType">
                        <option value="">All Blood Types</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filterCity">City (Nueva Ecija)</label>
                    <select id="filterCity">
                        <option value="">All Cities</option>
                        <option value="Cabanatuan">Cabanatuan City</option>
                        <option value="Gapan">Gapan City</option>
                        <option value="San Fernando">San Fernando</option>
                        <option value="Palayan">Palayan City</option>
                        <option value="San Jose City">San Jose City</option>
                        <option value="Muñoz">Muñoz</option>
                        <option value="General Tinio">General Tinio</option>
                        <option value="Aliaga">Aliaga</option>
                        <option value="Santa Cruz">Santa Cruz</option>
                        <option value="Talugtug">Talugtug</option>
                        <option value="Guimba">Guimba</option>
                        <option value="Tagkawayan">Tagkawayan</option>
                        <option value="Pantabangan">Pantabangan</option>
                        <option value="Cuyapo">Cuyapo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button id="filterBtn" class="btn btn-primary" style="width: 100%;">Search Donors</button>
                </div>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-container">
            <h2>Donor Locations Map</h2>
            <div id="donor-map"></div>
            <p style="color: #b0b0b0; margin-top: 1rem; font-size: 0.9rem;">
                <strong>Tip:</strong> Click on any donor marker to view their details and contact them.
            </p>
        </div>

        <!-- Donors List -->
        <div style="background: linear-gradient(135deg, rgba(0, 188, 212, 0.1), rgba(255, 255, 255, 0.05)); backdrop-filter: blur(10px); padding: 2rem; border-radius: 15px; border: 2px solid rgba(0, 188, 212, 0.2);">
            <h2 style="color: var(--patient-teal); margin-bottom: 1.5rem; font-size: 1.6rem;">Available Donors</h2>
            <div id="donorsList" class="donors-list">
                <!-- Donors will be loaded here by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Contact Donor Modal -->
    <div id="contactDonorModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="modal-header">
                <h3>Contact Donor</h3>
            </div>
            <form id="contactForm" onsubmit="submitContactRequest(event)">
                <input type="hidden" id="selectedDonorId" name="donor_id">
                
                <div class="form-group">
                    <label for="contactMessage">Message (Optional)</label>
                    <textarea id="contactMessage" name="message" rows="4" placeholder="Add any additional information..."></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Send Contact Request</button>
                    <button type="button" class="btn btn-secondary" onclick="closeContactDonorModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Leaflet JS for Maps -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script src="js/patient.js"></script>

    <script>
        // Additional contact form submission
        function submitContactRequest(event) {
            event.preventDefault();
            
            const donorId = document.getElementById('selectedDonorId').value;
            const message = document.getElementById('contactMessage').value;
            
            if (!donorId) {
                showAlert('Invalid donor selected.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('donor_id', donorId);
            formData.append('message', message);
            
            fetch('api/contact_donor.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Contact request sent successfully!', 'success');
                    closeContactDonorModal();
                    document.getElementById('contactForm').reset();
                } else {
                    showAlert(data.message || 'Error sending contact request.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error sending contact request.', 'error');
            });
        }
    </script>
</body>
</html>
