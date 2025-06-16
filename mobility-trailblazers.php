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
    
    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', strtolower($relative_class)) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
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
     * @var \MobilityTrailblazers\Admin\Admin
     */
    private $admin;
    
    /**
     * @var \MobilityTrailblazers\Frontend\Frontend
     */
    private $frontend;
    
    /**
     * @var \MobilityTrailblazers\Shortcodes\ShortcodeHandler
     */
    private $shortcode_handler;
    
    /**
     * @var \MobilityTrailblazers\Integrations\IntegrationsLoader
     */
    private $integrations_loader;
    
    /**
     * @var \MobilityTrailblazers\Diagnostic\Diagnostic
     */
    private $diagnostic;
    
    /**
     * @var \MobilityTrailblazers\Database\Database
     */
    private $database;
    
    /**
     * @var \MobilityTrailblazers\Roles\Roles
     */
    private $roles;
    
    /**
     * @var \MobilityTrailblazers\Taxonomies\Taxonomies
     */
    private $taxonomies;
    
    /**
     * @var \MobilityTrailblazers\PostTypes\PostTypes
     */
    private $post_types;
    
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
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-mt-database.php');
        
        // Load roles handler
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-mt-roles.php');
        
        // Load taxonomies handler
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-mt-taxonomies.php');
        
        // Load post types handler
        $this->safe_require(MT_PLUGIN_PATH . 'includes/class-mt-post-types.php');

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
        require_once plugin_dir_path(__FILE__) . 'includes/mt-utility-functions.php';

        // Load core classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-roles.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-taxonomies.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-post-types.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-shortcodes.php';

        // Load admin classes if in admin area
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'includes/class-mt-admin-menus.php';
            require_once plugin_dir_path(__FILE__) . 'includes/class-mt-diagnostic.php';
        }

        // Load frontend classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-mt-jury-system.php';

        // Load integrations
        require_once plugin_dir_path(__FILE__) . 'includes/integrations/class-integrations-loader.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize core components
        $this->init_class('Shortcodes');
        $this->init_class('Admin');
        $this->init_class('Frontend');
        $this->init_class('IntegrationsLoader');
        $this->init_class('Diagnostic');
        $this->init_class('Database');
        $this->init_class('Roles');
        $this->init_class('Taxonomies');
        $this->init_class('PostTypes');
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
     * Safely require a file
     */
    private function safe_require($file) {
        if (file_exists($file)) {
            require_once $file;
        } else {
            // Log error but don't break the site
            error_log('Mobility Trailblazers: Missing file ' . $file);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Load and register post types first
        if (class_exists('\MobilityTrailblazers\PostTypes')) {
            $post_types = new \MobilityTrailblazers\PostTypes();
            $post_types->register_post_types();
        }
        
        // Load and register taxonomies
        if (class_exists('\MobilityTrailblazers\Taxonomies')) {
            $taxonomies = new \MobilityTrailblazers\Taxonomies();
            $taxonomies->register_taxonomies();
        }
        
        // Create database tables
        \MobilityTrailblazers\Database::create_tables();
        
        // Create roles
        \MobilityTrailblazers\Roles::create_roles();
        
        // Create default terms
        \MobilityTrailblazers\Taxonomies::create_default_terms();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Remove roles
        \MobilityTrailblazers\Roles::remove_roles();
        
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
        // Global admin styles
        wp_enqueue_style(
            'mt-admin-style',
            MT_PLUGIN_URL . 'assets/admin.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        // Special handling for assignment page
        if ($hook === 'mt-award-system_page_mt-assignments' || $hook === 'mt-awards_page_mt-assignment-management') {
            // Force refresh with timestamp
            $version = time(); // Use timestamp for development, filemtime() for production
            
            // Enqueue assignment.css from correct path
            wp_enqueue_style(
                'mt-assignment-css', 
                MT_PLUGIN_URL . 'assets/assignment.css',
                array(), 
                $version
            );
            
            // Enqueue assignment.js from correct path
            wp_enqueue_script(
                'mt-assignment-js', 
                MT_PLUGIN_URL . 'assets/assignment.js',
                array('jquery', 'jquery-ui-sortable'), 
                $version,
                true
            );
            
            // Get candidates data
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
                    'company' => get_post_meta($candidate->ID, 'company', true),
                    'position' => get_post_meta($candidate->ID, 'position', true),
                    'category' => wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'slugs')),
                    'assigned' => false, // Will be updated based on assignments
                    'jury_member_id' => null
                );
            }
            
            // Get jury members data
            $jury_members = get_posts(array(
                'post_type' => 'mt_jury',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            $jury_data = array();
            foreach ($jury_members as $jury) {
                $assignments = get_post_meta($jury->ID, 'assigned_candidates', true);
                $assignments = is_array($assignments) ? $assignments : array();
                
                $jury_data[] = array(
                    'id' => $jury->ID,
                    'name' => $jury->post_title,
                    'position' => get_post_meta($jury->ID, 'position', true),
                    'expertise' => get_post_meta($jury->ID, 'expertise', true),
                    'role' => get_post_meta($jury->ID, 'role', true),
                    'assignments' => count($assignments),
                    'max_assignments' => get_post_meta($jury->ID, 'max_assignments', true) ?: 10,
                    'assigned_candidates' => $assignments
                );
            }
            
            // Update candidates with assignment info
            foreach ($candidates_data as &$candidate) {
                foreach ($jury_data as $jury) {
                    if (in_array($candidate['id'], $jury['assigned_candidates'])) {
                        $candidate['assigned'] = true;
                        $candidate['jury_member_id'] = $jury['id'];
                        break;
                    }
                }
            }
            
            // Localize script with data
            wp_localize_script('mt-assignment-js', 'mt_assignment_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_assignment_nonce'),
                'candidates' => $candidates_data,
                'jury_members' => $jury_data
            ));
            
            // Debug: Log that files are being enqueued
            error_log('MT Assignment files enqueued with version: ' . $version);
        }
        
        // Page-specific scripts
        if (strpos($hook, 'mt-') !== false) {
            // jQuery UI for sortable
            wp_enqueue_script('jquery-ui-sortable');
            
            // Chart.js for analytics
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1'
            );
            
            // Main admin script
            wp_enqueue_script(
                'mt-admin-script',
                MT_PLUGIN_URL . 'assets/admin.js',
                array('jquery', 'chartjs'),
                MT_PLUGIN_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('mt-admin-script', 'mt_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ajax_nonce'),
                'strings' => array(
                    'confirm_reset' => __('Are you sure you want to reset this vote?', 'mobility-trailblazers'),
                    'confirm_bulk_reset' => __('Are you sure you want to reset all votes for this candidate?', 'mobility-trailblazers'),
                    'processing' => __('Processing...', 'mobility-trailblazers'),
                    'error' => __('An error occurred. Please try again.', 'mobility-trailblazers')
                )
            ));
        }
        
        // Vote reset specific
        if ($hook === 'mt-awards_page_mt-vote-reset') {
            wp_enqueue_script(
                'mt-vote-reset-script',
                MT_PLUGIN_URL . 'assets/admin.js',
                array('jquery'),
                MT_PLUGIN_VERSION,
                true
            );
            
            wp_localize_script('mt-vote-reset-script', 'mt_vote_reset', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_vote_reset_nonce'),
                'rest_url' => rest_url('mt/v1/'),
                'rest_nonce' => wp_create_nonce('wp_rest')
            ));
        }
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        // Frontend styles
        wp_enqueue_style(
            'mt-frontend-style',
            MT_PLUGIN_URL . 'assets/frontend.css',
            array(),
            MT_PLUGIN_VERSION
        );
        
        // Frontend scripts
        wp_enqueue_script(
            'mt-frontend-script',
            MT_PLUGIN_URL . 'assets/frontend.js',
            array('jquery'),
            MT_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-frontend-script', 'mt_frontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_frontend_nonce')
        ));
    }
    
    /**
     * Load Elementor compatibility
     */
    public function load_elementor_compatibility() {
        if (did_action('elementor/loaded')) {
            $elementor_compat_file = MT_PLUGIN_PATH . 'includes/class-mt-elementor-compat.php';
            if (file_exists($elementor_compat_file)) {
                require_once $elementor_compat_file;
                if (class_exists('MT_Elementor_Compat')) {
                    new MT_Elementor_Compat();
                }
            }
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
        IntegrationsLoader::get_instance();
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
}

// Initialize the plugin
function mobility_trailblazers() {
    return MobilityTrailblazersPlugin::get_instance();
}

// Start the plugin
mobility_trailblazers();

?>