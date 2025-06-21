<?php
/**
 * Registration Form Elementor Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Registration_Form_Widget
 */
class MT_Registration_Form_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mt-registration-form';
    }
    
    public function get_title() {
        return __('Registration Form', 'mobility-trailblazers');
    }
    
    public function get_icon() {
        return 'eicon-form-horizontal';
    }
    
    public function get_categories() {
        return array('mobility-trailblazers');
    }
    
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show_categories',
            array(
                'label' => __('Show Categories', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'redirect_url',
            array(
                'label' => __('Redirect URL', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::URL,
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        echo do_shortcode('[mt_registration_form show_categories="' . $settings['show_categories'] . '"]');
    }
} 