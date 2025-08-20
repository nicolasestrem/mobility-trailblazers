<?php
/**
 * Evaluation Repository Interface
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Interfaces;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface MT_Evaluation_Repository_Interface
 *
 * Contract for evaluation repository implementations
 */
interface MT_Evaluation_Repository_Interface extends MT_Repository_Interface {
    
    /**
     * Check if evaluation exists
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public function exists($jury_member_id, $candidate_id);
    
    /**
     * Get evaluations by jury member
     *
     * @param int $jury_member_id Jury member ID
     * @return array
     */
    public function get_by_jury_member($jury_member_id);
    
    /**
     * Get evaluations by candidate
     *
     * @param int $candidate_id Candidate ID
     * @return array
     */
    public function get_by_candidate($candidate_id);
    
    /**
     * Get average score for candidate
     *
     * @param int $candidate_id Candidate ID
     * @return float
     */
    public function get_average_score_for_candidate($candidate_id);
    
    /**
     * Find evaluation by jury member and candidate
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return object|null
     */
    public function find_by_jury_and_candidate($jury_member_id, $candidate_id);
    
    /**
     * Save evaluation
     *
     * @param array $data Evaluation data
     * @return int|false Evaluation ID or false on failure
     */
    public function save($data);
    
    /**
     * Get evaluation statistics
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_statistics($args = []);
    
    /**
     * Get top candidates
     *
     * @param int $limit Number of candidates to retrieve
     * @param string $category Category filter
     * @return array
     */
    public function get_top_candidates($limit = 10, $category = '');
    
    /**
     * Get ranked candidates for jury member
     *
     * @param int $jury_member_id Jury member ID
     * @param int $limit Number of candidates
     * @return array
     */
    public function get_ranked_candidates_for_jury($jury_member_id, $limit = 10);
}