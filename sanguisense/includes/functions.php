<?php
include 'config.php';
include 'locations.php';
include 'dropdown_components.php';

// Function to redirect users
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get user data
function getUserData($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to add notification
function addNotification($user_id, $title, $message, $type = 'info') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$user_id, $title, $message, $type]);
}

// Function to get notifications - FIXED VERSION
function getNotifications($user_id, $limit = 5) {
    global $pdo;
    // Use integer directly in SQL for LIMIT with MariaDB
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT " . (int)$limit);
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get user's facility (for admins)
function getUserFacility($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT f.* FROM facilities f WHERE f.admin_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get hospital stats
function getHospitalStats($facility_id) {
    global $pdo;
    $stats = [];
    
    // Total eligible donors (exclude donors whose last fulfilled donation is within 56 days)
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total_donors
         FROM users u
         LEFT JOIN (
             SELECT donor_id, MAX(donation_date) AS last_donation
             FROM donations
             WHERE status = 'fulfilled'
             GROUP BY donor_id
         ) ld ON u.id = ld.donor_id
                 WHERE u.user_type = 'donor'
                     AND (
                             ld.last_donation IS NULL OR ld.last_donation <= DATE_SUB(NOW(), INTERVAL 56 DAY)
                     )
                     AND (u.eligibility_status IS NULL OR u.eligibility_status != 'ineligible')"
    );
    $stmt->execute();
    $stats['total_donors'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_donors'];
    
    // Pending donations for this hospital
    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_donations FROM donations WHERE status = 'scheduled' AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['pending_donations'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending_donations'];
    
    // Recent donations for this hospital
    $stmt = $pdo->prepare("SELECT COUNT(*) as recent_donations FROM donations WHERE donation_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['recent_donations'] = $stmt->fetch(PDO::FETCH_ASSOC)['recent_donations'];
    
    // Blood requests for this hospital
    $stmt = $pdo->prepare("SELECT COUNT(*) as blood_requests FROM demand_forecasts WHERE predicted_demand > 0 AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['blood_requests'] = $stmt->fetch(PDO::FETCH_ASSOC)['blood_requests'];
    
    return $stats;
}

// Function to get blood bank stats
function getBloodBankStats($facility_id) {
    global $pdo;
    $stats = [];
    
    // Total inventory for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_inventory FROM inventory WHERE facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['total_inventory'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_inventory'];
    
    // Low stock items for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as low_stock FROM inventory WHERE quantity < 10 AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['low_stock'];
    
    // Expiring soon for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as expiring_soon FROM inventory WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['expiring_soon'] = $stmt->fetch(PDO::FETCH_ASSOC)['expiring_soon'];
    
    // Total donations for this blood bank
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_donations FROM donations WHERE status = 'fulfilled' AND facility_id = ?");
    $stmt->execute([$facility_id]);
    $stats['total_donations'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_donations'];
    
    return $stats;
}

// Function to handle profile picture upload
function uploadProfilePicture($file, $user_id) {
    // Validate file upload
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred.');
    }
    
    // Check file size (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds 5MB limit.');
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($file['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, and GIF are allowed.');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = __DIR__ . '/../uploads/profile_pictures/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    // Delete old profile picture if exists
    global $pdo;

    // Ensure the users table has the profile_picture column. If it's missing,
    // create it so the update/select below won't fail. This is a lightweight
    // migration performed on-demand to avoid fatal exceptions on installations
    // that were created from an earlier schema without the column.
    try {
        $colCheck = $pdo->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_picture'");
        $colCheck->execute();
        $hasCol = (int)$colCheck->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        if (!$hasCol) {
            // Add column with a NULLable VARCHAR(255)
            $pdo->exec("ALTER TABLE `users` ADD COLUMN `profile_picture` VARCHAR(255) NULL AFTER `created_at`");
        }
    } catch (Exception $e) {
        // If information_schema is not accessible or ALTER fails, continue gracefully
        // but don't prevent the upload file move. We'll not attempt DB update in that case.
        $hasCol = false;
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file.');
    }

    // Update database with profile picture filename if column exists
    if (!empty($hasCol)) {
        try {
            $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result && $result['profile_picture']) {
                $old_file = __DIR__ . '/../uploads/profile_pictures/' . $result['profile_picture'];
                if (file_exists($old_file)) {
                    @unlink($old_file);
                }
            }

            $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $stmt->execute([$filename, $user_id]);
        } catch (Exception $e) {
            // swallow DB errors to avoid breaking the upload flow
        }
    }

    return $filename;
}

// Function to get profile picture URL
function getProfilePictureUrl($user_id) {
    global $pdo;
    try {
        // Ensure column exists before querying to avoid SQL errors on older schemas
        $colCheck = $pdo->prepare("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'profile_picture'");
        $colCheck->execute();
        $hasCol = (int)$colCheck->fetch(PDO::FETCH_ASSOC)['cnt'] > 0;
        if ($hasCol) {
            $stmt = $pdo->prepare("SELECT profile_picture FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['profile_picture'])) {
                return '/sanguisense/uploads/profile_pictures/' . htmlspecialchars($result['profile_picture']);
            }
        }
    } catch (Exception $e) {
        // anything goes wrong, fall back to default avatar
    }

    return '/sanguisense/assets/default-avatar.svg';
}

// Ensure donor_communications table exists. Safe to call repeatedly.
function ensure_donor_communications_table_exists() {
    global $pdo;
    try {
        $sql = "CREATE TABLE IF NOT EXISTS `donor_communications` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `donor_id` INT UNSIGNED NOT NULL,
            `subject` VARCHAR(255) DEFAULT NULL,
            `message` TEXT,
            `contact_method` VARCHAR(32) DEFAULT 'email',
            `sent_by` INT UNSIGNED DEFAULT NULL,
            `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX (`donor_id`),
            INDEX (`sent_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $pdo->exec($sql);
        return true;
    } catch (Exception $e) {
        // Don't throw — just return false so callers can handle gracefully
        return false;
    }
}
?>