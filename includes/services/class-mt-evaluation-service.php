<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Evaluation Service
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Interfaces\MT_Service_Interface;
use MobilityTrailblazers\Repositories\MT_Evaluation_Repository;
use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;
use MobilityTrailblazers\Core\MT_Audit_Logger;

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
        
        // Debug: Log the incoming data
        error_log('MT Evaluation Service - Incoming data: ' . print_r($data, true));
        
        // Validate input
        if (!$this->validate($data)) {
            error_log('MT Evaluation Service - Validation failed: ' . print_r($this->errors, true));
            return false;
        }
        
        // Check if user has permission
        if (!$this->check_permission($data['jury_member_id'], $data['candidate_id'])) {
            $this->errors[] = __('You do not have permission to evaluate this candidate.', 'mobility-trailblazers');
            error_log('MT Evaluation Service - Permission check failed for jury_member_id: ' . $data['jury_member_id'] . ', candidate_id: ' . $data['candidate_id']);
            return false;
        }
        
        // Prepare data
        $evaluation_data = $this->prepare_data($data);
        error_log('MT Evaluation Service - Prepared data: ' . print_r($evaluation_data, true));
        
        // Check if evaluation exists
        $existing = $this->get_existing_evaluation($data['jury_member_id'], $data['candidate_id']);
        
        if ($existing) {
            // Update existing evaluation
            error_log('MT Evaluation Service - Updating existing evaluation ID: ' . $existing->id);
            $result = $this->repository->update($existing->id, $evaluation_data);
            
            if ($result) {
                // Trigger action
                do_action('mt_evaluation_updated', $existing->id, $evaluation_data);
                
                // Audit log
                $action = 'evaluation_submitted';
                MT_Audit_Logger::log($action, 'evaluation', $existing->id, $evaluation_data);
                
                error_log('MT Evaluation Service - Update successful, returning ID: ' . $existing->id);
                return $existing->id;
            } else {
                error_log('MT Evaluation Service - Update failed');
                $this->errors[] = __('Failed to update existing evaluation.', 'mobility-trailblazers');
                return false;
            }
        } else {
            // Create new evaluation
            error_log('MT Evaluation Service - Creating new evaluation');
            $result = $this->repository->create($evaluation_data);
            
            if ($result) {
                // Trigger action
                do_action('mt_evaluation_submitted', $result, $evaluation_data);
                
                // Audit log
                $action = 'evaluation_submitted';
                MT_Audit_Logger::log($action, 'evaluation', $result, $evaluation_data);
                
                error_log('MT Evaluation Service - Create successful, returning ID: ' . $result);
                return $result;
            } else {
                error_log('MT Evaluation Service - Create failed');
                $this->errors[] = __('Failed to create new evaluation.', 'mobility-trailblazers');
                return false;
            }
        }
        
        $this->errors[] = __('Failed to save evaluation. Please try again.', 'mobility-trailblazers');
        error_log('MT Evaluation Service - Process failed, errors: ' . print_r($this->errors, true));
        return false;
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
     * Save or update evaluation
     *
     * @param array $data Evaluation data
     * @return int|WP_Error Evaluation ID or error
     */
    public function save_evaluation($data) {
        // Validate data
        if (!$this->validate($data)) {
            return new \WP_Error('validation_failed', implode(', ', $this->get_errors()));
        }
        
        // Ensure all score fields are present with defaults
        $score_fields = [
            'courage_score' => 0,
            'innovation_score' => 0,
            'implementation_score' => 0,
            'relevance_score' => 0,
            'visibility_score' => 0
        ];
        
        // Merge with defaults
        foreach ($score_fields as $field => $default) {
            if (!isset($data[$field])) {
                $data[$field] = $default;
            } else {
                $data[$field] = floatval($data[$field]);
            }
        }
        
        // Calculate total score (average of all scores)
        $total = 0;
        $count = 0;
        foreach ($score_fields as $field => $default) {
            if (isset($data[$field]) && $data[$field] > 0) {
                $total += floatval($data[$field]);
                $count++;
            }
        }
        
        $data['total_score'] = $count > 0 ? ($total / $count) : 0;
        
        // Set timestamps using correct field names
        if (!isset($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = current_time('mysql');
        }
        
        // Convert notes to comments if present
        if (isset($data['notes']) && !isset($data['comments'])) {
            $data['comments'] = $data['notes'];
            unset($data['notes']);
        }
        
        // Save to repository
        try {
            $result = $this->repository->save($data);
            
            if ($result) {
                do_action('mt_evaluation_submitted', $result, $data);
                
                // Audit log
                $action = 'evaluation_submitted';
                MT_Audit_Logger::log($action, 'evaluation', $result, $data);
                
                return $result;
            }
            
            return new \WP_Error('save_failed', __('Failed to save evaluation', 'mobility-trailblazers'));
            
        } catch (\Exception $e) {
            return new \WP_Error('save_error', $e->getMessage());
        }
    }
    
    /**
     * Validate evaluation data
     *
     * @param array $data Input data
     * @return bool
     */
    public function validate($data) {
        $this->errors = []; // Clear previous errors
        $valid = true;

        try {
            // Required fields validation
            if (empty($data['jury_member_id'])) {
                $this->errors[] = __('Jury member ID is required.', 'mobility-trailblazers');
                $valid = false;
            } elseif (!is_numeric($data['jury_member_id']) || $data['jury_member_id'] <= 0) {
                $this->errors[] = __('Invalid jury member ID.', 'mobility-trailblazers');
                $valid = false;
            }

            if (empty($data['candidate_id'])) {
                $this->errors[] = __('Candidate ID is required.', 'mobility-trailblazers');
                $valid = false;
            } elseif (!is_numeric($data['candidate_id']) || $data['candidate_id'] <= 0) {
                $this->errors[] = __('Invalid candidate ID.', 'mobility-trailblazers');
                $valid = false;
            }

            // Validate that jury member and candidate exist
            if (!empty($data['jury_member_id']) && !empty($data['candidate_id'])) {
                if (!$this->validate_jury_candidate_relationship($data['jury_member_id'], $data['candidate_id'])) {
                    $this->errors[] = __('Invalid jury member and candidate combination.', 'mobility-trailblazers');
                    $valid = false;
                }
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
                $score = floatval($data[$field]); // Convert to float for validation
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
        
            if (!$valid) {
                MT_Logger::warning('Evaluation validation failed', [
                    'jury_member_id' => $data['jury_member_id'] ?? null,
                    'candidate_id' => $data['candidate_id'] ?? null,
                    'errors' => $this->errors
                ]);
            }

            return apply_filters('mt_evaluation_validate', $valid, $data, $this);

        } catch (\Exception $e) {
            MT_Logger::critical('Exception during evaluation validation', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data_keys' => array_keys($data)
            ]);
            $this->errors[] = __('Validation error occurred. Please try again.', 'mobility-trailblazers');
            return false;
        }
    }

    /**
     * Validate jury member and candidate relationship
     *
     * @param int $jury_member_id Jury member ID
     * @param int $candidate_id Candidate ID
     * @return bool
     */
    private function validate_jury_candidate_relationship($jury_member_id, $candidate_id) {
        // Check if jury member exists
        $jury_member = get_post($jury_member_id);
        if (!$jury_member || $jury_member->post_type !== 'mt_jury_member' || $jury_member->post_status !== 'publish') {
            return false;
        }

        // Check if candidate exists - only published candidates
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== 'mt_candidate' || $candidate->post_status !== 'publish') {
            return false;
        }

        // Check if assignment exists (optional - depends on business rules)
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        $assignment = $assignment_repo->get_by_jury_and_candidate($jury_member_id, $candidate_id);

        return !empty($assignment);
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
                'description' => __('Demonstrates bold vision and willingness to take risks in advancing mobility transformation', 'mobility-trailblazers'),
                'icon' => 'dashicons-superhero',
                'color' => '#FF6B6B'
            ],
            'innovation' => [
                'key' => 'innovation_score',
                'label' => __('Innovationsgrad', 'mobility-trailblazers'),
                'description' => __('Shows creative problem-solving and introduces novel approaches to mobility challenges', 'mobility-trailblazers'),
                'icon' => 'dashicons-lightbulb',
                'color' => '#4ECDC4'
            ],
            'implementation' => [
                'key' => 'implementation_score',
                'label' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
                'description' => __('Successfully executes ideas with measurable impact on sustainable mobility', 'mobility-trailblazers'),
                'icon' => 'dashicons-hammer',
                'color' => '#45B7D1'
            ],
            'relevance' => [
                'key' => 'relevance_score',
                'label' => __('Relevanz für Mobilitätswende', 'mobility-trailblazers'),
                'description' => __('Addresses critical aspects of transportation transformation and future mobility needs', 'mobility-trailblazers'),
                'icon' => 'dashicons-location-alt',
                'color' => '#96CEB4'
            ],
            'visibility' => [
                'key' => 'visibility_score',
                'label' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'),
                'description' => __('Serves as an inspiring example and actively promotes sustainable mobility solutions', 'mobility-trailblazers'),
                'icon' => 'dashicons-visibility',
                'color' => '#FFEAA7'
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
                    // Handle any other statuses as pending
                    $progress['pending']++;
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
     * Get assignment progress for display
     *
     * @param int $jury_member_id Jury member ID
     * @return array Progress data
     */
    public function get_assignment_progress($jury_member_id) {
        $assignment_repo = new MT_Assignment_Repository();
        $evaluation_repo = new MT_Evaluation_Repository();
        
        // Get all assignments
        $assignments = $assignment_repo->get_by_jury_member($jury_member_id);
        $total_assignments = count($assignments);
        
        if ($total_assignments === 0) {
            return [
                'total' => 0,
                'completed' => 0,
                'percentage' => 0
            ];
        }
        
        // Count completed evaluations
        $completed = 0;
        foreach ($assignments as $assignment) {
            $evaluations = $evaluation_repo->find_all([
                'jury_member_id' => $jury_member_id,
                'candidate_id' => $assignment->candidate_id,
                'limit' => 1
            ]);
            
            if (!empty($evaluations) && $evaluations[0]->status === 'completed') {
                $completed++;
            }
        }
        
        return [
            'total' => $total_assignments,
            'completed' => $completed,
            'percentage' => round(($completed / $total_assignments) * 100)
        ];
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
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'completed'
        ];
        
        // Add scores as floats (decimal(3,1) in database)
        $score_fields = [
            'courage_score',
            'innovation_score',
            'implementation_score',
            'relevance_score',
            'visibility_score'
        ];
        
        foreach ($score_fields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $prepared[$field] = floatval($data[$field]); // Convert to float for decimal storage
            }
        }
        
        // Add comments if provided
        if (!empty($data['comments'])) {
            $prepared['comments'] = sanitize_textarea_field($data['comments']);
        }
        
        return apply_filters('mt_evaluation_prepare_data', $prepared, $data);
    }
} 
