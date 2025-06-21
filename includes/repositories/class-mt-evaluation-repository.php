<?php
/**
 * Evaluation Repository
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Repository_Interface;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Evaluation_Repository
 *
 * Handles database operations for evaluations
 */
class MT_Evaluation_Repository implements MT_Repository_Interface {
    
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
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        ));
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
        
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            [
                '%d', // jury_member_id
                '%d', // candidate_id
                '%d', // courage_score
                '%d', // innovation_score
                '%d', // implementation_score
                '%d', // relevance_score
                '%d', // visibility_score
                '%f', // total_score
                '%s', // comments
                '%s', // status
                '%s', // created_at
                '%s'  // updated_at
            ]
        );
        
        return $result ? $wpdb->insert_id : false;
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
                case 'courage_score':
                case 'innovation_score':
                case 'implementation_score':
                case 'relevance_score':
                case 'visibility_score':
                    $formats[] = '%d';
                    break;
                case 'total_score':
                    $formats[] = '%f';
                    break;
                default:
                    $formats[] = '%s';
            }
        }
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            $formats,
            ['%d']
        ) !== false;
    }
    
    /**
     * Delete evaluation
     *
     * @param int $id Evaluation ID
     * @return bool
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        ) !== false;
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
            $stats['average_score'] = round($averages->avg_total, 2);
            $stats['by_criteria'] = [
                'courage' => round($averages->avg_courage, 2),
                'innovation' => round($averages->avg_innovation, 2),
                'implementation' => round($averages->avg_implementation, 2),
                'relevance' => round($averages->avg_relevance, 2),
                'visibility' => round($averages->avg_visibility, 2)
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
     * Calculate total score
     *
     * @param array $data Evaluation data with individual scores
     * @return float
     */
    private function calculate_total_score($data) {
        $scores = [
            'courage_score' => isset($data['courage_score']) ? intval($data['courage_score']) : 0,
            'innovation_score' => isset($data['innovation_score']) ? intval($data['innovation_score']) : 0,
            'implementation_score' => isset($data['implementation_score']) ? intval($data['implementation_score']) : 0,
            'relevance_score' => isset($data['relevance_score']) ? intval($data['relevance_score']) : 0,
            'visibility_score' => isset($data['visibility_score']) ? intval($data['visibility_score']) : 0
        ];
        
        // Get weights from options
        $weights = get_option('mt_criteria_weights', [
            'courage' => 1,
            'innovation' => 1,
            'implementation' => 1,
            'relevance' => 1,
            'visibility' => 1
        ]);
        
        $weighted_sum = 0;
        $weight_total = 0;
        
        foreach ($scores as $key => $score) {
            $criteria = str_replace('_score', '', $key);
            $weight = isset($weights[$criteria]) ? floatval($weights[$criteria]) : 1;
            $weighted_sum += $score * $weight;
            $weight_total += $weight;
        }
        
        return $weight_total > 0 ? round($weighted_sum / $weight_total, 2) : 0;
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
} 