<?php
include '../includes/config.php';
// config.php starts the session; avoid duplicate session_start()
include '../includes/auth.php';

requireLogin();

$donation_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$donation_id) {
    $_SESSION['error'] = 'Invalid donation ID.';
    header('Location: history.php');
    exit;
}

try {
    // Get donation details
    $stmt = $pdo->prepare("SELECT * FROM donations WHERE id = ? AND donor_id = ?");
    $stmt->execute([$donation_id, $user_id]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donation) {
        $_SESSION['error'] = 'Donation not found.';
        header('Location: history.php');
        exit;
    }

    // Only allow cancellation of scheduled donations
    if ($donation['status'] !== 'scheduled') {
        $_SESSION['error'] = 'Only scheduled donations can be cancelled.';
        header('Location: history.php');
        exit;
    }

    // Check if the donation is in the future
    $donation_date = strtotime($donation['donation_date']);
    if ($donation_date < time()) {
        $_SESSION['error'] = 'Cannot cancel past donations.';
        header('Location: history.php');
        exit;
    }

    // Update donation status to cancelled
    $stmt = $pdo->prepare("UPDATE donations SET status = 'cancelled' WHERE id = ? AND donor_id = ?");
    $stmt->execute([$donation_id, $user_id]);

    $_SESSION['message'] = 'Donation cancelled successfully.';
    header('Location: history.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    header('Location: history.php');
    exit;
}
?>
