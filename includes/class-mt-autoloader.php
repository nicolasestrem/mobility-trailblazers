<?php
/**
 * Autoloader for Mobility Trailblazers plugin
 *
 * @package MobilityTrailblazers
 * @since 1.0.7
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Autoloader
 * Handles autoloading of namespaced classes
 */
class MT_Autoloader {
    
    /**
     * Plugin base directory
     *
     * @var string
     */
    private $base_dir;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->base_dir = plugin_dir_path(dirname(__FILE__));
    }
    
    /**
     * Register autoloader
     */
    public function register() {
        spl_autoload_register(array($this, 'autoload'));
    }
    
    /**
     * Autoload method
     *
     * @param string $class The fully-qualified class name
     */
    public function autoload($class) {
        // Project-specific namespace prefix
        $prefix = 'MobilityTrailblazers\\';
        
        // Base directory for the namespace prefix
        $base_dir = $this->base_dir . 'includes/';
        
        // Does the class use the namespace prefix?
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            // No, move to the next registered autoloader
            return;
        }
        
        // Get the relative class name
        $relative_class = substr($class, $len);
        
        // Replace the namespace prefix with the base directory,
        // replace namespace separators with directory separators in the relative class name,
        // append with .php
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        // Convert to lowercase and add 'class-' prefix for consistency
        $path_parts = explode('/', $file);
        $filename = array_pop($path_parts);
        $filename = 'class-' . strtolower(str_replace('_', '-', $filename));
        $file = implode('/', $path_parts) . '/' . $filename;
        
        // If the file exists, require it
        if (file_exists($file)) {
            require_once $file;
        }
    }
}