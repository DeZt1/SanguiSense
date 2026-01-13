<?php
include '../includes/auth.php';
requireHospitalAdmin();

if (!isset($_GET['id'])) {
    header("Location: donors.php");
    exit();
}

$donor_id = $_GET['id'];

// Get donor details
global $pdo;
$donor_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'donor'");
$donor_stmt->execute([$donor_id]);
$donor = $donor_stmt->fetch(PDO::FETCH_ASSOC);

// Ensure communications table exists so history and inserts won't fatal-error
if (function_exists('ensure_donor_communications_table_exists')) {
    @ensure_donor_communications_table_exists();
}

if (!$donor) {
    header("Location: donors.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $contact_method = $_POST['contact_method'];
    
    if (!$subject || !$message) {
        $error = 'Please fill in all required fields';
    } else {
        try {
            // Store the message in database for record keeping
            $insert_stmt = $pdo->prepare(
                "INSERT INTO donor_communications (donor_id, subject, message, contact_method, sent_by, sent_at) VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $insert_stmt->execute([
                $donor_id, 
                $subject, 
                $message, 
                $contact_method, 
                $_SESSION['user_id']
            ]);
            
            // In a real system, you would:
            if ($contact_method === 'email') {
                // Send actual email using PHP mail() or SMTP
                $to = $donor['email'];
                $headers = "From: hospital@sanguisense.com\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                
                $email_body = "
                    <html>
                    <body>
                        <h3>$subject</h3>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                        <hr>
                        <p><small>This message was sent from SanguiSense Hospital. Please do not reply to this email.</small></p>
                    </body>
                    </html>
                ";
                
                // mail($to, $subject, $email_body, $headers); // Uncomment in production
            }

            $success = "Message sent to donor successfully!";

        } catch(PDOException $e) {
            $error = "Failed to send message: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Donor - Hospital Portal</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_hospital.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>Contact Donor</h1>
            <p>Send messages to donors for appointments, requests, or follow-ups</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="content-grid">
            <div class="content-card">
                <h3>Donor Information</h3>
                <div class="admin-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Donor Name</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px; font-weight: bold;">
                                <?php echo htmlspecialchars($donor['name']); ?>
                            </p>
                        </div>
                        <div class="form-group">
                            <label>Blood Type</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px; font-weight: bold;">
                                <?php echo htmlspecialchars($donor['blood_type']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;">
                                <?php echo htmlspecialchars($donor['email']); ?>
                            </p>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <p style="padding: 0.8rem; background: #f5f5f5; border-radius: 5px;">
                                <?php echo htmlspecialchars($donor['phone'] ?: 'Not provided'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h3>Quick Templates</h3>
                <div class="action-buttons-vertical">
                    <button type="button" onclick="loadTemplate('appointment')" class="btn btn-secondary">
                        Schedule Appointment
                    </button>
                    <button type="button" onclick="loadTemplate('emergency')" class="btn btn-secondary">
                        Emergency Request
                    </button>
                    <button type="button" onclick="loadTemplate('thankyou')" class="btn btn-secondary">
                        Thank You Message
                    </button>
                    <button type="button" onclick="loadTemplate('followup')" class="btn btn-secondary">
                        Follow-up
                    </button>
                </div>
                
                <div style="margin-top: 2rem;">
                    <h4>Contact History</h4>
                    <?php
                    try {
                        $history_stmt = $pdo->prepare("SELECT dc.*, u.name as sent_by_name FROM donor_communications dc LEFT JOIN users u ON dc.sent_by = u.id WHERE dc.donor_id = ? ORDER BY dc.sent_at DESC LIMIT 5");
                        $history_stmt->execute([$donor_id]);
                        $contact_history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $contact_history = [];
                        $error = isset($error) ? $error : "Contact history unavailable: " . htmlspecialchars($e->getMessage());
                    }
                    ?>
                    
                    <?php if (empty($contact_history)): ?>
                        <p style="color: #666; text-align: center;">No previous contact</p>
                    <?php else: ?>
                        <div style="max-height: 200px; overflow-y: auto;">
                            <?php foreach ($contact_history as $history): ?>
                                <div style="border-left: 3px solid var(--hospital-blue); padding: 0.5rem 1rem; margin-bottom: 0.5rem; background: rgba(255,255,255,0.1);">
                                    <strong><?php echo htmlspecialchars($history['subject']); ?></strong>
                                    <br>
                                    <small>By <?php echo htmlspecialchars($history['sent_by_name']); ?> on <?php echo date('M j, Y', strtotime($history['sent_at'])); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3>Compose Message</h3>
            <div class="admin-form">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact_method">Contact Method</label>
                            <select id="contact_method" name="contact_method" required>
                                <option value="email" selected>Email</option>
                                <option value="sms" <?php echo $donor['phone'] ? '' : 'disabled'; ?>>SMS Text</option>
                                <option value="phone">Phone Call</option>
                            </select>
                            <small id="method-help">
                                <?php if (!$donor['phone']): ?>
                                    SMS disabled - donor hasn't provided phone number
                                <?php endif; ?>
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="Enter message subject" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="8" placeholder="Type your message here..." required></textarea>
                        <small>This message will be sent to the donor via the selected contact method.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="send_message" class="btn btn-primary" style="background: var(--hospital-blue);">
                            Send Message
                        </button>
                        <a href="donors.php" class="btn btn-secondary">Cancel</a>
                        <a href="donor_details.php?id=<?php echo $donor_id; ?>" class="btn btn-secondary">
                            Back to Donor Details
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Safe donor variables (JSON encoded to escape properly)
        const donorName = <?php echo json_encode($donor['name']); ?>;
        const donorBlood = <?php echo json_encode($donor['blood_type']); ?>;
        const donorHasPhone = <?php echo json_encode(!empty($donor['phone'])); ?>;

        // Template messages (use donor vars)
        const templates = {
            appointment: {
                subject: "Blood Donation Appointment Scheduling",
                message: `Dear ${donorName},\n\nWe would like to schedule a blood donation appointment with you. Your ${donorBlood} blood type is currently in high demand and would be greatly appreciated.\n\nPlease reply to this message with your preferred date and time, or call us at (555) 123-4567 to schedule your appointment.\n\nThank you for your life-saving contributions!\n\nBest regards,\nSanguiSense Hospital Staff`
            },
            emergency: {
                subject: "URGENT: Emergency Blood Donation Request",
                message: `Dear ${donorName},\n\nWe have an urgent need for ${donorBlood} blood due to a critical emergency situation. Your immediate assistance could save lives.\n\nIf you are eligible to donate, please visit us as soon as possible today or contact us immediately at (555) 123-4567.\n\nYour prompt response is crucial in this emergency situation.\n\nEmergency Coordinator,\nSanguiSense Hospital`
            },
            thankyou: {
                subject: "Thank You for Your Recent Blood Donation",
                message: `Dear ${donorName},\n\nWe want to express our sincere gratitude for your recent blood donation. Your contribution of ${donorBlood} blood is making a significant difference in patients' lives.\n\nBecause of donors like you, we are able to maintain our blood supply and respond to emergency needs. Each donation can save up to three lives!\n\nWe look forward to seeing you again for your next donation when you become eligible.\n\nWith heartfelt thanks,\nSanguiSense Hospital Team`
            },
            followup: {
                subject: "Follow-up on Your Blood Donation Eligibility",
                message: `Dear ${donorName},\n\nWe're following up regarding your eligibility for blood donation. Based on our records, you may now be eligible to donate again.\n\nYour ${donorBlood} blood type continues to be in demand. Would you be available to schedule a donation appointment in the coming days?\n\nPlease let us know your availability or contact us at (555) 123-4567 to schedule.\n\nThank you for your ongoing support!\n\nBest regards,\nSanguiSense Hospital Staff`
            }
        };

        // Populate fields, set an appropriate contact method, and focus the compose form
        function loadTemplate(templateKey) {
            const template = templates[templateKey];
            if (!template) return;

            const subjectEl = document.getElementById('subject');
            const messageEl = document.getElementById('message');
            const methodEl = document.getElementById('contact_method');

            subjectEl.value = template.subject;
            messageEl.value = template.message;

            // Prefer SMS for appointments if donor has phone, otherwise email
            if (templateKey === 'appointment' && donorHasPhone) {
                methodEl.value = 'sms';
            } else {
                methodEl.value = 'email';
            }

            // Trigger change handler to update help text
            methodEl.dispatchEvent(new Event('change'));

            // Scroll to compose area and focus message for quick sending
            messageEl.focus();
            messageEl.scrollIntoView({behavior: 'smooth', block: 'center'});
        }

        // Update contact method help text
        document.getElementById('contact_method').addEventListener('change', function() {
            const method = this.value;
            const helpText = document.getElementById('method-help');
            
            if (method === 'sms') {
                helpText.textContent = 'SMS will be sent to donor\'s phone number';
                helpText.style.color = '#28a745';
            } else if (method === 'phone') {
                helpText.textContent = 'Please call the donor directly';
                helpText.style.color = '#ffc107';
            } else {
                helpText.textContent = 'Email will be sent to donor\'s email address';
                helpText.style.color = '#666';
            }
        });
    </script>
</body>
</html>