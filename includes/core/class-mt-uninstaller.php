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
            $wpdb->prefix . 'mt_jury_assignments'
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
} 