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
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_ajax_nonce')
        ]);
        
        // Start output buffering
        ob_start();
        
        // Output custom CSS
        echo '<style type="text/css">' . $this->generate_dashboard_custom_css() . '</style>';
        
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
        
        // Add candidate presentation styles
        $presentation = get_option('mt_candidate_presentation', []);

        // Profile layout styles
        if (($presentation['profile_layout'] ?? '') === 'stacked') {
            $css .= "
            .mt-layout-stacked .mt-candidate-profile {
                grid-template-columns: 1fr;
                text-align: center;
            }
            .mt-layout-stacked .mt-candidate-photo {
                margin: 0 auto;
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

        // Form styles
        if (($presentation['form_style'] ?? '') === 'wizard') {
            $css .= "
            .mt-form-wizard .mt-criteria-grid {
                display: none;
            }
            .mt-form-wizard .mt-criterion-card.active {
                display: block;
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
} 