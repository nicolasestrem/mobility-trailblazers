<?php
/**
 * Import/Export Handler
 *
 * @package MobilityTrailblazers
 * @since 2.2.23
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Import_Export
 *
 * Handles CSV import/export for candidates and jury members
 */
class MT_Import_Export {
    
    /**
     * Initialize the import/export handler
     */
    public static function init() {
        // Admin post handlers for form submissions
        add_action('admin_post_mt_import_data', [__CLASS__, 'handle_import']);
        add_action('admin_post_mt_export_candidates', [__CLASS__, 'export_candidates']);
        add_action('admin_post_mt_export_evaluations', [__CLASS__, 'export_evaluations']);
        add_action('admin_post_mt_export_assignments', [__CLASS__, 'export_assignments']);
        add_action('admin_post_mt_download_template', [__CLASS__, 'download_template']);
        
        // AJAX handlers removed - handled by MT_CSV_Import_Ajax class
    }
    
    /**
     * Handle import from admin form
     */
    public static function handle_import() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'mt_import_data')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permission
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to import data', 'mobility-trailblazers'));
        }
        
        // Check for file upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = 'import_error';
            
            // Add specific error details for debugging
            if (isset($_FILES['csv_file']['error'])) {
                MT_Logger::error('File upload error', [
                    'error_code' => $_FILES['csv_file']['error'],
                    'error_constant' => self::get_upload_error_constant($_FILES['csv_file']['error'])
                ]);
            }
            
            wp_redirect(add_query_arg([
                'page' => 'mt-import-export',
                'message' => $error_message
            ], admin_url('admin.php')));
            exit;
        }
        
        $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
        $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] === '1';
        
        $result = self::process_csv_import(
            $_FILES['csv_file']['tmp_name'],
            $import_type,
            $update_existing
        );
        
        if ($result['success'] > 0 || $result['updated'] > 0) {
            $total = $result['success'] + $result['updated'];
            wp_redirect(add_query_arg([
                'page' => 'mt-import-export',
                'message' => 'import_success',
                'count' => $total
            ], admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg([
                'page' => 'mt-import-export',
                'message' => 'import_error'
            ], admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * AJAX handler for CSV import
     * @deprecated 2.5.38 Use MT_CSV_Import_Ajax::handle_csv_import() instead
     */
    /*
    public static function ajax_import_csv() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
            return;
        }
        
        // Check permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'mobility-trailblazers')]);
            return;
        }
        
        // Check file upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('File upload failed', 'mobility-trailblazers')]);
            return;
        }
        
        $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
        $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] === '1';
        
        $result = self::process_csv_import(
            $_FILES['csv_file']['tmp_name'],
            $import_type,
            $update_existing
        );
        
        if ($result['success'] > 0 || $result['updated'] > 0) {
            wp_send_json_success([
                'message' => sprintf(
                    __('Import completed: %d created, %d updated, %d skipped', 'mobility-trailblazers'),
                    $result['success'],
                    $result['updated'],
                    $result['skipped']
                ),
                'data' => $result
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Import failed. Please check your CSV format.', 'mobility-trailblazers'),
                'data' => $result
            ]);
        }
    }
    */
    
    /**
     * Process CSV import
     *
     * @param string $file_path Path to uploaded CSV file
     * @param string $import_type Type of import (candidates or jury_members)
     * @param bool $update_existing Whether to update existing records
     * @return array Import results
     */
    private static function process_csv_import($file_path, $import_type, $update_existing = false) {
        // Use the new import handler for better processing
        if (class_exists('\MobilityTrailblazers\Admin\MT_Import_Handler')) {
            $handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();
            return $handler->process_csv_import($file_path, $import_type, $update_existing);
        }
        
        // Fallback to existing implementation if handler not available
        $result = [
            'success' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'error_details' => []
        ];
        
        // Open CSV file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return $result;
        }
        
        // Get headers
        $headers = fgetcsv($handle, 0, ',');
        if (!$headers) {
            fclose($handle);
            return $result;
        }
        
        // Clean headers - remove BOM and trim
        $headers = array_map(function($header) {
            return trim(str_replace("\xEF\xBB\xBF", '', $header));
        }, $headers);
        
        // Process rows
        $row_number = 1;
        while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
            $row_number++;
            
            // Create associative array
            $row = array_combine($headers, $data);
            if (!$row) {
                $result['errors']++;
                $result['error_details'][] = [
                    'row' => $row_number,
                    'error' => __('Invalid row format', 'mobility-trailblazers')
                ];
                continue;
            }
            
            // Process based on type
            if ($import_type === 'candidates') {
                $import_result = self::import_candidate($row, $update_existing);
            } elseif ($import_type === 'jury_members') {
                $import_result = self::import_jury_member($row, $update_existing);
            } else {
                $result['errors']++;
                $result['error_details'][] = [
                    'row' => $row_number,
                    'error' => __('Invalid import type', 'mobility-trailblazers')
                ];
                continue;
            }
            
            // Update counters
            if ($import_result['status'] === 'created') {
                $result['success']++;
            } elseif ($import_result['status'] === 'updated') {
                $result['updated']++;
            } elseif ($import_result['status'] === 'skipped') {
                $result['skipped']++;
            } else {
                $result['errors']++;
                $result['error_details'][] = [
                    'row' => $row_number,
                    'error' => $import_result['error'] ?? __('Unknown error', 'mobility-trailblazers')
                ];
            }
        }
        
        fclose($handle);
        
        MT_Logger::info('CSV import completed', [
            'type' => $import_type,
            'results' => $result
        ]);
        
        return $result;
    }
    
    /**
     * Import a single candidate
     *
     * @param array $data Row data
     * @param bool $update_existing Whether to update existing records
     * @return array Import result
     */
    private static function import_candidate($data, $update_existing = false) {
        // Map the German/specific headers to internal field names
        $field_mapping = [
            'ID' => 'id',
            'Name' => 'name',
            'Organisation' => 'organisation',
            'Position' => 'position',
            'LinkedIn-Link' => 'linkedin',
            'Webseite' => 'website',
            'Article about coming of age' => 'article',
            'Description' => 'description',
            'Category' => 'category',
            'Status' => 'status'
        ];
        
        // Remap the data array with standard keys
        $mapped_data = [];
        foreach ($field_mapping as $csv_key => $internal_key) {
            $mapped_data[$internal_key] = isset($data[$csv_key]) ? $data[$csv_key] : '';
        }
        
        // Required fields
        $required_fields = ['name', 'organisation', 'category'];
        foreach ($required_fields as $field) {
            if (empty($mapped_data[$field])) {
                return [
                    'status' => 'error',
                    'error' => sprintf(__('Missing required field: %s', 'mobility-trailblazers'), $field)
                ];
            }
        }
        
        // Sanitize data
        $import_id = isset($mapped_data['id']) ? sanitize_text_field($mapped_data['id']) : '';
        $name = sanitize_text_field($mapped_data['name']);
        $organisation = sanitize_text_field($mapped_data['organisation']);
        $position = sanitize_text_field($mapped_data['position']);
        $category = sanitize_text_field($mapped_data['category']);
        $status = sanitize_text_field($mapped_data['status']);
        $description = wp_kses_post($mapped_data['description']);
        $website = esc_url_raw($mapped_data['website']);
        $linkedin = esc_url_raw($mapped_data['linkedin']);
        $article = esc_url_raw($mapped_data['article']);
        
        // Check if candidate exists - using import ID or name
        $meta_query = [];
        if (!empty($import_id)) {
            $meta_query[] = [
                'key' => '_mt_candidate_id',
                'value' => $import_id,
                'compare' => '='
            ];
        }
        $meta_query[] = [
            'key' => '_mt_candidate_name',
            'value' => $name,
            'compare' => '='
        ];
        
        $existing_query = new \WP_Query([
            'post_type' => 'mt_candidate',
            'meta_query' => [
                'relation' => 'OR',
                $meta_query
            ],
            'posts_per_page' => 1
        ]);
        
        if ($existing_query->have_posts() && !$update_existing) {
            return ['status' => 'skipped'];
        }
        
        // Prepare post data
        $post_data = [
            'post_title' => $name,
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'post_content' => $description
        ];
        
        // Update or create
        if ($existing_query->have_posts() && $update_existing) {
            $post_data['ID'] = $existing_query->posts[0]->ID;
            $post_id = wp_update_post($post_data);
            $import_status = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            $import_status = 'created';
        }
        
        if (is_wp_error($post_id)) {
            return [
                'status' => 'error',
                'error' => $post_id->get_error_message()
            ];
        }
        
        // Update meta fields with new mapping
        update_post_meta($post_id, '_mt_candidate_id', $import_id);
        update_post_meta($post_id, '_mt_candidate_name', $name);
        update_post_meta($post_id, '_mt_organization', $organisation);
        update_post_meta($post_id, '_mt_position', $position);
        update_post_meta($post_id, '_mt_category_type', $category);
        update_post_meta($post_id, '_mt_top_50_status', $status);
        update_post_meta($post_id, '_mt_description_full', $description);
        update_post_meta($post_id, '_mt_website_url', $website);
        update_post_meta($post_id, '_mt_linkedin_url', $linkedin);
        update_post_meta($post_id, '_mt_article_url', $article);
        
        // Parse and save evaluation criteria if present in description
        if (!empty($description)) {
            $criteria = \MobilityTrailblazers\Admin\MT_Import_Handler::parse_evaluation_criteria($description);
            foreach ($criteria as $key => $value) {
                if (!empty($value)) {
                    update_post_meta($post_id, $key, $value);
                }
            }
        }
        
        return ['status' => $import_status];
    }
    
    /**
     * Import a single jury member
     *
     * @param array $data Row data
     * @param bool $update_existing Whether to update existing records
     * @return array Import result
     */
    private static function import_jury_member($data, $update_existing = false) {
        // Required fields
        $required_fields = ['name', 'email', 'organization'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return [
                    'status' => 'error',
                    'error' => sprintf(__('Missing required field: %s', 'mobility-trailblazers'), $field)
                ];
            }
        }
        
        // Sanitize data
        $name = sanitize_text_field($data['name']);
        $email = sanitize_email($data['email']);
        $organization = sanitize_text_field($data['organization']);
        $title = isset($data['title']) ? sanitize_text_field($data['title']) : '';
        $role = isset($data['role']) ? sanitize_text_field($data['role']) : 'mt_jury_member';
        
        // Check if user exists
        $user = get_user_by('email', $email);
        
        if ($user && !$update_existing) {
            return ['status' => 'skipped'];
        }
        
        if ($user && $update_existing) {
            // Update existing user
            $user_id = $user->ID;
            
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => explode(' ', $name)[0],
                'last_name' => trim(str_replace(explode(' ', $name)[0], '', $name))
            ]);
            
            $status = 'updated';
        } else {
            // Create new user
            $username = sanitize_user(strtolower(str_replace(' ', '_', $name)));
            $password = wp_generate_password(12, true, true);
            
            // Ensure unique username
            $counter = 1;
            $original_username = $username;
            while (username_exists($username)) {
                $username = $original_username . '_' . $counter;
                $counter++;
            }
            
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                return [
                    'status' => 'error',
                    'error' => $user_id->get_error_message()
                ];
            }
            
            // Update user details
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => explode(' ', $name)[0],
                'last_name' => trim(str_replace(explode(' ', $name)[0], '', $name))
            ]);
            
            // Send welcome email
            wp_new_user_notification($user_id, null, 'both');
            
            $status = 'created';
        }
        
        // Set role
        $user_obj = new \WP_User($user_id);
        $user_obj->set_role('mt_jury_member');
        
        // Update user meta
        update_user_meta($user_id, 'mt_organization', $organization);
        update_user_meta($user_id, 'mt_title', $title);
        update_user_meta($user_id, 'mt_jury_member', 'yes');
        
        // Create jury member post
        $post_query = new \WP_Query([
            'post_type' => 'mt_jury_member',
            'meta_key' => '_mt_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ]);
        
        if (!$post_query->have_posts()) {
            $post_id = wp_insert_post([
                'post_title' => $name,
                'post_type' => 'mt_jury_member',
                'post_status' => 'publish',
                'post_content' => ''
            ]);
            
            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_mt_user_id', $user_id);
                update_post_meta($post_id, '_mt_jury_name', $name);
                update_post_meta($post_id, '_mt_jury_email', $email);
                update_post_meta($post_id, '_mt_jury_organization', $organization);
                update_post_meta($post_id, '_mt_jury_title', $title);
            }
        }
        
        return ['status' => $status];
    }
    
    /**
     * Export candidates to CSV
     */
    public static function export_candidates() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_export_candidates')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permission
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'));
        }
        
        // Get candidates
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=candidates-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, [
            'name',
            'company',
            'category',
            'description',
            'innovation',
            'website',
            'linkedin',
            'email'
        ]);
        
        // Optimize meta data fetching - get all meta at once
        $candidate_ids = wp_list_pluck($candidates, 'ID');
        if (!empty($candidate_ids)) {
            // Create placeholders for prepared statement
            $placeholders = implode(',', array_fill(0, count($candidate_ids), '%d'));
            
            $meta_query = $wpdb->prepare(
                "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} 
                 WHERE post_id IN ($placeholders) 
                 AND meta_key IN ('_mt_candidate_name', '_mt_organization', '_mt_category_type', 
                                  '_mt_description_full', '_mt_innovation', '_mt_website_url', 
                                  '_mt_linkedin_url', '_mt_email')",
                ...$candidate_ids
            );
            $all_meta = $wpdb->get_results($meta_query);
            
            // Organize meta by post ID
            $meta_by_post = [];
            foreach ($all_meta as $meta) {
                if (!isset($meta_by_post[$meta->post_id])) {
                    $meta_by_post[$meta->post_id] = [];
                }
                $meta_by_post[$meta->post_id][$meta->meta_key] = $meta->meta_value;
            }
            
            // Write data using cached meta
            foreach ($candidates as $candidate) {
                $meta = isset($meta_by_post[$candidate->ID]) ? $meta_by_post[$candidate->ID] : [];
                fputcsv($output, [
                    isset($meta['_mt_candidate_name']) ? $meta['_mt_candidate_name'] : $candidate->post_title,
                    isset($meta['_mt_organization']) ? $meta['_mt_organization'] : '',
                    isset($meta['_mt_category_type']) ? $meta['_mt_category_type'] : '',
                    isset($meta['_mt_description_full']) ? $meta['_mt_description_full'] : '',
                    isset($meta['_mt_innovation']) ? $meta['_mt_innovation'] : '',
                    isset($meta['_mt_website_url']) ? $meta['_mt_website_url'] : '',
                    isset($meta['_mt_linkedin_url']) ? $meta['_mt_linkedin_url'] : '',
                    isset($meta['_mt_email']) ? $meta['_mt_email'] : ''
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export evaluations to CSV
     */
    public static function export_evaluations() {
        global $wpdb;
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_export_evaluations')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permission
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'));
        }
        
        // Get evaluations
        $table_name = $wpdb->prefix . 'mt_evaluations';
        $evaluations = $wpdb->get_results("
            SELECT e.*, c.post_title as candidate_name, j.post_title as jury_member
            FROM {$table_name} e
            LEFT JOIN {$wpdb->posts} c ON e.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON e.jury_member_id = j.ID AND j.post_type = 'mt_jury_member'
            ORDER BY e.created_at DESC
        ");
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=evaluations-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, [
            'Candidate',
            'Jury Member',
            'Criterion 1',
            'Criterion 2',
            'Criterion 3',
            'Criterion 4',
            'Criterion 5',
            'Comments',
            'Status',
            'Date'
        ]);
        
        // Write data
        foreach ($evaluations as $evaluation) {
            fputcsv($output, [
                $evaluation->candidate_name,
                $evaluation->jury_member,
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->relevance_score,
                $evaluation->visibility_score,
                $evaluation->comments,
                $evaluation->status,
                $evaluation->created_at
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export assignments to CSV
     */
    public static function export_assignments() {
        global $wpdb;
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_export_assignments')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check permission
        if (!current_user_can('edit_posts')) {
            wp_die(__('Permission denied', 'mobility-trailblazers'));
        }
        
        // Get assignments
        $table_name = $wpdb->prefix . 'mt_jury_assignments';
        $assignments = $wpdb->get_results("
            SELECT a.*, c.post_title as candidate_name, j.post_title as jury_member
            FROM {$table_name} a
            LEFT JOIN {$wpdb->posts} c ON a.candidate_id = c.ID
            LEFT JOIN {$wpdb->posts} j ON a.jury_member_id = j.ID
            ORDER BY a.assigned_at DESC
        ");
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=assignments-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, [
            'Jury Member',
            'Candidate',
            'Status',
            'Date Assigned'
        ]);
        
        // Write data
        foreach ($assignments as $assignment) {
            fputcsv($output, [
                $assignment->jury_member,
                $assignment->candidate_name,
                $assignment->status,
                $assignment->created_at
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Download template CSV
     */
    public static function download_template() {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'mt_download_template')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        
        // Validate type
        if (!in_array($type, ['candidates', 'jury_members'])) {
            wp_die(__('Invalid template type', 'mobility-trailblazers'));
        }
        
        // Check if template file exists
        $template_file = MT_PLUGIN_DIR . 'data/templates/' . $type . '.csv';
        
        // Also check for hyphenated version as fallback
        if (!file_exists($template_file)) {
            $alt_file = MT_PLUGIN_DIR . 'data/templates/' . str_replace('_', '-', $type) . '.csv';
            if (file_exists($alt_file)) {
                $template_file = $alt_file;
            } else {
                // If template doesn't exist, generate it dynamically
                self::generate_template($type);
                return;
            }
        }
        
        // Clean any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $type . '-template.csv');
        header('Content-Length: ' . filesize($template_file));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file
        readfile($template_file);
        exit;
    }
    
    /**
     * Export candidates with streaming for memory optimization
     *
     * @param array $args Export arguments
     * @return void Outputs CSV directly
     * @since 2.2.28
     */
    public static function export_candidates_stream($args = []) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="candidates-' . date('Y-m-d-His') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        $headers = [
            'ID',
            'Name',
            'Organisation',
            'Position',
            'Category',
            'Status',
            'LinkedIn',
            'Website',
            'Description',
            'Created Date',
            'Modified Date'
        ];
        fputcsv($output, $headers);
        
        // Query in batches to avoid memory issues
        $offset = 0;
        $batch_size = 100;
        
        while (true) {
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
                'post_status' => 'any',
                'orderby' => 'ID',
                'order' => 'ASC'
            ]);
            
            if (empty($candidates)) {
                break;
            }
            
            foreach ($candidates as $candidate) {
                $row = [
                    $candidate->ID,
                    $candidate->post_title,
                    get_post_meta($candidate->ID, '_mt_organization', true),
                    get_post_meta($candidate->ID, '_mt_position', true),
                    get_post_meta($candidate->ID, '_mt_category_type', true),
                    $candidate->post_status,
                    get_post_meta($candidate->ID, '_mt_linkedin_url', true),
                    get_post_meta($candidate->ID, '_mt_website_url', true),
                    wp_strip_all_tags($candidate->post_content),
                    $candidate->post_date,
                    $candidate->post_modified
                ];
                fputcsv($output, $row);
                
                // Free memory
                unset($row);
            }
            
            $offset += $batch_size;
            
            // Clear WordPress object cache
            wp_cache_flush();
            
            // Prevent timeout on large exports
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export evaluations with streaming for memory optimization
     *
     * @param array $args Export arguments
     * @return void Outputs CSV directly
     * @since 2.2.28
     */
    public static function export_evaluations_stream($args = []) {
        global $wpdb;
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="evaluations-' . date('Y-m-d-His') . '.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        $headers = [
            'Evaluation ID',
            'Jury Member',
            'Candidate',
            'Criterion 1',
            'Criterion 2',
            'Criterion 3',
            'Criterion 4',
            'Criterion 5',
            'Total Score',
            'Comments',
            'Status',
            'Created Date'
        ];
        fputcsv($output, $headers);
        
        // Query in batches using direct SQL for efficiency
        $table_name = $wpdb->prefix . 'mt_evaluations';
        $offset = 0;
        $batch_size = 100;
        
        while (true) {
            $evaluations = $wpdb->get_results($wpdb->prepare(
                "SELECT e.*, 
                        u.display_name as jury_name,
                        p.post_title as candidate_name
                 FROM {$table_name} e
                 LEFT JOIN {$wpdb->users} u ON e.jury_member_id = u.ID
                 LEFT JOIN {$wpdb->posts} p ON e.candidate_id = p.ID
                 ORDER BY e.id ASC
                 LIMIT %d OFFSET %d",
                $batch_size,
                $offset
            ));
            
            if (empty($evaluations)) {
                break;
            }
            
            foreach ($evaluations as $eval) {
                $total_score = $eval->courage_score + $eval->innovation_score + 
                              $eval->implementation_score + $eval->relevance_score + $eval->visibility_score;
                
                $row = [
                    $eval->id,
                    $eval->jury_name,
                    $eval->candidate_name,
                    $eval->courage_score,
                    $eval->innovation_score,
                    $eval->implementation_score,
                    $eval->relevance_score,
                    $eval->visibility_score,
                    $total_score,
                    $eval->comments,
                    $eval->status,
                    $eval->created_at
                ];
                fputcsv($output, $row);
                
                // Free memory
                unset($row);
            }
            
            $offset += $batch_size;
            
            // Prevent timeout on large exports
            if (function_exists('set_time_limit')) {
                set_time_limit(30);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Generate template dynamically if file doesn't exist
     *
     * @param string $type Template type
     */
    private static function generate_template($type) {
        // Clean any output buffer
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $type . '-template.csv');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        if ($type === 'candidates') {
            // Write headers matching the expected format
            fputcsv($output, [
                'ID',
                'Name',
                'Organisation',
                'Position',
                'LinkedIn-Link',
                'Webseite',
                'Article about coming of age',
                'Description',
                'Category',
                'Status'
            ]);
            
            // Write sample data
            fputcsv($output, [
                '1',
                'Dr. Anna Schmidt',
                'GreenMobility GmbH',
                'CEO & Gründerin',
                'https://linkedin.com/in/anna-schmidt',
                'https://greenmobility.example.com',
                'https://example.com/article-schmidt',
                'Mut & Pioniergeist: Dr. Schmidt zeigt außergewöhnlichen Mut bei der Entwicklung von Wasserstoff-betriebenen Stadtbussen. Innovationsgrad: Ihre Zero-Emission-Busflotte mit 500km Reichweite setzt neue Maßstäbe.',
                'Startup',
                'Top 50: Yes'
            ]);
            
        } elseif ($type === 'jury_members') {
            // Write headers
            fputcsv($output, [
                'name',
                'title',
                'organization',
                'email',
                'role'
            ]);
            
            // Write sample data
            fputcsv($output, [
                'Prof. Dr. Andreas Herrmann',
                'President',
                'Institut für Mobilität, University of St. Gallen',
                'andreas.herrmann@example.com',
                'jury_member'
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get upload error constant name for debugging
     *
     * @param int $error_code PHP upload error code
     * @return string Error constant name
     */
    private static function get_upload_error_constant($error_code) {
        $errors = [
            UPLOAD_ERR_OK => 'UPLOAD_ERR_OK',
            UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE',
            UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE',
            UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL',
            UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE',
            UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
            UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
            UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION',
        ];
        
        return isset($errors[$error_code]) ? $errors[$error_code] : 'UNKNOWN_ERROR';
    }
}

// Initialize the class
MT_Import_Export::init();
