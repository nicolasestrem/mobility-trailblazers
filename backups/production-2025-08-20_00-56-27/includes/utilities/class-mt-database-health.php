<?php
/**
 * Database Health Check Utility
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

namespace MobilityTrailblazers\Utilities;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Database_Health
 *
 * Provides detailed database health checks and analysis
 */
class MT_Database_Health {
    
    /**
     * Required tables for the plugin
     *
     * @var array
     */
    private $required_tables = [
        'mt_evaluations',
        'mt_jury_assignments',
        'mt_votes',
        'mt_candidate_scores',
        'mt_vote_backups',
        'vote_reset_logs',
        'mt_error_log'
    ];
    
    /**
     * Check all plugin tables
     *
     * @return array Table check results
     */
    public function check_all_tables() {
        global $wpdb;
        $results = [];
        
        foreach ($this->required_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $results[$table] = $this->check_single_table($full_table_name);
        }
        
        return $results;
    }
    
    /**
     * Check a single table
     *
     * @param string $table_name Full table name with prefix
     * @return array Table information
     */
    private function check_single_table($table_name) {
        global $wpdb;
        
        // Check if table exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
        
        if (!$exists) {
            return [
                'exists' => false,
                'status' => 'missing',
                'message' => __('Table does not exist', 'mobility-trailblazers')
            ];
        }
        
        // Get table information
        $info = [
            'exists' => true,
            'status' => 'healthy',
            'row_count' => $wpdb->get_var("SELECT COUNT(*) FROM `$table_name`"),
            'size' => $this->get_table_size($table_name),
            'engine' => $this->get_table_engine($table_name),
            'collation' => $this->get_table_collation($table_name),
            'auto_increment' => $this->get_auto_increment($table_name),
            'indexes' => $this->get_table_indexes($table_name)
        ];
        
        // Check for issues
        $issues = $this->check_table_issues($table_name);
        if (!empty($issues)) {
            $info['status'] = 'warning';
            $info['issues'] = $issues;
        }
        
        // Run CHECK TABLE
        $check_result = $wpdb->get_row("CHECK TABLE `$table_name`");
        if ($check_result && $check_result->Msg_text !== 'OK') {
            $info['status'] = 'error';
            $info['check_result'] = $check_result->Msg_text;
        }
        
        return $info;
    }
    
    /**
     * Get table size
     *
     * @param string $table_name Table name
     * @return int Table size in bytes
     */
    private function get_table_size($table_name) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                (data_length + index_length) AS size
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            AND table_name = %s",
            $table_name
        ));
        
        return $result ? intval($result->size) : 0;
    }
    
    /**
     * Get table engine
     *
     * @param string $table_name Table name
     * @return string Table engine
     */
    private function get_table_engine($table_name) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT ENGINE 
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            AND table_name = %s",
            $table_name
        ));
        
        return $result ?: 'Unknown';
    }
    
    /**
     * Get table collation
     *
     * @param string $table_name Table name
     * @return string Table collation
     */
    private function get_table_collation($table_name) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT TABLE_COLLATION 
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            AND table_name = %s",
            $table_name
        ));
        
        return $result ?: 'Unknown';
    }
    
    /**
     * Get auto increment value
     *
     * @param string $table_name Table name
     * @return int|null Auto increment value
     */
    private function get_auto_increment($table_name) {
        global $wpdb;
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT AUTO_INCREMENT 
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            AND table_name = %s",
            $table_name
        ));
        
        return $result ? intval($result) : null;
    }
    
    /**
     * Get table indexes
     *
     * @param string $table_name Table name
     * @return array Table indexes
     */
    private function get_table_indexes($table_name) {
        global $wpdb;
        
        $indexes = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                INDEX_NAME as name,
                COLUMN_NAME as column_name,
                NON_UNIQUE as non_unique,
                INDEX_TYPE as type
            FROM information_schema.STATISTICS 
            WHERE table_schema = DATABASE()
            AND table_name = %s
            ORDER BY INDEX_NAME, SEQ_IN_INDEX",
            $table_name
        ), ARRAY_A);
        
        // Group by index name
        $grouped = [];
        foreach ($indexes as $index) {
            $name = $index['name'];
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'type' => $index['type'],
                    'unique' => !$index['non_unique'],
                    'columns' => []
                ];
            }
            $grouped[$name]['columns'][] = $index['column_name'];
        }
        
        return $grouped;
    }
    
    /**
     * Check for table issues
     *
     * @param string $table_name Table name
     * @return array List of issues
     */
    private function check_table_issues($table_name) {
        global $wpdb;
        $issues = [];
        
        // Check for missing indexes on key tables
        if (strpos($table_name, 'mt_evaluations') !== false) {
            $indexes = $this->get_table_indexes($table_name);
            
            // Check for jury-candidate index
            $has_jury_candidate_index = false;
            foreach ($indexes as $index) {
                if (in_array('jury_member_id', $index['columns']) && 
                    in_array('candidate_id', $index['columns'])) {
                    $has_jury_candidate_index = true;
                    break;
                }
            }
            
            if (!$has_jury_candidate_index) {
                $issues[] = __('Missing index on jury_member_id, candidate_id', 'mobility-trailblazers');
            }
        }
        
        // Check for orphaned records
        if (strpos($table_name, 'mt_evaluations') !== false) {
            $orphaned = $wpdb->get_var(
                "SELECT COUNT(*) FROM `$table_name` e
                 WHERE NOT EXISTS (
                     SELECT 1 FROM {$wpdb->prefix}mt_jury_assignments a 
                     WHERE a.jury_member_id = e.jury_member_id 
                     AND a.candidate_id = e.candidate_id
                 )"
            );
            
            if ($orphaned > 0) {
                $issues[] = sprintf(
                    __('%d orphaned evaluations found', 'mobility-trailblazers'),
                    $orphaned
                );
            }
        }
        
        // Check table engine consistency
        $engine = $this->get_table_engine($table_name);
        if ($engine !== 'InnoDB' && $engine !== 'Unknown') {
            $issues[] = sprintf(
                __('Table uses %s engine instead of InnoDB', 'mobility-trailblazers'),
                $engine
            );
        }
        
        return $issues;
    }
    
    /**
     * Get database connection info
     *
     * @return array Connection information
     */
    public function get_connection_info() {
        global $wpdb;
        
        return [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'prefix' => $wpdb->prefix,
            'version' => $wpdb->db_version(),
            'client_version' => $wpdb->get_var("SELECT VERSION()"),
            'max_connections' => $wpdb->get_var("SHOW VARIABLES LIKE 'max_connections'")?->Value ?? 'Unknown',
            'max_packet_size' => $wpdb->get_var("SHOW VARIABLES LIKE 'max_allowed_packet'")?->Value ?? 'Unknown'
        ];
    }
    
    /**
     * Get database statistics
     *
     * @return array Database statistics
     */
    public function get_database_stats() {
        global $wpdb;
        
        $stats = [
            'total_size' => 0,
            'table_count' => 0,
            'row_count' => 0,
            'plugin_tables' => []
        ];
        
        // Get all plugin tables
        foreach ($this->required_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            
            if ($this->table_exists($full_table_name)) {
                $size = $this->get_table_size($full_table_name);
                $rows = $wpdb->get_var("SELECT COUNT(*) FROM `$full_table_name`");
                
                $stats['plugin_tables'][$table] = [
                    'size' => $size,
                    'rows' => $rows
                ];
                
                $stats['total_size'] += $size;
                $stats['row_count'] += $rows;
                $stats['table_count']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if table exists
     *
     * @param string $table_name Table name
     * @return bool
     */
    private function table_exists($table_name) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        )) === $table_name;
    }
    
    /**
     * Analyze table for optimization
     *
     * @param string $table_name Table name
     * @return array Analysis results
     */
    public function analyze_table($table_name) {
        global $wpdb;
        
        $result = $wpdb->get_results("ANALYZE TABLE `$table_name`", ARRAY_A);
        
        return [
            'status' => $result[0]['Msg_type'] ?? 'unknown',
            'message' => $result[0]['Msg_text'] ?? '',
            'table' => $table_name
        ];
    }
    
    /**
     * Get fragmentation info
     *
     * @param string $table_name Table name
     * @return array Fragmentation information
     */
    public function get_fragmentation_info($table_name) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                data_length,
                index_length,
                data_free
            FROM information_schema.TABLES 
            WHERE table_schema = DATABASE()
            AND table_name = %s",
            $table_name
        ));
        
        if (!$result) {
            return ['fragmentation' => 0];
        }
        
        $total_size = $result->data_length + $result->index_length;
        $fragmentation = $total_size > 0 ? 
            round(($result->data_free / $total_size) * 100, 2) : 0;
        
        return [
            'data_size' => $result->data_length,
            'index_size' => $result->index_length,
            'free_space' => $result->data_free,
            'total_size' => $total_size,
            'fragmentation' => $fragmentation,
            'needs_optimization' => $fragmentation > 10
        ];
    }
    
    /**
     * Get slow queries related to plugin tables
     *
     * @param int $limit Number of queries to retrieve
     * @return array Slow queries
     */
    public function get_slow_queries($limit = 10) {
        global $wpdb;
        
        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
            return [
                'enabled' => false,
                'message' => __('SAVEQUERIES not enabled', 'mobility-trailblazers')
            ];
        }
        
        $slow_queries = [];
        $threshold = 0.05; // 50ms
        
        foreach ($wpdb->queries as $query) {
            // Check if query involves our tables
            $involves_plugin = false;
            foreach ($this->required_tables as $table) {
                if (strpos($query[0], $wpdb->prefix . $table) !== false) {
                    $involves_plugin = true;
                    break;
                }
            }
            
            if ($involves_plugin && $query[1] > $threshold) {
                $slow_queries[] = [
                    'query' => $query[0],
                    'time' => $query[1],
                    'caller' => $query[2] ?? 'Unknown'
                ];
            }
        }
        
        // Sort by time descending
        usort($slow_queries, function($a, $b) {
            return $b['time'] <=> $a['time'];
        });
        
        return [
            'enabled' => true,
            'queries' => array_slice($slow_queries, 0, $limit),
            'total' => count($slow_queries),
            'threshold' => $threshold
        ];
    }
}
