<?php
/**
 * Base AJAX Handler
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
 * Class MT_Base_Ajax
 *
 * Base class for AJAX handlers
 */
abstract class MT_Base_Ajax {
    
    /**
     * Verify nonce
     *
     * @param string $nonce_name Nonce name
     * @return void
     */
    protected function verify_nonce($nonce_name = 'mt_ajax_nonce') {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $nonce_name)) {
            $this->error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Check user permission
     *
     * @param string $capability Required capability
     * @return void
     */
    protected function check_permission($capability) {
        if (!current_user_can($capability)) {
            $this->error(__('You do not have permission to perform this action.', 'mobility-trailblazers'));
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
        wp_send_json_error([
            'message' => $message ?: __('An error occurred. Please try again.', 'mobility-trailblazers'),
            'data' => $data
        ]);
    }
    
    /**
     * Get POST parameter
     *
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_param($key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
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
        $value = $this->get_param($key, $default);
        return is_array($value) ? $value : $default;
    }
    
    /**
     * Initialize AJAX handler
     *
     * @return void
     */
    abstract public function init();
} 