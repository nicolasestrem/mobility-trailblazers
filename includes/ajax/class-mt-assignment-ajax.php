<?php
/**
 * Assignment AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Services\MT_Assignment_Service;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Assignment_Ajax
 *
 * Handles AJAX requests for assignments
 */
class MT_Assignment_Ajax extends MT_Base_Ajax {
    
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    public function init() {
        // Admin actions
        add_action('wp_ajax_mt_get_jury_assignments', [$this, 'get_jury_assignments']);
        add_action('wp_ajax_mt_get_unassigned_candidates', [$this, 'get_unassigned_candidates']);
        add_action('wp_ajax_mt_create_assignment', [$this, 'create_assignment']);
        add_action('wp_ajax_mt_remove_assignment', [$this, 'remove_assignment']);
        add_action('wp_ajax_mt_delete_assignment', [$this, 'delete_assignment']);
        add_action('wp_ajax_mt_bulk_assign', [$this, 'bulk_assign']);
        add_action('wp_ajax_mt_bulk_create_assignments', [$this, 'bulk_create_assignments']);
        add_action('wp_ajax_mt_clear_all_assignments', [$this, 'clear_all_assignments']);
        add_action('wp_ajax_mt_export_assignments', [$this, 'export_assignments']);
    }
    
    /**
     * Get assignments for a jury member
     *
     * @return void
     */
    public function get_jury_assignments() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $jury_member_id = $this->get_int_param('jury_member_id');
        if (!$jury_member_id) {
            $this->error(__('Invalid jury member ID.', 'mobility-trailblazers'));
        }
        
        $assignment_repo = new MT_Assignment_Repository();
        $assignments = $assignment_repo->get_by_jury_member($jury_member_id);
        
        $this->success([
            'assignments' => $assignments,
            'count' => count($assignments)
        ]);
    }
    
    /**
     * Get unassigned candidates
     *
     * @return void
     */
    public function get_unassigned_candidates() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $assignment_repo = new MT_Assignment_Repository();
        $candidates = $assignment_repo->get_unassigned_candidates();
        
        $this->success([
            'candidates' => $candidates,
            'count' => count($candidates)
        ]);
    }
    
    /**
     * Create single assignment
     *
     * @return void
     */
    public function create_assignment() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $data = [
            'jury_member_id' => $this->get_int_param('jury_member_id'),
            'candidate_id' => $this->get_int_param('candidate_id')
        ];
        
        $service = new MT_Assignment_Service();
        $result = $service->process($data);
        
        if ($result) {
            $this->success(
                null,
                __('Assignment created successfully.', 'mobility-trailblazers')
            );
        } else {
            $this->error(
                __('Failed to create assignment.', 'mobility-trailblazers'),
                ['errors' => $service->get_errors()]
            );
        }
    }
    
    /**
     * Remove assignment
     *
     * @return void
     */
    public function remove_assignment() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $jury_member_id = $this->get_int_param('jury_member_id');
        $candidate_id = $this->get_int_param('candidate_id');
        
        $service = new MT_Assignment_Service();
        $result = $service->remove_assignment($jury_member_id, $candidate_id);
        
        if ($result) {
            $this->success(
                null,
                __('Assignment removed successfully.', 'mobility-trailblazers')
            );
        } else {
            $this->error(
                __('Failed to remove assignment.', 'mobility-trailblazers'),
                ['errors' => $service->get_errors()]
            );
        }
    }
    
    /**
     * Delete a single assignment
     *
     * @return void
     */
    public function delete_assignment() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $assignment_id = $this->get_int_param('assignment_id');
        if (!$assignment_id) {
            $this->error(__('Invalid assignment ID.', 'mobility-trailblazers'));
        }
        
        $assignment_repo = new MT_Assignment_Repository();
        $result = $assignment_repo->delete($assignment_id);
        
        if ($result) {
            $this->success(__('Assignment removed successfully.', 'mobility-trailblazers'));
        } else {
            $this->error(__('Failed to remove assignment.', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Bulk assign candidates
     *
     * @return void
     */
    public function bulk_assign() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_assignments');
        
        $jury_member_id = $this->get_int_param('jury_member_id');
        $candidate_ids = $this->get_array_param('candidate_ids');
        
        if (!$jury_member_id || empty($candidate_ids)) {
            $this->error(__('Invalid parameters.', 'mobility-trailblazers'));
        }
        
        $assignment_repo = new MT_Assignment_Repository();
        $assignments = [];
        
        foreach ($candidate_ids as $candidate_id) {
            $assignments[] = [
                'jury_member_id' => $jury_member_id,
                'candidate_id' => intval($candidate_id)
            ];
        }
        
        $created = $assignment_repo->bulk_create($assignments);
        
        if ($created > 0) {
            $this->success(
                ['created' => $created],
                sprintf(
                    __('%d assignments created successfully.', 'mobility-trailblazers'),
                    $created
                )
            );
        } else {
            $this->error(__('No assignments were created. They may already exist.', 'mobility-trailblazers'));
        }
    }

    /**
     * Handle bulk assignment creation
     */
    public function bulk_create_assignments() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            $this->send_json_error(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            $this->send_json_error(__('You do not have permission to manage assignments.', 'mobility-trailblazers'));
        }
        
        $jury_member_id = $this->get_int_param('jury_member_id');
        $candidate_ids = $this->get_param('candidate_ids', array());
        
        if (!$jury_member_id || empty($candidate_ids)) {
            $this->send_json_error(__('Invalid data provided.', 'mobility-trailblazers'));
        }
        
        $assignment_service = new MT_Assignment_Service();
        $created = 0;
        $errors = 0;
        
        foreach ($candidate_ids as $candidate_id) {
            $result = $assignment_service->create_assignment($jury_member_id, intval($candidate_id));
            if ($result) {
                $created++;
            } else {
                $errors++;
            }
        }
        
        if ($created > 0) {
            $message = sprintf(
                __('%d assignments created successfully.', 'mobility-trailblazers'),
                $created
            );
            
            if ($errors > 0) {
                $message .= ' ' . sprintf(
                    __('%d assignments could not be created (may already exist).', 'mobility-trailblazers'),
                    $errors
                );
            }
            
            $this->send_json_success($message);
        } else {
            $this->send_json_error(__('No assignments could be created.', 'mobility-trailblazers'));
        }
    }

    /**
     * Handle clearing all assignments
     */
    public function clear_all_assignments() {
        // Verify nonce
        if (!$this->verify_nonce()) {
            $this->send_json_error(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions - only administrators
        if (!current_user_can('manage_options')) {
            $this->send_json_error(__('Only administrators can clear all assignments.', 'mobility-trailblazers'));
        }
        
        $assignment_repo = new MT_Assignment_Repository();
        
        // Use proper method to delete all
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_jury_assignments';
        $result = $wpdb->query("TRUNCATE TABLE $table_name");
        
        if ($result !== false) {
            $this->send_json_success(__('All assignments have been cleared.', 'mobility-trailblazers'));
        } else {
            $this->send_json_error(__('Failed to clear assignments.', 'mobility-trailblazers'));
        }
    }

    /**
     * Export assignments to CSV
     */
    public function export_assignments() {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
        if (!wp_verify_nonce($nonce, 'mt_admin_nonce')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_export_data')) {
            wp_die(__('You do not have permission to export data.', 'mobility-trailblazers'));
        }
        
        $assignment_repo = new MT_Assignment_Repository();
        $assignments = $assignment_repo->find_all();
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=assignments-' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, array(
            __('Jury Member', 'mobility-trailblazers'),
            __('Email', 'mobility-trailblazers'),
            __('Candidate', 'mobility-trailblazers'),
            __('Category', 'mobility-trailblazers'),
            __('Assigned Date', 'mobility-trailblazers'),
            __('Evaluation Status', 'mobility-trailblazers')
        ));
        
        // Add data
        foreach ($assignments as $assignment) {
            $jury = get_post($assignment->jury_member_id);
            $candidate = get_post($assignment->candidate_id);
            $user = get_user_by('ID', get_post_meta($assignment->jury_member_id, '_mt_user_id', true));
            
            $categories = wp_get_post_terms($assignment->candidate_id, 'mt_category');
            $category_name = !empty($categories) ? $categories[0]->name : '';
            
            // Check if evaluation exists
            $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
            $evaluation = $evaluation_repo->get_by_jury_and_candidate($assignment->jury_member_id, $assignment->candidate_id);
            $status = $evaluation ? __('Completed', 'mobility-trailblazers') : __('Pending', 'mobility-trailblazers');
            
            fputcsv($output, array(
                $jury ? $jury->post_title : '',
                $user ? $user->user_email : '',
                $candidate ? $candidate->post_title : '',
                $category_name,
                date('Y-m-d', strtotime($assignment->created_at)),
                $status
            ));
        }
        
        fclose($output);
        exit;
    }
} 