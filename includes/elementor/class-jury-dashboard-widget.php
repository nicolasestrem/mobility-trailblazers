<?php
/**
 * Elementor Jury Dashboard Widget
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Jury Dashboard Widget
 */
class MT_Jury_Dashboard_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'mt_jury_dashboard';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('MT Jury Dashboard', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-dashboard';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['general', 'mobility-trailblazers'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Dashboard Settings', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'show_stats',
            [
                'label' => __('Show Statistics', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_assignments',
            [
                'label' => __('Show Assignments', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'editor_notice',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => __('<div style="background: #f0f0f0; padding: 10px; border-radius: 5px;">
                    <strong>Note:</strong> The dashboard requires user authentication. 
                    In editor mode, a preview placeholder is shown.
                </div>', 'mobility-trailblazers'),
            ]
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'dashboard_padding',
            [
                'label' => __('Padding', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .mt-jury-dashboard' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        // Safety check for Elementor
        if (!class_exists('\Elementor\Plugin')) {
            return;
        }

        // Safety check for required functions
        if (!function_exists('is_user_logged_in') || !function_exists('wp_login_url') || !function_exists('get_permalink')) {
            return;
        }
        
        $settings = $this->get_settings_for_display();
        
        // Check if in editor
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $this->render_editor_preview();
            return;
        }
        
        // Additional check for preview mode
        if (\Elementor\Plugin::$instance->preview->is_preview_mode()) {
            $this->render_preview_mode();
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo '<div class="mt-elementor-login-required">';
            echo '<p>' . esc_html__('Please log in to view the jury dashboard.', 'mobility-trailblazers') . '</p>';
            echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="button">' . esc_html__('Log In', 'mobility-trailblazers') . '</a>';
            echo '</div>';
            return;
        }
        
        // Build shortcode attributes
        $atts = [];
        if ($settings['show_stats'] !== 'yes') {
            $atts[] = 'show_stats="false"';
        }
        if ($settings['show_assignments'] !== 'yes') {
            $atts[] = 'show_assignments="false"';
        }
        
        $shortcode = '[mt_jury_dashboard' . (count($atts) ? ' ' . implode(' ', $atts) : '') . ']';
        
        echo '<div class="mt-elementor-widget-wrapper">';
        echo do_shortcode($shortcode);
        echo '</div>';
    }
    
    /**
     * Render preview mode
     */
    private function render_preview_mode() {
        ?>
        <div class="mt-elementor-preview" style="background: #f9f9f9; padding: 30px; text-align: center;">
            <i class="eicon-dashboard" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
            <h3><?php _e('Jury Dashboard (Preview)', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('The live dashboard will appear here when viewing the page.', 'mobility-trailblazers'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Render editor preview
     */
    private function render_editor_preview() {
        ?>
        <div class="mt-elementor-preview">
            <i class="eicon-dashboard" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
            <h3><?php _e('Jury Dashboard', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('The jury dashboard will be displayed here.', 'mobility-trailblazers'); ?></p>
            <p style="font-size: 12px; margin-top: 10px;">
                <?php _e('Requires user authentication to display.', 'mobility-trailblazers'); ?>
            </p>
        </div>
        <?php
    }
}