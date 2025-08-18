<?php
/**
 * Clear all WordPress caches and verify v2.5.20 deployment
 */

// Bootstrap WordPress
require_once('/home/mobilitytrailblazers/public_html/vote/wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h2>Cache Clear and Version Check - v2.5.20</h2>";
echo "<pre>";

// 1. Check current plugin version
$plugin_data = get_plugin_data('/home/mobilitytrailblazers/public_html/vote/wp-content/plugins/mobility-trailblazers/mobility-trailblazers.php');
echo "Current Plugin Version: " . $plugin_data['Version'] . "\n";
echo "Expected Version: 2.5.20\n\n";

// 2. Clear WordPress object cache
wp_cache_flush();
echo "✓ WordPress object cache cleared\n";

// 3. Clear transients
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
echo "✓ All transients deleted\n";

// 4. Clear any page cache plugins
if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
    echo "✓ W3 Total Cache cleared\n";
}

if (function_exists('wp_cache_clear_cache')) {
    wp_cache_clear_cache();
    echo "✓ WP Super Cache cleared\n";
}

if (class_exists('WP_Rocket')) {
    rocket_clean_domain();
    echo "✓ WP Rocket cache cleared\n";
}

if (class_exists('LiteSpeed_Cache_API')) {
    LiteSpeed_Cache_API::purge_all();
    echo "✓ LiteSpeed Cache cleared\n";
}

// 5. Clear Elementor cache
if (defined('ELEMENTOR_VERSION')) {
    \Elementor\Plugin::$instance->files_manager->clear_cache();
    echo "✓ Elementor cache cleared\n";
}

// 6. Check file modifications
echo "\n--- File Verification ---\n";
$files_to_check = [
    'assets/js/frontend.js' => '2025-08-18', // Expected modification date
    'includes/services/class-mt-evaluation-service.php' => '2025-08-18',
    'includes/core/class-mt-plugin.php' => '2025-08-18',
    'assets/css/enhanced-candidate-profile.css' => '2025-08-18'
];

$plugin_path = '/home/mobilitytrailblazers/public_html/vote/wp-content/plugins/mobility-trailblazers/';
foreach ($files_to_check as $file => $expected_date) {
    $full_path = $plugin_path . $file;
    if (file_exists($full_path)) {
        $mod_time = date('Y-m-d H:i:s', filemtime($full_path));
        $size = filesize($full_path);
        echo "$file:\n";
        echo "  Modified: $mod_time\n";
        echo "  Size: " . number_format($size) . " bytes\n";
        
        // Check for specific v2.5.20 changes
        $content = file_get_contents($full_path);
        if ($file === 'assets/js/frontend.js') {
            if (strpos($content, 'parseFloat') !== false) {
                echo "  ✓ Contains parseFloat fix\n";
            } else {
                echo "  ✗ Missing parseFloat fix!\n";
            }
        }
        if ($file === 'assets/css/enhanced-candidate-profile.css') {
            if (strpos($content, '#004C5F') !== false) {
                echo "  ✓ Contains #004C5F color fix\n";
            } else {
                echo "  ✗ Missing #004C5F color fix!\n";
            }
        }
    } else {
        echo "$file: NOT FOUND!\n";
    }
}

// 7. Force WordPress to reload
wp_cache_flush();
flush_rewrite_rules();
echo "\n✓ WordPress reloaded\n";

// 8. Clear opcache if available
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✓ PHP OpCache cleared\n";
}

echo "\n--- Cache Clear Complete ---\n";
echo "Please refresh your browser with Ctrl+F5 to see changes.\n";
echo "</pre>";

// Add refresh button
echo '<br><a href="javascript:location.reload(true)" style="background:#004C5F;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;">Refresh Page</a>';
?>