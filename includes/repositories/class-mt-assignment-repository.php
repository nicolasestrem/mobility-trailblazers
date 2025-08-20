<?php
/**
 * Assignment Repository
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface;
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
class MT_Assignment_Repository implements MT_Assignment_Repository_Interface {
    
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
        MT_Logger::debug('Creating assignment', ['data' => $data]);
        
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
            MT_Logger::database_error('INSERT', $this->table_name, $wpdb->last_error, ['data' => $data]);
        } else {
            MT_Logger::debug('Assignment created successfully', ['assignment_id' => $wpdb->insert_id]);
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
     * @param bool $cascade_evaluations Whether to delete related evaluations
     * @return bool
     */
    public function delete($id, $cascade_evaluations = false) {
        global $wpdb;
        
        // Get existing assignment to clear appropriate caches
        $existing = $this->find($id);
        
        if (!$existing) {
            return false;
        }
        
        // Optionally delete related evaluations first
        if ($cascade_evaluations) {
            $evaluation_repo = new MT_Evaluation_Repository();
            $evaluation_repo->delete_orphaned_evaluations(
                $existing->jury_member_id, 
                $existing->candidate_id
            );
        }
        
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        ) !== false;
        
        if ($result && $existing) {
            // Clear caches
            $this->clear_assignment_caches($existing->jury_member_id);
            
            MT_Logger::info('Deleted assignment', [
                'assignment_id' => $id,
                'jury_member_id' => $existing->jury_member_id,
                'candidate_id' => $existing->candidate_id,
                'cascade_evaluations' => $cascade_evaluations
            ]);
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
        
        MT_Logger::debug('Checking assignment existence', [
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id
        ]);
        
        $count = $wpdb->get_var($query);
        
        MT_Logger::debug('Assignment existence check result', [
            'count' => $count,
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id
        ]);
        
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
     * @param bool $cascade_evaluations Whether to delete related evaluations
     * @return int Number of assignments deleted
     */
    public function delete_by_jury_member($jury_member_id, $cascade_evaluations = false) {
        global $wpdb;
        
        // Optionally delete related evaluations first
        if ($cascade_evaluations) {
            $evaluation_repo = new MT_Evaluation_Repository();
            $evaluation_repo->delete_orphaned_evaluations($jury_member_id);
        }
        
        $deleted = $wpdb->delete(
            $this->table_name,
            ['jury_member_id' => $jury_member_id],
            ['%d']
        );
        
        // Clear caches
        $this->clear_assignment_caches($jury_member_id);
        
        return $deleted;
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
     * @param bool $cascade_evaluations Whether to delete all evaluations too
     * @return bool
     */
    public function clear_all($cascade_evaluations = false) {
        global $wpdb;
        
        // Optionally delete all evaluations first
        if ($cascade_evaluations) {
            $evaluation_repo = new MT_Evaluation_Repository();
            // Truncate evaluations table
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}mt_evaluations");
            // Clear all evaluation caches
            $evaluation_repo->clear_all_evaluation_caches();
        }
        
        // Use TRUNCATE for better performance and to reset auto-increment
        // TRUNCATE is safe here as we're intentionally removing all data
        $result = $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        
        // TRUNCATE returns 0 on success, false on failure
        // DELETE returns number of rows affected or false on failure
        // We consider the operation successful if it doesn't return false
        if ($result !== false) {
            // Clear all related caches
            $this->clear_all_assignment_caches();
            
            // Clear ALL transients that might contain assignment/evaluation data
            $wpdb->query("DELETE FROM {$wpdb->options} 
                         WHERE option_name LIKE '_transient_mt_%' 
                         OR option_name LIKE '_transient_timeout_mt_%'");
            
            // Log the action
            MT_Logger::info('Cleared all assignments', [
                'cascade_evaluations' => $cascade_evaluations,
                'user_id' => get_current_user_id()
            ]);
            
            return true;
        }
        
        return false;
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
    
    /**
     * Clean up orphaned assignments (where candidate or jury member no longer exists)
     *
     * @return array Array with counts of cleaned items
     * @since 2.2.28
     */
    public function cleanup_orphaned_assignments() {
        global $wpdb;
        
        $cleaned = [
            'orphaned_candidates' => 0,
            'orphaned_jury_members' => 0,
            'missing_assigned_by' => 0
        ];
        
        // Remove assignments for non-existent candidates
        $orphaned_candidates = $wpdb->query("
            DELETE a FROM {$this->table_name} a
            LEFT JOIN {$wpdb->posts} p ON a.candidate_id = p.ID
            WHERE p.ID IS NULL OR p.post_type != 'mt_candidate'
        ");
        $cleaned['orphaned_candidates'] = $orphaned_candidates;
        
        // Remove assignments for non-existent jury members
        $orphaned_jury = $wpdb->query("
            DELETE a FROM {$this->table_name} a
            LEFT JOIN {$wpdb->users} u ON a.jury_member_id = u.ID
            WHERE u.ID IS NULL
        ");
        $cleaned['orphaned_jury_members'] = $orphaned_jury;
        
        // Fix missing assigned_by values (set to admin user)
        $admin = get_users(['role' => 'administrator', 'number' => 1]);
        if (!empty($admin)) {
            $admin_id = $admin[0]->ID;
            $missing_assigned_by = $wpdb->query($wpdb->prepare("
                UPDATE {$this->table_name}
                SET assigned_by = %d
                WHERE assigned_by IS NULL OR assigned_by = 0
            ", $admin_id));
            $cleaned['missing_assigned_by'] = $missing_assigned_by;
        }
        
        // Clear all assignment caches
        $this->clear_all_caches();
        
        // Log cleanup
        if (array_sum($cleaned) > 0) {
            MT_Logger::info('Assignment cleanup completed', $cleaned);
        }
        
        return $cleaned;
    }
    
    /**
     * Verify database integrity for assignments
     *
     * @return array Array with integrity check results
     * @since 2.2.28
     */
    public function verify_integrity() {
        global $wpdb;
        
        $issues = [];
        
        // Check for orphaned candidates
        $orphaned_candidates = $wpdb->get_var("
            SELECT COUNT(*) FROM {$this->table_name} a
            LEFT JOIN {$wpdb->posts} p ON a.candidate_id = p.ID
            WHERE p.ID IS NULL OR p.post_type != 'mt_candidate'
        ");
        if ($orphaned_candidates > 0) {
            $issues['orphaned_candidates'] = $orphaned_candidates;
        }
        
        // Check for orphaned jury members
        $orphaned_jury = $wpdb->get_var("
            SELECT COUNT(*) FROM {$this->table_name} a
            LEFT JOIN {$wpdb->users} u ON a.jury_member_id = u.ID
            WHERE u.ID IS NULL
        ");
        if ($orphaned_jury > 0) {
            $issues['orphaned_jury_members'] = $orphaned_jury;
        }
        
        // Check for missing assigned_by
        $missing_assigned_by = $wpdb->get_var("
            SELECT COUNT(*) FROM {$this->table_name}
            WHERE assigned_by IS NULL OR assigned_by = 0
        ");
        if ($missing_assigned_by > 0) {
            $issues['missing_assigned_by'] = $missing_assigned_by;
        }
        
        // Check for duplicate assignments
        $duplicates = $wpdb->get_var("
            SELECT COUNT(*) FROM (
                SELECT jury_member_id, candidate_id, COUNT(*) as cnt
                FROM {$this->table_name}
                GROUP BY jury_member_id, candidate_id
                HAVING cnt > 1
            ) as duplicates
        ");
        if ($duplicates > 0) {
            $issues['duplicate_assignments'] = $duplicates;
        }
        
        return $issues;
    }
    
    /**
     * Clear all assignment caches
     *
     * @return void
     * @since 2.2.28
     */
    private function clear_all_caches() {
        global $wpdb;
        
        // Delete all assignment-related transients
        $wpdb->query("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE '_transient_mt_jury_assignments_%'
            OR option_name LIKE '_transient_timeout_mt_jury_assignments_%'
        ");
    }
    
    /**
     * Auto-distribute assignments
     *
     * @param array $options Distribution options
     * @return array Distribution results
     */
    public function auto_distribute($options = []) {
        global $wpdb;
        
        $defaults = [
            'candidates_per_jury' => 5,
            'max_total_assignments' => 100,
            'clear_existing' => false,
            'dry_run' => false
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $results = [
            'success' => false,
            'assignments_created' => 0,
            'assignments_cleared' => 0,
            'errors' => [],
            'dry_run' => $options['dry_run']
        ];
        
        try {
            // Clear existing assignments if requested
            if ($options['clear_existing'] && !$options['dry_run']) {
                $cleared = $this->clear_all(false);
                $results['assignments_cleared'] = $cleared ? $this->count() : 0;
            }
            
            // Get all active jury members and candidates
            $jury_members = get_posts([
                'post_type' => 'mt_jury_member',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);
            
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);
            
            if (empty($jury_members) || empty($candidates)) {
                $results['errors'][] = 'No jury members or candidates found';
                return $results;
            }
            
            // Distribute assignments
            $assignments_to_create = [];
            $jury_count = count($jury_members);
            $candidate_count = count($candidates);
            
            foreach ($jury_members as $jury_id) {
                // Shuffle candidates for random distribution
                $shuffled_candidates = $candidates;
                shuffle($shuffled_candidates);
                
                $assigned_count = 0;
                foreach ($shuffled_candidates as $candidate_id) {
                    if ($assigned_count >= $options['candidates_per_jury']) {
                        break;
                    }
                    
                    // Check if assignment already exists (if not clearing)
                    if (!$options['clear_existing'] && $this->exists($jury_id, $candidate_id)) {
                        continue;
                    }
                    
                    $assignments_to_create[] = [
                        'jury_member_id' => $jury_id,
                        'candidate_id' => $candidate_id
                    ];
                    
                    $assigned_count++;
                }
            }
            
            // Limit total assignments
            if (count($assignments_to_create) > $options['max_total_assignments']) {
                $assignments_to_create = array_slice($assignments_to_create, 0, $options['max_total_assignments']);
            }
            
            if (!$options['dry_run']) {
                $results['assignments_created'] = $this->bulk_create($assignments_to_create);
            } else {
                $results['assignments_created'] = count($assignments_to_create);
            }
            
            $results['success'] = true;
            
            MT_Logger::info('Auto-distribution completed', $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            MT_Logger::error('Auto-distribution failed', ['error' => $e->getMessage()]);
        }
        
        return $results;
    }
    
    /**
     * Rebalance assignments
     *
     * @return array Rebalancing results
     */
    public function rebalance_assignments() {
        global $wpdb;
        
        $results = [
            'success' => false,
            'moved_assignments' => 0,
            'errors' => []
        ];
        
        try {
            // Get assignment counts per jury member
            $assignment_counts = $wpdb->get_results("
                SELECT 
                    jury_member_id,
                    COUNT(*) as assignment_count
                FROM {$this->table_name}
                GROUP BY jury_member_id
                ORDER BY assignment_count DESC
            ");
            
            if (empty($assignment_counts)) {
                $results['errors'][] = 'No assignments found to rebalance';
                return $results;
            }
            
            // Calculate average assignments per jury member
            $total_assignments = array_sum(array_column($assignment_counts, 'assignment_count'));
            $jury_count = count($assignment_counts);
            $target_per_jury = ceil($total_assignments / $jury_count);
            
            // Find overloaded and underloaded jury members
            $overloaded = [];
            $underloaded = [];
            
            foreach ($assignment_counts as $count) {
                if ($count->assignment_count > $target_per_jury) {
                    $overloaded[] = $count;
                } elseif ($count->assignment_count < $target_per_jury) {
                    $underloaded[] = $count;
                }
            }
            
            // Move assignments from overloaded to underloaded
            foreach ($overloaded as $over) {
                $excess = $over->assignment_count - $target_per_jury;
                
                // Get some assignments from this jury member
                $assignments_to_move = $wpdb->get_results($wpdb->prepare("
                    SELECT * FROM {$this->table_name}
                    WHERE jury_member_id = %d
                    ORDER BY id ASC
                    LIMIT %d
                ", $over->jury_member_id, $excess));
                
                foreach ($assignments_to_move as $assignment) {
                    // Find an underloaded jury member
                    $target_jury = null;
                    foreach ($underloaded as $key => $under) {
                        if ($under->assignment_count < $target_per_jury) {
                            $target_jury = $under;
                            $underloaded[$key]->assignment_count++;
                            break;
                        }
                    }
                    
                    if ($target_jury) {
                        // Move the assignment
                        $updated = $wpdb->update(
                            $this->table_name,
                            ['jury_member_id' => $target_jury->jury_member_id],
                            ['id' => $assignment->id],
                            ['%d'],
                            ['%d']
                        );
                        
                        if ($updated) {
                            $results['moved_assignments']++;
                        }
                    }
                }
            }
            
            // Clear caches
            $this->clear_all_assignment_caches();
            
            $results['success'] = true;
            
            MT_Logger::info('Assignment rebalancing completed', $results);
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            MT_Logger::error('Assignment rebalancing failed', ['error' => $e->getMessage()]);
        }
        
        return $results;
    }
} 
