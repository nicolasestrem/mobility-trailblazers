<?php
/**
 * Elementor Winners Display Widget
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
 * Class MT_Elementor_Winners_Display
 *
 * Elementor widget for winners display
 */
class MT_Elementor_Winners_Display extends Widget_Base {
    
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
        return ['winners', 'awards', 'top', 'mobility', 'trailblazers', 'results'];
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
            'year',
            [
                'label' => __('Year', 'mobility-trailblazers'),
                'type' => Controls_Manager::NUMBER,
                'default' => date('Y'),
                'min' => 2020,
                'max' => date('Y') + 1,
                'step' => 1,
            ]
        );
        
        $this->add_control(
            'limit',
            [
                'label' => __('Number of Winners', 'mobility-trailblazers'),
                'type' => Controls_Manager::NUMBER,
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
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'no',
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
            'medal_color_gold',
            [
                'label' => __('Gold Medal Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#FFD700',
                'selectors' => [
                    '{{WRAPPER}} .mt-rank-1 .mt-medal' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'medal_color_silver',
            [
                'label' => __('Silver Medal Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#C0C0C0',
                'selectors' => [
                    '{{WRAPPER}} .mt-rank-2 .mt-medal' => 'background-color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'medal_color_bronze',
            [
                'label' => __('Bronze Medal Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'default' => '#CD7F32',
                'selectors' => [
                    '{{WRAPPER}} .mt-rank-3 .mt-medal' => 'background-color: {{VALUE}}',
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
            'year' => intval($settings['year']),
            'limit' => intval($settings['limit']),
            'show_scores' => $settings['show_scores'],
        ];
        
        // Render using shared renderer
        echo $this->renderer->render_winners_display($args);
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="mt-notice mt-notice-info">
            <?php echo esc_html__('Winners Display will show top candidates based on evaluation scores.', 'mobility-trailblazers'); ?>
        </div>
        <?php
    }
}
