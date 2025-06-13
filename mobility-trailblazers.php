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

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_PLUGIN_VERSION', '1.0.0');
define('MT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class MobilityTrailblazersPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('wp_ajax_mt_assign_candidates', array($this, 'handle_assign_candidates'));
        add_action('wp_ajax_mt_auto_assign', array($this, 'handle_auto_assign'));
        add_action('wp_ajax_mt_get_assignment_stats', array($this, 'handle_get_assignment_stats'));
        add_action('wp_ajax_mt_clear_assignments', array($this, 'handle_clear_assignments'));
        add_action('wp_ajax_mt_export_assignments', array($this, 'handle_export_assignments'));
        
        // Add jury dashboard hooks
        add_action('admin_menu', array($this, 'add_jury_dashboard_menu'), 99);
        add_action('admin_init', array($this, 'handle_jury_dashboard_direct'));
        add_action('init', array($this, 'add_jury_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_jury_query_vars'));
        add_action('template_redirect', array($this, 'jury_template_redirect'));
        add_filter('login_redirect', array($this, 'jury_login_redirect'), 10, 3);
        add_action('wp_dashboard_setup', array($this, 'add_jury_dashboard_widget'));
        add_shortcode('mt_jury_dashboard', array($this, 'jury_dashboard_shortcode'));
        add_action('wp_ajax_mt_get_candidate_details', array($this, 'ajax_get_candidate_details'));
        
        // Evaluation page hooks
        add_action('admin_menu', array($this, 'add_evaluation_page'));
        add_action('admin_post_mt_submit_evaluation', array($this, 'handle_evaluation_submission'));
        
        add_action('admin_menu', array($this, 'add_diagnostic_menu'));
        add_action('admin_init', array($this, 'ensure_jury_menu_exists'));
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

    private function load_textdomain() {
        load_plugin_textdomain('mobility-trailblazers', false, dirname(MT_PLUGIN_BASENAME) . '/languages');
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
     * Create Custom Post Types
     */
    public function create_custom_post_types() {
        // Candidates Post Type
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
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'mt-award-system',
            'query_var' => true,
            'rewrite' => array('slug' => 'candidate'),
            'capability_type' => array('mt_candidate', 'mt_candidates'),
            'map_meta_cap' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true
        ));

        // Jury Members Post Type
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
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'mt-award-system',
            'query_var' => true,
            'rewrite' => array('slug' => 'jury'),
            'capability_type' => array('mt_jury', 'mt_jurys'),
            'map_meta_cap' => true,
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true
        ));

        // Awards Post Type
        register_post_type('mt_award', array(
            'labels' => array(
                'name' => __('Awards', 'mobility-trailblazers'),
                'singular_name' => __('Award', 'mobility-trailblazers'),
                'add_new' => __('Add New Award', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Award', 'mobility-trailblazers'),
                'edit_item' => __('Edit Award', 'mobility-trailblazers'),
                'new_item' => __('New Award', 'mobility-trailblazers'),
                'view_item' => __('View Award', 'mobility-trailblazers'),
                'search_items' => __('Search Awards', 'mobility-trailblazers'),
                'not_found' => __('No awards found', 'mobility-trailblazers'),
                'not_found_in_trash' => __('No awards found in trash', 'mobility-trailblazers')
            ),
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => 'mt-award-system',
            'query_var' => true,
            'rewrite' => array('slug' => 'award'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true
        ));
    }

    /**
     * Create Custom Taxonomies
     */
    public function create_custom_taxonomies() {
        // Category taxonomy for candidates (3 dimensions from docs)
        register_taxonomy('mt_category', array('mt_candidate'), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Categories', 'mobility-trailblazers'),
                'singular_name' => __('Category', 'mobility-trailblazers'),
                'search_items' => __('Search Categories', 'mobility-trailblazers'),
                'all_items' => __('All Categories', 'mobility-trailblazers'),
                'parent_item' => __('Parent Category', 'mobility-trailblazers'),
                'parent_item_colon' => __('Parent Category:', 'mobility-trailblazers'),
                'edit_item' => __('Edit Category', 'mobility-trailblazers'),
                'update_item' => __('Update Category', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Category', 'mobility-trailblazers'),
                'new_item_name' => __('New Category Name', 'mobility-trailblazers'),
                'menu_name' => __('Categories', 'mobility-trailblazers'),
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'candidate-category'),
            'show_in_rest' => true
        ));

        // Award Year taxonomy
        register_taxonomy('mt_award_year', array('mt_candidate', 'mt_award'), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Award Years', 'mobility-trailblazers'),
                'singular_name' => __('Award Year', 'mobility-trailblazers'),
                'search_items' => __('Search Years', 'mobility-trailblazers'),
                'all_items' => __('All Years', 'mobility-trailblazers'),
                'edit_item' => __('Edit Year', 'mobility-trailblazers'),
                'update_item' => __('Update Year', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Year', 'mobility-trailblazers'),
                'new_item_name' => __('New Year Name', 'mobility-trailblazers'),
                'menu_name' => __('Award Years', 'mobility-trailblazers'),
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'award-year'),
            'show_in_rest' => true
        ));

        // Selection Status taxonomy
        register_taxonomy('mt_status', array('mt_candidate'), array(
            'hierarchical' => true,
            'labels' => array(
                'name' => __('Selection Status', 'mobility-trailblazers'),
                'singular_name' => __('Status', 'mobility-trailblazers'),
                'search_items' => __('Search Status', 'mobility-trailblazers'),
                'all_items' => __('All Status', 'mobility-trailblazers'),
                'edit_item' => __('Edit Status', 'mobility-trailblazers'),
                'update_item' => __('Update Status', 'mobility-trailblazers'),
                'add_new_item' => __('Add New Status', 'mobility-trailblazers'),
                'new_item_name' => __('New Status Name', 'mobility-trailblazers'),
                'menu_name' => __('Status', 'mobility-trailblazers'),
            ),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'candidate-status'),
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

        // Public voting table
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
        dbDelta($sql_public_votes);
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
        add_action('wp_ajax_mt_submit_public_vote', array($this, 'handle_public_vote'));
        add_action('wp_ajax_nopriv_mt_submit_public_vote', array($this, 'handle_public_vote'));
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
        if (strpos($hook, 'mt-') !== false || in_array($hook, array('post.php', 'post-new.php'))) {
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

    /**
     * Enqueue frontend scripts and styles
     */
    public function frontend_enqueue_scripts() {
        wp_enqueue_script('mt-frontend-js', MT_PLUGIN_URL . 'assets/frontend.js', array('jquery'), MT_PLUGIN_VERSION, true);
        wp_enqueue_style('mt-frontend-css', MT_PLUGIN_URL . 'assets/frontend.css', array(), MT_PLUGIN_VERSION);
        
        wp_localize_script('mt-frontend-js', 'mt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_public_nonce'),
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
        $table_public_votes = $wpdb->prefix . 'mt_public_votes';
        
        $jury_votes = $wpdb->get_var("SELECT COUNT(*) FROM $table_votes");
        $public_votes = $wpdb->get_var("SELECT COUNT(*) FROM $table_public_votes");
        
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
        
        echo '<div class="mt-stat-box">';
        echo '<h3>' . __('Public Votes', 'mobility-trailblazers') . '</h3>';
        echo '<div class="mt-stat-number">' . $public_votes . '</div>';
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
        if (!check_ajax_referer('mt_nonce', 'nonce', false)) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        $jury_member_id = get_current_user_id();
        
        if (!$this->is_jury_member($jury_member_id)) {
            wp_die(__('Unauthorized access.', 'mobility-trailblazers'));
        }

        $scores = array(
            'courage_score' => intval($_POST['courage_score']),
            'innovation_score' => intval($_POST['innovation_score']),
            'implementation_score' => intval($_POST['implementation_score']),
            'mobility_relevance_score' => intval($_POST['mobility_relevance_score']),
            'visibility_score' => intval($_POST['visibility_score'])
        );

        $total_score = array_sum($scores);

        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';

        $result = $wpdb->replace(
            $table_scores,
            array_merge($scores, array(
                'candidate_id' => $candidate_id,
                'jury_member_id' => $jury_member_id,
                'total_score' => $total_score,
                'evaluation_round' => 1,
                'evaluation_date' => current_time('mysql')
            ))
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => __('Evaluation submitted successfully!', 'mobility-trailblazers')));
        } else {
            wp_send_json_error(array('message' => __('Error submitting evaluation.', 'mobility-trailblazers')));
        }
    }

    /**
     * Handle public vote submission
     */
    public function handle_public_vote() {
        if (!check_ajax_referer('mt_public_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed.', 'mobility-trailblazers')));
        }
        
        $candidate_id = intval($_POST['candidate_id']);
        $voter_email = sanitize_email($_POST['voter_email']);
        $voter_ip = $_SERVER['REMOTE_ADDR'];

        if (!is_email($voter_email)) {
            wp_send_json_error(array('message' => __('Please provide a valid email address.', 'mobility-trailblazers')));
        }

        global $wpdb;
        $table_public_votes = $wpdb->prefix . 'mt_public_votes';

        // Check if already voted
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_public_votes WHERE candidate_id = %d AND voter_email = %s",
            $candidate_id,
            $voter_email
        ));

        if ($existing_vote) {
            wp_send_json_error(array('message' => __('You have already voted for this candidate.', 'mobility-trailblazers')));
        }

        $result = $wpdb->insert(
            $table_public_votes,
            array(
                'candidate_id' => $candidate_id,
                'voter_email' => $voter_email,
                'voter_ip' => $voter_ip,
                'vote_date' => current_time('mysql')
            )
        );

        if ($result !== false) {
            wp_send_json_success(array('message' => __('Thank you for your vote!', 'mobility-trailblazers')));
        } else {
            wp_send_json_error(array('message' => __('Error submitting vote.', 'mobility-trailblazers')));
        }
    }

    /**
     * Check if user is jury member
     */
    private function is_jury_member($user_id) {
        if (!$user_id) return false;
        
        $user = get_user_by('id', $user_id);
        if (!$user) return false;

        // Check if user has jury role
        if (in_array('mt_jury_member', (array) $user->roles)) {
            return true;
        }

        // Check if user is linked to a jury member post
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_mt_jury_email',
                    'value' => $user->user_email,
                    'compare' => '='
                ),
                array(
                    'key' => '_mt_jury_user_id',
                    'value' => $user_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));

        return !empty($jury_posts) || user_can($user_id, 'manage_options');
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
            'type' => 'public',
            'limit' => 10
        ), $atts);

        global $wpdb;

        if ($atts['type'] === 'public') {
            $table = $wpdb->prefix . 'mt_public_votes';
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT p.ID, p.post_title, COUNT(v.id) as vote_count
                FROM {$wpdb->posts} p
                LEFT JOIN $table v ON p.ID = v.candidate_id
                WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish'
                GROUP BY p.ID
                ORDER BY vote_count DESC
                LIMIT %d
            ", intval($atts['limit'])));
        } else {
            $table = $wpdb->prefix . 'mt_candidate_scores';
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT p.ID, p.post_title, AVG(s.total_score) as avg_score, COUNT(s.id) as evaluation_count
                FROM {$wpdb->posts} p
                LEFT JOIN $table s ON p.ID = s.candidate_id
                WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish'
                GROUP BY p.ID
                HAVING evaluation_count > 0
                ORDER BY avg_score DESC
                LIMIT %d
            ", intval($atts['limit'])));
        }

        if (empty($results)) {
            return '<p>' . __('No voting results available yet.', 'mobility-trailblazers') . '</p>';
        }

        ob_start();
        ?>
        <div class="mt-voting-results">
            <h3><?php echo $atts['type'] === 'public' ? __('Public Voting Results', 'mobility-trailblazers') : __('Jury Evaluation Results', 'mobility-trailblazers'); ?></h3>
            
            <ol class="mt-results-list">
                <?php foreach ($results as $result): ?>
                    <li class="mt-result-item">
                        <span class="mt-candidate-name"><?php echo esc_html($result->post_title); ?></span>
                        <span class="mt-result-score">
                            <?php if ($atts['type'] === 'public'): ?>
                                <?php echo intval($result->vote_count); ?> <?php _e('votes', 'mobility-trailblazers'); ?>
                            <?php else: ?>
                                <?php echo number_format($result->avg_score, 1); ?>/50 
                                (<?php echo intval($result->evaluation_count); ?> <?php _e('evaluations', 'mobility-trailblazers'); ?>)
                            <?php endif; ?>
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
                "SELECT * FROM $table_scores WHERE candidate_id = %d AND jury_member_id = %d AND evaluation_round = 1",
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
        echo '<div class="wrap">';
        echo '<h1>' . __('Voting Results', 'mobility-trailblazers') . '</h1>';
        
        // Jury results
        echo '<div class="mt-results-section">';
        echo do_shortcode('[mt_voting_results type="jury" limit="25"]');
        echo '</div>';
        
        echo '<hr>';
        
        // Public results
        echo '<div class="mt-results-section">';
        echo do_shortcode('[mt_voting_results type="public" limit="25"]');
        echo '</div>';
        
        echo '</div>';
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['mt_settings_nonce'], 'mt_settings')) {
            update_option('mt_voting_enabled', isset($_POST['voting_enabled']));
            update_option('mt_public_voting_enabled', isset($_POST['public_voting_enabled']));
            update_option('mt_current_phase', sanitize_text_field($_POST['current_phase']));
            update_option('mt_award_year', sanitize_text_field($_POST['award_year']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved.', 'mobility-trailblazers') . '</p></div>';
        }

        $voting_enabled = get_option('mt_voting_enabled', false);
        $public_voting_enabled = get_option('mt_public_voting_enabled', false);
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
            'public_voting' => __('Public Voting', 'mobility-trailblazers'),
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
        
        echo '<tr><th scope="row">' . __('Enable Public Voting', 'mobility-trailblazers') . '</th>';
        echo '<td><input type="checkbox" name="public_voting_enabled" value="1"' . checked($public_voting_enabled, 1, false) . ' /> ' . __('Allow public to vote for candidates', 'mobility-trailblazers') . '</td></tr>';
        
        echo '</table>';
        
        echo '<p class="submit"><input type="submit" name="submit" class="button-primary" value="' . __('Save Settings', 'mobility-trailblazers') . '" /></p>';
        echo '</form>';
        
        echo '</div>';
    }

    /**
     * Assignment Management Page - CORRECTED VERSION
     */
    public function assignment_management_page() {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Get data for JavaScript
        $candidates_data = $this->get_candidates_for_assignment();
        $jury_data = $this->get_jury_members_for_assignment();
        $existing_assignments = $this->get_existing_assignments();
        
        // Get current statistics
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
        
        // Get current phase
        $current_phase = get_option('mt_current_phase', 'preparation');
        $phase_names = array(
            'preparation' => 'Preparation',
            'candidate_collection' => 'Candidate Collection',
            'jury_evaluation' => 'Jury Evaluation',
            'public_voting' => 'Public Voting',
            'final_selection' => 'Final Selection',
            'award_ceremony' => 'Award Ceremony',
            'post_award' => 'Post Award'
        );
        
        ?>
        <div class="wrap">
            <h1><?php _e('Jury Assignment Management', 'mobility-trailblazers'); ?></h1>
            
            <!-- Debug Information -->
            <div id="debug-info" style="background: #fff; border: 1px solid #ccc; padding: 15px; margin: 20px 0;">
                <h3>Debug Information</h3>
                <p><strong>Total Candidates:</strong> <?php echo $total_candidates; ?></p>
                <p><strong>Total Jury Members:</strong> <?php echo $total_jury; ?></p>
                <p><strong>Assigned Count:</strong> <?php echo $assigned_count; ?></p>
                <p><strong>Completion Rate:</strong> <?php echo number_format($completion_rate, 1); ?>%</p>
                <p><strong>Current Phase:</strong> <?php echo $current_phase; ?></p>
            </div>
            
            <!-- Assignment Interface Container -->
            <div id="mt-assignment-interface" class="mt-assignment-interface">
                
                <!-- Header -->
                <div class="mt-assignment-header" style="background: linear-gradient(135deg, #2c5282 0%, #38b2ac 100%); color: white; padding: 30px; margin-bottom: 30px; border-radius: 15px; text-align: center;">
                    <h1 style="font-size: 2.5rem; font-weight: 700; margin: 0 0 10px 0;">🏆 Jury Assignment System</h1>
                    <p style="font-size: 1.2rem; opacity: 0.9; margin: 0;">Advanced Assignment Interface v3.2 - Mobility Trailblazers 2025</p>
                </div>

                <!-- Status Banner -->
                <div class="mt-status-banner" style="background: #38a169; color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 30px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">✅</span>
                    <div>
                        <strong>System Status: OPERATIONAL</strong> | Last check: <?php echo date('H:i:s'); ?> | 
                        Active Phase: <?php echo esc_html($phase_names[$current_phase] ?? $current_phase); ?>
                    </div>
                </div>

                <!-- Statistics Grid -->
                <div class="mt-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="mt-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-left: 4px solid #38b2ac; text-align: center;">
                        <span class="mt-stat-number" style="font-size: 2.5rem; font-weight: 700; color: #2c5282; display: block; line-height: 1;"><?php echo $total_candidates; ?></span>
                        <div class="mt-stat-label" style="color: #718096; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Total Candidates</div>
                    </div>
                    <div class="mt-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-left: 4px solid #38b2ac; text-align: center;">
                        <span class="mt-stat-number" style="font-size: 2.5rem; font-weight: 700; color: #2c5282; display: block; line-height: 1;"><?php echo $total_jury; ?></span>
                        <div class="mt-stat-label" style="color: #718096; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Jury Members</div>
                    </div>
                    <div class="mt-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-left: 4px solid #38b2ac; text-align: center;">
                        <span class="mt-stat-number" id="assigned-count" style="font-size: 2.5rem; font-weight: 700; color: #2c5282; display: block; line-height: 1;"><?php echo $assigned_count; ?></span>
                        <div class="mt-stat-label" style="color: #718096; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Total Assignments</div>
                    </div>
                    <div class="mt-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-left: 4px solid #38b2ac; text-align: center;">
                        <span class="mt-stat-number" id="completion-rate" style="font-size: 2.5rem; font-weight: 700; color: #2c5282; display: block; line-height: 1;"><?php echo number_format($completion_rate, 1); ?>%</span>
                        <div class="mt-stat-label" style="color: #718096; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Completion Rate</div>
                    </div>
                    <div class="mt-stat-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-left: 4px solid #38b2ac; text-align: center;">
                        <span class="mt-stat-number" id="avg-per-jury" style="font-size: 2.5rem; font-weight: 700; color: #2c5282; display: block; line-height: 1;"><?php echo number_format($avg_per_jury, 1); ?></span>
                        <div class="mt-stat-label" style="color: #718096; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 5px;">Avg Per Jury</div>
                    </div>
                </div>

                <!-- Assignment Controls -->
                <div class="mt-assignment-controls" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <h3 style="margin: 0 0 20px 0; color: #2c5282; font-size: 1.3rem;">🔧 Assignment Tools</h3>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                        <button id="mt-auto-assign-btn" onclick="testAutoAssign()" style="background: #38a169; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">⚡ Auto-Assign All</button>
                        <button id="mt-manual-assign-btn" disabled style="background: #d69e2e; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; opacity: 0.6;">👥 Assign Selected (0)</button>
                        <button id="mt-clear-assignments-btn" onclick="testClearAssignments()" style="background: #718096; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">🗑️ Clear All Assignments</button>
                        <button id="mt-export-btn" onclick="testExport()" style="background: #2c5282; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">📊 Export Data</button>
                        <button id="mt-refresh-btn" onclick="location.reload()" style="background: #718096; color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">🔄 Refresh Data</button>
                    </div>
                    
                    <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                        <label>Candidates per Jury Member: 
                            <input type="number" id="candidates-per-jury" value="<?php echo ceil($total_candidates / max($total_jury, 1)); ?>" min="1" max="50" style="width: 80px; padding: 5px; margin-left: 5px;">
                        </label>
                        <label>Algorithm: 
                            <select id="assignment-algorithm" style="padding: 5px; margin-left: 5px;">
                                <option value="balanced">Balanced Distribution</option>
                                <option value="random">Random Assignment</option>
                                <option value="category">Category Balanced</option>
                            </select>
                        </label>
                    </div>
                </div>

                <!-- Simple Candidate and Jury Lists -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Candidates Panel -->
                    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <div style="background: #2c5282; color: white; padding: 20px;">
                            <h3 style="font-size: 1.3rem; font-weight: 600; margin: 0;">📋 Candidates (<?php echo count($candidates_data); ?>)</h3>
                        </div>
                        <div style="padding: 20px; max-height: 400px; overflow-y: auto;">
                            <div id="candidates-list">
                                <?php foreach (array_slice($candidates_data, 0, 10) as $candidate): ?>
                                    <div style="padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px;">
                                        <strong><?php echo esc_html($candidate['name']); ?></strong><br>
                                        <small><?php echo esc_html($candidate['company']); ?></small><br>
                                        <span style="color: <?php echo $candidate['assigned'] ? '#38a169' : '#e53e3e'; ?>;">
                                            <?php echo $candidate['assigned'] ? 'Assigned' : 'Unassigned'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($candidates_data) > 10): ?>
                                    <div style="text-align: center; padding: 10px; color: #718096;">
                                        ... and <?php echo count($candidates_data) - 10; ?> more candidates
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Jury Panel -->
                    <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <div style="background: #2c5282; color: white; padding: 20px;">
                            <h3 style="font-size: 1.3rem; font-weight: 600; margin: 0;">👨‍⚖️ Jury Members (<?php echo count($jury_data); ?>)</h3>
                        </div>
                        <div style="padding: 20px; max-height: 400px; overflow-y: auto;">
                            <div id="jury-list">
                                <?php foreach ($jury_data as $jury): ?>
                                    <div style="padding: 15px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 10px; <?php echo $jury['role'] === 'president' ? 'border-color: #ffd700;' : ($jury['role'] === 'vice_president' ? 'border-color: #38b2ac;' : ''); ?>">
                                        <strong><?php echo esc_html($jury['name']); ?></strong>
                                        <?php if ($jury['role'] === 'president'): ?>
                                            <span style="background: #ffd700; color: #8b6914; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-left: 10px;">President</span>
                                        <?php elseif ($jury['role'] === 'vice_president'): ?>
                                            <span style="background: #38b2ac; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; margin-left: 10px;">Vice President</span>
                                        <?php endif; ?>
                                        <br>
                                        <small><?php echo esc_html($jury['position']); ?></small><br>
                                        <div style="margin-top: 10px; padding: 10px; background: #f7fafc; border-radius: 5px; font-size: 0.9rem;">
                                            Assignments: <strong><?php echo $jury['assignments']; ?>/<?php echo $jury['max_assignments']; ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        // Embedded JavaScript for testing
        function testAutoAssign() {
            if (confirm('Auto-assign all unassigned candidates to jury members?\n\nThis will distribute approximately <?php echo ceil($total_candidates / max($total_jury, 1)); ?> candidates per jury member.')) {
                var candidatesPerJury = document.getElementById('candidates-per-jury').value;
                var algorithm = document.getElementById('assignment-algorithm').value;
                
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'mt_auto_assign',
                    candidates_per_jury: candidatesPerJury,
                    algorithm: algorithm,
                    nonce: '<?php echo wp_create_nonce('mt_assignment_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('Auto-assignment completed successfully!\n\n' + response.data.message);
                        location.reload();
                    } else {
                        alert('Auto-assignment failed:\n\n' + (response.data.message || 'Unknown error'));
                    }
                }).fail(function() {
                    alert('Network error during auto-assignment. Please try again.');
                });
            }
        }
        
        function testClearAssignments() {
            if (confirm('Clear all current assignments?\n\nThis will remove all candidate-jury assignments. This action cannot be undone.')) {
                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'mt_clear_assignments',
                    nonce: '<?php echo wp_create_nonce('mt_assignment_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert('All assignments cleared successfully!\n\n' + response.data.message);
                        location.reload();
                    } else {
                        alert('Clear assignments failed:\n\n' + (response.data.message || 'Unknown error'));
                    }
                }).fail(function() {
                    alert('Network error during clear assignments. Please try again.');
                });
            }
        }
        
        function testExport() {
            window.open('<?php echo admin_url('admin-ajax.php'); ?>?action=mt_export_assignments&nonce=<?php echo wp_create_nonce('mt_assignment_nonce'); ?>', '_blank');
        }
        
        // Debug information
        jQuery(document).ready(function($) {
            console.log('Assignment page loaded successfully');
            console.log('Total candidates: <?php echo $total_candidates; ?>');
            console.log('Total jury members: <?php echo $total_jury; ?>');
            console.log('Current assignments: <?php echo $assigned_count; ?>');
            console.log('Completion rate: <?php echo $completion_rate; ?>%');
            
            // Test AJAX connectivity
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'mt_get_assignment_stats',
                nonce: '<?php echo wp_create_nonce('mt_assignment_nonce'); ?>'
            }, function(response) {
                console.log('AJAX connectivity test:', response.success ? 'PASSED' : 'FAILED');
            }).fail(function() {
                console.log('AJAX connectivity test: FAILED - Network error');
            });
        });
        </script>
        
        <style>
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        button:disabled {
            cursor: not-allowed !important;
            transform: none !important;
            box-shadow: none !important;
        }
        </style>
        <?php
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
    private function get_candidates_for_assignment() {
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
     * Get jury members formatted for assignment interface
     */
    private function get_jury_members_for_assignment() {
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $formatted_jury = array();
        
        foreach ($jury_members as $jury) {
            $position = get_post_meta($jury->ID, '_mt_jury_position', true);
            $expertise = get_post_meta($jury->ID, '_mt_jury_expertise', true);
            $is_president = get_post_meta($jury->ID, '_mt_jury_is_president', true);
            $is_vice_president = get_post_meta($jury->ID, '_mt_jury_is_vice_president', true);
            
            // Count current assignments
            $assignments = get_posts(array(
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => '_mt_assigned_jury_member',
                        'value' => $jury->ID,
                        'compare' => '='
                    )
                )
            ));
            
            $role = 'member';
            if ($is_president) $role = 'president';
            elseif ($is_vice_president) $role = 'vice_president';
            
            $formatted_jury[] = array(
                'id' => $jury->ID,
                'name' => $jury->post_title,
                'position' => $position ?: 'No position',
                'expertise' => $expertise ?: 'General expertise',
                'role' => $role,
                'assignments' => count($assignments),
                'max_assignments' => 25 // Configurable
            );
        }
        
        return $formatted_jury;
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
     * Add jury dashboard to admin menu
     */
    public function add_jury_dashboard_menu() {
        // Debug logging
        error_log('MT Debug: add_jury_dashboard_menu called');
        
        $current_user_id = get_current_user_id();
        error_log('MT Debug: Current user ID: ' . $current_user_id);
        
        // Check if user is a jury member or admin
        $is_jury = $this->is_jury_member($current_user_id);
        $is_admin = current_user_can('manage_options');
        
        error_log('MT Debug: Is jury: ' . ($is_jury ? 'yes' : 'no'));
        error_log('MT Debug: Is admin: ' . ($is_admin ? 'yes' : 'no'));
        
        if (!$is_jury && !$is_admin) {
            error_log('MT Debug: User does not have permission');
            return;
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
            error_log('MT Debug: Parent menu mt-award-system does not exist!');
            // Try to create it
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
        
        // Add the submenu
        $result = add_submenu_page(
            'mt-award-system',
            __('My Dashboard', 'mobility-trailblazers'),
            __('My Dashboard', 'mobility-trailblazers'),
            'read', // Low capability so jury members can access
            'mt-jury-dashboard',
            array($this, 'jury_dashboard_page')
        );
        
        error_log('MT Debug: add_submenu_page result: ' . ($result ? 'success' : 'failed'));
    }

    /**
     * Ensure jury menu exists (fallback method)
     */
    public function ensure_jury_menu_exists() {
        // This runs later to ensure the menu exists
        global $submenu;
        
        if (!isset($submenu['mt-award-system'])) {
            return;
        }
        
        // Check if our menu already exists
        $exists = false;
        foreach ($submenu['mt-award-system'] as $item) {
            if ($item[2] === 'mt-jury-dashboard') {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $current_user_id = get_current_user_id();
            if ($this->is_jury_member($current_user_id) || current_user_can('manage_options')) {
                $submenu['mt-award-system'][] = array(
                    __('My Dashboard', 'mobility-trailblazers'),
                    'read',
                    'mt-jury-dashboard',
                    __('My Dashboard', 'mobility-trailblazers')
                );
            }
        }
    }

    /**
     * Render the jury dashboard page
     */
    public function jury_dashboard_page() {
        $current_user_id = get_current_user_id();
        $jury_member_id = $this->get_jury_member_for_user($current_user_id);
        
        if (!$jury_member_id && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
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
                <div class="mt-candidates-grid">
                    <?php foreach ($assigned_candidates as $candidate) : 
                        $evaluated = $this->has_jury_member_evaluated($jury_member_id, $candidate->ID);
                        $company = get_post_meta($candidate->ID, '_mt_company', true);
                        $position = get_post_meta($candidate->ID, '_mt_position', true);
                        $category = wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'names'));
                        $category_name = !empty($category) ? $category[0] : '';
                    ?>
                        <div class="mt-candidate-card <?php echo $evaluated ? 'evaluated' : 'pending'; ?>">
                            <div class="mt-candidate-header">
                                <h3><?php echo esc_html($candidate->post_title); ?></h3>
                                <span class="mt-status-badge">
                                    <?php echo $evaluated ? '✅ ' . __('Evaluated', 'mobility-trailblazers') : '⏳ ' . __('Pending', 'mobility-trailblazers'); ?>
                                </span>
                            </div>
                            
                            <div class="mt-candidate-info">
                                <?php if ($position) : ?>
                                    <p><strong><?php echo esc_html($position); ?></strong></p>
                                <?php endif; ?>
                                <?php if ($company) : ?>
                                    <p><?php echo esc_html($company); ?></p>
                                <?php endif; ?>
                                <?php if ($category_name) : ?>
                                    <p class="mt-category"><?php echo esc_html($category_name); ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mt-candidate-actions">
                                <a href="<?php echo get_permalink($candidate->ID); ?>" class="button" target="_blank">
                                    <?php _e('View Profile', 'mobility-trailblazers'); ?>
                                </a>
                                <?php if ($evaluated) : ?>
                                    <a href="<?php echo admin_url('admin.php?page=mt-evaluate&candidate=' . $candidate->ID . '&edit=1'); ?>" class="button button-secondary">
                                        <?php _e('Edit Evaluation', 'mobility-trailblazers'); ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo admin_url('admin.php?page=mt-evaluate&candidate=' . $candidate->ID); ?>" class="button button-primary">
                                        <?php _e('Evaluate Now', 'mobility-trailblazers'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
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
        add_submenu_page(
            null, // No parent menu (hidden)
            __('Evaluate Candidate', 'mobility-trailblazers'),
            __('Evaluate Candidate', 'mobility-trailblazers'),
            'read',
            'mt-evaluate',
            array($this, 'evaluation_page')
        );
    }

    /**
     * Render the evaluation page
     */
    public function evaluation_page() {
        $current_user_id = get_current_user_id();
        $jury_member_id = $this->get_jury_member_for_user($current_user_id);
        
        if (!$jury_member_id && !current_user_can('manage_options')) {
            wp_die(__('You do not have permission to access this page.', 'mobility-trailblazers'));
        }
        
        $candidate_id = isset($_GET['candidate']) ? intval($_GET['candidate']) : 0;
        $edit_mode = isset($_GET['edit']) && $_GET['edit'] == '1';
        
        if (!$candidate_id) {
            wp_die(__('No candidate specified.', 'mobility-trailblazers'));
        }
        
        $candidate = get_post($candidate_id);
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            wp_die(__('Invalid candidate.', 'mobility-trailblazers'));
        }
        
        // Check if candidate is assigned to this jury member
        $assigned_jury = get_post_meta($candidate_id, '_mt_assigned_jury_member', true);
        if ($assigned_jury != $jury_member_id && !current_user_can('manage_options')) {
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
            
            <style>
                .mt-evaluation-container {
                    display: grid;
                    grid-template-columns: 1fr 2fr;
                    gap: 30px;
                    margin-top: 20px;
                }
                
                .mt-candidate-details {
                    background: white;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                
                .mt-candidate-details h2 {
                    margin-top: 0;
                }
                
                .mt-bio, .mt-achievements {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                }
                
                .mt-evaluation-form {
                    background: white;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
                }
                
                .mt-criterion {
                    margin-bottom: 30px;
                    padding-bottom: 30px;
                    border-bottom: 1px solid #eee;
                }
                
                .mt-criterion:last-of-type {
                    border-bottom: none;
                }
                
                .mt-criterion h3 {
                    margin-bottom: 10px;
                }
                
                .mt-score-selector {
                    display: flex;
                    gap: 10px;
                    margin-top: 15px;
                }
                
                .mt-score-option {
                    flex: 1;
                    text-align: center;
                    cursor: pointer;
                }
                
                .mt-score-option input[type="radio"] {
                    display: none;
                }
                
                .mt-score-option span {
                    display: block;
                    padding: 10px;
                    border: 2px solid #ddd;
                    border-radius: 5px;
                    font-weight: bold;
                    transition: all 0.3s ease;
                }
                
                .mt-score-option input[type="radio"]:checked + span {
                    background: #0073aa;
                    color: white;
                    border-color: #0073aa;
                }
                
                .mt-score-option:hover span {
                    border-color: #0073aa;
                }
                
                .mt-form-actions {
                    margin-top: 30px;
                    padding-top: 30px;
                    border-top: 1px solid #eee;
                    display: flex;
                    gap: 10px;
                }
                
                @media (max-width: 782px) {
                    .mt-evaluation-container {
                        grid-template-columns: 1fr;
                    }
                    
                    .mt-score-selector {
                        flex-wrap: wrap;
                    }
                    
                    .mt-score-option {
                        flex: 0 0 18%;
                    }
                }
            </style>
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
}

// Instantiate the plugin
new MobilityTrailblazersPlugin();

?>