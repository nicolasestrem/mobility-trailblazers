<?php
namespace MobilityTrailblazers;

use MobilityTrailblazers\Integrations\IntegrationsLoader;

/**
 * Plugin Name: Mobility Trailblazers Award System
 * Plugin URI: https://mobilitytrailblazers.de
 * Description: Complete award management system for 25 Mobility Trailblazers in 25 - managing candidates, jury members, voting process, and public engagement.
 * Version: 0.2.1
 * Author: Mobility Trailblazers Team
 * License: GPL v2 or later
 * Text Domain: mobility-trailblazers
 */

// Suppress PHP 8.2 deprecation warnings for this plugin
if (version_compare(PHP_VERSION, '8.0', '>=')) {
    error_reporting(error_reporting() & ~E_DEPRECATED);
}

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_PLUGIN_VERSION', '1.0.0');
define('MT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Define the plugin file constant if not already defined
if (!defined('MT_PLUGIN_FILE')) {
    define('MT_PLUGIN_FILE', __FILE__);
}

/**
 * Autoloader for plugin classes
 */
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'MobilityTrailblazers\\';
    
    // Base directory for the namespace prefix
    $base_dir = MT_PLUGIN_PATH . 'includes/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Split the class name into parts
    $parts = explode('\\', $relative_class);
    
    // The last part is the class name
    $class_name = array_pop($parts);
    
    // Convert class name to file name format
    // First, split the class name into words (e.g., IntegrationsLoader -> Integrations Loader)
    $words = preg_split('/(?=[A-Z])/', $class_name, -1, PREG_SPLIT_NO_EMPTY);
    // Then convert to lowercase and join with hyphens
    $file_name = 'class-' . strtolower(implode('-', $words)) . '.php';
    
    // Build the directory path from namespace parts
    $dir_path = '';
    if (!empty($parts)) {
        $dir_path = strtolower(implode('/', $parts)) . '/';
    }
    
    // Build the full path
    $file = $base_dir . $dir_path . $file_name;
    
    // Debug logging
    error_log("Mobility Trailblazers: Autoloader trying to load class: $class");
    error_log("Mobility Trailblazers: Autoloader looking for file: $file");
    error_log("Mobility Trailblazers: MT_PLUGIN_PATH is: " . MT_PLUGIN_PATH);
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
        error_log("Mobility Trailblazers: Successfully loaded file: $file");
    } else {
        error_log("Mobility Trailblazers: Autoloader could not find file: $file");
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
     * @var \MobilityTrailblazers\Core\Evaluation
     */
    private $evaluation;
    
    /**
     * @var \MobilityTrailblazers\Core\JuryMember
     */
    private $jury_member;
    
    /**
     * @var \MobilityTrailblazers\Core\Candidate
     */
    private $candidate;
    
    /**
     * @var \MobilityTrailblazers\Core\Statistics
     */
    private $statistics;
    
    /**
     * @var \MobilityTrailblazers\Database
     */
    private $database;
    
    /**
     * @var \MobilityTrailblazers\Roles
     */
    private $roles;
    
    /**
     * @var \MobilityTrailblazers\Taxonomies
     */
    private $taxonomies;
    
    /**
     * @var \MobilityTrailblazers\PostTypes
     */
    private $post_types;
    
    /**
     * @var \MobilityTrailblazers\Shortcodes
     */
    private $shortcode_handler;
    
    /**
     * @var \MobilityTrailblazers\Admin
     */
    private $admin;
    
    /**
     * @var \MobilityTrailblazers\Frontend
     */
    private $frontend;
    
    /**
     * @var \MobilityTrailblazers\Diagnostic
     */
    private $diagnostic;
    
    /**
     * @var \MobilityTrailblazers\Integrations\IntegrationsLoader
     */
    private $integrations_loader;
    
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
     * Constructor - Initialize the plugin
     */
    private function __construct() {
        // Define plugin constants
        $this->define_constants();
        
        // Load core dependencies immediately
        $this->load_core_dependencies();
        
        // Hook into WordPress
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'load_textdomain'));
        
        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // Activation/Deactivation hooks
        register_activation_hook(MT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(MT_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Load Elementor compatibility
        add_action('plugins_loaded', array($this, 'load_elementor_compatibility'));
        
        // Load integrations
        $this->load_integrations();
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
    }
    
    /**
     * Define plugin constants
     */
    private function define_constants() {
        // Constants are already defined at the top of the file
        // This method is here for consistency and future expansion
    }
    
    /**
     * Load core dependencies that need to be available immediately
     */
    private function load_core_dependencies() {
        // Load utility functions first
        $this->safe_require(MT_PLUGIN_PATH . 'includes/mt-utility-functions.php');
        
        // Load database handler
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-database.php');
        
        // Load roles handler
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-roles.php');
        
        // Load taxonomies handler
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-taxonomies.php');
        
        // Load post types handler
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-post-types.php');

        // Load core classes
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-evaluation.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-jury-member.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-candidate.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-statistics.php');

        // Initialize core classes
        $this->evaluation = new \MobilityTrailblazers\Core\Evaluation();
        $this->jury_member = new \MobilityTrailblazers\Core\JuryMember();
        $this->candidate = new \MobilityTrailblazers\Core\Candidate();
        $this->statistics = new \MobilityTrailblazers\Core\Statistics();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load all plugin components
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Update database tables if needed
        $this->maybe_update_database();
        
        // Add custom image sizes
        add_image_size('candidate-thumbnail', 300, 300, true);
        add_image_size('candidate-full', 800, 600, true);
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Load utility functions first
        $this->safe_require(MT_PLUGIN_PATH . 'includes/mt-utility-functions.php');

        // Load core classes
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-database.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-roles.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-taxonomies.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-post-types.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-shortcodes.php');

        // Load core functionality
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-evaluation.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-jury-member.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-candidate.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/core/class-statistics.php');

        // Load admin classes if in admin area
        if (is_admin()) {
            $this->safe_require(MT_PLUGIN_PATH . 'includes/class-admin.php');
            $this->safe_require(MT_PLUGIN_PATH . 'includes/class-diagnostic.php');
        }

        // Load frontend classes
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-frontend.php');

        // Load integrations
        $this->safe_require(MT_PLUGIN_PATH . 'includes/integrations/class-integrations-loader.php');

        // Initialize core classes with proper namespaces
        if (class_exists('\MobilityTrailblazers\Database')) {
            $this->database = new \MobilityTrailblazers\Database();
        }
        if (class_exists('\MobilityTrailblazers\Roles')) {
            $this->roles = new \MobilityTrailblazers\Roles();
        }
        if (class_exists('\MobilityTrailblazers\Taxonomies')) {
            $this->taxonomies = new \MobilityTrailblazers\Taxonomies();
        }
        if (class_exists('\MobilityTrailblazers\PostTypes')) {
            $this->post_types = new \MobilityTrailblazers\PostTypes();
        }
        if (class_exists('\MobilityTrailblazers\Shortcodes')) {
            $this->shortcode_handler = new \MobilityTrailblazers\Shortcodes();
        }
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize core components
        $this->init_class('\MobilityTrailblazers\Shortcodes');
        $this->init_class('\MobilityTrailblazers\Admin');
        $this->init_class('\MobilityTrailblazers\Frontend');
        $this->init_class('\MobilityTrailblazers\Integrations\IntegrationsLoader');
        $this->init_class('\MobilityTrailblazers\Diagnostic');
        $this->init_class('\MobilityTrailblazers\Database');
        $this->init_class('\MobilityTrailblazers\Roles');
        $this->init_class('\MobilityTrailblazers\Taxonomies');
        $this->init_class('\MobilityTrailblazers\PostTypes');
    }
    
    /**
     * Helper function to safely initialize classes whether they use singleton or not
     */
    private function init_class($class_name) {
        if (!class_exists($class_name)) {
            error_log("Mobility Trailblazers: Class $class_name not found during initialization");
            return false;
        }
        
        try {
            // Check if class uses singleton pattern
            if (method_exists($class_name, 'get_instance')) {
                return $class_name::get_instance();
            } else {
                return new $class_name();
            }
        } catch (Exception $e) {
            error_log("Mobility Trailblazers: Error initializing $class_name: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Safely require a file if it exists
     */
    private function safe_require($file) {
        if (file_exists($file)) {
            require_once $file;
        } else {
            error_log("Mobility Trailblazers: Missing file $file");
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Load core dependencies first
        $this->load_core_dependencies();
        
        // Load and register post types first
        if (class_exists('\MobilityTrailblazers\PostTypes')) {
            $post_types = new \MobilityTrailblazers\PostTypes();
            $post_types->register_post_types();
        } else {
            error_log('Mobility Trailblazers: PostTypes class not found during activation');
        }
        
        // Load and register taxonomies
        if (class_exists('\MobilityTrailblazers\Taxonomies')) {
            $taxonomies = new \MobilityTrailblazers\Taxonomies();
            $taxonomies->register_taxonomies();
        } else {
            error_log('Mobility Trailblazers: Taxonomies class not found during activation');
        }
        
        // Create database tables
        if (class_exists('\MobilityTrailblazers\Database')) {
            \MobilityTrailblazers\Database::create_tables();
        } else {
            error_log('Mobility Trailblazers: Database class not found during activation');
        }
        
        // Create roles
        if (class_exists('\MobilityTrailblazers\Roles')) {
            \MobilityTrailblazers\Roles::create_roles();
        } else {
            error_log('Mobility Trailblazers: Roles class not found during activation');
        }
        
        // Create default terms
        if (class_exists('\MobilityTrailblazers\Taxonomies')) {
            \MobilityTrailblazers\Taxonomies::create_default_terms();
        } else {
            error_log('Mobility Trailblazers: Taxonomies class not found during activation');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Load core dependencies first
        $this->load_core_dependencies();
        
        // Remove roles
        if (class_exists('\MobilityTrailblazers\Roles')) {
            \MobilityTrailblazers\Roles::remove_roles();
        } else {
            error_log('Mobility Trailblazers: Roles class not found during deactivation');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('mobility-trailblazers', false, dirname(MT_PLUGIN_BASENAME) . '/languages/');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'mobility-trailblazers') === false) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'mt-admin-css',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MT_PLUGIN_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'mt-admin-js',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );

        // Enqueue assignment interface scripts and styles
        if (strpos($hook, 'mt-assignments') !== false) {
            wp_enqueue_style(
                'mt-assignment-css',
                MT_PLUGIN_URL . 'assets/css/assignment.css',
                array(),
                MT_PLUGIN_VERSION
            );

            wp_enqueue_script(
                'mt-assignment-js',
                MT_PLUGIN_URL . 'assets/js/assignment.js',
                array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
                MT_PLUGIN_VERSION,
                true
            );

            // Localize script with data
            wp_localize_script('mt-assignment-js', 'mt_assignment_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_assignment_nonce'),
                'candidates' => $this->get_candidates_for_js(),
                'jury_members' => $this->get_jury_members_for_js()
            ));
        }

        // Enqueue dashboard scripts
        if (strpos($hook, 'mt-dashboard') !== false) {
            wp_enqueue_script(
                'mt-dashboard-js',
                MT_PLUGIN_URL . 'assets/js/dashboard.js',
                array('jquery'),
                MT_PLUGIN_VERSION,
                true
            );
        }

        // Localize admin script
        wp_localize_script('mt-admin-js', 'mtAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_admin_nonce')
        ));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        // Enqueue frontend styles
        wp_enqueue_style(
            'mt-frontend-css',
            MT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            MT_PLUGIN_VERSION
        );

        // Enqueue frontend scripts
        wp_enqueue_script(
            'mt-frontend-js',
            MT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );

        // Localize frontend script
        wp_localize_script('mt-frontend-js', 'mtFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_frontend_nonce')
        ));
    }
    
    /**
     * Load Elementor compatibility
     */
    public function load_elementor_compatibility() {
        $elementor_compat_file = MT_PLUGIN_PATH . 'includes/integrations/elementor/class-elementor-compat.php';
        if (file_exists($elementor_compat_file)) {
            require_once $elementor_compat_file;
        }
    }
    
    /**
     * Maybe update database tables
     */
    private function maybe_update_database() {
        $db_version = get_option('mt_db_version', '1.0.0');
        $current_version = MT_PLUGIN_VERSION;
        
        // Check if we need to update the database
        if (version_compare($db_version, $current_version, '<')) {
            // Update tables for vote reset functionality
            if (class_exists('MT_Database')) {
                MT_Database::update_tables_for_reset();
            }
            
            // Update the database version
            update_option('mt_db_version', $current_version);
        }
    }
    
    /**
     * Load integrations
     */
    private function load_integrations() {
        $this->integrations_loader = \MobilityTrailblazers\Integrations\IntegrationsLoader::get_instance();
    }
    
    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_mt_submit_vote', array($this, 'handle_vote_submission'));
        add_action('wp_ajax_nopriv_mt_submit_vote', array($this, 'handle_vote_submission'));
    }
    
    /**
     * Handle vote submission
     */
    public function handle_vote_submission() {
        // Verify nonce
        check_ajax_referer('mt_vote_nonce', 'mt_vote_nonce');
        
        // Get and sanitize data
        $candidate_id = intval($_POST['candidate_id']);
        $vote_type = sanitize_text_field($_POST['vote_type']);
        $comments = sanitize_textarea_field($_POST['comments']);
        $criteria = isset($_POST['criteria']) ? array_map('intval', $_POST['criteria']) : array();
        
        // Validate required fields
        if (!$candidate_id || !$vote_type) {
            wp_send_json_error(array(
                'message' => __('Missing required fields.', 'mobility-trailblazers')
            ));
        }
        
        // Check if user has already voted
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            if ($this->has_user_voted($user_id, $candidate_id)) {
                wp_send_json_error(array(
                    'message' => __('You have already voted for this candidate.', 'mobility-trailblazers')
                ));
            }
        }
        
        // Process vote
        $vote_id = $this->save_vote($candidate_id, $vote_type, $criteria, $comments);
        
        if ($vote_id) {
            wp_send_json_success(array(
                'message' => __('Vote submitted successfully!', 'mobility-trailblazers'),
                'reset_form' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to submit vote. Please try again.', 'mobility-trailblazers')
            ));
        }
    }
    
    /**
     * Check if user has already voted
     */
    private function has_user_voted($user_id, $candidate_id) {
        // Implementation depends on your voting system
        return false; // Placeholder
    }
    
    /**
     * Save vote to database
     */
    private function save_vote($candidate_id, $vote_type, $criteria, $comments) {
        // Implementation depends on your voting system
        return true; // Placeholder
    }

    /**
     * Get candidates data for JavaScript
     */
    private function get_candidates_for_js() {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $candidates_data = array();
        foreach ($candidates as $candidate) {
            $candidates_data[] = array(
                'id' => $candidate->ID,
                'name' => $candidate->post_title,
                'description' => get_post_meta($candidate->ID, 'description', true),
                'category' => wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'names')),
                'assigned' => false,
                'jury_member_id' => null
            );
        }
        
        // Update candidates with assignment info
        foreach ($candidates_data as &$candidate) {
            $assigned_jury = get_post_meta($candidate['id'], 'assigned_jury', true);
            if ($assigned_jury) {
                $candidate['assigned'] = true;
                $candidate['jury_member_id'] = $assigned_jury;
            }
        }
        
        return $candidates_data;
    }

    /**
     * Get jury members data for JavaScript
     */
    private function get_jury_members_for_js() {
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $jury_data = array();
        foreach ($jury_members as $jury) {
            $assigned_candidates = get_post_meta($jury->ID, 'assigned_candidates', true);
            $assigned_candidates = is_array($assigned_candidates) ? $assigned_candidates : array();
            
            $jury_data[] = array(
                'id' => $jury->ID,
                'name' => $jury->post_title,
                'role' => get_post_meta($jury->ID, 'role', true),
                'expertise' => get_post_meta($jury->ID, 'expertise', true),
                'max_assignments' => get_post_meta($jury->ID, 'max_assignments', true) ?: 10,
                'assigned_count' => count($assigned_candidates)
            );
        }
        
        return $jury_data;
    }
}

// Initialize the plugin
function mobility_trailblazers() {
    return MobilityTrailblazersPlugin::get_instance();
}

// Start the plugin
mobility_trailblazers();

?>