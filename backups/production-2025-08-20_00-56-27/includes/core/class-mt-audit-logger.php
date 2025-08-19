<?php
/**
 * Audit Logger
 *
 * @package MobilityTrailblazers
 * @since 2.2.2
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Audit_Logger
 *
 * Handles audit logging for platform actions
 */
class MT_Audit_Logger {
    
    /**
     * Log an audit event
     *
     * @param string $action The action performed (e.g., 'assignment_created', 'evaluation_submitted')
     * @param string $object_type The type of object (e.g., 'candidate', 'evaluation')
     * @param int $object_id The ID of the related post or item
     * @param array $details Optional array of additional details to store as JSON
     * @return bool True on success, false on failure
     */
    public static function log($action, $object_type, $object_id, $details = []) {
        global $wpdb;
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Convert details array to JSON string
        $details_json = !empty($details) ? json_encode($details) : null;
        
        // Insert into audit log table
        $result = $wpdb->insert(
            $wpdb->prefix . 'mt_audit_log',
            [
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'object_type' => sanitize_text_field($object_type),
                'object_id' => absint($object_id),
                'details' => $details_json,
                'created_at' => current_time('mysql')
            ],
            [
                '%d', // user_id
                '%s', // action
                '%s', // object_type
                '%d', // object_id
                '%s', // details
                '%s'  // created_at
            ]
        );
        
        return $result !== false;
    }
}
