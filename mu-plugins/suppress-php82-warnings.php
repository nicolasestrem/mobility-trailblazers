<?php
/**
 * Plugin Name: Suppress PHP 8.2 Deprecation Warnings
 * Description: Suppresses PHP 8.2 deprecation warnings to prevent header errors
 * Version: 1.0
 */

// Suppress PHP 8.2 deprecation warnings
if (version_compare(PHP_VERSION, '8.0', '>=')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
    
    // Also set display_errors to off
    @ini_set('display_errors', '0');
    
    // Ensure WordPress debug display is off
    if (!defined('WP_DEBUG_DISPLAY')) {
        define('WP_DEBUG_DISPLAY', false);
    }
}

// Fix null parameter warnings by filtering early
add_action('plugins_loaded', function() {
    // Override error reporting for this session
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}, 1);
