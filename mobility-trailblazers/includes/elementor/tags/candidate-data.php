<?php
/**
 * Candidate Data Dynamic Tag
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Data_Tag
 */
class MT_Candidate_Data_Tag extends \Elementor\Core\DynamicTags\Tag {
    
    public function get_name() {
        return 'mt-candidate-data';
    }
    
    public function get_title() {
        return __('Candidate Data', 'mobility-trailblazers');
    }
    
    public function get_group() {
        return 'post';
    }
    
    public function get_categories() {
        return array(\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY);
    }
    
    protected function register_controls() {
        $this->add_control(
            'field',
            array(
                'label' => __('Field', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'company' => __('Company', 'mobility-trailblazers'),
                    'position' => __('Position', 'mobility-trailblazers'),
                    'location' => __('Location', 'mobility-trailblazers'),
                    'innovation_title' => __('Innovation Title', 'mobility-trailblazers'),
                    'score' => __('Score', 'mobility-trailblazers'),
                ),
            )
        );
    }
    
    public function render() {
        $field = $this->get_settings('field');
        $post_id = get_the_ID();
        
        if (get_post_type($post_id) !== 'mt_candidate') {
            return;
        }
        
        switch ($field) {
            case 'company':
                echo esc_html(get_post_meta($post_id, '_mt_company_name', true));
                break;
            case 'position':
                echo esc_html(get_post_meta($post_id, '_mt_position', true));
                break;
            case 'location':
                echo esc_html(get_post_meta($post_id, '_mt_location', true));
                break;
            case 'innovation_title':
                echo esc_html(get_post_meta($post_id, '_mt_innovation_title', true));
                break;
            case 'score':
                echo esc_html(get_post_meta($post_id, '_mt_final_score', true));
                break;
        }
    }
} 