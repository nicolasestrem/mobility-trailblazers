<?php
/**
 * Plugin Name: Mobility Trailblazers Award System
 * Plugin URI: https://example.com/mobility-trailblazers
 * Description: A comprehensive award system for managing candidates, jury members, evaluations, and voting processes.
 * Version: 2.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MT_VERSION', '2.0.0');

// Autoloader (optional - fallback to manual loading if not available)
$autoloader_path = MT_PLUGIN_DIR . 'vendor/autoload.php';
if (file_exists($autoloader_path)) {
    require_once $autoloader_path;
}

// Load the main plugin class
require_once MT_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize the plugin
 */
function mobility_trailblazers_init() {
    $plugin = MT_Plugin::get_instance();
    $plugin->run();
}
add_action('plugins_loaded', 'mobility_trailblazers_init');

/**
 * Plugin activation hook
 */
function mobility_trailblazers_activate() {
    // Basic activation - just flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mobility_trailblazers_activate');

/**
 * Plugin deactivation hook
 */
function mobility_trailblazers_deactivate() {
    // Basic deactivation - just flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'mobility_trailblazers_deactivate');

?>