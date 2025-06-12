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
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    private function load_textdomain() {
        load_plugin_textdomain('mobility-trailblazers', false, dirname(MT_PLUGIN_BASENAME) . '/languages');
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
            'capability_type' => 'post',
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
            'capability_type' => 'post',
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

        // Check if user has jury role or is linked to a jury member post
        $jury_posts = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_query' => array(
                array(
                    'key' => '_mt_jury_email',
                    'value' => $user->user_email,
                    'compare' => '='
                )
            )
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
     * Assignment Management Page
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
        
        // CRITICAL: Enqueue scripts FIRST
        wp_enqueue_style('mt-assignment-style', plugins_url('assets/assignment.css', __FILE__));
        wp_enqueue_script('mt-assignment-script', plugins_url('assets/assignment.js', __FILE__), array('jquery'), '1.0', true);
        
        // THEN localize (must be after enqueue)
        wp_localize_script('mt-assignment-script', 'mtAssignmentData', array(
            'candidates' => $candidates_data,
            'jury' => $jury_data,
            'assignments' => $existing_assignments,
            'stats' => array(
                'total_candidates' => $total_candidates,
                'total_jury' => $total_jury,
                'assigned_count' => $assigned_count,
                'completion_rate' => $completion_rate,
                'avg_per_jury' => $avg_per_jury
            ),
            'current_phase' => $current_phase,
            'phase_names' => $phase_names,
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_assignment_nonce')
        ));
        
        // Render the HTML content
        ?>
        <div class="wrap">
            <h1><?php _e('Jury Assignment Management', 'mobility-trailblazers'); ?></h1>
            
            <div id="mt-assignment-interface" class="mt-assignment-interface">
                <!-- Interface will be built by JavaScript -->
                <div id="loading-message" style="text-align: center; padding: 50px;">
                    <h2>Loading Assignment Interface...</h2>
                    <p>If this message persists, check browser console for errors.</p>
                </div>
            </div>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Debug output
            console.log('Assignment page loaded');
            console.log('mtAssignmentData:', typeof mtAssignmentData !== 'undefined' ? mtAssignmentData : 'UNDEFINED');
            
            // Initialize interface if data is available
            if (typeof mtAssignmentData !== 'undefined' && typeof initAssignmentInterface === 'function') {
                initAssignmentInterface();
                $('#loading-message').hide();
            } else {
                console.error('mtAssignmentData or initAssignmentInterface not available');
                $('#loading-message').html('<h2 style="color: red;">Error: Assignment interface failed to load</h2><p>Check browser console for details.</p>');
            }
        });
        </script>
        <?php
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
}

// Initialize the plugin
new MobilityTrailblazersPlugin();

/**
 * Additional helper functions
 */

/**
 * Get candidate vote count
 */
function mt_get_candidate_vote_count($candidate_id, $type = 'public') {
    global $wpdb;
    
    if ($type === 'public') {
        $table = $wpdb->prefix . 'mt_public_votes';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE candidate_id = %d",
            $candidate_id
        ));
    } else {
        $table = $wpdb->prefix . 'mt_candidate_scores';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(total_score) FROM $table WHERE candidate_id = %d AND evaluation_round = 1",
            $candidate_id
        ));
    }
}

/**
 * Get candidate ranking
 */
function mt_get_candidate_ranking($candidate_id, $type = 'public') {
    global $wpdb;
    
    if ($type === 'public') {
        $table = $wpdb->prefix . 'mt_public_votes';
        $results = $wpdb->get_results("
            SELECT p.ID, COUNT(v.id) as vote_count
            FROM {$wpdb->posts} p
            LEFT JOIN $table v ON p.ID = v.candidate_id
            WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY vote_count DESC
        ");
    } else {
        $table = $wpdb->prefix . 'mt_candidate_scores';
        $results = $wpdb->get_results("
            SELECT p.ID, AVG(s.total_score) as avg_score
            FROM {$wpdb->posts} p
            LEFT JOIN $table s ON p.ID = s.candidate_id
            WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish'
            GROUP BY p.ID
            HAVING avg_score IS NOT NULL
            ORDER BY avg_score DESC
        ");
    }
    
    $ranking = 1;
    foreach ($results as $result) {
        if ($result->ID == $candidate_id) {
            return $ranking;
        }
        $ranking++;
    }
    
    return null;
}

/**
 * Export candidates to CSV
 */
function mt_export_candidates_csv() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized access.', 'mobility-trailblazers'));
    }
    
    $candidates = get_posts(array(
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));
    
    $filename = 'mobility-trailblazers-candidates-' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, array(
        'Name',
        'Company',
        'Position',
        'Location',
        'Email',
        'Category',
        'Status',
        'Public Votes',
        'Jury Score',
        'Ranking'
    ));
    
    foreach ($candidates as $candidate) {
        $company = get_post_meta($candidate->ID, '_mt_company', true);
        $position = get_post_meta($candidate->ID, '_mt_position', true);
        $location = get_post_meta($candidate->ID, '_mt_location', true);
        $email = get_post_meta($candidate->ID, '_mt_email', true);
        
        $categories = wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'names'));
        $statuses = wp_get_post_terms($candidate->ID, 'mt_status', array('fields' => 'names'));
        
        $public_votes = mt_get_candidate_vote_count($candidate->ID, 'public');
        $jury_score = mt_get_candidate_vote_count($candidate->ID, 'jury');
        $ranking = mt_get_candidate_ranking($candidate->ID, 'public');
        
        fputcsv($output, array(
            $candidate->post_title,
            $company,
            $position,
            $location,
            $email,
            implode(', ', $categories),
            implode(', ', $statuses),
            $public_votes,
            $jury_score ? number_format($jury_score, 1) : '0',
            $ranking ? $ranking : '-'
        ));
    }
    
    fclose($output);
    exit;
}

// Add export action
add_action('admin_post_mt_export_candidates', 'mt_export_candidates_csv');

/**
 * REST API endpoints
 */
add_action('rest_api_init', function() {
    // Get candidates endpoint
    register_rest_route('mobility-trailblazers/v1', '/candidates', array(
        'methods' => 'GET',
        'callback' => 'mt_rest_get_candidates',
        'permission_callback' => '__return_true'
    ));
    
    // Get voting results endpoint
    register_rest_route('mobility-trailblazers/v1', '/results', array(
        'methods' => 'GET',
        'callback' => 'mt_rest_get_results',
        'permission_callback' => '__return_true'
    ));
    
    // Submit public vote endpoint
    register_rest_route('mobility-trailblazers/v1', '/vote', array(
        'methods' => 'POST',
        'callback' => 'mt_rest_submit_vote',
        'permission_callback' => '__return_true'
    ));
});

function mt_rest_get_candidates($request) {
    $args = array(
        'post_type' => 'mt_candidate',
        'posts_per_page' => $request->get_param('per_page') ?: 25,
        'post_status' => 'publish'
    );
    
    if ($request->get_param('category')) {
        $args['tax_query'][] = array(
            'taxonomy' => 'mt_category',
            'field' => 'slug',
            'terms' => $request->get_param('category')
        );
    }
    
    if ($request->get_param('status')) {
        $args['tax_query'][] = array(
            'taxonomy' => 'mt_status',
            'field' => 'slug',
            'terms' => $request->get_param('status')
        );
    }
    
    $candidates = get_posts($args);
    $data = array();
    
    foreach ($candidates as $candidate) {
        $data[] = array(
            'id' => $candidate->ID,
            'title' => $candidate->post_title,
            'excerpt' => get_the_excerpt($candidate),
            'company' => get_post_meta($candidate->ID, '_mt_company', true),
            'position' => get_post_meta($candidate->ID, '_mt_position', true),
            'location' => get_post_meta($candidate->ID, '_mt_location', true),
            'featured_image' => get_the_post_thumbnail_url($candidate->ID, 'medium'),
            'permalink' => get_permalink($candidate->ID),
            'public_votes' => mt_get_candidate_vote_count($candidate->ID, 'public'),
            'jury_score' => mt_get_candidate_vote_count($candidate->ID, 'jury'),
            'categories' => wp_get_post_terms($candidate->ID, 'mt_category', array('fields' => 'names')),
            'status' => wp_get_post_terms($candidate->ID, 'mt_status', array('fields' => 'names'))
        );
    }
    
    return rest_ensure_response($data);
}

function mt_rest_get_results($request) {
    global $wpdb;
    
    $type = $request->get_param('type') ?: 'public';
    $limit = $request->get_param('limit') ?: 10;
    
    if ($type === 'public') {
        $table = $wpdb->prefix . 'mt_public_votes';
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, p.post_title, COUNT(v.id) as vote_count
            FROM {$wpdb->posts} p
            LEFT JOIN $table v ON p.ID = v.candidate_id
            WHERE p.post_type = 'mt_candidate' AND p.post_status = 'publish'
            GROUP BY p.ID
            ORDER BY vote_count DESC
            LIMIT %d
        ", $limit));
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
        ", $limit));
    }
    
    $data = array();
    $ranking = 1;
    
    foreach ($results as $result) {
        $data[] = array(
            'ranking' => $ranking,
            'candidate_id' => $result->ID,
            'candidate_name' => $result->post_title,
            'score' => $type === 'public' ? intval($result->vote_count) : number_format($result->avg_score, 1),
            'evaluation_count' => $type === 'jury' ? intval($result->evaluation_count) : null
        );
        $ranking++;
    }
    
    return rest_ensure_response($data);
}

function mt_rest_submit_vote($request) {
    $candidate_id = $request->get_param('candidate_id');
    $voter_email = $request->get_param('voter_email');
    
    if (!$candidate_id || !is_email($voter_email)) {
        return new WP_Error('invalid_data', __('Invalid data provided.', 'mobility-trailblazers'), array('status' => 400));
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
        return new WP_Error('already_voted', __('You have already voted for this candidate.', 'mobility-trailblazers'), array('status' => 409));
    }
    
    $result = $wpdb->insert(
        $table_public_votes,
        array(
            'candidate_id' => $candidate_id,
            'voter_email' => $voter_email,
            'voter_ip' => $_SERVER['REMOTE_ADDR'],
            'vote_date' => current_time('mysql')
        )
    );
    
    if ($result !== false) {
        return rest_ensure_response(array('message' => __('Vote submitted successfully!', 'mobility-trailblazers')));
    } else {
        return new WP_Error('vote_failed', __('Failed to submit vote.', 'mobility-trailblazers'), array('status' => 500));
    }
}

/**
 * Email notifications
 */
function mt_send_jury_notification($jury_member_email, $subject, $message) {
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $email_template = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .header { background-color: #2c5282; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background-color: #f7fafc; padding: 15px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Mobility Trailblazers</h1>
        </div>
        <div class="content">
            ' . $message . '
        </div>
        <div class="footer">
            <p>© ' . date('Y') . ' Mobility Trailblazers | Institut für Mobilität, Universität St. Gallen</p>
        </div>
    </body>
    </html>';
    
    return wp_mail($jury_member_email, $subject, $email_template, $headers);
}

/**
 * Scheduled tasks
 */
function mt_schedule_events() {
    if (!wp_next_scheduled('mt_daily_report')) {
        wp_schedule_event(time(), 'daily', 'mt_daily_report');
    }
}
add_action('wp', 'mt_schedule_events');

function mt_daily_report() {
    global $wpdb;
    
    $table_votes = $wpdb->prefix . 'mt_public_votes';
    $today_votes = $wpdb->get_var("SELECT COUNT(*) FROM $table_votes WHERE DATE(vote_date) = CURDATE()");
    
    $admin_email = get_option('admin_email');
    $subject = __('Daily Mobility Trailblazers Report', 'mobility-trailblazers');
    $message = sprintf(__('Today\'s public votes: %d', 'mobility-trailblazers'), $today_votes);
    
    wp_mail($admin_email, $subject, $message);
}
add_action('mt_daily_report', 'mt_daily_report');

/**
 * Widget for displaying voting stats
 */
class MT_Voting_Stats_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'mt_voting_stats',
            __('MT Voting Statistics', 'mobility-trailblazers'),
            array('description' => __('Display voting statistics for Mobility Trailblazers.', 'mobility-trailblazers'))
        );
    }
    
    public function widget($args, $instance) {
        global $wpdb;
        
        $title = apply_filters('widget_title', $instance['title']);
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . $title . $args['after_title'];
        }
        
        $table_public_votes = $wpdb->prefix . 'mt_public_votes';
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        $public_votes = $wpdb->get_var("SELECT COUNT(*) FROM $table_public_votes");
        $jury_evaluations = $wpdb->get_var("SELECT COUNT(*) FROM $table_scores");
        $candidates_count = wp_count_posts('mt_candidate')->publish;
        
        echo '<div class="mt-voting-stats">';
        echo '<ul>';
        echo '<li><strong>' . __('Candidates:', 'mobility-trailblazers') . '</strong> ' . $candidates_count . '</li>';
        echo '<li><strong>' . __('Public Votes:', 'mobility-trailblazers') . '</strong> ' . $public_votes . '</li>';
        echo '<li><strong>' . __('Jury Evaluations:', 'mobility-trailblazers') . '</strong> ' . $jury_evaluations . '</li>';
        echo '</ul>';
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Voting Statistics', 'mobility-trailblazers');
        
        echo '<p>';
        echo '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'mobility-trailblazers') . '</label>';
        echo '<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr($title) . '">';
        echo '</p>';
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
}

function mt_register_widgets() {
    register_widget('MT_Voting_Stats_Widget');
}
add_action('widgets_init', 'mt_register_widgets');

?>