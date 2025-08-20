<?php
/**
 * Assignment Repository Interface
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
 * Interface MT_Assignment_Repository_Interface
 *
 * Contract for assignment repository implementations
 */
interface MT_Assignment_Repository_Interface extends MT_Repository_Interface {
    
    /**
     * Check if assignment exists
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public function exists($jury_member_id, $candidate_id);
    
    /**
     * Get assignments by jury member
     *
     * @param int $jury_member_id Jury member ID
     * @return array
     */
    public function get_by_jury_member($jury_member_id);
    
    /**
     * Get assignments by candidate
     *
     * @param int $candidate_id Candidate ID
     * @return array
     */
    public function get_by_candidate($candidate_id);
    
    /**
     * Get assignment by jury member and candidate
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return object|null
     */
    public function get_by_jury_and_candidate($jury_member_id, $candidate_id);
    
    /**
     * Bulk create assignments
     *
     * @param array $assignments Array of assignment data
     * @return array Results with success/failure counts
     */
    public function bulk_create($assignments);
    
    /**
     * Clear all assignments
     *
     * @param bool $cascade_evaluations Whether to delete related evaluations
     * @return bool Success status
     */
    public function clear_all($cascade_evaluations = false);
    
    /**
     * Get assignment statistics
     *
     * @return array Statistics data
     */
    public function get_statistics();
    
    /**
     * Get unassigned candidates
     *
     * @return array List of unassigned candidates
     */
    public function get_unassigned_candidates();
    
    /**
     * Auto-distribute assignments
     *
     * @param array $options Distribution options
     * @return array Distribution results
     */
    public function auto_distribute($options = []);
    
    /**
     * Rebalance assignments
     *
     * @return array Rebalancing results
     */
    public function rebalance_assignments();
}