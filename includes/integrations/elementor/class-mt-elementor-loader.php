<?php
/**
 * Elementor Integration Loader
 *
 * @package MobilityTrailblazers
 * @since 2.5.24
 */

namespace MobilityTrailblazers\Integrations\Elementor;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Elementor_Loader
 *
 * Handles loading of Elementor integration
 */
class MT_Elementor_Loader {
    
    /**
     * Instance
     *
     * @var MT_Elementor_Loader
     */
    private static $instance = null;
    
    /**
     * Widgets registered flag
     *
     * @var bool
     */
    private $widgets_registered = false;
    
    /**
     * Get instance
     *
     * @return MT_Elementor_Loader
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Register widgets when Elementor is ready
        add_action('elementor/widgets/register', [$this, 'register_widgets'], 10);
        
        // Register category
        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
    }
    
    /**
     * Initialize the integration
     *
     * @return void
     */
    public static function init() {
        self::get_instance();
    }
    
    /**
     * Register widget category
     *
     * @param \Elementor\Elements_Manager $elements_manager
     * @return void
     */
    public function register_category($elements_manager) {
        $elements_manager->add_category(
            'mobility-trailblazers',
            [
                'title' => __('Mobility Trailblazers', 'mobility-trailblazers'),
                'icon' => 'fa fa-plug',
            ]
        );
    }
    
    /**
     * Register widgets
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     * @return void
     */
    public function register_widgets($widgets_manager) {
        // Prevent double registration
        if ($this->widgets_registered) {
            return;
        }
        
        // Load widget files
        $widget_files = [
            'jury-dashboard',
            'candidates-grid',
            'evaluation-stats',
            'winners-display'
        ];
        
        foreach ($widget_files as $widget) {
            $file = MT_PLUGIN_DIR . 'includes/integrations/elementor/widgets/class-mt-widget-' . $widget . '.php';
            if (file_exists($file)) {
                require_once $file;
                
                // Generate class name
                $class_name = 'MobilityTrailblazers\\Integrations\\Elementor\\Widgets\\MT_Widget_' . 
                             str_replace('-', '_', ucwords($widget, '-'));
                
                // Register widget if class exists
                if (class_exists($class_name)) {
                    try {
                        $widgets_manager->register(new $class_name());
                    } catch (\Exception $e) {
                        MT_Logger::error('Elementor widget registration failed', [
                            'widget_class' => $class_name,
                            'error_message' => $e->getMessage(),
                            'file' => $file
                        ]);
                    }
                }
            }
        }
        
        $this->widgets_registered = true;
    }
}
