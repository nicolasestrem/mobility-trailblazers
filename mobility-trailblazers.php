<?php
/**
 * Plugin Name: Mobility Trailblazers Award System
 * Plugin URI: https://mobilitytrailblazers.de
 * Description: Complete award management system for 25 Mobility Trailblazers in 25 - managing candidates, jury members, voting process, and public engagement.
 * Version: 0.0.1
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
        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Initialize plugin
        add_action('init', array($this, 'init'));
        
        // Register AJAX handlers EARLY
        add_action('wp_loaded', array($this, 'register_ajax_handlers'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // Other hooks...
        add_action('wp_ajax_nopriv_mt_public_vote', array($this, 'handle_public_vote'));
        
        // Shortcodes
        $this->add_shortcodes();
    }

    /**
     * Register all AJAX handlers
     */
    public function register_ajax_handlers() {
        // Assignment AJAX handlers
        add_action('wp_ajax_mt_assign_candidates', array($this, 'ajax_assign_candidates'));
        add_action('wp_ajax_mt_clear_all_assignments', array($this, 'ajax_clear_all_assignments'));
        add_action('wp_ajax_mt_auto_assign_candidates', array($this, 'ajax_auto_assign_candidates'));
        add_action('wp_ajax_mt_get_assignment_stats', array($this, 'ajax_get_assignment_stats'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'ajax_export_assignments'));
        
        // Evaluation AJAX handlers
        add_action('wp_ajax_mt_submit_vote', array($this, 'ajax_submit_evaluation'));
        add_action('wp_ajax_mt_get_candidate_details', array($this, 'ajax_get_candidate_details'));
        
        // Public AJAX handlers (if needed)
        add_action('wp_ajax_nopriv_mt_public_vote', array($this, 'ajax_public_vote'));
    }

    public function init() {
        $this->load_textdomain();
        $this->create_custom_post_types();
        $this->create_custom_taxonomies();
        $this->add_hooks();
        $this->load_admin();
        $this->load_frontend();
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

        // Public voting table - commented out as public voting is disabled
        /*
        $table_public_votes = $wpdb->prefix . 'mt_public_votes';
        $sql_public_votes = "CREATE TABLE $table_public_votes (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidate_id bigint(20) NOT NULL,
            voter_email varchar(255) NOT NULL,
            voter_ip varchar(45) NOT NULL,
            vote_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_public_vote (candidate_id, voter_email),
            KEY candidate_idx (candidate_id)
        ) $charset_collate;";
        */

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
        // dbDelta($sql_public_votes); // Commented out as public voting is disabled
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_candidate_meta'));
        add_action('wp_ajax_mt_submit_vote', array($this, 'handle_jury_vote'));
        add_shortcode('mt_voting_form', array($this, 'voting_form_shortcode'));
        add_shortcode('mt_candidate_grid', array($this, 'candidate_grid_shortcode'));
        add_shortcode('mt_jury_members', array($this, 'jury_members_shortcode'));
        add_shortcode('mt_voting_results', array($this, 'voting_results_shortcode'));
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
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        // Check if we're on the assignment page
        $is_assignment_page = isset($_GET['page']) && $_GET['page'] === 'mt-assignments';
        
        if ($is_assignment_page) {
            // Don't enqueue admin.js on assignment page to avoid conflicts
            wp_enqueue_script('jquery');
            // Assignment.js will be enqueued in assignment_management_page()
        } else {
            // Regular admin pages
            if (!empty($hook) && (strpos($hook, 'mt-') !== false || in_array($hook, array('post.php', 'post-new.php')))) {
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
        }
    }

    /**
     * Properly enqueue assignment assets
     */
    public function enqueue_assignment_assets() {
        // Enqueue jQuery if not already loaded
        wp_enqueue_script('jquery');
        
        // Enqueue assignment JavaScript
        wp_enqueue_script(
            'mt-assignment-js',
            MT_PLUGIN_URL . 'assets/assignment.js',
            array('jquery'),
            '3.2.1', // Version number
            true // Load in footer
        );
        
        // Enqueue assignment CSS
        wp_enqueue_style(
            'mt-assignment-css',
            MT_PLUGIN_URL . 'assets/assignment.css',
            array(),
            '3.2.1'
        );
    }

    /**
     * Get candidates data formatted for JavaScript
     */
    public function get_candidates_for_assignment() {
        try {
            $candidates = get_posts(array(
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            $formatted_candidates = array();
            
            foreach ($candidates as $candidate) {
                $assigned_jury_id = get_post_meta($candidate->ID, '_mt_assigned_jury_member', true);
                $category = get_post_meta($candidate->ID, '_mt_category', true);
                $company = get_post_meta($candidate->ID, '_mt_company', true);
                $stage = get_post_meta($candidate->ID, '_mt_stage', true);
                
                $formatted_candidates[] = array(
                    'id' => $candidate->ID,
                    'name' => $candidate->post_title,
                    'company' => $company ?: '',
                    'category' => $category ?: 'general',
                    'stage' => $stage ?: 'round1',
                    'assigned' => !empty($assigned_jury_id),
                    'jury_member_id' => $assigned_jury_id ?: null,
                    'post_date' => $candidate->post_date
                );
            }
            
            return $formatted_candidates;
            
        } catch (Exception $e) {
            error_log('Error getting candidates for assignment: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Get jury members data formatted for JavaScript
     */
    public function get_jury_members_for_assignment() {
        try {
            $jury_members = get_posts(array(
                'post_type' => 'mt_jury',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'orderby' => 'title',
                'order' => 'ASC'
            ));
            
            $formatted_jury = array();
            
            foreach ($jury_members as $jury) {
                $position = get_post_meta($jury->ID, '_mt_position', true);
                $company = get_post_meta($jury->ID, '_mt_company', true);
                $max_assignments = get_post_meta($jury->ID, '_mt_max_assignments', true);
                
                // Count current assignments
                global $wpdb;
                $current_assignments = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(*) FROM {$wpdb->postmeta} 
                    WHERE meta_key = '_mt_assigned_jury_member' 
                    AND meta_value = %s
                ", $jury->ID));
                
                $max_assignments = intval($max_assignments) ?: 25;
                $current_assignments = intval($current_assignments) ?: 0;
                
                $formatted_jury[] = array(
                    'id' => $jury->ID,
                    'name' => $jury->post_title,
                    'position' => $position ?: '',
                    'company' => $company ?: '',
                    'max_assignments' => $max_assignments,
                    'assignments' => $current_assignments,
                    'available_slots' => max(0, $max_assignments - $current_assignments)
                );
            }
            
            return $formatted_jury;
            
        } catch (Exception $e) {
            error_log('Error getting jury members for assignment: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * AJAX handler for candidate assignment
     */
    public function ajax_assign_candidates() {
        // Log the request for debugging
        error_log('AJAX assign_candidates called');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_assignment_nonce')) {
            error_log('Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('Permission check failed');
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        // Get and validate data
        $candidate_ids = isset($_POST['candidate_ids']) ? array_map('intval', $_POST['candidate_ids']) : array();
        $jury_member_id = isset($_POST['jury_member_id']) ? intval($_POST['jury_member_id']) : 0;
        
        if (empty($candidate_ids) || !$jury_member_id) {
            error_log('Invalid data: candidates=' . count($candidate_ids) . ', jury_id=' . $jury_member_id);
            wp_send_json_error(array('message' => 'Invalid data provided'));
        }
        
        // Verify jury member exists
        if (!get_post($jury_member_id) || get_post_type($jury_member_id) !== 'mt_jury') {
            error_log('Invalid jury member: ' . $jury_member_id);
            wp_send_json_error(array('message' => 'Invalid jury member'));
        }
        
        $success_count = 0;
        $errors = array();
        
        foreach ($candidate_ids as $candidate_id) {
            // Verify candidate exists
            if (!get_post($candidate_id) || get_post_type($candidate_id) !== 'mt_candidate') {
                $errors[] = "Invalid candidate ID: $candidate_id";
                continue;
            }
            
            // Update assignment
            $result = update_post_meta($candidate_id, '_mt_assigned_jury_member', $jury_member_id);
            if ($result !== false) {
                $success_count++;
                error_log("Assigned candidate $candidate_id to jury member $jury_member_id");
            } else {
                $errors[] = "Failed to assign candidate ID: $candidate_id";
                error_log("Failed to assign candidate $candidate_id to jury member $jury_member_id");
            }
        }
        
        if ($success_count > 0) {
            wp_send_json_success(array(
                'message' => sprintf(
                    'Successfully assigned %d candidate(s). %s',
                    $success_count,
                    !empty($errors) ? 'Some assignments failed.' : ''
                ),
                'assigned_count' => $success_count,
                'errors' => $errors
            ));
        } else {
            wp_send_json_error(array(
                'message' => 'No assignments were completed',
                'errors' => $errors
            ));
        }
    }

    /**
     * AJAX handler for clearing all assignments
     */
    public function ajax_clear_all_assignments() {
        // Log the request
        error_log('AJAX clear_all_assignments called');
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_assignment_nonce')) {
            error_log('Clear assignments: Nonce verification failed');
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('Clear assignments: Permission check failed');
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        global $wpdb;
        
        // Clear all assignments
        $result = $wpdb->delete(
            $wpdb->postmeta,
            array('meta_key' => '_mt_assigned_jury_member'),
            array('%s')
        );
        
        error_log('Clear assignments result: ' . $result);
        
        if ($result !== false) {
            wp_send_json_success(array(
                'message' => "Cleared $result assignment(s)",
                'cleared_count' => $result
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to clear assignments'));
        }
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
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $candidate_ids = array_map('intval', $_POST['candidate_ids']);
        $jury_member_id = intval($_POST['jury_member_id']);
        
        $success_count = 0;
        
        foreach ($candidate_ids as $candidate_id) {
            $result = update_post_meta($candidate_id, '_mt_assigned_jury_member', $jury_member_id);
            if ($result !== false) {
                $success_count++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d candidates assigned successfully', 'mobility-trailblazers'), $success_count),
            'assigned_count' => $success_count
        ));

        if ($success_count > 0) {
            $this->notify_jury_member_assignment($jury_member_id, $candidate_ids);
        }
    }

    /**
     * Handle auto-assignment
     */
    public function handle_auto_assign() {
        if (!check_ajax_referer('mt_assignment_nonce', 'nonce', false)) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $candidates_per_jury = intval($_POST['candidates_per_jury']);
        $algorithm = sanitize_text_field($_POST['algorithm']);
        
        // Get unassigned candidates
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_member',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        // Get jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $assignments_made = 0;
        $jury_index = 0;
        
        // Simple balanced assignment
        foreach ($candidates as $candidate) {
            if ($jury_index >= count($jury_members)) {
                $jury_index = 0;
            }
            
            $current_jury = $jury_members[$jury_index];
            update_post_meta($candidate->ID, '_mt_assigned_jury_member', $current_jury->ID);
            $assignments_made++;
            $jury_index++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d assignments created successfully', 'mobility-trailblazers'), $assignments_made),
            'assignments_made' => $assignments_made
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
    private function get_candidates_for_interface() {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $formatted_candidates = array();
        
        foreach ($candidates as $candidate) {
            $company = get_post_meta($candidate->ID, '_mt_company', true);
            $position = get_post_meta($candidate->ID, '_mt_position', true);
            $categories = wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'slugs'));
            $assigned_jury = get_post_meta($candidate->ID, '_mt_assigned_jury_member', true);
            
            $formatted_candidates[] = array(
                'id' => $candidate->ID,
                'name' => $candidate->post_title,
                'company' => $company ?: 'No company',
                'position' => $position ?: 'No position',
                'category' => !empty($categories) ? $categories[0] : 'uncategorized',
                'assigned' => !empty($assigned_jury),
                'jury_member_id' => $assigned_jury ?: null
            );
        }
        
        return $formatted_candidates;
    }

    /**
     * Get jury members data formatted for JavaScript
     */
    

    /**
     * Check if jury member has evaluated a candidate
     */
    public function has_jury_member_evaluated($jury_member_id, $candidate_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $score_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE jury_member_id = %d AND candidate_id = %d",
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
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores WHERE jury_member_id = %d",
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
            <a href="<?php echo admin_url('admin.php?page=mt-jury-dashboard'); ?>" class="button button-primary">
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
        
        $message .= '<p><a href="' . admin_url('admin.php?page=mt-jury-dashboard') . '" style="background: #2c5282; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">' . __('Go to Dashboard', 'mobility-trailblazers') . '</a></p>';
        
        $message .= '<p>' . __('Thank you for your valuable contribution to recognizing mobility innovation in the DACH region.', 'mobility-trailblazers') . '</p>';
        
        $message .= '<p><em>' . __('The Mobility Trailblazers Team', 'mobility-trailblazers') . '</em></p>';
        
        // Send email
        mt_send_jury_notification($jury_email, $subject, $message);
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
     * Diagnostic page callback
     */
    public function diagnostic_page() {
        echo "<div class='wrap'>";
        echo "<h1>Mobility Trailblazers Diagnostic</h1>";

        global $wpdb;

        // 1. Check if custom post types exist
        echo "<h2>1. Post Types Status</h2>";
        $candidate_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_candidate' AND post_status = 'publish'");
        $jury_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_jury' AND post_status = 'publish'");

        echo "<p><strong>Candidates:</strong> {$candidate_count}</p>";
        echo "<p><strong>Jury Members:</strong> {$jury_count}</p>";

        // 2. Check assignments
        echo "<h2>2. Assignment Status</h2>";
        $assignment_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = '_mt_assigned_jury_member'
            AND p.post_type = 'mt_candidate'
        ");

        echo "<p><strong>Total Assignments:</strong> {$assignment_count}</p>";

        if ($assignment_count > 0) {
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

            echo "<h3>Sample Assignments:</h3>";
            echo "<table class='wp-list-table widefat fixed striped'>";
            echo "<thead><tr><th>Candidate</th><th>Assigned to Jury</th></tr></thead><tbody>";
            foreach ($assignments as $assignment) {
                echo "<tr><td>{$assignment->candidate_name}</td><td>{$assignment->jury_name}</td></tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<p style='color: red;'><strong>⚠️ No assignments found!</strong></p>";
            echo "<p>Jury members won't see any candidates in their dashboard.</p>";
        }

        // 3. Check jury-user linking
        echo "<h2>3. Jury-User Linking</h2>";
        $jury_links = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->posts} p
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'mt_jury'
            AND (pm.meta_key = '_mt_jury_user_id' OR pm.meta_key = '_mt_jury_email')
        ");

        echo "<p><strong>Jury-User Links:</strong> {$jury_links}</p>";

        if ($jury_links === '0') {
            echo "<p style='color: red;'><strong>⚠️ No jury-user links found!</strong></p>";
            echo "<p>Jury members won't be able to access their dashboard.</p>";
        }

        // 4. Check database tables
        echo "<h2>4. Database Tables</h2>";
        $required_tables = [
            'mt_candidate_scores' => 'Stores jury evaluations',
            'mt_votes' => 'Stores jury votes', 
            'mt_public_votes' => 'Stores public votes'
        ];

        echo "<table class='wp-list-table widefat fixed striped'>";
        echo "<thead><tr><th>Table</th><th>Status</th><th>Records</th><th>Purpose</th></tr></thead><tbody>";
        
        foreach ($required_tables as $table => $purpose) {
            $full_table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            
            if ($exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
                echo "<tr><td>{$table}</td><td style='color: green;'>✅ Exists</td><td>{$count}</td><td>{$purpose}</td></tr>";
            } else {
                echo "<tr><td>{$table}</td><td style='color: red;'>❌ Missing</td><td>-</td><td>{$purpose}</td></tr>";
            }
        }
        echo "</tbody></table>";

        // 5. Current user status
        echo "<h2>5. Current User Status</h2>";
        $current_user = wp_get_current_user();
        echo "<p><strong>User ID:</strong> {$current_user->ID}</p>";
        echo "<p><strong>Email:</strong> {$current_user->user_email}</p>";
        echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";

        // Check if current user is jury member
        $is_jury = $this->is_jury_member($current_user->ID);
        echo "<p><strong>Jury Status:</strong> " . ($is_jury ? '✅ Is jury member' : '❌ Not a jury member') . "</p>";

        // 6. Quick fixes
        echo "<h2>6. Quick Fixes</h2>";
        
        if (isset($_POST['create_test_assignment'])) {
            $this->create_test_assignment();
            echo "<div class='notice notice-success'><p>Test assignment created!</p></div>";
        }
        
        if (isset($_POST['link_current_user'])) {
            $this->link_current_user_to_jury();
            echo "<div class='notice notice-success'><p>Current user linked to jury!</p></div>";
        }

        echo "<form method='post' style='margin: 10px 0;'>";
        echo "<input type='submit' name='create_test_assignment' class='button button-secondary' value='Create Test Assignment'>";
        echo "</form>";

        echo "<form method='post' style='margin: 10px 0;'>";
        echo "<input type='submit' name='link_current_user' class='button button-secondary' value='Link Current User to First Jury Member'>";
        echo "</form>";

        echo "</div>";
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
     * Add evaluation page (hidden from menu)
     */
    public function add_evaluation_page() {
        // Don't check user permissions here - let WordPress handle it with capability
        add_submenu_page(
            null,  // Hidden from menu
            __('Evaluate Candidate', 'mobility-trailblazers'),
            __('Evaluate', 'mobility-trailblazers'),
            'read',  // Basic read capability that jury members have
            'mt-evaluate',
            array($this, 'evaluation_page')
        );
    }

    /**
     * Render the evaluation page
     */
    public function evaluation_page() {
        // Check permissions inside the page callback
        $current_user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Allow if user has jury role OR is admin OR is linked jury member
        $has_jury_role = in_array('mt_jury_member', (array) $user->roles);
        $is_jury_member = $this->is_jury_member($current_user_id);
        $is_admin = current_user_can('manage_options');
        
        if (!$has_jury_role && !$is_jury_member && !$is_admin) {
            wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
        }
        
        // Get candidate ID
        $candidate_id = isset($_GET['candidate']) ? intval($_GET['candidate']) : 0;
        
        if (!$candidate_id) {
            wp_redirect(admin_url('admin.php?page=mt-jury-dashboard'));
            exit;
        }
        
        $jury_member_id = $this->get_jury_member_for_user($current_user_id);
        $edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';
        
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            wp_die(__('Invalid candidate.', 'mobility-trailblazers'));
        }
        
        // Check if candidate is assigned to this jury member
        $assigned_jury = get_post_meta($candidate_id, '_mt_assigned_jury_member', true);
        if ($assigned_jury != $jury_member_id && !$is_admin) {
            wp_die(__('You are not assigned to evaluate this candidate.', 'mobility-trailblazers'));
        }
        
        // Get existing scores if in edit mode
        $existing_scores = array();
        if ($edit_mode) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'mt_candidate_scores';
            $existing_scores = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE jury_member_id = %d AND candidate_id = %d",
                $jury_member_id,
                $candidate_id
            ), ARRAY_A);
        }
        
        // Get candidate details
        $company = get_post_meta($candidate_id, '_mt_company', true);
        $position = get_post_meta($candidate_id, '_mt_position', true);
        $bio = get_post_meta($candidate_id, '_mt_bio', true);
        $achievements = get_post_meta($candidate_id, '_mt_achievements', true);
        
        ?>
        <div class="wrap">
            <h1><?php echo $edit_mode ? __('Edit Evaluation', 'mobility-trailblazers') : __('Evaluate Candidate', 'mobility-trailblazers'); ?></h1>
            
            <div class="mt-evaluation-container">
                <!-- Candidate Information -->
                <div class="mt-candidate-details">
                    <h2><?php echo esc_html($candidate->post_title); ?></h2>
                    <?php if ($position) : ?>
                        <p><strong><?php echo esc_html($position); ?></strong></p>
                    <?php endif; ?>
                    <?php if ($company) : ?>
                        <p><?php echo esc_html($company); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($bio) : ?>
                        <div class="mt-bio">
                            <h3><?php _e('Biography', 'mobility-trailblazers'); ?></h3>
                            <p><?php echo wp_kses_post($bio); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($achievements) : ?>
                        <div class="mt-achievements">
                            <h3><?php _e('Key Achievements', 'mobility-trailblazers'); ?></h3>
                            <div><?php echo wp_kses_post($achievements); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Evaluation Form -->
                <div class="mt-evaluation-form">
                    <h2><?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?></h2>
                    <p class="description"><?php _e('Please rate the candidate on each criterion from 1 (lowest) to 10 (highest).', 'mobility-trailblazers'); ?></p>
                    
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('mt_evaluation', 'mt_evaluation_nonce'); ?>
                        <input type="hidden" name="action" value="mt_submit_evaluation">
                        <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">
                        <input type="hidden" name="jury_member_id" value="<?php echo $jury_member_id; ?>">
                        <input type="hidden" name="edit_mode" value="<?php echo $edit_mode ? '1' : '0'; ?>">
                        
                        <!-- Mut & Pioniergeist -->
                        <div class="mt-criterion">
                            <h3><?php _e('Mut & Pioniergeist', 'mobility-trailblazers'); ?></h3>
                            <p class="description"><?php _e('Wurde gegen Widerstände gehandelt? Gab es neue Wege? Persönliches Risiko?', 'mobility-trailblazers'); ?></p>
                            <div class="mt-score-selector">
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <label class="mt-score-option">
                                        <input type="radio" name="courage_score" value="<?php echo $i; ?>" 
                                               <?php checked($existing_scores['courage_score'] ?? 0, $i); ?> required>
                                        <span><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Innovationsgrad -->
                        <div class="mt-criterion">
                            <h3><?php _e('Innovationsgrad', 'mobility-trailblazers'); ?></h3>
                            <p class="description"><?php _e('Inwiefern stellt der Beitrag eine echte Neuerung dar (Technologie, Business Modell)?', 'mobility-trailblazers'); ?></p>
                            <div class="mt-score-selector">
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <label class="mt-score-option">
                                        <input type="radio" name="innovation_score" value="<?php echo $i; ?>" 
                                               <?php checked($existing_scores['innovation_score'] ?? 0, $i); ?> required>
                                        <span><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Umsetzungskraft & Wirkung -->
                        <div class="mt-criterion">
                            <h3><?php _e('Umsetzungskraft & Wirkung', 'mobility-trailblazers'); ?></h3>
                            <p class="description"><?php _e('Welche Resultate wurden erzielt (z.B. Skalierung, Impact)?', 'mobility-trailblazers'); ?></p>
                            <div class="mt-score-selector">
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <label class="mt-score-option">
                                        <input type="radio" name="implementation_score" value="<?php echo $i; ?>" 
                                               <?php checked($existing_scores['implementation_score'] ?? 0, $i); ?> required>
                                        <span><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Relevanz für Mobilitätswende -->
                        <div class="mt-criterion">
                            <h3><?php _e('Relevanz für Mobilitätswende', 'mobility-trailblazers'); ?></h3>
                            <p class="description"><?php _e('Trägt die Initiative zur Transformation der Mobilität im DACH-Raum bei?', 'mobility-trailblazers'); ?></p>
                            <div class="mt-score-selector">
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <label class="mt-score-option">
                                        <input type="radio" name="relevance_score" value="<?php echo $i; ?>" 
                                               <?php checked($existing_scores['relevance_score'] ?? 0, $i); ?> required>
                                        <span><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Vorbildfunktion & Sichtbarkeit -->
                        <div class="mt-criterion">
                            <h3><?php _e('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'); ?></h3>
                            <p class="description"><?php _e('Ist die Person ein inspirierendes Role Model mit öffentlicher Wirkung?', 'mobility-trailblazers'); ?></p>
                            <div class="mt-score-selector">
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <label class="mt-score-option">
                                        <input type="radio" name="visibility_score" value="<?php echo $i; ?>" 
                                               <?php checked($existing_scores['visibility_score'] ?? 0, $i); ?> required>
                                        <span><?php echo $i; ?></span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Comments -->
                        <div class="mt-criterion">
                            <h3><?php _e('Additional Comments', 'mobility-trailblazers'); ?></h3>
                            <p class="description"><?php _e('Optional: Provide any additional feedback or context for your evaluation.', 'mobility-trailblazers'); ?></p>
                            <textarea name="comments" rows="5" class="large-text"><?php echo esc_textarea($existing_scores['comments'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Submit buttons -->
                        <div class="mt-form-actions">
                            <button type="submit" class="button button-primary button-large">
                                <?php echo $edit_mode ? __('Update Evaluation', 'mobility-trailblazers') : __('Submit Evaluation', 'mobility-trailblazers'); ?>
                            </button>
                            <a href="<?php echo admin_url('admin.php?page=mt-jury-dashboard'); ?>" class="button button-secondary button-large">
                                <?php _e('Cancel', 'mobility-trailblazers'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
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
    }

    /**
     * Debug function to check evaluation access
     */
    public function debug_evaluation_access() {
        if (isset($_GET['debug_evaluation'])) {
            $current_user = wp_get_current_user();
            
            echo '<div style="background: #f5f5f5; padding: 20px; margin: 20px;">';
            echo '<h2>Debug Evaluation Access</h2>';
            echo '<p><strong>User:</strong> ' . $current_user->user_login . ' (ID: ' . $current_user->ID . ')</p>';
            echo '<p><strong>Roles:</strong> ' . implode(', ', $current_user->roles) . '</p>';
            echo '<p><strong>Has mt_jury_member role:</strong> ' . (in_array('mt_jury_member', $current_user->roles) ? 'YES' : 'NO') . '</p>';
            
            // Check registered pages
            global $_registered_pages;
            $evaluation_page_registered = isset($_registered_pages['mt-award-system_page_mt-evaluate']) || 
                                        isset($_registered_pages['admin_page_mt-evaluate']) ||
                                        isset($_registered_pages['toplevel_page_mt-evaluate']);
            
            echo '<p><strong>Evaluation page registered:</strong> ' . ($evaluation_page_registered ? 'YES' : 'NO') . '</p>';
            
            // Check capabilities
            echo '<h3>Capabilities Check:</h3>';
            echo '<ul>';
            echo '<li>read: ' . (current_user_can('read') ? 'YES' : 'NO') . '</li>';
            echo '<li>mt_access_jury_dashboard: ' . (current_user_can('mt_access_jury_dashboard') ? 'YES' : 'NO') . '</li>';
            echo '<li>mt_submit_evaluations: ' . (current_user_can('mt_submit_evaluations') ? 'YES' : 'NO') . '</li>';
            echo '</ul>';
            
            // Show all registered admin pages
            echo '<h3>All Registered Admin Pages:</h3>';
            echo '<pre>' . print_r(array_keys($_registered_pages), true) . '</pre>';
            
            echo '</div>';
            exit;
        }
    }

    /**
     * Add jury dashboard menu
     */
    public function add_jury_dashboard_menu() {
        // Check if user should see the jury dashboard
        $current_user_id = get_current_user_id();
        $is_jury = false;
        $is_admin = current_user_can('manage_options');
        
        // Check if user is a jury member
        $jury_post = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_mt_jury_email',
                    'value' => wp_get_current_user()->user_email,
                    'compare' => '='
                ),
                array(
                    'key' => '_mt_jury_user_id', 
                    'value' => $current_user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        $is_jury = !empty($jury_post);
        
        // Only add menu if user is jury member or admin
        if ($is_jury || $is_admin) {
            add_submenu_page(
                'mt-award-system',
                __('My Dashboard', 'mobility-trailblazers'),
                __('My Dashboard', 'mobility-trailblazers'),
                'read', // Basic capability that all logged-in users have
                'mt-jury-dashboard',
                array($this, 'jury_dashboard_page')
            );
        }
    }

    /**
     * Jury dashboard page
     */
    public function jury_dashboard_page() {
        // Include the jury dashboard template
        include MT_PLUGIN_PATH . 'templates/jury-dashboard.php';
    }

    /**
     * Get jury member ID for a user
     */
    public function get_jury_member_for_user($user_id) {
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        return !empty($jury_posts) ? $jury_posts[0]->ID : false;
    }

    /**
     * Ensure jury menu exists (fallback)
     */
    public function ensure_jury_menu_exists() {
        // This is a fallback method - implementation can be left empty
        // or you can add additional menu registration logic here if needed
    }

    /**
     * Handle jury dashboard direct access
     */
    public function handle_jury_dashboard_direct() {
        // This is a fallback method - implementation can be left empty
        // or you can add direct access handling logic here if needed
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
            WHERE jury_member_id IN (%d, %d)",
            $user_id,
            $jury_post_id
        ));
    } else {
        // Just count by user ID
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_scores 
            WHERE jury_member_id = %d",
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
            WHERE candidate_id = %d AND jury_member_id IN (%d, %d)",
            $candidate_id,
            $user_id,
            $jury_post_id
        )) > 0;
    } else {
        // Just check by user ID
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_scores 
            WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $user_id
        )) > 0;
    }
}

?>