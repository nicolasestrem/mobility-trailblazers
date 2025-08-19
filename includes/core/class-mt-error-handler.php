<?php
/**
 * Centralized Error Handler
 *
 * @package MobilityTrailblazers
 * @since 2.5.33
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Error_Handler
 *
 * Provides centralized error handling and logging
 */
class MT_Error_Handler {
    
    /**
     * Error levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Log an error with context
     *
     * @param string $message Error message
     * @param array $context Additional context
     * @param string $level Error level
     * @return void
     */
    public static function log($message, $context = [], $level = self::LEVEL_ERROR) {
        // Only log if debug mode is enabled
        if (!defined('MT_DEBUG') || !MT_DEBUG) {
            if ($level !== self::LEVEL_CRITICAL && $level !== self::LEVEL_ERROR) {
                return;
            }
        }
        
        // Build log entry
        $log_entry = [
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'url' => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI',
            'backtrace' => self::get_backtrace()
        ];
        
        // Log to error log
        error_log(sprintf(
            '[MT %s] %s | Context: %s',
            strtoupper($level),
            $message,
            json_encode($context)
        ));
        
        // Store in transient for admin display (last 50 errors)
        self::store_for_admin($log_entry);
        
        // For critical errors, send admin notification
        if ($level === self::LEVEL_CRITICAL) {
            self::notify_admin($message, $context);
        }
    }
    
    /**
     * Log a debug message
     *
     * @param string $message Debug message
     * @param array $context Additional context
     * @return void
     */
    public static function debug($message, $context = []) {
        self::log($message, $context, self::LEVEL_DEBUG);
    }
    
    /**
     * Log an info message
     *
     * @param string $message Info message
     * @param array $context Additional context
     * @return void
     */
    public static function info($message, $context = []) {
        self::log($message, $context, self::LEVEL_INFO);
    }
    
    /**
     * Log a warning
     *
     * @param string $message Warning message
     * @param array $context Additional context
     * @return void
     */
    public static function warning($message, $context = []) {
        self::log($message, $context, self::LEVEL_WARNING);
    }
    
    /**
     * Log an error
     *
     * @param string $message Error message
     * @param array $context Additional context
     * @return void
     */
    public static function error($message, $context = []) {
        self::log($message, $context, self::LEVEL_ERROR);
    }
    
    /**
     * Log a critical error
     *
     * @param string $message Critical error message
     * @param array $context Additional context
     * @return void
     */
    public static function critical($message, $context = []) {
        self::log($message, $context, self::LEVEL_CRITICAL);
    }
    
    /**
     * Handle exceptions
     *
     * @param \Exception $exception The exception to handle
     * @param string $context Context where exception occurred
     * @return void
     */
    public static function handle_exception($exception, $context = '') {
        self::critical('Exception occurred', [
            'context' => $context,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
    
    /**
     * Store error for admin display
     *
     * @param array $log_entry Log entry data
     * @return void
     */
    private static function store_for_admin($log_entry) {
        $errors = get_transient('mt_error_log') ?: [];
        
        // Add new entry
        array_unshift($errors, $log_entry);
        
        // Keep only last 50 entries
        $errors = array_slice($errors, 0, 50);
        
        // Store for 24 hours
        set_transient('mt_error_log', $errors, DAY_IN_SECONDS);
    }
    
    /**
     * Get simplified backtrace
     *
     * @return array Simplified backtrace
     */
    private static function get_backtrace() {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $simplified = [];
        
        // Skip first two entries (this method and log method)
        for ($i = 2; $i < count($trace) && $i < 5; $i++) {
            if (isset($trace[$i]['file'])) {
                $simplified[] = [
                    'file' => str_replace(ABSPATH, '', $trace[$i]['file']),
                    'line' => $trace[$i]['line'] ?? 0,
                    'function' => $trace[$i]['function'] ?? 'unknown'
                ];
            }
        }
        
        return $simplified;
    }
    
    /**
     * Notify admin of critical errors
     *
     * @param string $message Error message
     * @param array $context Error context
     * @return void
     */
    private static function notify_admin($message, $context) {
        // Store critical error notification
        $notifications = get_option('mt_critical_errors', []);
        
        $notifications[] = [
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'context' => $context
        ];
        
        // Keep only last 10 critical errors
        $notifications = array_slice($notifications, -10);
        
        update_option('mt_critical_errors', $notifications);
        
        // Set admin notice
        set_transient('mt_admin_critical_error', true, HOUR_IN_SECONDS);
    }
    
    /**
     * Get recent errors for display
     *
     * @param int $limit Number of errors to retrieve
     * @param string $level Minimum error level to retrieve
     * @return array Recent errors
     */
    public static function get_recent_errors($limit = 10, $level = self::LEVEL_WARNING) {
        $all_errors = get_transient('mt_error_log') ?: [];
        
        // Filter by level if specified
        if ($level) {
            $level_priority = self::get_level_priority($level);
            $all_errors = array_filter($all_errors, function($error) use ($level_priority) {
                return self::get_level_priority($error['level']) >= $level_priority;
            });
        }
        
        return array_slice($all_errors, 0, $limit);
    }
    
    /**
     * Get error level priority
     *
     * @param string $level Error level
     * @return int Priority (higher = more severe)
     */
    private static function get_level_priority($level) {
        $priorities = [
            self::LEVEL_DEBUG => 1,
            self::LEVEL_INFO => 2,
            self::LEVEL_WARNING => 3,
            self::LEVEL_ERROR => 4,
            self::LEVEL_CRITICAL => 5
        ];
        
        return $priorities[$level] ?? 0;
    }
    
    /**
     * Clear error log
     *
     * @return void
     */
    public static function clear_log() {
        delete_transient('mt_error_log');
        delete_option('mt_critical_errors');
        delete_transient('mt_admin_critical_error');
    }
    
    /**
     * Export error log
     *
     * @return string JSON encoded error log
     */
    public static function export_log() {
        $errors = get_transient('mt_error_log') ?: [];
        
        return json_encode($errors, JSON_PRETTY_PRINT);
    }
}
