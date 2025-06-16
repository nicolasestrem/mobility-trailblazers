<?php
namespace MobilityTrailblazers;

use MobilityTrailblazers\Integrations\IntegrationsLoader;

/**
 * Plugin Name: Mobility Trailblazers Award System
 * Plugin URI: https://mobilitytrailblazers.de
 * Description: Complete award management system for 25 Mobility Trailblazers in 25 - managing candidates, jury members, voting process, and public engagement.
 * Version: 2.0.0
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
        
        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'register_assets'), 5);
        add_action('admin_enqueue_scripts', array($this, 'register_assets'), 5);
        
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
        
        // Load vote management classes (needed for audit logging)
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-vote-audit-logger.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-vote-backup-manager.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-vote-reset-manager.php');
        
        // Load jury synchronization class
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-jury-sync.php');

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
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        $this->load_textdomain();
        
        // Register admin menu
        // add_action('admin_menu', array($this, 'register_admin_menu'));
        
        // Register AJAX handlers
        $this->register_ajax_handlers();
        
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Load integrations
        $this->load_integrations();
        
        // Maybe update database
        $this->maybe_update_database();
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
        
        // Load vote management classes
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-vote-audit-logger.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-vote-backup-manager.php');
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-vote-reset-manager.php');

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
        $this->init_class('\MobilityTrailblazers\JurySync');
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
     * Register all plugin assets
     */
    public function register_assets() {
        // Register CSS files
        wp_register_style(
            'mt-frontend-css',
            MT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        wp_register_style(
            'mt-admin-css',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        wp_register_style(
            'mt-assignment-css',
            MT_PLUGIN_URL . 'assets/css/assignment.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        wp_register_style(
            'mt-jury-dashboard',
            MT_PLUGIN_URL . 'assets/css/jury-dashboard.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        // Register JavaScript files
        wp_register_script(
            'mt-frontend-js',
            MT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
        
        wp_register_script(
            'mt-admin-js',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
        
        wp_register_script(
            'mt-assignment-js',
            MT_PLUGIN_URL . 'assets/js/assignment.js',
            array('jquery', 'jquery-ui-draggable', 'jquery-ui-droppable'),
            MT_PLUGIN_VERSION,
            true
        );
        
        wp_register_script(
            'mt-dashboard-js',
            MT_PLUGIN_URL . 'assets/js/dashboard.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
        
        wp_register_script(
            'mt-jury-evaluation',
            MT_PLUGIN_URL . 'assets/js/jury-evaluation.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'mobility-trailblazers') === false) {
            return;
        }

        // Enqueue admin styles and scripts
        wp_enqueue_style('mt-admin-css');
        wp_enqueue_script('mt-admin-js');

        // Enqueue assignment interface scripts and styles
        if (strpos($hook, 'mt-assignments') !== false) {
            wp_enqueue_style('mt-assignment-css');
            wp_enqueue_script('mt-assignment-js');

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
            wp_enqueue_script('mt-dashboard-js');
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
        // Enqueue frontend styles and scripts
        wp_enqueue_style('mt-frontend-css');
        wp_enqueue_script('mt-frontend-js');

        // Localize frontend script
        wp_localize_script('mt-frontend-js', 'mtFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_frontend_nonce')
        ));
        
        // Check if we're on a page that needs jury evaluation functionality
        if ($this->is_jury_dashboard_page()) {
            // Enqueue jury dashboard styles and scripts
            wp_enqueue_style('mt-jury-dashboard');
            wp_enqueue_script('mt-jury-evaluation');
            
            // Localize jury evaluation script
            wp_localize_script('mt-jury-evaluation', 'mt_jury_dashboard', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_jury_dashboard'),
                'i18n' => array(
                    'loading_evaluation' => __('Loading evaluation...', 'mobility-trailblazers'),
                    'evaluation_loaded' => __('Evaluation loaded successfully', 'mobility-trailblazers'),
                    'error_loading' => __('Error loading evaluation', 'mobility-trailblazers'),
                    'submitting' => __('Submitting evaluation...', 'mobility-trailblazers'),
                    'submit_evaluation' => __('Submit Evaluation', 'mobility-trailblazers'),
                    'evaluation_submitted' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                    'error_submitting' => __('Error submitting evaluation', 'mobility-trailblazers'),
                    'network_error' => __('Network error. Please try again.', 'mobility-trailblazers'),
                    'evaluated' => __('Evaluated', 'mobility-trailblazers'),
                    'please_rate_all' => __('Please rate all criteria before submitting', 'mobility-trailblazers'),
                    'saving' => __('Saving draft...', 'mobility-trailblazers'),
                    'save_draft' => __('Save as Draft', 'mobility-trailblazers'),
                    'draft_saved' => __('Draft saved successfully!', 'mobility-trailblazers'),
                    'error_saving' => __('Error saving draft', 'mobility-trailblazers'),
                    'all_complete' => __('Congratulations! You have completed all evaluations!', 'mobility-trailblazers'),
                    'preparing_export' => __('Preparing export...', 'mobility-trailblazers'),
                    'export_complete' => __('Export ready! Download will start shortly.', 'mobility-trailblazers'),
                    'export_error' => __('Error preparing export', 'mobility-trailblazers'),
                    'unsaved_changes' => __('You have unsaved changes. Are you sure you want to leave?', 'mobility-trailblazers'),
                    'confirm_submit' => __('Are you sure you want to submit this evaluation?', 'mobility-trailblazers'),
                    'confirm_export' => __('Are you sure you want to export your evaluations?', 'mobility-trailblazers')
                )
            ));
        }
    }
    
    /**
     * Check if current page needs jury dashboard functionality
     */
    private function is_jury_dashboard_page() {
        global $post;
        
        // Check if we're on a page with jury dashboard shortcode
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'mt_jury_dashboard')) {
            return true;
        }
        
        // Check if current user is a jury member (for Elementor widgets or other dynamic content)
        if (is_user_logged_in()) {
            $current_user_id = get_current_user_id();
            $jury_post = get_posts(array(
                'post_type' => 'mt_jury',
                'meta_query' => array(
                    array(
                        'key' => '_mt_jury_user_id',
                        'value' => $current_user_id,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1
            ));
            
            if (!empty($jury_post)) {
                return true;
            }
        }
        
        return false;
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
            if (class_exists('\MobilityTrailblazers\Database')) {
                \MobilityTrailblazers\Database::update_tables_for_reset();
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
        // Existing vote handlers
        add_action('wp_ajax_mt_submit_vote', array($this, 'handle_vote_submission'));
        add_action('wp_ajax_nopriv_mt_submit_vote', array($this, 'handle_vote_submission'));

        // Assignment handlers
        add_action('wp_ajax_mt_assign_candidates', array($this, 'handle_candidate_assignment'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'handle_auto_assignment'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'handle_export_assignments'));
        
        // Jury evaluation handlers
        add_action('wp_ajax_mt_submit_evaluation', array($this, 'handle_evaluation_submission'));
        add_action('wp_ajax_mt_save_draft', array($this, 'handle_draft_save'));
        add_action('wp_ajax_mt_get_evaluation', array($this, 'handle_get_evaluation'));
        add_action('wp_ajax_mt_export_evaluations', array($this, 'handle_export_evaluations'));
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

    /**
     * Handle candidate assignment
     */
    public function handle_candidate_assignment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_assignment_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $candidate_ids = array_map('intval', $_POST['candidate_ids']);
        $jury_member_id = intval($_POST['jury_member_id']);
        
        // Validate jury member exists
        if (!get_post($jury_member_id)) {
            wp_send_json_error(array('message' => 'Invalid jury member'));
        }
        
        // Update assignments
        $success_count = 0;
        foreach ($candidate_ids as $candidate_id) {
            if (get_post($candidate_id)) {
                update_post_meta($candidate_id, '_mt_assigned_jury_member', $jury_member_id);
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d candidates assigned successfully', $success_count)
        ));
    }

    /**
     * Handle auto-assignment
     */
    public function handle_auto_assignment() {
        if (!wp_verify_nonce($_POST['nonce'], 'mt_assignment_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $candidates_per_jury = intval($_POST['candidates_per_jury']);
        $algorithm = sanitize_text_field($_POST['algorithm']);
        $clear_existing = $_POST['clear_existing'] === 'true';
        
        // Validate input
        if ($candidates_per_jury < 1) {
            wp_send_json_error(array('message' => 'Invalid candidates per jury value'));
        }
        
        // Clear existing if requested
        if ($clear_existing) {
            $candidates = get_posts(array(
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1
            ));
            foreach ($candidates as $candidate) {
                delete_post_meta($candidate->ID, '_mt_assigned_jury_member');
            }
        }
        
        // Get unassigned candidates
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_member',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        // Get available jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1
        ));
        
        if (empty($jury_members)) {
            wp_send_json_error(array('message' => 'No jury members available'));
        }
        
        // Implement assignment based on algorithm
        $assignments_count = array();
        $success_count = 0;
        
        switch ($algorithm) {
            case 'balanced':
                $jury_index = 0;
                foreach ($candidates as $candidate) {
                    $jury_member = $jury_members[$jury_index];
                    $jury_id = $jury_member->ID;
                    
                    if (!isset($assignments_count[$jury_id])) {
                        $assignments_count[$jury_id] = 0;
                    }
                    
                    if ($assignments_count[$jury_id] < $candidates_per_jury) {
                        update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_id);
                        $assignments_count[$jury_id]++;
                        $success_count++;
                    }
                    
                    $jury_index = ($jury_index + 1) % count($jury_members);
                }
                break;
                
            case 'expertise':
                foreach ($candidates as $candidate) {
                    $candidate_categories = wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'slugs'));
                    
                    // Find jury member with matching expertise and available slots
                    $assigned = false;
                    foreach ($jury_members as $jury) {
                        $jury_id = $jury->ID;
                        $jury_expertise = get_post_meta($jury_id, 'expertise', true);
                        
                        if (!isset($assignments_count[$jury_id])) {
                            $assignments_count[$jury_id] = 0;
                        }
                        
                        if ($assignments_count[$jury_id] < $candidates_per_jury && 
                            in_array($jury_expertise, $candidate_categories)) {
                            update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_id);
                            $assignments_count[$jury_id]++;
                            $success_count++;
                            $assigned = true;
                            break;
                        }
                    }
                    
                    // If no matching expertise found, assign to jury member with least assignments
                    if (!$assigned) {
                        $min_assignments = min($assignments_count);
                        $jury_id = array_search($min_assignments, $assignments_count);
                        
                        if ($jury_id && $assignments_count[$jury_id] < $candidates_per_jury) {
                            update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_id);
                            $assignments_count[$jury_id]++;
                            $success_count++;
                        }
                    }
                }
                break;
                
            case 'random':
                shuffle($candidates);
                $jury_index = 0;
                foreach ($candidates as $candidate) {
                    $jury_member = $jury_members[$jury_index];
                    $jury_id = $jury_member->ID;
                    
                    if (!isset($assignments_count[$jury_id])) {
                        $assignments_count[$jury_id] = 0;
                    }
                    
                    if ($assignments_count[$jury_id] < $candidates_per_jury) {
                        update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_id);
                        $assignments_count[$jury_id]++;
                        $success_count++;
                    }
                    
                    $jury_index = ($jury_index + 1) % count($jury_members);
                }
                break;
        }
        
        wp_send_json_success(array(
            'message' => sprintf('%d candidates auto-assigned successfully', $success_count),
            'assignments' => $assignments_count
        ));
    }

    /**
     * Handle export assignments
     */
    public function handle_export_assignments() {
        if (!wp_verify_nonce($_POST['nonce'], 'mt_assignment_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="assignments-' . date('Y-m-d') . '.csv"');
        
        // Create CSV
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, array('Candidate Name', 'Company', 'Category', 'Assigned To', 'Assignment Date'));
        
        // Get data
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1
        ));
        
        foreach ($candidates as $candidate) {
            $jury_id = get_post_meta($candidate->ID, '_mt_assigned_jury_member', true);
            $jury_name = '';
            
            if ($jury_id) {
                $jury = get_post($jury_id);
                $jury_name = $jury ? $jury->post_title : 'Unknown';
            }
            
            $categories = wp_get_post_terms($candidate->ID, 'mt_category');
            $category = !empty($categories) ? $categories[0]->name : '';
            
            fputcsv($output, array(
                $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_company', true),
                $category,
                $jury_name,
                get_the_date('Y-m-d', $candidate->ID)
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Handle jury evaluation submission
     */
    public function handle_evaluation_submission() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        // Check if user is a jury member
        $current_user_id = get_current_user_id();
        $jury_post = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $current_user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($jury_post)) {
            wp_send_json_error(array('message' => __('Access denied. You are not authorized to submit evaluations.', 'mobility-trailblazers')));
        }
        
        $jury_member_id = $jury_post[0]->ID;
        
        // Get and sanitize data
        $candidate_id = intval($_POST['candidate_id']);
        $courage = intval($_POST['courage']);
        $innovation = intval($_POST['innovation']);
        $implementation = intval($_POST['implementation']);
        $relevance = intval($_POST['relevance']);
        $visibility = intval($_POST['visibility']);
        $comments = sanitize_textarea_field($_POST['comments']);
        
        // Validate required fields
        if (!$candidate_id || $courage < 1 || $courage > 10 || $innovation < 1 || $innovation > 10 || 
            $implementation < 1 || $implementation > 10 || $relevance < 1 || $relevance > 10 || 
            $visibility < 1 || $visibility > 10) {
            wp_send_json_error(array('message' => __('Invalid evaluation data. All scores must be between 1 and 10.', 'mobility-trailblazers')));
        }
        
        // Verify candidate is assigned to this jury member
        $assigned_jury = get_post_meta($candidate_id, '_mt_assigned_jury_member', true);
        if ($assigned_jury != $jury_member_id) {
            wp_send_json_error(array('message' => __('You are not assigned to evaluate this candidate.', 'mobility-trailblazers')));
        }
        
        // Save evaluation to database
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Check if evaluation already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $jury_member_id
        ));
        
        $data = array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $jury_member_id,
            'courage_score' => $courage,
            'innovation_score' => $innovation,
            'implementation_score' => $implementation,
            'relevance_score' => $relevance,
            'visibility_score' => $visibility,
            'comments' => $comments,
            'evaluation_date' => current_time('mysql')
        );
        
        if ($existing) {
            // Update existing evaluation
            $result = $wpdb->update(
                $table_scores,
                $data,
                array('id' => $existing),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Insert new evaluation
            $result = $wpdb->insert(
                $table_scores,
                $data,
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
            );
        }
        
        if ($result !== false) {
            // Remove any draft for this candidate
            delete_user_meta($current_user_id, "mt_evaluation_draft_{$candidate_id}");
            
            wp_send_json_success(array(
                'message' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                'total_score' => $courage + $innovation + $implementation + $relevance + $visibility
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save evaluation. Please try again.', 'mobility-trailblazers')));
        }
    }
    
    /**
     * Handle draft save
     */
    public function handle_draft_save() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        $current_user_id = get_current_user_id();
        $candidate_id = intval($_POST['candidate_id']);
        
        // Prepare draft data
        $draft_data = array(
            'courage' => intval($_POST['courage']),
            'innovation' => intval($_POST['innovation']),
            'implementation' => intval($_POST['implementation']),
            'relevance' => intval($_POST['relevance']),
            'visibility' => intval($_POST['visibility']),
            'comments' => sanitize_textarea_field($_POST['comments']),
            'saved_at' => current_time('mysql')
        );
        
        // Save draft as user meta
        $result = update_user_meta($current_user_id, "mt_evaluation_draft_{$candidate_id}", $draft_data);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => __('Draft saved successfully!', 'mobility-trailblazers')
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save draft.', 'mobility-trailblazers')));
        }
    }
    
    /**
     * Handle get evaluation
     */
    public function handle_get_evaluation() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        $current_user_id = get_current_user_id();
        $candidate_id = intval($_POST['candidate_id']);
        
        // Get jury member ID
        $jury_post = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $current_user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($jury_post)) {
            wp_send_json_error(array('message' => __('Access denied.', 'mobility-trailblazers')));
        }
        
        $jury_member_id = $jury_post[0]->ID;
        
        // Get existing evaluation
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $evaluation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d ORDER BY evaluation_date DESC LIMIT 1",
            $candidate_id,
            $jury_member_id
        ), ARRAY_A);
        
        if ($evaluation) {
            wp_send_json_success(array(
                'evaluation' => $evaluation,
                'is_draft' => false
            ));
        } else {
            // Check for draft
            $draft = get_user_meta($current_user_id, "mt_evaluation_draft_{$candidate_id}", true);
            if ($draft) {
                wp_send_json_success(array(
                    'evaluation' => $draft,
                    'is_draft' => true
                ));
            } else {
                wp_send_json_success(array(
                    'evaluation' => null,
                    'is_draft' => false
                ));
            }
        }
    }
    
    /**
     * Handle export evaluations
     */
    public function handle_export_evaluations() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_dashboard')) {
            wp_send_json_error(array('message' => __('Security check failed', 'mobility-trailblazers')));
        }
        
        $current_user_id = get_current_user_id();
        
        // Get jury member ID
        $jury_post = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $current_user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        if (empty($jury_post)) {
            wp_send_json_error(array('message' => __('Access denied.', 'mobility-trailblazers')));
        }
        
        $jury_member_id = $jury_post[0]->ID;
        $jury_name = $jury_post[0]->post_title;
        
        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="my-evaluations-' . sanitize_file_name($jury_name) . '-' . date('Y-m-d') . '.csv"');
        
        // Create CSV
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, array(
            'Candidate Name', 
            'Company', 
            'Courage', 
            'Innovation', 
            'Implementation', 
            'Relevance', 
            'Visibility', 
            'Total Score', 
            'Comments', 
            'Evaluation Date'
        ));
        
        // Get evaluations
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $evaluations = $wpdb->get_results($wpdb->prepare(
            "SELECT cs.*, p.post_title as candidate_name 
             FROM $table_scores cs 
             JOIN {$wpdb->posts} p ON cs.candidate_id = p.ID 
             WHERE cs.jury_member_id = %d 
             ORDER BY cs.evaluation_date DESC",
            $jury_member_id
        ));
        
        foreach ($evaluations as $evaluation) {
            $company = get_post_meta($evaluation->candidate_id, '_mt_company', true);
            
            fputcsv($output, array(
                $evaluation->candidate_name,
                $company,
                $evaluation->courage_score,
                $evaluation->innovation_score,
                $evaluation->implementation_score,
                $evaluation->relevance_score,
                $evaluation->visibility_score,
                $evaluation->total_score,
                $evaluation->comments,
                $evaluation->evaluation_date
            ));
        }
        
        fclose($output);
        exit;
    }
}

// Initialize the plugin
function mobility_trailblazers() {
    return MobilityTrailblazersPlugin::get_instance();
}

// Start the plugin
mobility_trailblazers();

?>