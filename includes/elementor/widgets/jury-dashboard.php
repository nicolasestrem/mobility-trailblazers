<?php
/**
 * Jury Dashboard Elementor Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Jury_Dashboard_Widget
 */
class MT_Jury_Dashboard_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     *
     * @return string Widget name
     */
    public function get_name() {
        return 'mt-jury-dashboard';
    }
    
    /**
     * Get widget title
     *
     * @return string Widget title
     */
    public function get_title() {
        return __('Jury Dashboard', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     *
     * @return string Widget icon
     */
    public function get_icon() {
        return 'eicon-dashboard';
    }
    
    /**
     * Get widget categories
     *
     * @return array Widget categories
     */
    public function get_categories() {
        return array('mobility-trailblazers');
    }
    
    /**
     * Get widget keywords
     *
     * @return array Widget keywords
     */
    public function get_keywords() {
        return array('jury', 'dashboard', 'evaluation', 'mobility', 'trailblazers');
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show_stats',
            array(
                'label' => __('Show Statistics', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_progress',
            array(
                'label' => __('Show Progress Bar', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_filters',
            array(
                'label' => __('Show Filters', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'login_message',
            array(
                'label' => __('Login Message', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Please log in to access the jury dashboard.', 'mobility-trailblazers'),
                'description' => __('Message shown to non-logged-in users', 'mobility-trailblazers'),
            )
        );
        
        $this->add_control(
            'permission_message',
            array(
                'label' => __('Permission Message', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('You do not have permission to access the jury dashboard.', 'mobility-trailblazers'),
                'description' => __('Message shown to users without jury permissions', 'mobility-trailblazers'),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Dashboard
        $this->start_controls_section(
            'dashboard_style_section',
            array(
                'label' => __('Dashboard', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name' => 'dashboard_background',
                'label' => __('Background', 'mobility-trailblazers'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .mt-jury-dashboard',
            )
        );
        
        $this->add_responsive_control(
            'dashboard_padding',
            array(
                'label' => __('Padding', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-jury-dashboard' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Statistics
        $this->start_controls_section(
            'stats_style_section',
            array(
                'label' => __('Statistics', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_stats' => 'yes',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name' => 'stat_box_background',
                'label' => __('Stat Box Background', 'mobility-trailblazers'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .mt-stat-box',
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'stat_box_border',
                'label' => __('Border', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-stat-box',
            )
        );
        
        $this->add_control(
            'stat_box_border_radius',
            array(
                'label' => __('Border Radius', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-stat-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'stat_number_typography',
                'label' => __('Number Typography', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-stat-number',
            )
        );
        
        $this->add_control(
            'stat_number_color',
            array(
                'label' => __('Number Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-stat-number' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'stat_label_typography',
                'label' => __('Label Typography', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-stat-label',
            )
        );
        
        $this->add_control(
            'stat_label_color',
            array(
                'label' => __('Label Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-stat-label' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Progress Bar
        $this->start_controls_section(
            'progress_style_section',
            array(
                'label' => __('Progress Bar', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'show_progress' => 'yes',
                ),
            )
        );
        
        $this->add_control(
            'progress_bar_height',
            array(
                'label' => __('Height', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 5,
                        'max' => 50,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 20,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .mt-progress-bar' => 'height: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'progress_bar_bg_color',
            array(
                'label' => __('Background Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-progress-bar' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'progress_fill_color',
            array(
                'label' => __('Fill Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-progress-fill' => 'background-color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_control(
            'progress_bar_border_radius',
            array(
                'label' => __('Border Radius', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-progress-bar' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .mt-progress-fill' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo '<div class="mt-notice mt-notice-error">' . 
                 esc_html($settings['login_message']) . 
                 ' <a href="' . wp_login_url(get_permalink()) . '">' . 
                 __('Log in', 'mobility-trailblazers') . '</a></div>';
            return;
        }
        
        // Check if user is jury member
        if (!mt_is_jury_member()) {
            echo '<div class="mt-notice mt-notice-error">' . 
                 esc_html($settings['permission_message']) . 
                 '</div>';
            return;
        }
        
        // Build shortcode attributes
        $atts = array(
            'show_stats' => $settings['show_stats'],
            'show_progress' => $settings['show_progress'],
            'show_filters' => $settings['show_filters'],
        );
        
        // Generate shortcode
        $shortcode = '[mt_jury_dashboard';
        foreach ($atts as $key => $value) {
            if (!empty($value)) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode .= ']';
        
        // Output shortcode
        echo do_shortcode($shortcode);
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <#
        var shortcode = '[mt_jury_dashboard';
        
        if (settings.show_stats) {
            shortcode += ' show_stats="' + settings.show_stats + '"';
        }
        if (settings.show_progress) {
            shortcode += ' show_progress="' + settings.show_progress + '"';
        }
        if (settings.show_filters) {
            shortcode += ' show_filters="' + settings.show_filters + '"';
        }
        
        shortcode += ']';
        #>
        <div class="mt-elementor-preview">
            <p><?php _e('Jury Dashboard will be displayed here for logged-in jury members.', 'mobility-trailblazers'); ?></p>
            <p><code>{{{ shortcode }}}</code></p>
        </div>
        <?php
    }
} 