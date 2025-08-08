<?php
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

        // Add AJAX handlers for error monitor
        add_action('wp_ajax_mt_clear_error_logs', [$this, 'clear_error_logs']);
        add_action('wp_ajax_mt_export_error_logs', [$this, 'export_error_logs']);
        add_action('wp_ajax_mt_get_error_stats', [$this, 'get_error_stats']);

        // Schedule cleanup of old logs
        if (!wp_next_scheduled('mt_cleanup_error_logs')) {
            wp_schedule_event(time(), 'daily', 'mt_cleanup_error_logs');
        }
        add_action('mt_cleanup_error_logs', [$this, 'cleanup_old_logs']);
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
        
        // Candidates submenu
        add_submenu_page(
            'mobility-trailblazers',
            __('Candidates', 'mobility-trailblazers'),
            __('Candidates', 'mobility-trailblazers'),
            'edit_posts',
            'mt-candidates',
            [$this, 'render_candidates_page']
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
        
        // Diagnostics submenu (for admins and jury admins)
        if (current_user_can('manage_options') || current_user_can('mt_jury_admin')) {
            add_submenu_page(
                'mobility-trailblazers',
                __('Diagnostics', 'mobility-trailblazers'),
                __('Diagnostics', 'mobility-trailblazers'),
                'manage_options',
                'mt-diagnostics',
                [$this, 'render_diagnostics_page']
            );
        }

        // Error Monitor submenu (for admins only)
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'mobility-trailblazers',
                __('Error Monitor', 'mobility-trailblazers'),
                __('Error Monitor', 'mobility-trailblazers'),
                'manage_options',
                'mt-error-monitor',
                [$this, 'render_error_monitor_page']
            );
            
            // Profile Migration submenu
            add_submenu_page(
                'mobility-trailblazers',
                __('Migrate Profiles', 'mobility-trailblazers'),
                __('Migrate Profiles', 'mobility-trailblazers'),
                'manage_options',
                'mt-migrate-profiles',
                [$this, 'render_migrate_profiles_page']
            );
            
            // Test Profile System (temporary)
            add_submenu_page(
                'mobility-trailblazers',
                __('Test Profile System', 'mobility-trailblazers'),
                __('Test Profile System', 'mobility-trailblazers'),
                'manage_options',
                'mt-test-profiles',
                [$this, 'render_test_profiles_page']
            );
            
            // Generate Sample Profiles
            add_submenu_page(
                'mobility-trailblazers',
                __('Generate Samples', 'mobility-trailblazers'),
                __('Generate Samples', 'mobility-trailblazers'),
                'manage_options',
                'mt-generate-samples',
                [$this, 'render_generate_samples_page']
            );
            
            // Import Profiles
            add_submenu_page(
                'mobility-trailblazers',
                __('Import Profiles', 'mobility-trailblazers'),
                __('Import Profiles', 'mobility-trailblazers'),
                'manage_options',
                'mt-import-profiles',
                [$this, 'render_import_profiles_page']
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
     * Render candidates page
     *
     * @return void
     */
    public function render_candidates_page() {
        $template_file = MT_PLUGIN_DIR . 'templates/admin/candidates.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Candidates template file not found.', 'mobility-trailblazers') . '</p></div>';
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
        exit;
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
     * Render diagnostics page
     *
     * @return void
     */
    public function render_diagnostics_page() {
        include MT_PLUGIN_DIR . 'templates/admin/diagnostics.php';
    }

    /**
     * Render migrate profiles page
     *
     * @return void
     */
    public function render_migrate_profiles_page() {
        // Include the migration script
        include MT_PLUGIN_DIR . 'debug/migrate-candidate-profiles.php';
    }
    
    /**
     * Render test profiles page
     *
     * @return void
     */
    public function render_test_profiles_page() {
        // Include the test script
        include MT_PLUGIN_DIR . 'debug/test-profile-system.php';
    }
    
    /**
     * Render generate samples page
     *
     * @return void
     */
    public function render_generate_samples_page() {
        // Include the sample generator script
        include MT_PLUGIN_DIR . 'debug/generate-sample-profiles.php';
    }
    
    /**
     * Render import profiles page
     *
     * @return void
     */
    public function render_import_profiles_page() {
        // Include the import script
        include MT_PLUGIN_DIR . 'debug/import-profiles.php';
    }
    
    /**
     * Render error monitor page
     *
     * @return void
     */
    public function render_error_monitor_page() {
        // Test the logging system (only for testing - remove in production)
        if (isset($_GET['test_error'])) {
            \MobilityTrailblazers\Core\MT_Logger::critical('Test error from admin page', [
                'test' => true,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ]);

            \MobilityTrailblazers\Core\MT_Logger::error('Test regular error', ['test' => true]);
            \MobilityTrailblazers\Core\MT_Logger::warning('Test warning', ['test' => true]);
            \MobilityTrailblazers\Core\MT_Logger::info('Test info message', ['test' => true]);
        }

        // Get error statistics
        $stats = $this->get_error_statistics();

        // Get recent errors
        $recent_errors = \MobilityTrailblazers\Core\MT_Logger::get_recent_errors(50);

        // Get error counts by level
        $error_counts = $this->get_error_counts_by_level();

        $template_file = MT_PLUGIN_DIR . 'templates/admin/error-monitor.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Error monitor template not found.', 'mobility-trailblazers') . '</p></div>';
        }
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
                exit;
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

        // Get error summary
        $error_summary = \MobilityTrailblazers\Admin\MT_Error_Monitor::get_dashboard_summary();

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
        // Only on our plugin pages
        if (strpos($hook, 'mobility-trailblazers') === false && strpos($hook, 'mt-') === false) {
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
        
        // Localize script for AJAX and internationalization
        wp_localize_script('mt-admin', 'mt_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_admin_nonce'),
            'admin_url' => admin_url(),
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
        
        // WordPress media scripts for settings page
        if ($hook === 'mobility-trailblazers_page_mt-settings') {
            wp_enqueue_media();
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

    /**
     * Get error statistics
     *
     * @return array
     */
    private function get_error_statistics() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mt_error_log';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [
                'total_errors' => 0,
                'errors_today' => 0,
                'errors_this_week' => 0,
                'critical_errors' => 0
            ];
        }

        $today = date('Y-m-d');
        $week_ago = date('Y-m-d', strtotime('-7 days'));

        $stats = [
            'total_errors' => $wpdb->get_var("SELECT COUNT(*) FROM $table_name"),
            'errors_today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
                $today
            )),
            'errors_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
                $week_ago
            )),
            'critical_errors' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE level = %s",
                'CRITICAL'
            ))
        ];

        return $stats;
    }

    /**
     * Get error counts by level
     *
     * @return array
     */
    private function get_error_counts_by_level() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mt_error_log';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            return [];
        }

        $results = $wpdb->get_results(
            "SELECT level, COUNT(*) as count FROM $table_name GROUP BY level ORDER BY count DESC"
        );

        $counts = [];
        foreach ($results as $result) {
            $counts[$result->level] = $result->count;
        }

        return $counts;
    }

    /**
     * Clear error logs AJAX handler
     *
     * @return void
     */
    public function clear_error_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mt_admin_nonce')) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'mobility-trailblazers'));
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_error_log';

        $deleted = $wpdb->query("DELETE FROM $table_name");

        if ($deleted !== false) {
            \MobilityTrailblazers\Core\MT_Logger::info('Error logs cleared by administrator', [
                'deleted_count' => $deleted,
                'admin_user_id' => get_current_user_id()
            ]);

            wp_send_json_success([
                'message' => sprintf(__('Cleared %d error logs.', 'mobility-trailblazers'), $deleted),
                'deleted_count' => $deleted
            ]);
        } else {
            wp_send_json_error(__('Failed to clear error logs.', 'mobility-trailblazers'));
        }
    }

    /**
     * Export error logs AJAX handler
     *
     * @return void
     */
    public function export_error_logs() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mt_admin_nonce')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'mobility-trailblazers'));
        }

        $errors = \MobilityTrailblazers\Core\MT_Logger::get_recent_errors(1000); // Get up to 1000 recent errors

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="mt-error-logs-' . date('Y-m-d-H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create CSV output
        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, [
            'ID',
            'Level',
            'Message',
            'Context',
            'User ID',
            'Request URI',
            'User Agent',
            'Created At'
        ]);

        // CSV data
        foreach ($errors as $error) {
            fputcsv($output, [
                $error->id,
                $error->level,
                $error->message,
                $error->context,
                $error->user_id,
                $error->request_uri,
                $error->user_agent,
                $error->created_at
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Get error statistics AJAX handler
     *
     * @return void
     */
    public function get_error_stats() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mt_admin_nonce')) {
            wp_send_json_error(__('Security check failed.', 'mobility-trailblazers'));
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions.', 'mobility-trailblazers'));
            return;
        }

        $stats = $this->get_error_statistics();
        $counts = $this->get_error_counts_by_level();

        wp_send_json_success([
            'stats' => $stats,
            'counts_by_level' => $counts
        ]);
    }

    /**
     * Cleanup old error logs
     *
     * @return void
     */
    public function cleanup_old_logs() {
        $deleted = \MobilityTrailblazers\Core\MT_Logger::cleanup_old_logs(30); // Keep logs for 30 days

        if ($deleted > 0) {
            \MobilityTrailblazers\Core\MT_Logger::info('Automatic error log cleanup completed', [
                'deleted_count' => $deleted
            ]);
        }
    }
}