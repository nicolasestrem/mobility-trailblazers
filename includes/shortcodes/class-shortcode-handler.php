<?php
/**
 * Shortcode Handler for Mobility Trailblazers
 * File: includes/shortcodes/class-shortcode-handler.php
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

namespace MobilityTrailblazers\Shortcodes;

use MobilityTrailblazers\Core\JuryMember;
use MobilityTrailblazers\Core\Candidate;
use MobilityTrailblazers\Core\Evaluation;
use MobilityTrailblazers\Core\Statistics;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ShortcodeHandler
 * 
 * Handles all plugin shortcodes
 */
class ShortcodeHandler {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Core class instances
     */
    private $evaluation;
    private $jury_member;
    private $candidate;
    private $statistics;
    
    /**
     * Get instance
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
        // Initialize core classes
        $this->evaluation = new Evaluation();
        $this->jury_member = new JuryMember();
        $this->candidate = new Candidate();
        $this->statistics = new Statistics();
        
        $this->register_shortcodes();
    }
    
    /**
     * Register all shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('mt_jury_dashboard', [$this, 'jury_dashboard_shortcode']);
        add_shortcode('mt_candidate_grid', [$this, 'candidate_grid_shortcode']);
        add_shortcode('mt_voting_form', [$this, 'voting_form_shortcode']);
        add_shortcode('mt_jury_members', [$this, 'jury_members_shortcode']);
        add_shortcode('mt_voting_results', [$this, 'voting_results_shortcode']);
    }
    
    /**
     * Jury Dashboard Shortcode
     */
    public function jury_dashboard_shortcode($atts) {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to access the jury dashboard.', 'mobility-trailblazers') . '</p>';
        }

        // Get current user
        $current_user = wp_get_current_user();
        
        // Initialize core classes if not already initialized
        if (!isset($this->jury_member)) {
            $this->jury_member = new \MobilityTrailblazers\Core\JuryMember();
        }
        if (!isset($this->statistics)) {
            $this->statistics = new \MobilityTrailblazers\Core\Statistics();
        }
        if (!isset($this->candidate)) {
            $this->candidate = new \MobilityTrailblazers\Core\Candidate();
        }

        // Check if user is a jury member
        if (!$this->jury_member->is_jury_member($current_user->ID)) {
            return '<p>' . __('You do not have permission to access the jury dashboard.', 'mobility-trailblazers') . '</p>';
        }

        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'show_stats' => 'yes',
            'show_assignments' => 'yes',
            'show_evaluations' => 'yes',
            'show_public_voting' => 'yes',
            'show_round_selector' => 'yes',
            'show_category_filter' => 'yes',
            'show_search' => 'yes',
            'show_sort' => 'yes',
            'show_pagination' => 'yes',
            'items_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'category' => '',
            'round' => '',
            'status' => '',
            'search' => '',
            'sort' => '',
            'page' => 1
        ), $atts, 'jury_dashboard');

        // Convert yes/no strings to booleans
        $atts['show_stats'] = filter_var($atts['show_stats'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_assignments'] = filter_var($atts['show_assignments'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_evaluations'] = filter_var($atts['show_evaluations'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_public_voting'] = filter_var($atts['show_public_voting'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_round_selector'] = filter_var($atts['show_round_selector'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_category_filter'] = filter_var($atts['show_category_filter'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_search'] = filter_var($atts['show_search'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_sort'] = filter_var($atts['show_sort'], FILTER_VALIDATE_BOOLEAN);
        $atts['show_pagination'] = filter_var($atts['show_pagination'], FILTER_VALIDATE_BOOLEAN);

        // Start output buffering
        ob_start();

        // Include the template with required variables
        include MT_PLUGIN_PATH . 'templates/jury-dashboard.php';

        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * Candidate Grid Shortcode
     */
    public function candidate_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'limit' => -1
        ), $atts);
        
        $candidates = $atts['category'] ? 
            $this->candidate->get_candidates_by_category($atts['category']) :
            $this->candidate->get_all_candidates(array('posts_per_page' => $atts['limit']));
        
        ob_start();
        include MT_PLUGIN_PATH . 'templates/shortcodes/candidate-grid.php';
        return ob_get_clean();
    }
    
    /**
     * Voting Form Shortcode
     */
    public function voting_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to submit your vote.', 'mobility-trailblazers') . '</p>';
        }
        
        $user_id = get_current_user_id();
        if (!$this->jury_member->is_jury_member($user_id)) {
            return '<p>' . __('You do not have permission to vote.', 'mobility-trailblazers') . '</p>';
        }
        
        $atts = shortcode_atts(array(
            'candidate_id' => 0
        ), $atts);
        
        if (!$atts['candidate_id']) {
            return '<p>' . __('No candidate specified.', 'mobility-trailblazers') . '</p>';
        }
        
        $candidate = $this->candidate->get_candidate($atts['candidate_id']);
        if (!$candidate) {
            return '<p>' . __('Candidate not found.', 'mobility-trailblazers') . '</p>';
        }
        
        $jury_member_id = $this->jury_member->get_jury_member_id_for_user($user_id);
        $has_evaluated = $this->evaluation->has_evaluated($user_id, $atts['candidate_id']);
        $evaluation = $has_evaluated ? $this->evaluation->get_evaluation($user_id, $atts['candidate_id']) : null;
        
        ob_start();
        include MT_PLUGIN_PATH . 'templates/shortcodes/voting-form.php';
        return ob_get_clean();
    }
    
    /**
     * Jury Members Shortcode
     */
    public function jury_members_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => -1
        ), $atts);
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'post_status' => 'publish',
            'posts_per_page' => $atts['limit']
        ));
        
        ob_start();
        include MT_PLUGIN_PATH . 'templates/shortcodes/jury-members.php';
        return ob_get_clean();
    }
    
    /**
     * Voting Results Shortcode
     */
    public function voting_results_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'category' => ''
        ), $atts);
        
        $top_candidates = $this->evaluation->get_top_candidates_by_score($atts['limit'], $atts['category']);
        $public_results = $this->evaluation->get_public_voting_results($atts['limit'], $atts['category']);
        
        ob_start();
        include MT_PLUGIN_PATH . 'templates/shortcodes/voting-results.php';
        return ob_get_clean();
    }
}