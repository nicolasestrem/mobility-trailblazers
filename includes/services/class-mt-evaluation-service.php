<?php
/**
 * Evaluation Service
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Evaluation_Repository;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Evaluation_Service
 *
 * Handles business logic for evaluations
 */
class MT_Evaluation_Service implements MT_Service_Interface {
    
    /**
     * Repository instance
     *
     * @var MT_Evaluation_Repository
     */
    private $repository;
    
    /**
     * Assignment repository instance
     *
     * @var MT_Assignment_Repository
     */
    private $assignment_repository;
    
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
        $this->repository = new MT_Evaluation_Repository();
        $this->assignment_repository = new MT_Assignment_Repository();
    }
    
    /**
     * Process evaluation submission
     *
     * @param array $data Evaluation data
     * @return int|false Evaluation ID on success, false on failure
     */
    public function process($data) {
        $this->errors = [];
        
        // Validate input
        if (!$this->validate($data)) {
            return false;
        }
        
        // Check if user has permission
        if (!$this->check_permission($data['jury_member_id'], $data['candidate_id'])) {
            $this->errors[] = __('You do not have permission to evaluate this candidate.', 'mobility-trailblazers');
            return false;
        }
        
        // Prepare data
        $evaluation_data = $this->prepare_data($data);
        
        // Check if evaluation exists
        $existing = $this->get_existing_evaluation($data['jury_member_id'], $data['candidate_id']);
        
        if ($existing) {
            // Update existing evaluation
            $result = $this->repository->update($existing->id, $evaluation_data);
            
            if ($result) {
                // Trigger action
                do_action('mt_evaluation_updated', $existing->id, $evaluation_data);
                return $existing->id;
            }
        } else {
            // Create new evaluation
            $result = $this->repository->create($evaluation_data);
            
            if ($result) {
                // Trigger action
                do_action('mt_evaluation_submitted', $result, $evaluation_data);
                return $result;
            }
        }
        
        $this->errors[] = __('Failed to save evaluation. Please try again.', 'mobility-trailblazers');
        return false;
    }
    
    /**
     * Save evaluation as draft
     *
     * @param array $data Evaluation data
     * @return int|false Evaluation ID on success, false on failure
     */
    public function save_draft($data) {
        $data['status'] = 'draft';
        return $this->process($data);
    }
    
    /**
     * Submit final evaluation
     *
     * @param array $data Evaluation data
     * @return int|false Evaluation ID on success, false on failure
     */
    public function submit_final($data) {
        $data['status'] = 'completed';
        return $this->process($data);
    }
    
    /**
     * Validate evaluation data
     *
     * @param array $data Input data
     * @return bool
     */
    public function validate($data) {
        $valid = true;
        
        // Required fields
        if (empty($data['jury_member_id'])) {
            $this->errors[] = __('Jury member ID is required.', 'mobility-trailblazers');
            $valid = false;
        }
        
        if (empty($data['candidate_id'])) {
            $this->errors[] = __('Candidate ID is required.', 'mobility-trailblazers');
            $valid = false;
        }
        
        // Validate scores
        $score_fields = [
            'courage_score' => __('Courage & Pioneer Spirit', 'mobility-trailblazers'),
            'innovation_score' => __('Innovation Degree', 'mobility-trailblazers'),
            'implementation_score' => __('Implementation & Impact', 'mobility-trailblazers'),
            'relevance_score' => __('Mobility Transformation Relevance', 'mobility-trailblazers'),
            'visibility_score' => __('Role Model & Visibility', 'mobility-trailblazers')
        ];
        
        foreach ($score_fields as $field => $label) {
            if (isset($data[$field])) {
                $score = intval($data[$field]);
                if ($score < 0 || $score > 10) {
                    $this->errors[] = sprintf(
                        __('%s score must be between 0 and 10.', 'mobility-trailblazers'),
                        $label
                    );
                    $valid = false;
                }
            }
        }
        
        // For final submission, all scores are required
        if (isset($data['status']) && $data['status'] === 'completed') {
            foreach ($score_fields as $field => $label) {
                if (!isset($data[$field]) || $data[$field] === '') {
                    $this->errors[] = sprintf(
                        __('%s score is required for final submission.', 'mobility-trailblazers'),
                        $label
                    );
                    $valid = false;
                }
            }
        }
        
        return apply_filters('mt_evaluation_validate', $valid, $data, $this);
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
     * Get evaluation criteria
     *
     * @return array
     */
    public function get_criteria() {
        $criteria = [
            'courage' => [
                'key' => 'courage_score',
                'label' => __('Mut & Pioniergeist', 'mobility-trailblazers'),
                'description' => __('Courage & Pioneer Spirit', 'mobility-trailblazers'),
                'icon' => 'dashicons-superhero'
            ],
            'innovation' => [
                'key' => 'innovation_score',
                'label' => __('Innovationsgrad', 'mobility-trailblazers'),
                'description' => __('Innovation Degree', 'mobility-trailblazers'),
                'icon' => 'dashicons-lightbulb'
            ],
            'implementation' => [
                'key' => 'implementation_score',
                'label' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
                'description' => __('Implementation & Impact', 'mobility-trailblazers'),
                'icon' => 'dashicons-hammer'
            ],
            'relevance' => [
                'key' => 'relevance_score',
                'label' => __('Relevanz für Mobilitätswende', 'mobility-trailblazers'),
                'description' => __('Mobility Transformation Relevance', 'mobility-trailblazers'),
                'icon' => 'dashicons-location-alt'
            ],
            'visibility' => [
                'key' => 'visibility_score',
                'label' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'),
                'description' => __('Role Model & Visibility', 'mobility-trailblazers'),
                'icon' => 'dashicons-visibility'
            ]
        ];
        
        return apply_filters('mt_evaluation_criteria', $criteria);
    }
    
    /**
     * Get jury member's evaluation progress
     *
     * @param int $jury_member_id Jury member ID
     * @return array
     */
    public function get_jury_progress($jury_member_id) {
        // Get all assignments
        $assignments = $this->assignment_repository->get_by_jury_member($jury_member_id);
        
        // Get all evaluations
        $evaluations = $this->repository->get_by_jury_member($jury_member_id);
        
        // Create evaluation map
        $evaluation_map = [];
        foreach ($evaluations as $eval) {
            $evaluation_map[$eval->candidate_id] = $eval;
        }
        
        // Build progress data
        $progress = [
            'total' => count($assignments),
            'completed' => 0,
            'drafts' => 0,
            'pending' => 0,
            'candidates' => []
        ];
        
        foreach ($assignments as $assignment) {
            $candidate_data = [
                'id' => $assignment->candidate_id,
                'name' => $assignment->candidate_name,
                'status' => 'pending',
                'evaluation_id' => null
            ];
            
            if (isset($evaluation_map[$assignment->candidate_id])) {
                $eval = $evaluation_map[$assignment->candidate_id];
                $candidate_data['status'] = $eval->status;
                $candidate_data['evaluation_id'] = $eval->id;
                
                if ($eval->status === 'completed') {
                    $progress['completed']++;
                } else {
                    $progress['drafts']++;
                }
            } else {
                $progress['pending']++;
            }
            
            $progress['candidates'][] = $candidate_data;
        }
        
        $progress['completion_rate'] = $progress['total'] > 0 
            ? round(($progress['completed'] / $progress['total']) * 100, 1)
            : 0;
        
        return $progress;
    }
    
    /**
     * Check if jury member has permission to evaluate candidate
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    private function check_permission($jury_member_id, $candidate_id) {
        // Check if assignment exists
        return $this->assignment_repository->exists($jury_member_id, $candidate_id);
    }
    
    /**
     * Get existing evaluation
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return object|null
     */
    private function get_existing_evaluation($jury_member_id, $candidate_id) {
        $evaluations = $this->repository->find_all([
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id,
            'limit' => 1
        ]);
        
        return !empty($evaluations) ? $evaluations[0] : null;
    }
    
    /**
     * Prepare evaluation data
     *
     * @param array $data Raw input data
     * @return array
     */
    private function prepare_data($data) {
        $prepared = [
            'jury_member_id' => intval($data['jury_member_id']),
            'candidate_id' => intval($data['candidate_id']),
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'draft'
        ];
        
        // Add scores
        $score_fields = [
            'courage_score',
            'innovation_score',
            'implementation_score',
            'relevance_score',
            'visibility_score'
        ];
        
        foreach ($score_fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $prepared[$field] = intval($data[$field]);
            }
        }
        
        // Add comments if provided
        if (!empty($data['comments'])) {
            $prepared['comments'] = sanitize_textarea_field($data['comments']);
        }
        
        return apply_filters('mt_evaluation_prepare_data', $prepared, $data);
    }
} 