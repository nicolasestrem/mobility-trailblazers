<?php
/**
 * Import AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 2.2.15
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Core\MT_Logger;
use MobilityTrailblazers\Admin\MT_Import_Handler;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Import_Ajax
 *
 * Handles AJAX requests for CSV imports
 */
class MT_Import_Ajax extends MT_Base_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX action hook
        add_action('wp_ajax_mt_import_candidates', [$this, 'handle_candidate_import']);
    }
    
    /**
     * Initialize AJAX handler
     *
     * @return void
     */
    public function init() {
        // Constructor already handles initialization
        // This method is required by abstract parent class
    }
    
    /**
     * Handle candidate import via AJAX
     *
     * @return void
     */
    public function handle_candidate_import() {
        try {
            // Step 1: Verify nonce using parent method
            if (!$this->verify_nonce('mt_ajax_nonce')) {
                $this->error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
                return;
            }
            
            // Step 2: Check user capability - requires admin access for imports
            if (!current_user_can('manage_options')) {
                $this->error(__('You do not have permission to import candidates. Administrator access required.', 'mobility-trailblazers'));
                return;
            }
            
            // Step 3: Validate uploaded file using enhanced validation
            $validation = $this->validate_upload($_FILES['csv_file'], ['csv', 'txt'], 10 * MB_IN_BYTES);
            if ($validation !== true) {
                MT_Logger::error('File upload validation failed', [
                    'error' => $validation,
                    'file_name' => $_FILES['csv_file']['name'] ?? 'unknown',
                    'action' => 'mt_import_candidates'
                ]);
                $this->error($validation);
                return;
            }
            
            // Step 4: Prepare import options
            $options = [
                'update_existing' => $this->get_param('update_existing') === '1',
                'skip_duplicates' => $this->get_param('skip_duplicates', '1') === '1',
                'skip_empty_fields' => false,
                'validate_urls' => true,
                'import_photos' => false, // Disabled for AJAX imports for performance
                'dry_run' => false
            ];
            
            // Log import attempt
            MT_Logger::info('Starting CSV import', [
                'file_name' => $_FILES['csv_file']['name'],
                'file_size' => $_FILES['csv_file']['size'],
                'options' => $options,
                'user_id' => get_current_user_id()
            ]);
            
            // Step 8: Call the import method using MT_Import_Handler
            $handler = new MT_Import_Handler();
            $update_existing = isset($options['update_existing']) ? $options['update_existing'] : false;
            
            $result = $handler->process_csv_import(
                $_FILES['csv_file']['tmp_name'],
                'candidates',  // Import type for candidates
                $update_existing
            );
            
            // Step 9: Process results
            $total_processed = $result['success'] + $result['updated'] + $result['skipped'];
            
            if ($total_processed > 0) {
                // Success - at least some records were processed
                MT_Logger::info('CSV import completed', [
                    'imported' => $result['success'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors'],
                    'user_id' => get_current_user_id()
                ]);
                
                // Log to audit trail if available
                if (class_exists('\MobilityTrailblazers\Core\MT_Audit_Logger')) {
                    \MobilityTrailblazers\Core\MT_Audit_Logger::log(
                        'csv_import',
                        'candidate',
                        0,
                        sprintf(
                            'Imported %d, updated %d, skipped %d, errors %d',
                            $result['success'],
                            $result['updated'],
                            $result['skipped'],
                            $result['errors']
                        )
                    );
                }
                
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
                    $response_data['error_details'] = array_slice($result['error_details'], 0, 10); // Limit to 10 errors
                }
                
                // Add messages if in debug mode
                if (defined('WP_DEBUG') && WP_DEBUG && !empty($result['messages'])) {
                    $response_data['debug_messages'] = array_slice($result['messages'], 0, 5);
                }
                
                // Send success response
                $this->success(
                    $response_data,
                    sprintf(
                        __('Import completed: %d created, %d updated, %d skipped, %d errors', 'mobility-trailblazers'),
                        $result['success'],
                        $result['updated'],
                        $result['skipped'],
                        $result['errors']
                    )
                );
                
            } else if ($result['errors'] > 0) {
                // All records failed
                MT_Logger::error('CSV import failed - all records had errors', [
                    'error_count' => $result['errors'],
                    'error_details' => array_slice($result['error_details'], 0, 5)
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
                    'error_details' => array_slice($result['error_details'], 0, 10)
                ]);
                
            } else {
                // No records processed at all
                MT_Logger::warning('CSV import - no records processed', [
                    'messages' => array_slice($result['messages'], 0, 5)
                ]);
                
                $this->error(
                    __('No candidates were imported. Please check your CSV format.', 'mobility-trailblazers'),
                    ['messages' => array_slice($result['messages'], 0, 5)]
                );
            }
            
        } catch (\Exception $e) {
            // Handle any exceptions
            $this->handle_exception($e, 'Candidate import');
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
new \MobilityTrailblazers\Ajax\MT_Import_Ajax();
