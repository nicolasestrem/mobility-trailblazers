<?php
/**
 * Evaluation Service
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Evaluation_Repository;

class MT_Evaluation_Service implements MT_Service_Interface {
    
    private $repository;
    private $errors = array();
    
    public function __construct() {
        $this->repository = new MT_Evaluation_Repository();
    }
    
    /**
     * Process evaluation submission
     */
    public function process($data) {
        // Clear previous errors
        $this->errors = array();
        
        // Validate data
        if (!$this->validate($data)) {
            return false;
        }
        
        // Calculate total score
        $total_score = $this->calculate_total_score($data['scores']);
        
        // Prepare evaluation data
        $evaluation_data = array(
            'jury_member_id' => $data['jury_member_id'],
            'candidate_id' => $data['candidate_id'],
            'scores' => json_encode($data['scores']),
            'total_score' => $total_score,
            'comments' => sanitize_textarea_field($data['comments'] ?? ''),
            'status' => 'submitted'
        );
        
        // Check if evaluation already exists
        if ($this->repository->exists($data['jury_member_id'], $data['candidate_id'])) {
            // Update existing evaluation
            $existing = $this->get_existing_evaluation($data['jury_member_id'], $data['candidate_id']);
            $result = $this->repository->update($existing->id, $evaluation_data);
        } else {
            // Create new evaluation
            $result = $this->repository->create($evaluation_data);
        }
        
        if ($result) {
            // Trigger action for other plugins
            do_action('mt_evaluation_submitted', $data['candidate_id'], $data['jury_member_id'], $data['scores']);
        }
        
        return $result;
    }
    
    /**
     * Validate evaluation data
     */
    public function validate($data) {
        $valid = true;
        
        // Check required fields
        if (empty($data['jury_member_id'])) {
            $this->errors[] = __('Jury member ID is required', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (empty($data['candidate_id'])) {
            $this->errors[] = __('Candidate ID is required', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (empty($data['scores']) || !is_array($data['scores'])) {
            $this->errors[] = __('Evaluation scores are required', 'mobility-trailblazers');
            $valid = false;
        }
        
        // Validate each score
        $criteria = mt_get_evaluation_criteria();
        foreach ($criteria as $criterion_id => $criterion) {
            if (!isset($data['scores'][$criterion_id])) {
                $this->errors[] = sprintf(__('Score for %s is required', 'mobility-trailblazers'), $criterion['name']);
                $valid = false;
            } elseif ($data['scores'][$criterion_id] < 1 || $data['scores'][$criterion_id] > 10) {
                $this->errors[] = sprintf(__('Score for %s must be between 1 and 10', 'mobility-trailblazers'), $criterion['name']);
                $valid = false;
            }
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
     * Calculate total score from individual scores
     */
    private function calculate_total_score($scores) {
        $criteria = mt_get_evaluation_criteria();
        $total = 0;
        $total_weight = 0;
        
        foreach ($criteria as $criterion_id => $criterion) {
            if (isset($scores[$criterion_id])) {
                $weight = $criterion['weight'] ?? 1;
                $total += $scores[$criterion_id] * $weight;
                $total_weight += $weight;
            }
        }
        
        return $total_weight > 0 ? round($total / $total_weight, 2) : 0;
    }
    
    /**
     * Get existing evaluation
     */
    private function get_existing_evaluation($jury_member_id, $candidate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_evaluations';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE jury_member_id = %d AND candidate_id = %d LIMIT 1",
            $jury_member_id,
            $candidate_id
        ));
    }
    
    /**
     * Save draft evaluation
     */
    public function save_draft($data) {
        $this->errors = array();
        
        // Less strict validation for drafts
        if (empty($data['jury_member_id']) || empty($data['candidate_id'])) {
            $this->errors[] = __('Missing required identifiers', 'mobility-trailblazers');
            return false;
        }
        
        $draft_data = array(
            'jury_member_id' => $data['jury_member_id'],
            'candidate_id' => $data['candidate_id'],
            'scores' => json_encode($data['scores'] ?? array()),
            'comments' => sanitize_textarea_field($data['comments'] ?? ''),
            'status' => 'draft'
        );
        
        if ($this->repository->exists($data['jury_member_id'], $data['candidate_id'])) {
            $existing = $this->get_existing_evaluation($data['jury_member_id'], $data['candidate_id']);
            return $this->repository->update($existing->id, $draft_data);
        }
        
        return $this->repository->create($draft_data);
    }
}