<?php
/**
 * AJAX Handler for Debug Center
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

namespace MobilityTrailblazers\Ajax;

use MobilityTrailblazers\Services\MT_Diagnostic_Service;
use MobilityTrailblazers\Admin\MT_Debug_Manager;
use MobilityTrailblazers\Admin\MT_Maintenance_Tools;
use MobilityTrailblazers\Utilities\MT_Database_Health;
use MobilityTrailblazers\Utilities\MT_System_Info;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Debug_Ajax
 *
 * Handles AJAX requests for the Debug Center
 */
class MT_Debug_Ajax extends MT_Base_Ajax {
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the AJAX handler
     *
     * @return void
     */
    public function init() {
        // Register AJAX actions
        add_action('wp_ajax_mt_run_diagnostic', [$this, 'run_diagnostic']);
        add_action('wp_ajax_mt_execute_debug_script', [$this, 'execute_debug_script']);
        add_action('wp_ajax_mt_run_maintenance', [$this, 'run_maintenance']);
        add_action('wp_ajax_mt_export_diagnostics', [$this, 'export_diagnostics']);
        add_action('wp_ajax_mt_get_error_stats', [$this, 'get_error_stats']);
        add_action('wp_ajax_mt_clear_debug_logs', [$this, 'clear_debug_logs']);
        add_action('wp_ajax_mt_get_database_health', [$this, 'get_database_health']);
        add_action('wp_ajax_mt_get_system_info', [$this, 'get_system_info']);
        add_action('wp_ajax_mt_refresh_debug_widget', [$this, 'refresh_debug_widget']);
    }
    
    /**
     * Run diagnostic
     *
     * @return void
     */
    public function run_diagnostic() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $type = sanitize_text_field($_POST['diagnostic_type'] ?? 'full');
        $diagnostic_service = MT_Diagnostic_Service::get_instance();
        
        try {
            $results = $diagnostic_service->run_diagnostic($type);
            
            // Cache results for export
            set_transient('mt_last_diagnostic_' . get_current_user_id(), $results, HOUR_IN_SECONDS);
            
            $this->success([
                'diagnostics' => $results,
                'timestamp' => current_time('mysql'),
                'type' => $type
            ]);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Diagnostic failed', $e->getMessage());
            $this->error(sprintf(
                __('Diagnostic failed: %s', 'mobility-trailblazers'),
                $e->getMessage()
            ));
        }
    }
    
    /**
     * Execute debug script
     *
     * @return void
     */
    public function execute_debug_script() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $script = sanitize_text_field($_POST['script'] ?? '');
        $params = isset($_POST['params']) ? array_map('sanitize_text_field', $_POST['params']) : [];
        
        if (empty($script)) {
            $this->error(__('No script specified', 'mobility-trailblazers'));
        }
        
        $debug_manager = new MT_Debug_Manager();
        
        // Check if script is allowed
        if (!$debug_manager->is_script_allowed($script)) {
            $this->error(__('Script not allowed in current environment', 'mobility-trailblazers'));
        }
        
        try {
            $result = $debug_manager->execute_script($script, $params);
            
            if ($result['success']) {
                $this->success($result);
            } else {
                $this->error($result['message'], $result);
            }
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Script execution failed', [
                'script' => $script,
                'error' => $e->getMessage()
            ]);
            $this->error(sprintf(
                __('Script execution failed: %s', 'mobility-trailblazers'),
                $e->getMessage()
            ));
        }
    }
    
    /**
     * Run maintenance operation
     *
     * @return void
     */
    public function run_maintenance() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $category = sanitize_text_field($_POST['category'] ?? '');
        $operation = sanitize_text_field($_POST['operation'] ?? '');
        $params = isset($_POST['params']) ? array_map('sanitize_text_field', $_POST['params']) : [];
        
        if (empty($category) || empty($operation)) {
            $this->error(__('Invalid operation specified', 'mobility-trailblazers'));
        }
        
        $maintenance_tools = new MT_Maintenance_Tools();
        
        try {
            $result = $maintenance_tools->execute_operation($category, $operation, $params);
            
            if ($result['success']) {
                $this->success($result);
            } else {
                // Check for special requirements
                if (isset($result['requires_confirmation'])) {
                    $this->error($result['message'], [
                        'requires_confirmation' => true
                    ]);
                } elseif (isset($result['requires_password'])) {
                    $this->error($result['message'], [
                        'requires_password' => true
                    ]);
                } else {
                    $this->error($result['message']);
                }
            }
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Maintenance operation failed', [
                'category' => $category,
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
            $this->error(sprintf(
                __('Operation failed: %s', 'mobility-trailblazers'),
                $e->getMessage()
            ));
        }
    }
    
    /**
     * Export diagnostics
     *
     * @return void
     */
    public function export_diagnostics() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        // Get cached diagnostics or run new
        $diagnostics = get_transient('mt_last_diagnostic_' . get_current_user_id());
        
        if (!$diagnostics) {
            $diagnostic_service = MT_Diagnostic_Service::get_instance();
            $diagnostics = $diagnostic_service->run_full_diagnostic();
        }
        
        // Add export metadata
        $export_data = [
            'plugin' => 'Mobility Trailblazers',
            'version' => MT_VERSION,
            'export_date' => current_time('mysql'),
            'export_by' => wp_get_current_user()->user_login,
            'site_url' => get_site_url(),
            'diagnostics' => $diagnostics
        ];
        
        $this->success([
            'filename' => 'mt-diagnostics-' . date('Y-m-d-His') . '.json',
            'data' => wp_json_encode($export_data, JSON_PRETTY_PRINT),
            'mime_type' => 'application/json'
        ]);
    }
    
    /**
     * Get error statistics
     *
     * @return void
     */
    public function get_error_stats() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) !== $table_name) {
            $this->success([
                'total' => 0,
                'today' => 0,
                'this_week' => 0,
                'by_level' => [],
                'recent' => []
            ]);
            return;
        }
        
        // Get statistics
        $stats = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'today' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(timestamp) = CURDATE()"
            ),
            'this_week' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $table_name WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            ),
            'by_level' => $wpdb->get_results(
                "SELECT level, COUNT(*) as count FROM $table_name 
                 GROUP BY level ORDER BY count DESC",
                ARRAY_A
            ),
            'recent' => $wpdb->get_results(
                "SELECT * FROM $table_name 
                 ORDER BY timestamp DESC LIMIT 10",
                ARRAY_A
            )
        ];
        
        $this->success($stats);
    }
    
    /**
     * Clear debug logs
     *
     * @return void
     */
    public function clear_debug_logs() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $type = sanitize_text_field($_POST['log_type'] ?? 'all');
        $cleared = [];
        
        global $wpdb;
        
        try {
            if ($type === 'all' || $type === 'error') {
                // Clear error log table
                $table_name = $wpdb->prefix . 'mt_error_log';
                if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
                    $wpdb->query("TRUNCATE TABLE $table_name");
                    $cleared[] = 'error_log';
                }
            }
            
            if ($type === 'all' || $type === 'debug') {
                // Clear debug.log file
                $debug_log = WP_CONTENT_DIR . '/debug.log';
                if (file_exists($debug_log) && is_writable($debug_log)) {
                    file_put_contents($debug_log, '');
                    $cleared[] = 'debug_log';
                }
            }
            
            if ($type === 'all' || $type === 'audit') {
                // Clear audit log
                $debug_manager = new MT_Debug_Manager();
                $debug_manager->clear_audit_log();
                $cleared[] = 'audit_log';
            }
            
            $this->success([
                'cleared' => $cleared,
                'message' => sprintf(
                    __('Cleared %d log(s)', 'mobility-trailblazers'),
                    count($cleared)
                )
            ]);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Failed to clear logs', $e->getMessage());
            $this->error(__('Failed to clear logs', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Get database health information
     *
     * @return void
     */
    public function get_database_health() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $db_health = new MT_Database_Health();
        
        try {
            $health_data = [
                'tables' => $db_health->check_all_tables(),
                'connection' => $db_health->get_connection_info(),
                'statistics' => $db_health->get_database_stats(),
                'slow_queries' => $db_health->get_slow_queries(5)
            ];
            
            $this->success($health_data);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Database health check failed', $e->getMessage());
            $this->error(__('Failed to check database health', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Get system information
     *
     * @return void
     */
    public function get_system_info() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $system_info = new MT_System_Info();
        
        try {
            $info = $system_info->get_system_info();
            
            // Cache for export
            set_transient('mt_last_sysinfo_' . get_current_user_id(), $info, HOUR_IN_SECONDS);
            
            $this->success($info);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('System info retrieval failed', $e->getMessage());
            $this->error(__('Failed to get system information', 'mobility-trailblazers'));
        }
    }
    
    /**
     * Refresh debug widget
     *
     * @return void
     */
    public function refresh_debug_widget() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        $widget_id = sanitize_text_field($_POST['widget_id'] ?? '');
        
        if (empty($widget_id)) {
            $this->error(__('No widget specified', 'mobility-trailblazers'));
        }
        
        try {
            $html = '';
            
            switch ($widget_id) {
                case 'system_status':
                    $diagnostic_service = MT_Diagnostic_Service::get_instance();
                    $status = $diagnostic_service->run_diagnostic('environment');
                    
                    ob_start();
                    ?>
                    <div class="mt-widget-content">
                        <p><strong><?php _e('PHP Version:', 'mobility-trailblazers'); ?></strong> 
                           <?php echo esc_html($status['php_version']); ?></p>
                        <p><strong><?php _e('Memory Usage:', 'mobility-trailblazers'); ?></strong>
                           <?php echo size_format(memory_get_usage(true)); ?> / 
                           <?php echo ini_get('memory_limit'); ?></p>
                        <p><strong><?php _e('Environment:', 'mobility-trailblazers'); ?></strong>
                           <?php echo esc_html($status['environment_type']); ?></p>
                    </div>
                    <?php
                    $html = ob_get_clean();
                    break;
                    
                case 'database_status':
                    $db_health = new MT_Database_Health();
                    $stats = $db_health->get_database_stats();
                    
                    ob_start();
                    ?>
                    <div class="mt-widget-content">
                        <p><strong><?php _e('Tables:', 'mobility-trailblazers'); ?></strong> 
                           <?php echo intval($stats['table_count']); ?></p>
                        <p><strong><?php _e('Total Rows:', 'mobility-trailblazers'); ?></strong>
                           <?php echo number_format($stats['row_count']); ?></p>
                        <p><strong><?php _e('Size:', 'mobility-trailblazers'); ?></strong>
                           <?php echo size_format($stats['total_size']); ?></p>
                    </div>
                    <?php
                    $html = ob_get_clean();
                    break;
                    
                default:
                    $this->error(__('Unknown widget', 'mobility-trailblazers'));
                    return;
            }
            
            $this->success([
                'widget_id' => $widget_id,
                'html' => $html
            ]);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Widget refresh failed', [
                'widget' => $widget_id,
                'error' => $e->getMessage()
            ]);
            $this->error(__('Failed to refresh widget', 'mobility-trailblazers'));
        }
    }
}