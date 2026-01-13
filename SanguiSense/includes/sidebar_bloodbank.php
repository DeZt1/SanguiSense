<?php
// Blood Bank Portal Sidebar Include
// Shows only: Dashboard, Inventory, Donations, Blood Requests, Distribution, Analytics, Logout

// Detect current page
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$current_file = basename($path);

$base = '/sanguisense';
$prefix = $base . '/bloodbank';

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
        <a class="ss-logo" href="<?php echo $base; ?>/bloodbank/dashboard.php">
            <span class="blood-drop">🩸</span>
            <span class="ss-title">SanguiSense</span>
        </a>
    </div>
    <nav class="ss-nav">
        <ul>
            <li><?php echo ss_link($prefix.'/dashboard.php', 'Dashboard', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/profile.php', 'Profile', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/facility_setup.php', 'Facility Setup', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/inventory.php', 'Inventory', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/donations.php', 'Donations', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/blood_requests.php', 'Blood Requests', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/distribution.php', 'Distribution', $current_file); ?></li>
            <li><?php echo ss_link($prefix.'/analytics.php', 'Analytics', $current_file); ?></li>
            <li><a href='<?php echo $base; ?>/includes/auth.php?logout=1' class='ss-nav-link'>Logout</a></li>
        </ul>
    </nav>
</aside>
