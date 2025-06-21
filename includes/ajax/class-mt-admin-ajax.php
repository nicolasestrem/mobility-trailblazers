<?php
/**
 * Admin AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Ajax;

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
        // Export actions
        add_action('wp_ajax_mt_export_candidates', [$this, 'export_candidates']);
        add_action('wp_ajax_mt_export_evaluations', [$this, 'export_evaluations']);
        add_action('wp_ajax_mt_export_assignments', [$this, 'export_assignments']);
        
        // Import actions
        add_action('wp_ajax_mt_import_candidates', [$this, 'import_candidates']);
        add_action('wp_ajax_mt_upload_import_file', [$this, 'upload_import_file']);
        
        // Dashboard actions
        add_action('wp_ajax_mt_get_dashboard_stats', [$this, 'get_dashboard_stats']);
        
        // Data management actions
        add_action('wp_ajax_mt_clear_data', [$this, 'clear_data']);
        add_action('wp_ajax_mt_force_db_upgrade', [$this, 'force_db_upgrade']);
    }
    
    /**
     * Export candidates to CSV
     *
     * @return void
     */
    public function export_candidates() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_export_data');
        
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        $csv_data = [];
        $csv_data[] = [
            'ID',
            'Name',
            'Organization',
            'Position',
            'Categories',
            'Average Score',
            'Evaluation Count'
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
            'ID',
            'Jury Member',
            'Candidate',
            'Courage Score',
            'Innovation Score',
            'Implementation Score',
            'Relevance Score',
            'Visibility Score',
            'Total Score',
            'Status',
            'Date'
        ];
        
        foreach ($evaluations as $evaluation) {
            $jury_member = get_post($evaluation->jury_member_id);
            $candidate = get_post($evaluation->candidate_id);
            
            $csv_data[] = [
                $evaluation->id,
                $jury_member ? $jury_member->post_title : 'Unknown',
                $candidate ? $candidate->post_title : 'Unknown',
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
            'Jury Member',
            'Candidate',
            'Assigned Date',
            'Evaluation Status'
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
            
            $status = 'Not Started';
            if (!empty($evaluations)) {
                $status = $evaluations[0]->status === 'completed' ? 'Completed' : 'Draft';
            }
            
            $csv_data[] = [
                $jury_member ? $jury_member->post_title : 'Unknown',
                $candidate ? $candidate->post_title : 'Unknown',
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
     * Import candidates from CSV
     *
     * @return void
     */
    public function import_candidates() {
        $this->verify_nonce('mt_admin_nonce');
        $this->check_permission('mt_import_data');
        
        $file_id = $this->get_int_param('file_id');
        if (!$file_id) {
            $this->error(__('No file provided.', 'mobility-trailblazers'));
        }
        
        $file_path = get_attached_file($file_id);
        if (!$file_path || !file_exists($file_path)) {
            $this->error(__('File not found.', 'mobility-trailblazers'));
        }
        
        $csv_data = $this->parse_csv($file_path);
        if (empty($csv_data)) {
            $this->error(__('Invalid CSV file.', 'mobility-trailblazers'));
        }
        
        $imported = 0;
        $errors = [];
        
        foreach ($csv_data as $row_num => $row) {
            // Skip header row
            if ($row_num === 0) {
                continue;
            }
            
            // Validate required fields
            if (empty($row[0])) { // Name
                $errors[] = sprintf(__('Row %d: Name is required.', 'mobility-trailblazers'), $row_num + 1);
                continue;
            }
            
            // Create candidate
            $candidate_data = [
                'post_title' => sanitize_text_field($row[0]),
                'post_type' => 'mt_candidate',
                'post_status' => 'publish',
                'post_content' => isset($row[4]) ? wp_kses_post($row[4]) : '', // Bio
            ];
            
            $candidate_id = wp_insert_post($candidate_data);
            
            if (is_wp_error($candidate_id)) {
                $errors[] = sprintf(__('Row %d: %s', 'mobility-trailblazers'), $row_num + 1, $candidate_id->get_error_message());
                continue;
            }
            
            // Add meta data
            if (!empty($row[1])) { // Organization
                update_post_meta($candidate_id, '_mt_organization', sanitize_text_field($row[1]));
            }
            if (!empty($row[2])) { // Position
                update_post_meta($candidate_id, '_mt_position', sanitize_text_field($row[2]));
            }
            
            // Add categories
            if (!empty($row[3])) { // Categories
                $categories = array_map('trim', explode(',', $row[3]));
                wp_set_object_terms($candidate_id, $categories, 'mt_award_category');
            }
            
            $imported++;
        }
        
        // Clean up
        wp_delete_attachment($file_id, true);
        
        if ($imported > 0) {
            $this->success([
                'imported' => $imported,
                'errors' => $errors
            ], sprintf(__('%d candidates imported successfully.', 'mobility-trailblazers'), $imported));
        } else {
            $this->error(__('No candidates were imported.', 'mobility-trailblazers'), ['errors' => $errors]);
        }
    }
    
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
        $this->verify_nonce('mt_admin_nonce');
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
        
        $type = $this->get_string_param('type');
        
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