<?php
namespace MobilityTrailblazers\Integrations;

/**
 * Class IntegrationsLoader
 * Handles loading of all plugin integrations
 */
class IntegrationsLoader {
    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance of this class
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
        // Load Elementor widgets after Elementor is fully loaded
        add_action('elementor/init', [$this, 'load_elementor_widgets']);
    }

    /**
     * Load Elementor widgets
     */
    public function load_elementor_widgets() {
        // Check if Elementor is active and loaded
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Load widget files
        $widget_files = [
            'class-jury-dashboard-widget.php',
            'class-candidate-grid-widget.php',
            'class-voting-form-widget.php'
        ];

        foreach ($widget_files as $file) {
            $file_path = MT_PLUGIN_PATH . 'includes/integrations/elementor/widgets/' . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }

        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }

    /**
     * Register Elementor widgets
     */
    public function register_widgets($widgets_manager) {
        $widgets = [
            'MobilityTrailblazers\Integrations\Elementor\Widgets\JuryDashboardWidget',
            'MobilityTrailblazers\Integrations\Elementor\Widgets\CandidateGridWidget',
            'MobilityTrailblazers\Integrations\Elementor\Widgets\VotingFormWidget'
        ];

        foreach ($widgets as $widget) {
            if (class_exists($widget)) {
                $widgets_manager->register(new $widget());
            }
        }
    }
}
