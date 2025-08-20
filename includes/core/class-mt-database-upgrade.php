<?php
/**
 * Database Upgrade Handler
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 */

namespace MobilityTrailblazers\Core;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Database_Upgrade
 *
 * Handles database schema upgrades
 */
class MT_Database_Upgrade {
    
    /**
     * Run database upgrades
     *
     * @return void
     */
    public static function run() {
        $current_version = get_option('mt_db_version', '1.0');
        
        // Run upgrades based on version
        if (version_compare($current_version, '2.0.1', '<')) {
            self::upgrade_to_2_0_1();
        }
        
        // Add candidates table for version 2.5.26
        if (version_compare($current_version, '2.5.26', '<')) {
            self::upgrade_to_2_5_26();
        }
        
        // Add performance optimizations for version 2.5.34
        if (version_compare($current_version, '2.5.34', '<')) {
            self::upgrade_to_2_5_34();
        }
        
        // Update database version
        update_option('mt_db_version', MT_VERSION);
    }
    
    /**
     * Force database upgrade (for debugging)
     *
     * @return void
     */
    public static function force_upgrade() {
        self::upgrade_to_2_0_1();
        update_option('mt_db_version', MT_VERSION);
    }
    
    /**
     * Upgrade to version 2.0.1
     *
     * @return void
     */
    private static function upgrade_to_2_0_1() {
        global $wpdb;
        
        // Check and add missing columns to evaluations table
        $evaluations_table = $wpdb->prefix . 'mt_evaluations';
        $eval_columns = $wpdb->get_col("SHOW COLUMNS FROM {$evaluations_table}");
        
        if (!in_array('comments', $eval_columns)) {
            $table = esc_sql($evaluations_table);
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN comments longtext AFTER total_score");
        }
        
        if (!in_array('created_at', $eval_columns)) {
            $table = esc_sql($evaluations_table);
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP");
        }
        
        if (!in_array('updated_at', $eval_columns)) {
            $table = esc_sql($evaluations_table);
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
        
        // Check and add missing columns to assignments table
        $assignments_table = $wpdb->prefix . 'mt_jury_assignments';
        $assign_columns = $wpdb->get_col("SHOW COLUMNS FROM {$assignments_table}");
        
        if (!in_array('assigned_at', $assign_columns)) {
            $table = esc_sql($assignments_table);
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN assigned_at datetime DEFAULT CURRENT_TIMESTAMP AFTER candidate_id");
        }
        
        if (!in_array('assigned_by', $assign_columns)) {
            $table = esc_sql($assignments_table);
            $wpdb->query("ALTER TABLE `{$table}` ADD COLUMN assigned_by bigint(20) DEFAULT NULL AFTER assigned_at");
        }
        
        // Add indexes if they don't exist
        $eval_indexes = $wpdb->get_results("SHOW INDEX FROM {$evaluations_table}");
        $eval_index_names = array_column($eval_indexes, 'Key_name');
        
        if (!in_array('idx_status', $eval_index_names)) {
            $table = esc_sql($evaluations_table);
            $wpdb->query("ALTER TABLE `{$table}` ADD INDEX idx_status (status)");
        }
        
        $assign_indexes = $wpdb->get_results("SHOW INDEX FROM {$assignments_table}");
        $assign_index_names = array_column($assign_indexes, 'Key_name');
        
        if (!in_array('unique_assignment', $assign_index_names)) {
            // First remove duplicates if any
            $table = esc_sql($assignments_table);
            $wpdb->query("
                DELETE a1 FROM `{$table}` a1
                INNER JOIN `{$table}` a2 
                WHERE a1.id > a2.id 
                AND a1.jury_member_id = a2.jury_member_id 
                AND a1.candidate_id = a2.candidate_id
            ");
            
            // Then add unique index
            $table = esc_sql($assignments_table);
            $wpdb->query("ALTER TABLE `{$table}` ADD UNIQUE KEY unique_assignment (jury_member_id, candidate_id)");
        }
    }
    
    /**
     * Clear all assignments
     *
     * @return bool
     */
    public static function clear_assignments() {
        global $wpdb;
        
        $assignments_table = $wpdb->prefix . 'mt_jury_assignments';
        $table = esc_sql($assignments_table);
        $result = $wpdb->query("TRUNCATE TABLE `{$table}`");
        
        return $result !== false;
    }
    
    /**
     * Clear all evaluations
     *
     * @return bool
     */
    public static function clear_evaluations() {
        global $wpdb;
        
        $evaluations_table = $wpdb->prefix . 'mt_evaluations';
        $table = esc_sql($evaluations_table);
        $result = $wpdb->query("TRUNCATE TABLE `{$table}`");
        
        return $result !== false;
    }
    
    /**
     * Upgrade to version 2.5.26 - Add candidates table
     *
     * @return void
     * @since 2.5.26
     */
    private static function upgrade_to_2_5_26() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        if (strpos($charset_collate, 'utf8mb4') === false) {
            $charset_collate = 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }
        
        // Create candidates table with LONGTEXT for German sections
        $candidates_table = $wpdb->prefix . 'mt_candidates';
        $candidates_sql = "CREATE TABLE IF NOT EXISTS {$candidates_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) DEFAULT NULL,
            slug varchar(255) NOT NULL,
            name varchar(255) NOT NULL,
            organization varchar(255) DEFAULT NULL,
            position varchar(255) DEFAULT NULL,
            country varchar(100) DEFAULT NULL,
            linkedin_url text DEFAULT NULL,
            website_url text DEFAULT NULL,
            article_url text DEFAULT NULL,
            description_sections longtext DEFAULT NULL COMMENT 'JSON with 6 German sections',
            photo_attachment_id bigint(20) DEFAULT NULL,
            import_id varchar(100) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_slug (slug),
            UNIQUE KEY unique_post_id (post_id),
            KEY idx_name (name),
            KEY idx_organization (organization),
            KEY idx_import_id (import_id)
        ) {$charset_collate};";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($candidates_sql);
        
        // Check if table was created successfully
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$candidates_table}'");
        if ($table_exists) {
            MT_Logger::info('Database table created successfully', ['table' => $candidates_table]);
        } else {
            MT_Logger::database_error('CREATE TABLE', $candidates_table, $wpdb->last_error);
        }
    }
    
    /**
     * Upgrade to version 2.5.34 - Add performance indexes
     *
     * @return void
     * @since 2.5.34
     */
    private static function upgrade_to_2_5_34() {
        // Load optimizer class if not already loaded
        if (!class_exists('\MobilityTrailblazers\Core\MT_Database_Optimizer')) {
            require_once MT_PLUGIN_DIR . 'includes/core/class-mt-database-optimizer.php';
        }
        
        // Run optimization
        $results = \MobilityTrailblazers\Core\MT_Database_Optimizer::optimize();
        
        // Log results
        MT_Logger::info('Database optimization completed', ['results' => $results]);
        
        // Store optimization results for admin notice
        set_transient('mt_db_optimization_results', $results, HOUR_IN_SECONDS);
    }
    
    /**
     * Reset all plugin data
     *
     * @return void
     */
    public static function reset_all_data() {
        self::clear_evaluations();
        self::clear_assignments();
        
        // Optionally clear candidates and jury members
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($candidates as $candidate) {
            wp_delete_post($candidate->ID, true);
        }
        
        $jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        foreach ($jury_members as $jury_member) {
            wp_delete_post($jury_member->ID, true);
        }
    }
} 
