<?php
/**
 * Mobility Trailblazers Uninstall
 *
 * This file is executed when the plugin is uninstalled via WordPress admin.
 * It handles the cleanup of plugin data based on user preferences.
 *
 * @package MobilityTrailblazers
 * @since 2.2.13
 */

// If uninstall is not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if the user has opted to remove all data on uninstall
$remove_data = get_option('mt_remove_data_on_uninstall', '0');

if ($remove_data === '1') {
    // Include the plugin file to access plugin classes
    require_once plugin_dir_path(__FILE__) . 'mobility-trailblazers.php';
    
    // Load the uninstaller class if not already loaded
    if (!class_exists('MobilityTrailblazers\Core\MT_Uninstaller')) {
        require_once plugin_dir_path(__FILE__) . 'includes/core/class-mt-uninstaller.php';
    }
    
    // Remove all plugin data
    \MobilityTrailblazers\Core\MT_Uninstaller::remove_all_data();
    
    // Log the uninstallation if possible
    if (class_exists('MobilityTrailblazers\\Core\\MT_Logger')) {
        \MobilityTrailblazers\Core\MT_Logger::info('Plugin data removed as per user settings during uninstall');
    }
} else {
    // User chose to preserve data
    // Only remove temporary data and scheduled events
    
    // Clear scheduled events
    wp_clear_scheduled_hook('mt_cleanup_error_logs');
    wp_clear_scheduled_hook('mt_daily_evaluation_reminder');
    wp_clear_scheduled_hook('mt_cleanup_audit_logs');
    
    // Clear transients only
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mt_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mt_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_mt_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_mt_%'");
    
    // Log the preservation
    if (function_exists('error_log')) {
        error_log('Mobility Trailblazers: Plugin uninstalled. Data preserved as per user settings.');
    }
}

// Flush rewrite rules
flush_rewrite_rules();