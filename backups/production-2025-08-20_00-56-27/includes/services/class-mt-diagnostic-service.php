<?php
/**
 * Diagnostic Service for system health checks
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

namespace MobilityTrailblazers\Services;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Diagnostic_Service
 *
 * Provides comprehensive system diagnostics and health checks
 */
class MT_Diagnostic_Service {
    
    /**
     * Singleton instance
     *
     * @var MT_Diagnostic_Service
     */
    private static $instance = null;
    
    /**
     * Diagnostics data
     *
     * @var array
     */
    private $diagnostics = [];
    
    /**
     * Start time for performance tracking
     *
     * @var float
     */
    private $start_time;
    
    /**
     * Get singleton instance
     *
     * @return MT_Diagnostic_Service
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor
     */
    private function __construct() {
        // Prevent direct instantiation
    }
    
    /**
     * Run complete system diagnostic
     *
     * @return array Diagnostic results
     */
    public function run_full_diagnostic() {
        $this->start_time = microtime(true);
        
        $this->diagnostics = [
            'timestamp' => current_time('mysql'),
            'environment' => $this->get_environment_info(),
            'wordpress' => $this->check_wordpress_health(),
            'database' => $this->check_database_health(),
            'plugin' => $this->check_plugin_components(),
            'filesystem' => $this->check_filesystem_health(),
            'performance' => $this->check_performance_metrics(),
            'security' => $this->check_security_status(),
            'errors' => $this->check_error_logs()
        ];
        
        $this->diagnostics['execution_time'] = microtime(true) - $this->start_time;
        $this->diagnostics['overall_status'] = $this->calculate_overall_status();
        
        return $this->diagnostics;
    }
    
    /**
     * Run specific diagnostic
     *
     * @param string $type Type of diagnostic to run
     * @return array Diagnostic results
     */
    public function run_diagnostic($type) {
        $this->start_time = microtime(true);
        
        $results = [];
        
        switch ($type) {
            case 'environment':
                $results = $this->get_environment_info();
                break;
            case 'wordpress':
                $results = $this->check_wordpress_health();
                break;
            case 'database':
                $results = $this->check_database_health();
                break;
            case 'plugin':
                $results = $this->check_plugin_components();
                break;
            case 'filesystem':
                $results = $this->check_filesystem_health();
                break;
            case 'performance':
                $results = $this->check_performance_metrics();
                break;
            case 'security':
                $results = $this->check_security_status();
                break;
            case 'errors':
                $results = $this->check_error_logs();
                break;
            default:
                $results = $this->run_full_diagnostic();
        }
        
        $results['execution_time'] = microtime(true) - $this->start_time;
        
        return $results;
    }
    
    /**
     * Get environment information
     *
     * @return array Environment info
     */
    private function get_environment_info() {
        return [
            'environment_type' => $this->detect_environment(),
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'extensions' => [
                'curl' => extension_loaded('curl'),
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring'),
                'mysqli' => extension_loaded('mysqli'),
                'openssl' => extension_loaded('openssl'),
                'zip' => extension_loaded('zip')
            ]
        ];
    }
    
    /**
     * Check WordPress health
     *
     * @return array WordPress health status
     */
    private function check_wordpress_health() {
        global $wp_version;
        
        return [
            'version' => $wp_version,
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'multisite' => is_multisite(),
            'debug_mode' => WP_DEBUG,
            'debug_log' => WP_DEBUG_LOG,
            'debug_display' => WP_DEBUG_DISPLAY,
            'script_debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            'memory_limit' => WP_MEMORY_LIMIT,
            'max_memory_limit' => WP_MAX_MEMORY_LIMIT,
            'timezone' => get_option('timezone_string'),
            'language' => get_locale(),
            'active_theme' => get_option('stylesheet'),
            'active_plugins' => count(get_option('active_plugins', [])),
            'cron_status' => !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON
        ];
    }
    
    /**
     * Check database health
     *
     * @return array Database health status
     */
    private function check_database_health() {
        global $wpdb;
        
        $results = [
            'version' => $wpdb->db_version(),
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'prefix' => $wpdb->prefix,
            'tables' => [],
            'integrity' => true
        ];
        
        // Check custom tables
        $custom_tables = [
            'mt_evaluations',
            'mt_jury_assignments',
            'mt_votes',
            'mt_candidate_scores',
            'mt_vote_backups',
            'vote_reset_logs',
            'mt_error_log'
        ];
        
        foreach ($custom_tables as $table) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $full_table_name
            )) === $full_table_name;
            
            $info = [
                'exists' => $exists,
                'status' => $exists ? 'ok' : 'missing'
            ];
            
            if ($exists) {
                $info['row_count'] = $wpdb->get_var("SELECT COUNT(*) FROM `$full_table_name`");
                $info['auto_increment'] = $wpdb->get_var(
                    "SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$full_table_name'"
                );
                
                // Check for table issues
                $check_result = $wpdb->get_row("CHECK TABLE `$full_table_name`");
                if ($check_result && $check_result->Msg_text !== 'OK') {
                    $info['status'] = 'needs_repair';
                    $info['message'] = $check_result->Msg_text;
                    $results['integrity'] = false;
                }
            } else {
                $results['integrity'] = false;
            }
            
            $results['tables'][$table] = $info;
        }
        
        // Check for orphaned data
        $results['orphaned_evaluations'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_evaluations e
             WHERE NOT EXISTS (
                 SELECT 1 FROM {$wpdb->prefix}mt_jury_assignments a 
                 WHERE a.jury_member_id = e.jury_member_id 
                 AND a.candidate_id = e.candidate_id
             )"
        );
        
        return $results;
    }
    
    /**
     * Check plugin components
     *
     * @return array Plugin component status
     */
    private function check_plugin_components() {
        $results = [
            'version' => MT_VERSION,
            'db_version' => get_option('mt_db_version', 'Not set'),
            'plugin_dir' => MT_PLUGIN_DIR,
            'plugin_url' => MT_PLUGIN_URL,
            'components' => []
        ];
        
        // Check post types
        $post_types = ['mt_candidate', 'mt_jury_member'];
        foreach ($post_types as $post_type) {
            $results['components']['post_types'][$post_type] = [
                'registered' => post_type_exists($post_type),
                'count' => wp_count_posts($post_type)->publish ?? 0
            ];
        }
        
        // Check taxonomies
        $taxonomies = ['mt_award_category'];
        foreach ($taxonomies as $taxonomy) {
            $results['components']['taxonomies'][$taxonomy] = [
                'registered' => taxonomy_exists($taxonomy),
                'count' => wp_count_terms($taxonomy)
            ];
        }
        
        // Check roles
        $roles = ['mt_jury_member'];
        foreach ($roles as $role) {
            $wp_role = get_role($role);
            $results['components']['roles'][$role] = [
                'exists' => !is_null($wp_role),
                'capabilities' => $wp_role ? array_keys($wp_role->capabilities) : []
            ];
        }
        
        // Check repositories
        $repositories = [
            'MT_Evaluation_Repository' => '\MobilityTrailblazers\Repositories\MT_Evaluation_Repository',
            'MT_Assignment_Repository' => '\MobilityTrailblazers\Repositories\MT_Assignment_Repository',
            'MT_Audit_Log_Repository' => '\MobilityTrailblazers\Repositories\MT_Audit_Log_Repository'
        ];
        
        foreach ($repositories as $name => $class) {
            $results['components']['repositories'][$name] = class_exists($class);
        }
        
        // Check services
        $services = [
            'MT_Evaluation_Service' => '\MobilityTrailblazers\Services\MT_Evaluation_Service',
            'MT_Assignment_Service' => '\MobilityTrailblazers\Services\MT_Assignment_Service'
        ];
        
        foreach ($services as $name => $class) {
            $results['components']['services'][$name] = class_exists($class);
        }
        
        return $results;
    }
    
    /**
     * Check filesystem health
     *
     * @return array Filesystem health status
     */
    private function check_filesystem_health() {
        $results = [
            'uploads_writable' => false,
            'plugin_writable' => false,
            'temp_writable' => false,
            'directories' => []
        ];
        
        // Check uploads directory
        $upload_dir = wp_upload_dir();
        $results['uploads_writable'] = wp_is_writable($upload_dir['basedir']);
        $results['directories']['uploads'] = [
            'path' => $upload_dir['basedir'],
            'url' => $upload_dir['baseurl'],
            'writable' => $results['uploads_writable']
        ];
        
        // Check plugin directory
        $results['plugin_writable'] = wp_is_writable(MT_PLUGIN_DIR);
        $results['directories']['plugin'] = [
            'path' => MT_PLUGIN_DIR,
            'writable' => $results['plugin_writable']
        ];
        
        // Check temp directory
        $temp_dir = get_temp_dir();
        $results['temp_writable'] = wp_is_writable($temp_dir);
        $results['directories']['temp'] = [
            'path' => $temp_dir,
            'writable' => $results['temp_writable']
        ];
        
        // Check critical plugin directories
        $critical_dirs = [
            'assets',
            'includes',
            'templates',
            'debug'
        ];
        
        foreach ($critical_dirs as $dir) {
            $dir_path = MT_PLUGIN_DIR . $dir;
            $results['directories'][$dir] = [
                'exists' => is_dir($dir_path),
                'writable' => wp_is_writable($dir_path)
            ];
        }
        
        return $results;
    }
    
    /**
     * Check performance metrics
     *
     * @return array Performance metrics
     */
    private function check_performance_metrics() {
        global $wpdb;
        
        $results = [
            'memory' => [],
            'database' => [],
            'cache' => []
        ];
        
        // Memory metrics
        $results['memory']['current'] = memory_get_usage(true);
        $results['memory']['peak'] = memory_get_peak_usage(true);
        $results['memory']['limit'] = $this->convert_to_bytes(ini_get('memory_limit'));
        $results['memory']['usage_percentage'] = round(
            ($results['memory']['current'] / $results['memory']['limit']) * 100,
            2
        );
        
        // Database metrics
        $results['database']['queries'] = $wpdb->num_queries;
        $results['database']['slow_queries'] = $this->check_slow_queries();
        
        // Cache metrics
        $results['cache']['object_cache'] = wp_using_ext_object_cache();
        
        if (function_exists('wp_cache_get_stats')) {
            $cache_stats = wp_cache_get_stats();
            if ($cache_stats) {
                $results['cache']['stats'] = $cache_stats;
            }
        }
        
        // Page load time (estimated)
        if (defined('WP_START_TIMESTAMP')) {
            $results['page_load_time'] = microtime(true) - WP_START_TIMESTAMP;
        }
        
        return $results;
    }
    
    /**
     * Check security status
     *
     * @return array Security status
     */
    private function check_security_status() {
        $results = [
            'ssl' => is_ssl(),
            'file_permissions' => [],
            'capabilities' => [],
            'recommendations' => []
        ];
        
        // Check critical file permissions
        $critical_files = [
            'wp-config.php' => ABSPATH . 'wp-config.php',
            '.htaccess' => ABSPATH . '.htaccess'
        ];
        
        foreach ($critical_files as $name => $path) {
            if (file_exists($path)) {
                $perms = fileperms($path);
                $results['file_permissions'][$name] = [
                    'exists' => true,
                    'permissions' => substr(sprintf('%o', $perms), -4),
                    'secure' => ($perms & 0777) <= 0644
                ];
            } else {
                $results['file_permissions'][$name] = ['exists' => false];
            }
        }
        
        // Check custom capabilities
        $custom_caps = [
            'mt_manage_evaluations',
            'mt_submit_evaluations',
            'mt_view_all_evaluations',
            'mt_manage_assignments',
            'mt_manage_settings',
            'mt_export_data',
            'mt_import_data',
            'mt_jury_admin'
        ];
        
        foreach ($custom_caps as $cap) {
            $roles_with_cap = [];
            foreach (wp_roles()->roles as $role_slug => $role) {
                if (isset($role['capabilities'][$cap]) && $role['capabilities'][$cap]) {
                    $roles_with_cap[] = $role_slug;
                }
            }
            $results['capabilities'][$cap] = $roles_with_cap;
        }
        
        // Security recommendations
        if (!$results['ssl']) {
            $results['recommendations'][] = __('Enable SSL for secure data transmission', 'mobility-trailblazers');
        }
        
        if (WP_DEBUG) {
            $results['recommendations'][] = __('Disable WP_DEBUG in production', 'mobility-trailblazers');
        }
        
        if (defined('DISALLOW_FILE_EDIT') && !DISALLOW_FILE_EDIT) {
            $results['recommendations'][] = __('Consider disabling file editing in admin', 'mobility-trailblazers');
        }
        
        return $results;
    }
    
    /**
     * Check error logs
     *
     * @return array Error log status
     */
    private function check_error_logs() {
        global $wpdb;
        
        $results = [
            'wp_debug' => WP_DEBUG,
            'wp_debug_log' => WP_DEBUG_LOG,
            'wp_debug_display' => WP_DEBUG_DISPLAY,
            'error_log_file' => '',
            'recent_errors' => [],
            'error_stats' => []
        ];
        
        // Check error log file
        $error_log_file = WP_CONTENT_DIR . '/debug.log';
        if (file_exists($error_log_file)) {
            $results['error_log_file'] = $error_log_file;
            $results['error_log_size'] = filesize($error_log_file);
            
            // Get recent plugin errors
            if (is_readable($error_log_file) && filesize($error_log_file) < 1048576) { // Less than 1MB
                $log_content = file_get_contents($error_log_file);
                $lines = explode("\n", $log_content);
                $mt_errors = array_filter($lines, function($line) {
                    return strpos($line, 'mobility-trailblazers') !== false || 
                           strpos($line, 'MT_') !== false;
                });
                $results['recent_errors'] = array_slice($mt_errors, -10);
            }
        }
        
        // Get error statistics from custom error log table
        $table_name = $wpdb->prefix . 'mt_error_log';
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name) {
            $results['error_stats'] = [
                'total' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
                'today' => $wpdb->get_var(
                    "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = CURDATE()"
                ),
                'critical' => $wpdb->get_var(
                    "SELECT COUNT(*) FROM $table_name WHERE level = 'critical'"
                ),
                'by_level' => $wpdb->get_results(
                    "SELECT level, COUNT(*) as count FROM $table_name 
                     GROUP BY level ORDER BY count DESC"
                )
            ];
        }
        
        return $results;
    }
    
    /**
     * Detect environment type
     *
     * @return string Environment type
     */
    private function detect_environment() {
        if (defined('MT_ENVIRONMENT')) {
            return MT_ENVIRONMENT;
        }
        
        if (defined('WP_ENVIRONMENT_TYPE')) {
            return WP_ENVIRONMENT_TYPE;
        }
        
        // Check common development indicators
        if (strpos(get_site_url(), 'localhost') !== false ||
            strpos(get_site_url(), '.local') !== false ||
            strpos(get_site_url(), 'staging') !== false) {
            return 'development';
        }
        
        return 'production';
    }
    
    /**
     * Calculate overall status
     *
     * @return string Overall status
     */
    private function calculate_overall_status() {
        $issues = 0;
        $warnings = 0;
        
        // Check database integrity
        if (isset($this->diagnostics['database']['integrity']) && 
            !$this->diagnostics['database']['integrity']) {
            $issues++;
        }
        
        // Check for orphaned data
        if (isset($this->diagnostics['database']['orphaned_evaluations']) && 
            $this->diagnostics['database']['orphaned_evaluations'] > 0) {
            $warnings++;
        }
        
        // Check memory usage
        if (isset($this->diagnostics['performance']['memory']['usage_percentage']) && 
            $this->diagnostics['performance']['memory']['usage_percentage'] > 80) {
            $warnings++;
        }
        
        // Check security
        if (isset($this->diagnostics['security']['recommendations']) && 
            count($this->diagnostics['security']['recommendations']) > 2) {
            $warnings++;
        }
        
        if ($issues > 0) {
            return 'critical';
        } elseif ($warnings > 1) {
            return 'warning';
        }
        
        return 'healthy';
    }
    
    /**
     * Check for slow queries
     *
     * @return int Number of slow queries
     */
    private function check_slow_queries() {
        global $wpdb;
        
        if (!defined('SAVEQUERIES') || !SAVEQUERIES) {
            return -1; // Not tracking
        }
        
        $slow_queries = 0;
        $threshold = 0.05; // 50ms
        
        foreach ($wpdb->queries as $query) {
            if ($query[1] > $threshold) {
                $slow_queries++;
            }
        }
        
        return $slow_queries;
    }
    
    /**
     * Convert string to bytes
     *
     * @param string $value Value to convert
     * @return int Bytes
     */
    private function convert_to_bytes($value) {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Export diagnostics as JSON
     *
     * @param array $diagnostics Diagnostics data
     * @return string JSON string
     */
    public function export_diagnostics($diagnostics = null) {
        if (null === $diagnostics) {
            $diagnostics = $this->run_full_diagnostic();
        }
        
        return wp_json_encode($diagnostics, JSON_PRETTY_PRINT);
    }
}
