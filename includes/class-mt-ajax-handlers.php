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
     * Submit jury evaluation
     */
    public function submit_evaluation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(array('message' => __('You do not have permission to submit evaluations.', 'mobility-trailblazers')));
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
        
        // Check if candidate is assigned to this jury member
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        if (!in_array($candidate_id, $assigned_candidates)) {
            wp_send_json_error(array('message' => __('This candidate is not assigned to you.', 'mobility-trailblazers')));
        }
        
        // Get scores
        $scores = array(
            'courage' => isset($_POST['courage']) ? mt_sanitize_score($_POST['courage']) : 0,
            'innovation' => isset($_POST['innovation']) ? mt_sanitize_score($_POST['innovation']) : 0,
            'implementation' => isset($_POST['implementation']) ? mt_sanitize_score($_POST['implementation']) : 0,
            'relevance' => isset($_POST['relevance']) ? mt_sanitize_score($_POST['relevance']) : 0,
            'visibility' => isset($_POST['visibility']) ? mt_sanitize_score($_POST['visibility']) : 0,
        );
        
        // Calculate total score
        $total_score = array_sum($scores);
        
        // Get comments
        $comments = isset($_POST['comments']) ? sanitize_textarea_field($_POST['comments']) : '';
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Check if evaluation already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name 
             WHERE candidate_id = %d AND jury_member_id = %d AND evaluation_round = %s",
            $candidate_id,
            $jury_member->ID,
            'initial'
        ));
        
        if ($existing) {
            // Update existing evaluation
            $result = $wpdb->update(
                $table_name,
                array(
                    'courage_score' => $scores['courage'],
                    'innovation_score' => $scores['innovation'],
                    'implementation_score' => $scores['implementation'],
                    'relevance_score' => $scores['relevance'],
                    'visibility_score' => $scores['visibility'],
                    'total_score' => $total_score,
                    'comments' => $comments,
                    'evaluation_date' => current_time('mysql'),
                    'is_active' => 1,
                ),
                array('id' => $existing->id),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%d'),
                array('%d')
            );
        } else {
            // Insert new evaluation
            $result = $wpdb->insert(
                $table_name,
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member->ID,
                    'courage_score' => $scores['courage'],
                    'innovation_score' => $scores['innovation'],
                    'implementation_score' => $scores['implementation'],
                    'relevance_score' => $scores['relevance'],
                    'visibility_score' => $scores['visibility'],
                    'total_score' => $total_score,
                    'evaluation_round' => 'initial',
                    'comments' => $comments,
                    'evaluation_date' => current_time('mysql'),
                ),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s')
            );
        }
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to save evaluation.', 'mobility-trailblazers')));
        }
        
        // Remove draft if exists
        delete_user_meta(get_current_user_id(), 'mt_evaluation_draft_' . $candidate_id);
        
        // Fire action
        do_action('mt_evaluation_completed', $candidate_id, $jury_member->ID, $total_score);
        
        // Log activity
        mt_log('Evaluation submitted', 'info', array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $jury_member->ID,
            'total_score' => $total_score,
        ));
        
        wp_send_json_success(array(
            'message' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
            'total_score' => $total_score,
        ));
    }
    
    /**
     * Save evaluation draft
     */
    public function save_draft() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_submit_evaluations')) {
            wp_send_json_error(array('message' => __('You do not have permission to save drafts.', 'mobility-trailblazers')));
        }
        
        // Validate input
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid candidate.', 'mobility-trailblazers')));
        }
        
        // Save draft data
        $draft_data = array(
            'courage' => isset($_POST['courage']) ? intval($_POST['courage']) : 0,
            'innovation' => isset($_POST['innovation']) ? intval($_POST['innovation']) : 0,
            'implementation' => isset($_POST['implementation']) ? intval($_POST['implementation']) : 0,
            'relevance' => isset($_POST['relevance']) ? intval($_POST['relevance']) : 0,
            'visibility' => isset($_POST['visibility']) ? intval($_POST['visibility']) : 0,
            'comments' => isset($_POST['comments']) ? sanitize_textarea_field($_POST['comments']) : '',
            'saved_at' => current_time('mysql'),
        );
        
        // Save to user meta
        update_user_meta(get_current_user_id(), 'mt_evaluation_draft_' . $candidate_id, $draft_data);
        
        wp_send_json_success(array(
            'message' => __('Draft saved successfully!', 'mobility-trailblazers'),
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
     * Assign candidates to jury members
     */
    public function assign_candidates() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $candidate_ids = isset($_POST['candidate_ids']) ? array_map('intval', (array)$_POST['candidate_ids']) : array();
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        
        if (empty($candidate_ids) || !$jury_member_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mobility-trailblazers')));
        }
        
        // Verify jury member exists
        $jury_member = get_post($jury_member_id);
        if (!$jury_member || $jury_member->post_type !== 'mt_jury_member') {
            wp_send_json_error(array('message' => __('Invalid jury member.', 'mobility-trailblazers')));
        }
        
        $assigned_count = 0;
        
        foreach ($candidate_ids as $candidate_id) {
            // Verify candidate exists
            $candidate = get_post($candidate_id);
            if (!$candidate || $candidate->post_type !== 'mt_candidate') {
                continue;
            }
            
            // Get current assignments
            $current_assignments = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
            if (!is_array($current_assignments)) {
                $current_assignments = array();
            }
            
            // Add jury member if not already assigned
            if (!in_array($jury_member_id, $current_assignments)) {
                $current_assignments[] = $jury_member_id;
                update_post_meta($candidate_id, '_mt_assigned_jury_members', $current_assignments);
                
                // Fire action
                do_action('mt_after_candidate_assignment', $candidate_id, $jury_member_id);
                
                $assigned_count++;
            }
        }
        
        // Log activity
        mt_log('Candidates assigned', 'info', array(
            'jury_member_id' => $jury_member_id,
            'candidate_count' => $assigned_count,
        ));
        
        wp_send_json_success(array(
            'message' => sprintf(
                _n('%d candidate assigned successfully.', '%d candidates assigned successfully.', $assigned_count, 'mobility-trailblazers'),
                $assigned_count
            ),
            'assigned_count' => $assigned_count,
        ));
    }
    
    /**
     * Auto-assign candidates
     */
    public function auto_assign() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $algorithm = isset($_POST['algorithm']) ? sanitize_text_field($_POST['algorithm']) : 'balanced';
        $candidates_per_jury = isset($_POST['candidates_per_jury']) ? intval($_POST['candidates_per_jury']) : 20;
        $preserve_existing = isset($_POST['preserve_existing']) ? (bool)$_POST['preserve_existing'] : true;
        
        // Get all published candidates
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'rand'
        ));
        
        // Get all published jury members
        $jury_members = get_posts(array(
            'post_type' => mt_get_jury_post_type(),
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        // Debug logging
        error_log('Auto-assign: Found ' . count($candidates) . ' candidates and ' . count($jury_members) . ' jury members');
        
        // Check if we have candidates and jury members
        if (empty($candidates) || empty($jury_members)) {
            wp_send_json_error(array(
                'message' => __('No candidates or jury members found. Please ensure you have published candidates and jury members.', 'mobility-trailblazers'),
                'debug' => array(
                    'candidates_count' => count($candidates),
                    'jury_count' => count($jury_members)
                )
            ));
        }
        
        // Clear existing assignments if not preserving
        if (!$preserve_existing) {
            foreach ($candidates as $candidate) {
                delete_post_meta($candidate->ID, '_mt_assigned_jury_members');
            }
        }
        
        $assignments = array();
        
        switch ($algorithm) {
            case 'balanced':
                $assignments = $this->balanced_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing);
                break;
                
            case 'random':
                $assignments = $this->random_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing);
                break;
                
            case 'expertise':
                $assignments = $this->expertise_based_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing);
                break;
                
            case 'category':
                $assignments = $this->category_based_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing);
                break;
        }
        
        // Apply assignments
        $total_assignments = 0;
        
        foreach ($assignments as $candidate_id => $jury_ids) {
            if (!empty($jury_ids)) {
                update_post_meta($candidate_id, '_mt_assigned_jury_members', $jury_ids);
                $total_assignments += count($jury_ids);
                
                // Fire actions
                foreach ($jury_ids as $jury_id) {
                    do_action('mt_after_candidate_assignment', $candidate_id, $jury_id);
                }
            }
        }
        
        // Log activity
        mt_log('Auto-assignment completed', 'info', array(
            'algorithm' => $algorithm,
            'total_assignments' => $total_assignments,
            'candidates_per_jury' => $candidates_per_jury,
        ));
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Auto-assignment completed. %d assignments made.', 'mobility-trailblazers'),
                $total_assignments
            ),
            'total_assignments' => $total_assignments
        ));
    }
    
    /**
     * Balanced assignment algorithm
     */
    private function balanced_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing) {
        $assignments = array();
        $jury_counts = array();
        
        // Initialize jury counts
        foreach ($jury_members as $jury) {
            $jury_counts[$jury->ID] = 0;
        }
        
        // If preserving existing, count current assignments
        if ($preserve_existing) {
            foreach ($candidates as $candidate) {
                $existing = get_post_meta($candidate->ID, '_mt_assigned_jury_members', true);
                if (is_array($existing)) {
                    $assignments[$candidate->ID] = $existing;
                    foreach ($existing as $jury_id) {
                        if (isset($jury_counts[$jury_id])) {
                            $jury_counts[$jury_id]++;
                        }
                    }
                }
            }
        }
        
        // Assign candidates to jury members
        foreach ($candidates as $candidate) {
            // Skip if already has assignments and preserving
            if ($preserve_existing && !empty($assignments[$candidate->ID])) {
                continue;
            }
            
            // Initialize assignments for this candidate
            if (!isset($assignments[$candidate->ID])) {
                $assignments[$candidate->ID] = array();
            }
            
            // Sort jury members by assignment count (ascending)
            asort($jury_counts);
            
            // Assign to least loaded jury members (typically 3 per candidate)
            $assigned_count = 0;
            foreach ($jury_counts as $jury_id => $count) {
                // Check if jury member hasn't exceeded their limit
                if ($count < $candidates_per_jury) {
                    $assignments[$candidate->ID][] = $jury_id;
                    $jury_counts[$jury_id]++;
                    $assigned_count++;
                    
                    // Usually assign 3 jury members per candidate
                    if ($assigned_count >= 3) {
                        break;
                    }
                }
            }
        }
        
        return $assignments;
    }
    
    /**
     * Random assignment algorithm
     */
    private function random_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing) {
        $assignments = array();
        $jury_ids = wp_list_pluck($jury_members, 'ID');
        
        // Get existing assignments if preserving
        if ($preserve_existing) {
            foreach ($candidates as $candidate) {
                $existing = get_post_meta($candidate->ID, '_mt_assigned_jury_members', true);
                if (is_array($existing) && !empty($existing)) {
                    $assignments[$candidate->ID] = $existing;
                }
            }
        }
        
        // Assign candidates randomly
        foreach ($candidates as $candidate) {
            // Skip if already has assignments and preserving
            if ($preserve_existing && !empty($assignments[$candidate->ID])) {
                continue;
            }
            
            // Initialize assignments for this candidate
            $assignments[$candidate->ID] = array();
            
            // Randomly select 3 jury members
            $available_jury = $jury_ids;
            shuffle($available_jury);
            
            // Assign 3 jury members
            $assignments[$candidate->ID] = array_slice($available_jury, 0, 3);
        }
        
        return $assignments;
    }
    
    /**
     * Expertise-based assignment algorithm
     */
    private function expertise_based_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing) {
        // For now, fall back to balanced assignment
        // TODO: Implement expertise matching based on categories
        return $this->balanced_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing);
    }
    
    /**
     * Category-based assignment algorithm
     */
    private function category_based_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing) {
        // For now, fall back to balanced assignment
        // TODO: Implement category-based assignment
        return $this->balanced_assignment($candidates, $jury_members, $candidates_per_jury, $preserve_existing);
    }
    
    /**
     * Remove assignment
     */
    public function remove_assignment() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_admin_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error(array('message' => __('You do not have permission to manage assignments.', 'mobility-trailblazers')));
        }
        
        // Get parameters
        $candidate_id = isset($_POST['candidate_id']) ? intval($_POST['candidate_id']) : 0;
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        
        if (!$candidate_id || !$jury_member_id) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'mobility-trailblazers')));
        }
        
        // Get current assignments
        $assignments = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
        
        if (is_array($assignments)) {
            $key = array_search($jury_member_id, $assignments);
            if ($key !== false) {
                unset($assignments[$key]);
                $assignments = array_values($assignments); // Re-index array
                
                update_post_meta($candidate_id, '_mt_assigned_jury_members', $assignments);
                
                wp_send_json_success(array(
                    'message' => __('Assignment removed successfully.', 'mobility-trailblazers'),
                ));
            }
        }
        
        wp_send_json_error(array('message' => __('Assignment not found.', 'mobility-trailblazers')));
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
        
        // Check if already voted (by email)
        global $wpdb;
        $votes_table = $wpdb->prefix . 'mt_votes';
        
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $votes_table 
             WHERE candidate_id = %d 
             AND JSON_EXTRACT(criteria_scores, '$.voter_email') = %s 
             AND is_active = 1",
            $candidate_id,
            $voter_email
        ));
        
        if ($existing_vote) {
            wp_send_json_error(array('message' => __('You have already voted for this candidate.', 'mobility-trailblazers')));
        }
        
        // Save vote
        $result = $wpdb->insert(
            $votes_table,
            array(
                'candidate_id' => $candidate_id,
                'jury_member_id' => 0, // Public vote
                'user_id' => get_current_user_id() ?: 0,
                'criteria_scores' => json_encode(array(
                    'voter_email' => $voter_email,
                    'voter_name' => $voter_name,
                    'vote_type' => 'public',
                )),
                'total_score' => 1, // Each public vote counts as 1
                'voting_phase' => 'public',
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%d', '%d', '%s', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to save vote. Please try again.', 'mobility-trailblazers')));
        }
        
        // Update candidate vote count
        $current_votes = get_post_meta($candidate_id, '_mt_public_votes', true);
        update_post_meta($candidate_id, '_mt_public_votes', intval($current_votes) + 1);
        
        // Send confirmation email
        $subject = __('Vote Confirmation - Mobility Trailblazers Award', 'mobility-trailblazers');
        $message = sprintf(
            __('Dear %s,

Thank you for voting in the Mobility Trailblazers Award!

You have successfully voted for: %s

Your vote has been recorded and will be counted in the public voting results.

Best regards,
%s', 'mobility-trailblazers'),
            $voter_name ?: __('Voter', 'mobility-trailblazers'),
            $candidate->post_title,
            get_bloginfo('name')
        );
        
        mt_send_email($voter_email, $subject, $message);
        
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
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check if user is jury member
        if (!mt_is_jury_member()) {
            wp_send_json_error(array('message' => __('Access denied.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member not found.', 'mobility-trailblazers')));
        }
        
        // Get assigned candidates
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        $candidates_data = array();
        
        $total_assigned = count($assigned_candidates);
        $evaluated_count = 0;
        $draft_count = 0;
        
        foreach ($assigned_candidates as $candidate_id) {
            $candidate = get_post($candidate_id);
            if (!$candidate) continue;
            
            // Get candidate details
            $company = get_post_meta($candidate_id, '_mt_company', true);
            $position = get_post_meta($candidate_id, '_mt_position', true);
            $categories = wp_get_post_terms($candidate_id, 'mt_candidate_category', array('fields' => 'names'));
            
            // Get evaluation status
            $evaluation_status = '';
            $total_score = null;
            
            if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
                $evaluation_status = 'completed';
                $evaluated_count++;
                
                $evaluation = mt_get_evaluation($candidate_id, $jury_member->ID);
                if ($evaluation) {
                    $total_score = intval($evaluation->courage) + intval($evaluation->innovation) + 
                                 intval($evaluation->implementation) + intval($evaluation->relevance) + 
                                 intval($evaluation->visibility);
                }
            } elseif (mt_has_draft_evaluation($candidate_id, $jury_member->ID)) {
                $evaluation_status = 'draft';
                $draft_count++;
            }
            
            $candidates_data[] = array(
                'id' => $candidate_id,
                'name' => $candidate->post_title,
                'company' => $company,
                'position' => $position,
                'categories' => $categories,
                'evaluation_status' => $evaluation_status,
                'total_score' => $total_score
            );
        }
        
        $completion_rate = $total_assigned > 0 ? round(($evaluated_count / $total_assigned) * 100) : 0;
        
        wp_send_json_success(array(
            'assigned_count' => $total_assigned,
            'evaluated_count' => $evaluated_count,
            'draft_count' => $draft_count,
            'completion_rate' => $completion_rate,
            'candidates' => $candidates_data
        ));
    }

    /**
     * Get candidate evaluation data
     */
    public function get_candidate_evaluation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check if user is jury member
        if (!mt_is_jury_member()) {
            wp_send_json_error(array('message' => __('Access denied.', 'mobility-trailblazers')));
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid candidate ID.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member not found.', 'mobility-trailblazers')));
        }
        
        // Verify this candidate is assigned to the jury member
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        
        if (!in_array($candidate_id, $assigned_candidates)) {
            wp_send_json_error(array('message' => __('This candidate is not assigned to you.', 'mobility-trailblazers')));
        }
        
        // Get candidate data
        $candidate = get_post($candidate_id);
        
        if (!$candidate) {
            wp_send_json_error(array('message' => __('Candidate not found.', 'mobility-trailblazers')));
        }
        
        $candidate_data = array(
            'id' => $candidate_id,
            'name' => $candidate->post_title,
            'company' => get_post_meta($candidate_id, '_mt_company', true),
            'position' => get_post_meta($candidate_id, '_mt_position', true),
        );
        
        // Get existing evaluation if any
        $evaluation_data = null;
        
        // Check for completed evaluation
        $evaluation = mt_get_evaluation($candidate_id, $jury_member->ID);
        
        if ($evaluation) {
            $evaluation_data = array(
                'courage' => $evaluation->courage,
                'innovation' => $evaluation->innovation,
                'implementation' => $evaluation->implementation,
                'relevance' => $evaluation->relevance,
                'visibility' => $evaluation->visibility,
                'comments' => $evaluation->comments,
                'status' => 'completed'
            );
        } else {
            // Check for draft evaluation
            $draft = get_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id, true);
            
            if ($draft) {
                $evaluation_data = array(
                    'courage' => isset($draft['courage']) ? $draft['courage'] : 5,
                    'innovation' => isset($draft['innovation']) ? $draft['innovation'] : 5,
                    'implementation' => isset($draft['implementation']) ? $draft['implementation'] : 5,
                    'relevance' => isset($draft['relevance']) ? $draft['relevance'] : 5,
                    'visibility' => isset($draft['visibility']) ? $draft['visibility'] : 5,
                    'comments' => isset($draft['comments']) ? $draft['comments'] : '',
                    'status' => 'draft'
                );
            }
        }
        
        wp_send_json_success(array(
            'candidate' => $candidate_data,
            'evaluation' => $evaluation_data
        ));
    }

    /**
     * Save evaluation (draft or final)
     */
    public function save_evaluation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        // Check if user is jury member
        if (!mt_is_jury_member()) {
            wp_send_json_error(array('message' => __('Access denied.', 'mobility-trailblazers')));
        }
        
        // Get evaluation data
        $evaluation = $_POST['evaluation'];
        $candidate_id = intval($evaluation['candidate_id']);
        $status = sanitize_text_field($evaluation['status']);
        
        if (!$candidate_id) {
            wp_send_json_error(array('message' => __('Invalid candidate ID.', 'mobility-trailblazers')));
        }
        
        // Get jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        
        if (!$jury_member) {
            wp_send_json_error(array('message' => __('Jury member not found.', 'mobility-trailblazers')));
        }
        
        // Verify this candidate is assigned to the jury member
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        
        if (!in_array($candidate_id, $assigned_candidates)) {
            wp_send_json_error(array('message' => __('This candidate is not assigned to you.', 'mobility-trailblazers')));
        }
        
        // Prepare evaluation data
        $evaluation_data = array(
            'courage' => intval($evaluation['courage']),
            'innovation' => intval($evaluation['innovation']),
            'implementation' => intval($evaluation['implementation']),
            'relevance' => intval($evaluation['relevance']),
            'visibility' => intval($evaluation['visibility']),
            'comments' => sanitize_textarea_field($evaluation['comments'])
        );
        
        if ($status === 'draft') {
            // Save as draft in user meta
            update_user_meta(get_current_user_id(), 'mt_draft_evaluation_' . $candidate_id, $evaluation_data);
            
            wp_send_json_success(array(
                'message' => __('Evaluation saved as draft.', 'mobility-trailblazers')
            ));
        } else {
            // Check if already evaluated
            if (mt_has_evaluated($candidate_id, $jury_member->ID)) {
                wp_send_json_error(array('message' => __('You have already evaluated this candidate.', 'mobility-trailblazers')));
            }
            
            // Save final evaluation
            global $wpdb;
            
            $table_name = $wpdb->prefix . 'mt_evaluations';
            
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
                    'total_score' => array_sum(array(
                        $evaluation_data['courage'],
                        $evaluation_data['innovation'],
                        $evaluation_data['implementation'],
                        $evaluation_data['relevance'],
                        $evaluation_data['visibility']
                    )),
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
            
            // Trigger action for other plugins to hook into
            do_action('mt_evaluation_submitted', $candidate_id, $jury_member->ID, $evaluation_data);
            
            wp_send_json_success(array(
                'message' => __('Evaluation submitted successfully!', 'mobility-trailblazers')
            ));
        }
    }
} 