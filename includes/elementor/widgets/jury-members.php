<?php
/**
 * MT Jury Widget
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Jury_Widget
 */
class MT_Jury_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'mt-jury-members';
    }
    
    public function get_title() {
        return __('Jury Members', 'mobility-trailblazers');
    }
    
    public function get_icon() {
        return 'eicon-person-group';
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
            'role',
            array(
                'label' => __('Role Filter', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => array(
                    '' => __('All', 'mobility-trailblazers'),
                    'president' => __('President', 'mobility-trailblazers'),
                    'vice_president' => __('Vice President', 'mobility-trailblazers'),
                    'member' => __('Member', 'mobility-trailblazers'),
                ),
            )
        );
        
        $this->add_control(
            'limit',
            array(
                'label' => __('Number of Members', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => -1,
            )
        );
        
        $this->add_responsive_control(
            'columns',
            array(
                'label' => __('Columns', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '4',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
            )
        );
        
        $this->add_control(
            'show_bio',
            array(
                'label' => __('Show Bio', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'yes',
            )
        );
        
        $this->end_controls_section();
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        $shortcode = sprintf(
            '[mt_jury_member role="%s" limit="%d" columns="%s" show_bio="%s"]',
            $settings['role'],
            $settings['limit'],
            $settings['columns'],
            $settings['show_bio']
        );
        echo do_shortcode($shortcode);
    }

    public function get_shortcode() {
        return sprintf(
            '[mt_jury_member role="%s" limit="%d" columns="%s" show_bio="%s"]',
            $this->get_settings('role'),
            $this->get_settings('limit'),
            $this->get_settings('columns'),
            $this->get_settings('show_bio') ? 'true' : 'false'
        );
    }
} 