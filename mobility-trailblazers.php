<?php
/**
 * Plugin Name: Mobility Trailblazers - Fixed Version
 * Plugin URI: https://mobilitytrailblazers.com
 * Description: Comprehensive award management system for mobility innovators in DACH region
 * Version: 2.5.0
 * Author: Nicolas Estrem
 * Text Domain: mobility-trailblazers
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_VERSION', '2.5.0');
define('MT_PLUGIN_FILE', __FILE__);
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_PATH', MT_PLUGIN_DIR);

// Register autoloader with improved logic
spl_autoload_register(function ($class) {
    // Only autoload our plugin's classes
    if (strpos($class, 'MobilityTrailblazers\\') !== 0) {
        return;
    }
    
    // Remove namespace prefix
    $class = str_replace('MobilityTrailblazers\\', '', $class);
    
    // Convert namespace separators to directory separators
    $class = str_replace('\\', '/', $class);
    
    // Handle different naming conventions
    $filename = strtolower(str_replace('_', '-', $class));
    
    // Try different file patterns
    $patterns = array(
        'includes/class-%s.php',
        'includes/%s.php',
        'includes/core/%s.php',
        'includes/admin/%s.php',
        'includes/frontend/%s.php',
        'admin/%s.php'
    );
    
    foreach ($patterns as $pattern) {
        $file = MT_PLUGIN_DIR . sprintf($pattern, $filename);
        if (file_exists($file)) {
            require_once $file;
            return;
        }
        
        // Also try with 'class-' prefix
        $file = MT_PLUGIN_DIR . sprintf($pattern, 'class-' . $filename);
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    // Log if class not found (only in debug mode)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("Mobility Trailblazers: Could not find class file for $class");
    }
});

/**
 * Main Plugin Class
 */
class MobilityTrailblazersPlugin {
    
    /**
     * @var MobilityTrailblazersPlugin
     */
    private static $instance = null;
    
    /**
     * @var array Loaded components
     */
    private $components = array();
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Load core files immediately
        $this->load_core_files();
        
        // Hook into WordPress
        add_action('plugins_loaded', array($this, 'init'), 10);
        add_action('init', array($this, 'wp_init'), 5);
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            add_action('admin_menu', array($this, 'admin_menu'), 9);
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // Activation/Deactivation hooks
        register_activation_hook(MT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(MT_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Check for database updates
        add_action('admin_init', array($this, 'check_database_updates'));
    }
    
    /**
     * Load core files that must be available immediately
     */
    private function load_core_files() {
        // Load utility functions
        $this->require_file('includes/mt-utility-functions.php');
        
        // Load core classes in specific order
        $core_files = array(
            'includes/class-database.php',
            'includes/class-database-updater.php',
            'includes/class-roles.php',
            'includes/class-taxonomies.php',
            'includes/class-post-types.php',
            'includes/class-vote-audit-logger.php',
            'includes/class-vote-backup-manager.php',
            'includes/class-vote-reset-manager.php'
        );
        
        foreach ($core_files as $file) {
            $this->require_file($file);
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('mobility-trailblazers', false, dirname(MT_PLUGIN_BASENAME) . '/languages');
        
        // Load remaining components
        $this->load_components();
        
        // Initialize components
        $this->init_components();
        
        // Load AJAX handlers
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $this->load_ajax_handlers();
        }
        
        // Load REST API
        if (class_exists('\MobilityTrailblazers\RestAPI')) {
            $this->components['rest_api'] = new \MobilityTrailblazers\RestAPI();
        }
    }
    
    /**
     * WordPress init hook
     */
    public function wp_init() {
        // Register post types
        if (class_exists('\MobilityTrailblazers\PostTypes')) {
            $post_types = new \MobilityTrailblazers\PostTypes();
            $post_types->register_post_types();
        }
        
        // Register taxonomies
        if (class_exists('\MobilityTrailblazers\Taxonomies')) {
            $taxonomies = new \MobilityTrailblazers\Taxonomies();
            $taxonomies->register_taxonomies();
        }
        
        // Register shortcodes
        if (class_exists('\MobilityTrailblazers\Shortcodes')) {
            $this->components['shortcodes'] = new \MobilityTrailblazers\Shortcodes();
        }
    }
    
    /**
     * Load components
     */
    private function load_components() {
        $component_files = array(
            'includes/class-mt-shortcodes.php',
            'includes/class-mt-meta-boxes.php',
            'includes/class-mt-admin-menus.php',
            'includes/class-mt-ajax-handlers.php',
            'includes/class-mt-rest-api.php',
            'includes/class-mt-jury-system.php',
            'includes/class-mt-diagnostic.php',
            'includes/class-jury-sync.php',
            'includes/class-mt-elementor-compat.php'
        );
        
        foreach ($component_files as $file) {
            $this->require_file($file);
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize meta boxes
        if (class_exists('\MobilityTrailblazers\MetaBoxes')) {
            $this->components['meta_boxes'] = new \MobilityTrailblazers\MetaBoxes();
        }
        
        // Initialize jury system
        if (class_exists('\MobilityTrailblazers\JurySystem')) {
            $this->components['jury_system'] = new \MobilityTrailblazers\JurySystem();
        }
        
        // Initialize jury sync
        if (class_exists('\MobilityTrailblazers\JurySync')) {
            $this->components['jury_sync'] = \MobilityTrailblazers\JurySync::get_instance();
        }
        
        // Initialize Elementor compatibility
        if (did_action('elementor/loaded')) {
            if (class_exists('\MobilityTrailblazers\ElementorCompat')) {
                $this->components['elementor'] = new \MobilityTrailblazers\ElementorCompat();
            }
        }
    }
    
    /**
     * Load AJAX handlers
     */
    private function load_ajax_handlers() {
        if (class_exists('\MobilityTrailblazers\AjaxHandlers')) {
            $this->components['ajax_handlers'] = \MobilityTrailblazers\AjaxHandlers::get_instance();
        }
    }
    
    /**
     * Admin menu
     */
    public function admin_menu() {
        if (class_exists('\MobilityTrailblazers\AdminMenus')) {
            $this->components['admin_menus'] = new \MobilityTrailblazers\AdminMenus();
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Global admin styles
        wp_enqueue_style(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MT_VERSION
        );
        
        // Global admin scripts
        wp_enqueue_script(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-admin', 'mt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_admin_nonce'),
            'i18n' => array(
                'confirm_reset' => __('Are you sure you want to reset? This action cannot be undone.', 'mobility-trailblazers'),
                'processing' => __('Processing...', 'mobility-trailblazers'),
                'success' => __('Success!', 'mobility-trailblazers'),
                'error' => __('An error occurred.', 'mobility-trailblazers')
            )
        ));
        
        // Page-specific assets
        $this->enqueue_page_specific_assets($hook);
    }
    
    /**
     * Enqueue page-specific assets
     */
    private function enqueue_page_specific_assets($hook) {
        // Assignment page assets
        if (strpos($hook, 'mt-assignment') !== false) {
            wp_enqueue_style(
                'mt-assignment',
                MT_PLUGIN_URL . 'assets/css/assignment.css',
                array(),
                MT_VERSION
            );
            
            wp_enqueue_script(
                'mt-assignment',
                MT_PLUGIN_URL . 'assets/js/assignment.js',
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
                MT_VERSION,
                true
            );
        }
        
        // Dashboard page assets
        if (strpos($hook, 'mt-dashboard') !== false) {
            wp_enqueue_script(
                'mt-dashboard',
                MT_PLUGIN_URL . 'assets/js/dashboard.js',
                array('jquery'),
                MT_VERSION,
                true
            );
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        // Frontend styles
        wp_enqueue_style(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            MT_VERSION
        );
        
        // Frontend scripts
        wp_enqueue_script(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-frontend', 'mt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_frontend_nonce'),
            'dashboard_url' => home_url('/jury-dashboard/')
        ));
    }
    
    /**
     * Check and run database updates
     */
    public function check_database_updates() {
        if (class_exists('\MobilityTrailblazers\DatabaseUpdater')) {
            \MobilityTrailblazers\DatabaseUpdater::run_updates();
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create/update database tables
        if (class_exists('\MobilityTrailblazers\Database')) {
            \MobilityTrailblazers\Database::create_tables();
        }
        
        // Run database updates
        if (class_exists('\MobilityTrailblazers\DatabaseUpdater')) {
            \MobilityTrailblazers\DatabaseUpdater::run_updates();
        }
        
        // Create roles
        if (class_exists('\MobilityTrailblazers\Roles')) {
            \MobilityTrailblazers\Roles::create_roles();
        }
        
        // Register post types and taxonomies for rewrite rules
        $this->wp_init();
        
        // Create default terms
        if (class_exists('\MobilityTrailblazers\Taxonomies')) {
            \MobilityTrailblazers\Taxonomies::create_default_terms();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('mt_plugin_activated', true);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Remove roles
        if (class_exists('\MobilityTrailblazers\Roles')) {
            \MobilityTrailblazers\Roles::remove_roles();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('mt_daily_cleanup');
        
        // Remove activation flag
        delete_option('mt_plugin_activated');
    }
    
    /**
     * Safely require a file
     */
    private function require_file($file) {
        $full_path = MT_PLUGIN_DIR . $file;
        if (file_exists($full_path)) {
            require_once $full_path;
            return true;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Mobility Trailblazers: Missing file $file");
        }
        
        return false;
    }
    
    /**
     * Get component instance
     */
    public function get_component($name) {
        return isset($this->components[$name]) ? $this->components[$name] : null;
    }
}

// Initialize plugin
function mobility_trailblazers() {
    return MobilityTrailblazersPlugin::get_instance();
}

// Start the plugin
mobility_trailblazers();

?>