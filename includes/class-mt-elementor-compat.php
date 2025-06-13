<?php
/**
 * Elementor Compatibility for Mobility Trailblazers
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
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Prevent conflicts in Elementor editor
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'handle_editor_compatibility'));
        
        // Register custom Elementor widgets
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));
        
        // Handle preview mode
        add_action('elementor/preview/enqueue_styles', array($this, 'enqueue_preview_styles'));
        
        // Clear cache compatibility
        add_action('mt_after_evaluation_saved', array($this, 'clear_elementor_cache'));
        
        // Fix shortcode rendering in Elementor
        add_filter('elementor/frontend/the_content', array($this, 'fix_shortcode_rendering'));
        
        // Ensure proper script dependencies
        add_action('wp_enqueue_scripts', array($this, 'ensure_elementor_dependencies'), 999);
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
        ');
    }
    
    /**
     * Register custom Elementor widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        // Include widget files
        require_once MT_PLUGIN_PATH . 'includes/elementor-widgets/class-jury-dashboard-widget.php';
        require_once MT_PLUGIN_PATH . 'includes/elementor-widgets/class-candidate-grid-widget.php';
        require_once MT_PLUGIN_PATH . 'includes/elementor-widgets/class-evaluation-stats-widget.php';
        
        // Register widgets
        $widgets_manager->register(new \MT_Jury_Dashboard_Widget());
        $widgets_manager->register(new \MT_Candidate_Grid_Widget());
        $widgets_manager->register(new \MT_Evaluation_Stats_Widget());
    }
    
    /**
     * Enqueue styles for Elementor preview
     */
    public function enqueue_preview_styles() {
        // Ensure our frontend styles load in preview
        wp_enqueue_style('mt-frontend-css', MT_PLUGIN_URL . 'assets/frontend.css', array(), MT_PLUGIN_VERSION);
    }
    
    /**
     * Clear Elementor cache when evaluations are saved
     */
    public function clear_elementor_cache($evaluation_id) {
        if (class_exists('\Elementor\Plugin')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
    }
    
    /**
     * Fix shortcode rendering in Elementor
     */
    public function fix_shortcode_rendering($content) {
        // Ensure our shortcodes work properly in Elementor
        if (has_shortcode($content, 'mt_jury_dashboard')) {
            // Make sure required scripts are loaded
            wp_enqueue_script('mt-frontend-js', MT_PLUGIN_URL . 'assets/frontend.js', array('jquery'), MT_PLUGIN_VERSION, true);
            wp_localize_script('mt-frontend-js', 'mt_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_nonce')
            ));
        }
        
        return $content;
    }
    
    /**
     * Check if we're in Elementor editor
     */
    public static function is_elementor_editor() {
        return isset($_GET['action']) && $_GET['action'] === 'elementor';
    }
    
    /**
     * Check if we're in Elementor preview
     */
    public static function is_elementor_preview() {
        return isset($_GET['elementor-preview']);
    }
    
    /**
     * Ensure proper Elementor dependencies
     */
    public function ensure_elementor_dependencies() {
        // Only on pages with Elementor content
        if (!is_singular() || !class_exists('\Elementor\Plugin')) {
            return;
        }
        
        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }
        
        // Check if page uses Elementor
        $document = \Elementor\Plugin::$instance->documents->get($post_id);
        if (!$document || !$document->is_built_with_elementor()) {
            return;
        }
        
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Ensure Elementor frontend scripts are loaded
        if (wp_script_is('elementor-frontend', 'registered') && !wp_script_is('elementor-frontend', 'enqueued')) {
            wp_enqueue_script('elementor-frontend');
        }
    }
}