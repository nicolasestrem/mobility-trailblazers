<?php
/**
 * Elementor Candidate Grid Widget
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Candidate Grid Widget
 */
class MT_Candidate_Grid_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'mt_candidate_grid';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('MT Candidate Grid', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-posts-grid';
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
                'label' => __('Grid Settings', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'category',
            [
                'label' => __('Category', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'all',
                'options' => [
                    'all' => __('All Categories', 'mobility-trailblazers'),
                    'established' => __('Established Companies', 'mobility-trailblazers'),
                    'startups' => __('Start-ups & New Makers', 'mobility-trailblazers'),
                    'infrastructure' => __('Infrastructure/Politics/Public', 'mobility-trailblazers'),
                ],
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ]
        );
        
        $this->add_control(
            'limit',
            [
                'label' => __('Number of Candidates', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 12,
                'min' => 1,
                'max' => 50,
            ]
        );
        
        $this->add_control(
            'show_evaluation_status',
            [
                'label' => __('Show Evaluation Status', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => __('Only visible to jury members', 'mobility-trailblazers'),
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
            'card_background',
            [
                'label' => __('Card Background', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-candidate-card' => 'background-color: {{VALUE}};',
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
                    '{{WRAPPER}} .mt-candidate-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Check if in editor
        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $this->render_editor_preview();
            return;
        }
        
        // Build shortcode
        $shortcode = sprintf(
            '[mt_candidate_grid category="%s" columns="%s" limit="%d" show_evaluation="%s"]',
            esc_attr($settings['category']),
            esc_attr($settings['columns']),
            intval($settings['limit']),
            $settings['show_evaluation_status'] === 'yes' ? 'true' : 'false'
        );
        
        echo '<div class="mt-elementor-widget-wrapper">';
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
            <i class="eicon-posts-grid" style="font-size: 48px; color: #999; margin-bottom: 20px;"></i>
            <h3><?php _e('Candidate Grid', 'mobility-trailblazers'); ?></h3>
            <p><?php printf(__('Showing %d candidates in %d columns', 'mobility-trailblazers'), $settings['limit'], $settings['columns']); ?></p>
            <p style="font-size: 12px; margin-top: 10px;">
                <?php _e('Category:', 'mobility-trailblazers'); ?> 
                <strong><?php echo $settings['category'] === 'all' ? __('All', 'mobility-trailblazers') : $settings['category']; ?></strong>
            </p>
        </div>
        <?php
    }
}