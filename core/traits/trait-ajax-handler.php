<?php
/**
 * AJAX Handler Trait
 *
 * @package MobilityTrailblazers
 * @subpackage Core\Traits
 */

namespace MobilityTrailblazers\Core\Traits;

/**
 * Trait for handling AJAX requests
 */
trait Ajax_Handler {
    
    /**
     * Verify AJAX nonce
     *
     * @param string $action The action name
     * @param string $nonce_field The nonce field name
     * @return bool
     */
    protected function verify_ajax_nonce($action, $nonce_field = 'nonce') {
        if (!isset($_POST[$nonce_field])) {
            return false;
        }
        
        return wp_verify_nonce($_POST[$nonce_field], $action);
    }
    
    /**
     * Send AJAX success response
     *
     * @param mixed $data The data to send
     * @param string $message Optional message
     * @return void
     */
    protected function ajax_success($data = null, $message = '') {
        wp_send_json_success(array(
            'data' => $data,
            'message' => $message
        ));
    }
    
    /**
     * Send AJAX error response
     *
     * @param string $message Error message
     * @param mixed $data Optional error data
     * @return void
     */
    protected function ajax_error($message, $data = null) {
        wp_send_json_error(array(
            'message' => $message,
            'data' => $data
        ));
    }
    
    /**
     * Check if current request is AJAX
     *
     * @return bool
     */
    protected function is_ajax_request() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }
    
    /**
     * Check user capabilities for AJAX request
     *
     * @param string $capability Required capability
     * @return bool
     */
    protected function check_ajax_capability($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            $this->ajax_error(__('Insufficient permissions', 'mobility-trailblazers'));
            return false;
        }
        
        return true;
    }
} 