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
        add_action('wp_ajax_mt_bulk_assign', [$this, 'bulk_assign']);
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
} 