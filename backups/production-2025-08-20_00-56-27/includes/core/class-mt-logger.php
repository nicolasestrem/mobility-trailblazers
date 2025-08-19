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

// Ensure config class is available
if (!class_exists('MobilityTrailblazers\Core\MT_Config')) {
    require_once MT_PLUGIN_DIR . 'includes/core/class-mt-config.php';
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
        if (MT_Config::should_log(self::LEVEL_DEBUG)) {
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
        if (MT_Config::should_log(self::LEVEL_INFO)) {
            self::log(self::LEVEL_INFO, $message, $context);
        }
    }
    
    /**
     * Log a warning message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function warning($message, $context = []) {
        if (MT_Config::should_log(self::LEVEL_WARNING)) {
            self::log(self::LEVEL_WARNING, $message, $context);
        }
    }
    
    /**
     * Log an error message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function error($message, $context = []) {
        if (MT_Config::should_log(self::LEVEL_ERROR)) {
            self::log(self::LEVEL_ERROR, $message, $context);
        }
    }
    
    /**
     * Log a critical error message
     *
     * @param string $message Log message
     * @param array $context Additional context data
     * @return void
     */
    public static function critical($message, $context = []) {
        if (MT_Config::should_log(self::LEVEL_CRITICAL)) {
            self::log(self::LEVEL_CRITICAL, $message, $context);
        }
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
        // Check if logging is enabled for this environment
        if (!MT_Config::get('log_to_file', false)) {
            return;
        }
        
        // Format the log message
        $formatted_message = self::format_message($level, $message, $context);
        
        // Write to WordPress error log
        error_log($formatted_message);
        
        // Error monitoring removed - no longer storing in database
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
    
    // Error monitoring removed - store_critical_error method no longer needed
    
    // Error monitoring removed - maybe_create_error_table method no longer needed
    
    /**
     * Get recent error logs for admin display
     * @deprecated Error monitoring removed in v2.5.7
     * @return array Empty array
     */
    public static function get_recent_errors($limit = 50, $level = null) {
        return [];
    }
    
    /**
     * Clear old error logs
     * @deprecated Error monitoring removed in v2.5.7
     * @return int Always returns 0
     */
    public static function cleanup_old_logs($days_to_keep = 30) {
        return 0;
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

