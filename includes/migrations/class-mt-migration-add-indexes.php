<?php
/**
 * Database Migration - Add Performance Indexes
 *
 * @package MobilityTrailblazers
 * @since 2.2.1
 */

namespace MobilityTrailblazers\Migrations;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Migration_Add_Indexes
 *
 * Adds performance indexes to database tables
 */
class MT_Migration_Add_Indexes {
    
    /**
     * Run the migration
     *
     * @return bool
     */
    public static function run() {
        global $wpdb;
        
        $success = true;
        
        // Add composite indexes for mt_evaluations table
        $evaluations_table = $wpdb->prefix . 'mt_evaluations';
        
        // Composite index for jury member and status (for progress queries)
        $index1 = self::add_index($evaluations_table, 'idx_jury_status', ['jury_member_id', 'status']);
        if (!$index1) $success = false;
        
        // Composite index for candidate and status (for ranking queries)
        $index2 = self::add_index($evaluations_table, 'idx_candidate_status', ['candidate_id', 'status']);
        if (!$index2) $success = false;
        
        // Index for total_score (for ranking and sorting)
        $index3 = self::add_index($evaluations_table, 'idx_total_score', ['total_score']);
        if (!$index3) $success = false;
        
        // Composite index for status and total_score (for filtered rankings)
        $index4 = self::add_index($evaluations_table, 'idx_status_score', ['status', 'total_score']);
        if (!$index4) $success = false;
        
        // Add composite indexes for mt_jury_assignments table
        $assignments_table = $wpdb->prefix . 'mt_jury_assignments';
        
        // Composite index for jury member and assigned date (for recent assignments)
        $index5 = self::add_index($assignments_table, 'idx_jury_date', ['jury_member_id', 'assigned_at']);
        if (!$index5) $success = false;
        
        // Index for assigned_by (for tracking who made assignments)
        $index6 = self::add_index($assignments_table, 'idx_assigned_by', ['assigned_by']);
        if (!$index6) $success = false;
        
        // Log migration completion
        if ($success) {
            update_option('mt_migration_indexes_added', current_time('mysql'));
            error_log('MT Migration: Successfully added performance indexes');
        } else {
            error_log('MT Migration: Some indexes could not be added');
        }
        
        return $success;
    }
    
    /**
     * Add an index to a table if it doesn't exist
     *
     * @param string $table Table name
     * @param string $index_name Index name
     * @param array $columns Column names
     * @return bool
     */
    private static function add_index($table, $index_name, $columns) {
        global $wpdb;
        
        // Check if index already exists
        $existing = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = '$index_name'");
        
        if (!empty($existing)) {
            error_log("MT Migration: Index $index_name already exists on $table");
            return true;
        }
        
        // Build column list
        $column_list = implode(', ', array_map(function($col) {
            return "`$col`";
        }, $columns));
        
        // Add the index
        $query = "ALTER TABLE $table ADD INDEX $index_name ($column_list)";
        $result = $wpdb->query($query);
        
        if ($result === false) {
            error_log("MT Migration: Failed to add index $index_name on $table. Error: " . $wpdb->last_error);
            return false;
        }
        
        error_log("MT Migration: Successfully added index $index_name on $table");
        return true;
    }
    
    /**
     * Check if migration is needed
     *
     * @return bool
     */
    public static function is_needed() {
        $migration_done = get_option('mt_migration_indexes_added', false);
        return !$migration_done;
    }
    
    /**
     * Rollback the migration
     *
     * @return bool
     */
    public static function rollback() {
        global $wpdb;
        
        $success = true;
        
        // Define indexes to remove
        $indexes = [
            $wpdb->prefix . 'mt_evaluations' => [
                'idx_jury_status',
                'idx_candidate_status',
                'idx_total_score',
                'idx_status_score'
            ],
            $wpdb->prefix . 'mt_jury_assignments' => [
                'idx_jury_date',
                'idx_assigned_by'
            ]
        ];
        
        // Remove each index
        foreach ($indexes as $table => $index_list) {
            foreach ($index_list as $index_name) {
                // Check if index exists
                $existing = $wpdb->get_results("SHOW INDEX FROM $table WHERE Key_name = '$index_name'");
                
                if (!empty($existing)) {
                    $query = "ALTER TABLE $table DROP INDEX $index_name";
                    $result = $wpdb->query($query);
                    
                    if ($result === false) {
                        error_log("MT Migration: Failed to remove index $index_name from $table");
                        $success = false;
                    } else {
                        error_log("MT Migration: Successfully removed index $index_name from $table");
                    }
                }
            }
        }
        
        // Remove migration flag
        if ($success) {
            delete_option('mt_migration_indexes_added');
        }
        
        return $success;
    }
}