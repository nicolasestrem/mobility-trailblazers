<?php
/**
 * Voting Form Elementor Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Voting_Form_Widget
 */
class MT_Voting_Form_Widget extends \Elementor\Widget_Base {
    
    /**
     * Get widget name
     *
     * @return string Widget name
     */
    public function get_name() {
        return 'mt-voting-form';
    }
    
    /**
     * Get widget title
     *
     * @return string Widget title
     */
    public function get_title() {
        return __('Voting Form', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     *
     * @return string Widget icon
     */
    public function get_icon() {
        return 'eicon-form-horizontal';
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
        return array('voting', 'form', 'public', 'vote', 'mobility', 'trailblazers');
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
            'form_type',
            array(
                'label' => __('Form Type', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'general',
                'options' => array(
                    'general' => __('General Voting Form', 'mobility-trailblazers'),
                    'specific' => __('Specific Candidate', 'mobility-trailblazers'),
                ),
            )
        );
        
        $this->add_control(
            'candidate_id',
            array(
                'label' => __('Candidate', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_candidate_options(),
                'default' => '',
                'condition' => array(
                    'form_type' => 'specific',
                ),
            )
        );
        
        $this->add_control(
            'show_results',
            array(
                'label' => __('Show Vote Results', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'no',
            )
        );
        
        $this->add_control(
            'closed_message',
            array(
                'label' => __('Voting Closed Message', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Public voting is currently closed.', 'mobility-trailblazers'),
                'description' => __('Message shown when voting is disabled', 'mobility-trailblazers'),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Form
        $this->start_controls_section(
            'form_style_section',
            array(
                'label' => __('Form', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name' => 'form_background',
                'label' => __('Background', 'mobility-trailblazers'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .mt-voting-form-wrapper',
            )
        );
        
        $this->add_responsive_control(
            'form_padding',
            array(
                'label' => __('Padding', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-voting-form-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'form_border',
                'label' => __('Border', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-voting-form-wrapper',
            )
        );
        
        $this->add_control(
            'form_border_radius',
            array(
                'label' => __('Border Radius', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-voting-form-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section - Button
        $this->start_controls_section(
            'button_style_section',
            array(
                'label' => __('Vote Button', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->start_controls_tabs('button_style_tabs');
        
        $this->start_controls_tab(
            'button_normal_tab',
            array(
                'label' => __('Normal', 'mobility-trailblazers'),
            )
        );
        
        $this->add_control(
            'button_text_color',
            array(
                'label' => __('Text Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-vote-button' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name' => 'button_background',
                'label' => __('Background', 'mobility-trailblazers'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .mt-vote-button',
            )
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'button_hover_tab',
            array(
                'label' => __('Hover', 'mobility-trailblazers'),
            )
        );
        
        $this->add_control(
            'button_hover_text_color',
            array(
                'label' => __('Text Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-vote-button:hover' => 'color: {{VALUE}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            array(
                'name' => 'button_hover_background',
                'label' => __('Background', 'mobility-trailblazers'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .mt-vote-button:hover',
            )
        );
        
        $this->add_control(
            'button_hover_border_color',
            array(
                'label' => __('Border Color', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .mt-vote-button:hover' => 'border-color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            array(
                'name' => 'button_border',
                'label' => __('Border', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-vote-button',
            )
        );
        
        $this->add_control(
            'button_border_radius',
            array(
                'label' => __('Border Radius', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-vote-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'button_padding',
            array(
                'label' => __('Padding', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .mt-vote-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            array(
                'name' => 'button_typography',
                'label' => __('Typography', 'mobility-trailblazers'),
                'selector' => '{{WRAPPER}} .mt-vote-button',
            )
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Get candidate options
     *
     * @return array
     */
    private function get_candidate_options() {
        $options = array('' => __('Select Candidate', 'mobility-trailblazers'));
        
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish',
        ));
        
        foreach ($candidates as $candidate) {
            $options[$candidate->ID] = $candidate->post_title;
        }
        
        return $options;
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Check if public voting is enabled
        if (!mt_is_public_voting_enabled()) {
            echo '<div class="mt-notice mt-notice-info">' . 
                 esc_html($settings['closed_message']) . 
                 '</div>';
            return;
        }
        
        // Build shortcode attributes
        $atts = array(
            'show_results' => $settings['show_results'],
        );
        
        if ($settings['form_type'] === 'specific' && !empty($settings['candidate_id'])) {
            $atts['candidate_id'] = $settings['candidate_id'];
        }
        
        // Generate shortcode
        $shortcode = '[mt_voting_form';
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
        var shortcode = '[mt_voting_form';
        
        if (settings.form_type === 'specific' && settings.candidate_id) {
            shortcode += ' candidate_id="' + settings.candidate_id + '"';
        }
        if (settings.show_results) {
            shortcode += ' show_results="' + settings.show_results + '"';
        }
        
        shortcode += ']';
        #>
        <div class="mt-elementor-preview">
            <p><?php _e('Voting Form will be displayed here when public voting is enabled.', 'mobility-trailblazers'); ?></p>
            <p><code>{{{ shortcode }}}</code></p>
        </div>
        <?php
    }
} 