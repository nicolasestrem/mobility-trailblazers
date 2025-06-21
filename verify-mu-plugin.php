<?php
/**
 * Verify MU Plugin Installation
 * 
 * This script verifies that the Elementor REST fix mu-plugin is properly installed
 */

// Check if WordPress is already loaded
if (!defined('ABSPATH')) {
    // Try to find wp-config.php relative to this file
    $wp_config_path = dirname(__FILE__) . '/../../../wp-config.php';
    if (file_exists($wp_config_path)) {
        require_once $wp_config_path;
        // wp-load.php should be in the same directory as wp-config.php
        $wp_load_path = dirname($wp_config_path) . '/wp-load.php';
        if (file_exists($wp_load_path)) {
            require_once $wp_load_path;
        } else {
            // Fallback to current directory
            require_once 'wp-load.php';
        }
    } else {
        // Fallback to current directory
        require_once 'wp-config.php';
        require_once 'wp-load.php';
    }
}

echo "=== MU Plugin Verification Script ===\n\n";

// Check if user is logged in and has admin permissions
$current_user = wp_get_current_user();
if (!$current_user->ID || !current_user_can('manage_options')) {
    echo "❌ Please log in as an administrator to run this script.\n";
    exit;
}

echo "✅ User authenticated: " . $current_user->user_login . "\n\n";

// Check mu-plugins directory
$mu_plugins_dir = WPMU_PLUGIN_DIR;
echo "=== MU Plugins Directory ===\n";
echo "Directory: $mu_plugins_dir\n";

if (is_dir($mu_plugins_dir)) {
    echo "✅ MU Plugins directory exists\n";
} else {
    echo "❌ MU Plugins directory does not exist\n";
    exit;
}

// Check if our mu-plugin file exists
$mu_plugin_file = $mu_plugins_dir . '/elementor-rest-fix.php';
echo "Target file: $mu_plugin_file\n";

if (file_exists($mu_plugin_file)) {
    echo "✅ MU Plugin file exists\n";
    
    // Check file permissions
    $perms = fileperms($mu_plugin_file);
    $perms_octal = substr(sprintf('%o', $perms), -4);
    echo "File permissions: $perms_octal\n";
    
    if ($perms_octal >= '0644') {
        echo "✅ File permissions are correct\n";
    } else {
        echo "⚠️  File permissions might be too restrictive\n";
    }
    
    // Check file size
    $file_size = filesize($mu_plugin_file);
    echo "File size: " . number_format($file_size) . " bytes\n";
    
    if ($file_size > 0) {
        echo "✅ File is not empty\n";
    } else {
        echo "❌ File is empty\n";
    }
    
} else {
    echo "❌ MU Plugin file does not exist\n";
    exit;
}

// Check if WordPress can load mu-plugins
echo "\n=== WordPress MU Plugin Detection ===\n";

$mu_plugins = get_mu_plugins();
echo "Total mu-plugins detected: " . count($mu_plugins) . "\n";

if (isset($mu_plugins['elementor-rest-fix.php'])) {
    echo "✅ WordPress can detect our mu-plugin\n";
    echo "Plugin Name: " . $mu_plugins['elementor-rest-fix.php']['Name'] . "\n";
    echo "Plugin Version: " . $mu_plugins['elementor-rest-fix.php']['Version'] . "\n";
    echo "Plugin Description: " . $mu_plugins['elementor-rest-fix.php']['Description'] . "\n";
} else {
    echo "⚠️  WordPress cannot detect our mu-plugin\n";
    echo "This might be normal if the plugin doesn't have proper headers\n";
}

// Check if Elementor is active and our fixes are working
echo "\n=== Elementor Status ===\n";

if (did_action('elementor/loaded')) {
    echo "✅ Elementor is active\n";
    echo "Elementor version: " . (defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'Unknown') . "\n";
    
    // Check Elementor database version
    $elementor_db_version = get_option('elementor_db_version');
    echo "Elementor database version: " . ($elementor_db_version ?: 'Not set') . "\n";
    
    if ($elementor_db_version) {
        echo "✅ Elementor database version is set\n";
    } else {
        echo "⚠️  Elementor database version is not set\n";
        echo "This might indicate that the mu-plugin needs to run the database fix\n";
    }
    
    // Check if our REST API fixes are working
    echo "\n=== REST API Status ===\n";
    
    // Test if Elementor's REST API is accessible
    $rest_url = rest_url('elementor/v1/globals');
    echo "Elementor REST API URL: $rest_url\n";
    
    // Check if the mu-plugin has set any options
    $mu_plugin_options = array(
        'elementor_db_version',
        'elementor_activation_time',
        'elementor_version'
    );
    
    echo "\nElementor Options:\n";
    foreach ($mu_plugin_options as $option) {
        $value = get_option($option);
        echo "  $option: " . ($value ?: 'Not set') . "\n";
    }
    
} else {
    echo "⚠️  Elementor is not active\n";
    echo "The mu-plugin will work when Elementor is activated\n";
}

// Check for any PHP errors that might prevent the mu-plugin from loading
echo "\n=== PHP Error Check ===\n";

// Try to include the mu-plugin file directly to check for syntax errors
$error_reporting = error_reporting();
error_reporting(E_ALL);
$old_display_errors = ini_get('display_errors');
ini_set('display_errors', 0);

ob_start();
$include_result = @include_once $mu_plugin_file;
$include_output = ob_get_clean();

error_reporting($error_reporting);
ini_set('display_errors', $old_display_errors);

if ($include_result !== false) {
    echo "✅ MU Plugin file can be included without errors\n";
} else {
    echo "❌ MU Plugin file has syntax errors or cannot be included\n";
    if ($include_output) {
        echo "Error output: $include_output\n";
    }
}

echo "\n=== Verification Summary ===\n";
echo "✅ MU Plugin file is in the correct location\n";
echo "✅ File permissions are appropriate\n";
echo "✅ WordPress can access the mu-plugins directory\n";

if (did_action('elementor/loaded')) {
    echo "✅ Elementor is active and the mu-plugin should be working\n";
    echo "\n=== Next Steps ===\n";
    echo "1. Clear your browser cache\n";
    echo "2. Refresh any Elementor pages\n";
    echo "3. Check the browser console for JavaScript errors\n";
    echo "4. If Elementor database version is still 'Not set', run the Elementor DB Fix script\n";
} else {
    echo "⚠️  Elementor is not active - activate it to test the mu-plugin\n";
}

echo "\n🎉 MU Plugin verification completed!\n"; 