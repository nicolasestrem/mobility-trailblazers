<?php
/**
 * Voting Service
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Voting_Repository;

class MT_Voting_Service implements MT_Service_Interface {
    
    private $repository;
    private $errors = array();
    
    public function __construct() {
        $this->repository = new MT_Voting_Repository();
    }
    
    /**
     * Process vote submission
     */
    public function process($data) {
        $this->errors = array();
        
        if (!$this->validate($data)) {
            return false;
        }
        
        // Check if already voted
        if ($this->repository->has_voted($data['voter_email'], $data['candidate_id'])) {
            $this->errors[] = __('You have already voted for this candidate', 'mobility-trailblazers');
            return false;
        }
        
        // Create vote
        $vote_data = array(
            'candidate_id' => $data['candidate_id'],
            'voter_email' => sanitize_email($data['voter_email']),
            'voter_name' => sanitize_text_field($data['voter_name']),
            'vote_time' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        $result = $this->repository->create($vote_data);
        
        if ($result) {
            do_action('mt_vote_submitted', $data['candidate_id'], $data['voter_email']);
        }
        
        return $result;
    }
    
    /**
     * Validate voting data
     */
    public function validate($data) {
        $valid = true;
        
        if (empty($data['candidate_id'])) {
            $this->errors[] = __('Candidate is required', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (empty($data['voter_email']) || !is_email($data['voter_email'])) {
            $this->errors[] = __('Valid email is required', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (empty($data['voter_name'])) {
            $this->errors[] = __('Name is required', 'mobility-trailblazers');
            $valid = false;
        }
        
        return $valid;
    }
    
    /**
     * Get validation errors
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Calculate voting results
     */
    public function calculate_results($category_id = null) {
        return $this->repository->get_vote_counts($category_id);
    }
    
    /**
     * Reset all votes
     */
    public function reset_votes() {
        // Create backup first
        $backup_id = $this->repository->create_backup();
        
        if (!$backup_id) {
            $this->errors[] = __('Failed to create backup before reset', 'mobility-trailblazers');
            return false;
        }
        
        // Clear votes
        $result = $this->repository->clear_all();
        
        if ($result) {
            do_action('mt_votes_reset', $backup_id);
        }
        
        return $result;
    }
}