<?php
include '../includes/auth.php';
requireLogin();

$user = getUserData($_SESSION['user_id']);
global $pdo;

// Ensure users table has eligibility columns. If not, attempt to add them (safe to ignore failures).
function ensureEligibilityColumns($pdo) {
    try {
        $check = $pdo->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'eligibility_status'");
        $check->execute();
        $row = $check->fetch(PDO::FETCH_ASSOC);
        $exists = isset($row['cnt']) && intval($row['cnt']) > 0;
        if (!$exists) {
            // Add both columns: eligibility_status and eligibility_check_date
            $pdo->exec("ALTER TABLE users ADD COLUMN eligibility_status VARCHAR(20) DEFAULT NULL, ADD COLUMN eligibility_check_date DATETIME DEFAULT NULL");
        }
    } catch (Exception $e) {
        // If ALTER fails (permissions, etc.), silently continue — updates will be skipped.
    }
}

// Try to ensure columns exist and load any existing eligibility status for this user
try {
    ensureEligibilityColumns($pdo);
    $statusStmt = $pdo->prepare("SELECT eligibility_status FROM users WHERE id = ?");
    $statusStmt->execute([$_SESSION['user_id']]);
    $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);
    $existing_status = $statusRow['eligibility_status'] ?? null;
} catch (Exception $e) {
    // If anything fails (no column, no permission), fall back to session only
    $existing_status = $_SESSION['eligibility_status'] ?? null;
}

// If the user already has an eligibility status recorded, lock the form (one-time check only)
$form_locked = ($existing_status !== null);

// Check if form was submitted
$eligibility_passed = false;
$eligibility_errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // If form is locked (user already completed check), do not allow reposting
    if (!empty($form_locked)) {
        header('Location: eligibility_check.php');
        exit();
    }
    // Validate all fields
    
    // Basic Eligibility
    $age = intval($_POST['age'] ?? 0);
    $weight = intval($_POST['weight'] ?? 0);
    $health_status = $_POST['health_status'] ?? '';
    $hemoglobin = $_POST['hemoglobin'] ?? '';
    
    // Lifestyle Requirements
    $fever_illness = $_POST['fever_illness'] ?? '';
    $dental_work = $_POST['dental_work'] ?? '';
    $major_surgery = $_POST['major_surgery'] ?? '';
    $tattoo_piercing = $_POST['tattoo_piercing'] ?? '';
    
    // Medical Conditions
    $hiv_aids = $_POST['hiv_aids'] ?? '';
    $hepatitis = $_POST['hepatitis'] ?? '';
    $heart_disease = $_POST['heart_disease'] ?? '';
    $bleeding_disorder = $_POST['bleeding_disorder'] ?? '';
    $cancer = $_POST['cancer'] ?? '';
    $epilepsy = $_POST['epilepsy'] ?? '';
    
    // Recent Activities
    $pregnancy = $_POST['pregnancy'] ?? '';
    $malaria_travel = $_POST['malaria_travel'] ?? '';
    $intravenous_drugs = $_POST['intravenous_drugs'] ?? '';
    $sexual_risk = $_POST['sexual_risk'] ?? '';
    
    // Validate basic eligibility
    if ($age < 18 || $age > 65) {
        $eligibility_errors[] = "Age must be between 18 and 65 years old";
    }
    
    if ($weight < 50) {
        $eligibility_errors[] = "Weight must be at least 50 kg (110 lbs)";
    }
    
    if ($health_status !== 'yes') {
        $eligibility_errors[] = "You must be in generally good health and feeling well";
    }
    
    if ($hemoglobin !== 'yes') {
        $eligibility_errors[] = "You must have adequate iron levels";
    }
    
    // Validate lifestyle requirements
    if ($fever_illness !== 'no') {
        $eligibility_errors[] = "You must not have had fever or illness in the past 2 weeks";
    }
    
    if ($dental_work !== 'no') {
        $eligibility_errors[] = "You must not have had dental work in the past 24-72 hours";
    }
    
    if ($major_surgery !== 'no') {
        $eligibility_errors[] = "You must not have had major surgery in the past 6 months";
    }
    
    if ($tattoo_piercing !== 'no') {
        $eligibility_errors[] = "You must not have had tattoos or piercings in the past 3-6 months";
    }
    
    // Validate medical conditions (all must be "no")
    if ($hiv_aids !== 'no') {
        $eligibility_errors[] = "You must not have HIV/AIDS";
    }
    
    if ($hepatitis !== 'no') {
        $eligibility_errors[] = "You must not have Hepatitis B or C";
    }
    
    if ($heart_disease !== 'no') {
        $eligibility_errors[] = "You must not have heart, lung disease, or bleeding disorders";
    }
    
    if ($bleeding_disorder !== 'no') {
        $eligibility_errors[] = "You must not have bleeding disorders";
    }
    
    if ($cancer !== 'no') {
        $eligibility_errors[] = "You must not have cancer (except some cured skin cancers)";
    }
    
    if ($epilepsy !== 'no') {
        $eligibility_errors[] = "You must not have epilepsy with recent seizures";
    }
    
    // Validate recent activities
    if ($pregnancy !== 'no') {
        $eligibility_errors[] = "You must not be pregnant or within 6 weeks of delivery";
    }
    
    if ($malaria_travel !== 'no') {
        $eligibility_errors[] = "You must not have traveled to malaria-risk areas in the past 3-12 months";
    }
    
    if ($intravenous_drugs !== 'no') {
        $eligibility_errors[] = "You must not use intravenous drugs";
    }
    
    if ($sexual_risk !== 'no') {
        $eligibility_errors[] = "You must not engage in high-risk sexual activities";
    }
    
    // Check time since last donation
    if ($user['last_donation_date']) {
        $last_donation = strtotime($user['last_donation_date']);
        $days_since = floor((time() - $last_donation) / (60 * 60 * 24));
        if ($days_since < 56) { // 8 weeks
            $eligibility_errors[] = "You must wait at least 8 weeks (56 days) since your last donation. Last donation was {$days_since} days ago.";
        }
    }
    
    // If no errors, user passed eligibility
    if (empty($eligibility_errors)) {
        $eligibility_passed = true;
        
        // Store eligibility status in session
        $_SESSION['eligibility_passed'] = true;
        $_SESSION['eligibility_status'] = 'eligible';
        $_SESSION['eligibility_check_time'] = time();
        
        // Update user record with eligibility status (attempt to add columns if missing)
        try {
            ensureEligibilityColumns($pdo);
            $stmt = $pdo->prepare("UPDATE users SET eligibility_status = 'passed', eligibility_check_date = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {
            // ignore DB write failures
        }
        
        // Redirect to schedule page
        header('Location: schedule.php?eligibility=passed');
        exit();
    } else {
        // User is ineligible
        $_SESSION['eligibility_passed'] = false;
        $_SESSION['eligibility_status'] = 'ineligible';
        $_SESSION['eligibility_check_time'] = time();
        
        // Update user record with ineligibility status (attempt to add columns if missing)
        try {
            ensureEligibilityColumns($pdo);
            $stmt = $pdo->prepare("UPDATE users SET eligibility_status = 'ineligible', eligibility_check_date = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (Exception $e) {
            // ignore DB write failures
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blood Donation Eligibility Check - SanguiSense</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .eligibility-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .eligibility-form {
            background: linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05));
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1.3rem;
            color: var(--yellow);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .section-subtitle {
            font-size: 0.9rem;
            color: #ccc;
            margin-bottom: 1rem;
            font-style: italic;
        }

        .form-group {
            margin-bottom: 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            align-items: center;
        }

        .form-group.full-width {
            grid-template-columns: 1fr;
        }

        .form-group label {
            display: block;
            color: #fff;
            font-weight: 500;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group input[type="number"],
        .form-group input[type="text"],
        .form-group select {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 0.8rem;
            color: #fff;
            font-size: 0.95rem;
        }

        .form-group input::placeholder,
        .form-group select {
            color: #aaa;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--donor-cyan);
            box-shadow: 0 0 0 3px rgba(106, 194, 202, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 2rem;
            margin-top: 0.5rem;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .radio-option input[type="radio"] {
            cursor: pointer;
            width: 18px;
            height: 18px;
            accent-color: var(--donor-cyan);
        }

        .radio-option label {
            margin: 0;
            cursor: pointer;
            font-weight: 400;
            color: #fff;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
        }

        .error-list {
            list-style: none;
            padding: 0;
        }

        .error-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-list li::before {
            content: "✕";
            color: #ff6b6b;
            font-weight: bold;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.8rem 2rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--donor-cyan), #5ab4c2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(106, 194, 202, 0.3);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.15);
        }

        .info-box {
            background: rgba(30, 136, 229, 0.1);
            border: 1px solid rgba(30, 136, 229, 0.3);
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            color: #7fc8f0;
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/sanguisense/includes/sidebar_donor.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1>🩸 Blood Donation Eligibility Check</h1>
            <p>Please answer the following questions to verify your eligibility to donate blood</p>
        </div>

        <div class="eligibility-container">
            <?php if ($form_locked && ($existing_status === 'ineligible' || $existing_status === 'passed' || $existing_status === 'eligible')): ?>
                <!-- FORM LOCKED - SHOW PREVIOUS RESULT -->
                <?php if ($existing_status === 'ineligible'): ?>
                    <div style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(220, 53, 69, 0.1)); border: 2px solid rgba(220, 53, 69, 0.5); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; text-align: center;">
                        <h2 style="color: #ff6b6b; margin-bottom: 1rem;">❌ Not Eligible to Donate</h2>
                        <p style="color: #ccc; font-size: 1.1rem; margin-bottom: 1.5rem;">Based on your previous assessment, you are currently <strong>not eligible</strong> to donate blood. This restriction is in place to ensure your safety and the safety of blood recipients.</p>
                        <a href="dashboard.php" class="btn btn-secondary" style="display: inline-block;">Return to Dashboard</a>
                    </div>
                <?php elseif ($existing_status === 'passed' || $existing_status === 'eligible'): ?>
                    <div style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(40, 167, 69, 0.1)); border: 2px solid rgba(40, 167, 69, 0.5); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; text-align: center;">
                        <h2 style="color: #28a745; margin-bottom: 1rem;">✅ Eligible to Donate</h2>
                        <p style="color: #ccc; font-size: 1.1rem; margin-bottom: 1.5rem;">You have already been verified as eligible. You may proceed to schedule a donation.</p>
                        <a href="schedule.php" class="btn btn-primary" style="display: inline-block; padding: 1rem 2.5rem; font-size: 1.1rem;">Schedule Your Donation Now</a>
                    </div>
                <?php else: ?>
                    <div class="info-box">
                        <strong>ℹ️ Note:</strong> You have already completed the eligibility check.
                    </div>
                <?php endif; ?>
            <?php elseif (!empty($eligibility_errors) && $_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <!-- FORM SUBMITTED WITH ERRORS -->
                <div class="alert alert-error">
                    <div>
                        <strong>⚠️ Eligibility Issues Found:</strong>
                        <ul class="error-list">
                            <?php foreach ($eligibility_errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div style="background: rgba(255,255,255,0.05); padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem; margin-top: 1.5rem;">
                    <p style="color: #aaa; margin-bottom: 1rem;"><strong>What happens now:</strong></p>
                    <ul style="list-style: none; padding: 0; text-align: left; display: inline-block;">
                        <li style="padding: 0.5rem 0; color: #ccc;"><span style="color: #ff6b6b;">✕</span> You cannot schedule a donation at this time</li>
                        <li style="padding: 0.5rem 0; color: #ccc;"><span style="color: #ff6b6b;">✕</span> You cannot donate blood until your eligibility status changes</li>
                        <li style="padding: 0.5rem 0; color: #28a745;"><span style="color: #28a745;">✓</span> You may recheck your eligibility after addressing the issues above</li>
                        <li style="padding: 0.5rem 0; color: #28a745;"><span style="color: #28a745;">✓</span> Contact your healthcare provider if you have questions</li>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- ELIGIBILITY FORM - SHOW UNLESS FORM IS LOCKED -->
            <?php if (!$form_locked): ?>
                <form method="POST" class="eligibility-form">
                <!-- BASIC ELIGIBILITY CRITERIA -->
                <div class="form-section">
                    <div class="section-title">✅ Basic Eligibility Criteria</div>
                    <div class="section-subtitle">Required health information</div>

                    <div class="form-group">
                        <div>
                            <label for="age">Age *</label>
                            <input type="number" id="age" name="age" min="16" max="120" required placeholder="Enter your age">
                        </div>
                        <div>
                            <label for="weight">Weight (kg) *</label>
                            <input type="number" id="weight" name="weight" min="30" max="200" required placeholder="Enter your weight in kg">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Are you in generally good health and feeling well right now? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="health_yes" name="health_status" value="yes" required>
                                <label for="health_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="health_no" name="health_status" value="no">
                                <label for="health_no">No</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Do you have adequate iron levels / good hemoglobin? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="hemoglobin_yes" name="hemoglobin" value="yes" required>
                                <label for="hemoglobin_yes">Yes</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="hemoglobin_no" name="hemoglobin" value="no">
                                <label for="hemoglobin_no">No</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LIFESTYLE REQUIREMENTS -->
                <div class="form-section">
                    <div class="section-title">✅ Lifestyle Requirements</div>
                    <div class="section-subtitle">Recent activities and conditions</div>

                    <div class="form-group full-width">
                        <label>Have you had fever or illness in the past 2 weeks? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="fever_no" name="fever_illness" value="no" required>
                                <label for="fever_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="fever_yes" name="fever_illness" value="yes">
                                <label for="fever_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Have you had dental work in the past 24-72 hours? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="dental_no" name="dental_work" value="no" required>
                                <label for="dental_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="dental_yes" name="dental_work" value="yes">
                                <label for="dental_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Have you had major surgery in the past 6 months? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="surgery_no" name="major_surgery" value="no" required>
                                <label for="surgery_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="surgery_yes" name="major_surgery" value="yes">
                                <label for="surgery_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Have you had tattoos or piercings in the past 3-6 months? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="tattoo_no" name="tattoo_piercing" value="no" required>
                                <label for="tattoo_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="tattoo_yes" name="tattoo_piercing" value="yes">
                                <label for="tattoo_yes">Yes</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- MEDICAL CONDITIONS -->
                <div class="form-section">
                    <div class="section-title">❌ Medical Conditions</div>
                    <div class="section-subtitle">Do you have any of the following conditions?</div>

                    <div class="form-group full-width">
                        <label>HIV/AIDS *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="hiv_no" name="hiv_aids" value="no" required>
                                <label for="hiv_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="hiv_yes" name="hiv_aids" value="yes">
                                <label for="hiv_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Hepatitis B or C *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="hepatitis_no" name="hepatitis" value="no" required>
                                <label for="hepatitis_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="hepatitis_yes" name="hepatitis" value="yes">
                                <label for="hepatitis_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Heart, Lung Disease, or Bleeding Disorders *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="heart_no" name="heart_disease" value="no" required>
                                <label for="heart_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="heart_yes" name="heart_disease" value="yes">
                                <label for="heart_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Bleeding Disorders *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="bleeding_no" name="bleeding_disorder" value="no" required>
                                <label for="bleeding_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="bleeding_yes" name="bleeding_disorder" value="yes">
                                <label for="bleeding_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Cancer (except some cured skin cancers) *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="cancer_no" name="cancer" value="no" required>
                                <label for="cancer_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="cancer_yes" name="cancer" value="yes">
                                <label for="cancer_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Epilepsy with recent seizures *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="epilepsy_no" name="epilepsy" value="no" required>
                                <label for="epilepsy_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="epilepsy_yes" name="epilepsy" value="yes">
                                <label for="epilepsy_yes">Yes</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RECENT ACTIVITIES -->
                <div class="form-section">
                    <div class="section-title">❌ Recent Activities</div>
                    <div class="section-subtitle">Recent behaviors and circumstances</div>

                    <div class="form-group full-width">
                        <label>Are you pregnant or within 6 weeks of delivery? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="pregnancy_no" name="pregnancy" value="no" required>
                                <label for="pregnancy_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="pregnancy_yes" name="pregnancy" value="yes">
                                <label for="pregnancy_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Have you traveled to malaria-risk areas in the past 3-12 months? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="malaria_no" name="malaria_travel" value="no" required>
                                <label for="malaria_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="malaria_yes" name="malaria_travel" value="yes">
                                <label for="malaria_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Do you use intravenous drugs? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="drugs_no" name="intravenous_drugs" value="no" required>
                                <label for="drugs_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="drugs_yes" name="intravenous_drugs" value="yes">
                                <label for="drugs_yes">Yes</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Do you engage in high-risk sexual activities? *</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" id="sexual_no" name="sexual_risk" value="no" required>
                                <label for="sexual_no">No</label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" id="sexual_yes" name="sexual_risk" value="yes">
                                <label for="sexual_yes">Yes</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Check Eligibility</button>
                </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
