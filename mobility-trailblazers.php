<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Plugin URI: https://mobility-trailblazers.com
 * Description: A comprehensive platform for managing mobility innovation awards, jury evaluations, and candidate profiles.
 * Version: 2.5.7
 * Author: Mobility Trailblazers Team
 * Author URI: https://mobility-trailblazers.com
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_VERSION', '2.5.7');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load shortcodes directly
require_once MT_PLUGIN_DIR . 'includes/core/class-mt-shortcodes.php';

// Initialize shortcodes
function mt_init() {
    $shortcodes = new \MobilityTrailblazers\Core\MT_Shortcodes();
    $shortcodes->init();
}
add_action('plugins_loaded', 'mt_init');

// Activation hook - temporarily disabled for debugging
// register_activation_hook(__FILE__, function() {
//     if (class_exists('\MobilityTrailblazers\Core\MT_Activator')) {
//         $activator = new \MobilityTrailblazers\Core\MT_Activator();
//         $activator->activate();
//     }
// });

// Deactivation hook - temporarily disabled for debugging
// register_deactivation_hook(__FILE__, function() {
//     if (class_exists('\MobilityTrailblazers\Core\MT_Deactivator')) {
//         $deactivator = new \MobilityTrailblazers\Core\MT_Deactivator();
//         $deactivator->deactivate();
//     }
// });

// Uninstall hook - temporarily disabled for debugging
// register_uninstall_hook(__FILE__, function() {
//     if (class_exists('\MobilityTrailblazers\Core\MT_Uninstaller')) {
//         $uninstaller = new \MobilityTrailblazers\Core\MT_Uninstaller();
//         $uninstaller->uninstall();
//     }
// });
