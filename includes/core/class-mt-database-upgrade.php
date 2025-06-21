<?php
/**
 * Database Upgrade Handler
 *
 * @package MobilityTrailblazers
 * @since 2.0.1
 */

namespace MobilityTrailblazers\Core;

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
        
        // Update database version
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
            $wpdb->query("ALTER TABLE {$evaluations_table} ADD COLUMN comments longtext AFTER total_score");
        }
        
        if (!in_array('created_at', $eval_columns)) {
            $wpdb->query("ALTER TABLE {$evaluations_table} ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP");
        }
        
        if (!in_array('updated_at', $eval_columns)) {
            $wpdb->query("ALTER TABLE {$evaluations_table} ADD COLUMN updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
        
        // Check and add missing columns to assignments table
        $assignments_table = $wpdb->prefix . 'mt_jury_assignments';
        $assign_columns = $wpdb->get_col("SHOW COLUMNS FROM {$assignments_table}");
        
        if (!in_array('assigned_at', $assign_columns)) {
            $wpdb->query("ALTER TABLE {$assignments_table} ADD COLUMN assigned_at datetime DEFAULT CURRENT_TIMESTAMP AFTER candidate_id");
        }
        
        if (!in_array('assigned_by', $assign_columns)) {
            $wpdb->query("ALTER TABLE {$assignments_table} ADD COLUMN assigned_by bigint(20) DEFAULT NULL AFTER assigned_at");
        }
        
        // Add indexes if they don't exist
        $eval_indexes = $wpdb->get_results("SHOW INDEX FROM {$evaluations_table}");
        $eval_index_names = array_column($eval_indexes, 'Key_name');
        
        if (!in_array('idx_status', $eval_index_names)) {
            $wpdb->query("ALTER TABLE {$evaluations_table} ADD INDEX idx_status (status)");
        }
        
        $assign_indexes = $wpdb->get_results("SHOW INDEX FROM {$assignments_table}");
        $assign_index_names = array_column($assign_indexes, 'Key_name');
        
        if (!in_array('unique_assignment', $assign_index_names)) {
            // First remove duplicates if any
            $wpdb->query("
                DELETE a1 FROM {$assignments_table} a1
                INNER JOIN {$assignments_table} a2 
                WHERE a1.id > a2.id 
                AND a1.jury_member_id = a2.jury_member_id 
                AND a1.candidate_id = a2.candidate_id
            ");
            
            // Then add unique index
            $wpdb->query("ALTER TABLE {$assignments_table} ADD UNIQUE KEY unique_assignment (jury_member_id, candidate_id)");
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
        $result = $wpdb->query("TRUNCATE TABLE {$assignments_table}");
        
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
        $result = $wpdb->query("TRUNCATE TABLE {$evaluations_table}");
        
        return $result !== false;
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