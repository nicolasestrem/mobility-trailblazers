<?php
/**
 * Plugin Name: Mobility Trailblazers Award System
 * Plugin URI: https://mobilitytrailblazers.de
 * Description: Complete award management system for 25 Mobility Trailblazers in 25 - managing candidates, jury members, voting process, and public engagement.
 * Version: 0.1.1
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

// Include the jury system fix
require_once MT_PLUGIN_PATH . 'includes/class-mt-jury-fix.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-ajax-fix.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-jury-consistency.php';

/**
 * Main Plugin Class
 */
class MobilityTrailblazersPlugin {
    
    public function __construct() {
        // Load dependencies first
        $this->load_dependencies();
        
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Menu hooks - Single registration point
        add_action('admin_menu', array($this, 'register_all_admin_menus'));
        
        // Register settings
        add_action('admin_init', function() {
            register_setting('mt_award_settings', 'mt_jury_dashboard_page');
        });
        
        // Jury dashboard and evaluation hooks
        add_action('admin_init', array($this, 'handle_jury_dashboard_direct'));
        add_action('admin_init', array($this, 'debug_evaluation_access'));
        
        // REST API and AJAX hooks
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        add_action('wp_ajax_mt_export_backup_history', array($this, 'handle_export_backup_history'));
        
        // Backup and reset AJAX handlers
        add_action('wp_ajax_mt_reset_vote', array($this, 'handle_ajax_reset_vote'));
        add_action('wp_ajax_mt_bulk_reset_candidate', array($this, 'handle_ajax_bulk_reset'));
        add_action('wp_ajax_mt_get_reset_history', array($this, 'handle_ajax_get_reset_history'));
        add_action('wp_ajax_mt_get_jury_stats', array($this, 'handle_ajax_get_jury_stats'));
        
        // Other jury-related hooks
        add_action('init', array($this, 'add_jury_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_jury_query_vars'));
        add_action('template_redirect', array($this, 'jury_template_redirect'));
        add_filter('login_redirect', array($this, 'jury_login_redirect'), 10, 3);
        add_action('wp_dashboard_setup', array($this, 'add_jury_dashboard_widget'));
        add_shortcode('mt_jury_dashboard', array($this, 'jury_dashboard_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_mt_assign_candidates', array($this, 'handle_assign_candidates'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'handle_auto_assign'));
        add_action('wp_ajax_mt_get_assignment_stats', array($this, 'handle_get_assignment_stats'));
        add_action('wp_ajax_mt_clear_assignments', array($this, 'handle_clear_assignments'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'handle_export_assignments'));
        add_action('wp_ajax_mt_get_candidate_details', array($this, 'ajax_get_candidate_details'));
        
        // Evaluation submission handler
        add_action('admin_post_mt_submit_evaluation', array($this, 'handle_evaluation_submission'));
        
        // Debug hook for jury access issues
        add_action('admin_notices', array($this, 'debug_jury_access'));
        
        // Add Elementor compatibility
        add_action('plugins_loaded', array($this, 'load_elementor_compatibility'));
    }

    /**
     * Load required classes
     */
    private function load_dependencies() {
        // Define constants if not already defined
        if (!defined('MT_PLUGIN_PATH')) {
            define('MT_PLUGIN_PATH', plugin_dir_path(__FILE__));
        }
        
        // Define the classes we need to load
        $classes = array(
            'includes/class-vote-backup-manager.php',
            'includes/class-vote-audit-logger.php',
            'includes/class-vote-reset-manager.php',
            'admin/class-jury-management-admin.php'
        );
        
        // Load each class file if it exists
        foreach ($classes as $class_file) {
            $file_path = plugin_dir_path(__FILE__) . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                // Log error if file doesn't exist
                error_log('Mobility Trailblazers: Missing file ' . $file_path);
            }
        }
    }

    public function init() {
        // Load text domain
        $this->load_textdomain();
        
        // Include required classes
        require_once MT_PLUGIN_PATH . 'includes/class-vote-reset-manager.php';
        require_once MT_PLUGIN_PATH . 'includes/class-vote-backup-manager.php';
        require_once MT_PLUGIN_PATH . 'includes/class-vote-audit-logger.php';
        
        // Include the new jury management admin class
        require_once MT_PLUGIN_PATH . 'admin/class-jury-management-admin.php';
        
        // Initialize the jury management admin using the singleton pattern
        if (is_admin() && class_exists('MT_Jury_Management_Admin')) {
            MT_Jury_Management_Admin::get_instance();
        }
        
        // Register custom post types
        $this->create_custom_post_types();
        
        // Create custom taxonomies
        $this->create_custom_taxonomies();
        
        // Initialize other components using helper function
        $this->init_class('MT_Vote_Reset_Manager');
        $this->init_class('MT_Vote_Backup_Manager');
        $this->init_class('MT_Jury_Consistency');
        
        // Add custom image sizes
        add_image_size('candidate-thumbnail', 300, 300, true);
        add_image_size('candidate-full', 800, 600, true);
        
        // Add hooks
        $this->add_hooks();
        
        // Load admin and frontend components
        $this->load_admin();
        $this->load_frontend();
    }

    /**
     * Helper function to safely initialize classes whether they use singleton or not
     */
    private function init_class($class_name) {
        if (!class_exists($class_name)) {
            return false;
        }
        
        // Check if class uses singleton pattern
        if (method_exists($class_name, 'get_instance')) {
            return $class_name::get_instance();
        } else {
            return new $class_name();
        }
    }

    public function activate() {
        $this->create_database_tables();
        $this->create_custom_post_types();
        $this->create_custom_taxonomies();
        $this->create_custom_roles();
        flush_rewrite_rules();
    }

    public function deactivate() {
        // Remove custom roles
        remove_role('mt_jury_member');
        remove_role('mt_award_admin');
        
        // Remove custom capabilities from administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->remove_cap('mt_submit_evaluations');
            $admin_role->remove_cap('mt_view_candidates');
            $admin_role->remove_cap('mt_access_jury_dashboard');
            $admin_role->remove_cap('mt_manage_awards');
            $admin_role->remove_cap('mt_manage_assignments');
            $admin_role->remove_cap('mt_view_all_evaluations');
            $admin_role->remove_cap('mt_export_data');
            $admin_role->remove_cap('mt_manage_voting');
        }
        
        flush_rewrite_rules();
    }

    /**
     * Load plugin text domain for translations
     */
    private function load_textdomain() {
        load_plugin_textdomain('mobility-trailblazers', false, dirname(MT_PLUGIN_BASENAME) . '/languages/');
    }

    /**
     * Create custom roles for the plugin
     */
    private function create_custom_roles() {
        // Create MT Jury Member role
        add_role('mt_jury_member', 'MT Jury Member', array(
            // Basic WordPress capabilities
            'read' => true,
            'edit_posts' => true,
            'upload_files' => true,
            
            // Custom post type capabilities for candidates
            'read_mt_candidate' => true,
            'read_private_mt_candidates' => true,
            'edit_mt_candidates' => true,
            'edit_published_mt_candidates' => true,
            
            // Jury evaluation capabilities
            'mt_submit_evaluations' => true,
            'mt_view_candidates' => true,
            'mt_access_jury_dashboard' => true,
        ));
        
        // Create MT Award Admin role
        add_role('mt_award_admin', 'MT Award Administrator', array(
            // All admin capabilities
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => true,
            'publish_posts' => true,
            'upload_files' => true,
            'manage_options' => true,
            
            // All candidate capabilities
            'edit_mt_candidate' => true,
            'read_mt_candidate' => true,
            'delete_mt_candidate' => true,
            'edit_mt_candidates' => true,
            'edit_others_mt_candidates' => true,
            'publish_mt_candidates' => true,
            'read_private_mt_candidates' => true,
            'delete_mt_candidates' => true,
            'delete_private_mt_candidates' => true,
            'delete_published_mt_candidates' => true,
            'delete_others_mt_candidates' => true,
            'edit_private_mt_candidates' => true,
            'edit_published_mt_candidates' => true,
            
            // All jury capabilities
            'edit_mt_jury' => true,
            'read_mt_jury' => true,
            'delete_mt_jury' => true,
            'edit_mt_jurys' => true,
            'edit_others_mt_jurys' => true,
            'publish_mt_jurys' => true,
            'read_private_mt_jurys' => true,
            'delete_mt_jurys' => true,
            
            // Award management capabilities
            'mt_manage_awards' => true,
            'mt_manage_assignments' => true,
            'mt_view_all_evaluations' => true,
            'mt_export_data' => true,
            'mt_manage_voting' => true,
        ));
        
        // Add capabilities to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            // Give admins all MT capabilities
            $admin_role->add_cap('mt_submit_evaluations');
            $admin_role->add_cap('mt_view_candidates');
            $admin_role->add_cap('mt_access_jury_dashboard');
            $admin_role->add_cap('mt_manage_awards');
            $admin_role->add_cap('mt_manage_assignments');
            $admin_role->add_cap('mt_view_all_evaluations');
            $admin_role->add_cap('mt_export_data');
            $admin_role->add_cap('mt_manage_voting');
        }
    }

    /**
     * Register custom post types
     */
    private function create_custom_post_types() {
        // Register Candidate Post Type
        register_post_type('mt_candidate', array(
            'labels' => array(
                'name' => __('Candidates', 'mobility-trailblazers'),
                'singular_name' => __('Candidate', 'mobility-trailblazers'),
                'add_new' => __('Add New Candidate', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Candidate', 'mobility-trailblazers'),
                'edit_item' => __('Edit Candidate', 'mobility-trailblazers'),
                'new_item' => __('New Candidate', 'mobility-trailblazers'),
                'view_item' => __('View Candidate', 'mobility-trailblazers'),
                'search_items' => __('Search Candidates', 'mobility-trailblazers'),
                'not_found' => __('No candidates found', 'mobility-trailblazers'),
                'not_found_in_trash' => __('No candidates found in trash', 'mobility-trailblazers')
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-groups',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'rewrite' => array('slug' => 'candidates'),
            'show_in_rest' => true
        ));
        
        // Register Jury Member Post Type
        register_post_type('mt_jury', array(
            'labels' => array(
                'name' => __('Jury Members', 'mobility-trailblazers'),
                'singular_name' => __('Jury Member', 'mobility-trailblazers'),
                'add_new' => __('Add New Jury Member', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Jury Member', 'mobility-trailblazers'),
                'edit_item' => __('Edit Jury Member', 'mobility-trailblazers'),
                'new_item' => __('New Jury Member', 'mobility-trailblazers'),
                'view_item' => __('View Jury Member', 'mobility-trailblazers'),
                'search_items' => __('Search Jury Members', 'mobility-trailblazers'),
                'not_found' => __('No jury members found', 'mobility-trailblazers'),
                'not_found_in_trash' => __('No jury members found in trash', 'mobility-trailblazers')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'menu_icon' => 'dashicons-businessman',
            'supports' => array('title', 'thumbnail', 'custom-fields'),
            'show_in_rest' => true
        ));
    }

    /**
     * Create Custom Taxonomies
     */
    public function create_custom_taxonomies() {
        // Category taxonomy for candidates
        register_taxonomy('mt_category', 'mt_candidate', array(
            'labels' => array(
                'name' => __('Categories', 'mobility-trailblazers'),
                'singular_name' => __('Category', 'mobility-trailblazers'),
                'search_items' => __('Search Categories', 'mobility-trailblazers'),
                'all_items' => __('All Categories', 'mobility-trailblazers'),
                'edit_item' => __('Edit Category', 'mobility-trailblazers'),
                'update_item' => __('Update Category', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Category', 'mobility-trailblazers'),
                'new_item_name' => __('New Category Name', 'mobility-trailblazers'),
                'menu_name' => __('Categories', 'mobility-trailblazers')
            ),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'mobility-category'),
            'show_in_rest' => true
        ));
        
        // Phase taxonomy for tracking selection phases
        register_taxonomy('mt_phase', 'mt_candidate', array(
            'labels' => array(
                'name' => __('Phases', 'mobility-trailblazers'),
                'singular_name' => __('Phase', 'mobility-trailblazers'),
                'search_items' => __('Search Phases', 'mobility-trailblazers'),
                'all_items' => __('All Phases', 'mobility-trailblazers'),
                'edit_item' => __('Edit Phase', 'mobility-trailblazers'),
                'update_item' => __('Update Phase', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Phase', 'mobility-trailblazers'),
                'new_item_name' => __('New Phase Name', 'mobility-trailblazers'),
                'menu_name' => __('Phases', 'mobility-trailblazers')
            ),
            'hierarchical' => false,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'phase'),
            'show_in_rest' => true
        ));
    }

    /**
     * Create Database Tables
     */
    private function create_database_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Voting table
        $table_votes = $wpdb->prefix . 'mt_votes';
        $sql_votes = "CREATE TABLE $table_votes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            vote_round tinyint(1) NOT NULL DEFAULT 1,
            rating tinyint(2) NOT NULL,
            comments text,
            vote_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (candidate_id, jury_member_id, vote_round),
            KEY candidate_idx (candidate_id),
            KEY jury_idx (jury_member_id)
        ) $charset_collate;";



        // Evaluation criteria scores table
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        $sql_scores = "CREATE TABLE $table_scores (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            jury_member_id bigint(20) NOT NULL,
            courage_score tinyint(2) NOT NULL DEFAULT 0,
            innovation_score tinyint(2) NOT NULL DEFAULT 0,
            implementation_score tinyint(2) NOT NULL DEFAULT 0,
            mobility_relevance_score tinyint(2) NOT NULL DEFAULT 0,
            visibility_score tinyint(2) NOT NULL DEFAULT 0,
            total_score decimal(4,2) DEFAULT 0,
            evaluation_round tinyint(1) NOT NULL DEFAULT 1,
            evaluation_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round),
            KEY candidate_idx (candidate_id),
            KEY jury_idx (jury_member_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_votes);
        dbDelta($sql_scores);

        // Create default categories based on the 3 dimensions from docs
        $this->create_default_terms();
    }

    /**
     * Create default terms
     */
    private function create_default_terms() {
        // Categories (3 dimensions from documentation)
        $categories = array(
            'established-companies' => __('Established Companies', 'mobility-trailblazers'),
            'startups-new-makers' => __('Start-ups & New Makers', 'mobility-trailblazers'),
            'infrastructure-politics-public' => __('Infrastructure / Politics / Public Companies', 'mobility-trailblazers')
        );

        foreach ($categories as $slug => $name) {
            if (!term_exists($name, 'mt_category')) {
                wp_insert_term($name, 'mt_category', array('slug' => $slug));
            }
        }

        // Selection statuses
        $statuses = array(
            'longlist' => __('Longlist (~200)', 'mobility-trailblazers'),
            'shortlist' => __('Shortlist (50)', 'mobility-trailblazers'),
            'finalist' => __('Finalist (25)', 'mobility-trailblazers'),
            'winner' => __('Winner (Top 3)', 'mobility-trailblazers'),
            'rejected' => __('Rejected', 'mobility-trailblazers')
        );

        foreach ($statuses as $slug => $name) {
            if (!term_exists($name, 'mt_status')) {
                wp_insert_term($name, 'mt_status', array('slug' => $slug));
            }
        }

        // Current award year
        $current_year = date('Y');
        if (!term_exists($current_year, 'mt_award_year')) {
            wp_insert_term($current_year, 'mt_award_year', array('slug' => $current_year));
        }
    }

    /**
     * Add WordPress hooks
     */
    private function add_hooks() {
        // Admin hooks (menu registration handled in constructor)
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_candidate_meta'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_shortcode('mt_voting_form', array($this, 'voting_form_shortcode'));
        add_shortcode('mt_candidate_grid', array($this, 'candidate_grid_shortcode'));
        add_shortcode('mt_jury_members', array($this, 'jury_members_shortcode'));
        add_shortcode('mt_voting_results', array($this, 'voting_results_shortcode'));
        
        // AJAX handlers
        add_action('wp_ajax_mt_jury_vote', array($this, 'handle_jury_vote'));
        add_action('wp_ajax_mt_assign_candidates', array($this, 'handle_assign_candidates'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'handle_auto_assign'));
        add_action('wp_ajax_mt_get_assignment_stats', array($this, 'handle_get_assignment_stats'));
        add_action('wp_ajax_mt_clear_assignments', array($this, 'handle_clear_assignments'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'handle_export_assignments'));
        add_action('wp_ajax_mt_get_candidate_details', array($this, 'ajax_get_candidate_details'));
        
        // Vote Reset AJAX handlers
        add_action('wp_ajax_mt_reset_individual_vote', array($this, 'handle_reset_individual_vote'));
        add_action('wp_ajax_mt_reset_phase_votes', array($this, 'handle_reset_phase_votes'));
        add_action('wp_ajax_mt_reset_all_votes', array($this, 'handle_reset_all_votes'));
        add_action('wp_ajax_mt_get_vote_stats', array($this, 'handle_get_vote_stats'));
        add_action('wp_ajax_mt_get_jury_progress', array($this, 'handle_get_jury_progress'));
        
        // Jury dashboard hooks (menu registration handled in constructor)
        add_action('init', array($this, 'add_jury_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_jury_query_vars'));
        add_action('template_redirect', array($this, 'jury_template_redirect'));
        add_filter('login_redirect', array($this, 'jury_login_redirect'), 10, 3);
        add_action('wp_dashboard_setup', array($this, 'add_jury_dashboard_widget'));
        add_shortcode('mt_jury_dashboard', array($this, 'jury_dashboard_shortcode'));
        
        // Evaluation submission hook
        add_action('wp_ajax_mt_evaluation_submission', array($this, 'handle_evaluation_submission'));
        
        // Diagnostic hooks (menu registration handled in constructor)
        add_action('wp_ajax_mt_diagnostic_action', array($this, 'handle_diagnostic_ajax'));

        // Temporary debug for assignment page
        add_action('admin_footer', function() {
            if (isset($_GET['page']) && $_GET['page'] === 'mt-assignments') {
                ?>
                <script>
                jQuery(document).ready(function($) {
                    console.log('=== Assignment Page Debug ===');
                    console.log('mt_assignment_ajax exists:', typeof mt_assignment_ajax !== 'undefined');
                    if (typeof mt_assignment_ajax !== 'undefined') {
                        console.log('Candidates loaded:', mt_assignment_ajax.candidates.length);
                        console.log('Jury members loaded:', mt_assignment_ajax.jury_members.length);
                        console.log('AJAX URL:', mt_assignment_ajax.ajax_url);
                        console.log('Nonce:', mt_assignment_ajax.nonce);
                    }
                });
                </script>
                <?php
            }
        });
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('MT Award System', 'mobility-trailblazers'),
            __('MT Award System', 'mobility-trailblazers'),
            'manage_options',
            'mt-award-system',
            array($this, 'admin_dashboard'),
            'dashicons-awards',
            30
        );

        add_submenu_page(
            'mt-award-system',
            __('Assignment Management', 'mobility-trailblazers'),
            __('Assignments', 'mobility-trailblazers'),
            'manage_options',
            'mt-assignments',
            array($this, 'assignment_management_page')
        );

        // Add voting results page with proper capability check
        add_submenu_page(
            'mt-award-system',
            __('Voting Results', 'mobility-trailblazers'),
            __('Voting Results', 'mobility-trailblazers'),
            'mt_manage_voting', // Custom capability for mt_award_admin role
            'mt-voting-results',
            array($this, 'voting_results_page')
        );

        // Add settings page
        add_submenu_page(
            'mt-award-system',
            __('Settings', 'mobility-trailblazers'),
            __('Settings', 'mobility-trailblazers'),
            'manage_options',
            'mt-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Register all admin menus in one place
     */
    public function register_all_admin_menus() {
        // Main menu
        $this->add_admin_menu();
        
        // Jury dashboard
        $this->add_jury_dashboard_menu();
        
        // Enhanced Jury Management submenu
        if (current_user_can('manage_options') && class_exists('MT_Jury_Management_Admin')) {
            $jury_admin_instance = MT_Jury_Management_Admin::get_instance();
            add_submenu_page(
                'mt-award-system',
                __('Jury Management', 'mobility-trailblazers'),
                __('Jury Management', 'mobility-trailblazers'),
                'manage_options',
                'mt-jury-management',
                array($jury_admin_instance, 'render_jury_management_page')
            );
        }
        
        // Vote Reset Management submenu
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'mt-award-system',
                __('Vote Reset Management', 'mobility-trailblazers'),
                __('Vote Reset', 'mobility-trailblazers'),
                'manage_options',
                'mt-vote-reset',
                array($this, 'vote_reset_page')
            );
        }
        
        // Diagnostic
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'mt-award-system',
                __('Diagnostic', 'mobility-trailblazers'),
                __('Diagnostic', 'mobility-trailblazers'),
                'manage_options',
                'mt-diagnostic',
                array($this, 'diagnostic_page')
            );
        }
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Don't load admin scripts in Elementor editor
        if (isset($_GET['action']) && $_GET['action'] === 'elementor') {
            return;
        }

        // Load on all MT plugin pages and post edit pages
        if (!empty($hook) && (strpos($hook, 'mt-') !== false || in_array($hook, array('post.php', 'post-new.php')))) {
            // Fix paths: assets/admin.js instead of assets/js/admin.js
            wp_enqueue_script('mt-admin-js', MT_PLUGIN_URL . 'assets/admin.js', array('jquery'), MT_PLUGIN_VERSION, true);
            wp_enqueue_style('mt-admin-css', MT_PLUGIN_URL . 'assets/admin.css', array(), MT_PLUGIN_VERSION);
            
            wp_localize_script('mt-admin-js', 'mt_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_nonce'),
                'strings' => array(
                    'confirm_vote' => __('Are you sure you want to submit this evaluation?', 'mobility-trailblazers'),
                    'vote_success' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                    'vote_error' => __('Error submitting evaluation. Please try again.', 'mobility-trailblazers')
                )
            ));
        }
        
        // Add menu fix for jury members
        if ($this->is_jury_member(get_current_user_id()) || current_user_can('manage_options')) {
            $dashboard_url = $this->get_jury_dashboard_page_url();
            if ($dashboard_url) {
                wp_add_inline_script('jquery', "
                    jQuery(document).ready(function($) {
                        // Fix the dashboard menu link
                        $('#adminmenu a[href*=\"jury-dashboard-redirect\"]').attr('href', '" . esc_js($dashboard_url) . "');
                        
                        // Remove any duplicate My Dashboard entries
                        var dashboardItems = $('#adminmenu a:contains(\"My Dashboard\")').parent();
                        if (dashboardItems.length > 1) {
                            dashboardItems.slice(1).remove();
                        }
                    });
                ");
            }
        }
        
        // Special handling for assignment page - CORRECTED VERSION
        if ($hook === 'mt-award-system_page_mt-assignments') {
            // Enqueue assignment.js from correct path
            wp_enqueue_script(
                'mt-assignment-js', 
                MT_PLUGIN_URL . 'assets/assignment.js',  // CORRECT PATH
                array('jquery'), 
                MT_PLUGIN_VERSION, 
                true
            );
            
            // Enqueue assignment.css from correct path
            wp_enqueue_style(
                'mt-assignment-css', 
                MT_PLUGIN_URL . 'assets/assignment.css',
                array(), 
                MT_PLUGIN_VERSION
            );
            
            // Get candidates for assignment - FIXED VERSION
            $candidates_data = $this->get_candidates_for_assignment();
            $jury_data = $this->get_jury_members_for_assignment();
            
            // Debug output
            error_log('MT Debug: Candidates data: ' . print_r($candidates_data, true));
            error_log('MT Debug: Jury data: ' . print_r($jury_data, true));
            
            // Localize script with data
            wp_localize_script('mt-assignment-js', 'mt_assignment_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_assignment_nonce'),
                'candidates' => $candidates_data,
                'jury_members' => $jury_data,
                'strings' => array(
                    'confirm_assign' => __('Are you sure you want to assign these candidates?', 'mobility-trailblazers'),
                    'assign_success' => __('Candidates assigned successfully!', 'mobility-trailblazers'),
                    'assign_error' => __('Error assigning candidates. Please try again.', 'mobility-trailblazers'),
                    'no_selection' => __('Please select candidates and a jury member.', 'mobility-trailblazers')
                )
            ));
        }
        
        // Special handling for jury dashboard page
        if ($hook === 'toplevel_page_mt-award-system' || $hook === 'mt-award-system_page_jury-dashboard') {
            // If you have specific dashboard scripts
            wp_enqueue_script(
                'mt-dashboard-js', 
                MT_PLUGIN_URL . 'assets/dashboard.js',
                array('jquery'), 
                MT_PLUGIN_VERSION, 
                true
            );
            
            // Load vote reset functionality for jury members
            if ($this->is_jury_member(get_current_user_id())) {
                wp_enqueue_script(
                    'mt-vote-reset-js',
                    MT_PLUGIN_URL . 'admin/js/vote-reset-admin.js',
                    array('jquery'),
                    MT_PLUGIN_VERSION,
                    true
                );
                
                wp_localize_script('mt-vote-reset-js', 'mt_vote_reset_ajax', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mt_vote_reset_nonce'),
                    'rest_url' => rest_url(''),
                    'admin_url' => admin_url(''),
                    'strings' => array(
                        'confirm_reset_individual' => __('Reset Vote?', 'mobility-trailblazers'),
                        'reset_success' => __('Your vote has been reset successfully.', 'mobility-trailblazers'),
                        'reset_error' => __('Error resetting vote. Please try again.', 'mobility-trailblazers')
                    )
                ));
            }
        }
        
        // Load vote reset scripts on the vote reset page
        if ($hook === 'mt-award-system_page_mt-vote-reset') {
            wp_enqueue_script(
                'mt-vote-reset-js',
                MT_PLUGIN_URL . 'admin/js/vote-reset-admin.js',
                array('jquery'),
                MT_PLUGIN_VERSION,
                true
            );
            
            // Use consistent variable name 'mt_ajax' to match JavaScript
            wp_localize_script('mt-vote-reset-js', 'mt_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'rest_url' => rest_url(''),
                'nonce' => wp_create_nonce('mt_vote_reset_nonce'),
                'rest_nonce' => wp_create_nonce('wp_rest'),
                'admin_url' => admin_url(''),
                'confirm_reset' => __('Are you sure you want to reset this vote? This action cannot be undone.', 'mobility-trailblazers'),
                'confirm_bulk_reset' => __('Are you sure you want to reset all votes for this candidate? This action cannot be undone.', 'mobility-trailblazers'),
                'confirm_delete' => __('Are you sure you want to permanently delete this item? This action cannot be undone.', 'mobility-trailblazers'),
                'processing' => __('Processing...', 'mobility-trailblazers'),
                'error_occurred' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'success' => __('Operation completed successfully.', 'mobility-trailblazers')
            ));
        }
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        // Fix paths for frontend assets
        wp_enqueue_script(
            'mt-frontend-js', 
            MT_PLUGIN_URL . 'assets/frontend.js',
            array('jquery'), 
            MT_PLUGIN_VERSION, 
            true
        );
        
        wp_enqueue_style(
            'mt-frontend-css', 
            MT_PLUGIN_URL . 'assets/frontend.css',
            array(), 
            MT_PLUGIN_VERSION
        );
        
        wp_localize_script('mt-frontend-js', 'mt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_nonce'),
            'strings' => array(
                'vote_success' => __('Thank you for your vote!', 'mobility-trailblazers'),
                'vote_error' => __('Error submitting vote. Please try again.', 'mobility-trailblazers'),
                'already_voted' => __('You have already voted for this candidate.', 'mobility-trailblazers')
            )
        ));
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Candidate meta boxes
        add_meta_box(
            'mt_candidate_details',
            __('Candidate Details', 'mobility-trailblazers'),
            array($this, 'candidate_details_meta_box'),
            'mt_candidate',
            'normal',
            'high'
        );

        add_meta_box(
            'mt_candidate_evaluation',
            __('Evaluation Criteria', 'mobility-trailblazers'),
            array($this, 'candidate_evaluation_meta_box'),
            'mt_candidate',
            'side',
            'default'
        );

        // Jury member meta boxes
        add_meta_box(
            'mt_jury_details',
            __('Jury Member Details', 'mobility-trailblazers'),
            array($this, 'jury_details_meta_box'),
            'mt_jury',
            'normal',
            'high'
        );
    }

    /**
     * Candidate details meta box
     */
    public function candidate_details_meta_box($post) {
        wp_nonce_field('mt_candidate_meta_nonce', 'mt_candidate_meta_nonce');
        
        $company = get_post_meta($post->ID, '_mt_company', true);
        $position = get_post_meta($post->ID, '_mt_position', true);
        $location = get_post_meta($post->ID, '_mt_location', true);
        $email = get_post_meta($post->ID, '_mt_email', true);
        $linkedin = get_post_meta($post->ID, '_mt_linkedin', true);
        $website = get_post_meta($post->ID, '_mt_website', true);
        $innovation_description = get_post_meta($post->ID, '_mt_innovation_description', true);
        $impact_metrics = get_post_meta($post->ID, '_mt_impact_metrics', true);
        $courage_story = get_post_meta($post->ID, '_mt_courage_story', true);

        echo '<table class="form-table">';
        echo '<tr><th><label for="mt_company">' . __('Company/Organization', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="mt_company" name="mt_company" value="' . esc_attr($company) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_position">' . __('Position/Role', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="mt_position" name="mt_position" value="' . esc_attr($position) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_location">' . __('Location (DACH)', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="mt_location" name="mt_location" value="' . esc_attr($location) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_email">' . __('Email', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="email" id="mt_email" name="mt_email" value="' . esc_attr($email) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_linkedin">' . __('LinkedIn Profile', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="url" id="mt_linkedin" name="mt_linkedin" value="' . esc_attr($linkedin) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_website">' . __('Website', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="url" id="mt_website" name="mt_website" value="' . esc_attr($website) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_innovation_description">' . __('Innovation Description', 'mobility-trailblazers') . '</label></th>';
        echo '<td><textarea id="mt_innovation_description" name="mt_innovation_description" rows="4" class="large-text">' . esc_textarea($innovation_description) . '</textarea></td></tr>';
        
        echo '<tr><th><label for="mt_impact_metrics">' . __('Impact & Metrics', 'mobility-trailblazers') . '</label></th>';
        echo '<td><textarea id="mt_impact_metrics" name="mt_impact_metrics" rows="3" class="large-text">' . esc_textarea($impact_metrics) . '</textarea></td></tr>';
        
        echo '<tr><th><label for="mt_courage_story">' . __('Courage Story', 'mobility-trailblazers') . '</label></th>';
        echo '<td><textarea id="mt_courage_story" name="mt_courage_story" rows="4" class="large-text">' . esc_textarea($courage_story) . '</textarea></td></tr>';
        
        echo '</table>';
    }

    /**
     * Candidate evaluation meta box
     */
    public function candidate_evaluation_meta_box($post) {
        $current_user_id = get_current_user_id();
        $is_jury_member = $this->is_jury_member($current_user_id);
        
        if (!$is_jury_member) {
            echo '<p>' . __('Only jury members can evaluate candidates.', 'mobility-trailblazers') . '</p>';
            return;
        }

        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $existing_score = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d AND evaluation_round = 1",
            $post->ID,
            $current_user_id
        ));

        echo '<div id="mt-evaluation-form">';
        echo '<p><strong>' . __('Evaluation Criteria (1-10 scale):', 'mobility-trailblazers') . '</strong></p>';
        
        $criteria = array(
            'courage_score' => __('Courage & Pioneer Spirit', 'mobility-trailblazers'),
            'innovation_score' => __('Innovation Degree', 'mobility-trailblazers'),
            'implementation_score' => __('Implementation & Impact', 'mobility-trailblazers'),
            'mobility_relevance_score' => __('Mobility Transformation Relevance', 'mobility-trailblazers'),
            'visibility_score' => __('Role Model & Visibility', 'mobility-trailblazers')
        );

        foreach ($criteria as $key => $label) {
            $value = $existing_score ? $existing_score->$key : 0;
            echo '<p><label for="' . $key . '">' . $label . ':</label><br>';
            echo '<select name="' . $key . '" id="' . $key . '">';
            for ($i = 0; $i <= 10; $i++) {
                echo '<option value="' . $i . '"' . selected($value, $i, false) . '>' . $i . '</option>';
            }
            echo '</select></p>';
        }

        echo '<p><button type="button" id="submit-evaluation" class="button button-primary">';
        echo $existing_score ? __('Update Evaluation', 'mobility-trailblazers') : __('Submit Evaluation', 'mobility-trailblazers');
        echo '</button></p>';
        
        if ($existing_score) {
            echo '<p><strong>' . __('Total Score:', 'mobility-trailblazers') . '</strong> ' . $existing_score->total_score . '/50</p>';
            echo '<p><em>' . __('Last updated:', 'mobility-trailblazers') . ' ' . date('d.m.Y H:i', strtotime($existing_score->evaluation_date)) . '</em></p>';
        }
        
        echo '</div>';
    }

    /**
     * Jury details meta box
     */
    public function jury_details_meta_box($post) {
        wp_nonce_field('mt_jury_meta_nonce', 'mt_jury_meta_nonce');
        
        $company = get_post_meta($post->ID, '_mt_jury_company', true);
        $position = get_post_meta($post->ID, '_mt_jury_position', true);
        $expertise = get_post_meta($post->ID, '_mt_jury_expertise', true);
        $bio = get_post_meta($post->ID, '_mt_jury_bio', true);
        $email = get_post_meta($post->ID, '_mt_jury_email', true);
        $linkedin = get_post_meta($post->ID, '_mt_jury_linkedin', true);
        $is_president = get_post_meta($post->ID, '_mt_jury_is_president', true);
        $is_vice_president = get_post_meta($post->ID, '_mt_jury_is_vice_president', true);

        echo '<table class="form-table">';
        echo '<tr><th><label for="mt_jury_company">' . __('Company/Organization', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="mt_jury_company" name="mt_jury_company" value="' . esc_attr($company) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_jury_position">' . __('Position/Role', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="mt_jury_position" name="mt_jury_position" value="' . esc_attr($position) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_jury_expertise">' . __('Area of Expertise', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="text" id="mt_jury_expertise" name="mt_jury_expertise" value="' . esc_attr($expertise) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_jury_email">' . __('Email', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="email" id="mt_jury_email" name="mt_jury_email" value="' . esc_attr($email) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_jury_linkedin">' . __('LinkedIn Profile', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="url" id="mt_jury_linkedin" name="mt_jury_linkedin" value="' . esc_attr($linkedin) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th><label for="mt_jury_bio">' . __('Biography', 'mobility-trailblazers') . '</label></th>';
        echo '<td><textarea id="mt_jury_bio" name="mt_jury_bio" rows="4" class="large-text">' . esc_textarea($bio) . '</textarea></td></tr>';
        
        echo '<tr><th><label for="mt_jury_is_president">' . __('President', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="checkbox" id="mt_jury_is_president" name="mt_jury_is_president" value="1"' . checked($is_president, 1, false) . ' /></td></tr>';
        
        echo '<tr><th><label for="mt_jury_is_vice_president">' . __('Vice President', 'mobility-trailblazers') . '</label></th>';
        echo '<td><input type="checkbox" id="mt_jury_is_vice_president" name="mt_jury_is_vice_president" value="1"' . checked($is_vice_president, 1, false) . ' /></td></tr>';
        
        echo '</table>';
    }

    /**
     * Save candidate meta data
     */
    public function save_candidate_meta($post_id) {
        if (isset($_POST['mt_candidate_meta_nonce']) && wp_verify_nonce($_POST['mt_candidate_meta_nonce'], 'mt_candidate_meta_nonce')) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }

            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            $fields = array(
                '_mt_company', '_mt_position', '_mt_location', '_mt_email', '_mt_linkedin', '_mt_website',
                '_mt_innovation_description', '_mt_impact_metrics', '_mt_courage_story'
            );

            foreach ($fields as $field) {
                $key = str_replace('_mt_', 'mt_', $field);
                if (isset($_POST[$key])) {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$key]));
                }
            }
        }

        // Handle jury member fields
        if (isset($_POST['mt_jury_meta_nonce']) && wp_verify_nonce($_POST['mt_jury_meta_nonce'], 'mt_jury_meta_nonce')) {
            if (get_post_type($post_id) === 'mt_jury') {
                $jury_fields = array(
                    '_mt_jury_company', '_mt_jury_position', '_mt_jury_expertise', '_mt_jury_bio',
                    '_mt_jury_email', '_mt_jury_linkedin', '_mt_jury_is_president', '_mt_jury_is_vice_president'
                );

                foreach ($jury_fields as $field) {
                    $key = str_replace('_mt_jury_', 'mt_jury_', $field);
                    if (isset($_POST[$key])) {
                        if (in_array($field, array('_mt_jury_is_president', '_mt_jury_is_vice_president'))) {
                            update_post_meta($post_id, $field, 1);
                        } else {
                            update_post_meta($post_id, $field, sanitize_text_field($_POST[$key]));
                        }
                    } else {
                        if (in_array($field, array('_mt_jury_is_president', '_mt_jury_is_vice_president'))) {
                            delete_post_meta($post_id, $field);
                        }
                    }
                }
            }
        }
    }

    /**
     * Admin dashboard page
     */
    public function admin_dashboard() {
        global $wpdb;
        
        // Get statistics
        $candidates_count = wp_count_posts('mt_candidate')->publish;
        $jury_count = wp_count_posts('mt_jury')->publish;
        
        $table_votes = $wpdb->prefix . 'mt_votes';
        
        $jury_votes = $wpdb->get_var("SELECT COUNT(*) FROM $table_votes");
        
        echo '<div class="wrap">';
        echo '<h1>' . __('Mobility Trailblazers Dashboard', 'mobility-trailblazers') . '</h1>';
        
        echo '<div class="mt-dashboard-stats">';
        echo '<div class="mt-stat-box">';
        echo '<h3>' . __('Candidates', 'mobility-trailblazers') . '</h3>';
        echo '<div class="mt-stat-number">' . $candidates_count . '</div>';
        echo '</div>';
        
        echo '<div class="mt-stat-box">';
        echo '<h3>' . __('Jury Members', 'mobility-trailblazers') . '</h3>';
        echo '<div class="mt-stat-number">' . $jury_count . '</div>';
        echo '</div>';
        
        echo '<div class="mt-stat-box">';
        echo '<h3>' . __('Jury Votes', 'mobility-trailblazers') . '</h3>';
        echo '<div class="mt-stat-number">' . $jury_votes . '</div>';
        echo '</div>';
        echo '</div>';
        
        // Quick actions
        echo '<div class="mt-quick-actions">';
        echo '<h2>' . __('Quick Actions', 'mobility-trailblazers') . '</h2>';
        echo '<a href="' . admin_url('post-new.php?post_type=mt_candidate') . '" class="button button-primary">' . __('Add New Candidate', 'mobility-trailblazers') . '</a> ';
        echo '<a href="' . admin_url('post-new.php?post_type=mt_jury') . '" class="button button-primary">' . __('Add New Jury Member', 'mobility-trailblazers') . '</a> ';
        echo '<a href="' . admin_url('admin.php?page=mt-voting-results') . '" class="button button-secondary">' . __('View Voting Results', 'mobility-trailblazers') . '</a>';
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Handle jury vote submission
     */
    public function handle_jury_vote() {
        // Verify nonce
        if (!check_ajax_referer('mt_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
            return;
        }
        
        $candidate_id = intval($_POST['candidate_id'] ?? 0);
        $current_user_id = get_current_user_id();
        
        // Verify user is logged in
        if (!$current_user_id) {
            wp_send_json_error(array('message' => __('Please log in to submit an evaluation.', 'mobility-trailblazers')));
            return;
        }
        
        // Verify user is a jury member
        if (!$this->is_jury_member($current_user_id)) {
            wp_send_json_error(array('message' => __('Unauthorized access. You must be a jury member.', 'mobility-trailblazers')));
            return;
        }

        // Validate candidate
        if (!$candidate_id || get_post_type($candidate_id) !== 'mt_candidate') {
            wp_send_json_error(array('message' => __('Invalid candidate selected.', 'mobility-trailblazers')));
            return;
        }

        // Collect and validate scores
        $scores = array(
            'courage_score' => intval($_POST['courage_score'] ?? 0),
            'innovation_score' => intval($_POST['innovation_score'] ?? 0),
            'implementation_score' => intval($_POST['implementation_score'] ?? 0),
            'relevance_score' => intval($_POST['relevance_score'] ?? $_POST['mobility_relevance_score'] ?? 0),
            'visibility_score' => intval($_POST['visibility_score'] ?? 0)
        );

        // Validate all scores are between 1-10
        foreach ($scores as $key => $score) {
            if ($score < 1 || $score > 10) {
                wp_send_json_error(array('message' => sprintf(__('Invalid %s. Score must be between 1 and 10.', 'mobility-trailblazers'), str_replace('_', ' ', $key))));
                return;
            }
        }

        $total_score = array_sum($scores);
        $comments = sanitize_textarea_field($_POST['comments'] ?? '');

        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';

        // Check if evaluation already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $current_user_id
        ));

        // Prepare data
        $data = array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $current_user_id,
            'courage_score' => $scores['courage_score'],
            'innovation_score' => $scores['innovation_score'],
            'implementation_score' => $scores['implementation_score'],
            'relevance_score' => $scores['relevance_score'],
            'visibility_score' => $scores['visibility_score'],
            'total_score' => $total_score,
            'comments' => $comments,
            'evaluated_at' => current_time('mysql')
        );

        // Save to database
        if ($existing) {
            $result = $wpdb->update(
                $table_scores,
                $data,
                array('id' => $existing->id),
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            $result = $wpdb->insert(
                $table_scores,
                $data,
                array('%d', '%d', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s')
            );
        }

        if ($result !== false) {
            // Get candidate name for success message
            $candidate_name = get_the_title($candidate_id);
            
            wp_send_json_success(array(
                'message' => sprintf(__('Evaluation for %s saved successfully! Total score: %d/50', 'mobility-trailblazers'), $candidate_name, $total_score),
                'total_score' => $total_score,
                'evaluated' => true
            ));
        } else {
            error_log('MT Database error: ' . $wpdb->last_error);
            wp_send_json_error(array('message' => __('Database error. Please try again.', 'mobility-trailblazers')));
        }
    }



    /**
     * Check if user is jury member
     */
    public function is_jury_member($user_id) {
        // Check by role first
        $user = get_user_by('id', $user_id);
        if ($user && in_array('mt_jury_member', (array) $user->roles)) {
            return true;
        }
        
        // Then check by jury post assignment
        $jury_post = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => '_mt_jury_email',
                    'value' => $user->user_email,
                    'compare' => '='
                )
            )
        ));
        
        return !empty($jury_post);
    }

    /**
     * Voting form shortcode
     */
    public function voting_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'candidate_id' => 0,
            'type' => 'public'
        ), $atts);

        if (!$atts['candidate_id']) {
            return '<p>' . __('Please specify a candidate ID.', 'mobility-trailblazers') . '</p>';
        }

        $candidate = get_post($atts['candidate_id']);
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            return '<p>' . __('Invalid candidate.', 'mobility-trailblazers') . '</p>';
        }

        ob_start();
        ?>
        <div class="mt-voting-form" data-candidate-id="<?php echo $atts['candidate_id']; ?>">
            <h3><?php _e('Vote for', 'mobility-trailblazers'); ?> <?php echo esc_html($candidate->post_title); ?></h3>
            
            <?php if ($atts['type'] === 'public'): ?>
                <form id="mt-public-vote-form">
                    <p>
                        <label for="voter_email"><?php _e('Your Email:', 'mobility-trailblazers'); ?></label>
                        <input type="email" id="voter_email" name="voter_email" required>
                    </p>
                    <p>
                        <button type="submit" class="button"><?php _e('Submit Vote', 'mobility-trailblazers'); ?></button>
                    </p>
                </form>
            <?php endif; ?>
            
            <div id="mt-vote-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Candidate grid shortcode
     */
    public function candidate_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'status' => 'finalist',
            'limit' => 25,
            'show_voting' => 'true'
        ), $atts);

        $args = array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish'
        );

        $tax_query = array();

        if ($atts['category']) {
            $tax_query[] = array(
                'taxonomy' => 'mt_category',
                'field' => 'slug',
                'terms' => $atts['category']
            );
        }

        if ($atts['status']) {
            $tax_query[] = array(
                'taxonomy' => 'mt_status',
                'field' => 'slug',
                'terms' => $atts['status']
            );
        }

        if (!empty($tax_query)) {
            $args['tax_query'] = $tax_query;
        }

        $candidates = new WP_Query($args);

        if (!$candidates->have_posts()) {
            return '<p>' . __('No candidates found.', 'mobility-trailblazers') . '</p>';
        }

        ob_start();
        ?>
        <div class="mt-candidate-grid">
            <?php while ($candidates->have_posts()): $candidates->the_post(); ?>
                <div class="mt-candidate-card">
                    <div class="mt-candidate-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <div class="mt-placeholder-image"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-candidate-content">
                        <h3><?php the_title(); ?></h3>
                        
                        <?php
                        $company = get_post_meta(get_the_ID(), '_mt_company', true);
                        $position = get_post_meta(get_the_ID(), '_mt_position', true);
                        ?>
                        
                        <?php if ($position): ?>
                            <p class="mt-position"><?php echo esc_html($position); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($company): ?>
                            <p class="mt-company"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>
                        
                        <div class="mt-candidate-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        
                        <?php if ($atts['show_voting'] === 'true'): ?>
                            <div class="mt-voting-section">
                                <?php echo do_shortcode('[mt_voting_form candidate_id="' . get_the_ID() . '"]'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php the_permalink(); ?>" class="mt-read-more"><?php _e('Read More', 'mobility-trailblazers'); ?></a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Jury members shortcode
     */
    public function jury_members_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1,
            'show_bio' => 'true'
        ), $atts);

        $args = array(
            'post_type' => 'mt_jury',
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'orderby' => 'menu_order title',
            'order' => 'ASC'
        );

        $jury_members = new WP_Query($args);

        if (!$jury_members->have_posts()) {
            return '<p>' . __('No jury members found.', 'mobility-trailblazers') . '</p>';
        }

        ob_start();
        ?>
        <div class="mt-jury-grid">
            <?php while ($jury_members->have_posts()): $jury_members->the_post(); ?>
                <?php
                $is_president = get_post_meta(get_the_ID(), '_mt_jury_is_president', true);
                $is_vice_president = get_post_meta(get_the_ID(), '_mt_jury_is_vice_president', true);
                $company = get_post_meta(get_the_ID(), '_mt_jury_company', true);
                $position = get_post_meta(get_the_ID(), '_mt_jury_position', true);
                $expertise = get_post_meta(get_the_ID(), '_mt_jury_expertise', true);
                ?>
                
                <div class="mt-jury-card <?php echo $is_president ? 'president' : ($is_vice_president ? 'vice-president' : ''); ?>">
                    <div class="mt-jury-image">
                        <?php if (has_post_thumbnail()): ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <div class="mt-placeholder-image"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-jury-content">
                        <?php if ($is_president): ?>
                            <span class="mt-jury-role president"><?php _e('President', 'mobility-trailblazers'); ?></span>
                        <?php elseif ($is_vice_president): ?>
                            <span class="mt-jury-role vice-president"><?php _e('Vice President', 'mobility-trailblazers'); ?></span>
                        <?php endif; ?>
                        
                        <h3><?php the_title(); ?></h3>
                        
                        <?php if ($position): ?>
                            <p class="mt-jury-position"><?php echo esc_html($position); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($company): ?>
                            <p class="mt-jury-company"><?php echo esc_html($company); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($expertise): ?>
                            <p class="mt-jury-expertise"><?php echo esc_html($expertise); ?></p>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_bio'] === 'true'): ?>
                            <div class="mt-jury-bio">
                                <?php the_excerpt(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Voting results shortcode
     */
    public function voting_results_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'jury',
            'limit' => 10
        ), $atts);

        global $wpdb;

        $table = $wpdb->prefix . 'mt_candidate_scores';
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, AVG(s.total_score) as avg_score, COUNT(s.id) as evaluation_count
            FROM {$wpdb->posts} p
            LEFT JOIN $table s ON p.ID = s.candidate_id AND s.is_active = 1
            WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish'
            GROUP BY p.ID
            HAVING evaluation_count > 0
            ORDER BY avg_score DESC
            LIMIT %d
        ", intval($atts['limit'])));

        if (empty($results)) {
            return '<p>' . __('No voting results available yet.', 'mobility-trailblazers') . '</p>';
        }

        ob_start();
        ?>
        <div class="mt-voting-results">
            <h3><?php _e('Jury Evaluation Results', 'mobility-trailblazers'); ?></h3>
            
            <ol class="mt-results-list">
                <?php foreach ($results as $result): ?>
                    <li class="mt-result-item">
                        <span class="mt-candidate-name"><?php echo esc_html($result->post_title); ?></span>
                        <span class="mt-result-score">
                            <?php echo number_format($result->avg_score, 1); ?>/50 
                            (<?php echo intval($result->evaluation_count); ?> <?php _e('evaluations', 'mobility-trailblazers'); ?>)
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Load admin functionality
     */
    private function load_admin() {
        // Admin-specific functionality can be loaded here
    }

    /**
     * Load frontend functionality
     */
    private function load_frontend() {
        // Frontend-specific functionality can be loaded here
    }

    /**
     * Jury evaluation page
     */
    public function jury_evaluation_page() {
        $current_user_id = get_current_user_id();
        
        if (!$this->is_jury_member($current_user_id)) {
            echo '<div class="wrap"><h1>' . __('Access Denied', 'mobility-trailblazers') . '</h1>';
            echo '<p>' . __('You are not authorized to access this page.', 'mobility-trailblazers') . '</p></div>';
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . __('Jury Evaluation', 'mobility-trailblazers') . '</h1>';
        
        // Get candidates for evaluation
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'mt_status',
                    'field' => 'slug',
                    'terms' => array('shortlist', 'finalist')
                )
            )
        ));

        if (empty($candidates)) {
            echo '<p>' . __('No candidates available for evaluation.', 'mobility-trailblazers') . '</p>';
            echo '</div>';
            return;
        }

        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        echo '<div class="mt-evaluation-overview">';
        echo '<h2>' . __('Candidates for Evaluation', 'mobility-trailblazers') . '</h2>';
        
        foreach ($candidates as $candidate) {
            $existing_score = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d AND evaluation_round = 1 AND is_active = 1",
                $candidate->ID,
                $current_user_id
            ));
            
            $company = get_post_meta($candidate->ID, '_mt_company', true);
            $position = get_post_meta($candidate->ID, '_mt_position', true);
            
            echo '<div class="mt-candidate-evaluation-card">';
            echo '<h3><a href="' . get_edit_post_link($candidate->ID) . '">' . esc_html($candidate->post_title) . '</a></h3>';
            if ($position) echo '<p><strong>' . esc_html($position) . '</strong></p>';
            if ($company) echo '<p>' . esc_html($company) . '</p>';
            
            if ($existing_score) {
                echo '<p class="evaluated"><strong>' . __('Evaluated:', 'mobility-trailblazers') . '</strong> ' . $existing_score->total_score . '/50</p>';
                echo '<p><em>' . date('d.m.Y H:i', strtotime($existing_score->evaluation_date)) . '</em></p>';
            } else {
                echo '<p class="not-evaluated">' . __('Not yet evaluated', 'mobility-trailblazers') . '</p>';
            }
            
            echo '<a href="' . get_edit_post_link($candidate->ID) . '" class="button">' . __('Evaluate', 'mobility-trailblazers') . '</a>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }

    /**
     * Voting results page
     */
    public function voting_results_page() {
        // Check if user has required capabilities
        if (!current_user_can('mt_manage_voting') && !current_user_can('administrator')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . __('Voting Results', 'mobility-trailblazers') . '</h1>';
        
        // Jury results
        echo '<div class="mt-results-section">';
        echo do_shortcode('[mt_voting_results type="jury" limit="25"]');
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Add settings to link to the Jury Dashboard page
     */
    public function add_settings_section($settings) {
        // Add to your existing settings page
        ?>
        <tr>
            <th scope="row">
                <label for="mt_jury_dashboard_page"><?php _e('Jury Dashboard Page', 'mobility-trailblazers'); ?></label>
            </th>
            <td>
                <?php
                wp_dropdown_pages(array(
                    'name' => 'mt_jury_dashboard_page',
                    'show_option_none' => __('— Select —', 'mobility-trailblazers'),
                    'option_none_value' => '0',
                    'selected' => get_option('mt_jury_dashboard_page', 0)
                ));
                ?>
                <p class="description">
                    <?php _e('Select the page containing the [mt_jury_dashboard] shortcode', 'mobility-trailblazers'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['mt_settings_nonce'], 'mt_settings')) {
            update_option('mt_voting_enabled', isset($_POST['voting_enabled']));
            update_option('mt_current_phase', sanitize_text_field($_POST['current_phase']));
            update_option('mt_award_year', sanitize_text_field($_POST['award_year']));
            update_option('mt_jury_dashboard_page', intval($_POST['mt_jury_dashboard_page']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'mobility-trailblazers') . '</p></div>';
        }

        // Check if template file exists, otherwise use inline implementation
        $template_file = MT_PLUGIN_PATH . 'admin/settings-page.php';
        
        if (file_exists($template_file)) {
            // Include the settings page template
            require_once $template_file;
        } else {
            // Fallback to inline implementation
            $this->render_settings_page_inline();
        }
    }

    /**
     * Render settings page inline (fallback method)
     */
    private function render_settings_page_inline() {
        $voting_enabled = get_option('mt_voting_enabled', false);
        $current_phase = get_option('mt_current_phase', 'preparation');
        $award_year = get_option('mt_award_year', date('Y'));

        echo '<div class="wrap">';
        echo '<h1>' . __('Mobility Trailblazers Settings', 'mobility-trailblazers') . '</h1>';
        
        echo '<form method="post" action="">';
        wp_nonce_field('mt_settings', 'mt_settings_nonce');
        
        echo '<table class="form-table">';
        
        echo '<tr><th scope="row">' . __('Award Year', 'mobility-trailblazers') . '</th>';
        echo '<td><input type="text" name="award_year" value="' . esc_attr($award_year) . '" class="regular-text" /></td></tr>';
        
        echo '<tr><th scope="row">' . __('Current Phase', 'mobility-trailblazers') . '</th>';
        echo '<td><select name="current_phase">';
        $phases = array(
            'preparation' => __('Preparation', 'mobility-trailblazers'),
            'candidate_collection' => __('Candidate Collection', 'mobility-trailblazers'),
            'jury_evaluation' => __('Jury Evaluation', 'mobility-trailblazers'),
            'final_selection' => __('Final Selection', 'mobility-trailblazers'),
            'award_ceremony' => __('Award Ceremony', 'mobility-trailblazers'),
            'post_award' => __('Post Award', 'mobility-trailblazers')
        );
        
        foreach ($phases as $phase_key => $phase_name) {
            echo '<option value="' . $phase_key . '"' . selected($current_phase, $phase_key, false) . '>' . $phase_name . '</option>';
        }
        echo '</select></td></tr>';
        
        echo '<tr><th scope="row">' . __('Enable Jury Voting', 'mobility-trailblazers') . '</th>';
        echo '<td><input type="checkbox" name="voting_enabled" value="1"' . checked($voting_enabled, 1, false) . ' /> ' . __('Allow jury members to submit evaluations', 'mobility-trailblazers') . '</td></tr>';
        
        // Add the jury dashboard page setting
        $this->add_settings_section(null);
        
        echo '</table>';
        
        echo '<p class="submit"><input type="submit" name="submit" class="button-primary" value="' . __('Save Settings', 'mobility-trailblazers') . '" /></p>';
        echo '</form>';
        
        echo '</div>';
    }

    /**
     * Assignment Management Page - Template-based with error handling
     */
    public function assignment_management_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include the template file
        $template_file = plugin_dir_path(__FILE__) . 'templates/assignment-template.php';
        
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Assignment Management', 'mobility-trailblazers') . '</h1>';
            echo '<div class="notice notice-error"><p>' . __('Assignment template file not found.', 'mobility-trailblazers') . '</p></div>';
            echo '</div>';
        }
    }

    /**
     * Vote Reset Management Page
     */
    public function vote_reset_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include the vote reset interface
        $interface_file = MT_PLUGIN_PATH . 'admin/views/vote-reset-interface.php';
        
        if (file_exists($interface_file)) {
            include $interface_file;
        } else {
            // Use the interface code from the implementation plan
            ?>
            <div class="wrap">
                <h1><?php _e('Vote Reset Management', 'mobility-trailblazers'); ?></h1>
                
                <div class="mt-admin-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
                    <!-- Current Status -->
                    <div class="mt-admin-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h2><?php _e('Current Voting Status', 'mobility-trailblazers'); ?></h2>
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Current Phase:', 'mobility-trailblazers'); ?></th>
                                <td><strong><?php echo get_option('mt_current_phase', 'Phase 1'); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php _e('Total Active Votes:', 'mobility-trailblazers'); ?></th>
                                <td><strong><?php echo $this->get_total_active_votes(); ?></strong></td>
                            </tr>
                            <tr>
                                <th><?php _e('Total Evaluations:', 'mobility-trailblazers'); ?></th>
                                <td><strong><?php echo $this->get_total_evaluations(); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Individual Reset -->
                    <div class="mt-admin-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h2><?php _e('Reset Individual Votes', 'mobility-trailblazers'); ?></h2>
                        <p><?php _e('Individual vote reset buttons are available in the jury evaluation interface for each candidate.', 'mobility-trailblazers'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=mt-jury-dashboard'); ?>" class="button button-secondary">
                            <?php _e('Go to Jury Dashboard', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                    
                    <!-- Phase Transition Reset -->
                    <div class="mt-admin-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                        <h2><?php _e('Phase Transition Reset', 'mobility-trailblazers'); ?></h2>
                        <p><?php _e('Reset all votes when transitioning between voting phases. This will mark current votes as inactive and prepare for the next phase.', 'mobility-trailblazers'); ?></p>
                        <button type="button" id="mt-bulk-reset-phase" class="button button-primary button-large">
                            <?php _e('Reset for Next Phase', 'mobility-trailblazers'); ?>
                        </button>
                    </div>
                    
                    <!-- Full System Reset -->
                    <div class="mt-admin-card mt-danger-zone" style="background: #fff; padding: 20px; border: 2px solid #dc3545; border-radius: 5px;">
                        <h2 style="color: #dc3545;"><?php _e('Danger Zone', 'mobility-trailblazers'); ?></h2>
                        <p style="color: #dc3545;"><?php _e('Complete system reset. This will permanently delete ALL votes and evaluations. This action cannot be undone!', 'mobility-trailblazers'); ?></p>
                        <button type="button" id="mt-bulk-reset-all" class="button button-large" style="background: #dc3545; color: white; border-color: #dc3545;">
                            <?php _e('Reset All Votes', 'mobility-trailblazers'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Recent Activity Log -->
                <div class="mt-admin-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">
                    <h2><?php _e('Recent Vote Activity', 'mobility-trailblazers'); ?></h2>
                    <?php $this->display_recent_vote_activity(); ?>
                </div>
            </div>
            
            <style>
                .mt-admin-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                
                .mt-admin-card {
                    background: #fff;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                
                .mt-danger-zone {
                    border-color: #dc3545 !important;
                }
                
                .mt-danger-zone h2 {
                    color: #dc3545;
                }
                
                .mt-danger-zone p {
                    color: #dc3545;
                }
            </style>
            <?php
        }
    }

    /**
     * Get total active votes count
     */
    private function get_total_active_votes() {
        global $wpdb;
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}mt_votes 
            WHERE vote_round = 1 AND is_active = 1
        ");
        return $count ?: 0;
    }

    /**
     * Get total evaluations count
     */
    private function get_total_evaluations() {
        global $wpdb;
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}mt_candidate_scores
            WHERE is_active = 1
        ");
        return $count ?: 0;
    }

    /**
     * Display recent vote activity
     */
    private function display_recent_vote_activity() {
        global $wpdb;
        
        $recent_votes = $wpdb->get_results("
            SELECT 
                v.vote_date,
                p.post_title as candidate_name,
                u.display_name as jury_name,
                v.rating
            FROM {$wpdb->prefix}mt_votes v
            LEFT JOIN {$wpdb->posts} p ON v.candidate_id = p.ID
            LEFT JOIN {$wpdb->users} u ON v.jury_member_id = u.ID
            WHERE v.is_active = 1
            ORDER BY v.vote_date DESC
            LIMIT 10
        ");
        
        $recent_evaluations = $wpdb->get_results("
            SELECT 
                cs.evaluation_date,
                p.post_title as candidate_name,
                u.display_name as jury_name,
                cs.total_score
            FROM {$wpdb->prefix}mt_candidate_scores cs
            LEFT JOIN {$wpdb->posts} p ON cs.candidate_id = p.ID
            LEFT JOIN {$wpdb->users} u ON cs.jury_member_id = u.ID
            WHERE cs.is_active = 1
            ORDER BY cs.evaluation_date DESC
            LIMIT 10
        ");
        
        if (empty($recent_votes) && empty($recent_evaluations)) {
            echo '<p>' . __('No recent voting activity found.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Date', 'mobility-trailblazers') . '</th>';
        echo '<th>' . __('Type', 'mobility-trailblazers') . '</th>';
        echo '<th>' . __('Candidate', 'mobility-trailblazers') . '</th>';
        echo '<th>' . __('Jury Member', 'mobility-trailblazers') . '</th>';
        echo '<th>' . __('Score', 'mobility-trailblazers') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        // Combine and sort activities
        $activities = array();
        
        foreach ($recent_evaluations as $eval) {
            $activities[] = array(
                'date' => $eval->evaluation_date,
                'type' => 'Evaluation',
                'candidate' => $eval->candidate_name,
                'jury' => $eval->jury_name,
                'score' => $eval->total_score . '/50'
            );
        }
        
        foreach ($recent_votes as $vote) {
            $activities[] = array(
                'date' => $vote->vote_date,
                'type' => 'Vote',
                'candidate' => $vote->candidate_name,
                'jury' => $vote->jury_name,
                'score' => $vote->rating . '/10'
            );
        }
        
        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        // Display top 10
        foreach (array_slice($activities, 0, 10) as $activity) {
            echo '<tr>';
            echo '<td>' . date('Y-m-d H:i', strtotime($activity['date'])) . '</td>';
            echo '<td>' . esc_html($activity['type']) . '</td>';
            echo '<td>' . esc_html($activity['candidate']) . '</td>';
            echo '<td>' . esc_html($activity['jury']) . '</td>';
            echo '<td>' . esc_html($activity['score']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }

    /**
     * Add missing AJAX handler for clearing assignments
     */
    public function handle_clear_assignments() {
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        $result = $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => '_mt_assigned_jury_member'),
            array('%s')
        );
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d assignments cleared', 'mobility-trailblazers'), $result),
            'cleared_count' => $result
        ));
    }

    /**
     * Add missing AJAX handler for export
     */
    public function handle_export_assignments() {
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $filename = 'mobility-trailblazers-assignments-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        fputcsv($output, array(
            'Candidate Name',
            'Company',
            'Position',
            'Jury Member',
            'Jury Position',
            'Assignment Date'
        ));
        
        global $wpdb;
        $assignments = $wpdb->get_results("
            SELECT 
                c.post_title as candidate_name,
                c_company.meta_value as company,
                c_position.meta_value as position,
                j.post_title as jury_name,
                j_position.meta_value as jury_position,
                NOW() as assignment_date
            FROM {$wpdb->posts} c
            INNER JOIN {$wpdb->postmeta} assignment ON c.ID = assignment.post_id
            INNER JOIN {$wpdb->posts} j ON assignment.meta_value = j.ID
            LEFT JOIN {$wpdb->postmeta} c_company ON c.ID = c_company.post_id AND c_company.meta_key = '_mt_company'
            LEFT JOIN {$wpdb->postmeta} c_position ON c.ID = c_position.post_id AND c_position.meta_key = '_mt_position'
            LEFT JOIN {$wpdb->postmeta} j_position ON j.ID = j_position.post_id AND j_position.meta_key = '_mt_jury_position'
            WHERE c.post_type = 'mt_candidate' 
            AND j.post_type = 'mt_jury'
            AND assignment.meta_key = '_mt_assigned_jury_member'
            ORDER BY j.post_title, c.post_title
        ");
        
        foreach ($assignments as $assignment) {
            fputcsv($output, array(
                $assignment->candidate_name,
                $assignment->company,
                $assignment->position,
                $assignment->jury_name,
                $assignment->jury_position,
                $assignment->assignment_date
            ));
        }
        
        fclose($output);
        exit;
    }

    /**
     * Handle manual candidate assignment
     */
    public function handle_assign_candidates() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get data
        $candidate_ids = isset($_POST['candidate_ids']) ? array_map('intval', $_POST['candidate_ids']) : array();
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        
        if (empty($candidate_ids) || empty($jury_member_id)) {
            wp_send_json_error('Invalid data provided');
        }
        
        // Perform assignments
        $assigned_count = 0;
        foreach ($candidate_ids as $candidate_id) {
            update_post_meta($candidate_id, '_mt_assigned_jury_member', $jury_member_id);
            $assigned_count++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d candidates assigned successfully', 'mobility-trailblazers'), $assigned_count),
            'assigned_count' => $assigned_count
        ));

        if ($assigned_count > 0) {
            $this->notify_jury_member_assignment($jury_member_id, $candidate_ids);
        }
    }

    /**
     * Handle auto-assignment
     */
    public function handle_auto_assign() {
        // Check nonce
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get parameters
        $candidates_per_jury = isset($_POST['candidates_per_jury']) ? intval($_POST['candidates_per_jury']) : 20;
        $algorithm = isset($_POST['algorithm']) ? sanitize_text_field($_POST['algorithm']) : 'balanced';
        $clear_existing = isset($_POST['clear_existing']) && $_POST['clear_existing'] === 'true';
        
        // Clear existing assignments if requested
        if ($clear_existing) {
            global $wpdb;
            $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => '_mt_assigned_jury_member'),
                array('%s')
            );
        }
        
        // Get all candidates and jury members
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_member',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'orderby' => 'rand'
        ));
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        if (empty($candidates) || empty($jury_members)) {
            wp_send_json_error('No candidates or jury members available for assignment');
        }
        
        // Perform auto-assignment
        $assigned_count = 0;
        $jury_index = 0;
        
        foreach ($candidates as $candidate) {
            $jury = $jury_members[$jury_index % count($jury_members)];
            update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury->ID);
            $assigned_count++;
            
            if ($assigned_count % $candidates_per_jury == 0) {
                $jury_index++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Auto-assignment completed: %d candidates assigned', 'mobility-trailblazers'), $assigned_count),
            'assigned_count' => $assigned_count
        ));
    }

    /**
     * Get assignment statistics
     */
    public function handle_get_assignment_stats() {
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        $total_candidates = wp_count_posts('mt_candidate')->publish;
        $total_jury = wp_count_posts('mt_jury')->publish;
        
        global $wpdb;
        $assigned_count = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_mt_assigned_jury_member' 
            AND meta_value != ''
        ");
        
        $completion_rate = $total_candidates > 0 ? ($assigned_count / $total_candidates) * 100 : 0;
        $avg_per_jury = $total_jury > 0 ? $assigned_count / $total_jury : 0;
        
        wp_send_json_success(array(
            'total_candidates' => $total_candidates,
            'total_jury' => $total_jury,
            'assigned_count' => $assigned_count,
            'completion_rate' => round($completion_rate, 1),
            'avg_per_jury' => round($avg_per_jury, 1)
        ));
    }

    /**
     * Get candidates formatted for assignment interface
     */
    private function get_candidates_for_assignment() {
        error_log('MT Debug: Getting candidates for assignment');
        
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        error_log('MT Debug: Found ' . count($candidates) . ' candidates');
        
        $candidates_data = array();
        
        if (!empty($candidates)) {
            foreach ($candidates as $candidate) {
                // Ensure $candidate is an object
                if (!is_object($candidate) || !isset($candidate->ID)) {
                    error_log('MT Debug: Invalid candidate object: ' . print_r($candidate, true));
                    continue;
                }
                
                $assigned_jury = get_post_meta($candidate->ID, '_mt_assigned_jury_member', true);
                $categories = wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'slugs'));
                
                $candidates_data[] = array(
                    'id' => $candidate->ID,
                    'name' => $candidate->post_title,
                    'company' => get_post_meta($candidate->ID, '_mt_company', true) ?: '',
                    'stage' => get_post_meta($candidate->ID, '_mt_stage', true) ?: '',
                    'category' => !empty($categories) ? $categories[0] : '',
                    'assigned' => !empty($assigned_jury),
                    'jury_member_id' => $assigned_jury ?: ''
                );
            }
        }
        
        error_log('MT Debug: Processed ' . count($candidates_data) . ' candidates');
        return $candidates_data;
    }

    /**
     * Get jury members formatted for assignment interface
     */
    private function get_jury_members_for_assignment() {
        error_log('MT Debug: Getting jury members for assignment');
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        error_log('MT Debug: Found ' . count($jury_members) . ' jury members');
        
        $jury_data = array();
        
        if (!empty($jury_members)) {
            foreach ($jury_members as $jury) {
                // Ensure $jury is an object
                if (!is_object($jury) || !isset($jury->ID)) {
                    error_log('MT Debug: Invalid jury object: ' . print_r($jury, true));
                    continue;
                }
                
                // Count current assignments
                $assignments_count = count(get_posts(array(
                    'post_type' => 'mt_candidate',
                    'posts_per_page' => -1,
                    'meta_query' => array(
                        array(
                            'key' => '_mt_assigned_jury_member',
                            'value' => $jury->ID,
                            'compare' => '='
                        )
                    ),
                    'fields' => 'ids'
                )));
                
                $jury_data[] = array(
                    'id' => $jury->ID,
                    'name' => $jury->post_title,
                    'company' => get_post_meta($jury->ID, '_mt_company', true) ?: '',
                    'position' => get_post_meta($jury->ID, '_mt_jury_position', true) ?: '',
                    'assignments' => $assignments_count,
                    'max_assignments' => 25
                );
            }
        }
        
        error_log('MT Debug: Processed ' . count($jury_data) . ' jury members');
        return $jury_data;
    }

    /**
     * Get existing assignments
     */
    private function get_existing_assignments() {
        global $wpdb;
        
        $assignments = $wpdb->get_results("
            SELECT p.ID as candidate_id, pm.meta_value as jury_member_id
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'mt_candidate'
            AND pm.meta_key = '_mt_assigned_jury_member'
            AND pm.meta_value != ''
        ");
        
        return $assignments ?: array();
    }

    /**
     * Add jury dashboard to admin menu - FIXED VERSION
     */
    public function add_jury_dashboard_menu() {
        // This method is now called from register_all_admin_menus() only
        $current_user_id = get_current_user_id();
        
        // Check if user is a jury member or admin
        $is_jury = $this->is_jury_member($current_user_id);
        $is_admin = current_user_can('manage_options');
        
        // Also check for the custom role
        $user = wp_get_current_user();
        $has_jury_role = in_array('mt_jury_member', (array) $user->roles);
        
        if (!$is_jury && !$is_admin && !$has_jury_role) {
            return;
        }
        
        // Check if menu already exists to prevent duplicates
        global $submenu;
        if (isset($submenu['mt-award-system'])) {
            foreach ($submenu['mt-award-system'] as $existing) {
                if ($existing[2] === 'mt-jury-dashboard-redirect') {
                    return; // Menu already exists, don't add again
                }
            }
        }
        
        // Check if parent menu exists
        global $menu;
        $parent_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'mt-award-system') {
                $parent_exists = true;
                break;
            }
        }
        
        if (!$parent_exists) {
            // Create parent menu with lower capability
            add_menu_page(
                __('MT Award System', 'mobility-trailblazers'),
                __('MT Award System', 'mobility-trailblazers'),
                'read',
                'mt-award-system',
                array($this, 'admin_dashboard'),
                'dashicons-awards',
                30
            );
        }
        
        // Add the dashboard submenu only once
        add_submenu_page(
            'mt-award-system',
            __('My Dashboard', 'mobility-trailblazers'),
            __('My Dashboard', 'mobility-trailblazers'),
            'read',
            'mt-jury-dashboard-redirect',
            array($this, 'jury_dashboard_redirect')
        );
    }

    /**
     * Handle jury dashboard redirect
     */
    public function jury_dashboard_redirect() {
        // Get the URL of your Jury Dashboard page
        $dashboard_page_url = $this->get_jury_dashboard_page_url();
        
        if ($dashboard_page_url) {
            wp_redirect($dashboard_page_url);
            exit;
        } else {
            // Fallback: use the built-in dashboard
            $this->jury_dashboard_page();
        }
    }

    /**
     * Get the URL of the Jury Dashboard page (with shortcode)
     */
    private function get_jury_dashboard_page_url() {
        // First check saved setting
        $page_id = get_option('mt_jury_dashboard_page');
        if ($page_id) {
            return get_permalink($page_id);
        }
        
        // Method 1: Look for page by slug
        $page = get_page_by_path('jury-dashboard');
        
        // Method 2: Look for page with specific meta key
        if (!$page) {
            $pages = get_posts(array(
                'post_type' => 'page',
                'meta_key' => '_mt_is_jury_dashboard',
                'meta_value' => '1',
                'posts_per_page' => 1
            ));
            if ($pages) {
                $page = $pages[0];
            }
        }
        
        // Method 3: Look for page containing the shortcode
        if (!$page) {
            global $wpdb;
            $page_id = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'page' 
                AND post_status = 'publish' 
                AND post_content LIKE %s 
                LIMIT 1",
                '%[mt_jury_dashboard]%'
            ));
            
            if ($page_id) {
                $page = get_post($page_id);
            }
        }
        
        return $page ? get_permalink($page->ID) : false;
    }

    /**
     * Render the jury dashboard page
     */
    public function jury_dashboard_page() {
        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        $has_jury_role = in_array('mt_jury_member', (array) $user->roles);
        
        // Get jury member ID
        $jury_member_id = $this->get_jury_member_for_user($current_user_id);
        
        // Allow access if: jury member, has jury role, or is admin
        if (!$jury_member_id && !$has_jury_role && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
        }
        
        // If user has jury role but no jury member post, try to find/create one
        if (!$jury_member_id && $has_jury_role) {
            // Try to find jury member by email
            $jury_posts = get_posts(array(
                'post_type' => 'mt_jury',
                'meta_query' => array(
                    array(
                        'key' => '_mt_jury_email',
                        'value' => $user->user_email,
                        'compare' => '='
                    )
                ),
                'posts_per_page' => 1
            ));
            
            if ($jury_posts) {
                $jury_member_id = $jury_posts[0]->ID;
                // Link the user ID
                update_post_meta($jury_member_id, '_mt_jury_user_id', $current_user_id);
            }
        }
        
        // Get jury member details
        $jury_member = get_post($jury_member_id);
        $jury_name = $jury_member ? $jury_member->post_title : __('Guest', 'mobility-trailblazers');
        
        // Get assigned candidates
        $assigned_candidates = $this->get_assigned_candidates($jury_member_id);
        
        // Calculate statistics
        $total_assigned = count($assigned_candidates);
        $evaluated = 0;
        $pending = 0;
        
        foreach ($assigned_candidates as $candidate) {
            if ($this->has_jury_member_evaluated($jury_member_id, $candidate->ID)) {
                $evaluated++;
            } else {
                $pending++;
            }
        }
        
        $completion_percentage = $total_assigned > 0 ? round(($evaluated / $total_assigned) * 100) : 0;
        
        ?>
        <div class="wrap">
            <h1>🏆 <?php _e('Jury Member Dashboard', 'mobility-trailblazers'); ?></h1>
            
            <div class="mt-dashboard-welcome">
                <h2><?php printf(__('Welcome, %s', 'mobility-trailblazers'), esc_html($jury_name)); ?></h2>
                <p><?php _e('Thank you for your participation in the 25 Mobility Trailblazers award.', 'mobility-trailblazers'); ?></p>
            </div>
            
            <!-- Statistics Cards -->
            <div class="mt-stats-grid">
                <div class="mt-stat-card">
                    <h3><?php _e('Assigned', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $total_assigned; ?></div>
                    <p><?php _e('Total candidates', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="mt-stat-card">
                    <h3><?php _e('Evaluated', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $evaluated; ?></div>
                    <p><?php _e('Completed evaluations', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="mt-stat-card">
                    <h3><?php _e('Pending', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $pending; ?></div>
                    <p><?php _e('Awaiting evaluation', 'mobility-trailblazers'); ?></p>
                </div>
                
                <div class="mt-stat-card">
                    <h3><?php _e('Progress', 'mobility-trailblazers'); ?></h3>
                    <div class="mt-stat-number"><?php echo $completion_percentage; ?>%</div>
                    <div class="mt-progress-bar">
                        <div class="mt-progress-fill" style="width: <?php echo $completion_percentage; ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Assigned Candidates -->
            <h2><?php _e('Your Assigned Candidates', 'mobility-trailblazers'); ?></h2>
            
            <?php if (empty($assigned_candidates)) : ?>
                <p><?php _e('No candidates have been assigned to you yet.', 'mobility-trailblazers'); ?></p>
            <?php else : ?>
                <div class="mt-candidate-grid">
                    <?php foreach ($assigned_candidates as $candidate): 
                        $evaluated = $this->has_jury_member_evaluated($jury_member_id, $candidate->ID);
                        $category = wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'names'));
                        $category_name = !empty($category) ? $category[0] : __('Uncategorized', 'mobility-trailblazers');
                        
                        // CORRECT URL FORMAT HERE:
                        $evaluate_url = admin_url('admin.php?page=mt-evaluate&candidate=' . $candidate->ID);
                        $view_url = get_permalink($candidate->ID);
                    ?>
                        <div class="mt-candidate-card <?php echo $evaluated ? 'evaluated' : 'pending'; ?>">
                            <div class="candidate-status">
                                <?php if ($evaluated): ?>
                                    <span class="status-badge evaluated">✓ <?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
                                <?php else: ?>
                                    <span class="status-badge pending">⏳ <?php _e('Pending', 'mobility-trailblazers'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h3><?php echo esc_html($candidate->post_title); ?></h3>
                            <p class="candidate-category"><?php echo esc_html($category_name); ?></p>
                            
                            <div class="candidate-actions">
                                <a href="<?php echo esc_url($view_url); ?>" class="button button-secondary" target="_blank">
                                    <?php _e('View Profile', 'mobility-trailblazers'); ?>
                                </a>
                                <a href="<?php echo esc_url($evaluate_url); ?>" class="button button-primary">
                                    <?php echo $evaluated ? __('Edit Evaluation', 'mobility-trailblazers') : __('Evaluate Now', 'mobility-trailblazers'); ?>
                                </a>
                            </div>
                            
                            <?php if ($evaluated): ?>
                                <div class="mt-vote-reset-container">
                                    <p><small><?php _e('Need to change your evaluation?', 'mobility-trailblazers'); ?></small></p>
                                    <button type="button" 
                                            class="mt-reset-vote-btn" 
                                            data-candidate-id="<?php echo $candidate->ID; ?>" 
                                            data-candidate-name="<?php echo esc_attr($candidate->post_title); ?>">
                                        <?php _e('Reset My Vote', 'mobility-trailblazers'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <style>
                .mt-dashboard-welcome {
                    background: #f0f0f1;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                
                .mt-stats-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin: 30px 0;
                }
                
                .mt-stat-card {
                    background: white;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    text-align: center;
                }
                
                .mt-stat-card h3 {
                    margin: 0 0 10px;
                    color: #23282d;
                }
                
                .mt-stat-number {
                    font-size: 36px;
                    font-weight: bold;
                    color: #0073aa;
                    margin: 10px 0;
                }
                
                .mt-stat-card p {
                    margin: 0;
                    color: #666;
                }
                
                .mt-progress-bar {
                    width: 100%;
                    height: 10px;
                    background: #f0f0f1;
                    border-radius: 5px;
                    margin-top: 10px;
                    overflow: hidden;
                }
                
                .mt-progress-fill {
                    height: 100%;
                    background: #0073aa;
                    transition: width 0.3s ease;
                }
                
                .mt-candidates-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                
                .mt-candidate-card {
                    background: white;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                    padding: 20px;
                    transition: box-shadow 0.3s ease;
                }
                
                .mt-candidate-card:hover {
                    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                }
                
                .mt-candidate-card.evaluated {
                    border-color: #46b450;
                }
                
                .mt-candidate-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: start;
                    margin-bottom: 15px;
                }
                
                .mt-candidate-header h3 {
                    margin: 0;
                    flex: 1;
                }
                
                .mt-status-badge {
                    font-size: 12px;
                    padding: 4px 8px;
                    border-radius: 3px;
                    background: #f0f0f1;
                    white-space: nowrap;
                }
                
                .mt-candidate-card.evaluated .mt-status-badge {
                    background: #d4edda;
                    color: #155724;
                }
                
                .mt-candidate-info p {
                    margin: 5px 0;
                }
                
                .mt-category {
                    color: #666;
                    font-style: italic;
                }
                
                .mt-candidate-actions {
                    margin-top: 15px;
                    display: flex;
                    gap: 10px;
                }
                
                .mt-candidate-actions .button {
                    flex: 1;
                    text-align: center;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Handle direct jury dashboard access
     * This provides an alternative access method if the menu doesn't work
     */
    public function handle_jury_dashboard_direct() {
        if (!isset($_GET['mt_jury_direct']) || $_GET['mt_jury_direct'] != '1') {
            return;
        }
        
        $current_user_id = get_current_user_id();
        if (!$this->is_jury_member($current_user_id) && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
        }
        
        // Include admin header
        require_once(ABSPATH . 'wp-admin/admin-header.php');
        
        // Render the dashboard
        $this->jury_dashboard_page();
        
        // Include admin footer
        require_once(ABSPATH . 'wp-admin/admin-footer.php');
        exit;
    }

    /**
     * Get jury member ID for a user
     */
    public function get_jury_member_for_user($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_mt_jury_user_id' AND meta_value = %s",
            $user_id
        ));
    }

    /**
     * Get candidates assigned to a jury member
     */
    public function get_assigned_candidates($jury_member_id) {
        $args = array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_member',
                    'value' => $jury_member_id,
                    'compare' => '='
                )
            ),
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        return get_posts($args);
    }

    /**
     * Check if jury member has evaluated a candidate
     */
    public function has_jury_member_evaluated($jury_member_id, $candidate_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $score_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE jury_member_id = %d AND candidate_id = %d AND is_active = 1",
            $jury_member_id,
            $candidate_id
        ));
        
        return $score_exists > 0;
    }

    /**
     * Add rewrite rules for pretty jury URLs
     */
    public function add_jury_rewrite_rules() {
        // Add rewrite rule for jury dashboard
        add_rewrite_rule(
            '^jury-dashboard/?$',
            'index.php?mt_jury_dashboard=1',
            'top'
        );
        
        // Add rewrite rule for individual jury member pages
        add_rewrite_rule(
            '^jury/([^/]+)/?$',
            'index.php?mt_jury_member=$matches[1]',
            'top'
        );
    }

    /**
     * Add query vars
     */
    public function add_jury_query_vars($vars) {
        $vars[] = 'mt_jury_dashboard';
        $vars[] = 'mt_jury_member';
        return $vars;
    }

    /**
     * Template redirect for jury pages
     */
    public function jury_template_redirect() {
        global $wp_query;
        
        // Check if jury dashboard
        if (get_query_var('mt_jury_dashboard')) {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url('/jury-dashboard/')));
                exit;
            }
            
            // Load custom template
            include MT_PLUGIN_PATH . 'templates/jury-dashboard-frontend.php';
            exit;
        }
        
        // Check if individual jury member page
        if (get_query_var('mt_jury_member')) {
            $jury_slug = get_query_var('mt_jury_member');
            // Load jury member public profile template
            include MT_PLUGIN_PATH . 'templates/jury-member-profile.php';
            exit;
        }
    }

    /**
     * Create login redirect for jury members
     */
    public function jury_login_redirect($redirect_to, $request, $user) {
        // Check if user is a jury member
        if (isset($user->ID) && $this->is_jury_member($user->ID)) {
            // Redirect to jury dashboard
            return home_url('/jury-dashboard/');
        }
        
        return $redirect_to;
    }

    /**
     * Add dashboard widget for jury members
     */
    public function add_jury_dashboard_widget() {
        $current_user_id = get_current_user_id();
        
        if ($this->is_jury_member($current_user_id)) {
            wp_add_dashboard_widget(
                'mt_jury_quick_stats',
                __('Your Evaluation Progress', 'mobility-trailblazers'),
                array($this, 'jury_dashboard_widget')
            );
        }
    }

    /**
     * Jury dashboard widget content
     */
    public function jury_dashboard_widget() {
        $current_user_id = get_current_user_id();
        
        // Get jury member post
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
            echo '<p>' . __('Jury member profile not found.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        $jury_member_id = $jury_post[0]->ID;
        
        // Get statistics
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $assigned_count = count(get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_member',
                    'value' => $jury_member_id,
                    'compare' => '='
                )
            )
        )));
        
        $evaluated_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores WHERE jury_member_id = %d AND is_active = 1",
            $current_user_id
        ));
        
        $completion_rate = $assigned_count > 0 ? ($evaluated_count / $assigned_count) * 100 : 0;
        
        ?>
        <div class="mt-widget-stats">
            <div class="mt-widget-stat">
                <span class="mt-widget-number"><?php echo $assigned_count; ?></span>
                <span class="mt-widget-label"><?php _e('Assigned', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-widget-stat">
                <span class="mt-widget-number"><?php echo $evaluated_count; ?></span>
                <span class="mt-widget-label"><?php _e('Evaluated', 'mobility-trailblazers'); ?></span>
            </div>
            <div class="mt-widget-stat">
                <span class="mt-widget-number"><?php echo number_format($completion_rate, 0); ?>%</span>
                <span class="mt-widget-label"><?php _e('Complete', 'mobility-trailblazers'); ?></span>
            </div>
        </div>
        
        <div class="mt-widget-actions">
            <a href="<?php echo $this->get_jury_dashboard_page_url() ?: admin_url('admin.php?page=mt-jury-dashboard'); ?>" class="button button-primary">
                <?php _e('Go to Dashboard', 'mobility-trailblazers'); ?>
            </a>
        </div>
        
        <style>
        .mt-widget-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        
        .mt-widget-stat {
            text-align: center;
        }
        
        .mt-widget-number {
            display: block;
            font-size: 28px;
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 5px;
        }
        
        .mt-widget-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        
        .mt-widget-actions {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        </style>
        <?php
    }

    /**
     * Create jury member shortcode for frontend
     */
    public function jury_dashboard_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access your jury dashboard.', 'mobility-trailblazers') . ' <a href="' . wp_login_url(get_permalink()) . '">' . __('Log in', 'mobility-trailblazers') . '</a></p>';
        }
        
        // Check if user is jury member
        $current_user_id = get_current_user_id();
        if (!$this->is_jury_member($current_user_id)) {
            return '<p>' . __('This dashboard is only accessible to jury members.', 'mobility-trailblazers') . '</p>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include the dashboard template
        include MT_PLUGIN_PATH . 'templates/jury-dashboard-frontend.php';
        
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting candidate details
     */
    public function ajax_get_candidate_details() {
        check_ajax_referer('mt_nonce', 'nonce');
        
        $candidate_id = intval($_POST['candidate_id']);
        $current_user_id = get_current_user_id();
        
        if (!$this->is_jury_member($current_user_id)) {
            wp_send_json_error(array('message' => __('Unauthorized access.', 'mobility-trailblazers')));
        }
        
        // Get existing evaluation
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $existing_score = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d AND evaluation_round = 1",
            $candidate_id,
            $current_user_id
        ));
        
        // Get candidate details
        $candidate = get_post($candidate_id);
        $company = get_post_meta($candidate_id, '_mt_company', true);
        $position = get_post_meta($candidate_id, '_mt_position', true);
        
        $response = array(
            'candidate' => array(
                'name' => $candidate->post_title,
                'company' => $company,
                'position' => $position
            ),
            'scores' => null
        );
        
        if ($existing_score) {
            $response['scores'] = array(
                'courage_score' => $existing_score->courage_score,
                'innovation_score' => $existing_score->innovation_score,
                'implementation_score' => $existing_score->implementation_score,
                'mobility_relevance_score' => $existing_score->mobility_relevance_score,
                'visibility_score' => $existing_score->visibility_score,
                'comments' => $existing_score->comments ?? ''
            );
        }
        
        wp_send_json_success($response);
    }

    /**
     * Email notification for new assignments
     */
    public function notify_jury_member_assignment($jury_member_id, $candidate_ids) {
        // Get jury member details
        $jury_member = get_post($jury_member_id);
        $jury_email = get_post_meta($jury_member_id, '_mt_jury_email', true);
        
        if (!$jury_email) {
            return;
        }
        
        // Get candidates
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'post__in' => $candidate_ids,
            'posts_per_page' => -1
        ));
        
        // Build email content
        $subject = __('New Candidates Assigned for Evaluation - Mobility Trailblazers', 'mobility-trailblazers');
        
        $message = '<h2>' . sprintf(__('Dear %s,', 'mobility-trailblazers'), $jury_member->post_title) . '</h2>';
        $message .= '<p>' . sprintf(__('You have been assigned %d new candidates to evaluate for the Mobility Trailblazers 2025 award.', 'mobility-trailblazers'), count($candidates)) . '</p>';
        
        $message .= '<h3>' . __('Assigned Candidates:', 'mobility-trailblazers') . '</h3>';
        $message .= '<ul>';
        foreach ($candidates as $candidate) {
            $company = get_post_meta($candidate->ID, '_mt_company', true);
            $message .= '<li><strong>' . esc_html($candidate->post_title) . '</strong>';
            if ($company) {
                $message .= ' - ' . esc_html($company);
            }
            $message .= '</li>';
        }
        $message .= '</ul>';
        
        $message .= '<p><a href="' . ($this->get_jury_dashboard_page_url() ?: admin_url('admin.php?page=mt-jury-dashboard')) . '" style="background: #2c5282; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">' . __('Go to Dashboard', 'mobility-trailblazers') . '</a></p>';
        
        $message .= '<p>' . __('Thank you for your valuable contribution to recognizing mobility innovation in the DACH region.', 'mobility-trailblazers') . '</p>';
        
        $message .= '<p><em>' . __('The Mobility Trailblazers Team', 'mobility-trailblazers') . '</em></p>';
        
        // Send email
        mt_send_jury_notification($jury_email, $subject, $message);
    }

    /**
     * Handle individual vote reset AJAX request
     */
    public function handle_reset_individual_vote() {
        // Check nonce
        if (!check_ajax_referer('mt_vote_reset_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        $current_user_id = get_current_user_id();
        if (!$this->is_jury_member($current_user_id) && !current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to reset votes.', 'mobility-trailblazers'));
        }
        
        $candidate_id = intval($_POST['candidate_id'] ?? 0);
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        if (!$candidate_id) {
            wp_send_json_error(__('Invalid candidate ID.', 'mobility-trailblazers'));
        }
        
        // Get jury member ID
        $jury_member_id = $this->get_jury_member_for_user($current_user_id);
        if (!$jury_member_id && !current_user_can('manage_options')) {
            wp_send_json_error(__('Jury member not found.', 'mobility-trailblazers'));
        }
        
        global $wpdb;
        
        // Delete from votes table
        $votes_deleted = $wpdb->delete(
            $wpdb->prefix . 'mt_votes',
            array(
                'candidate_id' => $candidate_id,
                'jury_member_id' => $current_user_id
            ),
            array('%d', '%d')
        );
        
        // Delete from candidate scores table
        $scores_deleted = $wpdb->delete(
            $wpdb->prefix . 'mt_candidate_scores',
            array(
                'candidate_id' => $candidate_id,
                'jury_member_id' => $current_user_id
            ),
            array('%d', '%d')
        );
        
        // Log the reset action
        $this->log_vote_reset('individual', $current_user_id, $candidate_id, $reason);
        
        $candidate_name = get_the_title($candidate_id);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Vote for %s has been reset successfully.', 'mobility-trailblazers'), $candidate_name),
            'votes_deleted' => $votes_deleted,
            'scores_deleted' => $scores_deleted
        ));
    }

    /**
     * Handle phase votes reset AJAX request
     */
    public function handle_reset_phase_votes() {
        // Check nonce
        if (!check_ajax_referer('mt_vote_reset_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to reset phase votes.', 'mobility-trailblazers'));
        }
        
        $notify_jury = intval($_POST['notify_jury'] ?? 0);
        
        global $wpdb;
        
        // Get current phase
        $current_phase = get_option('mt_current_phase', 'phase_1');
        
        // Count votes to be reset
        $votes_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE vote_round = 1");
        $scores_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores");
        
        // Create backup before reset
        $backup_created = $this->create_vote_backup($current_phase);
        
        // Reset votes (mark as inactive instead of deleting)
        $wpdb->update(
            $wpdb->prefix . 'mt_votes',
            array('is_active' => 0, 'reset_date' => current_time('mysql')),
            array('vote_round' => 1),
            array('%d', '%s'),
            array('%d')
        );
        
        // Reset scores (mark as inactive)
        $wpdb->update(
            $wpdb->prefix . 'mt_candidate_scores',
            array('is_active' => 0, 'reset_date' => current_time('mysql')),
            array('evaluation_round' => 1),
            array('%d', '%s'),
            array('%d')
        );
        
        // Log the reset action
        $this->log_vote_reset('phase_transition', get_current_user_id(), null, "Phase transition reset for {$current_phase}");
        
        $notifications_sent = 0;
        if ($notify_jury) {
            $notifications_sent = $this->notify_jury_phase_reset();
        }
        
        wp_send_json_success(array(
            'message' => __('Phase votes have been reset successfully.', 'mobility-trailblazers'),
            'votes_reset' => $votes_count,
            'scores_reset' => $scores_count,
            'backup_created' => $backup_created,
            'notifications_sent' => $notifications_sent
        ));
    }

    /**
     * Handle full system reset AJAX request
     */
    public function handle_reset_all_votes() {
        // Check nonce
        if (!check_ajax_referer('mt_vote_reset_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to reset all votes.', 'mobility-trailblazers'));
        }
        
        $confirm = sanitize_text_field($_POST['confirm'] ?? '');
        if ($confirm !== 'DELETE ALL') {
            wp_send_json_error(__('Confirmation text does not match.', 'mobility-trailblazers'));
        }
        
        global $wpdb;
        
        // Count votes to be reset
        $votes_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes");
        $scores_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores");
        
        // Create full backup
        $backup_created = $this->create_vote_backup('full_reset_' . date('Y-m-d_H-i-s'));
        
        // Delete all votes
        $wpdb->query("DELETE FROM {$wpdb->prefix}mt_votes");
        
        // Delete all scores
        $wpdb->query("DELETE FROM {$wpdb->prefix}mt_candidate_scores");
        
        // Log the reset action
        $this->log_vote_reset('full_reset', get_current_user_id(), null, 'Full system reset - all votes and evaluations deleted');
        
        wp_send_json_success(array(
            'message' => __('All votes and evaluations have been reset.', 'mobility-trailblazers'),
            'votes_reset' => $votes_count,
            'evaluations_reset' => $scores_count,
            'backup_created' => $backup_created
        ));
    }

    /**
     * Handle get vote stats AJAX request
     */
    public function handle_get_vote_stats() {
        // Check nonce
        if (!check_ajax_referer('mt_vote_reset_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        global $wpdb;
        
        $stats = array(
            'total_votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes"),
            'total_evaluations' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores"),
            'active_jury' => $wpdb->get_var("SELECT COUNT(DISTINCT jury_member_id) FROM {$wpdb->prefix}mt_candidate_scores"),
            'candidates_evaluated' => $wpdb->get_var("SELECT COUNT(DISTINCT candidate_id) FROM {$wpdb->prefix}mt_candidate_scores")
        );
        
        wp_send_json_success($stats);
    }

    /**
     * Handle get jury progress AJAX request
     */
    public function handle_get_jury_progress() {
        // Check nonce
        if (!check_ajax_referer('mt_vote_reset_nonce', 'nonce', false)) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        $current_user_id = get_current_user_id();
        $jury_member_id = $this->get_jury_member_for_user($current_user_id);
        
        if (!$jury_member_id) {
            wp_send_json_error(__('Jury member not found.', 'mobility-trailblazers'));
        }
        
        // Get assigned candidates
        $assigned_candidates = $this->get_assigned_candidates($jury_member_id);
        $total = count($assigned_candidates);
        
        // Count evaluated candidates
        $completed = 0;
        foreach ($assigned_candidates as $candidate) {
            if ($this->has_jury_member_evaluated($jury_member_id, $candidate->ID)) {
                $completed++;
            }
        }
        
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        
        wp_send_json_success(array(
            'total' => $total,
            'completed' => $completed,
            'progress' => $progress
        ));
    }

    /**
     * Log vote reset action
     */
    private function log_vote_reset($type, $user_id, $candidate_id = null, $reason = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_vote_reset_log';
        
        // Create log table if it doesn't exist
        $wpdb->query("CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            reset_type varchar(50) NOT NULL,
            user_id int(11) NOT NULL,
            candidate_id int(11) NULL,
            reason text,
            reset_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        )");
        
        $wpdb->insert(
            $table_name,
            array(
                'reset_type' => $type,
                'user_id' => $user_id,
                'candidate_id' => $candidate_id,
                'reason' => $reason,
                'reset_timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%s', '%s')
        );
    }

    /**
     * Create backup of votes before reset
     */
    private function create_vote_backup($phase) {
        global $wpdb;
        
        $backup_table_votes = $wpdb->prefix . 'mt_votes_backup_' . sanitize_key($phase);
        $backup_table_scores = $wpdb->prefix . 'mt_candidate_scores_backup_' . sanitize_key($phase);
        
        // Backup votes
        $wpdb->query("CREATE TABLE $backup_table_votes AS SELECT * FROM {$wpdb->prefix}mt_votes");
        
        // Backup scores
        $wpdb->query("CREATE TABLE $backup_table_scores AS SELECT * FROM {$wpdb->prefix}mt_candidate_scores");
        
        return true;
    }

    /**
     * Notify jury members about phase reset
     */
    private function notify_jury_phase_reset() {
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $notifications_sent = 0;
        
        foreach ($jury_members as $jury_member) {
            $email = get_post_meta($jury_member->ID, '_mt_jury_email', true);
            if ($email) {
                $subject = __('Voting Phase Reset - Mobility Trailblazers', 'mobility-trailblazers');
                $message = sprintf(
                    __('Dear %s,<br><br>The voting phase has been reset. Please log in to your dashboard to continue with the evaluation process.<br><br>Thank you for your participation.', 'mobility-trailblazers'),
                    $jury_member->post_title
                );
                
                wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
                $notifications_sent++;
            }
        }
        
        return $notifications_sent;
    }

    /**
     * Add diagnostic menu to admin
     */
    public function add_diagnostic_menu() {
        add_submenu_page(
            'mt-award-system',
            'System Diagnostic',
            'Diagnostic',
            'manage_options',
            'mt-diagnostic',
            array($this, 'diagnostic_page')
        );
    }

    /**
     * Enhanced Diagnostic page callback with comprehensive checks and fixes
     */
    public function diagnostic_page() {
        // Handle AJAX actions first
        if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'], 'mt_diagnostic_nonce')) {
            $this->handle_diagnostic_action($_POST['action'], $_POST);
        }
        
        ?>
        <div class="wrap mt-diagnostic-page">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-admin-tools" style="font-size: 36px; width: 36px; height: 36px; margin-right: 10px;"></span>
                <?php _e('Mobility Trailblazers System Diagnostic', 'mobility-trailblazers'); ?>
            </h1>
            
            <hr class="wp-header-end">
            
            <!-- System Overview Cards -->
            <div class="mt-diagnostic-overview">
                <?php $this->render_system_overview(); ?>
            </div>
            
            <!-- Diagnostic Sections -->
            <div class="mt-diagnostic-sections">
                
                <!-- WordPress Environment -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-wordpress"></span> WordPress Environment</h2>
                    <?php $this->check_wordpress_environment(); ?>
                </div>
                
                <!-- Plugin Status -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-admin-plugins"></span> Plugin Status</h2>
                    <?php $this->check_plugin_status(); ?>
                </div>
                
                <!-- Database Status -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-database"></span> Database Status</h2>
                    <?php $this->check_database_status(); ?>
                </div>
                
                <!-- Post Types & Content -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-admin-post"></span> Content Status</h2>
                    <?php $this->check_content_status(); ?>
                </div>
                
                <!-- User Roles & Permissions -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-admin-users"></span> User Roles & Permissions</h2>
                    <?php $this->check_user_permissions(); ?>
                </div>
                
                <!-- Assignments & Evaluations -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-networking"></span> Assignments & Evaluations</h2>
                    <?php $this->check_assignments_evaluations(); ?>
                </div>
                
                <!-- Menu & Navigation -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-menu"></span> Menu & Navigation</h2>
                    <?php $this->check_menu_navigation(); ?>
                </div>
                
                <!-- API & Endpoints -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-rest-api"></span> API & Endpoints</h2>
                    <?php $this->check_api_endpoints(); ?>
                </div>
                
                <!-- File System -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-media-code"></span> File System</h2>
                    <?php $this->check_file_system(); ?>
                </div>
                
                <!-- Performance & Caching -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-performance"></span> Performance & Caching</h2>
                    <?php $this->check_performance_caching(); ?>
                </div>
                
                <!-- Security -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-shield"></span> Security</h2>
                    <?php $this->check_security(); ?>
                </div>
                
            </div>
            
            <!-- Quick Fixes Section -->
            <div class="mt-diagnostic-section">
                <h2><span class="dashicons dashicons-admin-tools"></span> Quick Fixes</h2>
                <?php $this->render_quick_fixes(); ?>
            </div>
            
            <!-- System Logs -->
            <div class="mt-diagnostic-section">
                <h2><span class="dashicons dashicons-media-text"></span> System Logs</h2>
                <?php $this->render_system_logs(); ?>
            </div>
            
            <!-- Export Options -->
            <div class="mt-diagnostic-section">
                <h2><span class="dashicons dashicons-download"></span> Export Diagnostic Report</h2>
                <?php $this->render_export_options(); ?>
            </div>
            
        </div>
        
        <!-- CSS Styles -->
        <style>
        .mt-diagnostic-page {
            max-width: 1200px;
        }
        
        .mt-diagnostic-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .mt-overview-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mt-overview-card h3 {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .mt-overview-card.status-good h3 { color: #46b450; }
        .mt-overview-card.status-warning h3 { color: #ffb900; }
        .mt-overview-card.status-error h3 { color: #dc3232; }
        
        .mt-diagnostic-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .mt-diagnostic-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mt-diagnostic-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .mt-check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .mt-check-item:last-child {
            border-bottom: none;
        }
        
        .mt-check-label {
            font-weight: 600;
            color: #23282d;
        }
        
        .mt-check-details {
            font-size: 0.9em;
            color: #646970;
            margin-top: 2px;
        }
        
        .mt-check-status {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .mt-status-good {
            background: #d4edda;
            color: #155724;
        }
        
        .mt-status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .mt-status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .mt-diagnostic-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .mt-diagnostic-table th,
        .mt-diagnostic-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .mt-diagnostic-table th {
            background: #f9f9f9;
            font-weight: 600;
        }
        
        .mt-fix-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .mt-fix-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #fafafa;
        }
        
        .mt-fix-item h4 {
            margin: 0 0 8px 0;
            color: #23282d;
        }
        
        .mt-fix-item p {
            margin: 0 0 12px 0;
            color: #646970;
            font-size: 0.9em;
        }
        
        .mt-quick-fix-btn {
            width: 100%;
        }
        
        .mt-log-viewer {
            background: #23282d;
            color: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            font-family: Consolas, Monaco, monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .mt-export-actions {
            margin: 15px 0;
        }
        
        .mt-export-actions .button {
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .mt-diagnostic-sections {
                grid-template-columns: 1fr;
            }
            
            .mt-diagnostic-overview {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
        </style>
        
        <!-- JavaScript -->
        <script>
        jQuery(document).ready(function($) {
            // Handle quick fix buttons
            $('.mt-quick-fix-btn').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var action = $button.data('action');
                var originalText = $button.text();
                
                $button.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mt_diagnostic_action',
                        diagnostic_action: action,
                        nonce: '<?php echo wp_create_nonce('mt_diagnostic_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.text('✓ Done').removeClass('button-primary button-secondary').addClass('button-disabled');
                            alert('Success: ' + response.data.message);
                            
                            // Refresh page after 2 seconds
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $button.prop('disabled', false).text(originalText);
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text(originalText);
                        alert('An error occurred while processing the request.');
                    }
                });
            });
        });
        </script>
        
        <?php
    }

    /**
     * Handle diagnostic AJAX actions
     */
    public function handle_diagnostic_ajax() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'mt_diagnostic_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        $action = sanitize_text_field($_POST['diagnostic_action']);
        
        switch ($action) {
            case 'create_test_assignment':
                $result = $this->create_test_assignment();
                if ($result) {
                    wp_send_json_success(array('message' => 'Test assignments created successfully'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to create test assignments'));
                }
                break;
                
            case 'link_current_user':
                $result = $this->link_current_user_to_jury();
                if ($result) {
                    wp_send_json_success(array('message' => 'Current user linked to jury member'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to link user to jury member'));
                }
                break;
                
            case 'create_missing_tables':
                $this->create_database_tables();
                wp_send_json_success(array('message' => 'Database tables created/updated'));
                break;
                
            case 'flush_rewrite_rules':
                flush_rewrite_rules();
                wp_send_json_success(array('message' => 'Rewrite rules flushed'));
                break;
                
            case 'clear_all_caches':
                $this->clear_all_caches();
                wp_send_json_success(array('message' => 'All caches cleared'));
                break;
                
            case 'fix_user_roles':
                $this->fix_user_roles();
                wp_send_json_success(array('message' => 'User roles fixed'));
                break;
                
            case 'sync_jury_users':
                $synced = $this->sync_jury_users();
                wp_send_json_success(array('message' => "Synced {$synced} jury members with users"));
                break;
                
            case 'regenerate_assignments':
                $assigned = $this->regenerate_assignments();
                if ($assigned) {
                    wp_send_json_success(array('message' => "Regenerated {$assigned} assignments"));
                } else {
                    wp_send_json_error(array('message' => 'Failed to regenerate assignments'));
                }
                break;
                
            default:
                wp_send_json_error(array('message' => 'Unknown action'));
        }
    }

    /**
     * Render system overview cards
     */
    private function render_system_overview() {
        global $wpdb;
        
        // Get basic counts
        $candidate_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_candidate' AND post_status = 'publish'");
        $jury_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_jury' AND post_status = 'publish'");
        $assignment_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_mt_assigned_jury_member'
            AND p.post_type = 'mt_candidate'
        ");
        $evaluation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores");
        
        // Determine overall system health
        $health_score = 0;
        $health_issues = array();
        
        if ($candidate_count > 0) $health_score += 25;
        else $health_issues[] = 'No candidates';
        
        if ($jury_count > 0) $health_score += 25;
        else $health_issues[] = 'No jury members';
        
        if ($assignment_count > 0) $health_score += 25;
        else $health_issues[] = 'No assignments';
        
        if ($evaluation_count > 0) $health_score += 25;
        else $health_issues[] = 'No evaluations';
        
        $health_status = $health_score >= 75 ? 'good' : ($health_score >= 50 ? 'warning' : 'error');
        
        ?>
        <div class="mt-overview-card status-<?php echo $health_status; ?>">
            <h3><?php echo $health_score; ?>%</h3>
            <p>System Health</p>
        </div>
        
        <div class="mt-overview-card">
            <h3><?php echo number_format($candidate_count); ?></h3>
            <p>Candidates</p>
        </div>
        
        <div class="mt-overview-card">
            <h3><?php echo number_format($jury_count); ?></h3>
            <p>Jury Members</p>
        </div>
        
        <div class="mt-overview-card">
            <h3><?php echo number_format($assignment_count); ?></h3>
            <p>Assignments</p>
        </div>
        
        <div class="mt-overview-card">
            <h3><?php echo number_format($evaluation_count); ?></h3>
            <p>Evaluations</p>
        </div>
        <?php
    }

    /**
     * Check WordPress environment
     */
    private function check_wordpress_environment() {
        global $wp_version;
        
        $checks = array(
            'WordPress Version' => array(
                'value' => $wp_version,
                'status' => version_compare($wp_version, '5.0', '>=') ? 'good' : 'warning',
                'details' => version_compare($wp_version, '5.0', '>=') ? 'Current' : 'Consider updating'
            ),
            'PHP Version' => array(
                'value' => PHP_VERSION,
                'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'good' : 'error',
                'details' => version_compare(PHP_VERSION, '7.4', '>=') ? 'Compatible' : 'Requires PHP 7.4+'
            ),
            'Memory Limit' => array(
                'value' => ini_get('memory_limit'),
                'status' => $this->parse_size(ini_get('memory_limit')) >= 128 * 1024 * 1024 ? 'good' : 'warning',
                'details' => $this->parse_size(ini_get('memory_limit')) >= 128 * 1024 * 1024 ? 'Sufficient' : 'Consider increasing'
            ),
            'Max Execution Time' => array(
                'value' => ini_get('max_execution_time') . 's',
                'status' => ini_get('max_execution_time') >= 30 ? 'good' : 'warning',
                'details' => ini_get('max_execution_time') >= 30 ? 'Adequate' : 'May cause timeouts'
            ),
            'Debug Mode' => array(
                'value' => WP_DEBUG ? 'Enabled' : 'Disabled',
                'status' => WP_DEBUG ? 'warning' : 'good',
                'details' => WP_DEBUG ? 'Disable in production' : 'Good for production'
            )
        );
        
        $this->render_check_items($checks);
    }

    /**
     * Check plugin status
     */
    private function check_plugin_status() {
        $checks = array(
            'Plugin Active' => array(
                'value' => is_plugin_active(plugin_basename(__FILE__)) ? 'Yes' : 'No',
                'status' => is_plugin_active(plugin_basename(__FILE__)) ? 'good' : 'error',
                'details' => is_plugin_active(plugin_basename(__FILE__)) ? 'Plugin is active' : 'Plugin not active'
            ),
            'Custom Post Types' => array(
                'value' => post_type_exists('mt_candidate') && post_type_exists('mt_jury') ? 'Registered' : 'Missing',
                'status' => post_type_exists('mt_candidate') && post_type_exists('mt_jury') ? 'good' : 'error',
                'details' => post_type_exists('mt_candidate') && post_type_exists('mt_jury') ? 'All registered' : 'Some missing'
            ),
            'Custom Roles' => array(
                'value' => get_role('mt_jury_member') ? 'Created' : 'Missing',
                'status' => get_role('mt_jury_member') ? 'good' : 'error',
                'details' => get_role('mt_jury_member') ? 'Jury role exists' : 'Role not created'
            ),
            'Taxonomies' => array(
                'value' => taxonomy_exists('mt_category') ? 'Registered' : 'Missing',
                'status' => taxonomy_exists('mt_category') ? 'good' : 'error',
                'details' => taxonomy_exists('mt_category') ? 'Categories available' : 'Taxonomy missing'
            )
        );
        
        $this->render_check_items($checks);
    }

    /**
     * Check database status
     */
    private function check_database_status() {
        global $wpdb;
        
        $required_tables = array(
            'mt_candidate_scores' => 'Stores jury evaluations',
            'mt_votes' => 'Stores jury votes',
            'mt_public_votes' => 'Stores public votes',
            'vote_reset_logs' => 'Vote reset audit trail',
            'vote_backups' => 'Vote backup storage'
        );
        
        echo '<table class="mt-diagnostic-table">';
        echo '<thead><tr><th>Table</th><th>Status</th><th>Records</th><th>Purpose</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($required_tables as $table => $purpose) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
                $status = 'good';
                $status_text = '✅ Exists';
                $actions = '<button class="button button-small" onclick="alert(\'Table: ' . $table . '\\nRecords: ' . $count . '\')">View Info</button>';
            } else {
                $count = '-';
                $status = 'error';
                $status_text = '❌ Missing';
                $actions = '<button class="button button-small mt-quick-fix-btn" data-action="create_missing_tables">Create</button>';
            }
            
            echo "<tr>";
            echo "<td><strong>{$table}</strong></td>";
            echo "<td class='mt-status-{$status}'>{$status_text}</td>";
            echo "<td>{$count}</td>";
            echo "<td>{$purpose}</td>";
            echo "<td>{$actions}</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table>';
    }

    /**
     * Check content status
     */
    private function check_content_status() {
        global $wpdb;
        
        $candidate_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_candidate' AND post_status = 'publish'");
        $jury_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_jury' AND post_status = 'publish'");
        $draft_candidates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_candidate' AND post_status = 'draft'");
        $categories = wp_count_terms('mt_category');
        
        $checks = array(
            'Published Candidates' => array(
                'value' => $candidate_count,
                'status' => $candidate_count > 0 ? 'good' : 'warning',
                'details' => $candidate_count > 0 ? 'Candidates available' : 'No published candidates'
            ),
            'Draft Candidates' => array(
                'value' => $draft_candidates,
                'status' => 'good',
                'details' => $draft_candidates . ' candidates in draft'
            ),
            'Jury Members' => array(
                'value' => $jury_count,
                'status' => $jury_count > 0 ? 'good' : 'warning',
                'details' => $jury_count > 0 ? 'Jury members available' : 'No jury members'
            ),
            'Categories' => array(
                'value' => $categories,
                'status' => $categories > 0 ? 'good' : 'warning',
                'details' => $categories > 0 ? 'Categories defined' : 'No categories'
            )
        );
        
        $this->render_check_items($checks);
    }

    /**
     * Check user permissions
     */
    private function check_user_permissions() {
        $current_user = wp_get_current_user();
        $jury_role = get_role('mt_jury_member');
        $jury_users = get_users(array('role' => 'mt_jury_member'));
        
        $checks = array(
            'Current User Role' => array(
                'value' => implode(', ', $current_user->roles),
                'status' => current_user_can('manage_options') ? 'good' : 'warning',
                'details' => current_user_can('manage_options') ? 'Admin access' : 'Limited access'
            ),
            'Jury Role Exists' => array(
                'value' => $jury_role ? 'Yes' : 'No',
                'status' => $jury_role ? 'good' : 'error',
                'details' => $jury_role ? 'Role properly defined' : 'Role missing'
            ),
            'Jury Users' => array(
                'value' => count($jury_users),
                'status' => count($jury_users) > 0 ? 'good' : 'warning',
                'details' => count($jury_users) . ' users with jury role'
            ),
            'Current User Jury Status' => array(
                'value' => $this->is_jury_member($current_user->ID) ? 'Yes' : 'No',
                'status' => $this->is_jury_member($current_user->ID) ? 'good' : 'warning',
                'details' => $this->is_jury_member($current_user->ID) ? 'Can access jury features' : 'No jury access'
            )
        );
        
        $this->render_check_items($checks);
    }

    /**
     * Check assignments and evaluations
     */
    private function check_assignments_evaluations() {
        global $wpdb;
        
        $assignment_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_mt_assigned_jury_member'
            AND p.post_type = 'mt_candidate'
        ");
        
        $evaluation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores");
        $jury_links = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'mt_jury'
            AND (pm.meta_key = '_mt_jury_user_id' OR pm.meta_key = '_mt_jury_email')
        ");
        
        $checks = array(
            'Candidate Assignments' => array(
                'value' => $assignment_count,
                'status' => $assignment_count > 0 ? 'good' : 'error',
                'details' => $assignment_count > 0 ? 'Candidates assigned to jury' : 'No assignments found'
            ),
            'Jury-User Links' => array(
                'value' => $jury_links,
                'status' => $jury_links > 0 ? 'good' : 'error',
                'details' => $jury_links > 0 ? 'Jury members linked to users' : 'No jury-user links'
            ),
            'Completed Evaluations' => array(
                'value' => $evaluation_count,
                'status' => $evaluation_count > 0 ? 'good' : 'warning',
                'details' => $evaluation_count . ' evaluations completed'
            )
        );
        
        $this->render_check_items($checks);
        
        // Show sample assignments if they exist
        if ($assignment_count > 0) {
            echo '<h4>Sample Assignments</h4>';
            $assignments = $wpdb->get_results("
                SELECT p.post_title as candidate_name, j.post_title as jury_name 
                FROM {$wpdb->postmeta} pm
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                JOIN {$wpdb->posts} j ON pm.meta_value = j.ID
                WHERE pm.meta_key = '_mt_assigned_jury_member'
                AND p.post_type = 'mt_candidate'
                AND j.post_type = 'mt_jury'
                LIMIT 5
            ");
            
            echo '<table class="mt-diagnostic-table">';
            echo '<thead><tr><th>Candidate</th><th>Assigned to Jury</th></tr></thead><tbody>';
            foreach ($assignments as $assignment) {
                echo "<tr><td>{$assignment->candidate_name}</td><td>{$assignment->jury_name}</td></tr>";
            }
            echo '</tbody></table>';
        }
    }

    /**
     * Check menu and navigation
     */
    private function check_menu_navigation() {
        global $menu, $submenu;
        
        $parent_menu_exists = false;
        foreach ($menu as $item) {
            if ($item[2] === 'mt-award-system') {
                $parent_menu_exists = true;
                break;
            }
        }
        
        $jury_dashboard_exists = false;
        if (isset($submenu['mt-award-system'])) {
            foreach ($submenu['mt-award-system'] as $item) {
                if ($item[2] === 'mt-jury-dashboard') {
                    $jury_dashboard_exists = true;
                    break;
                }
            }
        }
        
        $checks = array(
            'Main Menu' => array(
                'value' => $parent_menu_exists ? 'Registered' : 'Missing',
                'status' => $parent_menu_exists ? 'good' : 'error',
                'details' => $parent_menu_exists ? 'MT Award System menu exists' : 'Main menu not registered'
            ),
            'Jury Dashboard Menu' => array(
                'value' => $jury_dashboard_exists ? 'Registered' : 'Missing',
                'status' => $jury_dashboard_exists ? 'good' : 'error',
                'details' => $jury_dashboard_exists ? 'Jury dashboard accessible' : 'Dashboard menu missing'
            ),
            'Submenu Count' => array(
                'value' => isset($submenu['mt-award-system']) ? count($submenu['mt-award-system']) : 0,
                'status' => 'good',
                'details' => 'Available submenus'
            )
        );
        
        $this->render_check_items($checks);
        
        // Show all submenus
        if (isset($submenu['mt-award-system'])) {
            echo '<h4>Available Submenus</h4>';
            echo '<ul>';
            foreach ($submenu['mt-award-system'] as $item) {
                echo '<li><strong>' . $item[0] . '</strong> (' . $item[2] . ')</li>';
            }
            echo '</ul>';
        }
    }

    /**
     * Check API endpoints
     */
    private function check_api_endpoints() {
        $rest_url = get_rest_url();
        $endpoints = array(
            'REST API Base' => $rest_url,
            'Vote Reset API' => $rest_url . 'mobility-trailblazers/v1/reset-vote',
            'Bulk Reset API' => $rest_url . 'mobility-trailblazers/v1/admin/bulk-reset',
            'Reset History API' => $rest_url . 'mobility-trailblazers/v1/reset-history'
        );
        
        echo '<table class="mt-diagnostic-table">';
        echo '<thead><tr><th>Endpoint</th><th>URL</th><th>Status</th></tr></thead><tbody>';
        
        foreach ($endpoints as $name => $url) {
            $status = $this->test_endpoint($url);
            $status_class = $status ? 'good' : 'warning';
            $status_text = $status ? '✅ Available' : '⚠️ Check manually';
            
            echo "<tr>";
            echo "<td><strong>{$name}</strong></td>";
            echo "<td><code>{$url}</code></td>";
            echo "<td class='mt-status-{$status_class}'>{$status_text}</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table>';
    }

    /**
     * Check file system
     */
    private function check_file_system() {
        $plugin_dir = plugin_dir_path(__FILE__);
        $upload_dir = wp_upload_dir();
        
        $files_to_check = array(
            'Main Plugin File' => __FILE__,
            'Admin CSS' => $plugin_dir . 'admin/css/vote-reset-admin.css',
            'Admin JS' => $plugin_dir . 'admin/js/vote-reset-admin.js',
            'Frontend CSS' => $plugin_dir . 'assets/frontend.css',
            'Frontend JS' => $plugin_dir . 'assets/frontend.js',
            'Jury Dashboard Template' => $plugin_dir . 'templates/jury-dashboard.php'
        );
        
        $checks = array(
            'Plugin Directory Writable' => array(
                'value' => is_writable($plugin_dir) ? 'Yes' : 'No',
                'status' => is_writable($plugin_dir) ? 'good' : 'warning',
                'details' => is_writable($plugin_dir) ? 'Can write files' : 'Limited write access'
            ),
            'Upload Directory Writable' => array(
                'value' => is_writable($upload_dir['basedir']) ? 'Yes' : 'No',
                'status' => is_writable($upload_dir['basedir']) ? 'good' : 'error',
                'details' => is_writable($upload_dir['basedir']) ? 'Can upload files' : 'Cannot upload files'
            )
        );
        
        $this->render_check_items($checks);
        
        echo '<h4>File Existence Check</h4>';
        echo '<table class="mt-diagnostic-table">';
        echo '<thead><tr><th>File</th><th>Status</th><th>Size</th></tr></thead><tbody>';
        
        foreach ($files_to_check as $name => $path) {
            $exists = file_exists($path);
            $size = $exists ? size_format(filesize($path)) : '-';
            $status_class = $exists ? 'good' : 'error';
            $status_text = $exists ? '✅ Exists' : '❌ Missing';
            
            echo "<tr>";
            echo "<td><strong>{$name}</strong></td>";
            echo "<td class='mt-status-{$status_class}'>{$status_text}</td>";
            echo "<td>{$size}</td>";
            echo "</tr>";
        }
        
        echo '</tbody></table>';
    }

    /**
     * Check performance and caching
     */
    private function check_performance_caching() {
        $object_cache = wp_using_ext_object_cache();
        $opcache_enabled = function_exists('opcache_get_status') && opcache_get_status();
        
        $checks = array(
            'Object Cache' => array(
                'value' => $object_cache ? 'Active' : 'Not Active',
                'status' => $object_cache ? 'good' : 'warning',
                'details' => $object_cache ? 'External object cache in use' : 'Using default cache'
            ),
            'OPcache' => array(
                'value' => $opcache_enabled ? 'Enabled' : 'Disabled',
                'status' => $opcache_enabled ? 'good' : 'warning',
                'details' => $opcache_enabled ? 'PHP bytecode cache active' : 'Consider enabling OPcache'
            ),
            'Query Count' => array(
                'value' => get_num_queries(),
                'status' => get_num_queries() < 50 ? 'good' : 'warning',
                'details' => get_num_queries() . ' queries on this page'
            )
        );
        
        $this->render_check_items($checks);
    }

    /**
     * Check security
     */
    private function check_security() {
        $checks = array(
            'SSL/HTTPS' => array(
                'value' => is_ssl() ? 'Enabled' : 'Disabled',
                'status' => is_ssl() ? 'good' : 'warning',
                'details' => is_ssl() ? 'Secure connection' : 'Consider enabling HTTPS'
            ),
            'File Editing' => array(
                'value' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'Disabled' : 'Enabled',
                'status' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'good' : 'warning',
                'details' => defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT ? 'File editing disabled' : 'Consider disabling file editing'
            ),
            'Database Prefix' => array(
                'value' => $GLOBALS['wpdb']->prefix !== 'wp_' ? 'Custom' : 'Default',
                'status' => $GLOBALS['wpdb']->prefix !== 'wp_' ? 'good' : 'warning',
                'details' => $GLOBALS['wpdb']->prefix !== 'wp_' ? 'Using custom prefix' : 'Consider custom prefix'
            )
        );
        
        $this->render_check_items($checks);
    }

    /**
     * Render quick fixes section
     */
    private function render_quick_fixes() {
        ?>
        <div class="mt-fix-grid">
            <div class="mt-fix-item">
                <h4>Create Test Assignment</h4>
                <p>Creates sample assignments between candidates and jury members for testing.</p>
                <button class="button button-primary mt-quick-fix-btn" data-action="create_test_assignment">
                    Create Test Assignment
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4>Link Current User</h4>
                <p>Links your current user account to the first jury member for testing access.</p>
                <button class="button button-primary mt-quick-fix-btn" data-action="link_current_user">
                    Link Current User
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4>Create Missing Tables</h4>
                <p>Creates any missing database tables required by the plugin.</p>
                <button class="button button-primary mt-quick-fix-btn" data-action="create_missing_tables">
                    Create Tables
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4>Flush Rewrite Rules</h4>
                <p>Refreshes WordPress URL rewrite rules to fix routing issues.</p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="flush_rewrite_rules">
                    Flush Rules
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4>Clear All Caches</h4>
                <p>Clears WordPress transients and object cache to resolve caching issues.</p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="clear_all_caches">
                    Clear Caches
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4>Fix User Roles</h4>
                <p>Recreates the jury member role with proper capabilities.</p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="fix_user_roles">
                    Fix Roles
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4>Sync Jury Users</h4>
                <p>Synchronizes jury post records with WordPress user accounts.</p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="sync_jury_users">
                    Sync Users
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4>Regenerate Assignments</h4>
                <p>Automatically assigns all candidates to jury members using round-robin.</p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="regenerate_assignments">
                    Auto Assign
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Render system logs
     */
    private function render_system_logs() {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if (file_exists($log_file) && is_readable($log_file)) {
            $log_content = file_get_contents($log_file);
            $lines = explode("\n", $log_content);
            $recent_lines = array_slice($lines, -50); // Last 50 lines
            
            echo '<h4>Recent Debug Log (Last 50 lines)</h4>';
            echo '<div class="mt-log-viewer">';
            echo esc_html(implode("\n", $recent_lines));
            echo '</div>';
        } else {
            echo '<p>Debug log not available or not readable.</p>';
            echo '<p>To enable logging, add these lines to wp-config.php:</p>';
            echo '<pre>define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);
define(\'WP_DEBUG_DISPLAY\', false);</pre>';
        }
    }

    /**
     * Render export options
     */
    private function render_export_options() {
        ?>
        <p>Export a comprehensive diagnostic report for troubleshooting or support.</p>
        <div class="mt-export-actions">
            <button class="button button-primary" onclick="exportDiagnosticReport('json')">
                Export as JSON
            </button>
            <button class="button button-secondary" onclick="exportDiagnosticReport('txt')">
                Export as Text
            </button>
        </div>
        
        <script>
        function exportDiagnosticReport(format) {
            var data = {
                timestamp: new Date().toISOString(),
                wordpress_version: '<?php echo $GLOBALS["wp_version"]; ?>',
                php_version: '<?php echo PHP_VERSION; ?>',
                plugin_version: '1.0.0',
                site_url: '<?php echo site_url(); ?>',
                checks: {}
            };
            
            // Collect all diagnostic data
            jQuery('.mt-check-item').each(function() {
                var label = jQuery(this).find('.mt-check-label').text();
                var value = jQuery(this).find('.mt-check-status').text();
                data.checks[label] = value;
            });
            
            var content = format === 'json' ? JSON.stringify(data, null, 2) : formatAsText(data);
            var filename = 'mt-diagnostic-' + new Date().toISOString().split('T')[0] + '.' + format;
            
            downloadFile(content, filename, format === 'json' ? 'application/json' : 'text/plain');
        }
        
        function formatAsText(data) {
            var text = 'Mobility Trailblazers Diagnostic Report\n';
            text += '=====================================\n\n';
            text += 'Generated: ' + data.timestamp + '\n';
            text += 'WordPress: ' + data.wordpress_version + '\n';
            text += 'PHP: ' + data.php_version + '\n';
            text += 'Site: ' + data.site_url + '\n\n';
            text += 'System Checks:\n';
            text += '--------------\n';
            
            for (var check in data.checks) {
                text += check + ': ' + data.checks[check] + '\n';
            }
            
            return text;
        }
        
        function downloadFile(content, filename, contentType) {
            var blob = new Blob([content], { type: contentType });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        </script>
        <?php
    }

    /**
     * Helper method to render check items
     */
    private function render_check_items($checks) {
        foreach ($checks as $label => $check) {
            echo '<div class="mt-check-item">';
            echo '<div>';
            echo '<div class="mt-check-label">' . esc_html($label) . '</div>';
            if (isset($check['details'])) {
                echo '<div class="mt-check-details">' . esc_html($check['details']) . '</div>';
            }
            echo '</div>';
            echo '<div class="mt-check-status mt-status-' . esc_attr($check['status']) . '">';
            echo esc_html($check['value']);
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Helper methods for diagnostic functions
     */
    private function parse_size($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        if ($unit) {
            return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        return round($size);
    }

    private function test_endpoint($url) {
        // Simple check - in a real implementation, you might want to make an actual HTTP request
        return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
    }

    private function clear_all_caches() {
        // Clear WordPress transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        return true;
    }

    private function fix_user_roles() {
        // Recreate the jury member role
        remove_role('mt_jury_member');
        add_role('mt_jury_member', 'Jury Member', array(
            'read' => true,
            'mt_jury_member' => true,
            'edit_posts' => false,
            'delete_posts' => false
        ));
        return true;
    }

    private function sync_jury_users() {
        // Get all jury posts
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $synced = 0;
        foreach ($jury_posts as $jury_post) {
            $email = get_post_meta($jury_post->ID, '_mt_jury_email', true);
            if ($email) {
                $user = get_user_by('email', $email);
                if ($user) {
                    update_post_meta($jury_post->ID, '_mt_jury_user_id', $user->ID);
                    $synced++;
                }
            }
        }
        
        return $synced;
    }

    private function regenerate_assignments() {
        $candidates = get_posts(array('post_type' => 'mt_candidate', 'posts_per_page' => -1));
        $jury_members = get_posts(array('post_type' => 'mt_jury', 'posts_per_page' => -1));
        
        if (empty($candidates) || empty($jury_members)) {
            return false;
        }
        
        $assigned = 0;
        foreach ($candidates as $index => $candidate) {
            $jury_index = $index % count($jury_members);
            update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_members[$jury_index]->ID);
            $assigned++;
        }
        
        return $assigned;
    }

    /**
     * Create test assignment between candidates and jury members
     */
    private function create_test_assignment() {
        $candidates = get_posts(array('post_type' => 'mt_candidate', 'posts_per_page' => 3));
        $jury_members = get_posts(array('post_type' => 'mt_jury', 'posts_per_page' => -1));
        
        if ($candidates && $jury_members) {
            foreach ($candidates as $index => $candidate) {
                $jury_index = $index % count($jury_members);
                update_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_members[$jury_index]->ID);
            }
            return true;
        }
        return false;
    }

    /**
     * Link current user to first jury member
     */
    private function link_current_user_to_jury() {
        $current_user = wp_get_current_user();
        $jury_members = get_posts(array('post_type' => 'mt_jury', 'posts_per_page' => 1));
        
        if ($jury_members) {
            update_post_meta($jury_members[0]->ID, '_mt_jury_user_id', $current_user->ID);
            return true;
        }
        return false;
    }

    /**
     * Handle evaluation form submission
     */
    public function handle_evaluation_submission() {
        // Verify nonce
        if (!isset($_POST['mt_evaluation_nonce']) || !wp_verify_nonce($_POST['mt_evaluation_nonce'], 'mt_evaluation')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        $jury_member_id = intval($_POST['jury_member_id']);
        $edit_mode = $_POST['edit_mode'] == '1';
        
        // Verify user permission
        $current_user_id = get_current_user_id();
        $expected_jury_id = $this->get_jury_member_for_user($current_user_id);
        
        if ($expected_jury_id != $jury_member_id && !current_user_can('manage_options')) {
            wp_die(__('Permission denied.', 'mobility-trailblazers'));
        }
        
        // Collect scores
        $scores = array(
            'courage_score' => intval($_POST['courage_score']),
            'innovation_score' => intval($_POST['innovation_score']),
            'implementation_score' => intval($_POST['implementation_score']),
            'relevance_score' => intval($_POST['relevance_score']),
            'visibility_score' => intval($_POST['visibility_score'])
        );
        
        // Calculate total score
        $total_score = array_sum($scores);
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $data = array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $jury_member_id,
            'courage_score' => $scores['courage_score'],
            'innovation_score' => $scores['innovation_score'],
            'implementation_score' => $scores['implementation_score'],
            'relevance_score' => $scores['relevance_score'],
            'visibility_score' => $scores['visibility_score'],
            'total_score' => $total_score,
            'comments' => sanitize_textarea_field($_POST['comments']),
            'evaluated_at' => current_time('mysql')
        );
        
        if ($edit_mode) {
            // Update existing evaluation
            $wpdb->update(
                $table_name,
                $data,
                array(
                    'candidate_id' => $candidate_id,
                    'jury_member_id' => $jury_member_id
                )
            );
        } else {
            // Insert new evaluation
            $wpdb->insert($table_name, $data);
        }
        
        // Redirect back to dashboard with success message
        wp_redirect(add_query_arg(array(
            'page' => 'mt-jury-dashboard',
            'message' => 'evaluation_saved'
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Debug function to help troubleshoot jury access issues
     */
    public function debug_jury_access() {
        if (!isset($_GET['mt_debug_access'])) {
            return;
        }
        
        ob_start();
        
        $current_user = wp_get_current_user();
        
        echo '<div style="background: #f0f0f0; padding: 20px; margin: 20px;">';
        echo '<h2>Debug Jury Access</h2>';
        echo '<p><strong>User ID:</strong> ' . $current_user->ID . '</p>';
        echo '<p><strong>Email:</strong> ' . $current_user->user_email . '</p>';
        echo '<p><strong>Roles:</strong> ' . implode(', ', $current_user->roles) . '</p>';
        echo '<p><strong>Has mt_jury_member role:</strong> ' . (in_array('mt_jury_member', $current_user->roles) ? 'YES' : 'NO') . '</p>';
        
        // Check capabilities
        echo '<h3>Capabilities:</h3>';
        echo '<ul>';
        echo '<li>read: ' . (current_user_can('read') ? 'YES' : 'NO') . '</li>';
        echo '<li>mt_access_jury_dashboard: ' . (current_user_can('mt_access_jury_dashboard') ? 'YES' : 'NO') . '</li>';
        echo '<li>mt_submit_evaluations: ' . (current_user_can('mt_submit_evaluations') ? 'YES' : 'NO') . '</li>';
        echo '<li>manage_options: ' . (current_user_can('manage_options') ? 'YES' : 'NO') . '</li>';
        echo '</ul>';
        
        // Check jury member linkage
        $jury_member_id = $this->get_jury_member_for_user($current_user->ID);
        echo '<p><strong>Linked to jury member:</strong> ' . ($jury_member_id ? 'YES (ID: ' . $jury_member_id . ')' : 'NO') . '</p>';
        
        echo '</div>';
        
        $output = ob_get_clean();
        add_action('admin_notices', function() use ($output) {
            echo $output;
        });
    }

    /**
     * Debug function to check evaluation access
     */
    public function debug_evaluation_access() {
        // Debug code for evaluation access
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ob_start();
        
        $user_id = get_current_user_id();
        $jury_member_id = $this->get_jury_member_for_user($user_id);
        
        if (!$jury_member_id) {
            echo '<div class="notice notice-error"><p>No jury member found for current user.</p></div>';
            $output = ob_get_clean();
            add_action('admin_notices', function() use ($output) {
                echo $output;
            });
            return;
        }
        
        $jury_member = get_post($jury_member_id);
        if (!$jury_member) {
            echo '<div class="notice notice-error"><p>Jury member post not found.</p></div>';
            $output = ob_get_clean();
            add_action('admin_notices', function() use ($output) {
                echo $output;
            });
            return;
        }
        
        $assigned_candidates = $this->get_assigned_candidates($jury_member_id);
        
        echo '<div class="notice notice-info">';
        echo '<p>Jury Member: ' . esc_html($jury_member->post_title) . '</p>';
        echo '<p>Assigned Candidates: ' . count($assigned_candidates) . '</p>';
        echo '</div>';
        
        $output = ob_get_clean();
        add_action('admin_notices', function() use ($output) {
            echo $output;
        });
    }

    /**
     * Load Elementor compatibility
     */
    public function load_elementor_compatibility() {
        // Check if Elementor is active
        if (did_action('elementor/loaded')) {
            require_once MT_PLUGIN_PATH . 'includes/class-mt-elementor-compat.php';
            new MT_Elementor_Compatibility();
        }
    }

    /**
     * Handle create backup REST API request
     */
    public function handle_create_backup($request) {
        $reason = sanitize_text_field($request->get_param('reason') ?? 'Manual backup via API');
        
        // Check if MT_Vote_Backup_Manager class exists
        if (!class_exists('MT_Vote_Backup_Manager')) {
            // Create backup using direct database queries
            global $wpdb;
            
            $votes_table = $wpdb->prefix . 'mt_candidate_scores';
            $votes_history_table = $wpdb->prefix . 'mt_votes_history';
            $scores_history_table = $wpdb->prefix . 'mt_candidate_scores_history';
            
            // Get all votes for backup
            $votes = $wpdb->get_results("SELECT * FROM $votes_table WHERE 1=1");
            $votes_backed_up = 0;
            $scores_backed_up = 0;
            
            // Create simple backup entries
            foreach ($votes as $vote) {
                $wpdb->insert($votes_history_table, array(
                    'vote_id' => $vote->id,
                    'candidate_id' => $vote->candidate_id,
                    'jury_member_id' => $vote->jury_member_id,
                    'created_at' => current_time('mysql'),
                    'created_by' => get_current_user_id(),
                    'reason' => $reason
                ));
                $votes_backed_up++;
            }
            
            $scores_backed_up = $votes_backed_up; // For simplicity
            
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Full backup created successfully',
                'data' => array(
                    'votes_backed_up' => $votes_backed_up,
                    'scores_backed_up' => $scores_backed_up,
                    'timestamp' => current_time('mysql')
                )
            ), 200);
        }
        
        // Use backup manager if available
        $backup_manager = new MT_Vote_Backup_Manager();
        
        // Create full backup
        $votes_backup = $backup_manager->bulk_backup('vote', array(), $reason);
        $scores_backup = $backup_manager->bulk_backup('score', array(), $reason);
        
        if ($votes_backup && $scores_backup) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Full backup created successfully',
                'data' => array(
                    'votes_backed_up' => $votes_backup,
                    'scores_backed_up' => $scores_backup,
                    'timestamp' => current_time('mysql')
                )
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to create backup'
        ), 500);
    }

    /**
     * Get backup history REST API request
     */
    public function get_backup_history($request) {
        $page = intval($request->get_param('page') ?? 1);
        $per_page = intval($request->get_param('per_page') ?? 50);
        
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        // Try to get backups from both backup tables if they exist
        $backups = array();
        
        // Check if backup tables exist
        $votes_history_table = $wpdb->prefix . 'mt_votes_history';
        $scores_history_table = $wpdb->prefix . 'mt_candidate_scores_history';
        
        // Combine results from both backup tables if they exist
        $query = "
            SELECT 'vote' as type, id, vote_id as item_id, created_at, created_by, reason, restored_at 
            FROM {$votes_history_table}
            WHERE created_at IS NOT NULL
            ORDER BY created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $vote_backups = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset));
        
        if ($vote_backups) {
            foreach ($vote_backups as &$backup) {
                $user = get_userdata($backup->created_by);
                $backup->created_by_name = $user ? $user->display_name : 'Unknown';
            }
            $backups = array_merge($backups, $vote_backups);
        }
        
        // Get scores backup if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$scores_history_table}'") == $scores_history_table) {
            $scores_query = "
                SELECT 'score' as type, id, candidate_id as item_id, created_at, created_by, reason, restored_at
                FROM {$scores_history_table}
                WHERE created_at IS NOT NULL
                ORDER BY created_at DESC
                LIMIT %d OFFSET %d
            ";
            
            $score_backups = $wpdb->get_results($wpdb->prepare($scores_query, $per_page, $offset));
            
            if ($score_backups) {
                foreach ($score_backups as &$backup) {
                    $user = get_userdata($backup->created_by);
                    $backup->created_by_name = $user ? $user->display_name : 'Unknown';
                }
                $backups = array_merge($backups, $score_backups);
            }
        }
        
        // Sort combined results by created_at descending
        usort($backups, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        
        return new WP_REST_Response(array(
            'success' => true,
            'backups' => $backups,
            'page' => $page,
            'per_page' => $per_page
        ), 200);
    }

    /**
     * Handle restore backup REST API request
     */
    public function handle_restore_backup($request) {
        $backup_id = intval($request->get_param('backup_id'));
        $backup_type = sanitize_text_field($request->get_param('backup_type'));
        
        if (!in_array($backup_type, array('vote', 'score'))) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Invalid backup type'
            ), 400);
        }
        
        // Check if backup manager exists
        if (!class_exists('MT_Vote_Backup_Manager')) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Backup manager not available'
            ), 500);
        }
        
        $backup_manager = new MT_Vote_Backup_Manager();
        $result = $backup_manager->restore_from_backup($backup_id, $backup_type);
        
        if ($result) {
            return new WP_REST_Response(array(
                'success' => true,
                'message' => 'Backup restored successfully'
            ), 200);
        }
        
        return new WP_REST_Response(array(
            'success' => false,
            'message' => 'Failed to restore backup'
        ), 500);
    }

    /**
     * Handle export backup history AJAX request
     */
    public function handle_export_backup_history() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'mobility-trailblazers'));
        }
        
        // Verify nonce - check both GET and POST
        $nonce = $_GET['nonce'] ?? $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'mt_vote_reset_nonce')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        $format = sanitize_text_field($_GET['format'] ?? $_POST['format'] ?? 'csv');
        
        // Simple export without backup manager dependency
        global $wpdb;
        
        // Get backup data from both tables
        $votes_history_table = $wpdb->prefix . 'mt_votes_history';
        $scores_history_table = $wpdb->prefix . 'mt_candidate_scores_history';
        
        $backups = array();
        
        // Get vote backups
        if ($wpdb->get_var("SHOW TABLES LIKE '{$votes_history_table}'") == $votes_history_table) {
            $vote_backups = $wpdb->get_results("
                SELECT 'vote' as type, id, vote_id as item_id, created_at, created_by, reason, restored_at 
                FROM {$votes_history_table}
                ORDER BY created_at DESC
            ");
            $backups = array_merge($backups, $vote_backups);
        }
        
        // Get score backups
        if ($wpdb->get_var("SHOW TABLES LIKE '{$scores_history_table}'") == $scores_history_table) {
            $score_backups = $wpdb->get_results("
                SELECT 'score' as type, id, candidate_id as item_id, created_at, created_by, reason, restored_at
                FROM {$scores_history_table}
                ORDER BY created_at DESC
            ");
            $backups = array_merge($backups, $score_backups);
        }
        
        // Add user names
        foreach ($backups as &$backup) {
            $user = get_userdata($backup->created_by);
            $backup->created_by_name = $user ? $user->display_name : 'Unknown';
        }
        
        $filename = 'backup-history-' . date('Y-m-d-His');
        
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            
            // Output CSV
            $output = fopen('php://output', 'w');
            fputcsv($output, array('ID', 'Type', 'Item ID', 'Created At', 'Created By', 'Reason', 'Restored At'));
            
            foreach ($backups as $backup) {
                fputcsv($output, array(
                    $backup->id,
                    $backup->type,
                    $backup->item_id,
                    $backup->created_at,
                    $backup->created_by_name,
                    $backup->reason,
                    $backup->restored_at
                ));
            }
            
            fclose($output);
        } else {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            
            echo json_encode(array(
                'export_date' => current_time('mysql'),
                'total_backups' => count($backups),
                'backups' => $backups
            ), JSON_PRETTY_PRINT);
        }
        
        wp_die();
    }

    /**
     * Handle AJAX reset vote request
     */
    public function handle_ajax_reset_vote() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $vote_id = intval($_POST['vote_id']);
        
        // Load reset manager if needed
        if (!class_exists('MT_Vote_Reset_Manager')) {
            wp_send_json_error('Reset manager not available');
        }
        
        $reset_manager = new MT_Vote_Reset_Manager();
        $result = $reset_manager->reset_individual_vote($vote_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Vote reset successfully'));
        } else {
            wp_send_json_error('Failed to reset vote');
        }
    }

    /**
     * Handle AJAX bulk reset request
     */
    public function handle_ajax_bulk_reset() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        
        // Load reset manager if needed
        if (!class_exists('MT_Vote_Reset_Manager')) {
            wp_send_json_error('Reset manager not available');
        }
        
        $reset_manager = new MT_Vote_Reset_Manager();
        $result = $reset_manager->bulk_reset_candidate($candidate_id);
        
        if ($result) {
            wp_send_json_success(array('message' => 'All votes for candidate reset successfully'));
        } else {
            wp_send_json_error('Failed to reset votes');
        }
    }

    /**
     * Handle AJAX get reset history request
     */
    public function handle_ajax_get_reset_history() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        // Load audit logger if needed
        if (!class_exists('MT_Vote_Audit_Logger')) {
            wp_send_json_error('Audit logger not available');
        }
        
        $audit_logger = new MT_Vote_Audit_Logger();
        $history = $audit_logger->get_reset_history(1, 50);
        
        wp_send_json_success(array('history' => $history));
    }

    /**
     * Handle AJAX get jury stats request
     */
    public function handle_ajax_get_jury_stats() {
        check_ajax_referer('mt_vote_reset_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        
        global $wpdb;
        
        // Get total votes
        $total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE deleted_at IS NULL");
        
        // Get active jury members (those who have voted)
        $active_jury = $wpdb->get_var("SELECT COUNT(DISTINCT jury_member_id) FROM {$wpdb->prefix}mt_votes WHERE deleted_at IS NULL");
        
        // Calculate completion rate (example: assume 50 jury members total)
        $total_jury_members = 50; // You should get this from your jury members table
        $completion_rate = $total_jury_members > 0 ? round(($active_jury / $total_jury_members) * 100) . '%' : '0%';
        
        wp_send_json_success(array(
            'total_votes' => $total_votes,
            'active_jury' => $active_jury,
            'completion_rate' => $completion_rate
        ));
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        // Register vote reset routes
        $this->register_vote_reset_routes();
        
        // Register backup API endpoints directly
        // Create backup endpoint
        register_rest_route('mobility-trailblazers/v1', '/admin/create-backup', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_create_backup'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => array(
                'reason' => array(
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'type' => array(
                    'required' => false,
                    'type' => 'string',
                    'default' => 'full'
                )
            )
        ));
        
        // Get backup history endpoint
        register_rest_route('mobility-trailblazers/v1', '/backup-history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_backup_history'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
        
        // Restore backup endpoint
        register_rest_route('mobility-trailblazers/v1', '/admin/restore-backup', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_restore_backup'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            }
        ));
    }

    /**
     * Register REST API routes for vote reset functionality
     */
    public function register_vote_reset_routes() {
        // Individual reset endpoint
        register_rest_route('mobility-trailblazers/v1', '/reset-vote', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_reset_vote'),
            'permission_callback' => function() {
                return is_user_logged_in() && (current_user_can('mt_jury_member') || current_user_can('manage_options'));
            },
            'args' => array(
                'candidate_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'reason' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Bulk reset endpoint
        register_rest_route('mobility-trailblazers/v1', '/admin/bulk-reset', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_bulk_reset'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => array(
                'reset_scope' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return in_array($param, array(
                            'phase_transition',
                            'all_user_votes',
                            'all_candidate_votes',
                            'full_reset'
                        ));
                    }
                ),
                'options' => array(
                    'required' => false,
                    'type' => 'object'
                )
            )
        ));
        
        // Reset history endpoint
        register_rest_route('mobility-trailblazers/v1', '/reset-history', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reset_history'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
            'args' => array(
                'page' => array(
                    'default' => 1,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    }
                ),
                'per_page' => array(
                    'default' => 20,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                )
            )
        ));
    }

    /**
     * Handle individual vote reset
     */
    public function handle_reset_vote($request) {
        $reset_manager = new MT_Vote_Reset_Manager();
        
        $result = $reset_manager->reset_individual_vote(
            $request['candidate_id'],
            get_current_user_id(),
            $request['reason'] ?? ''
        );
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return new WP_REST_Response($result, 200);
    }

    /**
     * Handle bulk reset operations
     */
    public function handle_bulk_reset($request) {
        $reset_manager = new MT_Vote_Reset_Manager();
        
        $result = $reset_manager->bulk_reset_votes(
            $request['reset_scope'],
            $request['options'] ?? array()
        );
        
        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => $result->get_error_message()
            ), 400);
        }
        
        return new WP_REST_Response($result, 200);
    }

    /**
     * Get reset history
     */
    public function get_reset_history($request) {
        $audit_logger = new MT_Vote_Audit_Logger();
        
        $history = $audit_logger->get_reset_history(
            $request['page'],
            $request['per_page']
        );
        
        return new WP_REST_Response(array(
            'success' => true,
            'data' => $history['data'],
            'pagination' => array(
                'total' => $history['total'],
                'pages' => $history['pages'],
                'current_page' => $history['current_page'],
                'per_page' => $history['per_page']
            )
        ), 200);
    }

    /**
     * Enhanced jury member creation with additional fields
     */
    public function create_enhanced_jury_member($data) {
        // Create the jury post
        $jury_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($data['name']),
            'post_type' => 'mt_jury',
            'post_status' => 'publish',
            'post_content' => isset($data['bio']) ? wp_kses_post($data['bio']) : ''
        ));
        
        if (!is_wp_error($jury_id)) {
            // Store additional meta fields
            update_post_meta($jury_id, '_mt_jury_email', sanitize_email($data['email']));
            update_post_meta($jury_id, '_mt_jury_organization', sanitize_text_field($data['organization']));
            update_post_meta($jury_id, '_mt_jury_position', sanitize_text_field($data['position']));
            update_post_meta($jury_id, '_mt_jury_category', sanitize_text_field($data['category']));
            update_post_meta($jury_id, '_mt_jury_linkedin', esc_url_raw($data['linkedin']));
            update_post_meta($jury_id, '_mt_jury_status', 'active');
            update_post_meta($jury_id, '_mt_jury_created_date', current_time('mysql'));
            
            // Create WordPress user if requested
            if (!empty($data['create_user'])) {
                $user_id = $this->create_jury_wordpress_user($data['email'], $data['name']);
                if ($user_id) {
                    update_post_meta($jury_id, '_mt_jury_user_id', $user_id);
                }
            }
            
            return $jury_id;
        }
        
        return false;
    }

    /**
     * Create WordPress user for jury member
     */
    private function create_jury_wordpress_user($email, $name) {
        $username = sanitize_user(strtolower(str_replace(' ', '', $name)));
        
        // Ensure unique username
        $original_username = $username;
        $i = 1;
        while (username_exists($username)) {
            $username = $original_username . $i;
            $i++;
        }
        
        $password = wp_generate_password(12, true, false);
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (!is_wp_error($user_id)) {
            $user = new WP_User($user_id);
            $user->set_role('mt_jury_member');
            
            // Update user details
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => explode(' ', $name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $name), 1))
            ));
            
            // Send welcome email
            $this->send_jury_welcome_email($user_id, $password);
            
            return $user_id;
        }
        
        return false;
    }

    /**
     * Send welcome email to new jury member
     */
    private function send_jury_welcome_email($user_id, $password) {
        $user = get_user_by('id', $user_id);
        $login_url = wp_login_url();
        $dashboard_url = admin_url('admin.php?page=mt-jury-dashboard');
        
        $subject = sprintf(__('Welcome to %s Jury Panel', 'mobility-trailblazers'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Dear %s,\n\nWelcome to the Mobility Trailblazers jury panel!\n\nYour account has been created with the following credentials:\n\nUsername: %s\nPassword: %s\n\nPlease log in here: %s\n\nOnce logged in, you can access your jury dashboard here: %s\n\nWe recommend changing your password after your first login.\n\nThank you for joining our distinguished panel.\n\nBest regards,\nThe Mobility Trailblazers Team", 'mobility-trailblazers'),
            $user->display_name,
            $user->user_login,
            $password,
            $login_url,
            $dashboard_url
        );
        
        wp_mail($user->user_email, $subject, $message);
    }

    /**
     * Enhanced jury statistics for dashboard
     */
    public function get_jury_statistics() {
        global $wpdb;
        
        $stats = array(
            'total_jury' => wp_count_posts('mt_jury')->publish,
            'active_jury' => 0,
            'total_candidates' => wp_count_posts('mt_candidate')->publish,
            'total_evaluations' => 0,
            'completion_by_category' => array(),
            'top_performers' => array()
        );
        
        // Active jury count
        $active_jury = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_key' => '_mt_jury_status',
            'meta_value' => 'active',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $stats['active_jury'] = count($active_jury);
        
        // Total evaluations
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        $stats['total_evaluations'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Completion by category
        $categories = array('infrastructure', 'startups', 'established');
        foreach ($categories as $category) {
            $jury_in_category = get_posts(array(
                'post_type' => 'mt_jury',
                'meta_key' => '_mt_jury_category',
                'meta_value' => $category,
                'posts_per_page' => -1
            ));
            
            $total_assigned = 0;
            $total_completed = 0;
            
            foreach ($jury_in_category as $jury) {
                $assigned = $this->get_assigned_candidates_count($jury->ID);
                $user_id = get_post_meta($jury->ID, '_mt_jury_user_id', true);
                $completed = $this->get_completed_evaluations_count($user_id);
                
                $total_assigned += $assigned;
                $total_completed += $completed;
            }
            
            $stats['completion_by_category'][$category] = array(
                'assigned' => $total_assigned,
                'completed' => $total_completed,
                'rate' => $total_assigned > 0 ? round(($total_completed / $total_assigned) * 100) : 0
            );
        }
        
        // Top performers
        $top_performers = $wpdb->get_results("
            SELECT 
                u.ID as user_id,
                u.display_name,
                COUNT(DISTINCT cs.candidate_id) as evaluations_count
            FROM {$wpdb->users} u
            INNER JOIN {$table_name} cs ON u.ID = cs.jury_member_id
            GROUP BY u.ID
            ORDER BY evaluations_count DESC
            LIMIT 5
        ");
        
        $stats['top_performers'] = $top_performers;
        
        return $stats;
    }

    /**
     * Get assigned candidates count for a jury member
     */
    private function get_assigned_candidates_count($jury_id) {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'meta_key' => '_mt_assigned_jury_member',
            'meta_value' => $jury_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        return count($candidates);
    }

    /**
     * Get completed evaluations count for a user
     */
    private function get_completed_evaluations_count($user_id) {
        if (!$user_id) return 0;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_name WHERE jury_member_id = %d AND is_active = 1",
            $user_id
        ));
    }

    /**
     * Jury assignment optimizer
     */
    public function optimize_jury_assignments() {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_key' => '_mt_jury_status',
            'meta_value' => 'active',
            'posts_per_page' => -1
        ));
        
        if (empty($jury_members)) {
            return array('error' => __('No active jury members found', 'mobility-trailblazers'));
        }
        
        // Group candidates by category
        $candidates_by_category = array();
        foreach ($candidates as $candidate) {
            $category = get_post_meta($candidate->ID, '_mt_candidate_category', true) ?: 'general';
            if (!isset($candidates_by_category[$category])) {
                $candidates_by_category[$category] = array();
            }
            $candidates_by_category[$category][] = $candidate;
        }
        
        // Group jury by expertise
        $jury_by_category = array();
        foreach ($jury_members as $jury) {
            $category = get_post_meta($jury->ID, '_mt_jury_category', true) ?: 'general';
            if (!isset($jury_by_category[$category])) {
                $jury_by_category[$category] = array();
            }
            $jury_by_category[$category][] = $jury;
        }
        
        // Clear existing assignments
        foreach ($candidates as $candidate) {
            delete_post_meta($candidate->ID, '_mt_assigned_jury_member');
        }
        
        // Assign candidates to jury members based on expertise
        $assignments = array();
        foreach ($candidates_by_category as $category => $category_candidates) {
            $relevant_jury = isset($jury_by_category[$category]) ? $jury_by_category[$category] : $jury_members;
            
            if (empty($relevant_jury)) {
                continue;
            }
            
            $jury_index = 0;
            foreach ($category_candidates as $candidate) {
                $assigned_jury = $relevant_jury[$jury_index % count($relevant_jury)];
                update_post_meta($candidate->ID, '_mt_assigned_jury_member', $assigned_jury->ID);
                
                $assignments[] = array(
                    'candidate' => $candidate->post_title,
                    'jury' => $assigned_jury->post_title,
                    'category' => $category
                );
                
                $jury_index++;
            }
        }
        
        return array(
            'success' => true,
            'assignments' => count($assignments),
            'details' => $assignments
        );
    }

    /**
     * Bulk email sender for jury communications
     */
    public function send_bulk_jury_email($subject, $message, $jury_ids = array()) {
        $sent_count = 0;
        
        if (empty($jury_ids)) {
            // Send to all active jury members
            $jury_members = get_posts(array(
                'post_type' => 'mt_jury',
                'meta_key' => '_mt_jury_status',
                'meta_value' => 'active',
                'posts_per_page' => -1
            ));
        } else {
            $jury_members = get_posts(array(
                'post_type' => 'mt_jury',
                'post__in' => $jury_ids,
                'posts_per_page' => -1
            ));
        }
        
        foreach ($jury_members as $jury) {
            $email = get_post_meta($jury->ID, '_mt_jury_email', true);
            $name = $jury->post_title;
            
            if (!empty($email)) {
                // Personalize message
                $personalized_message = str_replace(
                    array('[name]', '[first_name]'),
                    array($name, explode(' ', $name)[0]),
                    $message
                );
                
                if (wp_mail($email, $subject, $personalized_message)) {
                    $sent_count++;
                }
            }
        }
        
        return $sent_count;
    }

    /**
     * Export jury evaluation report
     */
    public function export_jury_evaluation_report() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        // Get all evaluations with jury and candidate details
        $evaluations = $wpdb->get_results("
            SELECT 
                cs.*,
                u.display_name as jury_name,
                u.user_email as jury_email,
                p.post_title as candidate_name
            FROM {$table_name} cs
            LEFT JOIN {$wpdb->users} u ON cs.jury_member_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON cs.candidate_id = p.ID
            WHERE cs.is_active = 1
            ORDER BY cs.evaluation_date DESC
        ");
        
        $csv_data = array();
        $csv_data[] = array(
            'Evaluation ID',
            'Jury Member',
            'Jury Email',
            'Candidate',
            'Innovation Score',
            'Impact Score',
            'Implementation Score',
            'Team Score',
            'Market Score',
            'Total Score',
            'Comments',
            'Evaluation Date'
        );
        
        foreach ($evaluations as $eval) {
            $csv_data[] = array(
                $eval->id,
                $eval->jury_name,
                $eval->jury_email,
                $eval->candidate_name,
                $eval->score_innovation,
                $eval->score_impact,
                $eval->score_implementation,
                $eval->score_team,
                $eval->score_market,
                $eval->total_score,
                $eval->comments,
                $eval->evaluation_date
            );
        }
        
        return $csv_data;
    }

}

// Instantiate the plugin
new MobilityTrailblazersPlugin();

/**
 * Get evaluation count for a user (unified function)
 * This ensures consistency across all dashboards
 */
function mt_get_user_evaluation_count($user_id) {
    global $wpdb;
    $table_scores = $wpdb->prefix . 'mt_candidate_scores';
    
    // Get jury post ID for this user
    $jury_post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_mt_jury_user_id' AND meta_value = %s",
        $user_id
    ));
    
    if ($jury_post_id) {
        // Count evaluations by BOTH user ID and jury post ID
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores 
            WHERE jury_member_id IN (%d, %d) AND is_active = 1",
            $user_id,
            $jury_post_id
        ));
    } else {
        // Just count by user ID
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores 
            WHERE jury_member_id = %d AND is_active = 1",
            $user_id
        ));
    }
}

/**
 * Check if jury member has evaluated a candidate (unified function)
 */
function mt_has_jury_evaluated($user_id, $candidate_id) {
    global $wpdb;
    $table_scores = $wpdb->prefix . 'mt_candidate_scores';
    
    // Get jury post ID for this user
    $jury_post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_mt_jury_user_id' AND meta_value = %s",
        $user_id
    ));
    
    if ($jury_post_id) {
        // Check by BOTH user ID and jury post ID
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_scores 
            WHERE candidate_id = %d AND jury_member_id IN (%d, %d) AND is_active = 1",
            $candidate_id,
            $user_id,
            $jury_post_id
        )) > 0;
    } else {
        // Just check by user ID
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_scores 
            WHERE candidate_id = %d AND jury_member_id = %d AND is_active = 1",
            $candidate_id,
            $user_id
        )) > 0;
    }
}

/**
 * Get jury scores for a candidate
 * 
 * @param int $user_id WordPress user ID
 * @param int $candidate_id Candidate post ID
 * @return object|null Score object or null if no scores exist
 */
function mt_get_jury_scores($user_id, $candidate_id) {
    global $wpdb;
    $table_scores = $wpdb->prefix . 'mt_candidate_scores';
    
    // Get jury post ID for this user
    $jury_post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_mt_jury_user_id' AND meta_value = %s",
        $user_id
    ));
    
    if ($jury_post_id) {
        // Try to get scores by BOTH user ID and jury post ID
        $scores = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_scores 
            WHERE candidate_id = %d 
            AND jury_member_id IN (%d, %d)
            AND is_active = 1
            ORDER BY evaluated_at DESC
            LIMIT 1",
            $candidate_id,
            $user_id,
            $jury_post_id
        ));
    } else {
        // Just get by user ID
        $scores = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_scores 
            WHERE candidate_id = %d 
            AND jury_member_id = %d
            AND is_active = 1
            ORDER BY evaluated_at DESC
            LIMIT 1",
            $candidate_id,
            $user_id
        ));
    }
    
    return $scores;
}

/**
 * Null-safe wrapper functions
 */
if (!function_exists('mt_safe_strpos')) {
    function mt_safe_strpos($haystack, $needle, $offset = 0) {
        if (null === $haystack || null === $needle) {
            return false;
        }
        return strpos((string)$haystack, (string)$needle, $offset);
    }
}

if (!function_exists('mt_safe_str_replace')) {
    function mt_safe_str_replace($search, $replace, $subject) {
        if (null === $subject) {
            return '';
        }
        if (null === $search) {
            return $subject;
        }
        if (null === $replace) {
            $replace = '';
        }
        return str_replace($search, $replace, $subject);
    }
}

if (!function_exists('mt_safe_plugin_basename')) {
    function mt_safe_plugin_basename($file = null) {
        if (null === $file) {
            $file = __FILE__;
        }
        
        // Ensure we have a valid file path
        if (empty($file) || !is_string($file)) {
            return '';
        }
        
        return plugin_basename($file);
    }
}

/**
 * Handle sync system AJAX request
 */
add_action('wp_ajax_mt_sync_system', 'mt_handle_sync_system');
function mt_handle_sync_system() {
    // Check nonce
    if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        wp_die();
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        wp_die();
    }
    
    try {
        // Perform synchronization tasks
        // This is where you'd add your actual sync logic
        
        // Example: Update assignment counts
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $jury_members = get_users(array(
            'role' => 'mt_jury_member'
        ));
        
        // Update meta data, clear cache, etc.
        wp_cache_flush();
        
        wp_send_json_success(array(
            'message' => 'System synchronized successfully',
            'candidates_count' => count($candidates),
            'jury_count' => count($jury_members)
        ));
        
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
    
    wp_die();
}

/**
 * Handle get progress data AJAX request
 */
add_action('wp_ajax_mt_get_progress_data', 'mt_handle_get_progress_data');
function mt_handle_get_progress_data() {
    // Check nonce
    if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        wp_die();
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        wp_die();
    }
    
    // Get progress data
    global $wpdb;
    
    // Get all candidates with assignments
    $candidates = get_posts(array(
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            array(
                'key' => '_mt_assigned_jury_member',
                'compare' => 'EXISTS'
            )
        )
    ));
    
    // Get evaluation progress
    $evaluations = $wpdb->get_results("
        SELECT 
            candidate_id,
            jury_member_id,
            COUNT(*) as evaluation_count,
            MAX(evaluation_date) as last_evaluation
        FROM {$wpdb->prefix}mt_candidate_scores
        GROUP BY candidate_id, jury_member_id
    ");
    
    // Build progress statistics
    $total_assignments = count($candidates);
    $total_evaluations = count($evaluations);
    $completion_rate = $total_assignments > 0 ? round(($total_evaluations / $total_assignments) * 100, 1) : 0;
    
    // Get jury member progress
    $jury_progress = $wpdb->get_results("
        SELECT 
            u.ID,
            u.display_name,
            COUNT(DISTINCT pm.post_id) as assigned_count,
            COUNT(DISTINCT cs.candidate_id) as evaluated_count
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->postmeta} pm ON pm.meta_value = u.ID AND pm.meta_key = '_mt_assigned_jury_member'
        LEFT JOIN {$wpdb->prefix}mt_candidate_scores cs ON cs.jury_member_id = u.ID
        WHERE u.ID IN (SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = '{$wpdb->prefix}capabilities' AND meta_value LIKE '%mt_jury_member%')
        GROUP BY u.ID
        ORDER BY u.display_name
    ");
    
    // Build HTML output
    $html = '<div class="mt-progress-report">';
    
    // Overall statistics
    $html .= '<div class="mt-progress-stats" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">';
    $html .= '<div class="mt-stat-box" style="background: #f7fafc; padding: 20px; border-radius: 8px; text-align: center;">';
    $html .= '<div style="font-size: 2em; font-weight: bold; color: #2d3748;">' . $total_assignments . '</div>';
    $html .= '<div style="color: #718096;">Total Assignments</div>';
    $html .= '</div>';
    
    $html .= '<div class="mt-stat-box" style="background: #f7fafc; padding: 20px; border-radius: 8px; text-align: center;">';
    $html .= '<div style="font-size: 2em; font-weight: bold; color: #38a169;">' . $total_evaluations . '</div>';
    $html .= '<div style="color: #718096;">Completed Evaluations</div>';
    $html .= '</div>';
    
    $html .= '<div class="mt-stat-box" style="background: #f7fafc; padding: 20px; border-radius: 8px; text-align: center;">';
    $html .= '<div style="font-size: 2em; font-weight: bold; color: #3182ce;">' . $completion_rate . '%</div>';
    $html .= '<div style="color: #718096;">Completion Rate</div>';
    $html .= '</div>';
    $html .= '</div>';
    
    // Jury member progress table
    $html .= '<h4 style="margin-bottom: 15px;">Jury Member Progress</h4>';
    $html .= '<table style="width: 100%; border-collapse: collapse;">';
    $html .= '<thead>';
    $html .= '<tr style="background: #f7fafc;">';
    $html .= '<th style="padding: 10px; text-align: left; border-bottom: 2px solid #e2e8f0;">Jury Member</th>';
    $html .= '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #e2e8f0;">Assigned</th>';
    $html .= '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #e2e8f0;">Evaluated</th>';
    $html .= '<th style="padding: 10px; text-align: center; border-bottom: 2px solid #e2e8f0;">Progress</th>';
    $html .= '</tr>';
    $html .= '</thead>';
    $html .= '<tbody>';
    
    foreach ($jury_progress as $member) {
        $progress = $member->assigned_count > 0 ? round(($member->evaluated_count / $member->assigned_count) * 100, 1) : 0;
        $progress_color = $progress >= 80 ? '#38a169' : ($progress >= 50 ? '#d69e2e' : '#e53e3e');
        
        $html .= '<tr style="border-bottom: 1px solid #e2e8f0;">';
        $html .= '<td style="padding: 10px;">' . esc_html($member->display_name) . '</td>';
        $html .= '<td style="padding: 10px; text-align: center;">' . $member->assigned_count . '</td>';
        $html .= '<td style="padding: 10px; text-align: center;">' . $member->evaluated_count . '</td>';
        $html .= '<td style="padding: 10px; text-align: center;">';
        $html .= '<div style="background: #e2e8f0; border-radius: 4px; overflow: hidden; height: 20px; position: relative;">';
        $html .= '<div style="background: ' . $progress_color . '; height: 100%; width: ' . $progress . '%; transition: width 0.3s;"></div>';
        $html .= '<span style="position: absolute; top: 0; left: 50%; transform: translateX(-50%); font-size: 12px; line-height: 20px;">' . $progress . '%</span>';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>';
    $html .= '</table>';
    
    // Category breakdown
    $category_stats = $wpdb->get_results("
        SELECT 
            t.name as category,
            COUNT(DISTINCT p.ID) as total_count,
            COUNT(DISTINCT CASE WHEN pm.meta_key = '_mt_assigned_jury_member' THEN p.ID END) as assigned_count,
            COUNT(DISTINCT cs.candidate_id) as evaluated_count
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_assigned_jury_member'
        LEFT JOIN {$wpdb->prefix}mt_candidate_scores cs ON p.ID = cs.candidate_id
        WHERE p.post_type = 'mt_candidate' 
        AND p.post_status = 'publish'
        AND tt.taxonomy = 'mt_category'
        GROUP BY t.term_id
    ");
    
    if (!empty($category_stats)) {
        $html .= '<h4 style="margin-top: 30px; margin-bottom: 15px;">Category Progress</h4>';
        $html .= '<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">';
        
        foreach ($category_stats as $cat) {
            $assigned_percent = $cat->total_count > 0 ? round(($cat->assigned_count / $cat->total_count) * 100, 1) : 0;
            $evaluated_percent = $cat->assigned_count > 0 ? round(($cat->evaluated_count / $cat->assigned_count) * 100, 1) : 0;
            
            $html .= '<div style="background: #f7fafc; padding: 15px; border-radius: 8px;">';
            $html .= '<h5 style="margin: 0 0 10px 0; color: #2d3748;">' . esc_html($cat->category) . '</h5>';
            $html .= '<div style="font-size: 0.9em; color: #718096;">Total: ' . $cat->total_count . ' | Assigned: ' . $cat->assigned_count . ' | Evaluated: ' . $cat->evaluated_count . '</div>';
            $html .= '<div style="margin-top: 10px;">';
            $html .= '<div style="display: flex; justify-content: space-between; font-size: 0.8em; margin-bottom: 3px;">';
            $html .= '<span>Assigned</span><span>' . $assigned_percent . '%</span>';
            $html .= '</div>';
            $html .= '<div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">';
            $html .= '<div style="background: #3182ce; height: 100%; width: ' . $assigned_percent . '%;"></div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div style="margin-top: 8px;">';
            $html .= '<div style="display: flex; justify-content: space-between; font-size: 0.8em; margin-bottom: 3px;">';
            $html .= '<span>Evaluated</span><span>' . $evaluated_percent . '%</span>';
            $html .= '</div>';
            $html .= '<div style="background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">';
            $html .= '<div style="background: #38a169; height: 100%; width: ' . $evaluated_percent . '%;"></div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
    }
    
    $html .= '</div>';
    
    wp_send_json_success(array('html' => $html));
    wp_die();
}

/**
 * Ensure the mt_export_assignments handler exists
 * This might already exist in your plugin, but adding it here for completeness
 */
if (!has_action('wp_ajax_mt_export_assignments', 'mt_handle_export_assignments')) {
    add_action('wp_ajax_mt_export_assignments', 'mt_handle_export_assignments');
}

function mt_handle_export_assignments() {
    if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="mobility-trailblazers-assignments-' . date('Y-m-d-H-i-s') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 recognition
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    fputcsv($output, array(
        'Candidate ID',
        'Candidate Name',
        'Company',
        'Category',
        'Jury Member ID',
        'Jury Member Name',
        'Jury Member Email',
        'Assignment Date',
        'Evaluation Status',
        'Evaluation Date',
        'Total Score'
    ));
    
    // Get assignments data
    global $wpdb;
    
    $assignments = $wpdb->get_results("
        SELECT 
            p.ID as candidate_id,
            p.post_title as candidate_name,
            pm_company.meta_value as company,
            t.name as category,
            u.ID as jury_id,
            u.display_name as jury_name,
            u.user_email as jury_email,
            pm_assign.meta_value as jury_member_id,
            cs.evaluation_date,
            cs.total_score
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_assign ON p.ID = pm_assign.post_id AND pm_assign.meta_key = '_mt_assigned_jury_member'
        LEFT JOIN {$wpdb->postmeta} pm_company ON p.ID = pm_company.post_id AND pm_company.meta_key = '_mt_company'
        LEFT JOIN {$wpdb->users} u ON u.ID = pm_assign.meta_value
        LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id AND tt.taxonomy = 'mt_category'
        LEFT JOIN {$wpdb->prefix}mt_candidate_scores cs ON p.ID = cs.candidate_id AND u.ID = cs.jury_member_id
        WHERE p.post_type = 'mt_candidate' 
        AND p.post_status = 'publish'
        AND pm_assign.meta_value IS NOT NULL
        ORDER BY u.display_name, p.post_title
    ");
    
    // Write data rows
    foreach ($assignments as $assignment) {
        fputcsv($output, array(
            $assignment->candidate_id,
            $assignment->candidate_name,
            $assignment->company ?: '',
            $assignment->category ?: '',
            $assignment->jury_id,
            $assignment->jury_name,
            $assignment->jury_email,
            date('Y-m-d', strtotime(get_post_meta($assignment->candidate_id, '_mt_assignment_date', true) ?: 'now')),
            $assignment->evaluation_date ? 'Evaluated' : 'Pending',
            $assignment->evaluation_date ?: '',
            $assignment->total_score ?: ''
        ));
    }
    
    fclose($output);
    wp_die();
}

/**
 * Add AJAX handler for export (for non-REST download)
 */
add_action('wp_ajax_mt_export_backup_history', 'mt_handle_export_backup_history');

function mt_handle_export_backup_history() {
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'mobility-trailblazers'));
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'mt_nonce')) {
        wp_die(__('Security check failed', 'mobility-trailblazers'));
    }
    
    $format = sanitize_text_field($_POST['format']);
    $backup_manager = new MT_Vote_Backup_Manager();
    
    // Export backups
    $result = $backup_manager->export_backups($format);
    
    if (is_wp_error($result)) {
        wp_die($result->get_error_message());
    }
    
    // Set headers for download
    $filename = basename($result);
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
    } else {
        header('Content-Type: application/json');
    }
    
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($result));
    
    // Output file
    readfile($result);
    
    // Clean up
    @unlink($result);
    
    exit;
}

?>