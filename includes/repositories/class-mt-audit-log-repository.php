<?php
/**
 * Audit Log Repository
 *
 * @package MobilityTrailblazers
 * @since 2.2.2
 */

namespace MobilityTrailblazers\Repositories;

use MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Audit_Log_Repository
 *
 * Handles database operations for audit log
 */
class MT_Audit_Log_Repository implements MT_Audit_Log_Repository_Interface {
    
    /**
     * Table name
     *
     * @var string
     */
    private $table_name;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mt_audit_log';
    }
    
    /**
     * Get audit logs
     *
     * @param array $args Query arguments
     * @return array
     */
    public function get_logs($args = []) {
        global $wpdb;
        
        // Default arguments
        $defaults = [
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'user_id' => null,
            'action' => null,
            'object_type' => null,
            'object_id' => null,
            'date_from' => null,
            'date_to' => null
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Build WHERE clause
        $where_conditions = ['1=1'];
        $prepare_values = [];
        
        if (!empty($args['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $prepare_values[] = $args['user_id'];
        }
        
        if (!empty($args['action'])) {
            $where_conditions[] = 'action = %s';
            $prepare_values[] = $args['action'];
        }
        
        if (!empty($args['object_type'])) {
            $where_conditions[] = 'object_type = %s';
            $prepare_values[] = $args['object_type'];
        }
        
        if (!empty($args['object_id'])) {
            $where_conditions[] = 'object_id = %d';
            $prepare_values[] = $args['object_id'];
        }
        
        if (!empty($args['date_from'])) {
            $where_conditions[] = 'created_at >= %s';
            $prepare_values[] = $args['date_from'];
        }
        
        if (!empty($args['date_to'])) {
            $where_conditions[] = 'created_at <= %s';
            $prepare_values[] = $args['date_to'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Build ORDER BY clause - Security: Use prepared statements for field names
        $allowed_orderby = [
            'id' => 'al.id',
            'user_id' => 'al.user_id', 
            'action' => 'al.action',
            'object_type' => 'al.object_type',
            'object_id' => 'al.object_id',
            'created_at' => 'al.created_at'
        ];
        
        // Security: Validate orderby against exact field mappings
        $orderby_field = isset($allowed_orderby[$args['orderby']]) ? $allowed_orderby[$args['orderby']] : 'al.created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Calculate pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        // Security: Execute query using prepared statement parameters
        $all_prepare_values = array_merge($prepare_values, [$args['per_page'], $offset]);
        
        $base_query = "SELECT al.*, u.display_name as user_name, u.user_email
                       FROM {$this->table_name} al
                       LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
                       WHERE {$where_clause}
                       ORDER BY {$orderby_field} {$order}
                       LIMIT %d OFFSET %d";
        
        // Execute query
        if (!empty($all_prepare_values)) {
            $results = $wpdb->get_results($wpdb->prepare($base_query, $all_prepare_values));
        } else {
            $results = $wpdb->get_results($base_query);
        }
        
        // Get total count for pagination (exclude LIMIT/OFFSET parameters)
        $count_query = "SELECT COUNT(*) FROM {$this->table_name} WHERE {$where_clause}";
        if (!empty($prepare_values)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $prepare_values));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }
        
        return [
            'items' => $results ? $results : [],
            'total_items' => (int) $total_items,
            'total_pages' => ceil($total_items / $args['per_page']),
            'current_page' => $args['page'],
            'per_page' => $args['per_page']
        ];
    }
    
    /**
     * Get log by ID
     *
     * @param int $id Log ID
     * @return object|null
     */
    public function get_by_id($id) {
        global $wpdb;
        
        $query = "SELECT al.*, u.display_name as user_name, u.user_email
                  FROM {$this->table_name} al
                  LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
                  WHERE al.id = %d";
        
        return $wpdb->get_row($wpdb->prepare($query, $id));
    }
    
    /**
     * Get unique actions
     *
     * @return array
     */
    public function get_unique_actions() {
        global $wpdb;
        
        $query = "SELECT DISTINCT action FROM {$this->table_name} ORDER BY action";
        $results = $wpdb->get_col($query);
        
        return $results ? $results : [];
    }
    
    /**
     * Get unique object types
     *
     * @return array
     */
    public function get_unique_object_types() {
        global $wpdb;
        
        $query = "SELECT DISTINCT object_type FROM {$this->table_name} ORDER BY object_type";
        $results = $wpdb->get_col($query);
        
        return $results ? $results : [];
    }
    
    /**
     * Delete logs older than specified days
     *
     * @param int $days Number of days
     * @return int Number of deleted rows
     */
    public function cleanup_old_logs($days = 90) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE created_at < %s",
            $cutoff_date
        ));
    }
    
    /**
     * Get statistics
     *
     * @return array
     */
    public function get_statistics() {
        global $wpdb;
        
        // Total logs
        $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        
        // Logs by action
        $actions = $wpdb->get_results(
            "SELECT action, COUNT(*) as count 
             FROM {$this->table_name} 
             GROUP BY action 
             ORDER BY count DESC"
        );
        
        // Logs by user (top 10)
        $users = $wpdb->get_results(
            "SELECT al.user_id, u.display_name as user_name, COUNT(*) as count 
             FROM {$this->table_name} al
             LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID
             GROUP BY al.user_id 
             ORDER BY count DESC
             LIMIT 10"
        );
        
        // Recent activity (last 7 days)
        $recent_activity = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table_name} 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        return [
            'total_logs' => (int) $total_logs,
            'actions' => $actions ? $actions : [],
            'top_users' => $users ? $users : [],
            'recent_activity' => (int) $recent_activity
        ];
    }
    
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
    public function log($action, $object_type, $object_id, $details = [], $user_id = null) {
        global $wpdb;
        
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        $data = [
            'user_id' => $user_id,
            'action' => $action,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'details' => maybe_serialize($details),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'created_at' => current_time('mysql')
        ];
        
        $result = $wpdb->insert($this->table_name, $data, [
            '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s'
        ]);
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Get logs by user
     *
     * @param int $user_id User ID
     * @param int $limit Number of entries to retrieve
     * @return array Log entries
     */
    public function get_by_user($user_id, $limit = 100) {
        return $this->get_logs([
            'user_id' => $user_id,
            'per_page' => $limit,
            'page' => 1
        ])['items'];
    }
    
    /**
     * Get logs by object
     *
     * @param string $object_type Object type
     * @param int $object_id Object ID
     * @param int $limit Number of entries to retrieve
     * @return array Log entries
     */
    public function get_by_object($object_type, $object_id, $limit = 100) {
        return $this->get_logs([
            'object_type' => $object_type,
            'object_id' => $object_id,
            'per_page' => $limit,
            'page' => 1
        ])['items'];
    }
    
    /**
     * Get logs by action
     *
     * @param string $action Action name
     * @param int $limit Number of entries to retrieve
     * @return array Log entries
     */
    public function get_by_action($action, $limit = 100) {
        return $this->get_logs([
            'action' => $action,
            'per_page' => $limit,
            'page' => 1
        ])['items'];
    }
    
    /**
     * Clean old logs
     *
     * @param int $days Number of days to keep
     * @return int Number of logs deleted
     */
    public function clean_old_logs($days = 90) {
        return $this->cleanup_old_logs($days);
    }
    
    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                return trim($ip);
            }
        }
        
        return '';
    }
    
    // Required base repository methods (from MT_Repository_Interface)
    
    /**
     * Find all records
     *
     * @param array $args Query arguments
     * @return array
     */
    public function find_all($args = []) {
        return $this->get_logs($args)['items'];
    }
    
    /**
     * Find record by ID
     *
     * @param int $id Record ID
     * @return object|null
     */
    public function find($id) {
        return $this->get_by_id($id);
    }
    
    /**
     * Create a new record
     *
     * @param array $data Record data
     * @return int|false
     */
    public function create($data) {
        return $this->log(
            $data['action'] ?? '',
            $data['object_type'] ?? '',
            $data['object_id'] ?? 0,
            $data['details'] ?? [],
            $data['user_id'] ?? null
        );
    }
    
    /**
     * Update a record
     *
     * @param int $id Record ID
     * @param array $data Data to update
     * @return bool
     */
    public function update($id, $data) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id]
        ) !== false;
    }
    
    /**
     * Delete a record
     *
     * @param int $id Record ID
     * @return bool
     */
    public function delete($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            ['id' => $id],
            ['%d']
        ) !== false;
    }
    
    /**
     * Check if record exists
     *
     * @param int $id Record ID
     * @return bool
     */
    public function exists($id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE id = %d",
            $id
        ));
        
        return $count > 0;
    }
}
