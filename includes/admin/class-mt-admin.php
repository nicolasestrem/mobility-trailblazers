<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Admin Class
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Admin
 *
 * Handles admin interface and functionality
 */
class MT_Admin {
    
    /**
     * Initialize admin
     *
     * @return void
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'display_admin_notices']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Handle redirects
        add_action('admin_init', [$this, 'handle_activation_redirect']);
        
        // Add dashboard widgets
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widgets']);
        
        // Initialize Elementor Templates tool
        if (is_admin()) {
            $this->init_elementor_templates();
        }
        
        // Initialize Candidate Editor
        require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-candidate-editor.php';
    }
    
    /**
     * Add admin menus
     *
     * @return void
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('Mobility Trailblazers', 'mobility-trailblazers'),
            __('Mobility Trailblazers', 'mobility-trailblazers'),
            'mt_manage_settings',
            'mobility-trailblazers',
            [$this, 'render_dashboard_page'],
            'dashicons-location-alt',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Dashboard', 'mobility-trailblazers'),
            __('Dashboard', 'mobility-trailblazers'),
            'mt_manage_settings',
            'mobility-trailblazers',
            [$this, 'render_dashboard_page']
        );
        
        // Evaluations submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Evaluations', 'mobility-trailblazers'),
            __('Evaluations', 'mobility-trailblazers'),
            'mt_view_all_evaluations',
            'mt-evaluations',
            [$this, 'render_evaluations_page']
        );
        
        // Assignments submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Assignments', 'mobility-trailblazers'),
            __('Assignments', 'mobility-trailblazers'),
            'mt_manage_assignments',
            'mt-assignments',
            [$this, 'render_assignments_page']
        );
        
        // Import/Export submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Import/Export', 'mobility-trailblazers'),
            __('Import/Export', 'mobility-trailblazers'),
            'mt_import_data',
            'mt-import-export',
            [$this, 'render_import_export_page']
        );
        
        // Settings submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Settings', 'mobility-trailblazers'),
            __('Settings', 'mobility-trailblazers'),
            'mt_manage_settings',
            'mt-settings',
            [$this, 'render_settings_page']
        );
        
        // Developer Tools submenu - Only in development/staging environments
        if (current_user_can('manage_options') && (defined('WP_DEBUG') && WP_DEBUG)) {
            add_submenu_page(
                'mobility-trailblazers',
                __('Developer Tools', 'mobility-trailblazers'),
                __('Developer Tools', 'mobility-trailblazers'),
                'manage_options',
                'mt-debug-center',
                [$this, 'render_debug_center_page']
            );
        }
        
        // Legacy redirects for backward compatibility
        if (isset($_GET['page'])) {
            if ($_GET['page'] === 'mt-tools') {
                wp_redirect(admin_url('admin.php?page=mt-debug-center&tab=tools'));
                wp_die();
            } elseif ($_GET['page'] === 'mt-diagnostics') {
                wp_redirect(admin_url('admin.php?page=mt-debug-center&tab=diagnostics'));
                wp_die();
            }
        }

        // Profile Migration and other admin-only menus
        if (current_user_can('manage_options')) {
            
            
            // Audit Log
            add_submenu_page(
                'mobility-trailblazers',
                __('Audit Log', 'mobility-trailblazers'),
                __('Audit Log', 'mobility-trailblazers'),
                'manage_options',
                'mt-audit-log',
                [$this, 'render_audit_log_page']
            );
            
        }
    }
    
    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings() {
        // General settings
        register_setting('mt_general_settings', 'mt_settings', [
            'sanitize_callback' => [$this, 'sanitize_general_settings']
        ]);
        

        
        // Criteria weights
        register_setting('mt_criteria_settings', 'mt_criteria_weights', [
            'sanitize_callback' => [$this, 'sanitize_criteria_weights']
        ]);
        
        // Dashboard customization settings
        register_setting('mt_dashboard_settings', 'mt_dashboard_settings', [
            'sanitize_callback' => [$this, 'sanitize_dashboard_settings']
        ]);
    }
    
    /**
     * Render dashboard page
     *
     * @return void
     */
    public function render_dashboard_page() {
        // Get statistics
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        
        $eval_stats = $evaluation_repo->get_statistics();
        $assign_stats = $assignment_repo->get_statistics();
        
        // Get recent activity
        $recent_evaluations = $evaluation_repo->find_all([
            'limit' => 5,
            'orderby' => 'updated_at',
            'order' => 'DESC'
        ]);
        
        $template_file = MT_PLUGIN_DIR . 'templates/admin/dashboard.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Dashboard template file not found.', 'mobility-trailblazers') . '</p></div>';
        }
    }
    
    /**
     * Render evaluations page
     *
     * @return void
     */
    public function render_evaluations_page() {
        // Check if viewing single evaluation
        if (isset($_GET['evaluation_id'])) {
            $evaluation_id = absint($_GET['evaluation_id']);
            if ($evaluation_id > 0) {
                $this->render_single_evaluation($evaluation_id);
            } else {
                wp_die(__('Invalid evaluation ID.', 'mobility-trailblazers'));
            }
            return;
        }
        
        // Get evaluations
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $page = max(1, $page);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $args = [
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => 'updated_at',
            'order' => 'DESC'
        ];
        
        // Filter by status
        if (!empty($_GET['status'])) {
            $args['status'] = sanitize_text_field($_GET['status']);
        }
        
        // Filter by jury member
        if (!empty($_GET['jury_member'])) {
            $args['jury_member_id'] = intval($_GET['jury_member']);
        }
        
        // Filter by candidate
        if (!empty($_GET['candidate'])) {
            $args['candidate_id'] = intval($_GET['candidate']);
        }
        
        $evaluations = $evaluation_repo->find_all($args);
        $total = $evaluation_repo->find_all(['limit' => -1]); // Get total count
        $total_count = count($total);
        
        include MT_PLUGIN_DIR . 'templates/admin/evaluations.php';
    }
    
    /**
     * Render single evaluation
     *
     * @return void
     */
    private function render_single_evaluation($evaluation_id = null) {
        if ($evaluation_id === null) {
            $evaluation_id = isset($_GET['evaluation_id']) ? absint($_GET['evaluation_id']) : 0;
        }
        
        if (!$evaluation_id) {
            wp_die(__('Invalid evaluation ID.', 'mobility-trailblazers'));
        }
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $evaluation = $evaluation_repo->find($evaluation_id);
        
        if (!$evaluation) {
            wp_die(__('Evaluation not found.', 'mobility-trailblazers'));
        }
        
        // Get related data
        $candidate = get_post($evaluation->candidate_id);
        $jury_member = get_post($evaluation->jury_member_id);
        
        include MT_PLUGIN_DIR . 'templates/admin/single-evaluation.php';
    }
    
    /**
     * Render assignments page
     *
     * @return void
     */
    public function render_assignments_page() {
        $assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
        
        // Handle form submissions
        if (isset($_POST['mt_assignment_action'])) {
            $this->handle_assignment_action();
        }
        
        // Get data
        $summary = $assignment_service->get_summary();
        $jury_members = get_posts([
            'post_type' => 'mt_jury_member',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        include MT_PLUGIN_DIR . 'templates/admin/assignments.php';
    }
    
    /**
     * Handle assignment actions
     *
     * @return void
     */
    private function handle_assignment_action() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'mt_assignment_action')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        $action = sanitize_text_field($_POST['mt_assignment_action']);
        $assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();
        
        switch ($action) {
            case 'manual_assign':
                $data = [
                    'jury_member_id' => intval($_POST['jury_member_id']),
                    'candidate_id' => intval($_POST['candidate_id'])
                ];
                
                if ($assignment_service->process($data)) {
                    add_settings_error(
                        'mt_assignments',
                        'assignment_created',
                        __('Assignment created successfully.', 'mobility-trailblazers'),
                        'success'
                    );
                } else {
                    $errors = $assignment_service->get_errors();
                    foreach ($errors as $error) {
                        add_settings_error('mt_assignments', 'assignment_error', $error);
                    }
                }
                break;
                
            case 'auto_assign':
                $data = [
                    'assignment_type' => 'auto',
                    'candidates_per_jury' => intval($_POST['candidates_per_jury']),
                    'distribution_type' => sanitize_text_field($_POST['distribution_type']),
                    'clear_existing' => !empty($_POST['clear_existing'])
                ];
                
                if ($assignment_service->process($data)) {
                    add_settings_error(
                        'mt_assignments',
                        'auto_assignment_completed',
                        __('Auto-assignment completed successfully.', 'mobility-trailblazers'),
                        'success'
                    );
                } else {
                    $errors = $assignment_service->get_errors();
                    foreach ($errors as $error) {
                        add_settings_error('mt_assignments', 'assignment_error', $error);
                    }
                }
                break;
        }
        
        // Store in transient to display after redirect
        set_transient('settings_errors', get_settings_errors(), 30);
        
        // Redirect to prevent resubmission
        wp_redirect(add_query_arg('settings-updated', 'true', wp_get_referer()));
        wp_die();
    }
    
    /**
     * Render import/export page
     *
     * @return void
     */
    public function render_import_export_page() {
        include MT_PLUGIN_DIR . 'templates/admin/import-export.php';
    }
    
    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page() {
        include MT_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Render Debug Center page
     *
     * @return void
     */
    public function render_debug_center_page() {
        // Load required classes
        if (!class_exists('\MobilityTrailblazers\Admin\MT_Debug_Manager')) {
            require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-debug-manager.php';
        }
        if (!class_exists('\MobilityTrailblazers\Services\MT_Diagnostic_Service')) {
            require_once MT_PLUGIN_DIR . 'includes/services/class-mt-diagnostic-service.php';
        }
        if (!class_exists('\MobilityTrailblazers\Admin\MT_Maintenance_Tools')) {
            require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-maintenance-tools.php';
        }
        
        $template_file = MT_PLUGIN_DIR . 'templates/admin/debug-center.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Debug Center template file not found.', 'mobility-trailblazers') . '</p></div>';
        }
    }
    
    /**
     * Render tools page (deprecated - redirects to Debug Center)
     *
     * @deprecated 2.3.0 Use render_debug_center_page() instead
     * @return void
     */
    public function render_tools_page() {
        wp_redirect(admin_url('admin.php?page=mt-debug-center&tab=tools'));
        wp_die();
    }
    
    /**
     * Render diagnostics page (deprecated - redirects to Debug Center)
     *
     * @deprecated 2.3.0 Use render_debug_center_page() instead
     * @return void
     */
    public function render_diagnostics_page() {
        // Deprecated - redirect to debug center with diagnostics tab
        wp_redirect(admin_url('admin.php?page=mt-debug-center&tab=diagnostics'));
        exit;
    }
    
    /**
     * Render audit log page
     *
     * @return void
     */
    public function render_audit_log_page() {
        // Get filter parameters
        $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 20;
        $per_page = min(100, max(10, $per_page)); // Between 10 and 100
        
        $args = [
            'page' => $page,
            'per_page' => $per_page,
            'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at',
            'order' => isset($_GET['order']) ? strtoupper(sanitize_text_field($_GET['order'])) : 'DESC'
        ];
        
        // Apply filters
        if (!empty($_GET['user_id'])) {
            $args['user_id'] = absint($_GET['user_id']);
        }
        
        if (!empty($_GET['action_filter'])) {
            $args['action'] = sanitize_text_field($_GET['action_filter']);
        }
        
        if (!empty($_GET['object_type'])) {
            $args['object_type'] = sanitize_text_field($_GET['object_type']);
        }
        
        if (!empty($_GET['date_from'])) {
            $args['date_from'] = sanitize_text_field($_GET['date_from']) . ' 00:00:00';
        }
        
        if (!empty($_GET['date_to'])) {
            $args['date_to'] = sanitize_text_field($_GET['date_to']) . ' 23:59:59';
        }
        
        // Get audit log data
        $audit_repo = new \MobilityTrailblazers\Repositories\MT_Audit_Log_Repository();
        $logs_data = $audit_repo->get_logs($args);
        $unique_actions = $audit_repo->get_unique_actions();
        $unique_object_types = $audit_repo->get_unique_object_types();
        
        // Get users for filter dropdown
        $users = get_users([
            'fields' => ['ID', 'display_name'],
            'number' => 100,
            'orderby' => 'display_name'
        ]);
        
        // Include template
        $template_file = MT_PLUGIN_DIR . 'templates/admin/audit-log.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Audit log template file not found.', 'mobility-trailblazers') . '</p></div>';
        }
    }
    
    
    /**
     * Initialize Elementor Templates tool
     *
     * @return void
     */
    private function init_elementor_templates() {
        if (!file_exists(MT_PLUGIN_DIR . 'includes/admin/tools/class-mt-elementor-templates.php')) {
            return;
        }
        
        require_once MT_PLUGIN_DIR . 'includes/admin/tools/class-mt-elementor-templates.php';
    }
    
    /**
     * Display admin notices
     *
     * @return void
     */
    public function display_admin_notices() {
        // Display settings errors/updates
        if (get_transient('settings_errors')) {
            $errors = get_transient('settings_errors');
            delete_transient('settings_errors');
            
            foreach ($errors as $error) {
                $class = $error['type'] === 'success' ? 'notice-success' : 'notice-error';
                printf(
                    '<div class="notice %s is-dismissible"><p>%s</p></div>',
                    esc_attr($class),
                    esc_html($error['message'])
                );
            }
        }
    }
    
    /**
     * Handle activation redirect
     *
     * @return void
     */
    public function handle_activation_redirect() {
        if (get_transient('mt_activation_redirect')) {
            delete_transient('mt_activation_redirect');
            
            if (!isset($_GET['activate-multi'])) {
                wp_redirect(admin_url('admin.php?page=mobility-trailblazers'));
                wp_die();
            }
        }
    }
    
    /**
     * Add dashboard widgets
     *
     * @return void
     */
    public function add_dashboard_widgets() {
        if (current_user_can('mt_view_all_evaluations')) {
            wp_add_dashboard_widget(
                'mt_evaluation_summary',
                __('Mobility Trailblazers - Evaluation Summary', 'mobility-trailblazers'),
                [$this, 'render_dashboard_widget']
            );
        }
    }
    
    /**
     * Render dashboard widget
     *
     * @return void
     */
    public function render_dashboard_widget() {
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $stats = $evaluation_repo->get_statistics();

        // Error monitor removed - no longer needed
        $error_summary = [];

        $template_file = MT_PLUGIN_DIR . 'templates/admin/dashboard-widget.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<p>' . esc_html__('Dashboard widget template not found.', 'mobility-trailblazers') . '</p>';
        }
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Get current screen for more accurate detection
        $screen = get_current_screen();
        
        // Check if we're on the candidates list page specifically
        $is_candidates_page = ($screen && $screen->post_type === 'mt_candidate' && $screen->base === 'edit');
        
        // Check if we're on any mobility trailblazers admin page
        $is_mt_admin_page = (
            $is_candidates_page ||
            strpos($hook, 'mobility-trailblazers') !== false ||
            strpos($hook, 'mt-') !== false ||
            (isset($_GET['page']) && strpos($_GET['page'], 'mt-') === 0)
        );
        
        // Only on our plugin pages
        if (!$is_mt_admin_page) {
            return;
        }
        
        // Enqueue admin CSS and JS
        wp_enqueue_style(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/css/admin.css',
            [],
            MT_VERSION
        );
        
        wp_enqueue_script(
            'mt-admin',
            MT_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            MT_VERSION,
            true
        );
        
        // Enqueue Evaluations page assets
        if (isset($_GET['page']) && $_GET['page'] === 'mt-evaluations') {
            wp_enqueue_style(
                'mt-evaluations-admin',
                MT_PLUGIN_URL . 'assets/css/mt-evaluations-admin.css',
                ['mt-admin'],
                MT_VERSION
            );
            
            wp_enqueue_script(
                'mt-evaluations-admin',
                MT_PLUGIN_URL . 'assets/js/mt-evaluations-admin.js',
                ['jquery', 'mt-admin'],
                MT_VERSION,
                true
            );
            
            // Localize script for evaluations
            wp_localize_script('mt-evaluations-admin', 'mt_evaluations_vars', [
                'nonce' => wp_create_nonce('mt_admin_nonce'),
                'ajax_url' => admin_url('admin-ajax.php')
            ]);
            
            wp_localize_script('mt-evaluations-admin', 'mt_evaluations_i18n', [
                'loading' => __('Loading...', 'mobility-trailblazers'),
                'close' => __('Close', 'mobility-trailblazers'),
                'delete' => __('Delete Evaluation', 'mobility-trailblazers'),
                'confirm_delete' => __('Are you sure you want to delete this evaluation?', 'mobility-trailblazers'),
                'confirm_bulk_delete' => __('Are you sure you want to delete the selected evaluations?', 'mobility-trailblazers'),
                'no_selection' => __('Please select at least one evaluation.', 'mobility-trailblazers'),
                'evaluation_details' => __('Evaluation Details', 'mobility-trailblazers'),
                'jury_member' => __('Jury Member', 'mobility-trailblazers'),
                'candidate' => __('Candidate', 'mobility-trailblazers'),
                'organization' => __('Organization', 'mobility-trailblazers'),
                'categories' => __('Categories', 'mobility-trailblazers'),
                'status' => __('Status', 'mobility-trailblazers'),
                'created' => __('Created', 'mobility-trailblazers'),
                'updated' => __('Last Updated', 'mobility-trailblazers'),
                'scores' => __('Evaluation Scores', 'mobility-trailblazers'),
                'total_score' => __('Total Score', 'mobility-trailblazers'),
                'average_score' => __('Average', 'mobility-trailblazers'),
                'comments' => __('Comments', 'mobility-trailblazers'),
                'total_score_label' => __('Total Score:', 'mobility-trailblazers'),
                'average_score_label' => __('Average Score:', 'mobility-trailblazers'),
                'evaluation_criteria' => __('Evaluation Criteria', 'mobility-trailblazers')
            ]);
        }
        
        // Enqueue Debug Center assets if on Debug Center page and not in production
        if (isset($_GET['page']) && $_GET['page'] === 'mt-debug-center' && (defined('WP_DEBUG') && WP_DEBUG)) {
            // Debug Center styles are now included in admin.css
            
            wp_enqueue_script(
                'mt-debug-center',
                MT_PLUGIN_URL . 'assets/js/debug-center.js',
                ['jquery', 'mt-admin'],
                MT_VERSION,
                true
            );
            
            // Localize script for Debug Center
            wp_localize_script('mt-debug-center', 'mt_debug', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_debug_nonce'),
                'i18n' => [
                    'running' => __('Running...', 'mobility-trailblazers'),
                    'diagnostic_complete' => __('Diagnostic complete', 'mobility-trailblazers'),
                    'diagnostic_failed' => __('Diagnostic failed', 'mobility-trailblazers'),
                    'network_error' => __('Network error occurred', 'mobility-trailblazers'),
                    'export_complete' => __('Export complete', 'mobility-trailblazers'),
                    'export_failed' => __('Export failed', 'mobility-trailblazers'),
                    'script_output' => __('Script Output', 'mobility-trailblazers'),
                    'script_complete' => __('Script executed', 'mobility-trailblazers'),
                    'script_failed' => __('Script failed', 'mobility-trailblazers'),
                    'errors_occurred' => __('Errors occurred', 'mobility-trailblazers'),
                    'close' => __('Close', 'mobility-trailblazers'),
                    'confirm_dangerous' => __('This is a dangerous operation. Are you sure?', 'mobility-trailblazers'),
                    'operation_complete' => __('Operation complete', 'mobility-trailblazers'),
                    'operation_failed' => __('Operation failed', 'mobility-trailblazers'),
                    'enter_password' => __('Enter your admin password:', 'mobility-trailblazers'),
                    'confirm_clear_logs' => __('Are you sure you want to clear the logs?', 'mobility-trailblazers'),
                    'logs_cleared' => __('Logs cleared', 'mobility-trailblazers'),
                    'clear_failed' => __('Failed to clear logs', 'mobility-trailblazers'),
                    'copied' => __('Copied to clipboard', 'mobility-trailblazers'),
                    'dismiss' => __('Dismiss', 'mobility-trailblazers')
                ]
            ]);
        }
        
        // Always localize script for AJAX and internationalization on our pages
        wp_localize_script('mt-admin', 'mt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_admin_nonce'),
            'admin_url' => admin_url(),
            'plugin_url' => MT_PLUGIN_URL,
            'current_page' => isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '',
            'i18n' => array(
                'confirm_remove_assignment' => __('Are you sure you want to remove this assignment?', 'mobility-trailblazers'),
                'assignment_removed' => __('Assignment removed successfully.', 'mobility-trailblazers'),
                'error_occurred' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
                'no_assignments' => __('No assignments yet', 'mobility-trailblazers'),
                'processing' => __('Processing...', 'mobility-trailblazers'),
                'select_jury_and_candidates' => __('Please select a jury member and at least one candidate.', 'mobility-trailblazers'),
                'assignments_created' => __('Assignments created successfully.', 'mobility-trailblazers'),
                'assign_selected' => __('Assign Selected', 'mobility-trailblazers'),
                'confirm_clear_all' => __('Are you sure you want to clear ALL assignments? This cannot be undone.', 'mobility-trailblazers'),
                'confirm_clear_all_second' => __('This will remove ALL jury assignments. Are you absolutely sure?', 'mobility-trailblazers'),
                'clearing' => __('Clearing...', 'mobility-trailblazers'),
                'clear_all' => __('Clear All', 'mobility-trailblazers'),
                'all_assignments_cleared' => __('All assignments have been cleared.', 'mobility-trailblazers'),
                'export_started' => __('Export started. Download will begin shortly.', 'mobility-trailblazers'),
                'select_bulk_action' => __('Please select a bulk action', 'mobility-trailblazers'),
                'select_assignments' => __('Please select at least one assignment', 'mobility-trailblazers'),
                'remove' => __('Remove', 'mobility-trailblazers'),
                'error' => __('Error', 'mobility-trailblazers'),
                'select_jury_candidates' => __('Please select a jury member and at least one candidate.', 'mobility-trailblazers'),
                'assignments_cleared' => __('All assignments have been cleared.', 'mobility-trailblazers'),
            )
        ));
        
        // Chart.js for statistics
        if (in_array($hook, ['toplevel_page_mobility-trailblazers', 'mobility-trailblazers_page_mt-evaluations'])) {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [],
                '3.9.1',
                true
            );
        }
        
        // WordPress media scripts and settings admin JS for settings page
        if ($hook === 'mobility-trailblazers_page_mt-settings') {
            wp_enqueue_media();
            
            // Enqueue settings admin script
            wp_enqueue_script(
                'mt-settings-admin',
                MT_PLUGIN_URL . 'assets/js/mt-settings-admin.js',
                ['jquery', 'wp-media-utils'],
                MT_VERSION,
                true
            );
        }
        
        // Candidate import script for candidates list page
        if ($is_candidates_page) {
            wp_enqueue_script(
                'mt-candidate-import',
                MT_PLUGIN_URL . 'assets/js/candidate-import.js',
                ['jquery'],
                MT_VERSION,
                true
            );
            
            // Localize script for import functionality
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
                    'error_details' => __('Error details:', 'mobility-trailblazers'),
                    'no_file_selected' => __('No file selected', 'mobility-trailblazers'),
                    'confirm_import' => __('Are you sure you want to import this CSV file?', 'mobility-trailblazers')
                ]
            ]);
        }
    }
    
    /**
     * Sanitize general settings
     *
     * @param array $input Raw input
     * @return array
     */
    public function sanitize_general_settings($input) {
        $sanitized = [];
        
        $sanitized['enable_jury_system'] = !empty($input['enable_jury_system']);
        $sanitized['candidates_per_jury'] = intval($input['candidates_per_jury'] ?? 5);
        $sanitized['evaluation_deadline'] = sanitize_text_field($input['evaluation_deadline'] ?? '');
        $sanitized['show_results_publicly'] = !empty($input['show_results_publicly']);
        
        return $sanitized;
    }
    

    
    /**
     * Sanitize criteria weights
     *
     * @param array $input Raw input
     * @return array
     */
    public function sanitize_criteria_weights($input) {
        $sanitized = [];
        
        $criteria = ['courage', 'innovation', 'implementation', 'relevance', 'visibility'];
        
        foreach ($criteria as $criterion) {
            $sanitized[$criterion] = isset($input[$criterion]) ? floatval($input[$criterion]) : 1;
            $sanitized[$criterion] = max(0.1, min(10, $sanitized[$criterion])); // Between 0.1 and 10
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize dashboard settings
     *
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_dashboard_settings($input) {
        $sanitized = [];
        
        // Header style
        $sanitized['header_style'] = in_array($input['header_style'], ['gradient', 'solid', 'image']) 
            ? $input['header_style'] : 'gradient';
        
        // Colors
        $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']) ?: '#667eea';
        $sanitized['secondary_color'] = sanitize_hex_color($input['secondary_color']) ?: '#764ba2';
        
        // Progress bar style
        $sanitized['progress_bar_style'] = in_array($input['progress_bar_style'], ['rounded', 'square', 'striped']) 
            ? $input['progress_bar_style'] : 'rounded';
        
        // Boolean options
        $sanitized['show_welcome_message'] = !empty($input['show_welcome_message']) ? 1 : 0;
        $sanitized['show_progress_bar'] = !empty($input['show_progress_bar']) ? 1 : 0;
        $sanitized['show_stats_cards'] = !empty($input['show_stats_cards']) ? 1 : 0;
        $sanitized['show_search_filter'] = !empty($input['show_search_filter']) ? 1 : 0;
        
        // Layout
        $sanitized['card_layout'] = in_array($input['card_layout'], ['grid', 'list', 'compact']) 
            ? $input['card_layout'] : 'grid';
        
        // Text fields
        $sanitized['intro_text'] = wp_kses_post($input['intro_text']);
        $sanitized['header_image_url'] = esc_url_raw($input['header_image_url']);
        
        return $sanitized;
    }

}
