<?php
/**
 * Jury Data Dynamic Tag
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Jury_Data_Tag
 */
class MT_Jury_Data_Tag extends \Elementor\Core\DynamicTags\Tag {
    
    public function get_name() {
        return 'mt-jury-data';
    }
    
    public function get_title() {
        return __('Jury Data', 'mobility-trailblazers');
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
                    'position' => __('Position', 'mobility-trailblazers'),
                    'company' => __('Company', 'mobility-trailblazers'),
                    'expertise' => __('Expertise Areas', 'mobility-trailblazers'),
                    'role' => __('Jury Role', 'mobility-trailblazers'),
                ),
            )
        );
    }
    
    public function render() {
        $field = $this->get_settings('field');
        $post_id = get_the_ID();
        
        if (get_post_type($post_id) !== 'mt_jury') {
            return;
        }
        
        switch ($field) {
            case 'position':
                echo esc_html(get_post_meta($post_id, '_mt_position', true));
                break;
            case 'company':
                echo esc_html(get_post_meta($post_id, '_mt_company', true));
                break;
            case 'expertise':
                $expertise = get_post_meta($post_id, '_mt_expertise_areas', true);
                if (is_array($expertise)) {
                    echo esc_html(implode(', ', $expertise));
                }
                break;
            case 'role':
                $role = get_post_meta($post_id, '_mt_jury_role', true);
                switch ($role) {
                    case 'president':
                        echo __('President', 'mobility-trailblazers');
                        break;
                    case 'vice_president':
                        echo __('Vice President', 'mobility-trailblazers');
                        break;
                    default:
                        echo __('Jury Member', 'mobility-trailblazers');
                }
                break;
        }
    }
} 