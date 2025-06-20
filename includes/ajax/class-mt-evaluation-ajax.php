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
}