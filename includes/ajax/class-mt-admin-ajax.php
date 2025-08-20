<?php
/**
 * Admin AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Admin_Ajax
 *
 * Handles general admin AJAX requests
 */
class MT_Admin_Ajax extends MT_Base_Ajax {
    
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    public function init() {
        // Export actions - support both AJAX and admin-post.php
        add_action('wp_ajax_mt_export_candidates', [$this, 'export_candidates']);
        add_action('wp_ajax_mt_export_evaluations', [$this, 'export_evaluations']);
        add_action('wp_ajax_mt_export_assignments', [$this, 'export_assignments']);
        
        // Admin-post actions for direct download
        add_action('admin_post_mt_export_candidates', [$this, 'export_candidates_download']);
        add_action('admin_post_mt_export_evaluations', [$this, 'export_evaluations_download']);
        add_action('admin_post_mt_export_assignments', [$this, 'export_assignments_download']);
        
        // Import actions
        // Using MT_Import_Ajax::handle_candidate_import for all import functionality
        add_action('wp_ajax_mt_upload_import_file', [$this, 'upload_import_file']);
        
        // Dashboard actions
        add_action('wp_ajax_mt_get_dashboard_stats', [$this, 'get_dashboard_stats']);
        
        // Data management actions
        add_action('wp_ajax_mt_clear_data', [$this, 'clear_data']);
        add_action('wp_ajax_mt_force_db_upgrade', [$this, 'force_db_upgrade']);
        
        // Bulk candidate operations
        add_action('wp_ajax_mt_bulk_candidate_action', [$this, 'bulk_candidate_action']);
    }
    
    /**
     * Export candidates to CSV
     *
     * @return void
     */
    public function export_candidates() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        // Get candidates with pagination for memory efficiency
        $paged = 1;
        $all_candidates = [];
        
        do {
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => 50,
                'paged' => $paged,
                'post_status' => 'publish'
            ]);
            
            if (!empty($candidates)) {
                $all_candidates = array_merge($all_candidates, $candidates);
            }
            
            $paged++;
        } while (!empty($candidates));
        
        $candidates = $all_candidates;
        
        $csv_data = [];
        $csv_data[] = [
            __('ID', 'mobility-trailblazers'),
            __('Name', 'mobility-trailblazers'),
            __('Organization', 'mobility-trailblazers'),
            __('Position', 'mobility-trailblazers'),
            __('Categories', 'mobility-trailblazers'),
            __('Average Score', 'mobility-trailblazers'),
            __('Evaluation Count', 'mobility-trailblazers')
        ];
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        foreach ($candidates as $candidate) {
            $categories = wp_get_post_terms($candidate->ID, 'mt_award_category', ['fields' => 'names']);
            $avg_score = $evaluation_repo->get_average_score_for_candidate($candidate->ID);
            $evaluations = $evaluation_repo->get_by_candidate($candidate->ID);
            
            $csv_data[] = [
                $candidate->ID,
                $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_organization', true),
                get_post_meta($candidate->ID, '_mt_position', true),
                implode(', ', $categories),
                $avg_score,
                count($evaluations)
            ];
        }
        
        $this->success([
            'csv' => $this->array_to_csv($csv_data),
            'filename' => 'candidates-' . date('Y-m-d') . '.csv'
        ]);
    }
    
    /**
     * Export candidates - Direct download version
     */
    public function export_candidates_download() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_admin_nonce')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_export_data') && !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'));
        }
        
        // Get candidates with pagination for memory efficiency
        $paged = 1;
        $all_candidates = [];
        
        do {
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => 50,
                'paged' => $paged,
                'post_status' => 'publish'
            ]);
            
            if (!empty($candidates)) {
                $all_candidates = array_merge($all_candidates, $candidates);
            }
            
            $paged++;
        } while (!empty($candidates));
        
        $candidates = $all_candidates;
        
        $csv_data = [];
        $csv_data[] = [
            __('ID', 'mobility-trailblazers'),
            __('Name', 'mobility-trailblazers'),
            __('Organization', 'mobility-trailblazers'),
            __('Position', 'mobility-trailblazers'),
            __('Categories', 'mobility-trailblazers'),
            __('Average Score', 'mobility-trailblazers'),
            __('Evaluation Count', 'mobility-trailblazers')
        ];
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        foreach ($candidates as $candidate) {
            $categories = wp_get_post_terms($candidate->ID, 'mt_award_category', ['fields' => 'names']);
            $avg_score = $evaluation_repo->get_average_score_for_candidate($candidate->ID);
            $evaluations = $evaluation_repo->get_by_candidate($candidate->ID);
            
            $csv_data[] = [
                $candidate->ID,
                $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_organization', true),
                get_post_meta($candidate->ID, '_mt_position', true),
                implode(', ', $categories),
                $avg_score,
                count($evaluations)
            ];
        }
        
        // Output CSV directly
        $this->output_csv_download($csv_data, 'candidates-' . date('Y-m-d') . '.csv');
    }
    
    /**
     * Export evaluations to CSV
     *
     * @return void
     */
    public function export_evaluations() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $evaluations = $evaluation_repo->find_all();
        
        $csv_data = [];
        $csv_data[] = [
            __('ID', 'mobility-trailblazers'),
            __('Jury Member', 'mobility-trailblazers'),
            __('Candidate', 'mobility-trailblazers'),
            __('Courage Score', 'mobility-trailblazers'),
            __('Innovation Score', 'mobility-trailblazers'),
            __('Implementation Score', 'mobility-trailblazers'),
            __('Relevance Score', 'mobility-trailblazers'),
            __('Visibility Score', 'mobility-trailblazers'),
            __('Total Score', 'mobility-trailblazers'),
            __('Status', 'mobility-trailblazers'),
            __('Date', 'mobility-trailblazers')
        ];
        
        foreach ($evaluations as $evaluation) {
            $jury_member = get_post($evaluation->jury_member_id);
            $candidate = get_post($evaluation->candidate_id);
            
            $csv_data[] = [
                $evaluation->id,
                $jury_member ? $jury_member->post_title : __('Unknown', 'mobility-trailblazers'),
                $candidate ? $candidate->post_title : __('Unknown', 'mobility-trailblazers'),
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->relevance_score,
                $evaluation->visibility_score,
                $evaluation->total_score,
                $evaluation->status,
                $evaluation->created_at
            ];
        }
        
        $this->success([
            'csv' => $this->array_to_csv($csv_data),
            'filename' => 'evaluations-' . date('Y-m-d') . '.csv'
        ]);
    }
    
    /**
     * Export evaluations - Direct download version
     */
    public function export_evaluations_download() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_admin_nonce')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_export_data') && !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'));
        }
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $evaluations = $evaluation_repo->find_all();
        
        $csv_data = [];
        $csv_data[] = [
            __('ID', 'mobility-trailblazers'),
            __('Jury Member', 'mobility-trailblazers'),
            __('Candidate', 'mobility-trailblazers'),
            __('Courage Score', 'mobility-trailblazers'),
            __('Innovation Score', 'mobility-trailblazers'),
            __('Implementation Score', 'mobility-trailblazers'),
            __('Relevance Score', 'mobility-trailblazers'),
            __('Visibility Score', 'mobility-trailblazers'),
            __('Total Score', 'mobility-trailblazers'),
            __('Status', 'mobility-trailblazers'),
            __('Date', 'mobility-trailblazers')
        ];
        
        foreach ($evaluations as $evaluation) {
            $jury_member = get_post($evaluation->jury_member_id);
            $candidate = get_post($evaluation->candidate_id);
            
            $csv_data[] = [
                $evaluation->id,
                $jury_member ? $jury_member->post_title : __('Unknown', 'mobility-trailblazers'),
                $candidate ? $candidate->post_title : __('Unknown', 'mobility-trailblazers'),
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->relevance_score,
                $evaluation->visibility_score,
                $evaluation->total_score,
                $evaluation->status,
                $evaluation->created_at
            ];
        }
        
        // Output CSV directly
        $this->output_csv_download($csv_data, 'evaluations-' . date('Y-m-d') . '.csv');
    }
    
    /**
     * Export assignments to CSV
     *
     * @return void
     */
    public function export_assignments() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        $assignments = $assignment_repo->find_all();
        
        $csv_data = [];
        $csv_data[] = [
            __('Jury Member', 'mobility-trailblazers'),
            __('Candidate', 'mobility-trailblazers'),
            __('Assigned Date', 'mobility-trailblazers'),
            __('Evaluation Status', 'mobility-trailblazers')
        ];
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        foreach ($assignments as $assignment) {
            $jury_member = get_post($assignment->jury_member_id);
            $candidate = get_post($assignment->candidate_id);
            
            // Check evaluation status
            $evaluations = $evaluation_repo->find_all([
                'jury_member_id' => $assignment->jury_member_id,
                'candidate_id' => $assignment->candidate_id,
                'limit' => 1
            ]);
            
            $status = __('Not Started', 'mobility-trailblazers');
            if (!empty($evaluations)) {
                $status = $evaluations[0]->status === 'completed' ? __('Completed', 'mobility-trailblazers') : __('Draft', 'mobility-trailblazers');
            }
            
            $csv_data[] = [
                $jury_member ? $jury_member->post_title : __('Unknown', 'mobility-trailblazers'),
                $candidate ? $candidate->post_title : __('Unknown', 'mobility-trailblazers'),
                $assignment->assigned_at,
                $status
            ];
        }
        
        $this->success([
            'csv' => $this->array_to_csv($csv_data),
            'filename' => 'assignments-' . date('Y-m-d') . '.csv'
        ]);
    }
    
    /**
     * Export assignments - Direct download version
     */
    public function export_assignments_download() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_admin_nonce')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('mt_export_data') && !current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'));
        }
        
        // Set headers for CSV download
        $filename = 'assignments-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add UTF-8 BOM for proper encoding in Excel
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write metadata comment
        fputcsv($output, ['# Mobility Trailblazers v' . MT_VERSION . ' - Export Date: ' . date('Y-m-d H:i:s')]);
        
        // Write headers
        fputcsv($output, [
            __('Jury Member', 'mobility-trailblazers'),
            __('Candidate', 'mobility-trailblazers'),
            __('Assigned Date', 'mobility-trailblazers'),
            __('Evaluation Status', 'mobility-trailblazers')
        ]);
        
        // Stream assignments in chunks to avoid memory issues
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        $offset = 0;
        $limit = 100; // Process 100 assignments at a time
        
        do {
            $assignments = $assignment_repo->find_all([
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            foreach ($assignments as $assignment) {
                $jury_member = get_post($assignment->jury_member_id);
                $candidate = get_post($assignment->candidate_id);
                
                // Check evaluation status
                $evaluations = $evaluation_repo->find_all([
                    'jury_member_id' => $assignment->jury_member_id,
                    'candidate_id' => $assignment->candidate_id,
                    'limit' => 1
                ]);
                
                $status = __('Not Started', 'mobility-trailblazers');
                if (!empty($evaluations)) {
                    $status = $evaluations[0]->status === 'completed' ? __('Completed', 'mobility-trailblazers') : __('Draft', 'mobility-trailblazers');
                }
                
                fputcsv($output, [
                    $jury_member ? $jury_member->post_title : __('Unknown', 'mobility-trailblazers'),
                    $candidate ? $candidate->post_title : __('Unknown', 'mobility-trailblazers'),
                    $assignment->assigned_at,
                    $status
                ]);
            }
            
            $offset += $limit;
        } while (count($assignments) === $limit);
        
        fclose($output);
        exit;
    }
    
    /**
     * Output CSV file for direct download
     * 
     * @param array $data CSV data
     * @param string $filename Filename for download
     */
    private function output_csv_download($data, $filename) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add UTF-8 BOM for proper encoding in Excel
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Output each row
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Import candidates from CSV
     *
     * @return void
     */
    // import_candidates method removed - functionality moved to MT_Import_Ajax
    
    /**
     * Upload import file
     *
     * @return void
     */
    public function upload_import_file() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_import_data');
        
        if (empty($_FILES['import_file'])) {
            $this->error(__('No file uploaded.', 'mobility-trailblazers'));
        }
        
        $file = $_FILES['import_file'];
        
        // Validate file type
        $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
        if (!in_array($file['type'], $allowed_types)) {
            $this->error(__('Invalid file type. Please upload a CSV file.', 'mobility-trailblazers'));
        }
        
        // Handle upload
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            $this->error($upload['error']);
        }
        
        // Create attachment
        $attachment = [
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'private'
        ];
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attach_id)) {
            $this->error(__('Failed to process file.', 'mobility-trailblazers'));
        }
        
        $this->success([
            'file_id' => $attach_id,
            'filename' => basename($upload['file'])
        ]);
    }
    
    /**
     * Get dashboard statistics
     *
     * @return void
     */
    public function get_dashboard_stats() {
        $this->verify_nonce('mt_ajax_nonce');
        $this->check_permission('mt_view_all_evaluations');
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        
        $eval_stats = $evaluation_repo->get_statistics();
        $assign_stats = $assignment_repo->get_statistics();
        
        // Get top candidates
        $top_candidates = $evaluation_repo->get_top_candidates(5);
        
        $this->success([
            'evaluations' => $eval_stats,
            'assignments' => $assign_stats,
            'top_candidates' => $top_candidates
        ]);
    }
    
    /**
     * Clear data based on type
     *
     * @return void
     */
    public function clear_data() {
        $this->verify_nonce('mt_clear_data');
        $this->check_permission('mt_manage_settings');
        
        $type = $this->get_param('type');
        
        if (!in_array($type, ['evaluations', 'assignments'])) {
            $this->error(__('Invalid data type.', 'mobility-trailblazers'));
        }
        
        global $wpdb;
        
        switch ($type) {
            case 'evaluations':
                if (\MobilityTrailblazers\Core\MT_Database_Upgrade::clear_evaluations()) {
                    $this->success([], __('All evaluations have been cleared.', 'mobility-trailblazers'));
                } else {
                    $this->error(__('Failed to clear evaluations.', 'mobility-trailblazers'));
                }
                break;
                
            case 'assignments':
                if (\MobilityTrailblazers\Core\MT_Database_Upgrade::clear_assignments()) {
                    $this->success([], __('All assignments have been cleared.', 'mobility-trailblazers'));
                } else {
                    $this->error(__('Failed to clear assignments.', 'mobility-trailblazers'));
                }
                break;
        }
    }
    
    /**
     * Force database upgrade
     *
     * @return void
     */
    public function force_db_upgrade() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_manage_settings');
        
        try {
            \MobilityTrailblazers\Core\MT_Database_Upgrade::force_upgrade();
            $this->success([], __('Database upgrade completed successfully!', 'mobility-trailblazers'));
        } catch (Exception $e) {
            $this->error(__('Database upgrade failed: ', 'mobility-trailblazers') . $e->getMessage());
        }
    }
    
    /**
     * Handle bulk candidate actions
     *
     * @return void
     */
    public function bulk_candidate_action() {
        // Verify nonce
        if (!$this->verify_nonce('mt_admin_nonce')) {
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            $this->error(__('Permission denied', 'mobility-trailblazers'));
            return;
        }
        
        // Get parameters
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $candidate_ids = isset($_POST['candidate_ids']) && is_array($_POST['candidate_ids']) 
            ? array_map('intval', $_POST['candidate_ids']) 
            : array();
        
        if (empty($action) || empty($candidate_ids)) {
            $this->error(__('Invalid parameters', 'mobility-trailblazers'));
            return;
        }
        
        // Log for debugging
        MT_Logger::info('Bulk candidate operation', [
            'action' => $action,
            'candidate_count' => count($candidate_ids)
        ]);
        
        $success_count = 0;
        $errors = [];
        
        foreach ($candidate_ids as $candidate_id) {
            // Verify it's a candidate
            $candidate = get_post($candidate_id);
            if (!$candidate || $candidate->post_type !== 'mt_candidate') {
                $errors[] = sprintf(__('Invalid candidate ID: %d', 'mobility-trailblazers'), $candidate_id);
                continue;
            }
            
            $result = false;
            
            switch ($action) {
                case 'publish':
                    $result = wp_update_post([
                        'ID' => $candidate_id,
                        'post_status' => 'publish'
                    ]);
                    break;
                    
                case 'draft':
                    $result = wp_update_post([
                        'ID' => $candidate_id,
                        'post_status' => 'draft'
                    ]);
                    break;
                    
                case 'trash':
                    $result = wp_trash_post($candidate_id);
                    break;
                    
                case 'delete':
                    // Check if user can delete
                    if (!current_user_can('delete_posts')) {
                        $errors[] = __('You do not have permission to delete candidates', 'mobility-trailblazers');
                        continue 2;
                    }
                    $result = wp_delete_post($candidate_id, true);
                    break;
                    
                case 'add_category':
                    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
                    if ($category) {
                        $result = wp_set_object_terms($candidate_id, $category, 'mt_award_category', true);
                        $result = !is_wp_error($result);
                    }
                    break;
                    
                case 'remove_category':
                    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
                    if ($category) {
                        $current_terms = wp_get_object_terms($candidate_id, 'mt_award_category', ['fields' => 'slugs']);
                        if (!is_wp_error($current_terms)) {
                            $new_terms = array_diff($current_terms, [$category]);
                            $result = wp_set_object_terms($candidate_id, $new_terms, 'mt_award_category');
                            $result = !is_wp_error($result);
                        }
                    }
                    break;
                    
                case 'export':
                    // Handle export separately
                    $this->bulk_export_candidates($candidate_ids);
                    return;
                    
                default:
                    $errors[] = sprintf(__('Unknown action: %s', 'mobility-trailblazers'), $action);
                    continue 2;
            }
            
            if ($result) {
                $success_count++;
            } else {
                $errors[] = sprintf(__('Failed to %s candidate ID: %d', 'mobility-trailblazers'), $action, $candidate_id);
            }
        }
        
        if ($success_count > 0) {
            $message = sprintf(
                __('%d candidates %s successfully.', 'mobility-trailblazers'),
                $success_count,
                $this->get_candidate_action_past_tense($action)
            );
            
            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('%d failed.', 'mobility-trailblazers'), count($errors));
            }
            
            $this->success([
                'success_count' => $success_count,
                'errors' => $errors
            ], $message);
        } else {
            $this->error(__('No candidates could be processed.', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Get past tense of candidate action for messages
     *
     * @param string $action Action name
     * @return string Past tense
     */
    private function get_candidate_action_past_tense($action) {
        $past_tense = [
            'publish' => 'published',
            'draft' => 'set to draft',
            'trash' => 'moved to trash',
            'delete' => 'deleted',
            'add_category' => 'updated',
            'remove_category' => 'updated'
        ];
        
        return isset($past_tense[$action]) ? $past_tense[$action] : $action;
    }
    
    /**
     * Bulk export selected candidates
     *
     * @param array $candidate_ids Array of candidate IDs
     * @return void
     */
    private function bulk_export_candidates($candidate_ids) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=selected-candidates-' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add headers
        fputcsv($output, array(
            __('ID', 'mobility-trailblazers'),
            __('Name', 'mobility-trailblazers'),
            __('Organization', 'mobility-trailblazers'),
            __('Position', 'mobility-trailblazers'),
            __('Categories', 'mobility-trailblazers'),
            __('Status', 'mobility-trailblazers'),
            __('Average Score', 'mobility-trailblazers'),
            __('Evaluation Count', 'mobility-trailblazers'),
            __('Bio', 'mobility-trailblazers')
        ));
        
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        // Add data for selected candidates
        foreach ($candidate_ids as $candidate_id) {
            $candidate = get_post($candidate_id);
            
            if (!$candidate || $candidate->post_type !== 'mt_candidate') {
                continue;
            }
            
            $categories = wp_get_post_terms($candidate->ID, 'mt_award_category', ['fields' => 'names']);
            $avg_score = $evaluation_repo->get_average_score_for_candidate($candidate->ID);
            $evaluations = $evaluation_repo->get_by_candidate($candidate->ID);
            
            fputcsv($output, array(
                $candidate->ID,
                $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_organization', true),
                get_post_meta($candidate->ID, '_mt_position', true),
                implode(', ', $categories),
                $candidate->post_status,
                $avg_score,
                count($evaluations),
                wp_strip_all_tags($candidate->post_content)
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Convert array to CSV string
     *
     * @param array $data Data array
     * @return string
     */
    private function array_to_csv($data) {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * Parse CSV file
     *
     * @param string $file_path Path to CSV file
     * @return array
     */
    private function parse_csv($file_path) {
        $data = [];
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }
        
        return $data;
    }
} 
