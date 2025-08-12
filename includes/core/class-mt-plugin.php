<?php
/**
 * Main Plugin Class
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Plugin
 *
 * Main plugin class that initializes all components
 */
class MT_Plugin {
    
    /**
     * Plugin instance
     *
     * @var MT_Plugin
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     *
     * @return MT_Plugin
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
        // Prevent direct instantiation
    }
    
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {
        // Initialize i18n first
        $i18n = new MT_I18n();
        $i18n->init();
        
        // Initialize language switcher widget
        $language_switcher = new \MobilityTrailblazers\Widgets\MT_Language_Switcher();
        $language_switcher->init();
        
        // Check for database upgrades
        MT_Database_Upgrade::run();
        
        // Register post types
        $post_types = new MT_Post_Types();
        $post_types->init();
        
        // Register taxonomies
        $taxonomies = new MT_Taxonomies();
        $taxonomies->init();
        
        // Setup roles and capabilities
        $roles = new MT_Roles();
        add_action('init', [$roles, 'add_capabilities']);
        
        // Initialize admin
        if (is_admin()) {
            $admin = new \MobilityTrailblazers\Admin\MT_Admin();
            $admin->init();
            
            // Initialize error monitor for admin users
            if (current_user_can('manage_options')) {
                $error_monitor = new \MobilityTrailblazers\Admin\MT_Error_Monitor();
                $error_monitor->init();
            }
        }
        
        // Initialize AJAX handlers - Always initialize, not just during AJAX requests
        $this->init_ajax_handlers();
        
        // Register shortcodes
        $shortcodes = new MT_Shortcodes();
        $shortcodes->init();
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Initialize AJAX handlers
     *
     * @return void
     */
    private function init_ajax_handlers() {
        // Evaluation AJAX
        $evaluation_ajax = new \MobilityTrailblazers\Ajax\MT_Evaluation_Ajax();
        $evaluation_ajax->init();
        
        // Assignment AJAX
        $assignment_ajax = new \MobilityTrailblazers\Ajax\MT_Assignment_Ajax();
        $assignment_ajax->init();
        
        // Admin AJAX
        $admin_ajax = new \MobilityTrailblazers\Ajax\MT_Admin_Ajax();
        $admin_ajax->init();
    }
    
    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Styles
        wp_enqueue_style(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            MT_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-frontend', 'mt_ajax', [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_ajax_nonce'),
            'i18n' => [
                'loading' => __('Loading...', 'mobility-trailblazers'),
                'error' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'success' => __('Success!', 'mobility-trailblazers'),
                'saving' => __('Saving...', 'mobility-trailblazers'),
                'saved' => __('Saved!', 'mobility-trailblazers'),
                'confirm_delete' => __('Are you sure you want to delete this?', 'mobility-trailblazers'),
                'draft_saved' => __('Draft saved successfully!', 'mobility-trailblazers'),
                'evaluation_submitted' => __('Evaluation submitted successfully!', 'mobility-trailblazers'),
                'please_rate_all' => __('Please rate all criteria before submitting.', 'mobility-trailblazers'),
                'submit' => __('Submit', 'mobility-trailblazers'),
                'cancel' => __('Cancel', 'mobility-trailblazers'),
                'search_placeholder' => __('Search candidates...', 'mobility-trailblazers'),
                'no_results' => __('No results found.', 'mobility-trailblazers'),
                'invalid_candidate' => __('Invalid candidate ID.', 'mobility-trailblazers'),
                'security_error' => __('Security configuration error. Please refresh the page and try again.', 'mobility-trailblazers'),
                'loading_evaluation' => __('Loading evaluation form...', 'mobility-trailblazers'),
                'request_timeout' => __('Request timed out. Please check your connection and try again.', 'mobility-trailblazers'),
                'request_cancelled' => __('Request was cancelled.', 'mobility-trailblazers'),
                'permission_denied' => __('You do not have permission to perform this action.', 'mobility-trailblazers'),
                'resource_not_found' => __('The requested resource was not found.', 'mobility-trailblazers'),
                'server_error' => __('Server error. Please try again later.', 'mobility-trailblazers'),
                'total_score' => __('Total Score:', 'mobility-trailblazers'),
                'characters' => __('characters', 'mobility-trailblazers'),
                'back_to_dashboard' => __('Back to Dashboard', 'mobility-trailblazers'),
                'evaluate_candidate' => __('Evaluate Candidate', 'mobility-trailblazers'),
                'evaluation_submitted' => __('Evaluation Submitted', 'mobility-trailblazers'),
                'draft_saved' => __('Draft Saved', 'mobility-trailblazers'),
                'innovation_summary' => __('Innovation Summary', 'mobility-trailblazers'),
                'biography' => __('Biography', 'mobility-trailblazers'),
                'evaluation_criteria' => __('Evaluation Criteria', 'mobility-trailblazers'),
                'additional_comments' => __('Additional Comments', 'mobility-trailblazers'),
                'comments_placeholder' => __('Share your thoughts about this candidate\'s contributions to mobility innovation...', 'mobility-trailblazers'),
                'save_as_draft' => __('Save as Draft', 'mobility-trailblazers'),
                'submit_evaluation' => __('Submit Evaluation', 'mobility-trailblazers'),
                'evaluation_guidelines' => __('Evaluation Guidelines', 'mobility-trailblazers'),
                'guideline_1' => __('Score each criterion from 0 (lowest) to 10 (highest) based on your assessment', 'mobility-trailblazers'),
                'guideline_2' => __('Consider the candidate\'s overall impact on mobility transformation', 'mobility-trailblazers'),
                'guideline_3' => __('You can save your evaluation as a draft and return later to complete it', 'mobility-trailblazers'),
                'guideline_4' => __('Once submitted, you can still edit your evaluation if needed', 'mobility-trailblazers'),
                'not_started' => __('Not Started', 'mobility-trailblazers'),
                'draft_saved_status' => __('Draft Saved', 'mobility-trailblazers'),
                'completed' => __('Completed', 'mobility-trailblazers'),
                'view_edit_evaluation' => __('View/Edit Evaluation', 'mobility-trailblazers'),
                'continue_evaluation' => __('Continue Evaluation', 'mobility-trailblazers'),
                'start_evaluation' => __('Start Evaluation', 'mobility-trailblazers'),
                'no_candidates_assigned' => __('No candidates have been assigned to you yet.', 'mobility-trailblazers'),
                'all_statuses' => __('All Statuses', 'mobility-trailblazers'),
                'pending' => __('Pending', 'mobility-trailblazers'),
                'draft' => __('Draft', 'mobility-trailblazers'),
                'completed_status' => __('Completed', 'mobility-trailblazers'),
                'total_assigned' => __('Total Assigned', 'mobility-trailblazers'),
                'in_draft' => __('In Draft', 'mobility-trailblazers'),
                'welcome_message' => __('Review and evaluate your assigned candidates for the Mobility Trailblazers Awards', 'mobility-trailblazers'),
                'not_assigned_error' => __('You are not assigned to evaluate this candidate.', 'mobility-trailblazers'),
                'candidate_not_found' => __('Candidate not found.', 'mobility-trailblazers'),
                'optional_comments' => __('Please provide any additional insights or observations about this candidate (optional).', 'mobility-trailblazers'),
                'submitting' => __('Submitting...', 'mobility-trailblazers'),
                'evaluation_submitted_editable' => __('This evaluation has been submitted. You can still edit and resubmit.', 'mobility-trailblazers'),
                'evaluation_submitted' => __('Evaluation Submitted', 'mobility-trailblazers'),
                'save' => __('Save', 'mobility-trailblazers'),
                'error_saving_evaluation' => __('Error saving evaluation', 'mobility-trailblazers'),
                'network_error' => __('Network error. Please try again.', 'mobility-trailblazers'),
                'invalid_scores' => __('Please ensure all scores are between 0 and 10.', 'mobility-trailblazers')
            ]
        ]);
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Check if we're on our plugin pages - be more inclusive
        $is_plugin_page = false;
        
        // Check various patterns
        if (strpos($hook, 'mobility-trailblazers') !== false ||
            strpos($hook, 'mt-') !== false ||
            (isset($_GET['page']) && strpos($_GET['page'], 'mt-') === 0) ||
            (isset($_GET['page']) && strpos($_GET['page'], 'mobility-trailblazers') !== false)) {
            $is_plugin_page = true;
        }
        
        if (!$is_plugin_page) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            MT_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-util'],
            MT_VERSION,
            true
        );
        
        // I18n admin script
        wp_enqueue_script(
            'mt-i18n-admin',
            MT_PLUGIN_URL . 'assets/js/i18n-admin.js',
            ['jquery', 'mt-admin'],
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-admin', 'mt_admin', [
            'url' => admin_url('admin-ajax.php'),
            'ajax_url' => admin_url('admin-ajax.php'), // Add both for compatibility
            'nonce' => wp_create_nonce('mt_admin_nonce'),
            'i18n' => [
                'confirm_delete' => __('Are you sure you want to delete this?', 'mobility-trailblazers'),
                'saving' => __('Saving...', 'mobility-trailblazers'),
                'saved' => __('Saved!', 'mobility-trailblazers'),
                'error' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'processing' => __('Processing...', 'mobility-trailblazers'),
                'error_occurred' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'assignments_created' => __('Assignments created successfully.', 'mobility-trailblazers'),
                'assign_selected' => __('Assign Selected', 'mobility-trailblazers'),
                'run_auto_assignment' => __('Run Auto-Assignment', 'mobility-trailblazers'),
                'select_bulk_action' => __('Please select a bulk action', 'mobility-trailblazers'),
                'select_assignments' => __('Please select at least one assignment', 'mobility-trailblazers'),
                'apply' => __('Apply', 'mobility-trailblazers'),
                'select_jury_member' => __('Please select a jury member', 'mobility-trailblazers'),
                'export_started' => __('Export started. Download will begin shortly.', 'mobility-trailblazers'),
                'confirm_clear_all' => __('Are you sure you want to clear ALL assignments? This cannot be undone.', 'mobility-trailblazers'),
                'confirm_clear_all_second' => __('This will remove ALL jury assignments. Are you absolutely sure?', 'mobility-trailblazers'),
                'clearing' => __('Clearing...', 'mobility-trailblazers'),
                'clear_all' => __('Clear All', 'mobility-trailblazers'),
                'all_assignments_cleared' => __('All assignments have been cleared.', 'mobility-trailblazers')
            ]
        ]);
    }
} 