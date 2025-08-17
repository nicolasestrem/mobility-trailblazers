<?php
/**
 * Centralized Logging System for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 * @since 2.0.11
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Logger
 *
 * Provides centralized logging functionality with different log levels
 */
class MT_Logger {
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    /**
     * Plugin prefix for log messages
     */
    const LOG_PREFIX = 'MT';
    
    /**
     * Log a debug message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function debug($message, $context = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log(self::LEVEL_DEBUG, $message, $context);
        }
    }
    
    /**
     * Log an info message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function info($message, $context = []) {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log a warning message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function warning($message, $context = []) {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log an error message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function error($message, $context = []) {
        self::log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log a critical error message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function critical($message, $context = []) {
        self::log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Log a message with specified level
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    private static function log($level, $message, $context = []) {
        // Only log if WordPress debug logging is enabled
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }
        
        // Format the log message
        $formatted_message = self::format_message($level, $message, $context);
        
        // Write to WordPress error log
        error_log($formatted_message);
        
        // Store in plugin-specific log if critical
        if ($level === self::LEVEL_CRITICAL) {
            self::store_critical_error($message, $context);
        }
    }
    
    /**
     * Format log message
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return string Formatted message
     */
    private static function format_message($level, $message, $context = []) {
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        
        $formatted = sprintf(
            '[%s] %s [%s] User:%d URI:%s - %s',
            $timestamp,
            self::LOG_PREFIX,
            $level,
            $user_id,
            $request_uri,
            $message
        );
        
        // Add context if provided
        if (!empty($context)) {
            $formatted .= ' Context: ' . wp_json_encode($context);
        }
        
        return $formatted;
    }
    
    /**
     * Store critical errors in database for admin review
     *
     * @param string $message Error message
     * @param array $context Error context
     * @return void
     */
    private static function store_critical_error($message, $context = []) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Create table if it doesn't exist
        self::maybe_create_error_table();
        
        $wpdb->insert(
            $table_name,
            [
                'level' => self::LEVEL_CRITICAL,
                'message' => $message,
                'context' => wp_json_encode($context),
                'user_id' => get_current_user_id(),
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'created_at' => current_time('mysql')
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );
    }
    
    /**
     * Create error log table if it doesn't exist
     *
     * @return void
     */
    private static function maybe_create_error_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level varchar(20) NOT NULL,
            message text NOT NULL,
            context longtext,
            user_id bigint(20) unsigned DEFAULT 0,
            request_uri varchar(500) DEFAULT '',
            user_agent varchar(500) DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY level (level),
            KEY created_at (created_at),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get recent error logs for admin display
     *
     * @param int $limit Number of errors to retrieve
     * @param string $level Filter by log level
     * @return array Error logs
     */
    public static function get_recent_errors($limit = 50, $level = null) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [];
        }
        
        $where_clause = '';
        $params = [];
        
        if ($level) {
            $where_clause = 'WHERE level = %s';
            $params[] = $level;
        }
        
        $params[] = $limit;
        
        $sql = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Clear old error logs
     *
     * @param int $days_to_keep Number of days to keep logs
     * @return int Number of deleted records
     */
    public static function cleanup_old_logs($days_to_keep = 30) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days_to_keep days"));
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE created_at < %s",
                $cutoff_date
            )
        );
    }
    
    /**
     * Log AJAX errors with additional context
     *
     * @param string $action AJAX action name
     * @param string $message Error message
     * @param array $context Additional context
     * @return void
     */
    public static function ajax_error($action, $message, $context = []) {
        $context['ajax_action'] = $action;
        $context['request_method'] = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
        
        self::error("AJAX Error in action '$action': $message", $context);
    }
    
    /**
     * Log database errors
     *
     * @param string $operation Database operation (INSERT, UPDATE, DELETE, etc.)
     * @param string $table Table name
     * @param string $error Error message
     * @param array $context Additional context
     * @return void
     */
    public static function database_error($operation, $table, $error, $context = []) {
        $context['db_operation'] = $operation;
        $context['db_table'] = $table;
        
        self::error("Database Error in $operation on $table: $error", $context);
    }
    
    /**
     * Log security-related events
     *
     * @param string $event Security event description
     * @param array $context Additional context
     * @return void
     */
    public static function security_event($event, $context = []) {
        $context['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $context['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        
        self::warning("Security Event: $event", $context);
    }
}
