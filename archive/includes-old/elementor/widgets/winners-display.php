<?php
/**
 * Winners Display Elementor Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Winners_Display_Widget
 */
class MT_Winners_Display_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mt-winners-display';
    }
    
    public function get_title() {
        return __('Winners Display', 'mobility-trailblazers');
    }
    
    public function get_icon() {
        return 'eicon-trophy';
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
            'year',
            array(
                'label' => __('Award Year', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => mt_get_current_award_year(),
            )
        );
        
        $this->add_control(
            'limit',
            array(
                'label' => __('Number of Winners', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 25,
            )
        );
        
        $this->add_control(
            'show_category',
            array(
                'label' => __('Show Category', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_score',
            array(
                'label' => __('Show Score', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        $shortcode = sprintf(
            '[mt_winners year="%s" limit="%d" show_category="%s" show_score="%s"]',
            $settings['year'],
            $settings['limit'],
            $settings['show_category'],
            $settings['show_score']
        );
        echo do_shortcode($shortcode);
    }
} 