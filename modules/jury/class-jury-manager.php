<?php
/**
 * Enhanced Jury Management Dashboard
 * File: /wp-content/plugins/mobility-trailblazers/admin/class-jury-management-admin.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class MT_Jury_Management_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add hooks for admin functionality
        add_action('admin_menu', array($this, 'add_jury_management_menu'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_mt_get_jury_list', array($this, 'ajax_get_jury_list'));
        add_action('wp_ajax_mt_create_jury_member', array($this, 'ajax_create_jury_member'));
        add_action('wp_ajax_mt_update_jury_member', array($this, 'ajax_update_jury_member'));
        add_action('wp_ajax_mt_delete_jury_member', array($this, 'ajax_delete_jury_member'));
        add_action('wp_ajax_mt_bulk_jury_action', array($this, 'ajax_bulk_jury_action'));
        add_action('wp_ajax_mt_get_jury_stats', array($this, 'ajax_get_jury_stats'));
        add_action('wp_ajax_mt_export_jury_data', array($this, 'ajax_export_jury_data'));
        add_action('wp_ajax_mt_send_jury_invitation', array($this, 'ajax_send_jury_invitation'));
        add_action('wp_ajax_mt_get_jury_activity', array($this, 'ajax_get_jury_activity'));
    }
    
    /**
     * Add jury management menu item
     */
    public function add_jury_management_menu() {
        // Only for administrators and mt_award_admin
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            return;
        }
        
        add_submenu_page(
            'mt-award-system',
            __('Jury Management', 'mobility-trailblazers'),
            __('Jury Management', 'mobility-trailblazers'),
            'manage_options',
            'mt-jury-management',
            array($this, 'render_jury_management_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'mt-award-system_page_mt-jury-management') {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'mt-jury-management-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/jury-management-admin.css',
            array(),
            MT_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'mt-jury-management-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/jury-management-admin.js',
            array('jquery', 'wp-api', 'jquery-ui-dialog'),
            MT_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('mt-jury-management-admin', 'mt_jury_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_jury_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this jury member?', 'mobility-trailblazers'),
                'confirm_bulk_delete' => __('Are you sure you want to delete selected jury members?', 'mobility-trailblazers'),
                'saving' => __('Saving...', 'mobility-trailblazers'),
                'saved' => __('Saved successfully!', 'mobility-trailblazers'),
                'error' => __('An error occurred. Please try again.', 'mobility-trailblazers'),
            )
        ));
        
        // Include jQuery UI for dialogs
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }
    
    /**
     * Render the jury management page
     */
    public function render_jury_management_page() {
        ?>
        <div class="wrap mt-jury-management">
            <h1 class="wp-heading-inline"><?php _e('Jury Management', 'mobility-trailblazers'); ?></h1>
            <a href="#" class="page-title-action" id="mt-add-jury-member"><?php _e('Add New Jury Member', 'mobility-trailblazers'); ?></a>
            
            <hr class="wp-header-end">
            
            <!-- Statistics Dashboard -->
            <div class="mt-jury-stats-container">
                <h2><?php _e('Jury Statistics', 'mobility-trailblazers'); ?></h2>
                <div class="mt-stats-grid" id="mt-jury-stats">
                    <div class="stat-box">
                        <span class="stat-number">-</span>
                        <span class="stat-label"><?php _e('Total Jury Members', 'mobility-trailblazers'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number">-</span>
                        <span class="stat-label"><?php _e('Active Members', 'mobility-trailblazers'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number">-</span>
                        <span class="stat-label"><?php _e('Evaluations Completed', 'mobility-trailblazers'); ?></span>
                    </div>
                    <div class="stat-box">
                        <span class="stat-number">-</span>
                        <span class="stat-label"><?php _e('Average Completion Rate', 'mobility-trailblazers'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Filter and Actions Bar -->
            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select id="bulk-action-selector">
                        <option value=""><?php _e('Bulk Actions', 'mobility-trailblazers'); ?></option>
                        <option value="delete"><?php _e('Delete', 'mobility-trailblazers'); ?></option>
                        <option value="activate"><?php _e('Activate', 'mobility-trailblazers'); ?></option>
                        <option value="deactivate"><?php _e('Deactivate', 'mobility-trailblazers'); ?></option>
                        <option value="send-reminder"><?php _e('Send Reminder', 'mobility-trailblazers'); ?></option>
                    </select>
                    <button class="button action" id="doaction"><?php _e('Apply', 'mobility-trailblazers'); ?></button>
                </div>
                
                <div class="alignleft actions">
                    <select id="filter-status">
                        <option value=""><?php _e('All Statuses', 'mobility-trailblazers'); ?></option>
                        <option value="active"><?php _e('Active', 'mobility-trailblazers'); ?></option>
                        <option value="inactive"><?php _e('Inactive', 'mobility-trailblazers'); ?></option>
                        <option value="pending"><?php _e('Pending', 'mobility-trailblazers'); ?></option>
                    </select>
                    
                    <select id="filter-category">
                        <option value=""><?php _e('All Categories', 'mobility-trailblazers'); ?></option>
                        <option value="infrastructure"><?php _e('Infrastructure/Politics', 'mobility-trailblazers'); ?></option>
                        <option value="startups"><?php _e('Startups/New Makers', 'mobility-trailblazers'); ?></option>
                        <option value="established"><?php _e('Established Companies', 'mobility-trailblazers'); ?></option>
                    </select>
                    
                    <button class="button" id="filter-button"><?php _e('Filter', 'mobility-trailblazers'); ?></button>
                </div>
                
                <div class="alignright">
                    <button class="button" id="export-jury-data">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Data', 'mobility-trailblazers'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Jury Members Table -->
            <table class="wp-list-table widefat fixed striped" id="jury-members-table">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th><?php _e('Name', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Email', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Category', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Assigned', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Completed', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Last Activity', 'mobility-trailblazers'); ?></th>
                        <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                    </tr>
                </thead>
                <tbody id="jury-members-list">
                    <tr>
                        <td colspan="9" class="text-center">
                            <span class="spinner is-active"></span>
                            <?php _e('Loading jury members...', 'mobility-trailblazers'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Add/Edit Jury Member Dialog -->
            <div id="jury-member-dialog" title="<?php _e('Jury Member Details', 'mobility-trailblazers'); ?>" style="display:none;">
                <form id="jury-member-form">
                    <input type="hidden" id="jury-member-id" name="jury_member_id" value="">
                    
                    <div class="form-group">
                        <label for="jury-name"><?php _e('Full Name', 'mobility-trailblazers'); ?> *</label>
                        <input type="text" id="jury-name" name="name" class="widefat" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="jury-email"><?php _e('Email Address', 'mobility-trailblazers'); ?> *</label>
                        <input type="email" id="jury-email" name="email" class="widefat" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="jury-organization"><?php _e('Organization', 'mobility-trailblazers'); ?></label>
                        <input type="text" id="jury-organization" name="organization" class="widefat">
                    </div>
                    
                    <div class="form-group">
                        <label for="jury-position"><?php _e('Position', 'mobility-trailblazers'); ?></label>
                        <input type="text" id="jury-position" name="position" class="widefat">
                    </div>
                    
                    <div class="form-group">
                        <label for="jury-category"><?php _e('Expertise Category', 'mobility-trailblazers'); ?> *</label>
                        <select id="jury-category" name="category" class="widefat" required>
                            <option value=""><?php _e('Select Category', 'mobility-trailblazers'); ?></option>
                            <option value="infrastructure"><?php _e('Infrastructure/Politics/Public', 'mobility-trailblazers'); ?></option>
                            <option value="startups"><?php _e('Startups/New Makers', 'mobility-trailblazers'); ?></option>
                            <option value="established"><?php _e('Established Companies', 'mobility-trailblazers'); ?></option>
                            <option value="general"><?php _e('General/All Categories', 'mobility-trailblazers'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="jury-bio"><?php _e('Biography', 'mobility-trailblazers'); ?></label>
                        <textarea id="jury-bio" name="bio" class="widefat" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="jury-linkedin"><?php _e('LinkedIn Profile', 'mobility-trailblazers'); ?></label>
                        <input type="url" id="jury-linkedin" name="linkedin" class="widefat" placeholder="https://linkedin.com/in/...">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="jury-send-invitation" name="send_invitation" value="1">
                            <?php _e('Send invitation email to jury member', 'mobility-trailblazers'); ?>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="jury-create-user" name="create_user" value="1">
                            <?php _e('Create WordPress user account', 'mobility-trailblazers'); ?>
                        </label>
                    </div>
                </form>
            </div>
            
            <!-- Activity Log -->
            <div class="mt-jury-activity-log">
                <h3><?php _e('Recent Activity', 'mobility-trailblazers'); ?></h3>
                <div id="jury-activity-log" class="activity-log-container">
                    <p class="loading"><?php _e('Loading activity...', 'mobility-trailblazers'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Get jury list
     */
    public function ajax_get_jury_list() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $args = array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        // Apply filters if provided
        if (!empty($_POST['status'])) {
            $args['meta_query'][] = array(
                'key' => '_mt_jury_status',
                'value' => sanitize_text_field($_POST['status']),
                'compare' => '='
            );
        }
        
        if (!empty($_POST['category'])) {
            $args['meta_query'][] = array(
                'key' => '_mt_jury_category',
                'value' => sanitize_text_field($_POST['category']),
                'compare' => '='
            );
        }
        
        $jury_members = get_posts($args);
        $jury_data = array();
        
        foreach ($jury_members as $member) {
            $member_id = $member->ID;
            $user_id = get_post_meta($member_id, '_mt_jury_user_id', true);
            
            // Get assignment and completion data
            $assigned_count = $this->get_assigned_candidates_count($member_id);
            $completed_count = $this->get_completed_evaluations_count($user_id);
            $last_activity = $this->get_last_activity($user_id);
            
            $jury_data[] = array(
                'id' => $member_id,
                'name' => $member->post_title,
                'email' => get_post_meta($member_id, '_mt_jury_email', true),
                'organization' => get_post_meta($member_id, '_mt_jury_organization', true),
                'position' => get_post_meta($member_id, '_mt_jury_position', true),
                'category' => get_post_meta($member_id, '_mt_jury_category', true),
                'status' => get_post_meta($member_id, '_mt_jury_status', true) ?: 'active',
                'assigned' => $assigned_count,
                'completed' => $completed_count,
                'completion_rate' => $assigned_count > 0 ? round(($completed_count / $assigned_count) * 100) : 0,
                'last_activity' => $last_activity,
                'user_id' => $user_id,
                'linkedin' => get_post_meta($member_id, '_mt_jury_linkedin', true),
            );
        }
        
        wp_send_json_success($jury_data);
    }
    
    /**
     * AJAX: Create jury member
     */
    public function ajax_create_jury_member() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $category = sanitize_text_field($_POST['category']);
        
        if (empty($name) || empty($email) || empty($category)) {
            wp_send_json_error(__('Please fill in all required fields', 'mobility-trailblazers'));
        }
        
        // Check if email already exists
        $existing = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_key' => '_mt_jury_email',
            'meta_value' => $email,
            'posts_per_page' => 1
        ));
        
        if (!empty($existing)) {
            wp_send_json_error(__('A jury member with this email already exists', 'mobility-trailblazers'));
        }
        
        // Create jury member post
        $jury_id = wp_insert_post(array(
            'post_title' => $name,
            'post_type' => 'mt_jury',
            'post_status' => 'publish'
        ));
        
        if (is_wp_error($jury_id)) {
            wp_send_json_error(__('Failed to create jury member', 'mobility-trailblazers'));
        }
        
        // Save meta data
        update_post_meta($jury_id, '_mt_jury_email', $email);
        update_post_meta($jury_id, '_mt_jury_category', $category);
        update_post_meta($jury_id, '_mt_jury_status', 'active');
        
        if (!empty($_POST['organization'])) {
            update_post_meta($jury_id, '_mt_jury_organization', sanitize_text_field($_POST['organization']));
        }
        
        if (!empty($_POST['position'])) {
            update_post_meta($jury_id, '_mt_jury_position', sanitize_text_field($_POST['position']));
        }
        
        if (!empty($_POST['bio'])) {
            update_post_meta($jury_id, '_mt_jury_bio', wp_kses_post($_POST['bio']));
        }
        
        if (!empty($_POST['linkedin'])) {
            update_post_meta($jury_id, '_mt_jury_linkedin', esc_url_raw($_POST['linkedin']));
        }
        
        // Create WordPress user if requested
        if (!empty($_POST['create_user']) && $_POST['create_user'] == '1') {
            $user_id = $this->create_jury_user($email, $name);
            if ($user_id) {
                update_post_meta($jury_id, '_mt_jury_user_id', $user_id);
            }
        }
        
        // Send invitation email if requested
        if (!empty($_POST['send_invitation']) && $_POST['send_invitation'] == '1') {
            $this->send_invitation_email($jury_id);
        }
        
        // Log activity
        $this->log_activity('jury_created', array(
            'jury_id' => $jury_id,
            'name' => $name,
            'email' => $email
        ));
        
        wp_send_json_success(array(
            'message' => __('Jury member created successfully', 'mobility-trailblazers'),
            'jury_id' => $jury_id
        ));
    }
    
    /**
     * AJAX: Update jury member
     */
    public function ajax_update_jury_member() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $jury_id = intval($_POST['jury_member_id']);
        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $category = sanitize_text_field($_POST['category']);
        
        if (empty($jury_id) || empty($name) || empty($email) || empty($category)) {
            wp_send_json_error(__('Invalid data provided', 'mobility-trailblazers'));
        }
        
        // Update post title
        wp_update_post(array(
            'ID' => $jury_id,
            'post_title' => $name
        ));
        
        // Update meta data
        update_post_meta($jury_id, '_mt_jury_email', $email);
        update_post_meta($jury_id, '_mt_jury_category', $category);
        
        if (isset($_POST['organization'])) {
            update_post_meta($jury_id, '_mt_jury_organization', sanitize_text_field($_POST['organization']));
        }
        
        if (isset($_POST['position'])) {
            update_post_meta($jury_id, '_mt_jury_position', sanitize_text_field($_POST['position']));
        }
        
        if (isset($_POST['bio'])) {
            update_post_meta($jury_id, '_mt_jury_bio', wp_kses_post($_POST['bio']));
        }
        
        if (isset($_POST['linkedin'])) {
            update_post_meta($jury_id, '_mt_jury_linkedin', esc_url_raw($_POST['linkedin']));
        }
        
        // Log activity
        $this->log_activity('jury_updated', array(
            'jury_id' => $jury_id,
            'name' => $name
        ));
        
        wp_send_json_success(array(
            'message' => __('Jury member updated successfully', 'mobility-trailblazers')
        ));
    }
    
    /**
     * AJAX: Delete jury member
     */
    public function ajax_delete_jury_member() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $jury_id = intval($_POST['jury_id']);
        
        if (empty($jury_id)) {
            wp_send_json_error(__('Invalid jury member ID', 'mobility-trailblazers'));
        }
        
        // Get jury member data before deletion
        $jury_name = get_the_title($jury_id);
        
        // Remove assignments
        $this->remove_jury_assignments($jury_id);
        
        // Delete the post
        $result = wp_delete_post($jury_id, true);
        
        if ($result) {
            // Log activity
            $this->log_activity('jury_deleted', array(
                'jury_id' => $jury_id,
                'name' => $jury_name
            ));
            
            wp_send_json_success(array(
                'message' => __('Jury member deleted successfully', 'mobility-trailblazers')
            ));
        } else {
            wp_send_json_error(__('Failed to delete jury member', 'mobility-trailblazers'));
        }
    }
    
    /**
     * AJAX: Handle bulk actions
     */
    public function ajax_bulk_jury_action() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $action = sanitize_text_field($_POST['action_type']);
        $jury_ids = array_map('intval', $_POST['jury_ids']);
        
        if (empty($action) || empty($jury_ids)) {
            wp_send_json_error(__('Invalid action or selection', 'mobility-trailblazers'));
        }
        
        $success_count = 0;
        $messages = array();
        
        foreach ($jury_ids as $jury_id) {
            switch ($action) {
                case 'delete':
                    $this->remove_jury_assignments($jury_id);
                    if (wp_delete_post($jury_id, true)) {
                        $success_count++;
                    }
                    break;
                    
                case 'activate':
                    update_post_meta($jury_id, '_mt_jury_status', 'active');
                    $success_count++;
                    break;
                    
                case 'deactivate':
                    update_post_meta($jury_id, '_mt_jury_status', 'inactive');
                    $success_count++;
                    break;
                    
                case 'send-reminder':
                    if ($this->send_reminder_email($jury_id)) {
                        $success_count++;
                    }
                    break;
            }
        }
        
        // Log activity
        $this->log_activity('bulk_action', array(
            'action' => $action,
            'count' => $success_count,
            'total' => count($jury_ids)
        ));
        
        $message = sprintf(
            __('%d of %d jury members processed successfully', 'mobility-trailblazers'),
            $success_count,
            count($jury_ids)
        );
        
        wp_send_json_success(array(
            'message' => $message,
            'processed' => $success_count
        ));
    }
    
    /**
     * AJAX: Get jury statistics
     */
    public function ajax_get_jury_stats() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Total jury members
        $total_jury = wp_count_posts('mt_jury')->publish;
        
        // Active jury members
        $active_jury = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_key' => '_mt_jury_status',
            'meta_value' => 'active',
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $active_count = count($active_jury);
        
        // Total evaluations completed
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        $total_evaluations = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        
        // Calculate average completion rate
        $completion_rates = array();
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1
        ));
        
        foreach ($jury_members as $member) {
            $assigned = $this->get_assigned_candidates_count($member->ID);
            $user_id = get_post_meta($member->ID, '_mt_jury_user_id', true);
            $completed = $this->get_completed_evaluations_count($user_id);
            
            if ($assigned > 0) {
                $completion_rates[] = ($completed / $assigned) * 100;
            }
        }
        
        $avg_completion = !empty($completion_rates) ? round(array_sum($completion_rates) / count($completion_rates)) : 0;
        
        wp_send_json_success(array(
            'total_jury' => $total_jury,
            'active_jury' => $active_count,
            'total_evaluations' => $total_evaluations,
            'avg_completion_rate' => $avg_completion . '%'
        ));
    }
    
    /**
     * AJAX: Export jury data
     */
    public function ajax_export_jury_data() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        $csv_data = array();
        $csv_data[] = array(
            'ID',
            'Name',
            'Email',
            'Organization',
            'Position',
            'Category',
            'Status',
            'LinkedIn',
            'Assigned Candidates',
            'Completed Evaluations',
            'Completion Rate',
            'Last Activity'
        );
        
        foreach ($jury_members as $member) {
            $member_id = $member->ID;
            $user_id = get_post_meta($member_id, '_mt_jury_user_id', true);
            $assigned = $this->get_assigned_candidates_count($member_id);
            $completed = $this->get_completed_evaluations_count($user_id);
            $completion_rate = $assigned > 0 ? round(($completed / $assigned) * 100) : 0;
            
            $csv_data[] = array(
                $member_id,
                $member->post_title,
                get_post_meta($member_id, '_mt_jury_email', true),
                get_post_meta($member_id, '_mt_jury_organization', true),
                get_post_meta($member_id, '_mt_jury_position', true),
                get_post_meta($member_id, '_mt_jury_category', true),
                get_post_meta($member_id, '_mt_jury_status', true) ?: 'active',
                get_post_meta($member_id, '_mt_jury_linkedin', true),
                $assigned,
                $completed,
                $completion_rate . '%',
                $this->get_last_activity($user_id)
            );
        }
        
        // Generate CSV
        $filename = 'jury-members-' . date('Y-m-d-His') . '.csv';
        $csv_content = $this->array_to_csv($csv_data);
        
        wp_send_json_success(array(
            'filename' => $filename,
            'content' => base64_encode($csv_content)
        ));
    }
    
    /**
     * AJAX: Get jury activity log
     */
    public function ajax_get_jury_activity() {
        check_ajax_referer('mt_jury_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('mt_manage_awards')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        global $wpdb;
        
        // Get recent activity from custom table (if exists) or transient
        $activities = get_transient('mt_jury_activity_log');
        
        if ($activities === false) {
            $activities = array();
        }
        
        // Format activities for display
        $formatted_activities = array();
        foreach (array_slice($activities, -20) as $activity) {
            $formatted_activities[] = array(
                'time' => human_time_diff(strtotime($activity['timestamp'])) . ' ' . __('ago', 'mobility-trailblazers'),
                'message' => $this->format_activity_message($activity),
                'type' => $activity['type']
            );
        }
        
        wp_send_json_success(array_reverse($formatted_activities));
    }
    
    /**
     * Helper: Get assigned candidates count
     */
    private function get_assigned_candidates_count($jury_id) {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'meta_key' => '_mt_assigned_jury_member',
            'meta_value' => $jury_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        return count($candidates);
    }
    
    /**
     * Helper: Get completed evaluations count
     */
    private function get_completed_evaluations_count($user_id) {
        if (empty($user_id)) {
            return 0;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT candidate_id) FROM $table_name WHERE jury_member_id = %d",
            $user_id
        ));
    }
    
    /**
     * Helper: Get last activity
     */
    private function get_last_activity($user_id) {
        if (empty($user_id)) {
            return __('Never', 'mobility-trailblazers');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $last_evaluation = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(evaluation_date) FROM $table_name WHERE jury_member_id = %d",
            $user_id
        ));
        
        if ($last_evaluation) {
            return human_time_diff(strtotime($last_evaluation)) . ' ' . __('ago', 'mobility-trailblazers');
        }
        
        // Check last login
        $last_login = get_user_meta($user_id, 'mt_last_login', true);
        if ($last_login) {
            return human_time_diff($last_login) . ' ' . __('ago', 'mobility-trailblazers');
        }
        
        return __('Never', 'mobility-trailblazers');
    }
    
    /**
     * Helper: Create WordPress user for jury member
     */
    private function create_jury_user($email, $name) {
        $username = sanitize_user(strtolower(str_replace(' ', '', $name)));
        $password = wp_generate_password();
        
        // Ensure unique username
        $original_username = $username;
        $i = 1;
        while (username_exists($username)) {
            $username = $original_username . $i;
            $i++;
        }
        
        $user_id = wp_create_user($username, $password, $email);
        
        if (!is_wp_error($user_id)) {
            // Set user role
            $user = new WP_User($user_id);
            $user->set_role('mt_jury_member');
            
            // Update display name
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => explode(' ', $name)[0],
                'last_name' => implode(' ', array_slice(explode(' ', $name), 1))
            ));
            
            // Send password reset email
            wp_new_user_notification($user_id, null, 'user');
            
            return $user_id;
        }
        
        return false;
    }
    
    /**
     * Helper: Send invitation email
     */
    private function send_invitation_email($jury_id) {
        $email = get_post_meta($jury_id, '_mt_jury_email', true);
        $name = get_the_title($jury_id);
        
        $subject = sprintf(__('Invitation: Join the %s Jury Panel', 'mobility-trailblazers'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Dear %s,\n\nYou have been invited to join our distinguished jury panel for the Mobility Trailblazers Award.\n\nAs a jury member, you will play a crucial role in identifying and recognizing the top innovators shaping the future of mobility in the DACH region.\n\nPlease click the link below to access your jury dashboard:\n%s\n\nBest regards,\nThe Mobility Trailblazers Team", 'mobility-trailblazers'),
            $name,
            admin_url('admin.php?page=mt-jury-dashboard')
        );
        
        return wp_mail($email, $subject, $message);
    }
    
    /**
     * Helper: Send reminder email
     */
    private function send_reminder_email($jury_id) {
        $email = get_post_meta($jury_id, '_mt_jury_email', true);
        $name = get_the_title($jury_id);
        $user_id = get_post_meta($jury_id, '_mt_jury_user_id', true);
        
        $assigned = $this->get_assigned_candidates_count($jury_id);
        $completed = $this->get_completed_evaluations_count($user_id);
        $remaining = $assigned - $completed;
        
        if ($remaining <= 0) {
            return false; // No pending evaluations
        }
        
        $subject = __('Reminder: Pending Candidate Evaluations', 'mobility-trailblazers');
        
        $message = sprintf(
            __("Dear %s,\n\nThis is a friendly reminder that you have %d candidate evaluations pending.\n\nYour insights are invaluable in selecting the mobility trailblazers who will shape our future.\n\nPlease log in to complete your evaluations:\n%s\n\nThank you for your continued commitment.\n\nBest regards,\nThe Mobility Trailblazers Team", 'mobility-trailblazers'),
            $name,
            $remaining,
            admin_url('admin.php?page=mt-jury-dashboard')
        );
        
        return wp_mail($email, $subject, $message);
    }
    
    /**
     * Helper: Remove jury assignments
     */
    private function remove_jury_assignments($jury_id) {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'meta_key' => '_mt_assigned_jury_member',
            'meta_value' => $jury_id,
            'posts_per_page' => -1
        ));
        
        foreach ($candidates as $candidate) {
            delete_post_meta($candidate->ID, '_mt_assigned_jury_member', $jury_id);
        }
    }
    
    /**
     * Helper: Log activity
     */
    private function log_activity($type, $data) {
        $activities = get_transient('mt_jury_activity_log');
        
        if (!is_array($activities)) {
            $activities = array();
        }
        
        $activities[] = array(
            'type' => $type,
            'data' => $data,
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        );
        
        // Keep only last 100 activities
        if (count($activities) > 100) {
            $activities = array_slice($activities, -100);
        }
        
        set_transient('mt_jury_activity_log', $activities, WEEK_IN_SECONDS);
    }
    
    /**
     * Helper: Format activity message
     */
    private function format_activity_message($activity) {
        $user = get_userdata($activity['user_id']);
        $user_name = $user ? $user->display_name : __('System', 'mobility-trailblazers');
        
        switch ($activity['type']) {
            case 'jury_created':
                return sprintf(
                    __('%s created jury member: %s', 'mobility-trailblazers'),
                    $user_name,
                    $activity['data']['name']
                );
                
            case 'jury_updated':
                return sprintf(
                    __('%s updated jury member: %s', 'mobility-trailblazers'),
                    $user_name,
                    $activity['data']['name']
                );
                
            case 'jury_deleted':
                return sprintf(
                    __('%s deleted jury member: %s', 'mobility-trailblazers'),
                    $user_name,
                    $activity['data']['name']
                );
                
            case 'bulk_action':
                return sprintf(
                    __('%s performed bulk %s on %d jury members', 'mobility-trailblazers'),
                    $user_name,
                    $activity['data']['action'],
                    $activity['data']['count']
                );
                
            default:
                return sprintf(
                    __('%s performed action: %s', 'mobility-trailblazers'),
                    $user_name,
                    $activity['type']
                );
        }
    }
    
    /**
     * Helper: Convert array to CSV
     */
    private function array_to_csv($data) {
        $output = fopen('php://memory', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}

// Initialize the class
MT_Jury_Management_Admin::get_instance();