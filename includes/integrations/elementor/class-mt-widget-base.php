<?php
/**
 * Base Widget Class for Elementor
 *
 * @package MobilityTrailblazers
 * @since 2.5.24
 */

namespace MobilityTrailblazers\Integrations\Elementor;

use Elementor\Widget_Base;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract class MT_Widget_Base
 *
 * Base class for all MT Elementor widgets
 */
abstract class MT_Widget_Base extends Widget_Base {
    
    /**
     * Get widget categories
     *
     * @return array
     */
    public function get_categories() {
        return ['mobility-trailblazers'];
    }
    
    /**
     * Render shortcode
     *
     * @param string $shortcode_name
     * @param array $attributes
     * @return void
     */
    protected function render_shortcode($shortcode_name, $attributes = []) {
        // Build shortcode string
        $shortcode = '[' . $shortcode_name;
        
        foreach ($attributes as $key => $value) {
            if ($value !== '' && $value !== null) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        
        $shortcode .= ']';
        
        // Output the shortcode
        echo do_shortcode($shortcode);
    }
    
    /**
     * Get default settings
     *
     * @return array
     */
    protected function get_default_settings() {
        return [
            'show_bio' => 'yes',
            'show_category' => 'yes',
            'show_scores' => 'no',
            'show_chart' => 'yes'
        ];
    }
    
    /**
     * Register common controls
     *
     * @return void
     */
    protected function register_common_controls() {
        // Style Section
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Style', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'custom_css_classes',
            [
                'label' => __('CSS Classes', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => '',
                'title' => __('Add your custom CSS classes', 'mobility-trailblazers'),
            ]
        );
        
        $this->end_controls_section();
    }
}
