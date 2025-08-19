<?php
/**
 * AJAX Handler for Debug Center - Fixed Version
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 * @updated 2.5.13
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
 * Handles AJAX requests for the Debug Center with enhanced security
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
        // Register AJAX actions - removed dangerous operations
        add_action('wp_ajax_mt_run_diagnostic', [$this, 'run_diagnostic']);
        add_action('wp_ajax_mt_execute_debug_script', [$this, 'execute_debug_script']);
        add_action('wp_ajax_mt_run_maintenance', [$this, 'run_maintenance']);
        add_action('wp_ajax_mt_export_diagnostics', [$this, 'export_diagnostics']);
        add_action('wp_ajax_mt_get_error_stats', [$this, 'get_error_stats']);
        add_action('wp_ajax_mt_clear_debug_logs', [$this, 'clear_debug_logs']);
        add_action('wp_ajax_mt_get_database_health', [$this, 'get_database_health']);
        add_action('wp_ajax_mt_get_system_info', [$this, 'get_system_info']);
        add_action('wp_ajax_mt_refresh_debug_widget', [$this, 'refresh_debug_widget']);
        // REMOVED: mt_delete_all_candidates - dangerous operation removed 2025-01-20
    }
    
    /**
     * Run diagnostic
     *
     * @return void
     */
    public function run_diagnostic() {
        // Enhanced security checks
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
            return;
        }
        
        $type = sanitize_text_field($_POST['diagnostic_type'] ?? 'full');
        
        // Validate diagnostic type
        $allowed_types = ['full', 'basic', 'database', 'system', 'plugin'];
        if (!in_array($type, $allowed_types, true)) {
            $type = 'full';
        }
        
        $diagnostic_service = MT_Diagnostic_Service::get_instance();
        
        try {
            $results = $diagnostic_service->run_diagnostic($type);
            
            // Cache results for export with shorter timeout
            set_transient('mt_last_diagnostic_' . get_current_user_id(), $results, 30 * MINUTE_IN_SECONDS);
            
            $this->success([
                'diagnostics' => $results,
                'timestamp' => current_time('mysql'),
                'type' => $type
            ]);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Diagnostic failed', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
            $this->error(sprintf(
                __('Diagnostic failed: %s', 'mobility-trailblazers'),
                esc_html($e->getMessage())
            ));
        }
    }
    
    /**
     * Execute debug script - with enhanced security
     *
     * @return void
     */
    public function execute_debug_script() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
            return;
        }
        
        // Only allow in development environment unless explicitly enabled
        if (!defined('MT_DEV_TOOLS') || !MT_DEV_TOOLS) {
            $environment = defined('WP_ENVIRONMENT_TYPE') ? WP_ENVIRONMENT_TYPE : 'production';
            if ($environment === 'production') {
                $this->error(__('Debug scripts are disabled in production', 'mobility-trailblazers'));
                return;
            }
        }
        
        $script = sanitize_file_name($_POST['script'] ?? '');
        $params = isset($_POST['params']) ? array_map('sanitize_text_field', (array)$_POST['params']) : [];
        
        if (empty($script)) {
            $this->error(__('No script specified', 'mobility-trailblazers'));
            return;
        }
        
        // Validate script name format
        if (!preg_match('/^[a-z0-9\-_]+\.php$/i', $script)) {
            $this->error(__('Invalid script name format', 'mobility-trailblazers'));
            return;
        }
        
        $debug_manager = new MT_Debug_Manager();
        
        // Check if script is allowed
        if (!$debug_manager->is_script_allowed($script)) {
            $this->error(__('Script not allowed in current environment', 'mobility-trailblazers'));
            return;
        }
        
        try {
            $result = $debug_manager->execute_script($script, $params);
            
            if ($result['success']) {
                $this->success($result, esc_html($result['message'] ?? __('Script executed successfully', 'mobility-trailblazers')));
            } else {
                $this->error(esc_html($result['message']), $result);
            }
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Script execution failed', [
                'script' => $script,
                'error' => $e->getMessage()
            ]);
            $this->error(sprintf(
                __('Script execution failed: %s', 'mobility-trailblazers'),
                esc_html($e->getMessage())
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
            return;
        }
        
        $category = sanitize_key($_POST['category'] ?? '');
        $operation = sanitize_key($_POST['operation'] ?? '');
        $params = isset($_POST['params']) ? array_map('sanitize_text_field', (array)$_POST['params']) : [];
        
        if (empty($category) || empty($operation)) {
            $this->error(__('Invalid operation specified', 'mobility-trailblazers'));
            return;
        }
        
        // Validate category and operation
        $allowed_categories = ['cache', 'database', 'transients', 'logs', 'optimization'];
        if (!in_array($category, $allowed_categories, true)) {
            $this->error(__('Invalid category specified', 'mobility-trailblazers'));
            return;
        }
        
        $maintenance_tools = new MT_Maintenance_Tools();
        
        try {
            $result = $maintenance_tools->execute_operation($category, $operation, $params);
            
            if ($result['success']) {
                $this->success($result, esc_html($result['message'] ?? __('Operation completed successfully', 'mobility-trailblazers')));
            } else {
                // Check for special requirements
                if (isset($result['requires_confirmation'])) {
                    $this->error(esc_html($result['message']), [
                        'requires_confirmation' => true
                    ]);
                } else {
                    $this->error(esc_html($result['message']));
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
                esc_html($e->getMessage())
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
            return;
        }
        
        // Get cached diagnostics or run new
        $diagnostics = get_transient('mt_last_diagnostic_' . get_current_user_id());
        
        if (!$diagnostics) {
            $diagnostic_service = MT_Diagnostic_Service::get_instance();
            $diagnostics = $diagnostic_service->run_full_diagnostic();
        }
        
        // Sanitize export data
        $export_data = [
            'plugin' => 'Mobility Trailblazers',
            'version' => esc_html(MT_VERSION),
            'export_date' => current_time('mysql'),
            'export_by' => esc_html(wp_get_current_user()->user_login),
            'site_url' => esc_url(get_site_url()),
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
            return;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_error_log';
        
        // Check if table exists
        $table_exists = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $table_name)
        );
        
        if ($table_exists !== $table_name) {
            $this->success([
                'total' => 0,
                'today' => 0,
                'this_week' => 0,
                'by_level' => [],
                'recent' => []
            ]);
            return;
        }
        
        // Get statistics with proper prepared statements
        $stats = [
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
            'today' => (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE DATE(timestamp) = %s",
                    current_time('Y-m-d')
                )
            ),
            'this_week' => (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table_name} WHERE timestamp >= %s",
                    date('Y-m-d H:i:s', strtotime('-7 days'))
                )
            ),
            'by_level' => $wpdb->get_results(
                "SELECT level, COUNT(*) as count FROM {$table_name} 
                 GROUP BY level ORDER BY count DESC",
                ARRAY_A
            ),
            'recent' => $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$table_name} 
                     ORDER BY timestamp DESC LIMIT %d",
                    10
                ),
                ARRAY_A
            )
        ];
        
        // Sanitize output
        foreach ($stats['recent'] as &$error) {
            $error['message'] = esc_html($error['message'] ?? '');
            $error['file'] = esc_html($error['file'] ?? '');
            $error['level'] = esc_html($error['level'] ?? '');
        }
        
        $this->success($stats);
    }
    
    /**
     * Clear debug logs - with restrictions
     *
     * @return void
     */
    public function clear_debug_logs() {
        $this->verify_nonce('mt_debug_nonce');
        
        if (!current_user_can('manage_options')) {
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
            return;
        }
        
        $type = sanitize_key($_POST['log_type'] ?? 'audit');
        $cleared = [];
        
        // Only allow clearing certain log types
        $allowed_types = ['audit', 'debug'];
        if (!in_array($type, $allowed_types, true)) {
            $this->error(__('Invalid log type specified', 'mobility-trailblazers'));
            return;
        }
        
        global $wpdb;
        
        try {
            if ($type === 'debug') {
                // Only clear debug.log if in development
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    $debug_log = WP_CONTENT_DIR . '/debug.log';
                    if (file_exists($debug_log) && is_writable($debug_log)) {
                        // Archive old log instead of deleting
                        $archive_name = WP_CONTENT_DIR . '/debug-' . date('Y-m-d-His') . '.log';
                        @copy($debug_log, $archive_name);
                        file_put_contents($debug_log, '');
                        $cleared[] = 'debug_log';
                    }
                }
            }
            
            if ($type === 'audit') {
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
            \MobilityTrailblazers\Core\MT_Logger::error('Failed to clear logs', [
                'error' => $e->getMessage(),
                'type' => $type
            ]);
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
            return;
        }
        
        try {
            $db_health = new MT_Database_Health();
            $health_data = $db_health->get_health_report();
            
            // Sanitize output
            array_walk_recursive($health_data, function(&$value) {
                if (is_string($value)) {
                    $value = esc_html($value);
                }
            });
            
            $this->success($health_data);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Database health check failed', [
                'error' => $e->getMessage()
            ]);
            $this->error(__('Failed to get database health', 'mobility-trailblazers'));
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
            return;
        }
        
        try {
            $system_info = new MT_System_Info();
            $info = $system_info->get_info();
            
            // Remove sensitive information
            unset($info['database']['password']);
            unset($info['server']['document_root']);
            unset($info['server']['server_admin']);
            
            // Sanitize output
            array_walk_recursive($info, function(&$value) {
                if (is_string($value)) {
                    $value = esc_html($value);
                }
            });
            
            $this->success($info);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('System info retrieval failed', [
                'error' => $e->getMessage()
            ]);
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
            return;
        }
        
        $widget_id = sanitize_key($_POST['widget_id'] ?? '');
        
        if (empty($widget_id)) {
            $this->error(__('No widget specified', 'mobility-trailblazers'));
            return;
        }
        
        // Allowed widgets
        $allowed_widgets = ['system_status', 'database_health', 'error_summary', 'recent_activity'];
        if (!in_array($widget_id, $allowed_widgets, true)) {
            $this->error(__('Invalid widget specified', 'mobility-trailblazers'));
            return;
        }
        
        try {
            $widget_data = [];
            
            switch ($widget_id) {
                case 'system_status':
                    $diagnostic_service = MT_Diagnostic_Service::get_instance();
                    $widget_data = $diagnostic_service->get_system_status();
                    break;
                    
                case 'database_health':
                    $db_health = new MT_Database_Health();
                    $widget_data = $db_health->get_summary();
                    break;
                    
                case 'error_summary':
                    // Limited error summary
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'mt_error_log';
                    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
                        $widget_data = [
                            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
                            'today' => (int) $wpdb->get_var(
                                $wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$table_name} WHERE DATE(timestamp) = %s",
                                    current_time('Y-m-d')
                                )
                            )
                        ];
                    }
                    break;
                    
                case 'recent_activity':
                    $debug_manager = new MT_Debug_Manager();
                    $widget_data = $debug_manager->get_audit_log(5);
                    break;
            }
            
            // Sanitize widget data
            array_walk_recursive($widget_data, function(&$value) {
                if (is_string($value)) {
                    $value = esc_html($value);
                }
            });
            
            $this->success([
                'widget_id' => $widget_id,
                'data' => $widget_data,
                'timestamp' => current_time('mysql')
            ]);
            
        } catch (\Exception $e) {
            \MobilityTrailblazers\Core\MT_Logger::error('Widget refresh failed', [
                'widget' => $widget_id,
                'error' => $e->getMessage()
            ]);
            $this->error(__('Failed to refresh widget', 'mobility-trailblazers'));
        }
    }
    
    // REMOVED: delete_all_candidates method - dangerous operation removed 2025-01-20
    // This functionality has been permanently removed for security reasons
}
