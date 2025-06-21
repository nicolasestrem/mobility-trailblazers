<?php
/**
 * Shortcodes Registration
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Shortcodes
 *
 * Registers and handles shortcodes
 */
class MT_Shortcodes {
    
    /**
     * Initialize shortcodes
     *
     * @return void
     */
    public function init() {
        add_shortcode('mt_jury_dashboard', [$this, 'render_jury_dashboard']);
        add_shortcode('mt_candidates_grid', [$this, 'render_candidates_grid']);
        add_shortcode('mt_evaluation_stats', [$this, 'render_evaluation_stats']);
        add_shortcode('mt_winners_display', [$this, 'render_winners_display']);
    }
    
    /**
     * Render jury dashboard shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_jury_dashboard($atts) {
        // Check if user is logged in and has permission
        if (!is_user_logged_in() || !current_user_can('mt_submit_evaluations')) {
            return '<div class="mt-notice mt-notice-error">' . 
                   __('You must be logged in as a jury member to access this dashboard.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        // Get current user
        $current_user_id = get_current_user_id();
        
        // Get jury member post
        $jury_member = $this->get_jury_member_by_user_id($current_user_id);
        if (!$jury_member) {
            return '<div class="mt-notice mt-notice-error">' . 
                   __('Your jury member profile could not be found.', 'mobility-trailblazers') . 
                   '</div>';
        }
        
        // Enqueue dashboard scripts and styles
        wp_enqueue_style('mt-jury-dashboard');
        wp_enqueue_script('mt-jury-dashboard');
        
        // Start output buffering
        ob_start();
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/frontend/jury-dashboard.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render candidates grid shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_candidates_grid($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'columns' => 3,
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'show_bio' => 'yes',
            'show_category' => 'yes'
        ], $atts, 'mt_candidates_grid');
        
        // Query candidates
        $args = [
            'post_type' => 'mt_candidate',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish'
        ];
        
        // Filter by category if specified
        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'mt_award_category',
                    'field' => 'slug',
                    'terms' => $atts['category']
                ]
            ];
        }
        
        $candidates = new \WP_Query($args);
        
        if (!$candidates->have_posts()) {
            return '<div class="mt-notice">' . __('No candidates found.', 'mobility-trailblazers') . '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/frontend/candidates-grid.php';
        
        // Reset post data
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Render evaluation statistics shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_evaluation_stats($atts) {
        // Check permissions
        if (!current_user_can('mt_view_all_evaluations')) {
            return '';
        }
        
        $atts = shortcode_atts([
            'type' => 'summary', // summary, by-category, by-jury
            'show_chart' => 'yes'
        ], $atts, 'mt_evaluation_stats');
        
        // Get statistics
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $stats = $evaluation_repo->get_statistics();
        
        // Start output buffering
        ob_start();
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/frontend/evaluation-stats.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render winners display shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_winners_display($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'year' => date('Y'),
            'limit' => 3,
            'show_scores' => 'no'
        ], $atts, 'mt_winners_display');
        
        // Get winners (top scored candidates)
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $winners = $evaluation_repo->get_top_candidates($atts['limit'], $atts['category']);
        
        if (empty($winners)) {
            return '<div class="mt-notice">' . __('Winners have not been announced yet.', 'mobility-trailblazers') . '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/frontend/winners-display.php';
        
        return ob_get_clean();
    }
    
    /**
     * Get jury member by user ID
     *
     * @param int $user_id User ID
     * @return WP_Post|null
     */
    private function get_jury_member_by_user_id($user_id) {
        $args = [
            'post_type' => 'mt_jury_member',
            'meta_key' => '_mt_user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ];
        
        $jury_members = get_posts($args);
        
        return !empty($jury_members) ? $jury_members[0] : null;
    }
} 