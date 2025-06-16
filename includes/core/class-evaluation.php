<?php
/**
 * Evaluation Class
 * File: includes/core/class-evaluation.php
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

namespace MobilityTrailblazers\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Evaluation
 * 
 * Handles all evaluation-related functionality
 */
class Evaluation {
    
    /**
     * Database table names
     */
    private $table_scores;
    private $table_votes;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_scores = $wpdb->prefix . 'mt_candidate_scores';
        $this->table_votes = $wpdb->prefix . 'mt_votes';
    }
    
    /**
     * Check if a jury member has evaluated a candidate
     *
     * @param int $user_id User ID
     * @param int $candidate_id Candidate post ID
     * @return bool
     */
    public function has_evaluated($user_id, $candidate_id) {
        global $wpdb;
        
        // Check both by user ID and jury member post ID
        $jury_member_id = $this->get_jury_member_id_for_user($user_id);
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_scores} 
            WHERE candidate_id = %d 
            AND (jury_member_id = %d OR jury_member_id = %d)
            AND is_active = 1",
            $candidate_id,
            $user_id,
            $jury_member_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Get evaluation data for a specific user and candidate
     *
     * @param int $user_id User ID
     * @param int $candidate_id Candidate post ID
     * @return object|null
     */
    public function get_evaluation($user_id, $candidate_id) {
        global $wpdb;
        
        $jury_member_id = $this->get_jury_member_id_for_user($user_id);
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_scores} 
            WHERE candidate_id = %d 
            AND (jury_member_id = %d OR jury_member_id = %d)
            AND is_active = 1
            ORDER BY evaluated_at DESC
            LIMIT 1",
            $candidate_id,
            $user_id,
            $jury_member_id
        ));
    }
    
    /**
     * Save or update an evaluation
     *
     * @param array $data Evaluation data
     * @return int|false Insert ID or false on failure
     */
    public function save_evaluation($data) {
        global $wpdb;
        
        // Required fields
        $required = ['candidate_id', 'jury_member_id', 'innovation_score', 'impact_score', 
                     'implementation_score', 'sustainability_score', 'scalability_score'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        // Calculate total score
        $total_score = $data['innovation_score'] + $data['impact_score'] + 
                      $data['implementation_score'] + $data['sustainability_score'] + 
                      $data['scalability_score'];
        
        // Check if evaluation exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_scores} 
            WHERE candidate_id = %d AND jury_member_id = %d",
            $data['candidate_id'],
            $data['jury_member_id']
        ));
        
        $db_data = [
            'candidate_id' => intval($data['candidate_id']),
            'jury_member_id' => intval($data['jury_member_id']),
            'innovation_score' => intval($data['innovation_score']),
            'impact_score' => intval($data['impact_score']),
            'implementation_score' => intval($data['implementation_score']),
            'sustainability_score' => intval($data['sustainability_score']),
            'scalability_score' => intval($data['scalability_score']),
            'total_score' => $total_score,
            'comments' => sanitize_textarea_field($data['comments'] ?? ''),
            'evaluated_at' => current_time('mysql'),
            'is_active' => 1
        ];
        
        if ($existing) {
            // Update existing
            $result = $wpdb->update(
                $this->table_scores,
                $db_data,
                ['id' => $existing]
            );
            return $result !== false ? $existing : false;
        } else {
            // Insert new
            $result = $wpdb->insert($this->table_scores, $db_data);
            return $result !== false ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Get top candidates by average score
     *
     * @param int $limit Number of results
     * @param string $category Category slug (optional)
     * @param int $min_votes Minimum number of votes required
     * @return array
     */
    public function get_top_candidates_by_score($limit = 10, $category = '', $min_votes = 1) {
        global $wpdb;
        
        $query = "
            SELECT 
                p.ID as candidate_id,
                p.post_title as candidate_name,
                AVG(s.total_score) as avg_score,
                COUNT(s.id) as evaluation_count,
                m1.meta_value as company,
                m2.meta_value as position
            FROM {$wpdb->posts} p
            LEFT JOIN {$this->table_scores} s ON p.ID = s.candidate_id AND s.is_active = 1
            LEFT JOIN {$wpdb->postmeta} m1 ON p.ID = m1.post_id AND m1.meta_key = '_mt_company'
            LEFT JOIN {$wpdb->postmeta} m2 ON p.ID = m2.post_id AND m2.meta_key = '_mt_position'
        ";
        
        if ($category) {
            $query .= "
                LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            ";
        }
        
        $query .= "
            WHERE p.post_type = 'mt_candidate' 
            AND p.post_status = 'publish'
        ";
        
        if ($category) {
            $query .= $wpdb->prepare(" AND t.slug = %s AND tt.taxonomy = 'mt_category'", $category);
        }
        
        $query .= "
            GROUP BY p.ID
            HAVING COUNT(s.id) >= %d
            ORDER BY avg_score DESC
            LIMIT %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($query, $min_votes, $limit));
    }
    
    /**
     * Get public voting results
     *
     * @param int $limit Number of results
     * @param string $category Category slug (optional)
     * @return array
     */
    public function get_public_voting_results($limit = 10, $category = '') {
        global $wpdb;
        
        // For now, return the same as jury results
        // You can implement a separate public voting table later
        return $this->get_top_candidates_by_score($limit, $category, 0);
    }
    
    /**
     * Delete an evaluation (soft delete)
     *
     * @param int $user_id User ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public function delete_evaluation($user_id, $candidate_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_scores,
            ['is_active' => 0, 'deleted_at' => current_time('mysql')],
            [
                'jury_member_id' => $user_id,
                'candidate_id' => $candidate_id
            ]
        ) !== false;
    }
    
    /**
     * Get all evaluations for a candidate
     *
     * @param int $candidate_id Candidate ID
     * @return array
     */
    public function get_candidate_evaluations($candidate_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.*,
                u.display_name as jury_name,
                u.user_email as jury_email
            FROM {$this->table_scores} s
            LEFT JOIN {$wpdb->users} u ON s.jury_member_id = u.ID
            WHERE s.candidate_id = %d AND s.is_active = 1
            ORDER BY s.evaluated_at DESC",
            $candidate_id
        ));
    }
    
    /**
     * Get all evaluations by a jury member
     *
     * @param int $user_id User ID
     * @return array
     */
    public function get_jury_member_evaluations($user_id) {
        global $wpdb;
        
        $jury_member_id = $this->get_jury_member_id_for_user($user_id);
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                s.*,
                p.post_title as candidate_name,
                m1.meta_value as company,
                m2.meta_value as position
            FROM {$this->table_scores} s
            LEFT JOIN {$wpdb->posts} p ON s.candidate_id = p.ID
            LEFT JOIN {$wpdb->postmeta} m1 ON p.ID = m1.post_id AND m1.meta_key = '_mt_company'
            LEFT JOIN {$wpdb->postmeta} m2 ON p.ID = m2.post_id AND m2.meta_key = '_mt_position'
            WHERE (s.jury_member_id = %d OR s.jury_member_id = %d)
            AND s.is_active = 1
            ORDER BY s.evaluated_at DESC",
            $user_id,
            $jury_member_id
        ));
    }
    
    /**
     * Get evaluation statistics
     *
     * @return array
     */
    public function get_evaluation_stats() {
        global $wpdb;
        
        $stats = [];
        
        // Total evaluations
        $stats['total_evaluations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_scores} WHERE is_active = 1"
        );
        
        // Average score
        $stats['average_score'] = $wpdb->get_var(
            "SELECT AVG(total_score) FROM {$this->table_scores} WHERE is_active = 1"
        );
        
        // Evaluations by category
        $stats['by_category'] = $wpdb->get_results("
            SELECT 
                t.name as category,
                COUNT(DISTINCT s.id) as evaluation_count,
                AVG(s.total_score) as avg_score
            FROM {$wpdb->posts} p
            LEFT JOIN {$this->table_scores} s ON p.ID = s.candidate_id AND s.is_active = 1
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            WHERE p.post_type = 'mt_candidate' 
            AND p.post_status = 'publish'
            AND tt.taxonomy = 'mt_category'
            GROUP BY t.term_id
        ");
        
        return $stats;
    }
    
    /**
     * Helper: Get jury member post ID for a user
     *
     * @param int $user_id User ID
     * @return int|null
     */
    private function get_jury_member_id_for_user($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_mt_jury_user_id' AND meta_value = %s",
            $user_id
        ));
    }
}