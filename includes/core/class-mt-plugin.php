<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

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

// Load configuration
if (!class_exists('MobilityTrailblazers\Core\MT_Config')) {
    require_once MT_PLUGIN_DIR . 'includes/core/class-mt-config.php';
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
     * Dependency Injection Container
     *
     * @var MT_Container
     */
    private $container = null;
    
    /**
     * Flag to track if services are registered
     *
     * @var bool
     */
    private $services_registered = false;
    
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
     * Get container instance
     *
     * @return MT_Container
     */
    public static function container() {
        return self::get_instance()->get_container();
    }
    
    /**
     * Validate container has required services
     * 
     * @return bool True if container is properly configured
     */
    public static function validate_container() {
        try {
            $container = self::container();
            
            // Check critical service bindings
            $critical_services = [
                'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface',
                'MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface'
            ];
            
            foreach ($critical_services as $service) {
                if (!$container->has($service)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("MT Container Validation Failed: Missing {$service}");
                    }
                    return false;
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MT Container Validation Exception: " . $e->getMessage());
            }
            return false;
        }
    }
    
    /**
     * Get container instance
     *
     * @return MT_Container
     */
    public function get_container() {
        // Ensure container is created
        if (null === $this->container) {
            $this->container = MT_Container::get_instance();
        }
        
        // Ensure services are registered (critical for AJAX context)
        if (!$this->services_registered) {
            $this->register_services();
        }
        
        return $this->container;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize container early
        $this->container = MT_Container::get_instance();
        
        // For AJAX requests, ensure services are registered immediately
        if (defined('DOING_AJAX') && DOING_AJAX) {
            add_action('wp_loaded', [$this, 'ensure_services_for_ajax'], 5);
        }
    }
    
    /**
     * Ensure services are registered for AJAX requests
     * This is called early in the WordPress lifecycle for AJAX requests
     *
     * @return void
     */
    public function ensure_services_for_ajax() {
        if (!$this->services_registered) {
            $this->register_services();
        }
    }
    
    /**
     * Register services with the container
     *
     * @return void
     */
    private function register_services() {
        // Prevent double registration
        if ($this->services_registered) {
            return;
        }
        
        try {
            // Load container and service provider if not already loaded
            if (!class_exists('MobilityTrailblazers\Core\MT_Container')) {
                require_once MT_PLUGIN_DIR . 'includes/core/class-mt-container.php';
            }
            
            if (!class_exists('MobilityTrailblazers\Core\MT_Service_Provider')) {
                require_once MT_PLUGIN_DIR . 'includes/core/class-mt-service-provider.php';
            }
            
            // Register providers
            $providers = [
                'MobilityTrailblazers\Providers\MT_Repository_Provider',
                'MobilityTrailblazers\Providers\MT_Services_Provider',
            ];
            
            foreach ($providers as $provider_class) {
                $provider_file = $this->get_provider_file($provider_class);
                if (file_exists($provider_file)) {
                    require_once $provider_file;
                    if (class_exists($provider_class)) {
                        $this->container->register_provider(new $provider_class($this->container));
                        
                        // Debug logging for development
                        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                            error_log("MT Container: Registered provider {$provider_class}");
                        }
                    } else {
                        // Log missing provider class
                        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                            error_log("MT Container: Provider class {$provider_class} not found after loading file");
                        }
                    }
                } else {
                    // Log missing provider file
                    if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                        error_log("MT Container: Provider file not found: {$provider_file}");
                    }
                }
            }
            
            // Mark services as registered
            $this->services_registered = true;
            
            // Debug logging
            if (defined('WP_DEBUG') && WP_DEBUG && function_exists('error_log')) {
                error_log("MT Container: All services registered successfully");
            }
            
        } catch (\Exception $e) {
            // Log the error but don't break the application
            if (function_exists('error_log')) {
                error_log("MT Container Error: " . $e->getMessage());
            }
            
            // Try to continue without dependency injection
            $this->services_registered = false;
        }
    }
    
    /**
     * Get provider file path from class name
     *
     * @param string $provider_class Provider class name
     * @return string File path
     */
    private function get_provider_file($provider_class) {
        $class_parts = explode('\\', $provider_class);
        $class_name = end($class_parts);
        $file_name = 'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        return MT_PLUGIN_DIR . 'includes/providers/' . $file_name;
    }
    
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {
        // Initialize container and register services
        $this->get_container();
        
        // Load Composer autoload for PhpSpreadsheet
        if (file_exists(MT_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once MT_PLUGIN_DIR . 'vendor/autoload.php';
        }
        
        // Load utility classes
        if (file_exists(MT_PLUGIN_DIR . 'includes/utilities/class-mt-ranking-display.php')) {
            require_once MT_PLUGIN_DIR . 'includes/utilities/class-mt-ranking-display.php';
        }
        
        // Initialize i18n first
        $i18n = new MT_I18n();
        $i18n->init();
        
        // Initialize photo fix for Issue #13
        require_once MT_PLUGIN_DIR . 'includes/fixes/class-mt-photo-fix.php';
        \MobilityTrailblazers\Fixes\MT_Photo_Fix::init();
        
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
        
        // Initialize Performance Optimizer for cache management
        if (class_exists('MobilityTrailblazers\Core\MT_Performance_Optimizer')) {
            MT_Performance_Optimizer::init();
        }
        
        // Setup roles and capabilities
        $roles = new MT_Roles();
        add_action('init', [$roles, 'add_capabilities']);
        
        // Initialize i18n handler for JavaScript localization
        if (class_exists('MobilityTrailblazers\Core\MT_I18n_Handler')) {
            MT_I18n_Handler::get_instance();
        }
        
        // Initialize admin
        if (is_admin()) {
            $admin = new \MobilityTrailblazers\Admin\MT_Admin();
            $admin->init();
            
            // Initialize candidate columns and CSV import
            $candidate_columns = new \MobilityTrailblazers\Admin\MT_Candidate_Columns();
            $candidate_columns->init();
            
            // Initialize import/export handler
            \MobilityTrailblazers\Admin\MT_Import_Export::init();
            
            // Initialize candidate importer
            new \MobilityTrailblazers\Admin\MT_Candidate_Importer();
            
            // Initialize coaching dashboard for admin users
            if (current_user_can('manage_options')) {
                $coaching = new \MobilityTrailblazers\Admin\MT_Coaching();
            }
        }
        
        // Initialize AJAX handlers - Always initialize, not just during AJAX requests
        $this->init_ajax_handlers();
        
        // Register shortcodes
        $shortcodes = new MT_Shortcodes();
        $shortcodes->init();
        
        // Initialize v4 CSS framework for public assets (conditional loading)
        if (!is_admin()) {
            if (file_exists(MT_PLUGIN_DIR . 'includes/public/class-mt-public-assets.php')) {
                require_once MT_PLUGIN_DIR . 'includes/public/class-mt-public-assets.php';
                $public_assets = new \MobilityTrailblazers\Public\MT_Public_Assets();
                $public_assets->init();
            }
        }
        
        // Initialize Elementor integration
        if (did_action('elementor/loaded')) {
            require_once MT_PLUGIN_DIR . 'includes/elementor/class-mt-elementor-bootstrap.php';
            \MobilityTrailblazers\Elementor\MT_Elementor_Bootstrap::init();
            
            // Initialize Elementor Export tool for admins
            if (is_admin() && current_user_can('manage_options')) {
                require_once MT_PLUGIN_DIR . 'includes/admin/tools/class-mt-elementor-export.php';
                $elementor_export = new \MobilityTrailblazers\Admin\Tools\MT_Elementor_Export();
                $elementor_export->init();
            }
        }
        
        // Initialize template loader for enhanced candidate profiles
        MT_Template_Loader::init();
        
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
        
        // Debug Center AJAX (v2.3.0) - Only in development/staging
        if (is_admin() && current_user_can('manage_options') && !MT_Config::is_production()) {
            // Load utility classes if needed
            if (!class_exists('\MobilityTrailblazers\Utilities\MT_Database_Health')) {
                require_once MT_PLUGIN_DIR . 'includes/utilities/class-mt-database-health.php';
            }
            if (!class_exists('\MobilityTrailblazers\Utilities\MT_System_Info')) {
                require_once MT_PLUGIN_DIR . 'includes/utilities/class-mt-system-info.php';
            }
            
            // Initialize Debug AJAX handler
            if (file_exists(MT_PLUGIN_DIR . 'includes/ajax/class-mt-debug-ajax.php')) {
                require_once MT_PLUGIN_DIR . 'includes/ajax/class-mt-debug-ajax.php';
                new \MobilityTrailblazers\Ajax\MT_Debug_Ajax();
            }
        }
        
        // Import AJAX (handles candidate CSV imports)
        // The file self-initializes when loaded, creating an instance at the bottom
        require_once MT_PLUGIN_DIR . 'includes/ajax/class-mt-import-ajax.php';
        
        // CSV Import AJAX (comprehensive CSV import handler)
        require_once MT_PLUGIN_DIR . 'includes/ajax/class-mt-csv-import-ajax.php';
    }
    
    /**
     * Enqueue frontend assets
     * Conditionally loads CSS based on the active widgets/pages
     * 
     * @since 2.5.34 - Optimized to prevent CSS redundancy
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Check if v4 CSS is enabled (can be disabled via filter)
        $use_v4_css = apply_filters('mt_enable_css_v4', true);
        
        if ($use_v4_css) {
            // Load v4 CSS framework
            $v4_base_url = MT_PLUGIN_URL . 'assets/css/v4/';
            
            wp_enqueue_style(
                'mt-v4-tokens',
                $v4_base_url . 'mt-tokens.css',
                [],
                MT_VERSION
            );
            
            wp_enqueue_style(
                'mt-v4-reset',
                $v4_base_url . 'mt-reset.css',
                ['mt-v4-tokens'],
                MT_VERSION
            );
            
            wp_enqueue_style(
                'mt-v4-base',
                $v4_base_url . 'mt-base.css',
                ['mt-v4-reset'],
                MT_VERSION
            );
            
            wp_enqueue_style(
                'mt-v4-components',
                $v4_base_url . 'mt-components.css',
                ['mt-v4-base'],
                MT_VERSION
            );
            
            wp_enqueue_style(
                'mt-v4-pages',
                $v4_base_url . 'mt-pages.css',
                ['mt-v4-components'],
                MT_VERSION
            );
        }
        
        // Core CSS Variables (loaded first)
        wp_enqueue_style(
            'mt-variables',
            MT_PLUGIN_URL . 'assets/css/mt-variables.css',
            [],
            MT_VERSION
        );
        
        // Component Library (loaded second)
        wp_enqueue_style(
            'mt-components',
            MT_PLUGIN_URL . 'assets/css/mt-components.css',
            ['mt-variables'],
            MT_VERSION
        );
        
        // Main Frontend Styles (core styles only)
        wp_enqueue_style(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/css/frontend-new.css',
            ['mt-variables', 'mt-components'],
            MT_VERSION
        );
        
        // Candidate Grid Module
        wp_enqueue_style(
            'mt-candidate-grid',
            MT_PLUGIN_URL . 'assets/css/mt-candidate-grid.css',
            ['mt-variables', 'mt-components'],
            MT_VERSION
        );
        
        // Evaluation Forms Module
        wp_enqueue_style(
            'mt-evaluation-forms',
            MT_PLUGIN_URL . 'assets/css/mt-evaluation-forms.css',
            ['mt-variables', 'mt-components'],
            MT_VERSION
        );
        
        // Jury Dashboard Enhanced Module
        wp_enqueue_style(
            'mt-jury-dashboard-enhanced',
            MT_PLUGIN_URL . 'assets/css/mt-jury-dashboard-enhanced.css',
            ['mt-variables', 'mt-components'],
            MT_VERSION
        );
        
        // Enhanced candidate profile styles (includes all fixes)
        wp_enqueue_style(
            'mt-enhanced-candidate-profile',
            MT_PLUGIN_URL . 'assets/css/enhanced-candidate-profile.css',
            ['mt-variables', 'mt-components', 'mt-frontend', 'mt-candidate-grid'],
            MT_VERSION
        );
        
        // Brand alignment styles to match main website (v2.5.11)
        wp_enqueue_style(
            'mt-brand-alignment',
            MT_PLUGIN_URL . 'assets/css/mt-brand-alignment.css',
            ['mt-variables', 'mt-components', 'mt-frontend', 'mt-jury-dashboard-enhanced'],
            MT_VERSION
        );
        
        // Brand fixes for alignment, padding, and colors (v2.5.12)
        wp_enqueue_style(
            'mt-brand-fixes',
            MT_PLUGIN_URL . 'assets/css/mt-brand-fixes.css',
            ['mt-brand-alignment'],
            MT_VERSION
        );
        
        // New Ranking System v2 (v2.5.19)
        wp_enqueue_style(
            'mt-rankings-v2',
            MT_PLUGIN_URL . 'assets/css/mt-rankings-v2.css',
            ['mt-frontend', 'mt-jury-dashboard-enhanced'],
            MT_VERSION
        );
        
        // Evaluation form fixes (v2.5.20.1)
        wp_enqueue_style(
            'mt-evaluation-fixes',
            MT_PLUGIN_URL . 'assets/css/mt-evaluation-fixes.css',
            ['mt-frontend', 'mt-evaluation-forms'],
            MT_VERSION
        );
        
        // Design enhancements JavaScript (v1.0.0)
        wp_enqueue_script(
            'mt-design-enhancements',
            MT_PLUGIN_URL . 'assets/js/design-enhancements.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        // Evaluation rating fix JavaScript (Issue #21 fix)
        wp_enqueue_script(
            'mt-evaluation-rating-fix',
            MT_PLUGIN_URL . 'assets/js/evaluation-rating-fix.js',
            ['jquery', 'mt-frontend'],
            MT_VERSION,
            true
        );
        
        // Table rankings enhancements JavaScript - fixes ranking updates
        wp_enqueue_script(
            'mt-table-rankings-enhancements',
            MT_PLUGIN_URL . 'assets/js/table-rankings-enhancements.js',
            ['jquery', 'mt-frontend'],
            MT_VERSION,
            true
        );
        
        // Photo adjustment fix JavaScript (Issue #13 fix)
        wp_enqueue_script(
            'mt-photo-adjustment-fix',
            MT_PLUGIN_URL . 'assets/js/photo-adjustment-fix.js',
            ['jquery'],
            MT_VERSION,
            true
        );
        
        // New Candidate Cards v3 CSS - Modern redesign following CSS v3 specifications  
        // IMPORTANT: Load v3 CSS BEFORE hotfixes to establish base styles first
        wp_enqueue_style(
            'mt-candidate-cards-v3',
            MT_PLUGIN_URL . 'assets/css/mt-candidate-cards-v3.css',
            ['mt-frontend', 'mt-candidate-grid', 'mt-evaluation-fixes'],
            MT_VERSION
        );
        
        // Consolidated Hotfixes CSS - Combines multiple small hotfix files for better performance
        // Includes: photo-adjustments.css, candidate-image-adjustments.css, evaluation-fix.css, 
        // language-switcher-enhanced.css, mt-jury-dashboard-fix.css, emergency-fixes.css
        // IMPORTANT: Loaded after v3 CSS to provide targeted fixes without breaking v3 design
        wp_enqueue_style(
            'mt-hotfixes-consolidated',
            MT_PLUGIN_URL . 'assets/css/mt-hotfixes-consolidated.css',
            ['mt-candidate-cards-v3'],
            MT_VERSION
        );
        
        // BACKUP: Individual hotfix files (kept as backup, uncomment if needed)
        /*
        // Photo adjustments CSS (Issue #13 fix)
        wp_enqueue_style(
            'mt-photo-adjustments',
            MT_PLUGIN_URL . 'assets/css/photo-adjustments.css',
            ['mt-frontend'],
            MT_VERSION
        );
        
        // Candidate image adjustments CSS (Issue #13 fix for grid view)
        wp_enqueue_style(
            'mt-candidate-image-adjustments',
            MT_PLUGIN_URL . 'assets/css/candidate-image-adjustments.css',
            ['mt-frontend', 'mt-candidate-grid'],
            MT_VERSION
        );
        
        // Evaluation fix CSS (Issue #21 visual fix - ensures multiple buttons can appear selected)
        wp_enqueue_style(
            'mt-evaluation-fix',
            MT_PLUGIN_URL . 'assets/css/evaluation-fix.css',
            ['mt-frontend'],
            MT_VERSION
        );
        
        // Language switcher enhanced CSS (Issue #24 - enhanced visibility)
        wp_enqueue_style(
            'mt-language-switcher-enhanced',
            MT_PLUGIN_URL . 'assets/css/language-switcher-enhanced.css',
            ['mt-frontend'],
            MT_VERSION
        );
        */
        
        // Legacy jury dashboard styles (for backward compatibility)
        if (is_page('jury-dashboard') || (isset($_GET['evaluate']) && !empty($_GET['evaluate']))) {
            wp_enqueue_style(
                'mt-jury-dashboard',
                MT_PLUGIN_URL . 'assets/css/jury-dashboard.css',
                ['mt-frontend', 'mt-jury-dashboard-enhanced'],
                MT_VERSION
            );
            
            // Fix for evaluation card content cutoff (now included in mt-hotfixes-consolidated.css)
            // wp_enqueue_style(
            //     'mt-jury-dashboard-fix',
            //     MT_PLUGIN_URL . 'assets/css/mt-jury-dashboard-fix.css',
            //     ['mt-jury-dashboard'],
            //     MT_VERSION
            // );
        }
        
        // Scripts with locale-based cache busting
        $script_version = MT_VERSION . '-' . get_locale();
        wp_enqueue_script(
            'mt-frontend',
            MT_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            $script_version,
            true
        );
        
        // Always localize script (needed for AJAX)
        wp_localize_script('mt-frontend', 'mt_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
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
                'load_error' => __('Error loading candidate information.', 'mobility-trailblazers'),
                'request_timeout' => __('Request timed out. Please check your connection and try again.', 'mobility-trailblazers'),
                'request_cancelled' => __('Request was cancelled.', 'mobility-trailblazers'),
                'permission_denied' => __('You do not have permission to perform this action.', 'mobility-trailblazers'),
                'resource_not_found' => __('The requested resource was not found.', 'mobility-trailblazers'),
                'server_error' => __('Server error. Please try again later.', 'mobility-trailblazers'),
                'total_score' => __('Total Score:', 'mobility-trailblazers'),
                'characters' => __('characters', 'mobility-trailblazers'),
                'back_to_dashboard' => __('Back to Dashboard', 'mobility-trailblazers'),
                'evaluate_candidate' => __('Evaluate Candidate', 'mobility-trailblazers'),
                'save_all_changes' => __('Save All Changes', 'mobility-trailblazers'),
                'saving_progress' => __('Saving...', 'mobility-trailblazers'),
                'export_rankings' => __('Export Rankings', 'mobility-trailblazers'),
                'evaluation_submitted_status' => __('Evaluation Submitted', 'mobility-trailblazers'),
                'draft_saved' => __('Draft Saved', 'mobility-trailblazers'),
                'innovation_summary' => __('Innovation Summary', 'mobility-trailblazers'),
                'biography' => __('Biography', 'mobility-trailblazers'),
                'evaluation_criteria' => __('Evaluation Criteria', 'mobility-trailblazers'),
                'additional_comments' => __('Additional Comments', 'mobility-trailblazers'),
                'comments_placeholder' => __('Share your thoughts about this candidate\'s contributions to mobility innovation...', 'mobility-trailblazers'),
                'save_as_draft' => __('Save as Draft', 'mobility-trailblazers'),
                'submit_evaluation' => __('Submit Evaluation', 'mobility-trailblazers'),
                'submitting' => __('Submitting...', 'mobility-trailblazers'),
                'evaluation_submitted_full' => __('Thank you for submitting your evaluation!', 'mobility-trailblazers'),
                'criteria_evaluated' => __('criteria evaluated', 'mobility-trailblazers'),
                'evaluation_submitted_editable' => __('This evaluation has been submitted. You can still edit and resubmit.', 'mobility-trailblazers'),
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
                'evaluation_submitted_status' => __('Evaluation Submitted', 'mobility-trailblazers'),
                'save' => __('Save', 'mobility-trailblazers'),
                'error_saving_evaluation' => __('Error saving evaluation', 'mobility-trailblazers'),
                'network_error' => __('Network error. Please try again.', 'mobility-trailblazers'),
                'invalid_scores' => __('Please ensure all scores are between 0 and 10.', 'mobility-trailblazers'),
                'criteria_evaluated' => __('criteria evaluated', 'mobility-trailblazers'),
                'error_loading_content' => __('Error loading content', 'mobility-trailblazers'),
                'error_saving_content' => __('Error saving content', 'mobility-trailblazers'),
                'unsaved_changes_warning' => __('You have unsaved changes. Are you sure you want to close?', 'mobility-trailblazers'),
                'courage_description' => __('Demonstrates bold vision and willingness to take risks in advancing mobility transformation', 'mobility-trailblazers'),
                'innovation_description' => __('Shows creative problem-solving and introduces novel approaches to mobility challenges', 'mobility-trailblazers'),
                'implementation_description' => __('Successfully executes ideas with measurable impact on sustainable mobility', 'mobility-trailblazers'),
                'relevance_description' => __('Addresses critical aspects of transportation transformation and future mobility needs', 'mobility-trailblazers'),
                'visibility_description' => __('Serves as an inspiring example and actively promotes sustainable mobility solutions', 'mobility-trailblazers')
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
        
        // Core CSS Variables (loaded first)
        wp_enqueue_style(
            'mt-variables',
            MT_PLUGIN_URL . 'assets/css/mt-variables.css',
            [],
            MT_VERSION
        );
        
        // Component Library (loaded second)
        wp_enqueue_style(
            'mt-components',
            MT_PLUGIN_URL . 'assets/css/mt-components.css',
            ['mt-variables'],
            MT_VERSION
        );
        
        // Admin Styles (includes debug center)
        wp_enqueue_style(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            ['mt-variables', 'mt-components'],
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
        
        // CSV Import script (on import/export page)
        if (isset($_GET['page']) && $_GET['page'] === 'mt-import-export') {
            // Enqueue CSV import styles
            wp_enqueue_style(
                'mt-csv-import',
                MT_PLUGIN_URL . 'assets/css/csv-import.css',
                ['mt-admin'],
                MT_VERSION
            );
            
            // Enqueue CSV import script
            wp_enqueue_script(
                'mt-csv-import',
                MT_PLUGIN_URL . 'assets/js/csv-import.js',
                ['jquery'],
                MT_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('mt-csv-import', 'mt_csv_import', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ajax_nonce'),
                'i18n' => [
                    'importing' => __('Importing Data', 'mobility-trailblazers'),
                    'please_wait' => __('Please wait while we process your file...', 'mobility-trailblazers'),
                    'uploading_file' => __('Uploading file...', 'mobility-trailblazers'),
                    'processing' => __('Processing CSV data...', 'mobility-trailblazers'),
                    'import_complete' => __('Import completed successfully!', 'mobility-trailblazers'),
                    'import_failed' => __('Import failed. Please check the error messages.', 'mobility-trailblazers'),
                    'import_error' => __('An error occurred during import', 'mobility-trailblazers'),
                    'ajax_import' => __('Import via AJAX', 'mobility-trailblazers'),
                    'no_file_selected' => __('Please select a CSV file to import.', 'mobility-trailblazers'),
                    'no_type_selected' => __('Please select an import type.', 'mobility-trailblazers'),
                    'invalid_file_type' => __('Invalid file type. Please select a CSV file.', 'mobility-trailblazers'),
                    'file_too_large' => __('File is too large. Maximum size is 10MB.', 'mobility-trailblazers'),
                    'file_selected' => __('File selected: %s', 'mobility-trailblazers'),
                    'created' => __('created', 'mobility-trailblazers'),
                    'updated' => __('updated', 'mobility-trailblazers'),
                    'skipped' => __('skipped', 'mobility-trailblazers'),
                    'errors' => __('errors', 'mobility-trailblazers'),
                    'error_details' => __('Error Details:', 'mobility-trailblazers'),
                    'candidates_help' => __('<strong>Candidates CSV Format:</strong> ID, Name, Organisation, Position, LinkedIn-Link, Webseite, Article about coming of age, Description, Category, Status', 'mobility-trailblazers'),
                    'jury_help' => __('<strong>Jury Members CSV Format:</strong> name, title, organization, email, role', 'mobility-trailblazers')
                ]
            ]);
        }
        
        // Candidate import script (only on candidates page)
        $screen = get_current_screen();
        if ($screen && $screen->id === 'edit-mt_candidate') {
            wp_enqueue_script(
                'mt-candidate-import',
                MT_PLUGIN_URL . 'assets/js/candidate-import.js',
                ['jquery'],
                MT_VERSION,
                true
            );
            
            // Localize for import script
            wp_localize_script('mt-candidate-import', 'mt_ajax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_ajax_nonce'),
                'i18n' => [
                    'importing' => __('Importing...', 'mobility-trailblazers'),
                    'import_complete' => __('Import complete!', 'mobility-trailblazers'),
                    'import_failed' => __('Import failed!', 'mobility-trailblazers'),
                    'import_error' => __('An error occurred during import.', 'mobility-trailblazers'),
                    'invalid_file_type' => __('Please select a CSV file.', 'mobility-trailblazers'),
                    'file_too_large' => __('File is too large. Maximum size is 10MB.', 'mobility-trailblazers'),
                    'created' => __('created', 'mobility-trailblazers'),
                    'updated' => __('updated', 'mobility-trailblazers'),
                    'skipped' => __('skipped', 'mobility-trailblazers'),
                    'errors' => __('errors', 'mobility-trailblazers'),
                    'error_details' => __('Error details:', 'mobility-trailblazers')
                ]
            ]);
        }
        
        // Localize script
        wp_localize_script('mt-admin', 'mt_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
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
