<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Plugin URI: https://mobility-trailblazers.com
 * Description: Award management platform for recognizing mobility innovators in the DACH region
 * Version: 2.5.35
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
define('MT_VERSION', '2.5.35');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Environment detection (can be overridden in wp-config.php)
if (!defined('MT_ENVIRONMENT')) {
    // Automatic detection based on domain or WP environment
    if (defined('WP_ENVIRONMENT_TYPE')) {
        $wp_env = WP_ENVIRONMENT_TYPE;
        if (in_array($wp_env, ['local', 'development'])) {
            define('MT_ENVIRONMENT', 'development');
        } elseif ($wp_env === 'staging') {
            define('MT_ENVIRONMENT', 'staging');
        } else {
            define('MT_ENVIRONMENT', 'production');
        }
    } elseif (function_exists('wp_get_environment_type')) {
        $wp_env = wp_get_environment_type();
        if (in_array($wp_env, ['local', 'development'])) {
            define('MT_ENVIRONMENT', 'development');
        } elseif ($wp_env === 'staging') {
            define('MT_ENVIRONMENT', 'staging');
        } else {
            define('MT_ENVIRONMENT', 'production');
        }
    } else {
        // Default to production for safety
        define('MT_ENVIRONMENT', 'production');
    }
}

// Require the autoloader
require_once MT_PLUGIN_DIR . 'includes/core/class-mt-autoloader.php';

// Register autoloader
MobilityTrailblazers\Core\MT_Autoloader::register();

// Load emergency fixes (TEMPORARY - Remove after proper fix)
if (file_exists(MT_PLUGIN_DIR . 'includes/emergency-german-fixes.php')) {
    require_once MT_PLUGIN_DIR . 'includes/emergency-german-fixes.php';
}

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

// Register WP-CLI commands
if (defined('WP_CLI') && WP_CLI) {
    require_once MT_PLUGIN_DIR . 'vendor/autoload.php';
    require_once MT_PLUGIN_DIR . 'includes/cli/class-mt-cli-commands.php';
    
    $cli_commands = new MobilityTrailblazers\CLI\MT_CLI_Commands();
    WP_CLI::add_command('mt import-candidates', [$cli_commands, 'import_candidates']);
    WP_CLI::add_command('mt db-upgrade', [$cli_commands, 'db_upgrade']);
    WP_CLI::add_command('mt list-candidates', [$cli_commands, 'list_candidates']);
} 
