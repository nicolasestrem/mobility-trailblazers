<?php
/**
 * Voting AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Services\MT_Voting_Service;

class MT_Voting_Ajax extends MT_Base_Ajax {
    
    /**
     * Register AJAX hooks
     */
    protected function register_hooks() {
        // Public voting
        add_action('wp_ajax_mt_submit_public_vote', array($this, 'submit_public_vote'));
        add_action('wp_ajax_nopriv_mt_submit_public_vote', array($this, 'submit_public_vote'));
        
        // Admin voting management
        add_action('wp_ajax_mt_get_voting_results', array($this, 'get_voting_results'));
        add_action('wp_ajax_mt_reset_votes', array($this, 'reset_votes'));
        add_action('wp_ajax_mt_export_votes', array($this, 'export_votes'));
        
        // Vote reset handlers
        add_action('wp_ajax_mt_reset_individual', array($this, 'reset_individual_vote'));
        add_action('wp_ajax_mt_reset_bulk_candidate', array($this, 'reset_candidate_votes'));
        add_action('wp_ajax_mt_reset_bulk_jury', array($this, 'reset_jury_votes'));
        add_action('wp_ajax_mt_reset_phase_transition', array($this, 'reset_phase_transition'));
        add_action('wp_ajax_mt_reset_full_system', array($this, 'reset_full_system'));
    }
    
    /**
     * Submit public vote
     */
    public function submit_public_vote() {
        $this->verify_nonce('mt_public_voting_nonce');
        
        $candidate_id = $this->get_int_param('candidate_id');
        $voter_data = array(
            'email' => sanitize_email($this->get_param('email', '')),
            'name' => sanitize_text_field($this->get_param('name', '')),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        );
        
        if (!$candidate_id) {
            $this->error(__('Please select a candidate', 'mobility-trailblazers'));
        }
        
        if (!is_email($voter_data['email'])) {
            $this->error(__('Please provide a valid email address', 'mobility-trailblazers'));
        }
        
        try {
            $service = new MT_Voting_Service();
            $result = $service->submit_vote($candidate_id, $voter_data);
            
            if ($result['success']) {
                $this->success(
                    array('vote_id' => $result['vote_id']),
                    __('Thank you for voting!', 'mobility-trailblazers')
                );
            } else {
                $this->error($result['message']);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Get voting results
     */
    public function get_voting_results() {
        $this->verify_nonce('mt_ajax_nonce');
        $this->check_permission('mt_view_results');
        
        $category = $this->get_param('category');
        
        try {
            $service = new MT_Voting_Service();
            $results = $service->get_results($category);
            
            $this->success(array('results' => $results));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Reset individual vote
     */
    public function reset_individual_vote() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_votes');
        
        $vote_id = $this->get_int_param('vote_id');
        $reason = sanitize_text_field($this->get_param('reason', ''));
        
        if (!$vote_id || !$reason) {
            $this->error(__('Please provide vote ID and reason', 'mobility-trailblazers'));
        }
        
        try {
            $service = new MT_Voting_Service();
            $result = $service->reset_vote($vote_id, $reason);
            
            if ($result) {
                $this->success(null, __('Vote reset successfully', 'mobility-trailblazers'));
            } else {
                $this->error(__('Error resetting vote', 'mobility-trailblazers'));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Reset candidate votes
     */
    public function reset_candidate_votes() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_votes');
        
        $candidate_id = $this->get_int_param('candidate_id');
        $options = array(
            'reason' => sanitize_text_field($this->get_param('reason', '')),
            'create_backup' => $this->get_param('create_backup') === 'true',
            'notify' => $this->get_param('notify') === 'true'
        );
        
        if (!$candidate_id || !$options['reason']) {
            $this->error(__('Please provide candidate and reason', 'mobility-trailblazers'));
        }
        
        try {
            $service = new MT_Voting_Service();
            $result = $service->reset_candidate_votes($candidate_id, $options);
            
            if ($result['success']) {
                $this->success(
                    $result,
                    sprintf(
                        __('%d votes reset successfully', 'mobility-trailblazers'),
                        $result['count']
                    )
                );
            } else {
                $this->error($result['message']);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Export votes
     */
    public function export_votes() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        $filters = array(
            'category' => $this->get_param('category'),
            'date_from' => $this->get_param('date_from'),
            'date_to' => $this->get_param('date_to'),
            'type' => $this->get_param('type', 'public')
        );
        
        try {
            $service = new MT_Voting_Service();
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