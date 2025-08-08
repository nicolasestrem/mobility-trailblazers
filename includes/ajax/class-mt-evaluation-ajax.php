<?php
/**
 * Evaluation AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Services\MT_Evaluation_Service;

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
     * Initialize AJAX handlers
     *
     * @return void
     */
    public function init() {
        // Logged in users
        add_action('wp_ajax_mt_submit_evaluation', [$this, 'submit_evaluation']);
        add_action('wp_ajax_mt_save_draft', [$this, 'save_draft']);
        add_action('wp_ajax_mt_get_evaluation', [$this, 'get_evaluation']);
        add_action('wp_ajax_mt_get_candidate_details', [$this, 'get_candidate_details']);
        add_action('wp_ajax_mt_get_jury_progress', [$this, 'get_jury_progress']);
        add_action('wp_ajax_mt_get_jury_rankings', [$this, 'get_jury_rankings']);
        add_action('wp_ajax_mt_save_inline_evaluation', [$this, 'save_inline_evaluation']);
        
        // Test AJAX action to handle test calls gracefully
        add_action('wp_ajax_mt_test_ajax', [$this, 'test_ajax']);
        
        // Bulk operations
        add_action('wp_ajax_mt_bulk_evaluation_action', [$this, 'bulk_evaluation_action']);
    }
    
    /**
     * Submit evaluation
     *
     * @return void
     */
    public function submit_evaluation() {
        // Verify nonce with proper error handling
        if (!$this->verify_nonce()) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            wp_send_json_error(__('You do not have permission to submit evaluations.', 'mobility-trailblazers'));
            return;
        }
        
        // Debug: Log raw POST data
        error_log('MT AJAX - Raw POST data: ' . print_r($_POST, true));
        
        // Get current user as jury member
        $current_user_id = get_current_user_id();
        error_log('MT AJAX - Current user ID: ' . $current_user_id);
        
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            error_log('MT AJAX - Jury member not found for user ID: ' . $current_user_id);
            $this->error(__('Your jury member profile could not be found.', 'mobility-trailblazers'));
        }
        
        error_log('MT AJAX - Found jury member: ' . $jury_member->ID . ' for user: ' . $current_user_id);
        
        // Get status (draft or completed)
        $status = $this->get_param('status', 'completed');
        if (!in_array($status, ['draft', 'completed'])) {
            $status = 'completed';
        }
        
        // Debug: Check candidate_id specifically
        $raw_candidate_id = $this->get_param('candidate_id');
        error_log('MT AJAX - Raw candidate_id from POST: ' . var_export($raw_candidate_id, true));
        $candidate_id = $this->get_int_param('candidate_id');
        error_log('MT AJAX - Processed candidate_id: ' . $candidate_id);
        
        // Prepare evaluation data
        $data = [
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $candidate_id,
            'courage_score' => $this->get_float_param('courage_score'),
            'innovation_score' => $this->get_float_param('innovation_score'),
            'implementation_score' => $this->get_float_param('implementation_score'),
            'relevance_score' => $this->get_float_param('relevance_score'),
            'visibility_score' => $this->get_float_param('visibility_score'),
            'comments' => $this->get_textarea_param('comments'),
            'status' => $status
        ];
        
        error_log('MT AJAX - Evaluation data prepared: ' . print_r($data, true));
        
        // Debug: Check if assignment exists
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        $has_assignment = $assignment_repo->exists($jury_member->ID, $data['candidate_id']);
        
        error_log('MT AJAX - Assignment check: jury_member_id=' . $jury_member->ID . ', candidate_id=' . $data['candidate_id'] . ', has_assignment=' . ($has_assignment ? 'true' : 'false'));
        
        // Debug: Let's also check what assignments exist for this jury member
        $all_assignments = $assignment_repo->get_by_jury_member($jury_member->ID);
        error_log('MT AJAX - All assignments for jury member ' . $jury_member->ID . ': ' . count($all_assignments));
        foreach ($all_assignments as $assignment) {
            error_log('MT AJAX - Assignment: jury_member_id=' . $assignment->jury_member_id . ', candidate_id=' . $assignment->candidate_id);
        }
        
        if (!$has_assignment) {
            $this->error(__('You do not have permission to evaluate this candidate. Please contact an administrator.', 'mobility-trailblazers'));
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
     * Save draft evaluation
     *
     * @return void
     */
    public function save_draft() {
        // Verify nonce with proper error handling
        if (!$this->verify_nonce()) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            wp_send_json_error(__('You do not have permission to save drafts.', 'mobility-trailblazers'));
            return;
        }
        
        // Get current user as jury member
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            $this->error(__('Your jury member profile could not be found.', 'mobility-trailblazers'));
        }
        
        // Prepare evaluation data
        $data = [
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $this->get_int_param('candidate_id'),
            'status' => 'draft'
        ];
        
        // Add scores if provided
        $score_fields = [
            'courage_score',
            'innovation_score',
            'implementation_score',
            'relevance_score',
            'visibility_score'
        ];
        
        foreach ($score_fields as $field) {
            $value = $this->get_param($field);
            if ($value !== null && $value !== '') {
                $data[$field] = floatval($value);
            }
        }
        
        // Add comments if provided
        $comments = $this->get_textarea_param('comments');
        if (!empty($comments)) {
            $data['comments'] = $comments;
        }
        
        // Process evaluation
        $service = new MT_Evaluation_Service();
        $result = $service->save_draft($data);
        
        if ($result) {
            $this->success(
                ['evaluation_id' => $result],
                __('Draft saved successfully!', 'mobility-trailblazers')
            );
        } else {
            $this->error(
                __('Failed to save draft.', 'mobility-trailblazers'),
                ['errors' => $service->get_errors()]
            );
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
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            wp_send_json_error(__('You do not have permission to view evaluations.', 'mobility-trailblazers'));
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
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
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
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            wp_send_json_error(__('You do not have permission to view candidate details.', 'mobility-trailblazers'));
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
        $organization = get_post_meta($candidate_id, '_mt_organization', true);
        $position = get_post_meta($candidate_id, '_mt_position', true);
        $linkedin = get_post_meta($candidate_id, '_mt_linkedin', true);
        $website = get_post_meta($candidate_id, '_mt_website', true);
        
        // Get categories
        $categories = wp_get_post_terms($candidate_id, 'mt_award_category', [
            'fields' => 'names'
        ]);
        
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
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!$this->check_permission('mt_submit_evaluations')) {
            wp_send_json_error(__('You do not have permission to view progress.', 'mobility-trailblazers'));
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
        // Verify nonce
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(__('Permission denied', 'mobility-trailblazers'));
        }
        
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            wp_send_json_error(__('Jury member not found', 'mobility-trailblazers'));
        }
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member->ID, $limit);
        
        wp_send_json_success([
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
        // Try different meta keys that might be used
        $meta_keys = ['_mt_user_id', 'mt_user_id', 'user_id', '_user_id'];
        
        foreach ($meta_keys as $meta_key) {
            $args = [
                'post_type' => 'mt_jury_member',
                'meta_key' => $meta_key,
                'meta_value' => $user_id,
                'posts_per_page' => 1,
                'post_status' => 'publish'
            ];
            
            error_log('MT AJAX - Trying meta key "' . $meta_key . '" with args: ' . print_r($args, true));
            
            $jury_members = get_posts($args);
            
            error_log('MT AJAX - Found ' . count($jury_members) . ' jury members for user ' . $user_id . ' with meta key "' . $meta_key . '"');
            
            if (!empty($jury_members)) {
                error_log('MT AJAX - Jury member found with meta key "' . $meta_key . '": ID=' . $jury_members[0]->ID . ', Title=' . $jury_members[0]->post_title);
                return $jury_members[0];
            }
        }
        
        // If no jury member found with any meta key, let's check what meta keys exist
        error_log('MT AJAX - No jury member found with any meta key for user ' . $user_id);
        
        // Get all jury members and check their meta
        $all_jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => 5,
            'post_status' => 'any'
        ]);
        
        foreach ($all_jury_members as $jm) {
            $all_meta = get_post_meta($jm->ID);
            error_log('MT AJAX - Jury member ' . $jm->ID . ' (' . $jm->post_title . ') meta: ' . print_r($all_meta, true));
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
            wp_send_json_error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_evaluations')) {
            wp_send_json_error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        // Get parameters
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $evaluation_ids = isset($_POST['evaluation_ids']) && is_array($_POST['evaluation_ids']) 
            ? array_map('intval', $_POST['evaluation_ids']) 
            : array();
        
        if (empty($action) || empty($evaluation_ids)) {
            wp_send_json_error(__('Invalid parameters', 'mobility-trailblazers'));
            return;
        }
        
        // Log for debugging
        error_log('MT Bulk Evaluation: action=' . $action . ', count=' . count($evaluation_ids));
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $success_count = 0;
        $errors = [];
        
        foreach ($evaluation_ids as $evaluation_id) {
            $result = false;
            
            switch ($action) {
                case 'approve':
                    $result = $evaluation_repo->update($evaluation_id, ['status' => 'approved']);
                    break;
                    
                case 'reject':
                    $result = $evaluation_repo->update($evaluation_id, ['status' => 'rejected']);
                    break;
                    
                case 'reset':
                    $result = $evaluation_repo->update($evaluation_id, ['status' => 'draft']);
                    break;
                    
                case 'delete':
                    $result = $evaluation_repo->delete($evaluation_id);
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
            
            wp_send_json_success([
                'message' => $message,
                'success_count' => $success_count,
                'errors' => $errors
            ]);
        } else {
            wp_send_json_error(__('No evaluations could be processed.', 'mobility-trailblazers'));
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
            error_log('MT: Inline evaluation save attempt by user ' . get_current_user_id());
        }
        
        // Verify nonce using base class method
        if (!$this->verify_nonce('mt_ajax_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MT: Inline evaluation nonce verification failed for user ' . get_current_user_id());
            }
            wp_send_json_error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            wp_send_json_error(__('Jury member not found', 'mobility-trailblazers'));
            return;
        }
        
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        if (!$candidate_id) {
            wp_send_json_error(__('Invalid candidate ID', 'mobility-trailblazers'));
            return;
        }
        
        $scores = isset($_POST['scores']) ? $_POST['scores'] : [];
        
        // Log scores for debugging
        error_log('MT Inline Save - Scores: ' . print_r($scores, true));
        
        // Verify assignment
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        if (!$assignment_repo->exists($jury_member->ID, $candidate_id)) {
            wp_send_json_error(__('You are not assigned to evaluate this candidate', 'mobility-trailblazers'));
            return;
        }
        
        // Get existing evaluation if any
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
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
        
        // Update with new scores from the form
        foreach ($scores as $criterion => $score) {
            if (!empty($criterion) && is_numeric($score)) {
                $evaluation_data[$criterion] = floatval($score);
            }
        }
        
        // Log final data for debugging
        error_log('MT Inline Save - Final evaluation data: ' . print_r($evaluation_data, true));
        
        // Save evaluation
        $evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
        $result = $evaluation_service->save_evaluation($evaluation_data);
        
        if (is_wp_error($result)) {
            error_log('MT Inline Save - Error: ' . $result->get_error_message());
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        // Get updated evaluation data
        $updated_evaluation = $evaluation_repo->find_by_jury_and_candidate($jury_member->ID, $candidate_id);
        
        $response_data = [
            'message' => __('Evaluation saved successfully', 'mobility-trailblazers'),
            'evaluation_id' => $result,
            'total_score' => $updated_evaluation ? floatval($updated_evaluation->total_score) : 0,
            'refresh_rankings' => true
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MT: Inline evaluation saved successfully for candidate ' . $candidate_id);
        }
        
        wp_send_json_success($response_data);
    }

    /**
     * Test AJAX endpoint for debugging
     *
     * @return void
     */
    public function test_ajax() {
        $this->success([
            'message' => 'AJAX is working correctly',
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        ], 'AJAX test successful');
    }
} 