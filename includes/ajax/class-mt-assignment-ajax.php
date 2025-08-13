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
        add_action('wp_ajax_mt_bulk_create_assignments', [$this, 'bulk_create_assignments']);
        add_action('wp_ajax_mt_manual_assign', [$this, 'manual_assign']); // Add this handler
        add_action('wp_ajax_mt_clear_all_assignments', [$this, 'clear_all_assignments']);
        add_action('wp_ajax_mt_auto_assign', [$this, 'auto_assign']);
        
        // Bulk operations
        add_action('wp_ajax_mt_bulk_remove_assignments', [$this, 'bulk_remove_assignments']);
        add_action('wp_ajax_mt_bulk_reassign_assignments', [$this, 'bulk_reassign_assignments']);
        add_action('wp_ajax_mt_bulk_export_assignments', [$this, 'bulk_export_assignments']);
        
        // Add test handler
        add_action('wp_ajax_mt_test', [$this, 'test_handler']);
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
        
        $assignment_id = $this->get_int_param('assignment_id');
        if (!$assignment_id) {
            $this->error(__('Invalid assignment ID.', 'mobility-trailblazers'));
            return;
        }
        
        // Use the service method for consistency and efficiency
        // The service handles all the audit logging and validation
        $assignment_service = new MT_Assignment_Service();
        $result = $assignment_service->remove_by_id($assignment_id);
        
        if ($result) {
            $this->success(__('Assignment removed successfully.', 'mobility-trailblazers'));
        } else {
            $errors = $assignment_service->get_errors();
            $this->error(!empty($errors) ? $errors[0] : __('Failed to remove assignment.', 'mobility-trailblazers'));
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
     * Handle manual assignment of candidates to a jury member
     */
    public function manual_assign() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MT: Manual assignment request by user ' . get_current_user_id());
        }
        
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        $this->check_permission('mt_manage_assignments');
        
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        $candidate_ids = isset($_POST['candidate_ids']) && is_array($_POST['candidate_ids']) 
            ? array_map('intval', $_POST['candidate_ids']) 
            : array();
        
        error_log('MT Manual Assignment: jury_member_id=' . $jury_member_id);
        error_log('MT Manual Assignment: candidate_ids=' . print_r($candidate_ids, true));
        
        if (!$jury_member_id || empty($candidate_ids)) {
            $this->error(__('Please select a jury member and at least one candidate.', 'mobility-trailblazers'));
            return;
        }
        
        $assignment_repo = new MT_Assignment_Repository();
        $created = 0;
        $errors = 0;
        $already_exists = 0;
        
        foreach ($candidate_ids as $candidate_id) {
            // Check if assignment already exists
            $existing = $assignment_repo->get_by_jury_and_candidate($jury_member_id, $candidate_id);
            
            if ($existing) {
                $already_exists++;
                continue;
            }
            
            // Create the assignment
            $result = $assignment_repo->create([
                'jury_member_id' => $jury_member_id,
                'candidate_id' => $candidate_id
            ]);
            
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
            
            if ($already_exists > 0) {
                $message .= ' ' . sprintf(
                    __('%d assignments already existed.', 'mobility-trailblazers'),
                    $already_exists
                );
            }
            
            if ($errors > 0) {
                $message .= ' ' . sprintf(
                    __('%d assignments failed.', 'mobility-trailblazers'),
                    $errors
                );
            }
            
            $this->success(['message' => $message]);
        } else if ($already_exists > 0) {
            $this->error(__('All selected assignments already exist.', 'mobility-trailblazers'));
        } else {
            $this->error(__('Failed to create assignments.', 'mobility-trailblazers'));
        }
    }

    /**
     * Handle bulk assignment creation
     */
    public function bulk_create_assignments() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('MT: Manual assignment request by user ' . get_current_user_id());
        }
        
        // Verify nonce - use mt_admin_nonce for admin actions
        if (!$this->verify_nonce('mt_admin_nonce')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MT: Manual assignment nonce verification failed for user ' . get_current_user_id());
            }
            $this->error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('MT: Manual assignment permission denied for user ' . get_current_user_id());
            }
            $this->error(__('You do not have permission to manage assignments.', 'mobility-trailblazers'));
            return;
        }
        
        $jury_member_id = $this->get_int_param('jury_member_id');
        // Get the raw array from $_POST to avoid sanitization issues
        $candidate_ids = isset($_POST['candidate_ids']) && is_array($_POST['candidate_ids']) 
            ? array_map('intval', $_POST['candidate_ids']) 
            : array();
        
        error_log('MT Manual Assignment: jury_member_id=' . $jury_member_id);
        error_log('MT Manual Assignment: candidate_ids=' . print_r($candidate_ids, true));
        
        if (!$jury_member_id || empty($candidate_ids)) {
            error_log('MT Manual Assignment: Invalid data - jury_member_id or candidate_ids empty');
            $this->error(__('Invalid data provided.', 'mobility-trailblazers'));
            return;
        }
        
        $assignment_service = new MT_Assignment_Service();
        $assignment_repo = new MT_Assignment_Repository();
        $created = 0;
        $errors = 0;
        
        // Convert candidate_ids array to assignments array
        $assignments = [];
        foreach ($candidate_ids as $candidate_id) {
            $assignments[] = [
                'jury_member_id' => $jury_member_id,
                'candidate_id' => intval($candidate_id)
            ];
        }
        
        // Use bulk_create directly on repository
        $created = $assignment_repo->bulk_create($assignments);
        
        if ($created > 0) {
            $message = sprintf(
                __('%d assignments created successfully.', 'mobility-trailblazers'),
                $created
            );
            
            // Calculate errors
            $errors = count($candidate_ids) - $created;
            if ($errors > 0) {
                $message .= ' ' . sprintf(
                    __('%d assignments could not be created (may already exist).', 'mobility-trailblazers'),
                    $errors
                );
            }
            
            $this->success(null, $message);
        } else {
            $this->error(__('No assignments could be created. They may already exist.', 'mobility-trailblazers'));
        }
    }

    /**
     * Handle clearing all assignments
     */
    public function clear_all_assignments() {
        // Verify nonce - use mt_admin_nonce like other methods
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions - requires manage_settings capability
        $this->check_permission('mt_manage_settings');
        
        $assignment_repo = new MT_Assignment_Repository();
        
        // Use repository method to clear all assignments
        // This properly handles cache clearing and any future hooks
        $result = $assignment_repo->clear_all();
        
        if ($result) {
            $this->success(null, __('All assignments have been cleared.', 'mobility-trailblazers'));
        } else {
            $this->error(__('Failed to clear assignments.', 'mobility-trailblazers'));
        }
    }

    /**
     * Handle auto assignment AJAX request
     *
     * @return void
     */
    public function auto_assign() {
        // Verify nonce using base class method
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        $this->check_permission('mt_manage_assignments');
        
        // Get parameters
        $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : 'balanced';
        $candidates_per_jury = isset($_POST['candidates_per_jury']) ? intval($_POST['candidates_per_jury']) : 5;
        
        // Log for debugging
        error_log('MT Auto Assign: Starting auto-assignment');
        error_log('MT Auto Assign: method=' . $method . ', candidates_per_jury=' . $candidates_per_jury);
        
        // Get active jury members
        $jury_args = [
            'post_type' => 'mt_jury_member',
            'post_status' => 'publish',
            'numberposts' => -1
        ];
        $jury_members = get_posts($jury_args);
        
        error_log('MT Auto Assign: Found ' . count($jury_members) . ' jury members');
        
        if (empty($jury_members)) {
            $this->error(__('No jury members found.', 'mobility-trailblazers'));
            return;
        }
        
        // Get candidates
        $candidate_args = [
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'numberposts' => -1
        ];
        $candidates = get_posts($candidate_args);
        
        error_log('MT Auto Assign: Found ' . count($candidates) . ' candidates');
        
        if (empty($candidates)) {
            $this->error(__('No candidates found.', 'mobility-trailblazers'));
            return;
        }
        
        // Initialize repository
        $assignment_repo = new MT_Assignment_Repository();
        
        // Clear existing assignments if requested
        if (isset($_POST['clear_existing']) && $_POST['clear_existing'] === 'true') {
            $clear_result = $assignment_repo->clear_all();
            if ($clear_result) {
                error_log('MT Auto Assign: Cleared all existing assignments');
            } else {
                error_log('MT Auto Assign: Failed to clear existing assignments');
            }
        }
        $assignments_created = 0;
        $errors = [];
        
        // Log distribution method being used
        error_log('MT Auto Assign: Using distribution method: ' . $method);
        
        // Perform assignment based on method
        if ($method === 'balanced') {
            // BALANCED DISTRIBUTION
            // Goal: Each candidate should be reviewed by roughly the same number of jury members
            // while each jury member reviews exactly candidates_per_jury candidates
            
            $jury_count = count($jury_members);
            $candidate_count = count($candidates);
            
            // Calculate how many times each candidate should be reviewed
            $total_assignments_needed = $jury_count * $candidates_per_jury;
            $reviews_per_candidate = floor($total_assignments_needed / $candidate_count);
            $extra_reviews = $total_assignments_needed % $candidate_count;
            
            error_log('MT Auto Assign: Balanced - Total assignments needed: ' . $total_assignments_needed);
            error_log('MT Auto Assign: Balanced - Reviews per candidate: ' . $reviews_per_candidate);
            error_log('MT Auto Assign: Balanced - Extra reviews to distribute: ' . $extra_reviews);
            
            // Create an array to track how many times each candidate is assigned
            $candidate_assignment_count = [];
            foreach ($candidates as $candidate) {
                $candidate_assignment_count[$candidate->ID] = 0;
            }
            
            // Track existing assignments if we're not clearing them
            if (!isset($_POST['clear_existing']) || $_POST['clear_existing'] !== 'true') {
                // Get all existing assignments
                $existing_assignments = $assignment_repo->find_all();
                foreach ($existing_assignments as $assignment) {
                    if (isset($candidate_assignment_count[$assignment->candidate_id])) {
                        $candidate_assignment_count[$assignment->candidate_id]++;
                    }
                }
            }
            
            // Create assignments for each jury member
            foreach ($jury_members as $jury_member) {
                $jury_assignments = 0;
                
                // Get existing assignments for this jury member if not clearing
                $existing_for_jury = [];
                if (!isset($_POST['clear_existing']) || $_POST['clear_existing'] !== 'true') {
                    $existing_jury_assignments = $assignment_repo->get_by_jury_member($jury_member->ID);
                    foreach ($existing_jury_assignments as $existing) {
                        $existing_for_jury[$existing->candidate_id] = true;
                        $jury_assignments++;
                    }
                }
                
                // Sort candidates by assignment count (ascending) to prioritize those with fewer assignments
                $sorted_candidates = $candidates;
                usort($sorted_candidates, function($a, $b) use ($candidate_assignment_count) {
                    return $candidate_assignment_count[$a->ID] - $candidate_assignment_count[$b->ID];
                });
                
                // Assign candidates to this jury member
                foreach ($sorted_candidates as $candidate) {
                    // Stop if we've reached the desired number of assignments for this jury member
                    if ($jury_assignments >= $candidates_per_jury) {
                        break;
                    }
                    
                    // Skip if this assignment already exists
                    if (isset($existing_for_jury[$candidate->ID])) {
                        continue;
                    }
                    
                    // Create the assignment
                    $result = $assignment_repo->create([
                        'jury_member_id' => $jury_member->ID,
                        'candidate_id' => $candidate->ID
                    ]);
                    
                    if ($result) {
                        $assignments_created++;
                        $jury_assignments++;
                        $candidate_assignment_count[$candidate->ID]++;
                        
                        error_log('MT Auto Assign: Assigned candidate ' . $candidate->ID . ' to jury ' . $jury_member->ID);
                    } else {
                        $errors[] = sprintf(
                            __('Failed to assign %s to %s', 'mobility-trailblazers'),
                            $candidate->post_title,
                            $jury_member->post_title
                        );
                        error_log('MT Auto Assign: Failed to assign candidate ' . $candidate->ID . ' to jury ' . $jury_member->ID);
                    }
                }
                
                // Log if jury member didn't get enough assignments
                if ($jury_assignments < $candidates_per_jury) {
                    error_log('MT Auto Assign: Warning - Jury member ' . $jury_member->ID . ' only got ' . $jury_assignments . ' assignments (requested: ' . $candidates_per_jury . ')');
                }
            }
            
        } else {
            // RANDOM DISTRIBUTION
            // Goal: Each jury member gets exactly candidates_per_jury candidates, selected randomly
            
            error_log('MT Auto Assign: Random - Starting random distribution');
            
            // Shuffle the candidates array once for efficiency
            $shuffled_candidates = $candidates;
            shuffle($shuffled_candidates);
            
            error_log('MT Auto Assign: Random - Shuffled ' . count($shuffled_candidates) . ' candidates');
            
            // Process each jury member
            foreach ($jury_members as $jury_member) {
                $jury_assignments = 0;
                $candidates_checked = 0;
                
                // Get existing assignments for this jury member if not clearing
                $existing_for_jury = [];
                if (!isset($_POST['clear_existing']) || $_POST['clear_existing'] !== 'true') {
                    $existing_jury_assignments = $assignment_repo->get_by_jury_member($jury_member->ID);
                    foreach ($existing_jury_assignments as $existing) {
                        $existing_for_jury[$existing->candidate_id] = true;
                        $jury_assignments++;
                    }
                    
                    error_log('MT Auto Assign: Random - Jury ' . $jury_member->ID . ' has ' . count($existing_for_jury) . ' existing assignments');
                }
                
                // Try to assign candidates from the shuffled list
                foreach ($shuffled_candidates as $candidate) {
                    $candidates_checked++;
                    
                    // Stop if we've reached the desired number of assignments
                    if ($jury_assignments >= $candidates_per_jury) {
                        break;
                    }
                    
                    // Skip if this assignment already exists
                    if (isset($existing_for_jury[$candidate->ID])) {
                        continue;
                    }
                    
                    // Create the assignment
                    $result = $assignment_repo->create([
                        'jury_member_id' => $jury_member->ID,
                        'candidate_id' => $candidate->ID
                    ]);
                    
                    if ($result) {
                        $assignments_created++;
                        $jury_assignments++;
                        
                        error_log('MT Auto Assign: Random - Assigned candidate ' . $candidate->ID . ' to jury ' . $jury_member->ID);
                    } else {
                        $errors[] = sprintf(
                            __('Failed to assign %s to %s', 'mobility-trailblazers'),
                            $candidate->post_title,
                            $jury_member->post_title
                        );
                        error_log('MT Auto Assign: Random - Failed to assign candidate ' . $candidate->ID . ' to jury ' . $jury_member->ID);
                    }
                }
                
                // Log statistics for this jury member
                error_log('MT Auto Assign: Random - Jury ' . $jury_member->ID . ' got ' . $jury_assignments . ' assignments after checking ' . $candidates_checked . ' candidates');
                
                // Warn if jury member didn't get enough assignments
                if ($jury_assignments < $candidates_per_jury) {
                    $warning_msg = sprintf(
                        __('Jury member %s only received %d assignments (requested: %d). Not enough available candidates.', 'mobility-trailblazers'),
                        $jury_member->post_title,
                        $jury_assignments,
                        $candidates_per_jury
                    );
                    $errors[] = $warning_msg;
                    error_log('MT Auto Assign: Random - Warning: ' . $warning_msg);
                }
            }
        }
        
        // Log final statistics
        error_log('MT Auto Assign: Completed - ' . $assignments_created . ' assignments created, ' . count($errors) . ' errors');
        
        // Prepare response
        if ($assignments_created > 0) {
            $message = sprintf(
                __('Auto-assignment completed successfully. %d assignments created.', 'mobility-trailblazers'),
                $assignments_created
            );
            
            if (!empty($errors)) {
                $message .= ' ' . sprintf(
                    __('However, %d issues occurred.', 'mobility-trailblazers'),
                    count($errors)
                );
            }
            
            $this->success([
                'created' => $assignments_created,
                'errors' => $errors
            ], $message);
        } else {
            if (empty($errors)) {
                $this->error(__('No new assignments were created. All candidates may already be assigned.', 'mobility-trailblazers'));
            } else {
                $this->error(__('Auto-assignment failed.', 'mobility-trailblazers'), [
                    'errors' => $errors
                ]);
            }
        }
    }

    /**
     * Handle bulk removal of assignments
     *
     * @return void
     */
    public function bulk_remove_assignments() {
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        // Get assignment IDs
        $assignment_ids = isset($_POST['assignment_ids']) && is_array($_POST['assignment_ids']) 
            ? array_map('intval', $_POST['assignment_ids']) 
            : array();
        
        if (empty($assignment_ids)) {
            $this->error(__('No assignments selected', 'mobility-trailblazers'));
            return;
        }
        
        // Log for debugging
        error_log('MT Bulk Remove: count=' . count($assignment_ids));
        
        $assignment_repo = new MT_Assignment_Repository();
        $success_count = 0;
        $errors = [];
        
        foreach ($assignment_ids as $assignment_id) {
            $result = $assignment_repo->delete($assignment_id);
            
            if ($result) {
                $success_count++;
            } else {
                $errors[] = sprintf(__('Failed to remove assignment ID: %d', 'mobility-trailblazers'), $assignment_id);
            }
        }
        
        if ($success_count > 0) {
            $message = sprintf(
                __('%d assignments removed successfully.', 'mobility-trailblazers'),
                $success_count
            );
            
            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('%d failed.', 'mobility-trailblazers'), count($errors));
            }
            
            $this->success([
                'success_count' => $success_count,
                'errors' => $errors
            ], $message);
        } else {
            $this->error(__('No assignments could be removed.', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Handle bulk reassignment of assignments
     *
     * @return void
     */
    public function bulk_reassign_assignments() {
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        // Get parameters
        $assignment_ids = isset($_POST['assignment_ids']) && is_array($_POST['assignment_ids']) 
            ? array_map('intval', $_POST['assignment_ids']) 
            : array();
        $new_jury_member_id = isset($_POST['new_jury_member_id']) ? intval($_POST['new_jury_member_id']) : 0;
        
        if (empty($assignment_ids) || !$new_jury_member_id) {
            $this->error(__('Invalid parameters', 'mobility-trailblazers'));
            return;
        }
        
        // Verify new jury member exists
        $jury_member = get_post($new_jury_member_id);
        if (!$jury_member || $jury_member->post_type !== 'mt_jury_member') {
            $this->error(__('Invalid jury member selected', 'mobility-trailblazers'));
            return;
        }
        
        // Log for debugging
        error_log('MT Bulk Reassign: count=' . count($assignment_ids) . ', new_jury_member_id=' . $new_jury_member_id);
        
        $assignment_repo = new MT_Assignment_Repository();
        $success_count = 0;
        $errors = [];
        $skipped = 0;
        
        foreach ($assignment_ids as $assignment_id) {
            // Get the existing assignment
            $assignment = $assignment_repo->find($assignment_id);
            
            if (!$assignment) {
                $errors[] = sprintf(__('Assignment ID %d not found', 'mobility-trailblazers'), $assignment_id);
                continue;
            }
            
            // Check if new assignment already exists
            $existing = $assignment_repo->get_by_jury_and_candidate($new_jury_member_id, $assignment->candidate_id);
            
            if ($existing) {
                $skipped++;
                error_log('MT Bulk Reassign: Assignment already exists for jury ' . $new_jury_member_id . ' and candidate ' . $assignment->candidate_id);
                continue;
            }
            
            // Create new assignment
            $new_assignment = $assignment_repo->create([
                'jury_member_id' => $new_jury_member_id,
                'candidate_id' => $assignment->candidate_id
            ]);
            
            if ($new_assignment) {
                // Delete old assignment
                $delete_result = $assignment_repo->delete($assignment_id);
                if ($delete_result) {
                    $success_count++;
                } else {
                    // If we can't delete the old one, remove the new one to maintain consistency
                    $assignment_repo->delete($new_assignment);
                    $errors[] = sprintf(__('Failed to remove old assignment ID: %d', 'mobility-trailblazers'), $assignment_id);
                }
            } else {
                $errors[] = sprintf(__('Failed to create new assignment for candidate ID: %d', 'mobility-trailblazers'), $assignment->candidate_id);
            }
        }
        
        if ($success_count > 0 || $skipped > 0) {
            $message = '';
            
            if ($success_count > 0) {
                $message = sprintf(
                    __('%d assignments reassigned successfully.', 'mobility-trailblazers'),
                    $success_count
                );
            }
            
            if ($skipped > 0) {
                $skip_message = sprintf(
                    __('%d assignments skipped (already exist).', 'mobility-trailblazers'),
                    $skipped
                );
                $message = $message ? $message . ' ' . $skip_message : $skip_message;
            }
            
            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('%d failed.', 'mobility-trailblazers'), count($errors));
            }
            
            $this->success([
                'success_count' => $success_count,
                'skipped' => $skipped,
                'errors' => $errors
            ], $message);
        } else {
            $this->error(__('No assignments could be reassigned.', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Handle bulk export of assignments
     *
     * @return void
     */
    public function bulk_export_assignments() {
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        $this->check_permission('mt_export_data');
        
        // Get assignment IDs
        $assignment_ids = isset($_POST['assignment_ids']) && is_array($_POST['assignment_ids']) 
            ? array_map('intval', $_POST['assignment_ids']) 
            : array();
        
        if (empty($assignment_ids)) {
            wp_die(__('No assignments selected for export.', 'mobility-trailblazers'));
        }
        
        $assignment_repo = new MT_Assignment_Repository();
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=selected-assignments-' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, array(
            __('Assignment ID', 'mobility-trailblazers'),
            __('Jury Member', 'mobility-trailblazers'),
            __('Email', 'mobility-trailblazers'),
            __('Candidate', 'mobility-trailblazers'),
            __('Category', 'mobility-trailblazers'),
            __('Assigned Date', 'mobility-trailblazers'),
            __('Evaluation Status', 'mobility-trailblazers'),
            __('Total Score', 'mobility-trailblazers')
        ));
        
        // Add data for selected assignments
        foreach ($assignment_ids as $assignment_id) {
            $assignment = $assignment_repo->find($assignment_id);
            
            if (!$assignment) {
                continue;
            }
            
            $jury = get_post($assignment->jury_member_id);
            $candidate = get_post($assignment->candidate_id);
            $user = get_user_by('ID', get_post_meta($assignment->jury_member_id, '_mt_user_id', true));
            
            // Get categories safely
            $category_name = '';
            $categories = wp_get_post_terms($assignment->candidate_id, 'mt_category');
            if (!is_wp_error($categories) && !empty($categories)) {
                $category_name = $categories[0]->name;
            }
            
            // Check if evaluation exists
            $evaluations = $evaluation_repo->find_all([
                'jury_member_id' => $assignment->jury_member_id,
                'candidate_id' => $assignment->candidate_id,
                'limit' => 1
            ]);
            $evaluation = !empty($evaluations) ? $evaluations[0] : null;
            $status = $evaluation ? __('Completed', 'mobility-trailblazers') : __('Pending', 'mobility-trailblazers');
            
            // Calculate total score if evaluation exists
            $total_score = '';
            if ($evaluation) {
                $total_score = floatval($evaluation->courage_score) + 
                               floatval($evaluation->innovation_score) + 
                               floatval($evaluation->implementation_score) + 
                               floatval($evaluation->relevance_score) + 
                               floatval($evaluation->visibility_score);
            }
            
            // Format date safely
            $assigned_date = '';
            if (!empty($assignment->created_at)) {
                $assigned_date = date('Y-m-d', strtotime($assignment->created_at));
            }
            
            fputcsv($output, array(
                $assignment_id,
                $jury ? $jury->post_title : '',
                $user ? $user->user_email : '',
                $candidate ? $candidate->post_title : '',
                $category_name,
                $assigned_date,
                $status,
                $total_score
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Test handler
     */
    public function test_handler() {
        $this->success(['time' => current_time('mysql')], 'AJAX is working!');
    }
} 