<?php
/**
 * Admin UI Fixes for Mobility Trailblazers
 * 
 * Fixes display issues in WordPress admin interface
 * 
 * @package MobilityTrailblazers
 * @since 1.0.9
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Admin_UI_Fixes
 * 
 * Handles admin interface display fixes
 */
class MT_Admin_UI_Fixes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Fix username display on users page
        add_action('admin_head', array($this, 'fix_username_display'));
        
        // Adjust user columns if needed
        add_filter('manage_users_columns', array($this, 'adjust_user_columns'), 999);
        
        // Add custom styles for admin pages
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_fixes'));
    }
    
    /**
     * Fix username display CSS specifically for the ellipsis issue
     */
    public function fix_username_display() {
        $screen = get_current_screen();
        
        // Apply fix on users page and any Mobility Trailblazers admin pages
        if ($screen && ($screen->id === 'users' || strpos($screen->id, 'mobility-trailblazers') !== false)) {
            ?>
            <style type="text/css">
                /* Fix username ellipsis issue in Users list */
                .wp-list-table td.username strong,
                .wp-list-table td.column-username,
                .wp-list-table td.username strong a {
                    display: inline-block !important;
                    min-width: 200px !important;
                    text-overflow: clip !important;
                    overflow: visible !important;
                    white-space: nowrap !important;
                    direction: ltr !important;
                }
                
                /* Ensure proper column width */
                .wp-list-table .column-username,
                .wp-list-table th.column-username {
                    width: 20% !important;
                    min-width: 200px !important;
                }
                
                /* Fix for display names */
                .wp-list-table td.column-name {
                    min-width: 250px !important;
                }
                
                /* Additional fix for any truncated text in user columns */
                #the-list .username strong a,
                #the-list .column-username {
                    text-overflow: initial !important;
                    overflow: visible !important;
                    display: inline-block !important;
                    width: auto !important;
                    min-width: 180px !important;
                }
                
                /* Fix for responsive view */
                @media screen and (max-width: 782px) {
                    .wp-list-table td.username strong,
                    .wp-list-table td.column-username {
                        min-width: 150px !important;
                    }
                }
                
                /* Fix for jury member usernames specifically */
                .users-php .wp-list-table td.username {
                    overflow: visible !important;
                }
                
                /* Remove any ellipsis from beginning of usernames */
                .wp-list-table td.username strong::before {
                    content: none !important;
                }
            </style>
            <?php
        }
    }
    
    /**
     * Adjust user column widths
     * 
     * @param array $columns User columns
     * @return array Modified columns
     */
    public function adjust_user_columns($columns) {
        // Ensure username column has proper label without modifications
        if (isset($columns['username'])) {
            // Keep original label but ensure it doesn't get truncated
            $columns['username'] = '<span style="display: inline-block; min-width: 100px;">' . __('Username') . '</span>';
        }
        
        return $columns;
    }
    
    /**
     * Enqueue additional admin fixes if needed
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_fixes($hook) {
        // Only on users.php page
        if ('users.php' === $hook) {
            // Add inline JavaScript fix as backup
            wp_add_inline_script('jquery', "
                jQuery(document).ready(function($) {
                    // Backup JavaScript fix for username display
                    setTimeout(function() {
                        $('.wp-list-table td.username strong').each(function() {
                            var text = $(this).text();
                            // Remove any leading dots/ellipsis
                            if