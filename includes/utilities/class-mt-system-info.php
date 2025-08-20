<?php
/**
 * System Information Utility
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

namespace MobilityTrailblazers\Utilities;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_System_Info
 *
 * Gathers comprehensive system information
 */
class MT_System_Info {
    
    /**
     * Get complete system information
     *
     * @return array System information
     */
    public function get_system_info() {
        return [
            'php' => $this->get_php_info(),
            'wordpress' => $this->get_wordpress_info(),
            'server' => $this->get_server_info(),
            'database' => $this->get_database_info(),
            'plugins' => $this->get_plugins_info(),
            'theme' => $this->get_theme_info(),
            'constants' => $this->get_important_constants(),
            'network' => $this->get_network_info()
        ];
    }
    
    /**
     * Get PHP information
     *
     * @return array PHP info
     */
    private function get_php_info() {
        return [
            'version' => PHP_VERSION,
            'version_id' => PHP_VERSION_ID,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'os_family' => PHP_OS_FAMILY,
            'architecture' => php_uname('m'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'display_errors' => ini_get('display_errors'),
            'error_reporting' => ini_get('error_reporting'),
            'log_errors' => ini_get('log_errors'),
            'error_log' => ini_get('error_log'),
            'date_timezone' => ini_get('date.timezone'),
            'extensions' => $this->get_php_extensions(),
            'disabled_functions' => $this->get_disabled_functions(),
            'opcache' => $this->get_opcache_info()
        ];
    }
    
    /**
     * Get PHP extensions
     *
     * @return array Extension information
     */
    private function get_php_extensions() {
        $important_extensions = [
            'curl', 'dom', 'exif', 'fileinfo', 'gd', 'imagick',
            'intl', 'json', 'mbstring', 'mysqli', 'openssl',
            'pcre', 'sodium', 'xml', 'xmlreader', 'zip'
        ];
        
        $extensions = [];
        foreach ($important_extensions as $ext) {
            $extensions[$ext] = [
                'loaded' => extension_loaded($ext),
                'version' => extension_loaded($ext) ? phpversion($ext) : null
            ];
        }
        
        return $extensions;
    }
    
    /**
     * Get disabled functions
     *
     * @return array List of disabled functions
     */
    private function get_disabled_functions() {
        $disabled = ini_get('disable_functions');
        return $disabled ? explode(',', $disabled) : [];
    }
    
    /**
     * Get OPcache information
     *
     * @return array OPcache info
     */
    private function get_opcache_info() {
        if (!extension_loaded('Zend OPcache')) {
            return ['enabled' => false];
        }
        
        $config = opcache_get_configuration();
        $status = opcache_get_status(false);
        
        return [
            'enabled' => $config['directives']['opcache.enable'] ?? false,
            'memory_consumption' => $config['directives']['opcache.memory_consumption'] ?? 0,
            'max_accelerated_files' => $config['directives']['opcache.max_accelerated_files'] ?? 0,
            'revalidate_freq' => $config['directives']['opcache.revalidate_freq'] ?? 0,
            'memory_usage' => $status['memory_usage'] ?? [],
            'statistics' => $status['opcache_statistics'] ?? []
        ];
    }
    
    /**
     * Get WordPress information
     *
     * @return array WordPress info
     */
    private function get_wordpress_info() {
        global $wp_version, $wp_db_version;
        
        $info = [
            'version' => $wp_version,
            'db_version' => $wp_db_version,
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'wp_content_dir' => WP_CONTENT_DIR,
            'wp_content_url' => WP_CONTENT_URL,
            'wp_plugin_dir' => WP_PLUGIN_DIR,
            'wp_plugin_url' => WP_PLUGIN_URL,
            'uploads_dir' => wp_upload_dir()['basedir'],
            'uploads_url' => wp_upload_dir()['baseurl'],
            'multisite' => is_multisite(),
            'network_admin' => is_network_admin(),
            'debug_mode' => WP_DEBUG,
            'debug_log' => WP_DEBUG_LOG,
            'debug_display' => WP_DEBUG_DISPLAY,
            'script_debug' => defined('SCRIPT_DEBUG') && SCRIPT_DEBUG,
            'memory_limit' => WP_MEMORY_LIMIT,
            'max_memory_limit' => WP_MAX_MEMORY_LIMIT,
            'cache' => WP_CACHE,
            'language' => get_locale(),
            'timezone' => get_option('timezone_string'),
            'gmt_offset' => get_option('gmt_offset'),
            'permalink_structure' => get_option('permalink_structure'),
            'active_theme' => get_option('stylesheet'),
            'active_plugins_count' => count(get_option('active_plugins', [])),
            'registered_post_types' => count(get_post_types()),
            'registered_taxonomies' => count(get_taxonomies()),
            'user_count' => count_users()['total_users'],
            'cron_status' => !defined('DISABLE_WP_CRON') || !DISABLE_WP_CRON,
            'cron_jobs' => $this->get_cron_jobs_count()
        ];
        
        // Multisite specific info
        if (is_multisite()) {
            $info['network_id'] = get_current_network_id();
            $info['site_id'] = get_current_blog_id();
            $info['site_count'] = get_blog_count();
        }
        
        return $info;
    }
    
    /**
     * Get cron jobs count
     *
     * @return int Number of scheduled cron jobs
     */
    private function get_cron_jobs_count() {
        $cron = _get_cron_array();
        $count = 0;
        
        foreach ($cron as $timestamp => $cronhooks) {
            if (is_array($cronhooks)) {
                $count += count($cronhooks);
            }
        }
        
        return $count;
    }
    
    /**
     * Get server information
     *
     * @return array Server info
     */
    private function get_server_info() {
        return [
            'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'hostname' => $_SERVER['SERVER_NAME'] ?? gethostname(),
            'ip_address' => $_SERVER['SERVER_ADDR'] ?? 'Unknown',
            'port' => $_SERVER['SERVER_PORT'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'server_admin' => $_SERVER['SERVER_ADMIN'] ?? 'Unknown',
            'gateway_interface' => $_SERVER['GATEWAY_INTERFACE'] ?? 'Unknown',
            'max_upload_size' => $this->get_max_upload_size(),
            'max_post_size' => $this->get_max_post_size(),
            'curl_version' => $this->get_curl_version(),
            'suhosin' => extension_loaded('suhosin'),
            'imagick' => extension_loaded('imagick'),
            'gd' => extension_loaded('gd')
        ];
    }
    
    /**
     * Get maximum upload size
     *
     * @return string Max upload size
     */
    private function get_max_upload_size() {
        $upload_max = $this->convert_to_bytes(ini_get('upload_max_filesize'));
        $post_max = $this->convert_to_bytes(ini_get('post_max_size'));
        $memory_limit = $this->convert_to_bytes(ini_get('memory_limit'));
        
        $min = min($upload_max, $post_max);
        if ($memory_limit > 0) {
            $min = min($min, $memory_limit);
        }
        
        return size_format($min);
    }
    
    /**
     * Get maximum post size
     *
     * @return string Max post size
     */
    private function get_max_post_size() {
        return ini_get('post_max_size');
    }
    
    /**
     * Get cURL version
     *
     * @return string cURL version
     */
    private function get_curl_version() {
        if (!function_exists('curl_version')) {
            return 'Not available';
        }
        
        $curl = curl_version();
        return $curl['version'] . ' / ' . $curl['ssl_version'];
    }
    
    /**
     * Get database information
     *
     * @return array Database info
     */
    private function get_database_info() {
        global $wpdb;
        
        $mysql_vars = [
            'key_buffer_size',
            'max_allowed_packet',
            'max_connections',
            'query_cache_size',
            'query_cache_type',
            'innodb_buffer_pool_size',
            'innodb_log_file_size',
            'max_heap_table_size',
            'tmp_table_size',
            'join_buffer_size',
            'sort_buffer_size'
        ];
        
        $variables = [];
        foreach ($mysql_vars as $var) {
            $result = $wpdb->get_row("SHOW VARIABLES LIKE '$var'");
            if ($result) {
                $variables[$var] = $result->Value;
            }
        }
        
        return [
            'server_version' => $wpdb->db_version(),
            'client_version' => $wpdb->get_var("SELECT VERSION()"),
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'charset' => $wpdb->charset,
            'collate' => $wpdb->collate,
            'prefix' => $wpdb->prefix,
            'variables' => $variables
        ];
    }
    
    /**
     * Get plugin information
     *
     * @return array Plugin info
     */
    private function get_plugins_info() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins', []);
        $network_activated = is_multisite() ? get_site_option('active_sitewide_plugins', []) : [];
        
        $plugins = [
            'total' => count($all_plugins),
            'active' => count($active_plugins),
            'inactive' => count($all_plugins) - count($active_plugins),
            'network_activated' => count($network_activated),
            'must_use' => count(get_mu_plugins()),
            'dropins' => count(get_dropins()),
            'list' => []
        ];
        
        // Get plugin details
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $is_active = in_array($plugin_file, $active_plugins) || 
                        isset($network_activated[$plugin_file]);
            
            $plugins['list'][] = [
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'author' => $plugin_data['Author'],
                'active' => $is_active,
                'network' => isset($network_activated[$plugin_file]),
                'file' => $plugin_file
            ];
        }
        
        // Sort by active status and name
        usort($plugins['list'], function($a, $b) {
            if ($a['active'] === $b['active']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $b['active'] - $a['active'];
        });
        
        return $plugins;
    }
    
    /**
     * Get theme information
     *
     * @return array Theme info
     */
    private function get_theme_info() {
        $theme = wp_get_theme();
        $parent_theme = $theme->parent();
        
        return [
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author'),
            'template' => $theme->get_template(),
            'stylesheet' => $theme->get_stylesheet(),
            'template_dir' => $theme->get_template_directory(),
            'stylesheet_dir' => $theme->get_stylesheet_directory(),
            'theme_root' => $theme->get_theme_root(),
            'parent_theme' => $parent_theme ? $parent_theme->get('Name') : null,
            'is_child' => $theme->parent() !== false,
            'text_domain' => $theme->get('TextDomain'),
            'tags' => $theme->get('Tags'),
            'screenshot' => $theme->get_screenshot()
        ];
    }
    
    /**
     * Get important constants
     *
     * @return array Constants
     */
    private function get_important_constants() {
        $constants = [
            'ABSPATH', 'WP_DEBUG', 'WP_DEBUG_LOG', 'WP_DEBUG_DISPLAY',
            'SCRIPT_DEBUG', 'WP_CACHE', 'CONCATENATE_SCRIPTS',
            'COMPRESS_SCRIPTS', 'COMPRESS_CSS', 'WP_LOCAL_DEV',
            'WP_MEMORY_LIMIT', 'WP_MAX_MEMORY_LIMIT', 'EMPTY_TRASH_DAYS',
            'WP_POST_REVISIONS', 'AUTOSAVE_INTERVAL', 'DISABLE_WP_CRON',
            'WP_CRON_LOCK_TIMEOUT', 'COOKIEPATH', 'SITECOOKIEPATH',
            'ADMIN_COOKIE_PATH', 'PLUGINS_COOKIE_PATH', 'TEMPLATEPATH',
            'STYLESHEETPATH', 'FORCE_SSL_ADMIN', 'FORCE_SSL_LOGIN',
            'WP_HTTP_BLOCK_EXTERNAL', 'WP_ACCESSIBLE_HOSTS',
            'WP_AUTO_UPDATE_CORE', 'IMAGE_EDIT_OVERWRITE',
            'MEDIA_TRASH', 'WP_ENVIRONMENT_TYPE', 'MT_ENVIRONMENT',
            'MT_VERSION', 'MT_PLUGIN_DIR', 'MT_PLUGIN_URL'
        ];
        
        $defined_constants = [];
        foreach ($constants as $constant) {
            if (defined($constant)) {
                $value = constant($constant);
                
                // Sanitize sensitive values
                if (in_array($constant, ['DB_PASSWORD', 'AUTH_KEY', 'SECURE_AUTH_KEY'])) {
                    $value = '***HIDDEN***';
                }
                
                $defined_constants[$constant] = $value;
            }
        }
        
        return $defined_constants;
    }
    
    /**
     * Get network information
     *
     * @return array Network info
     */
    private function get_network_info() {
        $info = [
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname()),
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'http_host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'Unknown',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? 'Unknown'
        ];
        
        // Check external connectivity
        $info['can_connect_external'] = $this->can_connect_external();
        
        return $info;
    }
    
    /**
     * Check if can connect to external services
     *
     * @return bool
     */
    private function can_connect_external() {
        $response = wp_remote_get('https://api.wordpress.org/core/version-check/1.7/', [
            'timeout' => 5,
            'redirection' => 0
        ]);
        
        return !is_wp_error($response);
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
     * Export system info as text
     *
     * @return string Formatted system info
     */
    public function export_as_text() {
        $info = $this->get_system_info();
        $output = "=== Mobility Trailblazers System Information ===\n";
        $output .= "Generated: " . current_time('mysql') . "\n\n";
        
        // PHP Info
        $output .= "== PHP Information ==\n";
        $output .= "Version: " . $info['php']['version'] . "\n";
        $output .= "Memory Limit: " . $info['php']['memory_limit'] . "\n";
        $output .= "Max Execution Time: " . $info['php']['max_execution_time'] . "\n";
        $output .= "Upload Max Filesize: " . $info['php']['upload_max_filesize'] . "\n\n";
        
        // WordPress Info
        $output .= "== WordPress Information ==\n";
        $output .= "Version: " . $info['wordpress']['version'] . "\n";
        $output .= "Site URL: " . $info['wordpress']['site_url'] . "\n";
        $output .= "Debug Mode: " . ($info['wordpress']['debug_mode'] ? 'Enabled' : 'Disabled') . "\n";
        $output .= "Memory Limit: " . $info['wordpress']['memory_limit'] . "\n\n";
        
        // Server Info
        $output .= "== Server Information ==\n";
        $output .= "Software: " . $info['server']['software'] . "\n";
        $output .= "Hostname: " . $info['server']['hostname'] . "\n";
        $output .= "HTTPS: " . ($info['server']['https'] ? 'Yes' : 'No') . "\n\n";
        
        // Database Info
        $output .= "== Database Information ==\n";
        $output .= "Server Version: " . $info['database']['server_version'] . "\n";
        $output .= "Database: " . $info['database']['database'] . "\n";
        $output .= "Charset: " . $info['database']['charset'] . "\n";
        $output .= "Table Prefix: " . $info['database']['prefix'] . "\n\n";
        
        return $output;
    }
}
