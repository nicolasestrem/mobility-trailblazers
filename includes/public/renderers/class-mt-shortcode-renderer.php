<?php
/**
 * Shared Shortcode Renderer
 *
 * @package MobilityTrailblazers
 * @since 2.5.22
 */

namespace MobilityTrailblazers\Public\Renderers;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Shortcode_Renderer
 *
 * Shared renderer for shortcodes and Elementor widgets
 */
class MT_Shortcode_Renderer {
    
    /**
     * Render jury dashboard
     *
     * @param array $args Arguments
     * @return string
     */
    public function render_jury_dashboard($args = []) {
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
        $this->enqueue_dashboard_assets();
        
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
     * Render candidates grid
     *
     * @param array $args Arguments
     * @return string
     */
    public function render_candidates_grid($args = []) {
        $defaults = [
            'category' => '',
            'columns' => 3,
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'show_bio' => 'yes',
            'show_category' => 'yes'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Query candidates
        $query_args = [
            'post_type' => 'mt_candidate',
            'posts_per_page' => intval($args['limit']),
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'post_status' => 'publish'
        ];
        
        // Filter by category if specified
        if (!empty($args['category'])) {
            $query_args['tax_query'] = [
                [
                    'taxonomy' => 'mt_award_category',
                    'field' => 'slug',
                    'terms' => $args['category']
                ]
            ];
        }
        
        $candidates = new \WP_Query($query_args);
        
        if (!$candidates->have_posts()) {
            return '<div class="mt-notice">' . esc_html__('No candidates found.', 'mobility-trailblazers') . '</div>';
        }
        
        // Enqueue grid assets
        $this->enqueue_grid_assets();
        
        // Start output buffering
        ob_start();
        
        // Output custom CSS
        echo '<style type="text/css">' . $this->generate_candidates_grid_css() . '</style>';
        
        // Pass attributes to template
        $atts = $args; // For backward compatibility with template
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/frontend/candidates-grid.php';
        
        // Reset post data
        wp_reset_postdata();
        
        return ob_get_clean();
    }
    
    /**
     * Render evaluation statistics
     *
     * @param array $args Arguments
     * @return string
     */
    public function render_evaluation_stats($args = []) {
        // Check permissions
        if (!current_user_can('mt_view_all_evaluations')) {
            return '';
        }
        
        $defaults = [
            'type' => 'summary', // summary, by-category, by-jury
            'show_chart' => 'yes'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Get statistics
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $stats = $evaluation_repo->get_statistics();
        
        // Enqueue stats assets
        $this->enqueue_stats_assets();
        
        // Start output buffering
        ob_start();
        
        // Output custom CSS for stats
        echo '<style type="text/css">' . $this->generate_stats_custom_css() . '</style>';
        
        // Pass attributes to template
        $atts = $args; // For backward compatibility with template
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/frontend/evaluation-stats.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render winners display
     *
     * @param array $args Arguments
     * @return string
     */
    public function render_winners_display($args = []) {
        $defaults = [
            'category' => '',
            'year' => date('Y'),
            'limit' => 3,
            'show_scores' => 'no'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Get winners (top scored candidates)
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $winners = $evaluation_repo->get_top_candidates($args['limit'], $args['category']);
        
        if (empty($winners)) {
            return '<div class="mt-notice">' . esc_html__('Winners have not been announced yet.', 'mobility-trailblazers') . '</div>';
        }
        
        // Enqueue winners assets
        $this->enqueue_winners_assets();
        
        // Start output buffering
        ob_start();
        
        // Pass attributes to template
        $atts = $args; // For backward compatibility with template
        
        // Include template
        include MT_PLUGIN_DIR . 'templates/frontend/winners-display.php';
        
        return ob_get_clean();
    }
    
    /**
     * Enqueue dashboard assets
     */
    private function enqueue_dashboard_assets() {
        // Check if v4 CSS framework is active
        if (apply_filters('mt_enable_css_v4', true)) {
            // v4 CSS is already loaded by MT_Public_Assets, skip legacy CSS
            // Only enqueue JavaScript and localization
        } else {
            // Enqueue v3 CSS files (legacy)
            $base = MT_PLUGIN_URL . 'assets/css/v3/';
            wp_enqueue_style('mt-v3-tokens', $base . 'mt-tokens.css', [], MT_VERSION);
            wp_enqueue_style('mt-v3-reset', $base . 'mt-reset.css', ['mt-v3-tokens'], MT_VERSION);
            wp_enqueue_style('mt-v3-grid', $base . 'mt-widget-candidates-grid.css', ['mt-v3-reset'], MT_VERSION);
            wp_enqueue_style('mt-v3-jury', $base . 'mt-widget-jury-dashboard.css', ['mt-v3-grid'], MT_VERSION);
            wp_enqueue_style('mt-v3-compat', $base . 'mt-compat.css', ['mt-v3-jury'], MT_VERSION);
            wp_enqueue_style('mt-v3-visual-tune', $base . 'mt-visual-tune.css', ['mt-v3-compat'], MT_VERSION);
            wp_enqueue_style('mt-v3-evaluation-cards', $base . 'mt-jury-evaluation-cards.css', ['mt-v3-visual-tune'], MT_VERSION);
            
            // Enqueue new candidate cards v3 CSS
            wp_enqueue_style('mt-candidate-cards-v3', MT_PLUGIN_URL . 'assets/css/mt-candidate-cards-v3.css', ['mt-v3-evaluation-cards'], MT_VERSION);
        }
        
        wp_enqueue_style('dashicons');
        // Add locale-based cache busting to ensure fresh translations
        $script_version = MT_VERSION . '-' . get_locale();
        wp_enqueue_script('mt-frontend', MT_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], $script_version, true);
        
        // Note: Not enqueueing mt-jury-filters.js as we're using inline JavaScript
        
        // Localize script
        wp_localize_script('mt-frontend', 'mt_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_ajax_nonce')
        ]);
        
        // Also localize with mt_frontend for evaluation form strings
        wp_localize_script('mt-frontend', 'mt_frontend', [
            'i18n' => [
                'evaluation_submitted' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                'error_try_again' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'network_error' => __('Network error. Please check your connection and try again.', 'mobility-trailblazers'),
                'evaluation_criteria' => __('Evaluation Criteria', 'mobility-trailblazers'),
                'mut_pioniergeist' => __('Mut & Pioniergeist', 'mobility-trailblazers'),
                'mut_description' => __('Mut, Konventionen herauszufordern und neue Wege in der Mobilität zu beschreiten', 'mobility-trailblazers'),
                'innovationsgrad' => __('Innovationsgrad', 'mobility-trailblazers'),
                'innovation_description' => __('Grad an Innovation und Kreativität bei der Lösung von Mobilitätsherausforderungen', 'mobility-trailblazers'),
                'umsetzungskraft' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
                'umsetzung_description' => __('Fähigkeit zur Umsetzung und realer Einfluss der Initiativen', 'mobility-trailblazers'),
                'relevanz' => __('Relevanz für die Mobilitätswende', 'mobility-trailblazers'),
                'relevanz_description' => __('Bedeutung und Beitrag zur Transformation der Mobilität', 'mobility-trailblazers'),
                'vorbildfunktion' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'),
                'vorbild_description' => __('Rolle als Vorbild und öffentliche Wahrnehmbarkeit im Mobilitätssektor', 'mobility-trailblazers'),
                // Additional UI strings for JavaScript
                'submitting' => __('Submitting...', 'mobility-trailblazers'),
                'submit_evaluation' => __('Submit Evaluation', 'mobility-trailblazers'),
                'evaluation_submitted_full' => __('Thank you for submitting your evaluation!', 'mobility-trailblazers'),
                'evaluation_submitted_status' => __('Evaluation Submitted', 'mobility-trailblazers'),
                'back_to_dashboard' => __('Back to Dashboard', 'mobility-trailblazers'),
                'additional_comments' => __('Additional Comments (Optional)', 'mobility-trailblazers'),
                'characters' => __('characters', 'mobility-trailblazers'),
                'criteria_evaluated' => __('criteria evaluated', 'mobility-trailblazers'),
                'evaluation_submitted_editable' => __('This evaluation has been submitted. You can still edit and resubmit.', 'mobility-trailblazers')
            ]
        ]);
    }
    
    /**
     * Enqueue grid assets
     */
    private function enqueue_grid_assets() {
        // Check if v4 CSS framework is active
        if (apply_filters('mt_enable_css_v4', true)) {
            // v4 CSS is already loaded by MT_Public_Assets, skip legacy CSS
            return;
        }
        
        // Enqueue v3 CSS files (legacy)
        $base = MT_PLUGIN_URL . 'assets/css/v3/';
        wp_enqueue_style('mt-v3-tokens', $base . 'mt-tokens.css', [], MT_VERSION);
        wp_enqueue_style('mt-v3-reset', $base . 'mt-reset.css', ['mt-v3-tokens'], MT_VERSION);
        wp_enqueue_style('mt-v3-grid', $base . 'mt-widget-candidates-grid.css', ['mt-v3-reset'], MT_VERSION);
        wp_enqueue_style('mt-v3-jury', $base . 'mt-widget-jury-dashboard.css', ['mt-v3-grid'], MT_VERSION);
        wp_enqueue_style('mt-v3-compat', $base . 'mt-compat.css', ['mt-v3-jury'], MT_VERSION);
        wp_enqueue_style('mt-v3-visual-tune', $base . 'mt-visual-tune.css', ['mt-v3-compat'], MT_VERSION);
        wp_enqueue_style('mt-v3-evaluation-cards', $base . 'mt-jury-evaluation-cards.css', ['mt-v3-visual-tune'], MT_VERSION);
        
        // Enqueue new candidate cards v3 CSS
        wp_enqueue_style('mt-candidate-cards-v3', MT_PLUGIN_URL . 'assets/css/mt-candidate-cards-v3.css', ['mt-v3-evaluation-cards'], MT_VERSION);
    }
    
    /**
     * Enqueue stats assets
     */
    private function enqueue_stats_assets() {
        // Check if v4 CSS framework is active
        if (apply_filters('mt_enable_css_v4', true)) {
            // v4 CSS is already loaded by MT_Public_Assets, skip legacy CSS
            return;
        }
        
        // Enqueue v3 CSS files (legacy)
        $base = MT_PLUGIN_URL . 'assets/css/v3/';
        wp_enqueue_style('mt-v3-tokens', $base . 'mt-tokens.css', [], MT_VERSION);
        wp_enqueue_style('mt-v3-reset', $base . 'mt-reset.css', ['mt-v3-tokens'], MT_VERSION);
        wp_enqueue_style('mt-v3-grid', $base . 'mt-widget-candidates-grid.css', ['mt-v3-reset'], MT_VERSION);
        wp_enqueue_style('mt-v3-jury', $base . 'mt-widget-jury-dashboard.css', ['mt-v3-grid'], MT_VERSION);
        wp_enqueue_style('mt-v3-compat', $base . 'mt-compat.css', ['mt-v3-jury'], MT_VERSION);
        wp_enqueue_style('mt-v3-visual-tune', $base . 'mt-visual-tune.css', ['mt-v3-compat'], MT_VERSION);
        wp_enqueue_style('mt-v3-evaluation-cards', $base . 'mt-jury-evaluation-cards.css', ['mt-v3-visual-tune'], MT_VERSION);
        
        // Enqueue new candidate cards v3 CSS
        wp_enqueue_style('mt-candidate-cards-v3', MT_PLUGIN_URL . 'assets/css/mt-candidate-cards-v3.css', ['mt-v3-evaluation-cards'], MT_VERSION);
    }
    
    /**
     * Enqueue winners assets
     */
    private function enqueue_winners_assets() {
        // Check if v4 CSS framework is active
        if (apply_filters('mt_enable_css_v4', true)) {
            // v4 CSS is already loaded by MT_Public_Assets, skip legacy CSS
            return;
        }
        
        // Enqueue v3 CSS files (legacy)
        $base = MT_PLUGIN_URL . 'assets/css/v3/';
        wp_enqueue_style('mt-v3-tokens', $base . 'mt-tokens.css', [], MT_VERSION);
        wp_enqueue_style('mt-v3-reset', $base . 'mt-reset.css', ['mt-v3-tokens'], MT_VERSION);
        wp_enqueue_style('mt-v3-grid', $base . 'mt-widget-candidates-grid.css', ['mt-v3-reset'], MT_VERSION);
        wp_enqueue_style('mt-v3-jury', $base . 'mt-widget-jury-dashboard.css', ['mt-v3-grid'], MT_VERSION);
        wp_enqueue_style('mt-v3-compat', $base . 'mt-compat.css', ['mt-v3-jury'], MT_VERSION);
        wp_enqueue_style('mt-v3-visual-tune', $base . 'mt-visual-tune.css', ['mt-v3-compat'], MT_VERSION);
        wp_enqueue_style('mt-v3-evaluation-cards', $base . 'mt-jury-evaluation-cards.css', ['mt-v3-visual-tune'], MT_VERSION);
        
        // Enqueue new candidate cards v3 CSS
        wp_enqueue_style('mt-candidate-cards-v3', MT_PLUGIN_URL . 'assets/css/mt-candidate-cards-v3.css', ['mt-v3-evaluation-cards'], MT_VERSION);
    }
    
    /**
     * Get jury member by user ID
     *
     * @param int $user_id User ID
     * @return \WP_Post|null
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
        
        // DEBUG: Log what settings we're getting
        error_log('MT DEBUG: Dashboard settings: ' . print_r($settings, true));
        
        $css = "
        .mt-dashboard-header.mt-header-gradient {
            background: linear-gradient(135deg, {$primary_color} 0%, {$secondary_color} 100%);
        }
        
        .mt-dashboard-header.mt-header-image,
        .mt-rankings-header {
            background-image: url('https://mobilitytrailblazers.de/vote/wp-content/uploads/2025/08/Background.webp') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            position: relative;
        }
        
        .mt-dashboard-header.mt-header-image::before,
        .mt-rankings-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            z-index: 1;
        }
        
        .mt-dashboard-header.mt-header-image > *,
        .mt-rankings-header > * {
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
