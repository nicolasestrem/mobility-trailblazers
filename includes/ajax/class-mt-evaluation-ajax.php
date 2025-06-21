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
    }
    
    /**
     * Submit evaluation
     *
     * @return void
     */
    public function submit_evaluation() {
        $this->verify_nonce();
        $this->check_permission('mt_submit_evaluations');
        
        // Get current user as jury member
        $current_user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        
        if (!$jury_member) {
            $this->error(__('Your jury member profile could not be found.', 'mobility-trailblazers'));
        }
        
        // Get status (draft or completed)
        $status = $this->get_param('status', 'completed');
        if (!in_array($status, ['draft', 'completed'])) {
            $status = 'completed';
        }
        
        // Prepare evaluation data
        $data = [
            'jury_member_id' => $jury_member->ID,
            'candidate_id' => $this->get_int_param('candidate_id'),
            'courage_score' => $this->get_int_param('courage_score'),
            'innovation_score' => $this->get_int_param('innovation_score'),
            'implementation_score' => $this->get_int_param('implementation_score'),
            'relevance_score' => $this->get_int_param('relevance_score'),
            'visibility_score' => $this->get_int_param('visibility_score'),
            'comments' => $this->get_textarea_param('comments'),
            'status' => $status
        ];
        
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
            $this->error(
                __('Failed to save evaluation.', 'mobility-trailblazers'),
                ['errors' => $service->get_errors()]
            );
        }
    }
    
    /**
     * Save draft evaluation
     *
     * @return void
     */
    public function save_draft() {
        $this->verify_nonce();
        $this->check_permission('mt_submit_evaluations');
        
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
                $data[$field] = intval($value);
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
        $this->verify_nonce();
        $this->check_permission('mt_submit_evaluations');
        
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
        $this->verify_nonce();
        $this->check_permission('mt_submit_evaluations');
        
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
        $this->verify_nonce();
        $this->check_permission('mt_submit_evaluations');
        
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
     * Get jury member by user ID
     *
     * @param int $user_id User ID
     * @return WP_Post|null
     */
    private function get_jury_member_by_user_id($user_id) {
        $args = [
            'post_type' => 'mt_jury_member',
            'meta_key' => '_mt_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ];
        
        $jury_members = get_posts($args);
        
        return !empty($jury_members) ? $jury_members[0] : null;
    }
} 