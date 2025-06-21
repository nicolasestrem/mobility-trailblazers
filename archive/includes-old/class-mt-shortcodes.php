<?php
/**
 * Shortcodes handler class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Shortcodes
 * Handles all plugin shortcodes
 */
class MT_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register shortcodes
        add_shortcode('mt_candidates', array($this, 'candidates_grid'));
        add_shortcode('mt_jury_dashboard', array($this, 'jury_dashboard'));
        add_shortcode('mt_voting_form', array($this, 'voting_form'));
        add_shortcode('mt_registration_form', array($this, 'registration_form'));
        add_shortcode('mt_evaluation_stats', array($this, 'evaluation_stats'));
        add_shortcode('mt_winners', array($this, 'winners_display'));
        add_shortcode('mt_jury', array($this, 'jury_members_grid'));
        add_shortcode('mt_candidate_profile', array($this, 'candidate_profile'));
    }
    
    /**
     * Candidates grid shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function candidates_grid($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'status' => 'approved',
            'limit' => 12,
            'columns' => 3,
            'orderby' => 'title',
            'order' => 'ASC',
            'show_filters' => 'yes',
            'show_pagination' => 'yes',
        ), $atts, 'mt_candidates');
        
        // Start output buffering
        ob_start();
        
        // Get current page
        $paged = get_query_var('paged') ? get_query_var('paged') : 1;
        
        // Build query args
        $args = array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => intval($atts['limit']),
            'paged' => $paged,
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish',
        );
        
        // Add category filter
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'mt_category',
                    'field' => 'slug',
                    'terms' => explode(',', $atts['category']),
                ),
            );
        }
        
        // Add status filter
        if (!empty($atts['status'])) {
            $args['meta_query'] = array(
                array(
                    'key' => '_mt_status',
                    'value' => $atts['status'],
                    'compare' => '=',
                ),
            );
        }
        
        // Apply filters
        $args = apply_filters('mt_candidates_query_args', $args, $atts);
        
        // Query candidates
        $query = new WP_Query($args);
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/candidates-grid.php';
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Jury dashboard shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function jury_dashboard($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="mt-notice mt-notice-error">' . 
                   __('Please log in to access the jury dashboard.', 'mobility-trailblazers') . 
                   ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                   __('Log in', 'mobility-trailblazers') . '</a></div>';
        }
        
        // Check if user is jury member
        if (!mt_is_jury_member()) {
            return '<div class="mt-notice mt-notice-error">' . 
                   __('You do not have permission to access the jury dashboard.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        // Ensure scripts are enqueued and localized for shortcode context
        if (class_exists('MT_Jury_System')) {
            $jury_system = new \MT_Jury_System();
            $jury_system->enqueue_jury_dashboard_scripts();
        }
        
        $atts = shortcode_atts(array(
            'show_stats' => 'yes',
            'show_progress' => 'yes',
            'show_filters' => 'yes',
        ), $atts, 'mt_jury_dashboard');
        
        // Get current user's jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        
        if (!$jury_member) {
            return '<div class="mt-notice mt-notice-error">' . 
                   __('Jury member profile not found.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Get assigned candidates
        $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/jury-dashboard.php';
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Voting form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function voting_form($atts) {
        // Check if public voting is enabled
        if (!mt_is_public_voting_enabled()) {
            return '<div class="mt-notice mt-notice-info">' . 
                   __('Public voting is currently closed.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'candidate_id' => 0,
            'show_results' => 'no',
        ), $atts, 'mt_voting_form');
        
        // Start output buffering
        ob_start();
        
        // Get candidate
        $candidate_id = intval($atts['candidate_id']);
        
        if ($candidate_id) {
            $candidate = get_post($candidate_id);
            
            if (!$candidate || $candidate->post_type !== 'mt_candidate') {
                return '<div class="mt-notice mt-notice-error">' . 
                       __('Invalid candidate.', 'mobility-trailblazers') . 
                       '</div>';
            }
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/voting-form.php';
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Registration form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function registration_form($atts) {
        // Check if registration is open
        if (!mt_is_registration_open()) {
            return '<div class="mt-notice mt-notice-info">' . 
                   __('Registration is currently closed.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        $atts = shortcode_atts(array(
            'show_categories' => 'yes',
            'redirect_url' => '',
        ), $atts, 'mt_registration_form');
        
        // Start output buffering
        ob_start();
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/registration-form.php';
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Evaluation statistics shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function evaluation_stats($atts) {
        $atts = shortcode_atts(array(
            'type' => 'overview', // overview, category, criteria
            'category' => '',
            'show_chart' => 'yes',
        ), $atts, 'mt_evaluation_stats');
        
        // Start output buffering
        ob_start();
        
        // Get statistics
        $stats = mt_get_evaluation_statistics();
        
        // Convert stdClass to array
        $stats = (array) $stats;
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/evaluation-stats.php';
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Winners display shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function winners_display($atts) {
        $atts = shortcode_atts(array(
            'year' => mt_get_current_award_year(),
            'limit' => 25,
            'show_category' => 'yes',
            'show_score' => 'no',
        ), $atts, 'mt_winners');
        
        // Start output buffering
        ob_start();
        
        // Query winners
        $args = array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => intval($atts['limit']),
            'meta_query' => array(
                array(
                    'key' => '_mt_status',
                    'value' => 'winner',
                    'compare' => '=',
                ),
            ),
            'tax_query' => array(
                array(
                    'taxonomy' => 'mt_award_year',
                    'field' => 'slug',
                    'terms' => $atts['year'],
                ),
            ),
            'orderby' => 'meta_value_num',
            'meta_key' => '_mt_final_score',
            'order' => 'DESC',
        );
        
        $query = new WP_Query($args);
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/winners-display.php';
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Jury members grid shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function jury_members_grid($atts) {
        $atts = shortcode_atts(array(
            'role' => '', // president, vice_president, member
            'limit' => -1,
            'columns' => 4,
            'show_bio' => 'yes',
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ), $atts, 'mt_jury');
        
        // Start output buffering
        ob_start();
        
        // Build query args
        $args = array(
            'post_type' => 'mt_jury_member',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish',
        );
        
        // Add role filter
        if (!empty($atts['role'])) {
            $args['meta_query'] = array(
                array(
                    'key' => '_mt_jury_role',
                    'value' => $atts['role'],
                    'compare' => '=',
                ),
            );
        }
        
        // Query jury members
        $query = new WP_Query($args);
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/jury-members-grid.php';
        
        // Return output
        return ob_get_clean();
    }
    
    /**
     * Candidate profile shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function candidate_profile($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'show_score' => 'no',
            'show_jury_comments' => 'no',
        ), $atts, 'mt_candidate_profile');
        
        // Get candidate ID
        $candidate_id = intval($atts['id']);
        
        if (!$candidate_id) {
            // Try to get from query var
            $candidate_id = get_query_var('candidate_id', 0);
        }
        
        if (!$candidate_id) {
            return '<div class="mt-notice mt-notice-error">' . 
                   __('No candidate specified.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        // Get candidate
        $candidate = get_post($candidate_id);
        
        if (!$candidate || $candidate->post_type !== 'mt_candidate') {
            return '<div class="mt-notice mt-notice-error">' . 
                   __('Candidate not found.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/shortcodes/candidate-profile.php';
        
        // Return output
        return ob_get_clean();
    }
} 