<?php
/**
 * Base AJAX Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

namespace MobilityTrailblazers\Ajax;

abstract class MT_Base_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->register_hooks();
    }
    
    /**
     * Register AJAX hooks
     */
    abstract protected function register_hooks();
    
    /**
     * Verify nonce
     */
    protected function verify_nonce($action = 'mt_ajax_nonce') {
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
    }
    
    /**
     * Check user permission
     */
    protected function check_permission($capability) {
        if (!current_user_can($capability)) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'mobility-trailblazers')));
        }
    }
    
    /**
     * Send success response
     */
    protected function success($data = null, $message = '') {
        $response = array();
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response = array_merge($response, is_array($data) ? $data : array('data' => $data));
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Send error response
     */
    protected function error($message, $data = null) {
        $response = array('message' => $message);
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        wp_send_json_error($response);
    }
    
    /**
     * Get POST parameter
     */
    protected function get_param($key, $default = null) {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Get sanitized integer parameter
     */
    protected function get_int_param($key, $default = 0) {
        return isset($_POST[$key]) ? intval($_POST[$key]) : $default;
    }
    
    /**
     * Get sanitized array parameter
     */
    protected function get_array_param($key, $default = array()) {
        return isset($_POST[$key]) && is_array($_POST[$key]) ? $_POST[$key] : $default;
    }
}