<?php
/**
 * Plugin Deactivator
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 */

namespace MobilityTrailblazers;

/**
 * Fired during plugin deactivation
 */
class Deactivator {
    
    /**
     * Deactivate the plugin
     *
     * @return void
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear scheduled events
        self::clear_scheduled_events();
        
        // Clear transients
        self::clear_transients();
        
        // Log deactivation
        error_log('Mobility Trailblazers plugin deactivated');
    }
    
    /**
     * Clear scheduled events
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        // Clear any scheduled cron events
        $scheduled_events = array(
            'mt_cleanup_expired_backups',
            'mt_send_reminder_emails',
            'mt_generate_daily_reports',
        );
        
        foreach ($scheduled_events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }
    
    /**
     * Clear plugin transients
     *
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Delete all plugin-related transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_%' 
             OR option_name LIKE '_transient_timeout_mt_%'"
        );
    }
} 