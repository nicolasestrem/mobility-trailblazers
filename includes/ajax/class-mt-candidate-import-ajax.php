<?php
/**
 * Candidate Import AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.2.15
 */

namespace MobilityTrailblazers\Ajax;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Import_Ajax
 *
 * Handles AJAX requests for candidate CSV imports
 */
class MT_Candidate_Import_Ajax {
    
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    public function init() {
        add_action('wp_ajax_mt_import_candidates', [$this, 'handle_import']);
        add_action('wp_ajax_nopriv_mt_import_candidates', [$this, 'handle_import_nopriv']);
    }
    
    /**
     * Handle import request for non-privileged users
     *
     * @return void
     */
    public function handle_import_nopriv() {
        wp_send_json_error([
            'message' => __('You must be logged in to import candidates.', 'mobility-trailblazers')
        ]);
    }
    
    /**
     * Handle candidate import via AJAX
     *
     * @return void
     */
    public function handle_import() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers')
            ]);
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error([
                'message' => __('You do not have permission to import candidates.', 'mobility-trailblazers')
            ]);
            return;
        }
        
        // Check for uploaded file
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $error_message = __('File upload failed.', 'mobility-trailblazers');
            
            // Provide more specific error messages
            if (isset($_FILES['csv_file']['error'])) {
                switch ($_FILES['csv_file']['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error_message = __('File is too large. Maximum size is 10MB.', 'mobility-trailblazers');
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error_message = __('File was only partially uploaded. Please try again.', 'mobility-trailblazers');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $error_message = __('No file was selected.', 'mobility-trailblazers');
                        break;
                    case UPLOAD_ERR_NO_TMP_DIR:
                    case UPLOAD_ERR_CANT_WRITE:
                        $error_message = __('Server error: Unable to save uploaded file.', 'mobility-trailblazers');
                        break;
                    case UPLOAD_ERR_EXTENSION:
                        $error_message = __('File upload blocked by server configuration.', 'mobility-trailblazers');
                        break;
                }
            }
            
            wp_send_json_error([
                'message' => $error_message
            ]);
            return;
        }
        
        // Validate file type
        $file_info = wp_check_filetype($_FILES['csv_file']['name']);
        $allowed_types = ['csv', 'txt'];
        
        if (!in_array(strtolower($file_info['ext']), $allowed_types)) {
            wp_send_json_error([
                'message' => __('Invalid file type. Please upload a CSV file.', 'mobility-trailblazers')
            ]);
            return;
        }
        
        // Validate file size (10MB max)
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        if ($_FILES['csv_file']['size'] > $max_size) {
            wp_send_json_error([
                'message' => sprintf(
                    __('File is too large. Maximum size is %s.', 'mobility-trailblazers'),
                    size_format($max_size)
                )
            ]);
            return;
        }
        
        // Get import options
        $options = [
            'update_existing' => isset($_POST['update_existing']) && $_POST['update_existing'] === '1',
            'skip_duplicates' => isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === '1',
            'validate_urls' => true,
            'import_photos' => false, // Disabled for AJAX imports for performance
            'dry_run' => false
        ];
        
        // Use the enhanced profile importer
        require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-enhanced-profile-importer.php';
        
        try {
            // Process the import
            $result = \MobilityTrailblazers\Admin\MT_Enhanced_Profile_Importer::import_csv(
                $_FILES['csv_file']['tmp_name'],
                $options
            );
            
            // Check if import was successful
            if (($result['success'] + $result['updated']) > 0 || $result['skipped'] > 0) {
                // Log the import
                if (class_exists('\MobilityTrailblazers\Core\MT_Audit_Logger')) {
                    \MobilityTrailblazers\Core\MT_Audit_Logger::log(
                        'csv_import',
                        'candidate',
                        0,
                        sprintf(
                            'CSV import: %d created, %d updated, %d skipped, %d errors',
                            $result['success'],
                            $result['updated'],
                            $result['skipped'],
                            $result['errors']
                        )
                    );
                }
                
                // Return success with statistics
                wp_send_json_success([
                    'imported' => $result['success'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                    'error_details' => $result['error_details'],
                    'message' => sprintf(
                        __('Import completed: %d created, %d updated, %d skipped, %d errors', 'mobility-trailblazers'),
                        $result['success'],
                        $result['updated'],
                        $result['skipped'],
                        $result['errors']
                    )
                ]);
            } else {
                // No successful imports
                $error_message = __('No candidates were imported.', 'mobility-trailblazers');
                
                // Add specific error details if available
                if (!empty($result['messages'])) {
                    $error_message .= ' ' . implode(' ', array_slice($result['messages'], -3));
                }
                
                wp_send_json_error([
                    'message' => $error_message,
                    'details' => $result['error_details']
                ]);
            }
            
        } catch (\Exception $e) {
            // Log the error
            error_log('MT CSV Import Error: ' . $e->getMessage());
            
            wp_send_json_error([
                'message' => sprintf(
                    __('Import failed: %s', 'mobility-trailblazers'),
                    $e->getMessage()
                )
            ]);
        }
    }
    
    /**
     * Handle dry run import (preview without saving)
     *
     * @return void
     */
    public function handle_dry_run() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_ajax_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed.', 'mobility-trailblazers')
            ]);
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error([
                'message' => __('Insufficient permissions.', 'mobility-trailblazers')
            ]);
            return;
        }
        
        // Check for uploaded file
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error([
                'message' => __('File upload failed.', 'mobility-trailblazers')
            ]);
            return;
        }
        
        // Set dry run option
        $options = [
            'update_existing' => isset($_POST['update_existing']) && $_POST['update_existing'] === '1',
            'skip_duplicates' => isset($_POST['skip_duplicates']) && $_POST['skip_duplicates'] === '1',
            'validate_urls' => true,
            'import_photos' => false,
            'dry_run' => true // This prevents actual database changes
        ];
        
        // Use the enhanced profile importer
        require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-enhanced-profile-importer.php';
        
        try {
            // Process the dry run
            $result = \MobilityTrailblazers\Admin\MT_Enhanced_Profile_Importer::import_csv(
                $_FILES['csv_file']['tmp_name'],
                $options
            );
            
            // Return preview results
            wp_send_json_success([
                'would_import' => $result['success'],
                'would_update' => $result['updated'],
                'would_skip' => $result['skipped'],
                'validation_errors' => $result['errors'],
                'preview_data' => array_slice($result['messages'], 0, 10), // First 10 messages
                'message' => __('Preview complete. No data was saved.', 'mobility-trailblazers')
            ]);
            
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Preview failed: %s', 'mobility-trailblazers'),
                    $e->getMessage()
                )
            ]);
        }
    }
}