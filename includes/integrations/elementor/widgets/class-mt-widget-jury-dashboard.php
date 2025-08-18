<?php
/**
 * Jury Dashboard Widget for Elementor
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
 * Class MT_Widget_Jury_Dashboard
 */
class MT_Widget_Jury_Dashboard extends \MobilityTrailblazers\Integrations\Elementor\MT_Widget_Base {
    
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
     * Get widget keywords
     *
     * @return array
     */
    public function get_keywords() {
        return ['jury', 'dashboard', 'evaluation', 'mobility', 'trailblazers'];
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
            'important_note',
            [
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => __('This widget displays the jury dashboard for logged-in jury members. It will show an access denied message for non-jury users.', 'mobility-trailblazers'),
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
        echo '<div class="mt-elementor-widget mt-jury-dashboard-widget ' . esc_attr($settings['custom_css_classes']) . '">';
        
        // Render the shortcode
        $this->render_shortcode('mt_jury_dashboard');
        
        echo '</div>';
    }
    
    /**
     * Render plain content (for live editor)
     *
     * @return void
     */
    public function render_plain_content() {
        echo '[mt_jury_dashboard]';
    }
}