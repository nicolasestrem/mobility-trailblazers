<?php
/**
 * Database Optimizer for Mobility Trailblazers
 * 
 * Adds performance indexes and optimizations
 * 
 * @package MobilityTrailblazers
 * @since 2.5.34
 */

namespace MobilityTrailblazers\Core;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Database_Optimizer
 * 
 * Handles database optimizations and index creation
 */
class MT_Database_Optimizer {
    
    /**
     * Run all optimizations
     * 
     * @return array Results of optimization
     */
    public static function optimize() {
        $results = [];
        
        // Add indexes
        $results['indexes'] = self::add_performance_indexes();
        
        // Analyze tables
        $results['analysis'] = self::analyze_tables();
        
        // Update option to track optimization
        update_option('mt_db_optimized_version', MT_VERSION);
        update_option('mt_db_optimized_date', current_time('mysql'));
        
        return $results;
    }
    
    /**
     * Add performance indexes
     * 
     * @return array Results
     */
    public static function add_performance_indexes() {
        global $wpdb;
        $results = [];
        
        // Evaluation table indexes
        $evaluations_table = esc_sql($wpdb->prefix . 'mt_evaluations');
        
        // Check and add indexes with error handling
        $indexes = [
            [
                'table' => $evaluations_table,
                'name' => 'idx_status_date',
                'columns' => '(status, created_at)',
                'description' => 'Optimize status filtering with date sorting'
            ],
            [
                'table' => $evaluations_table,
                'name' => 'idx_total_score',
                'columns' => '(total_score DESC)',
                'description' => 'Optimize ranking calculations'
            ],
            [
                'table' => $evaluations_table,
                'name' => 'idx_jury_candidate',
                'columns' => '(jury_member_id, candidate_id)',
                'description' => 'Optimize evaluation lookups'
            ]
        ];
        
        // Assignment table indexes
        $assignments_table = esc_sql($wpdb->prefix . 'mt_jury_assignments');
        
        $indexes[] = [
            'table' => $assignments_table,
            'name' => 'idx_candidate_jury',
            'columns' => '(candidate_id, jury_member_id)',
            'description' => 'Optimize reverse assignment lookups'
        ];
        
        $indexes[] = [
            'table' => $assignments_table,
            'name' => 'idx_assigned_at',
            'columns' => '(assigned_at)',
            'description' => 'Optimize date-based queries'
        ];
        
        // Process each index
        foreach ($indexes as $index) {
            $result = self::add_index_if_not_exists(
                $index['table'],
                $index['name'],
                $index['columns']
            );
            
            $results[$index['name']] = [
                'status' => $result ? 'added' : 'exists',
                'description' => $index['description']
            ];
        }
        
        // Add composite index for postmeta MT fields
        $postmeta_table = esc_sql($wpdb->postmeta);
        
        // Check if we can add index to postmeta (requires ALTER privilege)
        $can_alter_postmeta = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM information_schema.USER_PRIVILEGES 
            WHERE GRANTEE LIKE '%{$wpdb->dbuser}%' 
            AND PRIVILEGE_TYPE = 'ALTER'
        ");
        
        if ($can_alter_postmeta) {
            $result = self::add_index_if_not_exists(
                $postmeta_table,
                'idx_mt_meta',
                '(meta_key(20), post_id)',
                "meta_key LIKE '_mt_%'"
            );
            
            $results['idx_mt_meta'] = [
                'status' => $result ? 'added' : 'exists',
                'description' => 'Optimize MT-specific meta queries'
            ];
        } else {
            $results['idx_mt_meta'] = [
                'status' => 'skipped',
                'description' => 'Insufficient privileges for postmeta table'
            ];
        }
        
        return $results;
    }
    
    /**
     * Add index if it doesn't exist
     * 
     * @param string $table Table name
     * @param string $index_name Index name
     * @param string $columns Column definition
     * @param string $where Optional WHERE clause for partial index
     * @return bool True if index was added, false if already exists
     */
    private static function add_index_if_not_exists($table, $index_name, $columns, $where = '') {
        global $wpdb;
        
        try {
            // Check if index exists
            $index_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) 
                FROM information_schema.STATISTICS 
                WHERE table_schema = %s 
                AND table_name = %s 
                AND index_name = %s",
                DB_NAME,
                $table,
                $index_name
            ));
            
            if ($index_exists) {
                MT_Logger::debug('Database index already exists', [
                    'index_name' => $index_name,
                    'table' => $table
                ]);
                return false;
            }
            
            // Add the index
            $sql = "ALTER TABLE `{$table}` ADD INDEX `{$index_name}` {$columns}";
            if ($where) {
                // MySQL 8.0+ supports partial indexes
                $mysql_version = $wpdb->db_version();
                if (version_compare($mysql_version, '8.0.0', '>=')) {
                    $sql .= " WHERE {$where}";
                }
            }
            
            $result = $wpdb->query($sql);
            
            if ($result === false) {
                MT_Logger::database_error('ADD INDEX', $table, $wpdb->last_error, [
                    'index_name' => $index_name
                ]);
                return false;
            }
            
            MT_Logger::info('Database index added successfully', [
                'index_name' => $index_name,
                'table' => $table
            ]);
            return true;
            
        } catch (\Exception $e) {
            MT_Logger::error('Database index creation failed', [
                'index_name' => $index_name,
                'table' => $table,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Analyze and optimize tables
     * 
     * @return array Analysis results
     */
    public static function analyze_tables() {
        global $wpdb;
        $results = [];
        
        $tables = [
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'mt_jury_assignments',
            $wpdb->prefix . 'mt_candidates'
        ];
        
        foreach ($tables as $table) {
            $table_name = esc_sql($table);
            
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
                $results[$table] = 'not_found';
                continue;
            }
            
            // Analyze table
            $wpdb->query("ANALYZE TABLE `{$table_name}`");
            
            // Get table stats
            $stats = $wpdb->get_row("
                SELECT 
                    table_rows as row_count,
                    data_length as data_size,
                    index_length as index_size,
                    data_free as fragmentation
                FROM information_schema.TABLES 
                WHERE table_schema = '" . DB_NAME . "' 
                AND table_name = '{$table_name}'
            ");
            
            $results[$table] = [
                'rows' => $stats->row_count ?? 0,
                'data_size' => self::format_bytes($stats->data_size ?? 0),
                'index_size' => self::format_bytes($stats->index_size ?? 0),
                'fragmentation' => self::format_bytes($stats->fragmentation ?? 0),
                'optimized' => true
            ];
            
            // Optimize if fragmentation is high
            if (($stats->fragmentation ?? 0) > 1048576) { // > 1MB fragmentation
                $wpdb->query("OPTIMIZE TABLE `{$table_name}`");
                $results[$table]['optimized_fragmentation'] = true;
            }
        }
        
        return $results;
    }
    
    /**
     * Check current index status
     * 
     * @return array Current indexes
     */
    public static function check_indexes() {
        global $wpdb;
        $indexes = [];
        
        $tables = [
            $wpdb->prefix . 'mt_evaluations',
            $wpdb->prefix . 'mt_jury_assignments'
        ];
        
        foreach ($tables as $table) {
            $table_name = esc_sql($table);
            
            $table_indexes = $wpdb->get_results("
                SELECT 
                    INDEX_NAME as name,
                    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns,
                    CARDINALITY as cardinality,
                    INDEX_TYPE as type
                FROM information_schema.STATISTICS
                WHERE table_schema = '" . DB_NAME . "'
                AND table_name = '{$table_name}'
                GROUP BY INDEX_NAME
            ");
            
            $indexes[$table] = $table_indexes;
        }
        
        return $indexes;
    }
    
    /**
     * Format bytes to human readable
     * 
     * @param int $bytes Bytes
     * @return string Formatted size
     */
    private static function format_bytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * Get optimization recommendations
     * 
     * @return array Recommendations
     */
    public static function get_recommendations() {
        global $wpdb;
        $recommendations = [];
        
        // Check for missing indexes
        $current_indexes = self::check_indexes();
        
        // Check evaluations table
        $eval_table = $wpdb->prefix . 'mt_evaluations';
        $eval_indexes = array_column($current_indexes[$eval_table] ?? [], 'name');
        
        if (!in_array('idx_status_date', $eval_indexes)) {
            $recommendations[] = [
                'priority' => 'high',
                'issue' => 'Missing index on status and created_at',
                'impact' => 'Slow filtering and sorting of evaluations',
                'solution' => 'Run database optimization'
            ];
        }
        
        if (!in_array('idx_total_score', $eval_indexes)) {
            $recommendations[] = [
                'priority' => 'high',
                'issue' => 'Missing index on total_score',
                'impact' => 'Slow ranking calculations',
                'solution' => 'Run database optimization'
            ];
        }
        
        // Check for large tables without optimization
        $large_tables = $wpdb->get_results("
            SELECT 
                table_name,
                table_rows,
                data_free
            FROM information_schema.TABLES
            WHERE table_schema = '" . DB_NAME . "'
            AND table_name LIKE '{$wpdb->prefix}mt_%'
            AND (table_rows > 10000 OR data_free > 5242880)
        ");
        
        foreach ($large_tables as $table) {
            if ($table->data_free > 5242880) { // > 5MB fragmentation
                $recommendations[] = [
                    'priority' => 'medium',
                    'issue' => "Table {$table->table_name} has high fragmentation",
                    'impact' => 'Slower queries and wasted disk space',
                    'solution' => 'Run OPTIMIZE TABLE'
                ];
            }
            
            if ($table->table_rows > 10000) {
                $recommendations[] = [
                    'priority' => 'low',
                    'issue' => "Table {$table->table_name} has {$table->table_rows} rows",
                    'impact' => 'Consider archiving old data',
                    'solution' => 'Implement data retention policy'
                ];
            }
        }
        
        return $recommendations;
    }
}
?>
