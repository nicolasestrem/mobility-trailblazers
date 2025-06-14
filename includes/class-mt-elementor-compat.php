<?php
/**
 * Elementor Compatibility for Mobility Trailblazers - FIXED VERSION
 * File: includes/class-mt-elementor-compat.php
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Elementor Compatibility Class
 */
class MT_Elementor_Compatibility {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if Elementor is active - improved check
        if (!$this->is_elementor_active()) {
            return;
        }
        
        // Hook into Elementor
        add_action('elementor/init', array($this, 'init_elementor_hooks'));
    }
    
    /**
     * Check if Elementor is active and loaded
     */
    private function is_elementor_active() {
        return did_action('elementor/loaded') && class_exists('\Elementor\Plugin');
    }
    
    /**
     * Initialize Elementor hooks
     */
    public function init_elementor_hooks() {
        // Register custom widget category
        add_action('elementor/elements/categories_registered', array($this, 'add_widget_category'));
        
        // Prevent conflicts in Elementor editor
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'handle_editor_compatibility'));
        
        // Register custom Elementor widgets - FIXED PATH
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        
        // Handle preview mode
        add_action('elementor/preview/enqueue_styles', array($this, 'enqueue_preview_styles'));
        
        // Clear cache compatibility
        add_action('mt_after_evaluation_saved', array($this, 'clear_elementor_cache'));
        
        // Fix shortcode rendering in Elementor
        add_filter('elementor/frontend/the_content', array($this, 'fix_shortcode_rendering'));
        
        // Add error handling
        add_action('elementor/widgets/widgets_registered', array($this, 'check_widget_registration'));
    }
    
    /**
     * Add custom widget category
     */
    public function add_widget_category($elements_manager) {
        try {
            $elements_manager->add_category(
                'mobility-trailblazers',
                [
                    'title' => __('Mobility Trailblazers', 'mobility-trailblazers'),
                    'icon' => 'fa fa-car',
                ]
            );
        } catch (Exception $e) {
            error_log('MT Elementor Category Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle compatibility in Elementor editor
     */
    public function handle_editor_compatibility() {
        // Prevent our admin scripts from loading in Elementor editor
        wp_dequeue_script('mt-admin-js');
        wp_dequeue_script('mt-assignment-js');
        
        // Add special styles for Elementor editor
        wp_add_inline_style('elementor-editor', '
            /* MT Plugin Elementor Editor Styles */
            .mt-elementor-preview {
                padding: 20px;
                background: #f0f0f0;
                border: 2px dashed #999;
                text-align: center;
                min-height: 200px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
            }
            .mt-elementor-preview h3 {
                margin: 0 0 10px 0;
                color: #333;
            }
            .mt-elementor-preview p {
                margin: 0;
                color: #666;
            }
            .mt-widget-error {
                background: #ffebee;
                color: #c62828;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #ffcdd2;
                text-align: center;
            }
        ');
    }
    
    /**
     * Register custom Elementor widgets - FIXED PATHS
     */
    public function register_elementor_widgets($widgets_manager) {
        try {
            // Define the correct paths based on your project structure
            $widget_files = array(
                'jury_dashboard' => 'includes/elementor/class-jury-dashboard-widget.php',
                'candidate_grid' => 'includes/elementor/class-candidate-grid-widget.php', 
                'evaluation_stats' => 'includes/elementor/class-evaluation-stats-widget.php'
            );
            
            // Get the plugin path - check multiple possible constants
            $plugin_path = $this->get_plugin_path();
            
            foreach ($widget_files as $widget_key => $file_path) {
                $full_path = $plugin_path . $file_path;
                
                if (file_exists($full_path)) {
                    require_once $full_path;
                    
                    // Register the specific widget based on the file
                    switch ($widget_key) {
                        case 'jury_dashboard':
                            if (class_exists('MT_Jury_Dashboard_Widget')) {
                                $widgets_manager->register(new \MT_Jury_Dashboard_Widget());
                            }
                            break;
                        case 'candidate_grid':
                            if (class_exists('MT_Candidate_Grid_Widget')) {
                                $widgets_manager->register(new \MT_Candidate_Grid_Widget());
                            }
                            break;
                        case 'evaluation_stats':
                            if (class_exists('MT_Evaluation_Stats_Widget')) {
                                $widgets_manager->register(new \MT_Evaluation_Stats_Widget());
                            }
                            break;
                    }
                } else {
                    error_log("MT Widget file not found: $full_path");
                }
            }
            
        } catch (Exception $e) {
            error_log('MT Widget Registration Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get the correct plugin path
     */
    private function get_plugin_path() {
        // Try multiple constants that might be defined
        if (defined('MT_PLUGIN_PATH')) {
            return MT_PLUGIN_PATH;
        }
        
        if (defined('MOBILITY_TRAILBLAZERS_PATH')) {
            return MOBILITY_TRAILBLAZERS_PATH;
        }
        
        // Fallback: calculate from current file
        return plugin_dir_path(dirname(__FILE__));
    }
    
    /**
     * Check widget registration (for debugging)
     */
    public function check_widget_registration() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
            $registered_widgets = $widgets_manager->get_widget_types();
            
            if (!isset($registered_widgets['mt_evaluation_stats'])) {
                error_log('MT Evaluation Stats Widget failed to register');
            }
        }
    }
    
    /**
     * Enqueue styles for Elementor preview
     */
    public function enqueue_preview_styles() {
        // Ensure our frontend styles load in preview
        $plugin_url = $this->get_plugin_url();
        
        if (file_exists($this->get_plugin_path() . 'assets/frontend.css')) {
            wp_enqueue_style(
                'mt-frontend-css', 
                $plugin_url . 'assets/frontend.css', 
                array(), 
                $this->get_plugin_version()
            );
        }
    }
    
    /**
     * Get plugin URL
     */
    private function get_plugin_url() {
        if (defined('MT_PLUGIN_URL')) {
            return MT_PLUGIN_URL;
        }
        
        return plugin_dir_url(dirname(__FILE__));
    }
    
    /**
     * Get plugin version
     */
    private function get_plugin_version() {
        if (defined('MT_PLUGIN_VERSION')) {
            return MT_PLUGIN_VERSION;
        }
        
        return '1.0.0';
    }
    
    /**
     * Clear Elementor cache when evaluations are saved
     */
    public function clear_elementor_cache($evaluation_id) {
        try {
            if (class_exists('\Elementor\Plugin')) {
                \Elementor\Plugin::$instance->files_manager->clear_cache();
            }
        } catch (Exception $e) {
            error_log('MT Clear Cache Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Fix shortcode rendering in Elementor
     */
    public function fix_shortcode_rendering($content) {
        try {
            // Ensure our shortcodes work properly in Elementor
            if (has_shortcode($content, 'mt_jury_dashboard')) {
                // Make sure required scripts are loaded
                $plugin_url = $this->get_plugin_url();
                
                if (file_exists($this->get_plugin_path() . 'assets/frontend.js')) {
                    wp_enqueue_script(
                        'mt-frontend-js', 
                        $plugin_url . 'assets/frontend.js', 
                        array('jquery'), 
                        $this->get_plugin_version(), 
                        true
                    );
                    
                    wp_localize_script('mt-frontend-js', 'mt_ajax', array(
                        'ajax_url' => admin_url('admin-ajax.php'),
                        'nonce' => wp_create_nonce('mt_nonce')
                    ));
                }
            }
        } catch (Exception $e) {
            error_log('MT Shortcode Fix Error: ' . $e->getMessage());
        }
        
        return $content;
    }
    
    /**
     * Check if we're in Elementor editor
     */
    public static function is_elementor_editor() {
        return (
            class_exists('\Elementor\Plugin') && 
            \Elementor\Plugin::$instance->editor->is_edit_mode()
        );
    }
    
    /**
     * Check if we're in Elementor preview
     */
    public static function is_elementor_preview() {
        return isset($_GET['elementor-preview']);
    }
    
    /**
     * Debug method to check widget status
     */
    public function debug_widget_status() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $debug_info = array(
            'elementor_loaded' => did_action('elementor/loaded'),
            'plugin_class_exists' => class_exists('\Elementor\Plugin'),
            'widgets_manager_exists' => class_exists('\Elementor\Widgets_Manager'),
            'mt_widget_class_exists' => class_exists('MT_Evaluation_Stats_Widget'),
            'plugin_path' => $this->get_plugin_path(),
            'widget_file_exists' => file_exists($this->get_plugin_path() . 'includes/elementor/class-evaluation-stats-widget.php'),
        );
        
        if (class_exists('\Elementor\Plugin')) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
            $registered_widgets = $widgets_manager->get_widget_types();
            $debug_info['registered_widgets'] = array_keys($registered_widgets);
            $debug_info['mt_stats_widget_registered'] = isset($registered_widgets['mt_evaluation_stats']);
        }
        
        error_log('MT Widget Debug Info: ' . print_r($debug_info, true));
    }
}