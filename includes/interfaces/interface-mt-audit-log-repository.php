<?php
/**
 * Audit Log Repository Interface
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Interfaces;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface MT_Audit_Log_Repository_Interface
 *
 * Contract for audit log repository implementations
 */
interface MT_Audit_Log_Repository_Interface extends MT_Repository_Interface {
    
    /**
     * Log an audit event
     *
     * @param string $action Action performed
     * @param string $object_type Type of object affected
     * @param int $object_id ID of object affected
     * @param array $details Additional details
     * @param int $user_id User ID (optional, defaults to current user)
     * @return int|false Log entry ID or false on failure
     */
    public function log($action, $object_type, $object_id, $details = [], $user_id = null);
    
    /**
     * Get audit logs
     *
     * @param array $args Query arguments
     * @return array Log entries
     */
    public function get_logs($args = []);
    
    /**
     * Get logs by user
     *
     * @param int $user_id User ID
     * @param int $limit Number of entries to retrieve
     * @return array Log entries
     */
    public function get_by_user($user_id, $limit = 100);
    
    /**
     * Get logs by object
     *
     * @param string $object_type Object type
     * @param int $object_id Object ID
     * @param int $limit Number of entries to retrieve
     * @return array Log entries
     */
    public function get_by_object($object_type, $object_id, $limit = 100);
    
    /**
     * Get logs by action
     *
     * @param string $action Action name
     * @param int $limit Number of entries to retrieve
     * @return array Log entries
     */
    public function get_by_action($action, $limit = 100);
    
    /**
     * Clean old logs
     *
     * @param int $days Number of days to keep
     * @return int Number of logs deleted
     */
    public function clean_old_logs($days = 90);
    
    /**
     * Get log statistics
     *
     * @return array Statistics data
     */
    public function get_statistics();
}