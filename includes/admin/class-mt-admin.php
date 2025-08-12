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

        // Profile Migration and other admin-only menus
        if (current_user_can('manage_options')) {
            
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
        // Include the import template
        $template_file = MT_PLUGIN_DIR . 'templates/admin/import-profiles.php';
        if (file_exists($template_file)) {
            include $template_file;
        } else {
            // Fallback to debug script if template doesn't exist
            $debug_file = MT_PLUGIN_DIR . 'debug/import-profiles.php';
            if (file_exists($debug_file)) {
                include $debug_file;
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Import profiles template not found.', 'mobility-trailblazers') . '</p></div>';
            }
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

}