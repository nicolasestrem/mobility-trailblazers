<?php
/**
 * Evaluation Repository
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface;
use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Evaluation_Repository
 *
 * Handles database operations for evaluations
 */
class MT_Evaluation_Repository implements MT_Evaluation_Repository_Interface {
    
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
        $this->table_name = $wpdb->prefix . 'mt_evaluations';
    }
    
    /**
     * Find evaluation by ID
     *
     * @param int $id Evaluation ID
     * @return object|null
     */
    public function find($id) {
        global $wpdb;

        try {
            $result = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$this->table_name} WHERE id = %d",
                $id
            ));

            if ($wpdb->last_error) {
                MT_Logger::database_error('SELECT', $this->table_name, $wpdb->last_error, ['evaluation_id' => $id]);
                return false;
            }

            return $result;

        } catch (\Exception $e) {
            MT_Logger::critical('Exception in evaluation repository find method', [
                'evaluation_id' => $id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Find all evaluations
     *
     * @param array $args Query arguments
     * @return array
     */
    public function find_all($args = []) {
        global $wpdb;
        
        $defaults = [
            'jury_member_id' => null,
            'candidate_id' => null,
            'status' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0
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
        
        if ($args['status'] !== null) {
            $where_clauses[] = 'status = %s';
            $values[] = $args['status'];
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
     * Create new evaluation
     *
     * @param array $data Evaluation data
     * @return int|false
     */
    public function create($data) {
        global $wpdb;
        
        $defaults = [
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => 'draft'
        ];
        
        $data = wp_parse_args($data, $defaults);
        
        // Calculate total score
        if (!isset($data['total_score'])) {
            $data['total_score'] = $this->calculate_total_score($data);
        }
        
        // Generate format specifiers dynamically
        $formats = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'jury_member_id':
                case 'candidate_id':
                    $formats[] = '%d';
                    break;
                case 'courage_score':
                case 'innovation_score':
                case 'implementation_score':
                case 'relevance_score':
                case 'visibility_score':
                case 'total_score':
                    $formats[] = '%f';
                    break;
                default:
                    $formats[] = '%s';
            }
        }
        
        try {
            MT_Logger::debug('Creating new evaluation', [
                'jury_member_id' => $data['jury_member_id'] ?? null,
                'candidate_id' => $data['candidate_id'] ?? null,
                'status' => $data['status'] ?? null
            ]);

            $result = $wpdb->insert(
                $this->table_name,
                $data,
                $formats
            );

            if ($result === false) {
                MT_Logger::database_error('INSERT', $this->table_name, $wpdb->last_error, [
                    'data_keys' => array_keys($data),
                    'formats' => $formats
                ]);
                return false;
            }

            $evaluation_id = $wpdb->insert_id;
            MT_Logger::info('Evaluation created successfully', ['evaluation_id' => $evaluation_id]);
            
            // Clear related caches
            $this->clear_evaluation_caches($data['jury_member_id'] ?? null);

            return $evaluation_id;

        } catch (\Exception $e) {
            MT_Logger::critical('Exception during evaluation creation', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_keys' => array_keys($data)
            ]);
            return false;
        }
    }
    
    /**
     * Update evaluation
     *
     * @param int $id Evaluation ID
     * @param array $data Updated data
     * @return bool
     */
    public function update($id, $data) {
        global $wpdb;
        
        // Get existing evaluation to clear appropriate caches
        $existing = $this->find($id);
        
        $data['updated_at'] = current_time('mysql');
        
        // Recalculate total score if scores are updated
        if ($this->should_recalculate_score($data)) {
            $existing = $this->find($id);
            if ($existing) {
                $merged_data = array_merge((array)$existing, $data);
                $data['total_score'] = $this->calculate_total_score($merged_data);
            }
        }
        
        $formats = [];
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'jury_member_id':
                case 'candidate_id':
                case 'user_id':
                    $formats[] = '%d';
                    break;
                case 'courage_score':
                case 'innovation_score':
                case 'implementation_score':
                case 'relevance_score':
                case 'visibility_score':
                case 'total_score':
                    $formats[] = '%f';
                    break;
                default:
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
            $this->clear_evaluation_caches($existing->jury_member_id);
            if (isset($data['jury_member_id']) && $data['jury_member_id'] != $existing->jury_member_id) {
                $this->clear_evaluation_caches($data['jury_member_id']);
            }
        }
        
        return $result;
    }
    
    /**
     * Delete evaluation
     *
     * @param int $id Evaluation ID
     * @return bool
     */
    public function delete($id) {
        global $wpdb;
        
        // Get existing evaluation to clear appropriate caches
        $existing = $this->find($id);
        
        $result = $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        ) !== false;
        
        if ($result && $existing) {
            // Clear caches
            $this->clear_evaluation_caches($existing->jury_member_id);
        }
        
        return $result;
    }
    
    /**
     * Check if evaluation exists
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public function exists($jury_member_id, $candidate_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_member_id,
            $candidate_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get evaluations by jury member
     *
     * @param int $jury_member_id Jury member ID
     * @return array
     */
    public function get_by_jury_member($jury_member_id) {
        return $this->find_all(['jury_member_id' => $jury_member_id]);
    }
    
    /**
     * Get evaluations by candidate
     *
     * @param int $candidate_id Candidate ID
     * @return array
     */
    public function get_by_candidate($candidate_id) {
        return $this->find_all(['candidate_id' => $candidate_id]);
    }
    
    /**
     * Get average score for candidate
     *
     * @param int $candidate_id Candidate ID
     * @return float
     */
    public function get_average_score_for_candidate($candidate_id) {
        global $wpdb;
        
        $avg = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(total_score) FROM {$this->table_name} 
             WHERE candidate_id = %d AND status = 'completed'",
            $candidate_id
        ));
        
        return $avg ? floatval($avg) : 0;
    }
    
    /**
     * Find evaluation by jury member and candidate
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return object|null
     */
    public function find_by_jury_and_candidate($jury_member_id, $candidate_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
             WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_member_id, $candidate_id
        ));
    }
    
    /**
     * Save evaluation
     *
     * @param array $data Evaluation data
     * @return int|false Evaluation ID or false on failure
     */
    public function save($data) {
        global $wpdb;
        
        // Check if this is an update
        if (isset($data['id']) && $data['id']) {
            // Update existing evaluation
            $update_data = $data;
            $id = $update_data['id'];
            unset($update_data['id']);
            
            // Remove fields that don't exist in the database
            unset($update_data['evaluation_date']);
            unset($update_data['last_modified']);
            
            // Add updated_at timestamp
            $update_data['updated_at'] = current_time('mysql');
            
            $result = $wpdb->update(
                $this->table_name,
                $update_data,
                ['id' => $id],
                null,
                ['%d']
            );
            
            if ($result !== false) {
                return $id;
            }
            
            MT_Logger::database_error('UPDATE', $this->table_name, $wpdb->last_error, ['id' => $id, 'data' => $data]);
            return false;
        } else {
            // Check if evaluation already exists
            $existing = $this->find_by_jury_and_candidate($data['jury_member_id'], $data['candidate_id']);
            
            if ($existing) {
                // Update existing
                $data['id'] = $existing->id;
                return $this->save($data);
            } else {
                // Insert new evaluation
                $insert_data = $data;
                
                // Note: Deprecated column handling removed as of v2.0.11
                
                // Ensure we have all required fields
                $defaults = [
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql'),
                    'status' => 'draft',
                    'comments' => '',
                    'courage_score' => 0,
                    'innovation_score' => 0,
                    'implementation_score' => 0,
                    'relevance_score' => 0,
                    'visibility_score' => 0,
                    'total_score' => 0
                ];
                
                $insert_data = array_merge($defaults, $insert_data);
                
                $result = $wpdb->insert(
                    $this->table_name,
                    $insert_data
                );
                
                if ($result) {
                    return $wpdb->insert_id;
                }
                
                MT_Logger::database_error('INSERT', $this->table_name, $wpdb->last_error, ['data' => $insert_data]);
                return false;
            }
        }
    }
    
    /**
     * Get statistics
     *
     * @param array $args Filter arguments
     * @return array
     */
    public function get_statistics($args = []) {
        global $wpdb;
        
        $stats = [
            'total' => 0,
            'completed' => 0,
            'drafts' => 0,
            'average_score' => 0,
            'by_criteria' => []
        ];
        
        // Total evaluations
        $stats['total'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Completed evaluations
        $stats['completed'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'completed'"
        );
        
        // Draft evaluations
        $stats['drafts'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE status = 'draft'"
        );
        
        // Average scores
        $averages = $wpdb->get_row(
            "SELECT 
                AVG(total_score) as avg_total,
                AVG(courage_score) as avg_courage,
                AVG(innovation_score) as avg_innovation,
                AVG(implementation_score) as avg_implementation,
                AVG(relevance_score) as avg_relevance,
                AVG(visibility_score) as avg_visibility
             FROM {$this->table_name} 
             WHERE status = 'completed'"
        );
        
        if ($averages) {
            $stats['average_score'] = $averages->avg_total ? round($averages->avg_total, 2) : 0;
            $stats['by_criteria'] = [
                'courage' => $averages->avg_courage ? round($averages->avg_courage, 2) : 0,
                'innovation' => $averages->avg_innovation ? round($averages->avg_innovation, 2) : 0,
                'implementation' => $averages->avg_implementation ? round($averages->avg_implementation, 2) : 0,
                'relevance' => $averages->avg_relevance ? round($averages->avg_relevance, 2) : 0,
                'visibility' => $averages->avg_visibility ? round($averages->avg_visibility, 2) : 0
            ];
        }
        
        return $stats;
    }
    
    /**
     * Get top candidates
     *
     * @param int $limit Number of candidates to return
     * @param string $category Category slug (optional)
     * @return array
     */
    public function get_top_candidates($limit = 10, $category = '') {
        global $wpdb;
        
        $query = "SELECT 
                    c.ID as candidate_id,
                    c.post_title as candidate_name,
                    AVG(e.total_score) as average_score,
                    COUNT(e.id) as evaluation_count
                  FROM {$wpdb->posts} c
                  INNER JOIN {$this->table_name} e ON c.ID = e.candidate_id
                  WHERE c.post_type = 'mt_candidate' 
                    AND c.post_status = 'publish'
                    AND e.status = 'completed'";
        
        if (!empty($category)) {
            $query .= " AND EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE tr.object_id = c.ID 
                    AND tt.taxonomy = 'mt_award_category'
                    AND t.slug = %s
            )";
        }
        
        $query .= " GROUP BY c.ID
                   ORDER BY average_score DESC
                   LIMIT %d";
        
        if (!empty($category)) {
            return $wpdb->get_results($wpdb->prepare($query, $category, $limit));
        } else {
            return $wpdb->get_results($wpdb->prepare($query, $limit));
        }
    }
    
    /**
     * Get ranked candidates for a specific jury member
     *
     * @param int $jury_member_id Jury member ID
     * @param int $limit Number of candidates to return
     * @return array
     */
    public function get_ranked_candidates_for_jury($jury_member_id, $limit = 10) {
        global $wpdb;
        
        // Check transient cache first
        $cache_key = 'mt_jury_rankings_' . $jury_member_id . '_' . $limit;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $query = "SELECT 
                    c.ID as candidate_id,
                    c.post_title as candidate_name,
                    e.total_score,
                    e.courage_score,
                    e.innovation_score,
                    e.implementation_score,
                    e.relevance_score,
                    e.visibility_score,
                    e.status as evaluation_status,
                    pm1.meta_value as organization,
                    pm2.meta_value as position
                  FROM {$wpdb->posts} c
                  INNER JOIN {$this->table_name} e ON c.ID = e.candidate_id
                  LEFT JOIN {$wpdb->postmeta} pm1 ON c.ID = pm1.post_id AND pm1.meta_key = '_mt_organization'
                  LEFT JOIN {$wpdb->postmeta} pm2 ON c.ID = pm2.post_id AND pm2.meta_key = '_mt_position'
                  WHERE e.jury_member_id = %d
                    AND c.post_type = 'mt_candidate'
                    AND c.post_status = 'publish'
                    AND e.status = 'completed'
                  ORDER BY e.total_score DESC
                  LIMIT %d";
        
        $results = $wpdb->get_results($wpdb->prepare($query, $jury_member_id, $limit));
        
        // Cache for 30 minutes
        set_transient($cache_key, $results, 30 * MINUTE_IN_SECONDS);
        
        return $results;
    }
    
    /**
     * Get all evaluated candidates with rankings across all juries
     *
     * @param int $limit Number of candidates to return
     * @return array
     */
    public function get_overall_rankings($limit = 10) {
        global $wpdb;
        
        $query = "SELECT 
                    c.ID as candidate_id,
                    c.post_title as candidate_name,
                    AVG(e.total_score) as average_score,
                    COUNT(DISTINCT e.jury_member_id) as evaluation_count,
                    pm1.meta_value as organization
                  FROM {$wpdb->posts} c
                  INNER JOIN {$this->table_name} e ON c.ID = e.candidate_id
                  LEFT JOIN {$wpdb->postmeta} pm1 ON c.ID = pm1.post_id AND pm1.meta_key = '_mt_organization'
                  WHERE c.post_type = 'mt_candidate'
                    AND c.post_status = 'publish'
                    AND e.status = 'completed'
                  GROUP BY c.ID
                  ORDER BY average_score DESC
                  LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($query, $limit));
    }
    
    /**
     * Calculate total score
     *
     * @param array $data Evaluation data with individual scores
     * @return float
     */
    private function calculate_total_score($data) {
        $scores = [
            'courage_score' => isset($data['courage_score']) ? floatval($data['courage_score']) : 0.0,
            'innovation_score' => isset($data['innovation_score']) ? floatval($data['innovation_score']) : 0.0,
            'implementation_score' => isset($data['implementation_score']) ? floatval($data['implementation_score']) : 0.0,
            'relevance_score' => isset($data['relevance_score']) ? floatval($data['relevance_score']) : 0.0,
            'visibility_score' => isset($data['visibility_score']) ? floatval($data['visibility_score']) : 0.0
        ];
        
        // Get weights from options
        $weights = get_option('mt_criteria_weights', [
            'courage' => 1,
            'innovation' => 1,
            'implementation' => 1,
            'relevance' => 1,
            'visibility' => 1
        ]);
        
        $weighted_sum = 0.0;
        $weight_total = 0.0;
        
        foreach ($scores as $key => $score) {
            $criteria = str_replace('_score', '', $key);
            $weight = isset($weights[$criteria]) ? floatval($weights[$criteria]) : 1.0;
            $weighted_sum += $score * $weight;
            $weight_total += $weight;
        }
        
        return $weight_total > 0 ? round($weighted_sum / $weight_total, 2) : 0.0;
    }
    
    /**
     * Check if score recalculation is needed
     *
     * @param array $data Update data
     * @return bool
     */
    private function should_recalculate_score($data) {
        $score_fields = [
            'courage_score',
            'innovation_score',
            'implementation_score',
            'relevance_score',
            'visibility_score'
        ];
        
        foreach ($score_fields as $field) {
            if (isset($data[$field])) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Clear evaluation caches
     *
     * @param int|null $jury_member_id Optional specific jury member
     */
    private function clear_evaluation_caches($jury_member_id = null) {
        global $wpdb;
        
        if ($jury_member_id) {
            // Clear specific jury member ranking caches
            $jury_id = intval($jury_member_id);
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 OR option_name LIKE %s",
                '_transient_mt_jury_rankings_' . $jury_id . '_%',
                '_transient_timeout_mt_jury_rankings_' . $jury_id . '_%'
            ));
        }
    }
    
    /**
     * Clear all evaluation caches
     */
    public function clear_all_evaluation_caches() {
        global $wpdb;
        
        // Clear all jury ranking caches
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE %s 
             OR option_name LIKE %s",
            '_transient_mt_jury_rankings_%',
            '_transient_timeout_mt_jury_rankings_%'
        ));
    }
    
    /**
     * Delete evaluations with broken assignment references
     *
     * @param int $jury_member_id Optional specific jury member
     * @param int $candidate_id Optional specific candidate
     * @return int Number of evaluations deleted
     */
    public function delete_orphaned_evaluations($jury_member_id = null, $candidate_id = null) {
        global $wpdb;
        
        $assignment_table = $wpdb->prefix . 'mt_jury_assignments';
        
        // Build the query to find orphaned evaluations
        $query = "DELETE e FROM {$this->table_name} e
                  WHERE NOT EXISTS (
                      SELECT 1 FROM {$assignment_table} a 
                      WHERE a.jury_member_id = e.jury_member_id 
                      AND a.candidate_id = e.candidate_id
                  )";
        
        $where_conditions = [];
        $values = [];
        
        if ($jury_member_id !== null) {
            $where_conditions[] = "e.jury_member_id = %d";
            $values[] = $jury_member_id;
        }
        
        if ($candidate_id !== null) {
            $where_conditions[] = "e.candidate_id = %d";
            $values[] = $candidate_id;
        }
        
        if (!empty($where_conditions)) {
            $query .= " AND " . implode(' AND ', $where_conditions);
        }
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        $deleted = $wpdb->query($query);
        
        // Clear caches
        $this->clear_all_evaluation_caches();
        
        MT_Logger::info('Deleted orphaned evaluations', [
            'count' => $deleted,
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id
        ]);
        
        return $deleted !== false ? $deleted : 0;
    }
    
    /**
     * Check if evaluation can be safely deleted
     *
     * @param int $id Evaluation ID
     * @return bool
     */
    public function can_delete($id) {
        $evaluation = $this->find($id);
        
        if (!$evaluation) {
            return false;
        }
        
        // Check if the evaluation has any dependent data
        // For now, evaluations can always be deleted
        // Add more checks here if needed in the future
        
        return true;
    }
    
    /**
     * Force delete evaluation (bypass constraints)
     *
     * @param int $id Evaluation ID
     * @return bool
     */
    public function force_delete($id) {
        global $wpdb;
        
        // Get existing evaluation for logging
        $existing = $this->find($id);
        
        if (!$existing) {
            return false;
        }
        
        // Use direct query to bypass any constraints
        $result = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE id = %d",
            $id
        ));
        
        if ($result !== false) {
            // Clear caches
            $this->clear_evaluation_caches($existing->jury_member_id);
            
            MT_Logger::info('Force deleted evaluation', [
                'evaluation_id' => $id,
                'jury_member_id' => $existing->jury_member_id,
                'candidate_id' => $existing->candidate_id
            ]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Sync evaluations with assignments
     * Removes evaluations that no longer have corresponding assignments
     *
     * @return array Statistics about the sync operation
     */
    public function sync_with_assignments() {
        global $wpdb;
        
        $stats = [
            'orphaned_found' => 0,
            'orphaned_deleted' => 0,
            'errors' => []
        ];
        
        $assignment_table = $wpdb->prefix . 'mt_jury_assignments';
        
        // Find orphaned evaluations
        $orphaned = $wpdb->get_results(
            "SELECT e.* FROM {$this->table_name} e
             WHERE NOT EXISTS (
                 SELECT 1 FROM {$assignment_table} a 
                 WHERE a.jury_member_id = e.jury_member_id 
                 AND a.candidate_id = e.candidate_id
             )"
        );
        
        $stats['orphaned_found'] = count($orphaned);
        
        // Delete orphaned evaluations
        foreach ($orphaned as $evaluation) {
            if ($this->force_delete($evaluation->id)) {
                $stats['orphaned_deleted']++;
            } else {
                $stats['errors'][] = "Failed to delete evaluation ID: {$evaluation->id}";
            }
        }
        
        // Clear all caches
        $this->clear_all_evaluation_caches();
        
        MT_Logger::info('Synced evaluations with assignments', $stats);
        
        return $stats;
    }
} 
