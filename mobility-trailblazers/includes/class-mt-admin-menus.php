<?php
/**
 * Admin menus handler class
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Admin_Menus
 * Handles admin menu registration and page rendering
 */
class MT_Admin_Menus {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add admin menus
        add_action('admin_menu', array($this, 'add_admin_menus'));
        
        // Add jury dashboard to admin bar
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }
    
    /**
     * Add admin menus
     */
    public function add_admin_menus() {
        // Main menu
        add_menu_page(
            __('MT Award System', 'mobility-trailblazers'),
            __('MT Award System', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-award-system',
            array($this, 'render_dashboard_page'),
            'dashicons-awards',
            25
        );
        
        // Dashboard submenu (rename the first item)
        add_submenu_page(
            'mt-award-system',
            __('Dashboard', 'mobility-trailblazers'),
            __('Dashboard', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-award-system',
            array($this, 'render_dashboard_page')
        );
        
        // Assignment Management
        add_submenu_page(
            'mt-award-system',
            __('Assignment Management', 'mobility-trailblazers'),
            __('Assignment Management', 'mobility-trailblazers'),
            'mt_manage_assignments',
            'mt-assignment-management',
            array($this, 'render_assignment_page')
        );
        
        // Voting Results
        add_submenu_page(
            'mt-award-system',
            __('Voting Results', 'mobility-trailblazers'),
            __('Voting Results', 'mobility-trailblazers'),
            'mt_view_all_evaluations',
            'mt-voting-results',
            array($this, 'render_voting_results_page')
        );
        
        // Vote Reset
        add_submenu_page(
            'mt-award-system',
            __('Vote Reset', 'mobility-trailblazers'),
            __('Vote Reset', 'mobility-trailblazers'),
            'mt_reset_votes',
            'mt-vote-reset',
            array($this, 'render_vote_reset_page')
        );
        
        // Import/Export
        add_submenu_page(
            'mt-award-system',
            __('Import/Export', 'mobility-trailblazers'),
            __('Import/Export', 'mobility-trailblazers'),
            'mt_export_data',
            'mt-import-export',
            array($this, 'render_import_export_page')
        );
        
        // Settings
        add_submenu_page(
            'mt-award-system',
            __('Settings', 'mobility-trailblazers'),
            __('Settings', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-settings',
            array($this, 'render_settings_page')
        );
        
        // Diagnostic
        add_submenu_page(
            'mt-award-system',
            __('Diagnostic', 'mobility-trailblazers'),
            __('Diagnostic', 'mobility-trailblazers'),
            'mt_manage_awards',
            'mt-diagnostic',
            array($this, 'render_diagnostic_page')
        );
        
        // Fix Capabilities (temporary, can be removed after fixing)
        add_submenu_page(
            'mt-award-system',
            __('Fix Capabilities', 'mobility-trailblazers'),
            __('Fix Capabilities', 'mobility-trailblazers'),
            'manage_options',
            'mt-fix-capabilities',
            array($this, 'render_fix_capabilities_page')
        );
        
        // Jury Dashboard (for jury members)
        if (mt_is_jury_member()) {
            add_menu_page(
                __('Jury Dashboard', 'mobility-trailblazers'),
                __('Jury Dashboard', 'mobility-trailblazers'),
                'mt_access_jury_dashboard',
                'mt-jury-dashboard',
                array($this, 'render_jury_dashboard_page'),
                'dashicons-groups',
                26
            );
        }
    }
    
    /**
     * Add admin bar menu
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar object
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!is_admin_bar_showing()) {
            return;
        }
        
        // Add jury dashboard link for jury members
        if (mt_is_jury_member()) {
            $wp_admin_bar->add_node(array(
                'id' => 'mt-jury-dashboard',
                'title' => __('Jury Dashboard', 'mobility-trailblazers'),
                'href' => admin_url('admin.php?page=mt-jury-dashboard'),
                'meta' => array(
                    'class' => 'mt-admin-bar-item',
                ),
            ));
        }
        
        // Add quick links for admins
        if (current_user_can('mt_manage_awards')) {
            $wp_admin_bar->add_node(array(
                'id' => 'mt-award-system',
                'title' => __('MT Award System', 'mobility-trailblazers'),
                'href' => admin_url('admin.php?page=mt-award-system'),
            ));
            
            $wp_admin_bar->add_node(array(
                'parent' => 'mt-award-system',
                'id' => 'mt-assignments',
                'title' => __('Assignments', 'mobility-trailblazers'),
                'href' => admin_url('admin.php?page=mt-assignment-management'),
            ));
            
            $wp_admin_bar->add_node(array(
                'parent' => 'mt-award-system',
                'id' => 'mt-results',
                'title' => __('Results', 'mobility-trailblazers'),
                'href' => admin_url('admin.php?page=mt-voting-results'),
            ));
        }
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        // Check permissions
        if (!current_user_can('mt_manage_awards')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Render assignment management page
     */
    public function render_assignment_page() {
        // Check permissions
        if (!current_user_can('mt_manage_assignments')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/assignment-template.php';
    }
    
    /**
     * Render voting results page
     */
    public function render_voting_results_page() {
        // Check permissions
        if (!current_user_can('mt_view_all_evaluations')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/voting-results.php';
    }
    
    /**
     * Render vote reset page
     */
    public function render_vote_reset_page() {
        // Check permissions
        if (!current_user_can('mt_reset_votes')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/vote-reset.php';
    }
    
    /**
     * Render import/export page
     */
    public function render_import_export_page() {
        // Check permissions
        if (!current_user_can('mt_export_data')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/import-export.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check permissions
        if (!current_user_can('mt_manage_awards')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Handle form submission
        if (isset($_POST['mt_save_settings']) && wp_verify_nonce($_POST['mt_settings_nonce'], 'mt_save_settings')) {
            $this->save_settings();
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Render diagnostic page
     */
    public function render_diagnostic_page() {
        // Check permissions
        if (!current_user_can('mt_manage_awards')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/diagnostic.php';
    }
    
    /**
     * Render jury dashboard page
     */
    public function render_jury_dashboard_page() {
        // Check permissions
        if (!current_user_can('mt_access_jury_dashboard')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Get current user's jury member
        $jury_member = mt_get_jury_member_by_user_id(get_current_user_id());
        
        if (!$jury_member) {
            wp_die(__('Jury member profile not found.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/jury-dashboard.php';
    }
    
    /**
     * Render fix capabilities page
     */
    public function render_fix_capabilities_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Include template
        include MT_PLUGIN_DIR . 'admin/views/fix-capabilities.php';
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        // General settings
        if (isset($_POST['mt_current_award_year'])) {
            update_option('mt_current_award_year', sanitize_text_field($_POST['mt_current_award_year']));
        }
        
        if (isset($_POST['mt_current_phase'])) {
            update_option('mt_current_phase', sanitize_text_field($_POST['mt_current_phase']));
        }
        
        update_option('mt_public_voting_enabled', isset($_POST['mt_public_voting_enabled']) ? 1 : 0);
        update_option('mt_registration_open', isset($_POST['mt_registration_open']) ? 1 : 0);
        
        // Evaluation settings
        if (isset($_POST['mt_min_evaluations_required'])) {
            update_option('mt_min_evaluations_required', absint($_POST['mt_min_evaluations_required']));
        }
        
        if (isset($_POST['mt_evaluation_deadline'])) {
            update_option('mt_evaluation_deadline', sanitize_text_field($_POST['mt_evaluation_deadline']));
        }
        
        update_option('mt_auto_reminders_enabled', isset($_POST['mt_auto_reminders_enabled']) ? 1 : 0);
        
        // Email settings
        if (isset($_POST['mt_email_from_name'])) {
            update_option('mt_email_from_name', sanitize_text_field($_POST['mt_email_from_name']));
        }
        
        if (isset($_POST['mt_email_from_address'])) {
            update_option('mt_email_from_address', sanitize_email($_POST['mt_email_from_address']));
        }
        
        // Display settings
        if (isset($_POST['mt_candidates_per_page'])) {
            update_option('mt_candidates_per_page', absint($_POST['mt_candidates_per_page']));
        }
        
        if (isset($_POST['mt_date_format'])) {
            update_option('mt_date_format', sanitize_text_field($_POST['mt_date_format']));
        }
        
        // Add admin notice
        add_settings_error(
            'mt_settings',
            'settings_updated',
            __('Settings saved successfully.', 'mobility-trailblazers'),
            'updated'
        );
    }
} 