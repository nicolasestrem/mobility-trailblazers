<?php
/**
 * Base AJAX Handler
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
 * Class MT_Base_Ajax
 *
 * Base class for AJAX handlers
 */
abstract class MT_Base_Ajax {
    
    /**
     * Verify nonce
     *
     * @param string $nonce_name Nonce name
     * @return bool
     */
    protected function verify_nonce($nonce_name = 'mt_ajax_nonce') {
        try {
            $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
            $result = wp_verify_nonce($nonce, $nonce_name);

            if (!$result) {
                MT_Logger::security_event('Nonce verification failed', [
                    'nonce_name' => $nonce_name,
                    'action' => $_REQUEST['action'] ?? 'unknown',
                    'provided_nonce' => $nonce ? 'present' : 'missing'
                ]);
            }

            return $result;

        } catch (\Exception $e) {
            MT_Logger::critical('Exception during nonce verification', [
                'exception' => $e->getMessage(),
                'nonce_name' => $nonce_name
            ]);
            return false;
        }
    }
    
    /**
     * Check user permission
     *
     * @param string $capability Required capability
     * @return bool
     */
    protected function check_permission($capability) {
        try {
            if (!current_user_can($capability)) {
                MT_Logger::security_event('Permission denied', [
                    'required_capability' => $capability,
                    'user_id' => get_current_user_id(),
                    'action' => $_REQUEST['action'] ?? 'unknown'
                ]);
                $this->error(__('You do not have permission to perform this action.', 'mobility-trailblazers'));
                return false;
            }
            return true;
        } catch (\Exception $e) {
            MT_Logger::critical('Exception during permission check', [
                'exception' => $e->getMessage(),
                'capability' => $capability
            ]);
            $this->error(__('Permission check failed. Please try again.', 'mobility-trailblazers'));
            return false;
        }
    }
    
    /**
     * Send success response
     *
     * @param mixed $data Response data
     * @param string $message Success message
     * @return void
     */
    protected function success($data = null, $message = '') {
        wp_send_json_success([
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Send error response
     *
     * @param string $message Error message
     * @param mixed $data Additional error data
     * @return void
     */
    protected function error($message = '', $data = null) {
        $error_message = $message ?: __('An error occurred. Please try again.', 'mobility-trailblazers');

        // Log the error
        MT_Logger::ajax_error($_REQUEST['action'] ?? 'unknown', $error_message, [
            'user_id' => get_current_user_id(),
            'additional_data' => $data
        ]);

        wp_send_json_error([
            'message' => $error_message,
            'data' => $data
        ]);
    }
    
    /**
     * Send JSON success response
     *
     * @param mixed $data Response data
     * @return void
     */
    protected function send_json_success($data = null) {
        wp_send_json_success($data);
    }
    
    /**
     * Send JSON error response
     *
     * @param string $message Error message
     * @return void
     */
    protected function send_json_error($message = '') {
        wp_send_json_error($message);
    }
    
    /**
     * Get POST parameter
     *
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_param($key, $default = null) {
        return isset($_REQUEST[$key]) ? sanitize_text_field($_REQUEST[$key]) : $default;
    }
    
    /**
     * Get sanitized text parameter
     *
     * @param string $key Parameter key
     * @param string $default Default value
     * @return string
     */
    protected function get_text_param($key, $default = '') {
        return sanitize_text_field($this->get_param($key, $default));
    }
    
    /**
     * Get sanitized integer parameter
     *
     * @param string $key Parameter key
     * @param int $default Default value
     * @return int
     */
    protected function get_int_param($key, $default = 0) {
        return intval($this->get_param($key, $default));
    }
    
    /**
     * Get sanitized float parameter
     *
     * @param string $key Parameter key
     * @param float $default Default value
     * @return float
     */
    protected function get_float_param($key, $default = 0.0) {
        return floatval($this->get_param($key, $default));
    }
    
    /**
     * Get sanitized textarea parameter
     *
     * @param string $key Parameter key
     * @param string $default Default value
     * @return string
     */
    protected function get_textarea_param($key, $default = '') {
        return sanitize_textarea_field($this->get_param($key, $default));
    }
    
    /**
     * Get array parameter
     *
     * @param string $key Parameter key
     * @param array $default Default value
     * @return array
     */
    protected function get_array_param($key, $default = []) {
        if (!isset($_REQUEST[$key])) {
            return $default;
        }

        $value = $_REQUEST[$key];
        return is_array($value) ? $value : $default;
    }

    /**
     * Handle exceptions in AJAX methods
     *
     * @param \Exception $e Exception object
     * @param string $context Context description
     * @return void
     */
    protected function handle_exception(\Exception $e, $context = '') {
        MT_Logger::critical('AJAX Exception: ' . $context, [
            'exception' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'action' => $_REQUEST['action'] ?? 'unknown',
            'user_id' => get_current_user_id(),
            'context' => $context
        ]);

        $this->error(__('An unexpected error occurred. Please try again.', 'mobility-trailblazers'));
    }

    /**
     * Validate required parameters
     *
     * @param array $required_params Array of required parameter names
     * @return bool
     */
    protected function validate_required_params($required_params) {
        $missing_params = [];

        foreach ($required_params as $param) {
            if (!isset($_REQUEST[$param]) || empty($_REQUEST[$param])) {
                $missing_params[] = $param;
            }
        }

        if (!empty($missing_params)) {
            MT_Logger::warning('Missing required AJAX parameters', [
                'missing_params' => $missing_params,
                'action' => $_REQUEST['action'] ?? 'unknown',
                'user_id' => get_current_user_id()
            ]);

            $this->error(sprintf(
                __('Missing required parameters: %s', 'mobility-trailblazers'),
                implode(', ', $missing_params)
            ));
            return false;
        }

        return true;
    }

    /**
     * Validate uploaded file
     *
     * @param array $file $_FILES array element
     * @param array $allowed_types Allowed file extensions
     * @param int $max_size Maximum file size in bytes
     * @return bool|string True if valid, error message if not
     * @since 2.2.28
     */
    protected function validate_upload($file, $allowed_types = ['csv'], $max_size = null) {
        // Check if file was uploaded
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return __('File upload failed. Please try again.', 'mobility-trailblazers');
        }
        
        // Set default max size (10MB)
        if ($max_size === null) {
            $max_size = 10 * MB_IN_BYTES;
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            return sprintf(
                __('File too large. Maximum size is %s.', 'mobility-trailblazers'),
                size_format($max_size)
            );
        }
        
        // Validate file extension
        $file_info = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed_types)) {
            return sprintf(
                __('Invalid file type. Allowed types: %s', 'mobility-trailblazers'),
                implode(', ', $allowed_types)
            );
        }
        
        // Additional MIME type validation for CSV files
        if (in_array('csv', $allowed_types)) {
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
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            // Allow text files and the specific MIME types
            if (!in_array($mime_type, $allowed_mimes) && strpos($mime_type, 'text') === false) {
                MT_Logger::warning('Unexpected MIME type for CSV', [
                    'mime_type' => $mime_type,
                    'file_name' => $file['name']
                ]);
                // Don't block entirely, but log the warning
            }
        }
        
        // Check for malicious content patterns
        $content = file_get_contents($file['tmp_name'], false, null, 0, 1024);
        if ($content !== false) {
            // Check for PHP tags
            if (preg_match('/<\?php|<\?=/i', $content)) {
                MT_Logger::security_event('Malicious file upload attempt', [
                    'file_name' => $file['name'],
                    'user_id' => get_current_user_id()
                ]);
                return __('File contains prohibited content.', 'mobility-trailblazers');
            }
            
            // Check for script tags in CSV
            if (in_array('csv', $allowed_types) && preg_match('/<script|javascript:/i', $content)) {
                return __('File contains prohibited content.', 'mobility-trailblazers');
            }
        }
        
        return true;
    }
    
    /**
     * Ensure container is properly initialized
     * Call this in AJAX handlers that rely on the container
     *
     * @return bool True if container is ready
     */
    protected function ensure_container() {
        // Check if container is valid
        if (!\MobilityTrailblazers\Core\MT_Plugin::validate_container()) {
            // Try to force re-initialization
            $plugin = \MobilityTrailblazers\Core\MT_Plugin::get_instance();
            $container = $plugin->get_container();
            
            // Try again
            if (!\MobilityTrailblazers\Core\MT_Plugin::validate_container()) {
                $this->error(__('Service initialization error. Please refresh the page and try again.', 'mobility-trailblazers'));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Initialize AJAX handler
     *
     * @return void
     */
    abstract public function init();
} 
