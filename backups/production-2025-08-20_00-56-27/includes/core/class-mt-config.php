<?php
/**
 * Configuration Manager for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 * @since 2.5.34
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Config
 *
 * Manages environment-specific configuration for the plugin
 */
class MT_Config {
    
    /**
     * Environment constants
     */
    const ENV_DEVELOPMENT = 'development';
    const ENV_STAGING = 'staging';
    const ENV_PRODUCTION = 'production';
    
    /**
     * Current environment
     *
     * @var string
     */
    private static $environment = null;
    
    /**
     * Configuration settings
     *
     * @var array
     */
    private static $config = null;
    
    /**
     * Get current environment
     *
     * @return string
     */
    public static function get_environment() {
        if (self::$environment === null) {
            // Check for explicit MT_ENVIRONMENT constant
            if (defined('MT_ENVIRONMENT')) {
                self::$environment = MT_ENVIRONMENT;
            }
            // Check WordPress environment type
            elseif (function_exists('wp_get_environment_type')) {
                $wp_env = wp_get_environment_type();
                self::$environment = in_array($wp_env, ['local', 'development']) ? self::ENV_DEVELOPMENT :
                                   ($wp_env === 'staging' ? self::ENV_STAGING : self::ENV_PRODUCTION);
            }
            // Check WP_ENVIRONMENT_TYPE constant
            elseif (defined('WP_ENVIRONMENT_TYPE')) {
                $wp_env = WP_ENVIRONMENT_TYPE;
                self::$environment = in_array($wp_env, ['local', 'development']) ? self::ENV_DEVELOPMENT :
                                   ($wp_env === 'staging' ? self::ENV_STAGING : self::ENV_PRODUCTION);
            }
            // Default to production for safety
            else {
                self::$environment = self::ENV_PRODUCTION;
            }
        }
        
        return self::$environment;
    }
    
    /**
     * Check if current environment is development
     *
     * @return bool
     */
    public static function is_development() {
        return self::get_environment() === self::ENV_DEVELOPMENT;
    }
    
    /**
     * Check if current environment is staging
     *
     * @return bool
     */
    public static function is_staging() {
        return self::get_environment() === self::ENV_STAGING;
    }
    
    /**
     * Check if current environment is production
     *
     * @return bool
     */
    public static function is_production() {
        return self::get_environment() === self::ENV_PRODUCTION;
    }
    
    /**
     * Get configuration for current environment
     *
     * @return array
     */
    public static function get_config() {
        if (self::$config === null) {
            self::$config = self::load_config();
        }
        
        return self::$config;
    }
    
    /**
     * Get specific configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get($key, $default = null) {
        $config = self::get_config();
        return isset($config[$key]) ? $config[$key] : $default;
    }
    
    /**
     * Load configuration based on environment
     *
     * @return array
     */
    private static function load_config() {
        $env = self::get_environment();
        
        // Base configuration for all environments
        $config = [
            'version' => MT_VERSION,
            'environment' => $env,
            'cache_enabled' => true,
            'cache_expiration' => 3600,
            'query_cache_enabled' => true,
            'ajax_cache_enabled' => true,
            'minify_assets' => false,
            'combine_assets' => false,
            'lazy_load_images' => true,
            'optimize_database' => true,
            'batch_size' => 50,
            'import_batch_size' => 20,
            'evaluation_cache_time' => 300,
            'voting_cache_time' => 60,
            'enable_cdn' => false,
            'cdn_url' => '',
            'enable_monitoring' => false,
            'monitoring_endpoint' => '',
            'rate_limiting' => true,
            'rate_limit_requests' => 100,
            'rate_limit_window' => 60,
            'security_headers' => true,
            'disable_xmlrpc' => true,
            'disable_rest_api_public' => false,
            'enable_maintenance_mode' => false,
            'maintenance_message' => '',
            'auto_cleanup_logs' => true,
            'log_retention_days' => 30,
            'enable_performance_tracking' => false,
            'slow_query_threshold' => 1.0,
            'memory_limit_warning' => 256,
            'max_upload_size' => 10485760, // 10MB
            'allowed_image_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'image_quality' => 85,
            'thumbnail_sizes' => [
                'candidate_thumb' => [150, 150, true],
                'candidate_medium' => [300, 300, true],
                'candidate_large' => [600, 600, false]
            ]
        ];
        
        // Environment-specific overrides
        switch ($env) {
            case self::ENV_PRODUCTION:
                $config = array_merge($config, [
                    // Debugging disabled
                    'debug_enabled' => false,
                    'debug_display' => false,
                    'debug_log' => false,
                    'script_debug' => false,
                    'error_reporting' => false,
                    'show_admin_bar' => false,
                    'enable_debug_scripts' => false,
                    'enable_debug_menu' => false,
                    'enable_system_info' => false,
                    'enable_diagnostics' => false,
                    'log_level' => 'ERROR',
                    'log_to_file' => false,
                    'log_ajax_errors' => false,
                    'verbose_errors' => false,
                    
                    // Performance optimizations
                    'minify_assets' => true,
                    'combine_assets' => true,
                    'cache_expiration' => 7200,
                    'evaluation_cache_time' => 600,
                    'voting_cache_time' => 120,
                    'batch_size' => 100,
                    'import_batch_size' => 50,
                    
                    // Security
                    'security_headers' => true,
                    'disable_xmlrpc' => true,
                    'disable_rest_api_public' => true,
                    'rate_limiting' => true,
                    'rate_limit_requests' => 60,
                    'rate_limit_window' => 60,
                    
                    // Monitoring
                    'enable_monitoring' => true,
                    'enable_performance_tracking' => true,
                    'slow_query_threshold' => 0.5,
                    
                    // Cleanup
                    'auto_cleanup_logs' => true,
                    'log_retention_days' => 7,
                    
                    // CDN
                    'enable_cdn' => true,
                    'cdn_url' => 'https://cdn.mobility-trailblazers.com'
                ]);
                break;
                
            case self::ENV_STAGING:
                $config = array_merge($config, [
                    // Limited debugging
                    'debug_enabled' => true,
                    'debug_display' => false,
                    'debug_log' => true,
                    'script_debug' => false,
                    'error_reporting' => true,
                    'show_admin_bar' => true,
                    'enable_debug_scripts' => false,
                    'enable_debug_menu' => true,
                    'enable_system_info' => true,
                    'enable_diagnostics' => true,
                    'log_level' => 'WARNING',
                    'log_to_file' => true,
                    'log_ajax_errors' => true,
                    'verbose_errors' => false,
                    
                    // Moderate optimizations
                    'minify_assets' => true,
                    'combine_assets' => false,
                    'cache_expiration' => 3600,
                    'batch_size' => 75,
                    'import_batch_size' => 30,
                    
                    // Security (less strict)
                    'security_headers' => true,
                    'disable_xmlrpc' => true,
                    'disable_rest_api_public' => false,
                    'rate_limiting' => true,
                    'rate_limit_requests' => 100,
                    
                    // Monitoring
                    'enable_monitoring' => true,
                    'enable_performance_tracking' => true,
                    'slow_query_threshold' => 0.75,
                    
                    // Cleanup
                    'auto_cleanup_logs' => true,
                    'log_retention_days' => 14
                ]);
                break;
                
            case self::ENV_DEVELOPMENT:
            default:
                $config = array_merge($config, [
                    // Full debugging
                    'debug_enabled' => true,
                    'debug_display' => true,
                    'debug_log' => true,
                    'script_debug' => true,
                    'error_reporting' => true,
                    'show_admin_bar' => true,
                    'enable_debug_scripts' => true,
                    'enable_debug_menu' => true,
                    'enable_system_info' => true,
                    'enable_diagnostics' => true,
                    'log_level' => 'DEBUG',
                    'log_to_file' => true,
                    'log_ajax_errors' => true,
                    'verbose_errors' => true,
                    
                    // No optimizations
                    'minify_assets' => false,
                    'combine_assets' => false,
                    'cache_enabled' => false,
                    'cache_expiration' => 0,
                    'query_cache_enabled' => false,
                    'ajax_cache_enabled' => false,
                    'batch_size' => 25,
                    'import_batch_size' => 10,
                    
                    // Relaxed security
                    'security_headers' => false,
                    'disable_xmlrpc' => false,
                    'disable_rest_api_public' => false,
                    'rate_limiting' => false,
                    
                    // Full monitoring
                    'enable_monitoring' => true,
                    'enable_performance_tracking' => true,
                    'slow_query_threshold' => 2.0,
                    
                    // No cleanup
                    'auto_cleanup_logs' => false,
                    'log_retention_days' => 90
                ]);
                break;
        }
        
        // Allow filtering of configuration
        return apply_filters('mt_config', $config, $env);
    }
    
    /**
     * Check if debug mode is enabled
     *
     * @return bool
     */
    public static function is_debug_enabled() {
        return self::get('debug_enabled', false);
    }
    
    /**
     * Check if feature is enabled
     *
     * @param string $feature Feature name
     * @return bool
     */
    public static function is_feature_enabled($feature) {
        return self::get('enable_' . $feature, false);
    }
    
    /**
     * Get log level
     *
     * @return string
     */
    public static function get_log_level() {
        return self::get('log_level', 'ERROR');
    }
    
    /**
     * Check if should log specific level
     *
     * @param string $level Log level to check
     * @return bool
     */
    public static function should_log($level) {
        $levels = [
            'DEBUG' => 0,
            'INFO' => 1,
            'WARNING' => 2,
            'ERROR' => 3,
            'CRITICAL' => 4
        ];
        
        $current_level = self::get_log_level();
        
        if (!isset($levels[$level]) || !isset($levels[$current_level])) {
            return false;
        }
        
        return $levels[$level] >= $levels[$current_level];
    }
}
