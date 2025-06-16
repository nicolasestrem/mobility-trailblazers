<?php
/**
 * Elementor Jury Dashboard Widget
 * File: includes/integrations/elementor/widgets/class-jury-dashboard-widget.php
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

namespace MobilityTrailblazers\Integrations\Elementor\Widgets;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Jury Dashboard Widget
 */
class JuryDashboardWidget extends \Elementor\Widget_Base {
    
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
     * Get widget keywords
     */
    public function get_keywords() {
        return ['jury', 'dashboard', 'mobility', 'trailblazers', 'evaluation'];
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
                'label_on' => __('Show', 'mobility-trailblazers'),
                'label_off' => __('Hide', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_assignments',
            [
                'label' => __('Show Assignments', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'mobility-trailblazers'),
                'label_off' => __('Hide', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_progress',
            [
                'label' => __('Show Progress Bar', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'mobility-trailblazers'),
                'label_off' => __('Hide', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => [
                    'show_stats' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => __('Assignment Columns', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '2',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
                'condition' => [
                    'show_assignments' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'editor_notice',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => '<div style="background: #f0f0f0; padding: 10px; border-radius: 5px;">
                    <strong>' . __('Note:', 'mobility-trailblazers') . '</strong> ' . 
                    __('The dashboard requires user authentication. In editor mode, a preview placeholder is shown.', 'mobility-trailblazers') . '
                </div>',
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
            'stats_background',
            [
                'label' => __('Stats Background', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'stats_text_color',
            [
                'label' => __('Stats Text Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-number' => 'color: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'progress_color',
            [
                'label' => __('Progress Bar Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-progress-fill' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'show_progress' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'card_padding',
            [
                'label' => __('Card Padding', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-card, {{WRAPPER}} .mt-candidate-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'button_style_heading',
            [
                'label' => __('Buttons', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .button',
            ]
        );
        
        $this->add_control(
            'button_background',
            [
                'label' => __('Button Background', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .button-primary' => 'background-color: {{VALUE}};',
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
        
        $settings = $this->get_settings_for_display();
        
        // Check if in editor
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $this->render_editor_preview();
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            $this->render_login_required();
            return;
        }
        
        // Build shortcode attributes
        $shortcode_atts = [];
        
        if ($settings['show_stats'] !== 'yes') {
            $shortcode_atts[] = 'show_stats="false"';
        }
        
        if ($settings['show_assignments'] !== 'yes') {
            $shortcode_atts[] = 'show_assignments="false"';
        }
        
        if ($settings['show_progress'] !== 'yes') {
            $shortcode_atts[] = 'show_progress="false"';
        }
        
        $shortcode = '[mt_jury_dashboard' . (!empty($shortcode_atts) ? ' ' . implode(' ', $shortcode_atts) : '') . ']';
        
        echo '<div class="mt-elementor-widget-wrapper mt-jury-dashboard-widget">';
        
        // Add custom columns CSS if needed
        if ($settings['show_assignments'] === 'yes' && $settings['columns'] !== '2') {
            echo '<style>';
            echo '.mt-jury-dashboard-widget .mt-candidate-list {';
            echo 'display: grid;';
            echo 'grid-template-columns: repeat(' . intval($settings['columns']) . ', 1fr);';
            echo 'gap: 20px;';
            echo '}';
            echo '</style>';
        }
        
        echo do_shortcode($shortcode);
        echo '</div>';
    }
    
    /**
     * Render editor preview
     */
    private function render_editor_preview() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="mt-elementor-preview">
            <i class="eicon-dashboard"></i>
            <h3><?php _e('Jury Dashboard', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('The jury dashboard will be displayed here.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-preview-settings">
                <p><strong><?php _e('Current Settings:', 'mobility-trailblazers'); ?></strong></p>
                <ul>
                    <li><?php _e('Statistics:', 'mobility-trailblazers'); ?> <?php echo $settings['show_stats'] === 'yes' ? '✓' : '✗'; ?></li>
                    <li><?php _e('Assignments:', 'mobility-trailblazers'); ?> <?php echo $settings['show_assignments'] === 'yes' ? '✓' : '✗'; ?></li>
                    <?php if ($settings['show_stats'] === 'yes'): ?>
                    <li><?php _e('Progress Bar:', 'mobility-trailblazers'); ?> <?php echo $settings['show_progress'] === 'yes' ? '✓' : '✗'; ?></li>
                    <?php endif; ?>
                    <?php if ($settings['show_assignments'] === 'yes'): ?>
                    <li><?php _e('Columns:', 'mobility-trailblazers'); ?> <?php echo $settings['columns']; ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <p class="mt-preview-note">
                <?php _e('Requires user authentication to display on frontend.', 'mobility-trailblazers'); ?>
            </p>
        </div>
        
        <style>
        .mt-preview-settings {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        .mt-preview-settings ul {
            margin: 10px 0 0 20px;
            list-style: disc;
        }
        .mt-preview-settings li {
            margin: 5px 0;
        }
        </style>
        <?php
    }
    
    /**
     * Render login required message
     */
    private function render_login_required() {
        ?>
        <div class="mt-elementor-login-required">
            <div class="mt-login-icon">
                <i class="eicon-lock"></i>
            </div>
            <h4><?php _e('Login Required', 'mobility-trailblazers'); ?></h4>
            <p><?php _e('Please log in to view the jury dashboard.', 'mobility-trailblazers'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="mt-login-button button">
                <?php _e('Log In', 'mobility-trailblazers'); ?>
            </a>
        </div>
        
        <style>
        .mt-elementor-login-required {
            text-align: center;
            padding: 40px 20px;
            background: #f0f8ff;
            border: 1px solid #bee3f8;
            border-radius: 8px;
        }
        .mt-login-icon i {
            font-size: 48px;
            color: #3182ce;
            margin-bottom: 20px;
        }
        .mt-elementor-login-required h4 {
            color: #2c5282;
            margin-bottom: 10px;
        }
        .mt-login-button {
            display: inline-block;
            padding: 10px 20px;
            background: #2c5282;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            transition: background 0.3s ease;
        }
        .mt-login-button:hover {
            background: #2a4365;
            color: white;
        }
        </style>
        <?php
    }
    
    /**
     * Render widget in the editor
     */
    protected function content_template() {
        ?>
        <# if ( settings.show_stats === 'yes' || settings.show_assignments === 'yes' ) { #>
        <div class="mt-elementor-preview">
            <i class="eicon-dashboard"></i>
            <h3><?php _e('Jury Dashboard', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Configure your dashboard settings', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-preview-settings">
                <# if ( settings.show_stats === 'yes' ) { #>
                <div class="mt-preview-item">
                    <i class="eicon-counter"></i> <?php _e('Statistics Enabled', 'mobility-trailblazers'); ?>
                </div>
                <# } #>
                
                <# if ( settings.show_assignments === 'yes' ) { #>
                <div class="mt-preview-item">
                    <i class="eicon-posts-grid"></i> <?php _e('Assignments Enabled', 'mobility-trailblazers'); ?> ({{{ settings.columns }}} <?php _e('columns', 'mobility-trailblazers'); ?>)
                </div>
                <# } #>
                
                <# if ( settings.show_stats === 'yes' && settings.show_progress === 'yes' ) { #>
                <div class="mt-preview-item">
                    <i class="eicon-skill-bar"></i> <?php _e('Progress Bar Enabled', 'mobility-trailblazers'); ?>
                </div>
                <# } #>
            </div>
        </div>
        <# } else { #>
        <div class="mt-elementor-preview">
            <i class="eicon-dashboard"></i>
            <h3><?php _e('Jury Dashboard', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Please enable at least one dashboard component.', 'mobility-trailblazers'); ?></p>
        </div>
        <# } #>
        
        <style>
        .mt-preview-settings {
            margin-top: 20px;
        }
        .mt-preview-item {
            background: #f5f5f5;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-size: 14px;
        }
        .mt-preview-item i {
            margin-right: 8px;
            color: #666;
        }
        </style>
        <?php
    }
}