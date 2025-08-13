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
        
        // AJAX handlers
        add_action('wp_ajax_mt_import_csv', [__CLASS__, 'ajax_import_csv']);
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
            wp_redirect(add_query_arg([
                'page' => 'mt-import-export',
                'message' => 'import_error'
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
     */
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
    
    /**
     * Process CSV import
     *
     * @param string $file_path Path to uploaded CSV file
     * @param string $import_type Type of import (candidates or jury_members)
     * @param bool $update_existing Whether to update existing records
     * @return array Import results
     */
    private static function process_csv_import($file_path, $import_type, $update_existing = false) {
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
        // Required fields
        $required_fields = ['name', 'company', 'category'];
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
        $company = sanitize_text_field($data['company']);
        $category = sanitize_text_field($data['category']);
        $description = isset($data['description']) ? wp_kses_post($data['description']) : '';
        $innovation = isset($data['innovation']) ? wp_kses_post($data['innovation']) : '';
        $website = isset($data['website']) ? esc_url_raw($data['website']) : '';
        $linkedin = isset($data['linkedin']) ? esc_url_raw($data['linkedin']) : '';
        $email = isset($data['email']) ? sanitize_email($data['email']) : '';
        
        // Check if candidate exists
        $existing_query = new \WP_Query([
            'post_type' => 'mt_candidate',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => '_mt_email',
                    'value' => $email,
                    'compare' => '='
                ],
                [
                    'key' => '_mt_candidate_name',
                    'value' => $name,
                    'compare' => '='
                ]
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
            $status = 'updated';
        } else {
            $post_id = wp_insert_post($post_data);
            $status = 'created';
        }
        
        if (is_wp_error($post_id)) {
            return [
                'status' => 'error',
                'error' => $post_id->get_error_message()
            ];
        }
        
        // Update meta fields
        update_post_meta($post_id, '_mt_candidate_name', $name);
        update_post_meta($post_id, '_mt_organization', $company);
        update_post_meta($post_id, '_mt_category_type', $category);
        update_post_meta($post_id, '_mt_description_full', $description);
        update_post_meta($post_id, '_mt_innovation', $innovation);
        update_post_meta($post_id, '_mt_website_url', $website);
        update_post_meta($post_id, '_mt_linkedin_url', $linkedin);
        update_post_meta($post_id, '_mt_email', $email);
        
        return ['status' => $status];
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
        
        // Write data
        foreach ($candidates as $candidate) {
            fputcsv($output, [
                get_post_meta($candidate->ID, '_mt_candidate_name', true) ?: $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_organization', true),
                get_post_meta($candidate->ID, '_mt_category_type', true),
                get_post_meta($candidate->ID, '_mt_description_full', true),
                get_post_meta($candidate->ID, '_mt_innovation', true),
                get_post_meta($candidate->ID, '_mt_website_url', true),
                get_post_meta($candidate->ID, '_mt_linkedin_url', true),
                get_post_meta($candidate->ID, '_mt_email', true)
            ]);
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
            SELECT e.*, c.post_title as candidate_name, u.display_name as jury_member
            FROM {$table_name} e
            LEFT JOIN {$wpdb->posts} c ON e.candidate_id = c.ID
            LEFT JOIN {$wpdb->users} u ON e.jury_member_id = u.ID
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
                $evaluation->criterion_1,
                $evaluation->criterion_2,
                $evaluation->criterion_3,
                $evaluation->criterion_4,
                $evaluation->criterion_5,
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
        $table_name = $wpdb->prefix . 'mt_assignments';
        $assignments = $wpdb->get_results("
            SELECT a.*, c.post_title as candidate_name, u.display_name as jury_member
            FROM {$table_name} a
            LEFT JOIN {$wpdb->posts} c ON a.candidate_id = c.ID
            LEFT JOIN {$wpdb->users} u ON a.jury_member_id = u.ID
            ORDER BY a.created_at DESC
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
        
        // Check if template file exists
        $template_file = MT_PLUGIN_DIR . 'data/templates/' . $type . '.csv';
        
        if (!file_exists($template_file)) {
            wp_die(__('Template file not found', 'mobility-trailblazers'));
        }
        
        // Set headers for download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $type . '-template.csv');
        header('Content-Length: ' . filesize($template_file));
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file
        readfile($template_file);
        exit;
    }
}

// Initialize the class
MT_Import_Export::init();