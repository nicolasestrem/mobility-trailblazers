<?php
namespace MobilityTrailblazers\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class VotingFormWidget extends Widget_Base {
    /**
     * Get widget name
     */
    public function get_name() {
        return 'mt_voting_form';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return __('Voting Form', 'mobility-trailblazers');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['mobility-trailblazers'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        // Candidate ID control
        $this->add_control(
            'candidate_id',
            [
                'label' => __('Candidate', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT2,
                'options' => $this->get_candidates(),
                'label_block' => true,
            ]
        );

        // Type control
        $this->add_control(
            'type',
            [
                'label' => __('Voting Type', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'default' => 'public',
                'options' => [
                    'public' => __('Public', 'mobility-trailblazers'),
                    'jury' => __('Jury', 'mobility-trailblazers'),
                ],
            ]
        );

        // Show criteria control
        $this->add_control(
            'show_criteria',
            [
                'label' => __('Show Criteria', 'mobility-trailblazers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        // Form background color
        $this->add_control(
            'form_background_color',
            [
                'label' => __('Form Background', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-voting-form' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        // Button color
        $this->add_control(
            'button_color',
            [
                'label' => __('Button Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-voting-form button' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get candidates for the select control
     */
    private function get_candidates() {
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        $options = [];
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
        
        if (empty($settings['candidate_id'])) {
            echo '<p>' . __('Please select a candidate.', 'mobility-trailblazers') . '</p>';
            return;
        }

        $shortcode = sprintf(
            '[mt_voting_form candidate_id="%s" type="%s" show_criteria="%s"]',
            esc_attr($settings['candidate_id']),
            esc_attr($settings['type']),
            $settings['show_criteria'] === 'yes' ? 'true' : 'false'
        );

        echo do_shortcode($shortcode);
    }
} 