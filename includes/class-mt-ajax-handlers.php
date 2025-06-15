<?php
/**
 * AJAX Handlers
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
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
     * Initialize the class
     */
    public function __construct() {
        // Assignment AJAX handlers
        add_action('wp_ajax_mt_assign_candidates', array($this, 'handle_assign_candidates'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'handle_auto_assign'));
        add_action('wp_ajax_mt_get_assignment_stats', array($this, 'handle_get_assignment_stats'));
        add_action('wp_ajax_mt_clear_assignments', array($this, 'handle_clear_assignments'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'handle_export_assignments'));
        
        // Vote reset AJAX handlers
        add_action('wp_ajax_mt_reset_vote', array($this, 'handle_ajax_reset_vote'));
        add_action('wp_ajax_mt_bulk_reset_candidate', array($this, 'handle_ajax_bulk_reset'));
        add_action('wp_ajax_mt_get_reset_history', array($this, 'handle_ajax_get_reset_history'));
        add_action('wp_ajax_mt_get_jury_stats', array($this, 'handle_ajax_get_jury_stats'));
        
        // New vote reset handlers for the interface
        add_action('wp_ajax_mt_reset_individual_vote', array($this, 'handle_reset_individual_vote'));
        add_action('wp_ajax_mt_reset_candidate_votes', array($this, 'handle_reset_candidate_votes'));
        add_action('wp_ajax_mt_reset_jury_votes', array($this, 'handle_reset_jury_votes'));
        add_action('wp_ajax_mt_reset_phase_transition', array($this, 'handle_reset_phase_transition'));
        add_action('wp_ajax_mt_reset_full_system', array($this, 'handle_reset_full_system'));
        add_action('wp_ajax_mt_create_full_backup', array($this, 'handle_create_full_backup'));
        add_action('wp_ajax_mt_export_votes', array($this, 'handle_export_votes'));
        add_action('wp_ajax_mt_export_evaluations', array($this, 'handle_export_evaluations'));
        
        // Backup AJAX handlers
        add_action('wp_ajax_mt_export_backup_history', array($this, 'handle_export_backup_history'));
        
        // Candidate details AJAX
        add_action('wp_ajax_mt_get_candidate_details', array($this, 'ajax_get_candidate_details'));
        add_action('wp_ajax_mt_get_candidates_for_assignment', array($this, 'handle_get_candidates_for_assignment'));
        
        // System sync handlers
        add_action('wp_ajax_mt_sync_system', array($this, 'handle_sync_system'));
        add_action('wp_ajax_mt_get_progress_data', array($this, 'handle_get_progress_data'));
    }
    
    /**
     * Handle candidate assignment
     */
    public function handle_assign_candidates() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Support both jury_id and jury_member_id for compatibility
        $jury_id = isset($_POST['jury_id']) ? intval($_POST['jury_id']) : 
                   (isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0);
        $candidate_ids = isset($_POST['candidate_ids']) ? array_map('intval', $_POST['candidate_ids']) : array();
        
        if (!$jury_id) {
            wp_send_json_error('Invalid jury member ID');
        }
        
        // Update assignments
        update_post_meta($jury_id, 'assigned_candidates', $candidate_ids);
        
        // Notify jury member if requested
        if (isset($_POST['notify']) && $_POST['notify'] === 'true') {
            $this->notify_jury_member_assignment($jury_id, $candidate_ids);
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Assigned %d candidates to jury member', 'mobility-trailblazers'), count($candidate_ids))
        ));
    }
    
    /**
     * Handle auto-assignment
     */
    public function handle_auto_assign() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $candidates_per_jury = intval($_POST['candidates_per_jury']);
        $overlap = isset($_POST['overlap']) ? intval($_POST['overlap']) : 0;
        
        if ($candidates_per_jury < 1) {
            wp_send_json_error('Invalid number of candidates per jury');
        }
        
        // Get all active candidates and jury members
        $candidates = $this->get_candidates_for_assignment();
        $jury_members = $this->get_jury_members_for_assignment();
        
        if (empty($candidates) || empty($jury_members)) {
            wp_send_json_error('No candidates or jury members available for assignment');
        }
        
        // Perform auto-assignment
        $assignments = array();
        $candidate_count = count($candidates);
        $jury_count = count($jury_members);
        
        // Shuffle candidates for random distribution
        shuffle($candidates);
        
        $candidate_index = 0;
        foreach ($jury_members as $jury) {
            $assigned = array();
            
            for ($i = 0; $i < $candidates_per_jury; $i++) {
                if ($candidate_index >= $candidate_count) {
                    $candidate_index = 0; // Wrap around if needed
                }
                $assigned[] = $candidates[$candidate_index]->ID;
                $candidate_index++;
            }
            
            update_post_meta($jury->ID, 'assigned_candidates', $assigned);
            $assignments[$jury->ID] = $assigned;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Auto-assigned %d candidates to %d jury members', 'mobility-trailblazers'), 
                $candidates_per_jury, $jury_count),
            'assignments' => $assignments
        ));
    }
    
    /**
     * Get assignment statistics
     */
    public function handle_get_assignment_stats() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        $candidate_counts = wp_count_posts('mt_candidate');
        $jury_counts = wp_count_posts('mt_jury');
        
        $stats = array(
            'total_candidates' => isset($candidate_counts->publish) ? $candidate_counts->publish : 0,
            'total_jury' => isset($jury_counts->publish) ? $jury_counts->publish : 0,
            'assigned_candidates' => 0,
            'unassigned_candidates' => 0,
            'assignments_per_jury' => array()
        );
        
        // Get all assignments
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $assigned_candidate_ids = array();
        
        foreach ($jury_members as $jury) {
            $assignments = get_post_meta($jury->ID, 'assigned_candidates', true);
            if (is_array($assignments)) {
                $count = count($assignments);
                $stats['assignments_per_jury'][$jury->ID] = $count;
                $assigned_candidate_ids = array_merge($assigned_candidate_ids, $assignments);
            } else {
                $stats['assignments_per_jury'][$jury->ID] = 0;
            }
        }
        
        $unique_assigned = array_unique($assigned_candidate_ids);
        $stats['assigned_candidates'] = count($unique_assigned);
        $stats['unassigned_candidates'] = $stats['total_candidates'] - $stats['assigned_candidates'];
        
        wp_send_json_success($stats);
    }
    
    /**
     * Clear all assignments
     */
    public function handle_clear_assignments() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $cleared = 0;
        foreach ($jury_members as $jury) {
            delete_post_meta($jury->ID, 'assigned_candidates');
            $cleared++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Cleared assignments for %d jury members', 'mobility-trailblazers'), $cleared)
        ));
    }
    
    /**
     * Export assignments
     */
    public function handle_export_assignments() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_export_data')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $format = isset($_POST['format']) ? $_POST['format'] : 'csv';
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $export_data = array();
        
        foreach ($jury_members as $jury) {
            $assignments = get_post_meta($jury->ID, 'assigned_candidates', true);
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            $user = $user_id ? get_user_by('id', $user_id) : null;
            
            if (is_array($assignments) && !empty($assignments)) {
                foreach ($assignments as $candidate_id) {
                    $candidate = get_post($candidate_id);
                    if ($candidate) {
                        $export_data[] = array(
                            'jury_name' => $jury->post_title,
                            'jury_email' => $user ? $user->user_email : '',
                            'candidate_name' => $candidate->post_title,
                            'candidate_id' => $candidate_id,
                            'assignment_date' => get_post_meta($jury->ID, 'assignment_date', true) ?: ''
                        );
                    }
                }
            }
        }
        
        if ($format === 'json') {
            wp_send_json_success(array(
                'data' => $export_data,
                'filename' => 'assignments_' . date('Y-m-d_H-i-s') . '.json'
            ));
        } else {
            // CSV format
            $csv_data = $this->array_to_csv($export_data);
            wp_send_json_success(array(
                'data' => $csv_data,
                'filename' => 'assignments_' . date('Y-m-d_H-i-s') . '.csv'
            ));
        }
    }
    
    /**
     * Handle vote reset AJAX
     */
    public function handle_ajax_reset_vote() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('mt_manage_voting')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $vote_id = intval($_POST['vote_id']);
        $reason = sanitize_text_field($_POST['reason']);
        
        if (!$vote_id) {
            wp_send_json_error('Invalid vote ID');
        }
        
        // Use the REST API handler internally
        $rest_api = new MT_REST_API();
        $request = new WP_REST_Request('POST', '/mt/v1/vote/reset');
        $request->set_param('vote_id', $vote_id);
        $request->set_param('reason', $reason);
        
        $response = $rest_api->handle_reset_vote($request);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response->data);
        }
    }
    
    /**
     * Handle bulk reset AJAX
     */
    public function handle_ajax_bulk_reset() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('mt_manage_voting')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $type = sanitize_text_field($_POST['type']);
        $candidate_id = intval($_POST['candidate_id']);
        $reason = sanitize_text_field($_POST['reason']);
        
        // Use the REST API handler internally
        $rest_api = new MT_REST_API();
        $request = new WP_REST_Request('POST', '/mt/v1/vote/bulk-reset');
        $request->set_param('type', $type);
        $request->set_param('candidate_id', $candidate_id);
        $request->set_param('reason', $reason);
        
        $response = $rest_api->handle_bulk_reset($request);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response->data);
        }
    }
    
    /**
     * Get reset history AJAX
     */
    public function handle_ajax_get_reset_history() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('mt_view_all_evaluations')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        
        // Use the REST API handler internally
        $rest_api = new MT_REST_API();
        $request = new WP_REST_Request('GET', '/mt/v1/vote/reset-history');
        $request->set_param('page', $page);
        
        $response = $rest_api->get_reset_history($request);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        } else {
            wp_send_json_success($response->data);
        }
    }
    
    /**
     * Get jury statistics AJAX
     */
    public function handle_ajax_get_jury_stats() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('mt_view_all_evaluations')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $stats = array();
        
        // Get all jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($jury_members as $jury) {
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            
            // Get vote count
            $vote_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE jury_member_id = %d",
                $user_id
            ));
            
            // Get evaluation count
            $eval_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE jury_member_id = %d",
                $user_id
            ));
            
            $stats[] = array(
                'jury_name' => $jury->post_title,
                'votes' => $vote_count,
                'evaluations' => $eval_count
            );
        }
        
        wp_send_json_success($stats);
    }
    
    /**
     * Export backup history
     */
    public function handle_export_backup_history() {
        check_ajax_referer('mt_backup_nonce', 'nonce');
        
        if (!current_user_can('mt_export_data')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $format = isset($_POST['format']) ? $_POST['format'] : 'csv';
        
        // Get all backups
        $backups = get_posts(array(
            'post_type' => 'mt_backup',
            'posts_per_page' => -1,
            'post_status' => 'private',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $export_data = array();
        
        foreach ($backups as $backup) {
            $export_data[] = array(
                'id' => $backup->ID,
                'date' => $backup->post_date,
                'type' => get_post_meta($backup->ID, 'backup_type', true),
                'description' => get_post_meta($backup->ID, 'description', true),
                'size' => get_post_meta($backup->ID, 'backup_size', true),
                'created_by' => get_the_author_meta('display_name', $backup->post_author)
            );
        }
        
        if ($format === 'json') {
            wp_send_json_success(array(
                'data' => $export_data,
                'filename' => 'backup_history_' . date('Y-m-d_H-i-s') . '.json'
            ));
        } else {
            // CSV format
            $csv_data = $this->array_to_csv($export_data);
            wp_send_json_success(array(
                'data' => $csv_data,
                'filename' => 'backup_history_' . date('Y-m-d_H-i-s') . '.csv'
            ));
        }
    }
    
    /**
     * Get candidate details AJAX
     */
    public function ajax_get_candidate_details() {
        check_ajax_referer('mt_jury_nonce', 'nonce');
        
        $candidate_id = intval($_POST['candidate_id']);
        
        if (!$candidate_id) {
            wp_send_json_error('Invalid candidate ID');
        }
        
        $candidate = get_post($candidate_id);
        
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            wp_send_json_error('Candidate not found');
        }
        
        // Get candidate details
        $details = array(
            'title' => $candidate->post_title,
            'content' => apply_filters('the_content', $candidate->post_content),
            'excerpt' => $candidate->post_excerpt,
            'thumbnail' => get_the_post_thumbnail_url($candidate_id, 'large'),
            'meta' => array(
                'organization' => get_post_meta($candidate_id, 'organization', true),
                'website' => get_post_meta($candidate_id, 'website', true),
                'contact_person' => get_post_meta($candidate_id, 'contact_person', true),
                'email' => get_post_meta($candidate_id, 'email', true),
                'innovation_description' => get_post_meta($candidate_id, 'innovation_description', true)
            )
        );
        
        wp_send_json_success($details);
    }
    
    /**
     * Handle system sync
     */
    public function handle_sync_system() {
        check_ajax_referer('mt_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $results = array(
            'users_synced' => 0,
            'assignments_updated' => 0,
            'errors' => array()
        );
        
        // Sync jury users
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($jury_members as $jury) {
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            $email = get_post_meta($jury->ID, 'email', true);
            
            if (!$user_id && $email) {
                $user = get_user_by('email', $email);
                if ($user) {
                    update_post_meta($jury->ID, 'user_id', $user->ID);
                    $results['users_synced']++;
                }
            }
        }
        
        // Update assignment metadata
        foreach ($jury_members as $jury) {
            $assignments = get_post_meta($jury->ID, 'assigned_candidates', true);
            if (is_array($assignments) && !empty($assignments)) {
                update_post_meta($jury->ID, 'assignment_date', current_time('mysql'));
                $results['assignments_updated']++;
            }
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Get progress data
     */
    public function handle_get_progress_data() {
        check_ajax_referer('mt_progress_nonce', 'nonce');
        
        if (!current_user_can('mt_view_all_evaluations')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        $data = array(
            'overview' => array(),
            'by_category' => array(),
            'by_jury' => array(),
            'timeline' => array()
        );
        
        // Overview stats
        $data['overview'] = array(
            'total_candidates' => wp_count_posts('mt_candidate')->publish,
            'total_jury' => wp_count_posts('mt_jury')->publish,
            'total_votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes"),
            'total_evaluations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores")
        );
        
        // Progress by category
        $categories = get_terms(array(
            'taxonomy' => 'mt_category',
            'hide_empty' => false
        ));
        
        foreach ($categories as $category) {
            $candidates_in_cat = get_posts(array(
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'mt_category',
                        'terms' => $category->term_id
                    )
                )
            ));
            
            $candidate_ids = wp_list_pluck($candidates_in_cat, 'ID');
            $votes_count = 0;
            
            if (!empty($candidate_ids)) {
                $votes_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE candidate_id IN (" . 
                    implode(',', array_fill(0, count($candidate_ids), '%d')) . ")",
                    ...$candidate_ids
                ));
            }
            
            $data['by_category'][] = array(
                'category' => $category->name,
                'candidates' => count($candidates_in_cat),
                'votes' => $votes_count
            );
        }
        
        // Progress by jury member
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($jury_members as $jury) {
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            $assigned = get_post_meta($jury->ID, 'assigned_candidates', true);
            $assigned_count = is_array($assigned) ? count($assigned) : 0;
            
            $completed = 0;
            if ($user_id) {
                $completed = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE jury_member_id = %d",
                    $user_id
                ));
            }
            
            $data['by_jury'][] = array(
                'name' => $jury->post_title,
                'assigned' => $assigned_count,
                'completed' => $completed,
                'completion_rate' => $assigned_count > 0 ? round(($completed / $assigned_count) * 100, 1) : 0
            );
        }
        
        // Timeline data (last 30 days)
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $votes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE DATE(vote_date) = %s",
                $date
            ));
            
            $data['timeline'][] = array(
                'date' => $date,
                'votes' => $votes
            );
        }
        
        wp_send_json_success($data);
    }
    
    // Helper methods
    
    /**
     * Get candidates for assignment
     */
    private function get_candidates_for_assignment() {
        return get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
    }
    
    /**
     * Get jury members for assignment
     */
    private function get_jury_members_for_assignment() {
        return get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
    }
    
    /**
     * Notify jury member of assignment
     */
    private function notify_jury_member_assignment($jury_member_id, $candidate_ids) {
        $jury_member = get_post($jury_member_id);
        if (!$jury_member) {
            return false;
        }
        
        $user_id = get_post_meta($jury_member_id, 'user_id', true);
        if (!$user_id) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $candidate_count = count($candidate_ids);
        
        $subject = sprintf(
            __('[%s] New Candidate Assignments', 'mobility-trailblazers'),
            get_bloginfo('name')
        );
        
        $message = sprintf(
            __('Dear %s,<br><br>You have been assigned %d new candidates to evaluate for the Mobility Trailblazers Award.<br><br>Please log in to your jury dashboard to begin the evaluation process.<br><br>Dashboard URL: %s<br><br>Thank you for your participation!<br><br>Best regards,<br>The Mobility Trailblazers Team', 'mobility-trailblazers'),
            $jury_member->post_title,
            $candidate_count,
            admin_url('admin.php?page=mt-jury-evaluation')
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Get candidates for assignment
     */
    public function handle_get_candidates_for_assignment() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        $jury_id = isset($_POST['jury_id']) ? intval($_POST['jury_id']) : 0;
        
        // Get all candidates
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Get current assignments for this jury member (if specified)
        $assigned = array();
        if ($jury_id > 0) {
            $assigned = get_post_meta($jury_id, 'assigned_candidates', true);
            if (!is_array($assigned)) {
                $assigned = array();
            }
        }
        
        $candidate_data = array();
        foreach ($candidates as $candidate) {
            // Get candidate meta data
            $company = get_post_meta($candidate->ID, 'company_name', true);
            $category_terms = wp_get_post_terms($candidate->ID, 'mt_category');
            $category = !empty($category_terms) && !is_wp_error($category_terms) ? $category_terms[0]->name : '';
            
            $candidate_data[] = array(
                'id' => $candidate->ID,
                'title' => $candidate->post_title,
                'company' => $company,
                'category' => $category,
                'assigned' => in_array($candidate->ID, $assigned)
            );
        }
        
        wp_send_json_success($candidate_data);
    }
    
    /**
     * Convert array to CSV
     */
    private function array_to_csv($data) {
        if (empty($data)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, array_keys($data[0]));
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    // New Vote Reset AJAX Handlers
    
    /**
     * Handle individual vote reset
     */
    public function handle_reset_individual_vote() {
        // Check nonce
        if (!check_ajax_referer('mt_individual_reset', 'mt_individual_reset_nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_voting')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        $jury_member_id = intval($_POST['jury_member_id']);
        $reason = sanitize_textarea_field($_POST['reason']);
        
        if (!$candidate_id || !$jury_member_id) {
            wp_send_json_error('Invalid candidate or jury member ID');
        }
        
        // Use the vote reset manager
        if (class_exists('MT_Vote_Reset_Manager')) {
            $reset_manager = new MT_Vote_Reset_Manager();
            $result = $reset_manager->reset_individual_vote($candidate_id, $jury_member_id, $reason);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } else {
            wp_send_json_error('Vote Reset Manager not available');
        }
    }
    
    /**
     * Handle candidate votes reset
     */
    public function handle_reset_candidate_votes() {
        // Check nonce
        if (!check_ajax_referer('mt_candidate_reset', 'mt_candidate_reset_nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_voting')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        
        if (!$candidate_id) {
            wp_send_json_error('Invalid candidate ID');
        }
        
        // Use the vote reset manager
        if (class_exists('MT_Vote_Reset_Manager')) {
            $reset_manager = new MT_Vote_Reset_Manager();
            $result = $reset_manager->bulk_reset_votes('all_candidate_votes', array(
                'candidate_id' => $candidate_id
            ));
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } else {
            wp_send_json_error('Vote Reset Manager not available');
        }
    }
    
    /**
     * Handle jury member votes reset
     */
    public function handle_reset_jury_votes() {
        // Check nonce
        if (!check_ajax_referer('mt_jury_reset', 'mt_jury_reset_nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_manage_voting')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $jury_member_id = intval($_POST['jury_member_id']);
        
        if (!$jury_member_id) {
            wp_send_json_error('Invalid jury member ID');
        }
        
        // Get the user ID associated with this jury member
        $user_id = get_post_meta($jury_member_id, 'user_id', true);
        if (!$user_id) {
            wp_send_json_error('No user associated with this jury member');
        }
        
        // Use the vote reset manager
        if (class_exists('MT_Vote_Reset_Manager')) {
            $reset_manager = new MT_Vote_Reset_Manager();
            $result = $reset_manager->bulk_reset_votes('all_user_votes', array(
                'user_id' => $user_id
            ));
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } else {
            wp_send_json_error('Vote Reset Manager not available');
        }
    }
    
    /**
     * Handle phase transition reset
     */
    public function handle_reset_phase_transition() {
        // Check nonce
        if (!check_ajax_referer('mt_phase_reset', 'mt_phase_reset_nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Admin access required');
        }
        
        $send_notifications = isset($_POST['send_notifications']) && $_POST['send_notifications'] === '1';
        
        // Use the vote reset manager
        if (class_exists('MT_Vote_Reset_Manager')) {
            $reset_manager = new MT_Vote_Reset_Manager();
            $result = $reset_manager->bulk_reset_votes('phase_transition', array(
                'notify_jury' => $send_notifications
            ));
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } else {
            wp_send_json_error('Vote Reset Manager not available');
        }
    }
    
    /**
     * Handle full system reset
     */
    public function handle_reset_full_system() {
        // Check nonce
        if (!check_ajax_referer('mt_full_reset', 'mt_full_reset_nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Admin access required');
        }
        
        $confirm = isset($_POST['confirm_full_reset']) && $_POST['confirm_full_reset'] === '1';
        $send_notifications = isset($_POST['send_notifications']) && $_POST['send_notifications'] === '1';
        
        if (!$confirm) {
            wp_send_json_error('Full reset confirmation required');
        }
        
        // Use the vote reset manager
        if (class_exists('MT_Vote_Reset_Manager')) {
            $reset_manager = new MT_Vote_Reset_Manager();
            $result = $reset_manager->bulk_reset_votes('full_reset', array(
                'confirm' => true,
                'notify_jury' => $send_notifications
            ));
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success($result);
            }
        } else {
            wp_send_json_error('Vote Reset Manager not available');
        }
    }
    
    /**
     * Handle full backup creation
     */
    public function handle_create_full_backup() {
        // Check nonce
        if (!check_ajax_referer('mt_backup_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Admin access required');
        }
        
        // Use the backup manager
        if (class_exists('MT_Vote_Backup_Manager')) {
            $backup_manager = new MT_Vote_Backup_Manager();
            $result = $backup_manager->create_full_backup('manual_backup');
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            } else {
                wp_send_json_success(array(
                    'message' => 'Full backup created successfully',
                    'backup_id' => $result
                ));
            }
        } else {
            wp_send_json_error('Backup Manager not available');
        }
    }
    
    /**
     * Handle votes export
     */
    public function handle_export_votes() {
        // Check nonce
        if (!check_ajax_referer('mt_export_nonce', '_wpnonce', false)) {
            wp_die('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_view_all_evaluations')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get all active votes
        $votes = $wpdb->get_results("
            SELECT 
                v.id,
                v.candidate_id,
                c.post_title as candidate_name,
                v.jury_member_id,
                j.post_title as jury_name,
                v.vote_round,
                v.rating,
                v.comments,
                v.vote_date
            FROM {$wpdb->prefix}mt_votes v
            LEFT JOIN {$wpdb->posts} c ON v.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON v.jury_member_id = j.ID
            WHERE v.is_active = 1
            ORDER BY v.vote_date DESC
        ");
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="votes_export_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Vote ID',
            'Candidate ID',
            'Candidate Name',
            'Jury Member ID',
            'Jury Member Name',
            'Vote Round',
            'Rating',
            'Comments',
            'Vote Date'
        ));
        
        // CSV data
        foreach ($votes as $vote) {
            fputcsv($output, array(
                $vote->id,
                $vote->candidate_id,
                $vote->candidate_name,
                $vote->jury_member_id,
                $vote->jury_name,
                $vote->vote_round,
                $vote->rating,
                $vote->comments,
                $vote->vote_date
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Handle evaluations export
     */
    public function handle_export_evaluations() {
        // Check nonce
        if (!check_ajax_referer('mt_export_nonce', '_wpnonce', false)) {
            wp_die('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('mt_view_all_evaluations')) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get all active evaluations
        $evaluations = $wpdb->get_results("
            SELECT 
                s.id,
                s.candidate_id,
                c.post_title as candidate_name,
                s.jury_member_id,
                j.post_title as jury_name,
                s.courage_score,
                s.innovation_score,
                s.implementation_score,
                s.mobility_relevance_score,
                s.visibility_score,
                s.total_score,
                s.evaluation_round,
                s.evaluation_date
            FROM {$wpdb->prefix}mt_candidate_scores s
            LEFT JOIN {$wpdb->posts} c ON s.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON s.jury_member_id = j.ID
            WHERE s.is_active = 1
            ORDER BY s.evaluation_date DESC
        ");
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="evaluations_export_' . date('Y-m-d_H-i-s') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'Evaluation ID',
            'Candidate ID',
            'Candidate Name',
            'Jury Member ID',
            'Jury Member Name',
            'Courage Score',
            'Innovation Score',
            'Implementation Score',
            'Mobility Relevance Score',
            'Visibility Score',
            'Total Score',
            'Evaluation Round',
            'Evaluation Date'
        ));
        
        // CSV data
        foreach ($evaluations as $evaluation) {
            fputcsv($output, array(
                $evaluation->id,
                $evaluation->candidate_id,
                $evaluation->candidate_name,
                $evaluation->jury_member_id,
                $evaluation->jury_name,
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->mobility_relevance_score,
                $evaluation->visibility_score,
                $evaluation->total_score,
                $evaluation->evaluation_round,
                $evaluation->evaluation_date
            ));
        }
        
        fclose($output);
        exit;
    }
} 