<?php
/**
 * Evaluation AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Services\MT_Evaluation_Service;

class MT_Evaluation_Ajax extends MT_Base_Ajax {
    
    /**
     * Register AJAX hooks
     */
    protected function register_hooks() {
        add_action('wp_ajax_mt_submit_evaluation', array($this, 'submit_evaluation'));
        add_action('wp_ajax_mt_save_draft', array($this, 'save_draft'));
        add_action('wp_ajax_mt_get_evaluation', array($this, 'get_evaluation'));
        add_action('wp_ajax_mt_export_evaluations', array($this, 'export_evaluations'));
        
        // Jury dashboard handlers
        add_action('wp_ajax_mt_get_jury_dashboard_data', array($this, 'get_jury_dashboard_data'));
        add_action('wp_ajax_mt_get_candidate_evaluation', array($this, 'get_candidate_evaluation'));
        add_action('wp_ajax_mt_save_evaluation', array($this, 'save_evaluation'));
    }
    
    /**
     * Submit evaluation
     */
    public function submit_evaluation() {
        $this->verify_nonce('mt_evaluation_nonce');
        $this->check_permission('mt_evaluate_candidates');
        
        $data = array(
            'candidate_id' => $this->get_int_param('candidate_id'),
            'jury_member_id' => get_current_user_id(),
            'scores' => $this->get_array_param('scores', array()),
            'comment' => sanitize_textarea_field($this->get_param('comment', '')),
            'status' => 'completed'
        );
        
        // Validate required fields
        if (!$data['candidate_id']) {
            $this->error(__('Invalid candidate', 'mobility-trailblazers'));
        }
        
        // Validate scores
        $required_criteria = array('courage', 'innovation', 'implementation', 'relevance', 'visibility');
        foreach ($required_criteria as $criterion) {
            if (!isset($data['scores'][$criterion]) || $data['scores'][$criterion] < 1 || $data['scores'][$criterion] > 10) {
                $this->error(__('Please rate all criteria before submitting', 'mobility-trailblazers'));
            }
        }
        
        try {
            $service = new MT_Evaluation_Service();
            $result = $service->process($data);
            
            if ($result) {
                $this->success(
                    array('evaluation_id' => $result),
                    __('Evaluation submitted successfully!', 'mobility-trailblazers')
                );
            } else {
                $this->error(__('Error submitting evaluation', 'mobility-trailblazers'));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Save draft evaluation
     */
    public function save_draft() {
        $this->verify_nonce('mt_evaluation_nonce');
        $this->check_permission('mt_evaluate_candidates');
        
        $data = array(
            'candidate_id' => $this->get_int_param('candidate_id'),
            'jury_member_id' => get_current_user_id(),
            'scores' => $this->get_array_param('scores', array()),
            'comment' => sanitize_textarea_field($this->get_param('comment', '')),
            'status' => 'draft'
        );
        
        try {
            $service = new MT_Evaluation_Service();
            $result = $service->process($data);
            
            if ($result) {
                $this->success(
                    array('evaluation_id' => $result),
                    __('Draft saved successfully!', 'mobility-trailblazers')
                );
            } else {
                $this->error(__('Error saving draft', 'mobility-trailblazers'));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Get evaluation data
     */
    public function get_evaluation() {
        $this->verify_nonce('mt_evaluation_nonce');
        $this->check_permission('mt_evaluate_candidates');
        
        $candidate_id = $this->get_int_param('candidate_id');
        $jury_member_id = get_current_user_id();
        
        if (!$candidate_id) {
            $this->error(__('Invalid candidate', 'mobility-trailblazers'));
        }
        
        try {
            $service = new MT_Evaluation_Service();
            $evaluation = $service->get_evaluation($candidate_id, $jury_member_id);
            
            $this->success(array('evaluation' => $evaluation));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Export evaluations
     */
    public function export_evaluations() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        $filters = array(
            'category' => $this->get_param('category'),
            'status' => $this->get_param('status'),
            'date_from' => $this->get_param('date_from'),
            'date_to' => $this->get_param('date_to')
        );
        
        try {
            $service = new MT_Evaluation_Service();
            $export_url = $service->export($filters);
            
            if ($export_url) {
                $this->success(array('download_url' => $export_url));
            } else {
                $this->error(__('Error generating export', 'mobility-trailblazers'));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Get jury dashboard data
     */
    public function get_jury_dashboard_data() {
        $this->verify_nonce('mt_jury_nonce');
        $this->check_permission('mt_submit_evaluations');
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            $this->error(__('Jury member profile not found.', 'mobility-trailblazers'));
        }
        
        // Get assigned candidates
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        $total_assigned = count($assigned_candidates);
        
        // Debug information
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Jury Dashboard Debug - Jury Member ID: {$jury_member->ID}, Assigned Candidates: " . implode(', ', $assigned_candidates));
        }
        
        // Count evaluations
        $completed_evaluations = 0;
        $draft_evaluations = 0;
        
        foreach ($assigned_candidates as $candidate_id) {
            if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
                $completed_evaluations++;
            } elseif (mt_has_draft_evaluation($candidate_id, $jury_member->ID)) {
                $draft_evaluations++;
            }
        }
        
        // Calculate completion rate
        $completion_rate = $total_assigned > 0 ? round(($completed_evaluations / $total_assigned) * 100) : 0;
        
        // Build candidates data
        $candidates_data = array();
        foreach ($assigned_candidates as $candidate_id) {
            $candidate = get_post($candidate_id);
            if ($candidate) {
                $status = 'pending';
                if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
                    $status = 'completed';
                } elseif (mt_has_draft_evaluation($candidate_id, $jury_member->ID)) {
                    $status = 'draft';
                }
                
                // Get excerpt
                $excerpt = wp_trim_words($candidate->post_excerpt ?: $candidate->post_content, 20);
                
                // Get thumbnail
                $thumbnail = get_the_post_thumbnail_url($candidate_id, 'medium');
                
                // Get category
                $categories = wp_get_post_terms($candidate_id, 'mt_category', array('fields' => 'names'));
                $category = !empty($categories) ? $categories[0] : '';
                
                $candidates_data[] = array(
                    'id' => $candidate_id,
                    'title' => $candidate->post_title,
                    'excerpt' => $excerpt,
                    'thumbnail' => $thumbnail,
                    'company' => get_post_meta($candidate_id, '_mt_company_name', true),
                    'position' => get_post_meta($candidate_id, '_mt_position', true),
                    'category' => $category,
                    'status' => $status,
                    'evaluated_at' => mt_has_evaluated($candidate_id, $jury_member->ID) ? 
                        get_post_meta($candidate_id, '_mt_evaluated_at_' . $jury_member->ID, true) : null
                );
            }
        }
        
        $response_data = array(
            'stats' => array(
                'total_assigned' => $total_assigned,
                'completed' => $completed_evaluations,
                'drafts' => $draft_evaluations,
                'pending' => $total_assigned - $completed_evaluations - $draft_evaluations,
                'completion_rate' => $completion_rate
            ),
            'candidates' => $candidates_data
        );
        
        // Add debug info if no candidates found
        if ($total_assigned === 0) {
            // Check if there are any candidates in the system
            $total_candidates = wp_count_posts('mt_candidate')->publish;
            $total_jury = wp_count_posts('mt_jury_member')->publish;
            
            $response_data['debug'] = array(
                'total_candidates_in_system' => $total_candidates,
                'total_jury_members_in_system' => $total_jury,
                'message' => 'No candidates assigned to this jury member. Please contact an administrator to assign candidates.'
            );
        }
        
        $this->success($response_data);
    }
    
    /**
     * Get candidate evaluation data
     */
    public function get_candidate_evaluation() {
        $this->verify_nonce('mt_jury_nonce');
        $this->check_permission('mt_submit_evaluations');
        
        // Get candidate ID
        $candidate_id = $this->get_int_param('candidateId');
        if (!$candidate_id) {
            $this->error(__('Invalid candidate ID.', 'mobility-trailblazers'));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            $this->error(__('Jury member profile not found.', 'mobility-trailblazers'));
        }
        
        // Check if candidate is assigned
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        if (!in_array($candidate_id, $assigned_candidates)) {
            $this->error(__('This candidate is not assigned to you.', 'mobility-trailblazers'));
        }
        
        // Get candidate details
        $candidate = get_post($candidate_id);
        if (!$candidate) {
            $this->error(__('Candidate not found.', 'mobility-trailblazers'));
        }
        
        $response_data = array(
            'candidate' => array(
                'id' => $candidate_id,
                'title' => $candidate->post_title,
                'content' => wpautop($candidate->post_content),
                'company' => get_post_meta($candidate_id, '_mt_company_name', true),
                'position' => get_post_meta($candidate_id, '_mt_position', true),
                'website' => get_post_meta($candidate_id, '_mt_website', true),
                'linkedin' => get_post_meta($candidate_id, '_mt_linkedin', true),
                'achievement' => get_post_meta($candidate_id, '_mt_achievement', true),
                'impact' => get_post_meta($candidate_id, '_mt_impact', true),
                'vision' => get_post_meta($candidate_id, '_mt_vision', true)
            ),
            'evaluation' => null,
            'is_final' => false
        );
        
        // Check for existing evaluation
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_evaluations';
        
        $existing_evaluation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $jury_member->ID
        ));
        
        if ($existing_evaluation) {
            $response_data['evaluation'] = array(
                'courage' => intval($existing_evaluation->courage_score),
                'innovation' => intval($existing_evaluation->innovation_score),
                'implementation' => intval($existing_evaluation->implementation_score),
                'relevance' => intval($existing_evaluation->relevance_score),
                'visibility' => intval($existing_evaluation->visibility_score),
                'comments' => $existing_evaluation->notes,
                'total_score' => intval($existing_evaluation->total_score)
            );
            $response_data['is_final'] = true;
        } else {
            // Check for draft
            $draft = get_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id, true);
            if ($draft) {
                $response_data['evaluation'] = $draft;
            }
        }
        
        $this->success($response_data);
    }
    
    /**
     * Save evaluation (draft or final)
     */
    public function save_evaluation() {
        $this->verify_nonce('mt_jury_nonce');
        $this->check_permission('mt_submit_evaluations');
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            $this->error(__('Jury member profile not found.', 'mobility-trailblazers'));
        }
        
        // Validate input
        $candidate_id = $this->get_int_param('candidateId');
        if (!$candidate_id) {
            $this->error(__('Invalid candidate ID.', 'mobility-trailblazers'));
        }
        
        // Check assignment
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        if (!in_array($candidate_id, $assigned_candidates)) {
            $this->error(__('This candidate is not assigned to you.', 'mobility-trailblazers'));
        }
        
        // Get evaluation data
        $evaluation_data = array(
            'courage' => $this->get_int_param('courage'),
            'innovation' => $this->get_int_param('innovation'),
            'implementation' => $this->get_int_param('implementation'),
            'relevance' => $this->get_int_param('relevance'),
            'visibility' => $this->get_int_param('visibility'),
            'comments' => sanitize_textarea_field($this->get_param('comments', ''))
        );
        
        // Validate scores
        foreach ($evaluation_data as $key => $value) {
            if ($key !== 'comments' && ($value < 1 || $value > 10)) {
                $this->error(__('All scores must be between 1 and 10.', 'mobility-trailblazers'));
            }
        }
        
        $status = $this->get_param('status', 'draft');
        $total_score = array_sum(array_slice($evaluation_data, 0, 5));
        
        if ($status === 'draft') {
            // Save as draft
            $draft_data = array_merge($evaluation_data, array('total_score' => $total_score));
            update_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id, $draft_data);
            
            $this->success(array(), __('Draft saved successfully!', 'mobility-trailblazers'));
        } else {
            // Check if already evaluated
            global $wpdb;
            $table_name = $wpdb->prefix . 'mt_evaluations';
            
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_name WHERE candidate_id = %d AND jury_member_id = %d",
                $candidate_id,
                $jury_member->ID
            ));
            
            if ($existing) {
                $this->error(__('You have already evaluated this candidate.', 'mobility-trailblazers'));
            }
            
            // Save final evaluation
            $result = $wpdb->insert(
                $table_name,
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member->ID,
                    'user_id' => get_current_user_id(),
                    'courage_score' => $evaluation_data['courage'],
                    'innovation_score' => $evaluation_data['innovation'],
                    'implementation_score' => $evaluation_data['implementation'],
                    'relevance_score' => $evaluation_data['relevance'],
                    'visibility_score' => $evaluation_data['visibility'],
                    'total_score' => $total_score,
                    'notes' => $evaluation_data['comments'],
                    'status' => 'completed',
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                $this->error(__('Failed to save evaluation. Please try again.', 'mobility-trailblazers'));
            }
            
            // Remove draft if exists
            delete_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id);
            
            // Trigger action for other plugins
            do_action('mt_evaluation_submitted', $candidate_id, $jury_member->ID, $evaluation_data);
            
            $this->success(array(), __('Evaluation submitted successfully!', 'mobility-trailblazers'));
        }
    }
}