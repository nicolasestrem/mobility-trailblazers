<?php
/**
 * Assignment Service
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Assignment_Service
 *
 * Handles business logic for jury assignments
 */
class MT_Assignment_Service implements MT_Service_Interface {
    
    /**
     * Repository instance
     *
     * @var MT_Assignment_Repository
     */
    private $repository;
    
    /**
     * Validation errors
     *
     * @var array
     */
    private $errors = [];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->repository = new MT_Assignment_Repository();
    }
    
    /**
     * Process assignment request
     *
     * @param array $data Assignment data
     * @return mixed
     */
    public function process($data) {
        $this->errors = [];
        
        // Determine assignment type
        if (isset($data['assignment_type']) && $data['assignment_type'] === 'auto') {
            return $this->process_auto_assignment($data);
        } else {
            return $this->process_manual_assignment($data);
        }
    }
    
    /**
     * Process manual assignment
     *
     * @param array $data Assignment data
     * @return bool
     */
    private function process_manual_assignment($data) {
        if (!$this->validate($data)) {
            return false;
        }
        
        // Create single assignment
        $assignment_data = [
            'jury_member_id' => intval($data['jury_member_id']),
            'candidate_id' => intval($data['candidate_id'])
        ];
        
        // Check if assignment already exists
        if ($this->repository->exists($assignment_data['jury_member_id'], $assignment_data['candidate_id'])) {
            $this->errors[] = __('This assignment already exists.', 'mobility-trailblazers');
            return false;
        }
        
        $result = $this->repository->create($assignment_data);
        
        if ($result) {
            do_action('mt_assignment_created', $result, $assignment_data);
            return true;
        }
        
        $this->errors[] = __('Failed to create assignment.', 'mobility-trailblazers');
        return false;
    }
    
    /**
     * Process auto assignment
     *
     * @param array $data Auto-assignment parameters
     * @return bool
     */
    private function process_auto_assignment($data) {
        if (!$this->validate_auto_assignment($data)) {
            return false;
        }
        
        // Get all jury members
        $jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ]);
        
        if (empty($jury_members)) {
            $this->errors[] = __('No jury members found.', 'mobility-trailblazers');
            return false;
        }
        
        // Get all candidates
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'fields' => 'ids'
        ]);
        
        if (empty($candidates)) {
            $this->errors[] = __('No candidates found.', 'mobility-trailblazers');
            return false;
        }
        
        // Clear existing assignments if requested
        if (!empty($data['clear_existing'])) {
            $this->repository->clear_all();
        }
        
        // Distribute candidates
        $candidates_per_jury = intval($data['candidates_per_jury']);
        $distribution_type = $data['distribution_type'] ?? 'balanced';
        
        if ($distribution_type === 'random') {
            $assignments = $this->distribute_randomly($jury_members, $candidates, $candidates_per_jury);
        } else {
            $assignments = $this->distribute_balanced($jury_members, $candidates, $candidates_per_jury);
        }
        
        // Bulk create assignments
        $created = $this->repository->bulk_create($assignments);
        
        if ($created > 0) {
            do_action('mt_auto_assignment_completed', $created, $data);
            return true;
        }
        
        $this->errors[] = __('No assignments were created.', 'mobility-trailblazers');
        return false;
    }
    
    /**
     * Remove assignment
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    public function remove_assignment($jury_member_id, $candidate_id) {
        $this->errors = [];
        
        // Find the assignment
        $assignments = $this->repository->find_all([
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id,
            'limit' => 1
        ]);
        
        if (empty($assignments)) {
            $this->errors[] = __('Assignment not found.', 'mobility-trailblazers');
            return false;
        }
        
        $result = $this->repository->delete($assignments[0]->id);
        
        if ($result) {
            do_action('mt_assignment_removed', $jury_member_id, $candidate_id);
            return true;
        }
        
        $this->errors[] = __('Failed to remove assignment.', 'mobility-trailblazers');
        return false;
    }
    
    /**
     * Validate manual assignment data
     *
     * @param array $data Input data
     * @return bool
     */
    public function validate($data) {
        $valid = true;
        
        if (empty($data['jury_member_id'])) {
            $this->errors[] = __('Jury member is required.', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (empty($data['candidate_id'])) {
            $this->errors[] = __('Candidate is required.', 'mobility-trailblazers');
            $valid = false;
        }
        
        return $valid;
    }
    
    /**
     * Validate auto assignment data
     *
     * @param array $data Input data
     * @return bool
     */
    private function validate_auto_assignment($data) {
        $valid = true;
        
        if (!isset($data['candidates_per_jury']) || intval($data['candidates_per_jury']) < 1) {
            $this->errors[] = __('Candidates per jury must be at least 1.', 'mobility-trailblazers');
            $valid = false;
        }
        
        return $valid;
    }
    
    /**
     * Get validation errors
     *
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }
    
    /**
     * Distribute candidates randomly
     *
     * @param array $jury_members Array of jury member IDs
     * @param array $candidates Array of candidate IDs
     * @param int $per_jury Number of candidates per jury member
     * @return array
     */
    private function distribute_randomly($jury_members, $candidates, $per_jury) {
        $assignments = [];
        
        foreach ($jury_members as $jury_id) {
            // Shuffle candidates for random selection
            $shuffled = $candidates;
            shuffle($shuffled);
            
            // Assign the specified number of candidates
            $assigned = array_slice($shuffled, 0, $per_jury);
            
            foreach ($assigned as $candidate_id) {
                $assignments[] = [
                    'jury_member_id' => $jury_id,
                    'candidate_id' => $candidate_id
                ];
            }
        }
        
        return $assignments;
    }
    
    /**
     * Distribute candidates in a balanced way
     *
     * @param array $jury_members Array of jury member IDs
     * @param array $candidates Array of candidate IDs
     * @param int $per_jury Number of candidates per jury member
     * @return array
     */
    private function distribute_balanced($jury_members, $candidates, $per_jury) {
        $assignments = [];
        $candidate_assignment_count = [];
        
        // Initialize assignment count for each candidate
        foreach ($candidates as $candidate_id) {
            $candidate_assignment_count[$candidate_id] = 0;
        }
        
        // Assign candidates to each jury member
        foreach ($jury_members as $jury_id) {
            // Sort candidates by assignment count (ascending)
            asort($candidate_assignment_count);
            
            // Get the least assigned candidates
            $available_candidates = array_keys($candidate_assignment_count);
            $to_assign = array_slice($available_candidates, 0, $per_jury);
            
            foreach ($to_assign as $candidate_id) {
                $assignments[] = [
                    'jury_member_id' => $jury_id,
                    'candidate_id' => $candidate_id
                ];
                
                // Increment assignment count
                $candidate_assignment_count[$candidate_id]++;
            }
        }
        
        return $assignments;
    }
    
    /**
     * Get assignment summary
     *
     * @return array
     */
    public function get_summary() {
        $stats = $this->repository->get_statistics();
        
        // Add additional summary data
        $total_jury = wp_count_posts('mt_jury_member')->publish;
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        
        $stats['total_jury_members'] = $total_jury;
        $stats['total_candidates'] = $total_candidates;
        $stats['unassigned_jury'] = $total_jury - $stats['assigned_jury_members'];
        $stats['unassigned_candidates'] = $total_candidates - $stats['assigned_candidates'];
        
        return $stats;
    }
    
    /**
     * Auto assign candidates to jury members
     *
     * @param string $method Assignment method (balanced/random)
     * @param int $candidates_per_jury Number of candidates per jury member
     * @return bool
     */
    public function auto_assign($method, $candidates_per_jury) {
        $data = [
            'assignment_type' => 'auto',
            'distribution_type' => $method,
            'candidates_per_jury' => $candidates_per_jury,
            'clear_existing' => true
        ];
        
        return $this->process_auto_assignment($data);
    }
} 