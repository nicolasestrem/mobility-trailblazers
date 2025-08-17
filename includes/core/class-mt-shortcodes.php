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
        wp_enqueue_style('mt-frontend', MT_PLUGIN_URL . 'assets/css/frontend.css', [], MT_VERSION);
        wp_enqueue_style('dashicons');
        wp_enqueue_script('mt-frontend', MT_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], MT_VERSION, true);
        
        // Localize script
        wp_localize_script('mt-frontend', 'mt_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_ajax_nonce')
        ]);
        
        // Start output buffering
        ob_start();
        
        // Output custom CSS
        echo '<style type="text/css">' . $this->generate_dashboard_custom_css() . '</style>';
        
        // Include template with validation
        $template_file = MT_PLUGIN_DIR . 'templates/frontend/jury-dashboard.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="mt-error">' . esc_html__('Jury dashboard template not found.', 'mobility-trailblazers') . '</div>';
        }
        
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
            return '<div class="mt-notice">' . esc_html__('No candidates found.', 'mobility-trailblazers') . '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Output custom CSS
        echo '<style type="text/css">' . $this->generate_candidates_grid_css() . '</style>';
        
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
        
        // Output custom CSS for stats
        echo '<style type="text/css">' . $this->generate_stats_custom_css() . '</style>';
        
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
            return '<div class="mt-notice">' . esc_html__('Winners have not been announced yet.', 'mobility-trailblazers') . '</div>';
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
    
    /**
     * Generate custom CSS for jury dashboard
     *
     * @return string
     */
    private function generate_dashboard_custom_css() {
        $settings = get_option('mt_dashboard_settings', []);
        $primary_color = $settings['primary_color'] ?? '#667eea';
        $secondary_color = $settings['secondary_color'] ?? '#764ba2';
        
        $css = "
        .mt-dashboard-header.mt-header-gradient {
            background: linear-gradient(135deg, {$primary_color} 0%, {$secondary_color} 100%);
        }
        
        .mt-dashboard-header.mt-header-image {
            background-image: url('" . esc_url($settings['header_image_url'] ?? '') . "');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        
        .mt-dashboard-header.mt-header-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        
        .mt-dashboard-header.mt-header-image > * {
            position: relative;
            z-index: 2;
        }
        
        .mt-stat-number,
        .mt-candidate-link:hover {
            color: {$primary_color};
        }
        
        .mt-btn-primary {
            background-color: {$primary_color};
        }
        
        .mt-progress-fill {
            background: linear-gradient(to right, {$primary_color}, {$secondary_color});
        }
        ";
        
        if ($settings['progress_bar_style'] === 'striped') {
            $css .= "
            .mt-progress-striped .mt-progress-fill {
                background-image: linear-gradient(
                    45deg,
                    rgba(255, 255, 255, .15) 25%,
                    transparent 25%,
                    transparent 50%,
                    rgba(255, 255, 255, .15) 50%,
                    rgba(255, 255, 255, .15) 75%,
                    transparent 75%,
                    transparent
                );
                background-size: 1rem 1rem;
                animation: progress-bar-stripes 1s linear infinite;
            }
            ";
        }

        // Add layout-specific styles for candidate cards
        $card_layout = $settings['card_layout'] ?? 'grid';

        // Grid layout styles
        if ($card_layout === 'grid') {
            $css .= "
            .mt-candidates-list.mt-candidates-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 25px;
            }
            
            .mt-candidates-grid.columns-2 {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .mt-candidates-grid.columns-3 {
                grid-template-columns: repeat(3, 1fr);
            }
            
            .mt-candidates-grid.columns-4 {
                grid-template-columns: repeat(4, 1fr);
            }
            
            @media (max-width: 768px) {
                .mt-candidates-grid.columns-2,
                .mt-candidates-grid.columns-3,
                .mt-candidates-grid.columns-4 {
                    grid-template-columns: 1fr;
                }
            }
            ";
        }

        // List layout styles
        if ($card_layout === 'list') {
            $css .= "
            .mt-candidates-list.mt-candidates-list {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            
            .mt-candidates-list .mt-candidate-card {
                display: flex;
                align-items: center;
                padding: 20px;
                gap: 20px;
            }
            
            .mt-candidates-list .mt-candidate-header {
                padding: 0;
                border: none;
                background: transparent;
            }
            
            .mt-candidates-list .mt-candidate-body {
                padding: 0;
                flex: 1;
            }
            ";
        }

        // Compact layout styles
        if ($card_layout === 'compact') {
            $css .= "
            .mt-candidates-list.mt-candidates-compact {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            
            .mt-candidates-compact .mt-candidate-card {
                display: flex;
                align-items: center;
                padding: 15px;
                gap: 15px;
                min-height: auto;
            }
            
            .mt-candidates-compact .mt-candidate-header {
                padding: 0;
                border: none;
                background: transparent;
                flex: 0 0 auto;
            }
            
            .mt-candidates-compact .mt-candidate-body {
                padding: 0;
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 15px;
            }
            
            .mt-candidates-compact .mt-candidate-name {
                font-size: 18px;
                margin: 0;
            }
            
            .mt-candidates-compact .mt-candidate-org {
                font-size: 13px;
            }
            
            .mt-candidates-compact .mt-candidate-category {
                margin: 0;
            }
            
            .mt-candidates-compact .mt-evaluation-status {
                margin: 0;
            }
            ";
        }

        // Add candidate presentation styles
        $presentation = get_option('mt_candidate_presentation', []);

        // Profile layout styles
        if (($presentation['profile_layout'] ?? '') === 'side-by-side') {
            $css .= "
            .mt-layout-side-by-side .mt-candidate-profile {
                display: grid;
                grid-template-columns: auto 1fr;
                gap: 30px;
                align-items: start;
            }
            .mt-layout-side-by-side .mt-candidate-details {
                text-align: left;
            }
            .mt-layout-side-by-side .mt-candidate-details h2 {
                text-align: left;
                margin: 0 0 15px 0;
            }
            ";
        } elseif (($presentation['profile_layout'] ?? '') === 'stacked') {
            $css .= "
            .mt-layout-stacked .mt-candidate-profile {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .mt-layout-stacked .mt-candidate-photo {
                margin: 0 auto;
            }
            .mt-layout-stacked .mt-candidate-details {
                text-align: center;
            }
            ";
        } elseif (($presentation['profile_layout'] ?? '') === 'card') {
            $css .= "
            .mt-layout-card {
                background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
                border: none;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            }
            .mt-layout-card .mt-candidate-profile {
                background: rgba(255,255,255,0.9);
                border-radius: 8px;
                padding: 20px;
                margin: 20px;
            }
            .mt-layout-card .mt-candidate-details {
                text-align: left;
            }
            ";
        } elseif (($presentation['profile_layout'] ?? '') === 'minimal') {
            $css .= "
            .mt-layout-minimal .mt-candidate-photo-wrap {
                display: none;
            }
            .mt-layout-minimal .mt-candidate-profile {
                grid-template-columns: 1fr;
            }
            .mt-layout-minimal .mt-candidate-details {
                text-align: left;
            }
            ";
        }

        // Photo styles
        if (($presentation['photo_style'] ?? '') === 'circle') {
            $css .= ".mt-photo-circle { border-radius: 50%; }";
        } elseif (($presentation['photo_style'] ?? '') === 'rounded') {
            $css .= ".mt-photo-rounded { border-radius: 12px; }";
        }

        // Photo sizes
        $photo_sizes = [
            'small' => '150px',
            'medium' => '200px',
            'large' => '300px'
        ];
        $size = $photo_sizes[$presentation['photo_size']] ?? '200px';
        $css .= "
        .mt-candidate-photo {
            width: {$size};
            height: {$size};
        }
        ";

        // Form style variations
        if (($presentation['form_style'] ?? '') === 'list') {
            $css .= "
            .mt-form-list .mt-criteria-grid {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            .mt-form-list .mt-criterion-card {
                display: flex;
                align-items: center;
                gap: 20px;
                padding: 20px;
            }
            .mt-form-list .mt-criterion-header {
                flex: 0 0 300px;
            }
            .mt-form-list .mt-scoring-control {
                flex: 1;
                display: flex;
                align-items: center;
                gap: 20px;
            }
            ";
        } elseif (($presentation['form_style'] ?? '') === 'compact') {
            $css .= "
            .mt-form-compact .mt-criterion-card {
                padding: 15px;
            }
            .mt-form-compact .mt-criterion-header {
                margin-bottom: 10px;
            }
            .mt-form-compact .mt-criterion-icon {
                font-size: 20px;
            }
            .mt-form-compact .mt-criterion-label {
                font-size: 16px;
            }
            .mt-form-compact .mt-criterion-description {
                display: none;
            }
            ";
        } elseif (($presentation['form_style'] ?? '') === 'wizard') {
            $css .= "
            .mt-form-wizard .mt-criteria-grid {
                position: relative;
            }
            .mt-form-wizard .mt-criterion-card {
                display: none;
                animation: fadeIn 0.3s ease-in;
            }
            .mt-form-wizard .mt-criterion-card.active {
                display: block;
            }
            .mt-form-wizard .mt-wizard-navigation {
                display: flex;
                justify-content: space-between;
                margin-top: 30px;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            ";
        }

        // Scoring display styles
        if (($presentation['scoring_style'] ?? '') === 'stars') {
            $css .= "
            .mt-scoring-control .mt-score-slider-wrapper {
                display: none;
            }
            .mt-scoring-control .mt-star-rating {
                display: flex;
                gap: 5px;
                font-size: 30px;
                color: #ddd;
                cursor: pointer;
            }
            .mt-scoring-control .mt-star-rating .dashicons {
                transition: color 0.2s;
            }
            .mt-scoring-control .mt-star-rating .dashicons.active,
            .mt-scoring-control .mt-star-rating .dashicons:hover {
                color: #f39c12;
            }
            ";
        } elseif (($presentation['scoring_style'] ?? '') === 'numeric') {
            $css .= "
            .mt-scoring-control .mt-score-slider-wrapper {
                display: none;
            }
            .mt-scoring-control .mt-numeric-input {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .mt-scoring-control .mt-numeric-input input {
                width: 80px;
                padding: 10px;
                font-size: 20px;
                text-align: center;
                border: 2px solid #ddd;
                border-radius: 5px;
            }
            ";
        } elseif (($presentation['scoring_style'] ?? '') === 'buttons') {
            $css .= "
            .mt-scoring-control .mt-score-slider-wrapper {
                display: none;
            }
            .mt-scoring-control .mt-button-group {
                display: flex;
                gap: 5px;
                flex-wrap: wrap;
            }
            .mt-scoring-control .mt-score-button {
                padding: 8px 15px;
                border: 2px solid #ddd;
                background: white;
                border-radius: 5px;
                cursor: pointer;
                transition: all 0.2s;
            }
            .mt-scoring-control .mt-score-button:hover {
                border-color: {$primary_color};
                color: {$primary_color};
            }
            .mt-scoring-control .mt-score-button.active {
                background: {$primary_color};
                border-color: {$primary_color};
                color: white;
            }
            ";
        }

        // Animation styles
        if (!empty($presentation['enable_animations'])) {
            $css .= "
            .mt-animated * {
                transition: all 0.3s ease;
            }
            .mt-animated .mt-candidate-card:hover {
                transform: translateY(-5px);
            }
            .mt-animated .mt-score-slider {
                transition: all 0.2s ease;
            }
            ";
        }

        // Hover effects
        if (!empty($presentation['enable_hover_effects'])) {
            $css .= "
            .mt-criterion-card:hover {
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                transform: translateY(-2px);
            }
            .mt-candidate-photo:hover {
                filter: brightness(1.05);
            }
            ";
        }

        return $css;
    }

    /**
     * Generate custom CSS for candidates grid
     *
     * @return string
     */
    private function generate_candidates_grid_css() {
        $settings = get_option('mt_dashboard_settings', []);
        $presentation = get_option('mt_candidate_presentation', []);
        $primary_color = $settings['primary_color'] ?? '#667eea';
        
        $css = "
        .mt-candidate-grid-item:hover {
            border-color: {$primary_color};
        }
        .mt-category-tag {
            background: {$primary_color};
            color: white;
        }
        ";
        
        // Apply photo styles to grid
        if (($presentation['photo_style'] ?? '') === 'circle') {
            $css .= ".mt-candidate-grid-item .mt-candidate-photo { border-radius: 50%; }";
        } elseif (($presentation['photo_style'] ?? '') === 'rounded') {
            $css .= ".mt-candidate-grid-item .mt-candidate-photo { border-radius: 8px; }";
        }
        
        return $css;
    }

    /**
     * Generate custom CSS for evaluation statistics
     *
     * @return string
     */
    private function generate_stats_custom_css() {
        $settings = get_option('mt_dashboard_settings', []);
        $primary_color = $settings['primary_color'] ?? '#667eea';
        $secondary_color = $settings['secondary_color'] ?? '#764ba2';
        
        $css = "
        .mt-stat-number {
            color: {$primary_color};
        }
        .mt-bar-fill {
            background: linear-gradient(to right, {$primary_color}, {$secondary_color});
        }
        .mt-progress-mini-fill {
            background: {$primary_color};
        }
        ";
        
        return $css;
    }
} 