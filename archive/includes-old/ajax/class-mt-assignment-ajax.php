<?php
/**
 * Assignment AJAX Handler - FIXED VERSION
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
        // First check if this is an AJAX request
        if (!wp_doing_ajax()) {
            return;
        }
        
        // Verify nonce - using 'mt_ajax_nonce' to match the JavaScript
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
            return;
        }
        
        // Check permissions - use manage_options instead of custom capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments', 'mobility-trailblazers')));
            return;
        }
        
        // Prepare data in the format expected by the service's process() method
        $data = array(
            'assignment_type' => 'auto',
            'candidates_per_jury' => isset($_POST['candidates_per_jury']) ? intval($_POST['candidates_per_jury']) : 5,
            'clear_existing' => isset($_POST['preserve_existing']) && $_POST['preserve_existing'] === 'true' ? false : true
        );
        
        try {
            $service = new MT_Assignment_Service();
            $result = $service->process($data);
            
            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Auto-assignment completed successfully', 'mobility-trailblazers'),
                    'created' => is_array($result) ? count($result) : 0
                ));
            } else {
                $errors = $service->get_errors();
                wp_send_json_error(array(
                    'message' => !empty($errors) ? implode(', ', $errors) : __('Failed to auto-assign candidates', 'mobility-trailblazers')
                ));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Manual assignment
     */
    public function manual_assign() {
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'mobility-trailblazers')));
            return;
        }
        
        $candidate_id = isset($_POST['candidateId']) ? intval($_POST['candidateId']) : 0;
        $jury_ids = isset($_POST['jury_ids']) && is_array($_POST['jury_ids']) ? array_map('intval', $_POST['jury_ids']) : array();
        
        if (!$candidate_id || empty($jury_ids)) {
            wp_send_json_error(array('message' => __('Please select candidate and jury members', 'mobility-trailblazers')));
            return;
        }
        
        try {
            $service = new MT_Assignment_Service();
            
            // Process each jury member assignment
            $success_count = 0;
            foreach ($jury_ids as $jury_id) {
                $result = $service->create_assignment($jury_id, $candidate_id);
                if ($result) {
                    $success_count++;
                }
            }
            
            if ($success_count > 0) {
                wp_send_json_success(array(
                    'message' => sprintf(__('Successfully assigned to %d jury members', 'mobility-trailblazers'), $success_count)
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to create assignments', 'mobility-trailblazers')));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Remove assignment
     */
    public function remove_assignment() {
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'mobility-trailblazers')));
            return;
        }
        
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        $candidate_id = isset($_POST['candidateId']) ? intval($_POST['candidateId']) : 0;
        
        if (!$jury_member_id || !$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid assignment data', 'mobility-trailblazers')));
            return;
        }
        
        try {
            $service = new MT_Assignment_Service();
            $result = $service->remove_assignment($jury_member_id, $candidate_id);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Assignment removed successfully', 'mobility-trailblazers')));
            } else {
                wp_send_json_error(array('message' => __('Failed to remove assignment', 'mobility-trailblazers')));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Get assignment statistics
     */
    public function get_assignment_stats() {
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
            return;
        }
        
        try {
            $service = new MT_Assignment_Service();
            $stats = $service->get_statistics();
            
            wp_send_json_success($stats);
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Clear all assignments
     */
    public function clear_assignments() {
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'mobility-trailblazers')));
            return;
        }
        
        try {
            $service = new MT_Assignment_Service();
            $result = $service->clear_all_assignments();
            
            if ($result) {
                wp_send_json_success(array('message' => __('All assignments cleared successfully', 'mobility-trailblazers')));
            } else {
                wp_send_json_error(array('message' => __('Failed to clear assignments', 'mobility-trailblazers')));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
    
    /**
     * Export assignments
     */
    public function export_assignments() {
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'mobility-trailblazers')));
            return;
        }
        
        try {
            $service = new MT_Assignment_Service();
            $assignments = $service->get_all_assignments_for_export();
            
            $csv_data = array();
            $csv_data[] = array('Jury Member', 'Email', 'Candidate', 'Category', 'Assigned Date');
            
            foreach ($assignments as $assignment) {
                $csv_data[] = array(
                    $assignment['jury_name'],
                    $assignment['jury_email'],
                    $assignment['candidate_name'],
                    $assignment['category'],
                    $assignment['assigned_date']
                );
            }
            
            // Generate CSV content
            $output = fopen('php://temp', 'r+');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
            
            foreach ($csv_data as $row) {
                fputcsv($output, $row);
            }
            
            rewind($output);
            $csv_content = stream_get_contents($output);
            fclose($output);
            
            wp_send_json_success(array(
                'filename' => 'assignments-' . date('Y-m-d') . '.csv',
                'content' => base64_encode($csv_content),
                'message' => sprintf(__('Exported %d assignments', 'mobility-trailblazers'), count($assignments))
            ));
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }
}