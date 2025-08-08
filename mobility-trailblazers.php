<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Plugin URI: https://mobility-trailblazers.com
 * Description: Award management platform for recognizing mobility innovators in the DACH region
 * Version: 2.0.16
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Nicolas Estrem
 * Author URI: https://mobility-trailblazers.com
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_VERSION', '2.0.14');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require the autoloader
require_once MT_PLUGIN_DIR . 'includes/class-mt-autoloader.php';

// Register autoloader
MobilityTrailblazers\Core\MT_Autoloader::register();

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Load text domain
    load_plugin_textdomain('mobility-trailblazers', false, dirname(MT_PLUGIN_BASENAME) . '/languages');
    
    // Initialize core
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    $plugin->init();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    $activator = new MobilityTrailblazers\Core\MT_Activator();
    $activator->activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    $deactivator = new MobilityTrailblazers\Core\MT_Deactivator();
    $deactivator->deactivate();
});

// Uninstall hook
register_uninstall_hook(__FILE__, ['MobilityTrailblazers\Core\MT_Uninstaller', 'uninstall']); 