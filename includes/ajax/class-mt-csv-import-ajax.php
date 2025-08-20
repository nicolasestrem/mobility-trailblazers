<?php
/**
 * CSV Import AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.2.23
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Admin\MT_Import_Handler;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_CSV_Import_Ajax
 *
 * Handles AJAX requests for CSV imports
 */
class MT_CSV_Import_Ajax extends MT_Base_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_mt_import_csv', [$this, 'handle_csv_import']);
        add_action('wp_ajax_mt_validate_csv', [$this, 'validate_csv_file']);
        add_action('wp_ajax_mt_get_import_progress', [$this, 'get_import_progress']);
    }
    
    /**
     * Initialize AJAX handler
     *
     * @return void
     */
    public function init() {
        // Constructor already handles initialization
    }
    
    /**
     * Handle CSV import via AJAX
     *
     * @return void
     */
    public function handle_csv_import() {
        try {
            // Step 1: Verify nonce
            if (!$this->verify_nonce('mt_ajax_nonce')) {
                $this->error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
                return;
            }
            
            // Step 2: Check user capability - requires admin access for imports
            if (!current_user_can('manage_options')) {
                $this->error(__('You do not have permission to import data. Administrator access required.', 'mobility-trailblazers'));
                return;
            }
            
            // Step 3: Validate import type
            $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : '';
            if (!in_array($import_type, ['candidates', 'jury_members'])) {
                $this->error(__('Invalid import type selected.', 'mobility-trailblazers'));
                return;
            }
            
            // Step 4: Validate uploaded file
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $error_message = $this->get_upload_error_message();
                MT_Logger::error('File upload failed', [
                    'error_code' => $_FILES['csv_file']['error'] ?? 'no_file',
                    'action' => 'mt_import_csv'
                ]);
                $this->error($error_message);
                return;
            }
            
            // Step 5: Validate file type
            $file_info = wp_check_filetype($_FILES['csv_file']['name']);
            $allowed_types = ['csv', 'txt', 'text'];
            
            if (!empty($file_info['ext']) && !in_array(strtolower($file_info['ext']), $allowed_types)) {
                MT_Logger::warning('Invalid file type for import', [
                    'file_type' => $file_info['ext'],
                    'file_name' => $_FILES['csv_file']['name']
                ]);
                $this->error(__('Invalid file type. Please upload a CSV file.', 'mobility-trailblazers'));
                return;
            }
            
            // Step 6: Validate file size (10MB max)
            $max_size = 10 * MB_IN_BYTES;
            if ($_FILES['csv_file']['size'] > $max_size) {
                MT_Logger::warning('File too large for import', [
                    'file_size' => $_FILES['csv_file']['size'],
                    'max_size' => $max_size
                ]);
                $this->error(sprintf(
                    __('File is too large. Maximum size is %s.', 'mobility-trailblazers'),
                    size_format($max_size)
                ));
                return;
            }
            
            // Step 7: Additional MIME type validation for security
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $_FILES['csv_file']['tmp_name']);
            finfo_close($finfo);
            
            $allowed_mimes = [
                'text/csv', 
                'text/plain', 
                'application/csv', 
                'application/x-csv',
                'text/x-csv',
                'text/comma-separated-values',
                'application/vnd.ms-excel',
                'application/octet-stream'
            ];
            
            if (!in_array($mime_type, $allowed_mimes) && strpos($mime_type, 'text') === false) {
                MT_Logger::warning('Invalid MIME type detected', [
                    'mime_type' => $mime_type,
                    'file_name' => $_FILES['csv_file']['name']
                ]);
                // Don't completely block, but log the warning
            }
            
            // Step 8: Prepare import options
            $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] === 'true';
            
            // Log import attempt
            MT_Logger::info('Starting CSV import via AJAX', [
                'file_name' => $_FILES['csv_file']['name'],
                'file_size' => $_FILES['csv_file']['size'],
                'import_type' => $import_type,
                'update_existing' => $update_existing,
                'user_id' => get_current_user_id()
            ]);
            
            // Step 9: Set progress transient for tracking
            $progress_key = 'mt_import_progress_' . get_current_user_id();
            set_transient($progress_key, [
                'status' => 'processing',
                'message' => __('Processing CSV file...', 'mobility-trailblazers'),
                'percentage' => 10
            ], 300); // 5 minutes expiry
            
            // Step 10: Process the import using MT_Import_Handler
            if (!class_exists('\MobilityTrailblazers\Admin\MT_Import_Handler')) {
                $this->error(__('Import handler not available. Please contact administrator.', 'mobility-trailblazers'));
                return;
            }
            
            $handler = new MT_Import_Handler();
            $result = $handler->process_csv_import(
                $_FILES['csv_file']['tmp_name'],
                $import_type,
                $update_existing
            );
            
            // Step 11: Update progress to complete
            set_transient($progress_key, [
                'status' => 'complete',
                'message' => __('Import completed!', 'mobility-trailblazers'),
                'percentage' => 100,
                'results' => $result
            ], 60); // Keep for 1 minute
            
            // Step 12: Process results
            $total_processed = $result['success'] + $result['updated'] + $result['skipped'];
            
            if ($total_processed > 0 || $result['errors'] === 0) {
                // Success - at least some records were processed or no errors
                MT_Logger::info('CSV import completed', [
                    'imported' => $result['success'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                    'user_id' => get_current_user_id()
                ]);
                
                // Prepare success response
                $response_data = [
                    'imported' => $result['success'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                    'total_processed' => $total_processed
                ];
                
                // Add error details if present
                if (!empty($result['error_details'])) {
                    $response_data['error_details'] = array_slice($result['error_details'], 0, 10);
                }
                
                // Add messages if present
                if (!empty($result['messages'])) {
                    $response_data['messages'] = array_slice($result['messages'], 0, 5);
                }
                
                // Build summary message
                $message_parts = [];
                if ($result['success'] > 0) {
                    $message_parts[] = sprintf(_n('%d created', '%d created', $result['success'], 'mobility-trailblazers'), $result['success']);
                }
                if ($result['updated'] > 0) {
                    $message_parts[] = sprintf(_n('%d updated', '%d updated', $result['updated'], 'mobility-trailblazers'), $result['updated']);
                }
                if ($result['skipped'] > 0) {
                    $message_parts[] = sprintf(_n('%d skipped', '%d skipped', $result['skipped'], 'mobility-trailblazers'), $result['skipped']);
                }
                if ($result['errors'] > 0) {
                    $message_parts[] = sprintf(_n('%d error', '%d errors', $result['errors'], 'mobility-trailblazers'), $result['errors']);
                }
                
                $summary_message = __('Import completed:', 'mobility-trailblazers') . ' ' . implode(', ', $message_parts);
                
                // Send success response
                $this->success($response_data, $summary_message);
                
            } else {
                // All records failed
                MT_Logger::error('CSV import failed - all records had errors', [
                    'error_count' => $result['errors'],
                    'error_details' => array_slice($result['error_details'] ?? [], 0, 5)
                ]);
                
                $error_message = __('Import failed. All records had errors.', 'mobility-trailblazers');
                
                // Add first few error details
                if (!empty($result['error_details'])) {
                    $error_samples = array_slice($result['error_details'], 0, 3);
                    $error_messages = [];
                    foreach ($error_samples as $error) {
                        $error_messages[] = sprintf('Row %d: %s', $error['row'], $error['error']);
                    }
                    $error_message .= "\n" . implode("\n", $error_messages);
                }
                
                $this->error($error_message, [
                    'error_count' => $result['errors'],
                    'error_details' => array_slice($result['error_details'] ?? [], 0, 10)
                ]);
            }
            
        } catch (\Exception $e) {
            // Handle any exceptions
            $this->handle_exception($e, 'CSV import');
        }
    }
    
    /**
     * Validate CSV file before import
     *
     * @return void
     */
    public function validate_csv_file() {
        try {
            // Verify nonce
            if (!$this->verify_nonce('mt_ajax_nonce')) {
                $this->error(__('Security check failed.', 'mobility-trailblazers'));
                return;
            }
            
            // Check capability
            if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
                $this->error(__('Permission denied.', 'mobility-trailblazers'));
                return;
            }
            
            // Check file
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $this->error($this->get_upload_error_message());
                return;
            }
            
            // Read file headers
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if (!$handle) {
                $this->error(__('Could not read file.', 'mobility-trailblazers'));
                return;
            }
            
            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }
            
            // Get headers
            $headers = fgetcsv($handle, 0, ',');
            fclose($handle);
            
            if (!$headers) {
                $this->error(__('File appears to be empty or invalid.', 'mobility-trailblazers'));
                return;
            }
            
            // Clean headers
            $headers = array_map(function($header) {
                return trim(str_replace("\xEF\xBB\xBF", '', $header));
            }, $headers);
            
            // Count rows
            $row_count = 0;
            $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
            if ($handle) {
                while (fgetcsv($handle, 0, ',') !== FALSE) {
                    $row_count++;
                }
                fclose($handle);
            }
            
            // Return validation results
            $this->success([
                'headers' => $headers,
                'row_count' => $row_count - 1, // Exclude header row
                'file_size' => $_FILES['csv_file']['size'],
                'file_name' => $_FILES['csv_file']['name']
            ], __('File is valid and ready for import.', 'mobility-trailblazers'));
            
        } catch (\Exception $e) {
            $this->handle_exception($e, 'CSV validation');
        }
    }
    
    /**
     * Get import progress
     *
     * @return void
     */
    public function get_import_progress() {
        // Verify nonce
        if (!$this->verify_nonce('mt_ajax_nonce')) {
            $this->error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }
        
        // Get progress from transient
        $progress_key = 'mt_import_progress_' . get_current_user_id();
        $progress = get_transient($progress_key);
        
        if ($progress) {
            $this->success($progress);
        } else {
            $this->success([
                'status' => 'idle',
                'message' => __('No import in progress.', 'mobility-trailblazers'),
                'percentage' => 0
            ]);
        }
    }
    
    /**
     * Get user-friendly upload error message
     *
     * @return string Error message
     */
    private function get_upload_error_message() {
        if (!isset($_FILES['csv_file']['error'])) {
            return __('No file was uploaded.', 'mobility-trailblazers');
        }
        
        switch ($_FILES['csv_file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return __('File is too large. Maximum size is 10MB.', 'mobility-trailblazers');
                
            case UPLOAD_ERR_PARTIAL:
                return __('File was only partially uploaded. Please try again.', 'mobility-trailblazers');
                
            case UPLOAD_ERR_NO_FILE:
                return __('No file was selected.', 'mobility-trailblazers');
                
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
                return __('Server error: Unable to save uploaded file.', 'mobility-trailblazers');
                
            case UPLOAD_ERR_EXTENSION:
                return __('File upload blocked by server configuration.', 'mobility-trailblazers');
                
            default:
                return __('File upload failed. Please try again.', 'mobility-trailblazers');
        }
    }
}

// Initialize the class
new MT_CSV_Import_Ajax();
