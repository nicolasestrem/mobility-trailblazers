<?php
/**
 * Elementor Compatibility for Mobility Trailblazers
 * File: includes/integrations/elementor/class-elementor-integration.php
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

namespace MobilityTrailblazers\Integrations\Elementor;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ElementorIntegration
 * 
 * Handles all Elementor compatibility and widget registration
 */
class ElementorIntegration {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        if (!$this->is_elementor_active()) {
            return;
        }
        
        // Hook into Elementor
        add_action('elementor/init', [$this, 'init_elementor_hooks']);
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
        add_action('elementor/elements/categories_registered', [$this, 'add_widget_category']);
        
        // Prevent conflicts in Elementor editor
        add_action('elementor/editor/before_enqueue_scripts', [$this, 'handle_editor_compatibility']);
        
        // Register custom Elementor widgets
        add_action('elementor/widgets/register', [$this, 'register_elementor_widgets']);
        
        // Handle preview mode
        add_action('elementor/preview/enqueue_styles', [$this, 'enqueue_preview_styles']);
        
        // Clear cache compatibility
        add_action('mt_after_evaluation_saved', [$this, 'clear_elementor_cache']);
        
        // Fix shortcode rendering in Elementor
        add_filter('elementor/frontend/the_content', [$this, 'fix_shortcode_rendering']);
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
        } catch (\Exception $e) {
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
        wp_dequeue_script('mt-vote-reset-js');
        
        // Add special styles for Elementor editor
        wp_add_inline_style('elementor-editor', $this->get_editor_styles());
    }
    
    /**
     * Register custom Elementor widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        try {
            // Define widget files
            $widget_files = [
                'jury-dashboard' => 'widgets/class-jury-dashboard-widget.php',
                'candidate-grid' => 'widgets/class-candidate-grid-widget.php',
                'evaluation-stats' => 'widgets/class-evaluation-stats-widget.php',
                'voting-form' => 'widgets/class-voting-form-widget.php',
                'jury-members' => 'widgets/class-jury-members-widget.php',
                'voting-results' => 'widgets/class-voting-results-widget.php'
            ];
            
            $base_path = MT_PLUGIN_PATH . 'includes/integrations/elementor/';
            
            foreach ($widget_files as $widget_key => $file_path) {
                $full_path = $base_path . $file_path;
                
                if (file_exists($full_path)) {
                    require_once $full_path;
                    
                    // Register widgets based on class names
                    $class_map = [
                        'jury-dashboard' => '\MobilityTrailblazers\Integrations\Elementor\Widgets\JuryDashboardWidget',
                        'candidate-grid' => '\MobilityTrailblazers\Integrations\Elementor\Widgets\CandidateGridWidget',
                        'evaluation-stats' => '\MobilityTrailblazers\Integrations\Elementor\Widgets\EvaluationStatsWidget',
                        'voting-form' => '\MobilityTrailblazers\Integrations\Elementor\Widgets\VotingFormWidget',
                        'jury-members' => '\MobilityTrailblazers\Integrations\Elementor\Widgets\JuryMembersWidget',
                        'voting-results' => '\MobilityTrailblazers\Integrations\Elementor\Widgets\VotingResultsWidget'
                    ];
                    
                    if (isset($class_map[$widget_key]) && class_exists($class_map[$widget_key])) {
                        $widgets_manager->register(new $class_map[$widget_key]());
                    }
                } else {
                    error_log("MT Widget file not found: $full_path");
                }
            }
            
        } catch (\Exception $e) {
            error_log('MT Widget Registration Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Enqueue styles for Elementor preview
     */
    public function enqueue_preview_styles() {
        // Ensure our frontend styles load in preview
        if (file_exists(MT_PLUGIN_PATH . 'assets/css/frontend.css')) {
            wp_enqueue_style(
                'mt-frontend-css', 
                MT_PLUGIN_URL . 'assets/css/frontend.css', 
                [], 
                MT_PLUGIN_VERSION
            );
        }
        
        // Add Elementor-specific styles
        wp_add_inline_style('mt-frontend-css', $this->get_elementor_styles());
    }
    
    /**
     * Clear Elementor cache when evaluations are saved
     */
    public function clear_elementor_cache($evaluation_id) {
        try {
            if (class_exists('\Elementor\Plugin')) {
                \Elementor\Plugin::$instance->files_manager->clear_cache();
            }
        } catch (\Exception $e) {
            error_log('MT Clear Cache Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Fix shortcode rendering in Elementor
     */
    public function fix_shortcode_rendering($content) {
        try {
            // List of our shortcodes
            $shortcodes = [
                'mt_jury_dashboard',
                'mt_candidate_grid',
                'mt_voting_form',
                'mt_jury_members',
                'mt_voting_results'
            ];
            
            // Check if any of our shortcodes are present
            foreach ($shortcodes as $shortcode) {
                if (has_shortcode($content, $shortcode)) {
                    // Make sure required scripts are loaded
                    if (file_exists(MT_PLUGIN_PATH . 'assets/js/frontend.js')) {
                        wp_enqueue_script(
                            'mt-frontend-js', 
                            MT_PLUGIN_URL . 'assets/js/frontend.js', 
                            ['jquery'], 
                            MT_PLUGIN_VERSION, 
                            true
                        );
                        
                        wp_localize_script('mt-frontend-js', 'mt_ajax', [
                            'ajax_url' => admin_url('admin-ajax.php'),
                            'nonce' => wp_create_nonce('mt_nonce'),
                            'strings' => [
                                'vote_success' => __('Thank you for your vote!', 'mobility-trailblazers'),
                                'vote_error' => __('Error submitting vote. Please try again.', 'mobility-trailblazers'),
                                'already_voted' => __('You have already voted for this candidate.', 'mobility-trailblazers'),
                                'confirm_vote' => __('Are you sure you want to submit this evaluation?', 'mobility-trailblazers')
                            ]
                        ]);
                    }
                    break;
                }
            }
        } catch (\Exception $e) {
            error_log('MT Shortcode Fix Error: ' . $e->getMessage());
        }
        
        return $content;
    }
    
    /**
     * Get editor styles
     */
    private function get_editor_styles() {
        return '
            /* MT Plugin Elementor Editor Styles */
            .mt-elementor-preview {
                padding: 40px 20px;
                background: #f7f7f7;
                border: 2px dashed #ddd;
                text-align: center;
                border-radius: 8px;
                min-height: 200px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
            }
            .mt-elementor-preview i {
                font-size: 48px;
                color: #999;
                margin-bottom: 20px;
                display: block;
            }
            .mt-elementor-preview h3 {
                margin: 0 0 10px 0;
                color: #333;
                font-size: 24px;
            }
            .mt-elementor-preview p {
                margin: 5px 0;
                color: #666;
                font-size: 14px;
            }
            .mt-widget-error {
                background: #ffebee;
                color: #c62828;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #ffcdd2;
                text-align: center;
            }
        ';
    }
    
    /**
     * Get Elementor-specific styles
     */
    private function get_elementor_styles() {
        return '
            /* Elementor Compatibility Styles */
            .elementor-widget-mt_jury_dashboard .mt-jury-dashboard {
                width: 100%;
            }
            
            .elementor-widget-mt_candidate_grid .mt-candidate-grid {
                display: grid;
                gap: 20px;
            }
            
            .elementor-widget-mt_evaluation_stats .mt-stats-widget {
                width: 100%;
            }
            
            .elementor-widget-mt_voting_form .mt-voting-form {
                max-width: 100%;
            }
            
            .elementor-widget-mt_jury_members .mt-jury-grid {
                display: grid;
                gap: 20px;
            }
            
            .elementor-widget-mt_voting_results .mt-voting-results {
                width: 100%;
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .elementor-widget .mt-candidate-grid,
                .elementor-widget .mt-jury-grid {
                    grid-template-columns: 1fr;
                }
            }
        ';
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
     * Register widget styles
     */
    public function register_styles() {
        // Register frontend styles
        if (file_exists(MT_PLUGIN_PATH . 'assets/css/frontend.css')) {
            wp_register_style(
                'mt-frontend-css',
                MT_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                MT_PLUGIN_VERSION
            );
        }
    }

    /**
     * Register widget scripts
     */
    public function register_scripts() {
        // Register frontend scripts
        if (file_exists(MT_PLUGIN_PATH . 'assets/js/frontend.js')) {
            wp_register_script(
                'mt-frontend-js',
                MT_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                MT_PLUGIN_VERSION,
                true
            );
        }
    }
}