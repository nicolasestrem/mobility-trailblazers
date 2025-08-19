<?php
/**
 * Candidates Grid Widget for Elementor
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
 * Class MT_Widget_Candidates_Grid
 */
class MT_Widget_Candidates_Grid extends \MobilityTrailblazers\Integrations\Elementor\MT_Widget_Base {
    
    /**
     * Get widget name
     *
     * @return string
     */
    public function get_name() {
        return 'mt_candidates_grid';
    }
    
    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __('MT Candidates Grid', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-posts-grid';
    }
    
    /**
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return ['candidates', 'grid', 'awards', 'mobility', 'trailblazers'];
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
            'columns',
            [
                'label' => __('Columns', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '3',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                ],
            ]
        );
        
        $this->add_control(
            'limit',
            [
                'label' => __('Number of Candidates', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => -1,
                'min' => -1,
                'step' => 1,
            ]
        );
        
        $this->add_control(
            'orderby',
            [
                'label' => __('Order By', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'title',
                'options' => [
                    'title' => __('Title', 'mobility-trailblazers'),
                    'date' => __('Date', 'mobility-trailblazers'),
                    'menu_order' => __('Menu Order', 'mobility-trailblazers'),
                    'rand' => __('Random', 'mobility-trailblazers'),
                ],
            ]
        );
        
        $this->add_control(
            'order',
            [
                'label' => __('Order', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'ASC',
                'options' => [
                    'ASC' => __('Ascending', 'mobility-trailblazers'),
                    'DESC' => __('Descending', 'mobility-trailblazers'),
                ],
            ]
        );
        
        $this->add_control(
            'show_bio',
            [
                'label' => __('Show Biography', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'show_category',
            [
                'label' => __('Show Category', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
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
        echo '<div class="mt-elementor-widget mt-candidates-grid-widget ' . esc_attr($settings['custom_css_classes']) . '">';
        
        // Prepare attributes
        $attributes = [
            'category' => $settings['category'],
            'columns' => $settings['columns'],
            'limit' => $settings['limit'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
            'show_bio' => $settings['show_bio'],
            'show_category' => $settings['show_category'],
        ];
        
        // Render the shortcode
        $this->render_shortcode('mt_candidates_grid', $attributes);
        
        echo '</div>';
    }
    
    /**
     * Render plain content (for live editor)
     *
     * @return void
     */
    public function render_plain_content() {
        $settings = $this->get_settings_for_display();
        
        $shortcode = '[mt_candidates_grid';
        
        if (!empty($settings['category'])) {
            $shortcode .= ' category="' . $settings['category'] . '"';
        }
        
        $shortcode .= ' columns="' . $settings['columns'] . '"';
        $shortcode .= ' limit="' . $settings['limit'] . '"';
        $shortcode .= ' orderby="' . $settings['orderby'] . '"';
        $shortcode .= ' order="' . $settings['order'] . '"';
        $shortcode .= ' show_bio="' . $settings['show_bio'] . '"';
        $shortcode .= ' show_category="' . $settings['show_category'] . '"';
        $shortcode .= ']';
        
        echo $shortcode;
    }
}
