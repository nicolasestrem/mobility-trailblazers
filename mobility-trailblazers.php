<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Plugin URI: https://mobility-trailblazers.com
 * Description: Award management platform for recognizing mobility innovators in the DACH region
 * Version: 2.5.21
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Nicolas Estrem
 * Author URI: https://mobility-trailblazers.com
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Copyright (c) 2025 Nicolas Estrem
 *
 * This program is free software. You can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation. Either version 2 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see https://www.gnu.org/licenses/old-licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_VERSION', '2.5.21');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Require the autoloader
require_once MT_PLUGIN_DIR . 'includes/core/class-mt-autoloader.php';

// Register autoloader
MobilityTrailblazers\Core\MT_Autoloader::register();

// Initialize the plugin
add_action('plugins_loaded', function() {
    // Load text domain
    load_plugin_textdomain('mobility-trailblazers', false, dirname(MT_PLUGIN_BASENAME) . '/languages');
    
    // Initialize core
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    $plugin->init();
    
    // Initialize migration runner
    MobilityTrailblazers\Core\MT_Migration_Runner::init();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    $activator = new MobilityTrailblazers\Core\MT_Activator();
    $activator->activate();
    
    // Run migrations on activation
    MobilityTrailblazers\Core\MT_Migration_Runner::activate();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    $deactivator = new MobilityTrailblazers\Core\MT_Deactivator();
    $deactivator->deactivate();
});

// Uninstall hook
register_uninstall_hook(__FILE__, ['MobilityTrailblazers\Core\MT_Uninstaller', 'uninstall']); 
