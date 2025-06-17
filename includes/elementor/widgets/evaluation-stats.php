<?php
/**
 * Evaluation Stats Elementor Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Evaluation_Stats_Widget
 */
class MT_Evaluation_Stats_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mt-evaluation-stats';
    }
    
    public function get_title() {
        return __('Evaluation Statistics', 'mobility-trailblazers');
    }
    
    public function get_icon() {
        return 'eicon-counter';
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
            'type',
            array(
                'label' => __('Statistics Type', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'overview',
                'options' => array(
                    'overview' => __('Overview', 'mobility-trailblazers'),
                    'category' => __('By Category', 'mobility-trailblazers'),
                    'criteria' => __('By Criteria', 'mobility-trailblazers'),
                ),
            )
        );
        
        $this->add_control(
            'show_chart',
            array(
                'label' => __('Show Chart', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        echo do_shortcode('[mt_evaluation_stats type="' . $settings['type'] . '" show_chart="' . $settings['show_chart'] . '"]');
    }
} 