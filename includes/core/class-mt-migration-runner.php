<?php
/**
 * Migration Runner
 *
 * @package MobilityTrailblazers
 * @since 2.2.1
 */

namespace MobilityTrailblazers\Core;

use MobilityTrailblazers\Migrations\MT_Migration_Add_Indexes;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Migration_Runner
 *
 * Handles running database migrations
 */
class MT_Migration_Runner {
    
    /**
     * Run all pending migrations
     *
     * @return void
     */
    public static function run_migrations() {
        // Check current database version
        $current_version = get_option('mt_db_version', '1.0.0');
        
        // Run index migration if needed
        if (version_compare($current_version, '2.2.1', '<')) {
            self::run_index_migration();
        }
        
        // Update database version
        update_option('mt_db_version', MT_VERSION);
    }
    
    /**
     * Run index migration
     *
     * @return void
     */
    private static function run_index_migration() {
        // Load migration class if not already loaded
        if (!class_exists('MobilityTrailblazers\Migrations\MT_Migration_Add_Indexes')) {
            require_once MT_PLUGIN_DIR . 'includes/migrations/class-mt-migration-add-indexes.php';
        }
        
        // Check if migration is needed
        if (MT_Migration_Add_Indexes::is_needed()) {
            // Run the migration
            $result = MT_Migration_Add_Indexes::run();
            
            if ($result) {
                // Log success
                MT_Logger::info('Database indexes migration completed successfully');
                
                // Clear all caches since we've optimized the database
                self::clear_all_caches();
            } else {
                // Log failure
                MT_Logger::error('Database indexes migration failed or partially completed');
            }
        }
    }
    
    /**
     * Clear all plugin caches after migration
     *
     * @return void
     */
    private static function clear_all_caches() {
        global $wpdb;
        
        // Clear all MT transients
        $query = "DELETE FROM {$wpdb->options} 
                  WHERE option_name LIKE '_transient_mt_%' 
                  OR option_name LIKE '_transient_timeout_mt_%'";
        $wpdb->query($query);
        
        // Log cache clearing
        MT_Logger::debug('Cleared all plugin caches after migration');
    }
    
    /**
     * Hook into WordPress to run migrations
     *
     * @return void
     */
    public static function init() {
        // Run migrations on admin init if needed
        add_action('admin_init', [__CLASS__, 'check_and_run_migrations']);
    }
    
    /**
     * Check and run migrations if needed
     *
     * @return void
     */
    public static function check_and_run_migrations() {
        // Only run for administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if we need to run migrations
        $current_version = get_option('mt_db_version', '1.0.0');
        
        if (version_compare($current_version, MT_VERSION, '<')) {
            self::run_migrations();
            
            // Add admin notice about migration
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . __('Mobility Trailblazers database has been optimized with performance indexes.', 'mobility-trailblazers') . '</p>';
                echo '</div>';
            });
        }
    }
    
    /**
     * Run migrations during plugin activation
     *
     * @return void
     */
    public static function activate() {
        self::run_migrations();
    }
}
