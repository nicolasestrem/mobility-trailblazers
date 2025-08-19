<?php
/**
 * Elementor Jury Dashboard Widget
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
 * Class MT_Elementor_Jury_Dashboard
 *
 * Elementor widget for jury dashboard
 */
class MT_Elementor_Jury_Dashboard extends Widget_Base {
    
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
        return 'mt_jury_dashboard';
    }
    
    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __('MT Jury Dashboard', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-dashboard';
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
        return ['jury', 'dashboard', 'evaluation', 'mobility', 'trailblazers'];
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Settings', 'mobility-trailblazers'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );
        
        $this->add_control(
            'info_text',
            [
                'label' => __('Information', 'mobility-trailblazers'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => __('This widget displays the jury dashboard for logged-in jury members. No configuration is required.', 'mobility-trailblazers'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
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
            'primary_color',
            [
                'label' => __('Primary Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-number' => 'color: {{VALUE}}',
                    '{{WRAPPER}} .mt-btn-primary' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .mt-candidate-link:hover' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'secondary_color',
            [
                'label' => __('Secondary Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-progress-fill' => 'background: linear-gradient(to right, {{primary_color.VALUE}}, {{VALUE}})',
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
        $args = [];
        
        // Render using shared renderer
        echo $this->renderer->render_jury_dashboard($args);
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="mt-notice mt-notice-info">
            <?php echo esc_html__('Jury Dashboard will be displayed here. Login as a jury member to see the content.', 'mobility-trailblazers'); ?>
        </div>
        <?php
    }
}
