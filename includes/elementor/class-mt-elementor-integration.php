<?php
/**
 * Elementor Integration
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Elementor_Integration
 * Handles Elementor Pro widgets integration
 */
class MT_Elementor_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }
        
        // Register widgets
        add_action('elementor/widgets/widgets_registered', array($this, 'register_widgets'));
        
        // Register widget categories
        add_action('elementor/elements/categories_registered', array($this, 'register_categories'));
        
        // Enqueue widget scripts
        add_action('elementor/frontend/after_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Register dynamic tags
        add_action('elementor/dynamic_tags/register_tags', array($this, 'register_dynamic_tags'));
    }
    
    /**
     * Register widget categories
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public function register_categories($elements_manager) {
        $elements_manager->add_category(
            'mobility-trailblazers',
            array(
                'title' => __('Mobility Trailblazers', 'mobility-trailblazers'),
                'icon' => 'fa fa-trophy',
            )
        );
    }
    
    /**
     * Register widgets
     *
     * @param \Elementor\Widgets_Manager $widgets_manager
     */
    public function register_widgets($widgets_manager) {
        // Include widget files
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/candidates-grid.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/jury-dashboard.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/voting-form.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/registration-form.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/evaluation-stats.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/winners-display.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/jury-members.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/widgets/candidate-profile.php';
        
        // Register widgets
        $widgets_manager->register_widget_type(new \MT_Candidates_Grid_Widget());
        $widgets_manager->register_widget_type(new \MT_Jury_Dashboard_Widget());
        $widgets_manager->register_widget_type(new \MT_Voting_Form_Widget());
        $widgets_manager->register_widget_type(new \MT_Registration_Form_Widget());
        $widgets_manager->register_widget_type(new \MT_Evaluation_Stats_Widget());
        $widgets_manager->register_widget_type(new \MT_Winners_Display_Widget());
        $widgets_manager->register_widget_type(new \MT_Jury_Members_Widget());
        $widgets_manager->register_widget_type(new \MT_Candidate_Profile_Widget());
    }
    
    /**
     * Enqueue scripts for Elementor editor
     */
    public function enqueue_scripts() {
        // Enqueue Elementor compatibility script
        wp_enqueue_script(
            'mt-elementor-compat',
            MT_PLUGIN_URL . 'assets/js/elementor-compat.js',
            array('jquery', 'elementor-frontend'),
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-elementor-compat', 'mt_elementor', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_elementor_nonce'),
        ));
    }
    
    /**
     * Register dynamic tags
     *
     * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager
     */
    public function register_dynamic_tags($dynamic_tags_manager) {
        // Include dynamic tag files
        require_once MT_PLUGIN_DIR . 'includes/elementor/tags/candidate-data.php';
        require_once MT_PLUGIN_DIR . 'includes/elementor/tags/jury-data.php';
        
        // Register tags
        $dynamic_tags_manager->register_tag('MT_Candidate_Data_Tag');
        $dynamic_tags_manager->register_tag('MT_Jury_Data_Tag');
    }
    
    /**
     * Check if Elementor Pro is active
     *
     * @return bool
     */
    public static function is_elementor_pro_active() {
        return defined('ELEMENTOR_PRO_VERSION');
    }
} 