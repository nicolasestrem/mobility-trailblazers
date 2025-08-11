<?php
/**
 * Assignment Repository
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;
use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Assignment_Repository
 *
 * Handles database operations for jury assignments
 */
class MT_Assignment_Repository implements MT_Repository_Interface {
    
    /**
     * Table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_jury_assignments';
    }
    
    /**
     * Find assignment by ID
     *
     * @param int $id Assignment ID
     * @return object|null
     */
    public function find($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Find all assignments
     *
     * @param array $args Query arguments
     * @return array
     */
    public function find_all($args = []) {
        global $wpdb;
        
        $defaults = [
            'jury_member_id' => null,
            'candidate_id' => null,
            'orderby' => 'id',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Check if assigned_at column exists, if not use id for ordering
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$this->table_name}");
        if ($args['orderby'] === 'assigned_at' && !in_array('assigned_at', $columns)) {
            $args['orderby'] = 'id';
        }
        
        // Build query
        $where_clauses = ['1=1'];
        $values = [];
        
        if ($args['jury_member_id'] !== null) {
            $where_clauses[] = 'jury_member_id = %d';
            $values[] = $args['jury_member_id'];
        }
        
        if ($args['candidate_id'] !== null) {
            $where_clauses[] = 'candidate_id = %d';
            $values[] = $args['candidate_id'];
        }
        
        $where = implode(' AND ', $where_clauses);
        $orderby = sprintf('%s %s', 
            esc_sql($args['orderby']), 
            esc_sql($args['order'])
        );
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where} ORDER BY {$orderby}";
        
        if ($args['limit'] > 0) {
            $query .= " LIMIT %d OFFSET %d";
            $values[] = $args['limit'];
            $values[] = $args['offset'];
        }
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Create new assignment
     *
     * @param array $data Assignment data
     * @return int|false
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = [
            'assigned_at' => current_time('mysql'),
            'assigned_by' => get_current_user_id()
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Log the data being inserted
        error_log('MT Assignment Repository - Creating assignment with data: ' . print_r($data, true));
        
        // Generate format specifiers dynamically
        $formats = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['jury_member_id', 'candidate_id', 'assigned_by'])) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            $formats
        );
        
        if ($result === false) {
            error_log('MT Assignment Repository - Insert failed. Last error: ' . $wpdb->last_error);
        } else {
            error_log('MT Assignment Repository - Insert successful. ID: ' . $wpdb->insert_id);
            // Clear related caches
            $this->clear_assignment_caches($data['jury_member_id'] ?? null);
        }
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update assignment
     *
     * @param int $id Assignment ID
     * @param array $data Updated data
     * @return bool
     */
    public function update($id, $data) {
        global $wpdb;
        
        // Get existing assignment to clear appropriate caches
        $existing = $this->find($id);
        
        $formats = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['jury_member_id', 'candidate_id', 'assigned_by'])) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            $formats,
            ['%d']
        ) !== false;
        
        if ($result && $existing) {
            // Clear caches for affected jury members
            $this->clear_assignment_caches($existing->jury_member_id);
            if (isset($data['jury_member_id']) && $data['jury_member_id'] != $existing->jury_member_id) {
                $this->clear_assignment_caches($data['jury_member_id']);
            }
        }
        
        return $result;
    }
    
    /**
     * Delete assignment
     *
     * @param int $id Assignment ID
     * @return bool
     */
    public function delete($id) {
        global $wpdb;
        
        // Get existing assignment to clear appropriate caches
        $existing = $this->find($id);
        
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        ) !== false;
        
        if ($result && $existing) {
            // Clear caches
            $this->clear_assignment_caches($existing->jury_member_id);
        }
        
        return $result;
    }
    
    /**
     * Check if assignment exists
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public function exists($jury_member_id, $candidate_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_member_id,
            $candidate_id
        );
        
        error_log('MT Assignment Repository - Checking assignment with query: ' . $query);
        
        $count = $wpdb->get_var($query);
        
        error_log('MT Assignment Repository - Assignment count: ' . $count . ' for jury_member_id=' . $jury_member_id . ', candidate_id=' . $candidate_id);
        
        return $count > 0;
    }
    
    /**
     * Get assignments for jury member
     *
     * @param int $jury_member_id Jury member ID
     * @return array
     */
    public function get_by_jury_member($jury_member_id) {
        global $wpdb;
        
        // Check transient cache first
        $cache_key = 'mt_jury_assignments_' . $jury_member_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Query database if not cached
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, c.post_title as candidate_name 
             FROM {$this->table_name} a
             INNER JOIN {$wpdb->posts} c ON a.candidate_id = c.ID
             WHERE a.jury_member_id = %d
             ORDER BY c.post_title ASC",
            $jury_member_id
        ));
        
        // Cache for 1 hour
        set_transient($cache_key, $results, HOUR_IN_SECONDS);
        
        return $results;
    }
    
    /**
     * Get assignments for candidate
     *
     * @param int $candidate_id Candidate ID
     * @return array
     */
    public function get_by_candidate($candidate_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, j.post_title as jury_member_name 
             FROM {$this->table_name} a
             INNER JOIN {$wpdb->posts} j ON a.jury_member_id = j.ID
             WHERE a.candidate_id = %d
             ORDER BY j.post_title ASC",
            $candidate_id
        ));
    }
    
    /**
     * Get assignment by jury member and candidate
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return object|null
     */
    public function get_by_jury_and_candidate($jury_member_id, $candidate_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE jury_member_id = %d AND candidate_id = %d
             LIMIT 1",
            $jury_member_id,
            $candidate_id
        ));
    }
    
    /**
     * Bulk create assignments
     *
     * @param array $assignments Array of assignment data
     * @return int Number of assignments created
     */
    public function bulk_create($assignments) {
        global $wpdb;
        
        $created = 0;
        $assigned_at = current_time('mysql');
        $assigned_by = get_current_user_id();
        
        foreach ($assignments as $assignment) {
            // Skip if assignment already exists
            if ($this->exists($assignment['jury_member_id'], $assignment['candidate_id'])) {
                continue;
            }
            
            $data = [
                'jury_member_id' => $assignment['jury_member_id'],
                'candidate_id' => $assignment['candidate_id'],
                'assigned_at' => $assigned_at,
                'assigned_by' => $assigned_by
            ];
            
            $result = $wpdb->insert(
                $this->table_name,
                $data,
                ['%d', '%d', '%s', '%d']
            );
            
            if ($result) {
                $created++;
            }
        }
        
        return $created;
    }
    
    /**
     * Delete all assignments for jury member
     *
     * @param int $jury_member_id Jury member ID
     * @return int Number of assignments deleted
     */
    public function delete_by_jury_member($jury_member_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['jury_member_id' => $jury_member_id],
            ['%d']
        );
    }
    
    /**
     * Delete all assignments for candidate
     *
     * @param int $candidate_id Candidate ID
     * @return int Number of assignments deleted
     */
    public function delete_by_candidate($candidate_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['candidate_id' => $candidate_id],
            ['%d']
        );
    }
    
    /**
     * Get assignment statistics
     *
     * @return array
     */
    public function get_statistics() {
        global $wpdb;
        
        // Check transient cache first
        $cache_key = 'mt_assignment_statistics';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $stats = [
            'total_assignments' => 0,
            'assigned_candidates' => 0,
            'assigned_jury_members' => 0,
            'assignments_per_jury' => []
        ];
        
        // Total assignments
        $stats['total_assignments'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name}"
        );
        
        // Unique assigned candidates
        $stats['assigned_candidates'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT candidate_id) FROM {$this->table_name}"
        );
        
        // Unique assigned jury members
        $stats['assigned_jury_members'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT jury_member_id) FROM {$this->table_name}"
        );
        
        // Assignments per jury member
        $per_jury = $wpdb->get_results(
            "SELECT 
                j.post_title as jury_member_name,
                COUNT(a.id) as assignment_count
             FROM {$this->table_name} a
             INNER JOIN {$wpdb->posts} j ON a.jury_member_id = j.ID
             GROUP BY a.jury_member_id
             ORDER BY assignment_count DESC"
        );
        
        foreach ($per_jury as $jury) {
            $stats['assignments_per_jury'][] = [
                'name' => $jury->jury_member_name,
                'count' => intval($jury->assignment_count)
            ];
        }
        
        // Cache for 30 minutes
        set_transient($cache_key, $stats, 30 * MINUTE_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Get unassigned candidates
     *
     * @return array
     */
    public function get_unassigned_candidates() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT ID, post_title 
             FROM {$wpdb->posts} 
             WHERE post_type = 'mt_candidate' 
               AND post_status = 'publish'
               AND ID NOT IN (
                   SELECT DISTINCT candidate_id 
                   FROM {$this->table_name}
               )
             ORDER BY post_title ASC"
        );
    }
    
    /**
     * Clear all assignments
     *
     * @return bool
     */
    public function clear_all() {
        global $wpdb;
        
        $result = $wpdb->query("DELETE FROM {$this->table_name}") !== false;
        
        if ($result) {
            // Clear all related caches
            $this->clear_all_assignment_caches();
        }
        
        return $result;
    }
    
    /**
     * Clear assignment caches
     *
     * @param int|null $jury_member_id Optional specific jury member
     */
    private function clear_assignment_caches($jury_member_id = null) {
        // Clear statistics cache
        delete_transient('mt_assignment_statistics');
        
        // Clear specific jury member cache if provided
        if ($jury_member_id) {
            delete_transient('mt_jury_assignments_' . $jury_member_id);
        }
    }
    
    /**
     * Clear all assignment caches
     */
    private function clear_all_assignment_caches() {
        global $wpdb;
        
        // Clear statistics cache
        delete_transient('mt_assignment_statistics');
        
        // Clear all jury member caches
        $query = "DELETE FROM {$wpdb->options} 
                  WHERE option_name LIKE '_transient_mt_jury_assignments_%' 
                  OR option_name LIKE '_transient_timeout_mt_jury_assignments_%'";
        $wpdb->query($query);
    }
    
    /**
     * Count total assignments
     *
     * @param array $args Optional filter arguments
     * @return int
     */
    public function count($args = []) {
        global $wpdb;
        
        $defaults = [
            'jury_member_id' => null,
            'candidate_id' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build query
        $where_clauses = ['1=1'];
        $values = [];
        
        if ($args['jury_member_id'] !== null) {
            $where_clauses[] = 'jury_member_id = %d';
            $values[] = $args['jury_member_id'];
        }
        
        if ($args['candidate_id'] !== null) {
            $where_clauses[] = 'candidate_id = %d';
            $values[] = $args['candidate_id'];
        }
        
        $where = implode(' AND ', $where_clauses);
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where}";
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        return (int) $wpdb->get_var($query);
    }
} 