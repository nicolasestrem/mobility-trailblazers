<?php
/**
 * Plugin Uninstaller
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Uninstaller
 *
 * Handles plugin uninstallation
 */
class MT_Uninstaller {
    
    /**
     * Uninstall the plugin
     *
     * @return void
     */
    public static function uninstall() {
        // Check if we should remove data
        $remove_data = get_option('mt_remove_data_on_uninstall', false);
        
        if (!$remove_data) {
            return;
        }
        
        // Remove database tables
        self::remove_tables();
        
        // Remove options
        self::remove_options();
        
        // Remove user roles
        self::remove_roles();
        
        // Remove capabilities
        self::remove_capabilities();
    }
    
    /**
     * Remove database tables
     *
     * @return void
     */
    private static function remove_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'mt_jury_assignments',
            $wpdb->prefix . 'mt_audit_log'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Remove options
     *
     * @return void
     */
    private static function remove_options() {
        $options = [
            'mt_db_version',
            'mt_settings',

            'mt_criteria_weights',
            'mt_remove_data_on_uninstall'
        ];
        
        foreach ($options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Remove user roles
     *
     * @return void
     */
    private static function remove_roles() {
        remove_role('mt_jury_member');
        remove_role('mt_jury_admin');
    }
    
    /**
     * Remove capabilities
     *
     * @return void
     */
    private static function remove_capabilities() {
        $roles = ['administrator', 'editor'];
        $capabilities = [
            'edit_mt_candidates',
            'edit_others_mt_candidates',
            'publish_mt_candidates',
            'delete_mt_candidates',
            'delete_others_mt_candidates',
            'edit_mt_jury_members',
            'edit_others_mt_jury_members',
            'publish_mt_jury_members',
            'delete_mt_jury_members',
            'delete_others_mt_jury_members',
            'mt_manage_evaluations',
            'mt_submit_evaluations',
            'mt_view_all_evaluations',
            'mt_manage_assignments',
            'mt_manage_settings'
        ];
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
    
    /**
     * Remove all plugin data completely
     * This is the method called from uninstall.php
     *
     * @since 2.2.13
     * @return void
     */
    public static function remove_all_data() {
        // Remove all custom post types and their data
        self::remove_post_types();
        
        // Remove all database tables
        self::remove_all_tables();
        
        // Remove all options
        self::remove_all_options();
        
        // Remove user roles and capabilities
        self::remove_roles();
        self::remove_capabilities();
        
        // Remove uploaded files if any
        self::remove_uploaded_files();
        
        // Clear any scheduled events
        self::clear_scheduled_events();
        
        // Clear transients
        self::clear_transients();
    }
    
    /**
     * Remove all custom post types and their data
     *
     * @return void
     */
    private static function remove_post_types() {
        global $wpdb;
        
        // Delete all mt_candidate posts and their meta
        $candidate_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mt_candidate'");
        foreach ($candidate_ids as $id) {
            wp_delete_post($id, true); // Force delete, bypassing trash
        }
        
        // Delete all mt_jury_member posts and their meta
        $jury_ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mt_jury_member'");
        foreach ($jury_ids as $id) {
            wp_delete_post($id, true); // Force delete, bypassing trash
        }
        
        // Clean up any orphaned postmeta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_mt_%'");
    }
    
    /**
     * Remove all database tables including audit logs
     *
     * @return void
     */
    private static function remove_all_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'mt_jury_assignments',
            $wpdb->prefix . 'mt_audit_log',
            // $wpdb->prefix . 'mt_error_log', // Removed in v2.5.7
            $wpdb->prefix . 'mt_voting_results'
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }
    
    /**
     * Remove all plugin options
     *
     * @return void
     */
    private static function remove_all_options() {
        global $wpdb;
        
        // Remove all options with mt_ prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'mt_%'");
        
        // Also remove specific known options
        $specific_options = [
            'mobility_trailblazers_version',
            'mobility_trailblazers_db_version',
            'widget_mt_language_switcher'
        ];
        
        foreach ($specific_options as $option) {
            delete_option($option);
        }
    }
    
    /**
     * Remove uploaded files (if stored in a custom directory)
     *
     * @return void
     */
    private static function remove_uploaded_files() {
        $upload_dir = wp_upload_dir();
        $mt_upload_dir = $upload_dir['basedir'] . '/mobility-trailblazers';
        
        if (is_dir($mt_upload_dir)) {
            self::recursive_rmdir($mt_upload_dir);
        }
    }
    
    /**
     * Recursively remove directory and its contents
     *
     * @param string $dir Directory path
     * @return bool
     */
    private static function recursive_rmdir($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::recursive_rmdir($path) : unlink($path);
        }
        
        return rmdir($dir);
    }
    
    /**
     * Clear scheduled events
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        // Clear any scheduled cron events
        // wp_clear_scheduled_hook('mt_cleanup_error_logs'); // Removed in v2.5.7
        wp_clear_scheduled_hook('mt_daily_evaluation_reminder');
        wp_clear_scheduled_hook('mt_cleanup_audit_logs');
    }
    
    /**
     * Clear all plugin transients
     *
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Delete transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mt_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mt_%'");
        
        // Delete site transients for multisite
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_mt_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_mt_%'");
    }
} 
