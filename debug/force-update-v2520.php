<?php
/**
 * Force Update to v2.5.20 - Direct File Verification and Cache Clear
 */

// Bootstrap WordPress
$wp_path = '/home/mobilitytrailblazers/public_html/vote/wp-load.php';
if (!file_exists($wp_path)) {
    // Try local environment
    $wp_path = '/var/www/html/wp-load.php';
}
require_once($wp_path);

// Security check
if (!current_user_can('manage_options')) {
    die('Access denied');
}

$plugin_path = WP_PLUGIN_DIR . '/mobility-trailblazers/';

echo "<h1>Force Update to Version 2.5.20</h1>";
echo "<pre style='background:#f5f5f5;padding:20px;'>";

// 1. Clear all caches first
echo "=== CLEARING ALL CACHES ===\n";
wp_cache_flush();
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
echo "✓ WordPress cache and transients cleared\n";

// Clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ PHP OpCache cleared\n";
}

// 2. Verify critical files contain v2.5.20 fixes
echo "\n=== VERIFYING v2.5.20 FIXES ===\n";

// Check frontend.js for parseFloat fix
$frontend_js_path = $plugin_path . 'assets/js/frontend.js';
if (file_exists($frontend_js_path)) {
    $js_content = file_get_contents($frontend_js_path);
    $has_parsefloat = strpos($js_content, 'var value = parseFloat($(this).val())') !== false;
    
    echo "frontend.js:\n";
    echo "  File size: " . filesize($frontend_js_path) . " bytes\n";
    echo "  Modified: " . date('Y-m-d H:i:s', filemtime($frontend_js_path)) . "\n";
    
    if ($has_parsefloat) {
        echo "  ✓ Contains parseFloat fix (line ~495)\n";
    } else {
        echo "  ✗ MISSING parseFloat fix!\n";
        echo "  → The file needs to be updated\n";
    }
} else {
    echo "  ✗ frontend.js NOT FOUND!\n";
}

// Check enhanced-candidate-profile.css for color fix
$css_path = $plugin_path . 'assets/css/enhanced-candidate-profile.css';
if (file_exists($css_path)) {
    $css_content = file_get_contents($css_path);
    $has_color_fix = strpos($css_content, 'background: #004C5F;') !== false;
    
    echo "\nenhanced-candidate-profile.css:\n";
    echo "  File size: " . filesize($css_path) . " bytes\n";
    echo "  Modified: " . date('Y-m-d H:i:s', filemtime($css_path)) . "\n";
    
    if ($has_color_fix) {
        echo "  ✓ Contains #004C5F color fix\n";
    } else {
        echo "  ✗ MISSING #004C5F color fix!\n";
        echo "  → The file needs to be updated\n";
    }
} else {
    echo "  ✗ enhanced-candidate-profile.css NOT FOUND!\n";
}

// Check evaluation service for enhanced descriptions
$service_path = $plugin_path . 'includes/services/class-mt-evaluation-service.php';
if (file_exists($service_path)) {
    $service_content = file_get_contents($service_path);
    $has_descriptions = strpos($service_content, 'Demonstrates bold vision and willingness to take risks') !== false;
    
    echo "\nclass-mt-evaluation-service.php:\n";
    echo "  File size: " . filesize($service_path) . " bytes\n";
    echo "  Modified: " . date('Y-m-d H:i:s', filemtime($service_path)) . "\n";
    
    if ($has_descriptions) {
        echo "  ✓ Contains enhanced descriptions\n";
    } else {
        echo "  ✗ MISSING enhanced descriptions!\n";
        echo "  → The file needs to be updated\n";
    }
} else {
    echo "  ✗ class-mt-evaluation-service.php NOT FOUND!\n";
}

// Check plugin version
$main_file = $plugin_path . 'mobility-trailblazers.php';
if (file_exists($main_file)) {
    $plugin_data = get_plugin_data($main_file);
    echo "\n=== PLUGIN VERSION ===\n";
    echo "Current Version: " . $plugin_data['Version'] . "\n";
    if ($plugin_data['Version'] === '2.5.20') {
        echo "✓ Version is correct (2.5.20)\n";
    } else {
        echo "✗ Version mismatch! Should be 2.5.20\n";
    }
}

// 3. Force browser cache refresh
echo "\n=== BROWSER CACHE ===\n";
echo "To see changes, users must:\n";
echo "1. Clear browser cache (Ctrl+Shift+Delete)\n";
echo "2. Hard refresh (Ctrl+F5 or Cmd+Shift+R)\n";

// 4. Test URLs
echo "\n=== TEST URLS ===\n";
$site_url = site_url();
echo "1. Candidate page with button: $site_url/candidate/andre-schwaemmlein/\n";
echo "2. Jury evaluation (needs login): $site_url/jury-dashboard/?evaluate=4377\n";

echo "\n=== RECOMMENDATIONS ===\n";
echo "If fixes are missing:\n";
echo "1. Re-upload the files via FTP\n";
echo "2. Check file permissions (should be 644)\n";
echo "3. Disable any caching plugins temporarily\n";
echo "4. Contact hosting provider about CDN/caching\n";

echo "</pre>";

// Add action buttons
echo '<div style="margin-top:20px;">';
echo '<a href="' . admin_url('plugins.php') . '" style="background:#004C5F;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;">Check Plugins</a>';
echo '<a href="javascript:location.reload(true)" style="background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Refresh This Page</a>';
echo '</div>';
?>