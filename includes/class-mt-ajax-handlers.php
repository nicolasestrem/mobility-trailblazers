<?php
/**
 * AJAX handlers class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_AJAX_Handlers
 * Handles all AJAX requests
 */
class MT_AJAX_Handlers {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Jury evaluation handlers
        add_action('wp_ajax_mt_submit_evaluation', array($this, 'submit_evaluation'));
        add_action('wp_ajax_mt_save_draft', array($this, 'save_draft'));
        add_action('wp_ajax_mt_get_evaluation', array($this, 'get_evaluation'));
        add_action('wp_ajax_mt_export_evaluations', array($this, 'export_evaluations'));
        
        // Assignment handlers
        add_action('wp_ajax_mt_assign_candidates', array($this, 'assign_candidates'));
        add_action('wp_ajax_nopriv_mt_assign_candidates', array($this, 'assign_candidates'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'auto_assign'));
        add_action('wp_ajax_mt_get_assignment_stats', array($this, 'get_assignment_stats'));
        add_action('wp_ajax_mt_get_candidates_for_assignment', array($this, 'get_candidates_for_assignment'));
        add_action('wp_ajax_mt_clear_assignments', array($this, 'clear_assignments'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'export_assignments'));
        add_action('wp_ajax_mt_remove_assignment', array($this, 'remove_assignment'));
        add_action('wp_ajax_mt_manual_assign', array($this, 'manual_assign'));
        
        // Vote reset handlers
        add_action('wp_ajax_mt_reset_individual', array($this, 'reset_individual_vote'));
        add_action('wp_ajax_mt_reset_bulk_candidate', array($this, 'reset_candidate_votes'));
        add_action('wp_ajax_mt_reset_bulk_jury', array($this, 'reset_jury_votes'));
        add_action('wp_ajax_mt_reset_phase_transition', array($this, 'reset_phase_transition'));
        add_action('wp_ajax_mt_reset_full_system', array($this, 'reset_full_system'));
        
        // Backup handlers
        add_action('wp_ajax_mt_create_backup', array($this, 'create_backup'));
        add_action('wp_ajax_mt_restore_backup', array($this, 'restore_backup'));
        add_action('wp_ajax_mt_delete_backup', array($this, 'delete_backup'));
        add_action('wp_ajax_mt_export_backup', array($this, 'export_backup'));
        
        // Import/Export handlers
        add_action('wp_ajax_mt_export_candidates', array($this, 'export_candidates'));
        add_action('wp_ajax_mt_export_jury', array($this, 'export_jury'));
        add_action('wp_ajax_mt_export_votes', array($this, 'export_votes'));
        add_action('wp_ajax_mt_import_data', array($this, 'import_data'));
        
        // Jury user management
        add_action('wp_ajax_mt_create_jury_user', array($this, 'create_jury_user'));
        add_action('wp_ajax_mt_send_jury_credentials', array($this, 'send_jury_credentials'));
        
        // Public voting handlers
        add_action('wp_ajax_mt_submit_vote', array($this, 'submit_public_vote'));
        add_action('wp_ajax_nopriv_mt_submit_vote', array($this, 'submit_public_vote'));
        
        // Registration handlers
        add_action('wp_ajax_mt_submit_registration', array($this, 'submit_registration'));
        add_action('wp_ajax_nopriv_mt_submit_registration', array($this, 'submit_registration'));
        
        // Register jury dashboard AJAX handlers
        add_action('wp_ajax_mt_get_jury_dashboard_data', array($this, 'get_jury_dashboard_data'));
        add_action('wp_ajax_mt_get_candidate_evaluation', array($this, 'get_candidate_evaluation'));
        add_action('wp_ajax_mt_save_evaluation', array($this, 'save_evaluation'));
    }
    
    /**
     * Submit evaluation
     */
    public function submit_evaluation() {
        // Verify nonce
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!mt_is_jury_member(get_current_user_id())) {
            wp_send_json_error(array('message' => __('You do not have permission to submit evaluations', 'mobility-trailblazers')));
        }
        
        // Prepare data for service
        $data = array(
            'jury_member_id' => get_current_user_id(),
            'candidate_id' => intval($_POST['candidate_id']),
            'scores' => isset($_POST['scores']) ? $_POST['scores'] : array(),
            'comments' => isset($_POST['comments']) ? $_POST['comments'] : ''
        );
        
        // Use the evaluation service
        $service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
        $result = $service->process($data);
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to save evaluation', 'mobility-trailblazers'),
                'errors' => $service->get_errors()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Evaluation submitted successfully!', 'mobility-trailblazers')
        ));
    }
    
    /**
     * Save draft evaluation
     */
    public function save_draft() {
        // Verify nonce
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!mt_is_jury_member(get_current_user_id())) {
            wp_send_json_error(array('message' => __('You do not have permission to save drafts', 'mobility-trailblazers')));
        }
        
        // Prepare data for service
        $data = array(
            'jury_member_id' => get_current_user_id(),
            'candidate_id' => intval($_POST['candidate_id']),
            'scores' => isset($_POST['scores']) ? $_POST['scores'] : array(),
            'comments' => isset($_POST['comments']) ? $_POST['comments'] : ''
        );
        
        // Use the evaluation service
        $service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
        $result = $service->save_draft($data);
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to save draft', 'mobility-trailblazers'),
                'errors' => $service->get_errors()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Draft saved successfully!', 'mobility-trailblazers')
        ));
    }
    
    /**
     * Get existing evaluation or draft
     */
    public function get_evaluation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(array('message' => __('You do not have permission to view evaluations.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member profile not found.', 'mobility-trailblazers')));
        }
        
        // Validate input
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid candidate.', 'mobility-trailblazers')));
        }
        
        // Check for existing evaluation
        $evaluation = mt_get_evaluation($candidate_id, $jury_member->ID);
        
        if ($evaluation) {
            wp_send_json_success(array(
                'evaluation' => array(
                    'courage' => $evaluation->courage_score,
                    'innovation' => $evaluation->innovation_score,
                    'implementation' => $evaluation->implementation_score,
                    'relevance' => $evaluation->relevance_score,
                    'visibility' => $evaluation->visibility_score,
                    'comments' => $evaluation->comments,
                ),
                'is_draft' => false,
            ));
        }
        
        // Check for draft
        $draft = get_user_meta(get_current_user_id(), 'mt_evaluation_draft_' . $candidate_id, true);
        
        if ($draft) {
            wp_send_json_success(array(
                'evaluation' => $draft,
                'is_draft' => true,
            ));
        }
        
        wp_send_json_error(array('message' => __('No evaluation found.', 'mobility-trailblazers')));
    }
    
    /**
     * Export jury member evaluations
     */
    public function export_evaluations() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_export_own_evaluations')) {
            wp_send_json_error(array('message' => __('You do not have permission to export evaluations.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member profile not found.', 'mobility-trailblazers')));
        }
        
        // Get evaluations
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $evaluations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE jury_member_id = %d AND is_active = 1 ORDER BY evaluation_date DESC",
            $jury_member->ID
        ));
        
        // Prepare CSV data
        $csv_data = array();
        $headers = array(
            __('Candidate', 'mobility-trailblazers'),
            __('Company', 'mobility-trailblazers'),
            __('Courage Score', 'mobility-trailblazers'),
            __('Innovation Score', 'mobility-trailblazers'),
            __('Implementation Score', 'mobility-trailblazers'),
            __('Relevance Score', 'mobility-trailblazers'),
            __('Visibility Score', 'mobility-trailblazers'),
            __('Total Score', 'mobility-trailblazers'),
            __('Comments', 'mobility-trailblazers'),
            __('Evaluation Date', 'mobility-trailblazers'),
        );
        
        foreach ($evaluations as $evaluation) {
            $candidate = get_post($evaluation->candidate_id);
            if (!$candidate) continue;
            
            $company = get_post_meta($candidate->ID, '_mt_company', true);
            
            $csv_data[] = array(
                $candidate->post_title,
                $company,
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->relevance_score,
                $evaluation->visibility_score,
                $evaluation->total_score,
                $evaluation->comments,
                mt_format_date($evaluation->evaluation_date),
            );
        }
        
        // Generate filename
        $filename = 'evaluations-' . sanitize_title($jury_member->post_title) . '-' . date('Y-m-d') . '.csv';
        
        // Create temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'mt_export_');
        $handle = fopen($temp_file, 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($handle, $headers);
        
        // Write data
        foreach ($csv_data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        // Read file content
        $content = file_get_contents($temp_file);
        unlink($temp_file);
        
        // Send response
        wp_send_json_success(array(
            'filename' => $filename,
            'content' => base64_encode($content),
            'message' => sprintf(__('Exported %d evaluations.', 'mobility-trailblazers'), count($csv_data)),
        ));
    }
    
    /**
     * Assign candidates to jury member
     */
    public function assign_candidates() {
        // Verify nonce
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments', 'mobility-trailblazers')));
        }
        
        // Prepare data
        $data = array(
            'assignment_type' => 'manual',
            'jury_member_id' => intval($_POST['jury_member_id']),
            'candidate_ids' => array_map('intval', $_POST['candidate_ids'] ?? array())
        );
        
        // Use assignment service
        $service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        $result = $service->process($data);
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to create assignments', 'mobility-trailblazers'),
                'errors' => $service->get_errors()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Assignments created successfully', 'mobility-trailblazers')
        ));
    }
    
    /**
     * Auto-assign candidates
     */
    public function auto_assign() {
        // Verify nonce
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments', 'mobility-trailblazers')));
        }
        
        // Prepare data
        $data = array(
            'assignment_type' => 'auto',
            'candidates_per_jury' => intval($_POST['candidates_per_jury']),
            'clear_existing' => !empty($_POST['clear_existing'])
        );
        
        // Use assignment service
        $service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        $result = $service->process($data);
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to auto-assign candidates', 'mobility-trailblazers'),
                'errors' => $service->get_errors()
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Auto-assignment completed successfully', 'mobility-trailblazers')
        ));
    }
    
    /**
     * Remove assignment
     */
    public function remove_assignment() {
        // Verify nonce
        if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments', 'mobility-trailblazers')));
        }
        
        $jury_member_id = intval($_POST['jury_member_id']);
        $candidate_id = intval($_POST['candidate_id']);
        
        // Use assignment service
        $service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        $result = $service->remove_assignment($jury_member_id, $candidate_id);
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to remove assignment', 'mobility-trailblazers')
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Assignment removed successfully', 'mobility-trailblazers')
        ));
    }
    
    /**
     * Clear all assignments
     */
    public function clear_assignments() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments.', 'mobility-trailblazers')));
        }
        
        // Get all candidates
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));
        
        $cleared = 0;
        
        foreach ($candidates as $candidate) {
            if (delete_post_meta($candidate->ID, '_mt_assigned_jury_members')) {
                $cleared++;
            }
        }
        
        // Log activity
        mt_log('All assignments cleared', 'info', array(
            'candidates_cleared' => $cleared,
        ));
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('All assignments cleared. %d candidates affected.', 'mobility-trailblazers'),
                $cleared
            ),
            'cleared' => $cleared,
        ));
    }
    
    /**
     * Export assignments
     */
    public function export_assignments() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_export_data')) {
            wp_send_json_error(array('message' => __('You do not have permission to export data.', 'mobility-trailblazers')));
        }
        
        // Get all candidates with assignments
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_members',
                    'compare' => 'EXISTS',
                ),
            ),
        ));
        
        // Prepare CSV data
        $csv_data = array();
        $headers = array(
            __('Candidate', 'mobility-trailblazers'),
            __('Company', 'mobility-trailblazers'),
            __('Category', 'mobility-trailblazers'),
            __('Assigned Jury Members', 'mobility-trailblazers'),
            __('Number of Assignments', 'mobility-trailblazers'),
        );
        
        foreach ($candidates as $candidate) {
            $company = get_post_meta($candidate->ID, '_mt_company', true);
            
            $categories = get_the_terms($candidate->ID, 'mt_category');
            $category = $categories && !is_wp_error($categories) ? $categories[0]->name : '';
            
            $jury_member_ids = get_post_meta($candidate->ID, '_mt_assigned_jury_members', true);
            $jury_names = array();
            
            if (is_array($jury_member_ids)) {
                foreach ($jury_member_ids as $jury_id) {
                    $jury = get_post($jury_id);
                    if ($jury) {
                        $jury_names[] = $jury->post_title;
                    }
                }
            }
            
            $csv_data[] = array(
                $candidate->post_title,
                $company,
                $category,
                implode('; ', $jury_names),
                count($jury_names),
            );
        }
        
        // Generate filename
        $filename = 'assignments-' . date('Y-m-d') . '.csv';
        
        // Create temporary file
        $temp_file = tempnam(sys_get_temp_dir(), 'mt_export_');
        $handle = fopen($temp_file, 'w');
        
        // Add BOM for Excel UTF-8 compatibility
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        fputcsv($handle, $headers);
        
        // Write data
        foreach ($csv_data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        // Read file content
        $content = file_get_contents($temp_file);
        unlink($temp_file);
        
        // Send response
        wp_send_json_success(array(
            'filename' => $filename,
            'content' => base64_encode($content),
            'message' => sprintf(__('Exported %d assignments.', 'mobility-trailblazers'), count($csv_data)),
        ));
    }
    
    /**
     * Reset individual vote
     */
    public function reset_individual_vote() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_vote_reset')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_reset_votes')) {
            wp_send_json_error(array('message' => __('You do not have permission to reset votes.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        if (!$candidate_id || !$jury_member_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mobility-trailblazers')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Create backup first
        $this->create_vote_backup('individual', array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $jury_member_id,
        ), $reason);
        
        // Soft delete the vote
        $result = $wpdb->update(
            $table_name,
            array(
                'is_active' => 0,
                'reset_at' => current_time('mysql'),
                'reset_by' => get_current_user_id(),
            ),
            array(
                'candidate_id' => $candidate_id,
                'jury_member_id' => $jury_member_id,
                'is_active' => 1,
            ),
            array('%d', '%s', '%d'),
            array('%d', '%d', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to reset vote.', 'mobility-trailblazers')));
        }
        
        // Log the reset
        $this->log_reset('individual', array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $jury_member_id,
            'affected_rows' => $result,
        ), $reason);
        
        wp_send_json_success(array(
            'message' => __('Vote reset successfully.', 'mobility-trailblazers'),
            'affected' => $result,
        ));
    }
    
    /**
     * Reset all votes for a candidate
     */
    public function reset_candidate_votes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_vote_reset')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_reset_votes')) {
            wp_send_json_error(array('message' => __('You do not have permission to reset votes.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid candidate.', 'mobility-trailblazers')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Create backup first
        $this->create_vote_backup('bulk_candidate', array(
            'candidate_id' => $candidate_id,
        ), $reason);
        
        // Soft delete all votes for this candidate
        $result = $wpdb->update(
            $table_name,
            array(
                'is_active' => 0,
                'reset_at' => current_time('mysql'),
                'reset_by' => get_current_user_id(),
            ),
            array(
                'candidate_id' => $candidate_id,
                'is_active' => 1,
            ),
            array('%d', '%s', '%d'),
            array('%d', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to reset votes.', 'mobility-trailblazers')));
        }
        
        // Log the reset
        $this->log_reset('bulk_candidate', array(
            'candidate_id' => $candidate_id,
            'affected_rows' => $result,
        ), $reason);
        
        // Notify affected jury members
        $this->notify_jury_members_about_reset($candidate_id, 'candidate');
        
        wp_send_json_success(array(
            'message' => sprintf(
                _n('%d vote reset successfully.', '%d votes reset successfully.', $result, 'mobility-trailblazers'),
                $result
            ),
            'affected' => $result,
        ));
    }
    
    /**
     * Reset all votes by a jury member
     */
    public function reset_jury_votes() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_vote_reset')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_reset_votes')) {
            wp_send_json_error(array('message' => __('You do not have permission to reset votes.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        if (!$jury_member_id) {
            wp_send_json_error(array('message' => __('Invalid jury member.', 'mobility-trailblazers')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Create backup first
        $this->create_vote_backup('bulk_jury', array(
            'jury_member_id' => $jury_member_id,
        ), $reason);
        
        // Soft delete all votes by this jury member
        $result = $wpdb->update(
            $table_name,
            array(
                'is_active' => 0,
                'reset_at' => current_time('mysql'),
                'reset_by' => get_current_user_id(),
            ),
            array(
                'jury_member_id' => $jury_member_id,
                'is_active' => 1,
            ),
            array('%d', '%s', '%d'),
            array('%d', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to reset votes.', 'mobility-trailblazers')));
        }
        
        // Log the reset
        $this->log_reset('bulk_jury', array(
            'jury_member_id' => $jury_member_id,
            'affected_rows' => $result,
        ), $reason);
        
        // Notify the jury member
        $this->notify_jury_member_about_reset($jury_member_id);
        
        wp_send_json_success(array(
            'message' => sprintf(
                _n('%d evaluation reset successfully.', '%d evaluations reset successfully.', $result, 'mobility-trailblazers'),
                $result
            ),
            'affected' => $result,
        ));
    }
    
    /**
     * Reset votes for phase transition
     */
    public function reset_phase_transition() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_vote_reset')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_reset_votes')) {
            wp_send_json_error(array('message' => __('You do not have permission to reset votes.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $new_phase = isset($_POST['new_phase']) ? sanitize_text_field($_POST['new_phase']) : '';
        $notify_jury = isset($_POST['notify_jury']) && $_POST['notify_jury'] === 'true';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        if (!$new_phase) {
            wp_send_json_error(array('message' => __('Invalid phase.', 'mobility-trailblazers')));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Create backup first
        $this->create_vote_backup('phase_transition', array(
            'old_phase' => mt_get_current_phase(),
            'new_phase' => $new_phase,
        ), $reason);
        
        // Archive current votes by marking them with the current phase
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name 
             SET evaluation_round = %s 
             WHERE is_active = 1 AND evaluation_round = 'initial'",
            mt_get_current_phase()
        ));
        
        // Update system phase
        update_option('mt_current_phase', $new_phase);
        
        // Log the reset
        $this->log_reset('phase_transition', array(
            'new_phase' => $new_phase,
            'notify_jury' => $notify_jury,
        ), $reason);
        
        // Notify jury members if requested
        if ($notify_jury) {
            $this->notify_all_jury_about_phase_transition($new_phase);
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Successfully transitioned to %s phase.', 'mobility-trailblazers'),
                mt_get_phase_label($new_phase)
            ),
        ));
    }
    
    /**
     * Full system reset
     */
    public function reset_full_system() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_vote_reset')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_reset_votes')) {
            wp_send_json_error(array('message' => __('You do not have permission to reset votes.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $notify_jury = isset($_POST['notify_jury']) && $_POST['notify_jury'] === 'true';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        if (!$reason) {
            wp_send_json_error(array('message' => __('Reason is required for full system reset.', 'mobility-trailblazers')));
        }
        
        global $wpdb;
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        $votes_table = $wpdb->prefix . 'mt_votes';
        
        // Create comprehensive backup
        $this->create_vote_backup('full_system', array(
            'timestamp' => current_time('mysql'),
        ), $reason);
        
        // Soft delete all scores
        $scores_result = $wpdb->update(
            $scores_table,
            array(
                'is_active' => 0,
                'reset_at' => current_time('mysql'),
                'reset_by' => get_current_user_id(),
            ),
            array('is_active' => 1),
            array('%d', '%s', '%d'),
            array('%d')
        );
        
        // Soft delete all votes
        $votes_result = $wpdb->update(
            $votes_table,
            array(
                'is_active' => 0,
                'reset_at' => current_time('mysql'),
                'reset_by' => get_current_user_id(),
            ),
            array('is_active' => 1),
            array('%d', '%s', '%d'),
            array('%d')
        );
        
        // Clear all assignments
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));
        
        foreach ($candidates as $candidate) {
            delete_post_meta($candidate->ID, '_mt_assigned_jury_members');
        }
        
        // Log the reset
        $this->log_reset('full_system', array(
            'scores_reset' => $scores_result,
            'votes_reset' => $votes_result,
            'assignments_cleared' => count($candidates),
        ), $reason);
        
        // Notify all jury members if requested
        if ($notify_jury) {
            $this->notify_all_jury_about_full_reset();
        }
        
        wp_send_json_success(array(
            'message' => __('Full system reset completed successfully. All votes, evaluations, and assignments have been cleared.', 'mobility-trailblazers'),
            'details' => array(
                'scores_reset' => $scores_result,
                'votes_reset' => $votes_result,
                'assignments_cleared' => count($candidates),
            ),
        ));
    }
    
    /**
     * Create vote backup
     */
    private function create_vote_backup($type, $data, $reason = '') {
        global $wpdb;
        
        $backup_data = array(
            'type' => $type,
            'data' => $data,
            'timestamp' => current_time('mysql'),
        );
        
        // Get affected records based on type
        switch ($type) {
            case 'individual':
                $scores_table = $wpdb->prefix . 'mt_candidate_scores';
                $backup_data['scores'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $scores_table 
                     WHERE candidate_id = %d AND jury_member_id = %d AND is_active = 1",
                    $data['candidate_id'],
                    $data['jury_member_id']
                ));
                break;
                
            case 'bulk_candidate':
                $scores_table = $wpdb->prefix . 'mt_candidate_scores';
                $backup_data['scores'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $scores_table WHERE candidate_id = %d AND is_active = 1",
                    $data['candidate_id']
                ));
                break;
                
            case 'bulk_jury':
                $scores_table = $wpdb->prefix . 'mt_candidate_scores';
                $backup_data['scores'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $scores_table WHERE jury_member_id = %d AND is_active = 1",
                    $data['jury_member_id']
                ));
                break;
                
            case 'full_system':
                $scores_table = $wpdb->prefix . 'mt_candidate_scores';
                $votes_table = $wpdb->prefix . 'mt_votes';
                
                $backup_data['scores'] = $wpdb->get_results("SELECT * FROM $scores_table WHERE is_active = 1");
                $backup_data['votes'] = $wpdb->get_results("SELECT * FROM $votes_table WHERE is_active = 1");
                $backup_data['assignments'] = array();
                
                $candidates = get_posts(array(
                    'post_type' => 'mt_candidate',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_mt_assigned_jury_members',
                            'compare' => 'EXISTS',
                        ),
                    ),
                ));
                
                foreach ($candidates as $candidate) {
                    $backup_data['assignments'][$candidate->ID] = get_post_meta($candidate->ID, '_mt_assigned_jury_members', true);
                }
                break;
        }
        
        // Save backup
        $table_name = $wpdb->prefix . 'mt_vote_backups';
        $wpdb->insert(
            $table_name,
            array(
                'backup_type' => $type,
                'backup_data' => json_encode($backup_data),
                'backup_reason' => $reason,
                'created_by' => get_current_user_id(),
            ),
            array('%s', '%s', '%s', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Log reset operation
     */
    private function log_reset($type, $data, $reason) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vote_reset_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'reset_type' => $type,
                'affected_data' => json_encode($data),
                'reason' => $reason,
                'performed_by' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'backup_created' => 1,
            ),
            array('%s', '%s', '%s', '%d', '%s', '%s', '%d')
        );
        
        // Also log to activity log
        mt_log('Vote reset performed', 'warning', array(
            'type' => $type,
            'data' => $data,
            'reason' => $reason,
        ));
    }
    
    /**
     * Notify jury members about reset
     */
    private function notify_jury_members_about_reset($candidate_id, $type) {
        // Get affected jury members
        $jury_member_ids = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
        
        if (!is_array($jury_member_ids)) {
            return;
        }
        
        $candidate = get_post($candidate_id);
        if (!$candidate) {
            return;
        }
        
        foreach ($jury_member_ids as $jury_id) {
            $jury_member = get_post($jury_id);
            if (!$jury_member) continue;
            
            $user_id = get_post_meta($jury_id, '_mt_user_id', true);
            if (!$user_id) continue;
            
            $user = get_user_by('id', $user_id);
            if (!$user) continue;
            
            // Send email
            $subject = __('Vote Reset Notification', 'mobility-trailblazers');
            $message = sprintf(
                __('Dear %s,

This is to inform you that the evaluation for candidate "%s" has been reset.

If you had previously submitted an evaluation for this candidate, you may need to re-evaluate them.

Please log in to your jury dashboard for more information.

Best regards,
%s', 'mobility-trailblazers'),
                $jury_member->post_title,
                $candidate->post_title,
                get_bloginfo('name')
            );
            
            mt_send_email($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Notify jury member about their votes being reset
     */
    private function notify_jury_member_about_reset($jury_member_id) {
        $jury_member = get_post($jury_member_id);
        if (!$jury_member) return;
        
        $user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
        if (!$user_id) return;
        
        $user = get_user_by('id', $user_id);
        if (!$user) return;
        
        // Send email
        $subject = __('Your Evaluations Have Been Reset', 'mobility-trailblazers');
        $message = sprintf(
            __('Dear %s,

This is to inform you that all your evaluations have been reset by the system administrator.

This may be due to a phase transition or other administrative action.

Please log in to your jury dashboard to view your current assignments and submit new evaluations.

Best regards,
%s', 'mobility-trailblazers'),
            $jury_member->post_title,
            get_bloginfo('name')
        );
        
        mt_send_email($user->user_email, $subject, $message);
    }
    
    /**
     * Notify all jury members about phase transition
     */
    private function notify_all_jury_about_phase_transition($new_phase) {
        $jury_members = get_posts(array(
            'post_type' => mt_get_jury_post_type(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        foreach ($jury_members as $jury_member) {
            $user_id = get_post_meta($jury_member->ID, '_mt_user_id', true);
            if (!$user_id) continue;
            
            $user = get_user_by('id', $user_id);
            if (!$user) continue;
            
            // Send email
            $subject = __('Award Phase Transition', 'mobility-trailblazers');
            $message = sprintf(
                __('Dear %s,

The Mobility Trailblazers Award has transitioned to the %s phase.

Your previous evaluations have been archived, and you may be asked to perform new evaluations for this phase.

Please log in to your jury dashboard to view your assignments for the new phase.

Best regards,
%s', 'mobility-trailblazers'),
                $jury_member->post_title,
                mt_get_phase_label($new_phase),
                get_bloginfo('name')
            );
            
            mt_send_email($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Notify all jury members about full system reset
     */
    private function notify_all_jury_about_full_reset() {
        $jury_members = get_posts(array(
            'post_type' => mt_get_jury_post_type(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        foreach ($jury_members as $jury_member) {
            $user_id = get_post_meta($jury_member->ID, '_mt_user_id', true);
            if (!$user_id) continue;
            
            $user = get_user_by('id', $user_id);
            if (!$user) continue;
            
            // Send email
            $subject = __('System Reset - Action Required', 'mobility-trailblazers');
            $message = sprintf(
                __('Dear %s,

The Mobility Trailblazers Award system has undergone a complete reset.

All previous evaluations and assignments have been cleared. You will receive new candidate assignments shortly.

Please wait for further instructions before accessing your jury dashboard.

We apologize for any inconvenience this may cause.

Best regards,
%s', 'mobility-trailblazers'),
                $jury_member->post_title,
                get_bloginfo('name')
            );
            
            mt_send_email($user->user_email, $subject, $message);
        }
    }
    
    /**
     * Create jury user account
     */
    public function create_jury_user() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_jury_members')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage jury members.', 'mobility-trailblazers')));
        }
        
        // Get jury member ID
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        
        if (!$jury_member_id) {
            wp_send_json_error(array('message' => __('Invalid jury member.', 'mobility-trailblazers')));
        }
        
        // Create user
        $user_id = MT_Roles::create_jury_user($jury_member_id, array(
            'send_notification' => isset($_POST['send_notification']) && $_POST['send_notification'] === 'true',
        ));
        
        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('User account created successfully.', 'mobility-trailblazers'),
            'user_id' => $user_id,
        ));
    }
    
    /**
     * Submit public vote
     */
    public function submit_public_vote() {
        // Check if public voting is enabled
        if (!mt_is_public_voting_enabled()) {
            wp_send_json_error(array('message' => __('Public voting is currently closed.', 'mobility-trailblazers')));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_public_voting')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        $voter_email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $voter_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        
        if (!$candidate_id || !$voter_email) {
            wp_send_json_error(array('message' => __('Please provide all required information.', 'mobility-trailblazers')));
        }
        
        // Verify candidate
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            wp_send_json_error(array('message' => __('Invalid candidate.', 'mobility-trailblazers')));
        }
        
        // Use voting service
        $service = new \MobilityTrailblazers\Services\MT_Voting_Service();
        $result = $service->process(array(
            'candidate_id' => $candidate_id,
            'voter_email' => $voter_email,
            'voter_name' => $voter_name
        ));
        
        if (!$result) {
            wp_send_json_error(array(
                'message' => __('Failed to save vote. Please try again.', 'mobility-trailblazers'),
                'errors' => $service->get_errors()
            ));
        }
        
        // Send confirmation email using notification service
        $notification_service = new \MobilityTrailblazers\Services\MT_Notification_Service();
        $notification_service->send_voting_confirmation($voter_email, $candidate->post_title);
        
        wp_send_json_success(array(
            'message' => __('Thank you for voting! Your vote has been recorded.', 'mobility-trailblazers'),
        ));
    }
    
    /**
     * Submit registration
     */
    public function submit_registration() {
        // Check if registration is open
        if (!mt_is_registration_open()) {
            wp_send_json_error(array('message' => __('Registration is currently closed.', 'mobility-trailblazers')));
        }
        
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_registration')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Validate required fields
        $required_fields = array('name', 'email', 'company', 'position', 'innovation_title');
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Please fill in all required fields. Missing: %s', 'mobility-trailblazers'), $field)
                ));
            }
        }
        
        // Create candidate post
        $post_data = array(
            'post_title' => sanitize_text_field($_POST['name']),
            'post_content' => sanitize_textarea_field($_POST['innovation_description'] ?? ''),
            'post_type' => 'mt_candidate',
            'post_status' => 'pending', // Requires approval
        );
        
        $candidate_id = wp_insert_post($post_data);
        
        if (is_wp_error($candidate_id)) {
            wp_send_json_error(array('message' => __('Failed to submit registration. Please try again.', 'mobility-trailblazers')));
        }
        
        // Save meta data
        $meta_fields = array(
            'company' => 'sanitize_text_field',
            'position' => 'sanitize_text_field',
            'email' => 'sanitize_email',
            'phone' => 'sanitize_text_field',
            'location' => 'sanitize_text_field',
            'website' => 'esc_url_raw',
            'linkedin' => 'esc_url_raw',
            'innovation_title' => 'sanitize_text_field',
            'innovation_stage' => 'sanitize_text_field',
            'target_market' => 'sanitize_text_field',
        );
        
        foreach ($meta_fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = call_user_func($sanitize_callback, $_POST[$field]);
                update_post_meta($candidate_id, '_mt_' . $field, $value);
            }
        }
        
        // Set initial status
        update_post_meta($candidate_id, '_mt_status', 'pending');
        
        // Set category if provided
        if (!empty($_POST['category'])) {
            wp_set_object_terms($candidate_id, intval($_POST['category']), 'mt_category');
        }
        
        // Set current award year
        $current_year_term = get_term_by('slug', mt_get_current_award_year(), 'mt_award_year');
        if ($current_year_term) {
            wp_set_object_terms($candidate_id, $current_year_term->term_id, 'mt_award_year');
        }
        
        // Send confirmation email to applicant
        $applicant_subject = __('Registration Received - Mobility Trailblazers Award', 'mobility-trailblazers');
        $applicant_message = sprintf(
            __('Dear %s,

Thank you for registering for the Mobility Trailblazers Award!

We have received your application for: %s

Your application is currently under review. We will notify you once it has been processed.

Application Details:
- Company: %s
- Position: %s
- Innovation: %s

If you have any questions, please don\'t hesitate to contact us.

Best regards,
%s', 'mobility-trailblazers'),
            sanitize_text_field($_POST['name']),
            sanitize_text_field($_POST['innovation_title']),
            sanitize_text_field($_POST['company']),
            sanitize_text_field($_POST['position']),
            sanitize_text_field($_POST['innovation_title']),
            get_bloginfo('name')
        );
        
        mt_send_email(sanitize_email($_POST['email']), $applicant_subject, $applicant_message);
        
        // Notify administrators
        $admin_emails = array();
        $admins = get_users(array('role' => 'administrator'));
        
        foreach ($admins as $admin) {
            $admin_emails[] = $admin->user_email;
        }
        
        if (!empty($admin_emails)) {
            $admin_subject = __('New Candidate Registration - Mobility Trailblazers Award', 'mobility-trailblazers');
            $admin_message = sprintf(
                __('A new candidate has registered for the Mobility Trailblazers Award.

Candidate: %s
Company: %s
Innovation: %s

Please review the application: %s

The application is currently in pending status and requires approval.', 'mobility-trailblazers'),
                sanitize_text_field($_POST['name']),
                sanitize_text_field($_POST['company']),
                sanitize_text_field($_POST['innovation_title']),
                admin_url('post.php?post=' . $candidate_id . '&action=edit')
            );
            
            foreach ($admin_emails as $admin_email) {
                mt_send_email($admin_email, $admin_subject, $admin_message);
            }
        }
        
        // Log registration
        mt_log('New candidate registration', 'info', array(
            'candidate_id' => $candidate_id,
            'name' => sanitize_text_field($_POST['name']),
            'company' => sanitize_text_field($_POST['company']),
        ));
        
        // Redirect URL if provided
        $redirect_url = '';
        if (!empty($_POST['redirect_url'])) {
            $redirect_url = esc_url_raw($_POST['redirect_url']);
        }
        
        wp_send_json_success(array(
            'message' => __('Thank you for your registration! We will review your application and get back to you soon.', 'mobility-trailblazers'),
            'redirect_url' => $redirect_url,
            'candidate_id' => $candidate_id,
        ));
    }
    
    /**
     * Get assignment statistics
     */
    public function get_assignment_stats() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error(array('message' => __('You do not have permission to view assignment statistics.', 'mobility-trailblazers')));
        }
        
        // Get all active candidates
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_mt_status',
                    'value' => array('approved', 'shortlisted'),
                    'compare' => 'IN',
                ),
            ),
        ));
        
        // Get all active jury members
        $jury_members = get_posts(array(
            'post_type' => mt_get_jury_post_type(),
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        $stats = array(
            'total_candidates' => count($candidates),
            'total_jury_members' => count($jury_members),
            'assigned_candidates' => 0,
            'unassigned_candidates' => 0,
            'jury_assignments' => array(),
        );
        
        // Count assignments per jury member
        foreach ($jury_members as $jury) {
            $stats['jury_assignments'][$jury->ID] = 0;
        }
        
        // Count assigned and unassigned candidates
        foreach ($candidates as $candidate) {
            $assignments = get_post_meta($candidate->ID, '_mt_assigned_jury_members', true);
            
            if (!empty($assignments) && is_array($assignments)) {
                $stats['assigned_candidates']++;
                
                // Count assignments per jury member
                foreach ($assignments as $jury_id) {
                    if (isset($stats['jury_assignments'][$jury_id])) {
                        $stats['jury_assignments'][$jury_id]++;
                    }
                }
            } else {
                $stats['unassigned_candidates']++;
            }
        }
        
        // Calculate average assignments per jury member
        $total_assignments = array_sum($stats['jury_assignments']);
        $stats['average_assignments'] = $stats['total_jury_members'] > 0 
            ? round($total_assignments / $stats['total_jury_members'], 2) 
            : 0;
        
        wp_send_json_success($stats);
    }

    /**
     * Handle manual assignment
     */
    public function manual_assign() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions - temporarily disabled for testing
        // if (!current_user_can('mt_manage_assignments')) {
        //     wp_send_json_error(array('message' => __('You do not have permission to manage assignments.', 'mobility-trailblazers')));
        // }
        
        // Get parameters
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        $jury_ids = isset($_POST['jury_ids']) ? array_map('intval', (array)$_POST['jury_ids']) : array();
        
        // Validate parameters
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Please select a candidate.', 'mobility-trailblazers')));
        }
        
        if (empty($jury_ids)) {
            wp_send_json_error(array('message' => __('Please select at least one jury member.', 'mobility-trailblazers')));
        }
        
        // Verify candidate exists
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            wp_send_json_error(array('message' => __('Invalid candidate selected.', 'mobility-trailblazers')));
        }
        
        // Verify jury members exist
        foreach ($jury_ids as $jury_id) {
            $jury_member = get_post($jury_id);
            if (!$jury_member || $jury_member->post_type !== 'mt_jury_member') {
                wp_send_json_error(array('message' => __('Invalid jury member selected.', 'mobility-trailblazers')));
            }
        }
        
        // Get existing assignments
        $existing_assignments = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
        if (!is_array($existing_assignments)) {
            $existing_assignments = array();
        }
        
        // Merge with new assignments (avoid duplicates)
        $all_assignments = array_unique(array_merge($existing_assignments, $jury_ids));
        
        // Save the assignments
        $updated = update_post_meta($candidate_id, '_mt_assigned_jury_members', array_values($all_assignments));
        
        if ($updated !== false) {
            // Log the action
            $this->log_action('manual_assignment', array(
                'candidate_id' => $candidate_id,
                'jury_ids' => $jury_ids,
                'user_id' => get_current_user_id()
            ));
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully assigned %s to %d jury member(s).', 'mobility-trailblazers'),
                    $candidate->post_title,
                    count($jury_ids)
                ),
                'assigned_count' => count($all_assignments)
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save assignment. Please try again.', 'mobility-trailblazers')));
        }
    }

    /**
     * Log action helper method
     */
    private function log_action($action, $data) {
        $log_entry = array(
            'action' => $action,
            'data' => $data,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id()
        );
        
        // You can implement actual logging here
        // For now, we'll use a transient
        $logs = get_transient('mt_assignment_logs');
        if (!is_array($logs)) {
            $logs = array();
        }
        
        $logs[] = $log_entry;
        
        // Keep only last 100 entries
        if (count($logs) > 100) {
            $logs = array_slice($logs, -100);
        }
        
        set_transient('mt_assignment_logs', $logs, DAY_IN_SECONDS);
    }

    /**
     * Get jury dashboard data
     */
    public function get_jury_dashboard_data() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_jury_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(array('message' => __('You do not have permission to access this data.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member profile not found.', 'mobility-trailblazers')));
        }
        
        // Get assigned candidates
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        $total_assigned = count($assigned_candidates);
        
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
            if (!$candidate) continue;
            
            $status = 'pending';
            if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
                $status = 'completed';
            } elseif (mt_has_draft_evaluation($candidate_id, $jury_member->ID)) {
                $status = 'draft';
            }
            
            $candidates_data[] = array(
                'id' => $candidate_id,
                'title' => $candidate->post_title,
                'excerpt' => wp_trim_words($candidate->post_excerpt ?: $candidate->post_content, 20),
                'thumbnail' => get_the_post_thumbnail_url($candidate_id, 'medium'),
                'status' => $status,
                'company' => get_post_meta($candidate_id, '_mt_company_name', true),
                'category' => wp_get_post_terms($candidate_id, 'mt_category', array('fields' => 'names'))[0] ?? ''
            );
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
        
        wp_send_json_success($response_data);
    }

    /**
     * Get candidate evaluation data
     */
    public function get_candidate_evaluation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_jury_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(array('message' => __('You do not have permission to access evaluations.', 'mobility-trailblazers')));
        }
        
        // Get candidate ID
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid candidate ID.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member profile not found.', 'mobility-trailblazers')));
        }
        
        // Check if candidate is assigned
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        if (!in_array($candidate_id, $assigned_candidates)) {
            wp_send_json_error(array('message' => __('This candidate is not assigned to you.', 'mobility-trailblazers')));
        }
        
        // Get candidate details
        $candidate = get_post($candidate_id);
        if (!$candidate) {
            wp_send_json_error(array('message' => __('Candidate not found.', 'mobility-trailblazers')));
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
                'courage' => intval($existing_evaluation->courage),
                'innovation' => intval($existing_evaluation->innovation),
                'implementation' => intval($existing_evaluation->implementation),
                'relevance' => intval($existing_evaluation->relevance),
                'visibility' => intval($existing_evaluation->visibility),
                'comments' => $existing_evaluation->comments,
                'total_score' => intval($existing_evaluation->total_score)
            );
            $response_data['is_final'] = true;
        } else {
            // Check for draft
            $draft = get_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id, true);
            if ($draft) {
                $response_data['evaluation'] = array(
                    'courage' => intval($draft['courage'] ?? 5),
                    'innovation' => intval($draft['innovation'] ?? 5),
                    'implementation' => intval($draft['implementation'] ?? 5),
                    'relevance' => intval($draft['relevance'] ?? 5),
                    'visibility' => intval($draft['visibility'] ?? 5),
                    'comments' => $draft['comments'] ?? '',
                    'total_score' => array_sum(array(
                        intval($draft['courage'] ?? 5),
                        intval($draft['innovation'] ?? 5),
                        intval($draft['implementation'] ?? 5),
                        intval($draft['relevance'] ?? 5),
                        intval($draft['visibility'] ?? 5)
                    ))
                );
            }
        }
        
        wp_send_json_success($response_data);
    }

    /**
     * Save evaluation (draft or final)
     */
    public function save_evaluation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_jury_evaluation')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(array('message' => __('You do not have permission to save evaluations.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member profile not found.', 'mobility-trailblazers')));
        }
        
        // Validate input
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid candidate ID.', 'mobility-trailblazers')));
        }
        
        // Check assignment
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        if (!in_array($candidate_id, $assigned_candidates)) {
            wp_send_json_error(array('message' => __('This candidate is not assigned to you.', 'mobility-trailblazers')));
        }
        
        // Get evaluation data
        $evaluation_data = array(
            'courage' => isset($_POST['courage']) ? intval($_POST['courage']) : 5,
            'innovation' => isset($_POST['innovation']) ? intval($_POST['innovation']) : 5,
            'implementation' => isset($_POST['implementation']) ? intval($_POST['implementation']) : 5,
            'relevance' => isset($_POST['relevance']) ? intval($_POST['relevance']) : 5,
            'visibility' => isset($_POST['visibility']) ? intval($_POST['visibility']) : 5,
            'comments' => isset($_POST['comments']) ? sanitize_textarea_field($_POST['comments']) : ''
        );
        
        // Validate scores (1-10)
        foreach (array('courage', 'innovation', 'implementation', 'relevance', 'visibility') as $criterion) {
            if ($evaluation_data[$criterion] < 1 || $evaluation_data[$criterion] > 10) {
                $evaluation_data[$criterion] = 5;
            }
        }
        
        // Calculate total score
        $total_score = array_sum(array(
            $evaluation_data['courage'],
            $evaluation_data['innovation'],
            $evaluation_data['implementation'],
            $evaluation_data['relevance'],
            $evaluation_data['visibility']
        ));
        
        // Get status
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'draft';
        
        if ($status === 'draft') {
            // Save as draft
            $evaluation_data['saved_at'] = current_time('mysql');
            update_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id, $evaluation_data);
            
            wp_send_json_success(array(
                'message' => __('Draft saved successfully!', 'mobility-trailblazers'),
                'total_score' => $total_score
            ));
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
                wp_send_json_error(array('message' => __('You have already evaluated this candidate.', 'mobility-trailblazers')));
            }
            
            // Save final evaluation
            $result = $wpdb->insert(
                $table_name,
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member->ID,
                    'courage' => $evaluation_data['courage'],
                    'innovation' => $evaluation_data['innovation'],
                    'implementation' => $evaluation_data['implementation'],
                    'relevance' => $evaluation_data['relevance'],
                    'visibility' => $evaluation_data['visibility'],
                    'total_score' => $total_score,
                    'comments' => $evaluation_data['comments'],
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
            );
            
            if ($result === false) {
                wp_send_json_error(array('message' => __('Failed to save evaluation. Please try again.', 'mobility-trailblazers')));
            }
            
            // Remove draft if exists
            delete_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id);
            
            // Trigger action for other plugins
            do_action('mt_evaluation_submitted', $candidate_id, $jury_member->ID, $evaluation_data);
            do_action_deprecated('evaluation_submitted', array($candidate_id, $jury_member->ID, $evaluation_data), '1.0.6', 'mt_evaluation_submitted');
            
            wp_send_json_success(array(
                'message' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                'total_score' => $total_score
            ));
        }
    }
} 