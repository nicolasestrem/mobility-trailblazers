<?php
/**
 * Winners Display Widget for Elementor
 *
 * @package MobilityTrailblazers
 * @since 2.5.24
 */

namespace MobilityTrailblazers\Integrations\Elementor\Widgets;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load base class if not already loaded
if (!class_exists('\MobilityTrailblazers\Integrations\Elementor\MT_Widget_Base')) {
    require_once MT_PLUGIN_DIR . 'includes/integrations/elementor/class-mt-widget-base.php';
}

/**
 * Class MT_Widget_Winners_Display
 */
class MT_Widget_Winners_Display extends \MobilityTrailblazers\Integrations\Elementor\MT_Widget_Base {
    
    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mt_winners_display';
    }
    
    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __('MT Winners Display', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-trophy';
    }
    
    /**
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return ['winners', 'awards', 'trophy', 'mobility', 'trailblazers'];
    }
    
    /**
     * Register widget controls
     *
     * @return void
     */
    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'mobility-trailblazers'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // Get categories for dropdown
        $categories = get_terms([
            'taxonomy' => 'mt_award_category',
            'hide_empty' => false,
        ]);
        
        $category_options = ['' => __('All Categories', 'mobility-trailblazers')];
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $category_options[$category->slug] = $category->name;
            }
        }
        
        $this->add_control(
            'category',
            [
                'label' => __('Category', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '',
                'options' => $category_options,
            ]
        );
        
        $this->add_control(
            'year',
            [
                'label' => __('Year', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => date('Y'),
                'min' => 2020,
                'max' => 2030,
                'step' => 1,
            ]
        );
        
        $this->add_control(
            'limit',
            [
                'label' => __('Number of Winners', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => 3,
                'min' => 1,
                'max' => 10,
                'step' => 1,
            ]
        );
        
        $this->add_control(
            'show_scores',
            [
                'label' => __('Show Scores', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );
        
        $this->end_controls_section();
        
        // Register common controls
        $this->register_common_controls();
    }
    
    /**
     * Render widget
     *
     * @return void
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Add wrapper div with custom classes
        echo '<div class="mt-elementor-widget mt-winners-display-widget ' . esc_attr($settings['custom_css_classes']) . '">';
        
        // Prepare attributes
        $attributes = [
            'category' => $settings['category'],
            'year' => $settings['year'],
            'limit' => $settings['limit'],
            'show_scores' => $settings['show_scores'],
        ];
        
        // Render the shortcode
        $this->render_shortcode('mt_winners_display', $attributes);
        
        echo '</div>';
    }
    
    /**
     * Render plain content (for live editor)
     *
     * @return void
     */
    public function render_plain_content() {
        $settings = $this->get_settings_for_display();
        
        $shortcode = '[mt_winners_display';
        
        if (!empty($settings['category'])) {
            $shortcode .= ' category="' . $settings['category'] . '"';
        }
        
        $shortcode .= ' year="' . $settings['year'] . '"';
        $shortcode .= ' limit="' . $settings['limit'] . '"';
        $shortcode .= ' show_scores="' . $settings['show_scores'] . '"';
        $shortcode .= ']';
        
        echo $shortcode;
    }
}