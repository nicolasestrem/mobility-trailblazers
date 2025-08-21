<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Evaluation AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Services\MT_Evaluation_Service;
use MobilityTrailblazers\Core\MT_Audit_Logger;
use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Core\MT_Plugin;
use MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface;
use MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Evaluation_Ajax
 *
 * Handles AJAX requests for evaluations
 */
class MT_Evaluation_Ajax extends MT_Base_Ajax {
    
    /**
     * Get evaluation repository from container with fallback
     *
     * @return MT_Evaluation_Repository_Interface
     */
    private function get_evaluation_repository() {
        try {
            $container = MT_Plugin::container();
            
            // Debug: Check if container has the binding
            if (defined('WP_DEBUG') && WP_DEBUG) {
                $interface_name = 'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface';
                $has_binding = $container->has($interface_name);
                if (!$has_binding) {
                    error_log("MT Container Debug: Missing binding for {$interface_name}");
                    
                    // Try to trigger service registration again
                    $plugin = MT_Plugin::get_instance();
                    $container = $plugin->get_container();
                    $has_binding_after_retry = $container->has($interface_name);
                    error_log("MT Container Debug: After retry, has binding: " . ($has_binding_after_retry ? 'YES' : 'NO'));
                }
            }
            
            return $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface');
            
        } catch (\Exception $e) {
            // Log the error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MT Container Error in get_evaluation_repository: " . $e->getMessage());
            }
            
            // Fallback to direct instantiation
            if (!class_exists('MobilityTrailblazers\Repositories\MT_Evaluation_Repository')) {
                require_once MT_PLUGIN_DIR . 'includes/repositories/class-mt-evaluation-repository.php';
            }
            
            return new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        }
    }
    
    /**
     * Get assignment repository from container with fallback
     *
     * @return MT_Assignment_Repository_Interface
     */
    private function get_assignment_repository() {
        try {
            $container = MT_Plugin::container();
            return $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface');
            
        } catch (\Exception $e) {
            // Log the error
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MT Container Error in get_assignment_repository: " . $e->getMessage());
            }
            
            // Fallback to direct instantiation
            if (!class_exists('MobilityTrailblazers\Repositories\MT_Assignment_Repository')) {
                require_once MT_PLUGIN_DIR . 'includes/repositories/class-mt-assignment-repository.php';
            }
            
            return new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        }
    }
    
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    public function init() {
        // Logged in users
        add_action('wp_ajax_mt_submit_evaluation', [$this, 'submit_evaluation']);
        add_action('wp_ajax_mt_get_evaluation', [$this, 'get_evaluation']);
        add_action('wp_ajax_mt_get_candidate_details', [$this, 'get_candidate_details']);
        add_action('wp_ajax_mt_get_jury_progress', [$this, 'get_jury_progress']);
        add_action('wp_ajax_mt_get_jury_rankings', [$this, 'get_jury_rankings']);
        add_action('wp_ajax_mt_save_inline_evaluation', [$this, 'save_inline_evaluation']);
        
        // Test AJAX action to handle test calls gracefully
        add_action('wp_ajax_mt_test_ajax', [$this, 'test_ajax']);
        
        // Debug AJAX action to check user capabilities
        add_action('wp_ajax_mt_debug_user', [$this, 'debug_user']);
        
        // Bulk operations
        add_action('wp_ajax_mt_bulk_evaluation_action', [$this, 'bulk_evaluation_action']);
        
        // View details modal
        add_action('wp_ajax_mt_get_evaluation_details', [$this, 'get_evaluation_details']);
        
        // Delete single evaluation
        add_action('wp_ajax_mt_delete_evaluation', [$this, 'delete_evaluation']);
    }
    
    /**
     * Submit evaluation
     *
     * @return void
     */
    public function submit_evaluation() {
        // Verify nonce with proper error handling
        if (!$this->verify_nonce()) {
            $this->error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Ensure container is properly initialized
        if (!$this->ensure_container()) {
            return;
        }
        
        // Debug: Log user capabilities
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $current_user_id = get_current_user_id();
            $user = wp_get_current_user();
            MT_Logger::debug('Evaluation AJAX user info', [
                'user_id' => $current_user_id,
                'user_roles' => $user->roles,
                'can_submit_evaluations' => current_user_can('mt_submit_evaluations')
            ]);
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            return;
        }
        
        // Debug: Log raw POST data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            MT_Logger::debug('Evaluation AJAX POST data', ['post_data' => $_POST]);
        }
        
        // Get current user as jury member
        $current_user_id = get_current_user_id();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            MT_Logger::debug('Evaluation AJAX processing for user', ['user_id' => $current_user_id]);
        }
        
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                MT_Logger::warning('Jury member not found for user', ['user_id' => $current_user_id]);
            }
            $this->error(__('Your jury member profile could not be found.', 'mobility-trailblazers'));
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            MT_Logger::debug('Jury member found for evaluation', [
                'jury_member_id' => $jury_member->ID,
                'user_id' => $current_user_id
            ]);
        }
        
        // Get status (draft or completed)
        $status = $this->get_param('status', 'completed');
        if (!in_array($status, ['draft', 'completed'])) {
            $status = 'completed';
        }
        
        // Debug: Check candidate_id specifically
        $raw_candidate_id = $this->get_param('candidate_id');
        MT_Logger::debug('Evaluation candidate ID processing', [
            'raw_candidate_id' => $raw_candidate_id
        ]);
        $candidate_id = $this->get_int_param('candidate_id');
        MT_Logger::debug('Candidate ID processed', ['processed_candidate_id' => $candidate_id]);
        
        // Prepare evaluation data with validation
        $courage_score = $this->get_float_param('courage_score');
        $innovation_score = $this->get_float_param('innovation_score');
        $implementation_score = $this->get_float_param('implementation_score');
        $relevance_score = $this->get_float_param('relevance_score');
        $visibility_score = $this->get_float_param('visibility_score');
        
        // Validate scores are within 1-10 range
        $scores_to_validate = [
            'courage_score' => $courage_score,
            'innovation_score' => $innovation_score,
            'implementation_score' => $implementation_score,
            'relevance_score' => $relevance_score,
            'visibility_score' => $visibility_score
        ];
        
        foreach ($scores_to_validate as $score_name => $score_value) {
            if ($score_value < 0 || $score_value > 10) {
                $this->error(__('Invalid score value. All scores must be between 0 and 10.', 'mobility-trailblazers'));
                return;
            }
        }
        
        $data = [
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $candidate_id,
            'courage_score' => $courage_score,
            'innovation_score' => $innovation_score,
            'implementation_score' => $implementation_score,
            'relevance_score' => $relevance_score,
            'visibility_score' => $visibility_score,
            'comments' => $this->get_textarea_param('comments'),
            'status' => $status
        ];
        
        MT_Logger::debug('Evaluation data prepared', ['data' => $data]);
        
        // Debug: Check if assignment exists
        $assignment_repo = $this->get_assignment_repository();
        $has_assignment = $assignment_repo->exists($jury_member->ID, $data['candidate_id']);
        
        MT_Logger::debug('Evaluation assignment check', [
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $data['candidate_id'],
            'has_assignment' => $has_assignment
        ]);
        
        // Debug: Let's also check what assignments exist for this jury member
        $all_assignments = $assignment_repo->get_by_jury_member($jury_member->ID);
        MT_Logger::debug('Evaluation jury member assignments', [
            'jury_member_id' => $jury_member->ID,
            'assignment_count' => count($all_assignments),
            'assignments' => array_map(function($assignment) {
                return [
                    'jury_member_id' => $assignment->jury_member_id,
                    'candidate_id' => $assignment->candidate_id
                ];
            }, $all_assignments)
        ]);
        
        if (!$has_assignment) {
            $this->error(__('You do not have permission to evaluate this candidate. Please contact an administrator.', 'mobility-trailblazers'));
            return;
        }
        
        // Process evaluation
        $service = new MT_Evaluation_Service();
        
        if ($status === 'draft') {
            $result = $service->save_draft($data);
            $message = __('Draft saved successfully!', 'mobility-trailblazers');
        } else {
            $result = $service->submit_final($data);
            $message = __('Evaluation submitted successfully!', 'mobility-trailblazers');
        }
        
        if ($result) {
            $this->success(
                ['evaluation_id' => $result],
                $message
            );
        } else {
            $errors = $service->get_errors();
            $error_message = !empty($errors) ? implode(', ', $errors) : __('Failed to save evaluation.', 'mobility-trailblazers');
            $this->error($error_message);
        }
    }
    
    /**
     * Get evaluation data
     *
     * @return void
     */
    public function get_evaluation() {
        // Verify nonce with proper error handling
        if (!$this->verify_nonce()) {
            $this->error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            return;
        }
        
        $candidate_id = $this->get_int_param('candidate_id');
        if (!$candidate_id) {
            $this->error(__('Invalid candidate ID.', 'mobility-trailblazers'));
        }
        
        // Get current user as jury member
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            $this->error(__('Your jury member profile could not be found.', 'mobility-trailblazers'));
        }
        
        // Get evaluation
        $evaluation_repo = $this->get_evaluation_repository();
        $evaluations = $evaluation_repo->find_all([
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $candidate_id,
            'limit' => 1
        ]);
        
        $evaluation = !empty($evaluations) ? $evaluations[0] : null;
        
        $this->success([
            'evaluation' => $evaluation,
            'exists' => !is_null($evaluation)
        ]);
    }
    
    /**
     * Get candidate details
     *
     * @return void
     */
    public function get_candidate_details() {
        // Verify nonce with proper error handling
        if (!$this->verify_nonce()) {
            $this->error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            return;
        }
        
        $candidate_id = $this->get_int_param('candidate_id');
        if (!$candidate_id) {
            $this->error(__('Invalid candidate ID.', 'mobility-trailblazers'));
        }
        
        // Get candidate
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            $this->error(__('Candidate not found.', 'mobility-trailblazers'));
        }
        
        // Get candidate meta
        $organization = get_post_meta($candidate_id, '_mt_organization', true) ?: '';
        $position = get_post_meta($candidate_id, '_mt_position', true) ?: '';
        $linkedin = get_post_meta($candidate_id, '_mt_linkedin_url', true) ?: '';
        $website = get_post_meta($candidate_id, '_mt_website_url', true) ?: '';
        
        // Get categories
        $categories = wp_get_post_terms($candidate_id, 'mt_award_category', [
            'fields' => 'names'
        ]);
        
        // Handle WP_Error case
        if (is_wp_error($categories)) {
            MT_Logger::warning('Failed to get categories in get_candidate_details', [
                'candidate_id' => $candidate_id,
                'error_message' => $categories->get_error_message()
            ]);
            $categories = [];
        }
        
        // Get featured image
        $photo_url = get_the_post_thumbnail_url($candidate_id, 'large');
        
        $this->success([
            'id' => $candidate->ID,
            'name' => $candidate->post_title,
            'bio' => $candidate->post_content,
            'excerpt' => $candidate->post_excerpt,
            'organization' => $organization,
            'position' => $position,
            'linkedin' => $linkedin,
            'website' => $website,
            'categories' => $categories,
            'photo_url' => $photo_url
        ]);
    }
    
    /**
     * Get jury member progress
     *
     * @return void
     */
    public function get_jury_progress() {
        // Verify nonce with proper error handling
        if (!$this->verify_nonce()) {
            $this->error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            return;
        }
        
        // Get current user as jury member
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            $this->error(__('Your jury member profile could not be found.', 'mobility-trailblazers'));
        }
        
        // Get progress
        $service = new MT_Evaluation_Service();
        $progress = $service->get_jury_progress($jury_member->ID);
        
        $this->success($progress);
    }

    /**
     * Get ranked candidates for jury dashboard
     */
    public function get_jury_rankings() {
        // Verify nonce with proper termination
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            wp_die(); // Ensure execution stops
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
        }
        
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            $this->error(__('Jury member not found', 'mobility-trailblazers'));
        }
        
        $evaluation_repo = $this->get_evaluation_repository();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member->ID, $limit);
        
        $this->success([
            'rankings' => $rankings,
            'html' => $this->render_rankings_html($rankings)
        ]);
    }

    /**
     * Render rankings HTML
     */
    private function render_rankings_html($rankings) {
        ob_start();
        $template_file = MT_PLUGIN_DIR . 'templates/frontend/partials/jury-rankings.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="mt-error">' . esc_html__('Rankings template not found.', 'mobility-trailblazers') . '</div>';
        }
        return ob_get_clean();
    }
    
    /**
     * Get jury member by user ID
     *
     * @param int $user_id User ID
     * @return WP_Post|null
     */
    private function get_jury_member_by_user_id($user_id) {
        // Standardized to use only _mt_user_id meta key
        $args = [
            'post_type' => 'mt_jury_member',
            'meta_key' => '_mt_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ];
        
        MT_Logger::debug('Looking for jury member by user ID', ['user_id' => $user_id]);
        
        $jury_members = get_posts($args);
        
        if (!empty($jury_members)) {
            MT_Logger::debug('Jury member found', [
                'jury_member_id' => $jury_members[0]->ID,
                'title' => $jury_members[0]->post_title
            ]);
            return $jury_members[0];
        }
        
        // If not found, try to migrate old meta keys to the standardized one
        $old_meta_keys = ['mt_user_id', 'user_id', '_user_id'];
        
        foreach ($old_meta_keys as $old_key) {
            $args['meta_key'] = $old_key;
            $jury_members = get_posts($args);
            
            if (!empty($jury_members)) {
                // Found with old key, migrate to new standardized key
                $jury_member = $jury_members[0];
                update_post_meta($jury_member->ID, '_mt_user_id', $user_id);
                delete_post_meta($jury_member->ID, $old_key, $user_id);
                
                MT_Logger::info('Jury member metadata migrated', [
                    'jury_member_id' => $jury_member->ID,
                    'old_meta_key' => $old_key,
                    'new_meta_key' => '_mt_user_id'
                ]);
                return $jury_member;
            }
        }
        
        // If still no jury member found, log additional debug info
        MT_Logger::warning('No jury member found for user after migration attempt', ['user_id' => $user_id]);
        
        // Get all jury members and check their meta
        $all_jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => 5,
            'post_status' => 'any'
        ]);
        
        foreach ($all_jury_members as $jm) {
            $all_meta = get_post_meta($jm->ID);
            MT_Logger::debug('Jury member metadata debug', [
                'jury_member_id' => $jm->ID,
                'title' => $jm->post_title,
                'metadata' => $all_meta
            ]);
        }
        
        return null;
    }

    /**
     * Handle bulk evaluation actions
     *
     * @return void
     */
    public function bulk_evaluation_action() {
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_evaluations')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        // Get parameters
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $evaluation_ids = isset($_POST['evaluation_ids']) && is_array($_POST['evaluation_ids']) 
            ? array_map('intval', $_POST['evaluation_ids']) 
            : array();
        
        if (empty($action) || empty($evaluation_ids)) {
            $this->error(__('Invalid parameters', 'mobility-trailblazers'));
            return;
        }
        
        // Log for debugging
        MT_Logger::info('Bulk evaluation operation', [
            'action' => $action,
            'evaluation_count' => count($evaluation_ids)
        ]);
        
        $evaluation_repo = $this->get_evaluation_repository();
        $success_count = 0;
        $errors = [];
        
        foreach ($evaluation_ids as $evaluation_id) {
            $result = false;
            
            // Get evaluation details for audit log
            $evaluation = $evaluation_repo->find($evaluation_id);
            
            switch ($action) {
                case 'approve':
                    $result = $evaluation_repo->update($evaluation_id, ['status' => 'approved']);
                    if ($result) {
                        MT_Audit_Logger::log(
                            'evaluation_approved',
                            'evaluation',
                            $evaluation_id,
                            [
                                'jury_member_id' => $evaluation->jury_member_id ?? null,
                                'candidate_id' => $evaluation->candidate_id ?? null,
                                'previous_status' => $evaluation->status ?? 'unknown',
                                'new_status' => 'approved'
                            ]
                        );
                    }
                    break;
                    
                case 'reject':
                    $result = $evaluation_repo->update($evaluation_id, ['status' => 'rejected']);
                    if ($result) {
                        MT_Audit_Logger::log(
                            'evaluation_rejected',
                            'evaluation',
                            $evaluation_id,
                            [
                                'jury_member_id' => $evaluation->jury_member_id ?? null,
                                'candidate_id' => $evaluation->candidate_id ?? null,
                                'previous_status' => $evaluation->status ?? 'unknown',
                                'new_status' => 'rejected'
                            ]
                        );
                    }
                    break;
                    
                case 'reset':
                    $result = $evaluation_repo->update($evaluation_id, ['status' => 'draft']);
                    if ($result) {
                        MT_Audit_Logger::log(
                            'evaluation_reset',
                            'evaluation',
                            $evaluation_id,
                            [
                                'jury_member_id' => $evaluation->jury_member_id ?? null,
                                'candidate_id' => $evaluation->candidate_id ?? null,
                                'previous_status' => $evaluation->status ?? 'unknown',
                                'new_status' => 'draft'
                            ]
                        );
                    }
                    break;
                    
                case 'delete':
                    // Capture evaluation details before deletion
                    $deleted_details = $evaluation ? [
                        'jury_member_id' => $evaluation->jury_member_id,
                        'candidate_id' => $evaluation->candidate_id,
                        'status' => $evaluation->status,
                        'score' => $evaluation->score ?? null
                    ] : [];
                    
                    $result = $evaluation_repo->delete($evaluation_id);
                    if ($result) {
                        MT_Audit_Logger::log(
                            'evaluation_deleted',
                            'evaluation',
                            $evaluation_id,
                            $deleted_details
                        );
                    }
                    break;
                    
                default:
                    $errors[] = sprintf(__('Unknown action: %s', 'mobility-trailblazers'), $action);
                    continue 2;
            }
            
            if ($result) {
                $success_count++;
            } else {
                $errors[] = sprintf(__('Failed to %s evaluation ID: %d', 'mobility-trailblazers'), $action, $evaluation_id);
            }
        }
        
        if ($success_count > 0) {
            $message = sprintf(
                __('%d evaluations %s successfully.', 'mobility-trailblazers'),
                $success_count,
                $this->get_action_past_tense($action)
            );
            
            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('%d failed.', 'mobility-trailblazers'), count($errors));
            }
            
            $this->success([
                'success_count' => $success_count,
                'errors' => $errors
            ], $message);
        } else {
            $this->error(__('No evaluations could be processed.', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Get past tense of action for messages
     *
     * @param string $action Action name
     * @return string Past tense
     */
    private function get_action_past_tense($action) {
        $past_tense = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'reset' => 'reset to draft',
            'delete' => 'deleted'
        ];
        
        return isset($past_tense[$action]) ? $past_tense[$action] : $action;
    }

    /**
     * Save inline evaluation from rankings grid
     */
    public function save_inline_evaluation() {
        // Log inline evaluation attempt for debugging if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            MT_Logger::debug('Inline evaluation save attempt', ['user_id' => get_current_user_id()]);
        }
        
        // Verify nonce using base class method
        if (!$this->verify_nonce('mt_ajax_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                MT_Logger::security_event('Inline evaluation nonce verification failed', ['user_id' => get_current_user_id()]);
            }
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            $this->error(__('Jury member not found', 'mobility-trailblazers'));
            return;
        }
        
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        if (!$candidate_id) {
            $this->error(__('Invalid candidate ID', 'mobility-trailblazers'));
            return;
        }
        
        $scores = isset($_POST['scores']) ? $_POST['scores'] : [];
        
        // Enhanced debugging for assignment verification
        MT_Logger::debug('Inline evaluation save debug info', [
            'current_user_id' => $current_user_id,
            'user_roles' => wp_get_current_user()->roles,
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $candidate_id,
            'scores' => $scores,
            'is_administrator' => current_user_can('administrator'),
            'can_manage_evaluations' => current_user_can('mt_manage_evaluations')
        ]);
        
        // Verify assignment
        $assignment_repo = $this->get_assignment_repository();
        $assignment_exists = $assignment_repo->exists($jury_member->ID, $candidate_id);
        
        MT_Logger::debug('Assignment existence check', ['assignment_exists' => $assignment_exists]);
        
        // If assignment doesn't exist, let's check what assignments this jury member has
        if (!$assignment_exists) {
            $jury_assignments = $assignment_repo->get_by_jury_member($jury_member->ID);
            MT_Logger::debug('Jury member assignments check', [
                'jury_member_id' => $jury_member->ID,
                'total_assignments' => count($jury_assignments),
                'assigned_candidate_ids' => array_map(function($assignment) {
                    return $assignment->candidate_id;
                }, $jury_assignments)
            ]);
            
            // Check if user has special permissions that allow evaluating any candidate
            $can_evaluate_all = current_user_can('administrator') || current_user_can('mt_manage_evaluations');
            
            // For table views from admin or managers, allow evaluation without assignment
            $is_table_view = isset($_POST['context']) && $_POST['context'] === 'table';
            
            // Even administrators must have proper assignments for security
            if (!$assignment_exists) {
                if ($can_evaluate_all) {
                    // Log this for audit purposes
                    MT_Logger::warning('Admin/Manager evaluating without assignment', [
                        'jury_member_id' => $jury_member->ID,
                        'candidate_id' => $candidate_id
                    ]);
                    // For now, allow admins but this should be reviewed
                    MT_Logger::info('Legacy behavior: allowing evaluation for admin/manager without assignment');
                } else {
                    $this->error(__('You are not assigned to evaluate this candidate', 'mobility-trailblazers'));
                    return;
                }
            }
        }
        
        // Get existing evaluation if any
        $evaluation_repo = $this->get_evaluation_repository();
        $existing_evaluation = $evaluation_repo->find_by_jury_and_candidate($jury_member->ID, $candidate_id);
        
        // Prepare evaluation data
        $evaluation_data = [
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $candidate_id,
            'status' => 'completed'
        ];
        
        // If we have an existing evaluation, preserve its data
        if ($existing_evaluation) {
            $evaluation_data['id'] = $existing_evaluation->id;
            $evaluation_data['comments'] = $existing_evaluation->comments;
            // Initialize with existing scores
            $evaluation_data['courage_score'] = floatval($existing_evaluation->courage_score);
            $evaluation_data['innovation_score'] = floatval($existing_evaluation->innovation_score);
            $evaluation_data['implementation_score'] = floatval($existing_evaluation->implementation_score);
            $evaluation_data['relevance_score'] = floatval($existing_evaluation->relevance_score);
            $evaluation_data['visibility_score'] = floatval($existing_evaluation->visibility_score);
        } else {
            // Initialize all scores to 0
            $evaluation_data['comments'] = '';
            $evaluation_data['courage_score'] = 0;
            $evaluation_data['innovation_score'] = 0;
            $evaluation_data['implementation_score'] = 0;
            $evaluation_data['relevance_score'] = 0;
            $evaluation_data['visibility_score'] = 0;
        }
        
        // Map score field names to ensure correct database column names
        $score_field_mapping = [
            'courage' => 'courage_score',
            'innovation' => 'innovation_score',
            'implementation' => 'implementation_score',
            'relevance' => 'relevance_score',
            'visibility' => 'visibility_score'
        ];
        
        // Update with new scores from the form
        foreach ($scores as $criterion => $score) {
            // Handle both full names (courage_score) and short names (courage)
            $field_name = isset($score_field_mapping[$criterion]) ? $score_field_mapping[$criterion] : $criterion;
            
            // Only update if it's a valid score field and has a numeric value
            if (in_array($field_name, array_values($score_field_mapping)) && is_numeric($score)) {
                $evaluation_data[$field_name] = floatval($score);
            }
        }
        
        // Log final data for debugging
        MT_Logger::debug('Final evaluation data for inline save', ['evaluation_data' => $evaluation_data]);
        
        // Save evaluation
        $evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
        $result = $evaluation_service->save_evaluation($evaluation_data);
        
        if (is_wp_error($result)) {
            MT_Logger::error('Inline evaluation save failed', ['error_message' => $result->get_error_message()]);
            $this->error($result->get_error_message());
            return;
        }
        
        // Get updated evaluation data
        $updated_evaluation = $evaluation_repo->find_by_jury_and_candidate($jury_member->ID, $candidate_id);
        
        // Clear transient cache for rankings to ensure fresh data
        // Clear all possible cache variations for this jury member
        for ($page = 1; $page <= 50; $page++) {
            delete_transient('mt_jury_rankings_' . $jury_member->ID . '_' . $page);
        }
        // Also clear the general rankings cache
        delete_transient('mt_ranked_candidates_' . $jury_member->ID);
        
        $response_data = [
            'message' => __('Evaluation saved successfully', 'mobility-trailblazers'),
            'evaluation_id' => $result,
            'total_score' => $updated_evaluation ? floatval($updated_evaluation->total_score) : 0,
            'refresh_rankings' => true
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            MT_Logger::info('Inline evaluation saved successfully', ['candidate_id' => $candidate_id]);
        }
        
        $this->success($response_data);
    }

    /**
     * Test AJAX endpoint for debugging
     *
     * @return void
     */
    public function test_ajax() {
        // Verify nonce
        if (!$this->verify_nonce('mt_ajax_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            $this->error(__('You must be logged in to test AJAX', 'mobility-trailblazers'));
            return;
        }
        
        $this->success([
            'message' => 'AJAX is working correctly',
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ], 'AJAX test successful');
    }
    
    /**
     * Debug user capabilities
     *
     * @return void
     */
    public function debug_user() {
        // Verify nonce
        if (!$this->verify_nonce('mt_ajax_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check if user has admin capabilities for debugging
        if (!current_user_can('manage_options')) {
            $this->error(__('You must be an administrator to access debug information', 'mobility-trailblazers'));
            return;
        }
        
        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        $debug_info = [
            'user_id' => $current_user_id,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'roles' => $user->roles,
            'capabilities' => $user->allcaps,
            'can_submit_evaluations' => current_user_can('mt_submit_evaluations'),
            'is_logged_in' => is_user_logged_in(),
            'has_jury_member_role' => in_array('mt_jury_member', $user->roles),
            'has_administrator_role' => in_array('administrator', $user->roles)
        ];
        
        $this->success($debug_info, 'User debug information');
    }
    
    /**
     * Get evaluation details for modal display
     *
     * @return void
     */
    public function get_evaluation_details() {
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_evaluations') && !current_user_can('administrator')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        // Get evaluation ID
        $evaluation_id = $this->get_int_param('evaluation_id');
        if (!$evaluation_id) {
            $this->error(__('Invalid evaluation ID', 'mobility-trailblazers'));
            return;
        }
        
        // Get evaluation data
        $evaluation_repo = $this->get_evaluation_repository();
        $evaluation = $evaluation_repo->find($evaluation_id);
        
        if (!$evaluation) {
            $this->error(__('Evaluation not found', 'mobility-trailblazers'));
            return;
        }
        
        // Get related data
        $jury_member = get_post($evaluation->jury_member_id);
        $candidate = get_post($evaluation->candidate_id);
        
        // Check if posts exist to avoid fatal errors
        $jury_member_title = ($jury_member && is_object($jury_member) && isset($jury_member->post_title)) 
            ? $jury_member->post_title 
            : __('Unknown (Deleted)', 'mobility-trailblazers');
            
        $candidate_title = ($candidate && is_object($candidate) && isset($candidate->post_title)) 
            ? $candidate->post_title 
            : __('Unknown (Deleted)', 'mobility-trailblazers');
        
        // Get categories - only if candidate exists
        $categories = ($candidate && is_object($candidate)) 
            ? wp_get_post_terms($evaluation->candidate_id, 'mt_category', ['fields' => 'names'])
            : [];
        
        // Ensure categories is an array (wp_get_post_terms can return WP_Error)
        if (is_wp_error($categories)) {
            // Log the error for debugging
            MT_Logger::warning('Failed to get categories for evaluation details', [
                'candidate_id' => $evaluation->candidate_id,
                'error_message' => $categories->get_error_message(),
                'error_code' => $categories->get_error_code()
            ]);
            $categories = [];
        }
        
        // Prepare response data
        $data = [
            'id' => $evaluation->id,
            'jury_member' => $jury_member_title,
            'candidate' => $candidate_title,
            'organization' => get_post_meta($evaluation->candidate_id, '_mt_organization', true),
            'categories' => implode(', ', $categories),
            'scores' => [
                'courage' => [
                    'label' => __('Courage & Pioneer Spirit', 'mobility-trailblazers'),
                    'value' => floatval($evaluation->courage_score)
                ],
                'innovation' => [
                    'label' => __('Innovation Degree', 'mobility-trailblazers'),
                    'value' => floatval($evaluation->innovation_score)
                ],
                'implementation' => [
                    'label' => __('Implementation & Impact', 'mobility-trailblazers'),
                    'value' => floatval($evaluation->implementation_score)
                ],
                'relevance' => [
                    'label' => __('Mobility Transformation Relevance', 'mobility-trailblazers'),
                    'value' => floatval($evaluation->relevance_score)
                ],
                'visibility' => [
                    'label' => __('Role Model & Visibility', 'mobility-trailblazers'),
                    'value' => floatval($evaluation->visibility_score)
                ]
            ],
            'total_score' => floatval($evaluation->courage_score) + 
                           floatval($evaluation->innovation_score) + 
                           floatval($evaluation->implementation_score) + 
                           floatval($evaluation->relevance_score) + 
                           floatval($evaluation->visibility_score),
            'average_score' => (floatval($evaluation->courage_score) + 
                              floatval($evaluation->innovation_score) + 
                              floatval($evaluation->implementation_score) + 
                              floatval($evaluation->relevance_score) + 
                              floatval($evaluation->visibility_score)) / 5,
            'comments' => $evaluation->comments,
            'status' => $evaluation->status,
            'created_at' => mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $evaluation->created_at),
            'updated_at' => mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $evaluation->updated_at)
        ];
        
        $this->success($data);
    }
    
    /**
     * Delete single evaluation
     *
     * @return void
     */
    public function delete_evaluation() {
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_evaluations') && !current_user_can('administrator')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        // Get evaluation ID
        $evaluation_id = $this->get_int_param('evaluation_id');
        if (!$evaluation_id) {
            $this->error(__('Invalid evaluation ID', 'mobility-trailblazers'));
            return;
        }
        
        // Get evaluation details for audit log
        $evaluation_repo = $this->get_evaluation_repository();
        $evaluation = $evaluation_repo->find($evaluation_id);
        
        if (!$evaluation) {
            $this->error(__('Evaluation not found', 'mobility-trailblazers'));
            return;
        }
        
        // Delete the evaluation
        $result = $evaluation_repo->delete($evaluation_id);
        
        if ($result) {
            // Log the deletion
            MT_Audit_Logger::log(
                'evaluation_deleted',
                'evaluation',
                $evaluation_id,
                [
                    'jury_member_id' => $evaluation->jury_member_id,
                    'candidate_id' => $evaluation->candidate_id,
                    'status' => $evaluation->status,
                    'total_score' => $evaluation->total_score ?? 0
                ]
            );
            
            $this->success(
                ['deleted_id' => $evaluation_id],
                __('Evaluation deleted successfully', 'mobility-trailblazers')
            );
        } else {
            $this->error(__('Failed to delete evaluation', 'mobility-trailblazers'));
        }
    }

} 
