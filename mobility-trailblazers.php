<?php
/**
 * Plugin Name: Mobility Trailblazers Award System
 * Plugin URI: https://mobilitytrailblazers.de
 * Description: Comprehensive WordPress plugin for managing the prestigious "25 Mobility Trailblazers in 25" award platform
 * Version: 1.0.2
 * Author: Nicolas Estrém
 * Author URI: https://mobilitytrailblazers.de
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_PLUGIN_VERSION', '1.0.2');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class using Singleton pattern
 */
class MobilityTrailblazersPlugin {
    /**
     * Single instance of the class
     *
     * @var MobilityTrailblazersPlugin
     */
    private static $instance = null;

    /**
     * Component instances
     *
     * @var array
     */
    private $components = array();

    /**
     * Get single instance of the class
     *
     * @return MobilityTrailblazersPlugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize the plugin
     */
    private function __construct() {
        // Load core dependencies immediately
        $this->load_core_dependencies();
        
        // Hook into plugins_loaded for remaining initialization
        add_action('plugins_loaded', array($this, 'init_plugin'));
        
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
    }

    /**
     * Load core dependencies that need to be available immediately
     */
    private function load_core_dependencies() {
        // Load database class first as it's needed for activation
        require_once MT_PLUGIN_DIR . 'includes/class-database.php';
        $this->components['database'] = new MT_Database();
        
        // Load other core classes
        require_once MT_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once MT_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once MT_PLUGIN_DIR . 'includes/class-roles.php';
    }

    /**
     * Initialize plugin components
     */
    public function init_plugin() {
        // Load remaining dependencies
        $this->load_dependencies();
        
        // Check and fix database if needed
        $this->ensure_database_tables();
        
        // Initialize components
        $this->init_components();
        
        // Hook into WordPress
        $this->setup_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Utility functions
        require_once MT_PLUGIN_DIR . 'includes/mt-utility-functions.php';
        require_once MT_PLUGIN_DIR . 'includes/mt-debug-functions.php';
        
        // Core functionality classes
        require_once MT_PLUGIN_DIR . 'includes/class-mt-shortcodes.php';
        require_once MT_PLUGIN_DIR . 'includes/class-mt-meta-boxes.php';
        require_once MT_PLUGIN_DIR . 'includes/class-mt-admin-menus.php';
        require_once MT_PLUGIN_DIR . 'includes/class-mt-ajax-handlers.php';
        require_once MT_PLUGIN_DIR . 'includes/class-mt-rest-api.php';
        require_once MT_PLUGIN_DIR . 'includes/class-mt-jury-system.php';
        require_once MT_PLUGIN_DIR . 'includes/class-mt-diagnostic.php';
        
        // Elementor integration
        if (did_action('elementor/loaded')) {
            require_once MT_PLUGIN_DIR . 'includes/elementor/class-mt-elementor-integration.php';
        }
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize post types and taxonomies
        $this->components['post_types'] = new MT_Post_Types();
        $this->components['taxonomies'] = new MT_Taxonomies();
        $this->components['roles'] = new MT_Roles();
        
        // Initialize functionality components
        $this->components['shortcodes'] = new MT_Shortcodes();
        $this->components['meta_boxes'] = new MT_Meta_Boxes();
        $this->components['admin_menus'] = new MT_Admin_Menus();
        $this->components['ajax_handlers'] = new MT_AJAX_Handlers();
        $this->components['rest_api'] = new MT_REST_API();
        $this->components['jury_system'] = new MT_Jury_System();
        $this->components['diagnostic'] = new MT_Diagnostic();
        
        // Initialize Elementor integration if available
        if (did_action('elementor/loaded')) {
            require_once MT_PLUGIN_DIR . 'includes/elementor/class-mt-elementor-integration.php';
            $this->components['elementor'] = new MT_Elementor_Integration();
        }
    }

    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Elementor compatibility
        add_action('elementor/init', array($this, 'init_elementor_compatibility'));
        
        // Plugin action links
        add_filter('plugin_action_links_' . MT_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mobility-trailblazers',
            false,
            dirname(MT_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets($hook) {
        // Global admin styles
        wp_enqueue_style(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/admin.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        // Global admin scripts
        wp_enqueue_script(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-admin', 'mt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_admin_nonce'),
            'i18n' => array(
                'confirm_delete' => __('Are you sure you want to delete this item?', 'mobility-trailblazers'),
                'processing' => __('Processing...', 'mobility-trailblazers'),
                'error' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
            ),
        ));
        
        // Page-specific assets
        if (strpos($hook, 'mt-') !== false) {
            // Assignment page assets
            if (strpos($hook, 'assignment') !== false) {
                wp_enqueue_style(
                    'mt-assignment',
                    MT_PLUGIN_URL . 'assets/assignment.css',
                    array(),
                    MT_PLUGIN_VERSION
                );
                
                wp_enqueue_script(
                    'mt-assignment',
                    MT_PLUGIN_URL . 'assets/assignment.js',
                    array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
                    MT_PLUGIN_VERSION,
                    true
                );

                // Localize assignment script
                wp_localize_script('mt-assignment', 'mt_assignment_vars', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mt_admin_nonce'),
                    'i18n' => array(
                        'confirm_clear' => __('Are you sure you want to clear all assignments? This action cannot be undone.', 'mobility-trailblazers'),
                        'confirm_auto_assign' => __('This will automatically assign candidates to jury members. Continue?', 'mobility-trailblazers'),
                        'processing' => __('Processing...', 'mobility-trailblazers'),
                        'error' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                        'success' => __('Operation completed successfully.', 'mobility-trailblazers'),
                        'export_started' => __('Export started. Download will begin shortly.', 'mobility-trailblazers'),
                        'confirm_delete' => __('Are you sure you want to delete this assignment?', 'mobility-trailblazers'),
                        'no_candidates' => __('No candidates available for assignment.', 'mobility-trailblazers'),
                        'no_jury' => __('No jury members available for assignment.', 'mobility-trailblazers'),
                        'assignment_saved' => __('Assignment saved successfully.', 'mobility-trailblazers'),
                        'assignment_failed' => __('Failed to save assignment. Please try again.', 'mobility-trailblazers')
                    )
                ));
            }
            
            // Dashboard assets
            if (strpos($hook, 'dashboard') !== false) {
                wp_enqueue_style(
                    'mt-jury-dashboard',
                    MT_PLUGIN_URL . 'assets/jury-dashboard.css',
                    array(),
                    MT_PLUGIN_VERSION
                );
                
                wp_enqueue_script(
                    'mt-dashboard',
                    MT_PLUGIN_URL . 'assets/jury-dashboard.js',
                    array('jquery'),
                    MT_PLUGIN_VERSION,
                    true
                );
            }
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/frontend.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        wp_enqueue_script(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/frontend.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-frontend', 'mt_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_frontend_nonce'),
        ));
    }

    /**
     * Initialize Elementor compatibility
     */
    public function init_elementor_compatibility() {
        wp_enqueue_script(
            'mt-elementor-compat',
            MT_PLUGIN_URL . 'assets/elementor-compat.js',
            array('elementor-frontend'),
            MT_PLUGIN_VERSION,
            true
        );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_action_links($links) {
        $action_links = array(
            '<a href="' . admin_url('admin.php?page=mt-settings') . '">' . __('Settings', 'mobility-trailblazers') . '</a>',
            '<a href="' . admin_url('admin.php?page=mt-diagnostic') . '">' . __('Diagnostic', 'mobility-trailblazers') . '</a>',
        );
        
        return array_merge($action_links, $links);
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        if (isset($this->components['database'])) {
            $this->components['database']->create_tables();
        }
        
        // Create roles and capabilities
        MT_Roles::create_roles();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Fire activation hook
        do_action('mt_plugin_activated');
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Fire deactivation hook
        do_action('mt_plugin_deactivated');
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        // General settings
        add_option('mt_current_award_year', date('Y'));
        add_option('mt_current_phase', 'nomination');
        add_option('mt_public_voting_enabled', false);
        add_option('mt_registration_open', true);
        
        // Evaluation settings
        add_option('mt_min_evaluations_required', 3);
        add_option('mt_evaluation_deadline', date('Y-m-d', strtotime('+30 days')));
        add_option('mt_auto_reminders_enabled', true);
        
        // Email settings
        add_option('mt_email_from_name', get_bloginfo('name'));
        add_option('mt_email_from_address', get_option('admin_email'));
        
        // Display settings
        add_option('mt_candidates_per_page', 20);
        add_option('mt_date_format', get_option('date_format'));
    }

    /**
     * Ensure database tables exist
     */
    private function ensure_database_tables() {
        global $wpdb;
        
        // Check if critical tables exist
        $critical_tables = array(
            $wpdb->prefix . 'mt_jury_assignments',
            $wpdb->prefix . 'mt_evaluations'
        );
        
        $missing_tables = false;
        foreach ($critical_tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                $missing_tables = true;
                break;
            }
        }
        
        // If any critical tables are missing, force create them
        if ($missing_tables) {
            if (isset($this->components['database'])) {
                $this->components['database']->force_create_tables();
            }
        }
    }

    /**
     * Get component instance
     *
     * @param string $component Component name
     * @return object|null Component instance or null
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }
}

// Initialize the plugin
MobilityTrailblazersPlugin::get_instance(); 