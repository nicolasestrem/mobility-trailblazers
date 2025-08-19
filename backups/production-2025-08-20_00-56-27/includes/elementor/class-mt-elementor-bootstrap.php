<?php
/**
 * Elementor Bootstrap Loader
 *
 * @package MobilityTrailblazers
 * @since 2.5.22
 */

namespace MobilityTrailblazers\Elementor;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Elementor_Bootstrap
 *
 * Registers and initializes Elementor widgets
 */
class MT_Elementor_Bootstrap {
    
    /**
     * Instance
     *
     * @var MT_Elementor_Bootstrap
     */
    private static $instance = null;
    
    /**
     * Get instance
     *
     * @return MT_Elementor_Bootstrap
     */
    public static function get_instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        
        // Register widget categories
        add_action('elementor/elements/categories_registered', [$this, 'register_widget_categories']);
    }
    
    /**
     * Register widget categories
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_widget_categories($elements_manager) {
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
     */
    public function register_widgets($widgets_manager) {
        // Load widget files
        $widget_files = [
            'class-mt-elementor-jury-dashboard.php',
            'class-mt-elementor-candidates-grid.php',
            'class-mt-elementor-evaluation-stats.php',
            'class-mt-elementor-winners-display.php'
        ];
        
        foreach ($widget_files as $file) {
            $file_path = MT_PLUGIN_DIR . 'includes/elementor/widgets/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        // Register widgets
        if (class_exists('\MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Jury_Dashboard')) {
            $widgets_manager->register(new \MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Jury_Dashboard());
        }
        
        if (class_exists('\MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Candidates_Grid')) {
            $widgets_manager->register(new \MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Candidates_Grid());
        }
        
        if (class_exists('\MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Evaluation_Stats')) {
            $widgets_manager->register(new \MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Evaluation_Stats());
        }
        
        if (class_exists('\MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Winners_Display')) {
            $widgets_manager->register(new \MobilityTrailblazers\Elementor\Widgets\MT_Elementor_Winners_Display());
        }
    }
    
    /**
     * Initialize
     */
    public static function init() {
        self::get_instance();
    }
}
