<?php
/**
 * Assignment AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Services\MT_Assignment_Service;

class MT_Assignment_Ajax extends MT_Base_Ajax {
    
    /**
     * Register AJAX hooks
     */
    protected function register_hooks() {
        add_action('wp_ajax_mt_assign_candidates', array($this, 'assign_candidates'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'auto_assign'));
        add_action('wp_ajax_mt_remove_assignment', array($this, 'remove_assignment'));
        add_action('wp_ajax_mt_get_assignment_stats', array($this, 'get_assignment_stats'));
        add_action('wp_ajax_mt_manual_assign', array($this, 'manual_assign'));
        add_action('wp_ajax_mt_clear_assignments', array($this, 'clear_assignments'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'export_assignments'));
    }
    
    /**
     * Auto-assign candidates to jury members
     */
    public function auto_assign() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $options = array(
            'algorithm' => $this->get_param('algorithm', 'balanced'),
            'candidates_per_jury' => $this->get_int_param('candidates_per_jury', 5),
            'preserve_existing' => $this->get_param('preserve_existing') === 'true'
        );
        
        try {
            $service = new MT_Assignment_Service();
            $result = $service->auto_assign($options);
            
            if ($result['success']) {
                $this->success(
                    $result,
                    sprintf(
                        __('%d assignments created successfully', 'mobility-trailblazers'),
                        $result['created']
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
     * Manual assignment
     */
    public function manual_assign() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $candidate_id = $this->get_int_param('candidate_id');
        $jury_ids = array_map('intval', $this->get_array_param('jury_ids', array()));
        
        if (!$candidate_id || empty($jury_ids)) {
            $this->error(__('Please select candidate and jury members', 'mobility-trailblazers'));
        }
        
        try {
            $service = new MT_Assignment_Service();
            $result = $service->manual_assign($candidate_id, $jury_ids);
            
            if ($result['success']) {
                $this->success(
                    $result,
                    __('Assignments created successfully', 'mobility-trailblazers')
                );
            } else {
                $this->error($result['message']);
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Remove assignment
     */
    public function remove_assignment() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $assignment_id = $this->get_int_param('assignment_id');
        
        if (!$assignment_id) {
            $this->error(__('Invalid assignment', 'mobility-trailblazers'));
        }
        
        try {
            $service = new MT_Assignment_Service();
            $result = $service->remove($assignment_id);
            
            if ($result) {
                $this->success(null, __('Assignment removed successfully', 'mobility-trailblazers'));
            } else {
                $this->error(__('Error removing assignment', 'mobility-trailblazers'));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Get assignment statistics
     */
    public function get_assignment_stats() {
        $this->verify_nonce('mt_ajax_nonce');
        
        try {
            $service = new MT_Assignment_Service();
            $stats = $service->get_statistics();
            
            $this->success($stats);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Clear all assignments
     */
    public function clear_assignments() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $confirm = $this->get_param('confirm');
        
        if ($confirm !== 'DELETE') {
            $this->error(__('Please type DELETE to confirm', 'mobility-trailblazers'));
        }
        
        try {
            $service = new MT_Assignment_Service();
            $result = $service->clear_all();
            
            if ($result) {
                $this->success(null, __('All assignments cleared successfully', 'mobility-trailblazers'));
            } else {
                $this->error(__('Error clearing assignments', 'mobility-trailblazers'));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
    
    /**
     * Export assignments
     */
    public function export_assignments() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        try {
            $service = new MT_Assignment_Service();
            $export_url = $service->export();
            
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