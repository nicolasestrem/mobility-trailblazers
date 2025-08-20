<?php
/**
 * Performance Optimizer
 *
 * @package MobilityTrailblazers
 * @since 2.5.35
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Performance_Optimizer
 *
 * Handles performance optimizations for the plugin
 */
class MT_Performance_Optimizer {
    
    /**
     * Initialize performance optimizations
     */
    public static function init() {
        // Hook into WordPress to optimize queries
        add_action('init', [__CLASS__, 'optimize_database_queries']);
        add_action('admin_init', [__CLASS__, 'optimize_admin_queries']);
        
        // Add query optimizations
        add_filter('posts_clauses', [__CLASS__, 'optimize_post_queries'], 10, 2);
        
        // Cache optimization
        add_action('save_post', [__CLASS__, 'clear_related_caches'], 999);
        add_action('deleted_post', [__CLASS__, 'clear_related_caches']);
        
        // Clear cache when post meta is updated directly
        add_action('updated_post_meta', [__CLASS__, 'clear_cache_on_meta_update'], 10, 4);
        add_action('added_post_meta', [__CLASS__, 'clear_cache_on_meta_update'], 10, 4);
        
        // Handle Elementor saves
        add_action('elementor/editor/after_save', [__CLASS__, 'clear_cache_after_elementor_save'], 10, 2);
    }
    
    /**
     * Optimize database queries
     */
    public static function optimize_database_queries() {
        global $wpdb;
        
        // Add missing indexes if they don't exist
        $indexes_to_add = [
            'wp_mt_evaluations' => [
                'idx_updated_at_opt' => 'updated_at',
                'idx_total_score_opt' => 'total_score',
                'idx_jury_candidate_status' => 'jury_member_id, candidate_id, status'
            ],
            'wp_mt_jury_assignments' => [
                'idx_jury_candidate_unique' => 'jury_member_id, candidate_id',
                'idx_assignment_date' => 'assignment_date'
            ]
        ];
        
        foreach ($indexes_to_add as $table => $indexes) {
            foreach ($indexes as $index_name => $columns) {
                $table_name = $wpdb->prefix . str_replace('wp_', '', $table);
                
                // Check if index exists
                $existing_indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name} WHERE Key_name = '{$index_name}'");
                
                if (empty($existing_indexes)) {
                    $wpdb->query("ALTER TABLE {$table_name} ADD INDEX {$index_name} ({$columns})");
                }
            }
        }
    }
    
    /**
     * Optimize admin queries
     */
    public static function optimize_admin_queries() {
        if (!is_admin()) {
            return;
        }
        
        // Optimize admin page queries
        add_filter('pre_get_posts', [__CLASS__, 'optimize_admin_post_queries']);
    }
    
    /**
     * Optimize post queries
     */
    public static function optimize_post_queries($clauses, $query) {
        global $wpdb;
        
        // Optimize candidate and jury member queries
        if ($query->get('post_type') === 'mt_candidate' || $query->get('post_type') === 'mt_jury_member') {
            // Add performance hints
            $clauses['fields'] = "SQL_CALC_FOUND_ROWS " . $clauses['fields'];
        }
        
        return $clauses;
    }
    
    /**
     * Optimize admin post queries
     */
    public static function optimize_admin_post_queries($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Optimize candidate listing
        if ($query->get('post_type') === 'mt_candidate') {
            // Use specific fields only
            $query->set('fields', 'ids');
            $query->set('update_post_meta_cache', false);
            $query->set('update_post_term_cache', false);
        }
    }
    
    /**
     * Get optimized recent evaluations with preloaded data
     */
    public static function get_optimized_recent_evaluations($limit = 5) {
        global $wpdb;
        
        $cache_key = 'mt_recent_evaluations_optimized_' . $limit;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Single optimized query with JOINs
        $query = "SELECT 
                    e.id,
                    e.jury_member_id,
                    e.candidate_id,
                    e.total_score,
                    e.status,
                    e.updated_at,
                    jm.post_title as jury_member_name,
                    c.post_title as candidate_name
                  FROM {$wpdb->prefix}mt_evaluations e
                  LEFT JOIN {$wpdb->posts} jm ON e.jury_member_id = jm.ID 
                    AND jm.post_type = 'mt_jury_member'
                  LEFT JOIN {$wpdb->posts} c ON e.candidate_id = c.ID 
                    AND c.post_type = 'mt_candidate'
                  WHERE jm.post_status = 'publish' 
                    AND c.post_status = 'publish'
                  ORDER BY e.updated_at DESC
                  LIMIT %d";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $limit));
        
        // Cache for 5 minutes
        set_transient($cache_key, $results, 5 * MINUTE_IN_SECONDS);
        
        return $results;
    }
    
    /**
     * Get optimized jury assignment progress
     */
    public static function get_optimized_jury_progress($jury_member_id) {
        global $wpdb;
        
        $cache_key = 'mt_jury_progress_opt_' . $jury_member_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $assignment_table = $wpdb->prefix . 'mt_jury_assignments';
        $evaluation_table = $wpdb->prefix . 'mt_evaluations';
        
        $query = "SELECT 
                    COUNT(a.id) as total_assignments,
                    COUNT(CASE WHEN e.status = 'completed' THEN 1 END) as completed,
                    COUNT(CASE WHEN e.status = 'draft' THEN 1 END) as drafts,
                    COUNT(CASE WHEN e.status IS NULL THEN 1 END) as pending
                  FROM {$assignment_table} a
                  LEFT JOIN {$evaluation_table} e ON a.jury_member_id = e.jury_member_id 
                    AND a.candidate_id = e.candidate_id
                  WHERE a.jury_member_id = %d";
        
        $result = $wpdb->get_row($wpdb->prepare($query, $jury_member_id));
        
        $total = $result ? intval($result->total_assignments) : 0;
        $completed = $result ? intval($result->completed) : 0;
        $drafts = $result ? intval($result->drafts) : 0;
        $pending = $result ? intval($result->pending) : 0;
        
        $progress = [
            'total' => $total,
            'completed' => $completed,
            'drafts' => $drafts,
            'pending' => $pending,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0
        ];
        
        // Cache for 10 minutes
        set_transient($cache_key, $progress, 10 * MINUTE_IN_SECONDS);
        
        return $progress;
    }
    
    /**
     * Clear related caches when posts are updated
     */
    public static function clear_related_caches($post_id) {
        $post_type = get_post_type($post_id);
        
        if (in_array($post_type, ['mt_candidate', 'mt_jury_member'])) {
            // Clear post meta cache for this specific post
            // This ensures the criteria grid content is refreshed after editing
            wp_cache_delete($post_id, 'post_meta');
            clean_post_cache($post_id);
            
            // Clear evaluation caches
            self::clear_evaluation_caches();
            
            // Clear assignment caches
            self::clear_assignment_caches();
            
            // Clear any page caches if caching plugins are active
            if (function_exists('wp_cache_flush')) {
                wp_cache_flush();
            }
        }
    }
    
    /**
     * Clear cache when post meta is updated
     * 
     * @param int    $meta_id    ID of updated metadata entry
     * @param int    $post_id    Post ID
     * @param string $meta_key   Meta key
     * @param mixed  $meta_value Meta value
     */
    public static function clear_cache_on_meta_update($meta_id, $post_id, $meta_key, $meta_value) {
        $post_type = get_post_type($post_id);
        
        // Clear cache if it's a candidate and the meta key is related to criteria
        if ($post_type === 'mt_candidate' && strpos($meta_key, '_mt_criterion_') === 0) {
            wp_cache_delete($post_id, 'post_meta');
            clean_post_cache($post_id);
            
            // Clear all evaluation-related caches
            self::clear_evaluation_caches();
        }
    }
    
    /**
     * Clear cache after Elementor save
     * 
     * @param int   $post_id Post ID
     * @param array $data    Editor data
     */
    public static function clear_cache_after_elementor_save($post_id, $data) {
        $post_type = get_post_type($post_id);
        
        if ($post_type === 'mt_candidate') {
            // Clear all caches for this candidate
            wp_cache_delete($post_id, 'post_meta');
            clean_post_cache($post_id);
            
            // Clear WordPress object cache
            wp_cache_flush();
            
            // Clear evaluation caches
            self::clear_evaluation_caches();
            self::clear_assignment_caches();
        }
    }
    
    /**
     * Clear evaluation-related caches
     */
    public static function clear_evaluation_caches() {
        global $wpdb;
        
        // Clear recent evaluations cache
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_recent_evaluations_%' 
             OR option_name LIKE '_transient_timeout_mt_recent_evaluations_%'"
        );
        
        // Clear jury progress caches
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_jury_progress_%' 
             OR option_name LIKE '_transient_timeout_mt_jury_progress_%'"
        );
    }
    
    /**
     * Clear assignment-related caches
     */
    public static function clear_assignment_caches() {
        global $wpdb;
        
        // Clear assignment caches
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_mt_jury_assignments_%' 
             OR option_name LIKE '_transient_timeout_mt_jury_assignments_%'"
        );
        
        // Clear assignment statistics
        delete_transient('mt_assignment_statistics');
    }
    
    /**
     * Get database query statistics
     */
    public static function get_query_statistics() {
        global $wpdb;
        
        $stats = [];
        
        // Get slow query count
        $slow_queries = $wpdb->get_var("SHOW STATUS LIKE 'Slow_queries'");
        $stats['slow_queries'] = $slow_queries;
        
        // Get query cache stats
        $query_cache_hits = $wpdb->get_var("SHOW STATUS LIKE 'Qcache_hits'");
        $stats['query_cache_hits'] = $query_cache_hits;
        
        return $stats;
    }
    
    /**
     * Optimize evaluation count queries
     */
    public static function get_optimized_evaluation_count($filters = []) {
        global $wpdb;
        
        $cache_key = 'mt_eval_count_' . md5(serialize($filters));
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $where_clauses = ['1=1'];
        $values = [];
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $values[] = $filters['status'];
        }
        
        if (!empty($filters['jury_member_id'])) {
            $where_clauses[] = 'jury_member_id = %d';
            $values[] = $filters['jury_member_id'];
        }
        
        if (!empty($filters['candidate_id'])) {
            $where_clauses[] = 'candidate_id = %d';
            $values[] = $filters['candidate_id'];
        }
        
        $where_sql = implode(' AND ', $where_clauses);
        $count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}mt_evaluations WHERE {$where_sql}";
        
        if (!empty($values)) {
            $count_query = $wpdb->prepare($count_query, $values);
        }
        
        $count = $wpdb->get_var($count_query);
        
        // Cache for 5 minutes
        set_transient($cache_key, $count, 5 * MINUTE_IN_SECONDS);
        
        return $count;
    }
}
