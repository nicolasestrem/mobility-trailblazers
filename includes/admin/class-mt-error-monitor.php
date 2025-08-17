<?php
/**
 * Error Monitoring and Reporting
 *
 * @package MobilityTrailblazers
 * @since 2.0.11
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Error_Monitor
 *
 * Handles error monitoring and reporting for administrators
 */
class MT_Error_Monitor {
    
    /**
     * Initialize error monitoring
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_mt_clear_error_logs', [$this, 'clear_error_logs']);
        add_action('wp_ajax_mt_export_error_logs', [$this, 'export_error_logs']);
        add_action('wp_ajax_mt_get_error_stats', [$this, 'get_error_stats']);
        
        // Schedule cleanup of old logs
        if (!wp_next_scheduled('mt_cleanup_error_logs')) {
            wp_schedule_event(time(), 'daily', 'mt_cleanup_error_logs');
        }
        add_action('mt_cleanup_error_logs', [$this, 'cleanup_old_logs']);
    }
    
    /**
     * Add admin menu page
     *
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'mobility-trailblazers',
            __('Error Monitor', 'mobility-trailblazers'),
            __('Error Monitor', 'mobility-trailblazers'),
            'manage_options',
            'mt-error-monitor',
            [$this, 'render_error_monitor_page']
        );
    }
    
    /**
     * Render error monitor page
     *
     * @return void
     */
    public function render_error_monitor_page() {
        // Get error statistics
        $stats = $this->get_error_statistics();
        
        // Get recent errors
        $recent_errors = MT_Logger::get_recent_errors(50);
        
        // Get error counts by level
        $error_counts = $this->get_error_counts_by_level();
        
        $template_file = MT_PLUGIN_DIR . 'templates/admin/error-monitor.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Error monitor template not found.', 'mobility-trailblazers') . '</p></div>';
        }
    }
    
    /**
     * Get error statistics
     *
     * @return array
     */
    public function get_error_statistics() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            return [
                'total_errors' => 0,
                'errors_today' => 0,
                'unique_errors' => 0,
                'most_common_type' => 'None'
            ];
        }
        
        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));
        
        $stats = [
            'total_errors' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'errors_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
                $today
            )),
            'errors_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
                $week_ago
            )),
            'critical_errors' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE level = %s",
                'CRITICAL'
            ))
        ];
        
        return $stats;
    }
    
    /**
     * Get error counts by level
     *
     * @return array
     */
    private function get_error_counts_by_level() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            return [];
        }
        
        $results = $wpdb->get_results(
            "SELECT level, COUNT(*) as count FROM $table_name GROUP BY level ORDER BY count DESC"
        );
        
        $counts = [];
        foreach ($results as $result) {
            $counts[$result->level] = $result->count;
        }
        
        return $counts;
    }
    
    /**
     * Clear error logs AJAX handler
     *
     * @return void
     */
    public function clear_error_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mt_admin_nonce')) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'mobility-trailblazers'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        $deleted = $wpdb->query("DELETE FROM $table_name");
        
        if ($deleted !== false) {
            MT_Logger::info('Error logs cleared by administrator', [
                'deleted_count' => $deleted,
                'admin_user_id' => get_current_user_id()
            ]);
            
            wp_send_json_success([
                'message' => sprintf(__('Cleared %d error logs.', 'mobility-trailblazers'), $deleted),
                'deleted_count' => $deleted
            ]);
        } else {
            wp_send_json_error(__('Failed to clear error logs.', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Export error logs AJAX handler
     *
     * @return void
     */
    public function export_error_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mt_admin_nonce')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'mobility-trailblazers'));
        }
        
        $errors = MT_Logger::get_recent_errors(1000); // Get up to 1000 recent errors
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mt-error-logs-' . date('Y-m-d-H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create CSV output
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID',
            'Level',
            'Message',
            'Context',
            'User ID',
            'Request URI',
            'User Agent',
            'Created At'
        ]);
        
        // CSV data
        foreach ($errors as $error) {
            fputcsv($output, [
                $error->id,
                $error->level,
                $error->message,
                $error->context,
                $error->user_id,
                $error->request_uri,
                $error->user_agent,
                $error->created_at
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Get error statistics AJAX handler
     *
     * @return void
     */
    public function get_error_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mt_admin_nonce')) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'mobility-trailblazers'));
            return;
        }
        
        $stats = $this->get_error_statistics();
        $counts = $this->get_error_counts_by_level();
        
        wp_send_json_success([
            'stats' => $stats,
            'counts_by_level' => $counts
        ]);
    }
    
    /**
     * Cleanup old error logs
     *
     * @return void
     */
    public function cleanup_old_logs() {
        $deleted = MT_Logger::cleanup_old_logs(30); // Keep logs for 30 days
        
        if ($deleted > 0) {
            MT_Logger::info('Automatic error log cleanup completed', [
                'deleted_count' => $deleted
            ]);
        }
    }
    
    /**
     * Get error summary for dashboard widget
     *
     * @return array
     */
    public static function get_dashboard_summary() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            return [
                'recent_errors' => 0,
                'critical_errors' => 0,
                'status' => 'unknown'
            ];
        }
        
        $today = date('Y-m-d');
        
        $recent_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
            $today
        ));
        
        $critical_errors = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE level = %s AND DATE(created_at) = %s",
            'CRITICAL',
            $today
        ));
        
        // Determine status
        $status = 'good';
        if ($critical_errors > 0) {
            $status = 'critical';
        } elseif ($recent_errors > 10) {
            $status = 'warning';
        }
        
        return [
            'recent_errors' => $recent_errors,
            'critical_errors' => $critical_errors,
            'status' => $status
        ];
    }
    
    /**
     * Get recent errors
     *
     * @param int $limit Number of errors to retrieve
     * @return array
     */
    public function get_recent_errors($limit = 50) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            return [];
        }
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
            $limit
        ), ARRAY_A);
        
        return $results ? $results : [];
    }
    
    /**
     * Get error types
     *
     * @return array
     */
    public function get_error_types() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            return [];
        }
        
        $results = $wpdb->get_results(
            "SELECT level as type, COUNT(*) as count FROM $table_name GROUP BY level",
            OBJECT_K
        );
        
        $types = [];
        if ($results) {
            foreach ($results as $type => $data) {
                $types[$type] = $data->count;
            }
        }
        
        return $types;
    }
}
