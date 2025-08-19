<?php
/**
 * Evaluation Statistics Widget for Elementor
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
 * Class MT_Widget_Evaluation_Stats
 */
class MT_Widget_Evaluation_Stats extends \MobilityTrailblazers\Integrations\Elementor\MT_Widget_Base {
    
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
        return 'eicon-counter';
    }
    
    /**
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return ['evaluation', 'statistics', 'stats', 'mobility', 'trailblazers'];
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
        
        $this->add_control(
            'type',
            [
                'label' => __('Statistics Type', 'mobility-trailblazers'),
                'type' => \Elementor\Controls_Manager::SELECT,
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
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'mobility-trailblazers'),
                'label_off' => __('No', 'mobility-trailblazers'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        $this->add_control(
            'important_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => __('This widget is only visible to users with permission to view all evaluations.', 'mobility-trailblazers'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
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
        echo '<div class="mt-elementor-widget mt-evaluation-stats-widget ' . esc_attr($settings['custom_css_classes']) . '">';
        
        // Prepare attributes
        $attributes = [
            'type' => $settings['type'],
            'show_chart' => $settings['show_chart'],
        ];
        
        // Render the shortcode
        $this->render_shortcode('mt_evaluation_stats', $attributes);
        
        echo '</div>';
    }
    
    /**
     * Render plain content (for live editor)
     *
     * @return void
     */
    public function render_plain_content() {
        $settings = $this->get_settings_for_display();
        
        $shortcode = '[mt_evaluation_stats';
        $shortcode .= ' type="' . $settings['type'] . '"';
        $shortcode .= ' show_chart="' . $settings['show_chart'] . '"';
        $shortcode .= ']';
        
        echo $shortcode;
    }
}
