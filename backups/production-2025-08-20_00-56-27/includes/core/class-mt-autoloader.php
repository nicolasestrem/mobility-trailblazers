<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * PSR-4 Autoloader for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Autoloader
 *
 * Handles PSR-4 autoloading for plugin classes
 */
class MT_Autoloader {
    
    /**
     * Namespace prefix
     *
     * @var string
     */
    private static $namespace_prefix = 'MobilityTrailblazers\\';
    
    /**
     * Base directory for classes
     *
     * @var string
     */
    private static $base_dir;
    
    /**
     * Register the autoloader
     *
     * @return void
     */
    public static function register() {
        self::$base_dir = MT_PLUGIN_DIR . 'includes/';
        
        spl_autoload_register([__CLASS__, 'autoload']);
    }
    
    /**
     * Autoload handler
     *
     * @param string $class The fully-qualified class name
     * @return void
     */
    public static function autoload($class) {
        // Check if class uses our namespace
        $len = strlen(self::$namespace_prefix);
        if (strncmp(self::$namespace_prefix, $class, $len) !== 0) {
            return;
        }
        
        // Get the relative class name
        $relative_class = substr($class, $len);
        
        // Convert namespace to file path
        $parts = explode('\\', $relative_class);
        $class_name = array_pop($parts);
        
        // Convert to lowercase directory structure
        $subdirectory = !empty($parts) ? strtolower(implode('/', $parts)) . '/' : '';
        
        // Convert class name to file name
        // Determine if this is an interface or class
        if (strpos($class_name, '_Interface') !== false || strpos($class_name, 'Interface') !== false) {
            // For interfaces, use 'interface-' prefix
            // Remove '_Interface' suffix (case-insensitive) and convert to filename
            $interface_name = preg_replace('/_?Interface$/i', '', $class_name);
            $file_name = 'interface-' . str_replace('_', '-', strtolower($interface_name)) . '.php';
        } else {
            // For classes, use 'class-' prefix
            $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        }
        
        // Build the file path
        $file = self::$base_dir . $subdirectory . $file_name;
        
        // Require the file if it exists
        if (file_exists($file)) {
            require_once $file;
        }
    }
} 
