<?php
/**
 * Candidate Profile Elementor Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Profile_Widget
 */
class MT_Candidate_Profile_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mt-candidate-profile';
    }
    
    public function get_title() {
        return __('Candidate Profile', 'mobility-trailblazers');
    }
    
    public function get_icon() {
        return 'eicon-person';
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
            'candidate_id',
            array(
                'label' => __('Candidate', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_candidate_options(),
                'description' => __('Leave empty to use current candidate in single view', 'mobility-trailblazers'),
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
        
        $this->add_control(
            'show_jury_comments',
            array(
                'label' => __('Show Jury Comments', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
            )
        );
        
        $this->end_controls_section();
    }
    
    private function get_candidate_options() {
        $options = array('' => __('Current Candidate', 'mobility-trailblazers'));
        
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
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        $shortcode = sprintf(
            '[mt_candidate_profile id="%s" show_score="%s" show_jury_comments="%s"]',
            $settings['candidate_id'],
            $settings['show_score'],
            $settings['show_jury_comments']
        );
        echo do_shortcode($shortcode);
    }
} 