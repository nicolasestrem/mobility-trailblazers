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
        
        // Email settings
        register_setting('mt_email_settings', 'mt_email_settings', [
            'sanitize_callback' => [$this, 'sanitize_email_settings']
        ]);
        
        // Criteria weights
        register_setting('mt_criteria_settings', 'mt_criteria_weights', [
            'sanitize_callback' => [$this, 'sanitize_criteria_weights']
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
        
        include MT_PLUGIN_DIR . 'templates/admin/dashboard.php';
    }
    
    /**
     * Render evaluations page
     *
     * @return void
     */
    public function render_evaluations_page() {
        // Check if viewing single evaluation
        if (isset($_GET['evaluation_id'])) {
            $this->render_single_evaluation();
            return;
        }
        
        // Get evaluations
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
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
    private function render_single_evaluation() {
        $evaluation_id = intval($_GET['evaluation_id']);
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
        
        include MT_PLUGIN_DIR . 'templates/admin/dashboard-widget.php';
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
     * Sanitize email settings
     *
     * @param array $input Raw input
     * @return array
     */
    public function sanitize_email_settings($input) {
        $sanitized = [];
        
        $sanitized['enable_notifications'] = !empty($input['enable_notifications']);
        $sanitized['admin_email'] = sanitize_email($input['admin_email'] ?? '');
        $sanitized['evaluation_reminder'] = !empty($input['evaluation_reminder']);
        $sanitized['reminder_days_before'] = intval($input['reminder_days_before'] ?? 3);
        
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
} 