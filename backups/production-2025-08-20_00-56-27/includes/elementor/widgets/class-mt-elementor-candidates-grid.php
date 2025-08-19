<?php
/**
 * Elementor Candidates Grid Widget
 *
 * @package MobilityTrailblazers
 * @since 2.5.22
 */

namespace MobilityTrailblazers\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use MobilityTrailblazers\Public\Renderers\MT_Shortcode_Renderer;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Elementor_Candidates_Grid
 *
 * Elementor widget for candidates grid
 */
class MT_Elementor_Candidates_Grid extends Widget_Base {
    
    /**
     * Renderer instance
     *
     * @var MT_Shortcode_Renderer
     */
    private $renderer;
    
    /**
     * Constructor
     *
     * @param array $data
     * @param array $args
     */
    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        
        // Initialize renderer
        require_once MT_PLUGIN_DIR . 'includes/public/renderers/class-mt-shortcode-renderer.php';
        $this->renderer = new MT_Shortcode_Renderer();
    }
    
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
     * Get widget categories
     *
     * @return array
     */
    public function get_categories() {
        return ['mobility-trailblazers'];
    }
    
    /**
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return ['candidates', 'grid', 'mobility', 'trailblazers', 'awards'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        // Get categories for dropdown
        $categories = get_terms([
            'taxonomy' => 'mt_award_category',
            'hide_empty' => false,
        ]);
        
        $category_options = ['' => __('All Categories', 'mobility-trailblazers')];
        if (!is_wp_error($categories) && !empty($categories)) {
            foreach ($categories as $category) {
                $category_options[$category->slug] = $category->name;
            }
        }
        
        $this->add_control(
            'category',
            [
                'label' => __('Category', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'default' => '',
                'options' => $category_options,
            ]
        );
        
        $this->add_control(
            'columns',
            [
                'label' => __('Columns', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
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
                'type' => Controls_Manager::NUMBER,
                'default' => -1,
                'min' => -1,
                'step' => 1,
                'description' => __('Use -1 to show all candidates', 'mobility-trailblazers'),
            ]
        );
        
        $this->add_control(
            'orderby',
            [
                'label' => __('Order By', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'default' => 'title',
                'options' => [
                    'title' => __('Title', 'mobility-trailblazers'),
                    'date' => __('Date', 'mobility-trailblazers'),
                    'modified' => __('Modified', 'mobility-trailblazers'),
                    'rand' => __('Random', 'mobility-trailblazers'),
                ],
            ]
        );
        
        $this->add_control(
            'order',
            [
                'label' => __('Order', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
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
                'type' => Controls_Manager::SWITCHER,
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
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Style controls
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'card_background',
            [
                'label' => __('Card Background', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-candidate-grid-item' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'card_border_color',
            [
                'label' => __('Card Border Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-candidate-grid-item' => 'border-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'category_tag_background',
            [
                'label' => __('Category Tag Background', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-category-tag' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'card_gap',
            [
                'label' => __('Card Gap', 'mobility-trailblazers'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 8,
                        'max' => 48,
                    ],
                ],
                'default' => [
                    'size' => 32,
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--mt-card-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'image_fit',
            [
                'label' => __('Image Fit', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'options' => [
                    'cover' => 'Cover',
                    'contain' => 'Contain',
                    'fill' => 'Fill',
                ],
                'default' => 'cover',
                'selectors' => [
                    '{{WRAPPER}} .mt-card__image' => 'object-fit: {{VALUE}};',
                ],
            ]
        );
        
        $this->add_control(
            'image_position',
            [
                'label' => __('Image Position', 'mobility-trailblazers'),
                'type' => Controls_Manager::TEXT,
                'default' => '30% 50%',
                'selectors' => [
                    '{{WRAPPER}} .mt-card__image' => 'object-position: {{VALUE}};',
                ],
            ]
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Prepare arguments for renderer
        $args = [
            'category' => sanitize_text_field($settings['category']),
            'columns' => intval($settings['columns']),
            'limit' => intval($settings['limit']),
            'orderby' => sanitize_text_field($settings['orderby']),
            'order' => sanitize_text_field($settings['order']),
            'show_bio' => $settings['show_bio'],
            'show_category' => $settings['show_category'],
        ];
        
        // Render using shared renderer
        echo $this->renderer->render_candidates_grid($args);
    }
}
