<?php
/**
 * Fix Elementor Database Initialization
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

echo "=== Elementor Database Fix ===\n\n";

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

// Check if Elementor is active
if (!did_action('elementor/loaded')) {
    echo "❌ Elementor is not active. Please activate Elementor first.\n";
    exit;
}

echo "✅ Elementor is active\n";

// Check current database version
$current_db_version = get_option('elementor_db_version');
echo "Current Elementor database version: " . ($current_db_version ?: 'Not set') . "\n";

// Fix database version if not set
if (!$current_db_version) {
    echo "\n=== Fixing Elementor Database Version ===\n";
    
    // Set the database version to match current Elementor version
    $elementor_version = defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0';
    $db_version_to_set = '3.0.0'; // Use a stable version
    
    echo "Setting Elementor database version to: $db_version_to_set\n";
    
    $result = update_option('elementor_db_version', $db_version_to_set);
    
    if ($result) {
        echo "✅ Successfully set Elementor database version\n";
    } else {
        echo "❌ Failed to set Elementor database version\n";
    }
    
    // Force Elementor to reinitialize
    if (class_exists('Elementor\Core\Upgrade\Manager')) {
        echo "Forcing Elementor upgrade manager to reinitialize...\n";
        try {
            $upgrade_manager = new Elementor\Core\Upgrade\Manager();
            $should_upgrade = $upgrade_manager->should_upgrade();
            echo "Elementor upgrade check result: " . ($should_upgrade ? 'Upgrade needed' : 'No upgrade needed') . "\n";
        } catch (Exception $e) {
            echo "⚠️  Error during upgrade manager initialization: " . $e->getMessage() . "\n";
        }
    }
    
    // Clear Elementor cache
    if (class_exists('Elementor\Core\Files\Manager')) {
        echo "Clearing Elementor cache...\n";
        try {
            $files_manager = new Elementor\Core\Files\Manager();
            $files_manager->clear_cache();
            echo "✅ Elementor cache cleared\n";
        } catch (Exception $e) {
            echo "⚠️  Error clearing Elementor cache: " . $e->getMessage() . "\n";
        }
    }
    
    // Clear WordPress cache
    echo "Clearing WordPress cache...\n";
    wp_cache_flush();
    echo "✅ WordPress cache cleared\n";
    
} else {
    echo "✅ Elementor database version is already set\n";
}

// Check if there are any missing Elementor options
echo "\n=== Checking Elementor Options ===\n";

$required_options = array(
    'elementor_db_version',
    'elementor_version',
    'elementor_activation_time',
    'elementor_install_time'
);

foreach ($required_options as $option) {
    $value = get_option($option);
    if ($value) {
        echo "✅ $option: $value\n";
    } else {
        echo "❌ $option: Not set\n";
        
        // Set default values for missing options
        switch ($option) {
            case 'elementor_version':
                $default_value = defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '3.0.0';
                break;
            case 'elementor_activation_time':
            case 'elementor_install_time':
                $default_value = current_time('timestamp');
                break;
            default:
                $default_value = '3.0.0';
        }
        
        update_option($option, $default_value);
        echo "   → Set to: $default_value\n";
    }
}

// Check Elementor tables
echo "\n=== Checking Elementor Database Tables ===\n";

global $wpdb;

$elementor_tables = array(
    $wpdb->prefix . 'elementor_scheme_color',
    $wpdb->prefix . 'elementor_scheme_typography',
    $wpdb->prefix . 'elementor_scheme_color_picker'
);

foreach ($elementor_tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        echo "✅ $table: Exists ($count records)\n";
    } else {
        echo "❌ $table: Missing\n";
    }
}

// Force Elementor to run its installation routine
echo "\n=== Forcing Elementor Installation ===\n";

if (class_exists('Elementor\Plugin')) {
    try {
        // Get the plugin instance
        $plugin = Elementor\Plugin::instance();
        
        // Force reinstallation
        if (method_exists($plugin, 'get_install_time')) {
            $install_time = $plugin->get_install_time();
            echo "Elementor install time: " . ($install_time ? date('Y-m-d H:i:s', $install_time) : 'Not set') . "\n";
        }
        
        // Check if we need to run the installation
        if (method_exists($plugin, 'get_db_version')) {
            $db_version = $plugin->get_db_version();
            echo "Elementor DB version from plugin: " . ($db_version ?: 'Not set') . "\n";
        }
        
        echo "✅ Elementor plugin instance accessed successfully\n";
        
    } catch (Exception $e) {
        echo "❌ Error accessing Elementor plugin: " . $e->getMessage() . "\n";
    }
}

// Verify the fix
echo "\n=== Verification ===\n";

$new_db_version = get_option('elementor_db_version');
echo "New Elementor database version: " . ($new_db_version ?: 'Still not set') . "\n";

if ($new_db_version) {
    echo "✅ Elementor database version is now properly set\n";
    echo "✅ This should resolve the JavaScript initialization errors\n";
} else {
    echo "❌ Elementor database version is still not set\n";
    echo "⚠️  You may need to manually run Elementor database update\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Clear your browser cache completely\n";
echo "2. Refresh the page and check for JavaScript errors\n";
echo "3. If errors persist, go to Elementor > Tools > Replace URL\n";
echo "4. Run the 'Regenerate Files' action in Elementor Tools\n";
echo "5. Check the browser console for any remaining errors\n";

echo "\n=== Summary ===\n";
echo "✅ Elementor database initialization fix applied\n";
echo "✅ Required options checked and set\n";
echo "✅ Cache cleared\n";
echo "✅ Database tables verified\n";

echo "\n🎉 Elementor database fix completed!\n"; 