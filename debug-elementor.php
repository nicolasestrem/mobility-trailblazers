<?php
/**
 * Debug Elementor Compatibility Issues
 */

// Load WordPress only if not already loaded
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

echo "=== Elementor Compatibility Debug ===\n\n";

// Check if user is logged in
$current_user = wp_get_current_user();
echo "Current user: " . ($current_user->ID ? $current_user->user_login : 'Not logged in') . "\n";

if (!$current_user->ID) {
    echo "No user logged in. Please log in first.\n";
    exit;
}

// Check if user has admin permissions
if (!current_user_can('manage_options')) {
    echo "User does not have admin permissions. Please log in as an administrator.\n";
    exit;
}

echo "\n=== Elementor Status Check ===\n";

// Check if Elementor is active
$elementor_active = did_action('elementor/loaded');
echo "Elementor loaded: " . ($elementor_active ? 'Yes' : 'No') . "\n";

if (!$elementor_active) {
    echo "❌ Elementor is not active. Please activate Elementor first.\n";
    exit;
}

// Check Elementor version
$elementor_version = defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'Unknown';
echo "Elementor version: $elementor_version\n";

// Check Elementor Pro
$elementor_pro_active = class_exists('ElementorPro\Plugin');
echo "Elementor Pro active: " . ($elementor_pro_active ? 'Yes' : 'No') . "\n";

if ($elementor_pro_active) {
    $elementor_pro_version = defined('ELEMENTOR_PRO_VERSION') ? ELEMENTOR_PRO_VERSION : 'Unknown';
    echo "Elementor Pro version: $elementor_pro_version\n";
}

// Check Elementor database version
$elementor_db_version = get_option('elementor_db_version', 'Not set');
echo "Elementor database version: $elementor_db_version\n";

// Check if database needs updating
if ($elementor_db_version && version_compare($elementor_db_version, '3.0.0', '<')) {
    echo "⚠️  Elementor database may need updating (current: $elementor_db_version, recommended: 3.0.0+)\n";
} else {
    echo "✅ Elementor database version is up to date\n";
}

echo "\n=== Plugin Compatibility Check ===\n";

// Check our plugin's Elementor integration
$mt_elementor_integration_exists = class_exists('MT_Elementor_Integration');
echo "MT Elementor Integration class: " . ($mt_elementor_integration_exists ? 'Exists' : 'Missing') . "\n";

// Check if our widgets are registered
$widgets_registered = has_action('elementor/widgets/widgets_registered');
echo "Elementor widgets registration hook: " . ($widgets_registered ? 'Registered' : 'Not registered') . "\n";

// Check if our scripts are enqueued
$scripts_enqueued = has_action('elementor/frontend/after_enqueue_scripts');
echo "Elementor frontend scripts hook: " . ($scripts_enqueued ? 'Registered' : 'Not registered') . "\n";

echo "\n=== JavaScript Assets Check ===\n";

// Check if Elementor frontend script is enqueued
$elementor_frontend_enqueued = wp_script_is('elementor-frontend', 'enqueued');
echo "Elementor frontend script enqueued: " . ($elementor_frontend_enqueued ? 'Yes' : 'No') . "\n";

// Check if our compatibility script is enqueued
$mt_compat_enqueued = wp_script_is('mt-elementor-compat', 'enqueued');
echo "MT Elementor compatibility script enqueued: " . ($mt_compat_enqueued ? 'Yes' : 'No') . "\n";

// Check if jQuery is enqueued
$jquery_enqueued = wp_script_is('jquery', 'enqueued');
echo "jQuery enqueued: " . ($jquery_enqueued ? 'Yes' : 'No') . "\n";

echo "\n=== REST API Check ===\n";

// Check REST API availability
$rest_api_url = rest_url();
echo "REST API URL: $rest_api_url\n";

// Test REST API access
$response = wp_remote_get($rest_api_url);
if (is_wp_error($response)) {
    echo "❌ REST API error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "REST API status code: $status_code\n";
    
    if ($status_code === 200) {
        echo "✅ REST API is accessible\n";
    } else {
        echo "⚠️  REST API returned status code $status_code\n";
    }
}

// Test Elementor-specific REST endpoints
$elementor_rest_url = rest_url('elementor/v1/');
$response = wp_remote_get($elementor_rest_url);
if (is_wp_error($response)) {
    echo "❌ Elementor REST API error: " . $response->get_error_message() . "\n";
} else {
    $status_code = wp_remote_retrieve_response_code($response);
    echo "Elementor REST API status code: $status_code\n";
    
    if ($status_code === 200 || $status_code === 401) {
        echo "✅ Elementor REST API is accessible (401 is normal for unauthenticated requests)\n";
    } else {
        echo "⚠️  Elementor REST API returned status code $status_code\n";
    }
}

echo "\n=== File System Check ===\n";

// Check if our compatibility script exists
$compat_script_path = MT_PLUGIN_DIR . 'assets/js/elementor-compat.js';
echo "MT Elementor compatibility script exists: " . (file_exists($compat_script_path) ? 'Yes' : 'No') . "\n";

// Check if our jury dashboard script exists
$jury_script_path = MT_PLUGIN_DIR . 'assets/jury-dashboard.js';
echo "MT Jury dashboard script exists: " . (file_exists($jury_script_path) ? 'Yes' : 'No') . "\n";

// Check if our CSS files exist
$jury_css_path = MT_PLUGIN_DIR . 'assets/jury-dashboard.css';
echo "MT Jury dashboard CSS exists: " . (file_exists($jury_css_path) ? 'Yes' : 'No') . "\n";

echo "\n=== WordPress Configuration Check ===\n";

// Check if WP_DEBUG is enabled
$wp_debug = defined('WP_DEBUG') && WP_DEBUG;
echo "WP_DEBUG enabled: " . ($wp_debug ? 'Yes' : 'No') . "\n";

// Check if SCRIPT_DEBUG is enabled
$script_debug = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG;
echo "SCRIPT_DEBUG enabled: " . ($script_debug ? 'Yes' : 'No') . "\n";

// Check if we're using minified scripts
$use_minified = !$script_debug;
echo "Using minified scripts: " . ($use_minified ? 'Yes' : 'No') . "\n";

echo "\n=== Recommendations ===\n";

if (!$elementor_active) {
    echo "1. ❌ Activate Elementor plugin\n";
} else {
    echo "1. ✅ Elementor is active\n";
}

if ($elementor_db_version && version_compare($elementor_db_version, '3.0.0', '<')) {
    echo "2. ⚠️  Update Elementor database: Go to Elementor > Tools > Replace URL\n";
} else {
    echo "2. ✅ Elementor database is up to date\n";
}

if (!$mt_compat_enqueued) {
    echo "3. ⚠️  MT Elementor compatibility script is not enqueued\n";
} else {
    echo "3. ✅ MT Elementor compatibility script is enqueued\n";
}

if (!$jquery_enqueued) {
    echo "4. ⚠️  jQuery is not enqueued (required for Elementor)\n";
} else {
    echo "4. ✅ jQuery is enqueued\n";
}

echo "\n=== JavaScript Error Prevention ===\n";
echo "The following fixes have been implemented:\n";
echo "- Safety checks for Elementor frontend availability\n";
echo "- Safety checks for Elementor hooks availability\n";
echo "- Safety checks for MTJuryDashboard object and methods\n";
echo "- Safety checks for mt_elementor configuration object\n";
echo "- Improved script loading order and dependencies\n";
echo "- Try-catch error handling for initialization\n";
echo "- Enhanced REST API access for Elementor\n";
echo "- Webpack module loading error handling\n";

echo "\n=== Next Steps ===\n";
echo "1. Clear browser cache and refresh the page\n";
echo "2. Check browser console for any remaining errors\n";
echo "3. If Elementor errors persist, try:\n";
echo "   - Deactivating other plugins temporarily\n";
echo "   - Switching to a default theme temporarily\n";
echo "   - Updating Elementor to the latest version\n";
echo "   - Running Elementor database update\n";
echo "4. Check Elementor system requirements:\n";
echo "   - PHP 7.4 or higher\n";
echo "   - WordPress 5.0 or higher\n";
echo "   - MySQL 5.6 or higher\n";

echo "\n=== Debug Complete ===\n"; 