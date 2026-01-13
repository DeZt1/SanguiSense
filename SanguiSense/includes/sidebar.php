<?php
// Shared sidebar include for SanguiSense portals
// Outputs a fixed sidebar with logo and navigation links, with active page highlighting.

// Detect portal and current page
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current_file = basename($path); // e.g., 'dashboard.php'
$parts = array_filter(explode('/', trim($path, '/')));
$portal = ''; // e.g., 'patient', 'hospital', 'donor', 'bloodbank'

foreach (['patient', 'hospital', 'donor', 'bloodbank'] as $p) {
    if (in_array($p, $parts)) {
        $portal = $p;
        break;
    }
}

$base = '/sanguisense';
$prefix = $base . ($portal ? "/$portal" : '');

// Helper: render nav link with active class if current file matches
function ss_link($href, $label, $current_file) {
    $link_file = basename(parse_url($href, PHP_URL_PATH));
    $is_active = ($link_file === $current_file) ? 'active' : '';
    return "<a href='{$href}' class='ss-nav-link {$is_active}'>{$label}</a>";
}

// Output stylesheet
echo "<link rel=\"stylesheet\" href=\"$base/includes/sidebar.css\">\n";

?>

<aside class="ss-sidebar">
    <div class="ss-header">
        <a class="ss-logo" href="<?php echo $base; ?>/index.php">
            <span class="blood-drop">🩸</span>
            <span class="ss-title">SanguiSense</span>
        </a>
    </div>
    <nav class="ss-nav">
        <ul>
            <li><?php echo ss_link($prefix.'/dashboard.php', 'Dashboard', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/index.php', 'Home', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/profile.php', 'Profile', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/find_donors.php', 'Find Donors', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/send_request.php', 'Send Request', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/request_history.php', 'Requests', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/inventory.php', 'Inventory', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/donors.php', 'Donors', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/schedule.php', 'Schedule', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/history.php', 'History', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/appointments.php', 'Appointments', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/blood_requests.php', 'Blood Requests', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/analytics.php', 'Analytics', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/contact_donor.php', 'Contact Donor', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/contact_bloodbank.php', 'Contact BloodBank', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/donor_details.php', 'Donor Details', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/edit_inventory.php', 'Edit Inventory', $current_file); ?></li>
            <li><a href='<?php echo $base; ?>/includes/auth.php?logout=1' class='ss-nav-link'>Logout</a></li>
        </ul>
    </nav>
</aside>
