<?php
/**
 * Elementor Evaluation Stats Widget
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
 * Class MT_Elementor_Evaluation_Stats
 *
 * Elementor widget for evaluation statistics
 */
class MT_Elementor_Evaluation_Stats extends Widget_Base {
    
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
        return 'mt_evaluation_stats';
    }
    
    /**
     * Get widget title
     *
     * @return string
     */
    public function get_title() {
        return __('MT Evaluation Statistics', 'mobility-trailblazers');
    }
    
    /**
     * Get widget icon
     *
     * @return string
     */
    public function get_icon() {
        return 'eicon-chart-bar';
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
        return ['evaluation', 'statistics', 'stats', 'mobility', 'trailblazers', 'voting'];
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
        
        $this->add_control(
            'type',
            [
                'label' => __('Statistics Type', 'mobility-trailblazers'),
                'type' => Controls_Manager::SELECT,
                'default' => 'summary',
                'options' => [
                    'summary' => __('Summary', 'mobility-trailblazers'),
                    'by-category' => __('By Category', 'mobility-trailblazers'),
                    'by-jury' => __('By Jury Member', 'mobility-trailblazers'),
                ],
            ]
        );
        
        $this->add_control(
            'show_chart',
            [
                'label' => __('Show Chart', 'mobility-trailblazers'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'permission_notice',
            [
                'label' => __('Permission Required', 'mobility-trailblazers'),
                'type' => Controls_Manager::RAW_HTML,
                'raw' => __('This widget requires the "View All Evaluations" permission to display content.', 'mobility-trailblazers'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
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
            'stat_number_color',
            [
                'label' => __('Statistic Number Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-stat-number' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_control(
            'bar_fill_color',
            [
                'label' => __('Bar Fill Color', 'mobility-trailblazers'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mt-bar-fill' => 'background-color: {{VALUE}}',
                    '{{WRAPPER}} .mt-progress-mini-fill' => 'background-color: {{VALUE}}',
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
            'type' => sanitize_text_field($settings['type']),
            'show_chart' => $settings['show_chart'],
        ];
        
        // Render using shared renderer
        echo $this->renderer->render_evaluation_stats($args);
    }
    
    /**
     * Render widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="mt-notice mt-notice-info">
            <?php echo esc_html__('Evaluation Statistics will be displayed here for users with appropriate permissions.', 'mobility-trailblazers'); ?>
        </div>
        <?php
    }
}
